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
if ( ! class_exists( 'Rtbiz_HD_Tickets_Front' ) ) {

	/**
	 * Class Rt_HD_Tickets_Front
	 * Initialize the frontend
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rtbiz_HD_Tickets_Front {

		/**
		 * change the title of frontend and template to front end.
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'flush_rewrite_rules' ), 15 );
			add_action( 'wp', array( $this, 'show_original_email' ) );

			add_filter( 'template_include', array( $this, 'template_include' ), 1, 1 );
			add_filter( 'wp_title', array( $this, 'change_title' ), 9999, 1 );
			add_filter( 'wpseo_title', array( $this, 'change_title' ), 9999, 1 );

			add_action( 'rthd_ticket_front_page_after_header', array( $this, 'set_rthd_ticket_post_data' ) );

			add_filter( 'page_template', array( $this, 'helpdesh_error_page_template' ) );
		}

		function helpdesh_error_page_template( $page_template ){
			$slug = 'helpdesk-authentication-error';
			if ( is_page( $slug ) ) {
				if ( ! is_user_logged_in() ){
					if ( ! isset( $_REQUEST['redirect_url'] ) ){
						$_REQUEST['redirect_url'] = '';
					}
					$login_url    = apply_filters( 'rthd_ticket_front_page_login_url', wp_login_url( esc_url( urldecode( $_REQUEST['redirect_url'] ) ) ) );
					$message      = sprintf( '%s <a href="%s">%s</a> %s', __( 'You are not logged in. Please login' ), $login_url, __( 'here' ), __( 'to view this ticket.' ) );
					global $rthd_messages;
					$rthd_messages[] = array( 'type' => 'error rthd-error', 'message' => $message, 'displayed' => 'no' );
					return rtbiz_hd_locate_template( 'ticket-error-page.php' );
				}else{
					wp_redirect( get_post_type_archive_link( Rtbiz_HD_Module::$post_type ) );
					die();
				}

			}
			return $page_template;
		}

		function show_original_email() {
			global $post;

			if ( empty( $_REQUEST['show_original'] ) || 'true' != $_REQUEST['show_original'] ){
				return ;
			}

			$cap               = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
			$user_edit_content = current_user_can( $cap );

			if ( empty( $_REQUEST['comment-id'] ) && $user_edit_content ) {
				$data = get_post_meta( $post->ID, '_rtbiz_hd_original_email_body', true );
				echo '<div class="rt_original_email"><pre style="word-wrap: break-word;white-space: pre-wrap;">' . ( $data ) . '</pre></div>';
				die( 0 );
			}
			if ( ! empty( $_REQUEST['comment-id'] ) && $user_edit_content ) {
				$data = get_comment_meta( $_REQUEST['comment-id'], '_rtbiz_hd_original_email', true );
				echo '<div class="rt_original_email"><pre style="word-wrap: break-word;white-space: pre-wrap;">' . ( $data ) . '</pre></div>';
				die( 0 );
			}
		}

		function restrict_seo_on_helpdesk() {
			add_filter( 'jetpack_enable_open_graph', '__return_false' );
			if ( isset( $GLOBALS['wpseo_og'] ) ) {
				remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), 30 );
			}
			remove_action( 'wpseo_head', array( 'WPSEO_GooglePlus', 'get_instance' ), 35 );
			remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), 40 );
			if ( function_exists( 'wpfbogp_build_head' ) ) {
				remove_action( 'wp_head', 'wpfbogp_build_head', 50 );
			}
		}

		function admin_bar_edit_menu( $wp_admin_bar ) {
			global $rtbiz_hd_module, $rtbiz_helpdesk_template, $post;
			$labels = $rtbiz_hd_module->labels;
			$cap    = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
			if ( ! empty( $rtbiz_helpdesk_template ) && current_user_can( $cap ) ) {
				$wp_admin_bar->add_menu( array(
					'id'    => 'edit',
					'title' => $labels['edit_item'],
					'href'  => get_edit_post_link( $post->ID ),
				) );
			}
		}

		function flush_rewrite_rules() {
			if ( is_admin() && true == get_option( 'rtbiz_hd_flush_rewrite_rules' ) ) {
				flush_rewrite_rules();
				delete_option( 'rtbiz_hd_flush_rewrite_rules' );
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
		function change_title( $title, $separator = '', $separator_location = ''  ) {
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
			global $wp_query, $post, $rtbiz_helpdesk_template, $rtbiz_hd_module, $rthd_front_page_title;
			$wrong_unique_id = false;
			if ( empty( $wp_query->query_vars['post_type'] ) || $wp_query->query_vars['post_type'] != Rtbiz_HD_Module::$post_type ) {
				return $template;
			}

			// Restrict SEO META tags for Helpdesk Ticket Page.
			$this->restrict_seo_on_helpdesk();

			if ( ! is_user_logged_in() ) {
				$redirect_url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$option = 'rtbiz_hd_helpdesk_authentication_error_page_id';
				$option_value = get_option( $option );
				if ( $option_value > 0 && get_post( $option_value ) && ! get_post_status( $option_value ) == 'publish' ){
					wp_redirect( add_query_arg( 'redirect_url', urlencode( $redirect_url ), get_page_link($option_value) ) );
				} else {
					wp_redirect( wp_login_url( $redirect_url ) );
				}
				die();
			}

			if ( ! empty( $post ) && isset( $wp_query->query[ Rtbiz_HD_Module::$post_type ] ) ) {
				global $rtbiz_hd_email_notification;
				$user = wp_get_current_user();
				if ( ! current_user_can( rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' ) ) && current_user_can( rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' ) ) ) {
					$subscriber     = get_post_meta( $post->ID, '_rtbiz_hd_subscribe_to', true );
					$post_author_id = get_post_field( 'post_author', $post->ID );
					$creator = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );

					if ( ! in_array( $user->ID, $subscriber ) && ! in_array( $user->user_email, $subscriber ) && $user->ID != $post_author_id && $creator != $user->ID ) {
						$redirect_url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						$login_url    = apply_filters( 'rthd_ticket_front_page_login_url', wp_login_url( $redirect_url ) );
						$message      = sprintf( '%s ', __( 'You do not have sufficient permissions to access this ticket.' ) );
						global $rthd_messages;
						$rthd_messages[] = array( 'type' => 'error rthd-error', 'message' => $message, 'displayed' => 'no' );
						$rthd_front_page_title = __( 'Helpdesk - Ticket #' . $post->ID );

						return rtbiz_hd_locate_template( 'ticket-404-page.php' );
					}
				} else if ( ! current_user_can( rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' ) ) ) {
					$contacts       = rtbiz_get_post_for_contact_connection( $post->ID, Rtbiz_HD_Module::$post_type );
					$other_contacts = $rtbiz_hd_email_notification->get_contacts( $post->ID );
					$contact_ids    = wp_list_pluck( $contacts, 'ID' );
					$contact_emails = wp_list_pluck( $other_contacts, 'email' );

					$current_contact = '';
					if ( ! empty( $user ) ) {
						$current_contact = rtbiz_get_contact_for_wp_user( $user->ID );
						if ( ! empty( $current_contact[0] ) ) {
							$current_contact = $current_contact[0];
						}
					}
					$creator = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );
					if ( ( empty( $current_contact ) || ( ! in_array( $current_contact->ID, $contact_ids ) && ! in_array( $user->user_email, $contact_emails ) ) ) && $creator != $user->ID ) {
						$redirect_url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						$login_url    = apply_filters( 'rthd_ticket_front_page_login_url', wp_login_url( $redirect_url ) );
						$message      = sprintf( '%s ', __( 'You do not have sufficient permissions to access this ticket.' ) );
						global $rthd_messages;
						$rthd_messages[] = array( 'type' => 'error rthd-error', 'message' => $message, 'displayed' => 'no' );
						$rthd_front_page_title = __( 'Helpdesk - Ticket #' . $post->ID );

						return rtbiz_hd_locate_template( 'ticket-404-page.php' );
					}
				}
			}

			if ( rtbiz_hd_is_unique_hash_enabled() && ! empty( $_REQUEST['rthd_unique_id'] ) ) {
				$args = array(
					'meta_key'    => '_rtbiz_hd_unique_id',
					'meta_value'  => $_REQUEST['rthd_unique_id'],
					'post_status' => 'any',
					'post_type'   => Rtbiz_HD_Module::$post_type,
				);

				$ticketpost = get_posts( $args );
				if ( ! empty( $ticketpost ) ) {
					$ticket = $ticketpost[0];
					global $rthd_front_page_title;
					$labels                = $rtbiz_hd_module->labels;
					$rthd_front_page_title = $ticket->post_title . ' | ' . get_bloginfo();
					$rthd_front_page_title = __( 'Helpdesk - Ticket #' . $ticket->ID );
					$post                  = $ticket;
					setup_postdata( $post );
				} else {
					$wrong_unique_id = true;
				}
			} else if ( is_archive( 'ticket' ) ) {
				$wrong_unique_id = true;
			}

			if ( empty( $post ) || $wrong_unique_id ) {
				if ( isset( $wp_query->query[ Rtbiz_HD_Module::$post_type ] ) ) {
					$message = sprintf( '%s ', __( "<div style='margin-left: 0;'>Sorry! Your requested ticket wasn't found.</div>" ) );
					$rthd_front_page_title = __( 'Helpdesk - Ticket Not Found' );
				} else {
					$message = '';
					$rthd_front_page_title = __( 'Helpdesk - Tickets' );
				}
				global $rthd_messages;
				$rthd_messages[] = array( 'type' => 'error rthd-error', 'message' => $message, 'displayed' => 'no' );


				return rtbiz_hd_locate_template( 'ticket-404-page.php' );

				//return get_404_template();
			}

			$rtbiz_helpdesk_template = true;
			$rthd_front_page_title = __( 'Helpdesk - Ticket #' . $post->ID );

			return rtbiz_hd_locate_template( 'ticket-front-page.php' );
		}

		/**
		 * Set rthd ticket data.
		 */
		function set_rthd_ticket_post_data() {
			global $post;

			if ( rtbiz_hd_is_unique_hash_enabled() && ! empty( $_REQUEST['rthd_unique_id'] ) ) {
				$args = array(
					'meta_key'    => '_rtbiz_hd_unique_id',
					'meta_value'  => $_REQUEST['rthd_unique_id'],
					'post_status' => 'any',
					'post_type'   => Rtbiz_HD_Module::$post_type,
				);

				$ticketpost = get_posts( $args );
				if ( ! empty( $ticketpost ) ) {
					$ticket = $ticketpost[0];
					$post   = $ticket;
				}
			}
		}
	}

}
