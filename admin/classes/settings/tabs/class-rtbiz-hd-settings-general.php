<?php

/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/11/15
 * Time: 10:52 AM
 */
if ( ! class_exists( 'rtBiz_HD_Settings_General ' ) ) :
	class rtBiz_HD_Settings_General extends rtBiz_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->id    = 'rtbiz_ticket_general';
			$this->label = __( 'General', RTBIZ_HD_TEXT_DOMAIN );
			add_filter( 'rtbiz_settings_tabs_array', array( $this, 'add_settings_page' ) );
			add_action( 'rtbiz_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'rtbiz_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'rtbiz_update_option_values', array( $this, 'product_sync' ) );
		}

		public function product_sync( $option ) {

			if ( ! isset( $option['rtbiz_product_plugin'] ) ) {
				return;
			}

			$old_value = get_option( 'rtbiz_product_plugin' );

			$new_value = $option['rtbiz_product_plugin'];

			$diff = array_diff_assoc( $new_value, $old_value );
			if ( ! empty( $diff ) ) {
				update_option( 'rt_product_plugin_sync', 'true' );
			}
		}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
		public function get_settings() {


			$args = array(
				'post_type'  => array( 'page' ),
				'pagination' => false,
			);

			// The Query
			$pages   = new WP_Query( $args );
			$options = array();
			foreach ( $pages->get_posts() as $page ) {
				$options[ $page->ID ] = $page->post_title;
			}
			$products_page_link = '<a href="' . admin_url( 'edit-tags.php?taxonomy=' . Rt_Products::$product_slug . '&post_type=' . Rtbiz_HD_Module::$post_type ) . '"> ' . __( 'Products Section.' ) . '</a>';

			$users_options = array();
			$users         = Rtbiz_HD_Utils::get_hd_rtcamp_user();

			foreach ( $users as $user ) {
				$users_options[ $user->ID ] = $user->display_name;
			}

			$admins = get_users( array( 'role' => 'administrator' ) );
			if ( ! empty( $admins ) ) {
				$default_assignee = $admins[0];
				$default_assignee = strval( $default_assignee->ID );
			} else {
				$default_assignee = strval( 1 );
			}

			$settings = apply_filters( 'rtbiz_hd_general_settings', array(

				array(
					'title' => __( 'General Options', RTBIZ_HD_TEXT_DOMAIN ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options'
				),
				array(
					'title'    => __( 'Support Page', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_settings_support_page',
					'desc_tip' => __( 'Select Page for Product Support', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'Add <strong>[rtbiz_hd_support_form]</strong> shortcode to any page. Select that page in dropdown above. That page from now used to handle support requests from web interface.' ),
					'type'     => 'select',
					'options'  => $options,
				),
				array(
					'title'         => __( 'Connected Store', RTBIZ_HD_TEXT_DOMAIN ),
					'id'            => 'rtbiz_product_plugin[]',
					'default'       => '',
					'type'          => 'multicheckbox',
					'options'  => array(
						'woocommerce'  => __( 'woocommerce', RTBIZ_HD_TEXT_DOMAIN ),
						'edd' => __( 'edd', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload'      => true
				),
				array(
					'title'    => __( 'Default Assignee', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_settings_default_user',
					'desc_tip' => __( 'Select user for HelpDesk ticket Assignee', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'To select dedicated assignee for a product, visit the ' ) . $products_page_link,
					'type'     => 'select',
					'options'  => $users_options,
					'default'  => $default_assignee,
				),
				array(
					'title'    => __( 'Unique Hash URLs for Tickets', RTBIZ_HD_TEXT_DOMAIN ),
					'desc'     => __( 'If enabled, this will generate a unique Hash URL for all the tickets through which tickets can be accessed in the web interface. This unique URLs will be sent in all emails of Helpdesk. Tickets can be accessed from the default WordPress permalinks as well.', RTBIZ_HD_TEXT_DOMAIN ),
					'desc_tip' => __( 'Please flush the permalinks after enabling this option.', RTBIZ_HD_TEXT_DOMAIN ),
					'id'       => 'rthd_settings_enable_ticket_unique_hash',
					'default'  => 'off',
					'type'     => 'radio',
					'options'  => array(
						'on'  => __( 'On', RTBIZ_HD_TEXT_DOMAIN ),
						'off' => __( 'Off', RTBIZ_HD_TEXT_DOMAIN ),
					),
					'autoload' => true
				),
				array( 'type' => 'sectionend', 'id' => 'general_options' ),

			) );

			return apply_filters( 'rtbiz_get_settings_' . $this->id, $settings );
		}


	}
endif;

return new rtBiz_HD_Settings_General();
