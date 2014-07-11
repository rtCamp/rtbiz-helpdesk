<?php
	$leadsTable = new Rt_CRM_Leads_List_View();
	$leadsTable->prepare_items();
?>
<?php screen_icon(); ?>
<div class="wrap">
	<h2>
		<?php echo $labels['all_items']; ?>
		<a href="<?php echo admin_url( 'edit.php?post_type='.$post_type.'&page=rtcrm-add-'.$post_type ); ?>" class="add-new-h2"><?php _e( 'Add new' ); ?></a>
	</h2>
	<?php $leadsTable->views(); ?>
	<form method="post">
	<?php $leadsTable->display(); ?>
	</form>
</div>