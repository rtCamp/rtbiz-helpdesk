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
 * Description of Rt_HD_User_Settings
 *
 * @author udit
 *
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_User_Settings' ) ) {
	class Rt_HD_User_Settings {

		public function __construct() {
			add_action( 'show_user_profile', array( $this, 'add_rthd_notification_events_field' ) );
			add_action( 'edit_user_profile', array( $this, 'add_rthd_notification_events_field' ) );

			add_action( 'personal_options_update', array( $this, 'save_rthd_notification_events_field' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_rthd_notification_events_field' ) );
		}

		/**
		 * Display the Notification info for a user
		 *
		 * @since 1.0
		 * @param WP_User $user
		 */
		public function add_rthd_notification_events_field( $user ) {

			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );

			if ( ! current_user_can( $cap ) )
				return;

			if ( current_user_can( $cap, $user->ID ) ) {
				?>
				<table class="form-table">
					<tbody>
					<tr>
						<?php
						$settings = rthd_get_redux_settings();
						$label = $settings['rthd_menu_label'] . ' Notification Preference';
						?>
						<th><label for="rthd_notification_pref"><?php _e( $label , RT_HD_TEXT_DOMAIN ); ?></label></th>
						<td>
							<input name="rthd_notification_pref" type="checkbox" id="rthd_notification_pref" value="1"
								<?php
								$user_pref = rthd_get_user_notification_preference( $user->ID );
								if ( $user_pref == 'yes' ){
									echo 'checked="checked"';
								}
								?>
								/>
							<span class="description"><?php _e( 'Turn On Event Notification', RT_HD_TEXT_DOMAIN ); ?></span>
						</td>
					</tr>
					</tbody>
				</table>
			<?php
			}
		}

		/**
		 * Save Notification Fields on edit user pages
		 *
		 * @param int $user_id User ID of the user being saved
		 */
		public function save_rthd_notification_events_field( $user_id ) {
			if ( isset( $_POST['rthd_notification_pref'] ) ){
				$_POST['rthd_notification_pref'] = 'yes';
			} else {
				$_POST['rthd_notification_pref'] = 'no';
			}

			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );

			if ( current_user_can( $cap, $user_id ) ) {
				update_user_meta( $user_id, 'rthd_notification_pref', sanitize_text_field( $_POST[ 'rthd_notification_pref' ] ) );
			}
		}
	}
}
