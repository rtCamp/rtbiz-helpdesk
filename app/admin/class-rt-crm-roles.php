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
 * Description of Rt_CRM_Roles
 *
 * @author udit
 */
if( !class_exists( 'Rt_CRM_Roles' ) ) {
	class Rt_CRM_Roles {

//		public $global_caps = array(
//			'manage_wp_crm' => 'manage_wp_crm',
//			'manage_attributes' => 'manage_attributes',
//
//			'manage_rtcrm_terms' => 'manage_rtcrm_terms',
//			'edit_rtcrm_terms' => 'edit_rtcrm_terms',
//			'delete_rtcrm_terms' => 'delete_rtcrm_terms',
//			'assign_rtcrm_terms' => 'assign_rtcrm_terms',
//
//			'manage_gravity_import' => 'manage_gravity_import',
//
//			'manage_wp_crm_settings' => 'manage_wp_crm_settings',
//		);

//		public function __construct() {
//			$this->register_roles();
//
//			add_filter( 'editable_roles', array( $this, 'remove_wp_crm_roles' ) );
//		}

//		function remove_wp_crm_roles( $roles ) {
//			unset( $roles['rt_wp_crm_manager'] );
//			// Add admin & user roles
//			return $roles;
//		}

//		function register_roles() {
//
//			if ( isset( $_REQUEST['rt_wp_crm_reset_roles'] ) && ! empty( $_REQUEST['rt_wp_crm_reset_roles'] ) ) {
//				remove_role( 'rt_wp_crm_manager' );
//			}
//
//			global $rt_crm_module;
//			$role = get_role( 'rt_wp_crm_manager' );
//			$post_type = $rt_crm_module->post_type;
//			if( empty( $role ) ) {
//
//				$caps = array(
//					$this->global_caps['manage_wp_crm'] => true,
//					$this->global_caps['manage_attributes'] => true,
//					$this->global_caps['manage_gravity_import'] => true,
//					$this->global_caps['manage_wp_crm_settings'] => true,
//
//					"edit_{$post_type}" => true,
//					"read_{$post_type}" => true,
//					"delete_{$post_type}" => true,
//					"edit_{$post_type}s" => true,
//					"edit_others_{$post_type}s" => true,
//					"publish_{$post_type}s" => true,
//					"read_private_{$post_type}s" => true,
//					"delete_{$post_type}s" => true,
//					"delete_private_{$post_type}s" => true,
//					"delete_published_{$post_type}s" => true,
//					"delete_others_{$post_type}s" => true,
//					"edit_private_{$post_type}s" => true,
//					"edit_published_{$post_type}s" => true,
//
//					"manage_{$post_type}_closing_reason" => true,
//					"edit_{$post_type}_closing_reason" => true,
//					"delete_{$post_type}_closing_reason" => true,
//					"assign_{$post_type}_closing_reason" => true,
//
//					$this->global_caps['manage_rtcrm_terms'] => true,
//					$this->global_caps['edit_rtcrm_terms'] => true,
//					$this->global_caps['delete_rtcrm_terms'] => true,
//					$this->global_caps['assign_rtcrm_terms'] => true,
//				);
//
//				$caps = array_merge( $caps, rt_biz_get_organization_capabilities() );
//				$caps = array_merge( $caps, rt_biz_get_person_capabilities() );
//				$caps = array_merge( $caps, rt_biz_get_dependent_capabilities() );
//
//				add_role( 'rt_wp_crm_manager', __( 'WordPress CRM Manager' ), $caps );
//			}
//
//			if ( isset( $_REQUEST['rt_wp_crm_reset_roles'] ) && ! empty( $_REQUEST['rt_wp_crm_reset_roles'] ) ) {
//				$users = get_users( array( 'role' => 'rt_wp_crm_manager' ) );
//				foreach ( $users as $user ) {
//					$u_obj = new WP_User( $user );
//					$u_obj->remove_role( 'rt_wp_crm_manager' );
//					$u_obj->add_role( 'rt_wp_crm_manager' );
//				}
//			}
//
//			// Add caps for admin & user too
//		}
	}
}
