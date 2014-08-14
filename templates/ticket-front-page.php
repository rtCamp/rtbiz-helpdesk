<?php

/*
 * ticket front page Template
 *
 * @author udit
 */
global $rt_hd_module, $rt_hd_contacts, $rt_hd_accounts, $rt_hd_settings, $rt_hd_tickets;

get_header();

do_action( 'rthd_ticket_front_page_after_header' );

global $rthd_ticket;
$post_type = get_post_type( $rthd_ticket );
$module_settings = rthd_get_settings();
$labels = $rt_hd_module->labels;
$post_id = $rthd_ticket->ID;
$user_edit = false;

?>
<div class="rthd-container">
<?php
global $wpdb;
echo "<script> var arr_ticketmeta_key=''; </script>";


$post = get_post( $post_id );
$ticket_unique_id = get_post_meta( $post_id, 'rthd_unique_id', true );

$create = new DateTime($post->post_date);

$modify = new DateTime($post->post_modified);
$createdate = $create->format("M d, Y h:i A");
$modifydate = $modify->format("M d, Y h:i A");
?>
<div  id="add-new-post" class="row">
	<input type="hidden" id='ticket_unique_id' value="<?php echo $ticket_unique_id; ?>" />
	<div class="large-12 columns">
		<?php if( $user_edit ) { ?>
			<input name="post[post_title]" id="new_<?php echo $post_type ?>_title" type="text" placeholder="<?php _e(ucfirst($labels['name'])." Subject"); ?>" value="<?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?>" />
		<?php } else { ?>
			<h4><?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?></h4>
		<?php } ?>
	</div>
	<div class="large-12 columns">
		<div class="large-9 small-12 columns">
			<div class="row expand">
				<div class="large-12 columns">
					<?php
						if( $user_edit ) {
							wp_editor(( isset($post->ID) ) ? $post->post_content : "", "post[post_content]");
						} else {
							echo '<span>'.(( isset($post->ID) ) ? $post->post_content : '').'</span>';
						}
					?>
				</div>
			</div>
			<br /><br />
<?php if (isset($post->ID)) { ?>
			<div id="followup_wrapper">
				<fieldset>
					<legend>Followup</legend>
					<form id="add_followup_form" method="post">
						<input type='hidden' id='edit-comment-id' />
						<textarea id="followup_content" name="followup_content" placeholder="Add new followup"></textarea>
						<button class="mybutton add-savefollowup" id="savefollwoup" type="button" ><?php _e("Add"); ?></button>
						<!--<button class="mybutton right" type="submit" ><?php _e("Add"); ?></button>-->
						<!--<input type="file" class="right" name="ticket_attach_file" id="ticket_attach_file" multiple />-->
					</form>
					<div class="row">
<?php
$page = 0;
$comment_count = count( get_comments(
	array(
		'meta_key' => '_rthd_privacy',
		'meta_value' => 'no',
		'order' => 'DESC',
		'post_id' => $post->ID,
		'post_type' => $post_type,
	)
) );
$comments = get_comments(
	array(
		'meta_key' => '_rthd_privacy',
		'meta_value' => 'no',
		'order' => 'DESC',
		'post_id' => $post->ID,
		'post_type' => $post_type,
		'number' => '10',
		'offset' => $page*10
	)
);
?>
						<div class="large-12 columns <?php echo (empty($comments)) ? 'hide' : ''; ?>">
							<a class="accordion-expand-all right" href="#" data-isallopen="false"><i class="general foundicon-down-arrow" title="Expand All"></i></a>
						</div>
						<div class="large-12 columns" id="commentlist">

<?php

global $prev_month, $prev_year, $prev_day;
$prev_month = '';
$prev_day = '';
$prev_year = '';
foreach ( $comments as $comment ) {
	rthd_get_template( 'followup.php', array( 'comment' => $comment, 'user_edit' => $user_edit, ) );
} //End Loop for comments

$all_hd_participants = array();
$comments = get_comments(
	array(
		'meta_key' => '_rthd_privacy',
		'meta_value' => 'no',
		'order' => 'DESC',
		'post_id' => $post->ID,
		'post_type' => $post_type,
	)
);
foreach ( $comments as $comment ) {
	$participants = '';
	$to = get_comment_meta( $comment->comment_ID, '_email_to', true );
	if( !empty( $to ) )
		$participants .= $to.',';
	$cc = get_comment_meta( $comment->comment_ID, '_email_cc', true );
	if( !empty( $cc ) )
		$participants .= $cc.',';
	$bcc = get_comment_meta( $comment->comment_ID, '_email_bcc', true );
	if( !empty( $bcc ) )
		$participants .= $bcc;

	if( !empty( $participants ) ) {
		$p_arr = explode(',', $participants);
		$p_arr = array_unique($p_arr);
		$all_hd_participants = array_merge($all_hd_participants, $p_arr);
	}
}
$all_hd_participants = array_filter( array_unique( $all_hd_participants ) );
									?>
						</div>
						<div class="large-12 columns <?php echo ( ($page+1) < ($comment_count/10) ) ? '' : 'hide'; ?>">
							<button id="load_more_btn" class="button large expand"><?php _e( 'LOAD MORE' ); ?></button>
						</div>
					</div>
				</fieldset>
			</div>
<?php } ?>
		</div>
		<div class="large-3 columns rthd_sticky_div">
			<fieldset>
				<legend><i class="foundicon-idea"></i> <?php _e(ucfirst($labels['name'])." Information"); ?></legend>
				<div class="row collapse">
					<div class="small-4 large-4  columns">
						<span class="prefix" title="Status">Status</span>
					</div>
					<div class="small-8 large-8 columns">
<?php
if (isset($post->ID))
    $pstatus = $post->post_status;
else
    $pstatus = "";
$post_status = $rt_hd_module->get_custom_statuses();
?>
					<?php if( $user_edit ) { ?>
						<select class="right" name="post[post_status]">
<?php foreach ($post_status as $status) {
    if ($status['slug'] == $pstatus)
        $selected = 'selected="selected"';
    else
        $selected = '';

    echo printf('<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name']);
} ?>
						</select>
					<?php } else {
						foreach ( $post_status as $status ) {
							if($status['slug'] == $pstatus) {
								echo '<div class="rthd_attr_border rthd_view_mode">'.$status['name'].'</div>';
								break;
							}
						}
					} ?>
					</div>
				</div>
				<div class="row collapse">
					<div class="large-4 small-4 columns">
						<span class="prefix" title="Create Date"><label>Create Date</label></span>
					</div>
					<div class="large-8 mobile-large-2 columns">
						<?php if( $user_edit ) { ?>
							<input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
									value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>"
									title="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>">
							<input name="post[post_date]" type="hidden" value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" />
						<?php } else { ?>
							<div class="rthd_attr_border rthd_view_mode moment-from-now" title="<?php echo $createdate ?>"><?php echo $createdate ?></div>
						<?php } ?>
					</div>
				</div>
<?php if (isset($post->ID)) { ?>

				<div class="row collapse">
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="Modify Date"><label>Modify Date</label></span>
					</div>
					<div class="large-8 mobile-large-3 columns">
						<?php if( $user_edit ) { ?>
							<input class="moment-from-now"  type="text" placeholder="Modified on Date"  value="<?php echo $modifydate; ?>"
								  title="<?php echo $modifydate; ?>" readonly="readonly">
						<?php } else { ?>
							<div class="rthd_attr_border rthd_view_mode moment-from-now" title="<?php echo $createdate ?>"><?php echo $createdate ?></div>
						<?php } ?>
					</div>
				</div>
<?php } ?>
				<div class="row collapse">
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php _e('Assigned To'); ?>"><label for="post[post_author]"><?php _e('Assigned To'); ?></label></span>
					</div>
					<div class="large-8 mobile-large-3 columns">
<?php
$get_assigned_to = array();
if (isset($post->ID)) {
    $post_author = $post->post_author;
    $get_assigned_to = get_post_meta($post->ID, "subscribe_to", true);
} else {
    $post_author = get_current_user_id();
}
$results = Rt_HD_Utils::get_hd_rtcamp_user();
$arrCommentReply = array();
$arrSubscriberUser[] = array();
$subScribetHTML = "";
if( !empty( $results ) ) {
	foreach ( $results as $author ) {
		if ($get_assigned_to && !empty($get_assigned_to) && in_array($author->ID, $get_assigned_to)) {
			if( in_array( $author->user_email, $all_hd_participants ) ) {
				$key = array_search($author->user_email, $all_hd_participants);
				if ( $key !== FALSE ) {
					unset( $all_hd_participants[$key] );
				}
			}
            $subScribetHTML .= "<li id='subscribe-auth-" . $author->ID . "' class='contact-list' >"
					. '<div class="row collapse"><div class="large-2 columns"> ' . get_avatar($author->user_email, 24) . '</div>'
					. '<div class="large-9 columns"><a target="_blank" class="heading" href="mailto:' . $author->user_email . '" title="' . $author->display_name . '">' . $author->display_name . '</a>'
						. '<input type="hidden" name="subscribe_to[]" value="' . $author->ID . '" />'
					. '</div>'
					. '</li>';
        }
        $arrSubscriberUser[] = array("id" => $author->ID, "label" => $author->display_name, "imghtml" => get_avatar($author->user_email, 24));
        $arrCommentReply[] = array("userid" => $author->ID, "label" => $author->display_name, "email" => $author->user_email, "contact" => false, "imghtml" => get_avatar($author->user_email, 24));
	}
}
?>
					<?php if( $user_edit ) { ?>
						<select name="post[post_author]" >
<?php
if (!empty($results)) {
    foreach ($results as $author) {
        if ($author->ID == $post_author) {
            $selected = " selected";
        } else {
            $selected = " ";
        }
        echo '<option value="' . $author->ID . '"' . $selected . '>' . $author->display_name . '</option>';
    }
}
?>
						</select>
					<?php } else {
						if(!empty($results)) {
							foreach ($results as $author) {
								if($author->ID == $post_author) {
									echo '<div class="rthd_attr_border rthd_view_mode">'.get_avatar( $author->user_email, 17 ).' '.$author->display_name.'</div>';
									break;
								}
							}
						} else {
							echo '<div class="rthd_attr_border rthd_view_mode">'.__( 'Not Assigned yet ').'</div>';
						}
					} ?>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<legend><i class="foundicon-smiley"></i> <?php _e("Participants"); ?></legend>
				<script>
					var arr_subscriber_user =<?php echo json_encode($arrSubscriberUser); ?>;
					var ac_auth_token = '<?php echo get_user_meta(get_current_user_id(), 'rthd_activecollab_token', true); ?>';
					var ac_default_project = '<?php echo get_user_meta(get_current_user_id(), 'rthd_activecollab_default_project', true); ?>';
				</script>
				<ul class="rthd-participant-list large-block-grid-1 small-block-grid-1">
<?php echo $subScribetHTML; ?>
				</ul>
<?php if ( isset( $module_settings['attach_contacts'] ) && $module_settings['attach_contacts'] == 'yes' ) { ?>
				<ul class="rthd-participant-list large-block-grid-1 small-block-grid-1">
					<?php if (isset($post->ID)) {
                            $scriptstr = "";
		$ticket_term = rt_biz_get_post_for_person_connection( $post->ID, $post->post_type, $fetch_person = true );
		foreach ($ticket_term as $tterm) {
                                $email = rt_biz_get_entity_meta($tterm->ID, $rt_hd_contacts->email_key, true);
								if( in_array( $email, $all_hd_participants ) ) {
									$key = array_search($email, $all_hd_participants);
									if ( $key !== FALSE ) {
										unset( $all_hd_participants[$key] );
									}
								}
                                echo "<li id='hd-contact-" . $tterm->term_id . "' class='contact-list' >"
										. "<div class='row collapse'>"
											. "<div class='large-2 columns'> " . get_avatar($email, 24) . "</div>"
											. "<div id='hd-contact-meta-" . $tterm->term_id . "'  class='large-9 columns'><a target='_blank' class='heading' href='mailto:" . $email . "' title='" . $tterm->name . "'>" . $tterm->name . "</a></div>"
										. "</div>"
								. "</li>";
                            }
                        } ?>
				</ul>
				<ul class="rthd-participant-list large-block-grid-1 small-block-grid-1">
				<?php foreach ( $all_hd_participants as $email ) {
					echo "<li class='contact-list'>"
							. "<div class='row collapse'>"
								. "<div class='large-2 columns'> " . get_avatar($email, 24) . "</div>"
								. "<div class='large-9 columns'><a target='_blank' class='heading' href='mailto:" . $email . "' title='" . $email . "'>" . $email . "</a></div>"
							. "</div>"
					. "</li>";
				} ?>
				</ul>
			</fieldset>
<?php }
$attachments = array();
if ( isset( $post->ID ) ) {
	$attachments = get_children( array(
		'post_parent' => $post->ID,
		'post_type' => 'attachment',
	));
} ?>
			<fieldset>
				<legend><i class="foundicon-paper-clip"></i> <?php _e("Attachments"); ?></legend>
				<?php if( $user_edit ) { ?>
					<a href="#" class="button" id="add_ticket_attachment"><?php _e('Add'); ?></a>
				<?php } ?>
				<div class="scroll-height">
					<?php foreach ($attachments as $attachment) { ?>
						<?php $extn_array = explode('.', $attachment->guid); $extn = $extn_array[count($extn_array) - 1]; ?>
						<div class="large-12 mobile-large-3 columns attachment-item" data-attachment-id="<?php echo $attachment->ID; ?>">
							<a class="rthd_attachment" title="<?php echo $attachment->post_title; ?>" target="_blank" href="<?php echo wp_get_attachment_url($attachment->ID); ?>">
								<img height="20px" width="20px" src="<?php echo RT_HD_URL . "assets/file-type/" . $extn . ".png"; ?>" />
								<?php echo $attachment->post_title; ?>
							</a>
							<?php if( $user_edit ) { ?>
								<a href="#" class="rthd_delete_attachment right">x</a>
							<?php } ?>
							<input type="hidden" name="attachment[]" value="<?php echo $attachment->ID; ?>" />
						</div>
					<?php } ?>
				</div>
			</fieldset>
		</div>
	</div>
</div>
<script>
    var arr_comment_reply_to = <?php echo json_encode($arrCommentReply); ?>;
</script>
</div>
<?php
do_action( 'rthd_ticket_front_page_before_footer' );
get_footer();