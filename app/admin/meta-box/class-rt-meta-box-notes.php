<?php
/**
 * User: spock
 * Date: 19/9/14
 * Time: 4:35 PM
 */

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
 * Description of RT_Meta_Box_Notes
 *
 * @since rt-Helpdesk 0.1
 */

if ( ! class_exists( 'RT_Meta_Box_Notes' ) ) {
	class RT_Meta_Box_Notes{

		static $key = 'rt-hd-notes';

		public static function ui( $post ) {
			?>
			<textarea name="notes" id="rt_hd_notes" style="width:100%" rows="5"><?php echo get_post_meta( $post->ID, self::$key, true );?></textarea>
		<?php
		}

		public static function save( $post_id, $post ) {
			if ( isset( $_POST['notes'] ) ){
				update_post_meta( $post_id, self::$key, $_POST['notes'] );
			}
		}

	}
}