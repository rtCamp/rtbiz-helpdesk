<?php
/**
 * User: spock
 * Date: 9/7/15
 * Time: 7:08 PM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Ticket_Creator_Migration' ) ) {

	class Rtbiz_HD_Ticket_Creator_Migration {
		/*
		 * construct
		 */
		public function __construct() {
			add_action( 'rt_db_update_finished' , array( $this, 'init' ) );
		}

		/*
		 * load current migration class / function
		 */
		public function init() {
			// bail if older helpdesk
			if ( ! defined( 'RTBIZ_HD_VERSION' )
			    || ( defined( 'RTBIZ_HD_VERSION' ) && version_compare( RTBIZ_HD_VERSION ,'1.3.6', '<' ) ) ) {
				return;
			}
			if ( empty( get_option( 'rt_hd_ticket_creator_migration' ) ) ) {
				$this->ticket_creator_migration();
			}

		}

		function ticket_creator_migration() {
			global $wpdb;
			//*****************Start Ticket post meta migration********************

			// get unique created by users
			$ticket_creators = $wpdb->get_col('SELECT DISTINCT meta_value
					FROM '.$wpdb->postmeta.'
					WHERE meta_key = "_rtbiz_hd_created_by"');

			// update each user using SQL update
			foreach ( $ticket_creators as $creator ) {
				if ( ( (string)(int)$creator === $creator ) && (int)$creator > 0  ) {
					$creator_contact_id = rtbiz_hd_get_contact_id_by_user_id( $creator, true );
					if ( ! empty( $creator_contact_id ) ) {
						$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $creator_contact_id ), array( 'meta_key'   => '_rtbiz_hd_created_by',
						                                                                                     'meta_value' => $creator,
						), array( '%d' ), array( '%s', '%d' ) );
					}
				}
			}

			//*****************End Ticket post meta migration********************


			//*****************Start Ticket Index table migration*****************
			$table_name = rtbiz_hd_get_ticket_table_name();

			$ticket_creators_index = $wpdb->get_col( 'SELECT DISTINCT user_created_by FROM '.$table_name ); // get unique user_creator id so we save update queries
			foreach ( $ticket_creators_index as $creator ) {
				if ( ( (string)(int) $creator === $creator ) && (int)$creator > 0 ) {
					$creator_contact_id = rtbiz_hd_get_contact_id_by_user_id( $creator, true );
					if ( ! empty( $creator_contact_id ) ) {
						// perform update on unique result
						$wpdb->update( $table_name, array( 'user_created_by' => $creator_contact_id ), array( 'user_created_by' => $creator ), array( '%d' ), array( '%d' ) );
					}
				}
			}

			//*****************End Ticket Index table migration*****************

			// script is been performed telling that to db so next time this will not run.
			// also auto load will be off because we do not need to load this option on every page load.
			add_option( 'rt_hd_ticket_creator_migration' ,'yes', false );
		}

	}
}
