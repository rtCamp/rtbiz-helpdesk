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

//$user_edit = current_user_can( rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' ) );
//if ( $user_edit ) {
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
//}
if ( ! empty( $post->post_content ) ) {

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
				<?php
					$content = balanceTags( ( isset($post->ID) ) ? $post->post_content : '', true );
					echo apply_filters('the_content', $content);
				?>
				</div>
				<time title="<?php echo esc_attr( $post->post_date); ?>" datetime="<?php echo esc_attr( $post->post_date); ?>">
					<span title="<?php echo esc_attr( $authoremail ); ?>"><?php echo esc_attr( ( $authorname== '' ) ? 'Anonymous' : $authorname ); ?> </span>
					| <?php echo esc_attr( human_time_diff( strtotime( $post->post_date), current_time( 'timestamp' ) ) ) . ' ago';
					?>
				</time>
			</div>
		</li>
	</ul>
<?php }?>
<ul class="discussion js-stream" id="chat-UI">

	<?php
	$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
	foreach ( $comments as $comment ) {
		$user_edit = current_user_can( $cap ) || (get_current_user_id() == $comment->user_id );
		$comment_user  = get_user_by( 'id', $comment->user_id );
		$comment_render_type = 'left';
		if ( ! empty( $comment_user ) ) {
			if ( $comment_user->has_cap( rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ) ) ) {
				$comment_render_type = 'right';
			}
		}
		rthd_render_comment( $comment, $user_edit, $comment_render_type );
	} ?>
</ul>
<input id="followup-offset" type="hidden" value="<?php echo esc_attr( $offset ); ?>" />
<input id="followup-limit" type="hidden" value="<?php echo esc_attr( $Limit ); ?>" />
<input id="followup-totalcomment" type="hidden" value="<?php echo esc_attr( $totalComment); ?>" />
<?php if ($Limit < $totalComment){ ?>
<div class="content-stream stream-loading js-loading-placeholder">
	<a href="#" id="followup-load-more" > Load more</a>
</div>
<?php } ?>
