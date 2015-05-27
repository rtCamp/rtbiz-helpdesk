<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Description of Rt_HD_Admin
 * Rt_HD_Admin is main class for admin backend and UI.
 * @author udit
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Admin' ) ) {

	class Rt_HD_Admin {

		private $admin_cap, $editor_cap, $author_cap;

		public function __construct() {

			$this->admin_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );
			$this->editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );
			$this->author_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );

			if ( is_admin() ) {
				$this->hooks();
			}
		}

		/**
		 * Hooks
		 * @since rt-Helpdesk 0.1
		 */
		function hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );
			add_filter( 'pre_insert_term', array( $this, 'remove_wocommerce_actions' ), 10, 2 );

			add_action( 'admin_menu', array( $this, 'add_people_menu' ), 1 );
			add_filter( 'current_screen', array( $this, 'rthd_acl_page_help' ) );

			//upload folder change
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'handle_upload_prefilter' ) );
			add_filter( 'wp_handle_upload', array( $this, 'handle_upload' ) );
		}

		/**
		 * rtbiz page menu "people"
		 */
		function add_people_menu() {
			if ( rthd_check_wizard_completed() ) {
				add_submenu_page( 'edit.php?post_type=' . Rt_HD_Module::$post_type, __( 'Customers' ), __( 'Customers' ), rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' ), esc_url( 'edit.php?post_type=' . rt_biz_get_contact_post_type() . '&rt_contact_group=customer&module=' . RT_HD_TEXT_DOMAIN ) );
				add_submenu_page( 'edit.php?post_type=' . Rt_HD_Module::$post_type, __( 'Staff' ), __( 'Staff' ), rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' ), esc_url( 'edit.php?post_type=' . rt_biz_get_contact_post_type() . '&rt_contact_group=staff&module=' . RT_HD_TEXT_DOMAIN ) );
				add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), __( '---Teams' ), __( '---Teams' ), rt_biz_get_access_role_cap( RT_BIZ_TEXT_DOMAIN, 'editor' ), esc_url( 'edit-tags.php?taxonomy=' . RT_Departments::$slug . '&post_type=' . Rt_HD_Module::$post_type ) );
				/* add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), __( 'Companies' ), __( '--- Companies' ), rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' ), 'edit.php?post_type=' . rt_biz_get_company_post_type() );
				  add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), __( 'Access Control' ), __( '--- Access Control' ), rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' ), Rt_Biz::$access_control_slug, array(
				  $rt_access_control,
				  'acl_settings_ui'
				  ) ); */

				/* $contact_groups_label = apply_filters( 'rtbiz_contact_groups_menu_item_label', __( 'Contact Groups' ) );
				  add_submenu_page( 'edit.php?post_type=' . esc_html( Rt_HD_Module::$post_type ), $contact_groups_label, '--- ' . $contact_groups_label, rt_biz_get_access_role_cap( RT_BIZ_TEXT_DOMAIN, 'editor' ), 'edit-tags.php?taxonomy=' . Rt_Contact::$user_category_taxonomy . '&post_type=' . Rt_HD_Module::$post_type ); */
				/*  */
			}
		}

		/**
		 * add help tab is acl page
		 */
		function rthd_acl_page_help() {
			global $rt_biz_help;
			if ( ! empty( $_GET['post_type'] ) && Rt_HD_Module::$post_type == $_GET['post_type'] && ! empty( $_GET['page'] ) && Rt_Biz::$access_control_slug == $_GET['page'] ) {
				get_current_screen()->add_help_tab( array(
					'id' => 'hd_acl_overview',
					'title' => 'Overview',
					'callback' => array( $this, 'tab_content' ),
				) );
			}
		}

		/**
		 * add helptab contain on acl page
		 *
		 * @param $screen
		 * @param $tab
		 */
		function tab_content( $screen, $tab ) {
			switch ( $tab['id'] ) {
				case 'hd_acl_overview':
					?>
					<p>
					<ul>
						<li><?php _e( 'Admin - can manage all tickets and has full access to settings.', RT_HD_TEXT_DOMAIN ) ?></li>
						<li><?php _e( 'Editor - can manage all tickets but has no access to settings.', RT_HD_TEXT_DOMAIN ) ?></li>
						<li><?php _e( 'Author - can only manage tickets assigned to himself/herself.', RT_HD_TEXT_DOMAIN ) ?></li>
						<li><?php _e( 'No Role - has no access to ticket backend but has read-only acces the web interface of the ticket.', RT_HD_TEXT_DOMAIN ) ?></li>
						<li><?php _e( 'Group Access - has same access as his/her team has.', RT_HD_TEXT_DOMAIN ) ?></li>
					</ul>
					</p> <?php
					break;
			}
		}

		/**
		 * Register CSS and JS
		 * @since rt-Helpdesk 0.1
		 */
		function load_styles_scripts( $hook ) {
			global $post, $pagenow, $rt_hd_setup_wizard;

			$rthd_post_type = '';

			if ( isset( $_GET['post'] ) ) {
				$rthd_post_type = get_post_type( $_GET['post'] );
			} elseif ( isset( $_GET['post_type'] ) && ( 'post-new.php' == $pagenow || 'edit.php' == $pagenow ) ) {
				$rthd_post_type = $_GET['post_type'];
			}
			// include this css everywhere
			wp_enqueue_style( 'rthd-common-css', RT_HD_URL . 'app/assets/css/rthd-common.css', array(), RT_HD_VERSION, 'all' );

			if ( ( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ) ) && Rt_HD_Module::$post_type == $rthd_post_type )
			     || ( in_array( $pagenow, array( 'admin.php', ) ) && 'rthd-setup-wizard' == $_REQUEST['page'] ) ) {

				if ( isset( $post->post_type ) && $post->post_type == Rt_HD_Module::$post_type ) {

					wp_enqueue_script( 'jquery-ui-timepicker-addon', RT_HD_URL . 'app/assets/admin/js/vendors/jquery-ui-timepicker-addon.js', array(
						'jquery-ui-datepicker',
						'jquery-ui-slider',
							), RT_HD_VERSION, true );

					wp_enqueue_script( 'jquery-ui-datepicker' );
				}
				wp_enqueue_script( 'jquery-ui-autocomplete', '', array(
					'jquery-ui-widget',
					'jquery-ui-position',
				), '1.9.2' );

				wp_enqueue_media();
				wp_enqueue_style( 'rthd-admin-css', RT_HD_URL . 'app/assets/admin/css/admin.css', array(), RT_HD_VERSION );
				wp_enqueue_script( 'rthd-admin-js', RT_HD_URL . 'app/assets/admin/js/helpdesk-admin-min.js', array( 'jquery' ), RT_HD_VERSION, true );

			}

			if ( isset( $rthd_post_type ) && in_array( $rthd_post_type, array( rt_biz_get_contact_post_type() ) ) ) {
				wp_enqueue_script( 'rthd-menu-hack-js', RT_HD_URL . 'app/assets/admin/js/rt-custom-status.js', array( 'jquery' ), time(), true );
				wp_localize_script( 'rthd-menu-hack-js', 'rthd_menu', Rt_HD_Module::$post_type );
				$query_arg = '';
				if ( ! empty( $_GET['rt_contact_group'] ) ) {
					$query_arg = '&rt_contact_group=' . $_GET['rt_contact_group'] . '&module=' . RT_HD_TEXT_DOMAIN;
				} else {
					if ( isset( $_REQUEST['post'] ) ) {
						$user = rt_biz_get_wp_user_for_contact( $_REQUEST['post'] );
						if ( in_array( 'administrator', $user[0]->roles ) ) {
							$query_arg = '&rt_contact_group=staff';
						} else {
							$is_staff_member = get_post_meta( $_REQUEST['post'], 'rt_biz_is_staff_member', true );
							if ( 'no' == $is_staff_member ) {
								$query_arg = '&rt_contact_group=customer';
							} else if ( 'yes' == $is_staff_member ) {
								$query_arg = '&rt_contact_group=staff';
							}
						}
						$query_arg .= '&module=' . RT_HD_TEXT_DOMAIN;
					}
				}
				wp_localize_script( 'rthd-menu-hack-js', 'rthd_url', admin_url( 'edit.php?post_type=' . rt_biz_get_contact_post_type() . $query_arg ) );
			}

			if ( isset( $_REQUEST['taxonomy'] ) && isset( $_REQUEST['post_type'] ) && in_array( $_REQUEST['post_type'], array( Rt_HD_Module::$post_type ) ) ) {
				wp_enqueue_script( 'rthd-menu-hack-js', RT_HD_URL . 'app/assets/admin/js/rt-custom-status.js', array( 'jquery' ), time(), true );
				wp_localize_script( 'rthd-menu-hack-js', 'rthd_menu', Rt_HD_Module::$post_type );
				wp_localize_script( 'rthd-menu-hack-js', 'rthd_url', admin_url( 'edit-tags.php?taxonomy=' . $_REQUEST['taxonomy'] . '&post_type=' . $_REQUEST['post_type'] ) );
			}

			$this->localize_scripts();
		}

		/**
		 * Passes data to JS
		 * @since rt-Helpdesk 0.1
		 */
		function localize_scripts() {
			global $post, $pagenow, $wp_scripts;
			$rthd_post_type = isset( $_GET['post'] ) ? get_post_type( $_GET['post'] ) : '';
			if ( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ) ) && Rt_HD_Module::$post_type == $rthd_post_type ) {
				$user_edit = false;
				if ( current_user_can( 'edit_' . Rt_HD_Module::$post_type ) ) {
					$user_edit = true;
				}
				wp_localize_script( 'rthd-admin-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
				wp_localize_script( 'rthd-admin-js', 'rthd_post_type', $rthd_post_type );
				wp_localize_script( 'rthd-admin-js', 'rthd_user_edit', array( $user_edit ) );
			} else {
				wp_localize_script( 'rthd-admin-js', 'rthd_user_edit', array( '' ) );
			}
			wp_localize_script( 'rthd-admin-js', 'adminurl', admin_url() );
			wp_localize_script( 'rthd-admin-js', 'hdDashboardUrl', admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-' . Rt_HD_Module::$post_type . '-dashboard&finish-wizard=yes' ) );
		}

		/**
		 * @param $term
		 * @param $taxonomy
		 *
		 * @return mixed
		 * @since rt-Helpdesk 0.1
		 */
		function remove_wocommerce_actions( $term, $taxonomy ) {
			$attrs = rthd_get_all_attributes();
			$attr_list = array( 'contacts', 'accounts' );
			foreach ( $attrs as $attr ) {
				if ( 'taxonomy' == $attr->attribute_store_as ) {
					$attr_list[] = $attr->attribute_name;
				}
			}
			if ( in_array( $taxonomy, $attr_list ) ) {
				remove_action( 'create_term', 'woocommerce_create_term', 5, 3 );
				remove_action( 'delete_term', 'woocommerce_delete_term', 5, 3 );
			}

			return $term;
		}

		/**
		 * add filter for Update path of helpdesk upload
		 *
		 * @param $file
		 *
		 * @return mixed
		 */
		function handle_upload_prefilter( $file ) {
			$postype = '';
			if ( isset( $_REQUEST['post_type'] ) ) {
				$postype = $_REQUEST['post_type'];
			}
			if ( empty( $postype ) && ! empty( $_REQUEST['post_id'] ) ) {
				$postype = get_post_type( $_REQUEST['post_id'] );
			}
			if ( Rt_HD_Module::$post_type == $postype ) {
				add_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );
			}
			return $file;
		}

		/**
		 * remode filter for Update path of helpdesk upload
		 *
		 * @param $fileinfo
		 *
		 * @return mixed
		 */
		function handle_upload( $fileinfo ) {
			$postype = '';
			if ( isset( $_REQUEST['post_type'] ) ) {
				$postype = $_REQUEST['post_type'];
			}
			if ( empty( $postype ) && ! empty( $_REQUEST['post_id'] ) ) {
				$postype = get_post_type( $_REQUEST['post_id'] );
			}
			if ( Rt_HD_Module::$post_type == $postype ) {
				remove_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );
			}
			return $fileinfo;
		}

		/**
		 * Update path for helpdesk upload
		 *
		 * @param $args
		 *
		 * @return mixed
		 */
		function custom_upload_dir( $args ) {
			$args['path'] = $args['basedir'] . '/' . RT_HD_TEXT_DOMAIN . $args['subdir'];
			$args['url'] = $args['baseurl'] . '/' . RT_HD_TEXT_DOMAIN . $args['subdir'];
			return $args;
		}

	}

}
