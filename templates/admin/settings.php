<?php
/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/
global $rt_hd_module_model, $rt_hd_module;

$module_settings = rthd_get_settings();
$flag = false;
$slug = str_replace( '-', '_', sanitize_title( strtolower( $rt_hd_module->name ) ) );
if ( isset( $_POST[$slug."_system_email"] ) ) {
	$module_settings['system_email'] = $_POST[$slug."_system_email"];
	$flag = true;
}
if ( isset( $_POST[$slug.'_outbound_emails'] ) ) {
	$module_settings['outbound_emails'] = $_POST[$slug."_outbound_emails"];
	$flag = true;
}
if ( $flag ) {
	update_site_option( 'rt_hd_settings', $module_settings );
	echo '<script>window.location="'.add_query_arg( array( 'post_type' => $rt_hd_module->post_type, 'page' => 'rthd-settings', 'type' => 'systememails' ), admin_url( 'edit.php' ) ).'";</script>';
	die();
}

if(isset($_POST["rthd_googleapi_clientid"])){
	update_site_option('rthd_googleapi_clientid',$_POST["rthd_googleapi_clientid"]);
	$flag = true;
}
if(isset($_POST["rthd_googleapi_clientsecret"])){
	update_site_option('rthd_googleapi_clientsecret',$_POST["rthd_googleapi_clientsecret"]);
	$flag = true;
}
if ( $flag ) {
	echo '<script>window.location="'.add_query_arg( array( 'post_type' => $rt_hd_module->post_type, 'page' => 'rthd-settings', 'type' => 'googleApi' ), admin_url( 'edit.php' ) ).'";</script>';
	die();
}

if(isset($_POST["rthd_wellcome_text"])){
	update_site_option('rthd_wellcome_text',$_POST["rthd_wellcome_text"]);
	$flag = true;
}
if ( isset( $_POST['rthd_logo_url'] ) ) {
	rthd_update_logo_url( $_POST['rthd_logo_url'] );
	$flag = true;
}
if ( isset( $_POST['rthd_menu_label'] ) ) {
	rthd_update_menu_label( $_POST['rthd_menu_label'] );
	$flag = true;
}
if ( $flag ) {
	echo '<script>window.location="'.add_query_arg( array( 'post_type' => $rt_hd_module->post_type, 'page' => 'rthd-settings', 'type' => 'other' ), admin_url( 'edit.php' ) ).'";</script>';
	die();
}

if ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] == 'imapServers' ) {

	if ( isset( $_POST['rthd_imap_servers_changed'] ) ) {

		global $rt_hd_imap_server_model;
		$old_servers = $rt_hd_imap_server_model->get_all_servers();

		if ( isset( $_POST['rthd_imap_servers'] ) ) {
			$new_servers = $_POST['rthd_imap_servers'];

			// Handle / Update Existing Servers
			foreach ( $old_servers as $id => $server ) {
				if ( isset( $new_servers[$server->id] ) ) {
					if ( empty( $new_servers[$server->id]['server_name'] )
							|| empty( $new_servers[$server->id]['incoming_imap_server'] )
							|| empty( $new_servers[$server->id]['incoming_imap_port'] )
							|| empty( $new_servers[$server->id]['outgoing_smtp_server'] )
							|| empty( $new_servers[$server->id]['outgoing_smtp_port'] ) ) {
						continue;
					}
					$args = array(
						'server_name' => $new_servers[$server->id]['server_name'],
						'incoming_imap_server' => $new_servers[$server->id]['incoming_imap_server'],
						'incoming_imap_port' => $new_servers[$server->id]['incoming_imap_port'],
						'incoming_imap_enc' => ( isset( $new_servers[$server->id]['incoming_imap_enc'] ) && ! empty( $new_servers[$server->id]['incoming_imap_enc'] ) ) ? $new_servers[$server->id]['incoming_imap_enc'] : NULL,
						'outgoing_smtp_server' => $new_servers[$server->id]['outgoing_smtp_server'],
						'outgoing_smtp_port' => $new_servers[$server->id]['outgoing_smtp_port'],
						'outgoing_smtp_enc' => ( isset( $new_servers[$server->id]['outgoing_smtp_enc'] ) && ! empty( $new_servers[$server->id]['outgoing_smtp_enc'] ) ) ? $new_servers[$server->id]['outgoing_smtp_enc'] : NULL,
					);
					$rt_hd_imap_server_model->update_server( $args, $server->id );
				} else {
					$rt_hd_imap_server_model->delete_server( $server->id );
				}
			}

			// New Server in the list
			if ( ! empty( $new_servers['new']['server_name'] )
					&& ! empty( $new_servers['new']['incoming_imap_server'] )
					&& ! empty( $new_servers['new']['incoming_imap_port'] )
					&& ! empty( $new_servers['new']['outgoing_smtp_server'] )
					&& ! empty( $new_servers['new']['outgoing_smtp_port'] ) ) {

				$args = array(
					'server_name' => $new_servers['new']['server_name'],
					'incoming_imap_server' => $new_servers['new']['incoming_imap_server'],
					'incoming_imap_port' => $new_servers['new']['incoming_imap_port'],
					'incoming_imap_enc' => ( isset( $new_servers[$server->id]['incoming_imap_enc'] ) && ! empty( $new_servers[$server->id]['incoming_imap_enc'] ) ) ? $new_servers[$server->id]['incoming_imap_enc'] : NULL,
					'outgoing_smtp_server' => $new_servers['new']['outgoing_smtp_server'],
					'outgoing_smtp_port' => $new_servers['new']['outgoing_smtp_port'],
					'outgoing_smtp_enc' => ( isset( $new_servers[$server->id]['outgoing_smtp_enc'] ) && ! empty( $new_servers[$server->id]['outgoing_smtp_enc'] ) ) ? $new_servers[$server->id]['outgoing_smtp_enc'] : NULL,
				);
				$rt_hd_imap_server_model->add_server( $args );
			}
		} else {
			foreach ( $old_servers as $server ) {
				$rt_hd_imap_server_model->delete_server( $server->id );
			}
		}
		echo '<script>window.location="'.add_query_arg( array( 'post_type' => $rt_hd_module->post_type, 'page' => 'rthd-settings', 'type' => 'imapServers' ), admin_url( 'edit.php' ) ).'";</script>';
		die();
	}
}

if(!isset($_REQUEST["type"])){
	$_REQUEST["type"]="googleApi";
}

?>

       	<ul class="subsubsub">
		<li><a href="<?php echo admin_url("edit.php?post_type=$rt_hd_module->post_type&page=rthd-settings&type=googleApi&tab=admin-settings");?>" <?php if ($_REQUEST["type"] == "googleApi") echo " class='current'"; ?> ><?php _e( 'Google API' ); ?></a> | </li>
		<li><a href="<?php echo admin_url("edit.php?post_type=$rt_hd_module->post_type&page=rthd-settings&type=imapServers&tab=admin-settings");?>" <?php if ($_REQUEST["type"] == "imapServers") echo " class='current'"; ?> ><?php _e( 'IMAP Servers' ); ?></a> | </li>
		<li><a href="<?php echo admin_url("edit.php?post_type=$rt_hd_module->post_type&page=rthd-settings&type=systememails&tab=admin-settings");?>" <?php if ($_REQUEST["type"] == "systememails") echo " class='current'"; ?> ><?php _e( 'System Emails' ); ?></a> | </li>
		<li><a href="<?php echo admin_url("edit.php?post_type=$rt_hd_module->post_type&page=rthd-settings&type=other&tab=admin-settings");?>" <?php if ($_REQUEST["type"] == "other") echo " class='current'"; ?> ><?php _e( 'Other' ); ?></a></li>
	</ul>

	<form method="post" action="<?php  echo admin_url("edit.php?post_type=$rt_hd_module->post_type&page=rthd-settings&type=" . $_REQUEST["type"]); ?>">
		<table class="form-table hd-option">
			<tbody>
				<?php if($_REQUEST["type"] == "googleApi"){
					$redirect_url =get_site_option('rthd_googleapi_redirecturl');

					if(!$redirect_url){
						$redirect_url= admin_url("edit.php?post_type=$rt_hd_module->post_type&page=rthd-settings&tab=my-settings&type=personal");
						update_site_option("rthd_googleapi_redirecturl", $redirect_url);
					}

					?>
					<tr valign="top">
						<th scope="row"><label for="rthd_googleapi_clientid">Google Api Client id</label></th>
							<td><input type="text" name="rthd_googleapi_clientid" id="rthd_googleapi_clientid" value="<?php echo get_site_option('rthd_googleapi_clientid'); ?>" />
							<p class="description">Create an app on <a target="_blank" href="https://code.google.com/apis/console">google api console</a>, set authorized redirect urls to
								<b><?php echo get_site_option('rthd_googleapi_redirecturl'); ?></b>
							</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rthd_googleapi_clientsecret">Google Api Client Secret</label></th>
							<td><input type="text" name="rthd_googleapi_clientsecret" id="rthd_googleapi_clientsecret" value="<?php echo get_site_option('rthd_googleapi_clientsecret'); ?>" /> </td>
					</tr>

					<?php } else if( $_REQUEST['type'] == 'imapServers' ) {
						global $rt_hd_imap_server_model;
						$servers = $rt_hd_imap_server_model->get_all_servers();
						foreach ( $servers as $server ) { ?>
					<tr valign="top">
						<th scope="row"><?php echo $server->server_name; ?></th>
						<td>
							<a href="#" class="rthd-edit-server" data-server-id="<?php echo $server->id; ?>"><?php _e( 'Edit' ); ?></a>
							<a href="#" class="rthd-remove-server" data-server-id="<?php echo $server->id; ?>"><?php _e( 'Remove' ); ?></a>
						</td>
					</tr>
					<tr valign="top" id="rthd_imap_server_<?php echo $server->id; ?>" class="rthd-hide-row">
						<td>
							<table>
								<tr valign="top">
									<th scope="row"><?php _e( 'Server Name: ' ); ?></th>
									<td><input type="text" required="required" name="rthd_imap_servers[<?php echo $server->id; ?>][server_name]" value="<?php echo $server->server_name; ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Server: ' ); ?></th>
									<td><input type="text" required="required" name="rthd_imap_servers[<?php echo $server->id; ?>][incoming_imap_server]" value="<?php echo $server->incoming_imap_server; ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Port: ' ); ?></th>
									<td><input type="text" required="required" name="rthd_imap_servers[<?php echo $server->id; ?>][incoming_imap_port]" value="<?php echo $server->incoming_imap_port; ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Encryption: ' ); ?></th>
									<td>
										<select name="rthd_imap_servers[<?php echo $server->id; ?>][incoming_imap_enc]">
											<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
											<option value="ssl" <?php echo ( $server->incoming_imap_enc == 'ssl' ) ? 'selected="selected"' : ''; ?>><?php _e( 'SSL' ); ?></option>
											<option value="tls" <?php echo ( $server->incoming_imap_enc == 'tls' ) ? 'selected="selected"' : ''; ?>><?php _e( 'TLS' ); ?></option>
										</select>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Server: ' ); ?></th>
									<td><input type="text" required="required" name="rthd_imap_servers[<?php echo $server->id; ?>][outgoing_smtp_server]" value="<?php echo $server->outgoing_smtp_server; ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Port: ' ); ?></th>
									<td><input type="text" required="required" name="rthd_imap_servers[<?php echo $server->id; ?>][outgoing_smtp_port]" value="<?php echo $server->outgoing_smtp_port; ?>" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Encryption: ' ); ?></th>
									<td>
										<select name="rthd_imap_servers[<?php echo $server->id; ?>][outgoing_smtp_enc]">
											<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
											<option value="ssl" <?php echo ( $server->outgoing_smtp_enc == 'ssl' ) ? 'selected="selected"' : ''; ?>><?php _e( 'SSL' ); ?></option>
											<option value="tls" <?php echo ( $server->outgoing_smtp_enc == 'tls' ) ? 'selected="selected"' : ''; ?>><?php _e( 'TLS' ); ?></option>
										</select>
									</td>
								</tr>
							</table>
						</td>
					</tr>
						<?php } ?>
					<input type="hidden" name="rthd_imap_servers_changed" value="1" />
					<tr valign="top">
						<th scope="row"><a href="#" class="button" id="rthd_add_imap_server"><?php _e( 'Add new server' ); ?></a></th>
					</tr>
					<tr valign="top" id="rthd_new_imap_server" class="rthd-hide-row">
						<td>
							<table>
								<tr valign="top">
									<th scope="row"><?php _e( 'Server Name: ' ); ?></th>
									<td><input type="text" name="rthd_imap_servers[new][server_name]" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Server: ' ); ?></th>
									<td><input type="text" name="rthd_imap_servers[new][incoming_imap_server]" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Port: ' ); ?></th>
									<td><input type="text" name="rthd_imap_servers[new][incoming_imap_port]" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Encryption: ' ); ?></th>
									<td>
										<select name="rthd_imap_servers[new][incoming_imap_enc]">
											<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
											<option value="ssl"><?php _e( 'SSL' ); ?></option>
											<option value="tls"><?php _e( 'TLS' ); ?></option>
										</select>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Server: ' ); ?></th>
									<td><input type="text" name="rthd_imap_servers[new][outgoing_smtp_server]" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Port: ' ); ?></th>
									<td><input type="text" name="rthd_imap_servers[new][outgoing_smtp_port]" /></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'Is SSL required for SMTP (Outgoing Mails): ' ); ?></th>
									<td>
										<select name="rthd_imap_servers[new][outgoing_smtp_enc]">
											<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
											<option value="ssl"><?php _e( 'SSL' ); ?></option>
											<option value="tls"><?php _e( 'TLS' ); ?></option>
										</select>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php } elseif ($_REQUEST["type"] == "systememails") {
						$allemails = $rt_hd_settings->get_all_email_address();
						$module_settings = get_site_option( 'rt_hd_settings', false );
						$slug = str_replace( '-', '_', sanitize_title( strtolower( $rt_hd_module->name ) ) );
						?>
						<tr valign="top">
							<th scope="row"><label for="<?php echo $slug; ?>_system_email"><?php echo $rt_hd_module->name.__(' System Email'); ?></label></th>
							<td>
								<select class="rthd_system_emails" id="<?php echo $slug; ?>_system_email" name="<?php echo $slug; ?>_system_email" data-prev-val="<?php echo ( isset( $module_settings['system_email'] ) ) ? $module_settings['system_email'] : ''; ?>">
									<option value=""><?php _e( 'Select Email' ); ?></option>
									<?php foreach ( $allemails as $key => $e ) {
										$selected = '';
										if (  isset( $module_settings['system_email'] ) && $e->email == $module_settings['system_email'] ) {
											$selected = 'selected="selected"';
											unset($allemails[$key]);
										}
									?>
									<option value="<?php echo $e->email; ?>" <?php echo $selected; ?>><?php echo $e->email; ?></option>
									<?php } ?>
								</select>
							</td>
							<th scope="row"><label for="<?php echo $slug; ?>_outbound_emails"><?php echo $rt_hd_module->name.__(' Outbound Emails'); ?></label></th>
							<td>
								<input type="text" id="<?php echo $slug; ?>_outbound_emails" name="<?php echo $slug; ?>_outbound_emails" placeholder="Comma Separated Outbound Emails" value="<?php echo ( isset( $module_settings['outbound_emails'] ) ) ? $module_settings['outbound_emails'] : ''; ?>" />
							</td>
						</tr>
						<script>
							var usedemails = new Array();
							jQuery.each(jQuery('.rthd_system_emails'), function(key, obj) {
								if(jQuery(obj).val() != '')
									usedemails.push(jQuery(obj).val());
							});
							jQuery('.rthd_system_emails').on('change', function(e) {
								var val = jQuery(this).val();
								var that = this;
								jQuery.each(jQuery('.rthd_system_emails'), function(key, obj) {
									if( obj != that ) {
										var children = jQuery(obj).children('option');
										var option = '';
										children.filter(function() {
											return jQuery(this).attr('value') == val;
										}).each(function() {
											option = jQuery(this).attr('value');
										});
										jQuery(obj).children('option').each(function() {
											if(jQuery(this).attr('value') == option && option != '')
												jQuery(this).remove();
										});
										if(jQuery(that).data('prev-val') != '')
											jQuery(obj).append('<option value="'+jQuery(that).data('prev-val')+'">'+jQuery(that).data('prev-val')+'</option>');
									}
								});
								jQuery(this).data('prev-val', val);
							});
						</script>
				<?php } elseif( $_REQUEST["type"] == "other" ) { ?>
					<tr valign="top">
						<th scope="row"><label for="rthd_wellcome_text">Welcome Text HTML</label></th>
						<td><?php wp_editor(get_site_option('rthd_wellcome_text'), "rthd_wellcome_text"); ?></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rthd_menu_label"><?php _e( 'Helpdesk Plugin Menu Label' ); ?></label></th>
						<td><input type="text" name="rthd_menu_label" value="<?php echo rthd_get_menu_label(); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rthd_logo_url"><?php _e( 'Helpdesk Plugin Icon (Logo) URL' ); ?></label></th>
						<td><input type="text" name="rthd_logo_url" value="<?php echo rthd_get_logo_url(); ?>" /></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
	</form>
