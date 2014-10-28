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
			global $rtbiz_plugins;
			$rtbiz_plugins = array(
				'rtbiz' => array(
					'project_type' => 'all', 'name' => esc_html__( 'WordPress for Business.', 'rt_biz' ), 'active' => class_exists( 'Rt_Biz' ), 'filename' => 'index.php',),
			);

			if ( ! $this->check_rt_biz_dependecy() ) {
				return false;
			}

			$this->init_globals();

			add_action( 'init', array( $this, 'admin_init' ), 5 );
			add_action( 'init', array( $this, 'init' ), 6 );

			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		}

		/**
		 * check for rt biz dependency and if it does not find any single dependency then it returns false
		 *
		 * @since 0.1
		 *
		 * @return bool
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
				'rt_biz_get_organization_meta_fields',
			);

			foreach ( $used_function as $fn ) {
				if ( ! function_exists( $fn ) ) {
					$flag = false;
				}
			}

			if ( ! $flag ) {
				function rtbiz_plugins_enque_js() {
					wp_enqueue_script( 'rtbiz-hd-plugins', RT_HD_URL . 'app/assets/javascripts/rtbiz_plugin_check.js', '', false, true );
					wp_localize_script( 'rtbiz-hd-plugins', 'rtbiz_ajax_url', admin_url( 'admin-ajax.php' ) );
				}
				add_action( 'admin_enqueue_scripts', 'rtbiz_plugins_enque_js' );
				add_action( 'wp_ajax_rtBiz_hd_active_plugin', array( $this, 'rt_biz_hd_activate_plugin_ajax' ), 10 );
				add_action( 'admin_notices', array( $this, 'admin_notice_rtbiz_not_installed' ) );
			}

			return $flag;
		}

		/**
		 * Initialize the global variables for all rtbiz-helpdesk classes
		 *
		 * @since 0.1
		 */
		function init_globals() {

			global $rt_hd_mail_accounts_model, $rt_hd_mail_acl_model, $rt_hd_mail_thread_importer_model, $rt_hd_mail_message_model, $rt_hd_mail_outbound_model, $rt_hd_gravity_fields_mapping_model, $rt_hd_ticket_history_model, $rt_hd_imap_server_model, $rthd_form, $taxonomy_metadata, $rt_hd_reports, $rt_hd_closing_reason, $rt_hd_attributes, $rt_hd_dashboard, $rt_hd_module, $rt_hd_cpt_tickets, $rt_hd_acl, $rt_hd_accounts, $rt_hd_contacts, $rt_hd_tickets_operation, $rt_hd_email_notification, $rt_hd_import_operation, $rt_hd_gravity_form_importer, $rt_hd_gravity_form_mapper, $rt_hd_settings, $rt_hd_user_settings, $rt_hd_logs, $rt_hd_woocommerce_edd;

			//Model class init
			$rt_hd_mail_accounts_model          = new Rt_HD_Mail_Accounts_Model();
			$rt_hd_mail_acl_model               = new Rt_HD_Mail_ACL_Model();
			$rt_hd_mail_thread_importer_model   = new Rt_HD_Mail_Thread_Importer_Model();
			$rt_hd_mail_message_model           = new Rt_HD_Mail_Message_Model();
			$rt_hd_mail_outbound_model          = new Rt_HD_Mail_Outbound_Model();
			$rt_hd_gravity_fields_mapping_model = new Rt_HD_Gravity_Fields_Mapping_Model();
			$rt_hd_ticket_history_model         = new Rt_HD_Ticket_History_Model();
			$rt_hd_imap_server_model            = new Rt_HD_IMAP_Server_Model();

			$rthd_form         = new Rt_Form();
			$taxonomy_metadata = new Rt_Helpdesk_Taxonomy_Metadata\Taxonomy_Metadata();
			$taxonomy_metadata->activate();

			$rt_hd_closing_reason = new Rt_HD_Closing_Reason();
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

			//Setting

			global $rt_hd_redux_framework_Helpdesk_Config, $rt_hd_settings_inbound_email, $rt_hd_import_operation, $rt_hd_settings_imap_server;

			$rt_hd_redux_framework_Helpdesk_Config = new Redux_Framework_Helpdesk_Config();
			$rt_hd_settings_inbound_email          = new RT_HD_Setting_Inbound_Email();
			$rt_hd_import_operation                = new Rt_HD_Import_Operation();
			$rt_hd_settings_imap_server = new RT_HD_Setting_Imap_Server();

			$rt_hd_gravity_form_importer = new Rt_HD_Gravity_Form_Importer();
			$rt_hd_gravity_form_mapper   = new Rt_HD_Gravity_Form_Mapper();
			$rt_hd_settings              = new Rt_HD_Settings();
			$rt_hd_user_settings         = new Rt_HD_User_Settings();
			$rt_hd_logs                  = new Rt_HD_Logs();

			$rt_hd_woocommerce_edd = new Rt_HD_Woocommerce_EDD();
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
			$this->templateURL = apply_filters( 'rthd_template_url', 'rthelpdesk/' );

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
		function load_scripts( $rthd_unique_id = null ) {
			global $wp_query;

			if ( ! isset( $wp_query->query_vars['name'] ) ) {
				return;
			}
			$name = $wp_query->query_vars['name'];

			$post_type = rthd_post_type_name( $name );
			if ( $post_type != Rt_HD_Module::$post_type ) {
				return;
			}

			if ( ( ! isset( $_REQUEST['rthd_unique_id'] ) || empty( $_REQUEST['rthd_unique_id'] ) )&& ( isset( $rthd_unique_id ) && ! empty( $rthd_unique_id ) ) ) {
					$_REQUEST['rthd_unique_id'] = $rthd_unique_id;
			}

			if ( ! isset( $_REQUEST['rthd_unique_id'] ) || empty( $_REQUEST['rthd_unique_id'] ) ){
				return;
			}

			$args = array(
				'meta_key'    => '_rtbiz_hd_unique_id',
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
				'jquery-ui-widget',
			), RT_HD_VERSION, true );
			wp_enqueue_style( 'rt-jquery-tagit-css', RT_HD_URL . 'app/assets/css/jquery.tagit.css', false, RT_HD_VERSION, 'all' );
			wp_enqueue_script( 'jquery-ui-timepicker-addon', RT_HD_URL . 'app/assets/javascripts/jquery-ui-timepicker-addon.js', array(
				'jquery-ui-datepicker',
				'jquery-ui-slider',
			), RT_HD_VERSION, true );

//			wp_enqueue_script( 'foundation.zepto', RT_HD_URL . 'app/assets/javascripts/vendor/zepto.js', array( 'jquery' ), '', true );
			wp_enqueue_script( 'jquery.foundation.reveal', RT_HD_URL . 'app/assets/javascripts/jquery.foundation.reveal.js', array( 'foundation-js' ), '', true );
			wp_enqueue_script( 'jquery.foundation.form', RT_HD_URL . 'app/assets/javascripts/foundation/foundation.forms.js', array( 'foundation-js' ), '', true );
			wp_enqueue_script( 'jquery.foundation.tabs', RT_HD_URL . 'app/assets/javascripts/foundation/foundation.section.js', array( 'foundation-js' ), '', true );
			wp_enqueue_script( 'foundation-modernizr-js', RT_HD_URL . 'app/assets/javascripts/vendor/custom.modernizr.js', array(), '', false );
			wp_enqueue_script( 'ratting-jquery', RT_HD_URL . 'app/assets/ratting-jquery/jquery.rating.pack.js', array(), RT_HD_VERSION, true );
			wp_enqueue_script( 'foundation-js', RT_HD_URL . 'app/assets/javascripts/foundation/foundation.js', array(
				'jquery',
				'foundation.zepto',
			), RT_HD_VERSION, true );
			wp_enqueue_script( 'sticky-kit', RT_HD_URL . 'app/assets/javascripts/stickyfloat.js', array( 'jquery' ), RT_HD_VERSION, true );
			wp_enqueue_script( 'rthd-app-js', RT_HD_URL . 'app/assets/javascripts/app.js', array(
				'foundation-js',
				'rt-jquery-tagit-js',
			), RT_HD_VERSION, true );

			wp_enqueue_script( 'moment-js', RT_HD_URL . 'app/assets/javascripts/moment.js', array( 'jquery' ), RT_HD_VERSION, true );

			if ( ! wp_script_is( 'jquery-ui-accordion' ) ) {
				wp_enqueue_script( 'jquery-ui-accordion' );
			}

			wp_enqueue_style( 'ratting-jquery', RT_HD_URL . 'app/assets/ratting-jquery/jquery.rating.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-icon-general-css', RT_HD_URL . 'app/assets/css/general_foundicons.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-icon-general-ie-css', RT_HD_URL . 'app/assets/css/general_foundicons_ie7.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-icon-social-css', RT_HD_URL . 'app/assets/css/social_foundicons.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-icon-social-ie-css', RT_HD_URL . 'app/assets/css/social_foundicons_ie7.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-normalize', RT_HD_URL . 'app/assets/css/legacy_normalize.css', false, '', 'all' );
			wp_enqueue_style( 'foundation-legacy-css', RT_HD_URL . 'app/assets/css/legacy_admin.css', false, '', 'all' );
			wp_enqueue_style( 'rthd-admin-css', RT_HD_URL . 'app/assets/css/admin.css', false, RT_HD_VERSION, 'all' );

			if ( ! wp_script_is( 'jquery-ui-datepicker' ) ) {
				wp_enqueue_scrispt( 'jquery-ui-datepicker' );
			}

			if ( ! wp_script_is( 'jquery-ui-autocomplete' ) ) {
				wp_enqueue_script( 'jquery-ui-autocomplete', '', array(
					'jquery-ui-widget',
					'jquery-ui-position',
				), '1.9.2', true );
			}

			global $wp_scripts;
			if ( ! wp_script_is( 'jquery-ui-core' ) ) {
				$ui = $wp_scripts->query( 'jquery-ui-core' );
				// tell WordPress to load the Smoothness theme from Google CDN
				$protocol = is_ssl() ? 'https' : 'http';
				$url      = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/" . $ui->ver . '/themes/smoothness/jquery-ui.css';
				if ( ! wp_style_is( 'jquery-ui-smoothness' ) ) {
					wp_enqueue_style( 'jquery-ui-smoothness', $url, false, null );
				}
			}
			$this->localize_scripts();
			return true;
		}
		/**
		 * This is functions localize values for JScript
		 * @since 0.1
		 */
		function localize_scripts( $rthd_unique_id = null ) {

			if ( ! isset( $_REQUEST['rthd_unique_id'] ) || empty( $_REQUEST['rthd_unique_id'] ) ) {
				if ( isset( $rthd_unique_id ) && ! empty( $rthd_unique_id ) ) {
					$_REQUEST['rthd_unique_id'] = $rthd_unique_id;
				} else {
					return;
				}
			}
			$unique_id  = $_REQUEST['rthd_unique_id'];
			$args       = array(
				'meta_key'    => '_rtbiz_hd_unique_id',
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

			return true;
		}



		/**
		 * if rtbiz plugin is not installed or activated it gives notification to user to do so.
		 *
		 * @since 0.1
		 */
		function admin_notice_rtbiz_not_installed() {
			?>
			<div class="error rtBiz-not-installed-error">
			<?php
			if ( $this->is_rt_biz_plugin_installed( 'rtbiz' ) && ! $this->is_rt_biz_plugin_active( 'rtbiz' ) ) {
				$path  = $this->get_path_for_rt_biz_plugins( 'rtbiz' );
				$nonce = wp_create_nonce( 'rtBiz_activate_plugin_' . $path );
				?>
				<p><b><?php _e( 'rtBiz Helpdesk:' ) ?></b> <?php _e( 'Click' ) ?> <a href="#"
				                                                            onclick="activate_rtBiz_plugins('<?php echo $path ?>','rtBiz_hd_active_plugin','<?php echo $nonce; ?>')">here</a> <?php _e( 'to activate rtBiz.', 'rtbiz' ) ?>
				</p>
			<?php } else { ?>
				<p><b><?php _e( 'rtBiz Helpdesk:' ) ?></b> <?php _e( 'rtBiz Core plugin is not found on this site. Please install & activate it in order to use this plugin.', RT_HD_TEXT_DOMAIN ); ?></p>
			<?php } ?>
			</div>
			<?php
		}

		function get_path_for_rt_biz_plugins( $slug ) {
			global $rtbiz_plugins;
			$filename = ( ! empty( $rtbiz_plugins[ $slug ]['filename'] ) ) ? $rtbiz_plugins[ $slug ]['filename'] : $slug . '.php';

			return $slug . '/' . $filename;
		}

		function is_rt_biz_plugin_active( $slug ) {
			global $rtbiz_plugins;
			if ( empty( $rtbiz_plugins[ $slug ] ) ) {
				return false;
			}

			return $rtbiz_plugins[ $slug ]['active'];
		}

		function is_rt_biz_plugin_installed( $slug ) {
			global $rtbiz_plugins;
			if ( empty( $rtbiz_plugins[ $slug ] ) ) {
				return false;
			}

			if ( $this->is_rt_biz_plugin_active( $slug ) || file_exists( WP_PLUGIN_DIR . '/' . $this->get_path_for_rt_biz_plugins( $slug ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * ajax call for active plugin
		 */
		function rt_biz_hd_activate_plugin_ajax() {
			if ( empty( $_POST['path'] ) ) {
				die( __( 'ERROR: No slug was passed to the AJAX callback.', 'rt_biz' ) );
			}
			check_ajax_referer( 'rtBiz_activate_plugin_' . $_POST['path'] );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				die( __( 'ERROR: You lack permissions to activate plugins.', 'rt_biz' ) );
			}

			$this->rt_biz_activate_plugin( $_POST['path'] );

			echo 'true';
			die();
		}

		/**
		 * @param $plugin_path
		 * ajax call for active plugin calls this function to active plugin
		 */
		function rt_biz_activate_plugin( $plugin_path ) {

			$activate_result = activate_plugin( $plugin_path );
			if ( is_wp_error( $activate_result ) ) {
				die( sprintf( __( 'ERROR: Failed to activate plugin: %s', 'rt_biz' ), $activate_result->get_error_message() ) );
			}
		}




	}

}
