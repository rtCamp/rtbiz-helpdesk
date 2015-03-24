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

			add_action( 'init', array( $this, 'flush_rewrite_rules' ), 15 );
			add_action( 'wp', array( $this, 'show_original_email' ) );

			add_filter( 'template_include', array( $this, 'template_include' ), 1, 1 );
			add_filter( 'wp_title', array( $this, 'change_title' ), 9999, 1 );
			
			add_action( 'rthd_ticket_front_page_after_header', array( $this, 'set_rthd_ticket_post_data' ) );
		}

		function show_original_email(){
			global $post;

			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			$user_edit_content = current_user_can( $cap );

			if ( ! empty( $_REQUEST['show_original'] ) && 'true' === $_REQUEST['show_original'] && empty( $_REQUEST['comment-id'] ) && $user_edit_content ){
				$data = get_post_meta( $post->ID, '_rt_hd_original_email_body', true );
				echo '<div class="rt_original_email">'.wpautop($data) .'</div>';
				die(0);
			}
			if ( ! empty( $_REQUEST['show_original'] ) && 'true' === $_REQUEST['show_original'] && ! empty( $_REQUEST['comment-id'] ) && $user_edit_content ){
				$data = get_comment_meta( $_REQUEST['comment-id'], 'rt_hd_original_email', true );
				echo '<div class="rt_original_email">'.wpautop($data) .'</div>';
				die(0);
			}
		}

		function restrict_seo_on_helpdesk() {
			add_filter( 'jetpack_enable_open_graph', '__return_false' );
			if( isset ( $GLOBALS['wpseo_og'] ) ) {
				remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), 30 );
			}
			remove_action( 'wpseo_head', array( 'WPSEO_GooglePlus', 'get_instance' ), 35 );
			remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), 40 );
		}

		function admin_bar_edit_menu( $wp_admin_bar ) {
			global $rt_hd_module, $rtbiz_helpdesk_template, $post;
			$labels    = $rt_hd_module->labels;
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			if ( ! empty( $rtbiz_helpdesk_template ) && current_user_can( $cap ) ) {
				$wp_admin_bar->add_menu( array(
					'id' => 'edit',
					'title' => $labels['edit_item'],
					'href' => get_edit_post_link( $post->ID ),
				) );
			}
		}

		function flush_rewrite_rules() {
			if ( is_admin() && 'true' == get_option( 'rthd_flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
				delete_option( 'rthd_flush_rewrite_rules' );
			}
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
			global $wp_query, $post, $rtbiz_helpdesk_template, $rt_hd_module;
			$wrong_unique_id = false;
			if ( empty( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rt_HD_Module::$post_type ) {
				return $template;
			}

			if ( rthd_is_unique_hash_enabled() && ! empty( $_REQUEST['rthd_unique_id'] ) ) {
				$args = array(
					'meta_key'    => '_rtbiz_hd_unique_id',
					'meta_value'  => $_REQUEST['rthd_unique_id'],
					'post_status' => 'any',
					'post_type'   => Rt_HD_Module::$post_type,
				);

				$ticketpost = get_posts( $args );
				if ( ! empty( $ticketpost ) ) {
					$ticket = $ticketpost[0];
					global $rthd_front_page_title;
					$labels    = $rt_hd_module->labels;
					$rthd_front_page_title = $ticket->post_title . ' | ' . get_bloginfo();
					$post = $ticket;
					setup_postdata( $post );
				}
				else {
					$wrong_unique_id = true;
				}
			}
			else if( is_archive('ticket') ) {
				$wrong_unique_id = true;
			}

			if ( empty( $post ) || $wrong_unique_id ) {
				$wp_query->is_404 = true;
				$wp_query->set_404();
				$message = sprintf( '%s ', __( "Sorry! Your requested ticket didn't Found." ) );
				global $rthd_messages;
				$rthd_messages[] = array( 'type' => 'error', 'message' => $message, 'displayed' => 'no' );
				global $rthd_front_page_title;
				$rthd_front_page_title = __( 'Helpdesk' );
				return rthd_locate_template( 'ticket-error-page.php' );

				//return get_404_template();
			}

			$rtbiz_helpdesk_template = true;

			// Restrict SEO META tags for Helpdesk Ticket Page.
			$this->restrict_seo_on_helpdesk();

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

			return rthd_locate_template( 'ticket-front-page.php' );
		}
		
		/**
		 * Set rthd ticket data.
		 */
		function set_rthd_ticket_post_data() {
			global $post;
		
			if ( rthd_is_unique_hash_enabled() && ! empty( $_REQUEST['rthd_unique_id'] ) ) {
				$args = array(
						'meta_key'    => '_rtbiz_hd_unique_id',
						'meta_value'  => $_REQUEST['rthd_unique_id'],
						'post_status' => 'any',
						'post_type'   => Rt_HD_Module::$post_type,
				);
		
				$ticketpost = get_posts( $args );
				if ( ! empty( $ticketpost ) ) {
					$ticket = $ticketpost[0];
					$post = $ticket;
				}
			}
		}
	}

}
