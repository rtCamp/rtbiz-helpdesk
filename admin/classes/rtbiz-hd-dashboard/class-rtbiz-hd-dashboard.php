<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Dashboard' ) ) {

	/**
	 * Class Rt_HD_Dashboard
	 * Dashboard for HelpDesk
	 * render charts on deshboad
	 *
	 * @since 0.1
	 */
	class Rtbiz_HD_Dashboard {


		public static $page_slug = 'rtbiz-hd-dashboard';

		/**
		 * @var string screen id for dashboard
		 *
		 * @since 0.1
		 */
		var $screen_id = '';

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

			Rtbiz_HD::$loader->add_action( 'admin_menu', $this, 'register_dashboard', 1 );

			Rtbiz_HD::$loader->add_action( 'rtbiz_welcome_panel_addon_link', $this, 'add_helpdesk_link' );

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_update_welcome_panel', $this, 'ajax_update_welcome_panel' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_setup_support_page', $this, 'ajax_setup_support_page' );

			/* Add Welcome panel on rt helpdesk dashboard. */
			Rtbiz_HD::$loader->add_action( 'rtbiz_hd_welcome_panel', $this, 'welcome_panel' );

			/* Setup js for rtHelpdesk dashboard */
			Rtbiz_HD::$loader->add_action( 'rtbiz_hd_after_dashboard', $this, 'print_dashboard_js' );

			/* Setup Google Charts */
			Rtbiz_HD::$loader->add_action( 'rtbiz_hd_after_dashboard', $this, 'render_google_charts' );

			Rtbiz_HD::$loader->add_filter( 'set-screen-option', $this, 'tickets_table_set_option', 10, 3 );

			$this->setup_defaults();

		}


		/**
		 * Register dashboard for custom page & hook for MetaBox on it
		 *
		 * @since 0.1
		 */
		public function register_dashboard() {

			if ( rtbiz_hd_check_wizard_completed() ) {

				$author_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );

				$this->screen_id = add_submenu_page( 'edit.php?post_type=' . esc_html( Rtbiz_HD_Module::$post_type ), __( 'Dashboard', RTBIZ_HD_TEXT_DOMAIN ), __( 'Dashboard', RTBIZ_HD_TEXT_DOMAIN ), $author_cap, self::$page_slug, array(
					$this,
					'dashboard_ui',
				) );

				/* Add callbacks for this screen only */

				Rtbiz_HD::$loader->add_action( 'load-' . $this->screen_id, $this, 'page_actions', 9 );
				Rtbiz_HD::$loader->add_action( 'admin_footer-' . $this->screen_id, $this, 'footer_scripts' );

				/* Metaboxes for dashboard widgets */
				Rtbiz_HD::$loader->add_action( 'rthd_dashboard_add_meta_boxes', $this, 'add_dashboard_widgets' );
				Rtbiz_HD::$loader->run();
			}

		}


		public function add_helpdesk_link() {
			?><a id="rtbiz-customize-helpdesk" class="button button-primary button-hero" href="<?php echo admin_url( 'admin.php?page=rthd-' . Rtbiz_HD_Module::$post_type . '-dashboard' ); ?>"><?php _e( 'Helpdesk' ); ?></a><?php
		}

		/**
		 * Setup default value for dashboard.
		 */
		public function setup_defaults() {
			if ( ! empty( $_REQUEST['page'] ) && self::$page_slug == $_REQUEST['page'] && ! metadata_exists( 'user', get_current_user_id(), '_rtbiz_hd_show_welcome_panel' ) ) {
				update_user_meta( get_current_user_id(), '_rtbiz_hd_show_welcome_panel', 1 );
			}
		}


		/**
		 * render dashboard template for given post type
		 *
		 * @since 0.1
		 *
		 * @param $post_type
		 */
		public function dashboard_ui( $post_type ) {
			rtbiz_hd_get_template( 'admin/dashboard.php', array( 'post_type' => $post_type ) );
		}

		/**
		 * Actions to be taken prior to page loading. This is after headers have been set.
		 * This calls the add_meta_boxes hooks, adds screen options and enqueue the postbox.js script.
		 *
		 * @since 0.1
		 */
		public function page_actions() {
			if ( isset( $_REQUEST['page'] ) && self::$page_slug === $_REQUEST['page'] ) {
				do_action( 'add_meta_boxes_' . $this->screen_id, null );
				do_action( 'rthd_dashboard_add_meta_boxes', $this->screen_id, null );

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
		public function footer_scripts() {
			?>
			<script> postboxes.add_postbox_toggles( pagenow );</script>
			<?php
		}

		/**
		 * render google ui charts
		 *
		 * @since 0.1
		 */
		public function render_google_charts() {
			global $rtbiz_hd_reports;
			$rtbiz_hd_reports->render_chart( $this->charts );
		}

		/**
		 * add dashboard widget
		 *
		 * @since 0.1
		 */
		public function add_dashboard_widgets() {
			global $rtbiz_hd_dashboard, $rtbiz_hd_attributes_model, $rtbiz_hd_attributes_relationship_model;

			/* Pie Chart - Progress Indicator (Post status based) */
			add_meta_box( 'rthd-tickets-by-status', __( 'Tickets by Status', RTBIZ_HD_TEXT_DOMAIN ), array(
				$this,
				'tickets_by_status',
					), $rtbiz_hd_dashboard->screen_id, 'column1' );
			/* Line Chart for Answered::Archived */
			add_meta_box( 'rthd-daily-tickets', __( 'Daily Tickets', RTBIZ_HD_TEXT_DOMAIN ), array(
				$this,
				'daily_tickets',
					), $rtbiz_hd_dashboard->screen_id, 'column2' );
			/* Load by Team (Matrix/Table) */
			add_meta_box( 'rthd-team-load', __( 'Work Load', RTBIZ_HD_TEXT_DOMAIN ), array(
				$this,
				'team_load',
					), $rtbiz_hd_dashboard->screen_id, 'column1' );
			/* Top Accounts */
			/* add_meta_box( 'rthd-top-accounts', __( 'Top Accounts', RT_BIZ_HD_TEXT_DOMAIN ), array(
			  $this,
			  'top_accounts',
			  ), $rtbiz_hd_dashboard->screen_id, 'column4' ); */
			/* Top Clients */
			add_meta_box( 'rthd-top-clients', __( 'Top Customers', RTBIZ_HD_TEXT_DOMAIN ), array(
				$this,
				'top_clients',
					), $rtbiz_hd_dashboard->screen_id, 'column2' );

			add_meta_box( 'rthd-tickets-by-product', __( 'Tickets by Products', RTBIZ_HD_TEXT_DOMAIN ), array(
				$this,
				'tickets_by_products',
					), $rtbiz_hd_dashboard->screen_id, 'column1' );

			add_meta_box( 'rthd-customer-by-product-tickets', __( 'Ticket Conversion from Sales', RTBIZ_HD_TEXT_DOMAIN ), array(
				$this,
				'tickets_by_product_purchase',
					), $rtbiz_hd_dashboard->screen_id, 'column2' );
			$relations = $rtbiz_hd_attributes_relationship_model->get_relations_by_post_type( Rtbiz_HD_Module::$post_type );
			foreach ( $relations as $r ) {
				$attr = $rtbiz_hd_attributes_model->get_attribute( $r->attr_id );
				if ( 'taxonomy' == $attr->attribute_store_as ) {
					add_meta_box( 'rthd-tickets-by-' . $attr->attribute_name, $attr->attribute_label . ' ' . __( 'Wise Tickets', RTBIZ_HD_TEXT_DOMAIN ), array( $this, 'dashboard_attributes_widget_content' ), $rtbiz_hd_dashboard->screen_id, 'column1', 'default', array( 'attribute_id' => $attr->id ) );
				}
			}
		}

		public function tickets_by_product_purchase( $obj, $args ) {

			global $rtbiz_hd_product_support, $wpdb;

			$customers_userid = $rtbiz_hd_product_support->get_customers_userid();

			if ( empty( $customers_userid ) ) {
				if ( ! class_exists( 'WooCommerce' ) && ! class_exists( 'Easy_Digital_Downloads' ) ) {
					echo 'This report will be generated with EDD or WooCommerce plugins.';
				} else {
					echo 'No customers found who have created any ticket.';
				}
				return;
			}
			$customers_userid = array_unique( $customers_userid );
			$totalcustomers = count( $customers_userid );

			$customers_userid = implode( ',', $customers_userid );
			$query = $wpdb->prepare( "SELECT count( distinct( meta_value ) ) FROM $wpdb->posts INNER JOIN  $wpdb->postmeta ON post_id = ID  WHERE post_status <> 'trash' and post_type = %s and meta_key = '_rtbiz_hd_created_by' and meta_value in ( " . $customers_userid . ' )', Rtbiz_HD_Module::$post_type );
			$customers_userid = $wpdb->get_col( $query );
			$custWithicket = 0;
			if ( ! empty( $customers_userid ) ) {
				$custWithicket = (int) $customers_userid[0];
			}
			$cols = array( __( 'Purchase', RTBIZ_HD_TEXT_DOMAIN ), __( 'Count', RTBIZ_HD_TEXT_DOMAIN ) );
			$rows = array();
			$rows[] = array( __( 'Customers who have created tickets' ), $custWithicket );
			$rows[] = array( __( 'Customers who have not created tickets' ), $totalcustomers - $custWithicket );

			$data_source = array();
			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;
			$this->charts[] = array(
				'id' => $args['id'],
				'chart_type' => 'pie',
				'data_source' => $data_source,
				'dom_element' => 'rtbiz_pie_' . $args['id'],
				'options' => array(
					'title' => $args['title'],
				),
			);
			?>
			<div id="<?php echo 'rtbiz_pie_' . $args['id']; ?>"></div>
			<?php
		}

		public function get_post_count_excluding_tax( $taxonomy, $post_type ) {
			$terms_name = get_terms( $taxonomy, array( 'fields' => 'id=>slug' ) );
			$count = 0;
			if ( ! $terms_name instanceof WP_Error && ! empty( $terms_name ) ) {
				$terms_names = array_values( $terms_name );
				$posts = new WP_Query( array(
					                       'post_type'   => $post_type,
					                       'post_status' => 'any',
					                       'nopaging'    => true,
					                       'fields'      => 'ids',
					                       'tax_query'   => array(
						                       array(
							                       'taxonomy' => $taxonomy,
							                       'field'    => 'slug',
							                       'terms'    => $terms_names,
							                       'operator' => 'NOT IN',
						                       ),
					                       ),
				                       ) );

				$count = $posts->found_posts;
			}
			return $count;
		}

		/**
		 * Tickets by product pi chart
		 */
		public function tickets_by_products( $obj, $args ) {
			$taxonomy = Rt_Products::$product_slug;
			$terms = get_terms( $taxonomy );
			$data_source = array();
			$cols = array( __( 'Products', RTBIZ_HD_TEXT_DOMAIN ), __( 'Count', RTBIZ_HD_TEXT_DOMAIN ) );
			$rows = array();
			$post_type = Rtbiz_HD_Module::$post_type;
			$total = 0;
			if ( empty( $terms ) ) {
				printf( 'No tickets found for available [ products / downloads ]. <a href="%s" >Add new product</a>', admin_url( 'edit-tags.php?taxonomy=' . Rt_Products::$product_slug . '&post_type=' . Rtbiz_HD_Module::$post_type ) );
				return;
			}
			if ( ! $terms instanceof WP_Error ) {
				foreach ( $terms as $t ) {
					$posts = new WP_Query( array(
						                       'post_type'     => $post_type,
						                       'post_status'   => 'any',
						                       'nopaging'      => true,
						                       $taxonomy       => $t->slug,
						                       'fields'        => 'ids',
					                       ) );
					$rows[] = array(
						$t->name,
						$posts->found_posts,
					);
					$total += $posts->found_posts;
				}
			}

			$rows[] = array( __( 'Uncategorized' ), $this->get_post_count_excluding_tax( $taxonomy, $post_type ) );

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id' => $args['id'],
				'chart_type' => 'pie',
				'data_source' => $data_source,
				'dom_element' => 'rtbiz_pie_' . $args['id'],
				'options' => array(
					'title' => $args['title'],
				),
			);
			?>
			<div id="<?php echo 'rtbiz_pie_' . $args['id']; ?>"></div>
			<?php
		}

		/**
		 * Status wise A single pie will show ticket and amount both: 11 Tickets worth $5555
		 *
		 * @since 0.1
		 */
		public function tickets_by_status() {
			global $rtbiz_hd_module, $wpdb;
			$settings = rtbiz_hd_get_redux_settings();
			$table_name = rtbiz_hd_get_ticket_table_name();
			$post_statuses = array();
			foreach ( $rtbiz_hd_module->statuses as $status ) {
				$post_statuses[ $status['slug'] ] = $status['name'];
			}

			$query = "SELECT post_status, COUNT(id) AS rthd_count FROM {$table_name} WHERE 1=1 GROUP BY post_status";
			$results = $wpdb->get_results( $query );
			if ( ! empty( $results ) ) {
				$data_source = array();
				$cols        = array( __( 'Ticket Status', RTBIZ_HD_TEXT_DOMAIN ), __( 'Count', RTBIZ_HD_TEXT_DOMAIN ) );
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
					'options'     => array( 'title' => __( 'Status wise Tickets', RTBIZ_HD_TEXT_DOMAIN ) ),
				);
			}
			?>
			<div id="rthd_hd_pie_tickets_by_status"></div>
				<?php if ( empty( $results ) ) {
					printf( 'No tickets found. <a target="_blank" href="%s" >Add new ticket</a>', get_page_link( $settings['rthd_support_page'] ) ); }
		}

		/**
		 * Daily tickets UI
		 *
		 * @since 0.1
		 */
		public function daily_tickets() {
			global $rtbiz_hd_module, $rtbiz_hd_ticket_history_model;
			$post_statuses = array();
			foreach ( $rtbiz_hd_module->statuses as $status ) {
				$post_statuses[ $status['slug'] ] = $status['name'];
			}
			$current_date = new DateTime();
			$date_before_30days = new DateTime();
			date_sub( $date_before_30days, date_interval_create_from_date_string( '30 days' ) );
			date_add( $current_date, date_interval_create_from_date_string( '1 days' ) );

			$last_date = date( 'Y-m-d', $current_date->format( 'U' ) );
			$first_date = date( 'Y-m-d', $date_before_30days->format( 'U' ) );
			$args = array(
				'type' => 'post_status',
				'update_time' => array( 'compare' => '>=', 'value' => array( $first_date ) ),
				'update_time' => array( 'compare' => '<=', 'value' => array( $last_date ) ),
			);
			$history = $rtbiz_hd_ticket_history_model->get( $args, false, false, 'update_time asc' );

			$month_map = array();
			$i = 0;
			$first_date = strtotime( $first_date );
			$last_date = strtotime( $last_date );
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
			$cols[0] = __( 'Daily Tickets', RTBIZ_HD_TEXT_DOMAIN );
			foreach ( $post_statuses as $status ) {
				$cols[] = $status;
			}
			$rows = array();
			foreach ( $month_map as $date => $tickets ) {
				$temp = array();
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
				'id' => 5,
				'chart_type' => 'line',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_line_daily_tickets',
				'options' => array(
					'title' => __( 'Daily Tickets', RTBIZ_HD_TEXT_DOMAIN ),
					'vAxis' => json_encode( array( 'viewWindow' => array( 'min' => 0 ) ) ),
				),
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
		public function team_load() {
			global $rtbiz_hd_module, $wpdb;
			$table_name = rtbiz_hd_get_ticket_table_name();
			$post_statuses = array();
			foreach ( $rtbiz_hd_module->statuses as $status ) {
				$post_statuses[ $status['slug'] ] = $status['name'];
			}

			$query = "SELECT assignee, post_status, COUNT(ID) AS rthd_ticket_count FROM {$table_name} WHERE 1=1 GROUP BY assignee, post_status";
			$results = $wpdb->get_results( $query );
			if ( ! empty( $results ) ) {
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
				$cols[]      = array( 'type' => 'string', 'label' => __( 'Users', RTBIZ_HD_TEXT_DOMAIN ) );
				foreach ( $post_statuses as $status ) {
					$cols[] = array( 'type' => 'number', 'label' => $status );
				}

				$rows = array();
				foreach ( $table_matrix as $user => $item ) {

					$temp = array();
					foreach ( $item as $status => $count ) {
						$temp[] = intval( $count );
					}
					$user = get_user_by( 'id', $user );
					if ( empty( $user ) ) {
						continue;
					}
					$url  = esc_url( add_query_arg( array(
						                                'post_type' => Rtbiz_HD_Module::$post_type,
						                                'assigned'  => $user->ID,
					                                ), admin_url( 'edit.php' ) ) );
					if ( ! empty( $user ) ) {
						array_unshift( $temp, '<a href="' . $url . '">' . $user->display_name . '</a>' );
						$rows[] = $temp;
					}
				}

				$data_source['cols'] = $cols;
				$data_source['rows'] = $rows;

				$this->charts[] = array(
					'id'          => 2,
					'chart_type'  => 'table',
					'data_source' => $data_source,
					'dom_element' => 'rthd_hd_table_team_load',
					'options'     => array( 'title' => __( 'Team Load', RTBIZ_HD_TEXT_DOMAIN ) , 'sortColumn' => 1, 'sortAscending' => true ),
				);
			}
			?>
			<div id="rthd_hd_table_team_load"></div>
			<?php if ( empty( $results ) ) {
				_e( 'No staff / ticket found.' ); }
		}

		/**
		 * get top accounts
		 *
		 * @since 0.1
		 */
		public function top_accounts() {
			global $wpdb;
			$table_name = rtbiz_hd_get_ticket_table_name();
			$account = rtbiz_get_company_post_type();

			$query = 'SELECT acc.ID AS account_id, acc.post_title AS account_name ' . ( ( isset( $wpdb->p2p ) ) ? ', COUNT( ticket.ID ) AS account_tickets ' : ' ' ) . "FROM {$wpdb->posts} AS acc " . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$wpdb->p2p} AS p2p ON acc.ID = p2p.p2p_to " : ' ' ) . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$table_name} AS ticket ON ticket.post_id = p2p.p2p_from " : ' ' ) . 'WHERE 2=2 ' . ( ( isset( $wpdb->p2p ) ) ? "AND p2p.p2p_type = '" . Rtbiz_HD_Module::$post_type . "_to_{$account}' " : ' ' ) . "AND acc.post_type = '{$account}' " . 'GROUP BY acc.ID ' . ( ( isset( $wpdb->p2p ) ) ? 'ORDER BY account_tickets DESC ' : ' ' ) . 'LIMIT 0 , 10';

			$results = $wpdb->get_results( $query );

			$data_source = array();
			$cols = array(
				array( 'type' => 'string', 'label' => __( 'Account Name', RTBIZ_HD_TEXT_DOMAIN ) ),
				array( 'type' => 'number', 'label' => __( 'Number of Tickets', RTBIZ_HD_TEXT_DOMAIN ) ),
			);

			$rows = array();
			foreach ( $results as $item ) {
				$url = esc_url( add_query_arg( array(
					                               'post_type'  => Rtbiz_HD_Module::$post_type,
					                               'account_id' => $item->account_id,
				                               ), admin_url( 'edit.php' ) ) );
				$rows[] = array(
					'<a href="' . $url . '">' . $item->account_name . '</a>',
					intval( $item->account_tickets ),
				);
			}

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id' => 3,
				'chart_type' => 'table',
				'data_source' => $data_source,
				'dom_element' => 'rthd_hd_table_top_accounts',
				'options' => array( 'title' => __( 'Top Accounts', RTBIZ_HD_TEXT_DOMAIN ) ),
			);
			?>
			<div id="rthd_hd_table_top_accounts"></div>
			<?php
		}

		/**
		 * top Customer UI
		 *
		 * @since 0.1
		 */
		public function top_clients() {
			global $wpdb;
			$table_name = rtbiz_hd_get_ticket_table_name();
			$contact = rtbiz_get_contact_post_type();
			$account = rtbiz_get_company_post_type();

			//$query = 'SELECT contact.ID AS contact_id, contact.post_title AS contact_name ' . ( ( isset( $wpdb->p2p ) ) ? ', COUNT( ticket.ID ) AS contact_tickets ' : ' ' ) . "FROM {$wpdb->posts} AS contact " . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$wpdb->p2p} AS p2p_lc ON contact.ID = p2p_lc.p2p_to " : ' ' ) . ( ( isset( $wpdb->p2p ) ) ? "JOIN {$table_name} AS ticket ON ticket.post_id = p2p_lc.p2p_from " : ' ' ) . ( ( isset( $wpdb->p2p ) ) ? "LEFT JOIN {$wpdb->p2p} AS p2p_ac ON contact.ID = p2p_ac.p2p_to AND p2p_ac.p2p_type = '{$account}_to_{$contact}'  " : ' ' ) . 'WHERE 2=2 ' . ( ( isset( $wpdb->p2p ) ) ? "AND p2p_lc.p2p_type = '" . Rtbiz_HD_Module::$post_type . "_to_{$contact}' " : ' ' ) . "AND contact.post_type = '{$contact}' " . ( ( isset( $wpdb->p2p ) ) ? 'AND p2p_ac.p2p_type IS NULL ' : ' ' ) . 'GROUP BY contact.ID ' . ( ( isset( $wpdb->p2p ) ) ? 'ORDER BY contact_tickets DESC ' : ' ' ) . 'LIMIT 0 , 10';
			$query = '
						SELECT
							us.ID as contact_id,
							us.display_name AS contact_name,
						    COUNT( ticket.ID ) AS contact_tickets
						FROM
							'.$wpdb->users.' as us
							JOIN
							'.$table_name.' AS ticket
							ON (us.ID = ticket.user_created_by)
						WHERE
							2=2
							GROUP BY us.ID
						    ORDER BY contact_tickets
						    DESC LIMIT 0 , 10';
			$results = $wpdb->get_results( $query );
			if ( ! empty( $results ) ) {
				$data_source = array();
				$cols = array(
					array( 'type' => 'string', 'label' => __( 'Contact Name', RTBIZ_HD_TEXT_DOMAIN ) ),
					array( 'type' => 'number', 'label' => __( 'Number of Tickets', RTBIZ_HD_TEXT_DOMAIN ) ),
				);

				$rows = array();
				foreach ( $results as $item ) {
					$url = esc_url( add_query_arg( array(
						                               'post_type'  => Rtbiz_HD_Module::$post_type,
						                               'contact_id' => $item->contact_id,
					                               ), admin_url( 'edit.php' ) ) );
					$rows[] = array(
						'<a href="' . $url . '">' . $item->contact_name . '</a>',
						intval( $item->contact_tickets ),
					);
				}

				$data_source['cols'] = $cols;
				$data_source['rows'] = $rows;

				$this->charts[] = array(
					'id' => 4,
					'chart_type' => 'table',
					'data_source' => $data_source,
					'dom_element' => 'rthd_hd_table_top_clients',
					'options' => array( 'title' => __( 'Top Clients', RTBIZ_HD_TEXT_DOMAIN ) ),
				);
			}
			?>
			<div id="rthd_hd_table_top_clients">
				<?php if ( empty( $results ) ) {
					_e( 'No customer found' ); } ?>
			</div>
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
		public function dashboard_attributes_widget_content( $obj, $args ) {
			global $rtbiz_hd_rt_attributes;
			$rtbiz_hd_attributes_model = new RT_Attributes_Model();
			$attribute_id = $args['args']['attribute_id'];
			$attr = $rtbiz_hd_attributes_model->get_attribute( $attribute_id );
			$taxonomy = $rtbiz_hd_rt_attributes->get_taxonomy_name( $attr->attribute_name );
			$post_type = Rtbiz_HD_Module::$post_type;
			$terms = get_terms( $taxonomy );

			$data_source = array();
			$cols = array( $attr->attribute_label, __( 'Tickets', RTBIZ_HD_TEXT_DOMAIN ) );
			$rows = array();
			$total = 0;

			foreach ( $terms as $t ) {
				$posts = new WP_Query( array(
					                       'post_type'   => $post_type,
					                       'post_status' => 'any',
					                       'nopaging'    => true,
					                       $taxonomy     => $t->slug,
					                       'fields' => 'ids',
				                       ) );
				$rows[] = array(
					$t->name,
					$posts->found_posts,
				);
				$total += $posts->found_posts;
			}

			$posts = new WP_Query( array(
				                       'post_type'     => $post_type,
				                       'post_status'   => 'any',
				                       'nopaging'      => true,
				                       'fields'        => 'ids',
			                       ) );

			$rows[] = array( __( 'Others', RTBIZ_HD_TEXT_DOMAIN ), $posts->found_posts - $total );

			$data_source['cols'] = $cols;
			$data_source['rows'] = $rows;

			$this->charts[] = array(
				'id' => $args['id'],
				'chart_type' => 'pie',
				'data_source' => $data_source,
				'dom_element' => 'rthd_pie_' . $args['id'],
				'options' => array( 'title' => $args['title'] ),
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
		public function tickets_table_set_option( $status, $option, $value ) {
			return $value;
		}

		/**
		 * Update rtHelpdesk welcome panel
		 */
		public function ajax_update_welcome_panel() {

			check_ajax_referer( 'rthd-welcome-panel-nonce', 'rthdwelcomepanelnonce' );

			$author_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );

			if ( ! current_user_can( $author_cap ) ) {
				wp_die( -1 );
			}

			update_user_meta( get_current_user_id(), '_rtbiz_hd_show_welcome_panel', empty( $_POST['visible'] ) ? 0 : 1 );

			wp_die( 1 );
		}

		/**
		 * Check welcome panel for logged in user.
		 */
		public function check_welcome_panel() {
			if ( isset( $_GET['rthdwelcome'] ) ) {
				$welcome_checked = empty( $_GET['rthdwelcome'] ) ? 0 : 1;
				update_user_meta( get_current_user_id(), '_rtbiz_hd_show_welcome_panel', $welcome_checked );
			}
		}

		/**
		 * Display welcome widget on rtHelpdesk dashboard.
		 */
		public function welcome_panel() {
			global $rtbiz_hd_attributes;

			$settings = rtbiz_hd_get_redux_settings();
			$welcome_label = 'Helpdesk';

			$admin_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'admin' );
			$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );
			?>
			<div class="welcome-panel-content">
				<h3><?php _e( 'Welcome to ' . $welcome_label ); ?></h3>
				<p class="about-description"><?php _e( 'We&#8217;ve assembled some links to get you started:' ); ?></p>
				<div class="welcome-panel-column-container">
					<div class="welcome-panel-column">
						<?php if ( current_user_can( $admin_cap ) ) : ?>
							<h4><?php _e( 'Get Started' ); ?></h4>
							<a id="rt-hd-customize-biz" class="button button-primary button-hero" href="<?php echo admin_url( 'edit.php?post_type=' . Rtbiz_HD_Module::$post_type . '&page=' . Rtbiz_HD_Settings::$page_slug ); ?>"><?php _e( 'Helpdesk Settings' ); ?></a>
						<?php endif; ?>
					</div>
					<div class="welcome-panel-column">
						<h4><?php _e( 'Next Steps' ); ?></h4>
						<ul>
							<?php if ( current_user_can( $editor_cap ) ) { ?>
								<div id="rthd-support-page">
									<?php if ( isset( $settings['rthd_support_page'] ) && ! empty( $settings['rthd_support_page'] ) && get_post( $settings['rthd_support_page'] ) ) : ?>
										<li>
											<a id="rthd-view-support-page" class="welcome-icon welcome-view-site" target="_blank" href="<?php echo get_page_link( $settings['rthd_support_page'] ); ?>"><?php _e( 'Add Support Ticket' ); ?></a>
										</li>
									<?php else : ?>
										<li>
											<a id="rthd-new-support-page" class="rthd-new-support-page welcome-icon welcome-add-page" href="javascript:;"><?php _e( 'Setup Support Page' ); ?></a>
											<img id="rthd-support-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
										</li>
									<?php endif; ?>
								</div>
								<!--<li><?php /* printf( '<a id="rtiz-add-ticket" href="%s" class="welcome-icon welcome-admin-users">' . __( 'Add new Ticket' ) . '</a>', admin_url( 'post-new.php?post_type=' . Rt_HD_Module::$post_type ) ); */ ?></li>-->
								<li><?php printf( '<a href="%s" class="welcome-icon welcome-networking">' . __( 'Setup Attributes' ) . '</a>', admin_url( 'edit.php?post_type=' . Rtbiz_HD_Module::$post_type . '&page=' . $rtbiz_hd_attributes->attributes_page_slug ) ); ?></li>
							<?php } ?>
						</ul>
					</div>

					<div class="welcome-panel-column welcome-panel-last">
						<h4><?php _e( 'More Actions' ); ?></h4>
						<ul>
							<li><?php printf( '<a href="%s" target="_blank" class="welcome-icon welcome-learn-more">' . __( 'Learn more about getting started' ) . '</a>', 'http://docs.rtcamp.com/rtbiz/' ); ?></li>
							<?php if ( current_user_can( $editor_cap ) ) { ?>
								<li><?php printf( '<a href="%s" class="welcome-icon welcome-universal-access-alt">' . __( 'Add new Team' ) . '</a>', admin_url( 'edit-tags.php?taxonomy=' . Rtbiz_Teams::$slug . '&post_type=' . Rtbiz_HD_Module::$post_type ) ); ?></li>
							<?php } ?>
						</ul>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Add js for hide/show welcome panel in rtHelpdesk dashboard.
		 */
		public function print_dashboard_js() {
			if ( isset( $_GET['rthdwelcome'] ) ) {
				$welcome_checked = empty( $_GET['rthdwelcome'] ) ? 0 : 1;
				update_user_meta( get_current_user_id(), '_rtbiz_hd_show_welcome_panel', $welcome_checked );
			} else {
				$welcome_checked = get_user_meta( get_current_user_id(), '_rtbiz_hd_show_welcome_panel', true );
				if ( 2 == $welcome_checked && wp_get_current_user()->user_email != get_option( 'admin_email' ) ) {
					$welcome_checked = false;
				}
			}
			?>
			<script>
				jQuery( document ).ready( function ( $ ) {
					var rthd_welcomePanel = $( '#welcome-panel' ),
							rthd_welcomePanelHide = '#rthd_welcome_panel-hide',
							rthd_updateWelcomePanel;

					rthd_updateWelcomePanel = function ( visible ) {
						$.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
							action: 'rtbiz_hd_update_welcome_panel',
							visible: visible,
							rthdwelcomepanelnonce: $( '#rthdwelcomepanelnonce' ).val()
						} );
					};

					if ( rthd_welcomePanel.hasClass( 'hidden' ) && $( rthd_welcomePanelHide ).prop( 'checked' ) ) {
						rthd_welcomePanel.removeClass( 'hidden' );
					}

					$( '.welcome-panel-close, .welcome-panel-dismiss a', rthd_welcomePanel ).click( function ( e ) {
						e.preventDefault();
						rthd_welcomePanel.addClass( 'hidden' );
						rthd_updateWelcomePanel( 0 );
						$( '#rthd_welcome_panel-hide' ).prop( 'checked', false );
					} );

					$( document ).on( 'click', rthd_welcomePanelHide, function () {
						rthd_welcomePanel.toggleClass( 'hidden', ! this.checked );
						rthd_updateWelcomePanel( this.checked ? 1 : 0 );
					} );

					$( document ).on( 'click', '#rthd-new-support-page', function () {
						var requestArray = { };

						requestArray.page_action = 'add';
						requestArray.action = 'rtbiz_hd_setup_support_page';
						jQuery( '#rthd-support-spinner' ).show();
						jQuery.ajax( {
							url: ajaxurl,
							dataType: 'json',
							type: 'post',
							data: requestArray,
							success: function ( response ) {
								if ( response.status ) {
									jQuery( '#rthd-support-page' ).html( response.html );
								}
							},
							error: function () {
								jQuery( '#rthd-support-spinner' ).hide();
								alert( 'Something goes wrong. Please try again.' );
							}
						} );
					} );

					$( '#screen-options-wrap #adv-settings .metabox-prefs' ).append( "<label for='rthd_welcome_panel-hide'><input type='checkbox' id='rthd_welcome_panel-hide' value='welcome-panel' <?php echo checked( (bool) $welcome_checked, true, false ); ?> /><?php _e( 'Welcome', RTBIZ_HD_TEXT_DOMAIN ); ?></label>" );
				} );
			</script>
			<?php
		}

		/**
		 * Create page for support form if it is not set from settings page.
		 */
		public function ajax_setup_support_page() {

			$response = array();
			$response['status'] = false;
			$page_name = 'Support';
			if ( ! empty( $_POST['new_page'] ) ) {
				$page_name = $_POST['new_page'];
			}
			if ( isset( $_POST['page_action'] ) && 'add' == $_POST['page_action'] ) {
				$support_page = array(
					'post_type' => 'page',
					'post_title' => $page_name,
					'post_content' => '[rtbiz_hd_support_form]',
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
				);
				$support_page_id = wp_insert_post( $support_page );
				$response['status'] = true;
			} else if ( ! empty( $_POST['old_page'] ) ) {
				$support_page = get_post( $_POST['old_page'] );
				if ( strstr( $support_page->post_content, '[rtbiz_hd_support_form]' ) === false ) {
					$support_page->post_content .= '[rtbiz_hd_support_form]';
					$support_page_id = wp_update_post( $support_page );
				}
				$response['status'] = true;
			}
			if ( ! empty( $support_page_id ) && ! $support_page_id instanceof WP_Error ) {
				/* Set support page option. */
				rtbiz_hd_set_redux_settings( 'rthd_support_page', $support_page_id );
				$response['status'] = true;
				$response['html'] = '<li><a id="rthd-view-support-page" class="welcome-icon welcome-view-site" target="_blank" href="' . get_page_link( $support_page_id ) . '">' . __( 'View Support Page' ) . '</a></li>';
			}
			echo json_encode( $response );
			die();
		}

	}

}
