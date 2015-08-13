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
			$op = get_option( 'rt_hd_ticket_creator_migration' );
			if ( empty( $op ) ) {
				$this->ticket_creator_migration();
			}

		}

		function ticket_creator_migration() {
			global $wpdb;
			//*****************Start Ticket post meta migration********************

			// get unique created by users
//			$ticket_creators = $wpdb->get_col('SELECT DISTINCT meta_value
//					FROM '.$wpdb->postmeta.'
//					WHERE meta_key = "_rtbiz_hd_created_by"');

			// update each user using SQL update
//			foreach ( $ticket_creators as $creator ) {
//				if ( ( (string)(int)$creator === $creator ) && (int)$creator > 0  ) {
//					$creator_contact_id = rtbiz_hd_get_contact_id_by_user_id( $creator, true );
//					if ( ! empty( $creator_contact_id ) ) {
//						$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $creator_contact_id ), array( 'meta_key'   => '_rtbiz_hd_created_by',
//						                                                                                     'meta_value' => $creator,
//						), array( '%d' ), array( '%s', '%d' ) );
//					} else {
//						error_log('[Meta migration] Ticket creator user id :'.$creator. " Not found");
//					}
//				}
//			}
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
			$wpdb->query( $q1 );

			//*****************End Ticket post meta migration********************


			//*****************Start Ticket Index table migration*****************
			$table_name = rtbiz_hd_get_ticket_table_name();

			/*$ticket_creators_index = $wpdb->get_col( 'SELECT DISTINCT user_created_by FROM '.$table_name ); // get unique user_creator id so we save update queries
			foreach ( $ticket_creators_index as $creator ) {
				if ( ( (string)(int) $creator === $creator ) && (int)$creator > 0 ) {
					$creator_contact_id = rtbiz_hd_get_contact_id_by_user_id( $creator, true );
					if ( ! empty( $creator_contact_id ) ) {
						// perform update on unique result
						$wpdb->update( $table_name, array( 'user_created_by' => $creator_contact_id ), array( 'user_created_by' => $creator ), array( '%d' ), array( '%d' ) );
					} else {
						error_log("[$table_name migration] Ticket creator user id :".$creator. " Not found");
					}
				}
			}*/

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
			$wpdb->query( $q2 );

			//*****************End Ticket Index table migration*****************


			//*****************Start Follow up user migration*****************
/*			$followup_authors = $wpdb->get_results( 'SELECT comment.user_id as user_id, comment.comment_ID as ID FROM '.$wpdb->comments.' as comment JOIN '.$wpdb->posts.' as posts ON (comment.comment_post_ID = posts.ID) WHERE posts.post_type="'.Rtbiz_HD_Module::$post_type.'"' ); // add existing followup creator to comment meta
			$followup_authors = array_filter( $followup_authors );
			foreach ( $followup_authors as $followup ) {
				if ( ! empty($followup->ID ) && ! empty($followup->user_id ) ) {
					$creator_contact_id = rtbiz_hd_get_contact_id_by_user_id( $followup->user_id, true );
					if ( ! empty( $creator_contact_id ) ) {
						update_comment_meta( $followup->ID, '_rtbiz_hd_followup_author',$creator_contact_id );
					} else {
						error_log("[comment meta migration] Ticket creator user id :".$followup->user_id. " Not found");
					}
				}
			}*/
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
			$wpdb->query( $q3 );
			//*****************End Follow up user migration*****************

			// script is been performed telling that to db so next time this will not run.
			// also auto load will be off because we do not need to load this option on every page load but only when version is changed.
			add_option( 'rt_hd_ticket_creator_migration' ,'yes', false );
		}

	}
}
