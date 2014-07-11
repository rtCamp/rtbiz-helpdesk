<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RT_WP_CRM
 *
 * @author udit
 */
if ( ! class_exists( 'RT_WP_CRM' ) ) {
	class RT_WP_CRM {

		public $templateURL;

		public function __construct() {

			if ( ! $this->check_rt_biz_dependecy() ) {
				return false;
			}

            $this->init_globals();

			add_action( 'init', array( $this, 'admin_init' ), 5 );
			add_action( 'init', array( $this, 'init' ), 6 );

			add_action( 'wp_enqueue_scripts', array( $this, 'loadScripts' ) );
		}

		function admin_init() {
			$this->templateURL = apply_filters('rtcrm_template_url', 'rtcrm/');

			$this->update_database();

			global $rt_crm_admin;
			$rt_crm_admin = new Rt_CRM_Admin();

			global $rt_crm_gravity_form_importer;
			$rt_crm_gravity_form_importer->crm_importer_ajax_hooks();
		}

		function check_rt_biz_dependecy() {

			$flag = true;
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
				'rt_biz_register_person_connection',
				'rt_biz_register_organization_connection',
				'rt_biz_get_organization_capabilities',
				'rt_biz_get_person_capabilities',
				'rt_biz_get_person_meta_fields',
				'rt_biz_get_organization_meta_fields'
			);

			foreach ( $used_function as $fn ) {
				if ( ! function_exists( $fn ) ) {
					$flag = false;
				}
			}

			if ( ! class_exists( 'Rt_Biz' ) ) {
				$flag = false;
			}

			if ( ! $flag ) {
				add_action( 'admin_notices', array( $this, 'rt_biz_admin_notice' ) );
			}

			return $flag;
		}

		function rt_biz_admin_notice() { ?>
			<div class="updated">
				<p><?php _e( sprintf( 'rtCRM : It seems that rtBiz plugin is not installed or activated. Please %s / %s it.', '<a href="'.admin_url( 'plugin-install.php?tab=search&s=rt-contacts' ).'">'.__( 'install' ).'</a>', '<a href="'.admin_url( 'plugins.php' ).'">'.__( 'activate' ).'</a>' ) ); ?></p>
			</div>
		<?php }

		function init_globals() {
			global $rt_crm_attributes, $rt_crm_leads, $rt_crm_acl,
					$rt_crm_gravity_form_importer, $rt_crm_settings, $rt_crm_logs,
					$taxonomy_metadata, $rt_crm_module, $rtcrm_form,
					$rt_crm_mail_accounts_model,
					$rt_crm_mail_acl_model, $rt_crm_mail_thread_importer_model,
					$rt_crm_mail_message_model, $rt_crm_mail_outbound_model,
					$rt_crm_gravity_fields_mapping_model, $rt_crm_user_settings,
					$rt_crm_dashboard, $rt_crm_lead_history_model, $rt_reports,
					$rt_crm_accounts, $rt_crm_contacts, $rt_crm_closing_reason,
					$rt_crm_imap_server_model, $rt_crm_gravity_form_mapper;

			$rtcrm_form = new Rt_Form();

			$rt_crm_mail_accounts_model = new Rt_CRM_Mail_Accounts_Model();
			$rt_crm_mail_acl_model = new Rt_CRM_Mail_ACL_Model();
			$rt_crm_mail_thread_importer_model = new Rt_CRM_Mail_Thread_Importer_Model();
			$rt_crm_mail_message_model = new Rt_CRM_Mail_Message_Model();
			$rt_crm_mail_outbound_model = new Rt_CRM_Mail_Outbound_Model();
			$rt_crm_gravity_fields_mapping_model = new Rt_CRM_Gravity_Fields_Mapping_Model();
			$rt_crm_lead_history_model = new Rt_CRM_Lead_History_Model();
			$rt_crm_imap_server_model = new Rt_CRM_IMAP_Server_Model();

			$taxonomy_metadata = new Taxonomy_Metadata();
			$taxonomy_metadata->activate();

			$rt_crm_closing_reason = new Rt_CRM_Closing_Reason();
			$rt_crm_attributes = new Rt_CRM_Attributes();
			$rt_crm_module = new Rt_CRM_Module();
			$rt_crm_acl = new Rt_CRM_ACL();
//			$rt_crm_roles = new Rt_CRM_Roles();
			$rt_crm_accounts = new Rt_CRM_Accounts();
			$rt_crm_contacts = new Rt_CRM_Contacts();
			$rt_crm_leads = new Rt_CRM_Leads();

			$rt_crm_dashboard = new Rt_CRM_Dashboard();

			$rt_crm_gravity_form_importer = new Rt_CRM_Gravity_Form_Importer();
			$rt_crm_gravity_form_mapper= new Rt_CRM_Gravity_Form_Mapper();
			$rt_crm_settings = new Rt_CRM_Settings();
			$rt_crm_user_settings = new Rt_CRM_User_Settings();
			$rt_crm_logs = new Rt_CRM_Logs();

			$page_slugs = array(
				'rtcrm-'.$rt_crm_module->post_type.'-dashboard',
			);
			$rt_reports = new Rt_Reports( $page_slugs );
		}

		function init() {
			global $rt_crm_leads_front;
			$rt_crm_leads_front = new Rt_CRM_Leads_Front();
		}

		function update_database() {
			$updateDB = new RT_DB_Update( trailingslashit( RT_CRM_PATH ) . 'index.php', trailingslashit( RT_CRM_PATH_SCHEMA ) );
			$updateDB->do_upgrade();
		}

		function loadScripts() {
			global $wp_query, $rt_crm_module;

			if ( !isset($wp_query->query_vars['name']) ) {
				return;
			}

			$name = $wp_query->query_vars['name'];

			$post_type = rtcrm_post_type_name( $name );
			if( $post_type != $rt_crm_module->post_type ) {
				return;
			}

			if( !isset( $_REQUEST['rtcrm_unique_id'] ) || (isset($_REQUEST['rtcrm_unique_id']) && empty($_REQUEST['rtcrm_unique_id'])) ) {
				return;
			}

			$args = array(
				'meta_key' => 'rtcrm_unique_id',
				'meta_value' => $_REQUEST['rtcrm_unique_id'],
				'post_status' => 'any',
				'post_type' => $post_type,
			);

			$leadpost = get_posts( $args );
			if( empty( $leadpost ) ) {
				return;
			}
			$lead = $leadpost[0];
			if( $post_type != $lead->post_type ) {
				return;
			}


			wp_enqueue_script('rt-jquery-tagit-js', RT_CRM_URL . 'app/assets/javascripts/tag-it.js', array( 'jquery', 'jquery-ui-widget' ), RT_CRM_VERSION, true);
			wp_enqueue_style('rt-jquery-tagit-css', RT_CRM_URL . 'app/assets/css/jquery.tagit.css', false, RT_CRM_VERSION, 'all');
			wp_enqueue_script('jquery-ui-timepicker-addon', RT_CRM_URL . 'app/assets/javascripts/jquery-ui-timepicker-addon.js',array("jquery-ui-datepicker","jquery-ui-slider"), RT_CRM_VERSION, true);

			wp_enqueue_script('foundation.zepto', RT_CRM_URL . 'app/assets/javascripts/vendor/zepto.js',array("jquery"), "", true);
			wp_enqueue_script('jquery.foundation.reveal', RT_CRM_URL . 'app/assets/javascripts/jquery.foundation.reveal.js',array("foundation-js"), "", true);
			wp_enqueue_script('jquery.foundation.form', RT_CRM_URL . 'app/assets/javascripts/foundation/foundation.forms.js',array("foundation-js"), "", true);
			wp_enqueue_script('jquery.foundation.tabs', RT_CRM_URL . 'app/assets/javascripts/foundation/foundation.section.js',array("foundation-js"), "", true);
			wp_enqueue_script('foundation-modernizr-js', RT_CRM_URL . 'app/assets/javascripts/vendor/custom.modernizr.js', array(), "", false);
			wp_enqueue_script('ratting-jquery', RT_CRM_URL . 'app/assets/ratting-jquery/jquery.rating.pack.js', array(), RT_CRM_VERSION, true);
			wp_enqueue_script('foundation-js', RT_CRM_URL . 'app/assets/javascripts/foundation/foundation.js',array("jquery","foundation.zepto"), RT_CRM_VERSION, true);
			wp_enqueue_script('sticky-kit', RT_CRM_URL . 'app/assets/javascripts/stickyfloat.js', array('jquery'), RT_CRM_VERSION, true);
			wp_enqueue_script('rtcrm-app-js', RT_CRM_URL . 'app/assets/javascripts/app.js', array( 'foundation-js', 'rt-jquery-tagit-js' ), RT_CRM_VERSION, true);


			wp_enqueue_script('moment-js', RT_CRM_URL . 'app/assets/javascripts/moment.js',array("jquery"), RT_CRM_VERSION, true);

			if( ! wp_script_is( 'jquery-ui-accordion' ) ) {
				wp_enqueue_script( 'jquery-ui-accordion' );
			}

			wp_enqueue_style('ratting-jquery', RT_CRM_URL . 'app/assets/ratting-jquery/jquery.rating.css', false, "", 'all');
			wp_enqueue_style('foundation-icon-general-css', RT_CRM_URL . 'app/assets/css/general_foundicons.css', false, "", 'all');
			wp_enqueue_style('foundation-icon-general-ie-css', RT_CRM_URL . 'app/assets/css/general_foundicons_ie7.css', false, "", 'all');
			wp_enqueue_style('foundation-icon-social-css', RT_CRM_URL . 'app/assets/css/social_foundicons.css', false, "", 'all');
			wp_enqueue_style('foundation-icon-social-ie-css', RT_CRM_URL . 'app/assets/css/social_foundicons_ie7.css', false, "", 'all');
			wp_enqueue_style('foundation-normalize', RT_CRM_URL . 'app/assets/css/legacy_normalize.css', false, '', 'all');
			wp_enqueue_style('foundation-legacy-css', RT_CRM_URL . 'app/assets/css/legacy_admin.css', false, '', 'all');
			wp_enqueue_style('rtcrm-admin-css', RT_CRM_URL . 'app/assets/css/admin.css', false, RT_CRM_VERSION, 'all');

			if( !wp_script_is('jquery-ui-datepicker') ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

			if( !wp_script_is('jquery-ui-autocomplete') ) {
				wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.9.2',true);
			}

			global $wp_scripts;
			if ( ! wp_script_is( 'jquery-ui-core' ) ) {
				$ui = $wp_scripts->query( 'jquery-ui-core' );
			}

			// tell WordPress to load the Smoothness theme from Google CDN
			$protocol = is_ssl() ? 'https' : 'http';
			$url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.css";
			if ( ! wp_style_is( 'jquery-ui-smoothness' ) ) {
				wp_enqueue_style( 'jquery-ui-smoothness', $url, false, null );
			}

			$this->localize_scripts();
		}

		function localize_scripts() {

			$unique_id = $_REQUEST['rtcrm_unique_id'];
			global $rt_crm_module;
			$args = array(
				'meta_key' => 'rtcrm_unique_id',
				'meta_value' => $unique_id,
				'post_status' => 'any',
				'post_type' => $rt_crm_module->post_type,
			);
			$leadpost = get_posts( $args );
			if( empty( $leadpost ) ) {
				return;
			}
			$lead = $leadpost[0];

			$user_edit = false;

			if( wp_script_is( 'rtcrm-app-js' ) ) {
				wp_localize_script( 'rtcrm-app-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
				wp_localize_script( 'rtcrm-app-js', 'rtcrm_post_type', get_post_type( $lead->ID ) );
				wp_localize_script( 'rtcrm-app-js', 'rtcrm_user_edit', array($user_edit) );
			}
		}
	}
}
