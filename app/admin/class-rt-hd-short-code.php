<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Description of class-RT_HD_Short_Code
 *
 * @author Dipesh
 */

if ( ! class_exists( 'RT_HD_Short_Code' ) ) {
	/**
	 * Class RT_HD_Short_Code
	 */
	class RT_HD_Short_Code {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_shortcode( 'rt_hd_support_form', array( $this, 'rt_hd_support_form_callback' ) );
			add_shortcode( 'rt_hd_tickets', array( $this, 'rt_hd_tickets_callback' ) );
		}

		/**
		 * Short code callback for Display Support Form
		 *
		 * @since 0.1
		 *
		 * [rt_hd_support_form]
		 */
		function rt_hd_support_form_callback( $attr ) {

			global $rt_hd_offering_support;

			ob_start();
			$rt_hd_offering_support->check_active_plugin();
			wp_enqueue_style( 'support-form-style', RT_HD_URL . 'app/assets/css/support_form_front.css', false, RT_HD_VERSION, 'all' );
			wp_enqueue_script( 'rthd-support-form', RT_HD_URL . 'app/assets/js/rt_support_form.js', array( 'jquery' ), RT_HD_VERSION, true );
			wp_enqueue_script( 'jquery-file-uploader', RT_HD_URL . 'app/assets/js/vendors/plupupload/plupload.full.min.js', array( 'jquery' ), RT_HD_VERSION, true );
			$offering_option = '';
			$order_email    = '';

			if ( is_user_logged_in() ) {
				$post_id = $rt_hd_offering_support->save_support_form();
				if ( ! empty( $post_id ) && is_int( $post_id ) ) { ?>
					<div id="info" class="success">Your support request has been submitted. We will get back to you for
						your query soon.
					</div>
				<?php }

				global $rtbiz_offerings;
				$terms = array();
				if ( isset( $rtbiz_offerings ) ) {
					add_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
					$terms = get_terms( Rt_Offerings::$offering_slug, array( 'hide_empty' => 0 ) );
					remove_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
				}
				$offering_exists = false;
				$wrong_user_flag = false;
				foreach ( $terms as $tm ) {
					$term_offering_id = '';
					$loggedin_id      = get_current_user_id();
					if ( isset( $_REQUEST[ 'order_id' ] ) && $rt_hd_offering_support->order_post_type == get_post_type( $_REQUEST[ 'order_id' ] ) ) {
						if ( $rt_hd_offering_support->isWoocommerceActive ) {
							$order = new WC_Order( $_REQUEST[ 'order_id' ] );
							if ( $loggedin_id = $order->get_user_id() ) {
								if ( ! empty( $order ) ) {
									$items            = $order->get_items();
									$product_ids      = wp_list_pluck( $items, 'product_id' );
									$term_offering_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, Rt_Offerings::$term_product_id_meta_key, true );
									if ( ! in_array( $term_offering_id, $product_ids ) ) {
										continue;
									}
								}
							} else {
								$wrong_user_flag = true;
							}
						} else if ( $rt_hd_offering_support->iseddActive ) {
							$payment = get_post( $_REQUEST[ 'order_id' ] );
							if ( $loggedin_id == $payment->post_author ) {
								if ( ! empty( $payment ) ) {
									$items            = edd_get_payment_meta_downloads( $payment->ID );
									$product_ids      = wp_list_pluck( $items, 'id' );
									$term_offering_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, Rt_Offerings::$term_product_id_meta_key, true );
									if ( ! in_array( $term_offering_id, $product_ids ) ) {
										continue;
									}
								}
							} else {
								$wrong_user_flag = true;
							}
						}
					}
					$offering_option .= '<option value="' . $tm->term_id . '" ' . ( ( ! empty( $_REQUEST[ 'product_id' ] ) && $term_offering_id == $_REQUEST[ 'product_id' ] ) ? 'selected="selected"' : '' ) . '> ' . $tm->name . '</option>';
					$offering_exists = true;
				}

				if ( $wrong_user_flag ) {
					echo '<span> You have not placed this order, Please login from account that placed this order. </span>';
				} else {
					rthd_get_template( 'support-form.php', array(
						'product_exists' => $offering_exists,
						'product_option' => $offering_option
					) );
				}
			} else{ ?>
				<div id="info" class="error">You're not logged in. Please login first to create support ticket.
				</div>
				<?php
			}
			return apply_filters( 'rt_hd_support_form_shorcode', ob_get_clean(), $attr );
		}

		/**
		 * Short code to display list of tickets in table format with Title, last updated, status, and link
		 *
		 * @since 0.1
		 *
		 * @param $atts
		 *
		 * @return string
		 */
		function rt_hd_tickets_callback( $atts ) {
			global $rt_hd_module, $current_user;

			$arg_shortcode = shortcode_atts(
				array(
					'userid' => '',
					'email' => '',
					'orderid' => '',
					'show_support_form_link' => 'no',
					'fav' => false
				), $atts );

			$args    = array(
				'post_type'   => Rt_HD_Module::$post_type,
				'post_status' => 'any',
				'nopaging'    => true,
			);


			global $current_user;

			if ( ! empty( $arg_shortcode['email'] ) && empty( $arg_shortcode['userid'] ) ) {
				if ( $arg_shortcode['email'] == '{{logged_in_user}}' ) {
					$arg_shortcode['userid'] = $current_user;
				}else{
					$person = rt_biz_get_contact_by_email( $arg_shortcode['email'] );
					$arg_shortcode['userid'] = rt_biz_get_wp_user_for_contact( $person->ID );
				}
				if ( is_object( $arg_shortcode['userid'] ) ){
					$arg_shortcode['userid'] = $arg_shortcode['userid'] ->ID;
				}
			}

			$tickets = array();
			if ( ! empty( $arg_shortcode['userid'] ) ) {
				if ( ! empty( $arg_shortcode['fav'] ) ) {
					$tickets = rthd_get_tickets( 'favourite', $arg_shortcode['userid'] );
				}
				elseif ( rthd_is_our_employee( $arg_shortcode['userid'], RT_HD_TEXT_DOMAIN ) ){
					$tickets = rthd_get_tickets( 'assignee', $arg_shortcode['userid'] );
				}
				else {
					$tickets = rthd_get_tickets( 'created_by', $arg_shortcode['userid'] );
				}
			} elseif ( ! empty( $arg_shortcode['orderid'] ) ) {
				$tickets = rthd_get_tickets( 'order', $arg_shortcode['orderid'] );
			}
			?>
			<?php
			ob_start();
			if ( ! empty( $arg_shortcode['fav'] ) ) { ?>
				<h2 class="rthd-ticket-list-title"><?php _e( 'Favourite Tickets', RT_HD_TEXT_DOMAIN ); ?></h2>
				<?php
			} else {
				?>
				<h2 class="rthd-ticket-list-title"><?php _e( 'Your Tickets', RT_HD_TEXT_DOMAIN ); ?></h2>
			<?php
			}
			echo '<div class="rthd-ticket-list">';
			printf( _n( 'One Ticket Found', '%d Tickets Found', count( $tickets ), 'my-RT_HD_TEXT_DOMAIN-domain' ), count( $tickets ) );
			if ( 'yes' == $arg_shortcode['show_support_form_link'] ) {
				global $redux_helpdesk_settings;
				if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
					$page    = get_post( $redux_helpdesk_settings['rthd_support_page'] );
					?>
					<a href="<?php echo "/{$page->post_name}"; ?>"><?php _e( '(Get Support)', RT_HD_TEXT_DOMAIN ) ?></a>
				<?php
				}
			}
			echo '</div>';
			if ( ! empty( $tickets ) ) {
				?>
				<table class="shop_table my_account_orders">
					<tr>
						<th>Ticket ID</th>
						<th>Title</th>
						<th>Last Updated</th>
						<th>Status</th>
						<th>Links</th>
					</tr>
					<?php
					foreach ( $tickets as $ticket ) {
						$date = new DateTime( $ticket->post_modified );
						?>
						<tr>
							<td> #<?php echo esc_attr( $ticket->ID ) ?> </td>
							<td><?php echo $ticket->post_title; ?></td>
							<td> <?php echo esc_attr( human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) ) . esc_attr( __( ' ago' ) ) ?> </td>
							<td>
								<?php
								$style         = 'padding: 5px; border: 1px solid black; border-radius: 5px;';
								$flag          = false;
								$post_statuses = $rt_hd_module->get_custom_statuses();
								foreach ( $post_statuses as $status ) {
									if ( $status[ 'slug' ] == $ticket->post_status ) {
										$ticket->post_status = $status[ 'name' ];
										if ( ! empty( $status[ 'style' ] ) ) {
											$style = $status[ 'style' ];
										}
										$flag = true;
										break;
									}
								}
								if ( ! $flag ) {
									$ticket->post_status = ucfirst( $ticket->post_status );
								}
								if ( ! empty( $ticket->post_status ) ) {
									printf( '<mark style="%s" class="%s tips" data-tip="%s">%s</mark>', $style, $ticket->post_status, $ticket->post_status, $ticket->post_status );
								}
								?>
							</td>
							<td>

								<?php if ( current_user_can( rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' ) ) || $ticket->post_author == $current_user->ID ) { ?>
									<a class="button support" target="_blank"
									   href="<?php echo get_edit_post_link( $ticket->ID ); ?>"><?php _e( 'Edit' ); ?></a> |
								<?php } ?>
								<a class="button support" target="_blank"
								   href="<?php echo esc_url( ( rthd_is_unique_hash_enabled() ) ? rthd_get_unique_hash_url( $ticket->ID ) : get_post_permalink( $ticket->ID ) ); ?>"><?php _e( 'View' ); ?></a>
							</td>
						</tr>
					<?php
					} ?>
				</table>
			<?php
			}
			$html_content = ob_get_clean();
			return $html_content;
		}

	}
}
