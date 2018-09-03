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

		private $slack_name = '';

		/**
		 * Rtbiz_HD_Slack_Integration constructor.
		 */
		function __construct() {
			add_action( 'admin_notices', array( $this, 'check_slack_integration_plugin' ) );
			add_action( 'wp_insert_comment', array( $this, 'handle_ticket_followup' ) );
			add_action( 'wp_insert_post', array( $this, 'handle_support_page_new_ticket' ), 10, 3 );
			add_action( 'show_user_profile', array( $this, 'show_slack_channel_field' ) );
			add_action( 'edit_user_profile', array( $this, 'show_slack_channel_field' ) );

			add_filter( 'rtbiz_contact_meta_fields', array( $this, 'show_slack_channel_field' ) );
			add_filter( 'wp_insert_post_data', array( $this, 'handle_post_data_update' ), 10, 2 );
			add_filter( 'slack_get_events', array( $this, 'slack_get_events' ) );

			add_action( 'rt_hd_si_new_support_page_ticket', array( $this, 'schedule_cron_for_new_ticket' ) );
			add_action( 'rt_hd_si_new_staff_note', array( $this, 'unschedule_cron_on_new_followup' ), 10, 2 );
			add_action( 'rt_hd_si_new_public_note', array( $this, 'unschedule_cron_on_new_followup' ), 10, 2 );
			add_action( 'init', array( $this, 'register_schedule_action_callback' ) );
		}


		/**
		 * Add reminder slack ID field on contact edit page.
		 *
		 * @param array $meta_fields Meta field configuration array.
		 *
		 * @return array
		 */
		public function show_slack_channel_field( $meta_fields ) {
			$meta_fields[] = array(
				'key'             => 'reminder_slack_id',
				'text'            => __( 'Reminder slack ID' ),
				'label'           => __( 'Reminder slack ID' ),
				'is_multiple'     => false,
				'type'            => 'text',
				'name'            => 'contact_meta[reminder_slack_id]',
				'description'     => __( 'Current active slack ID for reminders' ),
				'hide_for_client' => true,
				'category'        => 'Contact',
			);

			return $meta_fields;
		}

		/**
		 * Register action callbacks for cron scheduled events.
		 */
		public function register_schedule_action_callback() {
			$new_posts = get_option( 'rt_hd_si_new_tickets' );
			if ( empty( $new_posts ) ) {
				return;
			}

			foreach ( $new_posts as $post ) {
				add_action( 'rt_hd_si_new_ticket_cron_' . $post, array( $this, 'handle_schedule_event' ) );
			}
		}

		/**
		 * Handle scheduled event for post.
		 *
		 * @param int $post_id Post ID for which event is being handled.
		 */
		public function handle_schedule_event( $post_id ) {
			$post    = get_post( $post_id );
			$contact = rtbiz_get_contact_for_wp_user( $post->post_author );
			if ( empty( $contact ) ) {
				return;
			}
			if ( is_array( $contact ) ) {
				$contact = $contact[0];
			}

			$slack_id = Rtbiz_Entity::get_meta( $contact->ID, 'reminder_slack_id', true );
			if ( empty( $slack_id ) ) {
				return;
			}

			$this->slack_name = $slack_id;

			add_filter( 'slack_channel_name', array( $this, 'change_slack_channel_name' ) );
			do_action( 'rt_hd_si_reminder_for_new_ticket', $post );
			remove_filter( 'slack_channel_name', array( $this, 'change_slack_channel_name' ) );
		}

		/**
		 * Return changed slack name.
		 *
		 * @return string
		 */
		public function change_slack_channel_name() {
			return $this->slack_name;
		}

		/**
		 * Unschedule event if staff note or public note is added.
		 *
		 * @param object $comment Comment object.
		 * @param object $post    Post object.
		 */
		public function unschedule_cron_on_new_followup( $comment, $post ) {
			$time = wp_next_scheduled( 'rt_hd_si_new_ticket_cron_' . $post->ID, array( 'post_id' => $post->ID ) );
			if ( $time ) {
				wp_unschedule_event( $time, 'rt_hd_si_new_ticket_cron_' . $post->ID, array( 'post_id' => $post->ID ) );
			}

			$new_posts = get_option( 'rt_hd_si_new_tickets' );
			if ( ! empty( $new_posts[$post->ID] ) ) {
				unset( $new_posts[$post->ID] );
				if ( empty( $new_posts ) ) {
					delete_option( 'rt_hd_si_new_tickets' );
				} else {
					update_option( 'rt_hd_si_new_tickets', $new_posts );
				}
			}
		}


		/**
		 * Schedule cron event for new ticket created.
		 *
		 * @param object $post Post object.
		 */
		public function schedule_cron_for_new_ticket( $post ) {
			if ( ! wp_next_scheduled( 'rt_hd_si_new_ticket_cron_' . $post->ID, array( 'post_id' => $post->ID ) ) ) {
				wp_schedule_event( strtotime( '+2 day', time() ), 'daily', 'rt_hd_si_new_ticket_cron_' . $post->ID, array( 'post_id' => $post->ID ) );
			}

			$new_posts = get_option( 'rt_hd_si_new_tickets' );
			if ( empty( $new_posts ) ) {
				$new_posts = array();
			}
			$new_posts[$post->ID] = $post->ID;
			update_option( 'rt_hd_si_new_tickets', $new_posts );
		}

		/**
		 * Do action on existing ticket author change.
		 *
		 * @param object $data    Post data object.
		 * @param array  $postarr Post data in array.
		 *
		 * @return object
		 */
		public function handle_post_data_update( $data, $postarr ) {
			$post = get_post( $postarr['ID'] );
			if ( empty( $post ) ) {
				return $data;
			}

			if ( $post->post_author != $postarr['post_author'] ) {
				do_action( 'rt_hd_si_ticket_author_change', $post, $postarr );
			}

			return $data;
		}

		/**
		 * Do action on new ticket creation.
		 *
		 * @param int    $post_id New post id.
		 * @param object $post    New post object.
		 * @param bool   $update  Whether of updation of existing post.
		 */
		public function handle_support_page_new_ticket( $post_id, $post, $update ) {
			if ( $post->post_type !== Rtbiz_HD_Module::$post_type ) {
				return;
			}

			if ( ! $update ) {
				do_action( 'rt_hd_si_new_support_page_ticket', $post );
			}
		}

		/**
		 * Do actions on new staff note or public note added.
		 *
		 * @param int $comment_id New comment id.
		 */
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
				do_action( 'rt_hd_si_new_public_note', $comment, $post );
			}
		}

		/**
		 * Check for slack plugin, show notice info if not active.
		 */
		public function check_slack_integration_plugin() {
			if ( ! is_plugin_active( 'slack/slack.php' ) ) {
				?>
				<div class="notice-info settings-error notice is-dismissible">
					<p><?php echo '<strong>' . esc_html__( 'rtBiz Helpdesk', 'rtbiz-helpdesk' ) . ': </strong>' . esc_html__( 'Install', 'rtbiz-helpdesk' ) . ' <a href="https://wordpress.org/plugins/slack/" target="_blank">' . esc_html__( 'Slack', 'rtbiz-helpdesk' ) . '</a> ' . esc_html__( 'to enable slack notifications.', 'rtbiz-helpdesk' ); ?></p>
				</div>
				<?php
			}
		}

		/**
		 * Return event parameters of new ticket creation.
		 *
		 * @return array
		 */
		public function rthd_new_ticket() {

			return array(
				'action'      => 'rt_hd_si_new_support_page_ticket',
				'description' => esc_html__( 'When new helpdesk ticket is created', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $post ) {
					$message = sprintf( 'New ticket: <%s|%s>', get_post_permalink( $post->ID ), $post->post_title );

					if ( ! empty( $post->post_author ) ) {
						$user = get_userdata( $post->post_author );
						if ( ! empty( $user ) ) {
							$message .= sprintf( ' by %s', $user->data->display_name );
						}
					}

					return apply_filters( 'rt_hd_si_new_support_page_ticket_slack_message', $message );
				},
			);

		}

		/**
		 * Return event parameters of new ticket creation.
		 *
		 * @return array
		 */
		public function rthd_new_ticket_assignee_reminder() {

			return array(
				'action'      => 'rt_hd_si_reminder_for_new_ticket',
				'description' => esc_html__( 'After 2+ days of no reply on new ticket', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $post ) {
					$date = new DateTime( $post->post_date );
					$diff = $date->diff( new DateTime() );

					$message = sprintf( 'Reminder for <%s|%s>.', get_post_permalink( $post->ID ), $post->post_title );

					if ( $diff->d >= 2 ) {
						$message .= sprintf( ' It\'s been %d days since no Staff Note / Public Note.', $diff->d );
					}

					return apply_filters( 'rt_hd_si_new_support_page_ticket_slack_message', $message );
				},
			);

		}

		/**
		 * Return event parameters of ticket author change.
		 *
		 * @return array
		 */
		public function rthd_ticket_author_change() {

			return array(
				'action'      => 'rt_hd_si_ticket_author_change',
				'description' => esc_html__( 'When helpdesk ticket author changes', 'rtbiz-helpdesk' ),
				'default'     => false,
				'message'     => function ( $old_post, $new_post ) {
					$old_author   = get_userdata( $old_post->post_author );
					$new_author   = get_userdata( $new_post['post_author'] );
					$current_user = '';
					if ( $old_author->ID === get_current_user_id() ) {
						$current_user = $old_author->data->display_name;
					} elseif ( $new_author->ID === get_current_user_id() ) {
						$current_user = $new_author->data->display_name;
					} else {
						$current_user = get_userdata( get_current_user_id() )->data->display_name;
					}

					$message = sprintf( 'Ticket <%s|%s> assignee changed from %s to %s by %s', get_post_permalink( $new_post['ID'] ), $new_post['post_title'], $old_author->data->display_name, $new_author->data->display_name, $current_user );

					return apply_filters( 'rt_hd_si_ticket_author_change_slack_message', $message );
				},
			);

		}

		/**
		 * Return event parameters of new staff note creation.
		 *
		 * @return array
		 */
		public function rthd_new_staff_note() {

			return array(
				'action'      => 'rt_hd_si_new_staff_note',
				'description' => esc_html__( 'When new staff note is added on helpdesk ticket', 'rtbiz-helpdesk' ),
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

					return apply_filters( 'rt_hd_si_new_staff_note_slack_message', $message );
				},
			);

		}

		/**
		 * Return event parameters of new public note creation.
		 *
		 * @return array
		 */
		public function rthd_new_public_note() {

			return array(
				'action'      => 'rt_hd_si_new_public_note',
				'description' => esc_html__( 'When new public note is added on helpdesk ticket', 'rtbiz-helpdesk' ),
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

					return apply_filters( 'rt_hd_si_new_public_note_slack_message', $message );
				},
			);

		}

		/**
		 * Return event parameters of ticket status change.
		 *
		 * @return array
		 */
		public function rthd_ticket_status_changed() {

			return array(
				'action'      => 'rthd_ticket_status_changed',
				'description' => esc_html__( 'When helpdesk ticket status changed', 'rtbiz-helpdesk' ),
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

					return apply_filters( 'rthd_ticket_status_changed_slack_message', $message );
				},
			);

		}

		/**
		 * Register events for slack integration plugin.
		 *
		 * @param array $events Array of events.
		 *
		 * @return array
		 */
		public function slack_get_events( $events ) {
			$events['rthd_ticket_status_changed']        = $this->rthd_ticket_status_changed();
			$events['rthd_new_ticket']                   = $this->rthd_new_ticket();
			$events['rthd_new_staff_note']               = $this->rthd_new_staff_note();
			$events['rthd_new_public_note']              = $this->rthd_new_public_note();
			$events['rthd_ticket_author_change']         = $this->rthd_ticket_author_change();
			$events['rthd_new_ticket_assignee_reminder'] = $this->rthd_new_ticket_assignee_reminder();

			return $events;
		}

	}

}
