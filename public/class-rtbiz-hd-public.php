<?php
/**
 * The public-facing functionality of the plugin.
 * User: spock
 */

class Rtbiz_HD_Public {

	/**
	 * Initialize the class and set its properties.
	 */
	function __construct() {
		Rtbiz_HD::$loader->add_action( 'init', $this, 'init', 6 );
	}

	public function enqueue_styles() {
		global $wp_query, $post;

		// include this css everywhere
		wp_enqueue_style( 'rthd-common-css', RTBIZ_HD_URL. 'public/css/rthd-common.css', array(), RTBIZ_HD_VERSION, 'all' );

		// bail if not helpdesk
		if ( ! isset( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rtbiz_HD_Module::$post_type || empty( $post ) ) {
			return;
		}
		wp_enqueue_style( 'rthd-main-css', RTBIZ_HD_URL . 'public/css/rthd-main.css', array(), RTBIZ_HD_VERSION, 'all' );
		//fancybox
		wp_enqueue_style( 'jquery-fancybox', RTBIZ_HD_URL . 'public/css/jquery.fancybox.css', array(), RTBIZ_HD_VERSION, 'all' );

	}

	public function enqueue_scripts() {
		global $wp_query, $post;

		// bail if not helpdesk
		if ( ! isset( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rtbiz_HD_Module::$post_type || empty( $post ) ) {
			return;
		}

		wp_enqueue_script( 'rthd-app-js', RTBIZ_HD_URL . 'public/js/helpdesk-min.js', array( 'jquery' ), RTBIZ_HD_VERSION, true );

		//fancybox
		wp_enqueue_script( 'jquery-fancybox', RTBIZ_HD_URL . 'public/js/vendors/lightbox/jquery.fancybox.pack.js', array( 'jquery' ), RTBIZ_HD_VERSION, true );
		$this->localize_scripts();
	}

	/**
	 * This is functions localize values for JScript
	 * @since 0.1
	 */
	function localize_scripts() {

		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$user_edit = false;

		if ( wp_script_is( 'rthd-app-js' ) ) {
			wp_localize_script( 'rthd-app-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
			wp_localize_script( 'rthd-app-js', 'rtbiz_hd_post_type', get_post_type( $post->ID ) );
			wp_localize_script( 'rthd-app-js', 'rtbiz_hd_user_edit', array( $user_edit ) );
		}

		return true;
	}

	/**
	 * Initialize the frontend.
	 *
	 * Load ticket front class on init
	 */
	function init() {
		global $rtbiz_hd_tickets_front;
		$rtbiz_hd_tickets_front = new Rtbiz_HD_Tickets_Front();
	}
}
