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
			rthd_get_template( 'admin/followup-common.php', array( 'post' => $post ) );
		}



		public static function save( $post_id, $post ) {

		}
	}
}