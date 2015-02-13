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
if ( ! class_exists( 'Redux_Framework_Helpdesk_Config' ) ) {

	class Redux_Framework_Helpdesk_Config {

		public $args = array();
		public $sections = array();
		public $ReduxFramework;
		static $page_slug = 'rthd-settings';

		static $hd_opt = 'redux_helpdesk_settings';

		public function __construct() {

			if ( ! class_exists( 'ReduxFramework' ) ) {
				return;
			}
			// hook priority 25 because rtBiz email model is on after_theme 20 and we can not get 'rt_get_all_system_emails' before that
			add_action( 'p2p_init', array( $this, 'init_settings' ), 30 );
		}




		public function init_settings() {
			// Set the default arguments
			$this->set_arguments();

			// Set a few help tabs so you can see how it's done
			//			$this->set_helptabs();

			// Create the sections and fields
			$this->set_sections();

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
				'title'  => __( 'Section via hook', 'redux-framework-demo' ),
				'desc'   => __( '<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', 'redux-framework-demo' ),
				'icon'   => 'el-icon-paper-clip',
				// Leave this as a blank section, no options just some intro text set above.
				'fields' => array()
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
				remove_filter( 'plugin_row_meta', array( ReduxFrameworkPlugin::instance(), 'plugin_metalinks', ), null, 2 );

				// Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
				remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
			}

		}

		public function set_sections() {
			//			$reply_by_email = new RT_HD_Setting_Inbound_Email();
			$author_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			$editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );
			$admin_cap  = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );

			$users         = Rt_HD_Utils::get_hd_rtcamp_user();
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

			$system_emails = rt_get_mpdule_mailbox_emails( RT_HD_TEXT_DOMAIN );

			$mailbox_options = array();
			foreach( $system_emails as $email ) {

				$mailbox_options[ $email ] = $email;
			}
			$is_mailbox_configured = ( ! empty( $system_emails ) );

			// ACTUAL DECLARATION OF SECTIONS
			$general_fields = array(
				array(
					'id'          => 'rthd_menu_label',
					'type'        => 'text',
					'title'       => __( 'Menu Label' ),
					'subtitle'    => __( 'Menu Label Identity for the Plugin.' ),
					'desc'        => __( 'This label will be used for the Menu Item label for Helpdesk' ),
					'default'     => __( 'Helpdesk' ),
					'placeholder' => __( 'rtBiz Helpdesk, Support, etc.' ),
				),
				array(
					'id'       => 'rthd_logo_url',
					'type'     => 'media',
					'url'      => true,
					'title'    => __( 'Logo' ),
					'subtitle' => __( 'Logo to be used for all Menu, Submenu, Post Types Menu Icons in Helpdesk.' ),
					'desc'     => __( 'Upload any logo using the WordPress native uploader, preferably with the size of 16x16' ),
					'default'  => array(
						'url' => RT_HD_URL . 'app/assets/img/hd-16X16.png',
					),
				),
				array(
					'id'       => 'rthd_default_user',
					'type'     => 'select',
					'options'  => $users_options,
					'default'  => $default_assignee,
					'title'    => __( 'Default Assignee' ),
					'desc'     => __( 'Default User for Helpdesk ticket Assignee' ),
					'subtitle' => __( 'Select user for HelpDesk ticket Assignee' ),
				),
				array(
					'id'       => 'rthd_support_page',
					'type'     => 'select',
					'data'     => 'pages',
					'title'    => __( 'Support Page' ),
					'desc'     => __( 'Add [rt_hd_support_form] shortcode to add support from to a page and select this page in the drop down. This page will then be used to handle new support requests from front-end.' ),
					'subtitle' => __( 'Select Page for Product Support' ),
				),
				array(
					'id'       => 'rthd_enable_ticket_unique_hash',
					'type'     => 'switch',
					'title'    => __( 'Enable Unique Hash URLs for Tickets' ),
					'subtitle' => __( 'Please flush the permalinks after enabling this option.' ),
					'desc'     => __( 'If enabled, this will generate a unique Hash URL for all the tickets through which tickets can be accessed in the front end. This unique URLs will be sent in all emails of Helpdesk. Tickets can be accessed from the default WordPress permalinks as well.' ),
					'default'  => false,
					'on'       => __( 'Enable' ),
					'off'      => __( 'Disable' ),
				),
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-cogs',
				'title'       => __( 'General' ),
				'permissions' => $admin_cap,
				'fields'      => $general_fields,
			);

			$redirect_url = get_option( 'rthd_googleapi_redirecturl' );
			if ( ! $redirect_url ) {
				$redirect_url = admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings' );
				update_option( 'rthd_googleapi_redirecturl', $redirect_url );
			}
			$email_fields = array();

			array_push( $email_fields, array(
				'id'      => 'rt_hd_Mailboxes_setup',
				'type'    => 'callback',
				'title'   => 'Mailboxes Setup',
				'subtitle' => __( 'Helpdesk Configured Mailbox(s)' ),
				'desc'    => 'Following mailboxes have been configured for Helpdesk. Emails from these mailboxes will be parsed and Helpdesk will use them to create new ticket / add new followup accordingly. You can configure these mailboxes from <a href="'.add_query_arg( 'page', RT_BIZ_Configuration::$page_slug, admin_url( 'admin.php' ) ).'"rtBiz</a>',
				'callback' => 'rthd_mailbox_setup_view',
			) );

			array_push( $email_fields, array(
					'id'       => 'rthd_outgoing_email_from_name',
					'title'    => __( 'Outgoing Emails\' FROM Name' ),
					'subtitle' => __( 'Outgoing System Name used for all Helpdesk Communication' ),
					'desc'     => sprintf( '%s.', __( 'System Name to be used for outbound emails. This Name will be used as FROM: name < email address > for all outgoing emails' ) ),
					'type'     => 'text',
					'default'  => get_bloginfo(),
				) );

			if ( $is_mailbox_configured ){
				array_push( $email_fields, array(
					'id'       => 'rthd_outgoing_email_mailbox',
					'title'    => __( 'Outgoing Emails\' Mailbox' ),
					'subtitle' => __( 'Mailbox to be used to send outgoing emails/notifications.' ),
					'desc'     => sprintf( '%s.', __( 'Choose any one email from the configured mailboxes.' ) ),
					'type'     => 'select',
					'options'  => $mailbox_options,
				) );
			}
			else {
				array_push( $email_fields, array(
					'id'       => 'rthd_outgoing_email_from_address',
					'title'    => __( 'Outgoing Emails\' FROM Address' ),
					'subtitle' => __( 'Outgoing System Email used for all Helpdesk Communication' ),
					'desc'     => sprintf( '%s <a href="%s">%s</a>. %s.', __( 'WordPress by default sends email using (mostly postfix) FROM/TO value set to Admin Email taken from' ), admin_url( 'options-general.php' ), __( 'here' ), __( 'System Email Address to be used for outbound emails. This Address will be used as FROM: name < email address > for all outgoing emails' ) ),
					'type'     => 'text',
					'default'  => get_option( 'admin_email' ),
					'validate' => 'email',
				) );
			}
				array_push( $email_fields,
				array(
					'id'         => 'rthd_notification_emails',
					'title'      => __( 'Notification Emails' ),
					'subtitle'   => __( 'Email addresses to be notified on events' ),
					'desc'       => __( 'These email addresses will be notified of the events that occurs in HelpDesk Systems. This is a global list. All the subscribers also will be notified along with this list.' ),
					'type'       => 'multi_text',
					'validate'   => 'email',
					'multi'      => true,
					'show_empty' => false,
				),
				array(
					'id'         => 'rthd_blacklist_emails_textarea',
					'title'      => __( 'Blacklist Emails' ),
					'subtitle'   => __( 'Email addresses to be blacklisted from creating tickets / follow-ups.' ),
					'desc'       => __( 'All mails coming from these addresses will be blocked by Helpdesk. It also accept arguments like @example.com, @example.*' ),
					'type'       => 'textarea',
					'multi'      => true,
					'show_empty' => false,
				),
				array(
					'id'       => 'rthd_notification_events',
					'title'    => __( 'Notification Events' ),
					'subtitle' => __( 'Events to be notified to users' ),
					'desc'     => __( 'These events will be notified by Notification Emails.' ),
					'type'     => 'checkbox',
					'options' => array(
						'new_ticket_created'      => __( 'When a New Ticket is created.' ),
						'new_comment_added'       => __( 'When a New follow up is added to a Ticket.' ),
						'followup_edited'         => __( 'When a follow up is edited' ),
						'followup_deleted'        => __( 'When a follow up is deleted' ),
						'status_metadata_changed' => __( 'When any status or metadata changed for a Ticket.' ),
						'new_ticket_assigned'     => __( 'When new ticket assigned' ),
						'new_ticket_reassigned'   => __( 'When new ticket reassigned' ),
						'ticket_subscribed'       => __( 'When new ticket subscribed' ),
						'ticket_unsubscribed'     => __( 'When new ticket unsubscribed' ),
					),
				)
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-envelope',
				'title'       => __( 'Mail Setup' ),
				'permissions' => $admin_cap,
				'fields'      => $email_fields,
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-edit',
				'title'       => __( 'Customize Notification Emails ' ),
				'desc'     => __( 'You can use {ticket_title} as a placeholder.<p><b>Suggestion:</b> Use same title for all mail types to let users have all emails in single thread.</p>' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'       => 'rthd_new_ticket_email_title',
						'type'     => 'text',
						'title'    => __( 'New ticket is created' ),
						'subtitle' => __( 'Title when a new ticket is created' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_update_ticket_email_title',
						'type'     => 'text',
						'title'    => __( 'Ticket is updated' ),
						'subtitle' => __( 'Title when a ticket is updated' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_ticket_assign_email_title',
						'type'     => 'text',
						'title'    => __( 'New ticket is assigned' ),
						'subtitle' => __( 'Title when new ticket is assigned to a user' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_ticket_reassign_email_title',
						'type'     => 'text',
						'title'    => __( 'Ticket is reassigned' ),
						'subtitle' => __( 'Title when an existing ticket is reassigned to another user' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_new_followup_email_title',
						'type'     => 'text',
						'title'    => __( 'New follow up is added' ),
						'subtitle' => __( 'Title when a new follow up is added' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_update_followup_email_title',
						'type'     => 'text',
						'title'    => __( 'Follow up is updated' ),
						'subtitle' => __( 'Title when an existing follow up is updated' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_delete_followup_email_title',
						'type'     => 'text',
						'title'    => __( 'Follow up is deleted' ),
						'subtitle' => __( 'Title when a follow up is deleted' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_ticket_subscribe_email_title',
						'type'     => 'text',
						'title'    => __( 'User subscribes to ticket' ),
						'subtitle' => __( 'Title when a user subscribes to a ticket' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_ticket_unsubscribe_email_title',
						'type'     => 'text',
						'title'    => __( 'User unsubscribes to ticket' ),
						'subtitle' => __( 'Title when a user unsubscribes to a ticket' ),
						'default'  => '{ticket_title}',
					),
					array(
						'id'       => 'rthd_enable_signature',
						'type'     => 'switch',
						'title'    => __( 'Enable email Signature' ),
						'subtitle' => __( 'To enable/disable signature for all email send via rtCamp Helpdesk.' ),
						'default'  => true,
						'on'       => __( 'Enable' ),
						'off'      => __( 'Disable' ),
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
						'title'        => __( 'Email Signature' ),
						'subtitle'     => __( 'Add here Email Signature' ),
						'desc'         => esc_attr( 'You can add email signature here that will be send with every email send with the Helpdesk plugin, Allowed tags are <a> <br> <em> <strong>.' ),
						'validate'     => 'html_custom',
						'default'      => esc_attr( ' -- Sent via rtBiz Helpdesk Plugin' ),
						'allowed_html' => array(
							'a'      => array(
								'href'  => array(),
								'title' => array()
							),
							'br'     => array(),
							'em'     => array(),
							'strong' => array()
						),
					),
				)
			);
			$this->sections[]   = array(
				'title'       => __( 'Contact Importer' ),
				'icon'        => 'el-icon-list-alt',
				'permissions' => $admin_cap,
				//'subsection'  => true,
				'fields'      => array(
					array(
						'id'      => 'rt_biz_import_users_to_contacts',
						'type'    => 'callback',
						'title'   => 'Import WP_Users to Contacts',
						'subtitle' => __( 'Can import contacts to rtbiz contacts from wp_users, You can import selected users to contacts from wp user page.' ),
						'callback' => 'rtbiz_export_wp_users_to_contacts',
					),
				),
			);

			$this->sections[]   = array(
				'title'       => __( 'Gravity Importer' ),
				'icon'        => 'el-icon-list-alt',
				'permissions' => $admin_cap,
				//'subsection'  => true,
				'fields'      => array(
					array(
						'id'      => 'rthd_ticket_import_view',
						'type'    => 'raw',
						'content' => rthd_gravity_importer_view(),
					),
				),
			);

			$this->sections[]   = array(
				'title'       => __( 'Importer Mapper' ),
				'icon'        => 'el-icon-list-alt',
				'permissions' => $admin_cap,
				'subsection'  => true,
				'fields'      => array(
					array(
						'id'      => 'rthd_ticket_import_view',
						'type'    => 'raw',
						'content' => rt_biz_gravity_importer_mapper_view(),
					),
				),
			);

			// Only initiates in case of settings page is getting displayed. Not otherwise
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == self::$page_slug ) {
				ob_start();
				rthd_ticket_import_logs();
				$import_log_content = ob_get_clean();
			} else {
				$import_log_content = '';
			}
			$this->sections[]   = array(
				'title'       => __( 'Import Logs' ),
				'icon'        => 'el-icon-list-alt',
				'permissions' => $admin_cap,
				'subsection'  => true,
				'fields'      => array(
					array(
						'id'      => 'rthd_ticket_import_logs',
						'type'    => 'raw',
						'content' => $import_log_content,
					),
				),
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-key',
				'title'       => __( 'License' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'      => 'rt_hd_activiation',
						'type'    => 'callback',
						'title'        => __( 'Plugin Activation' ),
						'subtitle'     => __( 'Enter License Key and Activate plugin' ),
						'callback' => 'rthd_activation_view',
					)
				),
			);

			$this->sections[] = array(
				'title'   => __( 'Miscellaneous' ),
				'heading' => __( 'Import / Export Settings' ),
				'desc'    => __( 'Import and Export your settings from file, text or URL.' ),
				'icon'    => 'el-icon-refresh',
				'permissions' => $admin_cap,
				'fields'  => array(
					array(
						'id'         => 'rthd_settings_import_export',
						'type'       => 'import_export',
						'title'      => __( 'Import Export' ),
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
			$author_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			$this->args = array(
				// TYPICAL -> Change these values as you need/desire
				'opt_name'           => self::$hd_opt,
				// This is where your data is stored in the database and also becomes your global variable name.
				'display_name'       => __( 'Settings' ),
				// Name that appears at the top of your panel
				'display_version'    => RT_HD_VERSION,
				// Version that appears at the top of your panel
				'menu_type'          => 'submenu',
				//Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
				'allow_sub_menu'     => false,
				// Show the sections below the admin menu item or not
				'menu_title'         => __( 'Settings' ),
				'page_title'         => __( 'Settings' ),
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
				// Show the time the page took to load, etc
				'customizer'         => true,
				// Enable basic customizer support
				//'open_expanded'     => true,                    // Allow you to start the panel in an expanded way initially.
				//'disable_save_warn' => true,                    // Disable the save warning when a user changes a field
				// OPTIONAL -> Give you extra features
				'page_priority'      => null,
				// Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
				'page_parent'        => 'edit.php?post_type=' . esc_attr( Rt_HD_Module::$post_type ),
				// For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
				'page_permissions'   => $author_cap,
				// Permissions needed to access the options panel.
				//'menu_icon' => '', // Specify a custom URL to an icon
				//'last_tab' => '', // Force your panel to always open to a specific tab (by id)
				//'page_icon' => 'icon-themes', // Icon displayed in the admin panel next to your menu_title
				'page_slug'          => self::$page_slug,
				// Page slug used to denote the panel
				'save_defaults'      => true,
				// On load save the defaults to DB before user clicks save or not
				'default_show'       => true,
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
				)
			);
			return true;
		}

	}

}

function rthd_get_redux_post_settings( $post ) {
	// NOTE : Make modifications for what value to return.
	if ( ! isset( $GLOBALS['redux_helpdesk_settings'] ) ) {
		$GLOBALS['redux_helpdesk_settings'] = get_option( 'redux_helpdesk_settings', array() );
	}
	$data = wp_parse_args( get_post_meta( $post->ID, 'redux_helpdesk_settings', true ), $GLOBALS['redux_helpdesk_settings'] );

	return $GLOBALS['redux_helpdesk_settings'];
}

function rthd_ticket_import_logs() {
	global $rt_hd_logs;
	$rt_hd_logs->ui();
}

function rthd_mailbox_setup_view(){
	$module_key = rt_biz_sanitize_module_key( RT_HD_TEXT_DOMAIN );
	rt_biz_mailbox_setup_view( $module_key );
}

function rthd_gravity_importer_view(){
	$module_key = rt_biz_sanitize_module_key( RT_HD_TEXT_DOMAIN );
	return rt_biz_gravity_importer_view( $module_key );
}

function rthd_activation_view(){
	do_action( 'rthelpdesk_addon_license_details' );
}
