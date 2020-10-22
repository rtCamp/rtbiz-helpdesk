<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Module' ) ) {
	/**
	 * Class Rt_HD_Module
	 * Register rtbiz-HelpDesk CPT [ Ticket ] & statuses
	 * Define connection with other post type [ contact, company ]
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rtbiz_HD_Module {

		/**
		 * @var string Stores Post Type
		 *
		 * @since 0.1
		 */
		static $post_type = 'ticket';
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
		 * initiate class local Variables
		 *
		 * @since 0.1
		 */
		public function __construct() {
			$this->get_custom_labels();
			$this->get_custom_statuses();
			Rtbiz_HD::$loader->add_action( 'init', $this, 'init_hd' );
			Rtbiz_HD::$loader->add_action( 'rt_db_update_finished', $this, 'db_ticket_table_update' );

			Rtbiz_HD::$loader->add_action( 'rt_attributes_relations_added', $this, 'create_database_table' );
			Rtbiz_HD::$loader->add_action( 'rt_attributes_relations_updated', $this, 'create_database_table' );
			Rtbiz_HD::$loader->add_action( 'rt_attributes_relations_deleted', $this, 'create_database_table' );

			Rtbiz_HD::$loader->add_action( 'rt_attributes_relations_added', $this, 'update_ticket_table', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'rt_attributes_relations_updated', $this, 'update_ticket_table', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'rt_attributes_relations_deleted', $this, 'update_ticket_table', 10, 2 );

			/*rtbiz_register_p2p_connection( self::$post_type, self::$post_type, array(
					'name' => self::$post_type . '_to_' . self::$post_type,
					'from' => self::$post_type,
					'to' => self::$post_type,
					'reciprocal' => true,
					'title' => 'Related ' . $this->labels['all_items'],
				) );*/

			Rtbiz_HD::$loader->add_action( 'bulk_post_updated_messages', $this, 'bulk_ticket_update_messages', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'post_updated_messages', $this, 'ticket_updated_messages', 10, 2 );

			Rtbiz_HD::$loader->add_action( 'p2p_connectable_args', $this, 'p2p_hook_for_rthd_post_filter', 10, 3 );
			Rtbiz_HD::$loader->add_action( 'p2p_candidate_title', $this, 'p2p_hook_for_changing_post_title', 10, 3 );
			Rtbiz_HD::$loader->add_action( 'p2p_connected_title', $this, 'p2p_hook_for_changing_post_title', 10, 3 );

			Rtbiz_HD::$loader->add_action( 'wp_before_admin_bar_render', $this, 'ticket_chnage_action_publish_update', 11 );

			if ( rtbiz_hd_get_redux_adult_filter() && isset( $_GET['post_type'] ) && $_GET['post_type'] == self::$post_type ) {
				Rtbiz_HD::$loader->add_action( 'parse_query', $this, 'adult_post_filter' );
			}
		}

		/**
		 * get rtbiz-HelpDesk CPT [ Ticket ] labels
		 *
		 * @since 0.1
		 *
		 * @return array
		 */
		public function get_custom_labels() {
			$this->labels = array(
				'name'          => __( 'Tickets', 'rtbiz-helpdesk' ),
				'singular_name' => __( 'Ticket', 'rtbiz-helpdesk' ),
				'menu_name'     => 'Helpdesk',
				'all_items'     => __( 'Tickets', 'rtbiz-helpdesk' ),
				'add_new'       => __( 'Add New Ticket', 'rtbiz-helpdesk' ),
				'add_new_item'  => __( 'Add New Ticket', 'rtbiz-helpdesk' ),
				'new_item'      => __( 'Add New Ticket', 'rtbiz-helpdesk' ),
				'edit_item'     => __( 'Edit Ticket', 'rtbiz-helpdesk' ),
				'view_item'     => __( 'View Ticket', 'rtbiz-helpdesk' ),
				'search_items'  => __( 'Search Tickets', 'rtbiz-helpdesk' ),
				'parent_item_colon'  => __( 'Parent Tickets', 'rtbiz-helpdesk' ),
				'not_found'  => __( 'No Tickets found', 'rtbiz-helpdesk' ),
				'not_found_in_trash'  => __( 'No Tickets found in Trash', 'rtbiz-helpdesk' ),
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
		public function get_custom_statuses() {
			$this->statuses = array(
				array(
					'slug'        => 'hd-answered',
					'name'        => __( 'Answered', 'rtbiz-helpdesk' ),
					'description' => __( 'Ticket is answered. Expecting further communication from client', 'rtbiz-helpdesk' ),
				),
				array(
					'slug'        => 'hd-unanswered',
					'name'        => __( 'Unanswered', 'rtbiz-helpdesk' ),
					'description' => __( 'Ticket is unanswered. It needs to be replied. The default state.', 'rtbiz-helpdesk' ),
				),
				array(
					'slug'        => 'hd-archived',
					'name'        => __( 'Archived', 'rtbiz-helpdesk' ),
					'description' => __( 'Ticket is archived. Client can re-open if they wish to.', 'rtbiz-helpdesk' ),
				),
			);

			return $this->statuses;
		}

		/**
		 * register rtbiz-HelpDesk CPT [ Ticket ] & define connection with other post type [ contact, company ]
		 *
		 * @since 0.1
		 */
		public function init_hd() {
			$menu_position = 32;
			$this->register_custom_post( $menu_position );

			foreach ( $this->statuses as $status ) {
				$this->register_custom_statuses( $status );
			}

			rtbiz_register_contact_connection( self::$post_type, array(
				'admin_column' => 'from',
				'from_labels' => array(
					'column_title' => $this->labels['name'],
				),
			) );

			//rtbiz_register_company_connection( self::$post_type, $this->labels['name'] );

			//$this->db_ticket_table_update();
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
		public function register_custom_post( $menu_position ) {

			$logo = apply_filters( 'rthd_helpdesk_logo', RTBIZ_HD_URL . 'public/img/hd-16X16.png' );

			$args = array(
				'labels'             => $this->labels,
				'public'             => true,
				'publicly_queryable' => true,
				'has_archive'        => true,
				'rewrite'            => array(
					'slug'       => strtolower( $this->labels['singular_name'] ),
				    'with_front' => false,
				),
				'show_ui'            => true, // Show the UI in admin panel
				'menu_icon'          => $logo,
				'menu_position'      => $menu_position,
				'supports'           => array( 'title', 'editor', 'comments', 'revisions' ),
				'capability_type'    => self::$post_type,
				'exclude_from_search'=> true,
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
		public function register_custom_statuses( $status ) {

			return register_post_status( $status['slug'], array(
				'label'       => $status['name'],
				'public'      => true,
				'exclude_from_search' => false,
				'label_count' => _n_noop( "{$status['name']} <span class='count'>(%s)</span>", "{$status['name']} <span class='count'>(%s)</span>", 'rtbiz-helpdesk' ),
			) );

		}

		/**
		 * update table
		 *
		 * @since 0.1
		 */
		public function db_ticket_table_update() {
			global $wpdb;
			$table_name    = rtbiz_hd_get_ticket_table_name();
			$db_table_name = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
			$updateDB      = new RT_DB_Update( trailingslashit( RTBIZ_HD_PATH ) . 'rtbiz-helpdesk.php', trailingslashit( RTBIZ_HD_PATH . 'admin/schema/' ) );
			if ( $updateDB->check_upgrade() || $db_table_name != $table_name ) {
				$this->create_database_table();
			}
		}

		/**
		 * create database table
		 *
		 * @since 0.1
		 */
		public function create_database_table() {

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			global $rtbiz_hd_attributes_relationship_model, $rtbiz_hd_attributes_model;
			$relations  = $rtbiz_hd_attributes_relationship_model->get_relations_by_post_type( self::$post_type );
			$table_name = rtbiz_hd_get_ticket_table_name();
			$sql        = "CREATE TABLE {$table_name} (\n" . "id BIGINT(20) NOT NULL AUTO_INCREMENT,\n" . "post_id BIGINT(20),\n" . "post_title TEXT,\n" . "post_content TEXT,\n" . "assignee BIGINT(20),\n" . "date_create TIMESTAMP NOT NULL DEFAULT 0,\n" . "date_create_gmt TIMESTAMP NOT NULL DEFAULT 0,\n" . "date_update TIMESTAMP NOT NULL DEFAULT 0,\n" . "date_update_gmt TIMESTAMP NOT NULL DEFAULT 0,\n" . "post_status VARCHAR(20),\n" . "user_created_by BIGINT(20),\n" . "user_updated_by BIGINT(20),\n" . "last_comment_id BIGINT(20),\n" . "flag VARCHAR(3),\n";

			foreach ( $relations as $relation ) {
				$attr      = $rtbiz_hd_attributes_model->get_attribute( $relation->attr_id );
				if ( 'taxonomy' === $attr->attribute_store_as ) {
					$attr_name = str_replace( '-', '_', rtbiz_post_type_name( $attr->attribute_name ) );
				} else {
					$attr_name = str_replace( '-', '_', rtbiz_hd_attribute_taxonomy_name( $attr->attribute_name ) );
				}
				$sql .= "{$attr_name} TEXT,\n";
			}

			$contact_name = rtbiz_get_contact_post_type();
			$sql .= "{$contact_name} TEXT,\n";

			$contact_name = rtbiz_get_company_post_type();
			$sql .= "{$contact_name} TEXT,\n";

			$sql .= 'PRIMARY KEY  (id) ) CHARACTER SET utf8 COLLATE utf8_general_ci;';

			dbDelta( $sql );
		}

		/**
		 * Update ticket table
		 *
		 * @since 0.1
		 *
		 * @param $attr_id
		 * @param $post_types
		 */

		public function update_ticket_table( $attr_id, $post_types = array() ) {
			if ( isset( $post_types ) && in_array( self::$post_type, $post_types ) ) {
				$updateDB = new RT_DB_Update( trailingslashit( RTBIZ_HD_PATH ) . 'rtbiz-helpdesk.php', trailingslashit( RTBIZ_HD_PATH . 'admin/schema/' ) );
				delete_option( $updateDB->db_version_option_name );
			}
		}


		public function ticket_updated_messages( $messages ) {
			$messages[ self::$post_type ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Ticket updated.', 'rtbiz-helpdesk' ),
				2  => __( 'Custom field updated.', 'rtbiz-helpdesk' ),
				3  => __( 'Custom field deleted.', 'rtbiz-helpdesk' ),
				4  => __( 'Ticket updated.', 'rtbiz-helpdesk' ),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ticket restored to revision from %s', 'rtbiz-helpdesk' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => __( 'Ticket published.', 'rtbiz-helpdesk' ),
				7  => __( 'Ticket saved.', 'rtbiz-helpdesk' ),
				8  => __( 'Ticket submitted.', 'rtbiz-helpdesk' ),
				10 => __( 'Ticket draft updated.', 'rtbiz-helpdesk' ),
			);
			return $messages;
		}

		/**
		 * Filter the bulk action updated messages for ticket.
		 * @param $bulk_messages
		 * @param $bulk_counts
		 *
		 * @return $bulk_messages
		 */
		public function bulk_ticket_update_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ self::$post_type ] = array(
				'updated'   => _n( '%s ticket updated.', '%s tickets updated.', $bulk_counts['updated'], 'rtbiz-helpdesk'  ),
				'locked'    => _n( '%s ticket not updated, somebody is editing it.', '%s tickets not updated, somebody is editing them.', $bulk_counts['locked'], 'rtbiz-helpdesk' ),
				'deleted'   => _n( '%s ticket permanently deleted.', '%s tickets permanently deleted.', $bulk_counts['deleted'], 'rtbiz-helpdesk' ),
				'trashed'   => _n( '%s ticket moved to the Trash.', '%s tickets moved to the Trash.', $bulk_counts['trashed'], 'rtbiz-helpdesk' ),
				'untrashed' => _n( '%s ticket restored from the Trash.', '%s tickets restored from the Trash.', $bulk_counts['untrashed'], 'rtbiz-helpdesk' ),
			);
			return $bulk_messages;
		}


		/**
		 * @param $args
		 * @param $ctype
		 * @param $post
		 *
		 * @return mixed
		 * p2p hook for hiding staff member and creator from connected contacts meta box
		 * p2p hook for making id serachable in related ticket box
		 */
		public function p2p_hook_for_rthd_post_filter( $args, $ctype, $post  ) {
			global $wpdb, $rtbiz_acl_model;
			// hide staff member and creator of ticket from connected contacts
			if ( $ctype->name == self::$post_type.'_to_'.rtbiz_get_contact_post_type() ) {
				$exclude = array();
				// ACL
				$result  = $wpdb->get_col( 'SELECT p2p_from FROM '.$wpdb->prefix."p2p WHERE p2p_type = '".rtbiz_get_contact_post_type()."_to_user' AND p2p_to in (SELECT DISTINCT(userid) FROM ".$rtbiz_acl_model->table_name." where module = '".RTBIZ_HD_TEXT_DOMAIN."' and permission != 0 )" );
				$exclude = array_merge( $exclude, $result );
				// Ticket Creator
				$contact  = rtbiz_hd_get_ticket_creator( $post->ID );
				if ( ! empty( $contact ) ) {
					$exclude[] = $contact->ID;
				}
				// Exclude Admins
				$admins = get_users(array(
					'fields' => 'ID',
					'role'   => 'administrator',
				));
				foreach ( $admins as $admin ) {
					// get contact and add it in exclude list
					$contact = rtbiz_get_contact_for_wp_user( $admin );
					if ( ! empty( $contact ) ) {
						$exclude[] = $contact[0]->ID;
					}
				}
				$exclude = array_filter( $exclude );
				$exclude = array_unique( $exclude );
				$args['p2p:exclude'] = array_merge( $args['p2p:exclude'], $exclude );
			} // related ticket - ticket id searchable
			elseif ( $ctype->name == self::$post_type.'_to_'.self::$post_type ) {
				// check if search string is number
				if ( ! empty( $args['p2p:search'] ) && is_numeric( $args['p2p:search'] ) ) {
					// if it is number then search it in post ID
					$args['post__in'] = array( $args['p2p:search'] );
					$args['p2p:search'] = '';
				}
			}
			return $args;
		}


		/**
		 * @param $title
		 * @param $post
		 * @param $ctype
		 *
		 * @return string
		 *
		 * Related tickets - p2p - Append post id in post title
		 */
		public function p2p_hook_for_changing_post_title( $title, $post, $ctype ) {
			if ( $ctype->name == self::$post_type.'_to_'.self::$post_type ) {
				$title = '[#'.$post->ID.'] '.$title;
			}
			return $title;
		}

		/**
		 * Change the publish action to update on Cpt-ticket add/edit page
		 *
		 * @since 0.1
		 *
		 * @global type $pagenow
		 * @global type $post
		 */
		public function ticket_chnage_action_publish_update() {
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
		 * Filter adult pref
		 * @param $query
		 */
		public function adult_post_filter( $query ) {

			if ( is_admin() && $query->query['post_type'] == self::$post_type && strpos( $_SERVER['REQUEST_URI'], '/wp-admin/edit.php' ) !== false ) {
				$qv = &$query->query_vars;

				$current_user = get_current_user_id();
				$pref = rtbiz_hd_get_user_adult_preference( $current_user );
				if ( 'yes' == $pref ) {
					$meta_q = array(
						'relation' => 'OR',
						array(
							'key'     => '_rtbiz_hd_ticket_adult_content',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_rtbiz_hd_ticket_adult_content',
							'value'   => 'yes',
							'compare' => '!=',
						),
					);
					$qv['meta_query'] = array_merge( empty( $qv['meta_query'] ) ? array() : $qv['meta_query'], $meta_q );
				}
			}
		}
	}
}
