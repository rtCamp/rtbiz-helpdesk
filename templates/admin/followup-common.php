<?php
$offset = 0;
$Limit =10;
$totalComment= get_comments_number($post->ID);

$comments = get_comments( array(
	'post_id' => $post->ID,
	'status'  => 'approve',
    'order'   => 'ASC',
    'number' => $Limit,
    'offset' => 0,
) );

$ticket_unique_id = get_post_meta( $post->ID, '_rtbiz_hd_unique_id', true );
$current_user = wp_get_current_user();
$user_edit = current_user_can( rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' ) );
if ( $user_edit ) {
	?>
	<div id="dialog-form" title="Edit Followup" style='display: none'>
		<textarea id="edited_followup_content" name="edited_followup_content" placeholder="Add new followup" rows="5"></textarea>
		<div id="edit-private-comment" class="red-color">
			<label for="edit-private"><input id="edit-private" type="checkbox" name="private" value="yes" text="check to make comment private"><?php _e('Private'); ?></label>
			<img id='edithdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
		</div>
		<div class="edit-action-button">
			<button class="edit-followup button red-color" id="delfollowup" type="button">Delete</button>
			<button class="edit-followup button button-primary" id="editfollowup" type="button">Update</button>
		</div>
	</div>
<?php
}
if ($post->post_content !=''){

$author_id=$post->post_author;
$authorinfo = get_userdata( $author_id );

$authoremail = $authorinfo->user_email;
$authorname =  $authorinfo->display_name;
?>
<ul class="discussion">
	<li class="left">
		<div class="avatar">
			<?php
			echo get_avatar( $authoremail, 40 ); ?>
		</div>
		<div class="messages">
			<div class="rthd-comment-content">
				<p><?php echo wpautop( make_clickable( $post->post_content ) ); ?></p>
			</div>
			<time title="<?php echo esc_attr( $post->post_date); ?>" datetime="<?php echo esc_attr( $post->post_date); ?>">
				<span title="<?php echo esc_attr( $authoremail ); ?>"><?php echo esc_attr( ( $authorname== '' ) ? 'Anonymous' : $authorname ); ?> </span>
				&middot; <?php echo esc_attr( human_time_diff( strtotime( $post->post_date), current_time( 'timestamp' ) ) );
				?>
			</time>
		</div>
	</li>
</ul>
<?php }?>
<ul class="discussion inner js-inner stream js-stream latest-posts" id="chat-UI">
	<?php foreach ( $comments as $comment ) {
	if ( ( $comment->user_id ) == ( get_current_user_id() ) ) {
		rthd_render_comment( $comment, $user_edit, 'right' );
	} else {
		rthd_render_comment( $comment, $user_edit, 'left' );
	}
	} ?>
</ul>
<?php if ($Limit < $totalComment){ ?>
<div class='content-stream stream-loading'>
	<a href="#" id="followup-load-more" > Load more</a>
<img id='load-more-hdspinner' class="helpdeskspinner js-loading-placeholder" src="<?php echo admin_url().'images/spinner.gif'; ?>">
</div>
<?php } ?>
<form id="add_followup_form" method="post">
	<input type="hidden" id='ticket_unique_id' value="<?php echo esc_attr( $ticket_unique_id ); ?>" />
	<input id="user-avatar" type="hidden" value="<?php echo get_avatar( get_current_user_id(), 80); ?>" />
	<input id="user-email" type="hidden" value="<?php echo sanitize_email( $current_user->user_email ); ?>" />
	<input id="user-name" type="hidden" value="<?php echo esc_attr( $current_user->display_name ); ?>" />
	<input id="post-id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>" />
	<input id="followup-offset" type="hidden" value="<?php echo esc_attr( $offset ); ?>" />
	<input id="followup-limit" type="hidden" value="<?php echo esc_attr( $Limit ); ?>" />
	<input id="followup-totalcomment" type="hidden" value="<?php echo esc_attr( $totalComment); ?>" />
	<input id="edit-comment-id" type="hidden" />
	<textarea id="followup_content" class="followup-content" name="followup_content" placeholder="Add new followup"></textarea>
	<div id="private-comment">
		<label for="add-private-comment"><input id="add-private-comment" type="checkbox" name="private" value="yes" text="check to make comment private" /><?php _e('Private'); ?></label>
	</div>
	<button class="add-savefollowup btn-primary btn" id="savefollwoup" type="button">Add followup</button>
	<img id='hdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
</form>

<form id="thumbnail_upload" method="post" enctype="multipart/form-data">
	<input type="file" name="thumbnail" id="thumbnail" />
	<input type='hidden' value='<?php wp_create_nonce('upload_thumb'); ?>' name='_nonce' />
	<input type="hidden" name="post_id" id="post_id" value="<?php echo esc_attr( $post->ID ); ?>" />
	<input type="hidden" name="action" id="action" value="my_upload_action" />
</form>
