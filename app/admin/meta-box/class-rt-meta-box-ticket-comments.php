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
 * Description of RT_Meta_Box_Ticket_Comments
 *
 * @since rt-Helpdesk 0.1
 */

if ( ! class_exists( 'RT_Meta_Box_Ticket_Comments' ) ) {
	class RT_Meta_Box_Ticket_Comments {

		public static function ui( $post ) {
			rthd_get_template( 'followup-common.php', array( 'post' => $post ) );
		}

	}
}