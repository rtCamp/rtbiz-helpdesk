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
 * Description of Rt_HD_Tickets_Front
 *
 * @author udit
 *
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Tickets_Front' ) ) {

	/**
	 * Class Rt_HD_Tickets_Front
	 * Initialize the frontend
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rt_HD_Tickets_Front {

		/**
		 * change the title of frontend and template to front end.
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'add_rewrite_rule' ) );
			add_action ( 'init', array( $this, 'add_rewrite_tag' ) );
			add_filter( 'template_include', array( $this, 'template_include' ), 1, 1 );
			add_filter( 'wp_title', array( $this, 'change_title' ), 9999, 1 );
		}

		function add_rewrite_endpoint() {
			global $rt_hd_module;
			$labels    = $rt_hd_module->labels;
			add_rewrite_endpoint( strtolower( $labels['name'] ), EP_ALL );
		}

		function add_rewrite_tag() {
			add_rewrite_tag( '%rtbiz_hd_ticket%', '([^/]*)' );
			add_rewrite_tag( '%rthd_unique_id%', '([^/]*)' );

		}

		function add_rewrite_rule() {
			global $rt_hd_module;
			$labels    = $rt_hd_module->labels;
			add_rewrite_rule( '^' . strtolower( $labels['name'] ) . '/([A-Za-z0-9]*)$', 'index.php?rtbiz_hd_ticket=true&rthd_unique_id=$matches[1]', 'top' );
		}

		/**
		 *
		 * Change the Title of frontend.
		 *
		 * @param $title
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function change_title( $title ) {
			global $rthd_front_page_title;
			if ( isset( $rthd_front_page_title ) && ! empty( $rthd_front_page_title ) ) {
				return $rthd_front_page_title;
			}

			return $title;
		}

		/**
		 * include template for ticket on frontend
		 *
		 * @param $template
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function template_include( $template ) {
			global $wp_query;

			if ( ! isset( $wp_query->query_vars[ Rt_HD_Module::$post_type ] ) ) {
				return $template;
			}

			if ( ! isset( $wp_query->query_vars['rthd_unique_id'] ) || ( isset( $wp_query->query_vars['rthd_unique_id'] ) && empty( $wp_query->query_vars['rthd_unique_id'] ) ) ) {
				return $template;
			}

			if ( ! is_user_logged_in() ) {
				$redirect_url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$login_url = apply_filters( 'rthd_ticket_front_page_login_url', wp_login_url( $redirect_url ) );
				$message = sprintf( '%s <a href="%s">%s</a> %s', __( 'You are not logged in. Please login' ), $login_url, __( 'here' ), __( 'to view this ticket.' ) );
				global $rthd_messages;
				$rthd_messages[] = array( 'type' => 'error', 'message' => $message, 'displayed' => 'no' );
				global $rthd_front_page_title;
				$rthd_front_page_title = __( 'Helpdesk' );

				return rthd_locate_template( 'ticket-error-page.php' );
			}

			$args = array(
				'meta_key'    => '_rtbiz_hd_unique_id',
				'meta_value'  => $wp_query->query_vars['rthd_unique_id'],
				'post_status' => 'any',
				'post_type'   => Rt_HD_Module::$post_type,
			);

			$ticketpost = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				return $template;
			}
			$ticket = $ticketpost[0];

			global $rthd_ticket, $rt_hd_module;
			$rthd_ticket = $ticket;
			global $rthd_front_page_title;
			$labels    = $rt_hd_module->labels;
			$rthd_front_page_title = ucfirst( $labels['name'] ) . ' | ' . $rthd_ticket->post_title;
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
			return rthd_locate_template( 'ticket-front-page.php' );
		}

		function load_scripts() {
			wp_enqueue_style( 'rthd-followup-css', RT_HD_URL . 'app/assets/css/follow-up.css', array(), RT_HD_VERSION, 'all' );
			global $wp_scripts;
			$ui = $wp_scripts->query( 'jquery-ui-core' );
			// tell WordPress to load the Smoothness theme from Google CDN
			$protocol = is_ssl() ? 'https' : 'http';
			$url      = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/" . $ui->ver . '/themes/smoothness/jquery-ui.css';
			if ( ! wp_style_is( 'jquery-ui-smoothness' ) ) {
				wp_enqueue_style( 'jquery-ui-smoothness', $url, array(), RT_HD_VERSION, 'all' );
			}
		}

	}

}
