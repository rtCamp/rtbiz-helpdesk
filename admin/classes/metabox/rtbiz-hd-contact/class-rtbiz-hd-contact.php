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
if ( ! class_exists( 'Rtbiz_HD_Contact ' ) ) {

	class Rtbiz_HD_Contact {

		/**
		 * Output the metabox
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function ui( $post ) {

			global $rtbiz_hd_module;
			$get_assigned_to = rtbiz_get_post_for_contact_connection( $post->ID, $post->post_type );
			if ( empty( $get_assigned_to ) ) {
				$get_assigned_to = array();
			}
			$subContactHTML    = '';
			if ( ! empty( $get_assigned_to ) ) {
				foreach ( $get_assigned_to as $contact ) {
					$email = get_post_meta( $contact->ID, Rtbiz_Entity::$meta_key_prefix . Rtbiz_Contact::$primary_email_key, true );
					$subContactHTML .= "<li id='subscribe-auth-" . $contact->ID . "' class='contact-list'>" .
								get_avatar( $email, 24 ) .
								"<br/><a target='_blank' class='subscribe-title heading' title='" . $contact->post_title . "' href='" . rtbiz_hd_biz_user_profile_link( $email ) . "'>" . $contact->post_title. '</a>' .
								"<input type='hidden' name='contacts_to[]' value='" . $contact->ID . "' /></li>";
				}
			} ?>

			<div class="">
			<ul id="divContactList" class="">
				<?php echo balanceTags( $subContactHTML ); ?>
			</ul>
			</div><?php
			do_action( 'rt_hd_after_ticket_information', $post );
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

//			global $rtbiz_hd_tickets_operation;

//			$newTicket = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] = 'inline-save' ) ? get_post( $_REQUEST['post_ID'] ) : $_POST['post'];
//			$newTicket = (array) $newTicket;

//			$rtbiz_hd_tickets_operation->ticket_subscribe_update( $_POST['contacts_to'], $newTicket['post_author'], $post_id );
		}

	}

}
