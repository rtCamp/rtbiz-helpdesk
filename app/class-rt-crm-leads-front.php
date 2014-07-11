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
 * Description of Rt_CRM_Leads_Front
 *
 * @author udit
 */
if ( ! class_exists( 'Rt_CRM_Leads_Front' ) ) {
	class Rt_CRM_Leads_Front {
		public function __construct() {
			add_filter( 'template_include', array( $this, 'template_include' ), 1, 1 );
			add_filter( 'wp_title', array( $this, 'change_title'), 9999, 1 );
		}

		function change_title( $title ) {
			global $rtcrm_front_page_title;
			if( isset( $rtcrm_front_page_title ) && !empty( $rtcrm_front_page_title ) ) {
				return $rtcrm_front_page_title;
			}
			return $title;
		}

		function template_include( $template ) {
			global $wp_query, $rt_crm_module;

			if ( !isset($wp_query->query_vars['name']) ) {
				return $template;
			}

			$name = $wp_query->query_vars['name'];

			$post_type = rtcrm_post_type_name( $name );
			if( $post_type != $rt_crm_module->post_type ) {
				return $template;
			}

			if( ! isset( $_REQUEST['rtcrm_unique_id'] ) || ( isset( $_REQUEST['rtcrm_unique_id'] ) && empty( $_REQUEST['rtcrm_unique_id'] ) ) ) {
				return $template;
			}

			$args = array(
				'meta_key' => 'rtcrm_unique_id',
				'meta_value' => $_REQUEST['rtcrm_unique_id'],
				'post_status' => 'any',
				'post_type' => $post_type,
			);

			$leadpost = get_posts( $args );
			if( empty( $leadpost ) ) {
				return $template;
			}
			$lead = $leadpost[0];
			if( $post_type != $lead->post_type ) {
				return $template;
			}

			global $rtcrm_lead;
			$rtcrm_lead = $lead;
			global $rtcrm_front_page_title;
			$rtcrm_front_page_title = ucfirst( $name ).' | '.$rtcrm_lead->post_title;
			return rtcrm_locate_template( 'lead-front-page.php' );
		}
	}
}
