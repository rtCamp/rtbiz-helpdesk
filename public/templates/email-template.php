<?php $date = strtotime( current_time( 'mysql', 1 ) ); ?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo get_bloginfo( 'name' ); ?></title>
	</head>

	<body>
		<div style="display: none !important; font-size: 0px !important; line-height: 0px !important; font: <?php echo $post_id; ?>; color:white;" >Ref: <?php echo $post_id; ?></div>
		<?php
		// todo : check this logic
		if ( $replyflag && rtbiz_hd_is_enable_mailbox_reading() && rtbiz_hd_get_reply_via_email() && ! rtbiz_hd_get_web_only_support() ) {
			echo '<div style="display: none !important; color:#c5c5c5 !important;font-size:11px !important;visibility: hidden !important;">' . htmlentities( '::Reply Above This Line::' ) . '</div>';
		} else {
			echo '<div style="color:#c5c5c5 !important;font-size:12px !important; padding-bottom: 10px; !important;">' . htmlentities( '-- Do not reply to this email --' ) . '</div>';
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

		<div style="display: none !important; font-size: 0px !important; line-height: 0px !important; font: <?php echo time();?>; color:white;" >Tick: <?php echo time(); ?></div>
	</body>

</html>
