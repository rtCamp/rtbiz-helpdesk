<?php
/*
 *
 * Comment Template - Frontend (Public Page for Ticket)
 *
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$dt         = new DateTime( $comment->comment_date );
$curr_month = $dt->format( 'M' );
$curr_day   = $dt->format( 'd' );
$curr_year  = $dt->format( 'Y' );
global $prev_month, $prev_year, $prev_day;

?>

<div id="header-<?php echo esc_attr( $comment->comment_ID ); ?>" class="comment-header row">
	<?php if ( ! ( $curr_month == $prev_month && $curr_day == $prev_day && $curr_year == $prev_year ) ) { ?>
		<div class="comment-date left large-1 columns" title="<?php echo esc_attr( $dt->format( 'M d,Y h:i A' ) ); ?>">
			<p class="comment-month"><?php echo esc_html( $curr_month ); ?></p>

			<p class="comment-day"><?php echo esc_html( $curr_day ); ?></p>

			<p class="comment-year"><?php echo esc_html( $curr_year ); ?></p>
		</div>
	<?php
	}

	$class = '';
if ( $curr_month == $prev_month && $curr_day == $prev_day && $curr_year == $prev_year ) {
	$class = 'comment-skip-date';
}


	$prev_month = $curr_month;
	$prev_day   = $curr_day;
	$prev_year  = $curr_year;
	?>
	<div class="comment-user-gravatar left <?php echo esc_attr( $class ); ?>">
		<a href="#" class="th radius"><?php echo get_avatar( $comment->comment_author_email, 40 ); ?></a>
	</div>
	<div class="large-10 columns">
		<div class="row">
			<div class="large-1 small-1 columns rthd_privacy"></div>
			<div class="large-7 small-7 columns">
				<div class="row">
					<div class="comment-user-title large-12 columns">
<?php
$user = get_user_by( 'email', $comment->comment_author_email );
if ( $user ) {
	echo esc_html( $user->display_name );
} else {
	if ( ! empty( $comment->comment_author ) ) {
		echo esc_attr( $comment->comment_author );
	} else {
		echo 'Annonymous';
	}
}
?>
					</div>
					<div class="comment-participant large-12 columns">
<?php
$participants = '';
$to           = get_comment_meta( $comment->comment_ID, '_email_to', true );
if ( ! empty( $to ) ) {
	$participants .= $to;
}
$cc = get_comment_meta( $comment->comment_ID, '_email_cc', true );
if ( ! empty( $cc ) ) {
	$participants .= ',' . $cc;
}
$bcc = get_comment_meta( $comment->comment_ID, '_email_bcc', true );
if ( ! empty( $bcc ) ) {
	$participants .= ',' . $bcc;
}

if ( ! empty( $participants ) ) {
	$p_arr        = explode( ',', $participants );
	$p_arr        = array_unique( $p_arr );
	$participants = implode( ' , ', $p_arr );
	echo 'to  ' . esc_html( $participants );
}
?>
					</div>
				</div>
			</div>

			<div class="comment-info large-3 small-3 columns">
				<span class="comment-type"><?php echo esc_html( ucfirst( $comment->comment_type ) ); ?></span>
			</div>
			<div class="large-1 small-1 columns">
				<?php if ( $user_edit ) { ?>
					<a class="folowup-hover" href="#editFollowup" title="Edit"
					   data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>"><?php _e( 'Edit' ); ?></a>
					<a class="folowup-hover delete" href="#deleteFollowup" title="Delete"
					   data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>"><?php _e( 'Delete' ); ?></a>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<div class="comment-wrapper row" id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>">
	<div class="large-8 columns comment-content">
<?php
if ( isset( $comment->comment_content ) && $comment->comment_content != '' ) {
	if ( strpos( '<body', $comment->comment_content ) !== false ) {
		preg_match_all( '/<body[^>]*>(.*?)<\/body>/s', $comment->comment_content, $output_array );
		if ( count( $output_array ) > 0 ) {
			$comment->comment_content = $output_array[0];
		}
	}
	echo balanceTags( Rt_HD_Utils::force_utf_8( $comment->comment_content ) );
}
?>
	</div>
	<div class="large-3 columns">
<?php
$comment_attechment = get_comment_meta( $comment->comment_ID, 'attachment' );
if ( ! empty( $comment_attechment ) ) {
	?>
	<ul class="comment_attechment large-block-grid-2">
	<?php
	foreach ( $comment_attechment as $commenytAttechment ) {
		$extn_array = explode( '.', $commenytAttechment );
		$extn       = $extn_array[ count( $extn_array ) - 1 ];
	
		$file_array = explode( '/', $commenytAttechment );
		$fileName   = $file_array[ count( $file_array ) - 1 ];
		?>
		<li>
			<a href="<?php echo esc_url( $commenytAttechment ); ?>" title="Attachment"> <img
					src="<?php echo esc_url( RT_HD_URL ) . 'assets/file-type/' . esc_attr( $extn ) . '.png'; ?>"/>
				<span><?php echo esc_html( $fileName ); ?></span> </a>
		</li>
	<?php } ?>
	</ul>
	<?php } ?>
	</div>
</div>
