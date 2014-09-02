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
 * Description of RT_WP_Helpdesk
 * Main class that initialize the rt-helpdesk plugin.
 * @author udit
 */
if ( !class_exists( 'RT_WP_Helpdesk' ) ) {

	/**
	 * Class RT_WP_Helpdesk
	 * Main class that initialize the rt-helpdesk plugin.
	 */
	class RT_WP_Helpdesk {

		/**
		 * @var $templateURL is used to set template's root path
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public $templateURL;

		/**
		 * Constructor of RT_WP_Helpdesk checks dependency and initialize UI and set all hooks for this class
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {

			if ( !$this->check_rt_biz_dependecy() ) {
				return false;
			}

			$this->init_redux();

			$this->init_globals();

			add_action( 'init', array( $this, 'admin_init' ), 5 );
			add_action( 'init', array( $this, 'init' ), 6 );

			add_action( 'wp_enqueue_scripts', array( $this, 'loadScripts' ) );
		}

		/**
		 * Admin_init sets admin UI and functionality,
		 * initialize the database,
		 * set all hooks for admins,
		 * set hook for gravity forms
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function admin_init() {
			$this->templateURL = apply_filters( 'rthd_template_url', 'rthelpdesk/' );

			$this->update_database();

			global $rt_hd_admin;
			$rt_hd_admin = new Rt_HD_Admin();

			global $rt_hd_gravity_form_importer;
			$rt_hd_gravity_form_importer->hd_importer_ajax_hooks();
		}

		/**
		 * check for rt biz dependency and if it does not find any single dependency then it returns false
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 *
		 */
		function check_rt_biz_dependecy() {

			$flag          = true;
			$used_function = array(
				'rt_biz_get_module_users',
				'rt_biz_get_entity_meta',
				'rt_biz_get_post_for_organization_connection',
				'rt_biz_get_post_for_person_connection',
				'rt_biz_get_organization_post_type',
				'rt_biz_get_person_post_type',
				'rt_biz_search_organization',
				'rt_biz_add_organization',
				'rt_biz_organization_connection_to_string',
				'rt_biz_connect_post_to_organization',
				'rt_biz_clear_post_connections_to_organization',
				'rt_biz_sanitize_module_key',
				'rt_biz_get_access_role_cap',
				'rt_biz_get_person_by_email',
				'rt_biz_add_person',
				'rt_biz_add_entity_meta',
				'rt_biz_person_connection_to_string',
				'rt_biz_connect_post_to_person',
				'rt_biz_get_organization_to_person_connection',
				'rt_biz_search_person',
				'rt_biz_connect_organization_to_person',
				'rt_biz_clear_post_connections_to_person',
				'rt_biz_register_person_connection',
				'rt_biz_register_organization_connection',
				'rt_biz_get_organization_capabilities',
				'rt_biz_get_person_capabilities',
				'rt_biz_get_person_meta_fields',
				'rt_biz_get_organization_meta_fields'
			);

			foreach ( $used_function as $fn ) {
				if ( !function_exists( $fn ) ) {
					$flag = false;
				}
			}

			if ( !$flag ) {
				add_action( 'admin_notices', array( $this, 'rt_biz_admin_notice' ) );
			}

			return $flag;
		}

		/**
		 * if rtbiz plugin is not installed or activated it gives notification to user to do so.
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function rt_biz_admin_notice() {
			?>
			<div class="updated">
				<p><?php _e( sprintf( 'rtHelpdesk : It seems that rtBiz plugin is not installed or activated. Please %s / %s it.', '<a href="' . admin_url( 'plugin-install.php?tab=search&s=rt-biz' ) . '">' . __( 'install' ) . '</a>', '<a href="' . admin_url( 'plugins.php' ) . '">' . __( 'activate' ) . '</a>' ), RT_HD_TEXT_DOMAIN ); ?></p>
			</div>
		<?php
		}

		/**
		 * Initialize the redux for setting UI and functionality.
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function init_redux() {
			if ( !class_exists( 'ReduxFramework' ) && file_exists( dirname( __FILE__ ) . '/vendor/redux/ReduxCore/framework.php' ) ) {
				require_once( dirname( __FILE__ ) . '/vendor/redux/ReduxCore/framework.php' );
			}
			if ( !isset( $redux_demo ) && file_exists( dirname( __FILE__ ) . '/settings/redux-config.php' ) ) {
				require_once( dirname( __FILE__ ) . '/settings/redux-config.php' );
			}
		}

		/**
		 * Initialize the global variables,
		 * Register taxonomies
		 * Register Hooks
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function init_globals() {
			global $rt_hd_attributes, $rt_hd_tickets, $rt_hd_acl,
			       $rt_hd_gravity_form_importer, $rt_hd_settings, $rt_hd_logs,
			       $taxonomy_metadata, $rt_hd_module, $rthd_form,
			       $rt_hd_mail_accounts_model,
			       $rt_hd_mail_acl_model, $rt_hd_mail_thread_importer_model,
			       $rt_hd_mail_message_model, $rt_hd_mail_outbound_model,
			       $rt_hd_gravity_fields_mapping_model, $rt_hd_user_settings,
			       $rt_hd_dashboard, $rt_hd_ticket_history_model, $rt_hd_reports,
			       $rt_hd_accounts, $rt_hd_contacts, $rt_hd_closing_reason,
			       $rt_hd_imap_server_model, $rt_hd_gravity_form_mapper,
			       $rt_hd_admin_meta_boxes, $rt_hd_woocommerce;

			$rthd_form = new Rt_Form();

			$rt_hd_mail_accounts_model          = new Rt_HD_Mail_Accounts_Model();
			$rt_hd_mail_acl_model               = new Rt_HD_Mail_ACL_Model();
			$rt_hd_mail_thread_importer_model   = new Rt_HD_Mail_Thread_Importer_Model();
			$rt_hd_mail_message_model           = new Rt_HD_Mail_Message_Model();
			$rt_hd_mail_outbound_model          = new Rt_HD_Mail_Outbound_Model();
			$rt_hd_gravity_fields_mapping_model = new Rt_HD_Gravity_Fields_Mapping_Model();
			$rt_hd_ticket_history_model         = new Rt_HD_Ticket_History_Model();
			$rt_hd_imap_server_model            = new Rt_HD_IMAP_Server_Model();

			$taxonomy_metadata = new Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata();
			$taxonomy_metadata->activate();

			$rt_hd_closing_reason = new Rt_HD_Closing_Reason();
			$rt_hd_attributes     = new Rt_HD_Attributes();
			$rt_hd_module         = new Rt_HD_Module();
			$rt_hd_acl            = new Rt_HD_ACL();
			$rt_hd_accounts       = new Rt_HD_Accounts();
			$rt_hd_contacts       = new Rt_HD_Contacts();
			$rt_hd_tickets        = new Rt_HD_Tickets();

			$rt_hd_dashboard = new Rt_HD_Dashboard();

			$rt_hd_gravity_form_importer = new Rt_HD_Gravity_Form_Importer();
			$rt_hd_gravity_form_mapper   = new Rt_HD_Gravity_Form_Mapper();
			$rt_hd_settings              = new Rt_HD_Settings();
			$rt_hd_user_settings         = new Rt_HD_User_Settings();
			$rt_hd_logs                  = new Rt_HD_Logs();

			$rt_hd_woocommerce = new Rt_HD_Woocommerce();

			$page_slugs    = array(
				'rthd-' . Rt_HD_Module::$post_type . '-dashboard',
			);
			$rt_hd_reports = new Rt_Reports( $page_slugs );

			$rt_hd_admin_meta_boxes = new RT_HD_Admin_Meta_Boxes();
		}

		/**
		 *  Initialize the frontend
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function init() {
			global $rt_hd_tickets_front;
			$rt_hd_tickets_front = new Rt_HD_Tickets_Front();
		}

		/**
		 * update_database() Setup database from schema
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function update_database() {
			$updateDB = new RT_DB_Update( trailingslashit( RT_HD_PATH ) . 'index.php', trailingslashit( RT_HD_PATH_SCHEMA ) );
			$updateDB->do_upgrade();
		}

		/**
		 * Register all js
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function loadScripts() {
			global $wp_query;

			if ( !isset( $wp_query->query_vars['name'] ) ) {
				return;
			}
			$name = $wp_query->query_vars['name'];

			$post_type = rthd_post_type_name( $name );
			if ( $post_type != Rt_HD_Module::$post_type ) {
				return;
			}

			if ( !isset( $_REQUEST['rthd_unique_id'] ) || ( isset( $_REQUEST['rthd_unique_id'] ) && empty( $_REQUEST['rthd_unique_id'] ) ) ) {
				return;
			}

			$args = array(
				'meta_key'    => '_rtbiz_helpdesk_unique_id',
				'meta_value'  => $_REQUEST['rthd_unique_id'],
				'post_status' => 'any',
				'post_type'   => $post_type,
			);

			$ticketpost = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				return;
			}
			$ticket = $ticketpost[0];
			if ( $post_type != $ticket->post_type ) {
				return;
			}

			wp_enqueue_script( 'rt-jquery-tagit-js', RT_HD_URL . 'app/assets/javascripts/tag-it.js', array(
					'jquery',
					'jquery-ui-widget'
				), RT_HD_VERSION, true );
			wp_enqueue_style( 'rt-jquery-tagit-css', RT_HD_URL . 'app/assets/css/jquery.tagit.css', false, RT_HD_VERSION, 'all' );
			wp_enqueue_script( 'jquery-ui-timepicker-addon', RT_HD_URL . 'app/assets/javascripts/jquery-ui-timepicker-addon.js', array(
					"jquery-ui-datepicker",
					"jquery-ui-slider"
				), RT_HD_VERSION, true );

			wp_enqueue_script( 'foundation.zepto', RT_HD_URL . 'app/assets/javascripts/vendor/zepto.js', array( "jquery" ), "", true );
			wp_enqueue_script( 'jquery.foundation.reveal', RT_HD_URL . 'app/assets/javascripts/jquery.foundation.reveal.js', array( "foundation-js" ), "", true );
			wp_enqueue_script( 'jquery.foundation.form', RT_HD_URL . 'app/assets/javascripts/foundation/foundation.forms.js', array( "foundation-js" ), "", true );
			wp_enqueue_script( 'jquery.foundation.tabs', RT_HD_URL . 'app/assets/javascripts/foundation/foundation.section.js', array( "foundation-js" ), "", true );
			wp_enqueue_script( 'foundation-modernizr-js', RT_HD_URL . 'app/assets/javascripts/vendor/custom.modernizr.js', array(), "", false );
			wp_enqueue_script( 'ratting-jquery', RT_HD_URL . 'app/assets/ratting-jquery/jquery.rating.pack.js', array(), RT_HD_VERSION, true );
			wp_enqueue_script( 'foundation-js', RT_HD_URL . 'app/assets/javascripts/foundation/foundation.js', array(
					"jquery",
					"foundation.zepto"
				), RT_HD_VERSION, true );
			wp_enqueue_script( 'sticky-kit', RT_HD_URL . 'app/assets/javascripts/stickyfloat.js', array( 'jquery' ), RT_HD_VERSION, true );
			wp_enqueue_script( 'rthd-app-js', RT_HD_URL . 'app/assets/javascripts/app.js', array(
					'foundation-js',
					'rt-jquery-tagit-js'
				), RT_HD_VERSION, true );


			wp_enqueue_script( 'moment-js', RT_HD_URL . 'app/assets/javascripts/moment.js', array( "jquery" ), RT_HD_VERSION, true );

			if ( !wp_script_is( 'jquery-ui-accordion' ) ) {
				wp_enqueue_script( 'jquery-ui-accordion' );
			}

			wp_enqueue_style( 'ratting-jquery', RT_HD_URL . 'app/assets/ratting-jquery/jquery.rating.css', false, "", 'all' );
			wp_enqueue_style( 'foundation-icon-general-css', RT_HD_URL . 'app/assets/css/general_foundicons.css', false, "", 'all' );
			wp_enqueue_style( 'foundation-icon-general-ie-css', RT_HD_URL . 'app/assets/css/general_foundicons_ie7.css', false, "", 'all' );
			wp_enqueue_style( 'foundation-icon-social-css', RT_HD_URL . 'app/assets/css/social_foundicons.css', false, "", 'all' );
			wp_enqueue_style( 'foundation-icon-social-ie-css', RT_HD_URL . 'app/assets/css/social_foundicons_ie7.css', false, "", 'all' );
			wp_enqueue_style( 'foundation-normalize', RT_HD_URL . 'app/assets/css/legacy_normalize.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-legacy-css', RT_HD_URL . 'app/assets/css/legacy_admin.css', false, '', 'all' );
			wp_enqueue_style( 'rthd-admin-css', RT_HD_URL . 'app/assets/css/admin.css', false, RT_HD_VERSION, 'all' );

			if ( !wp_script_is( 'jquery-ui-datepicker' ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

			if ( !wp_script_is( 'jquery-ui-autocomplete' ) ) {
				wp_enqueue_script( 'jquery-ui-autocomplete', '', array(
						'jquery-ui-widget',
						'jquery-ui-position'
					), '1.9.2', true );
			}

			global $wp_scripts;
			if ( !wp_script_is( 'jquery-ui-core' ) ) {
				$ui = $wp_scripts->query( 'jquery-ui-core' );
			}

			// tell WordPress to load the Smoothness theme from Google CDN
			$protocol = is_ssl() ? 'https' : 'http';
			$url      = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";
			if ( !wp_style_is( 'jquery-ui-smoothness' ) ) {
				wp_enqueue_style( 'jquery-ui-smoothness', $url, false, null );
			}

			$this->localize_scripts();
		}

		/**
		 *  This is functions passes the value to java script
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function localize_scripts() {

			$unique_id  = $_REQUEST['rthd_unique_id'];
			$args       = array(
				'meta_key'    => '_rtbiz_helpdesk_unique_id',
				'meta_value'  => $unique_id,
				'post_status' => 'any',
				'post_type'   => Rt_HD_Module::$post_type,
			);
			$ticketpost = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				return;
			}
			$ticket    = $ticketpost[0];
			$user_edit = false;

			if ( wp_script_is( 'rthd-app-js' ) ) {
				wp_localize_script( 'rthd-app-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
				wp_localize_script( 'rthd-app-js', 'rthd_post_type', get_post_type( $ticket->ID ) );
				wp_localize_script( 'rthd-app-js', 'rthd_user_edit', array( $user_edit ) );
			}
		}

	}

}
