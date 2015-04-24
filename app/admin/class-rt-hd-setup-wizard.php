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
			add_action( 'wp_ajax_rthd_search_non_helpdesk_user_from_name', array( $this, 'get_user_from_name' ) );
			add_action( 'wp_ajax_rthd_creater_rtbiz_and_give_access_helpdesk', array( $this, 'rthd_creater_rtbiz_and_give_access_helpdesk' ) );
			add_action( 'wp_ajax_rthd_domain_search_and_import', array( $this, 'domain_search_and_import' ) );
			add_action( 'wp_ajax_rthd_import_all_users', array( $this, 'import_all_users' ) );
			add_action( 'wp_ajax_rthd_offering_sync', array( $this, 'offering_sync' ) );
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

				<h1><?php _e( 'Support Page' ); ?></h1>
				<fieldset> <?php $this->support_page_ui(); ?></fieldset>

				<h1><?php _e( 'Connect Store' ); ?></h1>
				<fieldset><?php $this->connect_store_ui() ?></fieldset>

				<h1><?php _e( 'Setup Your Team' ); ?></h1>
				<fieldset>
					<?php $this->setup_team(); ?>

				</fieldset>

				<h1><?php _e( 'Default Assignee' ); ?></h1>
				<fieldset> Default assignee content</fieldset>

				<h1><?php _e( 'Mailbox Setup' ); ?></h1>
				<fieldset>
					<h3><?php _e( 'MailBox Setup', RT_BIZ_TEXT_DOMAIN ); ?></h3>
					<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.</p>
					<?php rthd_mailbox_setup_view(); ?>
				</fieldset>

				<h1><?php _e( 'Finish' ); ?></h1>
				<fieldset> YEY! you're good to go. </fieldset>

			</div>


			<?php
		}

		function connect_store_ui(){
			?>
			<h3><?php _e( 'Connect your store with helpdesk', RT_BIZ_TEXT_DOMAIN ); ?></h3>
			<?php
			global $rt_hd_offering_support;
			$rt_hd_offering_support->check_active_plugin();
			$eddactive = is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ;
			$wooactive = is_plugin_active( 'woocommerce/woocommerce.php' ) ;
			if ( $wooactive || $eddactive ){
				if( $wooactive && $eddactive ){
					$active_plugins = 'WooCommerce and EDD';
				} else if ( $wooactive){
					$active_plugins = 'WooCommerce';
				}
				else {
					$active_plugins = 'EDD';
				}
				echo '<p class="description rthd-setup-description"> Looks like you have '.$active_plugins. ' Active. Helpdesk have selected '.$active_plugins.' for you, You can change that if you want to.</p>';
			}
			?>
			<div class="rthd-setup-wizard-row">
				<input id="option" type="checkbox" name="rthd-wizard-store" value="woocommerce" <?php echo $wooactive?'checked':'';?> >
				<label for="option">WooCommerce</label>
			</div>
			<div class="rthd-setup-wizard-row">
				<input id="option" type="checkbox" name="rthd-wizard-store" value="edd" <?php echo $eddactive?'checked':'';?> >
				<label for="option">EDD</label>
			</div>
<!--			<div class="rthd-setup-wizard-row">-->
<!--				<input id="option" type="checkbox" name="rthd-wizard-store" value="none" --><?php //echo (!$eddactive&&!$wooactive)?'checked':'';?><!-- >-->
<!--				<label for="option">none</label>-->
<!--			</div>-->
			<div class="rthd-store-process" style="display: none;">
				<span>Connecting store and importing existing products</span>
				<img id="rthd-support-page-spinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div>
			<button class="rthd-wizard-skip" type="button">Skip</button>

		<?php
		}

		function support_page_ui(){
			$pages = get_pages();
			?>
			<h3>Support Page </h3>
			<div class="rthd-setup-wizard-row">
			<label for="rthd-setup-wizard-support-page"><?php _e('Select Support Page ',RT_BIZ_TEXT_DOMAIN); ?></label>
			<select id="rthd-setup-wizard-support-page">
				<option value="0" selected><?php _e('--Select Page--',RT_BIZ_TEXT_DOMAIN); ?></option>
				<?php
				if ( ! empty( $pages ) ){
					foreach($pages as $page) {
						echo '<option value="'.$page->ID.'">'.$page->post_title.'</option>';
					}
				} ?>
				<option value="-1"><?php _e('-Create New Page-',RT_BIZ_TEXT_DOMAIN); ?></option>
			</select>

			</div>
		<div class="rthd-setup-wizard-row rthd-setup-wizard-support-page-new-div" style="display: none;">
			<label for="rthd-setup-wizard-support-page-new"><?php _e('Create New Page',RT_BIZ_TEXT_DOMAIN); ?></label>
			<input type="text" id="rthd-setup-wizard-support-page-new" name="rthd-setup-wizard-support-page-new-value" />
		</div>
			<div class="rthd-support-process" style="display: none;">
			<span>Setting up support page</span>
			<img id="rthd-support-page-spinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div>
			<button class="rthd-wizard-skip" type="button">Skip</button>
		<?php
		}

		function setup_team(){
			global $rthd_form;
			?>
			<h3>There are 3 ways you can add users to your team. If you forget somebody now, you can add them later. </h3>
			<div class="rthd_wizard_container rthd-setup-wizard-row">
				<div class="rthd-setup-value-container">
					<label for="rthd-user-autocomplete"> 1. Search and add users </label>
					<input id="rthd-user-autocomplete" type="text" placeholder="Search by name or email" class="rthd-user-autocomplete rthd-setup-wizard-text " />
				<br/>
					<span class="rthd-warning" style="display: none;"></span>
					<input type="button" class='rthd-importer-add-contact' value="Add" style="display: none;" />
					<input type="hidden" id="rthd-new-user-email" />

				</div>
			</div>
			<div class="rthd_wizard_container rthd_selected_user ">
				<ul class="rthd-setup-ul-text-decoration rthd-setup-list-users">

				</ul>
			</div>

			<div class="rthd_wizard_container rthd-setup-wizard-row">
				<?php
					$domain_name =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);

					$count_domain_users = rthd_search_non_helpdesk_users( '@'.$domain_name, true, true );
				?>
				<label for="rthd-add-user-domain"> 2. Add all users from my domain</label>
				<input id="rthd-add-user-domain" class="rthd-setup-wizard-text" type="text" value="<?php echo '@'.$domain_name; ?>" placeholder="@gmail.com" />
				<br/>
				<label></label>
				<input id="rthd-import-domain-users" type="button" value="Add users" />
				<span id='rthd-domain-import-message' style=""> Found<?php echo sprintf( _n( '%s user', '%s users', $count_domain_users, RT_HD_TEXT_DOMAIN ), $count_domain_users );?></span>
				<?php wp_nonce_field( get_current_user_id().'import-user-domain', 'import_domain' );?>
			</div>

			<div class="rthd_wizard_container rthd-setup-wizard-row">
				<form>
					<?php
					$helpdesk_users = rthd_get_helpdesk_user_ids();
					$count = count_users();
					$remain_wp_users = $count['total_users']-count( $helpdesk_users);
					?>
				<label> 3. Add all users</label>
				<input id="rthd-add-all-users" type="button" value="add all users" />
					<span> Found <?php echo sprintf( _n( '%s user', '%s users', $remain_wp_users, RT_HD_TEXT_DOMAIN ), $remain_wp_users ); ?></span>
				<?php wp_nonce_field( get_current_user_id().'import-all-users', 'import_all_users' );?>
				<input type="hidden" id="rthd-setup-import-all-count" value="<?php echo $remain_wp_users; ?>" />
				<progress id="rthd-setup-import-users-progress" max="<?php echo $remain_wp_users; ?>" value="0" style="display: none;"></progress>
				</form>
			</div>
		<?php
		}


		/**
		 *
		 * Search all wp user who don't have helpdesk access
		 *
		 */
		function get_user_from_name() {
			if ( ! isset( $_POST['query'] ) ) {
				wp_die( 'Invalid request Data' );
			}
			$query = $_POST['query'];
			$results = rthd_search_non_helpdesk_users( $query, false, false );
			$arrReturn = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $author ) {
					$arrReturn[ ] = array( 'id'       => $author->ID,
					                       'label'    => $author->display_name,
					                       'imghtml'  => get_avatar( $author->user_email, 25 ),
					                       'editlink' => rt_biz_get_contact_edit_link( $author->user_email )
					);
				}
			}
			else {
				if ( is_email( $_POST['query'] ) ) {
					if ( email_exists( $_POST[ 'query' ] ) ) {
						$arrReturn[ 'have_access' ] = true;
					} else {
						$arrReturn[ 'show_add' ] = true;
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * Create wp user if not exist, create rtbiz contact if not exist, if exist and not mapped map it, give helpdesk access
		 * ajax call
		 */
		function rthd_creater_rtbiz_and_give_access_helpdesk(){
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['email'] ) ||  ! empty( $_POST['ID'] ) ){
				if ( ! empty( $_POST['email'] ) && is_email($_POST['email'] )){
					// create wordpress user and get it's id for creating rt contact user
					global $rt_hd_contacts;
					$_POST['ID'] = $rt_hd_contacts->get_user_from_email( $_POST['email'] );
				}
				if ( ! empty( $_POST['ID'] )){
					// Create dept if not exist in wp-option, if exist assign new user to that taxonomy and add entry for author access to that user
//					global $rt_biz_acl_model,$rt_contact;
					$wpuser = get_user_by( 'id', $_POST['ID'] );

					$team_id = rthd_get_default_support_team();

					if ( rthd_give_user_access( $wpuser, Rt_Access_Control::$permissions['author']['value'], $team_id )) {
						// do something
						$arrReturn[ 'status' ] = true;
						$arrReturn             = array_merge( $arrReturn, array(
							'id'       => $wpuser->ID,
							'label'    => $wpuser->display_name,
							'imghtml'  => get_avatar( $wpuser->user_email, 25 ),
							'editlink' => rt_biz_get_contact_edit_link( $wpuser->user_email )
						) );
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}


		function domain_search_and_import(){
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['count'] ) && ! empty( $_POST['domain_query'] ) && ! empty( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], get_current_user_id().'import-user-domain' )){
				if ( $_POST['count'] === 'true' ) { // return counts of users
					$arrReturn[ 'count' ]  = rthd_search_non_helpdesk_users( $_POST[ 'domain_query' ], true, true );
					$arrReturn[ 'status' ] = true;
				}
				else {
					$users_to_import  = rthd_search_non_helpdesk_users( $_POST[ 'domain_query' ], false, false );
					$team_id = rthd_get_default_support_team();
					$arrReturn['imported_all'] = true;
					foreach ( $users_to_import as $user ){
						if ( rthd_give_user_access( $user->ID, Rt_Access_Control::$permissions['author']['value'], $team_id ) ){
							$arrReturn['imported_users'][] = array( 'id'       => $user->ID,
							                                        'label'    => $user->display_name,
							                                        'imghtml'  => get_avatar( $user->user_email, 25 ),
							                                        'editlink' => rt_biz_get_contact_edit_link( $user->user_email ) );
						} else {
							$arrReturn['not_imported_users'][] = $user;
							$arrReturn['imported_all'] = false;
						}
					}
					$arrReturn[ 'status' ] = true;
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}


		function import_all_users(){
			$arrReturn = array( 'status' => false );
			$LIMIT = 2; //todo : change this to 100 or more
			if ( ! empty($_POST['import']) && ! empty( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], get_current_user_id().'import-all-users' )){
				global $wpdb;
				$helpdesk_users = rthd_get_helpdesk_user_ids();
				$q = '';
				if ( ! empty( $helpdesk_users ) ){
					$q = ' WHERE ID not IN ('. implode( ',',$helpdesk_users ) .') ';
				}
				$users_to_import =  $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users".$q."LIMIT ".$LIMIT );
				$arrReturn['imported_all'] = true;
				$team_id = rthd_get_default_support_team();

				foreach ( $users_to_import as $user ){
					if ( rthd_give_user_access( $user->ID, Rt_Access_Control::$permissions['author']['value'], $team_id ) ){
						$arrReturn['imported_users'][] = array( 'id'       => $user->ID,
						                                        'label'    => $user->display_name,
						                                        'imghtml'  => get_avatar( $user->user_email, 25 ),
						                                        'editlink' => rt_biz_get_contact_edit_link( $user->user_email ) );
					} else {
						$arrReturn['not_imported_users'][] = $user;
						$arrReturn['imported_all'] = false;
					}
				}
				$arrReturn['imported_count'] = count($users_to_import) ;
				$arrReturn[ 'status' ] = true;
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}


		function offering_sync() {
			$arrReturn       = array( 'status' => false );
			$offering        = array();
			$defaultoffering = array( 'woocommerce' => 0, 'edd' => 0 );
			if ( ! empty( $_POST[ 'store' ] ) ) {
				foreach ( $_POST[ 'store' ] as $store ) {
					$offering[ $store ] = '1';
				}
			}
			$offering = array_merge( $defaultoffering, $offering );
			rt_biz_set_redux_setting( 'offering_plugin', $offering );
			global $rtbiz_offerings;
			$rtbiz_offerings->bulk_insert_offerings( $offering );
			$arrReturn[ 'status' ] = true;
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

	}

}
