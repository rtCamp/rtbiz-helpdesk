<?php

/*
 * ticket front page Template
 *
 * @author udit
 */
get_header();

do_action( 'rthd_ticket_front_page_after_header' );

global $rthd_ticket, $rt_hd_module;
$post_type       = get_post_type( $rthd_ticket );
$labels          = $rt_hd_module->labels;
$post_id         = $rthd_ticket->ID;
$user_edit       = false;

?>
	<div class="rthd-container rthd-clearfix">
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
			<h2><?php echo esc_attr( ( isset( $post->ID ) ) ? '[#'.$post_id.'] '.$post->post_title : '' ); ?></h2>
		</div>
		<br/><br/>
		<?php if ( isset( $post->ID ) ) { ?>
			<div id="followup_wrapper">
				<div id="commentlist">
					<?php rthd_get_template( 'followup-common.php', array( 'post' => $post ) ); ?>
				</div>
				<div class="add-followup-form">
					<?php rthd_get_template('ticket-add-followup-form.php', array('post' => $post, 'ticket_unique_id' => $ticket_unique_id)); ?>
				</div>
			</div>
		<?php } ?>
	</div>
	<div id="rthd-sidebar" class="rthd_sticky_div">
		<h2><i class="foundicon-idea"></i> <?php _e( esc_attr( ucfirst( $labels['name'] ) ) . ' Information' ); ?></h2>
		<div>
			<span class="prefix" title="Status">Status</span>
			<?php
if ( isset( $post->ID ) ) {
	$pstatus = $post->post_status;
} else {
	$pstatus = '';
}
$style = 'padding: 5px; border: 1px solid black; border-radius: 5px;';
$flag = false;
$post_statuses = $rt_hd_module->get_custom_statuses();
foreach ( $post_statuses as $status ) {
	if ( $status['slug'] == $pstatus ) {
		$pstatus = $status['name'];
		if ( ! empty( $status['style'] ) ) {
			$style = $status['style'];
		}
		$flag = true;
		break;
	}
}
if ( ! $flag ) {
	$pstatus = ucfirst( $pstatus );
}
if( ! empty( $pstatus ) ) {
	printf( '<mark style="%s" class="%s tips" data-tip="%s">%s</mark>', $style, $pstatus, $pstatus, $pstatus );
}
?>
		</div>
		<div>
                <span title="Create Date"><strong>Created: </strong></span>
			<div class="prefix" title="<?php echo esc_attr( $createdate )?>">
			<?php
				echo esc_attr( human_time_diff( strtotime( $createdate ), current_time( 'timestamp' ) ) ) . ' ago';
				$created_by = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
				if ( ! empty( $created_by ) ) {
					echo ' by ' . $created_by->display_name;
				}
			?>
			</div>
		</div>
	<?php
		$comment = get_comments(array('post_id'=>$post->ID,'number' => 1));

		if ( ! empty( $comment ) ){
			$comment = $comment[0];
		?>

		<div>
			<span class="prefix" title="Status">Last reply:</span>
			<span class="rthd_attr_border prefix rthd_view_mode"> <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) )) ."ago by ". $comment->comment_author; ?></span>
		</div>
	<?php }

if ( isset( $post->ID ) ) {
	$attachments = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', ) );

	if ( ! empty( $attachments ) ) { ?>

		<h2><i class="foundicon-paper-clip"></i> <?php _e( 'Attachments' ); ?></h2>
		<div>
			<div id="attachment-files">
				<?php foreach ( $attachments as $attachment ) { ?>
					<?php $extn_array = explode( '.', $attachment->guid );
					$extn             = $extn_array[ count( $extn_array ) - 1 ]; ?>
					<div class="attachment-item"
					     data-attachment-id="<?php echo esc_attr( $attachment->ID ); ?>">
						<a class="rthd_attachment" title="<?php echo balanceTags( $attachment->post_title ); ?>" target="_blank"
						   href="<?php echo esc_url( wp_get_attachment_url( $attachment->ID ) ); ?>"> <img height="20px" width="20px"
						                                                                                   src="<?php echo esc_url( RT_HD_URL . 'app/assets/file-type/' . $extn . '.png' ); ?>"/>
							<span title="<?php echo balanceTags( $attachment->post_title ); ?>"> 	<?php echo esc_attr( strlen( balanceTags( $attachment->post_title ) ) > 12 ? substr( balanceTags( $attachment->post_title ), 0, 12 ). '...' : balanceTags( $attachment->post_title ) ); ?> </span>
						</a>
						<?php if ( $user_edit ) { ?>
							<a href="#" class="rthd_delete_attachment right">x</a>
						<?php } ?>
						<input type="hidden" name="attachment[]" value="<?php echo esc_attr( $attachment->ID ); ?>"/>
					</div>
				<?php } ?>
			</div>
		</div>

	<?php }
} ?>
	</div>
</div>
<?php
do_action( 'rthd_ticket_front_page_before_footer' );
get_footer();
