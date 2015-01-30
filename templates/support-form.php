<script type="text/javascript">
jQuery(document).ready(function ($) {
	//print list of selected file
	$("#filesToUpload").change(function () {
		var input = document.getElementById('filesToUpload');
		var list = '';
		//for every file...
		for (var x = 0; x < input.files.length; x++) {
			//add to list
			list += '<li>' + input.files[x].name + '</li>';
		}
		$("#fileList").html(list);
	});
});
</script>

<div class="rthd-support-title"><?php _e( 'Get Support', 'RT_HD_TEXT_DOMAIN' ); ?></div>
<form method="post" class="pure-form rthd_support_from" enctype="multipart/form-data">

	<?php if ( isset( $_REQUEST['order_id'] ) ) { ?>
		<input type="hidden" name="post[order_id]" value="<?php echo $_REQUEST['order_id']; ?>">
	<?php } ?>

	<?php if ( isset( $_REQUEST['order_type'] ) ) { ?>
		<input type="hidden" name="post[order_type]" value="<?php echo $_REQUEST['order_type']; ?>">
	<?php } ?>

	<div>
		<input id="title" placeholder="Title" type="text" name="post[title]" required />
	</div>

	<?php if ( $product_exists ) { ?>
		<div>
			<select name="post[product_id]" required >
				<option value="">Choose Product</option>
				<?php echo balanceTags( $product_option ); ?>
			</select>
		</div>
	<?php } ?>


	<?php
	$email = '';
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$email = $current_user->user_email;
	} ?>
	<div>
		<input id="email" placeholder="Email" type="email" name="post[email]" value="<?php echo sanitize_email( $email ) ?>" required />
	</div>

	<div>
		<textarea name="post[description]" placeholder="Description" rows="10" cols="10" required></textarea>
	</div>

	<div>
		<input type="file" id="filesToUpload" name="attachment[]" multiple="multiple"/>
		<ul id="fileList">
			<li>No Files Selected</li>
		</ul>
	</div>

	<div>
		<input type="hidden" name="rthd_support_form_submit" value="1" />
                <input class="btn btn-primary" type="submit" value="Submit" />
	</div>

</form>
