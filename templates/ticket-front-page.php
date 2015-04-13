<?php

/*
 * ticket front page Template
 *
 * @author udit
 */
get_header();

do_action( 'rthd_ticket_front_page_after_header' );

global $rt_hd_module, $post;
$post_type       = get_post_type( $post );
$labels          = $rt_hd_module->labels;
$post_id         = $post->ID;
$user_edit       = false;
$assignee_info	 = array();
$current_user	 = get_user_by( 'id', get_current_user_id() );

$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
$user_edit_content = current_user_can( $cap );
	?>
	<div id="add-new-post" class="rthd-container row">
	<?php
	global $wpdb;

	$post             = get_post( $post_id );
	$ticket_unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );

	$create = new DateTime( $post->post_date );

	$modify     = new DateTime( $post->post_modified );
	$createdate = $create->format( 'M d, Y h:i A' );
	$modifydate = $modify->format( 'M d, Y h:i A' );
	$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );

	?>
	<div id="rthd-ticket">
		<input type="hidden" id='ticket_unique_id' value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>

			<div>
				<h1 class="rt-hd-ticket-front-title"><?php echo esc_attr( ( isset( $post->ID ) ) ? '[#' . $post_id . '] ' . $post->post_title : '' ); ?></h1>
			</div>

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
		<div id="rthd-sidebar" class="rthd_sticky_div">

			<div class="rt-hd-ticket-info">
				<h2 class="rt-hd-ticket-info-header"><i class="foundicon-idea"></i> <?php _e( esc_attr( ucfirst( $labels[ 'name' ] ) ) . ' Information' ); ?>
				</h2>
				<div class="rt-hd-front-icons">
					<?php if ( current_user_can( $cap ) ){ ?>
						<a id='ticket-information-edit-ticket-link' href="<?php echo get_edit_post_link( $post->ID )  ?>" title="<?php _e( 'Edit '.esc_attr( ucfirst( $labels[ 'name' ] ) ) ); ?>"> <span class="dashicons dashicons-edit"></span></a>
					<?php } ?>


					<?php
					// Watch/Unwatch ticket feature.
					$watch_unwatch_label = $watch_unwatch_value = '';
					if ( current_user_can( $cap ) && $assignee_info->user_email != $current_user->user_email ) { // For staff/subscriber
						if ( rthd_is_ticket_subscriber( $post->ID ) ) {
							$watch_unwatch_label = 'Unsubscribe';
							$watch_unwatch_value = 'unwatch';
						}
						else {
							$watch_unwatch_label = 'Subscribe';
							$watch_unwatch_value = 'watch';
						}
					}
					if( ! empty( $watch_unwatch_label ) ) { ?>
					<a id="rthd-ticket-watch-unwatch" href="#" data-value="<?php echo $watch_unwatch_value; ?>" title="<?php _e( $watch_unwatch_label ) ?>">
						<?php
						if ($watch_unwatch_value == 'watch'){
							echo '<span class="dashicons dashicons-email-alt"></span>';
						} else{
							echo '<span class="dashicons dashicons-email"></span>';
						}
						?>
						<?php
						}?>
						<a id="ticket-add-fav" href="#" title="<?php _e('Favorite ticket') ?>"><?php
						if ( in_array( $post->ID , rthd_get_user_fav_ticket( get_current_user_id() ) ) ){
							echo '<span class="dashicons dashicons-star-filled"></span>';
 						} else {
							echo '<span class="dashicons dashicons-star-empty"></span>';
						}
						?></a>
					<?php wp_nonce_field( 'heythisisrthd_ticket_fav_'.$post->ID, 'rthd_fav_tickets_nonce' ); ?>


				</div>
			</div>

			<div class="rt-hd-ticket-info">
				<span title="Status"><strong>Status: </strong></span>
				<?php
					if ( current_user_can( $cap ) ){
					?>
						<select id="rthd-status-list" name="rt-hd-status" class="">
							<?php $post_statuses = $rt_hd_module->get_custom_statuses();
							foreach ( $post_statuses as $status ) {
								$selected = ( $status[ 'slug' ] == $post->post_status ) ? 'selected' : '';?>
								<option value="<?php echo esc_attr( $status[ 'slug' ] ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_html( $status[ 'name' ] ); ?></option>
							<?php } ?>
						</select> <img id="status-change-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" /><?php
					}
				else if ( isset( $post->ID ) ) {
					$pstatus = $post->post_status;
					echo '<div id="rthd-status-visiable" >' . rthd_status_markup( $pstatus ) . '</div>';
				}
				?>
			</div>
			<?php
				if ( current_user_can( $cap ) ) {
					$rtcamp_users = Rt_HD_Utils::get_hd_rtcamp_user();
			?>
				<div class="rt-hd-ticket-info">
					<span title="<?php _e( 'Assigned To', RT_HD_TEXT_DOMAIN ); ?>">
						<strong>
							<?php _e( 'Assigned To', RT_HD_TEXT_DOMAIN ); ?>:
						</strong>
					</span>
					<select id="rthd-assignee-list" name="rt-hd-assignee">
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
					}?>
						<input type="hidden" class="rthd-current-user-id" value="<?php echo get_current_user_id(); ?>" />
						<a style="<?php echo $assign_tome_style; ?>" href="#" class="rt-hd-assign-me"><?php _e( 'Assign me' ); ?></a>
					<img id="assignee-change-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
				</div>
			<?php
				}
			?>
			<div class="rt-hd-ticket-info">
				<span title="Create Date"><strong>Created: </strong></span>
				<span title="<?php echo esc_attr( $createdate )?>">
			<?php
			echo esc_attr( human_time_diff( strtotime( $createdate ), current_time( 'timestamp' ) ) ) . ' ago';
			$created_by = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
			if ( ! empty( $created_by ) ) {
				if ( current_user_can( $cap ) ){
					echo ' by <a href="'.rthd_biz_user_profile_link($created_by->user_email).'">' . $created_by->display_name.'</a>';
				} else {
					echo ' by ' . $created_by->display_name;
				}
			}
			?>
				</span>
			</div>
			<?php
			$comment = get_comments( array( 'post_id' => $post->ID, 'number' => 1 ) );

			if ( ! empty( $comment ) ) {
				$comment = $comment[ 0 ];
				if ( current_user_can( $cap ) ){
					$commentlink = '<a href="'.rthd_biz_user_profile_link($comment->comment_author_email).'" >'.$comment->comment_author.'</a>';
				}
				else {
					$commentlink = $comment->comment_author;
				}
				?>

				<div class="rt-hd-ticket-info rt-hd-ticket-last-reply">
					<span title="Status"><strong>Last reply: </strong></span>
					<span class="rthd_attr_border rthd_view_mode"> <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ) . " ago by " . $commentlink; ?></span>
				</div>
			<?php } ?>
			<div class='rt-hd-ticket-info'>
				<h2 class="rt-hd-ticket-info-header"><?php echo __( 'Add people' ); ?></h2>
			</div>
			<div class="rt-hd-ticket-info rt-hd-related-ticket">
					<input type="email" placeholder="email" id="rthd-subscribe-email">
				<button type="button" class='rthd-subscribe-email-submit button btn'>Add</button>
				<span style="display: none;" class="rthd-subscribe-validation" ></span>
				<img id="rthd-subscribe-email-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />

			</div>

			<?php
			if ( isset( $post->ID ) ) {
				$attachments = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', ) );

				if ( ! empty( $attachments ) ) {
					$attach_cmt = rthd_get_attachment_url_from_followups( $post->ID );
					$attachFlag  = true;
					$tmphtml= '<div class="rt-hd-ticket-info"><h2 class="rt-hd-ticket-info-header">'. __( 'Attachments' ) .'</h2></div><div class="rt-hd-ticket-info"><ul id="attachment-files">';
							?>
							<?php foreach ( $attachments as $attachment ) {
								if ( in_array($attachment->ID ,$attach_cmt)){
									continue;
								}
								?>
								<?php $extn_array = explode( '.', $attachment->guid );
								$extn             = $extn_array[ count( $extn_array ) - 1 ];
								if ( $attachFlag ){
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
							<?php }
					if ( ! $attachFlag ){
						echo '</ul> </div>';
					}
					?>


				<?php }

				/* Display reference link if any */
				$ref_links = get_post_meta( $post->ID, '_rtbiz_hd_external_file' );
				if( ! empty( $ref_links ) ) {
				?>
					<div class="rt-hd-ticket-info">
						<h2 class="rt-hd-ticket-info-header"><?php _e( 'Reference Link' ); ?></h2>
					</div>
					<div class="rt-hd-ticket-info">
						<ul>
							<?php foreach ( $ref_links as $ref_link ) {
								$ref_link = (array) json_decode( $ref_link );
							?>
								<li><a target="_blank" href="<?php echo $ref_link['link']; ?>"><?php echo $ref_link['title']; ?></a></li>
							<?php } ?>
						</ul>
					</div>
				<?php
				}

				if ( current_user_can( $cap ) ) {

					// Products
					global $rtbiz_offerings;
					$products = array();
					if ( ! empty( $rtbiz_offerings ) ) {
						$products = get_terms( Rt_Offerings::$offering_slug );
						$ticket_offering = wp_get_post_terms( $post->ID, Rt_Offerings::$offering_slug );
					}
					if ( ! $products instanceof WP_Error && ! empty( $products ) ) { ?>
						<div class="rt-hd-ticket-info">
							<span>
								<strong>
									<?php _e( 'Ticket Offering:' ); ?>
								</strong>
							</span>
							<select id="rthd-offering-list" ndame="rt-hd-offering">
								<?php foreach ( $products as $p ) {
									if ( ! empty( $ticket_offering ) && $ticket_offering[0]->term_id == $p->term_id ){
										$selected = ' selected="selected" ';
									} else {
										$selected = ' ';
									}
									?>
									<?php
									echo '<option value="' . esc_attr( $p->term_id ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $p->name ) . '</option>';
									?>
								<?php }
								if ( empty( $ticket_offering ) ){
									echo '<option value="0" selected="selected" >-Select Offering-</option>';
								}
								?>
								</select>
							<img id="offering-change-spinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" />
						</div>
					<?php }
					// Attributes
					global $rt_hd_attributes_relationship_model;
					$relations = $rt_hd_attributes_relationship_model->get_relations_by_post_type( Rt_HD_Module::$post_type );
					foreach ( $relations as $r ) {
						$attr = $rt_hd_attributes_model->get_attribute( $r->attr_id );
						if ( 'taxonomy' == $attr->attribute_store_as ) {
							$taxonomy = $rt_hd_rt_attributes->get_taxonomy_name( $attr->attribute_name );
							$terms    = wp_get_post_terms( $post->ID, $taxonomy );

							if ( ! $terms instanceof WP_Error && ! empty( $terms ) ) {
								?>
								<div class="rt-hd-ticket-info">
									<h2 class="rt-hd-ticket-info-header"><?php echo $attr->attribute_label; ?></h2>
								</div>
								<div class="rt-hd-ticket-info">
									<ul>
										<?php foreach ( $terms as $t ) { ?>
											<li><?php echo $t->name; ?></li>
										<?php } ?>
									</ul>
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
			$otherposts = get_posts( array( 'post_type' => Rt_HD_Module::$post_type,'post_status' => 'any', 'post__not_in' => array( $post->ID ) ,'meta_query' => array(
				array(
					'key'     => '_rtbiz_hd_created_by',
					'value'   => $created_by->ID,
				),
			), ));
			if ( $otherposts ) {
			?>
			<div class="rt-hd-ticket-info">
				<h2 class="rt-hd-ticket-info-header"><?php echo __( 'Ticket History' ); ?></h2>
			</div>
			<div class="rt-hd-ticket-info rt-hd-ticket-history">
				<ul>
					<?php foreach ( $otherposts as $p ) { ?>
						<li><a href="<?php echo get_post_permalink( $p->ID ); ?>" ><?php echo '[#' . $p->ID. '] ' . esc_attr( strlen( balanceTags( $p->post_title ) ) > 15 ? substr( balanceTags( $p->post_title ), 0, 15 ) . '...' : balanceTags( $p->post_title ) ) ?>  </a><?php echo rthd_status_markup( $p->post_status ); ?></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>

			<?php
			$connected_tickets = new WP_Query( array(
				                            'connected_type' => Rt_HD_Module::$post_type.'_to_'.Rt_HD_Module::$post_type,
				                            'connected_items' => $post->ID,
				                            'nopaging' => true,
			                            ) );
			if ( $connected_tickets->have_posts() ){ ?>
			<div class="rt-hd-ticket-info">
				<h2 class="rt-hd-ticket-info-header"><?php echo __( 'Related Tickets' ); ?></h2>
			</div>
			<div class="rt-hd-ticket-info rt-hd-related-ticket">
				<ul>
					<?php foreach ( $connected_tickets->posts as $p ) { ?>
						<li><a href="<?php echo get_post_permalink( $p->ID ); ?>" ><?php echo '[#' . $p->ID. '] ' . esc_attr( strlen( balanceTags( $p->post_title ) ) > 15 ? substr( balanceTags( $p->post_title ), 0, 15 ) . '...' : balanceTags( $p->post_title ) ) ?>  </a><?php echo rthd_status_markup( $p->post_status ); ?></li>
					<?php } ?>
				</ul>
			</div>
				<?php } ?>
		</div>
	</div>
<a href="#" class="rthd-scroll-up"></a>
<?php
do_action( 'rthd_ticket_front_page_before_footer' );
get_footer();
