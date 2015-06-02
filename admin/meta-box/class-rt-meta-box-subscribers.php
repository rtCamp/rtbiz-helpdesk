<?php
/**
 * Don't load this file directly!
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
 * Description of RT_HD_Admin_Meta_Boxes
 *
 * @since rt-Helpdesk 0.1
 */
if ( ! class_exists( 'RT_Meta_Box_Subscribers ' ) ) {

	class RT_Meta_Box_Subscribers {

		/**
		 * Output the metabox
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function ui( $post ) {

			global $rtbiz_hd_module;

			$post_type = Rtbiz_HD_Module::$post_type;

			$all_hd_participants = array();
			if ( isset( $post->ID ) ) {
				$comments = get_comments(
					array(
						'order'     => 'DESC',
						'post_id'   => $post->ID,
						'post_type' => $post_type,
					) );
					foreach ( $comments as $comment ) {
						$participants = '';
						$to           = get_comment_meta( $comment->comment_ID, '_email_to', true );
						if ( ! empty( $to ) ) {
							$participants .= $to . ',';
						}
						$cc = get_comment_meta( $comment->comment_ID, '_email_cc', true );
						if ( ! empty( $cc ) ) {
							$participants .= $cc . ',';
						}
						$bcc = get_comment_meta( $comment->comment_ID, '_email_bcc', true );
						if ( ! empty( $bcc ) ) {
							$participants .= $bcc;
						}

						if ( ! empty( $participants ) ) {
							$p_arr               = explode( ',', $participants );
							$p_arr               = array_unique( $p_arr );
							$all_hd_participants = array_merge( $all_hd_participants, $p_arr );
						}
					}
					$all_hd_participants = array_filter( array_unique( $all_hd_participants ) );
			}

			$get_assigned_to = get_post_meta( $post->ID, '_rtbiz_hd_subscribe_to', true );
			if ( empty( $get_assigned_to ) ) {
				$get_assigned_to = array();
			}

			$results           = Rt_HD_Utils::get_hd_rtcamp_user();
			$arrCommentReply   = array();
			$arrSubscriberUser = array();
			$subScribetHTML    = '';
			if ( ! empty( $results ) ) {

				foreach ( $results as $author ) {

					$subscriber_flag = true;
					if ( $post->post_author == $author->ID ) {
						continue;
					}
					foreach ( $arrSubscriberUser as $s ) {
						if ( $s['id'] == $author->ID ) {
							$subscriber_flag = false;
							break;
						}
					}

					if ( $get_assigned_to && ! empty( $get_assigned_to ) && in_array( $author->ID, $get_assigned_to ) ) {
						if ( in_array( $author->user_email, $all_hd_participants ) ) {
							$key = array_search( $author->user_email, $all_hd_participants );
							if ( false !== $key ) {
								unset( $all_hd_participants[ $key ] );
							}
						}

						if ( $subscriber_flag ) {
							$subScribetHTML .= "<li id='subscribe-auth-" . $author->ID . "' class='contact-list'>" .
									get_avatar( $author->user_email, 24 ) .
									"<a href='#removeSubscriber' class='delete_row'><span class='dashicons dashicons-dismiss'></span></a>" .
									"<br/><a target='_blank' class='subscribe-title heading' title='" . $author->display_name . "' href='" . rtbiz_hd_biz_user_profile_link( $author->user_email ) . "'>" . $author->display_name . '</a>' .
									"<input type='hidden' name='subscribe_to[]' value='" . $author->ID . "' /></li>";
						}
					}

					if ( $subscriber_flag ) {
						$arrSubscriberUser[] = array(
							'id' => $author->ID,
							'label' => $author->display_name,
							'imghtml' => get_avatar( $author->user_email, 24 ),
							'user_edit_link' => rtbiz_hd_biz_user_profile_link( $author->user_email ),
						);
					}

					$arrCommentReply[] = array(
						'userid'  => $author->ID,
						'label'   => $author->display_name,
						'email'   => $author->user_email,
						'contact' => false,
						'imghtml' => get_avatar( $author->user_email, 24 ),
					);
				}
			} ?>

			<div class="">
			<!--<span class="prefix"
			      title="<?php /*_e( 'staff', RT_BIZ_HD_TEXT_DOMAIN ); */ ?>"><label><strong><?php /*_e( 'Subscribers', RT_BIZ_HD_TEXT_DOMAIN ); */ ?></strong></label></span>-->
			<script>
				var arr_subscriber_user =<?php echo json_encode( $arrSubscriberUser ); ?>;
			</script>
			<p>
				<input type="text" placeholder="Type Staff Name to select" id="subscriber_user_ac"/>
			</p>
			<ul id="divSubscriberList" class="">
				<?php echo balanceTags( $subScribetHTML ); ?>
			</ul>
			</div><?php
			do_action( 'rt_hd_after_ticket_information', $post );
		}

		/**
		 * Save meta box data
		 *
		 * @since rt-Helpdesk 0.1
		 *
		 * @param $post_id
		 * @param $post
		 */
		public static function save( $post_id, $post ) {

			global $rt_hd_tickets_operation;

			$newTicket = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] = 'inline-save' ) ? get_post( $_REQUEST['post_ID'] ) : $_POST['post'];
			$newTicket = (array) $newTicket;

			$rt_hd_tickets_operation->ticket_subscribe_update( $_POST['subscribe_to'], $newTicket['post_author'], $post_id );
		}

	}

}
