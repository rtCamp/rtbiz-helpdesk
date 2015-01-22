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

$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
$user_edit_content = current_user_can( $cap );

$show_original_email = false;
if ( ! empty( $_REQUEST['show_original'] ) && 'true' === $_REQUEST['show_original'] && empty( $_REQUEST['comment-id'] ) && $user_edit_content ){
	$data = get_post_meta( $post->ID, 'rt_hd_original_email_body', true );
	echo '<div class="rt_original_email">'.wpautop($data) .'</div>';
	$show_original_email = true;
}
if ( ! empty( $_REQUEST['show_original'] ) && 'true' === $_REQUEST['show_original'] && ! empty( $_REQUEST['comment-id'] ) && $user_edit_content ){
	$data = get_comment_meta( $_REQUEST['comment-id'], 'rt_hd_original_email', true );
	echo '<div class="rt_original_email">'.wpautop($data) .'</div>';
	$show_original_email = true;
}
if ( ! $show_original_email ) {
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
	?>
	<div id="rthd-ticket">
		<input type="hidden" id='ticket_unique_id' value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>

			<div>
				<h2 class="rt-hd-ticket-front-title"><?php echo esc_attr( ( isset( $post->ID ) ) ? '[#' . $post_id . '] ' . $post->post_title : '' ); ?></h2>
			</div>
			<br/><br/>
			<?php if ( isset( $post->ID ) ) { ?>
				<div id="followup_wrapper">
					<div id="commentlist">
						<?php rthd_get_template( 'followup-common.php', array( 'post' => $post ) ); ?>
					</div>
					<div class="add-followup-form">
						<?php rthd_get_template( 'ticket-add-followup-form.php', array( 'post'             => $post,
						                                                                'ticket_unique_id' => $ticket_unique_id
						) ); ?>
					</div>
				</div>
			<?php } ?>
		</div>
		<div id="rthd-sidebar" class="rthd_sticky_div">
			<h2><i class="foundicon-idea"></i> <?php _e( esc_attr( ucfirst( $labels[ 'name' ] ) ) . ' Information' ); ?>
			</h2>

			<div class="rt-hd-ticket-info">
				<span class="prefix" title="Status"><strong>Status: </strong></span>
				<?php
				if ( isset( $post->ID ) ) {
					$pstatus = $post->post_status;
					echo rthd_status_markup( $pstatus );
				}
				?>
			</div>
			<div class="rt-hd-ticket-info">
				<span title="Create Date"><strong>Created: </strong></span>
			<span class="prefix" title="<?php echo esc_attr( $createdate )?>">
			<?php
			echo esc_attr( human_time_diff( strtotime( $createdate ), current_time( 'timestamp' ) ) ) . ' ago';
			$created_by = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
			if ( ! empty( $created_by ) ) {
				echo ' by ' . $created_by->display_name;
			}
			?>
			</span>
			</div>
			<?php
			$comment = get_comments( array( 'post_id' => $post->ID, 'number' => 1 ) );

			if ( ! empty( $comment ) ) {
				$comment = $comment[ 0 ];
				?>

				<div class="rt-hd-ticket-info">
					<span title="Status"><strong>Last reply: </strong></span> <span
						class="rthd_attr_border prefix rthd_view_mode"> <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ) . " ago by " . $comment->comment_author; ?></span>
				</div>
			<?php }

			if ( isset( $post->ID ) ) {
				$attachments = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', ) );

				if ( ! empty( $attachments ) ) { ?>

					<h2><i class="foundicon-paper-clip"></i> <?php _e( 'Attachments' ); ?></h2>
					<div class="rt-hd-ticket-info">
						<div id="attachment-files">
							<?php foreach ( $attachments as $attachment ) { ?>
								<?php $extn_array = explode( '.', $attachment->guid );
								$extn             = $extn_array[ count( $extn_array ) - 1 ]; ?>
								<div class="attachment-item"
								     data-attachment-id="<?php echo esc_attr( $attachment->ID ); ?>">
									<a class="rthd_attachment"
									   title="<?php echo balanceTags( $attachment->post_title ); ?>" target="_blank"
									   href="<?php echo esc_url( wp_get_attachment_url( $attachment->ID ) ); ?>"> <img
											height="20px" width="20px"
											src="<?php echo esc_url( RT_HD_URL . 'app/assets/file-type/' . $extn . '.png' ); ?>"/>
										<span
											title="<?php echo balanceTags( $attachment->post_title ); ?>"> 	<?php echo esc_attr( strlen( balanceTags( $attachment->post_title ) ) > 40 ? substr( balanceTags( $attachment->post_title ), 0, 40 ) . '...' : balanceTags( $attachment->post_title ) ); ?> </span>
									</a>
									<?php if ( $user_edit ) { ?>
										<a href="#" class="rthd_delete_attachment right">x</a>
									<?php } ?>
									<input type="hidden" name="attachment[]"
									       value="<?php echo esc_attr( $attachment->ID ); ?>"/>
								</div>
							<?php } ?>
						</div>
					</div>

				<?php }

				$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
				if ( current_user_can( $cap ) ) {

					// Products
					global $rtbiz_offerings;
					$products = array();
					if ( ! empty( $rtbiz_offerings ) ) {
						$products = wp_get_post_terms( $post->ID, Rt_Offerings::$offering_slug );
					}
					$base_url = add_query_arg( array( 'post_type' => $post->post_type ), admin_url( 'edit.php' ) );
					if ( ! $products instanceof WP_Error && ! empty( $products ) ) { ?>
						<div class="rt-hd-ticket-info">
							<h2><?php _e( 'Ticket Products' ); ?></h2>
							<ul>
								<?php foreach ( $products as $p ) {
									$url = add_query_arg( 'product_id', $p->term_id, $base_url );
									?>
									<li><a href="<?php echo $url; ?>"><?php echo $p->name; ?></a></li>
								<?php } ?>
							</ul>
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
									<h2><?php echo $attr->attribute_label; ?></h2>
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
			<div class="rt-hd-ticket-history">
				<h2><?php echo __( 'Ticket History' ); ?></h2>
				<ul>
					<?php foreach ( $otherposts as $p ) { ?>
						<li><a href="<?php echo get_post_permalink( $p->ID ); ?>" ><?php echo '[#' . $p->ID. '] ' . wp_trim_words( $p->post_title, $num_words = 3 , $more = '...' ); ?>  </a><?php echo rthd_status_markup( $p->post_status ); ?></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>
	</div>
<?php
}
do_action( 'rthd_ticket_front_page_before_footer' );
get_footer();