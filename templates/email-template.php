<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 26/3/15
 * Time: 1:40 PM
 */

$date = strtotime( current_time( 'mysql', 1 ) );
?>
<html>
<head> <title></title></head>
<body>
<div style="border: 1px solid #DFE9f2;padding: 20px;background: #f1f6fa;">
	<?php echo rthd_content_filter( $body ) ;?>
	<br/>
	<div style="float: right;color: gray;">
		<?php echo date( 'l M d, Y H:i e', $date ); ?>
	</div>
</div>
</body>
</html>
