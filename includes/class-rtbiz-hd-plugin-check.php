<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Plugin_Check' ) ) {

	/**
	 * Class Rt_Biz_Helpdesk
	 * Check Dependency
	 * Main class that initialize the rt-helpdesk Classes.
	 * Load Css/Js for front end
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rtbiz_HD_Plugin_Check {

		private $plugins_dependency = array();

		public function __construct( $plugins_dependency ) {
			$this->plugins_dependency = $plugins_dependency;
		}

		public function rtbiz_hd_check_plugin_dependency() {

			$flag = true;

			if ( ! class_exists( 'Rtbiz' ) || ! did_action( 'rtbiz_init' ) ) {
				$flag = false;
			}

			if ( ! $flag ) {
				add_action( 'admin_init', array( $this, 'rtbiz_hd_install_dependency' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'rtbiz_hd_plugin_check_enque_js' ) );
				add_action( 'wp_ajax_rthd_install_plugin', array( $this, 'rtbiz_hd_install_plugin_ajax' ) );
				add_action( 'wp_ajax_rthd_activate_plugin', array( $this, 'rtbiz_hd_activate_plugin_ajax' ) );
				//      add_action( 'admin_notices', 'rtbiz_hd_admin_notice_dependency_not_installed' );

			}
			return $flag;
		}

		/**
		 * install dependency
		 */
		function rtbiz_hd_install_dependency() {
			$biz_installed = $this->rtbiz_hd_is_plugin_installed( 'rtbiz' );
			$p2p_installed = $this->rtbiz_hd_is_plugin_installed( 'posts-to-posts' );
			$isRtbizActionDone = false;
			$isPtopActionDone = false;
			$string = '';

			if ( ! $biz_installed || ! $p2p_installed ) {
				if ( ! $biz_installed ) {
					$this->rtbiz_hd_install_plugin( 'rtbiz' );
					$isRtbizActionDone = true;
					$string = 'installed and activated <strong>rtBiz</strong> plugin.';
				}
				if ( ! $p2p_installed ) {
					$this->rtbiz_hd_install_plugin( 'posts-to-posts' );
					$isPtopActionDone = true;
					$string = 'installed and activated <strong>posts to posts</strong> plugin.';
				}
				if ( $isRtbizActionDone && $isPtopActionDone ) {
					$string = 'installed and activated <strong>rtBiz</strong> plugin and <strong>posts to posts</strong> plugin.';
				}
			}
//
			$rtbiz_active = $this->rtbiz_hd_is_plugin_active( 'rtbiz' );
			$p2p_active = $this->rtbiz_hd_is_plugin_active( 'posts-to-posts' );
			if ( ! $rtbiz_active || ! $p2p_active ) {
				if ( ! $p2p_active ) {
					$p2ppath = $this->rtbiz_hd_get_path_for_plugin( 'posts-to-posts' );
					$this->rtbiz_hd_activate_plugin( $p2ppath );
					$isRtbizActionDone = true;
					$string = 'activated <strong>posts to posts</strong> plugin.';
				}
				if ( ! $rtbiz_active ) {
					$rtbizpath = $this->rtbiz_hd_get_path_for_plugin( 'rtbiz' );
					$this->rtbiz_hd_activate_plugin( $rtbizpath );
					$isPtopActionDone = true;
					$string = 'activated <strong>rtBiz</strong> plugin.';
				}
				if ( $isRtbizActionDone && $isPtopActionDone ) {
					$string = 'activated <strong>rtBiz</strong> plugin and <strong>posts to posts</strong> plugin.';
				}
			}

			if ( ! empty( $string ) ) {
				$string = 'rtBiz Helpdesk has also  ' . $string;
				update_option( 'rtbiz_helpdesk_dependency_installed', $string );
			}

			if ( $this->rtbiz_hd_check_wizard_completed() ) {
				wp_safe_redirect( admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rthd-rtbiz_hd_ticket-dashboard' ) );
			} else {
				wp_safe_redirect( admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rtbiz-hd-setup-wizard' ) );
			}
		}

		function rtbiz_hd_check_wizard_completed() {
			if ( ! empty( $option ) && 'true' == $option ) {
				return true;
			}
			return false;
		}

		/**
		 * Enqueue js for plugin check
		 */
		public function rtbiz_hd_plugin_check_enque_js() {
			wp_enqueue_script( RTBIZ_HD_TEXT_DOMAIN . '-plugins-dependency', RTBIZ_HD_URL . 'admin/js/rtbiz-plugin-check.js', '', RTBIZ_HD_VERSION, true );
			wp_localize_script( RTBIZ_HD_TEXT_DOMAIN . '-plugins-dependency', 'rtbiz_hd_ajax_url', admin_url( 'admin-ajax.php' ) );
		}

		function rtbiz_hd_install_plugin_ajax() {
			if ( empty( $_POST['plugin_slug'] ) ) {
				die( __( 'ERROR: No slug was passed to the AJAX callback.', RTBIZ_HD_TEXT_DOMAIN ) );
			}
			check_ajax_referer( 'rthd_install_plugin_rtbiz' );

			if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
				die( __( 'ERROR: You lack permissions to install and/or activate plugins.', RTBIZ_HD_TEXT_DOMAIN ) );
			}
			$biz_installed = $this->rtbiz_hd_is_plugin_installed( 'rtbiz' );
			$p2p_installed = $this->rtbiz_hd_is_plugin_installed( 'posts-to-posts' );

			if ( ! $p2p_installed ) {
				$this->rtbiz_hd_install_plugin( 'posts-to-posts' );
			}
			if ( ! $biz_installed ) {
				$this->rtbiz_hd_install_plugin( 'rtbiz' );
			}
			echo 'true';
			die();
		}

		function rtbiz_hd_is_plugin_installed( $slug ) {
			if ( empty( $this->plugins_dependency[ $slug ] ) ) {
				return false;
			}

			if ( $this->rtbiz_hd_is_plugin_active( $slug ) || file_exists( WP_PLUGIN_DIR . '/' . $this->rtbiz_hd_get_path_for_plugin( $slug ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * ajax call for active plugin
		 */
		public function rtbiz_hd_activate_plugin_ajax() {
			if ( empty( $_POST['path'] ) ) {
				die( __( 'ERROR: No slug was passed to the AJAX callback.', RTBIZ_HD_TEXT_DOMAIN ) );
			}
			$rtbizpath = $this->rtbiz_hd_get_path_for_plugin( 'rtbiz' );
			check_ajax_referer( 'rthd_activate_plugin_' . $rtbizpath );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				die( __( 'ERROR: You lack permissions to activate plugins.', RTBIZ_HD_TEXT_DOMAIN ) );
			}
			$rtbiz_active = $this->rtbiz_hd_is_plugin_active( 'rtbiz' );
			$p2p_active = $this->rtbiz_hd_is_plugin_active( 'posts-to-posts' );
			if ( ! $p2p_active ) {
				$p2ppath = $this->rtbiz_hd_get_path_for_plugin( 'posts-to-posts' );
				$this->rtbiz_hd_activate_plugin( $p2ppath );
			}

			if ( ! $rtbiz_active ) {
				$this->rtbiz_hd_activate_plugin( $rtbizpath );
			}

			echo 'true';
			die();
		}

		public function rtbiz_hd_is_plugin_active( $slug ) {
			if ( empty( $this->plugins_dependency[ $slug ] ) ) {
				return false;
			}

			return $this->plugins_dependency[ $slug ]['active'];
		}


		/**
		 * @param $plugin_path
		 * ajax call for active plugin calls this function to active plugin
		 */
		public function rtbiz_hd_activate_plugin( $plugin_path ) {

			$activate_result = activate_plugin( $plugin_path );
			if ( is_wp_error( $activate_result ) ) {
				die( sprintf( __( 'ERROR: Failed to activate plugin: %s', RTBIZ_HD_TEXT_DOMAIN ), $activate_result->get_error_message() ) );
			}
		}

		public function rtbiz_hd_get_path_for_plugin( $slug ) {
			$filename = ( ! empty( $this->plugins_dependency[ $slug ]['filename'] ) ) ? $this->plugins_dependency[ $slug ]['filename'] : $slug . '.php';

			return $slug . '/' . $filename;
		}

		function rtbiz_hd_install_plugin( $plugin_slug ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

			$api = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug, 'fields' => array( 'sections' => false ) ) );

			if ( is_wp_error( $api ) ) {
				die( sprintf( __( 'ERROR: Error fetching plugin information: %s', RTBIZ_HD_TEXT_DOMAIN ), $api->get_error_message() ) );
			}

			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			}

			if ( ! class_exists( 'Rtbiz_HD_Plugin_Upgrader_Skin' ) ) {
				require_once( RTBIZ_HD_PATH . 'admin/class-rt-hd-plugin-upgrader-skin.php' );
			}

			$upgrader = new Plugin_Upgrader( new Rtbiz_HD_Plugin_Upgrader_Skin( array(
				'nonce' => 'install-plugin_' . $plugin_slug,
				'plugin' => $plugin_slug,
				'api' => $api,
			) ) );

			$install_result = $upgrader->install( $api->download_link );

			if ( ! $install_result || is_wp_error( $install_result ) ) {
				// $install_result can be false if the file system isn't writable.
				$error_message = __( 'Please ensure the file system is writable', RTBIZ_HD_TEXT_DOMAIN );

				if ( is_wp_error( $install_result ) ) {
					$error_message = $install_result->get_error_message();
				}

				die( sprintf( __( 'ERROR: Failed to install plugin: %s', RTBIZ_HD_TEXT_DOMAIN ), $error_message ) );
			}

			$activate_result = activate_plugin( $this->rtbiz_hd_get_path_for_plugin( $plugin_slug ) );
			if ( is_wp_error( $activate_result ) ) {
				die( sprintf( __( 'ERROR: Failed to activate plugin: %s', RTBIZ_HD_TEXT_DOMAIN ), $activate_result->get_error_message() ) );
			}
		}

	}
}
