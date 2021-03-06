<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 11/11/14
 * Time: 9:47 PM
 */
global $current_user;

$cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
$staffonly = current_user_can( $cap );
?>

<div id="new-followup-form">
	<input type="hidden" id='ticket_unique_id' name="followup_ticket_unique_id"
		   value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>
	<input type="hidden" id="followup_post_type" name="post_type" value="<?php echo Rtbiz_HD_Module::$post_type ?>"/>
	<input type="hidden" id="followuptype" name="followuptype" value=""/>
	<input type="hidden" id="follwoup-time" name="follwoup-time" value=""/>

	<input id="post-id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>"/>
	<input id="edit-comment-id" name="comment_id" type="hidden"/>

	<div class="clearfix">
		<?php if ( $staffonly ) { ?>

			<ui id="followup-type-list" class="followup-tabs">
				<li id="tab-<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC; ?>" class="tab active" data-ctype="<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC; ?>"><?php _e( 'Public Reply', 'rtbiz-helpdesk' ) ?></li>
				<li id="tab-<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF; ?>" class="tab" data-ctype="<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF; ?>"><?php _e( 'Staff Note', 'rtbiz-helpdesk' ) ?></li>
			</ui>

		<?php } ?>

		<div class="rthd-followup-content-helpbar">
			<span id="rthd-new-followup-preview" class="rthd-markdown-preview">
				Preview |
			</span>
			<span class="rthd-tooltip rthd-followup-content-tolltip">
				Markdown & HTML support
				<span class="rthd-tip-bottom"><?php _e( 'You may use Markdown syntax and these HTML tags - a, abbr, acronym, b, blockquote, cite, code, del, em, i, q, s, strike and strong', 'rtbiz-helpdesk' ); ?>
				</span>
			</span>
		</div>
	</div>

	<div id="followupcontent_markdown">
		<div id="followupcontent_html" class="pane markdown_preview_container"><noscript><h2>You'll need to enable Javascript to use this tool.</h2></noscript></div>
		<div class="rthd-followup-content-container">
			<textarea id="followupcontent" class="followupcontent" rows="5" cols="20" name="followupcontent"
					  placeholder="Add new reply"></textarea>
		</div>
	</div>

	<div id="rthd-followup-form" class="clearfix">
		<div class="rthd-attachment-box">

			<div class="rthd-visibility-wrap">
				<?php
				$rthd_sensitive_style = '';
				if ( rtbiz_hd_get_email_only_support()) {
					$rthd_sensitive_style = 'style="display:none;"';
				}
				?>
				<div class="rthd-sensitive-wrap" <?php echo $rthd_sensitive_style; ?> >
					<label for="rthd_sensitive_content">
						<input id="rthd_sensitive_content" type="checkbox" name="followup_sensitive"
							   value="true"/>&nbsp;<?php _e( 'Mark this as Sensitive', 'rtbiz-helpdesk' ); ?>
						<span class="rthd-tooltip rthd-followup-type-tolltip">
							<i class="dashicons dashicons-info rtmicon"></i>
							<span class="rthd-tip"><?php _e( 'Email notification will not show content of this followup. Recommended, if you are sharing password or other sensitive information.', 'rtbiz-helpdesk' ); ?>
							</span>
						</span>
					</label></div>
				<?php if ( $staffonly && $post->post_status != 'hd-answered' ) { ?>
					<div class="rthd-keep-status-wrap">
						<label for="rthd_keep_status">
							<input id="rthd_keep_status" type="checkbox" name="rthd_keep_status"
								   text="check keep status unanswered"/>
							<?php _e( 'Keep unanswered', 'rtbiz-helpdesk' ); ?></label></div>
				<?php } ?>

			</div>
			<div class="rthd-attachment">
				<!--			<input id="attachemntlist" name="attachemntlist[]" type="file" multiple />-->
				<div id="rthd-attachment-container">
					<a href="javascript:;" class="rthd-attach-file" id="attachemntlist" value="Attach Files"><span
							class="dashicons dashicons-upload" id="attachemntlist"></span><span>Attach Files</span>
					</a>
				</div>
				<div id="followup-filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
				<!--	        <span class="followup-note"><b>Note:</b> Attachments will be uploaded when the form is submitted by clicking <i>Add Followup</i> button.</span>-->
			</div>
		</div>
		<div id="rthd-followup-action" class="rthd-followup-action">
			<button class="add-savefollowup btn btn-primary button-primary" id="savefollwoup" type="button">Add Reply</button>
			<img id='hdspinner' class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>">
		</div>
	</div>

	<div class="rthd-clearfix"></div>
</div>
