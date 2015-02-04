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
		 * construct
		 *
		 * @since 0.1
		 */
		function __construct() {
			$this->hooks();
		}

		var $isWoocommerceActive, $iseddActive, $activePostType, $order_post_type;


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

			// shortcode for get support form
			add_shortcode( 'rt_hd_support_form', array( $this, 'rt_hd_support_form_callback' ) );
			add_shortcode( 'rt_hd_tickets', array( $this, 'rt_hd_tickets_callback' ) );

			add_action( 'woocommerce_after_my_account', array( $this, 'woo_my_tickets_my_account' ) );

			// Metaboxes for Orders
			// WP 3.0+
			add_action( 'add_meta_boxes', array( $this, 'order_support_history' ) );
			// backwards compatible
			add_action( 'admin_init', array( $this, 'order_support_history' ), 1 );

			// User Purchase History on Ticket Page
			add_action( 'rtbiz_hd_user_purchase_history', array( $this, 'user_purchase_history' ) );
		}

		function get_emails_of_customer(){
			$this->check_active_plugin();
			if ( $this->isWoocommerceActive ) {
				//
				$payments = get_posts( array(
					                       'numberposts' => -1,
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

			if ( ! empty( $payments ) ){
				foreach ( $payments as $payment ){
					if ( $this->isWoocommerceActive ) {
						$emails[] =get_post_meta( $payment->ID, '_billing_email', true );
					}
					else if( $this->iseddActive ){
						$emails[] =get_post_meta( $payment->ID, '_edd_payment_user_email', true );
					}
				}
			}
			return $emails;
		}

		function user_purchase_history( $ticket_id ) {
			$created_by_id = get_post_meta( $ticket_id, '_rtbiz_hd_created_by', true );
			if ( !empty( $created_by_id ) ) {
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
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_header_wrapper_start', '<div class="rt-hd-ticket-info">' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_heading', '<h2 class="rt-hd-ticket-info-header">' . __( 'Purchase History' ) . '</h2>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_header_wrapper_end', '</div>' );
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_wrapper_start', '<div class="rt-hd-ticket-info">' );
					echo '<ul>';
					foreach ($payments as $key => $payment ) {
						$link = '';
						if( $this->iseddActive ){
							$status = $order_post_status[$payment->post_status];
							$link =admin_url( "edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id={$payment->ID}" ) ;
						} else if( $this->isWoocommerceActive ) {
							$status = wc_get_order_status_name($payment->post_status);
//							$status = $status." " .sprintf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $payment->post_status ), wc_get_order_status_name( $payment->post_status ), wc_get_order_status_name( $payment->post_status ) );
							$link = get_edit_post_link( $payment->ID );
						}
						echo '<li><a href="' . $link . '">' . sprintf( __( 'Order #%d', RT_HD_TEXT_DOMAIN ), $payment->ID ) . '</a> <div class="rthd_order_status">'. $status .'</div></li>';
					}
					echo '</ul>';
					echo apply_filters( 'rtbiz_hd_ticket_purchase_history_wrapper_end', '</div>' );
				}
			}
		}

		function order_support_history( $post ) {
			add_meta_box( 'rtbiz-helpdesk-support-info', __( 'Support History' ), array( $this, 'support_info' ), 'shop_order', 'side' );
			add_action( 'edd_view_order_details_main_after', array( $this, 'edd_support_info' ), 700 );
		}

		function support_info( $post ) {
			echo balanceTags( do_shortcode( '[rt_hd_tickets order=' . $post->ID . ']' ) );
		}

		function edd_support_info( $order_id ) {
			?>
			<div class="postbox-container">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h3 class="hndle"><?php _e( 'Support History' ); ?></h3>
						<div class="inside" style="margin: 0; padding: 0;">
							<?php $this->support_info( get_post( $order_id ) ); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		function edd_action_link_header() {
			global $redux_helpdesk_settings;
			if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
				?>
				<th class="edd_rt_hd_support"><?php _e( 'Support', 'edd' ); ?></th>
			<?php
			}
		}

		function edd_support_link( $payment_id, $download_id ) {
			global $redux_helpdesk_settings;
			if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
				$page = get_post( $redux_helpdesk_settings[ 'rthd_support_page' ] );
				?>
				<td class="edd_rt_hd_support"><a
						href="<?php echo "/{$page->post_name}/?product_id={$download_id}&order_id={$payment_id}&order_type=edd"; ?>"><?php _e( 'Get Support', RT_HD_TEXT_DOMAIN ) ?></a>
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
				$page                 = get_post( $redux_helpdesk_settings[ 'rthd_support_page' ] );
				$actions[ 'support' ] = array(
					'url'  => "/{$page->post_name}/?order_id={$order->id}&order_type=woocommerce",
					'name' => __( 'Get Support', RT_HD_TEXT_DOMAIN )
				);
			}
			return $actions;
		}


		function check_active_plugin(){
			$settings = biz_get_redux_settings();

			$activePlugin = $settings['offering_plugin'];
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && 'woocommerce' === $activePlugin ) {
				$this->isWoocommerceActive = true;
				$this->iseddActive = false;
				$this->activePostType = 'product';
				$this->order_post_type = 'shop_order';
			} else if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) && 'edd' === $activePlugin ) {
				$this->iseddActive = true;
				$this->isWoocommerceActive  = false;
				$this->activePostType = 'download';
				$this->order_post_type = 'edd_payment';
			} else {
				$this->iseddActive = false;
				$this->isWoocommerceActive = false;
				$this->activePostType = false;
				$this->order_post_type = false;
			}
		}


		/**
		 * Short code callback for Display Support Form
		 *
		 * @since 0.1
		 *
		 * [rt_hd_support_form]
		 */
		function rt_hd_support_form_callback( $attr ) {
			ob_start();
			$this->check_active_plugin();
			wp_enqueue_style( 'support-form-style', RT_HD_URL . 'app/assets/css/support_form_front.css', false, RT_HD_VERSION, 'all' );
			$offering_option = '';
			$order_email    = '';

			$post_id = $this->save_support_form();
			if ( ! empty( $post_id ) && is_int( $post_id ) ) { ?>
				<div id="info" class="success">Your support request has been submitted. We will get back to you for your query soon.</div>
			<?php }

			global $rtbiz_offerings;
			$terms = array();
			if ( isset( $rtbiz_offerings ) ) {
				$terms = get_terms( Rt_Offerings::$offering_slug, array( 'hide_empty' => 0 ) );
			}
			$offering_exists = false;
			$wrong_user_flag = false;
			foreach ( $terms as $tm ) {
				$term_offering_id = '';
				$loggedin_id = get_current_user_id();
				if ( isset( $_REQUEST['order_id'] ) && $this->order_post_type == get_post_type( $_REQUEST['order_id'] ) ) {
					if ( $this->isWoocommerceActive ) {
						$order = new WC_Order( $_REQUEST['order_id'] );
						if ( $loggedin_id = $order->get_user_id() ) {
							if ( ! empty( $order ) ) {
								$items            = $order->get_items();
								$product_ids      = wp_list_pluck( $items, 'product_id' );
								$term_offering_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, Rt_Offerings::$term_meta_key, true );
								if ( ! in_array( $term_offering_id, $product_ids ) ) {
									continue;
								}
							}
						} else {
							$wrong_user_flag = true;
						}
					} else if ( $this->iseddActive ) {
						$payment = get_post( $_REQUEST['order_id'] );
						if ( $loggedin_id == $payment->post_author ) {
							if ( ! empty( $payment ) ) {
								$items            = edd_get_payment_meta_downloads( $payment->ID );
								$product_ids      = wp_list_pluck( $items, 'id' );
								$term_offering_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, Rt_Offerings::$term_meta_key, true );
								if ( ! in_array( $term_offering_id, $product_ids ) ) {
									continue;
								}
							}
						} else {
							$wrong_user_flag = true;
						}
					}
				}
				$offering_option .= '<option value="' . $tm->term_id . '" ' . ( ( ! empty( $_REQUEST['product_id'] ) && $term_offering_id == $_REQUEST['product_id'] ) ? 'selected="selected"' : '' ) . '> '.$tm->name.'</option>';
				$offering_exists = true;
			}

			if ( $wrong_user_flag ){
				echo '<span> You have not placed this order, Please login from account that placed this order. </span>';
			}
			else {
				rthd_get_template( 'support-form.php', array( 'product_exists' => $offering_exists, 'product_option' => $offering_option ) );
			}
			return apply_filters( 'rt_hd_support_form_shorcode', ob_get_clean(), $attr );
		}

		/**
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

			if ( empty( $_POST['post'] ) || empty( $_POST['post']['title'] ) || empty( $_POST['post']['email'] ) ) {
				echo '<div id="info" class="error">Please fill all the details.</div>';
				return false;
			}

			if ( ( $this->isWoocommerceActive || $this->isWoocommerceActive ) && empty( $_POST['post']['product_id'] ) ) {
				echo '<div id="info" class="error">Please select a product to let us know more about your query.</div>';
				return false;
			}

			$data = $_POST['post'];
			$offeringstr = $data['title'];

			$uploaded = array();

			// Created attachment
			if ( $_FILES ) {
				$attachment = $_FILES['attachment'];
				foreach ( $attachment['name'] as $key => $value ) {
					if ( $attachment['name'][ $key ] ) {
						$file = array(
							'name'     => $attachment['name'][ $key ],
							'type'     => $attachment['type'][ $key ],
							'tmp_name' => $attachment['tmp_name'][ $key ],
							'error'    => $attachment['error'][ $key ],
							'size'     => $attachment['size'][ $key ],
						);
						$uploaded[] = self::insert_attachment( $file );
					}
				}
			}

			$uploaded = array_filter( $uploaded );

			//Ticket created
			$rt_hd_tickets_id = $rt_hd_import_operation->insert_new_ticket(
				$offeringstr,
				stripslashes($data['description']),
				'now',
				array( array( 'address' => $data['email'], 'name' => '' ) ),
				$uploaded,
				$data['email']
			);

			if ( isset( $data['product_id'] ) ) {
				$term = get_term_by( 'id', $data['product_id'], Rt_Offerings::$offering_slug );
				if ( $term ) {
					wp_set_post_terms( $rt_hd_tickets_id, array( $term->term_id ), Rt_Offerings::$offering_slug );
				}
			}

			if ( isset( $data['order_id'] ) && $data['order_type'] ) {
				//Store Order ID
				update_post_meta( $rt_hd_tickets_id, 'rtbiz_hd_order_id', esc_attr( $data['order_id'] ) );
				update_post_meta( $rt_hd_tickets_id, 'rtbiz_hd_order_type', esc_attr( $data['order_type'] ) );

				$link = '';
				if ( 'woocommerce' === $data['order_type'] ) {
					$link = add_query_arg( 'post', $_REQUEST['order_id'], admin_url( 'post.php?action=edit' ) );
				} else if ( 'edd' === $data['order_type'] ) {
					$link = add_query_arg( 'id', $_REQUEST['order_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) );
				}
				update_post_meta( $rt_hd_tickets_id, 'rtbiz_hd_order_link', $link );
			}

			return $rt_hd_tickets_id;
		}

		/**
		 * View ticket on wooCommerce My account page
		 *
		 * @since 0.1
		 */
		function woo_my_tickets_my_account() {

			global $current_user;

			echo balanceTags( do_shortcode( '[rt_hd_tickets email=' . $current_user->user_email . ']' ) );
		}


		/**
		 * add attachment
		 *
		 * @since 0.1
		 *
		 * @param $file_handler
		 * @param $post_id
		 *
		 * @return int| WP_Error
		 */
		static function insert_attachment( $file_handler ) {
			global $rt_hd_admin;
			// check to make sure its a successful upload
			if ( $_FILES[ $file_handler ]['error'] !== UPLOAD_ERR_OK ) {
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
		 * wooCommerce View list all ticket
		 * Default All ticket | Ticket by UserID | Ticket by User Email
		 *
		 * @since 0.1
		 *
		 * @param $atts
		 */
		function rt_hd_tickets_callback( $atts ) {
			global $rt_hd_module;
			$labels        = $rt_hd_module->labels;
			$arg_shortcode = shortcode_atts(
				array(
					'email' => '',
					'user'  => '',
					'order' => '',
					'show_support_form_link' => 'no',
				), $atts );

			$args    = array(
				'post_type'   => Rt_HD_Module::$post_type,
				'post_status' => 'any',
				'nopaging'    => true,
			);
			$tickets = array();
			if ( ! empty( $arg_shortcode['email'] ) || ! empty( $arg_shortcode['user'] ) ) {

				if ( ! empty( $arg_shortcode['email'] ) ) {

					if ( $arg_shortcode['email'] == '{{logged_in_user}}' ) {
						global $current_user;
						if ( isset( $current_user->user_email ) && ! empty( $current_user->user_email ) ) {
							$person = rt_biz_get_contact_by_email( $current_user->user_email );
						} else {
							$person = '';
						}
					} else {
						$person = rt_biz_get_contact_by_email( $arg_shortcode['email'] );
					}
					if ( isset( $person ) && ! empty( $person ) ) {
						$args['connected_items'] = $person[0]->ID;
						$args['connected_type']  = Rt_HD_Module::$post_type . '_to_' . rtbiz_post_type_name( 'contact' );
						$tickets                 = get_posts( $args );
					}
				}

				if ( ! empty( $arg_shortcode['user'] ) ) {
					$args['author'] = $arg_shortcode['user'];
					$tickets        = get_posts( $args );
				}
			} else if ( ! empty( $arg_shortcode['order'] ) ) {
				$args['meta_query'] = array(
					array(
						'key' => 'rtbiz_hd_order_id',
						'value' => $arg_shortcode['order'],
					),
				);
				$tickets = get_posts( $args );
			} else {
				$tickets = get_posts( $args );
			} ?>

			<h2><?php _e( 'Tickets', RT_HD_TEXT_DOMAIN ); ?></h2>

			<?php
			printf( _n( 'One Ticket Found.', '%d Tickets Found.', count( $tickets ), 'my-RT_HD_TEXT_DOMAIN-domain' ), count( $tickets ) );
			if ( 'yes' == $arg_shortcode['show_support_form_link'] ) {
				global $redux_helpdesk_settings;
				if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
					$page    = get_post( $redux_helpdesk_settings['rthd_support_page'] );
					?>
					<a href="<?php echo "/{$page->post_name}"; ?>"><?php _e( '(Get Support)', RT_HD_TEXT_DOMAIN ) ?></a>
				<?php
				}
			}?>
			<table class="shop_table my_account_orders">
				<tr>
					<th>Ticket ID</th>
					<th>Title</th>
					<th>Last Updated</th>
					<th>Status</th>
					<th></th>
				</tr>
			<?php if ( ! empty( $tickets ) ) {
				foreach ( $tickets as $ticket ) {
						$date = new DateTime( $ticket->post_modified );
						$link = ( is_admin() ) ? get_edit_post_link( $ticket->ID ) : esc_url( ( rthd_is_unique_hash_enabled() ) ? rthd_get_unique_hash_url( $ticket->ID ) : get_post_permalink( $ticket->ID ) );
						?>
						<tr>
							<td> #<?php echo esc_attr( $ticket->ID ) ?> </td>
							<td><?php echo $ticket->post_title; ?></td>
							<td> <?php echo esc_attr( human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) ) . esc_attr( __( ' ago' ) ) ?> </td>
							<td>
							<?php
								$style = 'padding: 5px; border: 1px solid black; border-radius: 5px;';
								$flag = false;
								$post_statuses = $rt_hd_module->get_custom_statuses();
								foreach ( $post_statuses as $status ) {
									if ( $status['slug'] == $ticket->post_status ) {
										$ticket->post_status = $status['name'];
										if ( ! empty( $status['style'] ) ) {
											$style = $status['style'];
										}
										$flag = true;
										break;
									}
								}
								if ( ! $flag ) {
									$ticket->post_status = ucfirst( $ticket->post_status );
								}
								if( ! empty( $ticket->post_status ) ) {
									printf( '<mark style="%s" class="%s tips" data-tip="%s">%s</mark>', $style, $ticket->post_status, $ticket->post_status, $ticket->post_status );
								}
							?>
							</td>
							<td><a class="button support" target="_blank"
							       href="<?php echo $link; ?>"><?php _e( 'Link' ); ?></a>
							</td>
						</tr>
					<?php
					}
			} else {
					?>
					<tr>
						<td colspan="5">No Tickets Found !</td>
					</tr>
				<?php } ?>
			</table>
		<?php
		}
	}
}
