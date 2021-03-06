<?php
/**
 * rtHelpdesk Studio Dashboard Template
 *
 * @author udit
 */
global $rtbiz_hd_dashboard;
$author_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' );
$settings = rtbiz_hd_get_redux_settings();
$welcome_label = 'Helpdesk';
?>
<div class="wrap">

	<?php screen_icon(); ?>

	<h2><?php printf( __( '%s Dashboard', 'rtbiz-helpdesk' ), $welcome_label ); ?></h2>

	<?php
	if ( current_user_can( $author_cap ) ) {
		$classes = 'welcome-panel';

		$option = get_user_meta( get_current_user_id(), '_rtbiz_hd_show_welcome_panel', true );
		// 0 = hide, 1 = toggled to show or single site creator, 2 = multisite site owner
		$hide = 0 == $option || ( 2 == $option && wp_get_current_user()->user_email != get_option( 'admin_email' ) );
		if ( $hide ) {
			$classes .= ' hidden';
		}
		?>

		<div id="welcome-panel" class="<?php echo esc_attr( $classes ); ?>">
			<?php wp_nonce_field( 'rthd-welcome-panel-nonce', 'rthdwelcomepanelnonce', false ); ?>
			<a class="welcome-panel-close" href="<?php echo esc_url( admin_url( 'admin.php?page=rthd-' . esc_html( Rtbiz_HD_Module::$post_type ) . '-dashboard&rthdwelcome=0' ) ); ?>"><?php _e( 'Dismiss', 'rtbiz-helpdesk' ); ?></a>
			<?php
			/**
			 * Add content to the welcome panel on the admin dashboard.
			 * @since 3.5.0
			 */
			do_action( 'rtbiz_hd_welcome_panel' );
			?>
		</div>
	<?php } ?>

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
		<?php do_action( 'rtbiz_hd_after_dashboard' ); ?>

	</div>
	<!-- #poststuff -->

</div><!-- .wrap -->
