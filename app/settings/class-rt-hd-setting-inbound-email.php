<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 12/9/14
 * Time: 5:10 PM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Zend\Mail\Storage\Imap as ImapStorage;

if ( ! class_exists( 'RT_HD_Setting_Inbound_Email' ) ) {

	/**
	 * Class RT_HD_Setting_Inbound_Email
	 */
	class RT_HD_Setting_Inbound_Email {

		/**
		 * @var string
		 */
		var $user_id = '';
		/**
		 * @var null
		 */
		var $oauth2 = null;
		/**
		 * @var null
		 */
		var $client = null;

		function __construct() {
			add_action( 'init', array( $this, 'save_replay_by_email' ) );
		}


		/**
		 *
		 */
		public function goole_oauth() {
			global $rt_hd_settings, $redux_helpdesk_settings;

			$rt_hd_settings->update_gmail_ac_count();

			//Google Client
			$redirect_url = get_site_option( 'rthd_googleapi_redirecturl' );
			if ( ! $redirect_url ) {
				$redirect_url = admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings' );
				update_site_option( 'rthd_googleapi_redirecturl', $redirect_url );
			}

			$google_client_id           = $redux_helpdesk_settings['rthd_googleapi_clientid'];
			$google_client_secret       = $redux_helpdesk_settings['rthd_googleapi_clientsecret'];
			$google_client_redirect_url = get_site_option( 'rthd_googleapi_redirecturl', '' );

			if ( ( $google_client_id == '' || $google_client_secret == '' ) ) {
				echo '<div id="error_handle" class="error"><p>Please set google api detail on Google API <a href="' . esc_url( admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings&type=googleApi&tab=admin-settings' ) ) . '">setting</a> page </p></div>';

				return;
			}

			include_once RT_HD_PATH_VENDOR . 'google-api-php-client/Google_Client.php';
			include_once RT_HD_PATH_VENDOR . 'google-api-php-client/contrib/Google_Oauth2Service.php';

			$this->client = new Google_Client();
			$this->client->setApplicationName( 'Helpdesk Studio' );
			$this->client->setClientId( $google_client_id );
			$this->client->setClientSecret( $google_client_secret );
			$this->client->setRedirectUri( $google_client_redirect_url );
			$this->client->setScopes(
				array(
					'https://mail.google.com/',
					'https://www.googleapis.com/auth/userinfo.email',
					'https://www.googleapis.com/auth/userinfo.profile',
				) );
			$this->client->setAccessType( 'offline' );
			$this->oauth2  = new Google_Oauth2Service( $this->client );
			$this->user_id = get_current_user_id();

			//Google Oauth redirection
			if ( isset( $_GET['code'] ) ) {
				$this->client->authenticate();
				$user  = $this->oauth2->userinfo_v2_me->get();
				$email = filter_var( $user['email'], FILTER_SANITIZE_EMAIL );
				$rt_hd_settings->add_user_google_ac( $this->client->getAccessToken(), $email, serialize( $user ), $this->user_id );
				wp_redirect( $google_client_redirect_url );
			}

			if ( isset( $_REQUEST['rthd_add_imap_email'] ) ) {
				if ( isset( $_POST['rthd_imap_user_email'] ) && ! empty( $_POST['rthd_imap_user_email'] ) && isset( $_POST['rthd_imap_user_pwd'] ) && ! empty( $_POST['rthd_imap_user_pwd'] ) && isset( $_POST['rthd_imap_server'] ) && ! empty( $_POST['rthd_imap_server'] ) ) {
					$password    = $_POST['rthd_imap_user_pwd'];
					$email       = $_POST['rthd_imap_user_email'];
					$email_data  = array(
						'email' => $email,
					);
					$imap_server = $_POST['rthd_imap_server'];
					$rt_hd_settings->add_user_google_ac( rthd_encrypt_decrypt( $password ), $email, maybe_serialize( $email_data ), $this->user_id, 'imap', $imap_server );
				}
			}
		}

		/**
		 * @param $field
		 * @param $value
		 */
		public function rthd_reply_by_email_view( $field, $value ) {

			global $rt_hd_settings, $rt_hd_imap_server_model;


			$this->goole_oauth();

			$google_acs = $rt_hd_settings->get_user_google_ac( $this->user_id );
			$authUrl    = $this->client->createAuthUrl();

			$results           = Rt_HD_Utils::get_hd_rtcamp_user();
			$arrSubscriberUser = array();
			foreach ( $results as $author ) {
				$arrSubscriberUser[] = array(
					'id'      => $author->ID,
					'label'   => $author->display_name,
					'imghtml' => get_avatar( $author->user_email, 25 ),
				);
			}
			echo '<script> var arr_rtcamper=' . json_encode( $arrSubscriberUser ) . '; </script>';
			$rCount = 0;
			if ( isset( $google_acs ) && ! empty( $google_acs ) ) {
				foreach ( $google_acs as $ac ) {
					$rCount ++;
					$ac->email_data = unserialize( $ac->email_data );
					$email          = filter_var( $ac->email_data['email'], FILTER_SANITIZE_EMAIL );
					$email_type     = $ac->type;
					$imap_server    = $ac->imap_server;
					$mail_folders   = ( isset( $ac->email_data['mail_folders'] ) ) ? $ac->email_data['mail_folders'] : '';
					$mail_folders   = array_filter( explode( ',', $mail_folders ) );
					$inbox_folder   = ( isset( $ac->email_data['inbox_folder'] ) ) ? $ac->email_data['inbox_folder'] : '';

					if ( $ac->type == 'goauth' ) {
						$token = json_decode( $ac->outh_token );
						$this->client->setAccessToken( $ac->outh_token );
						if ( $this->client->isAccessTokenExpired() ) {
							$this->client->refreshToken( $token->refresh_token );
							$user  = $this->oauth2->userinfo_v2_me->get();
							$email = filter_var( $user['email'], FILTER_SANITIZE_EMAIL );
							if ( isset( $ac->email_data['inbox_folder'] ) ) {
								$user['inbox_folder'] = $ac->email_data['inbox_folder'];
							}
							if ( isset( $ac->email_data['mail_folders'] ) ) {
								$user['mail_folders'] = $ac->email_data['mail_folders'];
							}
							$rt_hd_settings->update_user_google_ac( $this->client->getAccessToken(), $email, serialize( $user ) );
							$ac->email_data = $user;
							$token          = json_decode( $this->client->getAccessToken() );
						}
						$token = $token->access_token;
					} else {
						$token = $ac->outh_token;
					}
					if ( isset( $ac->email_data['picture'] ) ) {
						$img          = filter_var( $ac->email_data['picture'], FILTER_VALIDATE_URL );
						$personMarkup = "<img src='$img?sz=96'>";
					} else {
						$personMarkup = get_avatar( $email, 96 );
					} ?>
					<table class="form-table hd-option">
						<tbody>
						<tr>
							<td>
								<input type="hidden" name='mail_ac' value="<?php echo esc_attr( $email ); ?>"/>
								<table class='hd-google-profile-table'>
					<?php if ( $ac->type == 'imap' ) { ?>
										<tr valign="top">
											<td></td>
											<th scope="row"><label><?php _e( 'IMAP Server' ); ?></label></th>
											<td class="long">
												<select required="required" name="imap_server">
													<option value=""><?php _e( 'Select Mail Server' ); ?></option>
						<?php $imap_servers = $rt_hd_imap_server_model->get_all_servers();
						foreach ( $imap_servers as $server ) {
							?>
							<option <?php echo esc_html( ( isset( $ac->imap_server ) && $ac->imap_server == $server->id ) ? 'selected="selected"' : '' ); ?>
								value="<?php echo esc_attr( $server->id ); ?>"><?php echo esc_html( $server->server_name ); ?></option>
						<?php } ?>
												</select>
											</td>
										</tr>
										<tr valign="top">
											<td></td>
											<th scope="row"><label><?php _e( 'Password' ); ?></label></th>
											<td class="long"><input required="required" autocomplete="off"
											                        type="password"
											                        name="imap_password" placeholder="Password"
											                        value="<?php echo esc_attr( rthd_encrypt_decrypt( $token ) ); ?>"/>
											</td>
										</tr>
					<?php
					}
					$all_folders = null;
					try {
						$hdZendEmail = new Rt_HD_Zend_Mail();
						if ( $hdZendEmail->try_lmap_login( $email, $token, $email_type, $imap_server ) ) {
							$storage     = new ImapStorage( $hdZendEmail->imap );
							$all_folders = $storage->getFolders();
						}
					} catch ( Exception $e ) {
						echo '<tr valign="top"><td></td><td></td><td><p class="description">' . esc_html( $e->getMessage() ) . '</p></td></tr>';
					} ?>
									<tr valign="top">
										<td><label><?php echo balanceTags( $personMarkup ); ?></label></td>
										<th scope="row"><label><?php _e( 'Mail Folders to read' ); ?></label></th>
										<td>
											<label>
												<?php _e( 'Inbox Folder' ); ?>
												<select data-email-id="<?php echo esc_attr( $ac->id ); ?>"
												        name="inbox_folder"
												        data-prev-value="<?php echo esc_attr( $inbox_folder ); ?>">
													<option value=""><?php _e( 'Choose Inbox Folder' ); ?></option>
													<?php if ( ! is_null( $all_folders ) ) {
														$hdZendEmail->render_folders_dropdown( $all_folders, $value = $inbox_folder );
													} ?>
												</select> </label>
											<?php if ( in_array( $email, rthd_get_all_system_emails() ) ) { ?>
												<p class="description"><?php _e( 'This is linked as a system mail. Hence it will only read the Inbox Folder; no matter what folder you choose over here. These will be ignored.' ); ?></p>
											<?php } ?>
					<?php if ( ! is_null( $all_folders ) ) {
						$hdZendEmail->render_folders_checkbox( $all_folders, $element_name = 'mail_folders', $values = $mail_folders, $data_str = 'data-email-id=' . $ac->id, $inbox_folder );
					} else { ?>
						<p class="description"><?php _e( 'No Folders found.' ); ?></p>
					<?php } ?>
										</td>
									</tr>
									<tr valign="top">
										<td></td>
										<th scope="row"><label></label></th>
										<td>
											<input type="hidden" name="rthd_submit_enable_reply_by_email" value="save"/>
											<a
												class='button remove-google-ac'
												href='<?php echo esc_url( admin_url( 'edit.php?post_type=' . Rt_HD_Module::$post_type . '&page=rthd-settings&tab=my-settings&rthd_submit_enable_reply_by_email=save&type=personal&email=' . $email ) ); ?>'>Remove
												A/C</a>
											<?php if ( $ac->type == 'goauth' ) { ?>
												<a class='button button-primary'
												   href='<?php echo esc_url( $authUrl ); ?>'>Re
													Connect Google Now</a>
											<?php } ?>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						</tbody>
					</table>
					<script>
						jQuery(document).ready(function ($) {
							$(document).on('change', 'select[name=inbox_folder]', function (e) {
								e.preventDefault();
								inbox = $(this).val();
								prev_value = $(this).data('prev-value');
								$(this).data('prev-value', inbox);
								var email_id = $(this).data('email-id');
								$('input[data-email-id="' + email_id + '"][value="' + inbox + '"]').parent().css('display', 'none');
								$('input[data-email-id="' + email_id + '"][value="' + prev_value + '"]').parent().css('display', 'inline');
							});
						});
					</script>
				<?php
				}
			} else {
				?>
				<p class="submit"><a class="button" id="rthd_add_personal_email"
				                     href="#"><?php _e( 'Add Email' ); ?></a></p>
				<p class="submit rthd-hide-row" id="rthd_email_acc_type_container">
					<select id="rthd_select_email_acc_type">
						<option value=""><?php _e( 'Select Type' ); ?></option>
						<option value="goauth"><?php _e( 'Google OAuth App' ); ?></option>
						<option value="imap"><?php _e( 'IMAP' ); ?></option>
					</select>
				</p>
				<p class="submit rthd-hide-row" id="rthd_goauth_container">
					<a class='button button-primary'
					   href='<?php echo esc_url( $authUrl ); ?>'><?php _e( 'Connect New Google A/C' ); ?></a>
				</p>
				<p id="rthd_add_imap_acc_form" autocomplete="off" class="rthd-hide-row">
					<input type="hidden" name="rthd_add_imap_email" value="1"/>
					<select required="required" name="rthd_imap_server">
						<option value=""><?php _e( 'Select Mail Server' ); ?></option>
				<?php
				$imap_servers = $rt_hd_imap_server_model->get_all_servers();
				foreach ( $imap_servers as $server ) {
					?>
					<option
						value="<?php echo esc_attr( $server->id ); ?>"><?php echo esc_html( $server->server_name ); ?></option>
				<?php } ?>
					</select>
					<input type="email" required="required" autocomplete="off" name="rthd_imap_user_email"
					       placeholder="Email"/> <input type="password" required="required" autocomplete="off"
					                                    name="rthd_imap_user_pwd" placeholder="Password"/>
					<button class="button button-primary" type="submit"><?php _e( 'Save' ); ?></button>
				</p>
			<?PHP
			}
		}

		public function save_replay_by_email() {

			global $rt_hd_settings, $redux_helpdesk_settings;

			if ( isset( $redux_helpdesk_settings ) && isset( $redux_helpdesk_settings['rthd_enable_reply_by_email'] ) && $redux_helpdesk_settings['rthd_enable_reply_by_email'] == 1 && isset( $_REQUEST['rthd_submit_enable_reply_by_email'] ) && $_REQUEST['rthd_submit_enable_reply_by_email'] == 'save' ) {
				if ( isset( $_POST['mail_ac'] ) && is_email( $_POST['mail_ac'] ) ) {
					if ( isset( $_POST['imap_password'] ) ) {
						$token = rthd_encrypt_decrypt( $_POST['imap_password'] );
					} else {
						$token = null;
					}
					if ( isset( $_POST['imap_server'] ) ) {
						$imap_server = $_POST['imap_server'];
					} else {
						$imap_server = null;
					}
					$email_ac   = $rt_hd_settings->get_email_acc( $_POST['mail_ac'] );
					$email_data = null;
					if ( isset( $_POST['mail_folders'] ) && ! empty( $_POST['mail_folders'] ) && is_array( $_POST['mail_folders'] ) && ! empty( $email_ac ) ) {
						$email_data                 = maybe_unserialize( $email_ac->email_data );
						$email_data['mail_folders'] = implode( ',', $_POST['mail_folders'] );
					}
					if ( isset( $_POST['inbox_folder'] ) && ! empty( $_POST['inbox_folder'] ) && ! empty( $email_ac ) ) {
						if ( is_null( $email_data ) ) {
							$email_data = maybe_unserialize( $email_ac->email_data );
						}
						$email_data['inbox_folder'] = $_POST['inbox_folder'];
					}
					$rt_hd_settings->update_mail_acl( $_POST['mail_ac'], $token, maybe_serialize( $email_data ), $imap_server );
				}
				if ( isset( $_REQUEST['email'] ) && is_email( $_REQUEST['email'] ) ) {
					$rt_hd_settings->delete_user_google_ac( $_REQUEST['email'] );
					echo '<script>window.location="' . esc_url( add_query_arg(
							array(
								'post_type' => Rt_HD_Module::$post_type,
								'page'      => 'rthd-settings',
							), admin_url( 'edit.php' ) ) ) . '";</script>';
					die();
				}
			}
		}

	}

}