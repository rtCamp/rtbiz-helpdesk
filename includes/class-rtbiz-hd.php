<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    rtbiz-helpdesk
 * @subpackage rtBiz/includes
 */

/**
 * The core plugin singleton class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    rtBiz
 * @subpackage rtBiz/includes
 * @author     Dipesh <dipesh.kakadiya@rtcamp.com>
 */
if ( ! class_exists( 'Rtbiz_HD' ) ) {
	class Rtbiz_HD {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Rt_Biz_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		public static $loader;

		public static $templateURL;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

			global $rtbiz_hd_plugin_check;

			if ( ! $rtbiz_hd_plugin_check->rtbiz_hd_check_plugin_dependency() ) {
				return false;
			}

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();

			$this->define_public_hooks();

			$this->run();
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
		 * - Plugin_Name_i18n. Defines internationalization functionality.
		 * - Plugin_Name_Admin. Defines all hooks for the admin area.
		 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since     1.0.0
		 * @access    private
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the helping function
			 * core plugin.
			 */
			include_once RTBIZ_HD_PATH . 'admin/helper/rthd-functions.php';
			include_once RTBIZ_HD_PATH . 'vendor/forceutf8/src/ForceUTF8/Encoding.php';
			// this dependency
			//			include_once RTBIZ_HD_PATH . 'vendor/excel_reader2.php';
			//			include_once RTBIZ_HD_PATH . 'vendor/parsecsv.lib.php';
			//			include_once RTBIZ_HD_PATH . 'vendor/simplexlsx.php';

			new RT_WP_Autoload( RTBIZ_HD_PATH . 'lib/rtformhelpers/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'includes/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'migration/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'admin/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'admin/classes' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'admin/classes/models/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'admin/classes/metabox' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'admin/settings/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'admin/helper/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'public/' );
			new RT_WP_Autoload( RTBIZ_HD_PATH . 'public/classes' );

			self::$loader = new Rtbiz_HD_Loader();

		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale() {
			$plugin_i18n = new Rtbiz_HD_i18n();
			$plugin_i18n->set_domain( RTBIZ_HD_TEXT_DOMAIN );

			// called on plugins_loaded hook
			$plugin_i18n->load_plugin_textdomain();
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks() {

			self::$templateURL = apply_filters( 'rtbiz_template_url', 'rtbiz-helpdesk/' );
			global $rtbiz_hd_admin;
			$rtbiz_hd_admin = new RtBiz_HD_Admin( );

			$rtbiz_hd_admin->init_admin();

			if ( is_admin() ) {
				// update menu order of rtbiz menu
				self::$loader->add_action( 'admin_menu', $rtbiz_hd_admin, 'register_menu', 1 );
				self::$loader->add_action( 'custom_menu_order', $rtbiz_hd_admin, 'custom_pages_order' );

				self::$loader->add_filter( 'plugin_action_links_' . RTBIZ_HD_BASE_NAME, $rtbiz_hd_admin, 'plugin_action_links' );
				self::$loader->add_filter( 'plugin_row_meta', $rtbiz_hd_admin, 'plugin_row_meta', 10, 4 );
			}

			self::$loader->add_action( 'admin_init', $rtbiz_hd_admin, 'database_update' );
			self::$loader->add_action( 'admin_init', $rtbiz_hd_admin, 'default_setting' );
			self::$loader->add_action( 'admin_init', $rtbiz_hd_admin, 'rtbiz_hd_welcome' );
			self::$loader->add_filter( 'rtbiz_modules', $rtbiz_hd_admin, 'module_register' );

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_add_hd_error_page', $rtbiz_hd_admin, 'ajax_rtbiz_add_hd_error_page' );
			Rtbiz_HD::$loader->add_action( 'admin_notices', $rtbiz_hd_admin, 'add_error_page_notice' );

			self::$loader->add_filter( 'pre_insert_term', $rtbiz_hd_admin, 'remove_wocommerce_actions', 10, 2 );

			self::$loader->add_filter( 'wp_handle_upload_prefilter', $rtbiz_hd_admin, 'handle_upload_prefilter' );
			self::$loader->add_filter( 'wp_handle_upload', $rtbiz_hd_admin, 'handle_upload' );

			self::$loader->add_action( 'admin_enqueue_scripts', $rtbiz_hd_admin, 'enqueue_styles' );
			self::$loader->add_action( 'admin_enqueue_scripts', $rtbiz_hd_admin, 'enqueue_scripts' );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_public_hooks() {

			$plugin_public = new Rtbiz_HD_Public( );

			self::$loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			self::$loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

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
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}

	}
}
