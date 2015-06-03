<?php

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_CPT_Tickets' ) ) {

	/**
	 * Class Rt_HD_CPT_Tickets
	 * Customise ticket CPT List view & Add/edit Post view
	 *
	 * @since  0.1
	 *
	 * @author dipesh
	 */
	class Rtbiz_HD_CPT_Tickets {

		/**
		 * Apply hook
		 *
		 * @since  0.1
		 */
		function __construct() {

			// CPT List View
			Rtbiz_HD::$loader->add_filter( 'manage_edit-' . Rtbiz_HD_Module::$post_type . '_columns', $this, 'edit_custom_columns', 20 );
			Rtbiz_HD::$loader->add_action( 'manage_' . Rtbiz_HD_Module::$post_type . '_posts_custom_column', $this, 'manage_custom_columns', 2 );
			Rtbiz_HD::$loader->add_filter( 'manage_edit-' . Rtbiz_HD_Module::$post_type . '_sortable_columns', $this, 'sortable_column' );

			// CPT Edit/Add View
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes', $this, 'remove_meta_boxes' );
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes', 30 );
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes_' . Rtbiz_HD_Module::$post_type, $this, 'metabox_rearrenge', 30 );
			Rtbiz_HD::$loader->add_action( 'save_post', $this, 'save_meta_boxes', 1, 2 );

			//metabox save
			add_action( 'wp_before_admin_bar_render', 'Rtbiz_HD_Ticket_Info::custom_post_status_rendar', 10 );

			add_action( 'pre_post_update', 'Rtbiz_HD_Ticket_Diff_Email::store_old_post_data', 1, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Ticket_Info::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Subscribers::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Attachment::save', 10, 2 );
			//          add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'Rtbiz_HD_External_Link::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Ticket_Diff_Email::save', 10, 2 );

			Rtbiz_HD::$loader->add_action( 'pre_get_posts', $this, 'pre_filter', 1 );
			Rtbiz_HD::$loader->add_action( 'untrashed_post', $this, 'after_restore_trashed_ticket' );
			Rtbiz_HD::$loader->add_action( 'before_delete_post', $this, 'before_ticket_deleted' );
			Rtbiz_HD::$loader->add_action( 'wp_trash_post', $this, 'before_ticket_trashed' );

			// Add custom view name `My Tickets`
			Rtbiz_HD::$loader->add_filter( 'views_edit-' . Rtbiz_HD_Module::$post_type, $this, 'display_custom_views' );

			// Add custom filters.
			Rtbiz_HD::$loader->add_action( 'restrict_manage_posts', $this, 'display_custom_filters' );

			Rtbiz_HD::$loader->add_action( 'edit_form_top', $this, 'append_ticket_id_to_title' );

		}

		/**
		 * Edit Column list view on Tickets List view page
		 *
		 * @param $cols
		 *
		 * @since  0.1
		 *
		 * @return array
		 */
		public function edit_custom_columns( $cols ) {
			$columns = array();

			unset( $cols['cb'] );
			unset( $cols['title'] );
			unset( $cols['comments'] );
			unset( $cols['date'] );
			unset( $cols[ 'p2p-from-'.Rtbiz_HD_Module::$post_type.'_to_'.rtbiz_get_contact_post_type() ] );
			$columns['cb'] = '<input type="checkbox" />';
			$columns['rthd_ticket_title'] = __( 'Ticket', RTBIZ_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_status'] = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', RTBIZ_HD_TEXT_DOMAIN ) . '">' . esc_attr__( 'Status', RTBIZ_HD_TEXT_DOMAIN ) . '</span>';
			$columns['rthd_ticket_customers'] = __( 'Customers', RTBIZ_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_staff'] = __( 'Staff', RTBIZ_HD_TEXT_DOMAIN );
			//			$columns['rthd_ticket_assignee'] = __( 'Assignee', RT_BIZ_HD_TEXT_DOMAIN );
			//			$columns['rthd_ticket_created_by'] = __( 'Ticket Author', RT_BIZ_HD_TEXT_DOMAIN );
			//			$columns['rthd_ticket_last_reply_by'] = __( 'Last Reply By', RT_BIZ_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_followup'] = __( 'Reply Count', RTBIZ_HD_TEXT_DOMAIN );
			//            $columns['rthd_ticket_updated_by']     = __( 'Updated By', RT_BIZ_HD_TEXT_DOMAIN );
			$columns = array_merge( $columns, $cols );
			//			$columns[ 'p2p-from-'.Rt_HD_Module::$post_type.'_to_'.rtbiz_get_contact_post_type() ] = __( 'Participants (Customers)', RT_BIZ_HD_TEXT_DOMAIN );

			return $columns;
		}

		/**
		 * Define new sortable columns for ticket list view
		 *
		 * @since 0.1
		 *
		 * @param $columns
		 *
		 * @return mixed
		 */
		function sortable_column( $columns ) {
			$columns['rthd_ticket_title'] = 'ticket';
			$columns['rthd_ticket_created_by'] = 'created_by';
			$columns['rthd_ticket_updated_by'] = 'updated_by';
			$columns['rthd_ticket_followup'] = 'comments';
			return $columns;
		}

		function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i = 0;

			if ( ! $action_count ) {
				return ''; }

			$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
			foreach ( $actions as $action => $link ) {
				++ $i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				$out .= "<span class='$action'>$link$sep</span>";
			}
			$out .= '</div>';

			return $out;
		}

		function add_gravatar_class( $class ) {
			$class = str_replace( "class='avatar", "class='avatar rthd-avatar-img", $class );
			return $class;
		}

		/**
		 * Edit Content of List view Columns
		 *
		 * @since  0.1
		 *
		 * @param $column
		 */
		function manage_custom_columns( $column ) {

			global $post, $rtbiz_hd_module,$rtbiz_hd_email_notification;

			$can_edit_post = current_user_can( 'edit_post', $post->ID );
			$post_type_object = get_post_type_object( $post->post_type );

			switch ( $column ) {

				case 'rthd_ticket_customers':
					// customers
					$create             = new DateTime( $post->post_date );
					$createdate         = $create->format( 'M d, Y h:i A' );
					$create_by_time     = esc_attr( human_time_diff( strtotime( $createdate ), current_time( 'timestamp' ) ) ) . ' ago';
					$created_by         = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
					$CCs                = $rtbiz_hd_email_notification->get_contacts( $post->ID );
					$CCs                = wp_list_pluck( $CCs, 'email' );
					$CCs                = array_diff( $CCs, array( $created_by->user_email ) );
					?>
					<div class="rthd-ticket-user-activity-backend">
						<?php
						if ( ! empty( $created_by ) ) {
							// Show ticket created by with large gravatar
							echo ' <a class="rthd-ticket-created-by" title="Created by ' . $created_by->display_name . ' ' . $create_by_time . '" href="' .  admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&created_by='.$created_by->ID ) .'">' . get_avatar( $created_by->user_email, '30' ) . '</a>';
						}
						foreach ( $CCs as $email ) {
							// show other CCs' contact
							$user         = get_user_by( 'email', $email );
							$display_name = $email;
							$url = '#';
							if ( ! empty( $user ) ) {
								$display_name = $user->display_name;
								$url = admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&created_by='.$user->ID );
							}
							echo '<a title= "' . $display_name . '" class="rthd-last-reply-by rthd-contact-avatar-no-reply"  href="' .$url . '">' . get_avatar( $email, '30' ) . ' </a>';
						}
						?>
					</div>
					<?php
					break;

				case 'rthd_ticket_staff':
					$subscriber         = $rtbiz_hd_email_notification->get_subscriber( $post->ID );
					$subscriber         = wp_list_pluck( $subscriber, 'email' );
					$assigned_to        = get_user_by( 'id', $post->post_author );
					$subscriber                = array_diff( $subscriber, array( $assigned_to->user_email ) );
					?>
					<div class="rthd-ticket-user-activity-backend">
						<?php
						if ( ! empty( $assigned_to ) ) {
							// Show ticket assignee by with large gravatar
							echo ' <a class="rthd-ticket-created-by" title="Assigned to ' . $assigned_to->display_name .'" href="' .  admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&assigned='.$assigned_to->ID ) .'">' . get_avatar( $assigned_to->user_email, '30' ) . '</a>';
						}
						foreach ( $subscriber as $email ) {
							// show other CCs' contact
							$user         = get_user_by( 'email', $email );
							$display_name = $email;
							if ( ! empty( $user ) ) {
								$display_name = $user->display_name;
							}
							echo '<a title= "' . $display_name . '" class="rthd-last-reply-by rthd-contact-avatar-no-reply"  href="' . admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&assigned='.$user->ID )  . '">' . get_avatar( $email, '30' ) . ' </a>';
						}
						?>
					</div>
					<?php
					break;

				case 'rthd_ticket_assignee':
					$user_id = $post->post_author;
					$user_info = get_userdata( $user_id );
					$query_var = array(
						'post_type' => Rtbiz_HD_Module::$post_type,
						'assigned' => $user_id,
					);
					$url = esc_url( add_query_arg( $query_var, 'edit.php' ) );
					if ( $user_info ) {
						//                      printf( "<a href='%s'>%s</a>", $url, $user_info->display_name );
						add_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
						printf( " <a href='%s'>%s <span  class='rthd_td_show'>%s</span> ", $url, get_avatar( $user_info->user_email, 25 ), $user_info->display_name );
						remove_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
					} else {
						echo '<div>' . __( 'No assignee', RTBIZ_HD_TEXT_DOMAIN ) . '</div>';
					}
					break;

				case 'rthd_ticket_followup' :
					$comment = get_comments( array( 'post_id' => $post->ID, 'number' => 1 ) );
					echo '<span class="post-com-count-wrapper"><a class="post-com-count" style="cursor: default;"><span class="comment-count">' . ( $post->comment_count) . '</span></a></span>';
					break;

				case 'rthd_ticket_last_reply_by' :
					$comment = get_comments( array( 'post_id' => $post->ID, 'number' => 1 ) );
					if ( ! empty( $comment ) ) {
						$comment = $comment[0];
						//                      echo ''.esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) )) ." ago by ". $comment->comment_author ;
						$user_info = get_user_by( 'id', $comment->user_id );
						$lastreplyby = sprintf( '<span class="created-by tips" data-tip="%s">'.__( '%s', RT_BIZ_TEXT_DOMAIN ).'</span>', $comment->comment_date, human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . __( ' ago' ) );
						if ( $user_info ) {
							add_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
							printf( "<div class='rthd-ticket-author'>%s <span class='rthd_td_show'>%s</span> <span class='rthd_td_show'>%s</span></div>", get_avatar( $user_info->user_email, 25 ), $user_info->display_name, $lastreplyby );
							remove_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
						}
					} else {
						echo '<div style="text-align: center;">' . __( 'No reply', RTBIZ_HD_TEXT_DOMAIN ) . '</div>';
					}
					break;

				case 'rthd_ticket_status':
					echo rtbiz_hd_status_markup( $post->post_status );
					break;

				case 'rthd_ticket_title' :

					if ( $can_edit_post && $post->post_status != 'trash' ) {
						printf( __( '%s  %s', RT_BIZ_TEXT_DOMAIN ), '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '"><strong>' . esc_attr( _x( '#', 'hash before order number', RT_BIZ_TEXT_DOMAIN ) . esc_attr( $post->ID ) ) . '</strong></a>', $post->post_title );
					} else {
						printf( __( '%s  %s', RT_BIZ_TEXT_DOMAIN ), '<strong>' . esc_attr( _x( '#', 'hash before order number', RT_BIZ_TEXT_DOMAIN ) . esc_attr( $post->ID ) ) . '</strong>', $post->post_title );
					}

					$user_id = $post->post_author;
					$user_info = get_userdata( $user_id );

					$query_var = array(
						'post_type' => Rtbiz_HD_Module::$post_type,
						'assigned' => $user_id,
					);

					if ( get_current_user_id() == $user_id ) {
						$query_var['post_status'] = 'assigned';
					}

					$url = esc_url( add_query_arg( $query_var, 'edit.php' ) );

					//                  if ( $user_info ) {
					//                      printf( " Assigned to <a href='%s'>%s</a>", $url, $user_info->display_name );
					//                  }

					$actions = array();
					if ( $can_edit_post && 'trash' != $post->post_status ) {
						$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item' ) ) . '">' . __( 'Edit' ) . '</a>';
						$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr( __( 'Edit this item inline' ) ) . '">' . __( 'Quick&nbsp;Edit' ) . '</a>';
					}

					if ( current_user_can( 'delete_post', $post->ID ) ) {
						if ( 'trash' == $post->post_status ) {
							$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore' ) . '</a>';
						} else {
							$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . '</a>';
						}
						if ( 'trash' == $post->post_status ) {
							$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . '</a>';
						}
					}
					if ( $post_type_object->public ) {
						if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
							if ( $can_edit_post ) {
								$preview_link = set_url_scheme( get_permalink( $post->ID ) );
								/** This filter is documented in wp-admin/includes/meta-boxes.php */
								$preview_link = apply_filters( 'preview_post_link', esc_url( add_query_arg( 'preview', 'true', $preview_link ) ), $post );
								$actions['view'] = '<a href="' . esc_url( $preview_link ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
							}
						} elseif ( 'trash' != $post->post_status ) {
							$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'View' ) . '</a>';
						}
					}

					echo $this->row_actions( $actions );

					get_inline_data( $post );

					break;

				case 'rthd_ticket_created_by':

					$date = new DateTime( get_the_date( 'Y-m-d H:i:s' ) );
					$datediff = human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) . __( ' ago' );

					$user_id = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );
					$user_info = get_userdata( $user_id );
					$url = esc_url(
						add_query_arg(
							array(
								'post_type' => Rtbiz_HD_Module::$post_type,
								'created_by' => $user_id,
									), 'edit.php' ) );

									$replyby = sprintf( __( '<span class="created-by tips" data-tip="%s">%s </span>', RT_BIZ_TEXT_DOMAIN ), get_the_date( 'd-m-Y H:i' ), $datediff );
							if ( $user_info ) {
								add_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
								printf( "<div class='rthd-ticket-author'><a href='%s'>%s <span  class='rthd_td_show'>%s</span></a> <span class='rthd_td_show'>%s</span></div>", $url, get_avatar( $user_info->user_email, 25 ), $user_info->display_name, $replyby );
								remove_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
							}
					break;

				/* case 'rthd_ticket_updated_by':

				  $date     = new DateTime( get_the_modified_date( 'Y-m-d H:i:s' ) );
				  $datediff = human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) . __( ' ago' );

				  $user_id   = get_post_meta( $post->ID, '_rtbiz_hd_updated_by', true );
				  $user_info = get_userdata( $user_id );
				  $url       = esc_url(
				  add_query_arg(
				  array(
				  'post_type'  => Rt_HD_Module::$post_type,
				  'updated_by' => $user_id,
				  ), 'edit.php' ) );

				  printf( __( '<span class="created-by tips" data-tip="%s">%s', RT_BIZ_TEXT_DOMAIN ), get_the_modified_date( 'd-m-Y H:i' ), $datediff );
				  if ( $user_info ) {
				  printf( ' by <a href="%s">%s</a>', $url, $user_info->display_name );
				  }
				  printf( '</span>' );
				  break; */
			}
		}

		/**
		 * Remove Default meta boxes on Edit post View for ticket
		 *
		 * @since  0.1
		 */
		public function remove_meta_boxes() {
			remove_meta_box( 'revisionsdiv', Rtbiz_HD_Module::$post_type, 'normal' );
			remove_meta_box( 'commentstatusdiv', Rtbiz_HD_Module::$post_type, 'normal' );
			remove_meta_box( 'slugdiv', Rtbiz_HD_Module::$post_type, 'normal' );
		}

		/**
		 * Add custom meta boxes on Edit post View for ticket
		 *
		 * @since  0.1
		 */
		public function add_meta_boxes() {
			global $post;
			if ( ! empty( $post ) && 'auto-draft' != $post->post_status ) {
				remove_post_type_support( Rtbiz_HD_Module::$post_type, 'editor' );
				add_meta_box( 'rt-hd-ticket-follow-up', __( 'Follow Up', RTBIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_Ticket_Comments::ui', Rtbiz_HD_Module::$post_type, 'normal', 'high' );
			}

			add_meta_box( 'rt-hd-ticket-data', __( 'Ticket Information', RTBIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_Ticket_Info::ui', Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-subscriiber', __( 'Participants (Staff)', RTBIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_Subscribers::ui', Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-ticket-order-history', __( 'Purchase History', RTBIZ_HD_TEXT_DOMAIN ), array( $this, 'order_history' ), Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-ticket-contacts-blacklist', __( 'Blacklist Contacts', RTBIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_Ticket_Contacts_Blacklist::ui', Rtbiz_HD_Module::$post_type, 'side', 'low' );
			add_meta_box( 'rt-hd-attachment', __( 'Attachments', RTBIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_Attachment::ui', Rtbiz_HD_Module::$post_type, 'side', 'low' );
			//          add_meta_box( 'rt-hd-external-link', __( 'Reference Links', RT_BIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_External_Link::ui', Rt_HD_Module::$post_type, 'side', 'default' );
		}

		/**
		 * up[date metabox order
		 */
		public function metabox_rearrenge() {
			global $wp_meta_boxes;
			$custom_order['submitdiv'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core']['submitdiv'];
			$custom_order['rt-hd-ticket-data'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-data'];
			$custom_order['rt-offeringdiv'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core']['rt-offeringdiv'];
			$custom_order[ 'p2p-from-' . Rtbiz_HD_Module::$post_type . '_to_' . rtbiz_get_contact_post_type() ] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default'][ 'p2p-from-' . Rtbiz_HD_Module::$post_type . '_to_' . rtbiz_get_contact_post_type() ];
			$custom_order['rt-hd-subscriiber'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-subscriiber'];
			$custom_order['rt-hd-ticket-order-history'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-order-history'];
			$custom_order[ 'p2p-any-' . Rtbiz_HD_Module::$post_type . '_to_' . Rtbiz_HD_Module::$post_type ] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default'][ 'p2p-any-' . Rtbiz_HD_Module::$post_type . '_to_' . Rtbiz_HD_Module::$post_type ];
			//$custom_order['rt-teamsdiv'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core']['rt-teamsdiv'];
			$wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core'] = $custom_order;
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-data'] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default'][ 'p2p-from-' . Rtbiz_HD_Module::$post_type . '_to_' . rtbiz_get_contact_post_type() ] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-subscriiber'] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-order-history'] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default'][ 'p2p-any-' . Rtbiz_HD_Module::$post_type . '_to_' . Rtbiz_HD_Module::$post_type ] );
		}

		/**
		 * @param $post
		 * add meta box for showing purchase history history
		 */
		function order_history( $post ) {
			add_filter( 'rtbiz_hd_ticket_purchase_history_box_wrapper_start', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_header_wrapper_start', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_heading', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_header_wrapper_end', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_box_wrapper_end', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_wrapper_start', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_wrapper_end', '__return_empty_string', 10, 1 );
			do_action( 'rtbiz_hd_user_purchase_history', $post->ID );
		}

		/**
		 * Save custom meta boxes Values on Edit post View for ticket
		 *
		 * @since  0.1
		 *
		 * @param $post_id
		 * @param $post
		 */
		public function save_meta_boxes( $post_id, $post ) {
			//global $rt_hd_module;
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

			// Check the post type
			if ( ! in_array( $post->post_type, array( Rtbiz_HD_Module::$post_type ) ) ) {
				return;
			}

			do_action( 'rt_hd_process_' . $post->post_type . '_meta', $post_id, $post );
			;
			if ( 'trash' == $post->post_status ) {

				$url = esc_url_raw( add_query_arg( array( 'post_type' => Rtbiz_HD_Module::$post_type ), admin_url( 'edit.php' ) ) );
				wp_safe_redirect( $url );
				die();
			}
		}

		/**
		 * Filter ticket list view according to user query
		 *
		 * @since 0.1
		 *
		 * @param $query
		 */
		function pre_filter( $query ) {
			if ( isset( $_GET['post_type'] ) && Rtbiz_HD_Module::$post_type == $_GET['post_type'] && $query->is_main_query() ) {
				$orderby = $query->get( 'orderby' );
				if ( isset( $_GET['contact_id'] ) ) {
					$formss = array();
					$contact_id = $_GET['contact_id'];
					global $wpdb;
					global $rtbiz_contact;
					$contact_froms = $wpdb->get_results(
						'SELECT p2p_from
                            FROM ' . $wpdb->prefix . "p2p
								WHERE p2p_type = '" . Rtbiz_HD_Module::$post_type . '_to_' . $rtbiz_contact->post_type .
					"' AND p2p_to = " . $contact_id );

					foreach ( $contact_froms as $form ) {
						$formss[] = intval( $form->p2p_from );
					}
					$query->set( 'post__in', $formss );
				}
				if ( isset( $_GET['account_id'] ) ) {
					$formss = array();
					$account_id = $_GET['account_id'];
					global $wpdb;
					global $rt_company;
					$account_froms = $wpdb->get_results(
						"SELECT p2p_from
							FROM wp_p2p
								WHERE p2p_type = '" . Rtbiz_HD_Module::$post_type . '_to_' . $rt_company->post_type .
					"' AND p2p_to = " . $account_id );

					foreach ( $account_froms as $form ) {
						$formss[] = intval( $form->p2p_from );
					}
					$query->set( 'post__in', $formss );
				}
				if ( isset( $orderby ) && ! empty( $orderby ) ) {
					switch ( $orderby ) {
						case 'ticket':
							$query->set( 'orderby', 'post_ID' );
							break;
						case 'create_by':
							$query->set( 'orderby', 'Date' );
							break;
						case 'updated_by':
							$query->set( 'orderby', 'modified' );
							break;
						case 'comments':
							$query->set( 'orderby', 'comment_count' );
							break;
					}
				} else {
					$query->set( 'orderby', 'modified' );
					$query->set( 'order', 'desc' );
				}

				if ( isset( $_GET['product_id'] ) ) {
					global $rtbiz_offerings;
					$query->set( 'tax_query', array(
						array(
							'taxonomy' => Rt_Offerings::$offering_slug,
							'field' => 'term_id',
							'terms' => $_GET['product_id'],
						),
					) );
				}

				if ( isset( $_GET['created_by'] ) ) {

					$query->set( 'meta_query', array(
						array(
							'key' => '_rtbiz_hd_created_by',
							'value' => $_GET['created_by'],
						),
					) );
				}

				if ( isset( $_GET['order-id'] ) && intval( $_GET['order-id'] ) ) {
					$query->set( 'meta_query', array(
						array(
							'key' => 'rtbiz_hd_order_id',
							'value' => $_GET['order-id'],
						),
					) );
				}

				if ( isset( $_GET['assigned'] ) ) {
					$query->set( 'author', $_GET['assigned'] );
				}

				if ( isset( $_GET['updated_by'] ) ) {

					$query->set( 'meta_query', array(
						array(
							'key' => '_rtbiz_hd_updated_by',
							'value' => $_GET['updated_by'],
						),
					) );
				}

				if ( isset( $_GET['ticket_status'] ) ) {
					$query->set( 'post_status', $_GET['ticket_status'] );
				}

				if ( isset( $_GET['ticket_assigned'] ) ) {
					$query->set( 'author', $_GET['ticket_assigned'] );
				}

				if ( isset( $_GET['favorite'] ) ) {
					$fav_ticket = rtbiz_hd_get_user_fav_ticket( get_current_user_id() );
					$query->set( 'post__in', $fav_ticket );
				}

				if ( isset( $_GET['subscribe'] ) ) {
					global $wpdb;
					//subscribe ticket
					$contacts = rtbiz_hd_get_user_subscribe_ticket( get_current_user_id() );
					$query->set( 'post__in', $contacts );
					$query->set( 'author', '' );
				} else {
					$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );
					if ( ! current_user_can( $editor_cap ) ) {
						$query->set( 'author', get_current_user_id() );
					}
				}
			}
		}

		/**
		 * update ticket status[ unanswered ] after restore from trash
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		function after_restore_trashed_ticket( $post_id ) {

			$ticket = get_post( $post_id );

			if ( $ticket->post_type == Rtbiz_HD_Module::$post_type ) {

				global $rtbiz_hd_ticket_history_model;

				$rtbiz_hd_ticket_history_model->insert(
					array(
							'ticket_id' => $post_id,
							'type' => 'post_status',
							'old_value' => 'trash',
							'new_value' => 'hd-unanswered',
							'message' => null,
							'update_time' => current_time( 'mysql' ),
							'updated_by' => get_current_user_id(),
						) );

						$ticket->post_status = 'hd-unanswered';
						wp_update_post( $ticket );
			}
		}

		/**
		 * Delete index table entry before post delete
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		function before_ticket_deleted( $post_id ) {

			if ( get_post_type( $post_id ) == Rtbiz_HD_Module::$post_type ) {

				global $rtbiz_hd_ticket_history_model;
				$ticketModel = new Rtbiz_HD_Ticket_Model();

				$ticket_index = array( 'post_id' => $post_id );
				$ticket_history = array( 'ticket_id' => $post_id );

				$rtbiz_hd_ticket_history_model->delete( $ticket_history );

				$ticketModel->delete_ticket( $ticket_index );
			}
		}

		/**
		 * update status history before ticket trashed
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		function before_ticket_trashed( $post_id ) {
			if ( get_post_type( $post_id ) == Rtbiz_HD_Module::$post_type ) {
				global $rtbiz_hd_ticket_history_model;
				$rtbiz_hd_ticket_history_model->insert(
					array(
							'ticket_id' => $post_id,
							'type' => 'post_status',
							'old_value' => get_post_status( $post_id ),
							'new_value' => 'trash',
							'message' => null,
							'update_time' => current_time( 'mysql' ),
							'updated_by' => get_current_user_id(),
						) );
			}
		}

		/**
		 * Display custom views along with CPT status
		 *
		 * @param $views
		 *
		 * @return array
		 */
		public function display_custom_views( $views ) {

			$temp_view = array();
			$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );
			$current_user_id = get_current_user_id();

			$count_user_tickets = new WP_Query(
				array(
				'posts_per_page' => -1,
				'post_type' => Rtbiz_HD_Module::$post_type,
				'post_status' => 'any',
				'author' => $current_user_id,
				'fields' => 'ids',
				)
			);

			//For Author WordPress provide mine link to view display current user post so added My ticket link only for admin/editor
			if ( current_user_can( $editor_cap ) ) {

				if ( isset( $_GET['author'] ) && ( $_GET['author'] == $current_user_id ) ) {
					$class = ' class="current"'; } else { 					$class = ''; }
				$temp_view['mine'] = "<a href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "&author=$current_user_id' $class>" . sprintf( _nx( 'Mine <span class="count">(%s)</span>', 'Mine <span class="count">(%s)</span>', $count_user_tickets->found_posts, RTBIZ_HD_TEXT_DOMAIN ), number_format_i18n( $count_user_tickets->post_count ) ) . '</a>';
			} else {
				unset( $views['all'] );
			}

			$fav_ticket = rtbiz_hd_get_user_fav_ticket( $current_user_id );
			if ( ! empty( $fav_ticket ) ) {
				if ( isset( $_GET['favorite'] ) ) {
					$class = ' class="current"'; } else { 					$class = ''; }
				$temp_view['favorite_ticket'] = "<a href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "&favorite=true' $class>" . sprintf( _nx( 'Favorite <span class="count">(%s)</span>', 'Favorites <span class="count">(%s)</span>', count( $fav_ticket ), RTBIZ_HD_TEXT_DOMAIN ), number_format_i18n( count( $fav_ticket ) ) ) . '</a>';
			}

			$contacts = rtbiz_hd_get_user_subscribe_ticket( get_current_user_id() );
			if ( ! empty( $contacts ) ) {
				if ( isset( $_GET['subscribed'] ) ) {
					$class = ' class="current"';
				} else {
					$class = '';
				}
				$temp_view['subscribe_ticket'] = "<a href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "&subscribed=true' $class>" . sprintf( _nx( 'Subscribed <span class="count">(%s)</span>', 'Subscribed <span class="count">(%s)</span>', count( $fav_ticket ), RTBIZ_HD_TEXT_DOMAIN ), number_format_i18n( count( $contacts ) ) ) . '</a>';
			}

			//remove count for editor
			if ( ! current_user_can( $editor_cap ) ) {
				foreach ( $views as $key => $view ) {
					$views[ $key ] = preg_replace( '#<span class=["\']count["\']>(.*?)</span>#', '', $view );
				}
			}
			$views = $temp_view + $views;
			if ( isset( $views['trash'] ) ) {
				$trash = $views['trash'];
				unset( $views['trash'] );
				$views['trash'] = $trash;
			}

			return $views;
		}

		/**
		 * Display custom filters to filter out tickets.
		 */
		public function display_custom_filters() {
			global $typenow, $rtbiz_hd_module, $rtbiz_offerings, $rt_hd_rt_attributes;

			if ( Rtbiz_HD_Module::$post_type == $typenow ) {

				// Filter by status
				echo '<label class="screen-reader-text" for="ticket_status">' . __( 'Filter by status' ) . '</label>';

				$statuses = $rtbiz_hd_module->get_custom_statuses();

				echo '<select id="ticket_status" class="postform" name="ticket_status">';
				echo '<option value="0">Select Status</option>';

				foreach ( $statuses as $status ) {
					if ( isset( $_GET['ticket_status'] ) && $status['slug'] == $_GET['ticket_status'] ) {
						echo '<option value="' . $status['slug'] . '" selected="selected">' . $status['name'] . '</option>';
					} else {
						echo '<option value="' . $status['slug'] . '">' . $status['name'] . '</option>';
					}
				}
				echo '</select>';

				// Filter by assignee
				echo '<label class="screen-reader-text" for="ticket_assigned">' . __( 'Filter by assignee' ) . '</label>';

				$ticket_authors = Rtbiz_HD_Utils::get_hd_rtcamp_user();

				echo '<select id="ticket_assigned" class="postform" name="ticket_assigned">';
				echo '<option value="0">Select Assignee</option>';

				foreach ( $ticket_authors as $author ) {
					if ( isset( $_GET['ticket_assigned'] ) && $author->ID == $_GET['ticket_assigned'] ) {
						echo '<option value="' . $author->ID . '" selected="selected">' . $author->display_name . '</option>';
					} else {
						echo '<option value="' . $author->ID . '">' . $author->display_name . '</option>';
					}
				}

				echo '</select>';

				// Filter by offering
				$products = array();
				if ( isset( $rtbiz_offerings ) ) {
					$products = get_terms( Rt_Offerings::$offering_slug, array( 'hide_empty' => 0 ) );
				}

				if ( ! empty( $products ) ) {
					echo '<label class="screen-reader-text" for="rt_offering">' . __( 'Filter by offering' ) . '</label>';

					echo '<select id="rt_offering" class="postform" name="rt-offering">';
					echo '<option value="0">Select Offering</option>';

					foreach ( $products as $product ) {
						if ( isset( $_GET['rt-offering'] ) && $product->slug == $_GET['rt-offering'] ) {
							echo '<option value="' . $product->slug . '" selected="selected">' . $product->name . '</option>';
						} else {
							echo '<option value="' . $product->slug . '">' . $product->name . '</option>';
						}
					}

					echo '</select>';
				}
				$attrs = rtbiz_hd_get_attributes( Rtbiz_HD_Module::$post_type );
				foreach ( $attrs as $attr ) {
					if ( ! empty( $attr->attribute_store_as ) && 'taxonomy' == $attr->attribute_store_as ) {
						$attr_tax = $rt_hd_rt_attributes->get_taxonomy_name( $attr->attribute_name );
						$attr_terms = get_terms( $attr_tax, array( 'hide_empty' => false ) );
						if ( ! empty( $attr_terms ) ) {
							echo '<label class="screen-reader-text" for="' . $attr_tax . '">' . __( 'Filter by '.$attr->attribute_label ) . '</label>';

							echo '<select id="' . $attr->attribute_name . '" class="postform" name="' . $attr_tax . '">';
							echo '<option value="0">Select ' . $attr->attribute_label . '</option>';

							foreach ( $attr_terms as $terms ) {
								if ( isset( $_GET[ $attr_tax ] ) && $terms->slug == $_GET[ $attr_tax ] ) {
									echo '<option value="' . $terms->slug . '" selected="selected">' . $terms->name . '</option>';
								} else {
									echo '<option value="' . $terms->slug . '">' . $terms->name . '</option>';
								}
							}
							echo '</select>';
						}
					}
				}
			}
		}

		/**
		 * @param $post
		 * Add ticket id on edit ticket
		 */
		function append_ticket_id_to_title( $post ) {
			if ( ! empty( $post ) && $post->post_type == Rtbiz_HD_Module::$post_type ) {
				echo '<h2>[#' . $post->ID . '] </h2>';
			}
		}

	}

}
