<?php

/*
 * rtHelpdesk Studio Dashboard Template
 *
 * @author udit
 */
?>
<div class="wrap">

	<?php screen_icon(); ?>

	<h2><?php _e( 'Helpdesk Dashboard' ); ?></h2>

	<div id="poststuff">

		<div id="dashboard-widgets" class="metabox-holder">


			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( '', 'column1', null ); ?>
			</div>

			<div id="postbox-container-2" class="postbox-container">
				<?php do_meta_boxes( '', 'column2', null ); ?>
			</div>

			<div id="postbox-container-3" class="postbox-container">
				<?php do_meta_boxes( '', 'column3', null ); ?>
			</div>

			<div id="postbox-container-4" class="postbox-container">
				<?php do_meta_boxes( '', 'column4', null ); ?>
			</div>
			<div id="postbox-container-4" class="postbox-container">
				<?php do_meta_boxes( '', 'column5', null ); ?>
			</div>
			<div id="postbox-container-4" class="postbox-container">
				<?php do_meta_boxes( '', 'column6', null ); ?>
			</div>

		</div>
		<!-- #post-body -->
		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php do_action( 'rthd_after_dashboard' ); ?>

	</div>
	<!-- #poststuff -->

</div><!-- .wrap -->
