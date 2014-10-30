<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_HD_Tickets_List_View' ) ) {
	/**
	 * Class Rt_HD_CPT_Tickets
	 * Customise ticket CPT List view & Add/edit Post view
	 *
	 * @since  0.1
	 *
	 * @author dipesh
	 */
	class Rt_HD_CPT_Tickets {

		/**
		 * Apply hook
		 *
		 * @since  0.1
		 */
		function __construct() {
			//add_filter( 'media_view_strings', array( $this, 'change_insert_media_title' ) );

			// CPT List View
			add_filter( 'manage_edit-' . Rt_HD_Module::$post_type . '_columns', array( $this, 'edit_custom_columns' ) );
			add_action( 'manage_' . Rt_HD_Module::$post_type . '_posts_custom_column', array(
				$this,
				'manage_custom_columns',
			), 2 );
			add_filter( 'manage_edit-' . Rt_HD_Module::$post_type . '_sortable_columns', array(
				$this,
				'sortable_column',
			) );

			// CPT Edit/Add View
			add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
			add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

			add_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );
			add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'RT_Meta_Box_Ticket_Info::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'RT_Meta_Box_Subscribers::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'RT_Meta_Box_Attachment::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'RT_Meta_Box_External_Link::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'RT_Ticket_Diff_Email::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'RT_Meta_Box_Notes::save', 10, 2 );

			add_action( 'pre_get_posts', array( $this, 'pre_filter' ), 1 );
			add_action( 'untrashed_post', array( $this, 'after_restore_trashed_ticket' ) );
			add_action( 'before_delete_post', array( $this, 'before_ticket_deleted' ) );
			add_action( 'wp_trash_post', array( $this, 'before_ticket_trashed' ) );
			add_action( 'wp_before_admin_bar_render', 'RT_Meta_Box_Ticket_Info::custom_post_status_rendar', 10 );
		}

		/**
		 * change title of media upload page
		 *
		 * @since  0.1
		 *
		 * @param $strings
		 *
		 * @return mixed
		 */
		function change_insert_media_title( $strings ) {
			global $post_type;

			if ( $post_type == Rt_HD_Module::$post_type ) {
				$obj = get_post_type_object( Rt_HD_Module::$post_type );

				$strings['insertIntoPost']     = sprintf( __( 'Insert into %s', RT_HD_PATH_ADMIN ), $obj->labels->singular_name );
				$strings['uploadedToThisPost'] = sprintf( __( 'Uploaded to this %s', RT_HD_PATH_ADMIN ), $obj->labels->singular_name );
			}

			return $strings;
		}

		/**
		 * Edit Column list view on Tickets List view page
		 *
		 * @since  0.1
		 *
		 * @return array
		 */
		public function edit_custom_columns( ) {
			$columns = array();

			$columns['cb']                         = '<input type="checkbox" />';
			$columns['rthd_ticket_title']          = __( 'Ticket', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_status']         = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', RT_HD_TEXT_DOMAIN ) . '">' . esc_attr__( 'Status', RT_HD_TEXT_DOMAIN ) . '</span>';
			$columns['rthd_ticket_created_by']     = __( 'Create_By', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_updated_by']     = __( 'Updated_By', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_closed_by']      = __( 'ClosedBy', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_closing_reason'] = __( 'Closing Reason', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_contacts']       = __( 'Contacts', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_accounts']       = __( 'Accounts', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_actions']        = __( 'Actions', RT_HD_TEXT_DOMAIN );

			return $columns;
		}

		/**
		 * Define new sortable columns for ticket list view
		 *
		 * @since 0.1
		 *
		 * @param $columns
		 *
		 * @return mixed
		 */
		function sortable_column( $columns ) {
			$columns['rthd_ticket_title']          = __( 'Ticket', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_created_by']     = __( 'Create_By', RT_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_updated_by']     = __( 'Updated_By', RT_HD_TEXT_DOMAIN );
			return $columns;
		}

		/**
		 * Edit Content of List view Columns
		 *
		 * @since  0.1
		 *
		 * @param $column
		 */
		function manage_custom_columns( $column ) {

			global $post;

			switch ( $column ) {

				case 'rthd_ticket_status' :

					printf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $post->post_status ), esc_html__( $post->post_status, RT_HD_PATH_ADMIN ), esc_html__( $post->post_status, RT_HD_PATH_ADMIN ) );
					break;

				case 'rthd_ticket_title' :

					printf( __( '%s : %s', RT_HD_PATH_ADMIN ), '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) ) . '"><strong>' . esc_attr( _x( '#', 'hash before order number', 'RT_HD_PATH_ADMIN' ) . esc_attr( $post->ID ) ) . '</strong></a>', $post->post_title );

					$user_id   = $post->post_author;
					$user_info = get_userdata( $user_id );
					$url       = esc_url(
						add_query_arg(
							array(
								'post_type'  => Rt_HD_Module::$post_type,
								'assigned' => $user_id,
							), 'edit.php' ) );

					if ( $user_info ) {
						printf( " Assigned to <a href='%s'>%s</a>", $url, $user_info->user_login );
					}
					break;

				case 'rthd_ticket_created_by':

					$date     = new DateTime( get_the_date( 'Y-m-d H:i:s' ) );
					$datediff = human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) . __( ' ago' );

					$user_id   = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );
					$user_info = get_userdata( $user_id );
					$url       = esc_url(
						add_query_arg(
							array(
								'post_type'  => Rt_HD_Module::$post_type,
								'created_by' => $user_id,
							), 'edit.php' ) );

					printf( __( '<span class="created-by tips" data-tip="%s">%s', RT_HD_PATH_ADMIN ), get_the_date( 'd-m-Y H:i' ), $datediff );
					if ( $user_info ) {
						printf( " by <a href='%s'>%s</a>", $url, $user_info->user_login );
					}
					printf( '</span>' );
					break;

				case 'rthd_ticket_updated_by':

					$date     = new DateTime( get_the_modified_date( 'Y-m-d H:i:s' ) );
					$datediff = human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) . __( ' ago' );

					$user_id   = get_post_meta( $post->ID, '_rtbiz_hd_updated_by', true );
					$user_info = get_userdata( $user_id );
					$url       = esc_url(
						add_query_arg(
							array(
								'post_type'  => Rt_HD_Module::$post_type,
								'updated_by' => $user_id,
							), 'edit.php' ) );

					printf( __( '<span class="created-by tips" data-tip="%s">%s', RT_HD_PATH_ADMIN ), get_the_modified_date( 'd-m-Y H:i' ), $datediff );
					if ( $user_info ) {
						printf( ' by <a href="%s">%s</a>', $url, $user_info->user_login );
					}
					printf( '</span>' );
					break;

				case 'rthd_ticket_closed_by':
					$closeDate = get_post_meta( $post->ID, '_rtbiz_hd_closing_date', true );
					if ( $closeDate ) {
						$date     = new DateTime( $closeDate );
						$datediff = human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) . __( ' ago' );
						printf( __( '<span class="created-by tips" data-tip="%s">%s', RT_HD_PATH_ADMIN ), get_the_modified_date( 'd-m-Y H:i' ), $datediff );

						$user_id   = get_post_meta( $post->ID, '_rtbiz_hd_closed_by', true );
						$user_info = get_userdata( $user_id );
						$url       = esc_url(
							add_query_arg(
								array(
									'post_type'  => Rt_HD_Module::$post_type,
									'updated_by' => $user_id,
								), 'edit.php' ) );

						if ( $user_info ) {
							printf( ' by <a href="%s">%s</a></span>', $url, $user_info->user_login );
						}

						printf( '</span>' );
					} else {
						echo esc_html( '-' );
					}
					break;

				case 'rthd_ticket_closing_reason':

					$term_name = wp_get_post_terms( $post->ID, rthd_attribute_taxonomy_name( 'closing-reason' ), array( 'fields' => 'names' ) );
					echo esc_attr( ! empty( $term_name ) ? $term_name[0] : '-' );

					break;

				case 'rthd_ticket_contacts' :

					$contacts = rt_biz_get_post_for_person_connection( $post->ID, Rt_HD_Module::$post_type );

					if ( isset( $contacts ) && ! empty( $contacts ) ) {
						$contact_name = array();
						$base_url     = add_query_arg( array( 'post_type' => Rt_HD_Module::$post_type ), admin_url( 'edit.php' ) );

						foreach ( $contacts as $contact ) {
							$url            = add_query_arg( array( 'contact_id' => $contact->ID ), $base_url );
							$contact_name[] = sprintf( '<a href="%s">%s</a>', $url, $contact->post_title );
						}
						echo balanceTags( implode( ',', $contact_name ) );
					} else {
						echo esc_attr( '-' );
					}

					break;

				case 'rthd_ticket_accounts' :

					$accounts = rt_biz_get_post_for_organization_connection( $post->ID, Rt_HD_Module::$post_type );
					if ( isset( $accounts ) && ! empty( $accounts ) ) {
						$account_name = array();
						$base_url     = add_query_arg( array( 'post_type' => Rt_HD_Module::$post_type ), admin_url( 'edit.php' ) );

						foreach ( $accounts as $account ) {
							$url            = add_query_arg( array( 'account_id' => $account->ID ), $base_url );
							$account_name[] = sprintf( '<a href="%s">%s</a>', $url, $account->post_title );
						}
						echo balanceTags( implode( ',', $account_name ) );
					} else {
						echo esc_attr( '-' );
					}
					break;
			}
		}

		/**
		 * Remove Default meta boxes on Edit post View for ticket
		 *
		 * @since  0.1
		 */
		public function remove_meta_boxes() {
			remove_meta_box( rthd_attribute_taxonomy_name( 'closing-reason' ) . 'div' , Rt_HD_Module::$post_type, 'side' );
			remove_meta_box( 'revisionsdiv', Rt_HD_Module::$post_type, 'normal' );
			remove_meta_box( 'commentstatusdiv', Rt_HD_Module::$post_type, 'normal' );
			remove_meta_box( 'slugdiv', Rt_HD_Module::$post_type, 'normal' );
		}

		/**
		 * Add custom meta boxes on Edit post View for ticket
		 *
		 * @since  0.1
		 */
		public function add_meta_boxes() {
			add_meta_box( 'rt-hd-ticket-title',  __( 'Ticket Title', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_Ticket_Title::ui',  Rt_HD_Module::$post_type,  'normal', 'high' );
			add_meta_box( 'rt-hd-ticket-data', __( 'Ticket Information', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_Ticket_Info::ui', Rt_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-subscriiber', __( 'Subscriber', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_Subscribers::ui', Rt_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-attachment', __( 'Attachment', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_Attachment::ui', Rt_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-external-link', __( 'External Link', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_External_Link::ui', Rt_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-ticket-follow-up',  __( 'Follow Up', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_Ticket_Comments::ui',  Rt_HD_Module::$post_type,  'normal', 'default' );
			add_meta_box( 'rt-hd-ticket-notes',  __( 'Notes', RT_HD_TEXT_DOMAIN ), 'RT_Meta_Box_Notes::ui',  Rt_HD_Module::$post_type,  'side', 'default' );

		}

		/**
		 * Save custom meta boxes Values on Edit post View for ticket
		 *
		 * @since  0.1
		 *
		 * @param $post_id
		 * @param $post
		 */
		public function save_meta_boxes( $post_id, $post ) {
			//global $rt_hd_module;
			// $post_id and $post are required
			if ( empty( $post_id ) || empty( $post ) ) {
				return;
			}

			// Dont' save meta boxes for revisions or autosaves
			if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
			if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
				return;
			}

			// Check user has permission to edit
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Check the post type
			if ( ! in_array( $post->post_type, array( Rt_HD_Module::$post_type ) ) ) {
				return;
			}

			do_action( 'rt_hd_process_' . $post->post_type . '_meta', $post_id, $post );;
			if ( 'trash' == $post->post_status ) {

				$url = add_query_arg( array( 'post_type' => Rt_HD_Module::$post_type ), admin_url( 'edit.php' ) );
				wp_safe_redirect( $url );
				die();
			}

		}

		/**
		 * Filter ticket list view according to user query
		 *
		 * @since 0.1
		 *
		 * @param $query
		 */
		function pre_filter( $query ) {
			if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == Rt_HD_Module::$post_type ) {
				$orderby = $query->get( 'orderby' );
				if ( isset( $_GET['contact_id'] ) ) {
					$formss = array();
					$contact_id = $_GET['contact_id'];
					global $wpdb;
					global $rt_person;
					$contact_froms = $wpdb->get_results(
						"SELECT p2p_from
							FROM wp_p2p
								WHERE p2p_type = '".Rt_HD_Module::$post_type.'_to_'.$rt_person->post_type.
										"' AND p2p_to = ". $contact_id);

					foreach ( $contact_froms as $form ){
						$formss[] = intval( $form->p2p_from );
					}
					$query->set( 'post__in', $formss );
				}
				if ( isset( $orderby ) && ! empty( $orderby ) ){
					switch ( $orderby ) {
						case 'Ticket':
							$query->set( 'orderby', 'post_ID' );
							break;
						case 'Create_By':
							$query->set( 'orderby', 'Date' );
							break;
						case 'Updated_By':
							$query->set( 'orderby', 'modified' );
							break;
					}
				} else {
					$query->set( 'orderby', 'modified' );
					$query->set( 'order', 'desc' );
				}

				if ( isset( $_GET['created_by'] ) ) {

					$query->set( 'meta_query', array(
						array(
							'key'   => '_rtbiz_hd_created_by',
							'value' => $_GET['created_by'],
						),
					) );

				}

				if ( isset( $_GET['assigned'] ) ) {
					$query->set( 'author', $_GET['assigned'] );
				}

				if ( isset( $_GET['updated_by'] ) ) {

					$query->set( 'meta_query', array(
						array(
							'key'   => '_rtbiz_hd_updated_by',
							'value' => $_GET['updated_by'],
						),
					) );

				}
			}

		}

		/**
		 * update ticket status[ unanswered ] after restore from trash
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		function after_restore_trashed_ticket( $post_id ) {

			$ticket = get_post( $post_id );

			if ( $ticket->post_type == Rt_HD_Module::$post_type ) {

				global $rt_hd_ticket_history_model;

				$rt_hd_ticket_history_model->insert(
					array(
						'ticket_id'   => $post_id,
						'type'        => 'post_status',
						'old_value'   => 'trash',
						'new_value'   => 'unanswered',
						'message'     => null,
						'update_time' => current_time( 'mysql' ),
						'updated_by'  => get_current_user_id(),
					) );

				$ticket->post_status = 'unanswered';
				wp_update_post( $ticket );

			}
		}

		/**
		 * Delete index table entry before post delete
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		function before_ticket_deleted( $post_id ) {

			if ( get_post_type( $post_id ) == Rt_HD_Module::$post_type ) {

				global $rt_hd_ticket_history_model;
				$ticketModel = new Rt_HD_Ticket_Model();

				$ticket_index   = array( 'post_id' => $post_id );
				$ticket_history = array( 'ticket_id' => $post_id );

				$rt_hd_ticket_history_model->delete( $ticket_history );

				$ticketModel->delete_ticket( $ticket_index );
			}
		}

		/**
		 * update status history before ticket trashed
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		function before_ticket_trashed( $post_id ) {
			if ( get_post_type( $post_id ) == Rt_HD_Module::$post_type ) {
				global $rt_hd_ticket_history_model;
				$rt_hd_ticket_history_model->insert(
					array(
						'ticket_id'   => $post_id,
						'type'        => 'post_status',
						'old_value'   => get_post_status( $post_id ),
						'new_value'   => 'trash',
						'message'     => null,
						'update_time' => current_time( 'mysql' ),
						'updated_by'  => get_current_user_id(),
					) );

			}
		}
	}
}