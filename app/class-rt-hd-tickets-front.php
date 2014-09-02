<?php

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Tickets_Front
 *
 * @author udit
 */
if ( ! class_exists( 'Rt_HD_Tickets_Front' ) ) {

	class Rt_HD_Tickets_Front {

		public function __construct() {
			add_filter( 'template_include', array( $this, 'template_include' ), 1, 1 );
			add_filter( 'wp_title', array( $this, 'change_title' ), 9999, 1 );
		}

		function change_title( $title ) {
			global $rthd_front_page_title;
			if ( isset( $rthd_front_page_title ) && ! empty( $rthd_front_page_title ) ) {
				return $rthd_front_page_title;
			}
			return $title;
		}

		function template_include( $template ) {
			global $wp_query;

			if ( ! isset( $wp_query->query_vars[ 'name' ] ) ) {
				return $template;
			}

			$name = $wp_query->query_vars[ 'name' ];

			$post_type = rthd_post_type_name( $name );
			if ( $post_type != Rt_HD_Module::$post_type ) {
				return $template;
			}

			if ( ! isset( $_REQUEST[ 'rthd_unique_id' ] ) || ( isset( $_REQUEST[ 'rthd_unique_id' ] ) && empty( $_REQUEST[ 'rthd_unique_id' ] ) ) ) {
				return $template;
			}

			$args = array(
				'meta_key' => '_rtbiz_helpdesk_ticket_unique_id',
				'meta_value' => $_REQUEST[ 'rthd_unique_id' ],
				'post_status' => 'any',
				'post_type' => $post_type,
			);

			$ticketpost = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				return $template;
			}
			$ticket = $ticketpost[ 0 ];
			if ( $post_type != $ticket->post_type ) {
				return $template;
			}

			global $rthd_ticket;
			$rthd_ticket = $ticket;
			global $rthd_front_page_title;
			$rthd_front_page_title = ucfirst( $name ) . ' | ' . $rthd_ticket->post_title;
			return rthd_locate_template( 'ticket-front-page.php' );
		}

	}

}
