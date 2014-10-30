<?php
/**
 * Don't load this file directly
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
 * Description of Rt_HD_Gravity_Form_Mapper
 *
 * @author dipesh
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Gravity_Form_Mapper' ) ) {

	/**
	 * Class Rt_HD_Gravity_Form_Mapper
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rt_HD_Gravity_Form_Mapper {

		/**
		 * Calling to Set Hooks
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {
			$this->hooks();
		}

		/**
		 * Hook function
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function hooks() {
			add_action( 'wp_ajax_helpdesk_delete_mapping', array( $this, 'delete_mapping_ajax' ) );
			add_action( 'wp_ajax_helpdesk_enable_mapping', array( $this, 'enable_mapping_ajax' ) );

		}

		/**
		 * delete mapping for gravity form using AJAX
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function delete_mapping_ajax() {
			global $rt_hd_gravity_fields_mapping_model;
			$response = array();
			if ( ! isset( $_POST['action'] ) || $_POST['action'] != 'helpdesk_delete_mapping' || ! isset( $_POST['mapping_id'] ) ) {
				die( 0 );
			}
			$response['status'] = $rt_hd_gravity_fields_mapping_model->delete_mapping( array( 'id' => $_POST['mapping_id'] ) );
			echo json_encode( $response );
			die( 0 );
		}

		/**
		 * enable gravity form mapping via ajax call
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function enable_mapping_ajax() {
			global $rt_hd_gravity_fields_mapping_model;
			$response = array();
			if ( ! isset( $_POST['action'] ) || $_POST['action'] != 'helpdesk_enable_mapping' || ! isset( $_POST['mapping_id'] ) ) {
				die( 0 );
			}
			$data               = array( 'enable' => isset( $_POST['mapping_enable'] ) ? $_POST['mapping_enable'] == 'true' ? 'yes' : 'no' : 'no' );
			$where              = array( 'id' => $_POST['mapping_id'] );
			$response['status'] = $rt_hd_gravity_fields_mapping_model->update_mapping( $data, $where );
			echo json_encode( $response );
			die( 0 );
		}

		/**
		 * Setting up UI
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function ui() {
			global $rt_hd_gravity_fields_mapping_model;
			$args           = array();
			$gravity_fields = $rt_hd_gravity_fields_mapping_model->get_all_mappings();
			foreach ( $gravity_fields as $key => $gravity_field ) {
				if ( class_exists( 'RGFormsModel' ) ) {
					$forms = RGFormsModel::get_forms();
				}
				if ( isset( $forms ) && ! empty( $forms ) ) {
					foreach ( $forms as $form ) {
						if ( $form->id == $gravity_field->form_id ) {
							$gravity_fields[ $key ]->form_name = $form->title;
							break;
						}
					}
				}
			}
			$args['gravity_fields'] = $gravity_fields;
			include dirname( __FILE__ ) . '/../../templates/admin/list-gravity-form-mapper.php';
		}


	}

}

