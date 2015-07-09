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


if ( ! class_exists( 'Rtbiz_HD_Product_Support' ) ) {

	/**
	 * Class Rtbiz_HD_Product_Support
	 * Provide wooCommerce & EDD integration with HelpDesk for product support
	 *
	 */
	class Rtbiz_HD_Product_Support {

		/**
		 * @var Flag for WooCommerce active or not
		 */
		var $isWoocommerceActive = false;

		/**
		 * @var Flag for EDD active or not
		 */
		var $iseddActive = false;

		/**
		 * construct
		 *
		 * @since 0.1
		 */
		function __construct() {
			// filter for add new action link on My Account page
			Rtbiz_HD::$loader->add_filter( 'woocommerce_my_account_my_orders_actions', $this, 'wocommerce_actions_link', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'edd_download_history_header_end', $this, 'edd_action_link_header' );
			Rtbiz_HD::$loader->add_action( 'edd_purchase_history_header_after', $this, 'edd_action_link_header' );

			Rtbiz_HD::$loader->add_action( 'edd_download_history_row_end', $this, 'edd_support_link', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'edd_purchase_history_row_end', $this, 'edd_support_link', 10, 2 );

			// Add product information in ticket meta.
			Rtbiz_HD::$loader->add_action( 'rtbiz_hd_add_ticket_product_info', $this, 'add_ticket_product_info' );

			// my account and download history ticket list view
			Rtbiz_HD::$loader->add_action( 'woocommerce_after_my_account', $this, 'woo_my_tickets_my_account' );
			//			Rtbiz_HD::$loader->add_action( 'edd_after_download_history', $this, 'woo_my_tickets_my_account' );

			// Metaboxes for Orders
			// WP 3.0+
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes', $this, 'order_support_history' );
			// backwards compatible
			Rtbiz_HD::$loader->add_action( 'admin_init', $this, 'order_support_history', 1 );

			// User Purchase History on Ticket Page
			Rtbiz_HD::$loader->add_action( 'rtbiz_hd_user_purchase_history', $this, 'user_purchase_history' );

			Rtbiz_HD::$loader->add_action( Rt_Products::$product_slug . '_add_form_fields', $this, 'product_add_custom_field', 10, 2 );
			Rtbiz_HD::$loader->add_action( Rt_Products::$product_slug. '_edit_form', $this, 'product_add_custom_field', 10, 2 );

			Rtbiz_HD::$loader->add_action( 'create_term', $this, 'save_products', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'edit_term', $this, 'save_products', 10, 2 );

			Rtbiz_HD::$loader->add_action( 'rt_product_column_content', $this, 'manage_product_column_body', 10, 3 );
			Rtbiz_HD::$loader->add_filter( 'rt_product_columns', $this, 'manage_product_column_header' );

			// Show tickets in woocommerce order page
			Rtbiz_HD::$loader->add_action( 'woocommerce_view_order', $this, 'woocommerce_view_order_show_ticket' );
			//			Rtbiz_HD::$loader->add_filter( 'edd_payment_receipt_after_table', $this, 'edd_view_order_show_ticket', 10, 3 );

			// Show ticket column in product and download post type
			Rtbiz_HD::$loader->add_filter( 'edd_download_columns', $this, 'manage_woo_edd_post_columns' );
			Rtbiz_HD::$loader->add_action( 'manage_download_posts_custom_column', $this, 'manage_woo_edd_post_columns_show', 10, 2 );

			Rtbiz_HD::$loader->add_filter( 'manage_product_posts_columns', $this, 'manage_woo_edd_post_columns' );
			Rtbiz_HD::$loader->add_action( 'manage_product_posts_custom_column', $this, 'manage_woo_edd_post_columns_show', 10, 2 );

			// Show ticket in order of woo and edd
			Rtbiz_HD::$loader->add_filter( 'manage_shop_order_posts_columns', $this, 'order_post_columns', 20 );
			Rtbiz_HD::$loader->add_action( 'manage_shop_order_posts_custom_column', $this, 'wc_order_post_columns_show', 20, 2 );

			Rtbiz_HD::$loader->add_filter( 'edd_payments_table_columns', $this, 'order_post_columns' );
			Rtbiz_HD::$loader->add_filter( 'edd_payments_table_column', $this, 'edd_order_post_columns_show', 10, 3 );

			// edd Customer column
			Rtbiz_HD::$loader->add_filter( 'edd_report_customer_columns', $this, 'edd_customer_columns' );
			Rtbiz_HD::$loader->add_filter( 'edd_report_column_'. Rtbiz_HD_Module::$post_type , $this, 'edd_customer_ticket_column', 10, 2 );
		}

		function edd_customer_ticket_column( $value, $item ) {
			/* @var $customer EDD_Customer*/
			$customer    = new EDD_Customer( $item );
			$contact_id     = rtbiz_hd_get_contact_id_by_user_id( $customer->user_id, true );
			if ( empty( $contact_id ) ) {
				return '0';
			}
			// todo : in case of empty contact create contact and map it with wp_user do it now.
			$posts = new WP_Query(array(
				                      'post_type'      => Rtbiz_HD_Module::$post_type,
				                      'post_status'    => 'any',
				                      'nopaging'       => true,
				                      'meta_key'       => '_rtbiz_hd_created_by',
				                      'meta_value'     => $contact_id,
				                      'fields'         => 'ids',
			                      ));
			return '<a href="'.admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&created_by='.$contact_id ).'">'.$posts->found_posts.'</a>';
		}

		function edd_customer_columns( $columns ) {
			$columns[ Rtbiz_HD_Module::$post_type ] = __( 'Tickets', RTBIZ_HD_TEXT_DOMAIN );
			return $columns;
		}

		function wc_order_post_columns_show( $column_name, $payment ) {
			if ( Rtbiz_HD_Module::$post_type.'_order' == $column_name ) {
				echo $this->get_order_ticket_column_view( $payment );
			}
		}

		function edd_order_post_columns_show( $value, $payment, $column_name ) {
			if ( Rtbiz_HD_Module::$post_type.'_order' == $column_name ) {
				$value = $this->get_order_ticket_column_view( $payment );
			}
			return $value;
		}

		function get_order_ticket_column_view( $payment ) {
			$posts = new WP_Query( array(
				                       'post_type'      => Rtbiz_HD_Module::$post_type,
				                       'post_status'    => 'any',
				                       'nopaging'       => true,
				                       'meta_key'       => '_rtbiz_hd_order_id',
				                       'meta_value'     => $payment,
				                       'fields'         => 'ids',
			                       ) );
			return '<a target="_blank" href="'.admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&order-id='.$payment ).'">'.$posts->found_posts.'</a>';
		}

		/**
		 * Add ticket column to order of EDD and Woocommerce
		 * @param $existing_columns
		 *
		 * @return mixed
		 */
		function order_post_columns( $existing_columns ) {
			$existing_columns[ Rtbiz_HD_Module::$post_type.'_order' ] = __( 'Tickets', RTBIZ_HD_TEXT_DOMAIN );
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
				case Rtbiz_HD_Module::$post_type.'_product':
					// to find count and display
					$tax = $wpdb->get_var( 'SELECT taxonomy_id FROM '.$wpdb->prefix.'taxonomymeta WHERE meta_key = "'.Rt_Products::$term_product_id_meta_key.'" AND meta_value ='.$post_id );
					if ( ! empty( $tax ) ) {
						$terms = get_term( $tax, Rt_Products::$product_slug );
						if ( ! is_wp_error( $terms ) && !empty( $terms ) ) {
							$posts = new WP_Query( array(
								                       'post_type'                      => Rtbiz_HD_Module::$post_type,
								                       'post_status'                    => 'any',
								                       'nopaging'                       => true,
								                       Rt_Products::$product_slug     => $terms->slug,
								                       'fields'                         => 'ids',
							                       ) );
							echo '<a target="_blank" href="'.admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&'.Rt_Products::$product_slug.'='.$terms->slug ).'">'.$posts->found_posts.'</a>';
						}
					} else {
						echo '-';
					}
					break;
				case Rtbiz_HD_Module::$post_type.'_order':
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
			$columns[ Rtbiz_HD_Module::$post_type.'_product' ] = __( 'Tickets', RTBIZ_HD_TEXT_DOMAIN );
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
			echo balanceTags( do_shortcode( '[rtbiz_hd_tickets show_support_form_link=yes orderid=' . $order_id. ']' ) );
		}

		function edd_view_order_show_ticket( $payment, $edd_receipt_args ) {
			$this->woocommerce_view_order_show_ticket( $payment );
		}

		/**
		 * Add column heading on product list page
		 * @param $columns
		 *
		 * @return mixed
		 */
		function manage_product_column_header( $columns ) {
			$columns['default_assignee']         = __( 'Helpdesk default assignee', RTBIZ_HD_TEXT_DOMAIN );
			return $columns;
		}

		/**
		 * UI for group List View custom Columns for products
		 *
		 * @param      $content
		 * @param type $column
		 * @param type $term_id
		 *
		 * @return type
		 */
		function manage_product_column_body( $content, $column, $term_id ) {
			switch ( $column ) {
				case 'default_assignee':
					$default_assignee = rtbiz_hd_get_product_meta( 'default_assignee', $term_id );
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
		 * Save Default assignee for product
		 * @param $term_id
		 */
		function save_products( $term_id ) {
			if ( isset( $_POST[ Rt_Products::$product_slug  ] ) ) {
				$prev_value = Rt_Lib_Taxonomy_Metadata\get_term_meta( $term_id, '_'.Rt_Products::$product_slug  . '_meta', true );
				$meta_value = (array) $_POST[ Rt_Products::$product_slug ];
				Rt_Lib_Taxonomy_Metadata\update_term_meta( $term_id, '_' . Rt_Products::$product_slug  . '_meta', $meta_value, $prev_value );
				if ( isset( $_POST['_wp_original_http_referer'] ) ) {
					wp_safe_redirect( $_POST['_wp_original_http_referer'] );
					exit();
				}
			}
		}

		/*
		 * To check user current page is product page or not
		 * @param bool $page
		 *
		 * @return bool
		 */
		function is_edit_products( $page = false ) {
			global $pagenow;
			if ( ( ! $page || 'edit' === $page ) && 'edit-tags.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['taxonomy'] ) && Rt_Products::$product_slug === $_GET['taxonomy'] ) {
				return true;
			}
			if ( ( ! $page || 'all' === $page ) && 'edit-tags.php' === $pagenow && isset( $_GET['taxonomy'] ) && Rt_Products::$product_slug === $_GET['taxonomy'] && ( ! isset( $_GET['action'] ) || 'edit' !== $_GET['action'] ) ) {
				return true;
			}
			return false;
		}

		/*
		 * Add custom field for default assignee on product page
		 * @param $tag
		 * @param string $group
		 */
		function product_add_custom_field( $tag, $group = '' ) {
			$users         = Rtbiz_HD_Utils::get_hd_rtcamp_user();
			if ( $this->is_edit_products( 'edit' ) ) {
				?>
				<h3><?php _e( 'Helpdesk Settings', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>

				<table class="form-table">
					<tbody>
					<tr class="form-field">
						<th scope="row" valign="top"><label
								for="<?php echo esc_attr( Rt_Products::$product_slug ); ?>[default_assignee]"><?php _e( 'Helpdesk Default Assignee', RTBIZ_HD_TEXT_DOMAIN ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( Rt_Products::$product_slug ); ?>[default_assignee]" id="<?php echo esc_attr( Rt_Products::$product_slug ); ?>[default_assignee]" >
								<?php
								$selected_userid = rtbiz_hd_get_product_meta( 'default_assignee' );
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

							<p class="description"><?php _e( 'All new support request for this product will be assigned to selected user.', RTBIZ_HD_TEXT_DOMAIN ); ?></p>
						</td>
					</tr>
					</tbody>
				</table> <?php
			} else { ?>

				<div class="form-field">
					<p>
						<label for="<?php echo esc_attr( Rt_Products::$product_slug ); ?>[default_assignee]"><?php _e( 'Helpdesk Default Assignee', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
						<select name="<?php echo esc_attr( Rt_Products::$product_slug ); ?>[default_assignee]" id="<?php echo esc_attr( Rt_Products::$product_slug ); ?>[default_assignee]" >
							<option disabled selected > -- select an assignee -- </option>
							<?php
							foreach ( $users as $user ) {
								echo '<option value="' . $user->ID . '">' . $user->display_name . '</option>';
							}
							?>
						</select>
					</p>
					<p class="description"><?php _e( 'All new support request for this product will be assigned to selected user.', RTBIZ_HD_TEXT_DOMAIN ); ?></p>
				</div>
			<?php }
		}

		/*
		 *  Get list of customer Who purchase a product
		 * @return array
		 */
		function get_customers_userid() {
			$this->check_active_plugin();
			$woo_payment = $edd_payments = array();
			if ( $this->isWoocommerceActive ) {
				//
				$woo_payment = get_posts( array(
					                       'numberposts' => -1,
					                       'post_type'   => 'shop_order',
					                       'meta_key'    => '_billing_email',
					                       'order'       => 'ASC',
					                       'post_status' => 'wc-completed',
				                       ) );
			}
			if ( $this->iseddActive ) {
				$edd_payments = get_posts( array(
					                       'numberposts' => -1,
					                       'post_type'   => 'edd_payment',
					                       'meta_key'    => '_edd_payment_mode',
					                       'meta_value'   => 'test',
					                       'meta_compare' => '!=',
					                       'order'       => 'ASC',
					                       'post_status' => 'publish',
				                       ) );
			}
			$payments = array_merge( $woo_payment, $edd_payments );
			$ids = array();

			if ( ! empty( $payments ) ) {
				foreach ( $payments as $payment ) {
				    if ( $payment->post_type == 'shop_order' ) {
					    $ids[] = get_post_meta( $payment->ID, '_customer_user', true );
				    }
					else if ( $payment->post_type == 'edd_payment' ) {
						$ids[] = get_post_meta( $payment->ID, '_edd_payment_user_id', true );
					}
				}
			}
			return $ids;
		}

		/*
		 * get a user purchase history
		 * @param $ticket_id
		 */
		function user_purchase_history( $ticket_id ) {
			$created_by_id = rtbiz_hd_get_ticket_creator( $ticket_id );
			$created_by_id = rtbiz_hd_get_user_id_by_contact_id( $created_by_id );
			if ( ! empty( $created_by_id ) ) {
				$this->check_active_plugin();
				$woo_payment = array();
				$edd_payments = array();
				if ( $this->isWoocommerceActive ) {
					$woo_payment = get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $created_by_id,
						'post_type'   => 'shop_order',
						'order'       => 'ASC',
						'post_status' => 'any', // wc-completed is for completed orders
					) );
				} if ( $this->iseddActive ) {
					$edd_payments = get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_edd_payment_user_id',
						'meta_value'  => $created_by_id,
						'post_type'   => 'edd_payment',
						'order'       => 'ASC',
						'post_status' => 'any', // publish is for completed orders
					) );
					$order_post_status = edd_get_payment_statuses();
				}
				$payments = array_merge( $woo_payment, $edd_payments );
				if ( ! empty( $payments ) ) {
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_box_wrapper_start','<div class="rt-hd-sidebar-box">' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_header_wrapper_start','<div class="rt-hd-ticket-info">' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_heading', '<h3 class="rt-hd-ticket-info-header">' . __( 'Purchase History' ) . '</h3><div class="rthd-collapse-icon"><a class="rthd-collapse-click" href="#"><span class="dashicons dashicons-arrow-up-alt2"></span></a></div><div class="rthd-clearfix"></div>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_header_wrapper_end', '</div>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_wrapper_start', '<div class="rt-hd-ticket-sub-row">' );
					echo '<ul>';
					foreach ( $payments as $key => $payment ) {
						$link = '';
						if ( $payment->post_type == 'edd_payment' ) {
							$status = $order_post_status[ $payment->post_status ];
							$link = admin_url( "edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id={$payment->ID}" );
						} else if ( $payment->post_type == 'shop_order' ) {
							$status = wc_get_order_status_name( $payment->post_status );
							$link = get_edit_post_link( $payment->ID );
						}
						echo '<li><a href="' . $link . '">' . sprintf( __( 'Order #%d', RTBIZ_HD_TEXT_DOMAIN ), $payment->ID ) . '</a> <div class="rthd_order_status">'. $status .'</div></li>';
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
			echo balanceTags( do_shortcode( '[rtbiz_hd_tickets title="false" orderid=' . $post->ID . ']' ) );
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
			if ( is_array( $download_id ) ) {
				$download_id = $download_id['downloads'][0]['id'];
			}
			global $redux_helpdesk_settings;
			if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
				$page = get_post( $redux_helpdesk_settings['rthd_support_page'] );
				$link = get_permalink( $page->ID );
				$link = add_query_arg( array( 'product_id' => $download_id, 'order_id' => $payment_id, 'order_type' => 'edd' ), $link );
				?>
				<td class="edd_rt_hd_support"><a
						href="<?php echo $link; ?>"><?php _e( 'Create Ticket', RTBIZ_HD_TEXT_DOMAIN ) ?></a>
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
				$link                 = get_permalink( $page->ID );
				$link = add_query_arg( array( 'order_id' => $order->id, 'order_type' => 'woocommerce' ), $link );
				$actions['support'] = array(
					'url'  => $link,
					'name' => __( 'Create Ticket', RTBIZ_HD_TEXT_DOMAIN ),
				);
			}
			return $actions;
		}

		/*
		 * check which plugins are active
		 */
		function check_active_plugin() {

			$activePlugin  = rtbiz_get_product_selection_setting();
			if ( ! empty( $activePlugin ) && is_plugin_active( 'woocommerce/woocommerce.php' ) && in_array( 'woocommerce', $activePlugin ) ) {
				$this->isWoocommerceActive = true;
				// post type 'product';
			}
			if ( ! empty( $activePlugin ) && is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) && in_array( 'edd', $activePlugin ) ) {
				$this->iseddActive = true;
				// post type 'download';
			} else {
				$this->iseddActive = false;
				$this->isWoocommerceActive = false;
			}
		}

		/*
		 * Save new support ticket for wooCommerce
		 *
		 * @since 0.1
		 *
		 */
		function save_support_form() {
			global $rtbiz_products, $rtbiz_hd_import_operation;

			if ( empty( $_POST['rthd_support_form_submit'] ) ) {
				return false;
			}
			$_POST['post']['title'] = trim( $_POST['post']['title'] );
			$_POST['post']['description'] = trim( $_POST['post']['description'] );

			if ( empty( $_POST['post'] ) || empty( $_POST['post']['description'] )|| empty( $_POST['post']['title'] ) || empty( $_POST['post']['email'][0] ) ) {
				echo '<div id="info" class="error rthd-notice">Please fill all the details.</div>';
				return false;
			}

			if ( ( $this->isWoocommerceActive || $this->iseddActive ) && ( isset( $_POST['post']['product_id'] ) && empty( $_POST['post']['product_id'] ) ) ) {
				echo '<div id="info" class="error rthd-notice">Please select a product to let us know more about your query.</div>';
				return false;
			}

			if ( rtbiz_hd_check_email_blacklisted( $_POST['post']['email'][0] ) ) {
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
			//$data['description'] = $_POST['post_description'];

			$allemails  = array();
			foreach ( array_filter( $data['email'] ) as $email ) {
				if ( is_email( $email ) ) {
					$allemails[] = array( 'address' => $email );
				}
			}
			$emails_array = rtbiz_hd_filter_emails( $allemails );
			$subscriber = $emails_array['subscriber'];
			$allemail = $emails_array['allemail'];

			$followup_attachment = explode( ',', $_POST['rthd_support_attach_ids'] );
			$uploaded = array_filter( $followup_attachment );

			$post_content['markdown'] = $data['description'];
			$post_content['html'] = wp_kses_post( stripslashes( $data['description_html'] ) );

			//Ticket created
			$rtbiz_hd_tickets_id = $rtbiz_hd_import_operation->insert_new_ticket(
				$data['title'],
				$post_content,
				'now',
				$allemail,
				$uploaded,
				$creator,'','','',$subscriber
			);

			$_POST['post'] = null;
			$_POST['post_description'] = null;

			return $rtbiz_hd_tickets_id;
		}

		/**
		 * View ticket on wooCommerce My account page
		 *
		 * @since 0.1
		 */
		function woo_my_tickets_my_account() {
			global $current_user;
			echo balanceTags( do_shortcode( '[rtbiz_hd_tickets show_support_form_link=yes userid = ' . $current_user->ID . ']' ) );
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
			global $rtbiz_hd_admin;
			// check to make sure its a successful upload
			if ( UPLOAD_ERR_OK !== $file_handler['error'] ) {
				__return_empty_array();
			}

			add_filter( 'upload_dir', array( $rtbiz_hd_admin, 'custom_upload_dir' ) );//added hook for add addon specific folder for attachment
			$uploaded = wp_upload_bits( $file_handler['name'], null, file_get_contents( $file_handler['tmp_name'] ) );
			remove_filter( 'upload_dir', array( $rtbiz_hd_admin, 'custom_upload_dir' ) );//remove hook for add addon specific folder for attachment

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
		 * @param $rtbiz_hd_tickets_id
		 *
		 * @internal param int $rtbiz_hd_ticket_id
		 */
		function add_ticket_product_info( $rtbiz_hd_tickets_id ) {
			if ( ! isset( $_POST['post'] ) ) {
				return ;
			}
			$data = $_POST['post'];

			// adult filter
			if ( rtbiz_hd_get_redux_adult_filter() ) {
				$adultval = '';
				if ( isset( $data['adult_ticket'] ) ) {
					$adultval = 'yes';
				} else {
					$adultval = 'no';
				}
				rtbiz_hd_save_adult_ticket_meta( $rtbiz_hd_tickets_id, $adultval );
			}

			if ( isset( $data['product_id'] ) ) {
				$term = get_term_by( 'id', $data['product_id'], Rt_Products::$product_slug );
				if ( $term ) {
					/* Product assignee | override defult assignee  */
					$product_meta = Rt_Lib_Taxonomy_Metadata\get_term_meta( $data['product_id'], '_'.Rt_Products::$product_slug  . '_meta', true );
					if ( ! empty( $product_meta ) && ! empty( $product_meta['default_assignee'] ) ){
						wp_update_post( array( 'ID' => $rtbiz_hd_tickets_id, 'post_author' => $product_meta['default_assignee'] ) );
					}
					/* Product assignee | override defult assignee end */
					wp_set_post_terms( $rtbiz_hd_tickets_id, array( $term->term_id ), Rt_Products::$product_slug );
				}
			}

			if ( isset( $data['order_id'] ) ) {
				update_post_meta( $rtbiz_hd_tickets_id, '_rtbiz_hd_order_id', esc_attr( $data['order_id'] ) );
			}

			if ( isset( $data['order_id'] ) && isset( $data['order_type'] ) ) {
				//Store Order ID
				update_post_meta( $rtbiz_hd_tickets_id, '_rtbiz_hd_order_type', esc_attr( $data['order_type'] ) );

				$link = '';
				if ( 'woocommerce' === $data['order_type'] ) {
					$link = add_query_arg( 'post', $_REQUEST['order_id'], admin_url( 'post.php?action=edit' ) );
				} else if ( 'edd' === $data['order_type'] ) {
					$link = add_query_arg( 'id', $_REQUEST['order_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) );
				}
				update_post_meta( $rtbiz_hd_tickets_id, '_rtbiz_hd_order_link', esc_url( $link ) );
			}
		}
	}
}
