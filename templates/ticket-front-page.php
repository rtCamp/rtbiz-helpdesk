<?php
/*
 * ticket front page Template
 *
 * @author udit
 */
get_header();

do_action( 'rthd_ticket_front_page_after_header' );

global $rt_hd_module, $post;
$post_type = get_post_type( $post );
$labels = $rt_hd_module->labels;
$post_id = $post->ID;
$user_edit = false;
$assignee_info = array();
$current_user = get_user_by( 'id', get_current_user_id() );

$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
$user_edit_content = current_user_can( $cap );
?>
<article id="add-new-post" <?php post_class( 'rthd-container' ); ?>>
	<?php
	global $wpdb;

	$post = get_post( $post_id );
	$ticket_unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );

	$create = new DateTime( $post->post_date );

	$modify = new DateTime( $post->post_modified );
	$createdate = $create->format( 'M d, Y h:i A' );
	$modifydate = $modify->format( 'M d, Y h:i A' );
	$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
	?>

	<header class="entry-header clearfix">
		<h1 class="rt-hd-ticket-front-title entry-title"><?php echo esc_attr( ( isset( $post->ID ) ) ? '[#' . $post_id . '] ' . $post->post_title : ''  ); ?></h1>
	</header>

	<div class="clearfix entry-content">

		<div id="rthd-ticket" class="rthd-ticket-content">
			<?php if ( isset( $post->ID ) ) { ?>
				<div id="followup_wrapper">
					<div id="commentlist">
						<?php rthd_get_template( 'followup-common.php', array( 'post' => $post ) ); ?>
					</div>

					<div class="add-followup-form">
						<?php rthd_get_template( 'ticket-add-followup-form.php', array( 'post' => $post, 'ticket_unique_id' => $ticket_unique_id ) ); ?>
					</div>
				</div>
			<?php } ?>
		</div>

		<div id="rthd-sidebar" class="rthd_sticky_div rthd-sidebar">
			<div class="rt-hd-sidebar-box">
				<div class="rt-hd-ticket-info">
					<h3 class="rt-hd-ticket-info-header"><?php _e( esc_attr( ucfirst( $labels[ 'name' ] ) ) . ' Information' ); ?>
					</h3>
					<div class="rthd-front-icons clearfix">
						<?php if ( current_user_can( $cap ) ) { ?>
							<a id='ticket-information-edit-ticket-link' href="<?php echo get_edit_post_link( $post->ID ) ?>" title="<?php _e( 'Edit ' . esc_attr( ucfirst( $labels[ 'name' ] ) ) ); ?>"> <span class="dashicons dashicons-edit"></span></a>
						<?php } ?>

						<?php
						// Watch/Unwatch ticket feature.
						$watch_unwatch_label = $watch_unwatch_value = '';
						if ( current_user_can( $cap ) ) { // For staff/subscriber
							if ( rthd_is_ticket_subscriber( $post->ID ) ) {
								$watch_unwatch_label = 'Unsubscribe';
								$watch_unwatch_value = 'unwatch';
							} else {
								$watch_unwatch_label = 'Subscribe';
								$watch_unwatch_value = 'watch';
							}
						}
						if ( ! empty( $watch_unwatch_label ) ) {
							?>
							<a id="rthd-ticket-watch-unwatch" href="#" data-value="<?php echo $watch_unwatch_value; ?>" title="<?php _e( $watch_unwatch_label ) ?>">
								<?php
								if ( $watch_unwatch_value == 'watch' ) {
									echo '<span class="dashicons dashicons-email-alt"></span>';
								} else {
									echo '<span class="dashicons dashicons-email"></span>';
								}
								?>
							<?php }
							?>
							<a id="ticket-add-fav" href="#" title="<?php _e( 'Favorite ticket' ) ?>"><?php
								if ( in_array( $post->ID, rthd_get_user_fav_ticket( get_current_user_id() ) ) ) {
									echo '<span class="dashicons dashicons-star-filled"></span>';
								} else {
									echo '<span class="dashicons dashicons-star-empty"></span>';
								}
								?></a>
							<?php wp_nonce_field( 'heythisisrthd_ticket_fav_' . $post->ID, 'rthd_fav_tickets_nonce' ); ?>

					</div> <div class="rthd-clearfix"></div>
				</div>

				<div class="rt-hd-ticket-sub-row">
					<div class="rthd-ticket-sidebar-sub-title">
						<span>Status</span></div>
					<div class="rthd-ticket-sidebar-sub-result">
						<?php
						if ( current_user_can( $cap ) ) {
							?>
							<select id="rthd-status-list" class="rthd-ticket-dropdown" name="rt-hd-status" class="">
								<?php
								$post_statuses = $rt_hd_module->get_custom_statuses();
								foreach ( $post_statuses as $status ) {
									$selected = ( $status[ 'slug' ] == $post->post_status ) ? 'selected' : '';
									?>
									<option value="<?php echo esc_attr( $status[ 'slug' ] ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_html( $status[ 'name' ] ); ?></option>
								<?php } ?>
							</select> <img id="status-change-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" /><?php
						} else if ( isset( $post->ID ) ) {
							$pstatus = $post->post_status;
							echo '<div id="rthd-status-visiable" >' . rthd_status_markup( $pstatus ) . '</div>';
						}
						?>
					</div>
				</div>
				<?php
				if ( current_user_can( $cap ) ) {
					$rtcamp_users = Rt_HD_Utils::get_hd_rtcamp_user();
					?>
					<div class="rt-hd-ticket-sub-row">
						<div class="rthd-ticket-sidebar-sub-title">
							<span>
								<?php _e( 'Assignee', RT_HD_TEXT_DOMAIN ); ?>
							</span>
						</div>
						<div class="rthd-ticket-sidebar-sub-result">

							<select id="rthd-assignee-list" class="rthd-ticket-dropdown" name="rt-hd-assignee">
								<?php
								if ( ! empty( $rtcamp_users ) ) {
									foreach ( $rtcamp_users as $author ) {
										if ( $author->ID == $post->post_author ) {
											$selected = ' selected="selected"';
											$assignee_info = $author;
										} else {
											$selected = ' ';
										}
										echo '<option value="' . esc_attr( $author->ID ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $author->display_name ) . '</option>';
									}
								}
								?>
							</select>
							<?php
							$assign_tome_style = '';
							if ( $post->post_author == get_current_user_id() ) {
								$assign_tome_style = 'display:none;';
							}
							?>
							<input type="hidden" class="rthd-current-user-id" value="<?php echo get_current_user_id(); ?>" />
							<a style="<?php echo $assign_tome_style; ?>" href="#" class="rt-hd-assign-me"><?php _e( 'Assign me' ); ?></a>
							<img id="assignee-change-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
						</div>
					</div>
					<?php
				}
				// Participants
				$create_by_time = esc_attr( human_time_diff( strtotime( $createdate ), current_time( 'timestamp' ) ) ) . ' ago';
				$created_by_user_id = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );
				$created_by = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );

				global $wpdb, $rt_hd_email_notification;

				// get all followup author ( aka followup email list ) to display their avatar
				$emails = $wpdb->get_results( 'SELECT distinct(comment_author_email) from ' . $wpdb->comments . ' where comment_post_ID= ' . $post->ID . ' AND comment_type=' . Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC );
				$emails = wp_list_pluck( $emails, 'comment_author_email' );

				// get last comment for getting date and time of last reply by
				$comment = get_comments( array( 'post_id' => $post->ID, 'number' => 1 ) );

				// get connected contacts email address
				$other_contacts = $rt_hd_email_notification->get_contacts( $post->ID );
				$subscriber = array();

				// show subscriber to only authorized users
				if ( current_user_can( $cap ) ) {

					$subscriber = $rt_hd_email_notification->get_subscriber( $post->ID );
					$subscriber = wp_list_pluck( $subscriber, 'email' );
					// remove subscriber from followup email list
					$subscriber = array_diff( $subscriber, $emails );

					// remove ticket creator from subscriber list if present ( in case of staff member created ticket )
					$subscriber = array_diff( $subscriber, array( $created_by->user_email ) );
				}

				$other_contacts = wp_list_pluck( $other_contacts, 'email' );

				// remove user who have added followup and are also in connected contacts list
				$other_contacts = array_diff( $other_contacts, $emails );

				// remove user ticket creator
				$other_contacts = array_diff( $other_contacts, array( $created_by->user_email ) );

				if ( ! empty( $comment ) ) {
					$comment = $comment[ 0 ];
					// remove last reply from all comments
					if ( ! empty( $emails ) ) {
						$emails = array_diff( $emails, array( $comment->comment_author_email ) );
					}
				}
				?>

				<?php
// Products
				global $rtbiz_offerings;
				$products = array();
				if ( ! empty( $rtbiz_offerings ) ) {
					$products = get_terms( Rt_Offerings::$offering_slug );
					$ticket_offering = wp_get_post_terms( $post->ID, Rt_Offerings::$offering_slug );
				}
				if ( ! $products instanceof WP_Error && ! empty( $products ) ) {
					if ( ! empty( $ticket_offering ) || current_user_can( $cap ) ) {
						?>
						<div class="rt-hd-ticket-sub-row">
							<div class="rthd-ticket-sidebar-sub-title">
								<span>
									<?php _e( 'Offering' ); ?>
								</span>
							</div>
							<div class="rthd-ticket-sidebar-sub-result">
								<?php if ( current_user_can( $cap ) ) { ?>
									<select id="rthd-offering-list" class="rthd-ticket-dropdown" name="rt-hd-offering">
										<?php
										foreach ( $products as $p ) {
											if ( ! empty( $ticket_offering ) && $ticket_offering[ 0 ]->term_id == $p->term_id ) {
												$selected = ' selected="selected" ';
											} else {
												$selected = ' ';
											}
											echo '<option value="' . esc_attr( $p->term_id ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $p->name ) . '</option>';
										}
										if ( empty( $ticket_offering ) ) {
											echo '<option value="0" selected="selected" >-Select Offering-</option>';
										}
										?>
									</select>
									<img id="offering-change-spinner" class="helpdeskspinner"
										 src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/>
										 <?php
									 } else {
										 echo '<span>' . $ticket_offering[ 0 ]->name . '</span>';
									 }
									 ?>
							</div>
						</div>
						<?php
					}
				}
				?>


				<div class="rt-hd-ticket-sub-row" style="">
					<div class="rthd-ticket-sidebar-sub-title">
						<span>
							<?php
//						if ( ! empty( $emails ) || ! empty( $subscriber ) || ! empty( $comment ) || ! empty( $other_contacts )){
							echo '<span class="rthd-participants">Participants</span>';
//						} else {
//							echo '<span class="rthd-participants" style="display: none">Participants</span>';
//						}
							?>
						</span>
					</div>
					<div class="rthd-ticket-sidebar-sub-result rthd-ticket-user-activity">
						<?php
						if ( ! empty( $created_by ) ) {
							echo ' <a class="rthd-ticket-created-by" title="Created by ' . $created_by->display_name . ' ' . $create_by_time . '" href="' . ( current_user_can( $cap ) ? rthd_biz_user_profile_link( $created_by->user_email ) : '#') . '">' . get_avatar( $created_by->user_email, '30' ) . '</a>';
						}
						echo "<div class='rthd-contact-avatar-no-reply-div'>";
						// contact group
						foreach ( $other_contacts as $email ) {

							$user = get_user_by( 'email', $email );
							$display_name = $email;

							if ( ! empty( $user ) ) {

								$display_name = $user->display_name;
							}

							echo '<a title= "' . $display_name . '" class="rthd-last-reply-by rthd-contact-avatar-no-reply"  href="' . (current_user_can( $cap ) ? rthd_biz_user_profile_link( $email ) : '#') . '">' . get_avatar( $email, '30' ) . ' </a>';
						}
						echo "</div>";

						if ( current_user_can( $cap ) ) {
							echo '<div class="rthd-subscriber-avatar-no-reply-div">';
							// Subscriber
							foreach ( $subscriber as $email ) {
								$user = get_user_by( 'email', $email );
								$display_name = $email;
								if ( ! empty( $user ) ) {
									$display_name = $user->display_name;
								}
								echo '<a title= "' . $display_name . '" class="rthd-last-reply-by rthd-contact-avatar-no-reply"  href="' . (current_user_can( $cap ) ? rthd_biz_user_profile_link( $email ) : '#') . '">' . get_avatar( $email, '30' ) . ' </a>';
							}
							echo "</div>";
						}
						// Other comments authors
						if ( ! empty( $emails ) ) {
							foreach ( $emails as $email ) {
								$user = get_user_by( 'email', $email );
								$display_name = $email;
								if ( ! empty( $user ) ) {
									$display_name = $user->display_name;
								}
								echo '<a title= "' . $display_name . '" class="rthd-last-reply-by"  href="' . ( current_user_can( $cap ) ? rthd_biz_user_profile_link( $email ) : '#' ) . '">' . get_avatar( $email, '30' ) . ' </a>';
							}
						}
						// Last reply author
						if ( ! empty( $comment ) ) {
							echo '<a class="rthd-last-reply-by" title="last reply by ' . $comment->comment_author . ' ' . esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ) . ' ago " href="' . (current_user_can( $cap ) ? rthd_biz_user_profile_link( $comment->comment_author_email ) : '#') . '">' . get_avatar( $comment->comment_author_email, '30' ) . ' </a>'
							?>
						<?php } ?>

						<div class="rthd-add-people-button">
							<a href="#" id="rthd-add-contact" title="Add people to this ticket"><span class="dashicons dashicons-plus-alt rthd-add-contact-icon"></span></a>
							<div class="rthd-add-people-box">
								<input type="email" placeholder="Enter email to add people" id="rthd-subscribe-email">
								<button type="button" class='rthd-subscribe-email-submit button btn'>Add</button>
								<span style="display: none;" class="rthd-subscribe-validation" ></span>
								<img id="rthd-subscribe-email-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
			if ( isset( $post->ID ) ) {
				$attachments = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', ) );

				if ( ! empty( $attachments ) ) {
					$attach_cmt = rthd_get_attachment_url_from_followups( $post->ID );
					$attachFlag = true;
					$tmphtml = '<div class="rt-hd-sidebar-box"><div class="rt-hd-ticket-info"><h3 class="rt-hd-ticket-info-header">' . __( 'Attachments' ) . '</h3> <div class="rthd-collapse-icon"><a class="rthd-collapse-click" href="#"><span class="dashicons dashicons-arrow-up-alt2"></span></a></div><div class="rthd-clearfix"></div></div><div class="rt-hd-ticket-sub-row"><ul id="attachment-files">';
					?>
					<?php
					foreach ( $attachments as $attachment ) {
						if ( in_array( $attachment->ID, $attach_cmt ) ) {
							continue;
						}
						?>
						<?php
						$extn_array = explode( '.', $attachment->guid );
						$extn = $extn_array[ count( $extn_array ) - 1 ];
						if ( $attachFlag ) {
							echo $tmphtml;
							$attachFlag = false;
						}
						?>
						<li class="attachment-item"
							data-attachment-id="<?php echo esc_attr( $attachment->ID ); ?>">
								<?php rt_hd_get_attchment_link_with_fancybox( $attachment ); ?>
								<?php if ( $user_edit ) { ?>
								<a href="#" class="rthd_delete_attachment right">x</a>
							<?php } ?>
							<input type="hidden" name="attachment[]"
								   value="<?php echo esc_attr( $attachment->ID ); ?>"/>
						</li>
						<?php
					}
					if ( ! $attachFlag ) {
						echo '</ul> </div> </div>';
					}
				}

				if ( current_user_can( $cap ) ) {

					// Attributes
					global $rt_hd_attributes_relationship_model;
					$relations = $rt_hd_attributes_relationship_model->get_relations_by_post_type( Rt_HD_Module::$post_type );
					foreach ( $relations as $r ) {
						$attr = $rt_hd_attributes_model->get_attribute( $r->attr_id );
						if ( 'taxonomy' == $attr->attribute_store_as ) {
							$taxonomy = $rt_hd_rt_attributes->get_taxonomy_name( $attr->attribute_name );
							$terms = wp_get_post_terms( $post->ID, $taxonomy );

							if ( ! $terms instanceof WP_Error && ! empty( $terms ) ) {
								?>
								<div class="rt-hd-sidebar-box">
									<div class="rt-hd-ticket-info">
										<h3 class="rt-hd-ticket-info-header"><?php echo $attr->attribute_label; ?></h3>
										<div class="rthd-collapse-icon"><a class='rthd-collapse-click' href="#"><span class="dashicons dashicons-arrow-up-alt2"></span></a></div>
										<div class="rthd-clearfix"></div>
									</div>
									<div class="rt-hd-ticket-sub-row">
										<ul>
											<?php foreach ( $terms as $t ) { ?>
												<li><?php echo $t->name; ?></li>
											<?php } ?>
										</ul>
									</div>
								</div>
								<?php
							}
						}
					}

					// Order History
					do_action( 'rtbiz_hd_user_purchase_history', $post->ID );
				}
			}

			$created_by = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
			$otherposts = get_posts( array( 'post_type' => Rt_HD_Module::$post_type, 'post_status' => 'any', 'post__not_in' => array( $post->ID ), 'meta_query' => array(
					array(
						'key' => '_rtbiz_hd_created_by',
						'value' => $created_by->ID,
					),
				), ) );
			if ( $otherposts ) {
				?>
				<div class="rt-hd-sidebar-box">
					<div class="rt-hd-ticket-info">
						<h3 class="rt-hd-ticket-info-header"><?php echo __( 'Ticket History' ); ?></h3>
						<div class="rthd-collapse-icon"><a class='rthd-collapse-click' href="#"><span class="dashicons dashicons-arrow-up-alt2"></span></a></div>
						<div class="rthd-clearfix"></div>

					</div>
					<div class="rt-hd-ticket-sub-row rt-hd-ticket-history">
						<ul>
							<?php foreach ( $otherposts as $p ) { ?>
								<li><a href="<?php echo get_post_permalink( $p->ID ); ?>" ><?php echo '[#' . $p->ID . '] ' . esc_attr( strlen( balanceTags( $p->post_title ) ) > 15 ? substr( balanceTags( $p->post_title ), 0, 15 ) . '...' : balanceTags( $p->post_title )  ) ?>  </a><?php echo rthd_status_markup( $p->post_status ); ?></li>
							<?php } ?>
						</ul>
					</div>
				</div>
			<?php } ?>

			<?php
			if ( current_user_can( $cap ) ) {
				$connected_tickets = new WP_Query( array(
					'connected_type' => Rt_HD_Module::$post_type . '_to_' . Rt_HD_Module::$post_type,
					'connected_items' => $post->ID,
					'nopaging' => true,
						) );
				if ( $connected_tickets->have_posts() ) {
					?>
					<div class="rt-hd-sidebar-box">
						<div class="rt-hd-ticket-info">
							<h3 class="rt-hd-ticket-info-header"><?php echo __( 'Related Tickets' ); ?></h3>

							<div class="rthd-collapse-icon"><a class='rthd-collapse-click' href="#"><span
										class="dashicons dashicons-arrow-up-alt2"></span></a></div>
							<div class="rthd-clearfix"></div>
						</div>
						<div class="rt-hd-ticket-sub-row rt-hd-related-ticket">
							<ul>
								<?php foreach ( $connected_tickets->posts as $p ) { ?>
									<li>
										<a href="<?php echo get_post_permalink( $p->ID ); ?>"><?php echo '[#' . $p->ID . '] ' . esc_attr( strlen( balanceTags( $p->post_title ) ) > 15 ? substr( balanceTags( $p->post_title ), 0, 15 ) . '...' : balanceTags( $p->post_title )  ) ?>  </a><?php echo rthd_status_markup( $p->post_status ); ?>
									</li>
								<?php } ?>
							</ul>
						</div>
					</div>
					<?php
				}
			}
			?>
		</div>

	</div>

</article>
<a href="#" class="rthd-scroll-up"></a>
<?php
do_action( 'rthd_ticket_front_page_before_footer' );
get_footer();
