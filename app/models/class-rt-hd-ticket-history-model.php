<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RtHDTicketHistoryModel
 * Model for 'wp_hd_ticket_history' table in DB
 * @author udit
 */
if ( !class_exists( 'Rt_HD_Ticket_History_Model' ) ) {
	class Rt_HD_Ticket_History_Model extends RT_DB_Model {
		public function __construct() {
			parent::__construct('wp_hd_ticket_history');
		}
	}
}