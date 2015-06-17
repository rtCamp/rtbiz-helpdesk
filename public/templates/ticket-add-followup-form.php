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
	<input type="hidden" id='ticket_unique_id' name="followup_ticket_unique_id"
	       value="<?php echo esc_attr( $ticket_unique_id ); ?>"/>
	<input type="hidden" id="followup_post_type" name="post_type" value="<?php echo Rtbiz_HD_Module::$post_type ?>"/>
	<input type="hidden" id="followuptype" name="followuptype" value=""/>
	<input type="hidden" id="follwoup-time" name="follwoup-time" value=""/>

	<input id="post-id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>"/>
	<input id="edit-comment-id" name="comment_id" type="hidden"/>

	<ui id="followup-type-list" class="">
		<li data-ctype="<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC; ?>">Customer + Staff</li>
		<li data-ctype="<?php echo Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF; ?>">Staff</li>
	</ui>

	<p><textarea id="followupcontent" class="followupcontent" rows="5" cols="20" name="followupcontent"
	             placeholder="Add new followup" required></textarea></p>

	<p class="form-allowed-tags" id="form-allowed-tags">You may use these <abbr
			title="HyperText Markup Language">HTML</abbr> tags and attributes: <code>&lt;a href="" title=""&gt; &lt;abbr
			title=""&gt; &lt;acronym title=""&gt; &lt;b&gt; &lt;blockquote cite=""&gt; &lt;cite&gt; &lt;code&gt; &lt;del
			datetime=""&gt; &lt;em&gt; &lt;i&gt; &lt;q cite=""&gt; &lt;s&gt; &lt;strike&gt; &lt;strong&gt; </code></p>

	<div id="rthd-followup-form" class="clearfix">
		<div class="rthd-attachment-box">
			<?php
			$cap       = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
			$staffonly = current_user_can( $cap ); ?>
			<div class="rthd-visibility-wrap">
				<label class="rthd-visibility"> <strong>Visibility </strong></label>
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
					</label></div>
				<?php if ( $staffonly && $post->post_status != 'hd-answered' ) { ?>
					<div class="rthd-keep-status-wrap">
						<label for="rthd_keep_status">
							<input id="rthd_keep_status" type="checkbox" name="rthd_keep_status"
							       text="check keep status unanswered"/>&nbsp;
							<?php _e( 'Keep unanswered' ); ?></label></div>
				<?php } ?>

			</div>
			<div class="rthd-attachment">
				<!--			<input id="attachemntlist" name="attachemntlist[]" type="file" multiple />-->
				<div id="rthd-attachment-container">
					<button class="btn button" id="attachemntlist" value="Attach Files"><span
							class="dashicons dashicons-upload" id="attachemntlist"></span><span>Attach Files</span>
					</button>
				</div>
				<div id="followup-filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
				<!--	        <span class="followup-note"><b>Note:</b> Attachments will be uploaded when the form is submitted by clicking <i>Add Followup</i> button.</span>-->
			</div>
		</div>
		<div id="rthd-followup-action" class="rthd-followup-action">
			<button class="add-savefollowup btn btn-primary button-primary button" id="savefollwoup" type="button">Add
				followup
			</button>
			<img id='hdspinner' class="helpdeskspinner" src="<?php echo admin_url() . 'images/spinner.gif'; ?>">
		</div>
	</div>

	<div class="rthd-clearfix"></div>
</div>
