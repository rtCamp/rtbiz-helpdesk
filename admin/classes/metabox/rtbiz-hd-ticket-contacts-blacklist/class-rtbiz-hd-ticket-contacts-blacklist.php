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
if ( ! class_exists( 'Rtbiz_HD_Ticket_Contacts_Blacklist' ) ) {

	class Rtbiz_HD_Ticket_Contacts_Blacklist {

		/**
		 * Hook for ajax
		 *
		 * @since 0.1
		 */
		public function __construct() {
			add_action( 'wp_ajax_rtbiz_hd_show_blacklisted_confirmation', array( $this, 'ajax_show_blacklisted_confirmation' ) );
			add_action( 'wp_ajax_rtbiz_hd_add_blacklisted_contact', array( $this, 'ajax_add_blacklisted_contact' ) );
			add_action( 'wp_ajax_rtbiz_hd_remove_blacklisted_contact', array( $this, 'ajax_remove_blacklisted_contact' ) );
		}

		/**
		 * Output the metabox
		 *
		 * @since rt-Helpdesk 0.1
		 *
		 * @param $post
		 */
		public static function ui( $post ) {
			$blocklist_action = 'remove_blacklisted';
			if ( isset( $post->ID ) ) {
				$blocklist_action = ( 'true' != get_post_meta( $post->ID, '_rtbiz_hd_is_blocklised', true ) ) ? 'blacklisted_confirmation' : 'remove_blacklisted';
			}
			$class = 'rthd-hide-row';
			if ( 'remove_blacklisted' == $blocklist_action ) {
				$blacklistedEmail = rtbiz_hd_get_blacklist_emails();
				$arrContactsEmail = array();
				$contacts         = rtbiz_get_post_for_contact_connection( $post->ID, Rtbiz_HD_Module::$post_type );
				foreach ( $contacts as $contact ) {
					$arrContactsEmail[] = get_post_meta( $contact->ID, Rtbiz_Entity::$meta_key_prefix . Rtbiz_Contact::$primary_email_key, true );
				}
				$contacts = array_intersect( $blacklistedEmail, $arrContactsEmail );
				$class    = '';
			}
			?>
			<div id="contacts-blacklist-container"
			     class="row_group <?php echo $class; ?>"><?php if ( 'remove_blacklisted' == $blocklist_action ) { ?>
					<ui class="blacklist_contacts_list"><?php foreach ( $contacts as $email ) { ?>
						<li><?php echo $email; ?></li>
					<?php } ?>
					</ui>
				<?php } ?>
			</div>
			<div id="contacts-blacklist-action" class="row_group">
				<p>
					<a href="#" data-action="<?php echo $blocklist_action; ?>"
					   data-postid="<?php echo isset( $post->ID ) ? $post->ID : '0'; ?>" class="button"
					   id="rthd_ticket_contacts_blacklist">
						<?php
						if ( 'remove_blacklisted' == $blocklist_action ) {
							_e( 'Remove', RTBIZ_HD_TEXT_DOMAIN );
						} else {
							_e( 'Blacklist', RTBIZ_HD_TEXT_DOMAIN );
						}
						?>
					</a>
				</p>
			</div>
			<p class="description"><?php _e( 'Note : Add ticket contacts as blacklist.', RTBIZ_HD_TEXT_DOMAIN ); ?></p>
		<?php
		}

		/**
		 * Ajax request for show confirmation for block listed contact of given ticket before contacts are block listed
		 */
		function ajax_show_blacklisted_confirmation() {
			if ( ! isset( $_POST['post_id'] ) || empty( $_POST['post_id'] ) ) {
				return;
			}
			$reponse           = array();
			$reponse['status'] = false;
			$ticket_data       = $_POST;
			$contacts          = rtbiz_get_post_for_contact_connection( $ticket_data['post_id'], Rtbiz_HD_Module::$post_type );
			$created_by        = rtbiz_hd_get_ticket_creator( $_POST['post_id'] );

			if ( ! empty( $created_by ) ) {
				$contacts[] = $created_by;
			}

			ob_start();
			?>
			<div class="confirmation-container notice notice-warning">
				<p>Are you sure to blacklist these contacts?</p>
			</div>
			<ui class="blacklist_contacts_list"><?php
			foreach ( $contacts as $contact ) {
				$email = get_post_meta( $contact->ID, Rtbiz_Entity::$meta_key_prefix . Rtbiz_Contact::$primary_email_key, true );
				?>
				<li><?php echo $email; ?></li>
			<?php } ?>
			</ui>
			<div class="confirmation-container">
				<p>
					<a href="#" data-action="blacklisted_contact" data-postid="<?php echo $ticket_data['post_id']; ?>"
					   class="button"
					   id="rthd_ticket_contacts_blacklist_yes"><?php _e( 'Yes', RTBIZ_HD_TEXT_DOMAIN ); ?></a>
					<a href="#" data-action="blacklisted_contact_no"
					   data-postid="<?php echo $ticket_data['post_id']; ?>" class="button"
					   id="rthd_ticket_contacts_blacklist_no"><?php _e( 'No', RTBIZ_HD_TEXT_DOMAIN ); ?></a>
				</p>
			</div>

			<?php
			$reponse['confirmation_ui'] = ob_get_clean();
			$reponse['status']          = true;
			echo json_encode( $reponse );
			die( 0 );
		}

		/**
		 * Ajax request for add blacklist email into blacklist email list
		 */
		function ajax_add_blacklisted_contact() {
			if ( ! isset( $_POST['post_id'] ) || empty( $_POST['post_id'] ) ) {
				return;
			}
			$reponse           = array();
			$reponse['status'] = false;
			$contacts          = rtbiz_get_post_for_contact_connection( $_POST['post_id'], Rtbiz_HD_Module::$post_type );
			$blacklistedEmail  = array();
			foreach ( $contacts as $contact ) {
				$blacklistedEmail[] = get_post_meta( $contact->ID, Rtbiz_Entity::$meta_key_prefix . Rtbiz_Contact::$primary_email_key, true );
			}
			$created_by         = rtbiz_hd_get_ticket_creator( $_POST['post_id'] );
			if ( ! empty( $created_by ) ) {
				$blacklistedEmail[] = $created_by->primary_email;
			}
			if ( ! empty( $blacklistedEmail ) ) {
				$blacklistedEmail = array_merge( rtbiz_hd_get_blacklist_emails(), $blacklistedEmail );
				$blacklistedEmail = implode( "\n", array_unique( $blacklistedEmail ) );
				rtbiz_hd_set_redux_settings( 'rthd_blacklist_emails_textarea', $blacklistedEmail );
				update_post_meta( $_POST['post_id'], '_rtbiz_hd_is_blocklised', 'true' );
				ob_start();
				?>
				<p>
					<a href="#" data-action="remove_blacklisted" data-postid="<?php echo $_POST['post_id']; ?>"
					   class="button" id="rthd_ticket_contacts_blacklist">
						<?php _e( 'Remove', RTBIZ_HD_TEXT_DOMAIN ); ?>
					</a>
				</p>

				<?php
				$reponse['remove_ui'] = ob_get_clean();
				$reponse['status']    = true;
			}
			echo json_encode( $reponse );
			die( 0 );
		}

		/**
		 * Ajax request for remove blacklist email into blacklist email list
		 */
		function ajax_remove_blacklisted_contact() {
			if ( ! isset( $_POST['post_id'] ) || empty( $_POST['post_id'] ) ) {
				return;
			}
			$reponse           = array();
			$reponse['status'] = false;
			$contacts          = rtbiz_get_post_for_contact_connection( $_POST['post_id'], Rtbiz_HD_Module::$post_type );
			$arrContactsEmail  = array();
			$blacklistedEmail  = rtbiz_hd_get_blacklist_emails();
			foreach ( $contacts as $contact ) {
				$arrContactsEmail[] = get_post_meta( $contact->ID, Rtbiz_Entity::$meta_key_prefix . Rtbiz_Contact::$primary_email_key, true );
			}
			if ( ! empty( $arrContactsEmail ) ) {
				if ( ! empty( $blacklistedEmail ) ) {
					$arrContactsEmail = array_diff( $blacklistedEmail, $arrContactsEmail );
				}
			}
			$arrContactsEmail = array_unique( $arrContactsEmail );
			$arrContactsEmail = implode( "\n", $arrContactsEmail );

			rtbiz_hd_set_redux_settings( 'rthd_blacklist_emails_textarea', $arrContactsEmail );
			update_post_meta( $_POST['post_id'], '_rtbiz_hd_is_blocklised', 'false' );
			ob_start();
			?>
			<p>
				<a href="#" data-action="blacklisted_confirmation" data-postid="<?php echo $_POST['post_id']; ?>"
				   class="button" id="rthd_ticket_contacts_blacklist">
					<?php _e( 'Blacklist', RTBIZ_HD_TEXT_DOMAIN ); ?>
				</a>
			</p>
			<?php
			$reponse['addBlacklist_ui'] = ob_get_clean();

			$reponse['status'] = true;
			echo json_encode( $reponse );
			die( 0 );
		}

	}

}
