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


if ( ! class_exists( 'Rt_HD_Woocommerce' ) ) {

	/**
	 * Class Rt_HD_Woocommerce
	 * Provide wooCommerce integration with HelpDesk for product support
	 *
	 */
	class Rt_HD_Woocommerce {

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

			// shortcode for get support form
			add_shortcode( 'rt_hd_support_form', array( $this, 'rt_hd_support_form_callback' ) );
			add_shortcode( 'rt_hd_tickets', array( $this, 'rt_hd_tickets_callback' ) );

			add_action( 'woocommerce_after_my_account', array( $this, 'woo_my_tickets_my_account' ) );

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
			$page               = get_page( $redux_helpdesk_settings['rthd_support_page'] );
			$actions['support'] = array(
				'url'  => "/{$page->post_name}/?order_id={$order->id}",
				'name' => __( 'Get Support', RT_HD_TEXT_DOMAIN )
			);

			return $actions;

		}


		/**
		 * Short code callback for Display Support Form
		 *
		 * @since 0.1
		 *
		 * [rt_hd_support_form]
		 */
		function rt_hd_support_form_callback() {
			wp_enqueue_style( 'support-form-style', RT_HD_URL . 'app/assets/css/support_form_front.css', false, RT_HD_VERSION, 'all' );
			$option      = '';
			$order_email = '';

			// Save ticket if data has been posted
			if ( ! empty( $_POST ) ) {
				self::save();
			}

			if ( isset( $_GET['order_id'] ) ) {
				$order = new WC_Order( $_GET['order_id'] );
				$items = $order->get_items();
				$order_email = $order->billing_email;
				foreach ( $items as $item ) {
					$product_name         = $item['name'];
					$product_id           = $item['product_id'];
					$product_variation_id = $item['variation_id'];
					$option .= "<option value=$product_id>$product_name</option>";
				}
			} else {
				$arg      = array(
					'post_type' => 'product',
					'nopagging' => true,
				);
				$products = get_posts( $arg );
				foreach ( $products as $product ) {
					$option .= "<option value=$product->ID>$product->post_title</option>";
				}
			} ?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					//print list of selected file
					$("#filesToUpload").change(function () {
						var input = document.getElementById('filesToUpload');
						var list = '';
						//for every file...
						for (var x = 0; x < input.files.length; x++) {
							//add to list

							list += '<li>' + input.files[x].name + '</li>';
						}
						$("#fileList").html(list);
					});
				});
			</script>

			<h2><?php _e( 'Get Support', 'RT_HD_TEXT_DOMAIN' ); ?></h2>
			<form method="post" action="" class="comment-form pure-form pure-form-aligned" enctype="multipart/form-data">
				<div class="pure-control-group">
			<!--					<label>--><?php //_e( 'Product', RT_HD_TEXT_DOMAIN ); ?><!--</label>-->
					<select name="post[product_id]" required>
						<option value="">Choose Product</option>
						<?php echo balanceTags( $option ); ?>
					</select>
				</div>
				 <div class="pure-control-group">
			<!--					<label for="email">--><?php //_e( 'Email', RT_HD_TEXT_DOMAIN ); ?><!--</label>-->
					<input id="email" placeholder="email" type="email" name="post[email]" value="<?php echo sanitize_email( $order_email ) ?>" required/>
				</div>

				 <div class="pure-control-group">
			<!--					<label>--><?php //_e( 'Description', RT_HD_TEXT_DOMAIN ); ?><!--</label>-->
					<textarea name="post[description]" placeholder="Description" rows="10" cols="10" required></textarea>

				</div>

				 <div class="pure-control-group">
					<input type="file" id="filesToUpload" name="attachment[]" multiple="multiple"/>
					<ul id="fileList">
						<li>No Files Selected</li>
					</ul>
				</div>
				 <div class="pure-control-group">
					<input type="submit" value="Submit"/>
				</div>
			</form>
		<?php
		}

		/**
		 * Save new support ticket for wooCommerce
		 *
		 * @since 0.1
		 *
		 * @global type $rt_hd_contacts
		 */
		function save() {

			global $rt_hd_contacts, $rt_hd_import_operation, $redux_helpdesk_settings;;

			$data = $_POST['post'];


			$product = get_product( $data['product_id'] );

			$rt_hd_tickets_id = $rt_hd_import_operation->insert_new_ticket(
				"Support for {$product->post->post_title}",
				$data['description'],
				$redux_helpdesk_settings['rthd_default_user'], // it will changed to dynamic once redux option for default assignee shell be introduced
				'now',
				array( array( 'address' => $data['email'], 'name' => '' ) ),
				array(),
				$data['email']
			);

			global $rtbiz_wc_product;
			$product_taxonomy = $rtbiz_wc_product->get_taxonomy( $data['product_id'] );
			wp_set_post_terms( $rt_hd_tickets_id, array( $product_taxonomy->term_id ) , $rtbiz_wc_product->product_slug );

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
							$newupload = self::insert_attachment( $file, $rt_hd_tickets_id );
						}
					}
				}
			}

			if ( isset( $_GET['order_id'] ) ) {
				update_post_meta( $rt_hd_tickets_id, '_rtbiz_hd_woocommerce_order_id', $_GET['order_id'] );
			}
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
			$labels = $rt_hd_module->labels;
			$arg_shortcode = shortcode_atts(
				array(
					'email' => '',
					'user'  => '',
				), $atts );

			$args = array(
				'post_type'   => Rt_HD_Module::$post_type,
				'post_status' => 'any',
				'nopaging'    => true,
			);
			$tickets = null;
			if ( ! empty( $arg_shortcode['email'] ) || ! empty( $arg_shortcode['user'] ) ){

				if ( ! empty( $arg_shortcode['email'] ) ) {
					$person = rt_biz_get_person_by_email( $arg_shortcode['email'] );
					if ( isset( $person ) && ! empty( $person ) ) {
						$args['connected_items'] = $person[0]->ID;
						$args['connected_type']  = Rt_HD_Module::$post_type .'_to_' . rtbiz_post_type_name( 'contact' );
						$tickets = get_posts( $args );
					}
				}

				if ( ! empty( $arg_shortcode['user'] ) ) {
					$args['author'] = $arg_shortcode['user'];
					$tickets = get_posts( $args );
				}
			} else {
				$tickets = get_posts( $args );
			} ?>

			<h2><?php _e( 'Tikets', RT_HD_TEXT_DOMAIN ); ?></h2>

			<?php
			printf( _n( 'One Ticket Found.', '%d Tickets Found.', count( $tickets ), 'my-RT_HD_TEXT_DOMAIN-domain' ), count( $tickets ) );
			?>
			<table class="shop_table my_account_orders">
				<tr>
					<th>Ticket ID</th>
					<th>Last Updated</th>
					<th>Status</th>
					<th></th>
				</tr>
			<?php if ( isset( $tickets ) && ! empty( $tickets ) ) {
				foreach ( $tickets as $ticket ) {
					$rthd_unique_id = get_post_meta( $ticket->ID, '_rtbiz_hd_unique_id', true );
					$date           = new DateTime( $ticket->post_modified );
					?>
					<tr>
						<td> #<?php echo esc_attr( $ticket->ID ) ?> </td>
						<td> <?php echo esc_attr( human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) ) . esc_attr( __( ' ago' ) ) ?> </td>
						<td> <?php echo esc_attr( $ticket->post_status ) ?> </td>
						<td><a class="button support" target="_blank"
						       href="<?php echo esc_url( trailingslashit( site_url() ) ) . esc_attr( strtolower( $labels['name'] ) ) . '/?rthd_unique_id=' . esc_attr( $rthd_unique_id ); ?>"><?php _e( 'Link' ); ?></a>
						</td>
					</tr>
				<?php }
			} else { ?>
					<tr><td colspan="4">No Ticketes Found !</td></tr>
			<?php } ?>
			</table>
		<?php
		}
	}
}
