<?php
/**
 * Description of Rtbiz_HD_Support
 *
 * @author Utkarsh Patel <utkarsh.patel@rtcamp.com>
 */
if ( ! class_exists( 'Rtbiz_HD_Support' ) ) {

	class Rtbiz_HD_Support {

		var $debug_info;
		var $curr_sub_tab;
		// current page
		public static $page;

		/**
		 * Constructor
		 *
		 * @access public
		 *
		 * @param  bool $init
		 *
		 */
		public function __construct( $init = true ) {

			if ( ! is_admin() ) {
				return;
			}

			$this->curr_sub_tab = 'support';
			if ( isset( $_REQUEST['tab'] ) ) {
				$this->curr_sub_tab = $_REQUEST['tab'];
			}
		}

		/**
		 * Get support content.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return void
		 */
		public function get_support_content() {
			$tabs   = array();
			$tabs[] = array(
				'title'    => __( 'Support', RTBIZ_HD_TEXT_DOMAIN ),
				'name'     => __( 'Support', RTBIZ_HD_TEXT_DOMAIN ),
				'href'     => '#support',
				'icon'     => 'dashicons-businessman',
				'callback' => array( $this, 'call_get_form' ),
			);
			$tabs[] = array(
				'title'    => __( 'Debug Info', RTBIZ_HD_TEXT_DOMAIN ),
				'name'     => __( 'Debug Info', RTBIZ_HD_TEXT_DOMAIN ),
				'href'     => '#debug',
				'icon'     => 'dashicons-admin-tools',
				'callback' => array( $this, 'debug_info_html' ),
			);
			?>
			<div id="rtbiz-hd-support">
				<?php RTMediaAdmin::render_admin_ui( self::$page, $tabs ); ?>
			</div>
			<?php
		}

		public static function support_sent(){
			echo '<p>' . __( 'Thank you for posting your support request.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
			echo '<p>' . __( 'We will get back to you shortly.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
		}

		/**
		 * Render support.
		 *
		 * @access public
		 *
		 * @param string|type $page
		 *
		 */
		public function render_support( $page = '' ) {

			self::$page = $page;

			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[ $page ] ) ) {
				return;
			}

			foreach ( ( array ) $wp_settings_sections[ $page ] as $section ) {

				if ( $section['callback'] ) {
					call_user_func( $section['callback'], $section );
				}

				if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
					continue;
				}

				echo '<table class="form-table">';
				do_settings_fields( $page, $section['id'] );
				echo '</table>';
			}
		}

		/**
		 * Define Service Selector.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return void
		 */
		public function service_selector() {
			?>
			<div>
				<form name="rtbiz_hd_service_select_form" method="post">
					<p>
						<label
						       for="select_support"><?php _e( 'Service', RTBIZ_HD_TEXT_DOMAIN ); ?>:</label>
						<select name="rtbiz_hd_service_select">
							<option
								value="premium_support" <?php
							if ( 'premium_support' == $_POST['form'] ) {
								echo 'selected';
							}
							?>><?php _e( 'Premium Support', RTBIZ_HD_TEXT_DOMAIN ); ?></option>
							<option
								value="bug_report" <?php
							if ( 'bug_report' == $_POST['form'] ) {
								echo 'selected';
							}
							?>><?php _e( 'Bug Report', RTBIZ_HD_TEXT_DOMAIN ); ?></option>
							<option
								value="new_feature" <?php
							if ( 'new_feature' == $_POST['form'] ) {
								echo 'selected';
							}
							?>><?php _e( 'New Feature', RTBIZ_HD_TEXT_DOMAIN ); ?></option>
						</select>
						<input name="support_submit" value="<?php esc_attr_e( 'Submit', RTBIZ_HD_TEXT_DOMAIN ); ?>"
						       type="submit" class="button"/>
					</p>
				</form>
			</div>
			<?php
		}

		/**
		 * Call rtbiz_hd admin support form.
		 *
		 * @access public
		 *
		 * param $void
		 */
		public function call_get_form() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'rtbiz-ticket-settings' ) {
				//echo "<h2 class='nav-tab-wrapper'>".$this->rtbiz_hd_support_sub_tabs()."</h2>";
					echo "<div id='rtbiz_hd_service_contact_container' class='rtbiz-hd-support-container'><form name='rtbiz_hd_service_contact_detail' method='post'>";
					$this->get_form( 'premium_support' );
					echo '</form></div>';
			}
		}

		/**
		 * Get plugin_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return array $rtbiz_hd_plugins
		 */
		public function get_plugin_info() {
			$active_plugins = ( array ) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, rtbiz_hd_get_site_option( 'active_sitewide_plugins', array() ) );
			}
			$rtbiz_hd_plugins = array();
			foreach ( $active_plugins as $plugin ) {
				$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$version_string = '';
				if ( ! empty( $plugin_data['Name'] ) ) {
					$rtbiz_hd_plugins[] = $plugin_data['Name'] . ' ' . __( 'by', RTBIZ_HD_TEXT_DOMAIN ) . ' ' . $plugin_data['Author'] . ' ' . __( 'version', RTBIZ_HD_TEXT_DOMAIN ) . ' ' . $plugin_data['Version'] . $version_string;
				}
			}
			if ( 0 == sizeof( $rtbiz_hd_plugins ) ) {
				return false;
			} else {
				return implode( ', <br/>', $rtbiz_hd_plugins );
			}
		}

		/**
		 * Scan the rtbiz_hd template files.
		 *
		 * @access public
		 *
		 * @param  string $template_path
		 *
		 * @return array  $result
		 */
		public function scan_template_files( $template_path ) {
			$files  = scandir( $template_path );
			$result = array();
			if ( $files ) {
				foreach ( $files as $key => $value ) {
					if ( ! in_array( $value, array( '.', '..' ) ) ) {
						if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
							$sub_files = $this->scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
							foreach ( $sub_files as $sub_file ) {
								$result[] = str_replace( ABSPATH . 'wp-content/', '', rtbiz_hd_locate_template( substr( $sub_file, 0, ( sizeof( $sub_file ) - 5 ) ) ) );
							}
						} else {
							if ( 'main.php' != $value ) {
								$result[] = $value;
							}
						}
					}
				}
			}

			return $result;
		}

		/**
		 * Show debug_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return void
		 */
		public function debug_info() {
			global $wpdb, $wp_version;
			$debug_info                                  = array();
			$debug_info['Home URL']                      = home_url();
			$debug_info['Site URL']                      = site_url();
			$debug_info['PHP']                           = PHP_VERSION;
			$debug_info['MYSQL']                         = $wpdb->db_version();
			$debug_info['WordPress']                     = $wp_version;
			$debug_info['rtBiz']                         = RTBIZ_VERSION;
			$debug_info['Helpdesk']                      = RTBIZ_HD_VERSION;
			$debug_info['OS']                            = PHP_OS;
			$debug_info['[php.ini] post_max_size']       = ini_get( 'post_max_size' );
			$debug_info['[php.ini] upload_max_filesize'] = ini_get( 'upload_max_filesize' );
			$debug_info['[php.ini] memory_limit']        = ini_get( 'memory_limit' );
			$debug_info['Installed Plugins']             = $this->get_plugin_info();
			$active_theme                                = wp_get_theme();
			$debug_info['Theme Name']                    = $active_theme->Name;
			$debug_info['Theme Version']                 = $active_theme->Version;
			$debug_info['Author URL']                    = $active_theme->{'Author URI'};
			$debug_info['Template Overrides']            = implode( ', <br/>', $this->scan_template_files( RTBIZ_HD_PATH . 'public/templates/' ) );

			$index_table = rtbiz_hd_get_ticket_table_name();
			$sql         = "select count(*) from {$index_table}";
			global $wpdb;
			$results = $wpdb->get_var( $sql );
			if ( $results ) {
				$debug_info['Total Ticket'] = $results;
			}
			$this->debug_info = $debug_info;
		}

		/**
		 * Generate debug_info html.
		 *
		 * @access public
		 *
		 * @param string $page
		 *
		 * @internal param $void
		 *
		 */
		public function debug_info_html( $page = '' ) {
			$this->debug_info();
			?>
			<div id="debug-info" class="rtbiz-hd-option-wrapper">
			<h3 class="rtbiz-hd-option-title"><?php _e( 'Debug Info', RTBIZ_HD_TEXT_DOMAIN ); ?></h3>
			<table class="form-table rtbiz-hd-debug-info">
				<tbody>
				<?php
				if ( $this->debug_info ) {
					foreach ( $this->debug_info as $configuration => $value ) {
						?>
						<tr>
						<th scope="row"><?php echo $configuration; ?></th>
						<td><?php echo $value; ?></td>
						</tr><?php
					}
				}
				?>
				</tbody>
			</table>
			</div><?php
		}


		/**
		 * Generate rtbiz_hd admin form.
		 *
		 * @global array $current_user
		 *
		 * @param  string $form
		 *
		 * @return void
		 */
		public function get_form( $form = '' ) {
			if ( empty( $form ) ) {
				$form = ( isset( $_POST['form'] ) ) ? $_POST['form'] : '';
			}
			if ( $form == '' ) {
				$form = 'premium_support';
			}
			switch ( $form ) {
				case 'bug_report':
					$meta_title = __( 'Submit a Bug Report', RTBIZ_HD_TEXT_DOMAIN );
					break;
				case 'new_feature':
					$meta_title = __( 'Submit a New Feature Request', RTBIZ_HD_TEXT_DOMAIN );
					break;
				case 'premium_support':
					$meta_title = __( 'Submit Support Request', RTBIZ_HD_TEXT_DOMAIN );
					break;
			}

			$status = get_option( 'rtbiz_hd_edd_license_status' );
			if ( 'premium_support' == $form ) {
				if ( false === $status && 'valid' != $status ) {
					$content = '<h3>' . $meta_title . '</h3>';
					$content .= '<p>' . __( 'If you have any suggestions, enhancements or bug reports, you can open a new issue on <a target="_blank" href="https://github.com/rtCamp/rtbiz/issues/new">GitHub</a>.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';

					echo $content;
				} else { ?>
					<h3 class="rtbiz-hd-option-title"><?php echo $meta_title; ?></h3>
					<div id="support-form" class="rtbiz-hd-support-form rtbiz-hd-option-wrapper">

						<div class="rtbiz-hd-form-filed clearfix">
							<label
							       for="name"><?php _e( 'Name', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
							<input id="name" type="text" name="name" value="" required/>
							<span class="rthd-tooltip">
								<i class="dashicons dashicons-info rtmicon"></i>
								<span class="rthd-tip">
									<?php _e( 'Use actual user name which used during purchased.', RTBIZ_HD_TEXT_DOMAIN ); ?>
								</span>
							</span>
						</div>

						<div class="rtbiz-hd-form-filed clearfix">
							<label
							       for="email"><?php _e( 'Email', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
							<input id="email" type="text" name="email" value="" required/>
							<span class="rthd-tooltip">
								<i class="dashicons dashicons-info rtmicon"></i>
								<span class="rthd-tip">
									<?php _e( 'Use email id which used during purchased', RTBIZ_HD_TEXT_DOMAIN ); ?>
								</span>
							</span>
						</div>

						<div class="rtbiz-hd-form-filed clearfix">
							<label
							       for="website"><?php _e( 'Website', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
							<input id="website" type="text" name="website"
							       value="<?php echo ( isset( $_REQUEST['website'] ) ) ? esc_attr( stripslashes( trim( $_REQUEST['website'] ) ) ) : get_bloginfo( 'url' ); ?>"
							       required/>
						</div>

						<div class="rtbiz-hd-form-filed clearfix">
							<label
							       for="subject"><?php _e( 'Subject', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
							<input id="subject" type="text" name="subject"
							       value="<?php echo ( isset( $_REQUEST['subject'] ) ) ? esc_attr( stripslashes( trim( $_REQUEST['subject'] ) ) ) : ''; ?>"
							       required/>
						</div>

						<div class="rtbiz-hd-form-filed clearfix">
							<label
							       for="details"><?php _e( 'Details', RTBIZ_HD_TEXT_DOMAIN ); ?></label>
							<textarea id="details" name="details"
							          required><?php echo ( isset( $_REQUEST['details'] ) ) ? esc_textarea( stripslashes( trim( $_REQUEST['details'] ) ) ) : ''; ?></textarea>

							<input type="hidden" name="request_type" value="<?php echo $form; ?>"/>
							<input type="hidden" name="request_id"
							       value="<?php echo wp_create_nonce( date( 'YmdHis' ) ); ?>"/>
							<input type="hidden" name="server_address" value="<?php echo $_SERVER['SERVER_ADDR']; ?>"/>
							<input type="hidden" name="ip_address" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>"/>
							<input type="hidden" name="server_type" value="<?php echo $_SERVER['SERVER_SOFTWARE']; ?>"/>
							<input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>"/>
						</div>
					</div><!-- .submit-bug-box -->

					<div class="rtbiz-hd-form-filed rtbiz-hd-button-wrapper clearfix">
						<?php submit_button( 'Submit', 'primary', 'rtbiz_hd-submit-request', false ); ?>
						<?php // submit_button( 'Cancel', 'secondary', 'cancel-request', false ); ?>
					</div>

					<?php
				}
			}
		}

		/**
		 * Now submit request.
		 *
		 * @global type $rtbiz_hd
		 *
		 * @param       void
		 *
		 * @return void
		 */
		public function submit_request() {
			$this->debug_info();
			//$form_data = wp_parse_args( $_POST['form_data'] );
			$form_data = $_POST;
			foreach ( $form_data as $key => $formdata ) {
				if ( '' == $formdata && 'phone' != $key ) {
					echo 'false';
					die();
				}
			}
			if ( 'premium_support' == $form_data['request_type'] ) {
				$mail_type = 'Premium Support';
				$title     = __( 'rtMedia Premium Support Request from', RTBIZ_HD_TEXT_DOMAIN );
			} elseif ( 'new_feature' == $form_data['request_type'] ) {
				$mail_type = 'New Feature Request';
				$title     = __( 'rtMedia New Feature Request from', RTBIZ_HD_TEXT_DOMAIN );
			} elseif ( 'bug_report' == $form_data['request_type'] ) {
				$mail_type = 'Bug Report';
				$title     = __( 'rtMedia Bug Report from', RTBIZ_HD_TEXT_DOMAIN );
			} else {
				$mail_type = 'Bug Report';
				$title     = __( 'rtMedia Contact from', RTBIZ_HD_TEXT_DOMAIN );
			}
			$message = '<html>
                            <head>
                                    <title>' . $title . get_bloginfo( 'name' ) . '</title>
                            </head>
                            <body>
				<table>
                                    <tr>
                                        <td>Name</td><td>' . strip_tags( $form_data['name'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Email</td><td>' . strip_tags( $form_data['email'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Website</td><td>' . strip_tags( $form_data['website'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Subject</td><td>' . strip_tags( $form_data['subject'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Details</td><td>' . strip_tags( $form_data['details'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Request ID</td><td>' . strip_tags( $form_data['request_id'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Server Address</td><td>' . strip_tags( $form_data['server_address'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>IP Address</td><td>' . strip_tags( $form_data['ip_address'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>Server Type</td><td>' . strip_tags( $form_data['server_type'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>User Agent</td><td>' . strip_tags( $form_data['user_agent'] ) . '</td>
                                    </tr>';
			if ( 'bug_report' == $form_data['request_type'] ) {
				$message .= '<tr>
                                        <td>WordPress Admin Username</td><td>' . strip_tags( $form_data['wp_admin_username'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>WordPress Admin Password</td><td>' . strip_tags( $form_data['wp_admin_pwd'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>SSH FTP Host</td><td>' . strip_tags( $form_data['ssh_ftp_host'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>SSH FTP Username</td><td>' . strip_tags( $form_data['ssh_ftp_username'] ) . '</td>
                                    </tr>
                                    <tr>
                                        <td>SSH FTP Password</td><td>' . strip_tags( $form_data['ssh_ftp_pwd'] ) . '</td>
                                    </tr>
                                    ';
			}
			$message .= '</table>';
			if ( $this->debug_info ) {
				$message .= '<h3>' . __( 'Debug Info', RTBIZ_HD_TEXT_DOMAIN ) . '</h3>';
				$message .= '<table>';
				foreach ( $this->debug_info as $configuration => $value ) {
					$message .= '<tr>
                                    <td style="vertical-align:top">' . $configuration . '</td><td>' . $value . '</td>
                                </tr>';
				}
				$message .= '</table>';
			}
			$message .= '</body>
                </html>';
			add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
			$headers       = 'From: ' . $form_data['name'] . ' <' . $form_data['email'] . '>' . "\r\n";
			$support_email = 'support@rtcamp.com';
			if ( wp_mail( $support_email, '[Helpdesk] ' . $mail_type . ' from ' . str_replace( array(
					'http://',
					'https://'
				), '', $form_data['website'] ), stripslashes( $message ), $headers ) ) {

//				echo '<div class="rtbiz_hd-success" style="margin:10px 0;">';
//				if ( 'new_feature' == $form_data['request_type'] ) {
//					echo '<p>' . __( 'Thank you for your Feedback/Suggestion.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
//				} else {
//					echo '<p>' . __( 'Thank you for posting your support request.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
//					echo '<p>' . __( 'We will get back to you shortly.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
//				}
//				echo '</div>';
//			} else {
//				echo '<div class="rtbiz_hd-error">';
//				echo '<p>' . __( 'Your server failed to send an email.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
//				echo '<p>' . __( 'Kindly contact your server support to fix this.', RTBIZ_HD_TEXT_DOMAIN ) . '</p>';
//				echo '<p>' . sprintf( __( 'You can alternatively create a support request <a href="%s">here</a>', RTBIZ_HD_TEXT_DOMAIN ), 'https://rtcamp.com/premium-support/' ) . '</p>';
//				echo '</div>';
			}
//			die();
		}

	}

}
