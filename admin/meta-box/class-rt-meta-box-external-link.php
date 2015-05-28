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

if ( ! class_exists( 'RT_Meta_Box_External_Link' ) ) {
	class RT_Meta_Box_External_Link {

		/**
		 * Output the metabox
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function ui( $post ) {

			$post_type = $post->post_type;?>
			<div id="external-files-container"><?php
			if ( isset( $post->ID ) ) {
				$ticket_ex_files = get_post_meta( $post->ID, '_rtbiz_hd_external_file' );
				$count           = 1;
				foreach ( $ticket_ex_files as $ex_file ) {
					$ex_file = (array) json_decode( $ex_file );?>
					<div class="row_group">
					<button class="delete_row removeMeta"><i class="foundicon-minus"></i>X</button>
					<input type="text" name="ticket_ex_files[<?php echo esc_attr( $count ); ?>'][title]"
					       value="<?php echo esc_attr( $ex_file['title'] ); ?>"/> <input type="text"
					                                                                     name="ticket_ex_files[<?php echo esc_attr( $count ) ?>'][link]"
					                                                                     value="<?php echo esc_url( $ex_file['link'] ); ?>"/>
					</div><?php
					$count ++;
				}
			}?>
			</div>

			<div class="row_group ">
			<input type="text" id='add_ex_file_title' placeholder="Title"/> <input type="text" id='add_ex_file_link'
			                                                                       placeholder="Link"/>
			<button id="add_new_ex_file" class="button" type="button"><?php _e( 'Add', RT_BIZ_HD_TEXT_DOMAIN ); ?></button>
			</div><?php
		}

		/**
		 * Save meta box data
		 *
		 * @since rt-Helpdesk 0.1
		 *
		 * @param $post_id
		 * @param $post
		 */
		public static function save( $post_id, $post ) {

			global $rt_hd_tickets_operation;
			if ( isset( $_POST['ticket_ex_files'] ) && ! empty( $_POST['ticket_ex_files'] ) ) {
				$rt_hd_tickets_operation->ticket_external_link_update( $_POST['ticket_ex_files'], $post_id );
			}

		}
	}
}
