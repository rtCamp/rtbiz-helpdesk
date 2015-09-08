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
			    || ( defined( 'RTBIZ_HD_VERSION' ) && version_compare( RTBIZ_HD_VERSION ,'1.5', '<' ) ) ) {
				return;
			}
			$op = get_option( 'rt_hd_ticket_creator_migration' );
			if ( empty( $op ) ) {
				$this->ticket_creator_migration();
			}

		}

		function ticket_creator_migration() {
			global $wpdb;
			rtbiz_hd_log("Migration for userid to contact id started");

			//*****************Start Ticket post meta migration********************

			$q1 = $wpdb->prepare( "UPDATE
					{$wpdb->postmeta} pm
						JOIN
					{$wpdb->prefix}p2p p2p
						ON (
							p2p.p2p_to = pm.meta_value
								AND
							pm.meta_key = %s
								AND
							p2p.p2p_type = %s
							)
					SET pm.meta_value =  p2p.p2p_from",
				'_rtbiz_hd_created_by',
				'contact_to_user'
			);
			rtbiz_hd_log("Migration post_meta start");
			$wpdb->query( $q1 );
			rtbiz_hd_log("Migration post_meta finish");

			//*****************End Ticket post meta migration********************


			//*****************Start Ticket Index table migration*****************

			$table_name = rtbiz_hd_get_ticket_table_name();
			$q2 = $wpdb->prepare( "UPDATE
				{$table_name} ti
					JOIN
				{$wpdb->prefix}p2p p2p
					ON (
						p2p.p2p_to = ti.user_created_by
							AND
						p2p.p2p_type = %s
						)
				SET ti.user_created_by =  p2p.p2p_from",
				'contact_to_user'
			);
			rtbiz_hd_log("Migration ticket index table start");
			$wpdb->query( $q2 );
			rtbiz_hd_log("Migration ticket index table finish");

			//*****************End Ticket Index table migration*****************


			//*****************Start Follow up user migration*****************
			$q3 = $wpdb->prepare( "
					INSERT INTO
						{$wpdb->commentmeta}
							(comment_id,meta_key,meta_value)
									SELECT
										cmt.comment_post_ID, %s, p2p.p2p_from
											FROM
											{$wpdb->comments} cmt
											JOIN
											{$wpdb->prefix}p2p p2p
												ON ( p2p.p2p_to = cmt.user_id AND p2p.p2p_type = %s  )
											JOIN
											{$wpdb->posts} posts
												ON (
														cmt.comment_post_ID = posts.ID
													AND
														posts.post_type = %s
													)
								",
				'_rtbiz_hd_followup_author',
				'contact_to_user',
				'ticket'
			);
			rtbiz_hd_log("Migration comment meta add start");
			$wpdb->query( $q3 );
			rtbiz_hd_log("Migration comment meta add finish");
			//*****************End Follow up user migration*****************

			//*****************Start Email ids migration*****************
			$emails_header_ids = array(
				'_rtbiz_hd_messageid'	=> '_rtlib_messageid',
				'_rtbiz_hd_references'	=> '_rtlib_references',
				'_rtbiz_hd_inreplyto'	=> '_rtlib_inreplyto',
			);
			rtbiz_hd_log("Migration Email message-id start");
			foreach ( $emails_header_ids as $key => $val ) {
				$meta_update_q = $wpdb->prepare('
						UPDATE '.$wpdb->postmeta.'
							SET meta_key = %s
							WHERE meta_key = %s
				', $val, $key);
				$wpdb->query($meta_update_q);

				$c_meta_update_q = $wpdb->prepare('
						UPDATE '.$wpdb->commentmeta.'
							SET meta_key = %s
							WHERE meta_key = %s
				', $val, $key);
				$wpdb->query($c_meta_update_q);
			}


			$wpdb->query( $q3 );
			rtbiz_hd_log("Migration Email message-id finish");
			//*****************End Email ids migration*****************

			// script is been performed telling that to db so next time this will not run.
			// also auto load will be off because we do not need to load this option on every page load but only when version is changed.
			add_option( 'rt_hd_ticket_creator_migration' ,'yes', false );
			rtbiz_hd_log("Migration for userid to contact id Over");

		}

	}
}
