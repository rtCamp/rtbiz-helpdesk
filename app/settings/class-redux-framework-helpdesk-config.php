<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Zend\Mail\Storage\Imap as ImapStorage;

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

		public function __construct() {

			if ( ! class_exists( 'ReduxFramework' ) ) {
				return;
			}

			add_action( 'plugins_loaded', array( $this, 'init_settings' ), 15 );
			add_action( 'init', array( $this, 'save_imap_servers' ) );
			add_action( 'init', array( $this, 'save_replay_by_email' ) );
		}

		function save_imap_servers( $rthd_imap_servers_changed = null, $rthd_imap_servers = null ) {
			if ( ! ( isset ( $_POST['rthd_imap_servers_changed'] ) && isset( $_POST['rthd_imap_servers'] ) ) ) {
				if ( isset ( $rthd_imap_servers_changed ) && ! empty( $rthd_imap_servers_changed ) ) {
					$_POST['rthd_imap_servers_changed'] = $rthd_imap_servers_changed;
					//	echo "1st";
				}
				if ( isset ( $rthd_imap_servers ) && ! empty( $rthd_imap_servers ) ) {
					$_POST['rthd_imap_servers'] = $rthd_imap_servers;
					//echo "2nd";
				}
			}


			if ( isset( $_POST['rthd_imap_servers_changed'] ) ) {

				global $rt_hd_imap_server_model;
				$old_servers = $rt_hd_imap_server_model->get_all_servers();

				if ( isset( $_POST['rthd_imap_servers'] ) ) {
					$new_servers = $_POST['rthd_imap_servers'];

					// Handle / Update Existing Servers
					foreach ( $old_servers as $id => $server ) {
						if ( isset( $new_servers[ $server->id ] ) ) {
							if ( empty( $new_servers[ $server->id ]['server_name'] ) || empty( $new_servers[ $server->id ]['incoming_imap_server'] ) || empty( $new_servers[ $server->id ]['incoming_imap_port'] ) || empty( $new_servers[ $server->id ]['outgoing_smtp_server'] ) || empty( $new_servers[ $server->id ]['outgoing_smtp_port'] ) ) {
								continue;
							}
							$args = array(
								'server_name'          => $new_servers[ $server->id ]['server_name'],
								'incoming_imap_server' => $new_servers[ $server->id ]['incoming_imap_server'],
								'incoming_imap_port'   => $new_servers[ $server->id ]['incoming_imap_port'],
								'incoming_imap_enc'    => ( isset( $new_servers[ $server->id ]['incoming_imap_enc'] ) && ! empty( $new_servers[ $server->id ]['incoming_imap_enc'] ) ) ? $new_servers[ $server->id ]['incoming_imap_enc'] : null,
								'outgoing_smtp_server' => $new_servers[ $server->id ]['outgoing_smtp_server'],
								'outgoing_smtp_port'   => $new_servers[ $server->id ]['outgoing_smtp_port'],
								'outgoing_smtp_enc'    => ( isset( $new_servers[ $server->id ]['outgoing_smtp_enc'] ) && ! empty( $new_servers[ $server->id ]['outgoing_smtp_enc'] ) ) ? $new_servers[ $server->id ]['outgoing_smtp_enc'] : null,
							);
							$rt_hd_imap_server_model->update_server( $args, $server->id );

						} else {
							$rt_hd_imap_server_model->delete_server( $server->id );
						}
					}

					// New Server in the list
					if ( ! empty( $new_servers['new']['server_name'] ) && ! empty( $new_servers['new']['incoming_imap_server'] ) && ! empty( $new_servers['new']['incoming_imap_port'] ) && ! empty( $new_servers['new']['outgoing_smtp_server'] ) && ! empty( $new_servers['new']['outgoing_smtp_port'] ) ) {

						$args = array(
							'server_name'          => $new_servers['new']['server_name'],
							'incoming_imap_server' => $new_servers['new']['incoming_imap_server'],
							'incoming_imap_port'   => $new_servers['new']['incoming_imap_port'],
							'incoming_imap_enc'    => ( isset( $new_servers[ $server->id ]['incoming_imap_enc'] ) && ! empty( $new_servers[ $server->id ]['incoming_imap_enc'] ) ) ? $new_servers[ $server->id ]['incoming_imap_enc'] : null,
							'outgoing_smtp_server' => $new_servers['new']['outgoing_smtp_server'],
							'outgoing_smtp_port'   => $new_servers['new']['outgoing_smtp_port'],
							'outgoing_smtp_enc'    => ( isset( $new_servers[ $server->id ]['outgoing_smtp_enc'] ) && ! empty( $new_servers[ $server->id ]['outgoing_smtp_enc'] ) ) ? $new_servers[ $server->id ]['outgoing_smtp_enc'] : null,
						);
						$rt_hd_imap_server_model->add_server( $args );

						return true;
					}
				} else {
					foreach ( $old_servers as $server ) {
						$rt_hd_imap_server_model->delete_server( $server->id );
					}
				}
			}
		}

		public function save_replay_by_email() {

			global $rt_hd_settings, $redux_helpdesk_settings;

			if ( isset( $redux_helpdesk_settings ) && isset( $redux_helpdesk_settings['rthd_enable_reply_by_email'] ) && $redux_helpdesk_settings['rthd_enable_reply_by_email'] == 1 && isset( $_POST['rthd_submit_enable_reply_by_email'] ) && $_POST['rthd_submit_enable_reply_by_email'] == 'save' ) {
				if ( isset( $_POST['mail_ac'] ) && is_email( $_POST['mail_ac'] ) ) {
					if ( isset( $_POST['imap_password'] ) ) {
						$token = rthd_encrypt_decrypt( $_POST['imap_password'] );
					} else {
						$token = null;
					}
					if ( isset( $_POST['imap_server'] ) ) {
						$imap_server = $_POST['imap_server'];
					} else {
						$imap_server = null;
					}
					$email_ac   = $rt_hd_settings->get_email_acc( $_POST['mail_ac'] );
					$email_data = null;
					if ( isset( $_POST['mail_folders'] ) && ! empty( $_POST['mail_folders'] ) && is_array( $_POST['mail_folders'] ) && ! empty( $email_ac ) ) {
						$email_data                 = maybe_unserialize( $email_ac->email_data );
						$email_data['mail_folders'] = implode( ',', $_POST['mail_folders'] );
					}
					if ( isset( $_POST['inbox_folder'] ) && ! empty( $_POST['inbox_folder'] ) && ! empty( $email_ac ) ) {
						if ( is_null( $email_data ) ) {
							$email_data = maybe_unserialize( $email_ac->email_data );
						}
						$email_data['inbox_folder'] = $_POST['inbox_folder'];
					}
					$rt_hd_settings->update_mail_acl( $_POST['mail_ac'], $token, maybe_serialize( $email_data ), $imap_server );
				}
				if ( isset( $_REQUEST['email'] ) && is_email( $_REQUEST['email'] ) ) {
					$rt_hd_settings->delete_user_google_ac( $_REQUEST['email'] );
					echo '<script>window.location="' . esc_url( add_query_arg( array(
							'post_type' => Rt_HD_Module::$post_type,
							'page'      => 'rthd-settings',
						), admin_url( 'edit.php' ) ) ) . '";</script>';
					die();
				}
			}
		}

		public function init_settings() {
			// Set the default arguments
			$this->set_arguments();

			// Set a few help tabs so you can see how it's done
			$this->set_helptabs();

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
				remove_filter( 'plugin_row_meta', array(
					ReduxFrameworkPlugin::instance(),
					'plugin_metalinks',
				), null, 2 );

				// Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
				remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
			}

		}

		public function set_sections() {

			$author_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			$editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );
			$admin_cap  = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );

			$users         = get_users();
			$users_options = array();

			foreach ( $users as $user ) {
				$users_options[ $user->ID ] = $user->user_login;
			}

			// ACTUAL DECLARATION OF SECTIONS
			$this->sections[] = array(
				'icon'        => 'el-icon-cogs',
				'title'       => __( 'General' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'          => 'rthd_menu_label',
						'type'        => 'text',
						'title'       => __( 'Menu Label' ),
						'subtitle'    => __( 'Menu Label Identity for the Plugin.' ),
						'desc'        => __( 'This label will be used for the Menu Item label for Helpdesk' ),
						'default'     => __( 'rtHelpdesk' ),
						'placeholder' => __( 'rtHelpdesk, Helpdesk, etc.' ),
					),
					array(
						'id'       => 'rthd_logo_url',
						'type'     => 'media',
						'url'      => true,
						'title'    => __( 'Logo' ),
						'desc'     => __( 'This logo will be used for all the Menu, Submenu, Post Types Menu Icons in Helpdesk.' ),
						'subtitle' => __( 'Upload any logo using the WordPress native uploader, preferrably with the size of 16x16.' ),
						'default'  => array(
							'url' => RT_HD_URL . 'app/assets/img/hd-16X16.png',
						),
					),
					array(
						'id'       => 'rthd_support_page',
						'type'     => 'select',
						'data'     => 'pages',
						'title'    => __( 'Support Page' ),
						'desc'     => __( 'This Page will used for redirect support request in WooCommerce.' ),
						'subtitle' => __( 'Select Page for Product Support' ),

					),
					array(
						'id'       => 'rthd_default_user',
						'type'     => 'select',
						'options'  => $users_options,
						'default'  => '1',
						'title'    => __( 'Default Assignee' ),
						'desc'     => __( 'Default User for HelpDesh ticket Assignee' ),
						'subtitle' => __( 'Select User for Support ticket Assign' ),
					),
				),
			);

			$redirect_url = get_option( 'rthd_googleapi_redirecturl' );
			if ( ! $redirect_url ) {
				$redirect_url = admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings' );
				update_option( 'rthd_googleapi_redirecturl', $redirect_url );
			}

			$this->sections[] = array(
				'icon'        => 'el-icon-envelope',
				'title'       => __( 'Mail Setup' ),
				'permissions' => $admin_cap,
				'fields'      => array(
					array(
						'id'       => 'rthd_outgoing_email_from_address',
						'title'    => __( 'Outgoing Emails\' FROM Address' ),
						'subtitle' => __( 'Outgoing System Email used for all Helpdesk Communication' ),
						'desc'     => sprintf( '%s <a href="%s">%s</a>. %s.', __( 'WordPress by default sends email using (mostly postfix) FROM/TO value set to Admin Email taken from' ), admin_url( 'options-general.php' ), __( 'here' ), __( 'System Email Address to be used for outbound emails. This Address will be used as FROM: email address for all outgoing emails' ) ),
						'type'     => 'text',
						'default'  => get_option( 'admin_email' ),
						'validate' => 'email',
					),
					array(
						'id'       => 'rthd_outgoing_email_delivery',
						'title'    => __( 'Outgoing Emails\' Delivery' ),
						'subtitle' => __( 'This is how the emails will be sent from the Helpdesk system.' ),
						'desc'     => __( '' ),
						'type'     => 'radio',
						'options'  => array(
							'wp_mail'         => __( 'WordPress wp_mail Function' ),
							'user_mail_login' => __( 'User\'s own Mail Login - (Google OAuth / SMTP Login etc. as per configuration)' ),
							'amazon_ses'      => __( 'Amazon SES - (Articles) - Not working as of now' ),
							'google_smtp'     => __( 'Google SMTP - NOT Recommended (Articles) - Not working as of now' )
						),
						'default'  => 'wp_mail',
					),
					array(
						'id'       => 'rthd_notification_emails',
						'title'    => __( 'Notification Emails' ),
						'subtitle' => __( 'Email addresses to be notified on events' ),
						'desc'     => __( 'These email addresses will be notified of the events that occurs in HelpDesk Systems. This is a global list. All the subscribers also will be notified along with this list.' ),
						'type'     => 'multi_text',
						'validate' => 'email',
						'multi'    => true,
					),
					array(
						'id'       => 'rthd_notification_events',
						'title'    => __( 'Notification Events' ),
						'subtitle' => __( 'Events to be notified to users' ),
						'desc'     => __( 'These events will be notified to the Notification Emails whenever they occur.' ),
						'type'     => 'checkbox',
						'options'  => array(
							'new_ticket_created'      => __( 'Whenever a New Ticket is created.' ),
							'new_comment_added'       => __( 'Whenever a New Comment is added to a Ticket.' ),
							'status_metadata_changed' => __( 'Whenever any status or metadata changed for a Ticket.' ),
						),
					),
					array(
						'id'       => 'rthd_enable_reply_by_email',
						'type'     => 'switch',
						'title'    => __( 'Enable Reply by Email' ),
						'subtitle' => __( 'This feature' ),
						'default'  => false,
						'on'       => __( 'Enable' ),
						'off'      => __( 'Disable' ),
					),
					array(
						'id'       => 'section-media-start',
						'type'     => 'section',
						'indent'   => true, // Indent all options below until the next 'section' option is set.
						'required' => array( 'rthd_enable_reply_by_email', '=', 1 ),
					),
					array(
						'id'       => 'rthd_reply_by_email_view',
						'type'     => 'callback',
						'callback' => 'rthd_reply_by_email_view',
					),
				),
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-googleplus',
				'title'       => __( 'Google OAuth' ),
				'permissions' => $admin_cap,
				'subsection'  => true,
				'fields'      => array(
					array(
						'id'       => 'rthd_googleapi_clientid',
						'type'     => 'text',
						'title'    => __( 'Google API Client ID' ),
						'subtitle' => __( 'Subtitle' ),
						'desc'     => sprintf( '<p class="description">%s <a href="https://console.developers.google.com">%s</a>, %s <b>%s</b></p>', __( 'Create an app on' ), __( 'Google API Console' ), __( 'set authorized redirect urls to' ), $redirect_url ),
					),
					array(
						'id'       => 'rthd_googleapi_clientsecret',
						'type'     => 'text',
						'title'    => __( 'Google API Client Secret' ),
						'subtitle' => __( 'Subtitle' ),
					),
				),
			);

			$this->sections[] = array(
				'icon'        => 'el-icon-network',
				'title'       => __( 'IMAP Servers' ),
				'permissions' => $admin_cap,
				'subsection'  => true,
				'fields'      => array(
					array(
						'id'       => 'rthd_imap_servers',
						'type'     => 'callback',
						'title'    => __( 'IMAP Servers' ),
						'subtitle' => __( 'This section lets you configure different IMAP & SMTP Mail Servers e.g., Outlook, Google, Yahoo etc.' ),
						'callback' => 'rthd_imap_servers',
					),
				),
			);

			ob_start();
			rthd_ticket_impoters();
			$importers_content = ob_get_clean();
			$this->sections[]  = array(
				'title'       => __( 'Importers' ),
				'desc'        => __( 'This section lets you import tickets into Helpdesk System from either a CSV file or any other Form Manager Plugin e.g., Gravity Forms.' ),
				'icon'        => 'el-icon-upload',
				'permissions' => $editor_cap,
				//'subsection' => true,
				'fields'      => array(
					array(
						'id'      => 'rthd_ticket_importers',
						'type'    => 'raw',
						'content' => $importers_content,
					),
				),
			);

			ob_start();
			rthd_ticket_import_mapper();
			$import_mapper_content = ob_get_clean();
			$this->sections[]      = array(
				'title'       => __( 'Mapper' ),
				'desc'        => __( 'This section lets you view all the Import Mappings existing for ticket importing into Helpdesk System.' ),
				'icon'        => 'el-icon-map-marker',
				'permissions' => $editor_cap,
				'subsection'  => true,
				'fields'      => array(
					array(
						'id'      => 'rthd_ticket_import_mapper',
						'type'    => 'raw',
						'content' => $import_mapper_content,
					),
				),
			);

			ob_start();
			rthd_ticket_import_logs();
			$import_log_content = ob_get_clean();
			$this->sections[]   = array(
				'title'       => __( 'Import Logs' ),
				'icon'        => 'el-icon-list-alt',
				'permissions' => $editor_cap,
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
				'title'   => __( 'Miscellaneous' ),
				'heading' => __( 'Import / Export Settings' ),
				'desc'    => __( 'Import and Export your settings from file, text or URL.' ),
				'icon'    => 'el-icon-refresh',
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

		public function set_helptabs() {

			// Custom page help tabs, displayed using the help API. Tabs are shown in order of definition.
			$this->args['help_tabs'][] = array(
				'id'      => 'redux-help-tab-1',
				'title'   => __( 'Theme Information 1', 'redux-framework-demo' ),
				'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'redux-framework-demo' )
			);

			$this->args['help_tabs'][] = array(
				'id'      => 'redux-help-tab-2',
				'title'   => __( 'Theme Information 2', 'redux-framework-demo' ),
				'content' => __( '<p>This is the tab content, HTML is allowed.</p>', 'redux-framework-demo' )
			);

			// Set the help sidebar
			$this->args['help_sidebar'] = __( '<p>This is the sidebar content, HTML is allowed.</p>', 'redux-framework-demo' );

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
				'opt_name'           => 'redux_helpdesk_settings',
				// This is where your data is stored in the database and also becomes your global variable name.
				'display_name'       => __( 'Settings' ),
				// Name that appears at the top of your panel
				'display_version'    => RT_HD_VERSION,
				// Version that appears at the top of your panel
				'menu_type'          => 'menu',
				//Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
				'allow_sub_menu'     => true,
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


			// SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
			$this->args['share_icons'][] = array(
				'url'   => 'https://github.com/ReduxFramework/ReduxFramework',
				'title' => 'Visit us on GitHub',
				'icon'  => 'el-icon-github',
				//'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
			);
			$this->args['share_icons'][] = array(
				'url'   => 'https://www.facebook.com/pages/Redux-Framework/243141545850368',
				'title' => 'Like us on Facebook',
				'icon'  => 'el-icon-facebook',
			);
			$this->args['share_icons'][] = array(
				'url'   => 'http://twitter.com/reduxframework',
				'title' => 'Follow us on Twitter',
				'icon'  => 'el-icon-twitter',
			);
			$this->args['share_icons'][] = array(
				'url'   => 'http://www.linkedin.com/company/redux-framework',
				'title' => 'Find us on LinkedIn',
				'icon'  => 'el-icon-linkedin',
			);

			// Panel Intro text -> before the form
			if ( ! isset( $this->args['global_variable'] ) || $this->args['global_variable'] !== false ) {
				if ( ! empty( $this->args['global_variable'] ) ) {
					$v = $this->args['global_variable'];
				} else {
					$v = str_replace( '-', '_', $this->args['opt_name'] );
				}
				$this->args['intro_text'] = sprintf( __( '<p>Did you know that Redux sets a global variable for you? To access any of your saved options from within your code you can use your global variable: <strong>$%1$s</strong></p>', 'redux-framework-demo' ), $v );
			} else {
				$this->args['intro_text'] = __( '<p>This text is displayed above the options panel. It isn\'t required, but more info is always better! The intro_text field accepts all HTML.</p>', 'redux-framework-demo' );
			}

			// Add content after the form.
			$this->args['footer_text'] = __( '<p>This text is displayed below the options panel. It isn\'t required, but more info is always better! The footer_text field accepts all HTML.</p>', 'redux-framework-demo' );

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

function rthd_ticket_impoters() {
	global $rt_hd_gravity_form_importer;
	$rt_hd_gravity_form_importer->ui();
}

function rthd_ticket_import_mapper() {
	global $rt_hd_gravity_form_mapper;
	$rt_hd_gravity_form_mapper->ui();
}

function rthd_ticket_import_logs() {
	global $rt_hd_logs;
	$rt_hd_logs->ui();
}

/**
 * @param $field
 * @param $value
 */
function rthd_reply_by_email_view( $field, $value ) {
	global $rt_hd_settings, $redux_helpdesk_settings, $rt_hd_imap_server_model;

	$rt_hd_settings->update_gmail_ac_count();

	//Google Client
	$redirect_url = get_site_option( 'rthd_googleapi_redirecturl' );
	if ( ! $redirect_url ) {
		$redirect_url = admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings' );
		update_site_option( 'rthd_googleapi_redirecturl', $redirect_url );
	}

	$google_client_id           = $redux_helpdesk_settings['rthd_googleapi_clientid'];
	$google_client_secret       = $redux_helpdesk_settings['rthd_googleapi_clientsecret'];
	$google_client_redirect_url = get_site_option( 'rthd_googleapi_redirecturl', '' );

	if ( ( $google_client_id == '' || $google_client_secret == '' ) ) {
		echo '<div id="error_handle" class="error"><p>Please set google api detail on Google API <a href="' . esc_url( admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings&type=googleApi&tab=admin-settings' ) ) . '">setting</a> page </p></div>';

		return;
	}

	include_once RT_HD_PATH_VENDOR . 'google-api-php-client/Google_Client.php';
	include_once RT_HD_PATH_VENDOR . 'google-api-php-client/contrib/Google_Oauth2Service.php';

	$client = new Google_Client();
	$client->setApplicationName( 'Helpdesk Studio' );
	$client->setClientId( $google_client_id );
	$client->setClientSecret( $google_client_secret );
	$client->setRedirectUri( $google_client_redirect_url );
	$client->setScopes( array(
		'https://mail.google.com/',
		'https://www.googleapis.com/auth/userinfo.email',
		'https://www.googleapis.com/auth/userinfo.profile',
	) );
	$client->setAccessType( 'offline' );
	$oauth2  = new Google_Oauth2Service( $client );
	$user_id = get_current_user_id();

	//Google Oauth redirection
	if ( isset( $_GET['code'] ) ) {
		$client->authenticate();
		$user  = $oauth2->userinfo_v2_me->get();
		$email = filter_var( $user['email'], FILTER_SANITIZE_EMAIL );
		$rt_hd_settings->add_user_google_ac( $client->getAccessToken(), $email, serialize( $user ), $user_id );
		wp_redirect( $google_client_redirect_url );
	}

	if ( isset( $_REQUEST['rthd_add_imap_email'] ) ) {
		if ( isset( $_POST['rthd_imap_user_email'] ) && ! empty( $_POST['rthd_imap_user_email'] ) && isset( $_POST['rthd_imap_user_pwd'] ) && ! empty( $_POST['rthd_imap_user_pwd'] ) && isset( $_POST['rthd_imap_server'] ) && ! empty( $_POST['rthd_imap_server'] ) ) {
			$password    = $_POST['rthd_imap_user_pwd'];
			$email       = $_POST['rthd_imap_user_email'];
			$email_data  = array(
				'email' => $email,
			);
			$imap_server = $_POST['rthd_imap_server'];
			$rt_hd_settings->add_user_google_ac( rthd_encrypt_decrypt( $password ), $email, maybe_serialize( $email_data ), $user_id, 'imap', $imap_server );
		}
	}

	$google_acs = $rt_hd_settings->get_user_google_ac( $user_id );
	$authUrl    = $client->createAuthUrl();

	$results           = Rt_HD_Utils::get_hd_rtcamp_user();
	$arrSubscriberUser = array();
	foreach ( $results as $author ) {
		$arrSubscriberUser[] = array(
			'id'      => $author->ID,
			'label'   => $author->display_name,
			'imghtml' => get_avatar( $author->user_email, 25 ),
		);
	}
	echo '<script> var arr_rtcamper=' . json_encode( $arrSubscriberUser ) . '; </script>';
	$rCount = 0;
	if ( isset( $google_acs ) && ! empty( $google_acs ) ) {
		foreach ( $google_acs as $ac ) {
			$rCount ++;
			$ac->email_data = unserialize( $ac->email_data );
			$email          = filter_var( $ac->email_data['email'], FILTER_SANITIZE_EMAIL );
			$email_type     = $ac->type;
			$imap_server    = $ac->imap_server;
			$mail_folders   = ( isset( $ac->email_data['mail_folders'] ) ) ? $ac->email_data['mail_folders'] : '';
			$mail_folders   = array_filter( explode( ',', $mail_folders ) );
			$inbox_folder   = ( isset( $ac->email_data['inbox_folder'] ) ) ? $ac->email_data['inbox_folder'] : '';

			if ( $ac->type == 'goauth' ) {
				$token = json_decode( $ac->outh_token );
				$client->setAccessToken( $ac->outh_token );
				if ( $client->isAccessTokenExpired() ) {
					$client->refreshToken( $token->refresh_token );
					$user  = $oauth2->userinfo_v2_me->get();
					$email = filter_var( $user['email'], FILTER_SANITIZE_EMAIL );
					if ( isset( $ac->email_data['inbox_folder'] ) ) {
						$user['inbox_folder'] = $ac->email_data['inbox_folder'];
					}
					if ( isset( $ac->email_data['mail_folders'] ) ) {
						$user['mail_folders'] = $ac->email_data['mail_folders'];
					}
					$rt_hd_settings->update_user_google_ac( $client->getAccessToken(), $email, serialize( $user ) );
					$ac->email_data = $user;
					$token          = json_decode( $client->getAccessToken() );
				}
				$token = $token->access_token;
			} else {
				$token = $ac->outh_token;
			}
			if ( isset( $ac->email_data['picture'] ) ) {
				$img          = filter_var( $ac->email_data['picture'], FILTER_VALIDATE_URL );
				$personMarkup = "<img src='$img?sz=96'>";
			} else {
				$personMarkup = get_avatar( $email, 96 );
			} ?>
			<table class="form-table hd-option">
				<tbody>
				<tr>
					<td>
						<input type="hidden" name='mail_ac' value="<?php echo esc_attr( $email ); ?>"/>
						<table class='hd-google-profile-table'>
							<?php if ( $ac->type == 'imap' ) { ?>
								<tr valign="top">
									<td></td>
									<th scope="row"><label><?php _e( 'IMAP Server' ); ?></label></th>
									<td class="long">
										<select required="required" name="imap_server">
											<option value=""><?php _e( 'Select Mail Server' ); ?></option>
											<?php $imap_servers = $rt_hd_imap_server_model->get_all_servers();
											foreach ( $imap_servers as $server ) {
												?>
												<option <?php echo esc_html( ( isset( $ac->imap_server ) && $ac->imap_server == $server->id ) ? 'selected="selected"' : '' ); ?>
													value="<?php echo esc_attr( $server->id ); ?>"><?php echo esc_html( $server->server_name ); ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
								<tr valign="top">
									<td></td>
									<th scope="row"><label><?php _e( 'Password' ); ?></label></th>
									<td class="long"><input required="required" autocomplete="off" type="password"
									                        name="imap_password" placeholder="Password"
									                        value="<?php echo esc_attr( rthd_encrypt_decrypt( $token ) ); ?>"/>
									</td>
								</tr>
							<?php
							}
							$all_folders = null;
							try {
								$hdZendEmail = new Rt_HD_Zend_Mail();
								if ( $hdZendEmail->tryImapLogin( $email, $token, $email_type, $imap_server ) ) {
									$storage     = new ImapStorage( $hdZendEmail->imap );
									$all_folders = $storage->getFolders();
								}
							} catch ( Exception $e ) {
								echo '<tr valign="top"><td></td><td></td><td><p class="description">' . esc_html( $e->getMessage() ) . '</p></td></tr>';
							} ?>
							<tr valign="top">
								<td><label><?php echo balanceTags( $personMarkup ); ?></label></td>
								<th scope="row"><label><?php _e( 'Mail Folders to read' ); ?></label></th>
								<td>
									<label>
										<?php _e( 'Inbox Folder' ); ?>
										<select data-email-id="<?php echo esc_attr( $ac->id ); ?>" name="inbox_folder"
										        data-prev-value="<?php echo esc_attr( $inbox_folder ); ?>">
											<option value=""><?php _e( 'Choose Inbox Folder' ); ?></option>
											<?php if ( ! is_null( $all_folders ) ) {
												$hdZendEmail->render_folders_dropdown( $all_folders, $value = $inbox_folder );
											} ?>
										</select>
									</label>
									<?php if ( in_array( $email, rthd_get_all_system_emails() ) ) { ?>
										<p class="description"><?php _e( 'This is linked as a system mail. Hence it will only read the Inbox Folder; no matter what folder you choose over here. These will be ignored.' ); ?></p>
									<?php } ?>
									<?php if ( ! is_null( $all_folders ) ) {
										$hdZendEmail->render_folders_checkbox( $all_folders, $element_name = 'mail_folders', $values = $mail_folders, $data_str = 'data-email-id=' . $ac->id, $inbox_folder );
									} else {
										?>
										<p class="description"><?php _e( 'No Folders found.' ); ?></p>
									<?php } ?>
								</td>
							</tr>
							<tr valign="top">
								<td></td>
								<th scope="row"><label></label></th>
								<td>
									<input type="hidden" name="rthd_submit_enable_reply_by_email" value="save"/>
									<a class='button remove-google-ac'
									   href='<?php echo esc_url( admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings&tab=my-settings&type=personal&email=' . $email ) ); ?>'>Remove
										A/C</a>
									<?php if ( $ac->type == 'goauth' ) { ?>
										<a class='button button-primary' href='<?php echo esc_url( $authUrl ); ?>'>Re Connect
											Google Now</a>
									<?php } ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				</tbody>
			</table>
			<script>
				jQuery(document).ready(function ($) {
					$(document).on('change', 'select[name=inbox_folder]', function (e) {
						e.preventDefault();
						inbox = $(this).val();
						prev_value = $(this).data('prev-value');
						$(this).data('prev-value', inbox);
						email_id = $(this).data('email-id');
						$('input[data-email-id="' + email_id + '"][value="' + inbox + '"]').parent().css('display', 'none');
						$('input[data-email-id="' + email_id + '"][value="' + prev_value + '"]').parent().css('display', 'inline');
					});
				});
			</script>
		<?php
		}
	} else {
		?>
		<p class="submit"><a class="button" id="rthd_add_personal_email" href="#"><?php _e( 'Add Email' ); ?></a></p>
		<p class="submit rthd-hide-row" id="rthd_email_acc_type_container">
			<select id="rthd_select_email_acc_type">
				<option value=""><?php _e( 'Select Type' ); ?></option>
				<option value="goauth"><?php _e( 'Google OAuth App' ); ?></option>
				<option value="imap"><?php _e( 'IMAP' ); ?></option>
			</select>
		</p>
		<p class="submit rthd-hide-row" id="rthd_goauth_container">
			<a class='button button-primary' href='<?php echo esc_url( $authUrl ); ?>'><?php _e( 'Connect New Google A/C' ); ?></a>
		</p>
		<p id="rthd_add_imap_acc_form" autocomplete="off" class="rthd-hide-row">
			<input type="hidden" name="rthd_add_imap_email" value="1"/>
			<select required="required" name="rthd_imap_server">
				<option value=""><?php _e( 'Select Mail Server' ); ?></option>
				<?php
				$imap_servers = $rt_hd_imap_server_model->get_all_servers();
				foreach ( $imap_servers as $server ) {
					?>
					<option value="<?php echo esc_attr( $server->id ); ?>"><?php echo esc_html( $server->server_name ); ?></option>
				<?php } ?>
			</select>
			<input type="email" required="required" autocomplete="off" name="rthd_imap_user_email" placeholder="Email"/>
			<input type="password" required="required" autocomplete="off" name="rthd_imap_user_pwd"
			       placeholder="Password"/>
			<button class="button button-primary" type="submit"><?php _e( 'Save' ); ?></button>
		</p>
	<?PHP
	}
}

function rthd_imap_servers( $field, $value ) {
	global $rt_hd_imap_server_model;
	$servers = $rt_hd_imap_server_model->get_all_servers();
	?>
	<table>
		<tbody>
		<?php foreach ( $servers as $server ) { ?>
			<tr valign="top">
				<th scope="row"><?php echo esc_html( $server->server_name ); ?></th>
				<td>
					<a href="#" class="rthd-edit-server"
					   data-server-id="<?php echo esc_attr( $server->id ); ?>"><?php _e( 'Edit' ); ?></a>
					<a href="#" class="rthd-remove-server"
					   data-server-id="<?php echo esc_attr( $server->id ); ?>"><?php _e( 'Remove' ); ?></a>
				</td>
			</tr>
			<tr valign="top" id="rthd_imap_server_<?php echo esc_attr( $server->id ); ?>" class="rthd-hide-row">
				<td>
					<table>
						<tr valign="top">
							<th scope="row"><?php _e( 'Server Name: ' ); ?></th>
							<td><input type="text" required="required"
							           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][server_name]"
							           value="<?php echo esc_attr( $server->server_name ); ?>"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'IMAP (Incoming) Server: ' ); ?></th>
							<td><input type="text" required="required"
							           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][incoming_imap_server]"
							           value="<?php echo esc_attr( $server->incoming_imap_server ); ?>"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'IMAP (Incoming) Port: ' ); ?></th>
							<td><input type="text" required="required"
							           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][incoming_imap_port]"
							           value="<?php echo esc_attr( $server->incoming_imap_port ); ?>"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'IMAP (Incoming) Encryption: ' ); ?></th>
							<td>
								<select name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][incoming_imap_enc]">
									<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
									<option
										value="ssl" <?php echo esc_html( ( $server->incoming_imap_enc == 'ssl' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'SSL' ); ?></option>
									<option
										value="tls" <?php echo esc_html( ( $server->incoming_imap_enc == 'tls' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'TLS' ); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'SMTP (Outgoing) Server: ' ); ?></th>
							<td><input type="text" required="required"
							           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][outgoing_smtp_server]"
							           value="<?php echo esc_attr( $server->outgoing_smtp_server ); ?>"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'SMTP (Outgoing) Port: ' ); ?></th>
							<td><input type="text" required="required"
							           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][outgoing_smtp_port]"
							           value="<?php echo esc_attr( $server->outgoing_smtp_port ); ?>"/></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'SMTP (Outgoing) Encryption: ' ); ?></th>
							<td>
								<select name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][outgoing_smtp_enc]">
									<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
									<option
										value="ssl" <?php echo esc_html( ( $server->outgoing_smtp_enc == 'ssl' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'SSL' ); ?></option>
									<option
										value="tls" <?php echo esc_html( ( $server->outgoing_smtp_enc == 'tls' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'TLS' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		<?php } ?>
		<input type="hidden" name="rthd_imap_servers_changed" value="1"/>
		<tr valign="top">
			<th scope="row"><a href="#" class="button" id="rthd_add_imap_server"><?php _e( 'Add new server' ); ?></a>
			</th>
		</tr>
		<tr valign="top" id="rthd_new_imap_server" class="rthd-hide-row">
			<td>
				<table>
					<tr valign="top">
						<th scope="row"><?php _e( 'Server Name: ' ); ?></th>
						<td><input type="text" name="rthd_imap_servers[new][server_name]"/></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'IMAP (Incoming) Server: ' ); ?></th>
						<td><input type="text" name="rthd_imap_servers[new][incoming_imap_server]"/></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'IMAP (Incoming) Port: ' ); ?></th>
						<td><input type="text" name="rthd_imap_servers[new][incoming_imap_port]"/></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'IMAP (Incoming) Encryption: ' ); ?></th>
						<td>
							<select name="rthd_imap_servers[new][incoming_imap_enc]">
								<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
								<option value="ssl"><?php _e( 'SSL' ); ?></option>
								<option value="tls"><?php _e( 'TLS' ); ?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'SMTP (Outgoing) Server: ' ); ?></th>
						<td><input type="text" name="rthd_imap_servers[new][outgoing_smtp_server]"/></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'SMTP (Outgoing) Port: ' ); ?></th>
						<td><input type="text" name="rthd_imap_servers[new][outgoing_smtp_port]"/></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Is SSL required for SMTP (Outgoing Mails): ' ); ?></th>
						<td>
							<select name="rthd_imap_servers[new][outgoing_smtp_enc]">
								<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
								<option value="ssl"><?php _e( 'SSL' ); ?></option>
								<option value="tls"><?php _e( 'TLS' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</tbody>
	</table>
<?php
}

/**
 * Custom function for the callback validation referenced above
 * */
if ( ! function_exists( 'redux_validate_callback_function' ) ):

	function redux_validate_callback_function( $field, $value, $existing_value ) {
		$error = false;
		$value = 'just testing';

		/*
		  do your validation

		  if(something) {
		  $value = $value;
		  } elseif(something else) {
		  $error = true;
		  $value = $existing_value;
		  $field['msg'] = 'your custom error message';
		  }
		 */

		$return['value'] = $value;
		if ( $error == true ) {
			$return['error'] = $field;
		}

		return $return;
	}






endif;
