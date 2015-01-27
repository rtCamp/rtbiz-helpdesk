<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
* To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

/**
 * Description of RT_HD_Admin_Meta_Boxes
 *
 * @since rt-Helpdesk 0.1
 */

if ( ! class_exists( 'RT_Ticket_Diff_Email' ) ) {
	class RT_Ticket_Diff_Email {

		/**
		 * it stores different of new data and old data on ticket update.
		 *
		 * @param $post_id
		 * @param $post
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function store_old_post_data( $post_id, $post ) {
			global $rt_hd_ticket_history_model, $rt_hd_module, $rt_hd_attributes, $rt_ticket_email_content;

			// $post_id and $post are required
			if ( empty( $post_id ) || empty( $post ) ) {
				return;
			}

			// Dont' save meta boxes for revisions or autosaves
			if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
			if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
				return;
			}

			// Check user has permission to edit
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if ( ! in_array( $post['post_type'], array( Rt_HD_Module::$post_type ) ) ) {
				return;
			}

			$rt_ticket_email_content = array();

			$oldpost   = get_post( $post_id );
			$newTicket = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] = 'inline-save' ) ? get_post( $_REQUEST['post_ID'] ) : $_POST['post'];
			$newTicket = ( array ) $newTicket;

			$emailHTML = $diff = '';

			// Title Diff
			$diff = rthd_text_diff( $oldpost->post_title, $_POST['post_title'] );
			if ( $diff ) {
				$emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Title</th><td>' . $diff . '</td><td></td></tr>';
			}

			// Status Diff
			$post_statuses = $rt_hd_module->get_custom_statuses();
			$old_status = ucfirst( $oldpost->post_status );
			$new_status = ucfirst( $_POST['post_status'] );
			foreach ( $post_statuses as $status ) {
				if ( ucfirst( $status['slug'] ) == $old_status ) {
					$old_status = $status['name'];
				}
				if ( ucfirst( $status['slug'] ) == $new_status ) {
					$new_status = $status['name'];
				}
			}
			$diff = rthd_text_diff( $old_status, $new_status );
			if ( $diff ) {
				$emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Status </th><td>' . $diff . '</td><td></td></tr>';
				/* Insert History for status */
				$id = $rt_hd_ticket_history_model->insert(
					array(
						'ticket_id'   => $post_id,
						'type'        => 'post_status',
						'old_value'   => $oldpost->post_status,
						'new_value'   => $_POST['post_status'],
						'update_time' => current_time( 'mysql' ),
						'updated_by'  => get_current_user_id(),
					) );
			}

			// Author
			$oldUser = get_user_by( 'id', $oldpost->post_author );
			$newUser = get_user_by( 'id', $newTicket['post_author'] );

			$diff = rthd_text_diff( ( ( ! empty( $oldUser->display_name ) ) ? $oldUser->display_name : '-NA-' ), ( ( ! empty( $newUser->display_name ) ) ? $newUser->display_name : '-NA-' ) );
			if ( $diff ) {
				$emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Assigned To</th><td>' . $diff . '</td><td></td></tr>';
			}

			//Content
			$diff = rthd_text_diff( strip_tags( $oldpost->post_content ), strip_tags( $_POST['post_content'] ) );
			if ( $diff ) {
				$emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Ticket Content</th><td>' . $diff . '</td><td></td></tr>';
			}

			// Attachments Diff
			if ( isset( $_POST['attachment'] ) ) {
				$old_attachments       = get_posts(
					array(
						'post_parent'    => $post_id,
						'post_type'      => 'attachment',
						'fields'         => 'ids',
						'posts_per_page' => - 1,
					) );
				$new_attachments       = $_POST['attachment'];
				$old_attachments_title = array();
				foreach ( $old_attachments as $attachment ) {
					$old_attachments_title[] = get_the_title( $attachment );
				}
				$old_attachments_str   = implode( ' , ', $old_attachments_title );
				$new_attachments_title = array();
				foreach ( $new_attachments as $attachment ) {
					$new_attachments_title[] = get_the_title( $attachment );
				}
				$new_attachments_str = implode( ' , ', $new_attachments_title );
				$diff                = rthd_text_diff( $old_attachments_str, $new_attachments_str );
				if ( $diff ) {
					$emailHTML .= '<tr><th style="padding: .5em;border: 0;">Attachments</th><td>' . $diff . '</td><td></td></tr>';
				}
			}

			// External File Links Diff
			if ( isset( $_POST['ticket_ex_files'] ) ) {
				$old_ex_files = get_post_meta( $post_id, '_rtbiz_hd_external_file' );
				$new_ex_files = $_POST['ticket_ex_files'];
				$old_ex_links = array();
				$new_ex_links = array();
				foreach ( $old_ex_files as $ex_file ) {
					$data           = json_decode( $ex_file );
					$old_ex_links[] = '<a href="' . $data->link . '" target="_blank">' . $data->title . '</a>';
				}
				foreach ( $new_ex_files as $ex_file ) {
					if ( empty( $ex_file['title'] ) ) {
						$ex_file['title'] = $ex_file['link'];
					}
					$new_ex_links[] = '<a href="' . $ex_file['link'] . '" target="_blank">' . $ex_file['title'] . '</a>';
				}
				$diff = rthd_text_diff( implode( ' , ', $old_ex_links ), implode( ' , ', $new_ex_links ) );
				if ( $diff ) {
					$emailHTML .= '<tr><th style="padding: .5em;border: 0;">External Files Links</th><td>' . $diff . '</td><td></td></tr>';
				}
			}

			// Attributes-meta Diff
			$attributes = rthd_get_attributes( Rt_HD_Module::$post_type, 'meta' );
			foreach ( $attributes as $attr ) {
				$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $newTicket );
				$emailHTML .= $attr_diff;
			}

			// Attributes-taxonomies Diff
			$attributes = rthd_get_attributes( Rt_HD_Module::$post_type, 'taxonomy' );
			foreach ( $attributes as $attr ) {
				$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $_POST['tax_input'] );
				$emailHTML .= $attr_diff;
			}

			//UnSubscribers List
			$oldSubscriberArr = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );
			if ( ! $oldSubscriberArr ) {
				$oldSubscriberArr = array();
			}

			$removedSUbscriber  = array_diff( $oldSubscriberArr, $_POST['subscribe_to'] );
			$newAddedSubscriber = array_diff( $_POST['subscribe_to'], $oldSubscriberArr );

			$newSubscriberList = array();
			$oldSubscriberList = array();
			$bccemails         = array();
			foreach ( $_POST['subscribe_to'] as $emailsubscriber ) {
				$userSub     = get_user_by( 'id', intval( $emailsubscriber ) );
				if ( ! empty( $userSub ) ) {
					$bccemails[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
					if ( in_array( $emailsubscriber, $newAddedSubscriber ) ) {
						$newSubscriberList[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
					}
				}
			}

			if ( ! empty( $removedSUbscriber ) ) {
				foreach ( $removedSUbscriber as $emailsubscriber ) {
					$userSub             = get_user_by( 'id', intval( $emailsubscriber ) );
					if ( ! empty( $userSub->use ) ) {
						$oldSubscriberList[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
					}
				}
			}

			$rt_ticket_email_content['oldUser']                   = $oldpost->post_author;
			$rt_ticket_email_content['newUser']                   = $newTicket['post_author'];
			$rt_ticket_email_content['bccemails']                 = $bccemails;
			$rt_ticket_email_content['newSubscriberList']         = $newSubscriberList;
			$rt_ticket_email_content['oldSubscriberList']         = $oldSubscriberList;
			$rt_ticket_email_content['emailHTML']                 = $emailHTML;
		}

		/**
		 * Diff Email between new ticket data & old ticket data -- can be new function
		 * Save meta box data
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function save( $post_id, $post ) {
			global $rt_ticket_email_content, $rt_hd_module, $rt_hd_email_notification;

			$emailTable        = "<table style='width: 100%; border-collapse: collapse; border: none;'>";
			$emailTableEnd     = "</table>";
			$updateFlag        = true;
			$oldUser           = $rt_ticket_email_content['oldUser'];
			$newUser           = $rt_ticket_email_content['newUser'];
			$newSubscriberList = $rt_ticket_email_content['newSubscriberList'];
			$oldSubscriberList = $rt_ticket_email_content['oldSubscriberList'];
			$emailHTML         = $rt_ticket_email_content['emailHTML'];
			$bccemails         = $rt_ticket_email_content['bccemails'];
			$labels        = $rt_hd_module->labels;

			if ( $updateFlag ) {
				if ( $oldUser != $newUser ) {
					$rt_hd_email_notification->notification_new_ticket_assigned( $post_id, $newUser, $labels['name'] );
					$rt_hd_email_notification->notification_new_ticket_reassigned( $post_id, $oldUser, $newUser, $labels['name'], array() );
				}

				if ( ! empty( $newSubscriberList ) ) {
					$rt_hd_email_notification->notification_ticket_subscribed( $post_id, $labels['name'], $newSubscriberList );
				}

				if ( ! empty( $oldSubscriberList ) ) {
					$rt_hd_email_notification->notification_ticket_unsubscribed( $post_id, $labels['name'], $oldSubscriberList );
				}

				if ( $emailHTML != '' && ! empty( $bccemails ) ) {
					$emailHTML = $emailTable . $emailHTML . $emailTableEnd;
					$rt_hd_email_notification->notification_ticket_updated( $post_id, $labels['name'], $emailHTML, $bccemails );
				}
				else {
					if ( $emailHTML != '' ) {
						$emailHTML = $emailTable . $emailHTML . $emailTableEnd;
						$rt_hd_email_notification->notification_ticket_updated( $post_id, $labels['name'], $emailHTML, array() );
					}
				}
			} else {
				$newUser = get_user_by( 'id', $post['post_author'] );
				if ( $newUser ) {
					$rt_hd_email_notification->notification_new_ticket_assigned( $post_id, $newUser, $labels['name'] );
				}
				if ( ! empty( $bccemails ) ) {
					$rt_hd_email_notification->notification_ticket_subscribed( $post_id, $labels['name'], $bccemails );
				}
				else {
					$rt_hd_email_notification->ticket_created_notification( $post_id, $labels['name'], array() );
				}
			}
		}
	}
}