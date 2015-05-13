<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 21/4/15
 * Time: 4:37 PM
 */
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_HD_setup_wizard' ) ) {

	/*
	 * This class is for setup wizard part
	 */

	class Rt_HD_setup_wizard {

		var $screen_id;

		/**
		 * Constructor calling hooks
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'register_setup_wizard' ), 1 );
			add_action( 'wp_ajax_rthd_search_non_helpdesk_user_from_name', array( $this, 'get_user_from_name' ) );
			add_action( 'wp_ajax_rthd_creater_rtbiz_and_give_access_helpdesk', array( $this, 'rthd_creater_rtbiz_and_give_access_helpdesk' ) );
			add_action( 'wp_ajax_rthd_domain_search_and_import', array( $this, 'domain_search_and_import' ) );
			add_action( 'wp_ajax_rthd_import_all_users', array( $this, 'import_all_users' ) );
			add_action( 'wp_ajax_rthd_offering_sync', array( $this, 'offering_sync' ) );
			add_action( 'wp_ajax_rthd_setup_wizard_assignee_save', array( $this, 'assignee_save' ) );
			add_action( 'wp_ajax_rthd_get_default_assignee_ui', array( $this, 'default_assignee' ) );
			add_action( 'wp_ajax_rthd_outboud_mail_setup_ui', array( $this, 'rthd_outboud_mail_setup_ui' ) );
			add_action( 'wp_ajax_rthd_outound_setup_wizard', array( $this, 'rthd_outound_setup_wizard_callback' ) );
			add_action( 'wp_ajax_rthd_remove_user', array( $this, 'rthd_remove_user' ) );
			add_action( 'wp_ajax_rthd_search_domain', array( $this, 'rthd_search_domain' ) );
			add_action( 'wp_ajax_rthd_change_ACL', array( $this, 'rthd_change_ACL' ) );
//			add_action( 'wp_ajax_rthd_get_ACL', array( $this, 'rthd_ACL' ) );
			add_action( 'wp_ajax_rthd_add_new_offering', array( $this, 'rthd_add_new_offering' ) );

			if ( ! empty( $_REQUEST[ 'close_notice' ] ) ) {
				delete_option( 'rtbiz_helpdesk_dependency_installed' );
			}
			if ( ! empty( $_REQUEST[ 'finish-wizard' ] ) ) {
				update_option( 'rtbiz_helpdesk_setup_wizard_option', 'true' );
			}
			add_action( 'admin_notices', 'rthd_admin_notice_dependency_installed' );
		}

		/**
		 *  Register page to Wordpress with admin capability
		 */
		function register_setup_wizard() {
			//if ( ! rthd_check_wizard_completed() ) {
			$admin_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );
			$this->screen_id = add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), __( 'Setup Wizard', RT_HD_TEXT_DOMAIN ), __( 'Setup Wizard', RT_HD_TEXT_DOMAIN ), $admin_cap, 'rthd-setup-wizard', array(
				$this,
				'setup_wizard_ui',
					) );
			//}
		}

		function set_assignee_ui() {
			?>
			<div id="rthd-setup-set-assignee-ui">

			</div>
			<?php
		}

		function mail_box_ui() {
			?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Incoming MailBox Setup', RT_BIZ_TEXT_DOMAIN ); ?></h3>
				<p class="rthd-notice">Connect the mailbox from which you would like to auto-create ticket from incoming e-mails.  Click on next if you want to do that later.</p>
				<?php rthd_mailbox_setup_view(); ?>
			</div>
			<div class="rthd-mailbox-setup-process rthd-wizard-process" style="display: none;">
				<span>Loading outbound emails</span>
				<img class="" alt="load" src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
			</div> <?php
		}

		function set_role_ui() {
			?>
			<div class="rthd-setup-wizard-controls rthd-ACL-change rthd-setup-wizard-row">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Congrats !!!', RT_BIZ_TEXT_DOMAIN ); ?></h3>
				<p>Your Helpdesk is ready. Click on finish to get started.</p>
			</div>
			<?php
		}

		// Generate Markup
		function generate_wizard( $wizard ) {
			if ( ! empty( $wizard ) ) {
				?>
				<div id="wizard">

					<?php foreach ( $wizard as $key => $val ) { ?>
						<h3><?php _e( $key ); ?></h3>
						<fieldset> <?php call_user_func( $val ); ?></fieldset>
					<?php } ?>

				</div> <?php
			}
		}

		/**
		 * @param $post_type
		 * setup wizard UI
		 */
		function setup_wizard_ui( $post_type ) {
			?>
			<div class="wrap">

				<h2><?php _e( 'rtBiz Helpdesk Setup', RT_BIZ_TEXT_DOMAIN ); ?></h2>
				<hr>
				<!--				<div class="updated notice notice-success is-dismissible below-h2 rthd-hide-notice-setup-wizard" id="message">
									<p><?php //_e( 'Thank you for choosing rtBiz', RT_BIZ_TEXT_DOMAIN );    ?></p>
								</div>-->

				<div class="rthd-row-container">
					<div class="rthd-content-section">

						<h3 class="rthd-option-title">Please follow given steps to configure your Helpdesk.</h3>

						<?php
						// title and function to call for content
						$wizard = array(
							'Support Page' => array( $this, 'support_page_ui' ),
							'Connect Store' => array( $this, 'connect_store_ui' ),
							'Setup Your Team' => array( $this, 'setup_team' ),
							'Set Assignee' => array( $this, 'set_assignee_ui' ),
							'Mailbox Setup' => array( $this, 'mail_box_ui' ),
							'Finish' => array( $this, 'set_role_ui' ),
						);
						$this->generate_wizard( $wizard );
						?>
					</div>
					<?php rthd_admin_sidebar(); ?>
				</div>
			</div>
			<?php
		}

		function rthd_ACL() {
			global $wpdb, $rt_biz_acl_model;
			$result = $wpdb->get_results( "SELECT acl.userid, acl.permission FROM " . $rt_biz_acl_model->table_name . " as acl INNER JOIN " . $wpdb->prefix . "p2p as p2p on ( acl.userid = p2p.p2p_to ) INNER JOIN " . $wpdb->posts . " as posts on (p2p.p2p_from = posts.ID )  where acl.module =  '" . RT_HD_TEXT_DOMAIN . "' and acl.permission != 0 and p2p.p2p_type = '" . rt_biz_get_contact_post_type() . "_to_user' and posts.post_status= 'publish' and posts.post_type= '" . rt_biz_get_contact_post_type() . "' and acl.groupid = 0 " );
			ob_start();
			if ( ! empty( $result ) ) {
				?>
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Set Roles', RT_BIZ_TEXT_DOMAIN ); ?></h3>

				<table class="shop_table my_account_orders">
					<tr>
						<th>User</th>
						<th>Admin</th>
						<th>Editor</th>
						<th>Author</th>
						<th></th>
					</tr>
					<?php
					foreach ( $result as $row ) {
						$user = get_userdata( $row->userid );
						?>
						<tr id="ACL_<?php echo $user->ID; ?>">
							<td><?php echo $user->display_name ?></td>
							<td><input type="radio" class="rt-hd-setup-acl" data-id="<?php echo $user->ID; ?>"
									   name="ACL_<?php echo $user->ID; ?>"
									   value="<?php echo Rt_Access_Control::$permissions[ 'admin' ][ 'value' ]; ?>" <?php echo ( $row->permission == Rt_Access_Control::$permissions[ 'admin' ][ 'value' ] ) ? 'checked' : ''; ?> />
							</td>
							<td><input type="radio" class="rt-hd-setup-acl" data-id="<?php echo $user->ID; ?>"
									   name="ACL_<?php echo $user->ID; ?>"
									   value="<?php echo Rt_Access_Control::$permissions[ 'editor' ][ 'value' ]; ?>" <?php echo ( $row->permission == Rt_Access_Control::$permissions[ 'editor' ][ 'value' ] ) ? 'checked' : ''; ?> />
							</td>
							<td><input type="radio" class="rt-hd-setup-acl" data-id="<?php echo $user->ID; ?>"
									   name="ACL_<?php echo $user->ID; ?>"
									   value="<?php echo Rt_Access_Control::$permissions[ 'author' ][ 'value' ]; ?>" <?php echo ( $row->permission == Rt_Access_Control::$permissions[ 'author' ][ 'value' ] ) ? 'checked' : ''; ?> />
							</td>
							<td><img alt="load" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
							</td>
						</tr> <?php }
					?>
				</table><?php
				$status = true;
			} else {
				$status = false;
			}
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => $status, 'html' => ob_get_clean() ) );
			die( 0 );
		}

		/**
		 * outbound mailbox ui
		 */
		function rthd_outboud_mail_setup_ui() {
			$system_emails = rtmb_get_module_mailbox_emails( RT_HD_TEXT_DOMAIN );
			ob_start();
			?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Outgoing Mail Setup ', RT_BIZ_TEXT_DOMAIN ); ?></h3>
				<p class="rthd-notice">Configure settings for the mailbox from where you will like to send Helpdesk e-mails to customers and staff.</p>
				<div id="rthd_outgoing_mailbox_setup_container">
					<input type="hidden" id="rthd_outound_sub-action" name="rthd_outound_sub-action" value="rthd_outound_setup_wizard">
					<?php wp_nonce_field( 'rthd_outound_setup_wizard' ); ?>
					<div class="rthd-setup-wizard-row">
						<label for="rthd_outgoing_email_from_name"> <?php _e( 'Outgoing Emails\' FROM Name' ); ?></label>
						<input type="text" id="rthd_outgoing_email_from_name" name="rthd_outgoing_email_from_name" value="<?php echo get_bloginfo(); ?>" />
					</div>
					<div class="rthd-setup-wizard-row">
						<label for="rthd_outgoing_email_mailbox"> <?php _e( 'Outgoing Emails\' Mailbox' ); ?></label>
						<select id="rthd_outgoing_email_mailbox" name="rthd_outgoing_email_mailbox">
							<?php foreach ( $system_emails as $email ) { ?>
								<option value="<?php echo $email; ?>"><?php echo $email; ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
			<div class="rthd-outbound-setup-process rthd-wizard-process" style="display: none;">
				<span>Setting up outbound mails</span>
				<img src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
			</div>
			<?php
			$comment_html = ob_get_clean();
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => true, 'html' => $comment_html ) );
			die( 0 );
		}

		/**
		 * save outbound mailbox
		 */
		function rthd_outound_setup_wizard_callback() {
			$result = array();
			$result[ 'status' ] = false;

			$obj_data = array();
			parse_str( $_POST[ 'data' ], $obj_data );

			if ( ! wp_verify_nonce( $obj_data[ '_wpnonce' ], 'rthd_outound_setup_wizard' ) ) {
				$result[ 'error' ] = 'Security check false';
				echo json_encode( $result );
				die();
			}

			if ( empty( $obj_data[ 'rthd_outgoing_email_from_name' ] ) || empty( $obj_data[ 'rthd_outgoing_email_mailbox' ] ) ) {
				$result[ 'error' ] = 'Error: Required mailbox field missing';
				echo json_encode( $result );
				die();
			}

			if ( ! empty( $obj_data[ 'rthd_outgoing_email_from_name' ] ) ) {
				rthd_set_redux_settings( 'rthd_outgoing_email_from_name', $obj_data[ 'rthd_outgoing_email_from_name' ] );
			}

			if ( ! empty( $obj_data[ 'rthd_outgoing_email_mailbox' ] ) ) {
				rthd_set_redux_settings( 'rthd_outgoing_email_mailbox', $obj_data[ 'rthd_outgoing_email_mailbox' ] );
			}
			$result[ 'status' ] = true;
			echo json_encode( $result );
			die( 0 );
		}

		/**
		 * default assignee ui ajax call
		 */
		function default_assignee() {
			ob_start();
			$current = get_current_user_id();
			?>
			<?php
			// get product list
			$terms = array();
			global $rtbiz_offerings;
			if ( isset( $rtbiz_offerings ) ) {
				add_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
				$terms = get_terms( Rt_Offerings::$offering_slug, array( 'hide_empty' => 0 ) );
				remove_filter( 'get_terms', array( $rtbiz_offerings, 'offering_filter' ), 10, 3 );
			}
			$users = Rt_HD_Utils::get_hd_rtcamp_user();
			?>
			<div class="rthd-setup-wizard-controls">

				<h3 class="rthd-setup-wizard-title"><?php _e( 'Select Ticket Assignee', RT_BIZ_TEXT_DOMAIN ); ?></h3>
				<?php
				if ( ! empty( $terms ) ) {
					?>
					<p class="rthd-notice"> <?php _e( 'Select an assignee for the products we synced in previous setup.', RT_BIZ_TEXT_DOMAIN ); ?> </p>

					<div class="rthd-setup-wizard-row">
						<ul>
							<?php foreach ( $terms as $tm ) { ?>
								<li>
									<label for="rthd_offering<?php echo $tm->term_id ?>"> <?php echo $tm->name ?></label>
									<select class="rthd-setup-assignee" data="<?php echo $tm->term_id ?>"
											id="rthd_offering<?php echo $tm->term_id ?>">
												<?php
												// if needed to set default assignee that already have assigned
												//							$selected_userid = get_offering_meta( 'default_assignee', $tm->term_id );
//                                    if (empty($current)) {
//                                        echo '<option disabled selected> -- select an assignee -- </option>';
//                                    } else {
//                                        echo '<option > -- select an assignee -- </option>';
//                                    }
												foreach ( $users as $user ) {
													if ( $user->ID == $current ) {
														$selected = 'selected';
													} else {
														$selected = '';
													}
													echo '<option value="' . $user->ID . '" ' . $selected . '>' . $user->display_name . '</option>';
												}
												?>
									</select>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				<?php } else {
					?>

					<div class="rthd-setup-wizard-row">
						<label class="rthd-offering-default-assignee" for="rthd_offering-default"> <strong><?php _e( 'Default Assignee', RT_BIZ_TEXT_DOMAIN ); ?> </strong></label>
						<select id="rthd_offering-default">
							<?php
							// if needed to set default assignee that already have assigned
							//							$selected_userid = get_offering_meta( 'default_assignee', $tm->term_id );
							if ( empty( $current ) ) {
								echo '<option disabled selected> -- select an assignee -- </option>';
							} else {
								echo '<option > -- select an assignee -- </option>';
							}
							foreach ( $users as $user ) {
								if ( $user->ID == $current ) {
									$selected = 'selected';
								} else {
									$selected = '';
								}
								echo '<option value="' . $user->ID . '" ' . $selected . '>' . $user->display_name . '</option>';
							}
							?>
						</select>
					</div>
				<?php } ?>
			</div>
			<div class="rthd-assignee-process rthd-wizard-process" style="display: none;">
				<span>Setting up default assignee for offerings</span>
				<img src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
			</div>
			<?php
			$comment_html = ob_get_clean();
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => true, 'html' => $comment_html ) );
			die( 0 );
		}

		/**
		 * connect store UI
		 */
		function connect_store_ui() {
			?>

			<div class="rthd-setup-wizard-controls rthd-setup-store-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Connect Your Store', RT_BIZ_TEXT_DOMAIN ); ?></h3>
				<?php
				global $rt_hd_offering_support;
				$rt_hd_offering_support->check_active_plugin();
				$eddactive = is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' );
				$wooactive = is_plugin_active( 'woocommerce/woocommerce.php' );
				if ( $wooactive || $eddactive ) {
					if ( $wooactive && $eddactive ) {
						$active_plugins = 'WooCommerce and EDD plugins';
					} else if ( $wooactive ) {
						$active_plugins = 'WooCommerce plugin';
					} else {
						$active_plugins = 'EDD plugin';
					}
					echo '<p class="rthd-notice"> Looks like you have ' . $active_plugins . ' Active. Helpdesk has selected it for you, You can uncheck that if you want to.</p>';
				} else {
					echo '<p class="rthd-notice"> Looks like none of the following plugins are installed right now. Anyways you can select the plugin you wish to install in future. Helpdesk will automatically sync products when that happens.</p>';
				}
				?>
				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-wc" class="" type="checkbox" name="rthd-wizard-store" value="woocommerce" <?php echo $wooactive ? 'checked' : ''; ?> >
					<label for="rthd-wizard-store-wc">WooCommerce</label>
				</div>
				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-edd" type="checkbox" name="rthd-wizard-store" value="edd" <?php echo $eddactive ? 'checked' : ''; ?> >
					<label for="rthd-wizard-store-edd">Easy Digital Downloads</label>
				</div>

				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-custom" type="checkbox" name="rthd-wizard-store" value="custom" >
					<label for="rthd-wizard-store-custom">Custom</label>
				</div>
				<div class="rthd-setup-wizard-row rthd-wizard-store-custom-div" style="display:none;">
					<label for="rthd-setup-store-new-team"><?php _e( 'Add New Offering', RT_BIZ_TEXT_DOMAIN ); ?></label>
					<input type="text" id="rthd-setup-store-new-team" />
					<input type="button" id="rthd-setup-store-new-team-submit" value="Add" />
				</div>
				<ol class="rthd-setup-wizard-new-offering">

				</ol>
			</div>

			<div class="rthd-store-process rthd-wizard-process" style="display: none; float: left;">
				<span>Connecting store and importing existing products</span>
				<img class="" alt="load"  src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div>

			<?php
		}

		/**
		 * Support page ui
		 */
		function support_page_ui() {
			$pages = get_pages();
			?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title">Support Page </h3>
				<p class="rthd-notice">Create a support page where your customers can submit tickets.</p>

				<div class="rthd-setup-wizard-row">
					<label for="rthd-setup-wizard-support-page"><?php _e( 'Select Support Page ', RT_BIZ_TEXT_DOMAIN ); ?></label>
					<select id="rthd-setup-wizard-support-page">
						<option value="-1"><?php _e( '- Create New Page -', RT_BIZ_TEXT_DOMAIN ); ?></option>
						<option value="0" selected><?php _e( '-- Select Page --', RT_BIZ_TEXT_DOMAIN ); ?></option>
						<?php
						if ( ! empty( $pages ) ) {
							foreach ( $pages as $page ) {
								echo '<option value="' . $page->ID . '">' . $page->post_title . '</option>';
							}
						}
						?>
					</select>

				</div>
				<div class="rthd-setup-wizard-row rthd-setup-wizard-support-page-new-div" style="display: none;">
					<label for="rthd-setup-wizard-support-page-new"><?php _e( 'Create New Page', RT_BIZ_TEXT_DOMAIN ); ?></label>
					<input type="text" id="rthd-setup-wizard-support-page-new" name="rthd-setup-wizard-support-page-new-value" />
				</div>

			</div>

			<div class="rthd-support-process rthd-wizard-process" style="display: none;">
				<span>Setting up support page</span>
				<img class="" alt="load" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div>
			<?php
		}

		/**
		 * setup team UI
		 */
		function setup_team( $isheader = true ) {
			?>

			<div>
				<div class="rthd-setup-wizard-controls rthd-setup-team-wizard-controls">
					<?php if ( $isheader ) { ?>
						<h3 class="rthd-setup-wizard-title">Setup Your Team</h3>
					<?php } ?>
					<p class="rthd-notice"><?php _e( "There are 3 ways you can add users to your ‘Support' team. If you forget somebody now, you can add them later. You (admin) are already part of this team.", RT_HD_TEXT_DOMAIN ) ?></p>

					<div class="rthd-setup-team-settings">
						<div class="rthd_wizard_container rthd-setup-wizard-row">
							<div class="rthd-setup-value-container">
								<label for="rthd-user-autocomplete"> 1. Search and add users </label>
								<input id="rthd-user-autocomplete" type="text" placeholder="Search by name or email" class="rthd-user-autocomplete rthd-setup-wizard-text " />
								<img id="rthd-autocomplete-page-spinner" alt="load" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
								<br/>
								<span class="rthd-warning" style="display: none;"></span>
								<input type="button" class='button rthd-importer-add-contact' value="Add" style="display: none;" />
								<input type="hidden" id="rthd-new-user-email" />

							</div>
						</div>

						<div class="rthd_wizard_container rthd-setup-wizard-row">
							<?php
							$domain_name = preg_replace( '/^www\./', '', $_SERVER[ 'SERVER_NAME' ] );

							$count_domain_users = rthd_search_non_helpdesk_users( $domain_name, true, true );
							?>
							<label for="rthd-add-user-domain"> 2. Add all users from domain</label>
							<input id="rthd-add-user-domain" class="rthd-setup-wizard-text" type="text" value="<?php echo $domain_name; ?>" placeholder="gmail.com" />
							<div class="rthd-domain-action">
								<span id='rthd-domain-import-message' style=""> Found <?php echo sprintf( _n( '%s user', '%s users', $count_domain_users, RT_HD_TEXT_DOMAIN ), $count_domain_users ); ?></span>
								<input id="rthd-import-domain-users" class="button" type="button" value="Add Users" />
								<img id="rthd-domain-import-spinner" alt="load" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
								<?php wp_nonce_field( get_current_user_id() . 'import-user-domain', 'import_domain' ); ?>
							</div>

						</div>

						<div class="rthd_wizard_container rthd-setup-wizard-row">
							<form>
								<?php
								$helpdesk_users = rthd_get_helpdesk_user_ids();
								$count = count_users();
								$remain_wp_users = $count[ 'total_users' ] - count( $helpdesk_users );
								?>
								<label> 3. Add all WordPress <?php echo sprintf( _n( '(%s) user', '(%s) users', $remain_wp_users, RT_HD_TEXT_DOMAIN ), $remain_wp_users ); ?></label>
								<input id="rthd-add-all-users" class="button" type="button" value="Add Users" />
								<img id="rthd-import-all-spinner" alt="load" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
								<?php wp_nonce_field( get_current_user_id() . 'import-all-users', 'import_all_users' ); ?>
								<input type="hidden" id="rthd-setup-import-all-count" value="<?php echo $remain_wp_users; ?>" />
								<progress id="rthd-setup-import-users-progress" max="<?php echo $remain_wp_users; ?>" value="0" style="display: none;"></progress>
								<span id='rthd-all-import-message'> </span>
							</form>
						</div>

						<div class="rthd-team-setup-loading rthd-wizard-process" style="display: none;">
							<span>Loading next page</span>
							<img class="" alt="load" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
						</div>

					</div>

					<div class="rthd_wizard_container rthd_selected_user clearfix">
						<table class="rthd-setup-ul-text-decoration rthd-setup-list-users">
							<tr>
								<th>User</th>
								<th><span><?php _e( 'Admin', RT_HD_TEXT_DOMAIN ); ?></span>
									<span class="rthd-tooltip">
										<i class="dashicons dashicons-info rtmicon"></i>
										<span class="rthd-tip-bottom">
											<?php _e( 'Can manage all tickets and Helpdesk settings.', RT_HD_TEXT_DOMAIN ); ?>
										</span>
									</span>
								</th>
								<th><span><?php _e( 'Editor', RT_HD_TEXT_DOMAIN ); ?></span>
									<span class="rthd-tooltip">
										<i class="dashicons dashicons-info rtmicon"></i>
										<span class="rthd-tip-bottom">
											<?php _e( 'Can manage all the tickets. No access to settings. ', RT_HD_TEXT_DOMAIN ); ?>
										</span>
									</span>
								</th>
								<th><span><?php _e( 'Author', RT_HD_TEXT_DOMAIN ); ?></span>
									<span class="rthd-tooltip">
										<i class="dashicons dashicons-info rtmicon"></i>
										<span class="rthd-tip-bottom">
											<?php _e( 'Can manager only the tickets assigned to him/her. No access to settings.', RT_HD_TEXT_DOMAIN ); ?>
										</span>
									</span>
								</th>
								<th></th>
							</tr>

						</table>
					</div>
				</div>
			</div>

			<?php
		}

		/**
		 *
		 * Search all wp user who don't have helpdesk access
		 *
		 */
		function get_user_from_name() {
			if ( ! isset( $_POST[ 'query' ] ) ) {
				wp_die( 'Invalid request Data' );
			}
			$query = $_POST[ 'query' ];
			$results = rthd_search_non_helpdesk_users( $query, false, false );
			$arrReturn = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $author ) {
					$arrReturn[] = array( 'id' => $author->ID,
						'label' => $author->display_name,
						'imghtml' => get_avatar( $author->user_email, 25 ),
						'editlink' => rt_biz_get_contact_edit_link( $author->user_email )
					);
				}
			} else {
				if ( is_email( $_POST[ 'query' ] ) ) {
					if ( email_exists( $_POST[ 'query' ] ) ) {
						$arrReturn[ 'have_access' ] = true;
					} else {
						$arrReturn[ 'show_add' ] = true;
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * Create wp user if not exist, create rtbiz contact if not exist, if exist and not mapped map it, give helpdesk access
		 * ajax call
		 */
		function rthd_creater_rtbiz_and_give_access_helpdesk() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST[ 'email' ] ) || ! empty( $_POST[ 'ID' ] ) ) {
				if ( ! empty( $_POST[ 'email' ] ) && is_email( $_POST[ 'email' ] ) ) {
					// create wordpress user and get it's id for creating rt contact user
					global $rt_hd_contacts;
					$_POST[ 'ID' ] = $rt_hd_contacts->get_user_from_email( $_POST[ 'email' ] );
				}
				if ( ! empty( $_POST[ 'ID' ] ) ) {
					// Create dept if not exist in wp-option, if exist assign new user to that taxonomy and add entry for author access to that user
//					global $rt_biz_acl_model,$rt_contact;
					$wpuser = get_user_by( 'id', $_POST[ 'ID' ] );

//					$team_id = rthd_get_default_support_team();

					if ( rthd_give_user_access( $wpuser, Rt_Access_Control::$permissions[ 'author' ][ 'value' ], 0 ) ) {
						/* $contact = rt_biz_get_contact_for_wp_user($wpuser->ID);
						  if ( ! empty( $contact[0] ) ) {
						  $user_permissions = get_post_meta( $contact[0]->ID, 'rt_biz_profile_permissions', true );
						  $value =  array( RT_HD_TEXT_DOMAIN => Rt_Access_Control::$permissions['author']['value'] );
						  if ( ! empty( $user_permissions ) ){
						  $value = array_merge( $value, $user_permissions );
						  }
						  update_post_meta( $contact[0]->ID, 'rt_biz_profile_permissions', $value );
						  } */
						// do something
						$arrReturn[ 'status' ] = true;
						$arrReturn = array_merge( $arrReturn, array(
							'id' => $wpuser->ID,
							'label' => $wpuser->display_name,
							'imghtml' => get_avatar( $wpuser->user_email, 25 ),
							'editlink' => rt_biz_get_contact_edit_link( $wpuser->user_email )
								) );
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * ajax call for getting domain search and import
		 */
		function domain_search_and_import() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST[ 'count' ] ) && ! empty( $_POST[ 'domain_query' ] ) && ! empty( $_POST[ 'nonce' ] ) && wp_verify_nonce( $_POST[ 'nonce' ], get_current_user_id() . 'import-user-domain' ) ) {
				if ( $_POST[ 'count' ] === 'true' ) { // return counts of users
					$arrReturn[ 'count' ] = rthd_search_non_helpdesk_users( $_POST[ 'domain_query' ], true, true );
					$arrReturn[ 'status' ] = true;
				} else {
					$users_to_import = rthd_search_non_helpdesk_users( $_POST[ 'domain_query' ], false, false );
//					$team_id = rthd_get_default_support_team();
					$arrReturn[ 'imported_all' ] = true;
					foreach ( $users_to_import as $user ) {
						if ( rthd_give_user_access( $user->ID, Rt_Access_Control::$permissions[ 'author' ][ 'value' ], 0 ) ) {
							$arrReturn[ 'imported_users' ][] = array( 'id' => $user->ID,
								'label' => $user->display_name,
								'imghtml' => get_avatar( $user->user_email, 25 ),
								'editlink' => rt_biz_get_contact_edit_link( $user->user_email ) );
						} else {
							$arrReturn[ 'not_imported_users' ][] = $user;
							$arrReturn[ 'imported_all' ] = false;
						}
					}
					$arrReturn[ 'status' ] = true;
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * import all users ajax call
		 */
		function import_all_users() {
			$arrReturn = array( 'status' => false );
			$LIMIT = 5; //todo change this limit to 50
			if ( ! empty( $_POST[ 'import' ] ) && ! empty( $_POST[ 'nonce' ] ) && wp_verify_nonce( $_POST[ 'nonce' ], get_current_user_id() . 'import-all-users' ) ) {
				global $wpdb;
				$helpdesk_users = rthd_get_helpdesk_user_ids();
				$q = '';
				if ( ! empty( $helpdesk_users ) ) {
					$q = ' WHERE ID not IN (' . implode( ',', $helpdesk_users ) . ') ';
					if ( isset( $_POST[ 'last_import' ] ) ) {
						$q .= 'AND ID > ' . intval( $_POST[ 'last_import' ] ) . ' ';
					}
				}
				$users_to_import = $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users" . $q . "LIMIT " . $LIMIT );
				$arrReturn[ 'imported_all' ] = true;
//				$team_id = rthd_get_default_support_team();

				foreach ( $users_to_import as $user ) {
					if ( rthd_give_user_access( $user->ID, Rt_Access_Control::$permissions[ 'author' ][ 'value' ], 0 ) ) {
						$arrReturn[ 'imported_users' ][] = array( 'id' => $user->ID,
							'label' => $user->display_name,
							'imghtml' => get_avatar( $user->user_email, 25 ),
							'editlink' => rt_biz_get_contact_edit_link( $user->user_email ) );
					} else {
						$arrReturn[ 'not_imported_users' ][] = $user;
						$arrReturn[ 'imported_all' ] = false;
					}
				}
				$arrReturn[ 'imported_count' ] = count( $users_to_import );
				$arrReturn[ 'status' ] = true;
				// count remain users
				$users_to_import = $wpdb->get_var( "SELECT count(ID) FROM $wpdb->users" . $q );
				$users_to_import = $users_to_import - $arrReturn[ 'imported_count' ];
				$arrReturn[ 'remain_import' ] = $users_to_import;
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 *  Offering save ajax call
		 */
		function offering_sync() {
			$arrReturn = array( 'status' => false );
			$offering = array();
			$defaultoffering = array( 'woocommerce' => 0, 'edd' => 0 );
			if ( ! empty( $_POST[ 'store' ] ) ) {
				foreach ( $_POST[ 'store' ] as $store ) {
					$offering[ $store ] = '1';
				}
			}
			$offering = array_merge( $defaultoffering, $offering );
			rthd_set_redux_setting( 'offering_plugin', $offering );
			rt_biz_set_redux_setting( 'offering_plugin', $offering );
			global $rtbiz_offerings;
			$offering = array_keys( $offering );
			$rtbiz_offerings->bulk_insert_offerings( $offering );
			$arrReturn[ 'status' ] = true;
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 *  Assignee save ajax call
		 */
		function assignee_save() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST[ 'assignee' ] ) ) {
				foreach ( $_POST[ 'assignee' ] as $assingee ) {
					update_offering_meta( 'default_assignee', $assingee[ 'user_ID' ], $assingee[ 'term_ID' ] );
				}
				$arrReturn[ 'status' ] = true;
			}
			//default_assignee
			if ( ! empty( $_POST[ 'default_assignee' ] ) && is_numeric( $_POST[ 'default_assignee' ] ) ) {
				rthd_set_redux_settings( 'rthd_default_user', $_POST[ 'default_assignee' ] );
				$arrReturn[ 'status' ] = true;
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		function rthd_remove_user() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST[ 'userid' ] ) ) {
				global $rt_biz_acl_model;
				$rt_biz_acl_model->remove_acl( array( 'module' => RT_HD_TEXT_DOMAIN, 'userid' => $_POST[ 'userid' ] ) );
				$rt_biz_acl_model->remove_acl( array( 'module' => RT_BIZ_TEXT_DOMAIN, 'userid' => $_POST[ 'userid' ] ) );
				$arrReturn[ 'status' ] = true;
				$contact = rt_biz_get_contact_for_wp_user( $_POST[ 'userid' ] );
//				$support_team = get_option( 'rthd_default_support_team' );
//				if ( ! empty( $support_team ) && ! empty( $contact[0] ) ){
//					wp_remove_object_terms($contact[0]->ID,array($support_team),RT_Departments::$slug );
//				}
				if ( ! empty( $contact[ 0 ] ) && empty( $team_term_id ) ) {
					$user_permissions = get_post_meta( $contact[ 0 ]->ID, 'rt_biz_profile_permissions', true );
					if ( ! empty( $user_permissions[ RT_HD_TEXT_DOMAIN ] ) ) {
						$user_permissions[ RT_HD_TEXT_DOMAIN ] = 0;
						$user_permissions[ RT_BIZ_TEXT_DOMAIN ] = 0;
						update_post_meta( $contact[ 0 ]->ID, 'rt_biz_profile_permissions', $user_permissions );
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		function rthd_search_domain() {
			global $wpdb;
			$arrReturn = array();
			if ( ! empty( $_POST[ 'query' ] ) ) {
				$prefix = '@';
				if ( substr( $_POST[ 'query' ], 0, strlen( $prefix ) ) == $prefix ) {
					$_POST[ 'query' ] = substr( $_POST[ 'query' ], strlen( $prefix ) );
				}
				$domains = $wpdb->get_col( "SELECT DISTINCT( SUBSTRING_INDEX(user_email,'@',-1)) FROM $wpdb->users where user_email like '%@" . $_POST[ 'query' ] . "%'" );
				$domains = array_filter( $domains );
				$arrReturn = $domains;
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		function rthd_change_ACL() {
			global $rt_biz_acl_model;
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST[ 'permission' ] ) && ! empty( $_POST[ 'userid' ] ) ) {

				//helpdesk role change in custom table
				$rt_biz_acl_model->update_acl( array( 'permission' => $_POST[ 'permission' ] ), array( 'userid' => $_POST[ 'userid' ], 'module' => RT_HD_TEXT_DOMAIN ) );
				//rtbiz role change in custom table
				$rt_biz_acl_model->update_acl( array( 'permission' => $_POST[ 'permission' ] ), array( 'userid' => $_POST[ 'userid' ], 'module' => RT_BIZ_TEXT_DOMAIN ) );

				$contact = rt_biz_get_contact_for_wp_user( $_POST[ 'userid' ] );
				// update contact meta as we store ACL values in contact meta as well
				if ( ! empty( $contact[ 0 ] ) ) {
					$user_permissions = get_post_meta( $contact[ 0 ]->ID, 'rt_biz_profile_permissions', true );
					$value = array(
						RT_HD_TEXT_DOMAIN => $_POST[ 'permission' ],
						RT_BIZ_TEXT_DOMAIN => $_POST[ 'permission' ],
					);
					if ( ! empty( $user_permissions ) ) {
						$value = array_merge( $user_permissions, $value );
					}
					update_post_meta( $contact[ 0 ]->ID, 'rt_biz_profile_permissions', $value );
				}
				$arrReturn[ 'status' ] = true;
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		function rthd_add_new_offering() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST[ 'offering' ] ) ) {
				$term = wp_insert_term( $_POST[ 'offering' ], Rt_Offerings::$offering_slug );
				if ( ! $term instanceof WP_Error && ! empty( $term ) ) {
					$arrReturn = array( 'status' => true );
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

	}

}
