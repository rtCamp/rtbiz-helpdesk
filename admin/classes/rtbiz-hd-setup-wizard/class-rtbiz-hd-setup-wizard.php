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

if ( ! class_exists( 'Rtbiz_HD_Setup_Wizard' ) ) {

	/*
	 * This class is for setup wizard part
	 */

	class Rtbiz_HD_Setup_Wizard {

		public static $page_slug = 'rtbiz-hd-setup-wizard';

		var $screen_id;

		/**
		 * Constructor calling hooks
		 */
		public function __construct() {

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_product_sync', $this, 'ajax_product_sync' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_add_new_product', $this, 'ajax_add_new_product' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_delete_product', $this, 'ajax_delete_product' );

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_search_non_hd_user_by_name', $this, 'ajax_get_user_from_name' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_create_contact_with_hd_access', $this, 'ajax_creater_rtbiz_and_give_access_helpdesk' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_search_domain', $this, 'ajax_search_domain' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_domain_user_import', $this, 'ajax_domain_search_and_import' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_import_all_users', $this, 'ajax_import_all_users' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_change_acl', $this, 'ajax_change_acl' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_remove_user', $this, 'ajax_remove_user' );

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_default_assignee_ui', $this, 'ajax_default_assignee' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_default_assignee_save', $this, 'ajax_assignee_save' );

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_outboud_mail_setup_ui', $this, 'ajax_outboud_mail_setup_ui' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_save_outound_setup', $this, 'ajax_save_outound_setup' );

			//Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_user_with_hd_role_list', $this, 'ajax_user_with_hd_role_list' );

			if ( ! empty( $_REQUEST['close_notice'] ) ) {
				delete_option( 'rtbiz_helpdesk_dependency_installed' );
			}
			if ( ! empty( $_REQUEST['finish-wizard'] ) ) {
				update_option( 'rtbiz_hd_setup_wizard_option', 'true' );
			}
		}


		/**
		 * @param $post_type
		 * setup wizard UI
		 */
		public function setup_wizard_ui( $post_type ) { ?>
			<div class="wrap">
				<h2><?php _e( 'rtBiz Helpdesk Setup', RTBIZ_HD_TEXT_DOMAIN ); ?></h2>
				<hr>
				<div class="rthd-row-container">
					<div class="rthd-content-section">
						<h3 class="rthd-option-title"><?php _e( 'Please follow given steps to configure your Helpdesk.', RTBIZ_HD_TEXT_DOMAIN ); ?></h3> <?php
						$wizard = array(
							'Support Page' => array( $this, 'support_page_ui' ),
							'Connect Store' => array( $this, 'connect_store_ui' ),
							'Setup Your Team' => array( $this, 'setup_team' ),
							'Set Assignee' => array( $this, 'set_assignee_ui' ),
							'Mailbox Setup' => array( $this, 'mail_box_ui' ),
							'Finish' => array( $this, 'set_role_ui' ),
						);
						$this->generate_wizard( $wizard ); ?>
					</div> <?php
					rtbiz_hd_admin_sidebar(); ?>
				</div>
			</div> <?php
		}

		// Generate Markup
		public function generate_wizard( $wizard ) {
			if ( ! empty( $wizard ) ) { ?>
				<div id="wizard"><?php
				foreach ( $wizard as $key => $val ) { ?>
						<h3><?php _e( $key ); ?></h3>
						<fieldset> <?php call_user_func( $val ); ?></fieldset> <?php
				} ?>
				</div> <?php
			}
		}

		/**
		 * Support page ui
		 */
		public function support_page_ui() {
			$pages = get_pages(); ?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Support Page', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>
				<p class="rthd-notice"><?php _e( 'Create a support page where your customers can submit tickets.', RTBIZ_HD_TEXT_DOMAIN ); ?></p>
				<div class="rthd-setup-wizard-row">
					<label for="rthd-setup-wizard-support-page"><?php _e( 'Select Support Page ', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
					<select id="rthd-setup-wizard-support-page">
						<option value="-1"><?php _e( '- Create New Page -', RTBIZ_HD_TEXT_DOMAIN ); ?></option>
						<option value="0" selected><?php _e( '-- Select Page --', RTBIZ_HD_TEXT_DOMAIN ); ?></option> <?php
						if ( ! empty( $pages ) ) {
							foreach ( $pages as $page ) {
								echo '<option value="' . $page->ID . '">' . $page->post_title . '</option>';
							}
						} ?>
					</select>
				</div>
				<div class="rthd-setup-wizard-row rthd-setup-wizard-support-page-new-div" style="display: none;">
					<label for="rthd-setup-wizard-support-page-new"><?php _e( 'Create New Page', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
					<input type="text" id="rthd-setup-wizard-support-page-new" name="rthd-setup-wizard-support-page-new-value" />
				</div>
			</div>
			<div class="rthd-support-process rthd-wizard-process" style="display: none;">
				<span><?php _e( 'Setting up support page', RTBIZ_HD_TEXT_DOMAIN ) ?></span>
				<img class="" alt="load" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div> <?php
		}

		/**
		 * connect store UI
		 */
		public function connect_store_ui() { ?>
			<div class="rthd-setup-wizard-controls rthd-setup-store-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Connect Your Store', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>
				<p class="rthd-notice"><?php
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
					_e( 'Looks like you have ' . $active_plugins . ' Active. Helpdesk has selected it for you, You can uncheck that if you want to.', RTBIZ_HD_TEXT_DOMAIN );
				} else {
					_e( 'Looks like none of the following plugins are installed right now. Anyways you can select the plugin you wish to install in future. Helpdesk will automatically sync products when that happens.', RTBIZ_HD_TEXT_DOMAIN );
				} ?>
				</p>
				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-wc" class="" type="checkbox" name="rthd-wizard-store" value="woocommerce" <?php echo $wooactive ? 'checked' : ''; ?> >
					<label for="rthd-wizard-store-wc"><?php _e( 'WooCommerce', RTBIZ_HD_TEXT_DOMAIN ) ?></label>
				</div>
				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-edd" type="checkbox" name="rthd-wizard-store" value="edd" <?php echo $eddactive ? 'checked' : ''; ?> >
					<label for="rthd-wizard-store-edd"><?php _e( 'Easy Digital Downloads', RTBIZ_HD_TEXT_DOMAIN ) ?></label>
				</div>

				<div class="rthd-setup-wizard-row">
					<input id="rthd-wizard-store-custom" type="checkbox" name="rthd-wizard-store" value="custom" >
					<label for="rthd-wizard-store-custom"><?php _e( 'Custom', RTBIZ_HD_TEXT_DOMAIN ) ?></label>
				</div>
				<div class="rthd-setup-wizard-row rthd-wizard-store-custom-div" style="display:none;">
					<label for="rthd-setup-store-new-team"><?php _e( 'Add New Product', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
					<input type="text" id="rthd-setup-store-new-team" />
					<input type="button" id="rthd-setup-store-new-team-submit" value="Add" />
				</div>
				<table class="rthd-setup-wizard-new-product">

				</table>
			</div>
			<div class="rthd-store-process rthd-wizard-process" style="display: none; float: left;">
				<span><?php _e( 'Connecting store and importing existing products', RTBIZ_HD_TEXT_DOMAIN ) ?></span>
				<img class="" alt="load"  src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
			</div> <?php
		}

		/**
		 * setup team UI
		 */
		public function setup_team( $isheader = true ) {
			?>
			<div>
				<div class="rthd-setup-wizard-controls rthd-setup-team-wizard-controls"><?php
				if ( $isheader ) { ?>
						<h3 class="rthd-setup-wizard-title"><?php _e( 'Setup Your Team', RTBIZ_HD_TEXT_DOMAIN ) ?></h3> <?php
				} ?>
					<p class="rthd-notice"><?php
					_e( 'There are 3 ways you can add users to this helpdesk.', RTBIZ_HD_TEXT_DOMAIN );
					if ( self::$page_slug == $_REQUEST['page'] ) {
						_e( ' If you forget somebody now, you can add them later.' );
					}
					_e( ' You (admin) are already added to the helpdesk.', RTBIZ_HD_TEXT_DOMAIN ) ?>
					</p>
					<div class="rthd-setup-team-settings">
						<div class="rthd_wizard_container rthd-setup-wizard-row">
							<div class="rthd-setup-value-container">
								<label for="rthd-user-autocomplete"><?php _e( '1. Search and add users', RTBIZ_HD_TEXT_DOMAIN ) ?></label>
								<input id="rthd-user-autocomplete" type="text" placeholder="Search by name or email" class="rthd-user-autocomplete rthd-setup-wizard-text " />
								<img id="rthd-autocomplete-page-spinner" alt="load" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
								<br/>
								<span class="rthd-warning" style="display: none;"></span>
								<input type="button" class='button rthd-importer-add-contact' value="Add" style="display: none;" />
								<input type="hidden" id="rthd-new-user-email" />

							</div>
						</div>
						<div class="rthd_wizard_container rthd-setup-wizard-row"> <?php
							$domain_name = preg_replace( '/^www\./', '', $_SERVER['SERVER_NAME'] );
							$count_domain_users = rtbiz_hd_search_non_helpdesk_users( $domain_name, true, true ); ?>
							<label for="rthd-add-user-domain"><?php _e( '2. Add all users from domain', RTBIZ_HD_TEXT_DOMAIN ) ?></label>
							<input id="rthd-add-user-domain" class="rthd-setup-wizard-text" type="text" value="<?php echo $domain_name; ?>" placeholder="gmail.com" />
							<div class="rthd-domain-action">
								<span id='rthd-domain-import-message' style=""> Found <?php echo sprintf( _n( '%s user', '%s users', $count_domain_users, RTBIZ_HD_TEXT_DOMAIN ), $count_domain_users ); ?></span>
								<input id="rthd-import-domain-users" class="button" type="button" value="Add Users" />
								<img id="rthd-domain-import-spinner" alt="load" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" /><?php
								wp_nonce_field( get_current_user_id() . 'import-user-domain', 'import_domain' ); ?>
							</div>

						</div>

						<div class="rthd_wizard_container rthd-setup-wizard-row">
							<form><?php
								$helpdesk_users = rtbiz_hd_get_helpdesk_user_ids();
								$count = count_users();
								$remain_wp_users = $count['total_users'] - count( $helpdesk_users ); ?>
								<label><?php _e( '3. Add all WordPress', RTBIZ_HD_TEXT_DOMAIN ) ?><?php echo sprintf( _n( '(%s) user', '(%s) users', $remain_wp_users, RTBIZ_HD_TEXT_DOMAIN ), $remain_wp_users ); ?></label>
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
								<th><span><?php _e( 'Admin', RTBIZ_HD_TEXT_DOMAIN ); ?></span>
									<span class="rthd-tooltip">
										<i class="dashicons dashicons-info rtmicon"></i>
										<span class="rthd-tip-bottom">
											<?php _e( 'Can manage all tickets and Helpdesk settings.', RTBIZ_HD_TEXT_DOMAIN ); ?>
										</span>
									</span>
								</th>
								<th><span><?php _e( 'Editor', RTBIZ_HD_TEXT_DOMAIN ); ?></span>
									<span class="rthd-tooltip">
										<i class="dashicons dashicons-info rtmicon"></i>
										<span class="rthd-tip-bottom">
											<?php _e( 'Can manage all the tickets. No access to settings. ', RTBIZ_HD_TEXT_DOMAIN ); ?>
										</span>
									</span>
								</th>
								<th class="rthd-author-row"><span><?php _e( 'Author', RTBIZ_HD_TEXT_DOMAIN ); ?></span>
									<span class="rthd-tooltip">
										<i class="dashicons dashicons-info rtmicon"></i>
										<span class="rthd-tip-bottom">
											<?php _e( 'Can manage only the tickets assigned to them. No access to settings.', RTBIZ_HD_TEXT_DOMAIN ); ?>
										</span>
									</span>
								</th>
								<th></th>
							</tr>
						</table>
					</div>
				</div>
			</div><?php
		}

		public function set_assignee_ui() { ?>
			<div id="rthd-setup-set-assignee-ui">
			</div><?php
		}

		public function mail_box_ui() { ?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Incoming MailBox Setup', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>
				<p class="rthd-notice"><?php
					_e( 'Connect the mailbox from which you would like to auto-create ticket from incoming e-mails.  Click on next if you want to do that later.', RTBIZ_HD_TEXT_DOMAIN ); ?>
				</p><?php
				rtbiz_hd_mailbox_setup_view( false ); ?>
			</div>
			<div class="rthd-mailbox-setup-process rthd-wizard-process" style="display: none;">
				<span><?php _e( 'Loading outbound emails', RTBIZ_HD_TEXT_DOMAIN ) ?></span>
				<img class="" alt="load" src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
			</div> <?php
		}

		public function set_role_ui() {
			?>
			<div class="rthd-setup-wizard-controls rthd-ACL-change rthd-setup-wizard-row">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Congratulations!', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>
				<p>Your Helpdesk is ready. Click on finish to get started.</p>
			</div>
		<?php
		}


		/***************** ajax mehod *********************/

		/**
		 *  Product save ajax call
		 */
		public function ajax_product_sync() {
			$arrReturn = array( 'status' => false );
			$product = array();
			$defaultproduct = array( 'woocommerce' => 0, 'edd' => 0 );
			if ( ! empty( $_POST['store'] ) ) {
				foreach ( $_POST['store'] as $store ) {
					$product[ $store ] = '1';
				}
			}
			$product = array_merge( $defaultproduct, $product );
			rtbiz_hd_set_settings( 'rtbiz_product_plugin', $product );
			global $rtbiz_products;
			$product = array_keys( $product );
			$rtbiz_products->bulk_insert_products( $product );
			$arrReturn['status'] = true;
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		public function ajax_add_new_product() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['product'] ) ) {
				$term = wp_insert_term( $_POST['product'], Rt_Products::$product_slug );

				if ( ! $term instanceof WP_Error && ! empty( $term ) ) {
					$arrReturn = array( 'status' => true, 'term_id' => $term['term_id'] );
				}
				if ( ! empty ( $term->errors['term_exists'] ) ) {
					$arrReturn = array( 'status' => false, 'product_exists' => 'This product already exists in system.' );
				}

			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		public function ajax_delete_product() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['term_id'] ) ) {
				$term = wp_delete_term( $_POST['term_id'], Rt_Products::$product_slug );

				if ( $term ) {
					$arrReturn = array( 'status' => true );
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * Search all wp user who don't have helpdesk access
		 */
		public function ajax_get_user_from_name() {
			if ( ! isset( $_POST['query'] ) ) {
				wp_die( 'Invalid request Data' );
			}
			$query = $_POST['query'];
			$results = rtbiz_hd_search_non_helpdesk_users( $query, false, false );
			$arrReturn = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $author ) {
					$arrReturn[] = array(
						'id' => $author->ID,
						'label' => $author->display_name,
						'imghtml' => get_avatar( $author->user_email, 25 ),
						'editlink' => rtbiz_get_contact_edit_link( $author->user_email ),
					);
				}
			} else {
				if ( is_email( $_POST['query'] ) ) {
					if ( email_exists( $_POST['query'] ) ) {
						$arrReturn['have_access'] = true;
					} else {
						$arrReturn['show_add'] = true;
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		public function ajax_search_domain() {
			global $wpdb;
			$arrReturn = array();
			if ( ! empty( $_POST['query'] ) ) {
				$prefix = '@';
				if ( substr( $_POST['query'], 0, strlen( $prefix ) ) == $prefix ) {
					$_POST['query'] = substr( $_POST['query'], strlen( $prefix ) );
				}
				$domains = $wpdb->get_col( "SELECT DISTINCT( SUBSTRING_INDEX(user_email,'@',-1)) FROM $wpdb->users where user_email like '%@" . $_POST['query'] . "%'" );
				$domains = array_filter( $domains );
				$arrReturn = $domains;
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * ajax call for getting domain search and import
		 */
		public function ajax_domain_search_and_import() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['count'] ) && ! empty( $_POST['domain_query'] ) && ! empty( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], get_current_user_id() . 'import-user-domain' ) ) {
				if ( 'true' === $_POST['count'] ) { // return counts of users
					$arrReturn['count'] = rtbiz_hd_search_non_helpdesk_users( $_POST['domain_query'], true, true );
					$arrReturn['status'] = true;
				} else {
					$users_to_import = rtbiz_hd_search_non_helpdesk_users( $_POST['domain_query'], false, false );
					//                  $team_id = rtbiz_hd_get_default_support_team();
					$arrReturn['imported_all'] = true;
					foreach ( $users_to_import as $user ) {
						if ( rtbiz_hd_give_user_access( $user->ID, Rtbiz_Access_Control::$permissions['author']['value'], 0 ) ) {
							$arrReturn['imported_users'][] = array(
								'id' => $user->ID,
								'label' => $user->display_name,
								'imghtml' => get_avatar( $user->user_email, 25 ),
								'editlink' => rtbiz_get_contact_edit_link( $user->user_email ),
							);
						} else {
							$arrReturn['not_imported_users'][] = $user;
							$arrReturn['imported_all'] = false;
						}
					}
					$arrReturn['status'] = true;
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * import all users ajax call
		 */
		public function ajax_import_all_users() {
			$arrReturn = array( 'status' => false );
			$LIMIT = 50;
			if ( ! empty( $_POST['import'] ) && ! empty( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], get_current_user_id() . 'import-all-users' ) ) {
				global $wpdb;
				$helpdesk_users = rtbiz_hd_get_helpdesk_user_ids();
				$q = '';
				if ( ! empty( $helpdesk_users ) ) {
					$q = ' WHERE ID not IN (' . implode( ',', $helpdesk_users ) . ') ';
					if ( isset( $_POST['last_import'] ) ) {
						$q .= 'AND ID > ' . intval( $_POST['last_import'] ) . ' ';
					}
				}
				$users_to_import = $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users" . $q . 'LIMIT ' . $LIMIT );
				$arrReturn['imported_all'] = true;
				//              $team_id = rtbiz_hd_get_default_support_team();

				foreach ( $users_to_import as $user ) {
					if ( rtbiz_hd_give_user_access( $user->ID, Rtbiz_Access_Control::$permissions['author']['value'], 0 ) ) {
						$arrReturn['imported_users'][] = array(
							'id' => $user->ID,
							'label' => $user->display_name,
							'imghtml' => get_avatar( $user->user_email, 25 ),
							'editlink' => rtbiz_get_contact_edit_link( $user->user_email ),
						);
					} else {
						$arrReturn['not_imported_users'][] = $user;
						$arrReturn['imported_all'] = false;
					}
				}
				$arrReturn['imported_count'] = count( $users_to_import );
				$arrReturn['status'] = true;
				// count remain users
				$users_to_import = $wpdb->get_var( "SELECT count(ID) FROM $wpdb->users" . $q );
				$users_to_import = $users_to_import - $arrReturn['imported_count'];
				$arrReturn['remain_import'] = $users_to_import;
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * Create wp user if not exist, create rtbiz contact if not exist, if exist and not mapped map it, give helpdesk access
		 * ajax call
		 */
		public function ajax_creater_rtbiz_and_give_access_helpdesk() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['email'] ) || ! empty( $_POST['ID'] ) ) {
				if ( ! empty( $_POST['email'] ) && is_email( $_POST['email'] ) ) {
					// create wordpress user and get it's id for creating rt contact user
					global $rtbiz_hd_contacts;
					$contact = $rtbiz_hd_contacts->insert_new_contact( $_POST['email'],$_POST['email'], true );
					if ( ! empty($contact->ID ) ) {
						$_POST[ 'ID' ] = rtbiz_hd_get_user_id_by_contact_id( $contact->ID );
					}
				}
				if ( ! empty( $_POST['ID'] ) ) {
					// Create dept if not exist in wp-option, if exist assign new user to that taxonomy and add entry for author access to that user
					//                  global $rtbiz_acl_model,$rtbiz_contact;
					$wpuser = get_user_by( 'id', $_POST['ID'] );

					//                  $team_id = rtbiz_hd_get_default_support_team();

					if ( rtbiz_hd_give_user_access( $wpuser, Rtbiz_Access_Control::$permissions['author']['value'], 0 ) ) {
						/* $contact = rtbiz_get_contact_for_wp_user($wpuser->ID);
						  if ( ! empty( $contact[0] ) ) {
						  $user_permissions = get_post_meta( $contact[0]->ID, 'rtbiz_profile_permissions', true );
						  $value =  array( RT_BIZ_HD_TEXT_DOMAIN => Rtbiz_Access_Control::$permissions['author']['value'] );
						  if ( ! empty( $user_permissions ) ){
						  $value = array_merge( $value, $user_permissions );
						  }
						  update_post_meta( $contact[0]->ID, 'rtbiz_profile_permissions', $value );
						  } */
						// do something
						$arrReturn['status'] = true;
						$arrReturn = array_merge( $arrReturn, array(
							'id' => $wpuser->ID,
							'label' => $wpuser->display_name,
							'imghtml' => get_avatar( $wpuser->user_email, 25 ),
							'editlink' => rtbiz_get_contact_edit_link( $wpuser->user_email ),
						) );
					}
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		public function ajax_change_acl() {
			global $rtbiz_acl_model;
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['permission'] ) && ! empty( $_POST['userid'] ) ) {

				//helpdesk role change in custom table
				$rtbiz_acl_model->update_acl( array( 'permission' => $_POST['permission'] ), array( 'userid' => $_POST['userid'], 'module' => RTBIZ_HD_TEXT_DOMAIN ) );
				//rtbiz role change in custom table
				$rtbiz_acl_model->update_acl( array( 'permission' => $_POST['permission'] ), array( 'userid' => $_POST['userid'], 'module' => RTBIZ_TEXT_DOMAIN ) );

				$contact = rtbiz_get_contact_for_wp_user( $_POST['userid'] );
				// update contact meta as we store ACL values in contact meta as well
				if ( ! empty( $contact[0] ) ) {
					$user_permissions = get_post_meta( $contact[0]->ID, 'rtbiz_profile_permissions', true );
					$value = array(
						RTBIZ_HD_TEXT_DOMAIN => $_POST['permission'],
						RTBIZ_TEXT_DOMAIN => $_POST['permission'],
					);
					if ( ! empty( $user_permissions ) ) {
						$value = array_merge( $user_permissions, $value );
					}
					update_post_meta( $contact[0]->ID, 'rtbiz_profile_permissions', $value );
				}
				$arrReturn['status'] = true;
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		public function ajax_remove_user() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['userid'] ) ) {
				global $rtbiz_acl_model;
				$rtbiz_acl_model->remove_acl( array( 'module' => RTBIZ_HD_TEXT_DOMAIN, 'userid' => $_POST['userid'] ) );
				$rtbiz_acl_model->remove_acl( array( 'module' => RTBIZ_TEXT_DOMAIN, 'userid' => $_POST['userid'] ) );
				$arrReturn['status'] = true;
				$contact = rtbiz_get_contact_for_wp_user( $_POST['userid'] );

				rtbiz_remove_contact_to_user( $contact[0]->p2p_from, $contact[0]->p2p_to );
				wp_delete_post( $contact[0]->p2p_from );

				//              $support_team = get_option( 'rtbiz_hd_default_support_team' );
				//              if ( ! empty( $support_team ) && ! empty( $contact[0] ) ){
				//                  wp_remove_object_terms($contact[0]->ID,array($support_team),Rtbiz_Teams::$slug );
				//              }
				if ( ! empty( $contact[0] ) ) {
					$user_permissions = get_post_meta( $contact[0]->ID, 'rtbiz_profile_permissions', true );
					if ( ! empty( $user_permissions[ RTBIZ_HD_TEXT_DOMAIN ] ) ) {
						$user_permissions[ RTBIZ_HD_TEXT_DOMAIN ] = 0;
						$user_permissions[ RTBIZ_TEXT_DOMAIN ] = 0;
						update_post_meta( $contact[0]->ID, 'rtbiz_profile_permissions', $user_permissions );
					}
					delete_post_meta( $contact[0]->ID, 'rtbiz_is_staff_member' );
				}
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * default assignee ui ajax call
		 */
		public function ajax_default_assignee() {
			ob_start();
			$current = get_current_user_id();

			// get product list
			$terms = array();
			global $rtbiz_products;
			if ( isset( $rtbiz_products ) ) {
				add_filter( 'get_terms', array( $rtbiz_products, 'product_filter' ), 10, 3 );
				$terms = get_terms( Rt_Products::$product_slug, array( 'hide_empty' => 0 ) );
				remove_filter( 'get_terms', array( $rtbiz_products, 'product_filter' ), 10, 3 );
			}
			$users = Rtbiz_HD_Utils::get_hd_rtcamp_user(); ?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Select Ticket Assignee', RTBIZ_HD_TEXT_DOMAIN ); ?></h3><?php
				if ( ! empty( $terms ) ) { ?>
					<p class="rthd-notice"> <?php _e( 'Select an assignee for the products we synced in previous setup.', RTBIZ_HD_TEXT_DOMAIN ); ?> </p>
					<div class="rthd-setup-wizard-row">
						<ul><?php
						foreach ( $terms as $tm ) { ?>
								<li>
									<label for="rthd_product<?php echo $tm->term_id ?>"> <?php echo $tm->name ?></label>
									<select class="rthd-setup-assignee" data="<?php echo $tm->term_id ?>"  id="rthd_product<?php echo $tm->term_id ?>"><?php
									echo '<option value="" > -- select an assignee -- </option>';
									foreach ( $users as $user ) {
										echo '<option value="' . $user->ID . '" >' . $user->display_name . '</option>';
									} ?>
									</select>
								</li><?php
						} ?>
						</ul>
					</div><?php
				} else { ?>
					<div class="rthd-setup-wizard-row">
						<label class="rthd-product-default-assignee" for="rthd_product-default"> <strong><?php _e( 'Default Assignee', RTBIZ_HD_TEXT_DOMAIN ); ?> </strong></label>
						<select id="rthd_product-default"><?php
						foreach ( $users as $user ) {
							if ( $user->ID == $current ) {
								$selected = 'selected';
							} else {
								$selected = '';
							}
							echo '<option value="' . $user->ID . '" ' . $selected . '>' . $user->display_name . '</option>';
						} ?>
						</select>
					</div><?php
				} ?>
			</div>
			<div class="rthd-assignee-process rthd-wizard-process" style="display: none;">
				<span>Setting up default assignee for products</span>
				<img src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
			</div><?php
			$comment_html = ob_get_clean();
			header( 'Content-Type: application/json' );
			echo json_encode( array( 'status' => true, 'html' => $comment_html ) );
			die( 0 );
		}

		/**
		 *  Assignee save ajax call
		 */
		public function ajax_assignee_save() {
			$arrReturn = array( 'status' => false );
			if ( ! empty( $_POST['assignee'] ) ) {
				foreach ( $_POST['assignee'] as $assingee ) {
					rtbiz_hd_update_product_meta( 'default_assignee', $assingee['user_ID'], $assingee['term_ID'] );
				}
				$arrReturn['status'] = true;
			}
			//default_assignee
			if ( ! empty( $_POST['default_assignee'] ) && is_numeric( $_POST['default_assignee'] ) ) {
				rtbiz_hd_set_settings( 'rthd_settings_default_user', $_POST['default_assignee'] );
				$arrReturn['status'] = true;
			}

			header( 'Content-Type: application/json' );
			echo json_encode( $arrReturn );
			die( 0 );
		}

		/**
		 * outbound mailbox ui
		 */
		public function ajax_outboud_mail_setup_ui() {
			$system_emails = rtmb_get_module_mailbox_emails( RTBIZ_HD_TEXT_DOMAIN );
			ob_start();
			?>
			<div class="rthd-setup-wizard-controls">
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Outgoing Mail Setup ', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>
				<p class="rthd-notice">Configure settings for the mailbox from where you will like to send Helpdesk e-mails to customers and staff.</p>
				<div id="rthd_outgoing_mailbox_setup_container">
					<input type="hidden" id="rthd_outound_sub-action" name="rthd_outound_sub-action" value="rtbiz_hd_save_outound_setup">
					<?php wp_nonce_field( 'rtbiz_hd_save_outound_setup' ); ?>
					<div class="rthd-setup-wizard-row">
						<label for="rthd_settings_outgoing_email_from_name"> <?php _e( 'Outgoing Emails \'From\' Name' ); ?></label>
						<input type="text" id="rthd_settings_outgoing_email_from_name" name="rthd_settings_outgoing_email_from_name" value="<?php echo get_bloginfo(); ?>" />
					</div>
					<div class="rthd-setup-wizard-row">
						<label for="rthd_settings_outgoing_email_mailbox"> <?php _e( 'Outgoing Emails Mailbox' ); ?></label>
						<select id="rthd_settings_outgoing_email_mailbox" name="rthd_settings_outgoing_email_mailbox">
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
		public function ajax_save_outound_setup() {
			$result = array();
			$result['status'] = false;

			$obj_data = array();
			parse_str( $_POST['data'], $obj_data );

			if ( ! wp_verify_nonce( $obj_data['_wpnonce'], 'rtbiz_hd_save_outound_setup' ) ) {
				$result['error'] = 'Security check false';
				echo json_encode( $result );
				die();
			}

			if ( empty( $obj_data['rthd_settings_outgoing_email_from_name'] ) || empty( $obj_data['rthd_settings_outgoing_email_mailbox'] ) ) {
				$result['error'] = 'Error: Required mailbox field missing';
				echo json_encode( $result );
				die();
			}

			if ( ! empty( $obj_data['rthd_settings_outgoing_email_from_name'] ) ) {
				rtbiz_hd_set_settings( 'rthd_settings_outgoing_email_from_name', $obj_data['rthd_settings_outgoing_email_from_name'] );
			}

			if ( ! empty( $obj_data['rthd_settings_outgoing_email_mailbox'] ) ) {
				rtbiz_hd_set_settings( 'rthd_settings_outgoing_email_mailbox', $obj_data['rthd_settings_outgoing_email_mailbox'] );
			}
			$result['status'] = true;
			echo json_encode( $result );
			die( 0 );
		}

		public function ajax_user_with_hd_role_list() {
			global $wpdb, $rtbiz_acl_model;
			$result = $wpdb->get_results( 'SELECT acl.userid, acl.permission FROM ' . $rtbiz_acl_model->table_name . ' as acl INNER JOIN ' . $wpdb->prefix . 'p2p as p2p on ( acl.userid = p2p.p2p_to ) INNER JOIN ' . $wpdb->posts . " as posts on (p2p.p2p_from = posts.ID )  where acl.module =  '" . RTBIZ_HD_TEXT_DOMAIN . "' and acl.permission != 0 and p2p.p2p_type = '" . rtbiz_get_contact_post_type() . "_to_user' and posts.post_status= 'publish' and posts.post_type= '" . rtbiz_get_contact_post_type() . "' and acl.groupid = 0 " );
			ob_start();
			if ( ! empty( $result ) ) {
				?>
				<h3 class="rthd-setup-wizard-title"><?php _e( 'Set Roles', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>

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
									   value="<?php echo Rtbiz_Access_Control::$permissions['admin']['value']; ?>" <?php echo ( $row->permission == Rtbiz_Access_Control::$permissions['admin']['value'] ) ? 'checked' : ''; ?> />
							</td>
							<td><input type="radio" class="rt-hd-setup-acl" data-id="<?php echo $user->ID; ?>"
									   name="ACL_<?php echo $user->ID; ?>"
									   value="<?php echo Rtbiz_Access_Control::$permissions['editor']['value']; ?>" <?php echo ( $row->permission == Rtbiz_Access_Control::$permissions['editor']['value'] ) ? 'checked' : ''; ?> />
							</td>
							<td><input type="radio" class="rt-hd-setup-acl" data-id="<?php echo $user->ID; ?>"
									   name="ACL_<?php echo $user->ID; ?>"
									   value="<?php echo Rtbiz_Access_Control::$permissions['author']['value']; ?>" <?php echo ( $row->permission == Rtbiz_Access_Control::$permissions['author']['value'] ) ? 'checked' : ''; ?> />
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
	}
}
