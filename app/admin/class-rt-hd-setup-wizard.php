<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 21/4/15
 * Time: 4:37 PM
 */

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_HD_setup_wizard' ) ) {

	/*
	 * This class is for setup wizard part
	 */

	class Rt_HD_setup_wizard{

		var $screen_id;


		/**
		 * Constructor calling hooks
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'register_setup_wizard' ), 1 );
		}

		/**
		 *  Register page to Wordpress with admin capability
		 */
		function register_setup_wizard(){

			$admin_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );


			$this->screen_id = add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), __( 'Setup Wizard', RT_HD_TEXT_DOMAIN ), __( 'Setup Wizard', RT_HD_TEXT_DOMAIN ), $admin_cap, 'rthd-setup-wizard', array(
				$this,
				'setup_wizard_ui',
			) );
		}

		/**
		 * @param $post_type
		 * setup wizard UI
		 */
		function setup_wizard_ui( $post_type ){
			?>
			<div id="wizard">
				<h1><?php _e( 'Setup Your Team' ); ?></h1>
				<fieldset>
					<?php $this->setup_team(); ?>

				</fieldset>

				<h1><?php _e( 'Connect Store' ); ?></h1>
				<fieldset>Second Content</fieldset>

				<h1><?php _e( 'Support Page' ); ?></h1>
				<fieldset> Support page content </fieldset>

				<h1><?php _e( 'Default Assignee' ); ?></h1>
				<fieldset> Default assignee content</fieldset>

				<h1><?php _e( 'Mailbox Setup' ); ?></h1>
				<fieldset> Mailbox content</fieldset>

				<h1><?php _e( 'Finish' ); ?></h1>
				<fieldset> YEY! you're good to go. </fieldset>

			</div>


			<?php
		}

		function setup_team(){
			echo 'setup teams ';
		}

	}

}
