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
 * Description of Rt_CRM_Admin
 *
 * @author udit
 */
if( !class_exists( 'Rt_CRM_Admin' ) ) {
	class Rt_CRM_Admin {
		public function __construct() {
			if ( is_admin() ) {
				$this->hooks();
			}
		}

		function load_styles_scripts() {
			global $post, $rt_crm_module;
			$pagearray = array( 'rtcrm-gravity-import', 'rtcrm-settings', 'rtcrm-user-settings', 'rtcrm-logs', 'rtcrm-'.$rt_crm_module->post_type.'-dashboard' );
			if( ( isset( $post->post_type ) && $post->post_type == $rt_crm_module->post_type )
					|| ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pagearray ) )
					|| ( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == $rt_crm_module->post_type ) ) {
				wp_enqueue_script('rt-jquery-tagit', RT_CRM_URL . 'app/assets/javascripts/tag-it.js', array('jquery', 'jquery-ui-widget'), RT_CRM_VERSION, true);
				wp_enqueue_script('rt-custom-status', RT_CRM_URL . 'app/assets/javascripts/rt-custom-status.js', array('jquery'), RT_CRM_VERSION, true);
				wp_enqueue_script('rt-handlebars', RT_CRM_URL . 'app/assets/javascripts/handlebars.js', array('jquery'), RT_CRM_VERSION, true);

				if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'rtcrm-'.$rt_crm_module->post_type.'-dashboard' ) {
					wp_localize_script('rt-custom-status', 'rt_crm_top_menu', 'menu-posts-'.$rt_crm_module->post_type);
					wp_localize_script('rt-custom-status', 'rt_crm_dashboard_url', admin_url( 'edit.php?post_type='.$rt_crm_module->post_type.'&page='.'rtcrm-'.$rt_crm_module->post_type.'-dashboard' ) );
				}

				if( !wp_script_is('jquery-ui-datepicker') ) {
					wp_enqueue_script( 'jquery-ui-datepicker' );
				}
				if( !wp_script_is('jquery-ui-autocomplete') ) {
					wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.9.2');
				}
				if( !wp_script_is('jquery-ui-progressbar') ) {
					wp_enqueue_script('jquery-ui-progressbar','',array('jquery-ui-widget', 'jquery-ui-position'), '1.9.2');
				}
				wp_enqueue_style('rt-crm-css', RT_CRM_URL . 'app/assets/css/rt-crm-css.css', false, RT_CRM_VERSION, 'all');
				wp_enqueue_style('rt-jquery-tagit', RT_CRM_URL . 'app/assets/css/jquery.tagit.css', false, RT_CRM_URL, 'all');
			}
			$pagearray = array( 'rtcrm-add-module', 'rtcrm-gravity-mapper', 'rtcrm-add-'.$rt_crm_module->post_type );
			if ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pagearray ) ) {
				wp_enqueue_script('jquery-ui-timepicker-addon', RT_CRM_URL . 'app/assets/javascripts/jquery-ui-timepicker-addon.js',array("jquery-ui-datepicker","jquery-ui-slider"), RT_CRM_VERSION, true);

				wp_enqueue_script('foundation.zepto', RT_CRM_URL . 'app/assets/javascripts/vendor/zepto.js',array("jquery"), "", true);
				wp_enqueue_script('jquery.foundation.reveal', RT_CRM_URL . 'app/assets/javascripts/jquery.foundation.reveal.js',array("foundation-js"), "", true);
				wp_enqueue_script('jquery.foundation.form', RT_CRM_URL . 'app/assets/javascripts/foundation/foundation.forms.js',array("foundation-js"), "", true);
				wp_enqueue_script('jquery.foundation.tabs', RT_CRM_URL . 'app/assets/javascripts/foundation/foundation.section.js',array("foundation-js"), "", true);
				wp_enqueue_script('foundation-modernizr-js', RT_CRM_URL . 'app/assets/javascripts/vendor/custom.modernizr.js', array(), "", false);
				wp_enqueue_script('ratting-jquery', RT_CRM_URL . 'app/assets/ratting-jquery/jquery.rating.pack.js', array(), RT_CRM_VERSION, true);
				wp_enqueue_script('foundation-js', RT_CRM_URL . 'app/assets/javascripts/foundation/foundation.js',array("jquery","foundation.zepto"), RT_CRM_VERSION, true);
				wp_enqueue_script('sticky-kit', RT_CRM_URL . 'app/assets/javascripts/stickyfloat.js', array('jquery'), RT_CRM_VERSION, true);
				wp_enqueue_script('rtcrm-admin-js', RT_CRM_URL . 'app/assets/javascripts/admin.js',array("foundation-js"), RT_CRM_VERSION, true);
				wp_enqueue_script('moment-js', RT_CRM_URL . 'app/assets/javascripts/moment.js',array("jquery"), RT_CRM_VERSION, true);

				if( !wp_script_is('jquery-ui-accordion') ) {
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

				if ( !  wp_style_is( 'rt-jquery-ui-css' ) ) {
					wp_enqueue_style('rt-jquery-ui-css', RT_HRM_URL . 'app/assets/css/jquery-ui-1.9.2.custom.css', false, RT_CRM_VERSION, 'all');
				}

				wp_enqueue_script( 'postbox' );
			}

			$this->localize_scripts();
		}

		function localize_scripts() {
			global $rt_crm_module;
			if ( wp_script_is( 'rt-custom-status' ) ) {
				wp_localize_script( 'rt-custom-status', 'rtcrm_valid_post_types', $rt_crm_module->post_type );
			}

			$pagearray = array( 'rtcrm-add-module', 'rtcrm-gravity-mapper', 'rtcrm-add-'.$rt_crm_module->post_type );
			if( wp_script_is( 'rtcrm-admin-js' ) && isset( $_REQUEST['post_type'] ) && isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pagearray ) ) {
				$user_edit = false;
				if ( current_user_can( "edit_{$rt_crm_module->post_type}" ) ) {
					$user_edit = true;
				}
				wp_localize_script( 'rtcrm-admin-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
				wp_localize_script( 'rtcrm-admin-js', 'rtcrm_post_type', $_REQUEST['post_type'] );
				wp_localize_script( 'rtcrm-admin-js', 'rtcrm_user_edit', array($user_edit) );
			} else {
				wp_localize_script( 'rtcrm-admin-js', 'rtcrm_user_edit', array('') );
			}
		}

		function hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

			add_action( 'admin_menu', array( $this, 'register_menu' ), 1 );
			add_action( 'admin_bar_menu', array( $this, 'register_toolbar_menu' ), 100 );

			add_filter( 'pre_insert_term', array( $this, 'remove_wocommerce_actions' ), 10, 2 );
		}

		function register_menu() {
			global $rt_crm_module, $rt_crm_gravity_form_importer, $rt_crm_gravity_form_mapper, $rt_crm_logs;

			$crm_logo_url = get_site_option( 'rtcrm_logo_url' );

			if ( empty( $crm_logo_url ) ) {
				$crm_logo_url = RT_CRM_URL.'app/assets/img/crm-16X16.png';
			}

			$admin_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'admin' );
			$editor_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'editor' );
			$author_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'author' );

			add_submenu_page( 'edit.php?post_type='.$rt_crm_module->post_type, __( 'Gravity Importer' ), __( 'Gravity Importer' ), $editor_cap, 'rtcrm-gravity-import', array( $rt_crm_gravity_form_importer, 'ui' ) );
			add_submenu_page( 'edit.php?post_type='.$rt_crm_module->post_type, __( 'Gravity Mapper' ), __( 'Gravity Mapper' ), $editor_cap, 'rtcrm-gravity-mapper', array( $rt_crm_gravity_form_mapper, 'ui' ) );
			add_submenu_page( 'edit.php?post_type='.$rt_crm_module->post_type, __( 'Logs' ), __( 'Logs' ), $editor_cap, 'rtcrm-logs', array( $rt_crm_logs, 'ui' ) );

			add_submenu_page( 'edit.php?post_type='.$rt_crm_module->post_type, __( 'Settings' ), __( 'Settings' ), $admin_cap, 'rtcrm-settings', array( $this, 'settings_ui' ) );
			add_submenu_page( 'edit.php?post_type='.$rt_crm_module->post_type, __( 'User Settings' ), __( 'User Settings' ), $author_cap, 'rtcrm-user-settings', array( $this, 'user_settings_ui' ) );
		}

		function remove_wocommerce_actions( $term, $taxonomy ) {
			$attrs = rtcrm_get_all_attributes();
			$attr_list = array( 'contacts', 'accounts' );
			foreach ($attrs as $attr) {
				if($attr->attribute_store_as == 'taxonomy') {
					$attr_list[] = $attr->attribute_name;
				}
			}
			if ( in_array( $taxonomy, $attr_list ) ) {
				remove_action( "create_term", 'woocommerce_create_term', 5, 3 );
				remove_action( "delete_term", 'woocommerce_delete_term', 5, 3 );
			}
			return $term;
		}

		function register_toolbar_menu( $admin_bar ) {
			global $rt_crm_module;

			$admin_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'admin' );
			$editor_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'editor' );
			$author_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'author' );

			$crm_logo_url = get_site_option( 'rtcrm_logo_url' );
			if ( empty( $crm_logo_url ) ) {
				$crm_logo_url = RT_CRM_URL.'app/assets/img/crm-16X16.png';
			}

			if ( current_user_can( $author_cap ) ) {
				$admin_bar->add_menu( array(
					'id'    => 'rt-wp-crm',
					'title' => '<img src="'.$crm_logo_url.'" style="vertical-align:middle;margin-right:5px" alt="'.__( 'CRM Studio' ).'" title="'.__( 'CRM' ).'" />'.__( 'CRM' ),
					'href'  => admin_url( 'admin.php?page=rtcrm-'.$rt_crm_module->post_type.'-dashboard' ),
					'meta'  => array(
						'title' => __( 'CRM' ),
					),
				));
			}
			if ( current_user_can( $editor_cap ) ) {
				$admin_bar->add_menu( array(
					'id'    => 'rtcrm-gravity-import',
					'parent' => 'rt-wp-crm',
					'title' => __( 'Gravity Import' ),
					'href'  => admin_url( 'admin.php?page=rtcrm-gravity-import' ),
					'meta'  => array(
						'title' => __( 'Gravity Import' ),
					),
				));
				$admin_bar->add_menu( array(
					'id'    => 'rtcrm-gravity-mapper',
					'parent' => 'rt-wp-crm',
					'title' => __( 'Gravity Mapper' ),
					'href'  => admin_url( 'admin.php?page=rtcrm-gravity-Mapper' ),
					'meta'  => array(
						'title' => __( 'Gravity Mapper' ),
					),
				));
			}
			if ( current_user_can( $admin_cap ) ) {
				$admin_bar->add_menu( array(
					'id'    => 'rtcrm-settings',
					'parent' => 'rt-wp-crm',
					'title' => __( 'CRM Settings' ),
					'href'  => admin_url( 'admin.php?page=rtcrm-settings' ),
					'meta'  => array(
						'title' => __( 'CRM Settings' ),
					),
				));
			}
			if ( current_user_can( $editor_cap ) ) {
				$admin_bar->add_menu( array(
					'id'    => 'rtcrm-logs',
					'parent' => 'rt-wp-crm',
					'title' => __( 'Logs' ),
					'href'  => admin_url( 'admin.php?page=rtcrm-logs' ),
					'meta'  => array(
						'title' => __( 'Logs' ),
					),
				));
			}
		}

		function user_settings_ui() {
			global $rt_crm_user_settings;
			$rt_crm_user_settings->ui();
		}

		function settings_ui() {
			global $rt_crm_settings;
			$rt_crm_settings->ui();
		}
	}
}