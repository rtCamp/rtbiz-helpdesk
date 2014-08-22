<?php 
        global $rt_hd_module;
        
	if (!isset( $_REQUEST['type'] )) {
            $_REQUEST['type'] = 'gravity';
	}
?>
<ul class="subsubsub">
    <li><a href="<?php echo admin_url("edit.php?post_type=".Rt_HD_Module::$post_type."&page=rthd-settings&type=gravity"); ?>" <?php if ($_REQUEST["type"] == "gravity") echo " class='current'"; ?>>Gravity</a></li>
    					
</ul>
<table class="wp-list-table widefat rthd-gravity-mapping" cellspacing="0">
		<thead>
			<tr>
				<th scope='col' id='rthd_form_name' class='manage-column column-rthd_form_name'  style=""><span>Form</span><span class="sorting-indicator"></span></th>
				<th scope='col' id='rthd_create_date' class='manage-column column-rthd_create_date'  style=""><span>Create Date</span></th>
				<th scope='col' id='rthd_enable' class='manage-column column-rthd_enable'  style=""><span>Enable\Disable</span></th>
				<th scope='col' id='rthd_delete' class='manage-column column-rthd_delete'  style=""><span>Delete</span></th>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<th scope='col' id='rthd_form_name' class='manage-column column-rthd_form_name'  style=""><span>Form</span><span class="sorting-indicator"></span></th>
				<th scope='col' id='rthd_create_date' class='manage-column column-rthd_create_date'  style=""><span>Create Date</span></th>
				<th scope='col' id='rthd_enable' class='manage-column column-rthd_enable'  style=""><span>Enable\Disable</span></th>
				<th scope='col' id='rthd_delete' class='manage-column column-rthd_delete'  style=""><span>Delete</span></th>
			</tr>
		</tfoot>

		<tbody id="the-list" data-wp-lists='list:ticket'>
		<?php if( isset($gravity_fields) && count($gravity_fields)>0   ){ ?>
			<?php foreach($gravity_fields as $gravity_field) { ?>
			<tr id="mapping_<?php echo $gravity_field->id; ?>" class="">
				<td class='rthd_form_name column-rthd_form_name'><?php echo $gravity_field->form_name; ?></td>
				<td class='rthd_create_date column-rthd_create_date'><?php echo $gravity_field->create_date; ?></td>
				<td class='rthd_enable column-rthd_enable aligncenter'><input id="cb-select-<?php echo $gravity_field->id; ?>" class="rthd_enable_mapping" type="checkbox"  <?php echo isset( $gravity_field->enable ) && $gravity_field->enable == 'yes' ? 'checked="checked"' : ''?>  data-mapping-id="<?php echo $gravity_field->id; ?>" value="yes" /></td>
				<td class='rthd_delete column-rthd_delete aligncenter'><a id="rthd-delete--<?php echo $gravity_field->id; ?>" class="rthd_delete_mapping" style="color: red; cursor: pointer" data-mapping-id="<?php echo $gravity_field->id; ?>" >X</a></td>
			</tr>
		<?php }
		}else{?>
		<tr><td colspan="5"><?php echo _e('No Mapping Found!') ?></td></tr>
		<?php } ?>
		</tbody>
	</table>
