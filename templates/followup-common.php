<?php
$offset = 0;
$Limit =3;
$totalComment= get_comments_number($post->ID);
if($totalComment >= $Limit){
	$offset=$totalComment-3;
}

$comments = get_comments( array(
	'post_id' => $post->ID,
	'status'  => 'approve',
	'order'   => 'ASC',
	'number' => $Limit,
	'offset' => $offset,
) );

$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
$created_by = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );

$user_edit_content = current_user_can( $cap ) || ( get_current_user_id() == $post->$created_by );


if ( ! empty( $post->post_content ) ) {

	$created_by = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
	$authorname = 'Annonymous';
	$authoremail = '';
	if ( ! empty( $created_by ) ) {
		$authorname = $created_by->display_name;
		$authoremail = $created_by->user_email;
	}
	?>
	<ul class="discussion" id="ticket-content-UI">
		<li class="other ticketother">
			<div class="avatar">
				<?php
				echo get_avatar( $authoremail, 48 ); ?>
			</div>
			<div class="messages ticketcontent">
                <div class="followup-information">
	                <?php
	                if ( current_user_can( $cap ) ){
						$autherLink = '<a class="rthd-ticket-author-link" href="'.rthd_biz_user_profile_link( $authoremail ).'">'.$authorname.'</a>';
	                }
	                else{
		                $autherLink = $authorname;
	                }
	                ?>
                    <span title="<?php echo esc_attr( $authoremail ); ?>"><?php echo ( $autherLink ); ?> </span>
                    <time title="<?php echo esc_attr( $post->post_date); ?>" datetime="<?php echo esc_attr( $post->post_date); ?>">
	                   <?php if ( $user_edit_content ) {
		                   ?>
		                   <a href="#" class="edit-ticket-link">Edit</a> |
		                   <?php
		                   $data = get_post_meta( $post->ID, 'rt_hd_original_email_body', true );
		                   if ( ! empty( $data ) ) {
			                   ?>
			                   <a href="?show_original=true" class="show-original-email"> Show original email</a> |
		                   <?php }
	                   }?>
	                    <?php echo '<a class="followup-hash-url" id="ticket_description'.'" href="#ticket_description" >'. esc_attr( human_time_diff( strtotime( $post->post_date), current_time( 'timestamp' ) ) ) . ' ago</a>';?>
                    </time>
            </div>
				<div class="rthd-comment-content">
				<?php
					$content = rthd_content_filter( isset( $post->ID ) ? $post->post_content : '' );
					echo $content;
				?>
				</div>
			</div>
		</li>
	</ul>
<?php }?>

<?php if ($Limit < $totalComment){
	?>
	<ul class="discussion load-more-ul" id="load-more-UI">
		<li><a class="load-more-block" href="#">
			<p>
				<label class="load-more-circle" id="followup-load-more-count"><?php echo $offset ?></label>
				<label class="load-more-count">more</label>
			</p>
            </a>
			<a href="#" class="load-more" id="followup-load-more" > Load more</a>
		</li>
		<li class="load-more-spinner-li">
			<div class="rthdcenter"><img id="load-more-hdspinner" class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>" /></div>
		</li>
	</ul>
<?php } ?>

<ul class="discussion js-stream" id="chat-UI">

	<?php
	foreach ( $comments as $comment ) {
		$user_edit = current_user_can( $cap ) || ( get_current_user_id() == $comment->user_id );
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
<div id="dialog-form" title="Edit Followup" style='display: none'>
<!--	<textarea id="editedfollowupcontent" name="editedfollowupcontent" placeholder="edit followup" rows="5"></textarea>-->
	<?php
	$editor_id = 'editedfollowupcontent';
	$settings = array( 'media_buttons' => false, 'tinymce' => array(
		'height' => 200,
	));
	wp_editor( '', $editor_id, $settings );
	?>
	<div id="edit-private-comment">
		<span class="rthd-visibility"> Visibility: </span>
		<select name="private" id="edit-private" >
			<option value="<?php echo Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ?>"> <?php echo rthd_get_comment_type(Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ) ?> </option>
			<option value="<?php echo Rt_HD_Import_Operation::$FOLLOWUP_SENSITIVE ?>"> <?php echo rthd_get_comment_type(Rt_HD_Import_Operation::$FOLLOWUP_SENSITIVE ) ?> </option>
			<?php
			if ( current_user_can( $cap ) ){ ?>
				<option value="<?php echo Rt_HD_Import_Operation::$FOLLOWUP_STAFF ?>"> <?php echo rthd_get_comment_type(Rt_HD_Import_Operation::$FOLLOWUP_STAFF ) ?> </option>
			<?php }
			?>
		</select>
		<img id='edithdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
	</div>
	<a href="#" class="close-edit-followup">Close</a>
	<div class="edit-action-button">
		<button class="edit-followup button red-color" id="delfollowup" type="button">Delete</button>
		<button class="edit-followup button button-primary" id="editfollowup" type="button">Update</button>
	</div>
</div>
<?php
	if ( $user_edit_content ){
	?>
	<div id="edit-ticket-data" title="Edit Ticket" style="display: none;">
		<?php
		$editor_id = 'editedticketcontent';
		$settings = array( 'media_buttons' => false, 'tinymce' => array(
			'height' => 200,
		));
		wp_editor( '', $editor_id, $settings );
		?>
		<!--	   <textarea id="editedticketcontent" name="editedticketcontent" placeholder="Edit ticket" rows="5"></textarea>-->
		<button class="edit-ticket button button-primary" id="edit-ticket-content-click" type="button">Update</button>
		<?php wp_nonce_field('rt_hd_ticket_edit','edit_ticket_nonce'); ?>
		<img id='ticket-edithdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
		<a href="#" class="close-edit-content">Close</a>
	</div>
<?php } ?>