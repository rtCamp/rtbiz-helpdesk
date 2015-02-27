<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'RT_WP_Helpdesk' ) ) {

	/**
	 * Class RT_WP_Helpdesk
	 * Check Dependency
	 * Main class that initialize the rt-helpdesk Classes.
	 * Load Css/Js for front end
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class RT_WP_Helpdesk {

		/**
		 * @var $templateURL is used to set template's root path
		 *
		 * @since 0.1
		 */
		public $templateURL;

		/**
		 * Constructor of RT_WP_Helpdesk checks dependency and initialize all classes and set all hooks for this class
		 *
		 * @since 0.1
		 */
		public function __construct() {

			if ( ! rthd_check_plugin_dependecy() ) {
				return false;
			}

			global $rthd_messages;
			$rthd_messages = array();

			$this->init_globals();

			add_action( 'init', array( $this, 'admin_init' ), 5 );
			add_action( 'init', array( $this, 'init' ), 6 );

			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		}

		/**
		 * Initialize the global variables for all rtbiz-helpdesk classes
		 *
		 * @since 0.1
		 */
		function init_globals() {

			global  $rt_hd_mail_acl_model, $rt_hd_ticket_history_model, $rthd_form, $taxonomy_metadata, $rt_hd_reports, $rt_hd_attributes, $rt_hd_dashboard, $rt_hd_module, $rt_hd_cpt_tickets, $rt_hd_acl, $rt_hd_accounts, $rt_hd_contacts, $rt_hd_tickets_operation, $rt_hd_email_notification, $rt_hd_gravity_form_importer, $rt_hd_user_settings, $rt_hd_logs, $rt_hd_auto_response, $rt_hd_ticket_index_model;

			//Model class init
			$rt_hd_mail_acl_model               = new Rt_HD_Mail_ACL_Model();
			$rt_hd_ticket_history_model         = new Rt_HD_Ticket_History_Model();
			$rt_hd_ticket_index_model           = new Rt_HD_Ticket_Model();

			$rthd_form         = new Rt_Form();
			$taxonomy_metadata = new Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata();
			$taxonomy_metadata->activate();

			$rt_hd_attributes     = new Rt_HD_Attributes();
			$rt_hd_module         = new Rt_HD_Module();
			$rt_hd_cpt_tickets    = new Rt_HD_CPT_Tickets();

			$page_slugs    = array( 'rthd-' . Rt_HD_Module::$post_type . '-dashboard', );
			$rt_hd_reports = new Rt_Reports( $page_slugs );

			$rt_hd_dashboard          = new Rt_HD_Dashboard();
			$rt_hd_acl                = new Rt_HD_ACL();
			$rt_hd_accounts           = new Rt_HD_Accounts();
			$rt_hd_contacts           = new Rt_HD_Contacts();
			$rt_hd_tickets_operation  = new Rt_HD_Tickets_Operation();
			$rt_hd_email_notification = new RT_HD_Email_Notification();
			$rt_hd_auto_response 	  = new Rt_HD_Auto_Response();

			//Setting

			global $rt_hd_redux_framework_Helpdesk_Config, $rt_hd_import_operation, $rt_hd_offering_support;

			$rt_hd_redux_framework_Helpdesk_Config = new Redux_Framework_Helpdesk_Config();
			$rt_hd_import_operation                = new Rt_HD_Import_Operation();

			$rt_hd_gravity_form_importer = new Rt_HD_Gravity_Form_Importer();
			$rt_hd_user_settings         = new Rt_HD_User_Settings();
			$rt_hd_logs                  = new Rt_HD_Logs();

			$rt_hd_offering_support = new Rt_HD_Offering_Support();
			global $Rt_Hd_Help;
			$Rt_Hd_Help = new Rt_Hd_Help();
		}

		/**
		 * Admin_init sets admin UI and functionality,
		 * initialize the database,
		 *
		 * @since 0.1
		 */
		function admin_init() {
			$this->templateURL = apply_filters( 'rthd_template_url', 'rtbiz-helpdesk/' );

			$this->update_database();

			global $rt_hd_admin, $rt_hd_admin_meta_boxes;
			$rt_hd_admin = new Rt_HD_Admin();
		}

		/**
		 * Setup database from schema if scheme updated
		 * if depend on plugin version [ do_upgrade Called if version change ]
		 *
		 * @since 0.1
		 */
		function update_database() {
			$updateDB = new RT_DB_Update( trailingslashit( RT_HD_PATH ) . 'rtbiz-helpdesk.php', trailingslashit( RT_HD_PATH_SCHEMA ) );
			$updateDB->do_upgrade();
		}

		/**
		 * Initialize the frontend
		 *
		 * @since 0.1
		 */
		function init() {
			global $rt_hd_tickets_front;
			$rt_hd_tickets_front = new Rt_HD_Tickets_Front();
		}

		/**
		 * Register all js
		 *
		 * @since 0.1
		 */
		function load_scripts() {
			global $wp_query, $post;

			if ( ! isset( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rt_HD_Module::$post_type || empty( $post ) ) {
				return;
			}

			wp_enqueue_script( 'jquery-ui-timepicker-addon', RT_HD_URL . 'app/assets/javascripts/jquery-ui-timepicker-addon.js', array(
				'jquery-ui-datepicker',
				'jquery-ui-slider',
			), RT_HD_VERSION, true );

			wp_enqueue_script( 'sticky-kit', RT_HD_URL . 'app/assets/javascripts/stickyfloat.js', array( 'jquery' ), RT_HD_VERSION, true );
			wp_enqueue_script( 'rthd-app-js', RT_HD_URL . 'app/assets/javascripts/app.js', array( 'jquery' ), RT_HD_VERSION, true );
			wp_enqueue_script( 'rthd-app-loadmore', RT_HD_URL . 'app/assets/javascripts/jquery.ba-throttle-debounce.js', array( 'jquery' ), RT_HD_VERSION, true );

			wp_enqueue_script( 'moment-js', RT_HD_URL . 'app/assets/javascripts/moment.js', array( 'jquery' ), RT_HD_VERSION, true );

			if ( ! wp_script_is( 'jquery-ui-datepicker' ) ) {
				wp_enqueue_scrispt( 'jquery-ui-datepicker' );
			}

			if ( ! wp_script_is( 'jquery-ui-autocomplete' ) ) {
				wp_enqueue_script( 'jquery-ui-autocomplete', '', array(
					'jquery-ui-widget',
					'jquery-ui-position',
				), '1.9.2', true );
			}

			wp_enqueue_script( 'jquery-form', array( 'jquery' ), false, true );
			wp_enqueue_script( 'jquery-ui-dialog' );

			wp_enqueue_style( 'rthd-followup-css', RT_HD_URL . 'app/assets/css/follow-up.css', array(), RT_HD_VERSION, 'all' );
			wp_enqueue_media();

			global $wp_scripts;
			$ui = $wp_scripts->query( 'jquery-ui-core' );
			// tell WordPress to load the Smoothness theme from Google CDN
			$protocol = is_ssl() ? 'https' : 'http';
			$url      = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/" . $ui->ver . '/themes/smoothness/jquery-ui.css';
			if ( ! wp_style_is( 'jquery-ui-smoothness' ) ) {
				wp_enqueue_style( 'jquery-ui-smoothness', $url, array(), RT_HD_VERSION, 'all' );
			}
			$this->localize_scripts();
		}
		/**
		 * This is functions localize values for JScript
		 * @since 0.1
		 */
		function localize_scripts() {

			global $post;

			if ( empty( $post ) ) {
				return;
			}

			$user_edit = false;

			if ( wp_script_is( 'rthd-app-js' ) ) {
				wp_localize_script( 'rthd-app-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
				wp_localize_script( 'rthd-app-js', 'rthd_post_type', get_post_type( $post->ID ) );
				wp_localize_script( 'rthd-app-js', 'rthd_user_edit', array( $user_edit ) );
			}

			return true;
		}
	}
}
