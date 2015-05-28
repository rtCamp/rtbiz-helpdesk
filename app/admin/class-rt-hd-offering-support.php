<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if ( ! class_exists( 'Rt_HD_Offering_Support' ) ) {

	/**
	 * Class Rt_HD_Offering_Support
	 * Provide wooCommerce & EDD integration with HelpDesk for product support
	 *
	 */
	class Rt_HD_Offering_Support {

		/**
		 * @var Flag for WooCommerce active or not
		 */
		var $isWoocommerceActive = false;

		/**
		 * @var Flag for EDD active or not
		 */
		var $iseddActive = false;

		/**
		 * @var Store product post type of active Plugin if plugin is not activate it is false
		 */
		var $activePostType = false;

		/**
		 * @var store order post type of active plugin if plugin is not activate it is false
		 */
		var $order_post_type = false;

		/**
		 * construct
		 *
		 * @since 0.1
		 */
		function __construct() {
			$this->hooks();
		}

		/**
		 * Hook
		 *
		 * @since 0.1
		 */
		function hooks() {

			// filter for add new action link on My Account page
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'wocommerce_actions_link' ), 10, 2 );
			add_action( 'edd_download_history_header_end', array( $this, 'edd_action_link_header' ) );
			add_action( 'edd_download_history_row_end', array( $this, 'edd_support_link' ), 10, 2 );

			// Add product information in ticket meta.
			add_action( 'rt_hd_add_ticket_offering_info', array( &$this, 'rt_hd_add_ticket_offering_info_callback' ) );

			// my account and download history ticket list view
			add_action( 'woocommerce_after_my_account', array( $this, 'woo_my_tickets_my_account' ) );
			//			add_action( 'edd_after_download_history', array( $this, 'woo_my_tickets_my_account' ) );

			// Metaboxes for Orders
			// WP 3.0+
			add_action( 'add_meta_boxes', array( $this, 'order_support_history' ) );
			// backwards compatible
			add_action( 'admin_init', array( $this, 'order_support_history' ), 1 );

			// User Purchase History on Ticket Page
			add_action( 'rtbiz_hd_user_purchase_history', array( $this, 'user_purchase_history' ) );

			add_action( Rt_Offerings::$offering_slug . '_add_form_fields', array( $this, 'offering_add_custom_field' ), 10, 2 );
			add_action( Rt_Offerings::$offering_slug. '_edit_form', array( $this, 'offering_add_custom_field' ), 10, 2 );

			add_action( 'create_term', array( $this, 'save_offerings' ), 10, 2 );
			add_action( 'edit_term', array( $this, 'save_offerings' ), 10, 2 );

			add_action( 'rt_biz_offering_column_content', array( $this, 'manage_offering_column_body' ), 10, 3 );
			add_filter( 'rt_biz_offerings_columns', array( $this, 'manage_offering_column_header' ) );

			// Show tickets in woocommerce order page
			add_action( 'woocommerce_view_order', array( $this, 'woocommerce_view_order_show_ticket' ) );
			//			add_filter( 'edd_payment_receipt_after_table', array( $this, 'edd_view_order_show_ticket' ), 10, 3 );

			// Show ticket column in product and download post type
			add_filter( 'edd_download_columns', array( $this, 'manage_woo_edd_post_columns' ) );
			add_filter( 'manage_product_posts_columns', array( $this, 'manage_woo_edd_post_columns' ) );
			add_action( 'manage_posts_custom_column', array( $this, 'manage_woo_edd_post_columns_show' ), 10, 2 );

			// Show ticket in order of woo and edd
			add_filter( 'manage_shop_order_posts_columns', array( $this, 'order_post_columns' ) );
			add_filter( 'edd_payments_table_columns', array( $this, 'order_post_columns' ) );
			add_filter( 'edd_payments_table_column', array( $this, 'order_post_columns_show' ), 10, 3 );
		}

		function order_post_columns_show( $value, $payment, $column_name ) {
			if ( Rt_HD_Module::$post_type.'_order' == $column_name ) {
				$value = $this->get_order_ticket_column_view( $payment );
			}
			return $value;
		}

		function get_order_ticket_column_view( $payment ) {
			$posts = new WP_Query( array(
				                       'post_type' => Rt_HD_Module::$post_type,
				                       'post_status' => 'any',
				                       'nopaging' => true,
				                       'meta_key' => 'rtbiz_hd_order_id',
				                       'meta_value' => $payment,
				                       'fields' => 'ids',
			                       ) );
			return '<a target="_blank" href="'.admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&order-id='.$payment ).'">'.$posts->found_posts.'</a>';
		}

		/**
		 * Add ticket column to order of EDD and Woocommerce
		 * @param $existing_columns
		 *
		 * @return mixed
		 */
		function order_post_columns( $existing_columns ) {
			$existing_columns[ Rt_HD_Module::$post_type.'_order' ] = __( 'Tickets', RT_HD_TEXT_DOMAIN );
			return $existing_columns;
		}

		/**
		 * Show ticket column in product and download post type
		 * @param $column_name
		 * @param $post_id
		 */
		function manage_woo_edd_post_columns_show( $column_name, $post_id ) {
			global $wpdb;
			switch ( $column_name ) {
				case Rt_HD_Module::$post_type.'_offering':
					// to find count and display
					$tax = $wpdb->get_var( 'SELECT taxonomy_id FROM '.$wpdb->prefix.'taxonomymeta WHERE meta_key = "'.Rt_Offerings::$term_product_id_meta_key.'" AND meta_value ='.$post_id );
					if ( ! empty( $tax ) ) {
						$terms = get_term( $tax, Rt_Offerings::$offering_slug );
						if ( ! is_wp_error( $terms ) ) {
							$posts = new WP_Query( array(
								                       'post_type' => Rt_HD_Module::$post_type,
								                       'post_status' => 'any',
								                       'nopaging' => true,
								                       Rt_Offerings::$offering_slug  => $terms->slug,
							                       ) );
							echo '<a target="_blank" href="'.admin_url( 'edit.php?post_type='.Rt_HD_Module::$post_type.'&'.Rt_Offerings::$offering_slug.'='.$terms->slug ).'">'.$posts->post_count.'</a>';
						}
					} else {
						echo '-';
					}
					break;
				case Rt_HD_Module::$post_type.'_order':
					echo $this->get_order_ticket_column_view( $post_id );
					break;
			}
		}

		/**
		 * Add ticket column to product and download post type
		 * @param $columns
		 *
		 * @return mixed
		 */
		function manage_woo_edd_post_columns( $columns ) {
			$columns[ Rt_HD_Module::$post_type.'_offering' ] = __( 'Tickets', RT_HD_TEXT_DOMAIN );
			return $columns;
		}


		/**
		 * Show Tickets in woocommerce single page view
		 * @param $order_id
		 */
		function woocommerce_view_order_show_ticket( $order_id ) {
			if ( is_object( $order_id ) ) {
				$order_id = $order_id->ID;
			}
			echo balanceTags( do_shortcode( '[rt_hd_tickets show_support_form_link=yes orderid=' . $order_id. ']' ) );
		}

		function edd_view_order_show_ticket( $payment, $edd_receipt_args ) {
			$this->woocommerce_view_order_show_ticket( $payment );
		}

		/**
		 * Add column heading on offering list page
		 * @param $columns
		 *
		 * @return mixed
		 */
		function manage_offering_column_header( $columns ) {
			$columns['default_assignee']         = __( 'Helpdesk default assignee', RT_HD_TEXT_DOMAIN );
			return $columns;
		}

		/**
		 * UI for group List View custom Columns for offerings
		 *
		 * @param      $content
		 * @param type $column
		 * @param type $term_id
		 *
		 * @return type
		 */
		function manage_offering_column_body( $content, $column, $term_id ) {
			switch ( $column ) {
				case 'default_assignee':
					$default_assignee = get_offering_meta( 'default_assignee', $term_id );
					if ( ! empty( $default_assignee ) ) {
						$user = get_user_by( 'id', $default_assignee );
						$content = esc_html( $user->display_name );
					} else {
						echo '-';
					}
					break;
			}
			return $content;
		}

		/*
		 * Save Default assignee for offering
		 * @param $term_id
		 */
		function save_offerings( $term_id ) {
			if ( isset( $_POST[ Rt_Offerings::$offering_slug  ] ) ) {
				$prev_value = Rt_Lib_Taxonomy_Metadata\get_term_meta( $term_id, Rt_Offerings::$offering_slug  . '-meta', true );
				$meta_value = (array) $_POST[ Rt_Offerings::$offering_slug ];
				Rt_Lib_Taxonomy_Metadata\update_term_meta( $term_id, Rt_Offerings::$offering_slug  . '-meta', $meta_value, $prev_value );
				if ( isset( $_POST['_wp_original_http_referer'] ) ) {
					wp_safe_redirect( $_POST['_wp_original_http_referer'] );
					exit();
				}
			}
		}

		/*
		 * To check user current page is offering page or not
		 * @param bool $page
		 *
		 * @return bool
		 */
		function is_edit_offerings( $page = false ) {
			global $pagenow;
			if ( ( ! $page || 'edit' === $page ) && 'edit-tags.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['taxonomy'] ) && Rt_Offerings::$offering_slug === $_GET['taxonomy'] ) {
				return true;
			}
			if ( ( ! $page || 'all' === $page ) && 'edit-tags.php' === $pagenow && isset( $_GET['taxonomy'] ) && Rt_Offerings::$offering_slug === $_GET['taxonomy'] && ( ! isset( $_GET['action'] ) || 'edit' !== $_GET['action'] ) ) {
				return true;
			}
			return false;
		}

		/*
		 * Add custom field for default assignee on offering page
		 * @param $tag
		 * @param string $group
		 */
		function offering_add_custom_field( $tag, $group = '' ) {
			$users         = Rt_HD_Utils::get_hd_rtcamp_user();
			if ( $this->is_edit_offerings( 'edit' ) ) {
				?>
				<h3><?php _e( 'Helpdesk Settings', RT_HD_TEXT_DOMAIN ); ?></h3>

				<table class="form-table">
					<tbody>
					<tr class="form-field">
						<th scope="row" valign="top"><label
								for="<?php echo esc_attr( Rt_Offerings::$offering_slug ); ?>[default_assignee]"><?php _e( 'Helpdesk Default Assignee', RT_HD_TEXT_DOMAIN ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( Rt_Offerings::$offering_slug ); ?>[default_assignee]" id="<?php echo esc_attr( Rt_Offerings::$offering_slug ); ?>[default_assignee]" >
								<?php
								$selected_userid = get_offering_meta( 'default_assignee' );
								if ( empty( $selected_userid ) ) {
									echo '<option disabled selected value="0"> -- select an assignee -- </option>';
								} else {
									echo '<option value="0"> -- select an assignee -- </option>';
								}
								foreach ( $users as $user ) {
									if ( $user->ID == $selected_userid ) {
										$selected = 'selected';
									} else {
										$selected = '';
									}
									echo '<option value="' . $user->ID . '" '.$selected.'>' . $user->display_name . '</option>';
								}
								?>
							</select>

							<p class="description"><?php _e( 'All new support request for this offering will be assigned to selected user.', RT_HD_TEXT_DOMAIN ); ?></p>
						</td>
					</tr>
					</tbody>
				</table> <?php
			} else { ?>

				<div class="form-field">
					<p>
						<label for="<?php echo esc_attr( Rt_Offerings::$offering_slug ); ?>[default_assignee]"><?php _e( 'Helpdesk Default Assignee', RT_HD_TEXT_DOMAIN ); ?></label>
						<select name="<?php echo esc_attr( Rt_Offerings::$offering_slug ); ?>[default_assignee]" id="<?php echo esc_attr( Rt_Offerings::$offering_slug ); ?>[default_assignee]" >
							<option disabled selected > -- select an assignee -- </option>
							<?php
							foreach ( $users as $user ) {
								echo '<option value="' . $user->ID . '">' . $user->display_name . '</option>';
							}
							?>
						</select>
					</p>
					<p class="description"><?php _e( 'All new support request for this offering will be assigned to selected user.', RT_HD_TEXT_DOMAIN ); ?></p>
				</div>
			<?php }
		}

		/*
		 *  Get list of customer Who purchase a product
		 * @return array
		 */
		function get_customers_userid() {
			// todo : update code as per the multiple plugin
			$this->check_active_plugin();
			if ( $this->isWoocommerceActive ) {
				//
				$payments = get_posts( array(
					                       'numberposts' => -1,
					                       'post_type'   => $this->order_post_type,
					                       'meta_key'    => '_billing_email',
					                       'order'       => 'ASC',
					                       'post_status' => 'wc-completed',
				                       ) );
			} else if ( $this->iseddActive ) {
				$payments = get_posts( array(
					                       'numberposts' => -1,
					                       'post_type'   => $this->order_post_type,
					                       'order'       => 'ASC',
					                       'post_status' => 'publish',
				                       ) );
			}
			$emails = array();

			if ( ! empty( $payments ) ) {
				foreach ( $payments as $payment ) {
					if ( $this->isWoocommerceActive ) {
						$emails[] = get_post_meta( $payment->ID, '_customer_user', true );
					} else if ( $this->iseddActive ) {
						$emails[] = get_post_meta( $payment->ID, '_edd_payment_user_id', true );
					}
				}
			}
			return $emails;
		}

		/*
		 * get a user purchase history
		 * @param $ticket_id
		 */
		function user_purchase_history( $ticket_id ) {
			$created_by_id = get_post_meta( $ticket_id, '_rtbiz_hd_created_by', true );
			if ( ! empty( $created_by_id ) ) {
				$this->check_active_plugin();
				if ( $this->isWoocommerceActive ) {
					$payments = get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $created_by_id,
						'post_type'   => $this->order_post_type,
						'order'       => 'ASC',
						'post_status' => 'any', // wc-completed is for completed orders
					) );
				} else if ( $this->iseddActive ) {
					$payments = get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_edd_payment_user_id',
						'meta_value'  => $created_by_id,
						'post_type'   => $this->order_post_type,
						'order'       => 'ASC',
						'post_status' => 'any', // publish is for completed orders
					) );
					$order_post_status = edd_get_payment_statuses();
				}
				if ( ! empty( $payments ) ) {
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_box_wrapper_start','<div class="rt-hd-sidebar-box">' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_header_wrapper_start','<div class="rt-hd-ticket-info">' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_heading', '<h3 class="rt-hd-ticket-info-header">' . __( 'Purchase History' ) . '</h3><div class="rthd-collapse-icon"><a class="rthd-collapse-click" href="#"><span class="dashicons dashicons-arrow-up-alt2"></span></a></div><div class="rthd-clearfix"></div>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_header_wrapper_end', '</div>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_wrapper_start', '<div class="rt-hd-ticket-sub-row">' );
					echo '<ul>';
					foreach ( $payments as $key => $payment ) {
						$link = '';
						if ( $this->iseddActive ) {
							$status = $order_post_status[ $payment->post_status ];
							$link = admin_url( "edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id={$payment->ID}" );
						} else if ( $this->isWoocommerceActive ) {
							$status = wc_get_order_status_name( $payment->post_status );
							$link = get_edit_post_link( $payment->ID );
						}
						echo '<li><a href="' . $link . '">' . sprintf( __( 'Order #%d', RT_HD_TEXT_DOMAIN ), $payment->ID ) . '</a> <div class="rthd_order_status">'. $status .'</div></li>';
					}
					echo '</ul>';
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_wrapper_end', '</div>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_box_wrapper_end', '</div>' );
				}
			}
		}

		/*
		 * Display ticket history on order page
		 */
		function order_support_history( $post ) {
			add_meta_box( 'rtbiz-helpdesk-support-info', __( 'Support History' ), array( $this, 'support_info' ), 'shop_order', 'normal' );
			add_action( 'edd_view_order_details_main_after', array( $this, 'edd_support_info' ), 700 );
		}

		/*
		 * Display ticket history on WooCommerce order page
		 */
		function support_info( $post ) {
			echo balanceTags( do_shortcode( '[rt_hd_tickets title="false" orderid=' . $post->ID . ']' ) );
		}

		/*
		 * Display ticket history on edd order page
		 */
		function edd_support_info( $order_id ) {
			?>
			<div class="postbox-container">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h3 class="hndle"><?php _e( 'Support History' ); ?></h3>
						<div class="inside">
							<?php $this->support_info( get_post( $order_id ) ); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/*
		 * add support link
		 */
		function edd_action_link_header() {
			global $redux_helpdesk_settings;
			if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
				?>
				<th class="edd_rt_hd_support"><?php _e( 'Support', 'edd' ); ?></th>
			<?php
			}
		}

		/*
		 * add support link with product id
		 */
		function edd_support_link( $payment_id, $download_id ) {
			global $redux_helpdesk_settings;
			if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
				$page = get_post( $redux_helpdesk_settings['rthd_support_page'] );
				?>
				<td class="edd_rt_hd_support"><a
						href="<?php echo "/{$page->post_name}/?product_id={$download_id}&order_id={$payment_id}&order_type=edd"; ?>"><?php _e( 'Create Ticket', RT_HD_TEXT_DOMAIN ) ?></a>
				</td>
			<?php
			}
		}

		/**
		 * Add new action link for Get Support in woocommerce order list
		 *
		 * @since 0.1
		 *
		 * @global type $redux_helpdesk_settings
		 *
		 * @param type  $actions
		 * @param type  $order
		 *
		 * @return type
		 */
		function wocommerce_actions_link( $actions, $order ) {
			global $redux_helpdesk_settings;
			if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
				$page                 = get_post( $redux_helpdesk_settings['rthd_support_page'] );
				$actions['support'] = array(
					'url'  => "/{$page->post_name}/?order_id={$order->id}&order_type=woocommerce",
					'name' => __( 'Create Ticket', RT_HD_TEXT_DOMAIN ),
				);
			}
			return $actions;
		}

		/*
		 * check which plugins are active
		 */
		function check_active_plugin() {

			$activePlugin  = rt_biz_get_offering_selection_setting();
			if ( ! empty( $activePlugin ) && is_plugin_active( 'woocommerce/woocommerce.php' ) && in_array( 'woocommerce', $activePlugin ) ) {
				$this->isWoocommerceActive = true;
				$this->activePostType = 'product';
				$this->order_post_type = 'shop_order';
			}
			if ( ! empty( $activePlugin ) && is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) && in_array( 'edd', $activePlugin ) ) {
				$this->iseddActive = true;
				$this->activePostType = 'download';
				$this->order_post_type = 'edd_payment';
			} else {
				$this->iseddActive = false;
				$this->isWoocommerceActive = false;
				$this->activePostType = false;
				$this->order_post_type = false;
			}
		}

		/*
		 * Save new support ticket for wooCommerce
		 *
		 * @since 0.1
		 *
		 */
		function save_support_form() {
			global $rtbiz_offerings, $rt_hd_import_operation;

			if ( empty( $_POST['rthd_support_form_submit'] ) ) {
				return false;
			}
			if ( empty( $_POST['post'] ) || empty( $_POST['post_description'] )|| empty( $_POST['post']['title'] ) || empty( $_POST['post']['email'][0] ) ) {
				echo '<div id="info" class="error rthd-notice">Please fill all the details.</div>';
				return false;
			}

			if ( ( $this->isWoocommerceActive || $this->iseddActive ) && ( isset( $_POST['post']['product_id'] ) && empty( $_POST['post']['product_id'] ) ) ) {
				echo '<div id="info" class="error rthd-notice">Please select a product to let us know more about your query.</div>';
				return false;
			}

			if ( rt_hd_check_email_blacklisted( $_POST['post']['email'][0] ) ) {
				echo '<div id="info" class="error rthd-notice">You have been blocked from the system.</div>';
				return false;
			}

			if ( ! is_email( $_POST['post']['email'][0] ) ) {
				echo '<div id="info" class="error rthd-notice">Please enter valid email id.</div>';
				return false;
			}
			// remove ticket creator from client email list
			$creator = $_POST['post']['email'][0];

			$data = $_POST['post'];
			$data['description'] = $_POST['post_description'];

			$allemails  = array();
			foreach ( array_filter( $data['email'] ) as $email ) {
				if ( is_email( $email ) ) {
					$allemails[] = array( 'address' => $email );
				}
			}
			$emails_array = rthd_filter_emails( $allemails );
			$subscriber = $emails_array['subscriber'];
			$allemail = $emails_array['allemail'];

			$followup_attachment = explode( ',', $_POST['rthd_support_attach_ids'] );
			$uploaded = array_filter( $followup_attachment );

			//Ticket created
			$rt_hd_tickets_id = $rt_hd_import_operation->insert_new_ticket(
				$data['title'],
				stripslashes( $data['description'] ),
				'now',
				$allemail,
				$uploaded,
				$creator,'','','',$subscriber
			);

			$_POST['post'] = null;
			$_POST['post_description'] = null;

			return $rt_hd_tickets_id;
		}

		/**
		 * View ticket on wooCommerce My account page
		 *
		 * @since 0.1
		 */
		function woo_my_tickets_my_account() {
			global $current_user;
			echo balanceTags( do_shortcode( '[rt_hd_tickets show_support_form_link=yes userid = ' . $current_user->ID . ']' ) );
		}


		/**
		 * add attachment
		 *
		 * @since 0.1
		 *
		 * @param $file_handler
		 *
		 * @return int|WP_Error
		 * @internal param $post_id
		 *
		 */
		static function insert_attachment( $file_handler ) {
			global $rt_hd_admin;
			// check to make sure its a successful upload
			if ( UPLOAD_ERR_OK !== $file_handler['error'] ) {
				__return_empty_array();
			}

			add_filter( 'upload_dir', array( $rt_hd_admin, 'custom_upload_dir' ) );//added hook for add addon specific folder for attachment
			$uploaded = wp_upload_bits( $file_handler['name'], null, file_get_contents( $file_handler['tmp_name'] ) );
			remove_filter( 'upload_dir', array( $rt_hd_admin, 'custom_upload_dir' ) );//remove hook for add addon specific folder for attachment

			$file = array();
			if ( false == $uploaded['error'] ) {
				$extn_array            = explode( '.', $file_handler['name'] );
				$extn                  = $extn_array[ count( $extn_array ) - 1 ];
				$file['file']          = $uploaded['file'];
				$file['url']           = $uploaded['url'];
				$file['filename']      = $file_handler['name'];
				$file['extn']          = $extn;
				$file['type']          = $file_handler['type'];
			}

			return $file;
		}

		/**
		 * Add product information in ticket meta data.
		 *
		 * @param $rt_hd_tickets_id
		 *
		 * @internal param int $rt_hd_ticket_id
		 */
		function rt_hd_add_ticket_offering_info_callback( $rt_hd_tickets_id ) {

			$data = $_POST['post'];

			// adult filter
			if ( rthd_get_redux_adult_filter() ) {
				$adultval = '';
				if ( isset( $data['adult_ticket'] ) ) {
					$adultval = 'yes';
				} else {
					$adultval = 'no';
				}
				rthd_save_adult_ticket_meta( $rt_hd_tickets_id, $adultval );
			}

			if ( isset( $data['product_id'] ) ) {
				$term = get_term_by( 'id', $data['product_id'], Rt_Offerings::$offering_slug );
				if ( $term ) {
					wp_set_post_terms( $rt_hd_tickets_id, array( $term->term_id ), Rt_Offerings::$offering_slug );
				}
			}

			if ( isset( $data['order_id'] ) ) {
				update_post_meta( $rt_hd_tickets_id, 'rtbiz_hd_order_id', esc_attr( $data['order_id'] ) );
			}

			if ( isset( $data['order_id'] ) && isset( $data['order_type'] ) ) {
				//Store Order ID
				update_post_meta( $rt_hd_tickets_id, 'rtbiz_hd_order_type', esc_attr( $data['order_type'] ) );

				$link = '';
				if ( 'woocommerce' === $data['order_type'] ) {
					$link = add_query_arg( 'post', $_REQUEST['order_id'], admin_url( 'post.php?action=edit' ) );
				} else if ( 'edd' === $data['order_type'] ) {
					$link = add_query_arg( 'id', $_REQUEST['order_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) );
				}
				update_post_meta( $rt_hd_tickets_id, 'rtbiz_hd_order_link', esc_url( $link ) );
			}
		}
	}
}
