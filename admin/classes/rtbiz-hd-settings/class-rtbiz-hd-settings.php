<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//use Zend\Mail\Storage\Imap as ImapStorage;

/**
 *
 * ReduxFramework Sample Config File
 * For full documentation, please visit: https://docs.reduxframework.com
 * @author udit
 *
 * */
if ( ! class_exists( 'Rtbiz_HD_Settings' ) ) {

	class Rtbiz_HD_Settings {

		public $args = array();
		public $sections = array();
		public $ReduxFramework;
		static $page_slug = 'rtbiz-hd-settings';

		static $hd_opt = 'redux_helpdesk_settings';

		public function __construct() {

			if ( ! class_exists( 'ReduxFramework' ) ) {
				return;
			}
			// hook priority 25 because rtBiz email model is on after_theme 20 and we can not get 'rt_get_all_system_emails' before that because of acl needs p2p
			Rtbiz_HD::$loader->add_action( 'p2p_init', $this, 'init_settings', 30 );

			Rtbiz_HD::$loader->add_filter( 'rtbiz_product_setting', $this, 'product_setting' );

			//after redux setting saved
			Rtbiz_HD::$loader->add_action( 'redux/options/' . self::$hd_opt . '/saved', $this, 'on_redux_save', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'rt_mailbox_add_mailbox', $this, 'outbound_mail_setup_on_mailbox_add', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'rt_mailbox_remove_mailbox', $this, 'outbound_mail_setup_on_mailbox_remove', 10, 2 );
			Rtbiz_HD::$loader->add_filter( 'rt_lib_import_page', $this, 'rt_lib_setting_page' );
			add_action( 'redux/construct', array( $this,'redux_disable_dev_mode_plugin'), 10, 1 );
			add_action( 'admin_menu', array( $this,'remove_redux_menu' ), 12 );
			$this->gf_active = is_plugin_active( 'gravityforms/gravityforms.php' );
		}

		public function rt_lib_setting_page( $pages ) {
			if ( $this->gf_active ) {
				$pages[]= self::$page_slug;
			}
			return $pages;
		}

		function remove_redux_menu() {
			remove_submenu_page( 'tools.php','redux-about' );
		}
		function redux_disable_dev_mode_plugin( $redux ) {
			if ( $redux->args['opt_name'] != 'redux_demo' ) {
				$redux->args['dev_mode'] = false;
				$redux->args['forced_dev_mode_off'] = false;
			}
		}

		function product_setting( $setting ) {
			$redux = rtbiz_hd_get_redux_settings();
			if ( ! empty( $redux['product_plugin'] ) ) {
				if ( empty( $redux['product_plugin'] ) ) {
					$redux['product_plugin'] = array();
				}
				$setting = $redux['product_plugin'];
			}

			return $setting;
		}

		public function on_redux_save( $setting, $old_setting ) {
			//removed product sync option
			$diff = array();

			//product sync option updated
			if ( isset( $setting['product_plugin'] ) && isset( $old_setting['product_plugin'] ) && is_array( $setting['product_plugin'] ) && is_array( $old_setting['product_plugin'] ) ) {
				$diff = array_diff( $setting['product_plugin'], $old_setting['product_plugin'] );
				$diff = array_unique( $diff );
			}
			if ( ! empty( $diff ) ) {
				update_option( 'rt_product_plugin_sync', 'true' );
			} else {
				update_option( 'rt_product_plugin_sync', 'false' );
			}

			if ( isset( $setting['rthd_email_signature'] ) ) {
				$setting['rthd_email_signature'] = wp_kses( $setting['rthd_email_signature'], array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array(),
					),
					'br' => array(),
					'em' => array(),
					'strong' => array(),
				) );

				rtbiz_hd_set_redux_settings( 'rthd_email_signature', $setting['rthd_email_signature'] );
			}

			if ( isset( $setting['rthd_auto_response_message'] ) ) {
				$setting['rthd_auto_response_message'] = wp_kses( $setting['rthd_auto_response_message'], array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array(),
					),
					'br' => array(),
					'em' => array(),
					'strong' => array(),
				) );
				rtbiz_hd_set_redux_settings( 'rthd_auto_response_message', $setting['rthd_auto_response_message'] );
			}

		}

		public  function outbound_mail_setup_on_mailbox_add( $email, $module ) {
			if ( RTBIZ_HD_TEXT_DOMAIN == $module ){
				$system_emails = rtmb_get_module_mailbox_emails( RTBIZ_HD_TEXT_DOMAIN );
				if ( count( $system_emails ) == 1 ){
					rtbiz_hd_set_redux_settings( 'rthd_outgoing_email_mailbox',     $email );
				}
			}
		}

		public  function outbound_mail_setup_on_mailbox_remove( $email, $module ) {
			if ( RTBIZ_HD_TEXT_DOMAIN == $module ){
				$system_emails = rtmb_get_module_mailbox_emails( RTBIZ_HD_TEXT_DOMAIN );
				$settings = rtbiz_hd_get_redux_settings();
				if ( count( $system_emails ) <= 0 ){
					rtbiz_hd_set_redux_settings( 'rthd_outgoing_email_mailbox', get_option( 'admin_email' ) );
				}else if ( empty( $settings['rthd_outgoing_email_mailbox'] ) || $email ==  $settings['rthd_outgoing_email_mailbox'] ){
					rtbiz_hd_set_redux_settings( 'rthd_outgoing_email_mailbox', $system_emails[0] );
				}
			}
		}

		public function init_settings() {
			// Set the default arguments
			$this->set_arguments();

			// Set a few help tabs so you can see how it's done
			//			$this->set_helptabs();

			// Create the sections and fields
			if ( ! empty( $_GET['page'] ) && ! empty( $_GET['post_type'] ) && self::$page_slug === $_GET['page'] && Rtbiz_HD_Module::$post_type === $_GET['post_type'] ) {
				$this->set_sections();
			}

			if ( ! isset( $this->args['opt_name'] ) ) { // No errors please
				return;
			}

			// If Redux is running as a plugin, this will remove the demo notice and links
			add_action( 'redux/loaded', array( $this, 'remove_demo' ) );
			// Function to test the compiler hook and demo CSS output.
			// Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.
			// add_filter('redux/options/'.$this->args['opt_name'].'/compiler', array( $this, 'compiler_action' ), 10, 3);
			// Change the arguments after they've been declared, but before the panel is created
			// add_filter('redux/options/'.$this->args['opt_name'].'/args', array( $this, 'change_arguments' ) );
			// Change the default value of a field after it's been set, but before it's been useds
			// add_filter('redux/options/'.$this->args['opt_name'].'/defaults', array( $this,'change_defaults' ) );
			// Dynamically add a section. Can be also used to modify sections/fields
			// add_filter('redux/options/' . $this->args['opt_name'] . '/sections', array($this, 'dynamic_section'));

			$this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );

			return true;
			//add_action("redux/options/{$this->args[ 'opt_name' ]}/register", array( $this, 'test') );

		}


		/**
		 *
		 * Custom function for filtering the sections array. Good for child themes to override or add to the sections.
		 * Simply include this function in the child themes functions.php file.
		 *
		 * NOTE: the defined constants for URLs, and directories will NOT be available at this point in a child theme,
		 * so you must use get_template_directory_uri() if you want to use any of the built in icons
		 * */
		function dynamic_section( $sections ) {
			//$sections = array();
			$sections[] = array(
				'title'  => __( 'Section via hook', 'rtbiz-helpdesk' ),
				'desc'   => __( '<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', 'rtbiz-helpdesk' ),
				'icon'   => 'el-icon-paper-clip',
				// Leave this as a blank section, no options just some intro text set above.
				'fields' => array(),
			);

			return $sections;
		}

		/**
		 *
		 * Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.
		 * */
		function change_arguments( $args ) {
			//$args['dev_mode'] = true;

			return $args;
		}

		/**
		 *
		 * Filter hook for filtering the default value of any given field. Very useful in development mode.
		 * */
		function change_defaults( $defaults ) {
			$defaults['str_replace'] = 'Testing filter hook!';

			return $defaults;
		}

		// Remove the demo link and the notice of integrated demo from the redux-framework plugin
		function remove_demo() {

			// Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
			if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
				remove_filter( 'plugin_row_meta', array( ReduxFrameworkPlugin::instance(), 'plugin_metalinks' ), null, 2 );

				// Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
				remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
			}

		}

		public function set_sections() {
			//			$reply_by_email = new RT_HD_Setting_Inbound_Email();
			$author_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
			$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );
			$admin_cap  = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'admin' );

			$users         = Rtbiz_HD_Utils::get_hd_rtcamp_user();
			$users_options = array();

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

			$system_emails = rtmb_get_module_mailbox_emails( RTBIZ_HD_TEXT_DOMAIN );

			$mailbox_options = array();
			foreach ( $system_emails as $email ) {
				$mailbox_options[ $email ] = $email;
			}

			$products_page_link = '<a href="' . admin_url( 'edit-tags.php?taxonomy=' . Rt_Products::$product_slug . '&post_type=' . Rtbiz_HD_Module::$post_type ) . '"> ' . __( 'Products Section.', 'rtbiz-helpdesk' ) . '</a>';
			// ACTUAL DECLARATION OF SECTIONS
			$general_fields = array(
				array(
					'id'       => 'rthd_support_page',
					'type'     => 'select',
					'data'     => 'pages',
					'title'    => __( 'Support Page', 'rtbiz-helpdesk' ),
					'desc' => __( 'Add <strong>[rtbiz_hd_support_form]</strong> shortcode to any page. Select that page in dropdown above. That page from now used to handle support requests from web interface.', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'Select Page for Product Support', 'rtbiz-helpdesk' ),
				),
				array(
					'id'       => 'product_plugin',
					'title'    => __( 'Connected Store', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'Select the plugin you want to connect Helpdesk with ', 'rtbiz-helpdesk' ),
					'desc' => __( 'All the existing and future products will be imported for the selected plugin. You can also create custom products from ', 'rtbiz-helpdesk' ) . $products_page_link,
					'type'     => 'checkbox',
					'options'  => array(
						'woocommerce' => __( 'WooCommerce', 'rtbiz-helpdesk' ),
						'edd'         => __( 'Easy Digital Download', 'rtbiz-helpdesk' ),
					),
					'default'  => '',
				),
				array(
					'id'       => 'rthd_default_user',
					'type'     => 'select',
					'options'  => $users_options,
					'default'  => $default_assignee,
					'title'    => __( 'Default Assignee', 'rtbiz-helpdesk' ),
					'desc'     => __( 'To select dedicated assignee for a product, visit the ', 'rtbiz-helpdesk' ) . $products_page_link,
					'subtitle' => __( 'Select user for HelpDesk ticket Assignee', 'rtbiz-helpdesk' ),
				),
				array(
					'id'       => 'rthd_enable_ticket_unique_hash',
					'type'     => 'switch',
					'title'    => __( 'Unique Hash URLs for Tickets', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'Please flush the permalinks after enabling this option.', 'rtbiz-helpdesk' ),
					'desc'     => __( 'If enabled, this will generate a unique Hash URL for all the tickets through which tickets can be accessed in the web interface. This unique URLs will be sent in all emails of Helpdesk. Tickets can be accessed from the default WordPress permalinks as well.', 'rtbiz-helpdesk' ),
					'default'  => false,
					'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
					'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
				),
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-cogs',
				'title'       => __( 'General', 'rtbiz-helpdesk' ),
				'permissions' => $admin_cap,
				'fields'      => $general_fields,
			);

			if ( ! current_user_can( $admin_cap ) ) {
				$this->sections[] = array(
					'icon'        => 'el-icon-cogs',
					'title'       => __( 'Note', 'rtbiz-helpdesk' ),
					'permissions' => $editor_cap,
					'fields'      => array(
						array(
							'id'      => 'rt_hd_no_access',
							'type'    => 'raw',
							'content' => rtbiz_hd_no_access_redux(),
						),
					),
				);
			}

			$redirect_url = get_option( 'rtbiz_hd_googleapi_redirecturl' );
			if ( ! $redirect_url ) {
				$redirect_url = admin_url( 'edit.php?post_type=' . Rtbiz_HD_Module::$post_type . '&page=rthd-settings' );
				update_option( 'rtbiz_hd_googleapi_redirecturl', $redirect_url );
			}

			$this->sections[] = array(
				'icon'        => 'el-icon-group',
				'title'       => __( 'Setup Your Team', 'rtbiz-helpdesk' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'      => 'rthd_team_setup',
						'type'    => 'raw',
						'content' => rtbiz_hd_get_setup_team_ui(),
					),
				),
			);

			$email_fields = array();

			array_push( $email_fields, array(
				'id'       => 'rthd_Mailboxes_setup',
				'type'     => 'callback',
				'title'    => 'Mailboxes Setup',
				'subtitle' => __( 'Helpdesk Configured Mailbox(s)', 'rtbiz-helpdesk' ),
				'callback' => 'rtbiz_hd_mailbox_setup_view',
			) );
			if ( ! empty( $system_emails ) ) {

				array_push( $email_fields, array(
					'id'       => 'rthd_email_support',
					'title'    => __( 'Email support', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'Allows customer to create tickets and reply tickets from configured mailbox.', 'rtbiz-helpdesk' ),
					'type'     => 'switch',
					'default'  => true,
					'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
					'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
				));
				array_push( $email_fields, array(
					'id'       => 'section-email-support-start',
					'type'     => 'section',
					'indent'   => true, // Indent all options below until the next 'section' option is set.
					'required' => array( 'rthd_email_support', '=', 1 ),
				));
				/*array_push( $email_fields, array(
					'id'       => 'rthd_email_only_support_user_not_allowed',
					'title'    => __( 'Email only support User access' ),
					'subtitle' => __( 'To allow user access to web interface of ticket web' ),
					'type'     => 'switch',
					'default'  => false,
					'on'       => __( 'Enable' ),
					'off'      => __( 'Disable' ),
					'required' => array( 'rthd_email_support', '=', 1 ),
				) );*/
				array_push( $email_fields, array(
					'id'       => 'rthd_enable_mailbox_reading',
					'type'     => 'switch',
					'title'    => __( 'Mailbox Reading', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'To enable/disable Mailbox Reading', 'rtbiz-helpdesk' ),
					'default'  => true,
					'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
					'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
					'required' => array( 'rthd_email_support', '=', 1 ),
				) );

				array_push( $email_fields, array(
					'id'       => 'rthd_reply_via_email',
					'type'     => 'switch',
					'title'    => __( 'Reply Via Email', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'To enable/disable Reply Via Email', 'rtbiz-helpdesk' ),
					'default'  => true,
					'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
					'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
					'required' => array( 'rthd_enable_mailbox_reading', '=', 1 ),
				) );
			}

			array_push( $email_fields, array(
				'id'     => 'section-email-support-end',
				'type'   => 'section',
				'indent' => false, // Indent all options below until the next 'section' option is set.
			) );
			array_push( $email_fields, array(
				'id'       => 'rthd_web_support',
				'title'    => __( 'Web support', 'rtbiz-helpdesk' ),
				'subtitle' => __( 'Allows customer to create support tickets and reply tickets from web interface.', 'rtbiz-helpdesk' ),
				'type'     => 'switch',
				'default'  => true,
				'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
				'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
			));
			if ( ! empty( $system_emails ) ) {
				array_push( $email_fields, array(
					'id'       => 'rthd_outgoing_email_mailbox',
					'title'    => __( 'Outgoing Emails Mailbox', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'Mailbox to be used to send outgoing emails/notifications.', 'rtbiz-helpdesk' ),
					'desc'     => sprintf( '%s.', __( 'Choose any one email from the configured mailboxes.', 'rtbiz-helpdesk' ) ),
					'type'     => 'select',
					'options'  => $mailbox_options,
				) );

				if ( count( $mailbox_options ) >= 2 ) {
					array_push( $email_fields, array(
						'id'       => 'rthd_outgoing_via_same_email',
						'type'     => 'switch',
						'title'    => __( 'Outgoing Emails From Same Mailbox', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'To enable/disable outgoing emails from same mailbox', 'rtbiz-helpdesk' ),
						'default'  => true,
						'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
						'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
						'required' => array( 'rthd_enable_mailbox_reading', '=', 1 ),
					) );
				}

			} else {
				$domain_name = preg_replace( '/^www\./', '', $_SERVER['SERVER_NAME'] );
				$domain_name = 'noreply@'.$domain_name;
				array_push( $email_fields, array(
					'id'       => 'rthd_outgoing_email_mailbox',
					'title'    => __( 'Outgoing Emails \'From\' Address', 'rtbiz-helpdesk' ),
					'subtitle' => __( 'Outgoing System Email used for all Helpdesk Communication', 'rtbiz-helpdesk' ),
//					'desc'     => sprintf( '%s <a href="%s">%s</a>. %s.', __( 'WordPress by default sends email using (mostly postfix) FROM/TO value set to Admin Email taken from' ), admin_url( 'options-general.php' ), __( 'here' ), __( 'System Email Address to be used for outbound emails. This Address will be used as FROM: name < email address > for all outgoing emails' ) ),
					'desc'     => sprintf( '%s.', __( 'This Address will be used as FROM: name < email address > for all outgoing emails', 'rtbiz-helpdesk' ) ),
					'type'     => 'text',
					'default'  => $domain_name,
					'validate' => 'email',
				) );

			}

			array_push( $email_fields, array(
				'id'       => 'rthd_outgoing_email_from_name',
				'title'    => __( 'Outgoing Emails \'From\' Name', 'rtbiz-helpdesk' ),
				'subtitle' => __( 'Outgoing System Name used for all Helpdesk Communication', 'rtbiz-helpdesk' ),
				'desc'     => sprintf( '%s.', __( 'System Name to be used for outbound emails. This Name will be used as FROM: name < email address > for all outgoing emails', 'rtbiz-helpdesk' ) ),
				'type'     => 'text',
				'default'  => get_bloginfo(),
			) );

			array_push( $email_fields,
				array(
					'id'         => 'rthd_blacklist_emails_textarea',
					'title'      => __( 'Blacklist Emails', 'rtbiz-helpdesk' ),
					'subtitle'   => __( 'Email addresses to be blacklisted from creating tickets / follow-ups.', 'rtbiz-helpdesk' ),
					'desc'       => __( 'All mails coming from these addresses will be blocked by Helpdesk. It also accept arguments like *@example.com, @example.*, Keep each email in new line without comma(,).', 'rtbiz-helpdesk' ),
					'type'       => 'textarea',
					'multi'      => true,
					'show_empty' => false,
				)
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-envelope',
				'title'       => __( 'Mail Setup', 'rtbiz-helpdesk' ),
				'permissions' => $admin_cap,
				'fields'      => $email_fields,
			);

			/*if ( rtbiz_is_email_template_addon_active() && rtbiz_is_email_template_on( Rtbiz_HD_Module::$post_type ) ) {
				$this->sections = apply_filters( 'rthd_email_templates_settings', $this->sections );
			}*/
			$this->sections[] = array(
				'icon'        => 'el-icon-edit',
				'title'       => __( 'Notification Emails ', 'rtbiz-helpdesk' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'         => 'rthd_notification_emails',
						'title'      => __( 'Email Addresses', 'rtbiz-helpdesk' ),
						'subtitle'   => __( 'Email addresses to be notified on events', 'rtbiz-helpdesk' ),
						'desc' => __( 'These email addresses will be notified of the events that occurs in HelpDesk Systems. This is a global list. All the subscribers will also notified along with this list.', 'rtbiz-helpdesk' ),
						'type'       => 'multi_text',
						'validate'   => 'email',
						'multi'      => true,
						'show_empty' => false,
						'add_text'   => 'Add Emails',
					),
					array(
						'id'       => 'rthd_enable_notification_acl',
						'type'     => 'switch',
						'title'    => __( 'Notification Events', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'To enable/disable Notification', 'rtbiz-helpdesk' ),
						'default'  => true,
						'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
						'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
					),
					array(
						'id'       => 'section-notification_acl-start',
						'type'     => 'section',
						'indent'   => true, // Indent all options below until the next 'section' option is set.
						'required' => array( 'rthd_enable_notification_acl', '=', 1 ),
					),
					array(
						'id'      => 'rthd_notification_acl_client_events',
						'title'   => __( 'Notification Event for Customer ', 'rtbiz-helpdesk' ),
						'type'    => 'checkbox',
						'default' => array(
							'new_ticket_created_mail'   => '1',
							'new_followup_created_mail' => '1',
						),
						'options' => array(
							'new_ticket_created_mail'   => __( 'When a customer creates a ticket via the web form or email', 'rtbiz-helpdesk' ),
							'new_followup_created_mail' => __( 'When a new follow-up is added to a ticket', 'rtbiz-helpdesk' ),
							'new_followup_updated_mail' => __( 'When any follow-up is edited by staff', 'rtbiz-helpdesk' ),
							'new_followup_deleted_mail' => __( 'When a follow-up is deleted by staff/customer', 'rtbiz-helpdesk' ),
							//'new_customer_accoutn_created_mail'	=> __( 'When a customer creates a ticket for the first time and an account is created automatically for them' ),
							//'ticket_solved_mail'	=> __( 'When a ticket status is changed to solved' ),
						),
					),
					array(
						'id'      => 'rthd_notification_acl_assignee_events',
						'title'   => __( 'Notification Event for Assignee', 'rtbiz-helpdesk' ),
						'type'    => 'checkbox',
						'default' => array(
							'new_ticket_created_mail'              => '1',
							'new_followup_created_mail'            => '1',
							'new_followup_updated_mail'            => '1',
							'new_followup_deleted_mail'            => '1',
							'ticket_reassigned_mail'               => '1',
							'new_staff_only_followup_created_mail' => '1',
							'ticket_updated_mail'                  => '1',
						),
						'options' => array(
							'new_ticket_created_mail'              => __( 'When a customer creates a ticket via the web form or email', 'rtbiz-helpdesk' ),
							'new_followup_created_mail'            => __( 'When a New follow-up is added to a ticket', 'rtbiz-helpdesk' ),
							'new_followup_updated_mail'            => __( 'When a follow-up is edited by staff/customer', 'rtbiz-helpdesk' ),
							'new_followup_deleted_mail'            => __( 'When a follow-up is deleted by staff/customer', 'rtbiz-helpdesk' ),
							'ticket_reassigned_mail'               => __( 'When a ticket is reassigned', 'rtbiz-helpdesk' ),
							'new_staff_only_followup_created_mail' => __( 'When a staff-only follow-up is added/edited on a ticket', 'rtbiz-helpdesk' ),
							'ticket_updated_mail'                  => __( 'When any status or metadata changed for a ticket', 'rtbiz-helpdesk' ),
							'new_subscriber_added_mail'            => __( 'When a staff member subscribes to a ticket', 'rtbiz-helpdesk' ),
							'subscriber_removed_mail'              => __( 'When a subscriber is removed from a ticket', 'rtbiz-helpdesk' ),
						),
					),
					array(
						'id'      => 'rthd_notification_acl_staff_events',
						'title'   => __( 'Notification Event for Staff', 'rtbiz-helpdesk' ),
						'type'    => 'checkbox',
						'default' => array(
							'new_ticket_created_mail'              => '1',
							'new_followup_created_mail'            => '1',
							'new_followup_updated_mail'            => '1',
							'new_followup_deleted_mail'            => '1',
							'ticket_reassigned_mail'               => '1',
							'new_staff_only_followup_created_mail' => '1',
							'ticket_updated_mail'                  => '1',
						),
						'options' => array(
							'new_ticket_created_mail'              => __( 'When a customer creates a ticket via the web form or email', 'rtbiz-helpdesk' ),
							'new_followup_created_mail'            => __( 'When a New follow-up is added to a ticket', 'rtbiz-helpdesk' ),
							'new_followup_updated_mail'            => __( 'When a follow-up is edited by staff/customer', 'rtbiz-helpdesk' ),
							'new_followup_deleted_mail'            => __( 'When a follow-up is deleted by staff/customer', 'rtbiz-helpdesk' ),
							'ticket_reassigned_mail'               => __( 'When a ticket is reassigned', 'rtbiz-helpdesk' ),
							'new_staff_only_followup_created_mail' => __( 'When a staff-only follow-up is added/edited on a ticket', 'rtbiz-helpdesk' ),
							'ticket_updated_mail'                  => __( 'When any status or metadata changed for a ticket', 'rtbiz-helpdesk' ),
							'new_subscriber_added_mail'            => __( 'When a staff member subscribes to a ticket', 'rtbiz-helpdesk' ),
							'subscriber_removed_mail'              => __( 'When a subscriber is removed from a ticket', 'rtbiz-helpdesk' ),
						),
					),
					array(
						'id'      => 'rthd_notification_acl_group_events',
						'title'   => __( 'Notification Event for Group', 'rtbiz-helpdesk' ),
						'type'    => 'checkbox',
						'default' => array(
							'new_ticket_created_mail'              => '1',
							'new_followup_created_mail'            => '1',
							'new_followup_updated_mail'            => '1',
							'new_followup_deleted_mail'            => '1',
							'ticket_reassigned_mail'               => '1',
							'new_staff_only_followup_created_mail' => '1',
							'ticket_updated_mail'                  => '1',
						),
						'options' => array(
							'new_ticket_created_mail'              => __( 'When a customer creates a ticket via the web form or email', 'rtbiz-helpdesk' ),
							'new_followup_created_mail'            => __( 'When a New follow-up is added to a ticket', 'rtbiz-helpdesk' ),
							'new_followup_updated_mail'            => __( 'When a follow-up is edited by staff/customer', 'rtbiz-helpdesk' ),
							'new_followup_deleted_mail'            => __( 'When a follow-up is deleted by staff/customer', 'rtbiz-helpdesk' ),
							'ticket_reassigned_mail'               => __( 'When a ticket is reassigned', 'rtbiz-helpdesk' ),
							'new_staff_only_followup_created_mail' => __( 'When a staff-only follow-up is added/edited on a ticket', 'rtbiz-helpdesk' ),
							'ticket_updated_mail'                  => __( 'When any status or metadata changed for a ticket', 'rtbiz-helpdesk' ),
							'new_subscriber_added_mail'            => __( 'When a staff member subscribes to a ticket', 'rtbiz-helpdesk' ),
							'subscriber_removed_mail'              => __( 'When a subscriber is removed from a ticket', 'rtbiz-helpdesk' ),
						),
					),
					array(
						'id'     => 'section-notification_acl-start',
						'type'   => 'section',
						'indent' => false, // Indent all options below until the next 'section' option is set.
					),
					array(
						'id'     => 'section-notification_email-customize-start',
						'type'   => 'section',
						'title'  => __( 'Customize Notification Emails ', 'rtbiz-helpdesk' ),
						'indent' => true, // Indent all options below until the next 'section' option is set.
					),
					array(
						'id'       => 'rthd_enable_signature',
						'type'     => 'switch',
						'title'    => __( 'Email Signature', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'To enable/disable signature for all email send via rtCamp Helpdesk.', 'rtbiz-helpdesk' ),
						'default'  => true,
						'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
						'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
					),
					array(
						'id'       => 'section-email-signature',
						'type'     => 'section',
						'indent'   => true, // Indent all options below until the next 'section' option is set.
						'required' => array( 'rthd_enable_signature', '=', 1 ),
					),
					array(
						'id'           => 'rthd_email_signature',
						'type'         => 'textarea',
						'title'        => __( 'Email Signature', 'rtbiz-helpdesk' ),
						'subtitle'     => __( 'Add here Email Signature', 'rtbiz-helpdesk' ),
						'desc' => esc_attr( 'You can add email signature here that will be sent with every email send by Helpdesk plugin.', 'rtbiz-helpdesk' ), // Allowed tags are <a> <br> <em> <strong>.
						//'validate'     => 'html_custom',
						'default'      => esc_attr( ' -- Sent via rtBiz Helpdesk Plugin' ),
						/*'allowed_html' => array(
							'a'      => array(
								'href'  => array(),
								'title' => array(),
							),
							'br'     => array(),
							'em'     => array(),
							'strong' => array(),
						),*/
					),
					array(
						'id'     => 'section-notification_email-customize-end',
						'type'   => 'section',
						'indent' => false, // Indent all options below until the next 'section' option is set.
					),
				),
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-magic',
				'title'       => __( 'Advanced Settings', 'rtbiz-helpdesk' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					/*					array(
											'id'       => 'rthd_enable_auto_wp_user_create',
											'type'     => 'switch',
											'title'    => __( 'Auto Wordpress User Create' ),
											'subtitle' => __( 'Create new wordpress user on adding of new customer' ),
											'default'  => false,
											'on'       => __( 'Enable' ),
											'off'      => __( 'Disable' ),
					),*/
					array(
						'id'       => 'rthd_enable_auto_assign',
						'type'     => 'switch',
						'title'    => __( 'Auto Assign Tickets', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'To auto assign a ticket to staff on reply', 'rtbiz-helpdesk' ),
						'default'  => false,
						'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
						'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
					),
					array(
						'id'       => 'section-auto-assign-start',
						'type'     => 'section',
						'indent'   => true, // Indent all options below until the next 'section' option is set.
						'required' => array( 'rthd_enable_auto_assign', '=', 1 ),
					),
					array(
						'id'      => 'rthd_auto_assign_events',
						'title'   => __( 'Event For Auto assign', 'rtbiz-helpdesk' ),
						'default' => 'on_first_followup',
						'type'    => 'radio',
						'options' => array(
							'on_first_followup' => __( 'When first follow-up is added to a ticket by any staff member.', 'rtbiz-helpdesk' ),
							'on_any_followup'   => __( 'When any follow-up is added to a ticket by any staff member.', 'rtbiz-helpdesk' ),
						),
					),
					array(
						'id'     => 'section-auto-assign-end',
						'type'   => 'section',
						'indent' => false,
					),
					array(
						'id'       => 'rthd_enable_auto_response',
						'type'     => 'switch',
						'title'    => __( 'Auto Response', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'To enable/disable auto response feature', 'rtbiz-helpdesk' ),
						'default'  => false,
						'on'       => __( 'Enable', 'rtbiz-helpdesk' ),
						'off'      => __( 'Disable', 'rtbiz-helpdesk' ),
					),
					array(
						'id'       => 'section-auto-response-start',
						'type'     => 'section',
						'indent'   => true, // Indent all options below until the next 'section' option is set.
						'required' => array( 'rthd_enable_auto_response', '=', 1 ),
					),
					array(
						'id'       => 'rthd_enable_auto_response_mode',
						'type'     => 'switch',
						'title'    => __( 'Select working shift', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'Day shift / Day-Night Shift', 'rtbiz-helpdesk' ),
						'default'  => true,
						'on'       => __( 'Day Shift', 'rtbiz-helpdesk' ),
						'off'      => __( 'Day-Night Shift', 'rtbiz-helpdesk' ),
					),
					array(
						'id'       => 'section-auto-response-dayshift-start',
						'type'     => 'section',
						'indent'   => true, //Indent all options below until the next 'section' option is set.
						'required' => array(
							array( 'rthd_enable_auto_response_mode', '=', 1 ),
							array( 'rthd_enable_auto_response', '=', 1 ),
						),
					),
					array(
						'id'       => 'rthd_auto_response_dayshift_time',
						'type'     => 'callback',
						'title'    => __( 'Configure working time for dayshift', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'Add hours of operation', 'rtbiz-helpdesk' ),
						'desc'     => '',
						'callback' => 'rtbiz_hd_auto_response_dayshift_view',
					),
					array(
						'id'     => 'section-auto-response-dayshift-end',
						'type'   => 'section',
						'indent' => false,
					),
					array(
						'id'       => 'section-auto-response-nightshift-start',
						'type'     => 'section',
						'indent'   => true, //Indent all options below until the next 'section' option is set.
						'required' => array(
							array( 'rthd_enable_auto_response_mode', '=', 0 ),
							array( 'rthd_enable_auto_response', '=', 1 ),
						),
					),
					array(
						'id'       => 'rthd_auto_response_nightshift_time',
						'type'     => 'callback',
						'title'    => __( 'Configure working time for nightshift', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'Add hours of operation', 'rtbiz-helpdesk' ),
						'desc'     => '',
						'callback' => 'rtbiz_hd_auto_response_daynightshift_view',
					),
					array(
						'id'     => 'section-auto-response-nightshift-end',
						'type'   => 'section',
						'indent' => false,
					),
					array(
						'id'       => 'rthd_autoresponse_weekend',
						'type'     => 'switch',
						'title'    => __( 'Auto-respond on weekends only.', 'rtbiz-helpdesk' ),
						'default'  => false,
						'on'       => __( 'Yes', 'rtbiz-helpdesk' ),
						'off'      => __( 'No', 'rtbiz-helpdesk' ),
						'required' => array( 'rthd_enable_auto_response', '=', 1 ),
					),
					array(
						'id'           => 'rthd_auto_response_message',
						'type'         => 'textarea',
						'title'        => __( 'Auto response message', 'rtbiz-helpdesk' ),
						'desc'         => esc_attr( 'Add a message here that will be sent to the customer when your team is offline. ' ) . 'Use <b>{NextStartingHour}</b> to get next working hours like <b>`Today after 10 pm` or `Monday after 9 AM`</b>', //Allowed tags are <a> <br> <em> <strong>.
						//'validate'     => 'html_custom',
						'default'      => esc_attr( 'We have received your support request. Our support team is currently offline. We will get back at the soonest.' ),
						'required'     => array( 'rthd_enable_auto_response', '=', 1 ),
						/*'allowed_html' => array(
							'a'      => array(
								'href'  => array(),
								'title' => array(),
							),
							'br'     => array(),
							'em'     => array(),
							'strong' => array(),
						),*/
					),
					array(
						'id'     => 'section-auto-response-end',
						'type'   => 'section',
						'indent' => false,
					),
					array(
						'id'      => 'rthd_enable_ticket_adult_content',
						'type'    => 'switch',
						'title'   => __( 'Adult Content Filter', 'rtbiz-helpdesk' ),
						'desc'    => __( 'For customer, a form feature to mark adult content will be enabled. For staff, profile level setting to filter the adult content will be enabled.', 'rtbiz-helpdesk' ),
						'default' => false,
						'on'      => __( 'Enable', 'rtbiz-helpdesk' ),
						'off'     => __( 'Disable', 'rtbiz-helpdesk' ),
					),
				),
			);
//			if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
			if ( $this->gf_active ) {

				$this->sections[] = array(
					'title'       => __( 'Gravity Importer', 'rtbiz-helpdesk' ),
					'icon'        => 'el-icon-list-alt',
					'permissions' => $admin_cap,
					//'subsection'  => true,
					'fields'      => array(
						array(
							'id'      => 'rthd_ticket_import_view',
							'type'    => 'raw',
							'content' => rtbiz_hd_gravity_importer_view(),
						),
					),
				);

				$this->sections[] = array(
					'title'       => __( 'Importer Mapper', 'rtbiz-helpdesk' ),
					'icon'        => 'el-icon-list-alt',
					'permissions' => $admin_cap,
					'subsection'  => true,
					'fields'      => array(
						array(
							'id'      => 'rthd_ticket_import_view',
							'type'    => 'raw',
							'content' => rtbiz_gravity_importer_mapper_view(),
						),
					),
				);
				// Only initiates in case of settings page is getting displayed. Not otherwise
				if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == self::$page_slug ) {
					ob_start();
					rtbiz_hd_ticket_import_logs();
					$import_log_content = ob_get_clean();
				} else {
					$import_log_content = '';
				}
				$this->sections[] = array(
					'title'       => __( 'Import Logs', 'rtbiz-helpdesk' ),
					'icon'        => 'el-icon-list-alt',
					'permissions' => $admin_cap,
					'subsection'  => true,
					'fields'      => array(
						array(
							'id'      => 'rtbiz_hd_ticket_import_logs',
							'type'    => 'raw',
							'content' => $import_log_content,
						),
					),
				);
			}

			$this->sections[] = array(
				'icon'        => 'el-icon-key',
				'title'       => __( 'License', 'rtbiz-helpdesk' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'       => 'rt_hd_activiation',
						'type'     => 'callback',
						'title'    => __( 'Plugin Activation', 'rtbiz-helpdesk' ),
						'subtitle' => __( 'Enter License Key and Activate plugin', 'rtbiz-helpdesk' ),
						'callback' => 'rtbiz_hd_activation_view',
					),
				),
			);
			$status = get_option( 'rtbiz_hd_edd_license_status' );
			$support = new Rtbiz_HD_Support();

			if ( 'valid' == $status ) {
				if ( isset($_POST['rtbiz_hd-submit-request'])){
					$support->submit_request();
					$this->sections[] = array(
						'icon'        => 'el-icon-phone',
						'title'       => __( 'Premium Support', 'rtbiz-helpdesk' ),
						'permissions' => $admin_cap,
						'full_width' => true,
						'fields'      => array(
							array(
								'id'       => 'rt_hd_support',
								'type'     => 'callback',
								'title'    => __( 'Premium Support', 'rtbiz-helpdesk' ),
								'callback' => array( 'Rtbiz_HD_Support', 'support_sent' ),
							),
						),
					);
				}  else {
					$this->sections[] = array(
						'icon'        => 'el-icon-phone',
						'title'       => __( 'Premium Support', 'rtbiz-helpdesk' ),
						'permissions' => $admin_cap,
						'full_width' => true,
						'fields'      => array(
							array(
								'full_width' => true,
								'id'       => 'rt_hd_support',
								'type'     => 'callback',
								'title'    => __( 'Premium Support', 'rtbiz-helpdesk' ),
								'callback' => array( $support, 'call_get_form' ),
							),
						),
					);
				}
			}



			$this->sections[] = array(
				'title'       => __( 'Miscellaneous', 'rtbiz-helpdesk' ),
				'heading'     => __( 'Import / Export Settings', 'rtbiz-helpdesk' ),
				'desc'        => __( 'Import and Export your settings from file, text or URL.', 'rtbiz-helpdesk' ),
				'icon'        => 'el-icon-refresh',
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'         => 'rthd_settings_import_export',
						'type'       => 'import_export',
						'title'      => __( 'Import Export', 'rtbiz-helpdesk' ),
						'subtitle'   => 'Save and restore your options',
						'full_width' => false,
					),
				),
			);

			return true;
		}

		/**
		 *
		 * All the possible arguments for Redux.
		 * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
		 * */
		public function set_arguments() {

			//$theme = wp_get_theme(); // For use with some settings. Not necessary.
			$admin_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'admin' );

			$this->args = array(
				// TYPICAL -> Change these values as you need/desire
				'opt_name'           => self::$hd_opt,
				// This is where your data is stored in the database and also becomes your global variable name.
				'display_name'       => __( 'Settings', 'rtbiz-helpdesk' ),
				// Name that appears at the top of your panel
				'display_version'    => RTBIZ_HD_VERSION,
				// Version that appears at the top of your panel
				'menu_type'          => 'submenu',
				//Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
				'allow_sub_menu'     => false,
				// Show the sections below the admin menu item or not
				'menu_title'         => __( 'Settings', 'rtbiz-helpdesk' ),
				'page_title'         => __( 'Settings', 'rtbiz-helpdesk' ),
				// You will need to generate a Google API key to use this feature.
				// Please visit: https://developers.google.com/fonts/docs/developer_api#Auth
				'google_api_key'     => '',
				// Must be defined to add google fonts to the typography module
				'async_typography'   => true,
				// Use a asynchronous font on the front end or font string
				//'disable_google_fonts_link' => true,                    // Disable this in case you want to create your own google fonts loader
				'admin_bar'          => false,
				// Show the panel pages on the admin bar
				'global_variable'    => '',
				// Set a different name for your global variable other than the opt_name
				'dev_mode'           => false,
			    'forced_dev_mode_off' => false,
			    'disable_tracking'  => false,
				// Show the time the page took to load, etc
				'customizer'         => false,
				// Enable basic customizer support
				//'open_expanded'     => true,                    // Allow you to start the panel in an expanded way initially.
				//'disable_save_warn' => true,                    // Disable the save warning when a user changes a field
				// OPTIONAL -> Give you extra features
				'page_priority'      => null,
				// Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
				'page_parent'        => 'edit.php?post_type=' . esc_attr( Rtbiz_HD_Module::$post_type ),
				// For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
				'page_permissions'   => $admin_cap,
				// Permissions needed to access the options panel.
				//'menu_icon' => '', // Specify a custom URL to an icon
				//'last_tab' => '', // Force your panel to always open to a specific tab (by id)
				//'page_icon' => 'icon-themes', // Icon displayed in the admin panel next to your menu_title
				'page_slug'          => self::$page_slug,
				// Page slug used to denote the panel
				'save_defaults'      => true,
				// On load save the defaults to DB before user clicks save or not
				'default_show'       => false,
				// If true, shows the default value next to each field that is not the default value.
				'default_mark'       => '',
				// What to print by the field's title if the value shown is default. Suggested: *
				'show_import_export' => true,
				// Shows the Import/Export panel when not used as a field.
				// CAREFUL -> These options are for advanced use only
				'transient_time'     => 60 * MINUTE_IN_SECONDS,
				'output'             => true,
				// Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output
				'output_tag'         => true,
				// Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
				// 'footer_credit'     => '',                   // Disable the footer credit of Redux. Please leave if you can help it.
				// FUTURE -> Not in use yet, but reserved or partially implemented. Use at your own risk.
				'database'           => '',
				// possible: options, theme_mods, theme_mods_expanded, transient. Not fully functional, warning!
				'system_info'        => false,
				'ajax_save'         => false,
				// REMOVE
				// HINTS
				'hints'              => array(
					'icon'          => 'icon-question-sign',
					'icon_position' => 'right',
					'icon_color'    => 'lightgray',
					'icon_size'     => 'normal',
					'tip_style'     => array(
						'color'   => 'light',
						'shadow'  => true,
						'rounded' => false,
						'style'   => '',
					),
					'tip_position'  => array(
						'my' => 'top left',
						'at' => 'bottom right',
					),
					'tip_effect'    => array(
						'show' => array(
							'effect'   => 'slide',
							'duration' => '500',
							'event'    => 'mouseover',
						),
						'hide' => array(
							'effect'   => 'slide',
							'duration' => '500',
							'event'    => 'click mouseleave',
						),
					),
				),
			);

			return true;
		}

	}

}
