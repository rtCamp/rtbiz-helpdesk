<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 11/11/14
 * Time: 9:47 PM
 */

global $current_user;
?>
<form id="add_followup_form" method="post" enctype="multipart/form-data">
	<input type="hidden" id='ticket_unique_id' name="followup_ticket_unique_id" value="<?php echo esc_attr( $ticket_unique_id ); ?>" />
	<input type="hidden" name="post_type" value="<?php echo Rt_HD_Module::$post_type ?>" />
	<input type="hidden" name="action" value="rthd_add_new_followup_front" />
	<input type="hidden" name="followuptype" value="" />
	<input type="hidden" name="follwoup-time" value="" />

	<input id="user-avatar" type="hidden" value="<?php echo get_avatar( get_current_user_id(), 80); ?>" />
	<input id="user-email" type="hidden" value="<?php echo sanitize_email( $current_user->user_email ); ?>" />
	<input id="user-name" type="hidden" value="<?php echo esc_attr( $current_user->display_name ); ?>" />
	<input id="post-id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>" />
	<input id="edit-comment-id" name="comment_id" type="hidden" />
	<textarea id="followup_content" class="followup-content" name="followup_content" placeholder="Add new followup"></textarea>
	<div id="private-comment">
		<label for="add-private-comment"><input id="add-private-comment" type="checkbox" name="private_comment" value="yes" text="check to make comment private" /><?php _e('Private'); ?></label>
	</div>
	<div>
	<input id="attachemntlist" name="attachemntlist" type="file" />
	</div>

	<div class="rthd-clearfix"></div>
	<button class="add-savefollowup btn-primary btn" id="savefollwoup" type="button">Add followup</button>
	<img id='hdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
	<div class="rthd-clearfix"></div>
</form>