<?php
/**
 * Don't load this file directly!
 */
if ( !defined( 'ABSPATH' ) ) {
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

if ( !class_exists( 'RT_Meta_Box_Ticket_Info' ) ) {
	class RT_Meta_Box_Ticket_Info {

		/**
		 * Output the metabox
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function ui( $post ) {

			global $rt_hd_module, $rt_hd_closing_reason, $rt_hd_attributes;
			$labels    = $rt_hd_module->labels;
			$post_type = Rt_HD_Module::$post_type;

			$create = new DateTime( $post->post_date );

			$modify     = new DateTime( $post->post_modified );
			$createdate = $create->format( "M d, Y h:i A" );
			$modifydate = $modify->format( "M d, Y h:i A" );

			$post_author = $post->post_author;

			$close_date_meta = get_post_meta( $post->ID, '_rtbiz_hd_closing_date', true );
			if ( !empty( $close_date_meta ) ) {
				$closingdate = new DateTime( $close_date_meta );
				$closingdate = $closingdate->format( 'M d, Y h:i A' );
			} else {
				$closingdate = '';
			}
			$rtcamp_users = Rt_HD_Utils::get_hd_rtcamp_user(); ?>

			<style type="text/css">
				#minor-publishing-actions, #misc-publishing-actions, #visibility, #delete-action {
					display: none
				}

				.hide {
					display: none;
				}
			</style>

			<div class="row_group">
				<span class="prefix" title="<?php _e( 'Assigned To', RT_HD_TEXT_DOMAIN ); ?>"><label
						for="post[post_author]"><strong><?php _e( 'Assigned To' ); ?></strong></label></span>
				<select name="post[post_author]"><?php
					if ( !empty( $rtcamp_users ) ) {
						foreach ( $rtcamp_users as $author ) {
							if ( $author->ID == $post_author ) {
								$selected = " selected";
							} else {
								$selected = " ";
							}
							echo '<option value="' . $author->ID . '"' . $selected . '>' . $author->display_name . '</option>';
						}
					} ?>
				</select>
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Status' ); ?>"><label><strong><?php _e( 'Status', RT_HD_TEXT_DOMAIN ); ?></strong></label></span><?php
				$pstatus = "";
				if ( isset( $post->ID ) ) {
					$pstatus = $post->post_status;
				}
				$post_status = $rt_hd_module->get_custom_statuses();

				$default_wp_status = array( 'auto-draft', 'draft' );
				if ( in_array( $pstatus, $default_wp_status ) ) {
					$pstatus = $post_status[0]['slug'];
				}
				$custom_status_flag = true;?>
				<select id="rthd_post_status" class="right" name="post_status"><?php
					foreach ( $post_status as $status ) {
						if ( $status['slug'] == $pstatus ) {
							$selected           = 'selected="selected"';
							$custom_status_flag = false;
						} else {
							$selected = '';
						}
						printf( '<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name'] );
					}
					if ( $custom_status_flag && isset( $post->ID ) ) {
						echo '<option selected="selected" value="' . $pstatus . '">' . $pstatus . '</option>';
					} ?>
				</select>
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Create Date', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Create Date', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>
				<input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
				       value="<?php echo ( isset( $createdate ) ) ? $createdate : ''; ?>"
				       title="<?php echo ( isset( $createdate ) ) ? $createdate : ''; ?>">
				<input name="post[post_date]" type="hidden"
				       value="<?php echo ( isset( $createdate ) ) ? $createdate : ''; ?>"/>
				<!--<span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar">[]</label></span>-->
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Modify Date', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Modify Date', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>
				<input class="moment-from-now" type="text" placeholder="Modified on Date"
				       value="<?php echo $modifydate; ?>"
				       title="<?php echo $modifydate; ?>" readonly="readonly">
				<!--<span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar">[]</label></span>-->
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Closing Date', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Closing Date', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>
				<input class="datepicker moment-from-now" type="text" placeholder="Select Closing Date"
				       value="<?php echo ( isset( $closingdate ) ) ? $closingdate : ''; ?>"
				       title="<?php echo ( isset( $closingdate ) ) ? $closingdate : ''; ?>">
				<input name="post[closing-date]" type="hidden"
				       value="<?php echo ( isset( $closingdate ) ) ? $closingdate : ''; ?>"/>
				<!--<span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar">[]</label></span>-->
			</div>

			<div class="row_group">
			<span class="prefix"
			      title="<?php _e( 'Closing Reason', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Closing Reason', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>
			<?php $rt_hd_closing_reason->get_closing_reasons( ( isset( $post->ID ) ) ? $post->ID : '', true ); ?>
			</div><?php

			$rthd_unique_id = get_post_meta( $post->ID, '_rtbiz_hd_unique_id', true );
			if ( !empty( $rthd_unique_id ) ) {
				?>
				<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Public URL', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Public URL', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>

				<div class="rthd_attr_border">
					<a class="rthd_public_link" target="_blank"
					   href="<?php echo trailingslashit( site_url() ) . strtolower( $labels['name'] ) . '/?rthd_unique_id=' . $rthd_unique_id; ?>"><?php _e( 'Link' ); ?></a>
				</div>
				</div><?php
			}

			$meta_attributes = rthd_get_attributes( $post_type, 'meta' );
			foreach ( $meta_attributes as $attr ) {
				?>
				<div class="row_group"><?php
				$rt_hd_attributes->render_meta( $attr, isset( $post->ID ) ? $post->ID : '', true ); ?>
				</div><?php
			}

			do_action( 'rt_hd_after_ticket_information', $post );

		}

		/**
		 * Save meta box data
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function save( $post_id, $post ) {
			global $rt_hd_admin_meta_boxes, $ticketModel, $rt_hd_closing_reason, $rt_hd_attributes, $rt_ticket_email_content, $rt_hd_ticket_history_model;

			$closing_reason_history_id = $rt_ticket_email_content['closing_reason_history_id'];

			$newTicket   = $_POST['post']; //post data
			$ticketModel = new Rt_HD_Ticket_Model();

			//Create Date
			$creationdate = $newTicket['post_date'];
			if ( isset( $creationdate ) && $creationdate != '' ) {
				try {
					$dr  = date_create_from_format( 'M d, Y H:i A', $creationdate );
					$UTC = new DateTimeZone( 'UTC' );
					$dr->setTimezone( $UTC );
					$timeStamp                  = $dr->getTimestamp();
					$newTicket['post_date']     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
					$newTicket['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
				} catch ( Exception $e ) {
					$newTicket['post_date']     = current_time( 'mysql' );
					$newTicket['post_date_gmt'] = gmdate( 'Y-m-d H:i:s' );
				}
			} else {
				$newTicket['post_date']     = current_time( 'mysql' );
				$newTicket['post_date_gmt'] = gmdate( 'Y-m-d H:i:s' );
			}

			// Post Data to be saved.
			$newpost = array(
				'post_author'   => $newTicket['post_author'],
				'post_date'     => $newTicket['post_date'],
				'post_date_gmt' => $newTicket['post_date_gmt'],
			);
			$newpost = array_merge( $newpost, array( 'ID' => $post_id ) );

			// unhook this function so it doesn't loop infinitely
			remove_action( 'save_post', array( $rt_hd_admin_meta_boxes, 'save_meta_boxes' ), 1, 2 );
			remove_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );

			// update the post, which calls save_post again
			@wp_update_post( $newpost );

			// re-hook this function
			add_action( 'save_post', array( $rt_hd_admin_meta_boxes, 'save_meta_boxes' ), 1, 2 );
			add_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );

			/* Update Index Table */
			$data = array(
				'assignee'     => $newpost['post_author'],
				'post_content' => $post->post_content,
				'post_status'  => $post->post_status,
				'post_title'   => $post->post_title,
			);

			//closing date
			if ( isset( $newTicket['closing-date'] ) && !empty( $newTicket['closing-date'] ) ) {
				update_post_meta( $post_id, '_rtbiz_hd_closing_date', $newTicket['closing-date'] );
				update_post_meta( $post_id, '_rtbiz_hd_closed_by', get_current_user_id() );
				$cd  = new DateTime( $newTicket['closing-date'] );
				$UTC = new DateTimeZone( 'UTC' );
				$cd->setTimezone( $UTC );
				$timeStamp = $cd->getTimestamp();
				$data      = array_merge( $data, array(
					'date_closing'     => gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ),
					'date_closing_gmt' => gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) ),
					'user_closed_by'   => get_current_user_id(),
				) );

			}

			//closing_reason
			if ( isset( $newTicket['closing_reason'] ) && !empty( $newTicket['closing_reason'] ) ) {
				$rt_hd_closing_reason->save_closing_reason( $post_id, $newTicket );
				$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( 'closing-reason' ) );
				$attr_val  = ( !isset( $newTicket['closing_reason'] ) ) ? array() : $newTicket['closing_reason'];
				$data      = array_merge( $data, array(
					$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
				) );
				$terms     = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( 'closing-reason' ) );
				$message   = '';
				foreach ( $terms as $term ) {
					if ( empty( $message ) ) {
						$message .= $term->name;
					} else {
						$message .= ( ' , ' . $term->name );
					}
				}
				if ( $closing_reason_history_id ) {
					$rt_hd_ticket_history_model->update( array( 'message' => $message ), array( 'id' => $closing_reason_history_id ) );
				}
			}

			//attributes- mata store
			$meta_attributes = rthd_get_attributes( $post->post_type, 'meta' );
			foreach ( $meta_attributes as $attr ) {
				$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $newTicket );
				if ( !empty( $attr_diff ) ) {
					$rt_hd_attributes->save_attributes( $attr, $post_id, $newTicket );
					/* Update Index Table */
					$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
					$attr_val  = ( !isset( $newTicket[$attr->attribute_name] ) ) ? array() : $newTicket[$attr->attribute_name];
					$data      = array_merge( $data, array(
						$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
					) );
					$where     = array( 'post_id' => $post_id );
					$ticketModel->update_ticket( $data, $where );
				}
			}

			//created by
			update_post_meta( $post_id, '_rtbiz_hd_updated_by', get_current_user_id() );
			$data = array_merge( $data, array(
				'date_update'     => current_time( 'mysql' ),
				'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'user_updated_by' => get_current_user_id(),
			) );

			//Unique link
			$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
			if ( empty( $unique_id ) ) {
				$d   = new DateTime( $newTicket['post_date'] );
				$UTC = new DateTimeZone( "UTC" );
				$d->setTimezone( $UTC );
				$timeStamp     = $d->getTimestamp();
				$post_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
				$unique_id     = md5( 'rthd_' . $post->post_type . '_' . $post_date_gmt );
				update_post_meta( $post_id, '_rtbiz_hd_unique_id', $unique_id );
			}

			if ( $ticketModel->is_exist( $post_id ) ) {
				$where = array( 'post_id' => $post_id );
				$ticketModel->update_ticket( $data, $where );
			} else {
				update_post_meta( $post_id, '_rtbiz_hd_created_by', get_current_user_id() );
				$data = array_merge( $data, array(
					'user_created_by' => get_current_user_id(),
					'date_create'     => $newpost['post_date'],
					'date_create_gmt' => $newpost['post_date_gmt'],
					'post_id'         => $post_id,
				) );

				$data = array_merge( $data, array( 'post_id' => $post_id ) );
				$ticketModel->add_ticket( $data );

			}
		}
	}
}