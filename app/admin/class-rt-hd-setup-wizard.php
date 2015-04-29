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
			add_action( 'wp_ajax_rthd_setup_wizard_assignee_save', array( $this, 'assignee_save' ) );
			add_action( 'wp_ajax_rthd_get_default_assignee_ui', array( $this, 'default_assignee' ) );
			add_action( 'wp_ajax_rthd_outboud_mail_setup_ui', array( $this, 'rthd_outboud_mail_setup_ui' ) );
			add_action( 'wp_ajax_rthd_outound_setup_wizard', array( $this, 'rthd_outound_setup_wizard_callback' ) );
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
			<div class="wrap" id="wizard">

				<h1><?php _e( 'Support Page' ); ?></h1>
				<fieldset>
					<?php $this->support_page_ui(); ?>
				</fieldset>

				<h1><?php _e( 'Connect Store' ); ?></h1>
				<fieldset style="display: none">
					<?php $this->connect_store_ui() ?>
				</fieldset>

				<h1><?php _e( 'Setup Your Team' ); ?></h1>
				<fieldset style="display: none">
					<?php $this->setup_team(); ?>
				</fieldset>

				<h1><?php _e( 'Set Assignee' ); ?></h1>
				<fieldset style="display: none">
					<div id="rthd-setup-set-assignee-ui">

					</div>
				</fieldset>

				<h1><?php _e( 'Mailbox Setup' ); ?></h1>
				<fieldset style="display: none">
					<h3><?php _e( 'Incoming MailBox Setup', RT_BIZ_TEXT_DOMAIN ); ?></h3>
					<p class="description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.</p>
					<?php rthd_mailbox_setup_view(); ?>
				</fieldset>

				<h1><?php _e( 'Finish' ); ?></h1>
				<fieldset style="display: none">
					YEY! you're good to go.
				</fieldset>

			</div>


			<?php
		}

		/**
		 * outbound mailbox ui
		 */
		function rthd_outboud_mail_setup_ui() {
			$system_emails = rtmb_get_module_mailbox_emails( RT_HD_TEXT_DOMAIN );
			ob_start();
			?>
			<h3><?php _e( 'Outgoing MailBox Setup', RT_BIZ_TEXT_DOMAIN ); ?></h3>
			<p class="description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
				Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a
				galley of type and scrambled it to make a type specimen book. It has survived not only five centuries,
				but also the leap into electronic typesetting, remaining essentially unchanged.</p>
			<div id="rthd_outgoing_mailbox_setup_container">
				<input type="hidden" id="rthd_outound_sub-action" name="rthd_outound_sub-action" value="rthd_outound_setup_wizard">
				<?php wp_nonce_field( 'rthd_outound_setup_wizard' );?>
				<div class="rthd-setup-wizard-row">
					<label for="rthd_outgoing_email_from_name"> <?php _e( 'Outgoing Emails\' FROM Name' ); ?></label>
					<input type="text" id="rthd_outgoing_email_from_name" name="rthd_outgoing_email_from_name" value="<?php echo get_bloginfo(); ?>" />
				</div>
				<div class="rthd-setup-wizard-row">
					<label for="rthd_outgoing_email_mailbox"> <?php _e( 'Outgoing Emails\' Mailbox' ); ?></label>
					<select id="rthd_outgoing_email_mailbox" name="rthd_outgoing_email_mailbox">
						<?php foreach ( $system_emails as $email ) { ?>
							<option value="<?php echo $email; ?>"><?php echo $email; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<?php
			$comment_html = ob_get_clean();
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => true, 'html' => $comment_html ) );
			die( 0 );
		}

		/**
		 * save outbound mailbox
		 */
		function rthd_outound_setup_wizard_callback(){
			$result           = array();
			$result['status'] = false;

			$obj_data = array();
			parse_str( $_POST['data'], $obj_data );

			if ( ! wp_verify_nonce( $obj_data['_wpnonce'], 'rthd_outound_setup_wizard' ) ) {
				$result['error'] = 'Security check false';
				echo json_encode( $result );
				die();
			}

			if ( empty( $obj_data['rthd_outgoing_email_from_name'] ) || empty( $obj_data['rthd_outgoing_email_mailbox'] ) ) {
				$result['error'] = 'Error: Required mailbox field missing';
				echo json_encode( $result );
				die();
			}

			if ( !empty( $obj_data['rthd_outgoing_email_from_name'] ) ){
				rthd_set_redux_settings( 'rthd_outgoing_email_from_name',$obj_data['rthd_outgoing_email_from_name'] );
			}

			if ( !empty( $obj_data['rthd_outgoing_email_mailbox'] ) ){
				rthd_set_redux_settings( 'rthd_outgoing_email_mailbox',$obj_data['rthd_outgoing_email_mailbox'] );
			}
			$result['status'] = true;
			echo json_encode( $result );
			die( 0 );
		}


		/**
		 * default assignee ui ajax call
		 */
		function default_assignee(){
			ob_start();
			$current = get_current_user_id();
			?>
	 <?php
			// get product list
			$terms = array();
			global $rtbiz_offerings;
			if ( isset( $rtbiz_offerings ) ) {
				add_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
				$terms = get_terms( Rt_Offerings::$offering_slug, array( 'hide_empty' => 0 ) );
				remove_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
			}
			$users  = Rt_HD_Utils::get_hd_rtcamp_user();
            if ( ! empty( $terms ) ) {
                ?>
	            <div class="rthd-setup-wizard-controls">

		            <h3><?php _e( 'Select Ticket Assignee', RT_BIZ_TEXT_DOMAIN ); ?></h3>
		            <p class="description"> <?php _e('Select an assignee for the products we synced in previous setup.', RT_BIZ_TEXT_DOMAIN); ?> </p>

                <div class="rthd-setup-wizard-row">
                    <ul>
                        <?php
                        foreach ($terms as $tm) { ?>
                            <li>
                                <label for="rthd_offering<?php echo $tm->term_id ?>"> <?php echo $tm->name ?></label>
                                <select class="rthd-setup-assignee" data="<?php echo $tm->term_id ?>"
                                        id="rthd_offering<?php echo $tm->term_id ?>">
                                    <?php
                                    // if needed to set default assignee that already have assigned
                                    //							$selected_userid = get_offering_meta( 'default_assignee', $tm->term_id );
                                    if (empty($current)) {
                                        echo '<option disabled selected> -- select an assignee -- </option>';
                                    } else {
                                        echo '<option > -- select an assignee -- </option>';
                                    }
                                    foreach ($users as $user) {
                                        if ($user->ID == $current) {
                                            $selected = 'selected';
                                        } else {
                                            $selected = '';
                                        }
                                        echo '<option value="' . $user->ID . '" ' . $selected . '>' . $user->display_name . '</option>';
                                    }
                                    ?>
                                </select>
                            </li>
                        <?php
                        }
                        ?>
                    </ul>
                </div>
	            </div>
                <div class="rthd-assignee-process" style="display: none;">
                    <span>Setting up default assignee for offerings</span>
                    <img src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
                </div> <?php
            }else{ ?>
                <p class="description"> <?php _e(' No product found! you will be auto redirect to next step within second.', RT_BIZ_TEXT_DOMAIN); ?> </p>
            <?php }
			$comment_html = ob_get_clean();
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => true, 'html'=> $comment_html ) );
			die( 0 );
		}

		/**
		 * connect store UI
		 */
		function connect_store_ui(){
			?>

			<div class="rthd-setup-wizard-controls">
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
					<input id="rthd-wizard-store-wc" class="" type="checkbox" name="rthd-wizard-store" value="woocommerce" <?php echo $wooactive?'checked':'';?> >
					<label for="rthd-wizard-store-wc">WooCommerce</label>
				</div>
				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-edd" type="checkbox" name="rthd-wizard-store" value="edd" <?php echo $eddactive?'checked':'';?> >
					<label for="rthd-wizard-store-edd">EDD</label>
				</div>
			</div>
			<div class="rthd-store-process" style="display: none;">
				<span>Connecting store and importing existing products</span>
				<img id="rthd-support-page-spinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div>

		<?php
		}

		/**
		 * Support page ui
		 */
		function support_page_ui(){
			$pages = get_pages();
			?>
			<div class="rthd-setup-wizard-controls">
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
			</div>
		<div class="rthd-setup-wizard-row rthd-setup-wizard-support-page-new-div" style="display: none;">
			<label for="rthd-setup-wizard-support-page-new"><?php _e('Create New Page',RT_BIZ_TEXT_DOMAIN); ?></label>
			<input type="text" id="rthd-setup-wizard-support-page-new" name="rthd-setup-wizard-support-page-new-value" />
		</div>
			<div class="rthd-support-process" style="display: none;">
			<span>Setting up support page</span>
			<img id="rthd-support-page-spinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div>
<!--			<button class="rthd-wizard-skip" type="button">Skip</button>-->
		<?php
		}

		/**
		 * setup team UI
		 */
		function setup_team(){
			global $rthd_form;
			?>

			<div class="rthd_wizard_container rthd_selected_user " style="display: none;">
				<ul class="rthd-setup-ul-text-decoration rthd-setup-list-users">

				</ul>
			</div>
			<div class="rthd-setup-wizard-controls">
				<h3>There are 3 ways you can add users to your team. If you forget somebody now, you can add them later. </h3>
				<div class="rthd_wizard_container rthd-setup-wizard-row">

					<div class="rthd-setup-value-container">
						<label for="rthd-user-autocomplete"> 1. Search and add users </label>
						<input id="rthd-user-autocomplete" type="text" placeholder="Search by name or email" class="rthd-user-autocomplete rthd-setup-wizard-text " />
						<img id="rthd-autocomplete-page-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
						<br/>
						<span class="rthd-warning" style="display: none;"></span>
						<input type="button" class='button rthd-importer-add-contact' value="Add" style="display: none;" />
						<input type="hidden" id="rthd-new-user-email" />

					</div>
				</div>
			<div class="rthd_wizard_container rthd-setup-wizard-row">
				<?php
					$domain_name =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);

					$count_domain_users = rthd_search_non_helpdesk_users( '@'.$domain_name, true, true );
				?>
				<label for="rthd-add-user-domain"> 2. Add all users from domain</label>
				<input id="rthd-add-user-domain" class="rthd-setup-wizard-text" type="text" value="<?php echo '@'.$domain_name; ?>" placeholder="@gmail.com" />
				<br/>
				<label></label>
				<span id='rthd-domain-import-message' style=""> Found <?php echo sprintf( _n( '%s user', '%s users', $count_domain_users, RT_HD_TEXT_DOMAIN ), $count_domain_users );?></span>
				<input id="rthd-import-domain-users" class="button" type="button" value="Add Users" />
				<img id="rthd-domain-import-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
				<?php wp_nonce_field( get_current_user_id().'import-user-domain', 'import_domain' );?>
			</div>

			<div class="rthd_wizard_container rthd-setup-wizard-row">
				<form>
					<?php
					$helpdesk_users = rthd_get_helpdesk_user_ids();
					$count = count_users();
					$remain_wp_users = $count['total_users']-count( $helpdesk_users);
					?>
				<label> 3. Add all WordPress <?php echo sprintf( _n( '(%s) user', '(%s) users', $remain_wp_users, RT_HD_TEXT_DOMAIN ), $remain_wp_users ); ?></label>
				<input id="rthd-add-all-users" class="button" type="button" value="Add Users" />
					<img id="rthd-import-all-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
				<?php wp_nonce_field( get_current_user_id().'import-all-users', 'import_all_users' );?>
				<input type="hidden" id="rthd-setup-import-all-count" value="<?php echo $remain_wp_users; ?>" />
				<progress id="rthd-setup-import-users-progress" max="<?php echo $remain_wp_users; ?>" value="0" style="display: none;"></progress>
				<span id='rthd-all-import-message'> </span>
				</form>
			</div>
			</div>
			<div class="rthd-team-setup-loading" style="display: none;">
				<span>Loading next page</span>
				<img id="rthd-support-page-spinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
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


		/**
		 * ajax call for getting domain search and import
		 */
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


		/**
		 * import all users ajax call
		 */
		function import_all_users(){
			$arrReturn = array( 'status' => false );
			$LIMIT = 50;
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


		/**
		 *  Offering save ajax call
		 */
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

		/**
		 *  Assignee save ajax call
		 */
		function assignee_save(){
			$arrReturn       = array( 'status' => false );
			if ( ! empty( $_POST['assignee'] ) ){
				foreach ( $_POST['assignee'] as $assingee ){
					update_offering_meta( 'default_assignee', $assingee['user_ID'], $assingee['term_ID'] );
				}
				$arrReturn[ 'status' ] = true;
			}
			//default_assignee
			if ( ! empty( $_POST['default_assignee'] ) && is_numeric( $_POST['default_assignee'] ) ){
				rthd_set_redux_settings('rthd_default_user',$_POST['default_assignee'] );
				$arrReturn[ 'status' ] = true;
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

	}

}
