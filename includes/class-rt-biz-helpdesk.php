<?php

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_Biz_Helpdesk' ) ) {

	/**
	 * Class Rt_Biz_Helpdesk
	 * Check Dependency
	 * Main class that initialize the rt-helpdesk Classes.
	 * Load Css/Js for front end
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rt_Biz_Helpdesk {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Rt_Biz_Helpdesk_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		static $loader;

		/**
		 * @var $templateURL is used to set template's root path
		 *
		 * @since 0.1
		 */
		public $templateURL;

		/**
		 * Constructor of Rt_Biz_Helpdesk checks dependency and initialize all classes and set all hooks for this class
		 *
		 * @since 0.1
		 */
		public function __construct() {

			include_once RT_BIZ_HD_PATH_INCLUDE . 'rthd-functions.php';

			if ( ! $this->rtbiz_hd_check_plugin_dependency() ) {
				return false;
			}

			$this->load_dependencies();

			global $rthd_messages;
			$rthd_messages = array();

			$this->init_globals();

			$this->define_admin_hooks();
			$this->define_public_hooks();

		}

		/**
		 * Using rt-lib [ RT_WP_Autoload ] class, Includes all files & external Require Libraries with in given directory.
		 *
		 * @since 0.1
		 */
		function load_dependencies() {

			include_once RT_BIZ_HD_PATH_ADMIN . 'vendor/forceutf8/src/ForceUTF8/Encoding.php';
			include_once RT_BIZ_HD_PATH_ADMIN . 'vendor/excel_reader2.php';
			include_once RT_BIZ_HD_PATH_ADMIN . 'vendor/parsecsv.lib.php';
			include_once RT_BIZ_HD_PATH_ADMIN . 'vendor/simplexlsx.php';

			global $rthd_app_autoload, $rthd_admin_autoload, $rthd_admin_metabox_autoload, $rthd_models_autoload, $rthd_helper_autoload, $rthd_settings_autoload, $rthd_form_autoload, $rthd_reports_autoload;
			$rthd_admin_metabox_autoload = new RT_WP_Autoload( RT_BIZ_HD_PATH_ADMIN . 'meta-box/' );
			$rthd_models_autoload        = new RT_WP_Autoload( RT_BIZ_HD_PATH_ADMIN. 'models/' );
			$rthd_settings_autoload      = new RT_WP_Autoload( RT_BIZ_HD_PATH_ADMIN. 'settings/' );
			$rthd_form_autoload          = new RT_WP_Autoload( RT_BIZ_HD_PATH_ADMIN. 'vendor/rtformhelpers/' );
			$rthd_reports_autoload       = new RT_WP_Autoload( RT_BIZ_HD_PATH_ADMIN . 'vendor/rtreports/' );
			new RT_WP_Autoload( RT_BIZ_HD_PATH_INCLUDE );
			new RT_WP_Autoload( RT_BIZ_HD_PATH_ADMIN );
			new RT_WP_Autoload( RT_BIZ_HD_PATH_PUBLIC );

			self::$loader = new Rt_Biz_Helpdesk_Loader();

		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			self::$loader->run();
		}


		/**
		 * Redirect to wizard page or helpdesk dashboard on plugin active.
		 */
		function rt_biz_hd_welcome_to_helpdesk() {
			// Bail if no activation redirect
			if ( ! get_option( '_rthd_activation_redirect' ) ) {
				return;
			}

			// Delete the redirect option
			delete_option( '_rthd_activation_redirect' );

			// Bail if activating from network, or bulk
			if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
				return;
			}

			if ( rt_biz_hd_check_wizard_completed() ) {
				wp_safe_redirect( admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&page=rthd-'.Rt_HD_Module::$post_type.'-dashboard' ) );
			} else {
				wp_safe_redirect( admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&page=rthd-setup-wizard' ) );
			}
			exit;
		}

		/**
		 * Initialize the global variables for all rtbiz-helpdesk classes
		 *
		 * @since 0.1
		 */
		function init_globals() {

			global $rt_hd_mail_acl_model, $rt_hd_ticket_history_model, $rthd_form, $rt_hd_reports, $rt_hd_attributes, $rt_hd_dashboard, $rt_hd_module, $rt_hd_cpt_tickets, $rt_hd_acl, $rt_hd_accounts, $rt_hd_contacts, $rt_hd_tickets_operation, $rt_hd_email_notification, $rt_hd_gravity_form_importer, $rt_hd_logs, $rt_hd_auto_response, $rt_hd_ticket_index_model;

			//Model class init
			$rt_hd_mail_acl_model = new Rt_HD_Mail_ACL_Model();
			$rt_hd_ticket_history_model = new Rt_HD_Ticket_History_Model();
			$rt_hd_ticket_index_model = new Rt_HD_Ticket_Model();

			$rthd_form = new Rt_Form();

			$rt_hd_attributes = new Rt_HD_Attributes();
			$rt_hd_module = new Rt_HD_Module();
			$rt_hd_cpt_tickets = new Rt_HD_CPT_Tickets();

			$page_slugs = array( 'rthd-' . Rt_HD_Module::$post_type . '-dashboard' );
			$rt_hd_reports = new Rt_Reports( $page_slugs );

			$rt_hd_dashboard = new Rt_HD_Dashboard();
			$rt_hd_acl = new Rt_HD_ACL();
			$rt_hd_accounts = new Rt_HD_Accounts();
			$rt_hd_contacts = new Rt_HD_Contacts();
			$rt_hd_tickets_operation = new Rt_HD_Tickets_Operation();
			$rt_hd_email_notification = new RT_HD_Email_Notification();
			$rt_hd_auto_response = new Rt_HD_Auto_Response();

			// setup wizard need to check condition for setup
			global $rt_hd_setup_wizard;
			$rt_hd_setup_wizard = new Rt_HD_setup_wizard();

			//Setting

			global $rt_hd_redux_framework_Helpdesk_Config, $rt_hd_import_operation, $rt_hd_offering_support, $rt_hd_short_code;

			$rt_hd_redux_framework_Helpdesk_Config = new Redux_Framework_Helpdesk_Config();
			$rt_hd_import_operation = new Rt_HD_Import_Operation();

			$rt_hd_gravity_form_importer = new Rt_HD_Gravity_Form_Importer();
			$rt_hd_logs = new Rt_HD_Logs();

			$rt_hd_offering_support = new Rt_HD_Offering_Support();
			$rt_hd_short_code = new RT_HD_Short_Code();

			global $Rt_Hd_Help;
			$Rt_Hd_Help = new Rt_Hd_Help();

			// For ajax request register with WordPress
			$temp_meta_box_contact_blacklist = new RT_Meta_Box_Ticket_Contacts_Blacklist();
		}

		/**
		 * Admin_init sets admin UI and functionality,
		 * initialize the database,
		 *
		 * @since 0.1
		 */
		function admin_init() {
			$this->templateURL = apply_filters( 'rthd_template_url', 'rtbiz-helpdesk/public/template' );
			$this->update_database();
			global $rt_biz_hd_admin;
			$rt_biz_hd_admin = new Rt_Biz_Helpdesk_Admin();
		}

		/**
		 * Setup database from schema if scheme updated
		 * if depend on plugin version [ do_upgrade Called if version change ]
		 *
		 * @since 0.1
		 */
		function update_database() {
			$updateDB = new RT_DB_Update( trailingslashit( RT_BIZ_HD_PATH ) . 'rtbiz-helpdesk.php', trailingslashit( RT_BIZ_HD_PATH_SCHEMA ) );
			$updateDB->do_upgrade();
		}

		/**
		 *  Register all of the hooks related to the admin area functionality of the plugin.
		 */
		private function define_admin_hooks() {

			self::$loader->add_action( 'init', $this, 'admin_init', 5 );
			self::$loader->add_action( 'admin_init', $this, 'rt_biz_hd_welcome_to_helpdesk' );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_public_hooks() {

			global $rt_biz_hd_admin;
			$rt_biz_hd_public = new Rt_Biz_Helpdesk_Public();
			self::$loader->add_action( 'wp_enqueue_scripts', $rt_biz_hd_public, 'enqueue_styles' );
			self::$loader->add_action( 'wp_enqueue_scripts', $rt_biz_hd_public, 'enqueue_scripts' );

		}


		/**
		 * Enqueue js for plugin check
		 */
		function rt_biz_hd_plugin_check_enque_js() {
			wp_enqueue_script( 'rtbiz-hd-plugin-check', RT_BIZ_HD_PATH_PUBLIC. 'js/rt_support_form.js', '', false, true );
			wp_localize_script( 'rtbiz-hd-plugin-check', 'rthd_ajax_url', admin_url( 'admin-ajax.php' ) );
		}

		/**
		 * ajax call for active plugin
		 */
		function rt_biz_hd_activate_plugin_ajax() {
			if ( empty( $_POST['path'] ) ) {
				die( __( 'ERROR: No slug was passed to the AJAX callback.', RT_BIZ_HD_TEXT_DOMAIN ) );
			}
			$rtbizpath = rt_biz_hd_get_path_for_plugin( 'rtbiz' );
			check_ajax_referer( 'rthd_activate_plugin_' . $rtbizpath );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				die( __( 'ERROR: You lack permissions to activate plugins.', RT_BIZ_HD_TEXT_DOMAIN ) );
			}
			$rtbiz_active = rt_biz_hd_is_plugin_active( 'rtbiz' );
			$p2p_active = rt_biz_hd_is_plugin_active( 'posts-to-posts' );
			if ( ! $p2p_active ) {
				$p2ppath = rt_biz_hd_get_path_for_plugin( 'posts-to-posts' );
				rt_biz_hd_activate_plugin( $p2ppath );
			}

			if ( ! $rtbiz_active ) {
				rt_biz_hd_activate_plugin( $rtbizpath );
			}

			echo 'true';
			die();
		}

		/**
		 * check for rt biz dependency and if it does not find any single dependency then it returns false
		 *
		 * @since 0.1
		 *
		 * @return bool
		 */
		public function rtbiz_hd_check_plugin_dependency() {

			global $rthd_plugin_check;
			$rthd_plugin_check = array(
				'rtbiz' => array(
					'project_type' => 'all',
					'name' => esc_html__( 'WordPress for Business.', RT_BIZ_HD_TEXT_DOMAIN ),
					'active' => class_exists( 'Rt_Biz' ),
					'filename' => 'index.php',
				),
				'posts-to-posts' => array(
					'project_type' => 'all',
					'name' => esc_html__( 'Create many-to-many relationships between all types of posts.', RT_BIZ_HD_TEXT_DOMAIN ),
					'active' => class_exists( 'P2P_Autoload' ),
					'filename' => 'posts-to-posts.php',
				),
			);

			$flag = true;

			if ( ! class_exists( 'Rt_Biz' ) || ! did_action( 'rt_biz_init' ) ) {
				$flag = false;
			}

			if ( ! $flag ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'rt_biz_hd_plugin_check_enque_js' ) );
				add_action( 'wp_ajax_rthd_activate_plugin', array( $this, 'rt_biz_hd_activate_plugin_ajax' ) );
				add_action( 'wp_ajax_rthd_install_plugin', array( $this, 'rt_biz_hd_install_plugin_ajax' ) );
				//      add_action( 'admin_notices', 'rt_biz_hd_admin_notice_dependency_not_installed' );
				add_action( 'admin_init', array( $this, 'rt_biz_hd_install_dependency' ) );
			}

			return $flag;
		}

		function rt_biz_hd_install_plugin_ajax() {
			if ( empty( $_POST['plugin_slug'] ) ) {
				die( __( 'ERROR: No slug was passed to the AJAX callback.', RT_BIZ_HD_TEXT_DOMAIN ) );
			}
			check_ajax_referer( 'rthd_install_plugin_rtbiz' );

			if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
				die( __( 'ERROR: You lack permissions to install and/or activate plugins.', RT_BIZ_HD_TEXT_DOMAIN ) );
			}
			$biz_installed = rt_biz_hd_is_plugin_installed( 'rtbiz' );
			$p2p_installed = rt_biz_hd_is_plugin_installed( 'posts-to-posts' );

			if ( ! $p2p_installed ) {
				rt_biz_hd_install_plugin( 'posts-to-posts' );
			}
			if ( ! $biz_installed ) {
				rt_biz_hd_install_plugin( 'rtbiz' );
			}
			echo 'true';
			die();
		}

		/**
		 * install dependency
		 */
		function rt_biz_hd_install_dependency() {
			$biz_installed = rt_biz_hd_is_plugin_installed( 'rtbiz' );
			$p2p_installed = rt_biz_hd_is_plugin_installed( 'posts-to-posts' );
			$isRtbizActionDone = false;
			$isPtopActionDone = false;
			$string = '';

			if ( ! $biz_installed || ! $p2p_installed ) {
				if ( ! $biz_installed ) {
					rt_biz_hd_install_plugin( 'rtbiz' );
					$isRtbizActionDone = true;
					$string = 'installed and activated <strong>rtBiz</strong> plugin.';
				}
				if ( ! $p2p_installed ) {
					rt_biz_hd_install_plugin( 'posts-to-posts' );
					$isPtopActionDone = true;
					$string = 'installed and activated <strong>posts to posts</strong> plugin.';
				}
				if ( $isRtbizActionDone && $isPtopActionDone ) {
					$string = 'installed and activated <strong>rtBiz</strong> plugin and <strong>posts to posts</strong> plugin.';
				}
			}

			$rtbiz_active = rt_biz_hd_is_plugin_active( 'rtbiz' );
			$p2p_active = rt_biz_hd_is_plugin_active( 'posts-to-posts' );
			if ( ! $rtbiz_active || ! $p2p_active ) {
				if ( ! $p2p_active ) {
					$p2ppath = rt_biz_hd_get_path_for_plugin( 'posts-to-posts' );
					rt_biz_hd_activate_plugin( $p2ppath );
					$isRtbizActionDone = true;
					$string = 'activated <strong>posts to posts</strong> plugin.';
				}
				if ( ! $rtbiz_active ) {
					$rtbizpath = rt_biz_hd_get_path_for_plugin( 'rtbiz' );
					rt_biz_hd_activate_plugin( $rtbizpath );
					$isPtopActionDone = true;
					$string = 'activated <strong>rtBiz</strong> plugin.';
				}
				if ( $isRtbizActionDone && $isPtopActionDone ) {
					$string = 'activated <strong>rtBiz</strong> plugin and <strong>posts to posts</strong> plugin.';
				}
			}

			if ( ! empty( $string ) ) {
				$string = 'rtBiz Helpdesk has also  ' . $string;
				update_option( 'rtbiz_helpdesk_dependency_installed', $string );
			}

			if ( rt_biz_hd_check_wizard_completed() ) {
				wp_safe_redirect( admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rthd-rtbiz_hd_ticket-dashboard' ) );
			} else {
				wp_safe_redirect( admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rthd-setup-wizard' ) );
			}
		}


	}

}
