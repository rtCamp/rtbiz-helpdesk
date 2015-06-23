<?php if ( 'yes' == $show_title ) { ?>
<div class="rthd-support-title"><?php _e( 'Get Support', RTBIZ_HD_TEXT_DOMAIN ); ?></div>
<?php }
?>
<form method="post" class="pure-form rthd_support_from" enctype="multipart/form-data">

	<?php if ( isset( $_REQUEST['order_id'] ) ) { ?>
		<input type="hidden" name="post[order_id]" value="<?php echo $_REQUEST['order_id']; ?>">
	<?php } ?>

	<?php if ( isset( $_REQUEST['order_type'] ) ) { ?>
		<input type="hidden" name="post[order_type]" value="<?php echo $_REQUEST['order_type']; ?>">
	<?php } ?>

	<div>
		<input id="title" placeholder="Title" type="text" name="post[title]"
		       value="<?php echo isset( $_POST['post']['title'] ) ? $_POST['post']['title'] : ''; ?>" required/>
	</div>

	<?php if ( $product_exists ) { ?>
		<div>
			<select name="post[product_id]" required>
				<option value="">Choose Product</option>
				<?php echo balanceTags( $product_option ); ?>
			</select>
		</div>
	<?php } ?>


	<?php
	$email = '';
	if ( is_user_logged_in() && ! isset( $_POST['post']['email'] ) ) {
		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;
	} else if ( ! empty( $_POST['post']['email'][0] ) ) {
		$email = $_POST['post']['email'][0];
	}
	?>
	<div>
		<div class="rthd-email-group">
			<div>
				<input class="rthd_email" placeholder="Email" type="email" name="post[email][]"
				       value="<?php echo sanitize_email( $email ) ?>" required/>
				<a href="#" class="rt-hd-add-more-email">Add CC</a>
			</div>
			<?php
			if ( isset( $_POST['post']['email'] ) && count( $_POST['post']['email'] ) > 1 ) {
				for ( $i = 1; $i <= count( $_POST['post']['email'] ); $i ++ ) {
					if ( ! empty( $_POST['post']['email'][ $i ] ) ) {
						?>
						<div>
							<input class="rthd_email" placeholder="CC" type="email" name="post[email][]"
							       value="<?php echo $_POST['post']['email'][ $i ]; ?>"/>
							<a href="#" class="rt-hd-remove-textbox">x</a>
						</div>
					<?php
					}
				}
			}
			?>
		</div>
	</div>

	<div class="rthd-hide-form-div">
		<div>
			<input class="rthd_email" placeholder="CC" type="email" name="post[email][]"/>
			<a href="#" class="rt-hd-remove-textbox">x</a>
		</div>
	</div>

	<div id="editor_container">
		<textarea id="post_description" placeholder="Description" rows="5" cols="20" name="post[description]" class="post_description" required><?php echo isset( $_POST['post']['description'] ) ? $_POST['post']['description'] : ''; ?></textarea>
		<span class="rthd-tooltip rthd-followup-content-tolltip">
			HTML support
			<span class="rthd-tip-bottom"><?php
				_e( 'You may use these HTML tags - a, abbr, acronym, b, blockquote, cite, code, del, em, i, q, s, strike and strong', RTBIZ_HD_TEXT_DOMAIN ); ?>
			</span>
		</span>
	</div>
	<div id="rthd-followup-form" class="clearfix">
	<div class="rthd-attachment-box">
		<?php
		//is ticket have adult content

		if ( rtbiz_hd_get_redux_adult_filter() ) {
			?>
			<div>
				<input type="checkbox" name="post[adult_ticket]" value="1"/>
				<span class="description"><?php _e( 'Adult Content', RTBIZ_HD_TEXT_DOMAIN ); ?></span>
				<span class="rthd-tooltip rthd-tooltip-adult-content">
					<i class="dashicons dashicons-info rtmicon"></i>
					<span class="rthd-tip">
						<?php _e( 'My site has adult content', RTBIZ_HD_TEXT_DOMAIN ); ?>
					</span>
				</span>
			</div>
		<?php } ?>
		<div class="rthd-attachment">
			<!--		--><?php //wp_nonce_field( 'rthd_support_add_nonce_for_security_thats_all', 'rthd_support_nonce' );       ?>

			<!--		<input type="file" id="filesToUpload" name="attachment[]" multiple="multiple"/>-->
			<div id="attachment-container">
				<a href="javascript:;" class="rthd-attach-file" id="attachemntlist" value="Attach Files"><span
						class="dashicons dashicons-upload" id="attachemntlist"></span><span>Attach Files</span>
				</a>
			</div>
			<div id="support-filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
			<input type="hidden" name="rthd_support_attach_ids" id="rthd_support_attach_ids">
		</div>
	</div>

	<div id="rthd-followup-action" class="rthd-followup-action">
		<input type="hidden" name="rthd_support_form_submit" value="1"/>
		<input class="btn btn-primary" id="submit-support-form" type="submit" value="Submit"/>
	</div>
		</div>

</form>
