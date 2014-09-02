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
 *  @since rt-Helpdesk 0.1
 */

if ( !class_exists( 'RT_Meta_Box_External_Link' ) ) {
	class RT_Meta_Box_External_Link {

		/**
		 * Output the metabox
		 *
		 *  @since rt-Helpdesk 0.1
		 */
		public static function ui( $post ) {

			$post_type = $post->post_type;?>
			<div id="external-files-container"><?php
				if ( isset( $post->ID ) ) {
					$ticket_ex_files = get_post_meta( $post->ID, '_rtbiz_helpdesk_external_file' );
					$count           = 1;
					foreach ( $ticket_ex_files as $ex_file ) {
						$ex_file = (array) json_decode( $ex_file );?>
						<div class="row_group">
						<button class="delete_row removeMeta"><i class="foundicon-minus"></i>X</button>
						<input type="text" name="ticket_ex_files[<?php echo $count; ?>'][title]"
						       value="<?php echo $ex_file['title']; ?>"/>
						<input type="text" name="ticket_ex_files[<?php echo $count; ?>'][link]"
						       value="<?php echo $ex_file['link']; ?>"/>
						</div><?php
						$count ++;
					}
				}?>
			</div>

			<div class="row_group ">
			<input type="text" id='add_ex_file_title' placeholder="Title"/>
			<input type="text" id='add_ex_file_link' placeholder="Link"/>
			<button id="add_new_ex_file" class="button" type="button"><?php _e( 'Add', RT_HD_TEXT_DOMAIN ); ?></button>
			</div><?php
		}

		/**
		 * Save meta box data
		 *
		 *  @since rt-Helpdesk 0.1
		 */
		public static function save( $post_id, $post ) {

			// External File Links
			$old_ex_files = get_post_meta( $post_id, '_rtbiz_helpdesk_external_file' );
			$new_ex_files = array();
			if ( isset( $_POST['ticket_ex_files'] ) ) {
				$new_ex_files = $_POST['ticket_ex_files'];

				delete_post_meta( $post_id, '_rtbiz_helpdesk_external_file' );

				foreach ( $new_ex_files as $ex_file ) {
					if ( empty( $ex_file['link'] ) ) {
						continue;
					}
					if ( empty( $ex_file['title'] ) ) {
						$ex_file['title'] = $ex_file['link'];
					}
					add_post_meta( $post_id, '_rtbiz_helpdesk_external_file', json_encode( $ex_file ) );
				}
			} else {
				delete_post_meta( $post_id, '_rtbiz_helpdesk_external_file' );
			}
		}
	}
}