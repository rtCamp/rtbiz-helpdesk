<?PHP
function right_comment( $comment ) {
	?>
		<li class="self" id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>">

			<div class="avatar">
				<?php echo get_avatar( $comment, 40 ); ?>
			</div>
			<div class="messages" title="Click for action">
				<input id="followup-id" type="hidden" value="<?php echo esc_attr( $comment->comment_ID ); ?>">
				<p><?php echo esc_attr( $comment->comment_content ); ?>
				</p>

				<time title="<?php echo esc_attr( $comment->comment_date ); ?>"
				      datetime="<?php echo esc_attr( $comment->comment_date ); ?>"><span
						title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo esc_attr( ( $comment->comment_author == '' ) ? 'Anonymous' : $comment->comment_author ); ?> </span>
					• <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ) ); ?>
				</time>

			</div>
		</li>
	<?php

}

/**
 * @param $comment
 */
function left_comment( $comment, $user_edit ) {
	$is_comment_private = get_comment_meta( $comment->comment_ID, '_rthd_privacy' );

	//	$display_private_comment_flag = ( $is_comment_private )? ( ( $user_edit )? true:false ) : false ;
	if ( isset( $is_comment_private ) || $is_comment_private ){
		if ( $user_edit ){
			$display_private_comment_flag = true;
		}
		else {
			$display_private_comment_flag = false;
		}
	}
	else {
		$display_private_comment_flag = true;
	}
	//	error_log( $display_private_comment_flag . ": -> system", 3, "/var/tmp/my-errors.log");
	if ( $display_private_comment_flag ) {
		?>
		<li class="other">
			<div class="avatar">
				<?php echo get_avatar( $comment, 40 ); ?>
			</div>
			<div class="messages">
				<p><?php echo esc_attr( $comment->comment_content ); ?></p>

				<time title="<?php echo esc_attr( $comment->comment_date ); ?>"
				      datetime="<?php echo esc_attr( $comment->comment_date ); ?>"><span
						title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo esc_attr( ( $comment->comment_author == '' ) ? 'Anonymous' : $comment->comment_author ); ?> </span>
					• <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ) ); ?>
				</time>
			</div>
		</li>
	<?php
	}
	else {
		?>
		<li class="other">
			<div class="avatar">
				<?php echo get_avatar( $comment, 40 ); ?>
			</div>
			<div class="messages">
				<p><?php echo 'This comment is private' ?></p>

				<time title="<?php echo esc_attr( $comment->comment_date ); ?>"
				      datetime="<?php echo esc_attr( $comment->comment_date ); ?>"><span
						title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo esc_attr( ( $comment->comment_author == '' ) ? 'Anonymous' : $comment->comment_author ); ?> </span>
					• <?php echo esc_attr( human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ) ); ?>
				</time>
			</div>
		</li>


		<?php
	}
}
$comments = get_comments(
	array(
		'post_id' => $post->ID,
		'status'  => 'approve', //Change this to the type of comments to be displayed
	) );
//echo( json_encode($comments) );
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
	<textarea id="followup_content" name="followup_content" placeholder="Add new followup" style="width: 100%"></textarea>
<div id ='private-comment'>	<input id="add-private-comment" type="checkbox" name="private" value="yes" text="check to make comment private">Private<br></div>
	<button class="mybutton add-savefollowup button" id="savefollwoup" type="button">Add followup</button>

	<!--<button class="mybutton right" type="submit" >Add</button>-->
	<!--<input type="file" class="right" name="ticket_attach_file" id="ticket_attach_file" multiple />-->
</form>

<form id="thumbnail_upload" method="post" enctype="multipart/form-data">
	<input type="file" name="thumbnail" id="thumbnail"> <input type='hidden'
	                                                           value='<?php wp_create_nonce( 'upload_thumb' ); ?>'
	                                                           name='_nonce'/> <input type="hidden"
	                                                                                  name="post_id"
	                                                                                  id="post_id"
	                                                                                  value="<?php echo esc_attr( $post->ID ); ?>">
	<input type="hidden" name="action" id="action" value="my_upload_action"> <br/> <input
		id="submit-ajax" name="submit-ajax" type="button" class="button" value="upload" style="margin-top: 5px">
</form>
<div id="output1"></div>
<?php
$user_edit = false;
if ( current_user_can( "edit_{$post->post_type}" ) ) {
	$user_edit = true;
}

if ( $user_edit ){
	?>
	<div id="dialog-form" title="Edit Followup" style="display: none">
		<textarea id="edited_followup_content" name="edited_followup_content" placeholder="Add new followup" style="width:100%"> </textarea>
		<div id ='edit-private-comment'>	<input id="edit-private" type="checkbox" name="private" value="yes" text="check to make comment private">Private<br></div>
		<button class="edit-followup button button-primary" id="editfollowup" type="button"> Update </button>
		<button class="edit-followup button" id="delfollowup" type="button"> Delete </button>
	</div>
	<?php
}
?>
<ol class="discussion" id="chat-UI">
	<?php foreach ( $comments as $comment ) {
	if ( ( $comment->user_id ) == ( get_current_user_id() ) ) {
		right_comment( $comment );
	} else {
		left_comment( $comment, $user_edit );
	}
	} ?>
</ol>
<?php
wp_enqueue_script( 'jquery-ui-dialog' );
?>