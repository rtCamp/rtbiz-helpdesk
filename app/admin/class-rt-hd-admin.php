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
 * Description of Rt_HD_Admin
 *
 * @author udit
 */
if( !class_exists( 'Rt_HD_Admin' ) ) {
	class Rt_HD_Admin {
            private $hd_settings_tabs, $defualt_tab, $admin_cap, $editor_cap, $author_cap;
		public function __construct() {
			if ( is_admin() ) {
                        $this->hooks();
                                
                        $this->admin_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );
			$this->editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );
			$this->author_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
                        
                                $this->hd_settings_tabs = array(
                                    'my-settings' => array(
                                        'menu_title' => __('My Settings'),
                                        'menu_slug' => 'my-settings',
                                        'capability' => $this->author_cap
                                    ),
                                    'admin-settings' => array(
                                        'menu_title' => __('Admin Settings'),
                                        'menu_slug' => 'admin-settings',
                                        'capability' => $this->admin_cap
                                    ),
                                    'importers' => array(
                                        'menu_title' => __('Importers'),
                                        'menu_slug' => 'importers',
                                        'capability' => $this->editor_cap
                                    ),
                                    'import-mapper' => array(
                                        'menu_title' => __('Import Mapper'),
                                        'menu_slug' => 'import-mapper',
                                        'capability' => $this->editor_cap
                                    ),
                                    'import-logs' => array(
                                        'menu_title' => __('Import Logs'),
                                        'menu_slug' => 'import-logs',
                                        'capability' => $this->editor_cap
                                    ),
                                );
                                
                                $this->defualt_tab='my-settings';
			}
		}

		function load_styles_scripts() {
			global $post, $rt_hd_module, $pagenow;
			$pagearray = array( 'rthd-gravity-import', 'rthd-settings', 'rthd-user-settings', 'rthd-logs', 'rthd-'.Rt_HD_Module::$post_type.'-dashboard' );
			if( ( isset( $post->post_type ) && $post->post_type == Rt_HD_Module::$post_type )
					|| ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pagearray ) )
					|| ( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == Rt_HD_Module::$post_type ) ) {
//				wp_enqueue_script('rt-jquery-tagit', RT_HD_URL . 'app/assets/javascripts/tag-it.js', array('jquery', 'jquery-ui-widget'), RT_HD_VERSION, true);
//				wp_enqueue_script('rt-custom-status', RT_HD_URL . 'app/assets/javascripts/rt-custom-status.js', array('jquery'), RT_HD_VERSION, true);
//				wp_enqueue_script('rt-handlebars', RT_HD_URL . 'app/assets/javascripts/handlebars.js', array('jquery'), RT_HD_VERSION, true);
//
//				if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'rthd-'.Rt_HD_Module::$post_type.'-dashboard' ) {
//					wp_localize_script('rt-custom-status', 'rt_hd_top_menu', 'menu-posts-'.Rt_HD_Module::$post_type);
//					wp_localize_script('rt-custom-status', 'rt_hd_dashboard_url', admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&page='.'rthd-'.Rt_HD_Module::$post_type.'-dashboard' ) );
//				}
//
//				if( !wp_script_is('jquery-ui-datepicker') ) {
//					wp_enqueue_script( 'jquery-ui-datepicker' );
//				}
//				if( !wp_script_is('jquery-ui-autocomplete') ) {
//					wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.9.2');
//				}
//				if( !wp_script_is('jquery-ui-progressbar') ) {
//					wp_enqueue_script('jquery-ui-progressbar','',array('jquery-ui-widget', 'jquery-ui-position'), '1.9.2');
//				}
//				wp_enqueue_style('rt-hd-css', RT_HD_URL . 'app/assets/css/rt-hd-css.css', false, RT_HD_VERSION, 'all');
//				wp_enqueue_style('rt-jquery-tagit', RT_HD_URL . 'app/assets/css/jquery.tagit.css', false, RT_HD_URL, 'all');
			}
			$pagearray = array( 'rthd-add-module', 'rthd-gravity-mapper', 'rthd-add-'.Rt_HD_Module::$post_type );
			if ( isset( $post->post_type ) && $post->post_type == Rt_HD_Module::$post_type && in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) 
                                || ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pagearray ) ) ) {
                            
                                // Date & time picker
				wp_enqueue_script('jquery-ui-timepicker-addon', RT_HD_URL . 'app/assets/javascripts/jquery-ui-timepicker-addon.js',array("jquery-ui-datepicker","jquery-ui-slider"), RT_HD_VERSION, true);
                                if( !wp_script_is('jquery-ui-datepicker') ) {
					wp_enqueue_script( 'jquery-ui-datepicker' );
				}   
                                if ( !  wp_style_is( 'rt-jquery-ui-css' ) ) {
					wp_enqueue_style('rt-jquery-ui-css', RT_HD_URL . 'app/assets/css/jquery-ui-1.9.2.custom.css', false, RT_HD_VERSION, 'all');
				}
                                //Momnet 
                                wp_enqueue_script('moment-js', RT_HD_URL . 'app/assets/javascripts/moment.js',array("jquery"), RT_HD_VERSION, true);
				wp_enqueue_script('rthd-admin-js', RT_HD_URL . 'app/assets/javascripts/admin.js', RT_HD_VERSION, true);
				
                                //autocomplete
                                if( !wp_script_is('jquery-ui-autocomplete') ) {
					wp_enqueue_script('jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.9.2',true);
				}
                                
                                //Admin css
                                wp_enqueue_style('rthd-admin-css', RT_HD_URL . 'app/assets/css/admin_new.css', false, RT_HD_VERSION, 'all');
                                
//				if( !wp_script_is('jquery-ui-accordion') ) {
//					wp_enqueue_script( 'jquery-ui-accordion' );
//				}
				
//				wp_enqueue_script( 'postbox' );
			}

			$this->localize_scripts();
		}

		function localize_scripts() {
			$pagearray = array( 'rthd-add-module', 'rthd-gravity-mapper', 'rthd-add-'.Rt_HD_Module::$post_type );
			if( wp_script_is( 'rthd-admin-js' ) && isset( $_REQUEST['post_type'] ) && isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pagearray ) ) {
				$user_edit = false;
				if ( current_user_can( 'edit_'.Rt_HD_Module::$post_type ) ) {
					$user_edit = true;
				}
				wp_localize_script( 'rthd-admin-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
				wp_localize_script( 'rthd-admin-js', 'rthd_post_type', $_REQUEST['post_type'] );
				wp_localize_script( 'rthd-admin-js', 'rthd_user_edit', array($user_edit) );
			} else {
				wp_localize_script( 'rthd-admin-js', 'rthd_user_edit', array('') );
			}
		}

		function hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

			add_action( 'admin_menu', array( $this, 'register_menu' ), 1 );

			add_filter( 'pre_insert_term', array( $this, 'remove_wocommerce_actions' ), 10, 2 );
		}

		function register_menu() {
			add_submenu_page( 'edit.php?post_type='.Rt_HD_Module::$post_type, __( 'Settings' ), __( 'Settings' ), $this->author_cap, 'rthd-settings', array( $this, 'settings_ui' ) );
		}

		function remove_wocommerce_actions( $term, $taxonomy ) {
			$attrs = rthd_get_all_attributes();
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

		function user_settings_ui() {
			global $rt_hd_user_settings;
			$rt_hd_user_settings->ui();
		}

		function settings_ui() { ?>
     
                    <div class="wrap">
                    <div id="icon-options-general" class="icon32"><br></div><h2><?php _e( 'Helpdesk Settings' ); ?></h2>
                                   <?php $this->settings_ui_tabs();

                                    global $rt_hd_settings, $rt_hd_user_settings, $rt_hd_gravity_form_importer, $rt_hd_gravity_form_mapper, $rt_hd_logs;
                                    $tab=isset( $_GET[ 'tab' ] )? $_GET[ 'tab' ] : $this->defualt_tab ;
                                    
                                    switch ( $tab ) {
                                        
                                        case  'admin-settings' :
                                            
                                            if ( current_user_can( $this->admin_cap ) ) {  
                                                 $rt_hd_settings->ui();
                                            }else{
                                                wp_die('You are not allowed to view this page');
                                            }
                                            
                                            break;
                                            
                                        case 'my-settings' : 
                                            
                                            if ( current_user_can( $this->author_cap ) ) {  
                                                  $rt_hd_user_settings->ui();
                                            }else{
                                                  wp_die('You are not allowed to view this page');
                                            }
                                            
                                            break;
                                            
                                        case 'importers' : 
                                                
                                            if ( current_user_can( $this->editor_cap ) ) {  
                                                 $rt_hd_gravity_form_importer->ui();
                                            }else{
                                                 wp_die('You are not allowed to view this page');
                                            }
                                            
                                            break;
                                            
                                        case 'import-mapper' : 
                                            
                                            if ( current_user_can( $this->editor_cap ) ) {  
                                                 $rt_hd_gravity_form_mapper->ui();
                                            }else{
                                                 wp_die('You are not allowed to view this page');
                                            }
                                            
                                            break;
                                            
                                        case 'import-logs' : 
                                            
                                            if ( current_user_can( $this->editor_cap ) ) {  
                                                  $rt_hd_logs->ui();
                                            }else{
                                                 wp_die('You are not allowed to view this page');
                                            }
                                    
                                    }
                                    ?>
                    </div>
        <?php
                        
			
		}
                
                function settings_ui_tabs(){
                    
                    $current=isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : $this->defualt_tab;
                    echo '<h2 class="nav-tab-wrapper">';
                    foreach ($this->hd_settings_tabs as $tab => $name) {
                        if (current_user_can( $name['capability'] ) ) {
                            
                            $class = ( $tab == $current ) ? ' nav-tab-active' : '';
                            echo '<a class="nav-tab' . $class . '" href="?post_type=rt_ticket&page=rthd-settings&tab=' . $name['menu_slug'] . '">' . $name['menu_title'] . '</a>';

                         }
                        
                        }
                    echo '</h2>';
                    
                }
            }
}