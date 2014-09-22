<?php
/**
 * User: spock
 * Date: 19/9/14
 * Time: 4:35 PM
 */

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
 * Description of RT_Meta_Box_Ticket_Comments
 *
 * @since rt-Helpdesk 0.1
 */

if ( ! class_exists( 'RT_Meta_Box_Ticket_Comments' ) ) {
	class RT_Meta_Box_Ticket_Comments {


		static function author_comment( $comment ) {
			?>
			<li class="self">
				<div class="avatar">
					<?php echo get_avatar( $comment, 40 ); ?>
				</div>
				<div class="messages">
					<p><?php echo esc_attr( $comment->comment_content ); ?>
					</p>

					<time title="<?php echo esc_attr( $comment->comment_date ); ?>"
					      datetime="<?php echo esc_attr( $comment->comment_date ); ?>"><span
							title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo esc_attr( $comment->comment_author ); ?> </span>
						• <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ) ); ?>
					</time>
				</div>
			</li>
		<?php
		}

		/**
		 * @param $comment
		 */
		static function non_author_comment( $comment ) {
			?>
			<li class="other">
				<div class="avatar">
					<?php echo get_avatar( $comment, 40 ); ?>
				</div>
				<div class="messages">
					<p><?php echo esc_attr( $comment->comment_content ); ?></p>

					<time title="<?php echo esc_attr( $comment->comment_date ); ?>"
					      datetime="<?php echo esc_attr( $comment->comment_date ); ?>"><span
							title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo esc_attr( ( $comment->comment_author == '' ) ? 'Anonymous' : $comment->comment_author ); ?> </span>
						• <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ) ); ?>
					</time>
				</div>
			</li>
		<?php
		}


		public static function ui( $post ) {
			wp_enqueue_style( 'rthd-followup-css', RT_HD_URL . 'app/assets/css/follow-up.css', false, RT_HD_VERSION, 'all' );

			$comments = get_comments(
				array(
					'post_id' => $post->ID,
					'status'  => 'approve', //Change this to the type of comments to be displayed
				) );
			//echo( json_encode($comments) );
			$ticket_unique_id = get_post_meta( $post->ID, '_rtbiz_hd_unique_id', true );
			?>
			<form id="add_followup_form" method="post">
			<input type="hidden" id='ticket_unique_id' value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>
				<input id="edit-comment-id" type="hidden"> <textarea id="followup_content" name="followup_content" placeholder="Add new followup"></textarea>
				<button class="mybutton add-savefollowup" id="savefollwoup" type="button">Add</button>
				<!--<button class="mybutton right" type="submit" >Add</button>-->
				<!--<input type="file" class="right" name="ticket_attach_file" id="ticket_attach_file" multiple />-->
			</form>
			<ol class="discussion">
				<?php foreach ( $comments as $comment ) {
				if ( ( $comment->user_id ) == ( get_current_user_id() ) ) {
					RT_Meta_Box_Ticket_Comments::author_comment( $comment );
				} else {
					RT_Meta_Box_Ticket_Comments::non_author_comment( $comment );
				}
				} ?>
			</ol>
		<?php

		}


		public static function save( $post_id, $post ) {

		}
	}
}