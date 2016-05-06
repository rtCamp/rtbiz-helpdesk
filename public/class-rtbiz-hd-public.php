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
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style( 'rthd-common-css', RTBIZ_HD_URL. 'public/css/rthd-common.css', array(), RTBIZ_HD_VERSION, 'all' );

		// bail if not helpdesk
		if ( ! isset( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rtbiz_HD_Module::$post_type || empty( $post ) ) {
			return;
		}
		wp_enqueue_style( 'rthd-main-css', RTBIZ_HD_URL . 'public/css/rthd-main.css', array(), RTBIZ_HD_VERSION, 'all' );
		//fancybox
        if ( is_rtbiz_hd_allow_fancybox_for_attachments() ) {
            wp_enqueue_style( 'jquery-fancybox', RTBIZ_HD_URL . 'public/css/jquery.fancybox.css', array(), RTBIZ_HD_VERSION, 'all' );
        }		

	}

	public function enqueue_scripts() {
		global $wp_query, $post;

		wp_enqueue_script( 'rthd-app-public-js', RTBIZ_HD_URL . 'public/js/helpdesk-shortcode-min.js', array( 'jquery' ), RTBIZ_HD_VERSION, true );
		wp_localize_script( 'rthd-app-public-js', 'ajaxurl', admin_url( 'admin-ajax.php' ) );

		// bail if not helpdesk
		if ( ! isset( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rtbiz_HD_Module::$post_type || empty( $post ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_script( 'rthd-app-js', RTBIZ_HD_URL . 'public/js/helpdesk-min.js', array( 'jquery' ), RTBIZ_HD_VERSION, true );

		wp_localize_script( 'rthd-app-js', 'rtbiz_hd_supported_extensions', implode( ',', rtbiz_hd_get_supported_extensions() ) );

//		wp_enqueue_script( 'rthd-markdown-js', RTBIZ_HD_URL . 'public/js/vendors/markdown/showdown.js', array( 'jquery' ), RTBIZ_HD_VERSION, true );
//		wp_enqueue_script( 'rthd-markdown-ui-js', RTBIZ_HD_URL . 'public/js/vendors/markdown/showdown-gui.js', array( 'rthd-markdown-js' ), RTBIZ_HD_VERSION, true );
//		wp_enqueue_script( 'rthd-markdown-table-js', RTBIZ_HD_URL . 'public/js/vendors/markdown/showdown-table.js', array( 'rthd-markdown-js' ), RTBIZ_HD_VERSION, true );
//		wp_enqueue_script( 'rthd-markdown-github-js', RTBIZ_HD_URL . 'public/js/vendors/markdown/showdown-github.js', array( 'rthd-markdown-js' ), RTBIZ_HD_VERSION, true );
//		wp_enqueue_script( 'rthd-markdown-prettify-js', RTBIZ_HD_URL . 'public/js/vendors/markdown/showdown-prettify.js', array( 'rthd-markdown-js' ), RTBIZ_HD_VERSION, true );

		//fancybox
        if ( is_rtbiz_hd_allow_fancybox_for_attachments() ) {
            wp_enqueue_script( 'jquery-fancybox', RTBIZ_HD_URL . 'public/js/vendors/lightbox/jquery.fancybox.pack.js', array( 'jquery' ), RTBIZ_HD_VERSION, true );
        }
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
        
        $rthd_fancybox_localize = ( is_rtbiz_hd_allow_fancybox_for_attachments() ) ? 'true' : 'false';

		if ( wp_script_is( 'rthd-app-js' ) ) {
			wp_localize_script( 'rthd-app-js', 'rtbiz_hd_post_type', get_post_type( $post->ID ) );
            wp_localize_script( 'rthd-app-js', 'rtbiz_fancybox_allow', $rthd_fancybox_localize );
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
