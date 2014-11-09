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

<div class="rthd-container">
	<?php
		foreach ( $rthd_messages as $key => $message ) {
			if ( $message['displayed'] == 'no' ) {
				echo '<div class="' . $message['type'] . '">' . $message['message'] . '</div>';
				$rthd_messages[$key]['displayed'] = 'yes';
			}
		}
	?>
</div>

<?php

do_action( 'rthd_ticket_front_page_before_footer' );

get_footer();
