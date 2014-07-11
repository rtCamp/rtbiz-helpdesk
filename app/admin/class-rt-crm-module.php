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
 * Description of Rt_CRM_Module
 *
 * @author udit
 */
if( !class_exists( 'Rt_CRM_Module' ) ) {
	class Rt_CRM_Module {

		var $post_type = 'rt_lead';
		// used in mail subject title - to detect whether it's a CRM mail or not. So no translation
		var $name = 'CRM';
		var $labels = array();
		var $statuses = array();
		var $custom_menu_order = array();

		public function __construct() {
			$this->get_custom_labels();
			$this->get_custom_statuses();
			$this->get_custom_menu_order();
			add_action( 'init', array( $this, 'init_crm' ) );
			$this->hooks();
		}

		function db_lead_table_update() {
			$updateDB = new RT_DB_Update( trailingslashit( RT_CRM_PATH ) . 'index.php', trailingslashit( RT_CRM_PATH_SCHEMA ) );
			if ( $updateDB->check_upgrade() ) {
				$this->create_database_table();
			}
		}

		function create_database_table() {

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			global $rt_crm_attributes_relationship_model, $rt_crm_attributes_model;
			$relations = $rt_crm_attributes_relationship_model->get_relations_by_post_type( $this->post_type );
			$table_name = rtcrm_get_lead_table_name();
			$sql = "CREATE TABLE {$table_name} (\n"
					. "id BIGINT(20) NOT NULL AUTO_INCREMENT,\n"
					. "post_id BIGINT(20),\n"
					. "post_title TEXT,\n"
					. "post_content TEXT,\n"
					. "assignee BIGINT(20),\n"
					. "date_create TIMESTAMP NOT NULL,\n"
					. "date_create_gmt TIMESTAMP NOT NULL,\n"
					. "date_update TIMESTAMP NOT NULL,\n"
					. "date_update_gmt TIMESTAMP NOT NULL,\n"
					. "date_closing TIMESTAMP NOT NULL,\n"
					. "date_closing_gmt TIMESTAMP NOT NULL,\n"
					. "post_status VARCHAR(20),\n"
					. "user_created_by BIGINT(20),\n"
					. "user_updated_by BIGINT(20),\n"
					. "user_closed_by BIGINT(20),\n"
					. "last_comment_id BIGINT(20),\n"
					. "flag VARCHAR(3),\n"
					. str_replace( '-', '_', rtcrm_attribute_taxonomy_name( 'closing_reason' ) )." TEXT,\n";

			foreach ( $relations as $relation ) {
				$attr = $rt_crm_attributes_model->get_attribute( $relation->attr_id );
				$attr_name = str_replace('-', '_', rtcrm_attribute_taxonomy_name($attr->attribute_name));
				$sql .= "{$attr_name} TEXT,\n";
			}

			$settings = get_site_option( 'rt_crm_settings', false );
			if ( isset( $settings['attach_contacts'] ) && $settings['attach_contacts'] == 'yes' ) {
				$contact_name = rt_biz_get_person_post_type();
				$sql .= "{$contact_name} TEXT,\n";
			}
			if ( isset( $settings['attach_accounts'] ) && $settings['attach_accounts'] == 'yes' ) {
				$contact_name = rt_biz_get_organization_post_type();
				$sql .= "{$contact_name} TEXT,\n";
			}

			$sql .= "PRIMARY KEY  (id)\n"
				. ");";

			dbDelta($sql);
		}

		function init_crm() {
			$menu_position = 31;
			$this->register_custom_post( $menu_position );
			$this->register_custom_statuses();

			$settings = get_site_option( 'rt_crm_settings', false );
			if ( isset( $settings['attach_contacts'] ) && $settings['attach_contacts'] == 'yes' ) {
				rt_biz_register_person_connection( $this->post_type, $this->labels['name'] );
			}
			if ( isset( $settings['attach_accounts'] ) && $settings['attach_accounts'] == 'yes' ) {
				rt_biz_register_organization_connection( $this->post_type, $this->labels['name'] );
			}

			global $rt_crm_closing_reason;
			$rt_crm_closing_reason->closing_reason( rtcrm_post_type_name( $this->labels['name'] ) );

			$this->db_lead_table_update();
		}

		function hooks() {
			add_action( 'admin_menu', array( $this, 'register_custom_pages' ), 1 );
			add_filter( 'custom_menu_order', array($this, 'custom_pages_order') );
			add_action( 'admin_init', array( $this, 'add_post_link' ) );
			add_action( 'admin_init', array( $this, 'native_list_view_link' ) );
			add_filter( 'get_edit_post_link', array( $this, 'lead_edit_link' ), 10, 3 );
			add_filter( 'post_row_actions', array( $this, 'post_row_action' ), 10, 2 );

			add_action( 'rt_attributes_relations_added', array( $this, 'create_database_table' ) );
			add_action( 'rt_attributes_relations_updated', array( $this, 'create_database_table' ) );
			add_action( 'rt_attributes_relations_deleted', array( $this, 'create_database_table' ) );
		}

		function native_list_view_link() {
			global $rt_crm_attributes;
			if ( strpos( $_SERVER["REQUEST_URI"], 'edit.php' ) > 0 &&
				strpos( $_SERVER['REQUEST_URI'], 'page='.$rt_crm_attributes->attributes_page_slug ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-gravity-import' ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-gravity-mapper' ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-settings' ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-logs' ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-user-settings' ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'post_type='.$this->post_type ) &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-add-'.$this->post_type ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-'.$this->post_type.'-dashboard' ) === false &&
				strpos( $_SERVER['REQUEST_URI'], 'page=rtcrm-all-'.$this->post_type ) === false ) {
				wp_redirect( add_query_arg( 'page', 'rtcrm-all-'.$this->post_type ), 200 );
			}
		}

		function add_post_link() {
			if ( strpos( $_SERVER["REQUEST_URI"], 'post-new.php?post_type='.$this->post_type ) > 0 ) {
				wp_redirect( admin_url( 'edit.php?post_type=' . $this->post_type.'&page=rtcrm-add-'.$this->post_type ), 200 );
			}
		}

		function lead_edit_link( $editlink, $postID, $context ) {
			$post_type = get_post_type( $postID );
			if ( $post_type != $this->post_type ) {
				return $editlink;
			}
			return admin_url( "edit.php?post_type={$post_type}&page=rtcrm-add-{$post_type}&{$post_type}_id=" . $postID );
		}

		function post_row_action( $action, $post ) {
			$post_type = get_post_type( $post );
			if ( $post_type != $this->post_type ) {
				return $action;
			}
			$title = __( 'Edit' );
			$action['edit'] = "<a href='" . admin_url("edit.php?post_type={$this->post_type}&page=rtcrm-add-{$this->post_type}&{$this->post_type}_id=" . $post->ID) . "' title='" . $title . "'>" . $title . "</a>";
			return $action;
		}

		function register_custom_pages() {
			global $rt_crm_dashboard;

			$author_cap = rt_biz_get_access_role_cap( RT_CRM_TEXT_DOMAIN, 'author' );

			$screen_id = add_submenu_page( 'edit.php?post_type='.$this->post_type, __( 'Dashboard' ), __( 'Dashboard' ), $author_cap, 'rtcrm-'.$this->post_type.'-dashboard', array( $this, 'dashboard' ) );
			$rt_crm_dashboard->add_screen_id( $screen_id );
			$rt_crm_dashboard->setup_dashboard();

			/* Metaboxes for dashboard widgets */
			add_action( 'add_meta_boxes', array( $this, 'add_dashboard_widgets' ) );

			$filter = add_submenu_page( 'edit.php?post_type='.$this->post_type, __( ucfirst( $this->labels['all_items'] ) ), __( ucfirst( $this->labels['all_items'] ) ), $author_cap, 'rtcrm-all-'.$this->post_type, array( $this, 'custom_page_list_view' ) );
			add_action( "load-$filter", array( $this, 'add_screen_options' ) );

			$screen_id = add_submenu_page( 'edit.php?post_type='.$this->post_type, __('Add ' . ucfirst( $this->labels['name'] ) ), __('Add ' . ucfirst( $this->labels['name'] ) ), $author_cap, 'rtcrm-add-'.$this->post_type, array( $this, 'custom_page_ui' ) );
			add_action( 'admin_footer-'.$screen_id, array( $this, 'footer_scripts' ) );

			add_filter( 'set-screen-option', array( $this,'leads_table_set_option' ), 10, 3 );
		}

		function footer_scripts() { ?>
			<script>postboxes.add_postbox_toggles(pagenow);</script>
		<?php }

		function leads_table_set_option($status, $option, $value) {
			return $value;
		}

		function add_screen_options() {

			$option = 'per_page';
			$args = array(
				'label' => $this->labels['all_items'],
				'default' => 10,
				'option' => $this->post_type.'_per_page',
			);
			add_screen_option($option, $args);
			new Rt_CRM_Leads_List_View();
		}

		function custom_pages_order( $menu_order ) {
			global $submenu;
			global $menu;
			if ( isset( $submenu['edit.php?post_type='.$this->post_type] ) && !empty( $submenu['edit.php?post_type='.$this->post_type] ) ) {
				$module_menu = $submenu['edit.php?post_type='.$this->post_type];
				unset($submenu['edit.php?post_type='.$this->post_type]);
				unset($module_menu[5]);
				unset($module_menu[10]);
				$new_index = 5;
				foreach ( $this->custom_menu_order as $item ) {
					foreach ( $module_menu as $p_key => $menu_item ) {
						if ( in_array( $item, $menu_item ) ) {
							$submenu['edit.php?post_type='.$this->post_type][$new_index] = $menu_item;
							unset ( $module_menu[$p_key] );
							$new_index += 5;
							break;
						}
					}
				}
				foreach( $module_menu as $p_key => $menu_item ) {
					$menu_item[0]= '--- '.$menu_item[0];
					$submenu['edit.php?post_type='.$this->post_type][$new_index] = $menu_item;
					unset ( $module_menu[$p_key] );
					$new_index += 5;
				}
			}
			return $menu_order;
		}

		function custom_page_ui() {
			$args = array();
			rtcrm_get_template('admin/add-lead.php', $args);
		}

		function custom_page_list_view() {
			$args = array(
				'post_type' => $this->post_type,
				'labels' => $this->labels,
			);
			rtcrm_get_template( 'admin/list-view.php', $args );
		}

		function register_custom_post( $menu_position ) {
			$crm_logo_url = get_site_option( 'rtcrm_logo_url' );

			if ( empty( $crm_logo_url ) ) {
				$crm_logo_url = RT_CRM_URL.'app/assets/img/crm-16X16.png';
			}

			$args = array(
				'labels' => $this->labels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true, // Show the UI in admin panel
				'menu_icon' => $crm_logo_url,
				'menu_position' => $menu_position,
				'supports' => array('title', 'editor', 'comments', 'custom-fields'),
				'capability_type' => $this->post_type,
			);
			register_post_type( $this->post_type, $args );
		}

		function register_custom_statuses() {
			foreach ($this->statuses as $status) {

				register_post_status($status['slug'], array(
					'label' => $status['slug']
					, 'protected' => true
					, '_builtin' => false
					, 'label_count' => _n_noop("{$status['name']} <span class='count'>(%s)</span>", "{$status['name']} <span class='count'>(%s)</span>"),
				));
			}
		}

        function get_custom_menu_order(){
			global $rt_crm_attributes;
            $this->custom_menu_order = array(
                'rtcrm-'.$this->post_type.'-dashboard',
				'rtcrm-all-'.$this->post_type,
				'rtcrm-add-'.$this->post_type,
				'rtcrm-gravity-import',
				'rtcrm-gravity-mapper',
				'rtcrm-logs',
				'rtcrm-settings',
				'rtcrm-user-settings',
				$rt_crm_attributes->attributes_page_slug,
            );
        }

		function get_custom_labels() {
			$menu_label = get_site_option( 'rtcrm_menu_label', __('rtCRM') );
			$this->labels = array(
				'name' => __( 'Lead' ),
				'singular_name' => __( 'Lead' ),
				'menu_name' => $menu_label,
				'all_items' => __( 'Leads' ),
				'add_new' => __( 'Add Lead' ),
				'add_new_item' => __( 'Add Lead' ),
				'new_item' => __( 'Add Lead' ),
				'edit_item' => __( 'Edit Lead' ),
				'view_item' => __( 'View Lead' ),
				'search_items' => __( 'Search Leads' ),
			);
			return $this->labels;
		}

		function get_custom_statuses() {
			$this->statuses = array(
				array(
					'slug' => 'new',
					'name' => __( 'New' ),
					'description' => __( 'New lead is created' ),
				),
				array(
					'slug' => 'assigned',
					'name' => __( 'Assigned' ),
					'description' => __( 'Lead is assigned' ),
				),
				array(
					'slug' => 'requirement-analysis',
					'name' => __( 'Requirement Analysis' ),
					'description' => __( 'Lead is under requirement analysis' ),
				),
				array(
					'slug' => 'quotation',
					'name' => __( 'Quotation' ),
					'description' => __( 'Lead is in quotation phase' ),
				),
				array(
					'slug' => 'negotiation',
					'name' => __( 'Negotiation' ),
					'description' => __( 'Lead is in negotiation phase' ),
				),
				array(
					'slug' => 'closed',
					'name' => __( 'Closed' ),
					'description' => __( 'Lead is closed' ),
				),
			);
			return $this->statuses;
		}

		function dashboard() {
			global $rt_crm_dashboard;
			$rt_crm_dashboard->ui( $this->post_type );
		}

		function add_dashboard_widgets() {
			global $rt_crm_dashboard;

			/* Pie Chart - Progress Indicator (Post status based) */
			add_meta_box( 'rtcrm-leads-by-status', __( 'Status wise Leads Budget' ), array( $this, 'leads_by_status' ), $rt_crm_dashboard->screen_id, 'column1' );
			/* Line Chart for Closed::Won */
			add_meta_box( 'rtcrm-daily-leads', __( 'Daily Leads' ), array( $this, 'daily_leads' ), $rt_crm_dashboard->screen_id, 'column2' );
			/* Load by Team (Matrix/Table) */
			add_meta_box( 'rtcrm-team-load', __( 'Team Load' ), array( $this, 'team_load' ), $rt_crm_dashboard->screen_id, 'column3' );
			/* Top Accounts */
			add_meta_box( 'rtcrm-top-accounts', __( 'Top Accounts' ), array( $this, 'top_accounts' ), $rt_crm_dashboard->screen_id, 'column4' );
			/* Top Clients */
			add_meta_box( 'rtcrm-top-clients', __( 'Top Clients' ), array( $this, 'top_clients' ), $rt_crm_dashboard->screen_id, 'column4' );
		}

		/**
		 * Status wise A single pie will show lead and amount both: 11 Leads worth $5555
		 */
		function leads_by_status() {
			global $rt_crm_dashboard, $wpdb;
			$table_name = rtcrm_get_lead_table_name();
			$post_statuses = array();
			foreach ( $this->statuses as $status ) {
				$post_statuses[$status['slug']] = $status['name'];
			}

			$query = "SELECT post_status, SUM(rt_budget) AS rtcrm_budget FROM {$table_name} WHERE 1=1 GROUP BY post_status";
			$results = $wpdb->get_results($query);
			$data_source = array();
			$cols = array( __('Lead Status'), __( 'Budget' ) );
			$rows = array();
			foreach ( $results as $item ) {
				$post_status = ( isset( $post_statuses[$item->post_status] ) ) ? $post_statuses[$item->post_status] : '';
				if ( !empty( $post_status ) ) {
					$rows[] = array( $post_status, ( !empty( $item->rtcrm_budget ) ) ? floatval( $item->rtcrm_budget ) : 0 );
				}
			}
			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$rt_crm_dashboard->charts[] = array(
				'id' => 1,
				'chart_type' => 'pie',
				'data_source' => $data_source,
				'dom_element' => 'rtcrm_crm_pie_leads_by_status',
				'options' => array(
					'title' => __( 'Status wise Leads Budget' ),
				),
			);
		?>
    		<div id="rtcrm_crm_pie_leads_by_status"></div>
		<?php
		}

		function team_load() {
			global $rt_crm_dashboard, $wpdb;
            $table_name = rtcrm_get_lead_table_name();
			$post_statuses = array();
			foreach ( $this->statuses as $status ) {
				$post_statuses[$status['slug']] = $status['name'];
			}

			$query = "SELECT assignee, post_status, COUNT(ID) AS rtcrm_lead_count FROM {$table_name} WHERE 1=1 GROUP BY assignee, post_status";
            $results = $wpdb->get_results($query);

			$table_matrix = array();
			foreach ($results as $item) {
				if ( isset( $table_matrix[$item->assignee] ) ) {
					if( isset( $post_statuses[$item->post_status] ) ) {
						$table_matrix[$item->assignee][$item->post_status] = $item->rtcrm_lead_count;
					}
				} else {
					foreach ($post_statuses as $key => $status) {
						$table_matrix[$item->assignee][$key] = 0;
					}
					if ( isset( $post_statuses[$item->post_status] ) ) {
						$table_matrix[$item->assignee][$item->post_status] = $item->rtcrm_lead_count;
					}
				}
			}

			$data_source = array();
            $cols[] = array(
				'type' => 'string',
				'label' => __('Users'),
			);
			foreach ( $post_statuses as $status ) {
				$cols[] = array(
					'type' => 'number',
					'label' => $status,
				);
			}

			$rows = array();
			foreach ( $table_matrix as $user => $item ) {

				$temp = array();
				foreach ( $item as $status => $count ) {
					$temp[] = intval($count);
				}
				$user = get_user_by('id', $user);
				$url = add_query_arg(
					array(
						'post_type' => 'rt_lead',
						'page' => 'rtcrm-all-rt_lead',
						'assignee' => $user->ID,
					),
					admin_url( 'edit.php' )
				);
				if ( !empty( $user ) ) {
					array_unshift($temp, '<a href="'.$url.'">'.$user->display_name.'</a>');
				}
				$rows[] = $temp;
			}

            $data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

            $rt_crm_dashboard->charts[] = array(
                'id' => 2,
                'chart_type' => 'table',
                'data_source' => $data_source,
                'dom_element' => 'rtcrm_crm_table_team_load',
                'options' => array(
                    'title' => __( 'Team Load' ),
                ),
            );
		?>
            <div id="rtcrm_crm_table_team_load"></div>
        <?php
		}

		function top_accounts() {
			global $rt_crm_dashboard, $wpdb;
			$table_name = rtcrm_get_lead_table_name();
			$account = rt_biz_get_organization_post_type();

			$query = "SELECT acc.ID AS account_id, acc.post_title AS account_name "
						. ( ( isset( $wpdb->p2p ) ) ? ", COUNT( lead.ID ) AS account_leads, SUM( lead.rt_budget ) AS account_budget " : ' ' )
					. "FROM {$wpdb->posts} AS acc "
					. ( ( isset( $wpdb->p2p ) ) ? "JOIN {$wpdb->p2p} AS p2p ON acc.ID = p2p.p2p_to " : ' ' )
					. ( ( isset( $wpdb->p2p ) ) ? "JOIN {$table_name} AS lead ON lead.post_id = p2p.p2p_from " : ' ' )
					. "WHERE 2=2 "
					. ( ( isset( $wpdb->p2p ) ) ? "AND p2p.p2p_type = '{$this->post_type}_to_{$account}' " : ' ' )
					. "AND acc.post_type = '{$account}' "
					. "GROUP BY acc.ID "
					. ( ( isset( $wpdb->p2p ) ) ? "ORDER BY account_budget DESC " : ' ' )
					. "LIMIT 0 , 10";

			$results = $wpdb->get_results($query);

			$data_source = array();
            $cols = array(
				array(
					'type' => 'string',
					'label' => __( 'Account Name' ),
				),
				array(
					'type' => 'number',
					'label' => __( 'Number of Leads' ),
				),
				array(
					'type' => 'number',
					'label' => __( 'Budget' ),
				),
			);

			$rows = array();
			foreach ( $results as $item ) {
				$url = add_query_arg(
					array(
						'post_type' => 'rt_lead',
						'page' => 'rtcrm-all-rt_lead',
						$account => $item->account_id,
					),
					admin_url( 'edit.php' )
				);
				$rows[] = array(
					'<a href="'.$url.'">'.$item->account_name.'</a>',
					intval($item->account_leads),
					floatval($item->account_budget),
				);
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

            $rt_crm_dashboard->charts[] = array(
                'id' => 3,
                'chart_type' => 'table',
                'data_source' => $data_source,
                'dom_element' => 'rtcrm_crm_table_top_accounts',
                'options' => array(
                    'title' => __( 'Top Accounts' ),
                ),
            );
		?>
            <div id="rtcrm_crm_table_top_accounts"></div>
        <?php
		}

		function top_clients() {
			global $rt_crm_dashboard, $wpdb;
			$table_name = rtcrm_get_lead_table_name();
			$contact = rt_biz_get_person_post_type();
			$account = rt_biz_get_organization_post_type();

			$query = "SELECT contact.ID AS contact_id, contact.post_title AS contact_name "
						. ( ( isset( $wpdb->p2p ) ) ? ", COUNT( lead.ID ) AS contact_leads, SUM( lead.rt_budget ) AS contact_budget " : ' ' )
					. "FROM {$wpdb->posts} AS contact "
					. ( ( isset( $wpdb->p2p ) ) ? "JOIN {$wpdb->p2p} AS p2p_lc ON contact.ID = p2p_lc.p2p_to " : ' ' )
					. ( ( isset( $wpdb->p2p ) ) ? "JOIN {$table_name} AS lead ON lead.post_id = p2p_lc.p2p_from " : ' ' )
					. ( ( isset( $wpdb->p2p ) ) ? "LEFT JOIN {$wpdb->p2p} AS p2p_ac ON contact.ID = p2p_ac.p2p_to AND p2p_ac.p2p_type = '{$account}_to_{$contact}'  " : ' ' )
					. "WHERE 2=2 "
					. ( ( isset( $wpdb->p2p ) ) ? "AND p2p_lc.p2p_type = '{$this->post_type}_to_{$contact}' " : ' ' )
					. "AND contact.post_type = '{$contact}' "
					. ( ( isset( $wpdb->p2p ) ) ? "AND p2p_ac.p2p_type IS NULL " : ' ' )
					. "GROUP BY contact.ID "
					. ( ( isset( $wpdb->p2p ) ) ? "ORDER BY contact_budget DESC " : ' ' )
					. "LIMIT 0 , 10";

			$results = $wpdb->get_results($query);

			$data_source = array();
            $cols = array(
				array(
					'type' => 'string',
					'label' => __( 'Contact Name' ),
				),
				array(
					'type' => 'number',
					'label' => __( 'Number of Leads' ),
				),
				array(
					'type' => 'number',
					'label' => __( 'Budget' ),
				),
			);

			$rows = array();
			foreach ( $results as $item ) {
				$url = add_query_arg(
					array(
						'post_type' => 'rt_lead',
						'page' => 'rtcrm-all-rt_lead',
						$contact => $item->contact_id,
					),
					admin_url( 'edit.php' )
				);
				$rows[] = array(
					'<a href="'.$url.'">'.$item->contact_name.'</a>',
					intval($item->contact_leads),
					floatval($item->contact_budget),
				);
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

            $rt_crm_dashboard->charts[] = array(
                'id' => 4,
                'chart_type' => 'table',
                'data_source' => $data_source,
                'dom_element' => 'rtcrm_crm_table_top_clients',
                'options' => array(
                    'title' => __( 'Top Clients' ),
                ),
            );
		?>
            <div id="rtcrm_crm_table_top_clients"></div>
        <?php
		}

		function daily_leads() {
			global $rt_crm_dashboard, $rt_crm_lead_history_model;
			$post_statuses = array();
			foreach ( $this->statuses as $status ) {
				$post_statuses[$status['slug']] = $status['name'];
			}
			$current_date = new DateTime();
			$first_date = date( 'Y-m-d', strtotime( 'first day of this month', $current_date->format('U') ) );
			$last_date = date( 'Y-m-d', strtotime( 'last day of this month', $current_date->format('U') ) );

			$args = array(
				'type' => 'post_status',
				'update_time' => array(
					'compare' => '>=',
					'value' => array($first_date),
				),
				'update_time' => array(
					'compare' => '<=',
					'value' => array($last_date),
				),
			);
			$history = $rt_crm_lead_history_model->get($args, false, false, 'update_time asc');

			$month_map = array();
			$i = 0;
			$first_date = strtotime($first_date);
			$last_date = strtotime($last_date);
			do {
				$current_date = strtotime( '+'.$i++.' days', $first_date );

				foreach ($post_statuses as $slug => $status) {
					$month_map[$current_date][$slug] = 0;
				}

				$dt_obj = DateTime::createFromFormat( 'U', $current_date );
				foreach ( $history as $item ) {
					$update_time = new DateTime( $item->update_time );
					if ( $dt_obj->format('Y-m-d') === $update_time->format('Y-m-d')  ) {
						if ( isset( $month_map[$current_date][$item->new_value] ) ) {
							$month_map[$current_date][$item->new_value]++;
						}
					}
				}
			} while ( $current_date < $last_date );

			$data_source = array();
            $cols[0] = __( 'Daily Leads' );
			foreach ( $post_statuses as $status ) {
				$cols[] = $status;
			}
			$rows = array();
			foreach ( $month_map as $date => $leads ) {
				$temp = array();
				$dt_obj = DateTime::createFromFormat( 'U', $date );
				$temp[] = $dt_obj->format('j M, Y');
				foreach ( $leads as $lead ) {
					$temp[] = $lead;
				}
				$rows[] = $temp;
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$rt_crm_dashboard->charts[] = array(
                'id' => 5,
                'chart_type' => 'line',
                'data_source' => $data_source,
                'dom_element' => 'rtcrm_crm_line_daily_leads',
                'options' => array(
                    'title' => __( 'Daily Leads' ),
                ),
            );

		?>
    		<div id="rtcrm_crm_line_daily_leads"></div>
		<?php
		}

		function ui() {
			global $rt_crm_attributes_model;
			rtcrm_get_template('admin/add-module.php', array( 'rt_attributes_model' => $rt_crm_attributes_model ) );
		}

	}
}
