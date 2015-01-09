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
			//			add_action( 'show_user_profile', array( $this, 'add_rthd_notification_events_field' ) );
			//			add_action( 'edit_user_profile', array( $this, 'add_rthd_notification_events_field' ) );

			if ( rthd_get_redux_adult_filter() ) {
				//				add_action( 'show_user_profile', array( $this, 'add_rthd_adult_filter' ) );
				//				add_action( 'edit_user_profile', array( $this, 'add_rthd_adult_filter' ) );
				//				add_action( 'personal_options_update', array( $this, 'save_rthd_adult_filter' ) );
				//				add_action( 'edit_user_profile_update', array( $this, 'save_rthd_adult_filter' ) );
				add_action( 'init', array( $this, 'save_rthd_adult_filter' ), 10 );

			}
			add_action( 'init', array( $this, 'save_rthd_notification_events_field' ), 10 );
			//			add_action( 'personal_options_update', array( $this, 'save_rthd_notification_events_field' ) );
			//			add_action( 'edit_user_profile_update', array( $this, 'save_rthd_notification_events_field' ) );
		}

		/**
		 * Show setting for adult filter
		 * @param $user
		 */
		public function add_rthd_adult_filter( $user ){
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );

			if ( ! current_user_can( $cap ) ){
				return;
			}
			if ( current_user_can( $cap, $user->ID ) ) {
				?>
				<table class="form-table">
					<tbody>
					<tr>
						<?php
						?>
<!--						<th><label for="rthd_adult_preference">--><?php //_e( 'Helpdesk Content Preference', RT_HD_TEXT_DOMAIN ); ?><!--</label></th>-->
						<td>
							<label for="rthd_adult_pref">
								<input name="rthd_adult_pref" type="checkbox" id="rthd_adult_pref" value="1"
									<?php
									$user_pref = rthd_get_user_adult_preference( $user->ID );
									if ( 'yes' == $user_pref ){
										echo 'checked="checked"';
									}
									?>
									/>
								<span class="description" style="display: inline"><?php _e( 'Show Adult Content', RT_HD_TEXT_DOMAIN ); ?></span>
							</label>
						</td>
					</tr>
					</tbody>
				</table>
			<?php
			}
		}

		public function save_rthd_adult_filter(){
			$user_id = get_current_user_id();
			if ( isset( $_POST['rthd_adult_pref'] ) ) {
				if ( ! empty( $_POST['rthd_adult_pref'] ) ) {
					$pref = 'yes';
				} else {
					$pref = 'no';
				}
				$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
				if ( current_user_can( $cap, $user_id ) ) {
					update_user_meta( $user_id, 'rthd_adult_pref', sanitize_text_field( $pref ) );
				}
			}
		}

		/**
		 * Display the Notification info for a user
		 *
		 * @since 1.0
		 * @param WP_User $user
		 */
		public function add_rthd_notification_events_field( $user ) {

			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );

			if ( ! current_user_can( $cap ) ){
				return;
			}

			if ( current_user_can( $cap, $user->ID ) ) {
				?>
				<table class="form-table">
					<tbody>
					<tr>
						<?php
						$settings = rthd_get_redux_settings();
						$label = $settings['rthd_menu_label'] . ' Notification Preference';
						?>
<!--						<th><label for="rthd_notification_pref">--><?php //_e( $label , RT_HD_TEXT_DOMAIN ); ?><!--</label></th>-->
						<td>
							<label for="rthd_notification_pref">
							<input name="rthd_notification_pref" type="checkbox" id="rthd_notification_pref" value="1"
								<?php
								$user_pref = rthd_get_user_notification_preference( $user->ID );
								if ( 'yes' == $user_pref ){
									echo 'checked="checked"';
								}
								?>
								/>
								<span class="description" style="display: inline"><?php _e( 'Turn On Event Notification', RT_HD_TEXT_DOMAIN ); ?></span>
							</label>
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
		public function save_rthd_notification_events_field( ) {
			$user_id = get_current_user_id();
			if ( isset( $_POST['rthd_notification_pref'] ) ){
				if ( ! empty( $_POST['rthd_notification_pref'] ) ){
					$pref = 'yes';
				}
				else {
					$pref = 'no';
				}
				$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
				if ( current_user_can( $cap, $user_id ) ) {
					update_user_meta( $user_id, 'rthd_notification_pref', sanitize_text_field( $pref ) );
				}
			}
		}
	}
}
