<?php $date = strtotime( current_time( 'mysql', 1 ) ); ?>

<!DOCTYPE html>
<html>
	<head>
		<title>Email</title>
	</head>

	<body>

		<?php
		if ( $replyflag && rthd_is_enable_mailbox_reading() && rthd_get_reply_via_email() ) {
			echo '<div style="color: #c5c5c5; font-size: 11px;">' . htmlentities( ':: Reply Above This Line ::' ) . '</div>';
		}

		echo $title;

		$beforeHTML = apply_filters( 'rthd_before_email_body', $body );
		$afterHTML = apply_filters( 'rthd_after_email_body', $body );

		if ( ! has_filter( 'rthd_before_email_body' ) ) {
			$beforeHTML = '';
		}

		if ( ! has_filter( 'rthd_after_email_body' ) ) {
			$afterHTML = '';
		}

		echo $beforeHTML;
		?>

		<div>
			<?php echo rthd_content_filter( $body ); ?>
		</div>

		<?php
		echo $afterHTML;

		$signature = rthd_get_email_signature_settings();

		echo ( ( ! empty( $signature ) ) ? '<div style="color:#666;">' . wpautop( $signature ) . '</div>' : '' ) . '<br/>'
		?>

	</body>

</html>