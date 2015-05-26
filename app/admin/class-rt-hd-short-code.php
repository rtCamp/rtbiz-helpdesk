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
			wp_enqueue_style( 'helpdesk-style', RT_HD_URL . 'app/assets/css/rthd-main.css', false, RT_HD_VERSION, 'all' );
			wp_enqueue_script( 'rthd-support-form', RT_HD_URL . 'app/assets/js/helpdesk-support-min.js', array( 'jquery' ), RT_HD_VERSION, true );
			$offering_option = '';

			if ( is_user_logged_in() ) {
				$post_id = $rt_hd_offering_support->save_support_form();
				if ( ! empty( $post_id ) && is_int( $post_id ) ) {
					?>
					<div id="info" class="success rthd-notice">Your support request has been submitted. We will get back to you for
						your query soon.
					</div>
				<?php
				}

				global $rtbiz_offerings;
				$terms = array();
				if ( isset( $rtbiz_offerings ) ) {
					add_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
					$terms = get_terms( Rt_Offerings::$offering_slug, array( 'hide_empty' => 0 ) );
					remove_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
				}
				$offering_exists = false;
				$wrong_user_flag = false;
				$loggedin_id = get_current_user_id();

				$product_ids = array();

				if ( isset( $_REQUEST['order_id'] ) ) {
					// check in woo orders
					if ( $rt_hd_offering_support->isWoocommerceActive ) {
						$order = new WC_Order( $_REQUEST['order_id'] );
						if ( ! empty( $order ) && 'shop_order' == $order->post->post_type ) {
							if ( $loggedin_id == $order->get_user_id() ) {
								$items           = $order->get_items();
								$product_ids     = wp_list_pluck( $items, 'product_id' );
								$wrong_user_flag = false;
							} else {
								$wrong_user_flag = true;
							}
						} else {
							$order = array();
						}
					}
					// check in edd orders
					if ( $rt_hd_offering_support->iseddActive && empty( $order ) ) {
						$payment = get_post( $_REQUEST['order_id'] );
						if ( ! empty( $payment ) && $loggedin_id == $payment->post_author ) {
							if ( 'edd_payment' == $payment->post_type ) {
								$items       = edd_get_payment_meta_downloads( $payment->ID );
								$product_ids = wp_list_pluck( $items, 'id' );
								$wrong_user_flag = false;
							}
						} else {
							$wrong_user_flag = true;
						}
					}
				}

				foreach ( $terms as $tm ) {
					$term_offering_id = '';
					// skip items if not from orders
					if ( ! empty( $product_ids ) ) {
						$term_offering_id = Rt_Lib_Taxonomy_Metadata\get_term_meta( $tm->term_id, Rt_Offerings::$term_product_id_meta_key, true );
						if ( ! in_array( $term_offering_id, $product_ids ) ) {
							continue;
						}
					}
					$offering_option .= '<option value="' . $tm->term_id . '" ' . ( ( ! empty( $_REQUEST['product_id'] ) && $term_offering_id == $_REQUEST['product_id'] ) ? 'selected="selected"' : '' ) . '> ' . $tm->name . '</option>';
					$offering_exists = true;
				}

				if ( $wrong_user_flag ) {
					echo '<span> You have not placed this order, Please login from account that placed this order. </span>';
				} else {
					rthd_get_template( 'support-form.php', array(
						'product_exists' => $offering_exists,
						'product_option' => $offering_option,
					) );
				}
			} else {
				?>
				<div id="info" class="error rthd-notice">You're not logged in. Please login first to create support ticket.
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
			global $rt_hd_module, $current_user, $redux_helpdesk_settings;

			$arg_shortcode = shortcode_atts(
				array(
				'userid' => '',
				'email' => '',
				'orderid' => '',
				'show_support_form_link' => 'no',
				'fav' => false,
				'title' => 'yes',
					), $atts );

					$args = array(
					'post_type' => Rt_HD_Module::$post_type,
					'post_status' => 'any',
					'nopaging' => true,
					);
					$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );

					if ( ! empty( $arg_shortcode['email'] ) && empty( $arg_shortcode['userid'] ) ) {
						if ( '{{logged_in_user}}' == $arg_shortcode['email'] ) {
							$arg_shortcode['userid'] = $current_user;
						} else {
							$person = rt_biz_get_contact_by_email( $arg_shortcode['email'] );
							$arg_shortcode['userid'] = rt_biz_get_wp_user_for_contact( $person->ID );
						}
						if ( is_object( $arg_shortcode['userid'] ) ) {
							$arg_shortcode['userid'] = $arg_shortcode['userid']->ID;
						}
					}
					$is_staff = user_can( $current_user, $cap );
					// if user can not access Helpdesk don't show him fav tickets
					if ( $arg_shortcode['fav'] ) {
						if ( ! $is_staff ) {
							return '';
						}
					}

					$tickets = array();
					$oder_shortcode = false;
					if ( ! empty( $arg_shortcode['userid'] ) ) {
						if ( ! empty( $arg_shortcode['fav'] ) ) {
							$tickets = rthd_get_tickets( 'favourite', $arg_shortcode['userid'] );
						} elseif ( $is_staff ) {
							$fav = rthd_get_tickets( 'favourite', $arg_shortcode['userid'] );
							$tickets = rthd_get_tickets( 'assignee', $arg_shortcode['userid'] );
							$tickets = array_udiff( $tickets, $fav, 'rthd_compare_wp_post' );
							$tickets = $fav + $tickets ;
						} else {
							$tickets = rthd_get_tickets( 'created_by', $arg_shortcode['userid'] );
						}
					} elseif ( ! empty( $arg_shortcode['orderid'] ) ) {
						$tickets = rthd_get_tickets( 'order', $arg_shortcode['orderid'] );
						$oder_shortcode = true;
					}

					if ( ! empty( $arg_shortcode['fav'] ) && empty( $tickets ) ) {
						return '';
					}
			?>
			<?php
			ob_start();
			?>
			<div class="rthd-ticket-list-header"> <?php
			if ( ! empty( $arg_shortcode['fav'] ) && ! empty( $tickets ) ) {
				if ( 'yes' === $arg_shortcode['title'] ) { ?>
					<h2 class="rthd-ticket-list-title"><?php echo __( 'Favourite Tickets', RT_HD_TEXT_DOMAIN ) . ' <span class="rthd-count">(' . count( $tickets ) . ')</span>'; ?></h2>
				<?php
				}

				if ( 'yes' == $arg_shortcode['show_support_form_link'] ) {
					global $redux_helpdesk_settings;
					if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
						$page = get_post( $redux_helpdesk_settings['rthd_support_page'] );
						?>
						<a class="clearfix" href="<?php echo "/{$page->post_name}"; ?>"><button class=""><?php _e( 'Create New Ticket', RT_HD_TEXT_DOMAIN ) ?></button></a>
					<?php
					}
				}
			} else {
				if ( 'yes' === $arg_shortcode['title'] ) { ?>
					<h2 class="rthd-ticket-list-title"><?php _e( 'Tickets', RT_HD_TEXT_DOMAIN );
						echo ( empty( $tickets ) ) ? '' : ' <span class="rthd-count">(' . count( $tickets ) . ')</span>'; ?></h2><?php
				}
				if ( 'yes' == $arg_shortcode['show_support_form_link'] ) {
					if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
						$page = get_post( $redux_helpdesk_settings['rthd_support_page'] );
						if ( $oder_shortcode ) {
						    $support_url = "/{$page->post_name}?order_id={$arg_shortcode['orderid']}";
						} else {
							$support_url = "/{$page->post_name}";
						} ?>

						<a class="clearfix" href="<?php echo $support_url; ?>"><button class="button btn button-primary btn-primary"><?php _e( 'Create New Ticket', RT_HD_TEXT_DOMAIN ) ?></button></a>
					<?php
					}
				}
				if ( empty( $tickets ) && ! $is_staff ) {
					if ( empty( $page ) ) {
						if ( isset( $redux_helpdesk_settings['rthd_support_page'] ) && ! empty( $redux_helpdesk_settings['rthd_support_page'] ) ) {
							$page = get_post( $redux_helpdesk_settings['rthd_support_page'] );
						}
					}
					echo '<p>'.__( 'You have not created any tickets yet. Create one now.', RT_HD_TEXT_DOMAIN ).'</p>';
				} else if ( empty( $tickets ) ) {
					echo '<p>'.__( 'No tickets found', RT_HD_TEXT_DOMAIN ).'</p>';
				}
			}
			//			echo '<div class="rthd-ticket-list">';
			//			printf( '<p>'._n( 'One Ticket Found', '%d Tickets Found', count( $tickets ), 'my-RT_HD_TEXT_DOMAIN-domain' ). '</p>', count( $tickets ) );

			echo '</div>';
			if ( $is_staff && ! empty( $arg_shortcode['userid'] ) ) {
				$fav_staff_tickets = rthd_get_user_fav_ticket( $arg_shortcode['userid'] );
				if ( ! empty( $fav ) ) {
					?>
					<p> <?php _e( 'Your favourite tickets are highlighted below.' ); ?></p>
				<?php
				}
			}

			if ( is_admin() && ! empty( $tickets ) && $oder_shortcode ) { ?>
				<p> <?php _e( 'Below are the all the tickets created by this customer. The tickets for this order are highlighted.', RT_HD_TEXT_DOMAIN ); ?></p>
			<?php
			}
			if ( ! empty( $tickets ) ) {
				?>
				<table class="wp-list-table striped widefat shop_table my_account_orders rthd_ticket_short_code">
				<thead>
					<tr>
						<th>Ticket ID</th>
						<th>Title</th>
						<th>Last Updated</th>
						<th>Status</th>
						<?php echo ( $is_staff )?'<th>Edit</th>':''; ?>
					</tr>
					</thead>
					<?php
					foreach ( $tickets as $ticket ) {
						$highlight_class = '';

						if ( $is_staff && ! empty( $fav_staff_tickets ) ) {
							if ( in_array( $ticket->ID, $fav_staff_tickets ) ) {
								$highlight_class = 'rthd_highlight_row';
							}
						}
						if ( $oder_shortcode && is_admin() ) {
							$hd_order_by = get_post_meta( $ticket->ID, 'rtbiz_hd_order_id', true );
							$order_by = $arg_shortcode['orderid'];

							if ( $hd_order_by == $order_by ) {
								$highlight_class = 'rthd_highlight_row';
							}
						}
						$date = new DateTime( $ticket->post_modified );
						?>
						<tr class="<?php echo $highlight_class; ?>">
							<td><a class="support" target="_blank"
							       href="<?php echo esc_url( ( rthd_is_unique_hash_enabled() ) ? rthd_get_unique_hash_url( $ticket->ID ) : get_post_permalink( $ticket->ID ) ); ?>"> #<?php echo esc_attr( $ticket->ID ) ?> </a></td>
							<td><?php echo $ticket->post_title; ?></td>
							<td> <?php echo esc_attr( human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) ) . esc_attr( __( ' ago' ) ) ?> </td>
							<td>
								<?php
								if ( ! empty( $ticket->post_status ) ) {
									echo rthd_status_markup( $ticket->post_status );
								}
								?>
							</td>
							<?php if ( current_user_can( rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' ) ) || $ticket->post_author == $current_user->ID ) { ?>
							<td>
									<a class="support" target="_blank"
									   href="<?php echo get_edit_post_link( $ticket->ID ); ?>"><span class="dashicons dashicons-edit"></span></a>
							</td>
							<?php } ?>
						</tr>
					<?php }
				?>
				</table>
				<?php
			}
			$html_content = ob_get_clean();
			return $html_content;
		}

	}

}
