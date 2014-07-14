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
 * Description of RtHDGravityFieldsMappingModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Gravity_Fields_Mapping_Model' ) ) {
	class Rt_HD_Gravity_Fields_Mapping_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct( 'wp_hd_gravity_fields_mapping' );
		}

		function get_all_mappings(){
			$gravity_mappings = parent::get( array() );
			return $gravity_mappings;
		}

		function get_mapping( $form_id = '' ) {
			$args = array();
			if(!empty($form_id)) {
				$args['form_id'] = $form_id;
			}
			return parent::get( $args );
		}

		function update_mapping( $data, $where ) {
			return parent::update( $data, $where );
		}

		function add_mapping( $data ) {
			$data['create_date'] = current_time( 'mysql' );
			$data['enable'] = 'yes';
			return parent::insert( $data );
		}

		function delete_mapping( $where ) {
			return parent::delete( $where );
		}
	}
}
