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

if ( ! class_exists( 'RT_Meta_Box_Ticket_Info' ) ) {
	class RT_Meta_Box_Ticket_Info {

		/**
		 * Metabox Ui for ticket info
		 *
		 * @since 0.1
		 */
		public static function ui( $post ) {

			global $rt_hd_module, $rt_hd_attributes;
			$labels    = $rt_hd_module->labels;
			$post_type = Rt_HD_Module::$post_type;

			$create = new DateTime( $post->post_date );

			$modify     = new DateTime( $post->post_modified );
			$createdate = $create->format( 'M d, Y h:i A' );
			$modifydate = $modify->format( 'M d, Y h:i A' );

			$post_author = $post->post_author;

			$rtcamp_users = Rt_HD_Utils::get_hd_rtcamp_user(); ?>

			<style type="text/css">
				.hide {
					display: none;
				}
			</style>

			<style type="text/css">
				#minor-publishing-actions, #misc-publishing-actions {
					display: none
				}
			</style>

			<div class="row_group">
				<span class="prefix" title="<?php _e( 'Assigned To', RT_HD_TEXT_DOMAIN ); ?>"><label
						for="post[post_author]"><strong><?php _e( 'Assigned To' ); ?></strong></label></span> <select
					name="post[post_author]"><?php
			if ( ! empty( $rtcamp_users ) ) {
				foreach ( $rtcamp_users as $author ) {
					if ( $author->ID == $post_author ) {
						$selected = ' selected';
					} else {
						$selected = ' ';
					}
					echo '<option value="' . esc_attr( $author->ID ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $author->display_name ) . '</option>';
				}
			} ?>
				</select>
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Status' ); ?>"><label><strong><?php _e( 'Status', RT_HD_TEXT_DOMAIN ); ?></strong></label></span><?php
			$pstatus = '';
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
				echo '<option selected="selected" value="' . esc_attr( $pstatus ) . '">' . esc_attr( $pstatus ) . '</option>';
			} ?>
				</select>
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Create Date', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Create Date', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>
				<input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
				       value="<?php echo esc_attr( ( isset( $createdate ) ) ? $createdate : '' ); ?>"
				       title="<?php echo esc_attr( ( isset( $createdate ) ) ? $createdate : '' ); ?>"> <input
					name="post[post_date]" type="hidden"
					value="<?php echo esc_attr( ( isset( $createdate ) ) ? $createdate : '' ); ?>"/>
			</div>

			<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Modify Date', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Modify Date', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>
				<input class="moment-from-now" type="text" placeholder="Modified on Date"
				       value="<?php echo esc_attr( $modifydate ); ?>" title="<?php echo esc_attr( $modifydate ); ?>"
				       readonly="readonly">
			</div>

			<?php
			$rthd_unique_id = get_post_meta( $post->ID, '_rtbiz_hd_unique_id', true );
			if ( ! empty( $rthd_unique_id ) ) {
				?>
				<div class="row_group">
				<span class="prefix"
				      title="<?php _e( 'Public URL', RT_HD_TEXT_DOMAIN ); ?>"><label><strong><?php _e( 'Public URL', RT_HD_TEXT_DOMAIN ); ?></strong></label></span>

				<div class="rthd_attr_border">
					<a class="rthd_public_link" target="_blank"
					   href="<?php echo esc_url( trailingslashit( site_url() ) . strtolower( $labels['name'] ) . '/' . $rthd_unique_id ); ?>"><?php _e( 'Link' ); ?></a>
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
		 * @since 0.1
		 */
		public static function save( $post_id, $post ) {

			global $rt_hd_tickets_operation;

			$newTicket = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] = 'inline-save' ) ? get_post( $_REQUEST['post_ID'] ) : $_POST['post'];
			$newTicket = ( array ) $newTicket;

			//Create Date
			$creationdate = $newTicket['post_date'];
			if ( isset( $creationdate ) && $creationdate != '' ) {
				try {
					$dr                         = date_create_from_format( 'Y-m-d H:i:s', $creationdate );
					$timeStamp                  = $dr->getTimestamp();
					$newTicket['post_date']     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
					$newTicket['post_date_gmt'] = get_gmt_from_date( $dr->format( 'Y-m-d H:i:s' ) );
				} catch ( Exception $e ) {
					$newTicket['post_date']     = current_time( 'mysql' );
					$newTicket['post_date_gmt'] = gmdate( 'Y-m-d H:i:s' );
				}
			} else {
				$newTicket['post_date']     = current_time( 'mysql' );
				$newTicket['post_date_gmt'] = gmdate( 'Y-m-d H:i:s' );
			}

			$postArray = array(
				'ID'            => $post_id,
				'post_author'   => $newTicket['post_author'],
				'post_date'     => $newTicket['post_date'],
				'post_date_gmt' => $newTicket['post_date_gmt'],
			);

			$dataArray = array(
				'assignee'     => $postArray['post_author'],
				'post_content' => $post->post_content,
				'post_status'  => $post->post_status,
				'post_title'   => $post->post_title,
			);

			$rt_hd_tickets_operation->ticket_default_field_update( $postArray, $dataArray, $post->post_type, $post_id );
			$rt_hd_tickets_operation->ticket_attribute_update( $newTicket, $post->post_type, $post_id, 'meta' );

		}

		public static function custom_post_status_rendar() {
			global $post, $pagenow, $rt_hd_module;
			$flag = false;
			if ( isset( $post ) && ! empty( $post ) && $post->post_type === Rt_HD_Module::$post_type ) {
				if ( 'edit.php' == $pagenow || 'post-new.php' == $pagenow ) {
					$flag = true;
				}
			}
			if ( isset( $post ) && ! empty( $post ) && 'post.php' == $pagenow && get_post_type( $post->ID ) === Rt_HD_Module::$post_type ) {
				$flag = true;
			}
			if ( $flag ) {
				$option      = '';
				$post_status = $rt_hd_module->get_custom_statuses();
				foreach ( $post_status as $status ) {
					if ( $post->post_status == $status['slug'] ) {
						$complete = " selected='selected'";
					} else {
						$complete = '';
					}
					$option .= "<option value='" . $status['slug'] . "' " . $complete . '>' . $status['name'] . '</option>';
				}

				echo '<script>
                        jQuery(document).ready(function($) {
                            $("select#post_status").html("'. $option .'");
                            $(".inline-edit-status select").html("'. $option .'");

                            $(document).on("change","#rthd_post_status",function(){
                                $("#post_status").val($(this).val());
                            });
                               });
                        </script>';
			}
		}
	}
}