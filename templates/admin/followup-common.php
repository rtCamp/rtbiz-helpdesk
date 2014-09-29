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
function left_comment( $comment ) {
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
wp_enqueue_style( 'rthd-followup-css', RT_HD_URL . 'app/assets/css/follow-up.css', false, RT_HD_VERSION, 'all' );
global $wp_scripts;
$ui = $wp_scripts->query( 'jquery-ui-core' );
// tell WordPress to load the Smoothness theme from Google CDN
$protocol = is_ssl() ? 'https' : 'http';
$url      = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/" . $ui->ver . '/themes/smoothness/jquery-ui.css';
if ( ! wp_style_is( 'jquery-ui-smoothness' ) ) {
	wp_enqueue_style( 'jquery-ui-smoothness', $url, false, null );
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
	$user_edit = 'true';
}

if ( $user_edit ){
	?>
	<div id="dialog-form" title="Edit Followup" style="display: none">
		<textarea id="edited_followup_content" name="edited_followup_content" placeholder="Add new followup" style="width:100%"> </textarea>
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
		left_comment( $comment );
	}
	} ?>
</ol>
<?php
wp_enqueue_script( 'jquery-ui-dialog' );
?>



