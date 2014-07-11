<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Zend\Mail\Storage\Imap as ImapStorage;

if (!isset($_REQUEST["type"])) {
	$_REQUEST["type"] = "personal";
}

if ( $_REQUEST["type"] == "personal" && isset( $_POST["mail_ac"] ) && is_email( $_POST["mail_ac"] ) ) {
	if ( isset( $_POST["allow_users"] ) ) {
		$allow_users=$_POST["allow_users"];
	} else {
		$allow_users=array();
	}
	if ( isset( $_POST['imap_password'] ) ) {
		$token = rtcrm_encrypt_decrypt( $_POST['imap_password'] );
	} else {
		$token = NULL;
	}
	if ( isset( $_POST['imap_server'] ) ) {
		$imap_server = $_POST['imap_server'];
	} else {
		$imap_server = NULL;
	}
	$email_ac = $rt_crm_settings->get_email_acc( $_POST['mail_ac'] );
	$email_data = NULL;
	if ( isset( $_POST['mail_folders'] ) && ! empty( $_POST['mail_folders'] ) && is_array( $_POST['mail_folders'] ) && ! empty( $email_ac ) ) {
		$email_data = maybe_unserialize( $email_ac->email_data );
		$email_data['mail_folders'] = implode( ',', $_POST['mail_folders'] );
	}
	if ( isset( $_POST['inbox_folder'] ) && ! empty( $_POST['inbox_folder'] ) && ! empty( $email_ac ) ) {
		if ( is_null( $email_data ) ) {
			$email_data = maybe_unserialize( $email_ac->email_data );
		}
		$email_data['inbox_folder'] = $_POST['inbox_folder'];
	}
	$rt_crm_settings->update_mail_acl( $_POST["mail_ac"], $token, maybe_serialize( $email_data ), $allow_users, $_POST["emailsignature"], $imap_server );
}
if ( $_REQUEST["type"] == "personal" && isset( $_REQUEST["email"] ) && is_email( $_REQUEST["email"] ) ) {
	$rt_crm_settings->delete_user_google_ac( $_REQUEST["email"] );
	echo '<script>window.location="'.add_query_arg( array( 'post_type' => $rt_crm_module->post_type, 'page' => 'rtcrm-user-settings', 'type' => 'personal' ), admin_url( 'edit.php' ) ).'";</script>';
	die();
}

$flag = false;
if(isset($_POST["rtcrm_activecollab_token"])){
	//update_site_option('rtcrm_activecollab_token',$_POST["rtcrm_activecollab_token"]);
	update_user_meta(get_current_user_id(), "rtcrm_activecollab_token", $_POST["rtcrm_activecollab_token"]);
	$flag = true;
}
if(isset($_POST["rtcrm_activecollab_default_project"])){
	//update_site_option('rtcrm_activecollab_default_project',$_POST["rtcrm_activecollab_default_project"]);
	update_user_meta(get_current_user_id(), "rtcrm_activecollab_default_project", $_POST["rtcrm_activecollab_default_project"]);
	$flag = true;
}
if ( $flag ) {
	echo '<script>window.location="'.add_query_arg( array( 'post_type' => $rt_crm_module->post_type, 'page' => 'rtcrm-user-settings', 'type' => 'activecollab' ), admin_url( 'edit.php' ) ).'";</script>';
	die();
}

$rt_crm_settings->update_gmail_ac_count();
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div><h2><?php _e('User Settings'); ?></h2>
	<ul class="subsubsub">
		<li><a href="<?php echo admin_url("edit.php?post_type=rt_lead&page=rtcrm-user-settings&type=personal"); ?>" <?php if ($_REQUEST["type"] == "personal") echo " class='current'"; ?> ><?php _e('Personal Emails'); ?></a> | </li>
		<li><a href="<?php echo admin_url("edit.php?post_type=rt_lead&page=rtcrm-user-settings&type=activecollab"); ?>" <?php if ($_REQUEST["type"] == "activecollab") echo " class='current'"; ?> ><?php _e('Active Collab'); ?></a></li>
	</ul>

	<?php if ( $_REQUEST['type'] != 'personal' ) { ?>
	<form method="post">
	<?php } ?>
	<table class="form-table crm-option">
		<tbody>
<?php
if ($_REQUEST['type'] == 'personal') {
	$redirect_url = get_site_option('rtcrm_googleapi_redirecturl');
	if (!$redirect_url) {
		$redirect_url = admin_url("edit.php?post_type=rt_lead&page=rtcrm-user-settings&type=personal");
		update_site_option("rtcrm_googleapi_redirecturl", $redirect_url);
	}
	$google_client_id = get_site_option('rtcrm_googleapi_clientid', "");
	$google_client_secret = get_site_option('rtcrm_googleapi_clientsecret', "");
	$google_client_redirect_url = get_site_option('rtcrm_googleapi_redirecturl', "");
	if ($google_client_id == "" || $google_client_secret == "") {
		echo '<tr valign="top"><td><div class="error"><p>Please set google api detail on Google API <a href="' . admin_url("edit.php?post_type=rt_lead&page=rtcrm-settings&type=googleApi") . '">setting</a> page </p></div></td></tr>';
		return;
	}
	include_once RT_CRM_PATH_VENDOR . 'google-api-php-client/Google_Client.php';
	include_once RT_CRM_PATH_VENDOR . 'google-api-php-client/contrib/Google_Oauth2Service.php';

	$client = new Google_Client();
	$client->setApplicationName("CRM Studio");
	$client->setClientId($google_client_id);
	$client->setClientSecret($google_client_secret);
	$client->setRedirectUri($google_client_redirect_url);
	$client->setScopes(array('https://mail.google.com/', 'https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile'));
	$client->setAccessType("offline");
	$oauth2 = new Google_Oauth2Service($client);
	$user_id = get_current_user_id();

	if ( isset( $_GET['code'] ) ) {
		$client->authenticate();
		$user = $oauth2->userinfo_v2_me->get();
		$email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
		$rt_crm_settings->add_user_google_ac($client->getAccessToken(), $email, serialize($user), $user_id);
		wp_redirect($google_client_redirect_url);
	}

	if ( isset( $_REQUEST['rtcrm_add_imap_email'] ) ) {
		if ( isset( $_POST['rtcrm_imap_user_email'] ) && ! empty( $_POST['rtcrm_imap_user_email'] )
				&& isset( $_POST['rtcrm_imap_user_pwd'] ) && ! empty( $_POST['rtcrm_imap_user_pwd'] )
				&& isset( $_POST['rtcrm_imap_server'] ) && ! empty( $_POST['rtcrm_imap_server'] ) ) {
			$password = $_POST['rtcrm_imap_user_pwd'];
			$email = $_POST['rtcrm_imap_user_email'];
			$email_data = array(
				'email' => $email,
			);
			$imap_server = $_POST['rtcrm_imap_server'];
			$rt_crm_settings->add_user_google_ac( rtcrm_encrypt_decrypt( $password ), $email, maybe_serialize( $email_data ), $user_id, 'imap', $imap_server );
		}
	}

	$google_acs = $rt_crm_settings->get_user_google_ac($user_id);
	$authUrl = $client->createAuthUrl();

	$results = Rt_CRM_Utils::get_crm_rtcamp_user();
	$arrSubscriberUser = array();
	foreach ( $results as $author ) {
		$arrSubscriberUser[] = array("id" => $author->ID, "label" => $author->display_name, "imghtml" => get_avatar($author->user_email, 25));
	}
	echo "<script> var arr_rtcamper=" . json_encode($arrSubscriberUser) . "; </script>";
	$rCount = 0;
	foreach ($google_acs as $ac) {
		$rCount++;
		$ac->email_data = unserialize($ac->email_data);
		$email = filter_var($ac->email_data['email'], FILTER_SANITIZE_EMAIL);
		$email_type = $ac->type;
		$imap_server = $ac->imap_server;
		$mail_folders = ( isset( $ac->email_data['mail_folders'] ) ) ? $ac->email_data['mail_folders'] : '';
		$mail_folders = array_filter( explode( ',', $mail_folders ) );
		$inbox_folder = ( isset( $ac->email_data['inbox_folder'] ) ) ? $ac->email_data['inbox_folder'] : '';

		if ( $ac->type == 'goauth' ) {
			$token = json_decode($ac->outh_token);
			$client->setAccessToken($ac->outh_token);
			if ($client->isAccessTokenExpired()) {
				$client->refreshToken($token->refresh_token);
				$user = $oauth2->userinfo_v2_me->get();
				$email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
				if ( isset( $ac->email_data['inbox_folder'] ) ) {
					$user['inbox_folder'] = $ac->email_data['inbox_folder'];
				}
				if ( isset( $ac->email_data['mail_folders'] ) ) {
					$user['mail_folders'] = $ac->email_data['mail_folders'];
				}
				$rt_crm_settings->update_user_google_ac($client->getAccessToken(), $email, serialize($user));
				$ac->email_data = $user;
				$token = json_decode($client->getAccessToken());
			}
			$token = $token->access_token;
		} else {
			$token = $ac->outh_token;
		}

		if (isset($ac->email_data['picture'])) {
			$img = filter_var($ac->email_data['picture'], FILTER_VALIDATE_URL);
			$personMarkup = "<img src='$img?sz=96'>";
		} else {
			$personMarkup = get_avatar($email, 96);
		}
		?>
					<tr>
						<td>
							<form method="post" action="<?php echo admin_url("edit.php?post_type=rt_lead&page=rtcrm-user-settings&type=personal"); ?>">
								<input type="hidden" name='mail_ac' value="<?php echo $email; ?>" />
								<table class='crm-google-profile-table'>
									<tr valign="top">
										<th scope="row" ><label><?php echo $personMarkup; ?></label></th>
										<th scope="row"><label>Allow user</label></th>
										<td class="long"><input type='text' name='mail_acl_<?php echo $rCount; ?>' class='rtcamp-user-ac' />
		<?php
		$mail_users_acl = $rt_crm_settings->get_email_acl($email);
		foreach ($mail_users_acl as $acl) {
			echo "<div class='mail-acl_user' acl-user='" . $acl->allow_user . "' id='mail_acl_" . $rCount . '_' . $acl->allow_user . "'>&nbsp;&nbsp;
										<a href='#removeAccess'>X</a><input type='hidden' name='allow_users[]' value='" . $acl->allow_user . "' /></div>";
		}
		?>
										</td>
									</tr>
									<tr valign="top">
										<td><?php if ( isset( $ac->email_data['name'] ) ) { echo $ac->email_data['name']; } ?> <br/><a href='mailto:<?php echo $email ?>'><?php echo $email ?></a></td>
										<th scope="row">Signature</th><td class="long">
											<textarea name='emailsignature' id='emailsignature'><?php echo isset($ac->signature) ? $ac->signature : ''; ?></textarea>
										</td>
									</tr>
									<?php if( $ac->type == 'imap' ) { ?>
									<tr valign="top">
										<td></td>
										<th scope="row"><label><?php _e( 'IMAP Server' ); ?></label></th>
										<td class="long">
											<select required="required" name="imap_server">
												<option value=""><?php _e( 'Select Mail Server' ); ?></option>
												<?php
												$imap_servers = $rt_crm_imap_server_model->get_all_servers();
												foreach ( $imap_servers as $server ) { ?>
												<option <?php echo ( isset( $ac->imap_server ) && $ac->imap_server == $server->id ) ? 'selected="selected"' : ''; ?> value="<?php echo $server->id; ?>"><?php echo $server->server_name; ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr valign="top">
										<td></td>
										<th scope="row"><label><?php _e( 'Password' ); ?></label></th>
										<td class="long"><input required="required" type="password" name="imap_password" placeholder="Password" value="<?php echo rtcrm_encrypt_decrypt( $token ); ?>" /></td>
									</tr>
									<?php }
									$all_folders = NULL;
									try {
										$crmZendEmail = new Rt_CRM_Zend_Mail();
										if ( $crmZendEmail->tryImapLogin( $email, $token, $email_type, $imap_server ) ) {
											$storage = new ImapStorage($crmZendEmail->imap);
											$all_folders = $storage->getFolders();
										}
									} catch( Exception $e ) {
										echo '<tr valign="top"><td></td><td></td><td><p class="description">'.$e->getMessage().'</p></td></tr>';
									}
									?>
									<tr valign="top">
										<td></td>
										<th scope="row"><label><?php _e( 'Mail Folders to read' ); ?></label></th>
										<td>
											<label>
												<?php _e( 'Inbox Folder' ); ?>
												<select data-email-id="<?php echo $ac->id; ?>" name="inbox_folder" required="required" data-prev-value="<?php echo $inbox_folder; ?>">
													<option value=""><?php _e( 'Choose Inbox Folder' ); ?></option>
													<?php if ( ! is_null( $all_folders ) ) {
														$crmZendEmail->render_folders_dropdown( $all_folders, $value = $inbox_folder );
													} ?>
												</select>
											</label>
											<?php if ( in_array( $email, rtcrm_get_all_system_emails() ) ) { ?>
											<p class="description"><?php _e( 'This is linked as a system mail. Hence it will only read the Inbox Folder; no matter what folder you choose over here. These will be ignored.' ); ?></p>
											<?php } ?>
										<?php
											if ( ! is_null( $all_folders ) ) {
												$crmZendEmail->render_folders_checkbox( $all_folders, $element_name = 'mail_folders', $values = $mail_folders, $data_str = 'data-email-id='.$ac->id, $inbox_folder );
											} else {
												echo '<p class="description">'.__( 'No Folders found.' ).'</p>';
											}
										?>
										</td>
									</tr>
									<tr valign="top">
										<td></td>
										<th scope="row"><label></label></th>
										<td>
											<button class='button' type='submit'>Save Changes</button>
											<?php if ( $ac->type == 'goauth' ) { ?>
											<a class='button button-primary' href='<?php echo $authUrl; ?>'>Re Connect Google Now</a>
											<?php } ?>
											<a class='button remove-google-ac' href='<?php echo admin_url("edit.php?post_type=rt_lead&page=rtcrm-user-settings&type=personal&email=" . $email); ?>'>Remove A/C</a>
										</td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
	<?php } ?>
		<script>
			jQuery(document).ready(function($){
				$(document).on('change','select[name=inbox_folder]',function(e){
					e.preventDefault();
					inbox = $(this).val();
					prev_value = $(this).data('prev-value');
					$(this).data('prev-value',inbox);
					email_id = $(this).data('email-id');
					$('input[data-email-id="'+email_id+'"][value="'+inbox+'"]').parent().css('display','none');
					$('input[data-email-id="'+email_id+'"][value="'+prev_value+'"]').parent().css('display','inline');
				});
			});
		</script>
			<?php } else if ( $_REQUEST['type'] == 'activecollab' ) { ?>
			<tr valign="top">
				<th scope="row"><label for="rtcrm_wellcome_text">Active Collab Token</label></th>
				<td><input type="text" name="rtcrm_activecollab_token" value="<?php echo get_user_meta(get_current_user_id(), 'rtcrm_activecollab_token',true); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="rtcrm_wellcome_text">Default Project ID</label></th>
				<td><input type="text" name="rtcrm_activecollab_default_project" value="<?php echo get_user_meta(get_current_user_id(), 'rtcrm_activecollab_default_project',true); ?>" /></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php if ( $_REQUEST['type'] != 'personal' ) { ?>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
	</form>
	<?php } else { ?>
		<p class="submit">
			<a class="button" id="rtcrm_add_personal_email" href="#"><?php _e( 'Add Email' ); ?></a>
		</p>
		<p class="submit rtcrm-hide-row" id="rtcrm_email_acc_type_container">
			<select id="rtcrm_select_email_acc_type">
				<option value=""><?php _e( 'Select Type' ); ?></option>
				<option value="goauth"><?php _e( 'Google OAuth App' ); ?></option>
				<option value="imap"><?php _e( 'IMAP' ); ?></option>
			</select>
		</p>
		<p class="submit rtcrm-hide-row" id="rtcrm_goauth_container">
			<a class='button button-primary' href='<?php echo $authUrl;?>'><?php _e( 'Connect New Google A/C' ); ?></a>
		</p>
		<form id="rtcrm_add_imap_acc_form" class="rtcrm-hide-row" method="post" action="<?php echo admin_url("edit.php?post_type=rt_lead&page=rtcrm-user-settings&type=personal"); ?>">
			<input type="hidden" name="rtcrm_add_imap_email" value="1" />
			<select required="required" name="rtcrm_imap_server">
				<option value=""><?php _e( 'Select Mail Server' ); ?></option>
				<?php
				$imap_servers = $rt_crm_imap_server_model->get_all_servers();
				foreach ( $imap_servers as $server ) { ?>
				<option value="<?php echo $server->id; ?>"><?php echo $server->server_name; ?></option>
				<?php } ?>
			</select>
			<input type="email" required="required" name="rtcrm_imap_user_email" placeholder="Email" />
			<input type="password" required="required" name="rtcrm_imap_user_pwd" placeholder="Password" />
			<button class="button button-primary" type="submit"><?php _e( 'Save' ); ?></button>
		</form>
	<?php } ?>
</div>