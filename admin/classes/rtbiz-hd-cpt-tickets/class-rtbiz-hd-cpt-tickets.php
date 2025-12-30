<?php

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_CPT_Tickets' ) ) {

	/**
	 * Class Rt_HD_CPT_Tickets
	 * Customise ticket CPT List view & Add/edit Post view
	 *
	 * @since  0.1
	 *
	 * @author dipesh
	 */
	class Rtbiz_HD_CPT_Tickets {

		/**
		 * Apply hook
		 *
		 * @since  0.1
		 */
		public function __construct() {

			// CPT List View
			Rtbiz_HD::$loader->add_filter( 'manage_edit-' . Rtbiz_HD_Module::$post_type . '_columns', $this, 'edit_custom_columns', 20 );
			Rtbiz_HD::$loader->add_filter( 'manage_'. Rtbiz_HD_Module::$post_type .'_posts_columns', $this, 'add_custom_columns', 10 );
			Rtbiz_HD::$loader->add_action( 'manage_' . Rtbiz_HD_Module::$post_type . '_posts_custom_column', $this, 'manage_custom_columns', 2 );
			Rtbiz_HD::$loader->add_filter( 'manage_edit-' . Rtbiz_HD_Module::$post_type . '_sortable_columns', $this, 'sortable_column' );

			// CPT Edit/Add View
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes', $this, 'remove_meta_boxes' );
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes', 30 );
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes_' . Rtbiz_HD_Module::$post_type, $this, 'metabox_rearrenge', 30 );
			Rtbiz_HD::$loader->add_action( 'save_post', $this, 'save_meta_boxes', 1, 2 );

			//metabox save
			add_action( 'wp_before_admin_bar_render', 'Rtbiz_HD_Ticket_Info::custom_post_status_rendar', 10 );

			add_action( 'pre_post_update', 'Rtbiz_HD_Ticket_Diff_Email::store_old_post_data', 1, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Ticket_Info::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Subscribers::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Contact::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Attachment::save', 10, 2 );
			//          add_action( 'rt_hd_process_' . Rt_HD_Module::$post_type . '_meta', 'Rtbiz_HD_External_Link::save', 10, 2 );
			add_action( 'rt_hd_process_' . Rtbiz_HD_Module::$post_type . '_meta', 'Rtbiz_HD_Ticket_Diff_Email::save', 10, 2 );

			Rtbiz_HD::$loader->add_action( 'pre_get_posts', $this, 'pre_filter', 1 );
			Rtbiz_HD::$loader->add_action( 'untrashed_post', $this, 'after_restore_trashed_ticket' );
			Rtbiz_HD::$loader->add_action( 'before_delete_post', $this, 'before_ticket_deleted' );
			Rtbiz_HD::$loader->add_action( 'wp_trash_post', $this, 'before_ticket_trashed' );

			// Add custom view name `My Tickets`
			Rtbiz_HD::$loader->add_filter( 'views_edit-' . Rtbiz_HD_Module::$post_type, $this, 'display_custom_views' );

			// Add custom filters.
			Rtbiz_HD::$loader->add_action( 'restrict_manage_posts', $this, 'display_custom_filters' );

			Rtbiz_HD::$loader->add_action( 'edit_form_top', $this, 'append_ticket_id_to_title' );
			Rtbiz_HD::$loader->add_filter( 'list_table_primary_column', $this,'ticket_primary_column', 10, 2 );
		}

		/**
		 * Add ticket title as primary column
		 * @param $column
		 * @param $screen
		 * @return string
         */
		function ticket_primary_column( $column, $screen ){
			if ( $screen === 'edit-ticket' ) {
				$column = 'rthd_ticket_title';
			}
			return $column;
		}

		/**
		 * Add title on early hook so that we can add it as primary column
		 * @param $cols
		 * @return mixed
         */
		public function add_custom_columns( $cols ){
			$cols['rthd_ticket_title'] = __( 'Ticket', 'rtbiz-helpdesk' );
			return $cols;
		}

		/**
		 * Edit Column list view on Tickets List view page
		 *
		 * @param $cols
		 *
		 * @since  0.1
		 *
		 * @return array
		 */
		public function edit_custom_columns( $cols ) {
			$columns = array();

			unset( $cols['cb'] );
			unset( $cols['title'] );
			unset( $cols['comments'] );
			unset( $cols['date'] );
			unset( $cols[ 'p2p-from-'.Rtbiz_HD_Module::$post_type.'_to_'.rtbiz_get_contact_post_type() ] );
			$columns['cb'] = '<input type="checkbox" />';
			$columns['rthd_ticket_title'] = __( 'Ticket', 'rtbiz-helpdesk' );
			$columns['rthd_ticket_status'] = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'rtbiz-helpdesk' ) . '">' . esc_attr__( 'Status', 'rtbiz-helpdesk' ) . '</span>';
			$columns['rthd_ticket_customers'] = __( 'Customers', 'rtbiz-helpdesk' );
			$columns['rthd_ticket_staff'] = __( 'Staff', 'rtbiz-helpdesk' );
			//			$columns['rthd_ticket_assignee'] = __( 'Assignee', RT_BIZ_HD_TEXT_DOMAIN );
			//			$columns['rthd_ticket_created_by'] = __( 'Ticket Author', RT_BIZ_HD_TEXT_DOMAIN );
			//			$columns['rthd_ticket_last_reply_by'] = __( 'Last Reply By', RT_BIZ_HD_TEXT_DOMAIN );
			$columns['rthd_ticket_followup'] = '<span><span class="vers comment-grey-bubble" title="Reply count"><span class="screen-reader-text">Reply count</span></span></span>';

			//            $columns['rthd_ticket_updated_by']     = __( 'Updated By', RT_BIZ_HD_TEXT_DOMAIN );
			$columns = array_merge( $columns, $cols );
			//			$columns[ 'p2p-from-'.Rt_HD_Module::$post_type.'_to_'.rtbiz_get_contact_post_type() ] = __( 'Participants (Customers)', RT_BIZ_HD_TEXT_DOMAIN );

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
		public function sortable_column( $columns ) {
			$columns['rthd_ticket_title'] = 'ticket';
			$columns['rthd_ticket_created_by'] = 'created_by';
			$columns['rthd_ticket_updated_by'] = 'updated_by';
			$columns['rthd_ticket_followup'] = 'comment_count';
			return $columns;
		}

		public function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i = 0;

			if ( ! $action_count ) {
				return ''; }

			$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
			foreach ( $actions as $action => $link ) {
				++ $i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				$out .= "<span class='$action'>$link$sep</span>";
			}
			$out .= '</div>';

			return $out;
		}

		public function add_gravatar_class( $class ) {
			$class = str_replace( "class='avatar", "class='avatar rthd-avatar-img", $class );
			return $class;
		}

		/**
		 * Edit Content of List view Columns
		 *
		 * @since  0.1
		 *
		 * @param $column
		 */
		public function manage_custom_columns( $column ) {

			global $post, $rtbiz_hd_module,$rtbiz_hd_email_notification;

			$can_edit_post = current_user_can( 'edit_post', $post->ID );
			$post_type_object = get_post_type_object( $post->post_type );

			switch ( $column ) {

				case 'rthd_ticket_customers':
					// customers
					$create             = new DateTime( $post->post_date );
					$createdate         = $create->format( 'M d, Y h:i A' );
					$create_by_time     = esc_attr( human_time_diff( strtotime( $createdate ), current_time( 'timestamp' ) ) ) . ' ago';
					$created_by         = rtbiz_hd_get_ticket_creator( $post->ID );
					$CCs = rtbiz_get_post_for_contact_connection( $post->ID , Rtbiz_HD_Module::$post_type, true );
					//					$CCs                = $rtbiz_hd_email_notification->get_contacts( $post->ID );
					//					$CCs                = wp_list_pluck( $CCs, 'email' );
					//					if ( ! empty( $created_by ) && ! empty( $CCs ) ) {
					//						$CCs = array_diff( $CCs, array( $created_by->user_email ) );
					//					}
					?>
					<div class="rthd-ticket-user-activity-backend">
						<?php
						if ( ! empty( $created_by ) ) {
							// Show ticket created by with large gravatar
							echo ' <a class="rthd-ticket-created-by" title="Created by ' . $created_by->post_title . ' ' . $create_by_time . '" href="' .  admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&created_by='.$created_by->ID ) .'">' . get_avatar( $created_by->primary_email, '30' ) . '</a>';
						}
						foreach ( $CCs as $contact ) {
							// show other CCs' contact
							$email = rtbiz_get_entity_meta( $contact->ID, Rtbiz_Contact::$primary_email_key, true );
							$display_name = $contact->post_title;
							$url = admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&created_by='.$contact->ID );
							echo '<a title= "' . $display_name . '" class="rthd-last-reply-by rthd-contact-avatar-no-reply"  href="' .$url . '">' . get_avatar( $email, '30' ) . ' </a>';
						}
						?>
					</div>
					<?php
					break;

				case 'rthd_ticket_staff':
					$subscriber         = $rtbiz_hd_email_notification->get_subscriber( $post->ID );
					$subscriber         = wp_list_pluck( $subscriber, 'email' );
					$assigned_to        = get_user_by( 'id', $post->post_author );
					if ( ! empty( $assigned_to ) ) {
						$subscriber         = array_diff( $subscriber, array( $assigned_to->user_email ) );
					}
					?>
					<div class="rthd-ticket-user-activity-backend">
						<?php
						if ( ! empty( $assigned_to ) ) {
							// Show ticket assignee by with large gravatar
							echo ' <a class="rthd-ticket-created-by" title="Assigned to ' . $assigned_to->display_name .'" href="' .  admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&assigned='.$assigned_to->ID ) .'">' . get_avatar( $assigned_to->user_email, '30' ) . '</a>';
						}
						foreach ( $subscriber as $email ) {
							// show other CCs' contact
							$user         = get_user_by( 'email', $email );
							$display_name = $email;
							if ( ! empty( $user ) ) {
								$display_name = $user->display_name;
							}
							echo '<a title= "' . $display_name . '" class="rthd-last-reply-by rthd-contact-avatar-no-reply"  href="' . admin_url( 'edit.php?post_type='.Rtbiz_HD_Module::$post_type.'&assigned='.$user->ID )  . '">' . get_avatar( $email, '30' ) . ' </a>';
						}
						?>
					</div>
					<?php
					break;

				case 'rthd_ticket_assignee':
					$user_id = $post->post_author;
					$user_info = get_userdata( $user_id );
					$query_var = array(
						'post_type' => Rtbiz_HD_Module::$post_type,
						'assigned' => $user_id,
					);
					$url = esc_url( add_query_arg( $query_var, 'edit.php' ) );
					if ( $user_info ) {
						//                      printf( "<a href='%s'>%s</a>", $url, $user_info->display_name );
						add_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
						printf( " <a href='%s'>%s <span  class='rthd_td_show'>%s</span> ", $url, get_avatar( $user_info->user_email, 25 ), $user_info->display_name );
						remove_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
					} else {
						echo '<div>' . __( 'No assignee', 'rtbiz-helpdesk' ) . '</div>';
					}
					break;

				case 'rthd_ticket_followup' :
					echo '<div class="column-comments"><div class="post-com-count-wrapper"><a href="javascript:;" class="post-com-count post-com-count-approved"><span class="comment-count-approved" aria-hidden="true">' . ( $post->comment_count ) . '</span><span class="screen-reader-text">' . ( $post->comment_count ) . ' comments</span></a></div></div>';
					break;
				case 'rthd_ticket_last_reply_by' :
					$comment = get_comments( array( 'post_id' => $post->ID, 'number' => 1 ) );
					if ( ! empty( $comment ) ) {
						$comment = $comment[0];
						$lastreplyby = sprintf( '<span class="created-by tips" data-tip="%s">'.__( '%s', 'rtbiz-helpdesk' ).'</span>', $comment->comment_date, human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . __( ' ago', 'rtbiz-helpdesk' ) );
						add_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
						printf( "<div class='rthd-ticket-author'>%s <span class='rthd_td_show'>%s</span> <span class='rthd_td_show'>%s</span></div>", get_avatar( $comment->comment_author_email, 25 ), $comment->comment_author, $lastreplyby );
						remove_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
					} else {
						echo '<div style="text-align: center;">' . __( 'No reply', 'rtbiz-helpdesk' ) . '</div>';
					}
					break;

				case 'rthd_ticket_status':
					echo rtbiz_hd_status_markup( $post->post_status );
					break;

				case 'rthd_ticket_title' :

					if ( $can_edit_post && $post->post_status != 'trash' ) {
						printf( __( '%s  %s', 'rtbiz-helpdesk' ), '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '"><strong>' . esc_attr( _x( '#', 'hash before order number', 'rtbiz-helpdesk' ) . esc_attr( $post->ID ) ) . '</strong></a>', $post->post_title );
					} else {
						printf( __( '%s  %s', 'rtbiz-helpdesk' ), '<strong>' . esc_attr( _x( '#', 'hash before order number', 'rtbiz-helpdesk' ) . esc_attr( $post->ID ) ) . '</strong>', $post->post_title );
					}

					get_inline_data( $post );

					global $wp_version;
					if ( version_compare( $wp_version, '4.3', '<' ) ) {
						$actions = array();
						if ( $can_edit_post && 'trash' != $post->post_status ) {
							$actions['edit']                 = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item', 'rtbiz-helpdesk' ) ) . '">' . __( 'Edit', 'rtbiz-helpdesk' ) . '</a>';
							$actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="' . esc_attr( __( 'Edit this item inline', 'rtbiz-helpdesk' ) ) . '">' . __( 'Quick&nbsp;Edit', 'rtbiz-helpdesk' ) . '</a>';
						}

						if ( current_user_can( 'delete_post', $post->ID ) ) {
							if ( 'trash' == $post->post_status ) {
								$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', 'rtbiz-helpdesk' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore', 'rtbiz-helpdesk' ) . '</a>';
							} else {
								$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', 'rtbiz-helpdesk' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', 'rtbiz-helpdesk' ) . '</a>';
							}
							if ( 'trash' == $post->post_status ) {
								$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', 'rtbiz-helpdesk' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'rtbiz-helpdesk' ) . '</a>';
							}
						}
						if ( $post_type_object->public ) {
							if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
								if ( $can_edit_post ) {
									$preview_link = set_url_scheme( get_permalink( $post->ID ) );
									/** This filter is documented in wp-admin/includes/meta-boxes.php */
									$preview_link    = apply_filters( 'preview_post_link', esc_url( add_query_arg( 'preview', 'true', $preview_link ) ), $post );
									$actions['view'] = '<a href="' . esc_url( $preview_link ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'rtbiz-helpdesk' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview', 'rtbiz-helpdesk' ) . '</a>';
								}
							} elseif ( 'trash' != $post->post_status ) {
								$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'rtbiz-helpdesk' ), $post->post_title ) ) . '" rel="permalink">' . __( 'View', 'rtbiz-helpdesk' ) . '</a>';
							}
						} elseif ( 'trash' != $post->post_status ) {
							$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'rtbiz-helpdesk' ), $post->post_title ) ) . '" rel="permalink">' . __( 'View', 'rtbiz-helpdesk' ) . '</a>';
						}
						echo $this->row_actions( $actions );
					}
					break;

				case 'rthd_ticket_created_by':

					$date = new DateTime( get_the_date( 'Y-m-d H:i:s' ) );
					$datediff = human_time_diff( $date->format( 'U' ), current_time( 'timestamp' ) ) . __( ' ago', 'rtbiz-helpdesk' );
					$contact = rtbiz_hd_get_ticket_creator( $post->ID );
					$url = esc_url(
						add_query_arg(
							array(
								'post_type' => Rtbiz_HD_Module::$post_type,
								'created_by' => $contact->ID,
									), 'edit.php' ) );

									$replyby = sprintf( __( '<span class="created-by tips" data-tip="%s">%s </span>', 'rtbiz-helpdesk' ), get_the_date( 'd-m-Y H:i' ), $datediff );
							if ( ! empty( $contact ) ) {
								add_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
								printf( "<div class='rthd-ticket-author'><a href='%s'>%s <span  class='rthd_td_show'>%s</span></a> <span class='rthd_td_show'>%s</span></div>", $url, get_avatar( $contact->primary_email, 25 ), $contact->post_title, $replyby );
								remove_filter( 'get_avatar', array( $this, 'add_gravatar_class' ) );
							}
					break;

				/* case 'rthd_ticket_updated_by':

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

				  printf( __( '<span class="created-by tips" data-tip="%s">%s', RTBIZ_HD_TEXT_DOMAIN ), get_the_modified_date( 'd-m-Y H:i' ), $datediff );
				  if ( $user_info ) {
				  printf( ' by <a href="%s">%s</a>', $url, $user_info->display_name );
				  }
				  printf( '</span>' );
				  break; */
			}
		}

		/**
		 * Remove Default meta boxes on Edit post View for ticket
		 *
		 * @since  0.1
		 */
		public function remove_meta_boxes() {
			remove_meta_box( 'revisionsdiv', Rtbiz_HD_Module::$post_type, 'normal' );
			remove_meta_box( 'commentstatusdiv', Rtbiz_HD_Module::$post_type, 'normal' );
			remove_meta_box( 'slugdiv', Rtbiz_HD_Module::$post_type, 'normal' );
		}

		/**
		 * Add custom meta boxes on Edit post View for ticket
		 *
		 * @since  0.1
		 */
		public function add_meta_boxes() {
			global $post;
			if ( ! empty( $post ) && 'auto-draft' != $post->post_status ) {
				remove_post_type_support( Rtbiz_HD_Module::$post_type, 'editor' );
				add_meta_box( 'rt-hd-ticket-follow-up', __( 'Reply', 'rtbiz-helpdesk' ), 'Rtbiz_HD_Ticket_Comments::ui', Rtbiz_HD_Module::$post_type, 'normal', 'high' );
			}

			add_meta_box( 'rt-hd-ticket-data', __( 'Ticket Information', 'rtbiz-helpdesk' ), 'Rtbiz_HD_Ticket_Info::ui', Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-subscriiber', __( 'Participants (Staff)', 'rtbiz-helpdesk' ), 'Rtbiz_HD_Subscribers::ui', Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-contact', __( 'Participants (Customer)', 'rtbiz-helpdesk' ), 'Rtbiz_HD_Contact::ui', Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-ticket-order-history', __( 'Purchase History', 'rtbiz-helpdesk' ), array( $this, 'order_history' ), Rtbiz_HD_Module::$post_type, 'side', 'default' );
			add_meta_box( 'rt-hd-ticket-contacts-blacklist', __( 'Blacklist Contacts', 'rtbiz-helpdesk' ), 'Rtbiz_HD_Ticket_Contacts_Blacklist::ui', Rtbiz_HD_Module::$post_type, 'side', 'low' );
			add_meta_box( 'rt-hd-attachment', __( 'Attachments', 'rtbiz-helpdesk' ), 'Rtbiz_HD_Attachment::ui', Rtbiz_HD_Module::$post_type, 'side', 'low' );
			//          add_meta_box( 'rt-hd-external-link', __( 'Reference Links', RT_BIZ_HD_TEXT_DOMAIN ), 'Rtbiz_HD_External_Link::ui', Rt_HD_Module::$post_type, 'side', 'default' );
		}

		/**
		 * up[date metabox order
		 */
		public function metabox_rearrenge() {
			global $wp_meta_boxes;
			$custom_order['submitdiv'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core']['submitdiv'];
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core']['submitdiv'] );

			$custom_order['rt-hd-ticket-data'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-data'];
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-data'] );

			$custom_order[ Rt_Products::$product_slug . 'div' ] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core'][ Rt_Products::$product_slug . 'div' ];
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core'][ Rt_Products::$product_slug . 'div' ] );

			$custom_order['rt-hd-subscriiber'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-subscriiber'];
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-subscriiber'] );

			$custom_order['rt-hd-ticket-order-history'] = $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-order-history'];
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-order-history'] );

			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-data'] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-subscriiber'] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['default']['rt-hd-ticket-order-history'] );
			unset( $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core'][ Rtbiz_Teams::$slug . 'div' ] );

			$custom_order = array_merge( $custom_order, $wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core'] );
			$wp_meta_boxes[ Rtbiz_HD_Module::$post_type ]['side']['core'] = $custom_order;
		}

		/**
		 * @param $post
		 * add meta box for showing purchase history history
		 */
		public function order_history( $post ) {
			add_filter( 'rtbiz_hd_ticket_purchase_history_box_wrapper_start', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_header_wrapper_start', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_heading', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_header_wrapper_end', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_box_wrapper_end', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_wrapper_start', '__return_empty_string', 10, 1 );
			add_filter( 'rtbiz_hd_ticket_purchase_history_wrapper_end', '__return_empty_string', 10, 1 );
			do_action( 'rtbiz_hd_user_purchase_history', $post->ID );
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
			//global $rtbiz_hd_module;
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
			if ( ! in_array( $post->post_type, array( Rtbiz_HD_Module::$post_type ) ) ) {
				return;
			}

			do_action( 'rt_hd_process_' . $post->post_type . '_meta', $post_id, $post );
			if ( 'trash' == $post->post_status ) {

				$url = esc_url_raw( add_query_arg( array( 'post_type' => Rtbiz_HD_Module::$post_type ), admin_url( 'edit.php' ) ) );
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
		public function pre_filter( $query ) {
			if ( isset( $_GET['post_type'] ) && Rtbiz_HD_Module::$post_type == $_GET['post_type'] && $query->is_main_query() ) {
				$orderby = $query->get( 'orderby' );
				if ( isset( $_GET['contact_id'] ) ) {
					$formss = array();
					$contact_id = $_GET['contact_id'];
					global $wpdb;
					global $rtbiz_contact;
					$contact_froms = $wpdb->get_results(
						'SELECT p2p_from
                            FROM ' . $wpdb->prefix . "p2p
								WHERE p2p_type = '" . Rtbiz_HD_Module::$post_type . '_to_' . $rtbiz_contact->post_type .
					"' AND p2p_to = " . $contact_id );

					foreach ( $contact_froms as $form ) {
						$formss[] = intval( $form->p2p_from );
					}
					$query->set( 'post__in', $formss );
				}
				if ( isset( $_GET['account_id'] ) ) {
					$formss = array();
					$account_id = $_GET['account_id'];
					global $wpdb;
					global $rt_company;
					$account_froms = $wpdb->get_results(
						"SELECT p2p_from
							FROM wp_p2p
								WHERE p2p_type = '" . Rtbiz_HD_Module::$post_type . '_to_' . $rt_company->post_type .
					"' AND p2p_to = " . $account_id );

					foreach ( $account_froms as $form ) {
						$formss[] = intval( $form->p2p_from );
					}
					$query->set( 'post__in', $formss );
				}
				if ( isset( $orderby ) && ! empty( $orderby ) ) {
					switch ( $orderby ) {
						case 'ticket':
							$query->set( 'orderby', 'post_ID' );
							break;
						case 'create_by':
							$query->set( 'orderby', 'Date' );
							break;
						case 'updated_by':
							$query->set( 'orderby', 'modified' );
							break;
						case 'comments':
							$query->set( 'orderby', 'comment_count' );
							break;
					}
				} else {
					$query->set( 'orderby', 'modified' );
					$query->set( 'order', 'desc' );
				}

				if ( isset( $_GET['product_id'] ) ) {
					global $rtbiz_products;
					$query->set( 'tax_query', array(
						array(
							'taxonomy' => Rt_Products::$product_slug,
							'field' => 'term_id',
							'terms' => $_GET['product_id'],
						),
					) );
				}

				if ( isset( $_GET['created_by'] ) ) {

					$query->set( 'meta_query', array(
						array(
							'key' => '_rtbiz_hd_created_by',
							'value' => $_GET['created_by'],
						),
					) );
				}

				if ( isset( $_GET['trans-id'] ) && intval( $_GET['trans-id'] ) ) {
					$query->set( 'meta_query', array(
						array(
							'key' => '_rtbiz_hd_transaction_id',
							'value' => $_GET['trans-id'],
						),
					) );
				}

				if ( isset( $_GET['order_id'] ) && intval( $_GET['order_id'] ) ) {
					$query->set( 'meta_query', array(
						array(
							'key' => '_rtbiz_hd_order_id',
							'value' => $_GET['order_id'],
						),
					) );
				}

				if ( isset( $_GET['assigned'] ) ) {
					$query->set( 'author', $_GET['assigned'] );
				}

				if ( isset( $_GET['updated_by'] ) ) {

					$query->set( 'meta_query', array(
						array(
							'key' => '_rtbiz_hd_updated_by',
							'value' => $_GET['updated_by'],
						),
					) );
				}

				if ( isset( $_GET['ticket_status'] ) ) {
					$query->set( 'post_status', $_GET['ticket_status'] );
				}

				if ( isset( $_GET['ticket_assigned'] ) ) {
					$query->set( 'author', $_GET['ticket_assigned'] );
				}

				if ( isset( $_GET['favorite'] ) ) {
					$fav_ticket = rtbiz_hd_get_user_fav_ticket( get_current_user_id() );
					$query->set( 'post__in', $fav_ticket );
				}

				if ( isset( $_GET['subscribed'] ) ) {
					global $wpdb;
					//subscribe ticket
					$contacts = rtbiz_hd_get_user_subscribe_ticket( get_current_user_id() );
					$query->set( 'post__in', $contacts );
					$query->set( 'author', '' );
				} else {
					$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );
					if ( ! current_user_can( $editor_cap ) ) {
						$query->set( 'author', get_current_user_id() );
					}
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
		public function after_restore_trashed_ticket( $post_id ) {

			$ticket = get_post( $post_id );

			if ( $ticket->post_type == Rtbiz_HD_Module::$post_type ) {

				global $rtbiz_hd_ticket_history_model;

				$rtbiz_hd_ticket_history_model->insert(
					array(
							'ticket_id' => $post_id,
							'type' => 'post_status',
							'old_value' => 'trash',
							'new_value' => 'hd-unanswered',
							'message' => null,
							'update_time' => current_time( 'mysql' ),
							'updated_by' => get_current_user_id(),
						) );

						$ticket->post_status = 'hd-unanswered';
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
		public function before_ticket_deleted( $post_id ) {
			global $wpdb;

			if ( get_post_type( $post_id ) == Rtbiz_HD_Module::$post_type ) {

				global $rtbiz_hd_ticket_history_model, $rtbiz_hd_ticket_index_model;

				$ticket_index = array( 'post_id' => $post_id );
				$ticket_history = array( 'ticket_id' => $post_id );

				$rtbiz_hd_ticket_history_model->delete( $ticket_history );

				$rtbiz_hd_ticket_index_model->delete_ticket( $ticket_index );

				// remove ticket from favorites list
				$sql = 'DELETE FROM ' . $wpdb->usermeta .  "  WHERE `meta_key` = '_rtbiz_hd_fav_tickets' and `meta_value` = " . $post_id;
				$result = $wpdb->get_results( $sql );

			}
		}

		/**
		 * update status history before ticket trashed
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 */
		public function before_ticket_trashed( $post_id ) {
			if ( get_post_type( $post_id ) == Rtbiz_HD_Module::$post_type ) {
				global $rtbiz_hd_ticket_history_model;
				$rtbiz_hd_ticket_history_model->insert(
					array(
							'ticket_id' => $post_id,
							'type' => 'post_status',
							'old_value' => get_post_status( $post_id ),
							'new_value' => 'trash',
							'message' => null,
							'update_time' => current_time( 'mysql' ),
							'updated_by' => get_current_user_id(),
						) );
			}
		}

		/**
		 * Display custom views along with CPT status
		 *
		 * @param $views
		 *
		 * @return array
		 */
		public function display_custom_views( $views ) {

			$temp_view = array();
			$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );
			$is_editor = current_user_can( $editor_cap );
			$current_user_id = get_current_user_id();
			$query  = array(
				'posts_per_page' => -1,
				'post_type' => Rtbiz_HD_Module::$post_type,
				'post_status' => 'any',
				'author' => $current_user_id,
				'fields' => 'ids',
			);

			$count_user_tickets = new WP_Query(
				$query
			);
			$current_all = true;

			//For Author WordPress provide mine link to view display current user post so added My ticket link only for admin/editor
			if ( $is_editor ) {
				if ( isset( $_GET['author'] ) && ( $_GET['author'] == $current_user_id ) ) {
					$class = ' class="current"'; $current_all = false;} else { 					$class = ''; }
				$temp_view['mine'] = "<a href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "&author=$current_user_id' $class>" . sprintf( _nx( 'Mine <span class="count">(%s)</span>', 'Mine <span class="count">(%s)</span>', $count_user_tickets->found_posts, 'rtbiz-helpdesk', 'rtbiz-helpdesk' ), number_format_i18n( $count_user_tickets->post_count ) ) . '</a>';
			} else {
				unset( $views['all'] );
			}

			$fav_ticket = rtbiz_hd_get_user_fav_ticket( $current_user_id );
			if ( ! empty( $fav_ticket ) ) {
				if ( isset( $_GET['favorite'] ) ) {
					$class = ' class="current"'; $current_all = false; } else { 					$class = ''; }
				$temp_view['favorite_ticket'] = "<a href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "&favorite=true' $class>" . sprintf( _nx( 'Favorite <span class="count">(%s)</span>', 'Favorites <span class="count">(%s)</span>', count( $fav_ticket ), 'rtbiz-helpdesk', 'rtbiz-helpdesk' ), number_format_i18n( count( $fav_ticket ) ) ) . '</a>';
			}

			$contacts = rtbiz_hd_get_user_subscribe_ticket( get_current_user_id() );
			if ( ! empty( $contacts ) ) {
				if ( isset( $_GET['subscribed'] ) ) {
					$class = ' class="current"';
					$current_all = false;
				} else {
					$class = '';
				}
				$temp_view['subscribe_ticket'] = "<a href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "&subscribed=true' $class>" . sprintf( _nx( 'Subscribed <span class="count">(%s)</span>', 'Subscribed <span class="count">(%s)</span>', count( $contacts ), 'rtbiz-helpdesk','rtbiz-helpdesk' ), number_format_i18n( count( $contacts ) ) ) . '</a>';
			}

			//remove count for editor
			if ( ! $is_editor  ) {
				foreach ( $views as $key => $view ) {
					$views[ $key ] = preg_replace( '#<span class=["\']count["\']>(.*?)</span>#', '', $view );
				}
			}
			$views = $temp_view + $views;
			if ( isset( $views['trash'] ) ) {
				$trash = $views['trash'];
				unset( $views['trash'] );
				$views['trash'] = $trash;
			}

			if ( rtbiz_hd_get_redux_adult_filter() && rtbiz_hd_get_user_adult_preference( $current_user_id ) == 'yes' && $is_editor ) {
				$all_query = array(
					'posts_per_page' => -1,
					'post_type'      => Rtbiz_HD_Module::$post_type,
					'post_status'    => 'any',
					'fields '        => 'ids',
					'meta_query'     => array(
						array(
							'key'    => '_rtbiz_hd_ticket_adult_content',
							'value'  => 'no',
						),
					),
				);
				$class = '';
				if ( $current_all ) {
					$class = ' class="current"';
				}
				$adult_count_user_all_tickets = new WP_Query( $all_query );
				$views['all'] = "<a " . $class. " href='edit.php?post_type=" . Rtbiz_HD_Module::$post_type . "'> All <span class='count'>(" .$adult_count_user_all_tickets->found_posts . ")</span></a>";
			}

			return $views;
		}

		/**
		 * Display custom filters to filter out tickets.
		 */
		public function display_custom_filters() {
			global $typenow, $rtbiz_hd_module, $rtbiz_products, $rtbiz_hd_rt_attributes;

			if ( Rtbiz_HD_Module::$post_type == $typenow ) {

				// Filter by status
				echo '<label class="screen-reader-text" for="ticket_status">' . __( 'Filter by status','rtbiz-helpdesk' ) . '</label>';

				$statuses = $rtbiz_hd_module->get_custom_statuses();

				echo '<select id="ticket_status" class="postform" name="ticket_status">';
				echo '<option value="0">Select Status</option>';

				foreach ( $statuses as $status ) {
					if ( isset( $_GET['ticket_status'] ) && $status['slug'] == $_GET['ticket_status'] ) {
						echo '<option value="' . $status['slug'] . '" selected="selected">' . $status['name'] . '</option>';
					} else {
						echo '<option value="' . $status['slug'] . '">' . $status['name'] . '</option>';
					}
				}
				echo '</select>';

				// Filter by assignee
				echo '<label class="screen-reader-text" for="ticket_assigned">' . __( 'Filter by assignee', 'rtbiz-helpdesk' ) . '</label>';

				$ticket_authors = Rtbiz_HD_Utils::get_hd_rtcamp_user();

				echo '<select id="ticket_assigned" class="postform" name="ticket_assigned">';
				echo '<option value="0">Select Assignee</option>';

				foreach ( $ticket_authors as $author ) {
					if ( isset( $_GET['ticket_assigned'] ) && $author->ID == $_GET['ticket_assigned'] ) {
						echo '<option value="' . $author->ID . '" selected="selected">' . $author->display_name . '</option>';
					} else {
						echo '<option value="' . $author->ID . '">' . $author->display_name . '</option>';
					}
				}

				echo '</select>';

				// Filter by product
				$products = array();
				if ( isset( $rtbiz_products ) ) {
					$products = get_terms( Rt_Products::$product_slug, array( 'hide_empty' => 0 ) );
				}

				if ( ! empty( $products ) ) {
					echo '<label class="screen-reader-text" for="rt_product">' . __( 'Filter by product', 'rtbiz-helpdesk' ) . '</label>';

					echo '<select id="' . Rt_Products::$product_slug . '" class="postform" name="' . Rt_Products::$product_slug . '">';
					echo '<option value="0">Select Product</option>';

					foreach ( $products as $product ) {
						if ( isset( $_GET[ Rt_Products::$product_slug ] ) && $product->slug == $_GET[ Rt_Products::$product_slug ] ) {
							echo '<option value="' . $product->slug . '" selected="selected">' . $product->name . '</option>';
						} else {
							echo '<option value="' . $product->slug . '">' . $product->name . '</option>';
						}
					}

					echo '</select>';
				}
				$attrs = rtbiz_hd_get_attributes( Rtbiz_HD_Module::$post_type );
				foreach ( $attrs as $attr ) {
					if ( ! empty( $attr->attribute_store_as ) && 'taxonomy' == $attr->attribute_store_as ) {
						$attr_tax = $rtbiz_hd_rt_attributes->get_taxonomy_name( $attr->attribute_name );
						$attr_terms = get_terms( $attr_tax, array( 'hide_empty' => false ) );
						if ( ! empty( $attr_terms ) ) {
							echo '<label class="screen-reader-text" for="' . $attr_tax . '">' . __( 'Filter by '.$attr->attribute_label, 'rtbiz-helpdesk' ) . '</label>';

							echo '<select id="' . $attr->attribute_name . '" class="postform" name="' . $attr_tax . '">';
							echo '<option value="0">Select ' . $attr->attribute_label . '</option>';

							foreach ( $attr_terms as $terms ) {
								if ( isset( $_GET[ $attr_tax ] ) && $terms->slug == $_GET[ $attr_tax ] ) {
									echo '<option value="' . $terms->slug . '" selected="selected">' . $terms->name . '</option>';
								} else {
									echo '<option value="' . $terms->slug . '">' . $terms->name . '</option>';
								}
							}
							echo '</select>';
						}
					}
				}
			}
		}

		/**
		 * @param $post
		 * Add ticket id on edit ticket
		 */
		public function append_ticket_id_to_title( $post ) {
			if ( ! empty( $post ) && $post->post_type == Rtbiz_HD_Module::$post_type ) {
				echo '<h2>[#' . $post->ID . '] </h2>';
			}
		}

	}

}
