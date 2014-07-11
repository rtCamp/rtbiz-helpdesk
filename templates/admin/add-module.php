<?php

/*
 * add-module Template
 *
 * @author udit
 */
?>
<div class="rtcrm-container">
	<form method="post">
		<div class="row">
			<div class="large-9 columns">
				<h4><i class="gen-enclosed foundicon-add-doc"></i> <?php _e('Add Module'); ?></h4>
			</div>
			<div class="large-3 columns">
				<input type="submit" class="right button button-primary button-large" value="<?php _e('Save Module'); ?>" />
			</div>
		</div>
		<div class="row">
			<fieldset>
				<div class="row">
					<div class="large-6 columns">
						<div class="row">
							<div class="large-6 columns">
								<label><?php _e('Module Name :'); ?></label>
								<input type="text" name="rtcrm-module-name" id="rtcrm-module-name" placeholder="CRM, HRM, Helpdesk etc." />
							</div>
							<div class="large-6 columns">
								<label><?php _e('Entity :'); ?></label>
								<input type="text" name="rtcrm-entity-name" id="rtcrm-entity-name" placeholder="Lead, Ticket etc." />
							</div>
							<div class="large-2 columns right">
								<input type="button" class="right" id="rtcrm-adv-module-toggle" value="<?php _e('Advanced'); ?>" />
							</div>
						</div>
						<div id="rtcrm-adv-module-div" class="row hide">
							<div class="large-6 columns">
								<label><?php _e('Singular Name :'); ?></label>
								<input type="text" name="rtcrm-singular-name" id="rtcrm-singular-name" />
								<label><?php _e('Add New Item Label :'); ?></label>
								<input type="text" name="rtcrm-add-new-item-label" id="rtcrm-add-new-item-label" />
								<label><?php _e('Edit Item Label :'); ?></label>
								<input type="text" name="rtcrm-edit-item-label" id="rtcrm-edit-item-label" />
							</div>
							<div class="large-6 columns">
								<label><?php _e('Menu Name :'); ?></label>
								<input type="text" name="rtcrm-menu-name" id="rtcrm-menu-name" />
								<label><?php _e('Add New Label :'); ?></label>
								<input type="text" name="rtcrm-add-new-label" id="rtcrm-add-new-label" />
								<label><?php _e('All Items Label :'); ?></label>
								<input type="text" name="rtcrm-all-items-label" id="rtcrm-all-items-label" />
							</div>
						</div>
					</div>
					<div class="large-6 columns">
						<h6><?php _e('Attributes'); ?></h6>
						<div></div>
					</div>
				</div>
			</fieldset>
		</div>
	</form>
</div>