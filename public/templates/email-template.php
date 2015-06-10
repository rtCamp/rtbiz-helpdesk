<?php $date = strtotime( current_time( 'mysql', 1 ) ); ?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo get_bloginfo( 'name' ); ?></title>
	</head>

	<body>

		<?php
		if ( $replyflag && rtbiz_hd_is_enable_mailbox_reading() && rtbiz_hd_get_reply_via_email() ) {
			echo '<div style="display: none !important; color:#c5c5c5;font-size:11px;visibility: hidden;">' . htmlentities( ':: Reply Above This Line ::' ) . '</div>';
		}

		$beforeHTML = apply_filters( 'rthd_before_email_body', $body );
		$afterHTML = apply_filters( 'rthd_after_email_body', $body );

		if ( ! has_filter( 'rthd_before_email_body' ) ) {
			$beforeHTML = '';
		}

		if ( ! has_filter( 'rthd_after_email_body' ) ) {
			$afterHTML = '';
		}
		$body = rtbiz_hd_replace_placeholder( $body, '{ticket_link}', $title );

		echo $beforeHTML;
		?>

		<div>
			<?php echo rtbiz_hd_content_filter( $body ); ?>
		</div>

		<?php
		echo $afterHTML;

		$signature = rtbiz_hd_get_email_signature_settings();

		echo ( ( ! empty( $signature ) ) ? '<div style="color:#c5c5c5; font-size: 14px;">' . wpautop( $signature ) . '</div>' : '' ) . '<br/>'
		?>

	</body>

</html>