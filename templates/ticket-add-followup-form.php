<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 11/11/14
 * Time: 9:47 PM
 */

global $current_user;
?>
<div id="new-followup-form">
	<input type="hidden" id='ticket_unique_id' name="followup_ticket_unique_id" value="<?php echo esc_attr( $ticket_unique_id ); ?>" />
	<input type="hidden" id="followup_post_type" name="post_type" value="<?php echo Rt_HD_Module::$post_type ?>" />
	<input type="hidden" id="followuptype" name="followuptype" value="" />
	<input type="hidden" id="follwoup-time" name="follwoup-time" value="" />

	<input id="post-id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>" />
	<input id="edit-comment-id" name="comment_id" type="hidden" />
<?php
$editor_id = 'followupcontent';
$settings = array( 'media_buttons' => false, 'editor_class' => 'followupcontent',  'tinymce' => array(
	'height' => 200,
));
wp_editor( '', $editor_id, $settings );
?>

<!--	<textarea id="followupcontent" class="followup-content" name="followupcontent" placeholder="Add new followup"></textarea>-->
	<div id="private-comment">
		<span class="rthd-visibility"> Visibility: </span>
		<select name="private_comment" id="add-private-comment" >
			<option value="<?php echo Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ?>"> <?php echo rthd_get_comment_type(Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC ) ?> </option>
			<option value="<?php echo Rt_HD_Import_Operation::$FOLLOWUP_SENSITIVE ?>"> <?php echo rthd_get_comment_type(Rt_HD_Import_Operation::$FOLLOWUP_SENSITIVE ) ?> </option>
			<?php
			$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
			$staffonly  = current_user_can( $cap );

			if( $staffonly ){ ?>
				<option value="<?php echo Rt_HD_Import_Operation::$FOLLOWUP_STAFF ?>"> <?php echo rthd_get_comment_type(Rt_HD_Import_Operation::$FOLLOWUP_STAFF ) ?> </option>
			<?php }
			?>
		</select>
		<?php if (current_user_can(rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' )) && $post->post_status != 'hd-answered' ){ ?>
        <?php } ?>

	</div>
	<div> <label for="rthd_keep_status"><input id="rthd_keep_status" type="checkbox" name="rthd_keep_status" text="check keep status unanswered" /><?php _e('Keep unanswered'); ?></label></div>
	<div>
		<input id="attachemntlist" name="attachemntlist[]" type="file" multiple />
		<button class="add-savefollowup btn-primary btn" id="savefollwoup" type="button">Add followup</button>
	</div>

	<div class="rthd-clearfix"></div>
	<img id='hdspinner' class="helpdeskspinner" src="<?php echo admin_url().'images/spinner.gif'; ?>">
	<div class="rthd-clearfix"></div>
</div>