<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 26/3/15
 * Time: 1:40 PM
 */

$date = strtotime( current_time( 'mysql', 1 ) );
?>
<!DOCTYPE html>
<html>
<head> <title></title></head>
<body>
<?php
echo $title;
if ( $replyflag && rthd_is_enable_mailbox_reading() && rthd_get_reply_via_email() ){
	echo '<div style="color:#777">'.htmlentities('[!-------REPLY ABOVE THIS LINE-------!]').'</div><br /> ';
}
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

<div style="border: 1px solid #DFE9f2;padding: 20px;background: #f1f6fa;">
	<?php echo rthd_content_filter( $body ) ;?>
	<br/>
	<div style="float: right;color: gray;">
		<?php echo date( 'l M d, Y H:i e', $date ); ?>
	</div>
</div>
<?php echo $afterHTML;
$signature = rthd_get_email_signature_settings();
echo  '<br/>' . ( ( ! empty( $signature ) ) ? '<div style="color:#666;">' . wpautop( $signature ) . '</div>' : '' ) . '<br/>'
?>
</body>
</html>
