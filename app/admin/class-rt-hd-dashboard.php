<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Dashboard
 * Dash Board backend
 * @author udit
 *
 * @since rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Dashboard' ) ) {
	/**
	 * Class Rt_HD_Dashboard
	 */
	class Rt_HD_Dashboard {

		/**
		 * @var string
		 */
		var $screen_id;
		/**
		 * @var array
		 */
		var $charts = array();

		/**
		 * Construct
		 */
		public function __construct() {
			$this->screen_id = '';
		}

		/**
		 * set actions hook for dashboard
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function setup_dashboard() {
			/* Add callbacks for this screen only */
			add_action( 'load-' . $this->screen_id, array( $this, 'page_actions' ), 9 );
			add_action( 'admin_footer-' . $this->screen_id, array( $this, 'footer_scripts' ) );

			/* Setup Google Charts */
			add_action( 'rthd_after_dashboard', array( $this, 'render_google_charts' ) );
		}

		/**
		 * setter for screen id
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function add_screen_id( $screen_id ) {
			$this->screen_id = $screen_id;
		}

		/**
		 * Prints the jQuery script to initiliase the metaboxes
		 * Called on admin_footer-*
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function footer_scripts() {
			?>
			<script> postboxes.add_postbox_toggles(pagenow);</script>
		<?php
		}

		/**
		 * Actions to be taken prior to page loading. This is after headers have been set.
		 * call on load-$hook
		 * This calls the add_meta_boxes hooks, adds screen options and enqueues the postbox.js script.
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function page_actions() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'rthd-' . Rt_HD_Module::$post_type . '-dashboard' ) {
				do_action( 'add_meta_boxes_' . $this->screen_id, null );
				do_action( 'add_meta_boxes', $this->screen_id, null );

				/* Enqueue WordPress' script for handling the metaboxes */
				wp_enqueue_script( 'postbox' );
			}
		}

		/**
		 * set custom template for Dashboard
		 *
		 * @param $post_type
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function ui( $post_type ) {
			rthd_get_template( 'admin/dashboard.php', array( 'post_type' => $post_type ) );
		}

		/**
		 * render google ui charts
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_google_charts() {
			global $rt_hd_reports;
			$rt_hd_reports->render_chart( $this->charts );
		}
	}
}
