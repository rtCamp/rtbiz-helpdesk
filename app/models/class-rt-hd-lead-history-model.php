<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RtHDLeadHistoryModel
 *
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Lead_History_Model' ) ) {
	class Rt_HD_Lead_History_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct('wp_hd_lead_history');
		}
	}
}