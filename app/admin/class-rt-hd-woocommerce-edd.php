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


if ( ! class_exists( 'Rt_HD_Woocommerce_EDD' ) ) {

	/**
	 * Class Rt_HD_Woocommerce_EDD
	 * Provide wooCommerce & EDD integration with HelpDesk for product support
	 *
	 */
	class Rt_HD_Woocommerce_EDD {

		/**
		 * construct
		 *
		 * @since 0.1
		 */
		function __construct() {
			$this->check_active_plugin();
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
			?>
			<th class="edd_rt_hd_support"><?php _e( 'Support', 'edd' ); ?></th>
			<?php
		}

		function edd_support_link( $payment_id, $download_id ) {
			global $redux_helpdesk_settings;
			$page = get_post( $redux_helpdesk_settings['rthd_support_page'] );
			?>
			<td class="edd_rt_hd_support"><a href="<?php echo "/{$page->post_name}/?product_id={$download_id}&order_id={$payment_id}&order_type=edd"; ?>"><?php _e( 'Get Support', RT_HD_TEXT_DOMAIN ) ?></a></td>
			<?php
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
			$page               = get_post( $redux_helpdesk_settings['rthd_support_page'] );
			$actions['support'] = array(
				'url'  => "/{$page->post_name}/?order_id={$order->id}&order_type=woocommerce",
				'name' => __( 'Get Support', RT_HD_TEXT_DOMAIN )
			);

			return $actions;

		}


		function check_active_plugin(){
			$activePlugin = rt_biz_get_settings( 'product_plugin' );
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && 'woocommerce' === $activePlugin ) {
				$this->isWoocommerceActive = true;
				$this->iseddActive = false;
				$this->activePostType = 'product';
				$this->order_post_type = 'shop_order';
			}
			else if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) && 'edd' === $activePlugin ) {
				$this->iseddActive = true;
				$this->isWoocommerceActive  = false;
				$this->activePostType = 'download';
				$this->order_post_type = 'edd_payment';
			}
			else {
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
			wp_enqueue_style( 'support-form-style', RT_HD_URL . 'app/assets/css/support_form_front.css', false, RT_HD_VERSION, 'all' );
			$product_option = '';
			$order_email    = '';

			if ( !empty( $_REQUEST['order_id'] ) ) {
				$order = new WC_Order( $_REQUEST['order_id'] );
				var_dump($order);
				if ( empty( $order ) ) {
				?>
					<div class="error">It looks like you are trying to get support for an invalid order. Please create a fresh support.</div>
				<?php
					unset( $_REQUEST['order_id'] );
					unset( $_GET['order_id'] );
					unset( $_POST['order_id'] );
				}

				global $current_user;
//				if ( $current_user->user_email == $order-> )
			}

			// Save ticket if data has been posted
			if ( ! empty( $_POST ) ) {
				$post_id = self::save();
				if ( isset( $post_id ) && ! empty( $post_id ) && is_int( $post_id ) ) {
				?>
					<div id="info" class="success">Your Support request have been Submitted.</div>
				<?php
				}
			}

			global $rtbiz_product_sync;
			$terms = array();
			if ( isset( $rtbiz_product_sync ) ) {
				$terms = get_terms( $rtbiz_product_sync->product_slug, array( 'hide_empty' => 0 ) );
			}
			$product_exists = false;
			foreach ( $terms as $tm ) {
				$term_product_id = '';
				if ( isset( $_REQUEST['order_id'] ) && $this->order_post_type == get_post_type( $_REQUEST['order_id'] ) ) {
					if ( $this->isWoocommerceActive ) {
						$order = new WC_Order( $_REQUEST['order_id'] );
						if ( !empty( $order ) ) {
							$items = $order->get_items();
							$product_ids = wp_list_pluck( $items, 'product_id' );
							$term_product_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, '_product_id', true );
							if ( ! in_array( $term_product_id, $product_ids ) ) {
								continue;
							}
						}
					} else if ( $this->iseddActive ) {
						$payment = get_post( $_REQUEST['order_id'] );
						if ( !empty( $payment ) ) {
							$items = edd_get_payment_meta_downloads( $payment->ID );
							$product_ids = wp_list_pluck( $items, 'id' );
							$term_product_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, '_product_id', true );
							if ( ! in_array( $term_product_id, $product_ids ) ) {
								continue;
							}
						}
					}
				}
				$product_option .= '<option value="' . $tm->term_id . '" ' . ( ( ! empty( $_REQUEST['product_id'] ) && $term_product_id == $_REQUEST['product_id'] ) ? 'selected="selected"' : '' ) . '> '.$tm->name.'</option>';
				$product_exists = true;
			}

			rthd_get_template( 'support-form.php', array( 'product_exists' => $product_exists, 'product_option' => $product_option ) );
			return apply_filters( 'rt_hd_support_form_shorcode', ob_get_clean(), $attr );
		}

		/**
		 * Save new support ticket for wooCommerce
		 *
		 * @since 0.1
		 *
		 */
		function save() {
			global $rtbiz_product_sync, $rt_hd_import_operation, $redux_helpdesk_settings;;

			$data = $_POST['post'];
			$productstr = $data['title'];

			//Ticket created
			$rt_hd_tickets_id = $rt_hd_import_operation->insert_new_ticket(
				$productstr,
				$data['description'],
				'now',
				array( array( 'address' => $data['email'], 'name' => '' ) ),
				array(),
				$data['email']
			);

			if ( isset( $data['product_id'] ) ) {
				$term = get_term_by( 'id', $data['product_id'], $rtbiz_product_sync->product_slug );
				if ( $term ) {
					wp_set_post_terms( $rt_hd_tickets_id, array( $term->term_id ), $rtbiz_product_sync->product_slug );
				}
			}

			// Created attachment
			if ( $_FILES ) {
				$files = $_FILES['attachment'];
				foreach ( $files['name'] as $key => $value ) {
					if ( $files['name'][ $key ] ) {
						$file = array(
							'name'     => $files['name'][ $key ],
							'type'     => $files['type'][ $key ],
							'tmp_name' => $files['tmp_name'][ $key ],
							'error'    => $files['error'][ $key ],
							'size'     => $files['size'][ $key ],
						);

						$_FILES = array( 'upload_attachment' => $file );

						foreach ( $_FILES as $file => $array ) {
							$attach_id              = self::insert_attachment( $file, $rt_hd_tickets_id );
							$filepath               = get_attached_file( $attach_id );
							$post_attachment_hashes = get_post_meta( $rt_hd_tickets_id, '_rtbiz_hd_attachment_hash' );
							if ( ! empty( $post_attachment_hashes ) && ! in_array( md5_file( $filepath ), $post_attachment_hashes ) ) {
								add_post_meta( $attach_id, '_wp_attached_file', $filepath );
								add_post_meta( $rt_hd_tickets_id, '_rtbiz_hd_attachment_hash', md5_file( $filepath ) );
							}
						}
					}
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
		static function insert_attachment( $file_handler, $post_id ) {
			// check to make sure its a successful upload
			if ( $_FILES[ $file_handler ]['error'] !== UPLOAD_ERR_OK ) {
				__return_false();
			}

			require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );

			$attach_id = media_handle_upload( $file_handler, $post_id );

			return $attach_id;
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
				), $atts );

			$args    = array(
				'post_type'   => Rt_HD_Module::$post_type,
				'post_status' => 'any',
				'nopaging'    => true,
			);
			$tickets = null;
			if ( ! empty( $arg_shortcode['email'] ) || ! empty( $arg_shortcode['user'] ) ) {

				if ( ! empty( $arg_shortcode['email'] ) ) {

					if ( $arg_shortcode['email'] == '{{logged_in_user}}' ) {
						global $current_user;
						if ( isset( $current_user->user_email ) && ! empty( $current_user->user_email ) ) {
							$person = rt_biz_get_person_by_email( $current_user->user_email );
						} else {
							$person = '';
						}
					} else {
						$person = rt_biz_get_person_by_email( $arg_shortcode['email'] );
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
			?>
			<table class="shop_table my_account_orders">
				<tr>
					<th>Ticket ID</th>
					<th>Title</th>
					<th>Last Updated</th>
					<th>Status</th>
					<th></th>
				</tr>
			<?php if ( isset( $tickets ) && ! empty( $tickets ) ) {
				foreach ( $tickets as $ticket ) {
						$rthd_unique_id = get_post_meta( $ticket->ID, '_rtbiz_hd_unique_id', true );
						$date           = new DateTime( $ticket->post_modified );
						$link           = ( is_admin() ) ? get_edit_post_link( $ticket->ID ) : esc_url( trailingslashit( site_url() ) ) . esc_attr( strtolower( $labels['name'] ) ) . '/' . esc_attr( $rthd_unique_id );
						?>
						<tr>
							<td> #<?php echo esc_attr( $ticket->ID ) ?> </td>
							<td><?php echo $ticket->post_title; ?></td>
							<td> <?php echo esc_attr( human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) ) . esc_attr( __( ' ago' ) ) ?> </td>
							<td> <?php echo esc_attr( $ticket->post_status ) ?> </td>
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
