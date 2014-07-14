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
 * Description of Rt_CRM_Leads_List_View
 *
 * @author udit
 */

if( ! class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( !class_exists( 'Rt_CRM_Leads_List_View' ) ) {
	class Rt_CRM_Leads_List_View extends WP_List_Table {

		var $table_name;
		var $post_type;
		var $post_statuses;
		var $labels;
		var $settings;
		var $relations;

		public function __construct() {

			global $rt_crm_attributes_relationship_model, $rt_crm_module;
			$this->table_name = rtcrm_get_lead_table_name();
			$this->labels = $rt_crm_module->labels;
			$this->post_type = $rt_crm_module->post_type;
			$this->settings = rthd_get_settings();
			$this->relations = $rt_crm_attributes_relationship_model->get_relations_by_post_type( $this->post_type );
			$this->post_statuses = $rt_crm_module->get_custom_statuses();

			$args = array(
				'singular'=> $this->labels['singular_name'], //Singular label
				'plural' => $this->labels['all_items'], //plural label, also this well be one of the table css class
				'ajax'	=> true, //We won't support Ajax for this table
				'screen' => get_current_screen(),
			);
			parent::__construct( $args );
		}

		/**
		* Add extra markup in the toolbars before or after the list
		* @param string $which, helps you decide if you add the markupafter (bottom) or before (top) the list */
		function extra_tablenav( $which ) {
			$search = @$_POST['s'] ? esc_attr( $_POST['s'] ) : '';
			if ( $which == 'top' ) {
				//The code that goes before the table is here
//				echo"Before the table";
				$this->search_box( 'Search', 'search_id' );
			}
			if ( $which == 'bottom' ) {
				//The code that goes after the table is there
//				echo"After the table";
			}
		}

		/**
		 * Get an associative array ( id => link ) with the list
		 * of views available on this table.
		 *
		 * @since 3.1.0
		 * @access protected
		 *
		 * @return array
		 */
		function get_views() {

			global $wpdb;
			$views = array();

			$current = ( isset( $_REQUEST['post_status'] ) && ! empty( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : 'all' );
			$temp = $wpdb->get_results( "SELECT post_status, count(id) AS lead_count FROM {$this->table_name} GROUP BY post_status HAVING lead_count > 0", ARRAY_A );
			if ( empty( $temp ) ) {
				return $views;
			}
			$num_count = array();
			foreach ( $temp as $status ) {
				$num_count[ $status['post_status'] ] = intval( $status['lead_count'] );
			}

			//All link
			$class = ( $current == 'all' ) ? ' class="current"' :'';
			$url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url( 'edit.php' ) );
			$count = array_sum( $num_count );
			$views['all'] = "<a href='{$url}' {$class} >".__('All <span class="count">('.$count.')</span>')."</a>";

			foreach ( $this->post_statuses as $status ) {
				if ( isset( $num_count[ $status['slug'] ] ) && ! empty( $num_count[ $status['slug'] ] ) ) {
					$url = add_query_arg( 'post_status', $status['slug'] );
					$class = ( $current == $status['slug'] ) ? ' class="current"' :'';
					$count = $num_count[ $status['slug'] ];
					$views[ $status['slug'] ] = "<a href='{$url}' {$class}>".$status['name']." <span class='count'>(".$count.")</span></a>";
					unset( $num_count[ $status['slug'] ] );
				}
			}

			foreach ( $num_count as $key => $value ) {
				$url = add_query_arg( 'post_status',$key );
				$class = ( $current == $key ) ? ' class="current"' :'';
				$count = $value;
				$views[$key] = "<a href='{$url}' {$class}>".$key." <span class='count'>(".$count.")</span></a>";
			}

			return $views;
		}

		/**
		* Define the columns that are going to be used in the table
		* @return array $columns, the array of columns to use with the table */
		public function get_columns() {

			global $rt_crm_attributes_model;

			$columns = array(
				'cb' => '<input type="checkbox" />',
				'rtcrm_title'=> __( 'Title' ),
				'rtcrm_assignee'=> __( 'Assignee' ),
				'rtcrm_create_date'=> __( 'Create Date' ),
				'rtcrm_update_date'=> __( 'Last Updated Date' ),
				'rtcrm_closing_date'=> __( 'Closing Date' ),
				'rtcrm_status'=> __( 'Status' ),
				'rtcrm_created_by'=> __( 'Created By' ),
				'rtcrm_updated_by'=> __( 'Last Updated By' ),
				'rtcrm_closed_by'=> __( 'Closed By' ),
				'rtcrm_closing_reason' => __( 'Closing Reason' ),
			);

			foreach ( $this->relations as $relation ) {
				$attr = $rt_crm_attributes_model->get_attribute( $relation->attr_id );
				$attr_name = str_replace('-', '_', rtcrm_attribute_taxonomy_name( $attr->attribute_name ) );
				$columns['rtcrm_'.$attr_name] = __( $attr->attribute_label );
			}

			if ( isset( $this->settings['attach_contacts'] ) && $this->settings['attach_contacts'] == 'yes' ) {
				$contact_name = rt_biz_get_person_post_type();
				$columns['rtcrm_'.$contact_name] = __( 'Contacts' );
			}
			if ( isset( $this->settings['attach_accounts'] ) && $this->settings['attach_accounts'] == 'yes' ) {
				$accounts_name = rt_biz_get_organization_post_type();
				$columns['rtcrm_'.$accounts_name] = __( 'Accounts' );
			}

			return $columns;
		}

		/**
		* Decide which columns to activate the sorting functionality on
		* @return array $sortable, the array of columns that can be sorted by the user */
		public function get_sortable_columns() {
			$sortable = array(
				'rtcrm_title'=> array('rtcrm_title', false),
				'rtcrm_assignee'=> array('rtcrm_assignee', false),
				'rtcrm_create_date'=> array('rtcrm_create_date', false),
				'rtcrm_update_date'=> array('rtcrm_update_date', false),
				'rtcrm_closing_date'=> array('rtcrm_closing_date', false),
			);
			return $sortable;
		}

		/**
		 * Get an associative array ( option_name => option_title ) with the list
		 * of bulk actions available on this table.
		 *
		 * @since 3.1.0
		 * @access protected
		 *
		 * @return array
		 */
		function get_bulk_actions() {
			$actions = array(
				'delete' => __( 'Trash' ),
			);
			return $actions;
		}

		/**
		 * Prepare the table with different parameters, pagination, columns and table elements */
		function prepare_items() {
			global $wpdb;
			global $rt_crm_attributes_model;

			$s = @$_POST['s'];

			/* -- Preparing your query -- */
			$query = "SELECT
						rt_lead.id AS rtcrm_id,
						rt_lead.post_id AS rtcrm_post_id,
						rt_lead.post_title AS rtcrm_title,
						rt_lead.assignee AS rtcrm_assignee,
						rt_lead.date_create AS rtcrm_create_date,
						rt_lead.date_update AS rtcrm_update_date,
						rt_lead.date_closing AS rtcrm_closing_date,
						rt_lead.post_status AS rtcrm_status,
						rt_lead.user_created_by AS rtcrm_created_by,
						rt_lead.user_updated_by AS rtcrm_updated_by,
						rt_lead.user_closed_by AS rtcrm_closed_by,
						rt_lead.rt_closing_reason AS rtcrm_closing_reason";

			foreach ( $this->relations as $relation ) {
				$attr = $rt_crm_attributes_model->get_attribute( $relation->attr_id );
				$attr_name = str_replace('-', '_', rtcrm_attribute_taxonomy_name($attr->attribute_name));
				$query .= " , rt_lead.{$attr_name} AS rtcrm_{$attr_name} ";
			}

			if ( isset( $this->settings['attach_contacts'] ) && $this->settings['attach_contacts'] == 'yes' ) {
				$contact_name = rt_biz_get_person_post_type();
				$query .= " ,rt_lead.{$contact_name} AS rtcrm_{$contact_name} ";
			}
			if ( isset( $this->settings['attach_accounts'] ) && $this->settings['attach_accounts'] == 'yes' ) {
				$account_name = rt_biz_get_organization_post_type();
				$query .= " ,rt_lead.{$account_name} AS rtcrm_{$account_name} ";
			}

			$query	.= " FROM {$this->table_name} AS rt_lead ";
			$query .= " WHERE 1=1 ";

			if ( $s ) {
				$query .= " AND ( ";
				$query .= " rt_lead.post_title LIKE '%{$s}%' ";
				$query .= " OR rt_lead.post_content LIKE '%{$s}%' ";
				$query .= ') ';
			}

			if ( isset( $_GET['post_status'] ) ) {
				$query .= " AND rt_lead.post_status = '".$_GET['post_status']."'";
			}

			if ( isset( $_GET['assignee'] ) ) {
				$query .= " AND rt_lead.assignee = '".$_GET['assignee']."'";
			}

			if ( isset( $_GET['user_updated_by'] ) ) {
				$query .= " AND rt_lead.user_updated_by = '".$_GET['user_updated_by']."'";
			}

			if ( isset( $_GET['user_created_by'] ) ) {
				$query .= " AND rt_lead.user_created_by = '".$_GET['user_created_by']."'";
			}

			if ( isset( $_GET['user_closed_by'] ) ) {
				$query .= " AND rt_lead.user_closed_by = '".$_GET['user_closed_by']."'";
			}

			$tax_query = $this->check_tax_query();
			if ( !empty( $tax_query ) ) {
				foreach ( $tax_query as $key => $value ) {
					$query .= " AND CONCAT( ',' , rt_lead.{$key} , ',' ) LIKE '%,".$value.",%'";
				}
			}

			/* -- Ordering parameters -- */
			//Parameters that are going to be used to order the result
			$orderby = ! empty( $_GET["orderby"] ) ? mysql_real_escape_string( $_GET["orderby"] ) : 'ASC';
			$order = ! empty( $_GET["order"] ) ? mysql_real_escape_string( $_GET["order"] ) : '';
			if ( ! empty( $orderby ) & ! empty( $order ) ) {
				$query.=' ORDER BY '.$orderby.' '.$order;
			} else {
				$query .= ' ORDER BY rtcrm_post_id DESC';
			}

			/* -- Pagination parameters -- */
			//Number of elements in your table?
			$totalitems = $wpdb->query( $query );
			//return the total number of affected rows

			//How many to display per page?
			//$perpage = 25;
			$perpage = $this->get_items_per_page( rtcrm_post_type_name( $this->labels['name'] ).'_per_page', 10 );

			//Which page is this?
			$paged = ! empty( $_GET['paged'] ) ? mysql_real_escape_string( $_GET['paged'] ) : '';
			//Page Number
			if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) { $paged=1; }
			//How many pages do we have in total?
			$totalpages = ceil( $totalitems / $perpage );
			//adjust the query to take pagination into account
			if ( ! empty( $paged ) && ! empty( $perpage ) ) {
				$offset = ( $paged - 1 ) * $perpage;
				$query .= ' LIMIT ' . (int) $offset . ' , ' . (int) $perpage;
			}

			/* -- Register the pagination -- */
			$this->set_pagination_args(
				array(
					'total_items' => $totalitems,
					'total_pages' => $totalpages,
					'per_page' => $perpage,
				)
			);
			//The pagination links are automatically built according to those parameters

			/* -- Register the Columns -- */
//			$columns = $this->get_columns();
//			$hidden = array();
//			$sortable = $this->get_sortable_columns();
//			$this->_column_headers = array($columns, $hidden, $sortable);
			$this->_column_headers = $this->get_column_info();

			/* -- Fetch the items -- */
			$this->items = $wpdb->get_results( $query );
		}

		/**
		 * Check For Taxonomy Query, if any
		 */
		function check_tax_query() {
			global $rt_crm_attributes_model;
			$taxonomies = array();
			foreach ($this->relations as $relation) {
				$attr = $rt_crm_attributes_model->get_attribute( $relation->attr_id );
				$attr_name = rtcrm_attribute_taxonomy_name( $attr->attribute_name );
				$key = str_replace( '-', '_', $attr_name );
				if ( isset( $_GET[$attr_name] ) ) {
					$term = get_term_by( 'slug', $_GET[$attr_name], $attr_name );
					$taxonomies[$key] = ( isset( $term->term_id ) && !empty( $term->term_id ) ) ? $term->term_id : 'NULL';
				}
			}

			$contact_name = rt_biz_get_person_post_type();
			if ( isset( $this->settings['attach_contacts'] ) && $this->settings['attach_contacts'] == 'yes' && isset( $_GET[$contact_name] ) ) {
				$taxonomies[$contact_name] = $_GET[$contact_name];
			}
			$account_name = rt_biz_get_organization_post_type();
			if ( isset( $this->settings['attach_accounts'] ) && $this->settings['attach_accounts'] == 'yes' && isset( $_GET[$account_name] ) ) {
				$taxonomies[$account_name] = $_GET[$account_name];
			}
			$closing_reason_name = rtcrm_attribute_taxonomy_name( 'closing_reason' );
			$key = str_replace( '-', '_', $closing_reason_name );
			if ( isset( $_GET[$closing_reason_name] ) ) {
				$term = get_term_by( 'slug', $_GET[$closing_reason_name], $closing_reason_name );
				$taxonomies[$key] = ( isset( $term->term_id ) && !empty( $term->term_id ) ) ? $term->term_id : 'NULL';
			}

			return $taxonomies;
		}

		/**
		 *
		 */
		function get_status_label( $slug ) {
			foreach ($this->post_statuses as $status) {
				if ( $slug === $status['slug'] ) {
					return $status;
				}
			}
			return false;
		}

		/**
		 * Display the rows of records in the table
		 * @return string, echo the markup of the rows */
		function display_rows() {

			global $rt_crm_attributes_model;

			//Get the records registered in the prepare_items method
			$records = $this->items;
			//Get the columns registered in the get_columns and get_sortable_columns methods
			list( $columns, $hidden, $sortable ) = $this->get_column_info();

			//Loop for each record
			$i = 0;
			if ( ! empty( $records ) ) {
				foreach( $records as $rec ) {
					//Open the line
					echo '<tr id="record_'.$rec->rtcrm_id.'" class="'.(($i%2)?'alternate':'').'">';
					foreach ( $columns as $column_name => $column_display_name ) {

						$class = "class='$column_name column-$column_name'";
						$style = "";
						if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
						$attributes = $class . $style;

						//Display the cell
						switch ( $column_name ) {
							case "cb":
								echo '<th scope="row" class="check-column">';
									echo '<input type="checkbox" name="'.$this->post_type.'[]" id="cb-select-'.$rec->rtcrm_id.'" value="'.$rec->rtcrm_id.'" />';
								echo '</th>';
								break;
							case "rtcrm_title":
								echo '<td '.$attributes.'>'.'<a href="'.admin_url('edit.php?post_type='.$this->post_type.'&page=rtcrm-add-'.$this->post_type.'&'.$this->post_type.'_id='.$rec->rtcrm_post_id).'">'.$rec->rtcrm_title.'</a>';
								$actions = array(
									'edit'      => '<a href="'.admin_url('edit.php?post_type='.$this->post_type.'&page=rtcrm-add-'.$this->post_type.'&'.$this->post_type.'_id='.$rec->rtcrm_post_id).'">Edit</a>',
									'delete'    => '<a href="'.admin_url('edit.php?post_type='.$this->post_type.'&page=rtcrm-add-'.$this->post_type.'&'.$this->post_type.'_id='.$rec->rtcrm_post_id.'&action=trash').'">Trash</a>',
								);
								echo $this->row_actions( $actions );
								//.'< /td>';
								break;
							case "rtcrm_assignee":
								if(!empty($rec->rtcrm_assignee)) {
									$user = get_user_by('id', $rec->rtcrm_assignee);
									$url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
									$url = add_query_arg( 'assignee', $rec->rtcrm_assignee, $url );
									echo '<td '.$attributes.'><a href="'.$url.'">'.$user->display_name.'</a>';
								} else {
									echo '<td '.$attributes.'>-';
								}
								//.'< /td>';
								break;
							case "rtcrm_create_date":
								$date = date_parse($rec->rtcrm_create_date);
								if(checkdate($date['month'], $date['day'], $date['year'])) {
									$dtObj = new DateTime($rec->rtcrm_create_date);
									echo '<td '.$attributes.'><span title="'.$rec->rtcrm_create_date.'">' . human_time_diff( $dtObj->format('U') , time() ) . __(' ago') . '</span>';
								} else {
									echo '<td '.$attributes.'>-';
								}
								//.'< /td>';
								break;
							case "rtcrm_update_date":
								$date = date_parse($rec->rtcrm_update_date);
								if(checkdate($date['month'], $date['day'], $date['year'])) {
									$dtObj = new DateTime($rec->rtcrm_update_date);
									echo '<td '.$attributes.'><span title="'.$rec->rtcrm_update_date.'">' . human_time_diff( $dtObj->format('U') , time() ) . __(' ago') . '</span>';
								} else {
									echo '<td '.$attributes.'>-';
								}
								//.'< /td>';
								break;
							case "rtcrm_closing_date":
								$date = date_parse($rec->rtcrm_closing_date);
								if(checkdate($date['month'], $date['day'], $date['year'])) {
									$dtObj = new DateTime($rec->rtcrm_closing_date);
									echo '<td '.$attributes.'><span title="'.$rec->rtcrm_closing_date.'">' . human_time_diff( $dtObj->format('U') , time() ) . __(' ago') . '</span>';
								} else {
									echo '<td '.$attributes.'>-';
								}
								//.'< /td>';
								break;
							case "rtcrm_status":
								if(!empty($rec->rtcrm_status)) {
									$status = $this->get_status_label($rec->rtcrm_status);
									if ( !empty( $status ) ) {
										$url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
										$url = add_query_arg( 'post_status', $rec->rtcrm_status, $url );
										echo '<td '.$attributes.'><a href="'.$url.'">'.$status['name'].'</a>';
									} else {
										echo '<td '.$attributes.'><a href="'.$url.'">'.$rec->rtcrm_status.'</a>';
									}
								} else {
									echo '<td '.$attributes.'>-';
								}
								//.'< /td>';
								break;
							case "rtcrm_closing_reason":
								if(!empty($rec->rtcrm_closing_reason)) {
									echo '<td '.$attributes.'>';
									$termArr = array();
									$reasons = explode(',', $rec->rtcrm_closing_reason);
									$base_url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
									foreach ($reasons as $reason) {
										$term = get_term_by( 'id', $reason, rtcrm_attribute_taxonomy_name( 'closing_reason' ) );
										if ( !empty( $term ) ) {
											$url = add_query_arg( rtcrm_attribute_taxonomy_name('closing_reason'), $term->slug, $base_url );
											$termArr[] = '<a href="'.$url.'">'.$term->name.'</a>';
										}
									}
									echo implode(',', $termArr);
								} else {
									echo '<td '.$attributes.'>-';
								}
								//.'< /td>';
								break;
							case "rtcrm_created_by":
								if(!empty($rec->rtcrm_created_by)) {
									$user = get_user_by('id', $rec->rtcrm_created_by);
									$url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
									$url = add_query_arg( 'user_created_by', $rec->rtcrm_created_by, $url );
									echo '<td '.$attributes.'><a href="'.$url.'">'.$user->display_name.'</a>';
								} else
									echo '<td '.$attributes.'>-';
								//.'< /td>';
								break;
							case "rtcrm_updated_by":
								if(!empty($rec->rtcrm_updated_by)) {
									$user = get_user_by('id', $rec->rtcrm_updated_by);
									$url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
									$url = add_query_arg( 'user_updated_by', $rec->rtcrm_updated_by, $url );
									echo '<td '.$attributes.'><a href="'.$url.'">'.$user->display_name.'</a>';
								} else
									echo '<td '.$attributes.'>-';
								//.'< /td>';
								break;
							case "rtcrm_closed_by":
								if(!empty($rec->rtcrm_closed_by)) {
									$user = get_user_by('id', $rec->rtcrm_closed_by);
									$url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
									$url = add_query_arg( 'user_closed_by', $rec->rtcrm_closed_by, $url );
									echo '<td '.$attributes.'><a href="'.$url.'">'.$user->display_name.'</a>';
								} else
									echo '<td '.$attributes.'>-';
								//.'< /td>';
								break;
							default:
								foreach ($this->relations as $relation) {
									$attr = $rt_crm_attributes_model->get_attribute( $relation->attr_id );
									$attr_name = str_replace('-', '_', rtcrm_attribute_taxonomy_name($attr->attribute_name));

									if($column_name == 'rtcrm_'.$attr_name) {
										if(!empty($rec->{'rtcrm_'.$attr_name})) {
											if( $attr->attribute_store_as == 'meta' ) {
												echo '<td '.$attributes.'>'.$rec->{'rtcrm_'.$attr_name};
											} else if( $attr->attribute_store_as == 'taxonomy' ) {
												echo '<td '.$attributes.'>';
												$terms = wp_get_post_terms( $rec->rtcrm_post_id, rtcrm_attribute_taxonomy_name( $attr->attribute_name ) );
												$term_display = array();
												$base_url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url('edit.php') );
												foreach ($terms as $term) {
													$url = add_query_arg( rtcrm_attribute_taxonomy_name( $attr->attribute_name ), $term->slug, $base_url );
													$term_display[] = '<a href="'.$url.'">'.$term->name.'</a>';
												}
												echo implode(' , ', $term_display);
											}
										} else {
											echo '<td '.$attributes.'>-';
										}
									}
								}

								if ( isset( $this->settings['attach_contacts'] ) && $this->settings['attach_contacts'] == 'yes' ) {
									$contact_name = rt_biz_get_person_post_type();
									if ( $column_name == 'rtcrm_'.$contact_name ) {
										if ( ! empty( $rec->{'rtcrm_'.$contact_name} ) ) {
											echo '<td '.$attributes.'>';
											$termArr = array();
											$contacts = explode( ',', $rec->{'rtcrm_'.$contact_name} );
											$base_url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url( 'edit.php' ) );
											foreach ( $contacts as $contact ) {
												$term = get_post( $contact );
												if ( ! empty( $term ) ) {
													$url = add_query_arg( $contact_name, $term->ID, $base_url );
													$termArr[] = '<a href="'.$url.'">'.$term->post_title.'</a>';
												}
											}
											echo implode( ' , ', $termArr );
										} else
											echo '<td '.$attributes.'>-';
									}
								}
								if ( isset( $this->settings['attach_accounts'] ) && $this->settings['attach_accounts'] == 'yes' ) {
									$accounts_name = rt_biz_get_organization_post_type();
									if ( $column_name == 'rtcrm_'.$accounts_name ) {
										if ( ! empty( $rec->{'rtcrm_'.$accounts_name} ) ) {
											echo '<td '.$attributes.'>';
											$termArr = array();
											$accounts = explode( ',', $rec->{'rtcrm_'.$accounts_name} );
											$base_url = add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtcrm-all-'.$this->post_type ), admin_url( 'edit.php' ) );
											foreach ( $accounts as $account ) {
												$term = get_post( $account );
												if ( ! empty( $term ) ) {
													$url = add_query_arg( $accounts_name, $term->ID, $base_url );
													$termArr[] = '<a href="'.$url.'">'.$term->post_title.'</a>';
												}
											}
											echo implode( ' , ', $termArr );
										} else
											echo '<td '.$attributes.'>-';
									}
								}
								break;
						}
					}
					//Close the line
//					echo'< /tr>';
				}
			}
		}
	}
}
