<?php

function rthd_render_comment( $comment, $user_edit, $type = 'right' ) {

	$is_comment_private = get_comment_meta( $comment->comment_ID, '_rthd_privacy' );
	if ( ( isset( $is_comment_private ) && is_array( $is_comment_private ) ) && 'true' == $is_comment_private[0] ) {
		if ( $user_edit ) {
			$display_private_comment_flag = true;
		} else {
			$display_private_comment_flag = false;
		}
	} else {
		$display_private_comment_flag = true;
	}

	$side_class = ( $type == 'right' ) ? 'self' : ( ( $type == 'left' ) ? 'other' : '' );
	$editable_class = ( $display_private_comment_flag ) ? 'editable' : '';

	?>
	<li class="<?php echo $side_class . ' ' . $editable_class; ?>" id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>">

		<div class="avatar">
			<?php echo get_avatar( $comment, 40 ); ?>
		</div>
		<div class="messages <?php echo ( $display_private_comment_flag ) ? '' : 'private-comment-display'; ?>" title="Click for action">
			<input id="followup-id" type="hidden" value="<?php echo esc_attr( $comment->comment_ID ); ?>">
			<input id="is-private-comment" type="hidden" value="<?php echo esc_attr( $is_comment_private[0] ); ?>">

			<?php if( $display_private_comment_flag ) { ?>
				<p><?php echo esc_attr( $comment->comment_content ); ?></p>
			<?php } else { ?>
				<p><?php _e( 'This followup has been marked private.', RT_HD_TEXT_DOMAIN ); ?></p>
			<?php } ?>

			<time title="<?php echo esc_attr( $comment->comment_date ); ?>" datetime="<?php echo esc_attr( $comment->comment_date ); ?>">
				<span title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo esc_attr( ( $comment->comment_author == '' ) ? 'Anonymous' : $comment->comment_author ); ?> </span>
				&middot; <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ) ); ?>
			</time>

		</div>
	</li>
	<?php
}

$comments = get_comments( array(
	'post_id' => $post->ID,
	'status'  => 'approve',
) );

$ticket_unique_id = get_post_meta( $post->ID, '_rtbiz_hd_unique_id', true );
$current_user = wp_get_current_user();
?>
<form id="add_followup_form" method="post">
	<input type="hidden" id='ticket_unique_id' value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>
	<input id="user-avatar" type="hidden" value="<?php echo get_avatar( get_current_user_id(),40 ); ?>">
	<input id="user-email" type="hidden" value="<?php echo sanitize_email( $current_user->user_email ); ?>">
	<input id="user-name" type="hidden" value="<?php echo esc_attr( $current_user->display_name ); ?>">
	<input id="post-id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>">
	<input id="edit-comment-id" type="hidden">
	<textarea id="followup_content" name="followup_content" placeholder="Add new followup"></textarea>
	<div id ='private-comment'>
		<label for="add-private-comment"><input id="add-private-comment" type="checkbox" name="private" value="yes" text="check to make comment private"><?php _e('Private'); ?></label>
	</div>
	<button class="add-savefollowup btn-primary btn" id="savefollwoup" type="button">Add followup</button>
	<img id='hdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
</form>

<form id="thumbnail_upload" method="post" enctype="multipart/form-data">
	<input type="file" name="thumbnail" id="thumbnail" />
	<input type='hidden' value='<?php wp_create_nonce( 'upload_thumb' ); ?>' name='_nonce' />
	<input type="hidden" name="post_id" id="post_id" value="<?php echo esc_attr( $post->ID ); ?>" />
	<input type="hidden" name="action" id="action" value="my_upload_action" />
</form>
<?php

$user_edit = current_user_can( rt_biz_get_access_role_cap( RT_BIZ_TEXT_DOMAIN, 'editor' ) );
if ( $user_edit ) {
	?>
	<div id="dialog-form" title="Edit Followup" style='display: none'>
		<textarea id="edited_followup_content" name="edited_followup_content" placeholder="Add new followup"></textarea>
		<div id="edit-private-comment">
			<label for="edit-private"><input id="edit-private" type="checkbox" name="private" value="yes" text="check to make comment private"><?php _e('Private'); ?></label>
			<img id='edithdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
		</div>
		<button class="edit-followup button button-primary" id="editfollowup" type="button">Update</button>
		<button class="edit-followup button" id="delfollowup" type="button">Delete</button>
	</div>
	<?php
}
?>

<ul class="discussion" id="chat-UI">
	<?php foreach ( $comments as $comment ) {
	if ( ( $comment->user_id ) == ( get_current_user_id() ) ) {
		rthd_render_comment( $comment, $user_edit, 'right' );
	} else {
		rthd_render_comment( $comment, $user_edit, 'left' );
	}
	} ?>
</ul>
<?php
wp_enqueue_script( 'jquery-ui-dialog' );
?>