<?php
$offset       = 0;
$Limit        = 3;
$totalComment = get_comments_number( $post->ID );
if ( $totalComment >= $Limit ) {
	$offset = $totalComment - 3;
}

$comments = get_comments( array(
	'post_id' => $post->ID,
	'status'  => 'approve',
	'order'   => 'ASC',
	'number'  => $Limit,
	'offset'  => $offset,
) );

$cap        = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
$staffonly = current_user_can( $cap );
$created_by = get_post_meta( $post->ID, '_rtbiz_hd_created_by', true );

$user_edit_content = $staffonly|| ( get_current_user_id() == $post->$created_by );



	$created_by  = get_user_by( 'id', get_post_meta( $post->ID, '_rtbiz_hd_created_by', true ) );
	$authorname  = 'Anonymous';
	$authoremail = '';
	if ( ! empty( $created_by ) ) {
		$authorname  = $created_by->display_name;
		$authoremail = $created_by->user_email;
	}
	?>
	<ul class="rthd-discussion" id="ticket-content-UI">
		<li class="rthd-other <?php echo count( $comments ) > 0 ? '' : 'rthd-no-comments'; ?> ticketother">
			<div class="avatar">
				<?php echo get_avatar( $authoremail, 48 ); ?>
			</div>
			<div class="rthd-messages ticketcontent">
				<div class="followup-information clearfix">
					<?php
					if ( $staffonly ) {
						$autherLink = '<a class="rthd-ticket-author-link" href="' . rtbiz_hd_biz_user_profile_link( $authoremail ) . '">' . $authorname . '</a>';
					} else {
						$autherLink = $authorname;
					}
					?>
					<span title="<?php echo esc_attr( $authoremail ); ?>"><?php echo( $autherLink ); ?> </span>
					<time
						title="<?php echo esc_attr( mysql2date( get_option( 'date_format' ), $post->post_date ) . ' at ' . mysql2date( get_option( 'time_format' ), $post->post_date, true ) ); ?>"
						datetime="<?php echo esc_attr( $post->post_date ); ?>">
						<?php if ( $user_edit_content ) {
							?>
							<a href="#" class="edit-ticket-link">Edit</a> |
							<?php
							$data = get_post_meta( $post->ID, '_rtbiz_hd_original_email_body', true );
							if ( ! empty( $data ) ) {
								$href = get_post_permalink( $post->ID ) . '?show_original=true';
								?>
								<a href="<?php echo $href; ?>" class="show-original-email" target="_blank"> Show
									original email</a> |
							<?php } ?>
						<?php } ?>
						<?php echo '<a class="followup-hash-url" id="ticket_description' . '" href="#ticket_description" >' . __( 'Created ' ) . esc_attr( human_time_diff( strtotime( $post->post_date ), current_time( 'timestamp' ) ) ) . ' ago</a>'; ?>
					</time>
				</div>
				<?php
				$markdown_content = get_post_meta( $post->ID, '_rtbiz_hd_markdown_data', true );
				if ( ! isset( $markdown_content ) || empty( $markdown_content ) ) {
					$markdown_content = $post->post_content;
				}
				?>
				<div class="rthd-comment-content"
				     data-content="<?php echo( isset( $post->ID ) ? esc_attr( $markdown_content ) : '' ); ?>">
					<?php
					$content = rtbiz_hd_content_filter( isset( $post->ID ) ? $post->post_content : '' );
					echo $content;
					if ( empty( $post->post_content ) ) {
						echo 'No content found.';
					}
					?>
				</div>
			</div>
		</li>
	</ul>

<?php if ( $Limit < $totalComment ) {
	?>
	<ul class="rthd-discussion load-more-ul" id="load-more-UI">
		<li>
			<a class="load-more-block" href="#">
				<span class="load-more-circle" id="followup-load-more-count"><?php echo $offset ?></span>
				<span class="load-more-count">more</span>
			</a>
			<a href="#" class="load-more" id="followup-load-more"> Load more</a>
		</li>
		<li class="load-more-spinner-li">
			<div class="rthdcenter"><img id="load-more-hdspinner" class="helpdeskspinner"
			                             src="<?php echo admin_url() . 'images/spinner.gif'; ?>"/></div>
		</li>
	</ul>
<?php } ?>

<ul class="rthd-discussion js-stream" id="chat-UI">

	<?php
	foreach ( $comments as $comment ) {
		$user_edit           = $staffonly || ( get_current_user_id() == $comment->user_id );
		$comment_user        = get_user_by( 'id', $comment->user_id );
		$comment_render_type = 'left';
		if ( ! empty( $comment_user ) ) {
			if ( $comment_user->has_cap( rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' ) ) ) {
				$comment_render_type = 'right';
			}
		}
		rtbiz_hd_render_comment( $comment, $user_edit, $comment_render_type );
	}
	?>
</ul>
<div id="dialog-form" title="Edit Followup" style='display: none'>

	<input id="followup-offset" type="hidden" value="<?php echo esc_attr( $offset ); ?>"/>
	<input id="followup-limit" type="hidden" value="<?php echo esc_attr( $Limit ); ?>"/>
	<input id="followup-totalcomment" type="hidden" value="<?php echo esc_attr( $totalComment ); ?>"/>
	<input id="followup-type" type="hidden" name="followuptype" value=""/>

	<div class="clearfix">
		<?php if ( $staffonly ) { ?>

			<ui id="followup-type-list" class="followup-tabs">
				<li id="tab-<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC; ?>" class="tab active" data-ctype="<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC; ?>"><?php _e('Public Reply', RTBIZ_HD_TEXT_DOMAIN) ?></li>
				<li id="tab-<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF; ?>" class="tab" data-ctype="<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF; ?>"><?php _e('Staff Note', RTBIZ_HD_TEXT_DOMAIN) ?></li>
			</ui>

		<?php } ?>

		<div class="rthd-followup-content-helpbar">
				<span class="rthd-markdown-preview" data-parent="#dialog-form">
					Preview |
				</span>
				<span class="rthd-tooltip rthd-followup-content-tolltip">
					Markdown & HTML support
					<span class="rthd-tip-bottom"><?php
						_e( 'You may use Markdown syntax and these HTML tags - a, abbr, acronym, b, blockquote, cite, code, del, em, i, q, s, strike and strong', RTBIZ_HD_TEXT_DOMAIN ); ?>
					</span>
				</span>
		</div>
	</div>

	<div id="editedfollowupcontent_markdown">
		<div id="editedfollowupcontent_html" class="pane markdown_preview_container"><noscript><h2>You'll need to enable Javascript to use this tool.</h2></noscript></div>
		<div class="rthd-followup-content-container">
			<textarea id="editedfollowupcontent" name="editedfollowupcontent" placeholder="edit reply" rows="5" cols="20"></textarea>
		</div>
	</div>

	<div id="edit-private-comment" class="rthd-edit-visibility-wrap">
		<div class="rthd-edit-visibility-wrap">
			<div class="rthd-sensitive-wrap">
				<label for="rthd_sensitive">
					<input id="rthd_sensitive" type="checkbox" name="followup_sensitive"
					       value="true"/>&nbsp;<?php _e( 'Mark this as Sensitive' ); ?>
					<span class="rthd-tooltip rthd-followup-type-tolltip">
							<i class="dashicons dashicons-info rtmicon"></i>
							<span class="rthd-tip"><?php
								_e( 'Email notification will not show content of this followup. Recommended, if you are sharing password or other sensitive information.', RTBIZ_HD_TEXT_DOMAIN ); ?>
							</span>
						</span>
				</label>
			</div>
		</div>
		<img id='edithdspinner' class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>">
	</div>
	<div class="edit-action-button clearfix">
		<button type="button" class="btn close-edit-followup">Close</button>
		<button class="edit-followup btn red-color" id="delfollowup" type="button">Delete</button>
		<button class="edit-followup btn btn-primary button-primary" id="editfollowup" type="button">Update</button>
	</div>
</div>
<?php
if ( $user_edit_content ) {
	?>
	<div id="edit-ticket-data" title="Edit Ticket" style="display: none;">
		<div class="clearfix">
			<div class="rthd-followup-content-helpbar">
					<span class="rthd-markdown-preview" data-parent="#edit-ticket-data">
						Preview |
					</span>
					<span class="rthd-tooltip rthd-followup-content-tolltip">
						Markdown & HTML support
						<span class="rthd-tip-bottom"><?php
		_e( 'You may use Markdown syntax and these HTML tags - a, abbr, acronym, b, blockquote, cite, code, del, em, i, q, s, strike and strong', RTBIZ_HD_TEXT_DOMAIN ); ?>
						</span>
					</span>
			</div>
		</div>

		<div id="editedticketcontent_markdown">
			<div v-html="input | marked" id="editedticketcontent_html" class="pane markdown_preview_container"><noscript><h2>You'll need to enable Javascript to use this tool.</h2></noscript></div>
			<div class="rthd-followup-content-container">
				<textarea v-model="input" debounce="300" id="editedticketcontent" name="editedticketcontent" placeholder="edit ticket content" rows="5" cols="20"></textarea>
			</div>
		</div>
		<button class="edit-ticket btn btn-primary" id="edit-ticket-content-click" type="button">Update</button>
		<?php wp_nonce_field( 'rt_hd_ticket_edit', 'edit_ticket_nonce' ); ?>
		<img id='ticket-edithdspinner' class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>">
		<button class="btn close-edit-content">Close</button>
	</div>
<?php } ?>

