<?php
/**
 * Created by PhpStorm.
 * User: sid177
 * Date: 30/8/18
 * Time: 3:25 PM
 */

if ( ! class_exists( 'Rtbiz_HD_Slack_Integration' ) ) {

	/**
	 * Class Rtbiz_HD_Slack_Integration
	 *
	 * Slack Integration specific functionalities of the plugin
	 * depends on slack <https://wordpress.org/plugins/slack/> plugin
	 */
	class Rtbiz_HD_Slack_Integration {

		function __construct() {
			add_action( 'admin_notices', array( $this, 'check_slack_integration_plugin' ) );
			add_action( 'wp_insert_comment', array( $this, 'handle_ticket_followup' ) );
			add_action( 'wp_insert_post', array( $this, 'handle_support_page_new_ticket' ), 10, 3 );

			add_filter( 'slack_get_events', array( $this, 'slack_get_events' ) );
		}

		public function handle_support_page_new_ticket( $post_id, $post, $update ) {
			if ( $post->post_type !== Rtbiz_HD_Module::$post_type ) {
				return;
			}

			if ( ! $update ) {
				error_log('new support ticket');
				do_action( 'rt_hd_si_new_support_page_ticket', $post );
			}
		}

		public function handle_ticket_followup( $comment_id ) {
			$comment = get_comment( $comment_id );
			if ( empty( $comment ) ) {
				return;
			}

			$post = get_post( $comment->comment_post_ID );
			if ( $post->post_type !== Rtbiz_HD_Module::$post_type ) {
				return;
			}

			if ( $comment->comment_type == Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF ) {
				do_action( 'rt_hd_si_new_staff_note', $comment, $post );
			}

			if ( $comment->comment_type == Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC ) {
				error_log('doing new public');
				do_action( 'rt_hd_si_new_public_note', $comment, $post );
			}
		}

		public function check_slack_integration_plugin() {
			if ( ! is_plugin_active( 'slack/slack.php' ) ) {
				?>
				<div class="notice-info settings-error notice is-dismissible">
					<p><?php echo '<strong>' . esc_html__( 'rtBiz Helpdesk', 'rtbiz-helpdesk' ) . ': </strong>' . esc_html__( 'Install', 'rtbiz-helpdesk' ) . ' <a href="https://wordpress.org/plugins/slack/" target="_blank">' . esc_html__( 'Slack', 'rtbiz-helpdesk' ) . '</a> ' . esc_html__( 'to enable slack notifications.', 'rtbiz-helpdesk' ); ?></p>
				</div>
				<?php
			}
		}

		public function rthd_new_ticket() {

			return array(
				'action'      => 'rt_hd_si_new_support_page_ticket',
				'description' => __( 'When new helpdesk ticket is created', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $post ) {
					$message = sprintf( 'New ticket: <%s|%s>', get_post_permalink( $post->ID ), $post->post_title );

					if ( ! empty( $post->post_author ) ) {
						$user = get_userdata( $post->post_author );
						if ( ! empty( $user ) ) {
							$message .= sprintf( ' by %s', $user->data->display_name );
						}
					}

					return $message;
				},
			);

		}

		public function rthd_new_staff_note() {

			return array(
				'action'      => 'rt_hd_si_new_staff_note',
				'description' => __( 'When new staff note is added on helpdesk ticket', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $comment, $post ) {
					$post_permalink = get_post_permalink( $comment->comment_post_ID );
					$message        = sprintf(
						'New <%s|Staff Note> on <%s|%s> ticket by %s',
						$post_permalink . '#comment-' . $comment->comment_ID,
						$post_permalink,
						$post->post_title,
						$comment->comment_author
					);

					return $message;
				},
			);

		}

		public function rthd_new_public_note() {

			return array(
				'action'      => 'rt_hd_si_new_public_note',
				'description' => __( 'When new public note is added on helpdesk ticket', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $comment, $post ) {
					$post_permalink = get_post_permalink( $comment->comment_post_ID );
					$message        = sprintf(
						'New <%s|Public Note> on <%s|%s> ticket by %s',
						$post_permalink . '#comment-' . $comment->comment_ID,
						$post_permalink,
						$post->post_title,
						$comment->comment_author
					);

					return $message;
				},
			);

		}

		public function rthd_ticket_status_changed() {

			return array(
				'action'      => 'rthd_ticket_status_changed',
				'description' => __( 'When helpdesk ticket status changed', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $old_status, $new_status, $post ) {
					$old_term = get_term_by( 'slug', $old_status, 'rtbiz_helpdesk_post_status' );
					$new_term = get_term_by( 'slug', $new_status, 'rtbiz_helpdesk_post_status' );

					if ( empty( $old_term ) || empty( $new_term ) ) {
						return false;
					}

					$user    = get_userdata( get_current_user_id() );
					$message = sprintf(
						'%s (%s) changed status of <%s|%s> ticket from %s to %s',
						$user->data->display_name,
						$user->data->user_email,
						get_post_permalink( $post->ID ),
						$post->post_title,
						( ! empty( $old_term ) ) ? $old_term->name : $old_status,
						( ! empty( $new_term ) ) ? $new_term->name : $new_status
					);

					return $message;
				},
			);

		}

		public function slack_get_events( $events ) {
			$events['rthd_ticket_status_changed'] = $this->rthd_ticket_status_changed();
			$events['rthd_new_ticket']            = $this->rthd_new_ticket();
			$events['rthd_new_staff_note']        = $this->rthd_new_staff_note();
			$events['rthd_new_public_note']       = $this->rthd_new_public_note();

			return $events;
		}

	}

}
