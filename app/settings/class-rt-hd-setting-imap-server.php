<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 15/9/14
 * Time: 11:55 AM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Zend\Mail\Storage\Imap as ImapStorage;

if ( ! class_exists( 'RT_HD_Setting_Imap_Server' ) ) {

	class RT_HD_Setting_Imap_Server {
		function rthd_imap_servers( $field, $value ) {
			global $rt_hd_imap_server_model;
			$servers = $rt_hd_imap_server_model->get_all_servers();
			?>
			<table>
				<tbody>
				<?php foreach ( $servers as $server ) { ?>
					<tr valign="top">
						<th scope="row"><?php echo esc_html( $server->server_name ); ?></th>
						<td>
							<a href="#" class="rthd-edit-server"
							   data-server-id="<?php echo esc_attr( $server->id ); ?>"><?php _e( 'Edit' ); ?></a> <a href="#"
							                                                                                         class="rthd-remove-server"
							                                                                                         data-server-id="<?php echo esc_attr( $server->id ); ?>"><?php _e( 'Remove' ); ?></a>
						</td>
					</tr>
					<tr valign="top" id="rthd_imap_server_<?php echo esc_attr( $server->id ); ?>" class="rthd-hide-row">
						<td>
							<table>
								<tr valign="top">
									<th scope="row"><?php _e( 'Server Name: ' ); ?></th>
									<td><input type="text" required="required"
									           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][server_name]"
									           value="<?php echo esc_attr( $server->server_name ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Server: ' ); ?></th>
									<td><input type="text" required="required"
									           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][incoming_imap_server]"
									           value="<?php echo esc_attr( $server->incoming_imap_server ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Port: ' ); ?></th>
									<td><input type="text" required="required"
									           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][incoming_imap_port]"
									           value="<?php echo esc_attr( $server->incoming_imap_port ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'IMAP (Incoming) Encryption: ' ); ?></th>
									<td>
										<select
											name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][incoming_imap_enc]">
											<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
											<option
												value="ssl" <?php echo esc_html( ( $server->incoming_imap_enc == 'ssl' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'SSL' ); ?></option>
											<option
												value="tls" <?php echo esc_html( ( $server->incoming_imap_enc == 'tls' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'TLS' ); ?></option>
										</select>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Server: ' ); ?></th>
									<td><input type="text" required="required"
									           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][outgoing_smtp_server]"
									           value="<?php echo esc_attr( $server->outgoing_smtp_server ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Port: ' ); ?></th>
									<td><input type="text" required="required"
									           name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][outgoing_smtp_port]"
									           value="<?php echo esc_attr( $server->outgoing_smtp_port ); ?>"/></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'SMTP (Outgoing) Encryption: ' ); ?></th>
									<td>
										<select
											name="rthd_imap_servers[<?php echo esc_attr( $server->id ); ?>][outgoing_smtp_enc]">
											<option value=""><?php _e( 'Select Encryption Method' ); ?></option>
											<option
												value="ssl" <?php echo esc_html( ( $server->outgoing_smtp_enc == 'ssl' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'SSL' ); ?></option>
											<option
												value="tls" <?php echo esc_html( ( $server->outgoing_smtp_enc == 'tls' ) ? 'selected="selected"' : '' ); ?>><?php _e( 'TLS' ); ?></option>
										</select>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				<?php } ?>
				<input type="hidden" name="rthd_imap_servers_changed" value="1"/>
				<tr valign="top">
					<th scope="row"><a href="#" class="button" id="rthd_add_imap_server"><?php _e( 'Add new server' ); ?></a>
					</th>
				</tr>
				<tr valign="top" id="rthd_new_imap_server" class="rthd-hide-row">
					<td>
						<table>
							<tr valign="top">
								<th scope="row"><?php _e( 'Server Name: ' ); ?></th>
								<td><input type="text" name="rthd_imap_servers[new][server_name]"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'IMAP (Incoming) Server: ' ); ?></th>
								<td><input type="text" name="rthd_imap_servers[new][incoming_imap_server]"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'IMAP (Incoming) Port: ' ); ?></th>
								<td><input type="text" name="rthd_imap_servers[new][incoming_imap_port]"/></td>
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
								<td><input type="text" name="rthd_imap_servers[new][outgoing_smtp_server]"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'SMTP (Outgoing) Port: ' ); ?></th>
								<td><input type="text" name="rthd_imap_servers[new][outgoing_smtp_port]"/></td>
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
				</tbody>
			</table>
		<?php
		}
	}
}