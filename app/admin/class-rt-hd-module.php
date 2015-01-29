<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_HD_Module' ) ) {
	/**
	 * Class Rt_HD_Module
	 * Register rtbiz-HelpDesk CPT [ Ticket ] & statuses
	 * Define connection with other post type [ contact, company ]
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rt_HD_Module {

		/**
		 * @var string Stores Post Type
		 *
		 * @since 0.1
		 */
		static $post_type = 'rtbiz_hd_ticket';
		/**
		 * @var string used in mail subject title - to detect whether it's a Helpdesk mail or not. So no translation
		 *
		 * @since 0.1
		 */
		static $name = 'Helpdesk';
		/**
		 * @var array Labels for rtbiz-HelpDesk CPT [ Ticket ]
		 *
		 * @since 0.1
		 */
		var $labels = array();
		/**
		 * @var array statuses for rtbiz-HelpDesk CPT [ Ticket ]
		 *
		 * @since 0.1
		 */
		var $statuses = array();
		/**
		 * @var array Menu order for rtbiz-HelpDesk
		 *
		 * @since 0.1
		 */
		var $custom_menu_order = array();

		/**
		 * initiate class local Variables
		 *
		 * @since 0.1
		 */
		public function __construct() {
			$this->get_custom_labels();
			$this->get_custom_statuses();
			$this->get_custom_menu_order();
			add_action( 'init', array( $this, 'init_hd' ) );
			$this->hooks();
		}

		/**
		 * get rtbiz-HelpDesk CPT [ Ticket ] labels
		 *
		 * @since 0.1
		 *
		 * @return array
		 */
		function get_custom_labels() {
			$settings     = rthd_get_redux_settings();
			$this->labels = array(
				'name'          => __( 'Ticket', RT_HD_TEXT_DOMAIN ),
				'singular_name' => __( 'Ticket', RT_HD_TEXT_DOMAIN ),
				'menu_name'     => isset( $settings['rthd_menu_label'] ) ? $settings['rthd_menu_label'] : 'Helpdesk',
				'all_items'     => __( 'Tickets', RT_HD_TEXT_DOMAIN ),
				'add_new'       => __( 'Add Ticket', RT_HD_TEXT_DOMAIN ),
				'add_new_item'  => __( 'Add Ticket', RT_HD_TEXT_DOMAIN ),
				'new_item'      => __( 'Add Ticket', RT_HD_TEXT_DOMAIN ),
				'edit_item'     => __( 'Edit Ticket', RT_HD_TEXT_DOMAIN ),
				'view_item'     => __( 'View Ticket', RT_HD_TEXT_DOMAIN ),
				'search_items'  => __( 'Search Tickets', RT_HD_TEXT_DOMAIN ),
			);

			return $this->labels;
		}

		/**
		 * get rtbiz-HelpDesk CPT [ Ticket ] statuses
		 *
		 * @since 0.1
		 *
		 * @return array
		 */
		function get_custom_statuses() {
			$this->statuses = array(
				array(
					'slug'        => 'hd-unanswered',
					'name'        => __( 'Unanswered', RT_HD_TEXT_DOMAIN ),
					'description' => __( 'Ticket is unanswered. It needs to be replied. The default state.', RT_HD_TEXT_DOMAIN ),
				    'style'       => 'padding: 5px; background: #ECA1A1; color: #FFF; border: 1px solid #ECA1A1; border-radius: 5px; font-weight: bold;',
				),
				array(
					'slug'        => 'hd-answered',
					'name'        => __( 'Answered', RT_HD_TEXT_DOMAIN ),
					'description' => __( 'Ticket is answered. Expecting further communication from client', RT_HD_TEXT_DOMAIN ),
					'style'       => 'padding: 5px; background: #82CE87; color: #FFF; border: 1px solid #82CE87; border-radius: 5px; font-weight: bold;',
				),
				array(
					'slug'        => 'hd-archived',
					'name'        => __( 'Archived', RT_HD_TEXT_DOMAIN ),
					'description' => __( 'Ticket is archived. Client can re-open if they wish to.', RT_HD_TEXT_DOMAIN ),
					'style'       => 'padding: 5px; background: #9CB8D4; color: #FFF; border: 1px solid #9CB8D4; border-radius: 5px; font-weight: bold;',
				),
			);

			return $this->statuses;
		}

		/**
		 * get menu order for rtbiz-HelpDesk
		 *
		 * @since 0.1
		 */
		function get_custom_menu_order() {
			global $rt_hd_attributes;
			$this->custom_menu_order = array(
				'rthd-' . self::$post_type . '-dashboard',
				'rthd-all-' . self::$post_type,
				'edit_rtbiz_hd_tickets',
				'edit_rtbiz_hd_tickets',
				'rthd-add-' . self::$post_type,
				$rt_hd_attributes->attributes_page_slug,
			);

			return $this->custom_menu_order;
		}

		/**
		 * register rtbiz-HelpDesk CPT [ Ticket ] & define connection with other post type [ contact, company ]
		 *
		 * @since 0.1
		 */
		function init_hd() {
			$menu_position = 32;
			$this->register_custom_post( $menu_position );

			foreach ( $this->statuses as $status ) {
				$this->register_custom_statuses( $status );
			}

			rt_biz_register_contact_connection( self::$post_type, $this->labels['name'] );

			rt_biz_register_company_connection( self::$post_type, $this->labels['name'] );

			$this->db_ticket_table_update();
		}

		/**
		 * Register CPT ( ticket )
		 *
		 * @since 0.1
		 *
		 * @param $menu_position
		 *
		 * @return object|\WP_Error
		 */
		function register_custom_post( $menu_position ) {
			$settings = rthd_get_redux_settings();

			$args = array(
				'labels'             => $this->labels,
				'public'             => true,
				'publicly_queryable' => true,
				'has_archive'        => true,
				'rewrite'            => array(
					'slug'       => strtolower( $this->labels['name'] ),
				    'with_front' => false,
				),
				'show_ui'            => true, // Show the UI in admin panel
				'menu_icon'          => isset( $settings['rthd_logo_url'] ) ? $settings['rthd_logo_url']['url'] : RT_HD_URL . 'app/assets/img/hd-16X16.png' ,
				'menu_position'      => $menu_position,
				'supports'           => array( 'title', 'editor', 'comments', 'custom-fields', 'revisions' ),
				'capability_type'    => self::$post_type,
				'map_meta_cap'    => true,
			);

			return register_post_type( self::$post_type, $args );
		}

		/**
		 * Register Custom statuses for CPT ( ticket )
		 *
		 * @since 0.1
		 *
		 * @param $status
		 *
		 * @return array|object|string
		 */
		function register_custom_statuses( $status ) {

			return register_post_status( $status['slug'], array(
				'label'       => $status['name'],
				'public'      => true,
				'exclude_from_search' => false,
				'label_count' => _n_noop( "{$status['name']} <span class='count'>(%s)</span>", "{$status['name']} <span class='count'>(%s)</span>" ),
			) );

		}

		/**
		 * update table
		 *
		 * @since 0.1
		 */
		function db_ticket_table_update() {
			global $wpdb;
			$table_name    = rthd_get_ticket_table_name();
			$db_table_name = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
			$updateDB      = new RT_DB_Update( trailingslashit( RT_HD_PATH ) . 'rtbiz-helpdesk.php', trailingslashit( RT_HD_PATH_SCHEMA ) );
			if ( $updateDB->check_upgrade() || $db_table_name != $table_name ) {
				$this->create_database_table();
			}
		}

		/**
		 * create database table
		 *
		 * @since 0.1
		 */
		function create_database_table() {

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			global $rt_hd_attributes_relationship_model, $rt_hd_attributes_model;
			$relations  = $rt_hd_attributes_relationship_model->get_relations_by_post_type( self::$post_type );
			$table_name = rthd_get_ticket_table_name();
			$sql        = "CREATE TABLE {$table_name} (\n" . "id BIGINT(20) NOT NULL AUTO_INCREMENT,\n" . "post_id BIGINT(20),\n" . "post_title TEXT,\n" . "post_content TEXT,\n" . "assignee BIGINT(20),\n" . "date_create TIMESTAMP NOT NULL DEFAULT 0,\n" . "date_create_gmt TIMESTAMP NOT NULL DEFAULT 0,\n" . "date_update TIMESTAMP NOT NULL DEFAULT 0,\n" . "date_update_gmt TIMESTAMP NOT NULL DEFAULT 0,\n" . "post_status VARCHAR(20),\n" . "user_created_by BIGINT(20),\n" . "user_updated_by BIGINT(20),\n" . "last_comment_id BIGINT(20),\n" . "flag VARCHAR(3),\n";

			foreach ( $relations as $relation ) {
				$attr      = $rt_hd_attributes_model->get_attribute( $relation->attr_id );
				if ( 'taxonomy' === $attr->attribute_store_as ){
					$attr_name = str_replace( '-', '_', rtbiz_post_type_name( $attr->attribute_name ) );
				} else {
					$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
				}
				$sql .= "{$attr_name} TEXT,\n";
			}

			$contact_name = rt_biz_get_contact_post_type();
			$sql .= "{$contact_name} TEXT,\n";

			$contact_name = rt_biz_get_company_post_type();
			$sql .= "{$contact_name} TEXT,\n";

			$sql .= 'PRIMARY KEY  (id) ) CHARACTER SET utf8 COLLATE utf8_general_ci;';

			dbDelta( $sql );
		}

		/**
		 * set hooks
		 *
		 * @since 0.1
		 */
		function hooks() {
			add_filter( 'custom_menu_order', array( $this, 'custom_pages_order' ) );

			add_action( 'rt_attributes_relations_added', array( $this, 'create_database_table' ) );
			add_action( 'rt_attributes_relations_updated', array( $this, 'create_database_table' ) );
			add_action( 'rt_attributes_relations_deleted', array( $this, 'create_database_table' ) );

			add_action( 'rt_attributes_relations_added', array( $this, 'update_ticket_table' ), 10, 2 );
			add_action( 'rt_attributes_relations_updated', array( $this, 'update_ticket_table' ), 10, 1 );
			add_action( 'rt_attributes_relations_deleted', array( $this, 'update_ticket_table' ), 10, 1 );

			add_action( 'wp_before_admin_bar_render', array( $this, 'ticket_chnage_action_publish_update' ), 11 );
		}

		/**
		 * Update ticket table
		 *
		 * @since 0.1
		 *
		 * @param $attr_id
		 * @param $post_types
		 */

		function update_ticket_table( $attr_id, $post_types = array() ) {
			if ( isset( $post_types ) && in_array( self::$post_type, $post_types ) ) {
				$updateDB = new RT_DB_Update( trailingslashit( RT_HD_PATH ) . 'rtbiz-helpdesk.php', trailingslashit( RT_HD_PATH_SCHEMA ) );
				delete_option( $updateDB->db_version_option_name );
			}
		}

		/**
		 * Change the publish action to update on Cpt-ticket add/edit page
		 *
		 * @since 0.1
		 *
		 * @global type $pagenow
		 * @global type $post
		 */
		function ticket_chnage_action_publish_update() {
			global $pagenow, $post;
			if ( get_post_type() == self::$post_type && (  'post.php' === $pagenow ||'edit.php' === $pagenow || 'post-new.php' === $pagenow || 'edit' == ( isset( $_GET['action'] ) && $_GET['action'] ) ) ) {
				if ( ! isset( $post ) ) {
					return;
				}
				echo '
				<script>
				jQuery(document).ready(function($){
					$("#publishing-action").html("<span class=\"spinner\"> <\/span><input name=\"original_publish\" type=\"hidden\" id=\"original_publish\" value=\"Update\"><input type=\"submit\" id=\"save-publish\" class=\"button button-primary button-large\" value=\"Update\" ><\/input>");
					$(".save-post-status").click(function(){
						$("#publish").hide();
						$("#publishing-action").html("<span class=\"spinner\"><\/span><input name=\"original_publish\" type=\"hidden\" id=\"original_publish\" value=\"Update\"><input type=\"submit\" id=\"save-publish\" class=\"button button-primary button-large\" value=\"Update\" ><\/input>");
					});
					$("#save-publish").click(function(){
						$("#publish").click();
					});
					$("#post-status-select").removeClass("hide-if-js");
				});
				</script>';
			}
		}

		/**
		 * Customize menu item order
		 *
		 * @since 0.1
		 *
		 * @param $menu_order
		 *
		 * @return mixed
		 */
		function custom_pages_order( $menu_order ) {
			global $submenu;
			global $menu;
			if ( isset( $submenu[ 'edit.php?post_type=' . self::$post_type ] ) && ! empty( $submenu[ 'edit.php?post_type=' . self::$post_type ] ) ) {
				$module_menu = $submenu[ 'edit.php?post_type=' . self::$post_type ];
				unset( $submenu[ 'edit.php?post_type=' . self::$post_type ] );
				//unset($module_menu[5]);
				//unset($module_menu[10]);
				$new_index = 5;
				foreach ( $this->custom_menu_order as $item ) {
					foreach ( $module_menu as $p_key => $menu_item ) {
						$out = array_filter( $menu_item, function( $in ) { return true !== $in; } );
						if ( in_array( $item, $out ) ) {
							$submenu[ 'edit.php?post_type=' . self::$post_type ][ $new_index ] = $menu_item;
							unset( $module_menu[ $p_key ] );
							$new_index += 5;
							break;
						}
					}
				}
				foreach ( $module_menu as $p_key => $menu_item ) {
					if ( $menu_item[2] != Redux_Framework_Helpdesk_Config::$page_slug ) {
						$menu_item[0] = '--- ' . $menu_item[0];
					}
					$submenu[ 'edit.php?post_type=' . self::$post_type ][ $new_index ] = $menu_item;
					unset( $module_menu[ $p_key ] );
					$new_index += 5;
				}
			}

			return $menu_order;
		}
	}
}