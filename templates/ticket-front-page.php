<?php

/*
 * ticket front page Template
 *
 * @author udit
 */
global $rt_hd_module, $rt_hd_contacts, $rt_hd_accounts, $rt_hd_settings, $rt_hd_import_operation;

add_action( 'wp_print_scripts', 'include_jquery_form_plugin' );
function include_jquery_form_plugin() {

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-form', array( 'jquery' ), false, true );

}

get_header();

do_action( 'rthd_ticket_front_page_after_header' );

global $rthd_ticket;
$post_type       = get_post_type( $rthd_ticket );
$module_settings = rthd_get_settings();
$labels          = $rt_hd_module->labels;
$post_id         = $rthd_ticket->ID;
$user_edit       = false;

?>
	<div class="rthd-container">
	<?php
	global $wpdb;
	echo "<script> var arr_ticketmeta_key=''; </script>";


	$post             = get_post( $post_id );
	$ticket_unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );

	$create = new DateTime( $post->post_date );

	$modify     = new DateTime( $post->post_modified );
	$createdate = $create->format( 'M d, Y h:i A' );
	$modifydate = $modify->format( 'M d, Y h:i A' );
	?>
	<div id="add-new-post">
	<input type="hidden" id='ticket_unique_id' value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>

	<div>
		<?php if ( $user_edit ) { ?>
			<input name="post[post_title]" id="new_<?php echo esc_attr( $post_type ) ?>_title" type="text"
			       placeholder="<?php _e( esc_attr( ucfirst( $labels['name'] ) ) . ' Subject' ); ?>"
			       value="<?php echo esc_attr( ( isset( $post->ID ) ) ? $post->post_title : '' ); ?>"/>
		<?php } else { ?>
			<h4><?php echo esc_attr( ( isset( $post->ID ) ) ? $post->post_title : '' ); ?></h4>
		<?php } ?>
	</div>
	<div>
<?php
if ( $user_edit ) {
	wp_editor( ( isset( $post->ID ) ) ? $post->post_content : '', 'post[post_content]' );
} else {
	echo '<span>' . balanceTags( ( isset( $post->ID ) ) ? $post->post_content : '' ) . '</span>';
}
?>

	</div>
		<br/><br/>
		<?php if ( isset( $post->ID ) ) { ?>
			<div id="followup_wrapper">
				<h2>Followup</h2>
				<div id="commentlist">
					<?php rthd_get_template( 'admin/followup-common.php', array( 'post' => $post ) ); ?>
				</div>
			</div>
		<?php } ?>
		</div>
		<div class="rthd_sticky_div">
			<h2><i class="foundicon-idea"></i> <?php _e( esc_attr( ucfirst( $labels['name'] ) ) . ' Information' ); ?></h2>
			<div>
				<span class="prefix" title="Status">Status</span>
				<?php
if ( isset( $post->ID ) ) {
	$pstatus = $post->post_status;
} else {
	$pstatus = '';
}
$post_status = $rt_hd_module->get_custom_statuses();
				?>
				<?php if ( $user_edit ) { ?>
					<select class="right" name="post[post_status]">
		<?php foreach ( $post_status as $status ) {
		if ( $status['slug'] == $pstatus ) {
			$selected = 'selected="selected"';
		} else {
			$selected = '';
		}

	echo balanceTags( printf( '<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name'] ) );
} ?>
					</select>
<?php
} else {
	foreach ( $post_status as $status ) {
		if ( $status['slug'] == $pstatus ) {
			echo '<div class="rthd_attr_border prefix rthd_view_mode">' . esc_html( $status['name']  ). '</div>';
			break;
		}
	}
} ?>
			</div>
			<div>
				<span class="prefix" title="Create Date"><label>Create Date</label></span>
				<?php if ( $user_edit ) { ?>
					<input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
					       value="<?php echo esc_attr( ( isset( $createdate ) ) ? $createdate : '' ); ?>"
					       title="<?php echo esc_attr( ( isset( $createdate ) ) ? $createdate : '' ); ?>">
					<input name="post[post_date]" type="hidden"
					       value="<?php echo esc_attr( ( isset( $createdate ) ) ? $createdate : '' ); ?>"/>
				<?php } else { ?>
					<div class="rthd_attr_border prefix rthd_view_mode moment-from-now"
					     title="<?php echo esc_attr( $createdate )?>"><?php echo esc_attr( $createdate ) ?></div>
				<?php } ?>
			</div>
<?php
$attachments = array();
if ( isset( $post->ID ) ) {
	$attachments = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', ) );
} ?>
			<h2><i class="foundicon-paper-clip"></i> <?php _e( 'Attachments' ); ?></h2>
		<?php if ( $user_edit ) { ?>
			<a href="#" class="button" id="add_ticket_attachment"><?php _e( 'Add' ); ?></a>
		<?php } ?>
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
	</div>
	</div>
	</div>
<?php
do_action( 'rthd_ticket_front_page_before_footer' );
get_footer();