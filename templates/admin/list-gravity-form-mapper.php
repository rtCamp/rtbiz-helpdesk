<div class="wrap rtcrm-container">
	<h2>
		<?php echo _e('CRM Gravity Mappings'); ?>
	</h2>
	<table class="wp-list-table widefat rtcrm-gravity-mapping" cellspacing="0">
		<thead>
			<tr>
				<th scope='col' id='rtcrm_form_name' class='manage-column column-rtcrm_form_name'  style=""><span>Form</span><span class="sorting-indicator"></span></th>
				<th scope='col' id='rtcrm_create_date' class='manage-column column-rtcrm_create_date'  style=""><span>Create Date</span></th>
				<th scope='col' id='rtcrm_enable' class='manage-column column-rtcrm_enable'  style=""><span>Enable\Disable</span></th>
				<th scope='col' id='rtcrm_delete' class='manage-column column-rtcrm_delete'  style=""><span>Delete</span></th>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<th scope='col' id='rtcrm_form_name' class='manage-column column-rtcrm_form_name'  style=""><span>Form</span><span class="sorting-indicator"></span></th>
				<th scope='col' id='rtcrm_create_date' class='manage-column column-rtcrm_create_date'  style=""><span>Create Date</span></th>
				<th scope='col' id='rtcrm_enable' class='manage-column column-rtcrm_enable'  style=""><span>Enable\Disable</span></th>
				<th scope='col' id='rtcrm_delete' class='manage-column column-rtcrm_delete'  style=""><span>Delete</span></th>
			</tr>
		</tfoot>

		<tbody id="the-list" data-wp-lists='list:lead'>
		<?php if( isset($gravity_fields) && count($gravity_fields)>0   ){ ?>
			<?php foreach($gravity_fields as $gravity_field) { ?>
			<tr id="mapping_<?php echo $gravity_field->id; ?>" class="">
				<td class='rtcrm_form_name column-rtcrm_form_name'><?php echo $gravity_field->form_name; ?></td>
				<td class='rtcrm_create_date column-rtcrm_create_date'><?php echo $gravity_field->create_date; ?></td>
				<td class='rtcrm_enable column-rtcrm_enable aligncenter'><input id="cb-select-<?php echo $gravity_field->id; ?>" class="rtcrm_enable_mapping" type="checkbox"  <?php echo isset( $gravity_field->enable ) && $gravity_field->enable == 'yes' ? 'checked="checked"' : ''?>  data-mapping-id="<?php echo $gravity_field->id; ?>" value="yes" /></td>
				<td class='rtcrm_delete column-rtcrm_delete aligncenter'><a id="rtcrm-delete--<?php echo $gravity_field->id; ?>" class="rtcrm_delete_mapping" style="color: red; cursor: pointer" data-mapping-id="<?php echo $gravity_field->id; ?>" >X</a></td>
			</tr>
		<?php }
		}else{?>
		<tr><td colspan="5"><?php echo _e('No Mapping Found!') ?></td></tr>
		<?php } ?>
		</tbody>
	</table>
</div>
