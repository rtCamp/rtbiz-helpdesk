<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Mailbox' ) ) {

	/**
	 * Class Rtbiz_HD_Mailbox
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rtbiz_HD_Mailbox {

		/**
		 * set hooks
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {

			Rtbiz_HD::$loader->add_action( 'rt_mailbox_assignee_ui', $this, 'mailbox_assignee_ui', 10, 2 );
			Rtbiz_HD::$loader->add_filter( 'rt_mailbox_assignee_save', $this, 'mailbox_assignee_save', 10, 2 );

			Rtbiz_HD::$loader->add_action( 'delete_term', $this, 'delete_mailbox_product', 10, 3 );
			Rtbiz_HD::$loader->add_action( 'delete_user', $this, 'delete_mailbox_employee', 10, 1 );
		}


		public function mailbox_assignee_ui( $email_data, $module = NULL ) {

			if ( $module == RTBIZ_HD_TEXT_DOMAIN ) {
				// get product list
				$rtbiz_hd_products = array();
				global $rtbiz_products;
				if ( isset( $rtbiz_products ) ) {
					add_filter( 'get_terms', array( $rtbiz_products, 'product_filter' ), 10, 3 );
					$rtbiz_hd_products = get_terms( Rt_Products::$product_slug, array( 'hide_empty' => 0 ) );
					remove_filter( 'get_terms', array( $rtbiz_products, 'product_filter' ), 10, 3 );
				}

				$product_list = '';
				if ( isset ( $rtbiz_hd_products ) && ! empty ( $rtbiz_hd_products ) ) {
					$product_list .= '<div class="rtmailbox-row rthd-mailbox-assign"><label> ' . __( 'Select Product', RTBIZ_HD_TEXT_DOMAIN ) . '</label>';
					$product_list .= '<div class="mailbox-folder-list">';
					$product_list .= '<select name="rtmailbox[product]" class="redux-select-item" tabindex="-1" title="">';
					$product_list .= '<option value="0">Select Product</option>';
					foreach ( $rtbiz_hd_products as $rtbiz_hd_product ) {
						$product_selected = '';
						if ( isset ( $email_data['product'] ) && ! empty ( $email_data['product'] ) ) {
							if ( $email_data['product'] == $rtbiz_hd_product->term_id ) {
								$product_selected = ' selected="selected" ';
							}
						}
						$product_list .= '<option value="' . $rtbiz_hd_product->term_id . '"' . $product_selected . '>' . $rtbiz_hd_product->name . '</option>';
					}
					$product_list .= '</select>';
					$product_list .= '</div>';
					$product_list .= '</div>';
				}
				echo $product_list;

				$users = Rtbiz_HD_Utils::get_hd_rtcamp_user();
				$users_options = '';

				if ( isset ( $users ) && ! empty ( $users ) ) {
					$users_options .= '<div class="rtmailbox-row rthd-mailbox-assign"><label> ' . __( 'Select Assignee', RTBIZ_HD_TEXT_DOMAIN ) . '</label>';
					$users_options .= '<div class="mailbox-folder-list">';
					$users_options .= '<select name="rtmailbox[staff]" class="redux-select-item" tabindex="-1" title="">';
					$users_options .= '<option value="0">Select Assignee</option>';
					foreach ( $users as $user ) {
						$user_selected = '';
						if ( isset ( $email_data['staff'] ) && ! empty ( $email_data['staff'] ) ) {
							if ( $email_data['staff'] == $user->ID ) {
								$user_selected = ' selected="selected" ';
							}
						}
						$users_options .= '<option value="' . $user->ID . '"' . $user_selected . '>' . $user->display_name . '</option>';
					}
					$users_options .= '</select>';
					$users_options .= '</div>';
					$users_options .= '</div>';
				}
				echo $users_options;
			}
		}


		/**
		 * Filter to replace the [caption] shortcode text with HTML5 compliant code
		 *
		 * @return text HTML content describing embedded figure
		 **/
		public function mailbox_assignee_save( $email_data, $obj_data ) {
			if ( $obj_data['module'] == RTBIZ_HD_TEXT_DOMAIN ) {
				if ( isset ( $obj_data['product'] ) ) {
					$email_data['product'] = $obj_data['product'];
				}
				if ( isset ( $obj_data['staff'] ) ) {
					$email_data['staff'] = $obj_data['staff'];
				}
			}
			return $email_data;
		}

		/**
		 * Remove product/staff from the mailbox account data
		 *
		 * @param type $email_data_key
		 * @param type $email_data_value
		 */
		public function delete_data_from_mailbox_account( $email_data_key, $email_data_value ) {
			$emails = rtmb_get_module_mailbox_emails( RTBIZ_HD_TEXT_DOMAIN );

			foreach ( $emails as $email ) {
				$mailbox_data = rtmb_get_module_mailbox_email( $email, RTBIZ_HD_TEXT_DOMAIN );
				if ( ! empty ( $mailbox_data ) ) {
					$mailbox_data = maybe_unserialize( $mailbox_data->email_data );
					if ( ! empty ( $mailbox_data[ $email_data_key ] ) ) {
						if ( $mailbox_data[ $email_data_key ] == $email_data_value ) {
							$mailbox_data[ $email_data_key ] = 0;
						}
							rtmb_set_module_mailbox_data( RTBIZ_HD_TEXT_DOMAIN, $email, $mailbox_data );
					}
				}
			}
		}

		/**
		 * Remove product from mailbox data which product going to deleted
		 *
		 * @param type $term_id
		 * @param type $tt_id
		 * @param type $taxonomy
		 */
		public function delete_mailbox_product( $term_id, $tt_id, $taxonomy ) {
			$this->delete_data_from_mailbox_account( 'product', $term_id );
		}

		/**
		 * Remove staff from mailbox data which staff going to delete
		 *
		 * @param type $user_id
		 */
		public function delete_mailbox_employee( $user_id ) {
			$this->delete_data_from_mailbox_account( 'staff', $user_id );
		}

	}

}
