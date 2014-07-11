<?php

/**
 * Don't load this file directly!
 */
if (!defined('ABSPATH'))
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_CRM_Zend_Mail
 *
 * @author udit
 */
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Storage\Imap as ImapStorage;
use Zend\Mime\Mime;

if (!class_exists('Rt_CRM_Zend_Mail')) {

	class Rt_CRM_Zend_Mail {

		public $imap;
		public $authString;

		//put your code here
		function __construct() {
			// set_include_path(get_include_path() . PATH_SEPARATOR . RT_CRM_PATH_LIB);
		}

		function render_folders_dropdown( $folders, $value ) {
			while( $folders->getChildren() ) {
				$folder = $folders->current();
				if ( $folder->getChildren() ) {
					echo '<optgroup label="'.$folder->getGlobalName().'">';
					$this->render_folders_dropdown( $folder, $value );
					echo '</optgroup>';
				} else {
					echo '<option value="'.$folder->getGlobalName().'" '.( ( $folder->getGlobalName() == $value ) ? 'selected="selected"' : '' ).'>'.$folder->getGlobalName().'</option>';
				}
				$folders->next();
			}
			$folders->rewind();
		}

		function render_folders_checkbox( $folders, $element_name, $values, $data_str, $inbox_folder ) {
			while( $folders->getChildren() ) {
				echo '<ul>';
				$folder = $folders->current();
				if ( $folder->getChildren() ) {
					echo '<li><strong>'.$folder->getGlobalName().'</strong></li>';
					echo '<li>';
					$this->render_folders_checkbox( $folder, $element_name, $values, $data_str, $inbox_folder );
					echo '</li>';
				} else {
					echo '<li>&nbsp;&nbsp;&nbsp;<label '.( ( $folder->getGlobalName() == $inbox_folder ) ? 'style="display: none;"' : '' ).'><input type="checkbox" '.$data_str.' name="'.$element_name.'[]" value="'.$folder->getGlobalName().'" '.( ( in_array( $folder->getGlobalName(), $values ) ) ? 'checked="checked"' : '' ).' />';
					echo $folder->getGlobalName().'</label></li>';
				}
				$folders->next();
				echo '</ul>';
			}
			$folders->rewind();
		}

		function constructAuthString($email, $accessToken) {
			return base64_encode("user=$email\1auth=Bearer $accessToken\1\1");
		}

		function oauth2Authenticate($imap, $email, $accessToken) {
			$this->authString = $this->constructAuthString($email, $accessToken);
			$authenticateParams = array('XOAUTH2', $this->authString);
//        echo $this->authString;
//        var_dump($authenticateParams);
			$imap->sendRequest('AUTHENTICATE', $authenticateParams);
			while (true) {
				$response = "";
				$is_plus = $imap->readLine($response, '+', true);
				if ($is_plus) {
					error_log("got an extra server challenge: $response");
					// Send empty client response.
					$imap->sendRequest('');
				} else {
					if (preg_match('/^NO /i', $response) ||
							preg_match('/^BAD /i', $response)) {
						error_log("got failure response: $response");
						return false;
					} else if (preg_match("/^OK /i", $response)) {
						return true;
					} else {
						// Some untagged response, such as CAPABILITY
					}
				}
			}
		}

		function tryImapLogin( $email, $accessToken, $email_type, $imap_server ) {
			$this->imap = new Zend\Mail\Protocol\Imap();

			switch ( $email_type ) {
				case 'goauth':
					$this->imap->connect('ssl://imap.gmail.com', '993', true);
					return $this->oauth2Authenticate($this->imap, $email, $accessToken);
				case 'imap':
					global $rt_crm_imap_server_model;
					$server = $rt_crm_imap_server_model->get_server( $imap_server );
					if ( empty( $server ) ) {
						echo 'Mail Server Not Found. Invalid Server id.';
						return false;
					}
					$host = $server->incoming_imap_server;
					$port = $server->incoming_imap_port;
					$ssl = ( isset( $server->incoming_imap_enc ) && ! is_null( $server->incoming_imap_enc ) ) ? true : false;
					$this->imap->connect( $host, $port, $ssl );
					return $this->imap->login( $email, rtcrm_encrypt_decrypt( $accessToken ) );
				default:
					return false;
			}
		}

		public function sendemail($fromemail, $accessToken, $email_type, $imap_server, $subject, $body, $toEmail, $ccEmail, $bccEmail, $attachemnts, $mailtype = 'notification') {
			set_time_limit(0);
			if ( ! $this->tryImapLogin( $fromemail, $accessToken, $email_type, $imap_server ) ) {
				return false;
			}

			$transport = new SmtpTransport();

			$smtp_args = array();
			switch( $email_type ) {
				case 'goauth':
					$smtp_args['name'] = 'gmail-smtp';
					$smtp_args['host'] = 'smtp.gmail.com';
					$smtp_args['port'] = 465;
					$smtp_args['connection_class'] = 'oauth2';
					$smtp_args['connection_config'] = array(
						'xoauth2_request' => $this->authString,
						'ssl' => 'ssl',
					);
					break;
				case 'imap':
					global $rt_crm_imap_server_model;
					$server = $rt_crm_imap_server_model->get_server( $imap_server );
					if ( empty( $server ) ) {
						echo 'Mail Server Not Found. Invalid Server id.';
						return false;
					}
					$smtp_args['name'] = $server->outgoing_smtp_server;
					$smtp_args['host'] = $server->outgoing_smtp_server;
					$smtp_args['port'] = $server->outgoing_smtp_port;
					$smtp_args['connection_class'] = 'login';
					$smtp_args['connection_config'] = array(
						'username' => $fromemail,
						'password' => rtcrm_encrypt_decrypt( $accessToken ),
						'ssl' => $server->outgoing_smtp_enc,
					);
					break;
				default:
					break;
			}

			$options = new SmtpOptions( $smtp_args );
			$transport->setOptions( $options );

			$message = new Message();
			$message->addFrom($fromemail);

			$message->addCustomeHeader("X-Crm", $mailtype);

			//$mail->setFrom($fromemail);

			$message->setSubject(stripslashes_deep(html_entity_decode($subject, ENT_QUOTES, 'UTF-8')));
			//$mail->setSubject($subject);
			if (!empty($toEmail)) {
				foreach ($toEmail as $temail) {
					//$mail->addTo($temail["email"], isset($temail["name"]) ? $temail["name"] : '');
					$message->addTo($temail["email"], isset($temail["name"]) ? $temail["name"] : '');
				}
			}
			if (!empty($ccEmail)) {
				foreach ($ccEmail as $temail) {
					//$mail->addCc($temail["email"], isset($temail["name"]) ? $temail["name"] : '');
					$message->addCc($temail["email"], isset($temail["name"]) ? $temail["name"] : '');
				}
			}
			if (!empty($bccEmail)) {
				foreach ($bccEmail as $temail) {
					if (isset($temail["email"]))
						$message->addBcc($temail["email"], isset($temail["name"]) ? $temail["name"] : '');
				}
			}


			// create a MimeMessage object that will hold the mail body and any attachments
			$bodyPart = new MimeMessage;

			$bodyMessage = new MimePart($body);
			$bodyMessage->type = 'text/html';
			$bodyMessage->encoding = Mime::ENCODING_QUOTEDPRINTABLE;


			$bodyPart->addPart($bodyMessage);

			if (!empty($attachemnts)) {
				foreach ($attachemnts as $attach) {
					$file_array = explode('/', $attach);
					$fileName = $file_array[count($file_array) - 1];
					$attachment = new MimePart(file_get_contents($attach));

					$attachment->type = Rt_CRM_Utils::get_mime_type($attach);
					$attachment->filename = $fileName;
					$attachment->encoding = Zend\Mime\Mime::ENCODING_BASE64;
					$attachment->disposition = Zend\Mime\Mime::DISPOSITION_ATTACHMENT;
					$bodyPart->addPart($attachment);
				}
			}
			$message->setBody($bodyPart);
			return $transport->send($message);
		}

		function get_decoded_message($part) {
			$txtBody = $part->getContent();
			if (isset($part->contentTransferEncoding)) {
				switch ($part->contentTransferEncoding) {
					case 'base64':
						$txtBody = base64_decode($txtBody);
						break;
					case 'quoted-printable':
						$txtBody = quoted_printable_decode($txtBody);
						break;
				}
			}
			preg_match('/charset="(.+)"$/', $part->contentType, $matches);
			$charset = isset($matches[1]) ? $matches[1] : '';
			if ($charset == 'iso-8859-1') {
				$txtBody = utf8_decode($txtBody); //convert to utf8
			}
			return $txtBody;
		}

		public function get_import_thread_request($email) {
			global $rt_crm_mail_thread_importer_model;
			$where = array(
				'email' => $email,
				'status' => 'r',
			);
			return $rt_crm_mail_thread_importer_model->get_thread( $where );
		}

		public function update_thread_import_status($id) {
			global $rt_crm_mail_thread_importer_model;
			$rows_affected = $rt_crm_mail_thread_importer_model->update_thread( array( 'status' => 'c' ), array( 'id' => $id ) );
			return ( !empty( $rows_affected ) );
		}

		public function reademail($email, $accessToken, $email_type, $imap_server, $lastDate, $user_id, $isSystemEmail = false, $signature = "", $isThreadImporter = false) {
			set_time_limit(0);
			global $signature, $rt_crm_settings;
			if ( ! $this->tryImapLogin( $email, $accessToken, $email_type, $imap_server ) ) {
				$rt_crm_settings->update_sync_status($email, false);
				echo "login fail";
				return false;
			}
			$storage = new ImapStorage($this->imap);

			$rtCampUser = Rt_CRM_Utils::get_crm_rtcamp_user();
			$crmUser = array();
			foreach ($rtCampUser as $rUser) {
				$crmUser[$rUser->user_email] = $rUser->ID;
			}

			$email_acc = $rt_crm_settings->get_email_acc( $email );
			if ( empty( $email_acc ) ) {
				$rt_crm_settings->update_sync_meta_time( $email, current_time( 'mysql' ) );
				$rt_crm_settings->update_sync_status( $email, false );
				echo 'email fail';
				return false;
			}

			$email_data = maybe_unserialize( $email_acc->email_data );

			if ( empty( $email_data['inbox_folder'] ) ) {
				$rt_crm_settings->update_sync_meta_time( $email, current_time( 'mysql' ) );
				$rt_crm_settings->update_sync_status( $email, false );
				echo 'inbox folder fail';
				return false;
			}

			$mail_folders = explode( ',', ( isset( $email_data['mail_folders'] ) ) ? $email_data['mail_folders'] : '' );
			$inbox_folder = $email_data['inbox_folder'];
			array_unshift( $mail_folders, $inbox_folder );
			if ( $isThreadImporter ) {

				if ( $isSystemEmail ) {
					$mail_folders = array( $inbox_folder );
				}
				foreach ( $mail_folders as $folder ) {
					$storage->selectFolder( $folder );
					$result = $this->get_import_thread_request( $email );
					if ( ! $result )
						return;
					if ( empty( $result ) )
						return;
					foreach ( $result as $rs ) {
						$threadId = $rs->threadid;
						$decThreadId = $this->bchexdec( $threadId );
						$allMail = $storage->protocol->requestAndResponse( "UID SEARCH X-GM-THRID", array( $storage->protocol->escapeString( $decThreadId ) ) );

						$allMailArray = array();
						foreach ( $allMail as $ids ) {
							if ( $ids[0] == 'SEARCH' ) {
								array_shift( $ids );
								$allMailArray = $ids;
							}
						}
						if( ! empty( $allMailArray ) ) {
							global $threadPostId;
							$threadPostId = $rs->post_id;
							$this->rt_parse_email( $email, $storage, $allMailArray, $crmUser, $user_id, $isSystemEmail );
							global $rt_crm_leads;

							$title = "[New Follwup Imported]" . $rt_crm_leads->create_title_for_mail( $threadPostId );
							$body = "New " . count( $allMailArray ) . " Follwup Imported From Gmail threads";
							$body.="<br/><b>Email Ac : </b>" . $email;
							$body.="<br/><b>Thread ID: </b>" . $threadId;
							$body.="<br/> ";
							$rt_crm_leads->notify_subscriber_via_email( $threadPostId, $title, $body, 0 );

							$this->update_thread_import_status( $rs->id );
						}
					}
				}
			} else {
				global $sync_inbox_type;
				global $rt_mail_uid;
				if ( $isSystemEmail ) {
					$mail_folders = array( $inbox_folder );
				}
				foreach ($mail_folders as $folder ) {
					$storage->selectFolder( $folder );
					echo $email.' : Reading - '.$folder.'\r\n';
					$sync_inbox_type = $folder;
					if (!isset($rt_mail_uid[$sync_inbox_type])) {
						$rt_mail_uid[$sync_inbox_type] = 0;
					}

					global $rt_mail_uid;
					if ($rt_mail_uid[$sync_inbox_type] > 0) {
						$allMail = $storage->protocol->requestAndResponse("UID FETCH {$rt_mail_uid[$sync_inbox_type]}:* (UID)", array());
						foreach ($allMail as $tempEmail) {
							$arrayMailIds[] = array("uid" => $tempEmail[2][1], 'msgid' => $tempEmail[0]);
						}
					} else {
						$arrayMailIds = $storage->protocol->search(array('SINCE ' . $lastDate));
					}
					echo $email . " : Found " . count( $arrayMailIds ) . " Mails \r\n";
					$this->rt_parse_email( $email, $storage, $arrayMailIds, $crmUser, $user_id, $isSystemEmail );
				}
				$rt_crm_settings->update_sync_meta_time( $email, current_time( 'mysql' ) );
				$rt_crm_settings->update_sync_status( $email, false );
			}
		}

		function bchexdec($hex) {
			$len = strlen($hex);
			$dec = "";
			for ($i = 1; $i <= $len; $i++)
				$dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));

			return $dec;
		}

		function getNumberByUniqueId($UmailId, &$storage) {
			$cMail = $storage->protocol->requestAndResponse("UID FETCH {$UmailId}:* (UID)", array());
			if (is_array($cMail)) {
				foreach ($cMail as $tempEmail) {
					return intval($tempEmail[0]);
				}
			} else {
				echo "here --> $UmailId ";
				var_dump($cMail);
				return $cMail;
			}
			throw new Exception("No Unique id found");
		}

		public function insert_mail_message_id( $messageid ) {
			global $rt_crm_mail_message_model;

			$result = $rt_crm_mail_message_model->get_message( array( 'messageid' => $messageid ) );
			if ( empty( $result ) ) {
				return $rt_crm_mail_message_model->add_message( array( 'messageid' => $messageid ) );
			}
			return false;
		}

		public function update_sync_meta($email, $replytime) {
			global $rt_crm_mail_accounts_model;
			$rows_affected = $rt_crm_mail_accounts_model->update_mail_account( array( 'last_mail_time' => $replytime ), array( 'email' => $email ) );
			return ( !empty( $rows_affected ) );
		}

		public function update_last_mail_uid($email, $uid) {
			global $threadPostId;
			if ($threadPostId) {
				return true;
			}
			global $rt_mail_uid;
			global $sync_inbox_type;
			global $rt_crm_mail_accounts_model;
			$rt_mail_uid[$sync_inbox_type] = $uid;
			$rows_affected = $rt_crm_mail_accounts_model->update_mail_account( array( 'last_mail_uid' => serialize($rt_mail_uid) ), array( 'email' => $email ) );

			return ( !empty( $rows_affected ) );
		}

		public function rt_parse_email($email, &$storage, &$arrayMailIds, &$crmUser, $user_id, $isSystemEmail ) {
			$lastMessageId = "-1";
			global $rt_crm_leads;
			$lastFlags = false;
			$lastFlag = array();
			$message = null;

			$systemEmails = rtcrm_get_all_system_emails();
			global $threadPostId;

			foreach ($arrayMailIds as $UmailId) {
				try {
					if (is_array($UmailId)) {
						$tempUIDArray = $UmailId;
						$UmailId = $tempUIDArray["uid"];
					}
					$mailId = $this->getNumberByUniqueId($UmailId, $storage);
					$message = $storage->getMessage($mailId); //1474);
					$lastFlags = $message->getFlags();
					try {
						$lastMessageId = $message->messageid;
					} catch (Exception $e) {
						$lastMessageId = false;
					}
					//$dateString = strstr($dateString," (",true);
					if (isset($message->xcrm)) {
						$dt = new DateTime($message->date);
						$this->update_last_mail_uid($email, $UmailId);
						continue;
					}
					if ($lastMessageId && $rt_crm_leads->check_duplicate_from_message_Id($lastMessageId)) {
						$dt = new DateTime($message->date);
						$this->update_last_mail_uid($email, $UmailId);
						continue;
					}

					if ($lastMessageId && !isset($threadPostId)) {
						if ( !$this->insert_mail_message_id( $lastMessageId ) ) {
							$this->update_last_mail_uid($email, $UmailId);
							continue;
						}
					}
					if (!isset($message->subject)) {
						$message->subject = " ";
					}
					echo $email . " Parsing Mail " . $message->subject . "\r\n";
					$subscriber = array();
					$from = array();
					$allEmails = array();
					global $rtcrm_all_emails;
					$rtcrm_all_emails = array();
					if (isset($message->from)) { // or $message->headerExists('cc');
						$arrFrom = $message->getHeader("from")->getAddressList();
						foreach ($arrFrom as $tFrom) {
							$from["address"] = $tFrom->getEmail();
							$from["name"] = $tFrom->getName();
							$rtcrm_all_emails[] = array("address" => $tFrom->getEmail(), "name" => $tFrom->getName(), 'key' => 'from');
							if (!array_key_exists($tFrom->getEmail(), $crmUser)) {
								if ( !in_array( $tFrom->getEmail(), $systemEmails ) )
									$allEmails[] = array("address" => $tFrom->getEmail(), "name" => $tFrom->getName());
							} else
								$subscriber[] = $crmUser[$tFrom->getEmail()];
						}
					}
					if (isset($message->to)) { // or $message->headerExists('cc');
						$arrTo = $message->getHeader("to")->getAddressList();
						foreach ($arrTo as $tTo) {
							if(!is_email($tTo->getEmail()))
								continue;
							$rtcrm_all_emails[] = array("address" => $tTo->getEmail(), "name" => $tTo->getName(), 'key' => 'to');
							if (!array_key_exists($tTo->getEmail(), $crmUser)) {
								if ( !in_array( $tTo->getEmail(), $systemEmails ) )
									$allEmails[] = array("address" => $tTo->getEmail(), "name" => $tTo->getName());
							} else
								$subscriber[] = $crmUser[$tTo->getEmail()];
						}
					}
					if (isset($message->cc)) { // or $message->headerExists('cc');
						$arrCC = $message->getHeader("cc")->getAddressList();
						foreach ($arrCC as $tCc) {
							if(!is_email($tCc->getEmail()))
								continue;
							$rtcrm_all_emails[] = array("address" => $tCc->getEmail(), "name" => $tCc->getName(), 'key' => 'cc');
							if (!array_key_exists($tCc->getEmail(), $crmUser)) {
								if ( !in_array( $tCc->getEmail(), $systemEmails ) )
									$allEmails[] = array("address" => $tCc->getEmail(), "name" => $tCc->getName());
							} else
								$subscriber[] = $crmUser[$tCc->getEmail()];
						}
					}
					if (isset($message->bcc)) { // or $message->headerExists('cc');
						$arrBCC = $message->getHeader("bcc")->getAddressList();
						foreach ($arrBCC as $tBCc) {
							if(!is_email($tBCc->getEmail()))
								continue;
							$rtcrm_all_emails[] = array("address" => $tBCc->getEmail(), "name" => $tBCc->getName(), 'key' => 'bcc');
							if (!array_key_exists($tBCc->getEmail(), $crmUser)) {
								if ( !in_array( $tBCc->getEmail(), $systemEmails ) )
									$allEmails[] = array("address" => $tBCc->getEmail(), "name" => $tBCc->getName());
							} else
								$subscriber[] = $crmUser[$tBCc->getEmail()];
						}
					}
					$htmlBody = "";
					$txtBody = "";
					$attachements = array();
					if ($message->isMultiPart()) {
						foreach ($message as $part) {
							$ContentType = strtok($part->contentType, ';');
							if (!(strpos($ContentType, 'multipart/alternative') === false)) {
								$totParts = $part->countParts();
								for ($rCount = 1; $rCount <= $totParts; $rCount++) {
									$tPart = $part->getPart($rCount);
									$tContentType = strtok($tPart->contentType, ';');
									if ($tContentType == 'text/plain') {
										$txtBody = $this->get_decoded_message($tPart);
									} else if ($tContentType == 'text/html') {
										$htmlBody = $this->get_decoded_message($tPart);
									}
								}
							} else if ($ContentType == 'text/plain') {
								$txtBody = $this->get_decoded_message($part);
							} else if ($ContentType == 'text/html') {
								$htmlBody = $this->get_decoded_message($part);
							} else {
								try {
									$filename = $part->getHeader('content-disposition')->getFieldValue("filename");
									if (preg_match('*filename=\"([^;]+)\"*', $filename, $matches)) {
										if (isset($matches[1]))
											$filename = trim($matches[1]);
										else
											$filename = time() . "." . Rt_CRM_Utils::get_extention($ContentType);
									} else {
										$filename = time() . "." . Rt_CRM_Utils::get_extention($ContentType);
									}
								} catch (Exception $e) {
									$e->getTrace();
									$filename = time() . "." . Rt_CRM_Utils::get_extention($ContentType);
								}

								//->getFieldValue('name');
								if (trim($filename) == "")
									$filename = time() . "." . Rt_CRM_Utils::get_extention($ContentType);
								$filedata = $this->get_decoded_message($part);
								$upload_dir = wp_upload_dir(null);
								$filename = sanitize_file_name($filename);
								if (!file_exists($upload_dir ['path'] . "/$filename")) {
									$uploaded = wp_upload_bits($filename, null, $filedata);
								} else {
									$uploaded['error'] = false;
									$uploaded['file'] = $upload_dir ['path'] . "/$filename";
									$uploaded['url'] = $upload_dir ['url'] . "/$filename";
								}
								if ($uploaded['error'] == false) {
									Rt_CRM_Utils::log("[Attachement Created] File:{$uploaded['file']} ; URL: {$uploaded['url']}", "mail-attachement.txt");
									$file = array();
									$extn_array = explode('.', $filename);
									$extn = $extn_array[count($extn_array) - 1];
									$file['file'] = $uploaded['file'];
									$file['url'] = $uploaded['url'];
									$file["filename"] = $filename;
									$file["extn"] = $extn;
									$file["type"] = $ContentType;
									$attachements[] = $file;
								} else {
									echo $filename . "\r\n";
									ob_start();
									var_dump($uploaded);
									$data = ob_get_clean();
									Rt_CRM_Utils::log("[Attachement Failed] Email: {$email};Message-Id: {$message->messageid}; Data : $data ", "error-mail-attachement.txt");
								}
							}
						}
					} else {
						if (isset($message->contentType)) {
							if ($message->contentType == 'text/plain') {
								$txtBody = $this->get_decoded_message($message);
								$htmlBody = $txtBody;
							} else if ($message->contentType == 'text/html') {
								$htmlBody = $this->get_decoded_message($message);
								$txtBody = strip_tags($htmlBody);
							} else {
								$htmlBody = $message->getContent();
								$txtBody = strip_tags($htmlBody);
							}
						} else {
							$htmlBody = nl2br($message->getContent());
							$txtBody = strip_tags($htmlBody);
						}
					}
					if ($lastFlags !== false) {
						$lastFlag = true;
						foreach ($lastFlags as $fl) {
							if ($fl == Zend\Mail\Storage::FLAG_SEEN) {
								$lastFlag = false;
							}
						}
						if ($lastFlag) {
							$storage->protocol->store(array(Zend\Mail\Storage::FLAG_SEEN), $mailId, null, "-", true);
						}
					}

					$messageid = "";
					if (isset($message->messageid))
						$messageid = $message->messageid;

					$inreplyto = "";
					if (isset($message->inreplyto))
						$inreplyto = $message->inreplyto;

					$references = "";
					if (isset($message->references))
						$references = $message->references;
					$subject = $message->subject;
					$htmlBody = Rt_CRM_Utils::forceUFT8($htmlBody);
					$subject = Rt_CRM_Utils::forceUFT8($subject);
					$txtBody = Rt_CRM_Utils::forceUFT8($txtBody);
					$success_flag = $rt_crm_leads->process_email_to_lead(
							$subject,
							$htmlBody,
							$from,
							$message->date,
							$allEmails,
							$attachements,
							$txtBody,
							true,
							$user_id,
							$messageid,
							$inreplyto,
							$references,
							$isSystemEmail,
							$subscriber
					);

					if ( ! $success_flag ) {
						foreach ( $attachements as $attachement ) {
							unlink($attachement['file']);
						}
					}

					global $threadPostId;
					if (!isset($threadPostId)) {
						$this->update_last_mail_uid($email, $UmailId);
						try {
							$dt = new DateTime($message->date);
							$this->update_sync_meta($email, $dt->format('Y-m-d H:i:s'));
						} catch (Exception $e) {
							$this->update_sync_meta($email, $dt->format('Y-m-d H:i:s'));
						}
					}
				} catch (Exception $e) {
					ob_start();
					echo $e->getMessage();
					var_dump($e->getTrace());
					echo "Error : ";
					var_dump($message);
					$data = ob_get_clean();

					if (!isset($message->subject)) {
						$message->subject = "";
					}
					Rt_CRM_Utils::log("[Mail Sync Failed]Subject:{$message->subject}; Email: {$email}; MailNo: {$mailId};Message-Id: {$lastMessageId} ", "error-mail-sync.txt");
					Rt_CRM_Utils::log("[Mail Sync Failed]Subject:{$message->subject}; Email: {$email}; MailNo: {$mailId};Message-Id: {$lastMessageId} ", $email . "error-mail-sync.txt");
					wp_mail("udit.desai@rtcamp.com", "Error in Mail Sync " . $email . " " . $message->subject, $data . "<br/><hr>" . $e->getMessage() . "<hr>" . $e->getTraceAsString());
					wp_mail("faishal.saiyed@rtcamp.com", "Error in Mail Sync " . $email . " " . $message->subject, $data . "<br/><hr>" . $e->getMessage() . "<hr>" . $e->getTraceAsString());
				}
			}
		}

	}

}
