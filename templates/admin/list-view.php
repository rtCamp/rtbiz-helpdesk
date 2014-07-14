<?php
	$ticketsTable = new Rt_HD_Tickets_List_View();
	$ticketsTable->prepare_items();
?>
<?php screen_icon(); ?>
<div class="wrap">
	<h2>
		<?php echo $labels['all_items']; ?>
		<a href="<?php echo admin_url( 'edit.php?post_type='.$post_type.'&page=rthd-add-'.$post_type ); ?>" class="add-new-h2"><?php _e( 'Add new' ); ?></a>
	</h2>
	<?php $ticketsTable->views(); ?>
	<form method="post">
	<?php $ticketsTable->display(); ?>
	</form>
</div>