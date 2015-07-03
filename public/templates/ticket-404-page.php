<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 11/7/14
 * Time: 9:07 PM
 */

get_header();

do_action( 'rthd_ticket_front_page_after_header' );

global $rthd_messages;

?>
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<section class="error-404 not-found entry-content">
				<div class="page-content">
					<div class="rthd-container">
				<?php $messages = wp_list_pluck( $rthd_messages, 'message' );
					if ( ! empty( $messages ) ) {
						foreach ( $rthd_messages as $key => $message ) {
							if ( 'no' == $message['displayed'] && ! empty( $message['message'] ) ) {
								echo '<div class="' . $message['type'] . '">' . $message['message'] . '</div>';
								$rthd_messages[ $key ]['displayed'] = 'yes';
							}
						}
					}
				global $current_user; ?>
					<div class="rthd-my-ticket">
						<?php echo balanceTags( do_shortcode( '[rtbiz_hd_tickets show_support_form_link=yes userid = ' . $current_user->ID . ']' ) ); ?>
					</div>
					</div>
				</div>
			</section>

		</main>
	</div>

<?php

do_action( 'rthd_ticket_front_page_before_footer' );

get_footer();
