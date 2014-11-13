<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_HD_Dashboard' ) ) {
	/**
	 * Class Rt_HD_Dashboard
	 * Dashboard for HelpDesk
	 * render charts on deshboad
	 *
	 * @since 0.1
	 */
	class Rt_HD_Dashboard {

		/**
		 * @var string screen id for dashboard
		 *
		 * @since 0.1
		 */
		var $screen_id;
		/**
		 * @var array store charts
		 *
		 * @since 0.1
		 */
		var $charts = array();

		/**
		 * Construct
		 *
		 * @since 0.1
		 */
		public function __construct() {
			$this->screen_id = '';
			$this->hook();
		}

		/**
		 * Hook
		 *
		 * @since 0.1
		 */
		public function hook() {
			add_action( 'admin_menu', array( $this, 'register_dashboard' ), 1 );
		}

		/**
		 * Register dashboard for custom page & hook for MetaBox on it
		 *
		 * @since 0.1
		 */
		function register_dashboard() {

			$author_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );

			$this->screen_id = add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), __( 'Dashboard', RT_HD_TEXT_DOMAIN ), __( 'Dashboard', RT_HD_TEXT_DOMAIN ), $author_cap, 'rthd-' . esc_html( Rt_HD_Module::$post_type ) . '-dashboard', array(
				$this,
				'dashboard_ui',
			) );

			/* Add callbacks for this screen only */
			add_action( 'load-' . $this->screen_id, array( $this, 'page_actions' ), 9 );
			add_action( 'admin_footer-' . $this->screen_id, array( $this, 'footer_scripts' ) );

			/* Setup Google Charts */
			add_action( 'rthd_after_dashboard', array( $this, 'render_google_charts' ) );

			/* Metaboxes for dashboard widgets */
			add_action( 'add_meta_boxes', array( $this, 'add_dashboard_widgets' ) );

			add_filter( 'set-screen-option', array( $this, 'tickets_table_set_option' ), 10, 3 );
		}

		/**
		 * render dashboard template for given post type
		 *
		 * @since 0.1
		 *
		 * @param $post_type
		 */
		function dashboard_ui( $post_type ) {
			rthd_get_template( 'admin/dashboard.php', array( 'post_type' => $post_type ) );
		}

		/**
		 * Actions to be taken prior to page loading. This is after headers have been set.
		 * This calls the add_meta_boxes hooks, adds screen options and enqueue the postbox.js script.
		 *
		 * @since 0.1
		 */
		function page_actions() {
			if ( isset( $_REQUEST['page'] ) && 'rthd-' . Rt_HD_Module::$post_type . '-dashboard' === $_REQUEST['page'] ) {
				do_action( 'add_meta_boxes_' . $this->screen_id, null );
				do_action( 'add_meta_boxes', $this->screen_id, null );

				/* Enqueue WordPress' script for handling the metaboxes */
				wp_enqueue_script( 'postbox' );
			}
		}

		/**
		 * Prints the jQuery script to initiliase the metaboxes
		 * Called on admin_footer-*
		 *
		 * @since 0.1
		 */
		function footer_scripts() {
			?>
			<script> postboxes.add_postbox_toggles( pagenow );</script>
		<?php
		}

		/**
		 * render google ui charts
		 *
		 * @since 0.1
		 */
		function render_google_charts() {
			global $rt_hd_reports;
			$rt_hd_reports->render_chart( $this->charts );
		}

		/**
		 * add dashboard widget
		 *
		 * @since 0.1
		 */
		function add_dashboard_widgets() {
			global $rt_hd_dashboard, $rt_hd_attributes_model, $rt_hd_attributes_relationship_model;

			/* Pie Chart - Progress Indicator (Post status based) */
			add_meta_box( 'rthd-tickets-by-status', __( 'Status wise Tickets', RT_HD_TEXT_DOMAIN ), array(
				$this,
				'tickets_by_status',
			), $rt_hd_dashboard->screen_id, 'column1' );
			/* Line Chart for Closed::Won */
			add_meta_box( 'rthd-daily-tickets', __( 'Daily Tickets', RT_HD_TEXT_DOMAIN ), array(
				$this,
				'daily_tickets',
			), $rt_hd_dashboard->screen_id, 'column2' );
			/* Load by Team (Matrix/Table) */
			add_meta_box( 'rthd-team-load', __( 'Team Load', RT_HD_TEXT_DOMAIN ), array(
				$this,
				'team_load',
			), $rt_hd_dashboard->screen_id, 'column3' );
			/* Top Accounts */
			add_meta_box( 'rthd-top-accounts', __( 'Top Accounts', RT_HD_TEXT_DOMAIN ), array(
				$this,
				'top_accounts',
			), $rt_hd_dashboard->screen_id, 'column4' );
			/* Top Clients */
			add_meta_box( 'rthd-top-clients', __( 'Top Clients', RT_HD_TEXT_DOMAIN ), array(
				$this,
				'top_clients',
			), $rt_hd_dashboard->screen_id, 'column4' );

			$relations = $rt_hd_attributes_relationship_model->get_relations_by_post_type( Rt_HD_Module::$post_type );
			foreach ( $relations as $r ) {
				$attr = $rt_hd_attributes_model->get_attribute( $r->attr_id );
				if ( 'taxonomy' == $attr->attribute_store_as ) {
					add_meta_box( 'rthd-tickets-by-' . $attr->attribute_name, $attr->attribute_label . ' ' . __( 'Wise Tickets', RT_HD_TEXT_DOMAIN ), array(
						$this,
						'dashboard_attributes_widget_content',
					), $rt_hd_dashboard->screen_id, 'column1', 'default', array( 'attribute_id' => $attr->id ) );
				}
			}
		}

		/**
		 * Status wise A single pie will show ticket and amount both: 11 Tickets worth $5555
		 *
		 * @since 0.1
		 */
		function tickets_by_status() {
			global $rt_hd_module, $wpdb;
			$table_name    = rthd_get_ticket_table_name();
			$post_statuses = array();
			foreach ( $rt_hd_module->statuses as $status ) {
				$post_statuses[ $status['slug'] ] = $status['name'];
			}

			$query       = "SELECT post_status, COUNT(id) AS rthd_count FROM {$table_name} WHERE 1=1 GROUP BY post_status";
			$results     = $wpdb->get_results( $query );
			$data_source = array();
			$cols        = array( __( 'Ticket Status', RT_HD_TEXT_DOMAIN ), __( 'Count', RT_HD_TEXT_DOMAIN ) );
			$rows        = array();
			foreach ( $results as $item ) {
				$post_status = ( isset( $post_statuses[ $item->post_status ] ) ) ? $post_statuses[ $item->post_status ] : '';
				if ( ! empty( $post_status ) ) {
					$rows[] = array(
						$post_status,
						( ! empty( $item->rthd_count ) ) ? floatval( $item->rthd_count ) : 0,
					);
				}
			}
			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id'          => 1,
				'chart_type'  => 'pie',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_pie_tickets_by_status',
				'options'     => array( 'title' => __( 'Status wise Tickets', RT_HD_TEXT_DOMAIN ), ),
			);
			?>
			<div id="rthd_hd_pie_tickets_by_status"></div>
		<?php
		}

		/**
		 * Daily tickets UI
		 *
		 * @since 0.1
		 */
		function daily_tickets() {
			global $rt_hd_module, $rt_hd_ticket_history_model;
			$post_statuses = array();
			foreach ( $rt_hd_module->statuses as $status ) {
				$post_statuses[ $status['slug'] ] = $status['name'];
			}
			$current_date = new DateTime();
			$first_date   = date( 'Y-m-d', strtotime( 'first day of this month', $current_date->format( 'U' ) ) );
			$last_date    = date( 'Y-m-d', strtotime( 'last day of this month', $current_date->format( 'U' ) ) );

			$args    = array(
				'type'        => 'post_status',
				'update_time' => array( 'compare' => '>=', 'value' => array( $first_date ), ),
				'update_time' => array( 'compare' => '<=', 'value' => array( $last_date ), ),
			);
			$history = $rt_hd_ticket_history_model->get( $args, false, false, 'update_time asc' );

			$month_map  = array();
			$i          = 0;
			$first_date = strtotime( $first_date );
			$last_date  = strtotime( $last_date );
			do {
				$current_date = strtotime( '+' . $i ++ . ' days', $first_date );

				foreach ( $post_statuses as $slug => $status ) {
					$month_map[ $current_date ][ $slug ] = 0;
				}

				$dt_obj = DateTime::createFromFormat( 'U', $current_date );
				foreach ( $history as $item ) {
					$update_time = new DateTime( $item->update_time );
					if ( $dt_obj->format( 'Y-m-d' ) === $update_time->format( 'Y-m-d' ) ) {
						if ( isset( $month_map[ $current_date ][ $item->new_value ] ) ) {
							$month_map[ $current_date ][ $item->new_value ] ++;
						}
					}
				}
			} while ( $current_date < $last_date );

			$data_source = array();
			$cols[0]     = __( 'Daily Tickets', RT_HD_TEXT_DOMAIN );
			foreach ( $post_statuses as $status ) {
				$cols[] = $status;
			}
			$rows = array();
			foreach ( $month_map as $date => $tickets ) {
				$temp   = array();
				$dt_obj = DateTime::createFromFormat( 'U', $date );
				$temp[] = $dt_obj->format( 'j M, Y' );
				foreach ( $tickets as $ticket ) {
					$temp[] = $ticket;
				}
				$rows[] = $temp;
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id'          => 5,
				'chart_type'  => 'line',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_line_daily_tickets',
				'options'     => array( 'title' => __( 'Daily Tickets', RT_HD_TEXT_DOMAIN ), ),
			);

			?>
			<div id="rthd_hd_line_daily_tickets"></div>
		<?php
		}

		/**
		 * team load
		 *
		 * @since 0.1
		 */
		function team_load() {
			global $rt_hd_module, $wpdb;
			$table_name    = rthd_get_ticket_table_name();
			$post_statuses = array();
			foreach ( $rt_hd_module->statuses as $status ) {
				$post_statuses[ $status['slug'] ] = $status['name'];
			}

			$query   = "SELECT assignee, post_status, COUNT(ID) AS rthd_ticket_count FROM {$table_name} WHERE 1=1 GROUP BY assignee, post_status";
			$results = $wpdb->get_results( $query );

			$table_matrix = array();
			foreach ( $results as $item ) {
				if ( isset( $table_matrix[ $item->assignee ] ) ) {
					if ( isset( $post_statuses[ $item->post_status ] ) ) {
						$table_matrix[ $item->assignee ][ $item->post_status ] = $item->rthd_ticket_count;
					}
				} else {
					foreach ( $post_statuses as $key => $status ) {
						$table_matrix[ $item->assignee ][ $key ] = 0;
					}
					if ( isset( $post_statuses[ $item->post_status ] ) ) {
						$table_matrix[ $item->assignee ][ $item->post_status ] = $item->rthd_ticket_count;
					}
				}
			}

			$data_source = array();
			$cols[]      = array( 'type' => 'string', 'label' => __( 'Users', RT_HD_TEXT_DOMAIN ), );
			foreach ( $post_statuses as $status ) {
				$cols[] = array( 'type' => 'number', 'label' => $status, );
			}

			$rows = array();
			foreach ( $table_matrix as $user => $item ) {

				$temp = array();
				foreach ( $item as $status => $count ) {
					$temp[] = intval( $count );
				}
				$user = get_user_by( 'id', $user );
				$url  = add_query_arg(
					array(
						'post_type' => Rt_HD_Module::$post_type,
						'assignee'  => $user->ID,
					), admin_url( 'edit.php' ) );
				if ( ! empty( $user ) ) {
					array_unshift( $temp, '<a href="' . $url . '">' . $user->display_name . '</a>' );
				}
				$rows[] = $temp;
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id'          => 2,
				'chart_type'  => 'table',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_table_team_load',
				'options'     => array( 'title' => __( 'Team Load', RT_HD_TEXT_DOMAIN ), ),
			);
			?>
			<div id="rthd_hd_table_team_load"></div>
		<?php
		}

		/**
		 * get top accounts
		 *
		 * @since 0.1
		 */
		function top_accounts() {
			global $wpdb;
			$table_name = rthd_get_ticket_table_name();
			$account    = rt_biz_get_organization_post_type();

			$query = 'SELECT acc.ID AS account_id, acc.post_title AS account_name ' . ( ( isset( $wpdb->p2p ) ) ? ', COUNT( ticket.ID ) AS account_tickets ' : ' ' ) . "FROM {$wpdb->posts} AS acc " . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$wpdb->p2p} AS p2p ON acc.ID = p2p.p2p_to " : ' ' ) . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$table_name} AS ticket ON ticket.post_id = p2p.p2p_from " : ' ' ) . 'WHERE 2=2 ' . ( ( isset( $wpdb->p2p ) ) ? "AND p2p.p2p_type = '" . Rt_HD_Module::$post_type . "_to_{$account}' " : ' ' ) . "AND acc.post_type = '{$account}' " . 'GROUP BY acc.ID ' . ( ( isset( $wpdb->p2p ) ) ? 'ORDER BY account_tickets DESC ' : ' ' ) . 'LIMIT 0 , 10';

			$results = $wpdb->get_results( $query );

			$data_source = array();
			$cols        = array(
				array( 'type' => 'string', 'label' => __( 'Account Name', RT_HD_TEXT_DOMAIN ), ),
				array( 'type' => 'number', 'label' => __( 'Number of Tickets', RT_HD_TEXT_DOMAIN ), ),
			);

			$rows = array();
			foreach ( $results as $item ) {
				$url    = add_query_arg(
					array(
						'post_type'  => Rt_HD_Module::$post_type,
						'account_id' => $item->account_id,
					), admin_url( 'edit.php' ) );
				$rows[] = array(
					'<a href="' . $url . '">' . $item->account_name . '</a>',
					intval( $item->account_tickets ),
				);
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id'          => 3,
				'chart_type'  => 'table',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_table_top_accounts',
				'options'     => array( 'title' => __( 'Top Accounts', RT_HD_TEXT_DOMAIN ), ),
			);
			?>
			<div id="rthd_hd_table_top_accounts"></div>
		<?php
		}

		/**
		 * top clients UI
		 *
		 * @since 0.1
		 */
		function top_clients() {
			global $wpdb;
			$table_name = rthd_get_ticket_table_name();
			$contact    = rt_biz_get_person_post_type();
			$account    = rt_biz_get_organization_post_type();

			$query = 'SELECT contact.ID AS contact_id, contact.post_title AS contact_name ' . ( ( isset( $wpdb->p2p ) ) ? ', COUNT( ticket.ID ) AS contact_tickets ' : ' ' ) . "FROM {$wpdb->posts} AS contact " . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$wpdb->p2p} AS p2p_lc ON contact.ID = p2p_lc.p2p_to " : ' ' ) . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$table_name} AS ticket ON ticket.post_id = p2p_lc.p2p_from " : ' ' ) . ( ( isset( $wpdb->p2p ) ) ? "LEFT JOIN {$wpdb->p2p} AS p2p_ac ON contact.ID = p2p_ac.p2p_to AND p2p_ac.p2p_type = '{$account}_to_{$contact}'  " : ' ' ) . 'WHERE 2=2 ' . ( ( isset( $wpdb->p2p ) ) ? "AND p2p_lc.p2p_type = '" . Rt_HD_Module::$post_type . "_to_{$contact}' " : ' ' ) . "AND contact.post_type = '{$contact}' " . ( ( isset( $wpdb->p2p ) ) ? 'AND p2p_ac.p2p_type IS NULL ' : ' ' ) . 'GROUP BY contact.ID ' . ( ( isset( $wpdb->p2p ) ) ? 'ORDER BY contact_tickets DESC ' : ' ' ) . 'LIMIT 0 , 10';

			$results = $wpdb->get_results( $query );

			$data_source = array();
			$cols        = array(
				array( 'type' => 'string', 'label' => __( 'Contact Name', RT_HD_TEXT_DOMAIN ), ),
				array( 'type' => 'number', 'label' => __( 'Number of Tickets', RT_HD_TEXT_DOMAIN ), ),
			);

			$rows = array();
			foreach ( $results as $item ) {
				$url    = add_query_arg(
					array(
						'post_type'  => Rt_HD_Module::$post_type,
						'contact_id' => $item->contact_id,
					), admin_url( 'edit.php' ) );
				$rows[] = array(
					'<a href="' . $url . '">' . $item->contact_name . '</a>',
					intval( $item->contact_tickets ),
				);
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id'          => 4,
				'chart_type'  => 'table',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_table_top_clients',
				'options'     => array( 'title' => __( 'Top Clients', RT_HD_TEXT_DOMAIN ), ),
			);
			?>
			<div id="rthd_hd_table_top_clients"></div>
		<?php
		}

		/**
		 * dashboard attribute widget UI
		 *
		 * @since 0.1
		 *
		 * @param $obj
		 * @param $args
		 */
		function dashboard_attributes_widget_content( $obj, $args ) {
			global $rt_hd_rt_attributes;
			$rt_hd_attributes_model = new RT_Attributes_Model();
			$attribute_id           = $args['args']['attribute_id'];
			$attr                   = $rt_hd_attributes_model->get_attribute( $attribute_id );
			$taxonomy               = $rt_hd_rt_attributes->get_taxonomy_name( $attr->attribute_name );
			$post_type              = Rt_HD_Module::$post_type;
			$terms                  = get_terms( $taxonomy );

			$data_source = array();
			$cols        = array( $attr->attribute_label, __( 'Tickets', RT_HD_TEXT_DOMAIN ) );
			$rows        = array();
			$total       = 0;

			foreach ( $terms as $t ) {
				$posts = new WP_Query(
					array(
						'post_type'   => $post_type,
						'post_status' => 'any',
						'nopaging'    => true,
						$taxonomy     => $t->slug,
					) );

				$rows[] = array( $t->name, count( $posts->posts ), );
				$total += count( $posts->posts );
			}

			$posts = new WP_Query( array( 'post_type' => $post_type, 'post_status' => 'any', 'nopaging' => true, ) );

			$rows[] = array( __( 'Others', RT_HD_TEXT_DOMAIN ), count( $posts->posts ) - $total );

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id'          => $args['id'],
				'chart_type'  => 'pie',
				'data_source' => $data_source,
				'dom_element' => 'rthd_pie_' . $args['id'],
				'options'     => array( 'title' => $args['title'], ),
			);
			?>
			<div id="<?php echo esc_attr( 'rthd_pie_' . $args['id'] ); ?>"></div>
		<?php
		}

		/**
		 * tickets table option set
		 *
		 * @since 0.1
		 *
		 * @param $status
		 * @param $option
		 * @param $value
		 *
		 * @return mixed
		 */
		function tickets_table_set_option( $status, $option, $value ) {
			return $value;
		}
	}
}
