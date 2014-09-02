<?php
/**
 * Don't load this file directly!
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Provide woocommerce integration with helpdesk for product suppourt
 *
 * @author paresh
 */

if ( !class_exists( 'Rt_HD_Woocommerce' ) ) {

	class Rt_HD_Woocommerce {

		function __construct() {

			$this->hooks();
		}


		function hooks() {

			// filter for add new action link on My Account page
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'wocommerce_actions_link' ), 10, 2 );

			// shortcode for get support form
			add_shortcode( 'rt_hd_support_form', array( $this, 'rt_hd_support_form_callback' ) );

			add_action( 'woocommerce_after_my_account', array( $this, 'woo_my_tickets_my_account' ) );

		}


		/**
		 * Add new action link for Get Support in woocommerce order list
		 * @global type $redux_helpdesk_settings
		 *
		 * @param type $actions
		 * @param type $order
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


		/*
		 * Shortcode callback for [rt_hd_support_form]
		 */
		function rt_hd_support_form_callback() {

			$option      = '';
			$order_email = '';

			// Save ticket if data has been posted
			if ( !empty( $_POST ) ) {
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
				$arg = array(
					'post_type' => 'product',
					'nopagging' => true,
				);

				$products = get_posts( $arg );

				foreach ( $products as $product ) {
					$option .= "<option value=$product->ID>$product->post_title</option>";
				}

			}
			?>
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
			<form method="post" action="" class="comment-form" enctype="multipart/form-data">

				<p>
					<label><?php _e( 'Product', RT_HD_TEXT_DOMAIN ); ?></label>
					<select name="post[product_id]">
						<option value="">Choose Product</option>
						<?php echo $option; ?>
					</select>
				</p>

				<p>
					<label><?php _e( 'Email', RT_HD_TEXT_DOMAIN ); ?></label>
					<input type="text" name="post[email]" value="<?php echo $order_email ?>"/>
				</p>

				<p>
					<label><?php _e( 'Description', RT_HD_TEXT_DOMAIN ); ?></label>
					<textarea name="post[description]"></textarea>
				</p>

				<p>
					<input type="file" id="filesToUpload" name="attachment[]" multiple="multiple"/>

				<ul id="fileList">
					<li>No Files Selected</li>
				</ul>
				</p>

				<p>
					<input type="submit" value="Submit"/>
				</p>


			</form>

		<?php

		}

		/**
		 * Save new support ticket for specified product
		 * @global type $rt_hd_contacts
		 */
		function save() {

			global $rt_hd_contacts, $rt_hd_tickets, $redux_helpdesk_settings;;

			$data = $_POST['post'];


			$product = get_product( $data['product_id'] );

			$rt_hd_tickets_id = $rt_hd_tickets->insert_new_ticket(
				"Support for {$product->post->post_title}",
				$data['description'],
				$redux_helpdesk_settings['rthd_default_user'], // it will changed to dynamic once redux option for default assignee shell be introduced
				'now',
				array( array( 'address' => $data['email'], 'name' => '' ) ),
				array(),
				$data['email']
			);

			update_post_meta( $rt_hd_tickets_id, '_rtbiz_helpdesk_woocommerce_product_id', $data['product_id'] );

			if ( $_FILES ) {

				$files = $_FILES['attachment'];
				foreach ( $files['name'] as $key => $value ) {
					if ( $files['name'][$key] ) {
						$file = array(
							'name'     => $files['name'][$key],
							'type'     => $files['type'][$key],
							'tmp_name' => $files['tmp_name'][$key],
							'error'    => $files['error'][$key],
							'size'     => $files['size'][$key]
						);

						$_FILES = array( "upload_attachment" => $file );

						foreach ( $_FILES as $file => $array ) {
							$newupload = self::insert_attachment( $file, $rt_hd_tickets_id );
						}
					}
				}
			}

			if ( isset( $_GET['order_id'] ) ) {
				update_post_meta( $rt_hd_tickets_id, '_rtbiz_helpdesk__woocommerce_order_id', $_GET['order_id'] );
			}

		}

		function woo_my_tickets_my_account() {

			global $current_user;

			echo do_shortcode( '[rt_hd_tickets email=' . $current_user->user_email . ']' );
		}


		static function insert_attachment( $file_handler, $post_id ) {
			// check to make sure its a successful upload
			if ( $_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK ) {
				__return_false();
			}

			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

			$attach_id = media_handle_upload( $file_handler, $post_id );

			return $attach_id;
		}
	}
}
