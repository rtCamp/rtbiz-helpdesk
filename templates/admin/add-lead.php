<?php
global $rt_crm_module, $rt_crm_attributes, $rt_crm_closing_reason, $rt_crm_contacts, $rt_crm_accounts, $rt_crm_settings, $rt_crm_leads, $rt_crm_lead_history_model;

if( ! isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != $rt_crm_module->post_type ) {
	wp_die("Opsss!! You are in restricted area");
}

$labels = $rt_crm_module->labels;
$module_settings = rthd_get_settings();
$post_type = $_REQUEST['post_type'];
$leadModel = new Rt_CRM_Lead_Model();

if ( !isset($_REQUEST[$post_type.'_id']) && !current_user_can( "publish_{$post_type}s" ) ) {
	wp_die("Opsss!! You are in restricted area");
}

$user_edit = false;
if ( current_user_can( "edit_{$post_type}" ) ) {
	$user_edit = 'true';
} else if ( current_user_can( "read_{$post_type}" ) ) {
	$user_edit = 'false';
} else {
	wp_die("Opsss!! You are in restricted area");
}

if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'trash' && isset( $_REQUEST[$post_type.'_id'] ) ) {
	wp_trash_post( $_REQUEST[$post_type.'_id'] );
	$leadModel->update_lead( array( 'post_status' => 'trash' ), array( 'post_id' => $_REQUEST[$post_type.'_id'] ) );
    $return = wp_redirect( admin_url( 'edit.php?post_type='.$post_type.'&page=rtcrm-all-'.$post_type ) );
    if( !$return ) {
        echo '<script> window.location="' . admin_url( 'edit.php?post_type='.$post_type.'&page=rtcrm-all-'.$post_type ) . '"; </script> ';
    }
}

$closing_reason_history_id = '';
if ( isset( $_POST['post'] ) ) {

	// Lead Creation Date -- Can be new function
    $newLead = $_POST['post'];
    $creationdate = $newLead['post_date'];
    if ( isset( $creationdate ) && $creationdate != '' ) {
        try {
            $dr = date_create_from_format( 'M d, Y H:i A', $creationdate );
			$UTC = new DateTimeZone('UTC');
			$dr->setTimezone($UTC);
			$timeStamp = $dr->getTimestamp();
            $newLead['post_date'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
			$newLead['post_date_gmt'] = gmdate('Y-m-d H:i:s', (intval($timeStamp)));
        } catch ( Exception $e ) {
            $newLead['post_date'] = current_time( 'mysql' );
			$newLead['post_date_gmt'] = gmdate('Y-m-d H:i:s');
        }
    } else {
        $newLead['post_date'] = current_time( 'mysql' );
		$newLead['post_date_gmt'] = gmdate('Y-m-d H:i:s');
    }

	// Post Data to be saved.
    $post = array(
        'post_author' => $newLead['post_author'],
        'post_content' => $newLead['post_content'],
        'post_status' => $newLead['post_status'],
        'post_title' => $newLead['post_title'],
        'post_date' => $newLead['post_date'],
        'post_date_gmt' => $newLead['post_date_gmt'],
        'post_type' => $post_type
    );

	// Diff Email between new lead data & old lead data -- can be new function
	$emailTable = "<table style='width: 100%; border-collapse: collapse; border: none;'>";
    $emailHTML = "";
    $updateFlag = false;
    if ( isset($newLead['post_id'] ) ) {
        $updateFlag = true;
        $oldpost = get_post ( $newLead['post_id'], ARRAY_A );
        $diff = rtcrm_text_diff( $oldpost['post_title'], $post['post_title'] );
        if ( $diff ) {
            $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Title</th><td>' . $diff . '</td><td></td></tr>';
        }
        $diff = rtcrm_text_diff( $oldpost['post_status'], $post['post_status'] );
        if ( $diff ) {
            $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Status </th><td>' . $diff . '</td><td></td></tr>';
			/* Insert History for status */
	        $id = $rt_crm_lead_history_model->insert( array(
					'lead_id' => $newLead['post_id'],
			        'type' => 'post_status',
			        'old_value' => $oldpost['post_status'],
			        'new_value' => $post['post_status'],
					'update_time' => current_time( 'mysql' ),
			        'updated_by' => get_current_user_id(),
		        )
	        );
	        if ( $post['post_status'] === 'closed' ) {
		        $closing_reason_history_id = $id;
	        }
        }
        $oldUser = get_user_by( 'id', $oldpost['post_author'] );
        $newUser = get_user_by( 'id', $post['post_author'] );

        $diff = rtcrm_text_diff( $oldUser->display_name, $newUser->display_name );
        if ( $diff ) {
            $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Assigned To</th><td>' . $diff . '</td><td></td></tr>';
        }

        $diff = rtcrm_text_diff( strip_tags( $oldpost['post_content'] ), strip_tags( $post['post_content'] ) );
        if ( $diff ) {
            $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Lead Content</th><td>' . $diff . '</td><td></td></tr>';
        }
        $post = array_merge( $post, array( 'ID' => $newLead['post_id'] ) );
        $post_id = @wp_update_post( $post );

		/* Update Index Table */
		$data = array(
			'assignee' => $post['post_author'],
			'post_content' => $post['post_content'],
			'post_status' => $post['post_status'],
			'post_title' => $post['post_title'],
			'date_create' => $post['post_date'],
			'date_create_gmt' => $post['post_date_gmt'],
			'date_update' => current_time( 'mysql' ),
			'date_update_gmt' => gmdate('Y-m-d H:i:s'),
			'user_updated_by' => get_current_user_id(),
		);
		$where = array( 'post_id' => $post_id );
		$leadModel->update_lead( $data, $where );

		/* System Notification - Lead Updated */

    } else {
        $emailHTML .= '<tr><td> Title </td><td>' . $post['post_title'] . '</td></tr>';
        $emailHTML .= '<tr><td> Status </td><td>' . $post['post_status'] . '</td></tr>';
        $emailHTML .= '<tr><td> Assigned To </td><td>' . $post['post_author'] . '</td></tr>';
        $emailHTML .= '<tr><td> Content </td><td>' . $post["post_content"] . '</td></tr>';
        $post_id = @wp_insert_post($post);

		/* Insert into Index Table */
		$data = array(
			'post_id' => $post_id,
			'assignee' => $post['post_author'],
			'post_content' => $post['post_content'],
			'post_status' => $post['post_status'],
			'post_title' => $post['post_title'],
			'date_create' => $post['post_date'],
			'date_create_gmt' => $post['post_date_gmt'],
			'date_update' => $post['post_date'],
			'date_update_gmt' => $post['post_date_gmt'],
			'user_updated_by' => get_current_user_id(),
			'user_created_by' => get_current_user_id(),
		);
		$leadModel->add_lead( $data );
		/* System Notification - Lead Inserted */

	    /* Insert History for status */
	    $id = $rt_crm_lead_history_model->insert(array(
			    'lead_id' => $post_id,
			    'type' => 'post_status',
			    'old_value' => '',
			    'new_value' => $post['post_status'],
				'update_time' => current_time( 'mysql' ),
			    'updated_by' => get_current_user_id(),
		    )
	    );
	    if ( $post['post_status'] === 'closed' ) {
			$closing_reason_history_id = $id;
	    }

	    $unique_id = get_post_meta( $post_id, 'rtcrm_unique_id', true );
		if( empty( $unique_id ) ) {
			$d = new DateTime($newLead['post_date']);
			$UTC = new DateTimeZone("UTC");
			$d->setTimezone($UTC);
			$timeStamp = $d->getTimestamp();
			$post_date_gmt = gmdate('Y-m-d H:i:s', (intval($timeStamp)));
			$unique_id = md5( 'rtcrm_'.$post_type.'_'.$post_date_gmt );
			update_post_meta( $post_id, 'rtcrm_unique_id', $unique_id );
		}
    }

	// Save post meta
	$postmeta = apply_filters( 'rt_crm_lead_meta', $_POST['postmeta'] );
	if ( ! empty( $postmeta ) ) {
        foreach ( $postmeta as $meta ) {
            update_post_meta( $post_id, $meta['key'], $meta['value'] );
        }
    }

    if ( is_wp_error( $post_id ) ) {
        wp_die( 'Error while creating new '. ucfirst( $labels['name'] ) );
    }

	// Closing Date Diff & Save
    if ( isset( $newLead['closing-date'] ) && !empty( $newLead['closing-date'] )) {
		$oldclosingdate = get_post_meta( $post_id, "lead_closing_date", true );
        $diff = rtcrm_text_diff( $oldclosingdate, $newLead["closing-date"] );
        if ( $diff ) {
            $emailHTML .= '<tr><th style="padding: .5em;border: 0;">Closing Date</th><td>' . $diff . '</td><td></td></tr>';
        }
        update_post_meta( $post_id, 'lead_closing_date', $newLead['closing-date'] );

		/* Update Index Table */
		$cd = new DateTime( $newLead['closing-date'] );
		$UTC = new DateTimeZone('UTC');
		$cd->setTimezone($UTC);
		$timeStamp = $cd->getTimestamp();
		$data = array(
			'date_closing' => gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 ))),
			'date_closing_gmt' => gmdate('Y-m-d H:i:s', (intval($timeStamp))),
			'user_closed_by' => get_current_user_id(),
			'date_update' => current_time( 'mysql' ),
			'date_update_gmt' => gmdate('Y-m-d H:i:s'),
			'user_updated_by' => get_current_user_id(),
		);
		$where = array( 'post_id' => $post_id );
		$leadModel->update_lead( $data, $where );
		/* System Notification -- Closing Date Changed */
    }

	// Subscribers Save & Diff
	if ( !isset( $_POST['subscribe_to'] ) ) {
        $_POST['subscribe_to'] = array();
        if( intval( $newLead['post_author'] ) != get_current_user_id() && !$updateFlag ) {
            $_POST['subscribe_to'][] = get_current_user_id();
        }
    }
    //Unscuscribe logic
    $oldSubscriberArr = get_post_meta( $post_id, 'subscribe_to', true );
    if ( !$oldSubscriberArr ) {
        $oldSubscriberArr = array();
    }

    $removedSUbscriber = array_diff( $oldSubscriberArr, $_POST['subscribe_to'] );
    $newAddedSubscriber = array_diff( $_POST['subscribe_to'], $oldSubscriberArr );

    $newSubscriberList = array();
    $oldSubscriberList = array();
    $bccemails = array();
    foreach ( $_POST['subscribe_to'] as $emailsubscriber ) {
        $userSub = get_user_by( 'id', intval( $emailsubscriber ) );
        $bccemails[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
        if ( in_array( $emailsubscriber, $newAddedSubscriber ) ) {
            $newSubscriberList[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
        }
    }
    if ( !empty( $removedSUbscriber ) ) {
        foreach ( $removedSUbscriber as $emailsubscriber ) {
            $userSub = get_user_by( 'id', intval( $emailsubscriber ) );
            $oldSubscriberList[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
        }
    }
    update_post_meta( $post_id, 'subscribe_to', $_POST['subscribe_to'] );

	// Attachments
	$old_attachments = get_posts( array(
		'post_parent' => $post_id,
		'post_type' => 'attachment',
		'fields' => 'ids',
        'posts_per_page' => -1,
	));
	$new_attachments = array();
	if ( isset( $_POST['attachment'] ) ) {
		$new_attachments = $_POST['attachment'];
		foreach ( $new_attachments as $attachment ) {
			if( !in_array( $attachment, $old_attachments ) ) {
				$file = get_post($attachment);
				$filepath = get_attached_file( $attachment );

				$post_attachment_hashes = get_post_meta( $post_id, '_rt_wp_crm_attachment_hash' );
				if ( ! empty( $post_attachment_hashes ) && in_array( md5_file( $filepath ), $post_attachment_hashes ) ) {
					continue;
				}

				if( !empty( $file->post_parent ) ) {
					$args = array(
						'post_mime_type' => $file->post_mime_type,
						'guid' => $file->guid,
						'post_title' => $file->post_title,
						'post_content' => $file->post_content,
						'post_parent' => $post_id,
						'post_author' => get_current_user_id(),
					);
					wp_insert_attachment( $args, $file->guid, $post_id );

					add_post_meta( $post_id, '_rt_wp_crm_attachment_hash', md5_file( $filepath ) );

				} else {
					wp_update_post( array( 'ID' => $attachment, 'post_parent' => $post_id ) );
					$file = get_attached_file( $attachment );
					add_post_meta( $post_id, '_rt_wp_crm_attachment_hash', md5_file( $filepath ) );
				}
			}
		}

		foreach ( $old_attachments as $attachment ) {
			if( !in_array( $attachment, $new_attachments ) ) {
				wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
				$filepath = get_attached_file( $attachment );
				delete_post_meta($post_id, '_rt_wp_crm_attachment_hash', md5_file( $filepath ) );
			}
		}
	} else {
		foreach ( $old_attachments as $attachment ) {
			wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
			$filepath = get_attached_file( $attachment );
			delete_post_meta($post_id, '_rt_wp_crm_attachment_hash', md5_file( $filepath ) );
		}
	}
	$old_attachments_title = array();
	foreach ($old_attachments as $attachment) {
		$old_attachments_title[] = get_the_title( $attachment );
	}
	$old_attachments_str = implode(' , ', $old_attachments_title);
	$new_attachments_title = array();
	foreach ($new_attachments as $attachment) {
		$new_attachments_title[] = get_the_title( $attachment );
	}
	$new_attachments_str = implode(' , ', $new_attachments_title);
	$diff = rtcrm_text_diff( $old_attachments_str, $new_attachments_str );
	if ( $diff ) {
		$emailHTML .= '<tr><th style="padding: .5em;border: 0;">Attachments</th><td>' . $diff . '</td><td></td></tr>';
	}

	// External File Links
	$old_ex_files = get_post_meta( $post_id, 'lead_external_file' );
	$new_ex_files = array();
	if ( isset( $_POST['lead_ex_files'] ) ) {
		$new_ex_files = $_POST['lead_ex_files'];

		delete_post_meta( $post_id, 'lead_external_file' );

		foreach ( $new_ex_files as $ex_file ) {
			if ( empty( $ex_file['link'] ) ) {
				continue;
			}
			if( empty( $ex_file['title'] ) ) {
				$ex_file['title'] = $ex_file['link'];
			}
			add_post_meta( $post_id, 'lead_external_file', json_encode( $ex_file ) );
		}
	} else {
		delete_post_meta( $post_id, 'lead_external_file' );
	}

	$old_ex_links = array();
	$new_ex_links = array();
	foreach ( $old_ex_files as $ex_file ) {
		$data = json_decode( $ex_file );
		$old_ex_links[] = '<a href="'.$data['link'].'" target="_blank">'.$data['title'].'</a>';
	}
	foreach ( $new_ex_files as $ex_file ) {
		if ( empty( $ex_file['title'] ) ) {
			$ex_file['title'] = $ex_file['link'];
		}
		$new_ex_links[] = '<a href="'.$ex_file['link'].'" target="_blank">'.$ex_file['title'].'</a>';
	}
	$diff = rtcrm_text_diff( implode( ' , ', $old_ex_links ), implode( ' , ', $new_ex_links ) );
	if ( $diff ) {
		$emailHTML .= '<tr><th style="padding: .5em;border: 0;">External Files Links</th><td>' . $diff . '</td><td></td></tr>';
	}

	// Cloasing Reason
	$closing_reason_diff = $rt_crm_closing_reason->closing_reason_diff( $post_id, $newLead );
	$emailHTML .= $closing_reason_diff;
	if ( !empty( $closing_reason_diff ) ) {
		$rt_crm_closing_reason->save_closing_reason( $post_id, $newLead );
		/* Update Index Table */
		$attr_name = str_replace('-', '_', rtcrm_attribute_taxonomy_name( 'closing_reason' ) );
		$attr_val = (!isset($newLead['closing_reason'])) ? array() : $newLead['closing_reason'];
		$data = array(
			$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
			'date_update' => current_time( 'mysql' ),
			'date_update_gmt' => gmdate('Y-m-d H:i:s'),
			'user_updated_by' => get_current_user_id(),
		);
		$where = array( 'post_id' => $post_id );
		$leadModel->update_lead( $data, $where );
		/* System Notification -- Closing Reason Changed */

		/* Insert History for status - closing reason */
		if ( $closing_reason_history_id ) {
			$terms = wp_get_post_terms( $post_id, rtcrm_attribute_taxonomy_name( 'closing_reason' ) );
			$message = '';
			foreach ( $terms as $term ) {
				if ( empty( $message ) ) {
					$message .= $term->name;
				} else {
					$message .= ( ' , ' . $term->name );
				}
			}
			$rt_crm_lead_history_model->update( array( 'message' => $message ), array( 'id' => $closing_reason_history_id ) );
		} else {
			$terms = wp_get_post_terms( $post_id, rtcrm_attribute_taxonomy_name( 'closing_reason' ) );
			$message = '';
			foreach ( $terms as $term ) {
				if ( empty( $message ) ) {
					$message .= $term->name;
				} else {
					$message .= ( ' , ' . $term->name );
				}
			}
			$rt_crm_lead_history_model->insert(array(
					'lead_id' => $post_id,
					'type' => 'post_status',
					'old_value' => $oldpost['post_status'],
					'new_value' => $post['post_status'],
					'message' => $message,
					'update_time' => current_time( 'mysql' ),
					'updated_by' => get_current_user_id(),
				)
			);
		}
	}

// Attributes Diff & Save
	$attributes = rtcrm_get_attributes( $post_type );
	foreach ( $attributes as $attr ) {
		$attr_diff = $rt_crm_attributes->attribute_diff( $attr, $post_id, $newLead );
		$emailHTML .= $attr_diff;
		if ( !empty( $attr_diff ) ) {
			$rt_crm_attributes->save_attributes( $attr, $post_id, $newLead );
			/* Update Index Table */
			$attr_name = str_replace('-', '_', rtcrm_attribute_taxonomy_name($attr->attribute_name));
			$attr_val = (!isset($newLead[$attr->attribute_name])) ? array() : $newLead[$attr->attribute_name];
			$data = array(
				$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
				'date_update' => current_time( 'mysql' ),
				'date_update_gmt' => gmdate('Y-m-d H:i:s'),
				'user_updated_by' => get_current_user_id(),
			);
			$where = array( 'post_id' => $post_id );
			$leadModel->update_lead( $data, $where );
			/* System Notification -- Attribute Changed */
		}
	}

	// Contacts
	if ( $module_settings['attach_contacts'] == 'yes' ) {
		$contact_diff = $rt_crm_contacts->contacts_diff_on_lead( $post_id, $newLead );
		$emailHTML .= $contact_diff;
		if ( !empty( $contact_diff ) ) {
			$rt_crm_contacts->contacts_save_on_lead( $post_id, $newLead );
			/* Update Index Table */
			$contact_name = rt_biz_get_person_post_type();
			$contact_val = ( ! isset( $newLead['contacts'] ) ) ? array() : $newLead['contacts'];
			$contact_val = array_map( 'intval', $contact_val );
			$contact_val = array_unique( $contact_val );
			$data = array(
				$contact_name => implode( ',', $contact_val ),
				'date_update' => current_time( 'mysql' ),
				'date_update_gmt' => gmdate('Y-m-d H:i:s'),
				'user_updated_by' => get_current_user_id(),
			);
			$where = array( 'post_id' => $post_id );
			$leadModel->update_lead( $data, $where );
			/* System Notification -- Contacts updated */
		}
	}

	// Accounts
	if ( $module_settings['attach_accounts'] == 'yes' ) {
		$account_diff = $rt_crm_accounts->accounts_diff_on_lead( $post_id, $newLead );
		$emailHTML .= $account_diff;
		if ( !empty( $account_diff ) ) {
			$rt_crm_accounts->accounts_save_on_lead( $post_id, $newLead );
			/* Update Index Table */
			$account_name = rt_biz_get_organization_post_type();
			$account_val = (!isset($newLead['accounts'])) ? array() : $newLead['accounts'];
			$account_val = array_map('intval', $account_val);
			$account_val = array_unique($account_val);
			$data = array(
				$account_name => implode( ',', $account_val ),
				'date_update' => current_time( 'mysql' ),
				'date_update_gmt' => gmdate('Y-m-d H:i:s'),
				'user_updated_by' => get_current_user_id(),
			);
			$where = array( 'post_id' => $post_id );
			$leadModel->update_lead( $data, $where );
			/* System Notification -- Accounts updated */
		}
	}



// Email Generation
    $flag = true;
    $systemEmail = ( isset( $module_settings['system_email'] ) ) ? $module_settings['system_email'] : '';
    if ($systemEmail) {
        if (!is_email($systemEmail)) {
            $flag = false;
        }
    } else {
        $flag = false;
    }
    if ($flag) {
        $accessToken = $rt_crm_settings->get_accesstoken_from_email( $systemEmail, $signature, $email_type, $imap_server );
        $crmZendEmail = new Rt_CRM_Zend_Mail();
        if (strpos($signature, "</") == false) {
            $signature = htmlentities($signature);
            $signature = preg_replace('/(\n|\r|\r\n)/i', "<br />", $signature);
            $signature = preg_replace('/  /i', "  ", $signature);
        }
        $title_suffix = $rt_crm_leads->create_title_for_mail( $post_id );
        $current_user = wp_get_current_user();
        if ($updateFlag) {
            if ($oldUser->ID != $newUser->ID) {


                $title = "[Assigned You] " . $title_suffix;
                $emailHTML1 = "<b>" . $current_user->display_name ."</b> assigned you new lead.<br /> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";

                $rt_crm_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), array(array("email" => $newUser->user_email, "name" => $newUser->display_name)), array(), $post_id, "post");
                $title = "[Reassigned] " . $title_suffix;
                $emailHTML1 = "You are no longer responsible for this lead. It has been reassigned to " . $newUser->display_name . " .<br /> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                $rt_crm_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), array(array("email" => $oldUser->user_email, "name" => $oldUser->display_name)), array(), $post_id, "post");
            }

            if (!empty($newSubscriberList)) {
                $title = "[Subscribe] " . $title_suffix;
                $emailHTML1 = "You have been <b>subscribed</b> to this lead.<br /> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                $rt_crm_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), $newSubscriberList, array(), $post_id, "post");
            }
            if (!empty($oldSubscriberList)) {
                $title = "[Unsubscribe] " . $title_suffix;
                $emailHTML1 = "You have been <b>unsubscribed</b> to this lead.<br /> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                $rt_crm_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), $oldSubscriberList, array(), $post_id, "post");
            }
            if ($emailHTML != "" && !empty($bccemails)) {
                $emailHTML = $emailTable . $emailHTML . "</table> </br> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                $emailHTML .= "<br />" . 'Lead updated by : <a target="_blank" href="">'.get_the_author_meta( 'display_name', get_current_user_id() ).'</a>';
                $emailHTML .= "<br />" . $signature;
                $title = "[Lead Updated] " . $title_suffix;
                $rt_crm_settings->insert_new_send_email($systemEmail, $title, stripslashes($emailHTML), array(), array(), $bccemails, array(), $post_id, "post");
            }
        } else {
            $newUser = get_user_by("id", $post["post_author"]);
            if ($newUser) {
                $title = "[Assigned You] " . $title_suffix;
                $emailHTML = "New lead assigned to you.<br /> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                $rt_crm_settings->insert_new_send_email($systemEmail, $title, $emailHTML, array(), array(), array(array("email" => $newUser->user_email, "name" => $newUser->display_name)), array(), $post_id, "post");
            }
            if (!empty($bccemails)) {
                $title = "[Subscribe] " . $title_suffix;
                $emailHTML = "You have been <b>subscribed</b> to this lead.<br /> To View Lead Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                $rt_crm_settings->insert_new_send_email($systemEmail, $title, $emailHTML, array(), array(), $bccemails, array(), $post_id, "post");
            }
        }
    }
    $_REQUEST[$post_type."_id"] = $post_id;
}
?>
<div class="rtcrm-container">
<?php
global $wpdb;
$meta_key_results = $wpdb->get_results(" select distinct meta_key from $wpdb->postmeta inner join $wpdb->posts on post_id=ID
                                             and post_type='{$post_type}' and  not meta_key like '\_%' order by meta_key");
echo "<script> var arr_leadmeta_key=" . json_encode($meta_key_results) . "; </script>";

$form_ulr = admin_url("edit.php?post_type={$post_type}&page=rtcrm-add-{$post_type}");
if (isset($_REQUEST["{$post_type}_id"])) {
    $form_ulr .= "&{$post_type}_id=" . $_REQUEST["{$post_type}_id"];
    if (isset($_REQUEST["new"])) {
        ?>
        <div class="alert-box success">
        <?php _e('New '.  ucfirst($labels['name']).' Inserted Sucessfully.'); ?>
            <a href="#" class="close">&times;</a>
        </div>
            <?php
        }
        if(isset($updateFlag) && $updateFlag){ ?>
        <div class="alert-box success">
        <?php _e(ucfirst($labels['name'])." Updated Sucessfully."); ?>
            <a href="#" class="close">&times;</a>
        </div>


        <?php }
        $post = get_post($_REQUEST["{$post_type}_id"]);
        if (!$post) {
            ?>
        <div class="alert-box alert">
            Invalid Post ID
            <a href="" class="close">&times;</a>
        </div>
        <?php
        $post = false;
    }
    if ( $post->post_type != $rt_crm_module->post_type ) {
        ?>
        <div class="alert-box alert">
            Invalid Post Type
            <a href="" class="close">&times;</a>
        </div>
        <?php
        $post = false;
    }

    $create = new DateTime($post->post_date);

    $modify = new DateTime($post->post_modified);
    $createdate = $create->format("M d, Y h:i A");
    $modifydate = $modify->format("M d, Y h:i A");

	$close_date_meta = get_post_meta($post->ID, 'lead_closing_date', true);
	if(!empty($close_date_meta)) {
		$closingdate = new DateTime($close_date_meta);
		$closingdate = $closingdate->format('M d, Y h:i A');
	} else {
		$closingdate = '';
	}
}
?>
<div  id="add-new-post" class="row">
	<?php if( $user_edit ) { ?>
	    <form method='post' id="form-add-post" action="<?php echo $form_ulr; ?>">
	<?php } ?>
<?php if (isset($post->ID) && $user_edit ) { ?>
            <input type="hidden" name="post[post_id]" id='lead_id' value="<?php echo $post->ID; ?>" />
<?php } ?>

        <div class="row">
            <div class="large-7 columns">
        <?php
        if (isset($post->ID)) {
			$post_icon = "foundicon-".( ( $user_edit ) ? 'edit' : 'view-mode' );
            $page_title = ucfirst($labels['name']);
            $save_button = "Update ".ucfirst($labels['name']);
        } else {
            $post_icon = "foundicon-add-doc";
            $page_title = "Add ".ucfirst($labels['name']);
            $save_button = "Save ".ucfirst($labels['name']);
        }
        ?>
                <h4><i class="gen-enclosed <?php echo $post_icon; ?>"></i> <?php _e($page_title); ?></h4>
            </div>
            <div class="large-5 columns rtcrm-sticky">
                <?php if(isset($post->ID) && current_user_can( "delete_{$post_type}s" ) ){ ?>
                <button id="button-trash" type="button" class="right mybutton alert" ><?php _e("Trash"); ?></button>
                <?php } ?>
				<?php if( $user_edit ) { ?>
                <button class="right mybutton success" type="submit" ><?php _e($save_button); ?></button> &nbsp;&nbsp;
				<?php } ?>
				<?php if (isset($post->ID) && $user_edit) { ?>
				<button class="mybutton add-followup right" type="button" ><?php _e("Add Followup"); ?></button>
				<?php } ?>
            </div>
        </div>
        <div class="row">
            <div class="large-6 small-12 columns ui-sortable meta-box-sortables">
                <div class="row collapse postbox">
                    <div class="large-12 columns">
						<?php if( $user_edit ) { ?>
							<input name="post[post_title]" id="new_<?php echo $post_type ?>_title" type="text" placeholder="<?php _e(ucfirst($labels['name'])." Subject"); ?>" value="<?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?>" />
						<?php } else { ?>
							<span><?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?></span><br /><br />
						<?php } ?>
                    </div>
                </div>
                <div class="row collapse rtcrm-lead-content-wrapper">
                    <div class="large-12 columns">
						<?php
							if( $user_edit ) {
								wp_editor( ( isset( $post->ID ) ) ? $post->post_content : "", "post_content", array( 'textarea_name' => 'post[post_content]' ) );
							} else {
								echo ucfirst($labels['name']).' Content : <br /><br /><span>'.(( isset($post->ID) ) ? $post->post_content : '').'</span><br /><br />';
							}
						?>
                    </div>
                </div>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="general foundicon-tools"></i> <?php _e('Custom Fields'); ?></span></h6>
					<div class="inside">
<?php
$arrayMeta = array();
if (isset($post->ID)) {
    $post_metas = get_post_custom( $post->ID );
    $post_users_meta = array( "subscribe_to", "lead_closing_date", 'lead_external_file', 'rtcrm_unique_id' );
	$post_users_meta = array_merge( $post_users_meta, array_map( 'rtcrm_extract_key_from_attributes', rtcrm_get_attributes( $post_type, 'meta' ) ) );
    $count = 1;

	foreach ($post_metas as $key => $my_custom_field) {
        if (strpos($key, "_") === 0) {
            continue;
        }

        if (in_array($key, $post_users_meta)) {
            continue;
        }
        foreach ($my_custom_field as $tkey => $pmeta) {
			if(empty($pmeta)) {
				continue;
			}
            ?>
                            <div class="row collapse">
                                <div class="large-12 small-12 columns">
                                    <div class="large-4 small-4 columns">
										<?php if( $user_edit ) { ?>
	                                        <input type="text" name="postmeta[<?php echo $count; ?>'][key]" value="<?php echo $key; ?>" />
										<?php } else { ?>
											<span><?php echo $key.': '; ?></span>
										<?php } ?>
                                    </div>
                                    <div class="large-8 small-8 columns">
										<?php if( $user_edit ) { ?>
											<input type="text" name="postmeta[<?php echo $count; ?>'][value]" value="<?php echo $pmeta; ?>" />
										<?php } else { ?>
											<span><?php echo $pmeta; ?></span>
										<?php } ?>
                                    </div>
                                </div>
                            </div>
            <?php
            $arrayMeta[] = array("count" => $count, "key" => $key, "value" => $pmeta);
            $count++;
        }
    }
}
				if( $user_edit ) { ?>
							<div class="row collapse" id="add-meta-container">
								<div class="large-12 mobile-large-3 columns">
									<div class="large-4 columns">
										<input type="text" id='add_newpost_meta_key' placeholder="Key"/>
									</div>
									<div class="large-6 columns">
										<input type="text" id='add_newpost_meta_Value' placeholder="Value"/>
									</div>
									<div class="large-2 columns">
										<button id="add_new_meta" class="mybutton expand" type="button" >Add</button>
									</div>
								</div>
							</div>
				<?php } ?>
						</div>
					</div>
				<?php $attachments = array();
					if ( isset( $post->ID ) ) {
						$attachments = get_posts( array(
							'posts_per_page' => -1,
							'post_parent' => $post->ID,
							'post_type' => 'attachment',
						));
					}
				?>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-paper-clip"></i> <?php _e('Attachments'); ?></span></h6>
					<div class="inside">
						<div class="row collapse" id="attachment-container">
							<?php if( $user_edit ) { ?>
								<a href="#" class="button" id="add_lead_attachment"><?php _e('Add'); ?></a>
							<?php } ?>
							<div class="scroll-height">
								<?php foreach ($attachments as $attachment) { ?>
									<?php $extn_array = explode('.', $attachment->guid); $extn = $extn_array[count($extn_array) - 1]; ?>
									<div class="large-12 mobile-large-3 columns attachment-item" data-attachment-id="<?php echo $attachment->ID; ?>">
										<a target="_blank" href="<?php echo wp_get_attachment_url($attachment->ID); ?>">
											<img height="20px" width="20px" src="<?php echo RT_CRM_URL . "assets/file-type/" . $extn . ".png"; ?>" />
											<?php echo $attachment->post_title; ?>
										</a>
										<?php if( $user_edit ) { ?>
											<a href="#" class="rtcrm_delete_attachment right">x</a>
										<?php } ?>
										<input type="hidden" name="attachment[]" value="<?php echo $attachment->ID; ?>" />
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-paper-clip"></i> <?php _e( 'External Files' ); ?></span></h6>
					<div class="inside">
					<?php
					if ( isset( $post->ID ) ) {
						$lead_ex_files = get_post_meta( $post->ID, 'lead_external_file' );
						$count = 1;
						foreach ( $lead_ex_files as $ex_file ) {
							$ex_file = (array)json_decode( $ex_file );
						?>
						<div class="row collapse">
							<div class="large-12 small-12 columns">
								<div class="large-3 small-3 columns">
								<?php if( $user_edit ) { ?>
									<input type="text" name="lead_ex_files[<?php echo $count; ?>'][title]" value="<?php echo $ex_file['title']; ?>" />
								<?php } else { ?>
									<span><?php echo $key.': '; ?></span>
								<?php } ?>
								</div>
								<?php if( $user_edit ) { ?>
								<div class="large-8 small-8 columns">
									<input type="text" name="lead_ex_files[<?php echo $count; ?>'][link]" value="<?php echo $ex_file['link']; ?>" />
								</div>
								<div class="large-1 small-1 columns">
									<button class="removeMeta"><i class="foundicon-minus"></i></button>
								</div>
								<?php } else { ?>
								<div class="large-9 small-9 columns">
									<span><?php echo $pmeta; ?></span>
								</div>
								<?php } ?>
							</div>
						</div>
						<?php
							$count++;
						}
					}
					if( $user_edit ) {
					?>
						<div class="row collapse" id="external-files-container">
							<div class="large-12 mobile-large-3 columns">
								<div class="large-3 columns">
									<input type="text" id='add_ex_file_title' placeholder="Title"/>
								</div>
								<div class="large-7 columns">
									<input type="text" id='add_ex_file_link' placeholder="Link"/>
								</div>
								<div class="large-2 columns">
									<button id="add_new_ex_file" class="mybutton expand" type="button" ><?php _e( 'Add' ); ?></button>
								</div>
							</div>
						</div>
					<?php } ?>
					</div>
				</div>
			</div>
            <div class="large-3 small-12 columns ui-sortable meta-box-sortables">
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-idea"></i> <?php _e( ucfirst( $labels['name'] )." Information" ); ?></span></h6>
					<div class="inside">
						<div class="row collapse">
							<div class="small-4 large-4 columns">
								<span class="prefix" title="Status">Status</span>
							</div>
							<div class="small-8 large-8 columns <?php echo ( ! $user_edit ) ? 'rtcrm_attr_border' : ''; ?>">
<?php
if (isset($post->ID))
$pstatus = $post->post_status;
else
$pstatus = "";
$post_status = $rt_crm_module->get_custom_statuses();
$custom_status_flag = true;
?>
						<?php if( $user_edit ) { ?>
							<select id="rtcrm_post_status" class="right" name="post[post_status]">
<?php foreach ($post_status as $status) {
if ($status['slug'] == $pstatus) {
	$selected = 'selected="selected"';
	$custom_status_flag = false;
} else {
	$selected = '';
}
printf('<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name']);
} ?>
<?php if ( $custom_status_flag && isset( $post->ID ) ) { echo '<option selected="selected" value="'.$pstatus.'">'.$pstatus.'</option>'; } ?>
							</select>
							<?php } else {
								foreach ( $post_status as $status ) {
									if($status['slug'] == $pstatus) {
										echo '<span class="rtcrm_view_mode">'.$status['name'].'</span>';
										break;
									}
								}
							} ?>
						</div>
					</div>
					<div id="rtcrm_closing_reason_wrapper" class="row collapse <?php echo ( $pstatus === 'closed' ) ? 'show' : 'hide'; ?> <?php echo ( ! $user_edit ) ? 'rtcrm_attr_border' : ''; ?>">
						<div class="large-4 small-4 columns">
							<span class="prefix" title="<?php _e('Closing Reason'); ?>"><label><?php _e('Closing Reason'); ?></label></span>
						</div>
						<div class="large-8 small-8 columns"><?php $rt_crm_closing_reason->get_closing_reasons( ( isset( $post->ID ) ) ? $post->ID : '', $user_edit ); ?></div>
					</div>
                    <div class="row collapse">
                        <div class="large-4 small-4 columns">
                            <span class="prefix" title="Create Date"><label>Create Date</label></span>
                        </div>
                        <div class="large-7 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtcrm_attr_border' : ''; ?>">
							<?php if( $user_edit ) { ?>
								<input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
									   value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>"
									   title="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>">
								<input name="post[post_date]" type="hidden" value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" />
							<?php } else { ?>
								<span class="rtcrm_view_mode moment-from-now"><?php echo $createdate ?></span>
							<?php } ?>
                        </div>
						<div class="large-1 mobile-large-1 columns">
							<span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar"></label></span>
						</div>
                    </div>
<?php if (isset($post->ID)) { ?>
                        <div class="row collapse">
                            <div class="large-4 mobile-large-1 columns">
                                <span class="prefix" title="Modify Date"><label>Modify Date</label></span>
                            </div>
                            <div class="large-7 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtcrm_attr_border' : ''; ?>">
								<?php if( $user_edit ) { ?>
	                                <input class="moment-from-now"  type="text" placeholder="Modified on Date"  value="<?php echo $modifydate; ?>"
		                                   title="<?php echo $modifydate; ?>" readonly="readonly">
								<?php } else { ?>
									<span class="rtcrm_view_mode moment-from-now"><?php echo $modifydate; ?></span>
								<?php } ?>
                            </div>
							<div class="large-1 mobile-large-1 columns">
								<span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar"></label></span>
							</div>
                        </div>
						<div class="row collapse">
							<div class="large-4 mobile-large-1 columns">
								<span class="prefix" title="<?php _e('Closing Date'); ?>"><label><?php _e('Closing Date'); ?></label></span>
							</div>
							<div class="large-7 mobile-large-2 columns <?php echo ( ! $user_edit ) ? 'rtcrm_attr_border' : ''; ?>">
								<?php if( $user_edit ) { ?>
									<input class="datepicker moment-from-now" type="text" placeholder="Select Closing Date"
									   value="<?php echo ( isset($closingdate) ) ? $closingdate : ''; ?>"
									   title="<?php echo ( isset($closingdate) ) ? $closingdate : ''; ?>">
									<input name="post[closing-date]" type="hidden" value="<?php echo ( isset($closingdate) ) ? $closingdate : ''; ?>" />
								<?php } else { ?>
									<span class="rtcrm_view_mode moment-from-now"><?php echo $closingdate; ?></span>
								<?php } ?>
							</div>
							<?php if( $user_edit ) { ?>
							<div class="large-1 mobile-large-1 columns">
								<span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar"></label></span>
							</div>
							<?php } ?>
						</div>
<?php } ?>
					<?php
						$meta_attributes = rtcrm_get_attributes( $post_type, 'meta' );
						foreach ( $meta_attributes as $attr ) {
							if ( strstr( $attr->attribute_name, 'date' ) ) { ?>
								<div class="row collapse">
									<?php $rt_crm_attributes->render_meta( $attr, isset($post->ID) ? $post->ID : '', $user_edit ); ?>
								</div>
							<?php } ?>
					<?php } ?>
<?php
$all_crm_participants = array();
if(isset($post->ID)) {
	$comments = get_comments(array('order' => 'DESC', 'post_id' => $post->ID, 'post_type' => $post_type ) );
	foreach ( $comments as $comment ) {
		$participants = '';
		$to = get_comment_meta( $comment->comment_ID, '_email_to', true );
		if( !empty( $to ) )
			$participants .= $to.',';
		$cc = get_comment_meta( $comment->comment_ID, '_email_cc', true );
		if( !empty( $cc ) )
			$participants .= $cc.',';
		$bcc = get_comment_meta( $comment->comment_ID, '_email_bcc', true );
		if( !empty( $bcc ) )
			$participants .= $bcc;

		if( !empty( $participants ) ) {
			$p_arr = explode(',', $participants);
			$p_arr = array_unique($p_arr);
			$all_crm_participants = array_merge($all_crm_participants, $p_arr);
		}
	}
	$all_crm_participants = array_filter( array_unique( $all_crm_participants ) );
}
$get_assigned_to = array();
if (isset($post->ID)) {
    $post_author = $post->post_author;
    $get_assigned_to = get_post_meta($post->ID, "subscribe_to", true);
} else {
    $post_author = get_current_user_id();
}
$results = Rt_CRM_Utils::get_crm_rtcamp_user();
$arrCommentReply = array();
$arrSubscriberUser[] = array();
$subScribetHTML = "";
if( !empty( $results ) ) {
	foreach ( $results as $author ) {
		if ($get_assigned_to && !empty($get_assigned_to) && in_array($author->ID, $get_assigned_to)) {
			if( in_array( $author->user_email, $all_crm_participants ) ) {
				$key = array_search($author->user_email, $all_crm_participants);
				if ( $key !== FALSE ) {
					unset( $all_crm_participants[$key] );
				}
			}
            $subScribetHTML .= "<li id='subscribe-auth-" . $author->ID
                    . "' class='contact-list'>" . get_avatar($author->user_email, 24) . '<a target="_blank" class="heading" title="'.$author->display_name.'" href="'.get_edit_user_link($author->ID).'">'.$author->display_name.'</a>'
                    . "<a class='right' href='#removeSubscriber'><i class='foundicon-remove'></i></a>
						<input type='hidden' name='subscribe_to[]' value='" . $author->ID . "' /></li>";
        }
        $arrSubscriberUser[] = array("id" => $author->ID, "label" => $author->display_name, "imghtml" => get_avatar($author->user_email, 24), 'user_edit_link'=>  get_edit_user_link($author->ID));
        $arrCommentReply[] = array("userid" => $author->ID, "label" => $author->display_name, "email" => $author->user_email, "contact" => false, "imghtml" => get_avatar($author->user_email, 24));
	}
}
?>
					<?php
						if ( isset( $post->ID ) ) {
							$rtcrm_unique_id = get_post_meta($post->ID, 'rtcrm_unique_id', true);
							if(!empty($rtcrm_unique_id)) { ?>
								<div class="row collapse">
									<div class="large-4 mobile-large-1 columns">
										<span class="prefix" title="<?php _e('Public URL'); ?>"><label><?php _e('Public URL'); ?></label></span>
									</div>
									<div class="large-8 mobile-large-3 columns">
										<div class="rtcrm_attr_border"><a class="rtcrm_public_link" target="_blank" href="<?php echo trailingslashit(site_url()) . strtolower($labels['name']) . '/?rtcrm_unique_id=' . $rtcrm_unique_id; ?>"><?php _e('Link'); ?></a></div>
									</div>
								</div>
							<?php }
						}
					?>
<?php
	$meta_attributes = rtcrm_get_attributes( $post_type, 'meta' );
	foreach ( $meta_attributes as $attr ) {
		if ( strstr( $attr->attribute_name, 'date' ) ) {
			continue;
		}
		?>
		<div class="row collapse">
			<?php $rt_crm_attributes->render_meta( $attr, isset($post->ID) ? $post->ID : '', $user_edit ); ?>
		</div>
	<?php }
	$attributes = rtcrm_get_attributes( $post_type, 'taxonomy' );
	foreach ($attributes as $attr) {
		if( !in_array( $attr->attribute_render_type, array( 'rating-stars', 'radio', 'dropdown' ) ) )
			continue;
		?>
		<div class="row collapse">
			<?php $rt_crm_attributes->render_meta( $attr, isset($post->ID) ? $post->ID : '', $user_edit ); ?>
		</div>
	<?php } ?>
	<?php do_action( 'rt_crm_after_lead_information', $post->ID, $user_edit ); ?>
	</div>
</div>
			<?php do_action( 'rt_crm_other_details', $user_edit, $post ); ?>
			<?php foreach ( $attributes as $attr ) {
				if( in_array( $attr->attribute_render_type, array( 'rating-stars', 'radio', 'dropdown' ) ) )
					continue;
			?>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="gen-enclosed foundicon-star"></i> <?php echo $attr->attribute_label; ?></span></h6>
					<div class="inside">
						<?php $rt_crm_attributes->render_taxonomy( $attr, isset($post->ID) ? $post->ID : '', $user_edit ); ?>
					</div>
				</div>
			<?php } ?>
            </div>
            <div class="large-3 columns ui-sortable meta-box-sortables">
				<div id="rtcrm-assignee" class="row collapse rtcrm-post-author-wrapper">
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php _e('Assigned To'); ?>"><label for="post[post_author]"><strong><?php _e('Assigned To'); ?></strong></label></span>
					</div>
					<div class="large-8 mobile-large-3 columns">
					<?php if( $user_edit ) { ?>
						<select name="post[post_author]" >
<?php
if (!empty($results)) {
foreach ($results as $author) {
	if ($author->ID == $post_author) {
		$selected = " selected";
	} else {
		$selected = " ";
	}
	echo '<option value="' . $author->ID . '"' . $selected . '>' . $author->display_name . '</option>';
}
}
?>
						</select>
						<?php } else {
							if(!empty($results)) {
								foreach ($results as $author) {
									if($author->ID == $post_author) {
										echo '<div class="rtcrm_view_mode">'.get_avatar( $author->user_email, 17 ).' '.$author->display_name.'</div>';
										break;
									}
								}
							}
						} ?>
					</div>
				</div>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-smiley"></i> <?php _e("Subscribers"); ?></span></h6>
					<div class="inside">
						<script>
							var arr_subscriber_user =<?php echo json_encode($arrSubscriberUser); ?>;
							var ac_auth_token = '<?php echo get_user_meta(get_current_user_id(), 'rtcrm_activecollab_token', true); ?>';
							var ac_default_project = '<?php echo get_user_meta(get_current_user_id(), 'rtcrm_activecollab_default_project', true); ?>';
						</script>
						<?php if ( $user_edit ) { ?>
						<input type="text" placeholder="Type Subscribers Name to select" id="subscriber_user_ac" />
						<?php } ?>
						<ul id="divSubscriberList" class="large-block-grid-1 small-block-grid-1">
	<?php echo $subScribetHTML; ?>
						</ul>
	                </div>
				</div>
<?php if ( $module_settings['attach_accounts'] == 'yes' ) { ?>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-globe"></i> <?php _e("Organization"); ?></span></h6>
					<div class="inside">
						<?php if ( $user_edit ) { ?>
						<div class="row collapse">
							<div class="large-9 mobile-large-3 columns">
								<input type="text" placeholder="Type Name to select" id="search_account" />
							</div>
							<button class="large-3 mobile-large-1 columns mybutton" type="button" id="add-new-account" >Add</button>
						</div>
						<?php } ?>
	                    <ul id="divAccountsList" class="block-grid large-1-up">
<?php
if (isset($post->ID)) {
	$lead_term = array();
	$account_post_type = rt_biz_get_organization_post_type();
	$lead_term = rt_biz_get_post_for_organization_connection( $post->ID, $post->post_type, $fetch_account = true );

    foreach ($lead_term as $tterm) {
		$email = rt_biz_get_entity_meta( $tterm->ID, $rt_crm_accounts->email_key, true );
        echo "<li id='crm-account-" . $tterm->ID . "' class='contact-list' >"
				. "<div class='row collapse'>"
					. "<div class='large-2 columns'> " . get_avatar($email, 24) . "</div>"
					. "<div id='crm-account-meta-" . $tterm->ID . "'  class='large-9 columns'><a target='_blank' class='heading' href='" . admin_url("edit.php?". $account_post_type ."=" . $tterm->ID . "&post_type=".$post_type) . "' title='" . $tterm->post_title . "'>" . $tterm->post_title . "</a></div>"
					. "<div class='large-1 columns'><a href='#removeContact'><i class='foundicon-remove'></i></a><input type='hidden' name='post[accounts][]' value='" . $tterm->ID . "' /></div>"
				. "</div>"
			. "</li>";
    }
}
?>
	                    </ul>
					</div>
				</div>
<?php } ?>
<?php if ( $module_settings['attach_contacts'] == 'yes' ) { ?>
				<div class="row collapse postbox">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-address-book"></i> <?php _e("Contacts"); ?></span></h6>
					<div class="inside">
						<?php if ( $user_edit ) { ?>
						<div class="row collapse">
							<div class="large-9 mobile-large-3 columns">
								<input type="text" placeholder="Type Name to select" id="search_contact" />
							</div>
							<button class="large-3 mobile-large-1 columns mybutton " type="button" id="add-new-contact" >Add</button>
						</div>
						<?php } ?>
	                    <ul id="divContactsList" class="large-block-grid-1"><?php
							if (isset($post->ID)) {
								$scriptstr = "";
								$lead_term = array();
								$contact_post_type = rt_biz_get_person_post_type();
								$lead_term = rt_biz_get_post_for_person_connection( $post->ID, $post->post_type, $fetch_person = true );
								foreach ($lead_term as $tterm) {

									$email = rt_biz_get_entity_meta( $tterm->ID, $rt_crm_contacts->email_key, true );
									if ( in_array( $email, $all_crm_participants ) ) {
										$key = array_search( $email, $all_crm_participants );
										if ( $key !== FALSE ) {
											unset( $all_crm_participants[$key] );
										}
									}
									echo "<li id='crm-contact-" . $tterm->ID . "' class='contact-list' >"
											. "<div class='row collapse'>"
												. "<div class='large-2 columns'> " . get_avatar($email, 24) . "</div>"
												. "<div id='crm-contact-meta-" . $tterm->ID . "'  class='large-9 columns'><a target='_blank' class='heading' href='" . admin_url("edit.php?".  rtcrm_post_type_name('contact')."=" . $tterm->ID . "&post_type=".$post_type) . "' title='" . $tterm->post_title . "'>" . $tterm->post_title . "</a>";
									if ($email) {
										echo "<i class=''><a href='mailto:" . $email . "'>" . $email . "</a><a class='inline' target='_blank' href='http://mail.google.com/mail/#search/" .$email."'> <i class='foundicon-search'></i></a></i>";
										$arrCommentReply[] = array("userid" => $tterm->ID, "label" => $tterm->post_title, "email" => $email, "contact" => true, "imghtml" => get_avatar($email, 24));
									}
									echo "</div>"
										. "<div class='large-1 columns'><a href='#removeContact'><i class='foundicon-remove'></i></a><input type='hidden' name='post[contacts][]' value='" . $tterm->ID . "' /></div>"
									. "</div>"
									. "</li>";
								}
							}
						?></ul>
					</div>
				</div>
<?php } ?>
				<div class="row collapse postbox hide">
					<div class="handlediv" title="<?php _e( 'Click to toggle' ); ?>"><br /></div>
					<h6 class="hndle"><span><i class="foundicon-smiley"></i> <?php _e("Participants"); ?></span></h6>
					<div class="inside">
						<ul class="rtcrm-participant-list large-block-grid-1 small-block-grid-1">
						<?php foreach ( $all_crm_participants as $email ) {
							echo "<li>"
									. "<div class='row collapse'>"
										. "<div class='large-2 columns'> " . get_avatar($email, 50) . "</div>"
										. "<div class='large-9 columns'><a target='_blank' class='heading' href='mailto:" . $email . "' title='" . $email . "'>" . $email . "</a></div>"
									. "</div>"
							. "</li>";
						} ?>
						</ul>
					</div>
				</div>
            </div>
        </div>
    </form>
<?php if (isset($post->ID)) { ?>
		<?php if ( $user_edit ) { ?>
		<div class="row">
            <div class="large-12 columns right">
                <button class="mybutton add-followup" type="button" ><?php _e("Add Followup"); ?></button>
            </div>
        </div>
		<?php } ?>
        <div class="large-12">
            <fieldset>
                <legend>Followup</legend>
	<?php $comments = get_comments(array('order' => 'DESC', 'post_id' => $post->ID, 'post_type' => $post_type ) ); ?>
				<div class="row">
					<div class="large-12 columns <?php echo (empty($comments)) ? 'hide' : ''; ?>">
						<a class="accordion-expand-all right" href="#" data-isallopen="false"><i class="general foundicon-down-arrow" title="Expand All"></i></a>
					</div>
					<div class="large-12 columns followup-scroll-height" id="commentlist">
	<?php
		$prev_month = '';
		$prev_day = '';
		$prev_year = '';
		foreach ($comments as $comment) {
        $user = get_user_by("email", $comment->comment_author_email);
    ?>
						<div id="header-<?php echo $comment->comment_ID; ?>" class="comment-header row">
							<?php
								$dt = new DateTime($comment->comment_date);
								$curr_month = $dt->format("M");
								$curr_day = $dt->format("d");
								$curr_year = $dt->format("Y");
								if ( !( $curr_month == $prev_month && $curr_day == $prev_day && $curr_year == $prev_year ) ) { ?>
									<div class="comment-date" title="<?php echo $dt->format("M d,Y h:i A"); ?>">
										<p class="comment-month"><?php echo $curr_month; ?></p>
										<p class="comment-day"><?php echo $curr_day; ?></p>
										<p class="comment-year"><?php echo $dt->format("Y"); ?></p>
									</div>
							<?php }
								$class = '';
								if ( $curr_month == $prev_month && $curr_day == $prev_day && $curr_year == $prev_year ) {
									$class = 'comment-skip-date';
								}
								$prev_month = $curr_month;
								$prev_day = $curr_day;
								$prev_year = $curr_year;
							?><div class="comment-meta <?php echo $class; ?>">
									<div class="comment-user-gravatar">
										<a href="#" class="th radius">
										<?php echo get_avatar($comment->comment_author_email, 40); ?>
										</a>
									</div><?php $rtcrm_privacy = get_comment_meta( $comment->comment_ID, '_rtcrm_privacy', true ); ?><div class="rtcrm_privacy" data-crm-privacy="<?php echo ( $rtcrm_privacy != 'no' ) ? 'yes' : 'no'; ?>">
										<?php if($rtcrm_privacy != 'no') { ?><i class="general foundicon-lock"></i><?php } ?>
									</div><div class="comment-users"><div class="comment-user-title large-12 columns">
								<?php
									if ($user)
										echo $user->display_name;
									else if(!empty ($comment->comment_author)){
										echo $comment->comment_author;
									} else {
										echo 'Annonymous';
									}
								?></div>
									<div class="comment-participant large-12 columns">
								<?php
									$participants = '';
									$to = get_comment_meta( $comment->comment_ID, '_email_to', true );
									if( !empty( $to ) )
										$participants .= $to;
									$cc = get_comment_meta( $comment->comment_ID, '_email_cc', true );
									if( !empty( $cc ) )
										$participants .= ','.$cc;
									$bcc = get_comment_meta( $comment->comment_ID, '_email_bcc', true );
									if( !empty( $bcc ) )
										$participants .= ','.$bcc;

									if( !empty( $participants ) ) {
										$p_arr = explode(',', $participants);
										$p_arr = array_unique($p_arr);
										$participants = implode(' , ', $p_arr);
										echo 'to  '.$participants;
									}
								?>
									</div>
								</div>
								<div class="comment-info">
									<span class="comment-type"><?php echo ucfirst($comment->comment_type); ?></span>
								</div>
								<div class="comment-actions">
									<?php if($user_edit) { ?>
										<a class="folowup-hover" href="#editFollowup" title="Edit" data-comment-id="<?php echo $comment->comment_ID; ?>"><?php _e('Edit'); ?></a>
										<a class="folowup-hover delete" href="#deleteFollowup" title="Delete" data-comment-id="<?php echo $comment->comment_ID; ?>"><?php _e('Delete'); ?></a>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="comment-wrapper row" id="comment-<?php echo $comment->comment_ID; ?>">
								<div class="push-1 large-8 columns comment-content">
	<?php
		if (isset($comment->comment_content) && $comment->comment_content != "") {
			if (strpos("<body", $comment->comment_content) !== false) {
				preg_match_all("/<body[^>]*>(.*?)<\/body>/s", $comment->comment_content, $output_array);
				if (count($output_array) > 0) {
					$comment->comment_content = $output_array[0];
				}
			}
			echo Rt_CRM_Utils::forceUFT8($comment->comment_content);
		}
	?>
								</div>
								<div class="large-3 columns">
        <?php
        $comment_attechment = get_comment_meta($comment->comment_ID, "attachment");
								if (!empty($comment_attechment)) { ?>
									<ul class='comment_attechment block-grid large-2-up'>
										<?php foreach ($comment_attechment as $commenytAttechment) {
											$extn_array = explode('.', $commenytAttechment);
											$extn = $extn_array[count($extn_array) - 1];

											$file_array = explode('/', $commenytAttechment);
											$fileName = $file_array[count($file_array) - 1];
										?>
											<li>
												<a href="<?php echo $commenytAttechment; ?>" title="Attachment" >
													<img src="<?php echo RT_CRM_URL . "assets/file-type/" . $extn . ".png"; ?>" />
													<span><?php echo $fileName; ?></span>
												</a>
												<input type="hidden" name="attachemnt" value="<?php echo $commenytAttechment; ?>">
												<?php if ( $user_edit ) { ?>
												<a class="edit-remove" href="#editRemoveAttachemnt"><i class="foundicon-remove"></i></a>
												<?php } ?>
											</li>
										<?php } ?>
									</ul>
								<?php } ?>
								</div>
							</div>
						<?php }     //End Loop for comments
						?>
                        </div>
                    </div>
            </fieldset>
        </div>
<?php } ?>

</div>
<script>
    var arr_comment_reply_to = <?php echo json_encode($arrCommentReply); ?>;
</script>



<?php if (isset($post->ID)) { //  ?>
    <div id='div-add-followup'class="reveal-modal expand" >
        <fieldset>
            <legend>New FollowUp</legend>
            <div class="row">
                <input type='hidden' id='edit-comment-id' />
                <div class="large-8 column mobile-large-4">
                    <input type="hidden" value='note' id='followup-type' />
                    <div class="row">
                        <div class="large-3 columns">
                            <div class="section-container vertical-nav" id="vertical-tab-header" data-section="vertical-nav">
                                <section class="section active">
                                    <p class="title"><a class="myfollowup" data-type="note" href="javascript:selecttab('note')" data-tab="note">Note</a></p>
                                </section>
                                <section class="section">
                                    <p class="title"><a class="myfollowup" data-type="mail" href="javascript:selecttab('mail')" data-tab="mail">Mail</a></p>
                                </section>
                                <section class="section">
                                    <p class="title"><a class="myfollowup" data-type="ac_import" href="javascript:selecttab('acim')" data-tab="acim">AC Importer</a></p>
                                </section>
                                <section class="section">
                                    <p class="title"><a class="myfollowup" data-type="gm_import" href="javascript:selecttab('gmim')" data-tab="gmim">Gmail Importer</a></p>
                                </section>
                            </div>
                        </div>
                        <div class="large-9 columns section vertical-tab-content">
                            <div id="tabnote" class="content">
                            </div>
                            <div id="tabmail" class="content hide">
                                <div class="row collapse">
                                    <div class="large-1 mobile-large-1 columns">
                                        <span class="prefix"><label>From</label></span>
                                    </div>
                                    <div class="large-5 mobile-large-2 columns">
                                        <select id="email_from_ac" name="email_from_ac">
    <?php

    $allowMailAc = $rt_crm_settings->get_allow_email_address();
    $strAllowMailAc= "";
    if ($allowMailAc) {
        foreach ($allowMailAc as $mailAc) {
           $strAllowMailAc .= "<option value='" . $mailAc->email . "'>" . $mailAc->email . "</option>";
        }
    }
    echo $strAllowMailAc;
    ?>
                                        </select>
                                    </div>
                                    <div class="large-6 mobile-large-2 columns">
                                        <label> <input type='checkbox' value='all' id='sendCommentMail'>Send Mail to Client Also</label>

                                    </div>

                                </div>
                                <div class="row collapse email-ac">
                                    <div class="large-1 mobile-large-1 columns">
                                        <span class="prefix"><label>To</label></span>
                                    </div>
                                    <div class="large-11 mobile-large-2 columns">
                                        <input  type="text" placeholder="Type Name" class="email-auto-complete" id="comment-reply-to" >
                                    </div>


                                </div>
                                <div class="row collapse email-ac">
                                    <div class="large-1 mobile-large-1 columns">
                                        <span class="prefix"><label>CC</label></span>
                                    </div>
                                    <div class="large-11 mobile-large-2 columns">
                                        <input  type="text" placeholder="Type Name" class="email-auto-complete" id="comment-reply-cc">
                                    </div>

                                </div>
                                <div class="row collapse email-ac">
                                    <div class="large-1 mobile-large-1 columns">
                                        <span class="prefix"><label>BCC</label></span>
                                    </div>
                                    <div class="large-11 mobile-large-2 columns">
                                        <input type="text" placeholder="Type Name" class="email-auto-complete" id="comment-reply-bcc" >
                                    </div>

                                </div>
                            </div>
                            <div id="tabacim" class="content hide">
                                <div class="row collapse">
                                    <div class="large-2 mobile-large-1 columns">
                                        <span class="prefix"><label>Project Id</label></span>
                                    </div>
                                    <div class="large-10 mobile-large-2 columns">
                                        <input  type="text" placeholder="Project ID" id="ac_project_id" />
                                    </div>
                                </div>
                                <div class="row collapse">
                                    <div class="large-2 mobile-large-1 columns">
                                        <span class="prefix"><label>Task Id</label></span>
                                    </div>
                                    <div class="large-10 mobile-large-2 columns">
                                        <input  type="text" placeholder="Task ID" id="ac_task_id" />
                                    </div>
                                </div>
                            </div>
                            <div id="tabgmim" class="content hide">
                                <div class="row collapse">
                                    <div class="large-2 mobile-large-1 columns">
                                        <span class="prefix"><label>Email A/C</label></span>
                                    </div>
                                    <div class="large-10 mobile-large-2 columns">
                                        <select id="gm-from-email">
                                            <?php echo $strAllowMailAc;?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row collapse">
                                    <div class="large-2 mobile-large-1 columns">
                                        <span class="prefix"><label>Thread ID</label></span>
                                    </div>
                                    <div class="large-10 mobile-large-2 columns">
                                        <input  type="text" placeholder="Gmail Thread ID" id="gm-thread-id" />
                                    </div>
                                </div>
                            </div>

<div class="row collapse" id="followup-editor">
                        <div class="large-12 mobile-large-2 columns">
    <?php
    wp_editor("", "followup_content");
    ?>
                        </div>

                    </div>
                        </div>

                    </div>
                </div>
                <div class="large-4 column" id="followup-slider">
					<div class="row collapse">
						<div class="large-12 columns"><label for="followup-private"><input type="checkbox" id="followup-private" /> <?php _e('Private ?'); ?></label></div>
						<div class="clearfix">&nbsp;</div>
					</div>
                    <div class="row collapse">
                        <div class="large-2 mobile-large-1 columns">
                            <span class="prefix"><label>Time</label></span>
                        </div>
                        <div class="large-10 mobile-large-2 columns">
                            <input  type="text" placeholder="Select Time" class="datetimepicker" id="follwoup-time" />
                        </div>
						<div class="clearfix">&nbsp;</div>
                    </div>
                    <fieldset>
                        <legend>Attachment</legend>
                        <ul class="large-block-grid-2  small-block-grid-1" id="attachmentList">
                            <li class="add">
                                <button class="button" id="btnCommentAttachment">Add</button>
                            </li>
                        </ul>
						<div class="clearfix">&nbsp;</div>
                    </fieldset>
                </div>
            </div>
            <div class="row">
                <div class="large-12 columns right">
                    <button class="mybutton add-savefollowup" id="savefollwoup" type="button" ><?php _e("Add Followup"); ?></button>
                </div>
            </div>
        </fieldset>
        <a class="close-reveal-modal">&#215;</a>
    </div>
<?php } ?>

<!--reveal-modal-->
<div id="add-contact" class="reveal-modal large">
    <form id='form-add-contact' method="post">
        <fieldset>
            <legend><h4><i class="foundicon-address-book"></i> <?php _e("Add New Contacts"); ?></h4></legend>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Name:</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <input type="text" name='new-contact-name' id='new-contact-name'/>
                </div>
            </div>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Note:</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <textarea type="text" name='new-contact-description' ></textarea>
                </div>
            </div>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Account:</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <div class="row collapse">
                        <div class="large-9 mobile-thee columns">
                            <input type="text" id="new-contact-account" name='new-contact-account' />
                        </div>
                        <button class="large-3 mobile-large-1 columns mybutton" type="button" id="contact-add-new-account" ><i class="foundicon-plus"></i> Account</button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <div class="row collapse">
                        <ul id="div_contact_account" class="large-block-grid-1 small-block-grid-1"></ul>
                    </div>
                </div>
            </div>

            <fieldset>
                <legend><?php _e("Social information"); ?></legend>
                <div class="row collapse">
                    <div class="large-8 mobile-large-2 columns">
                        <input type="text" name='new-contact-meta' id='new-contact-meta' class='sugget-meta-select'/>
                    </div>
                    <div class="large-3 mobile-large-1 columns contact-meta">
                        <select>
						<?php
							$meta_fields = rt_biz_get_person_meta_fields();
							foreach ( $meta_fields as $field ) {
								if ( $field['key'] == 'contact_user_id' ) { continue; }
							?>
							<option value="<?php echo $field['key']; ?>"><?php echo $field['text']; ?></option>
							<?php }
						?>
                        </select>
                    </div>
                    <button class="large-1 mobile-large-1 columns mybutton" type="button" id="contact-add-new-meta" ><i class="foundicon-plus"></i></button>

                </div>
                <div id='contact_meta_container' class="large-block-grid-1">

                </div>
            </fieldset>
            <button class="mybutton right" type="submit" id="save-contact" >Save Contact</button>
        </fieldset>
    </form>
    <a class="close-reveal-modal">&#215;</a>
</div>

<div id="add-account" class="reveal-modal large">
    <fieldset>
        <legend><h4><i class="foundicon-address-book"></i> <?php _e("Add New Account"); ?></h4></legend>
        <form id='form-add-account' method="post">
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Name:</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <input type="text" name='new-account-name' id='new-account-name' />
                </div>
            </div>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Note:</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <textarea type="text" name='new-account-note' ></textarea>
                </div>
            </div>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Address</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <textarea type="text" name='new-account-address' ></textarea>
                </div>
            </div>
            <div class="row">
                <div class="large-2 mobile-large-1 columns">
                    <label class="right inline">Country</label>
                </div>
                <div class="large-10 mobile-large-3 columns">
                    <input type="text" name='new-account-country' id='new-account-country' />
                </div>
            </div>
            <fieldset>
                <legend><?php _e("Social information"); ?></legend>

                <div class="row collapse">
                    <div class="large-8 mobile-large-2 columns">
                        <input type="text" name='new-account-meta' id='new-account-meta' class="sugget-meta-select"/>
                    </div>
                    <div class="large-3 mobile-large-1 columns contact-meta">
                        <select>
						<?php
							$meta_fields = rt_biz_get_organization_meta_fields();
							foreach ( $meta_fields as $field ) {
								if ( in_array( $field['key'], array( 'account_address', 'account_country' ) ) ) { continue; }
							?>
							<option value="<?php echo $field['key']; ?>"><?php echo $field['text']; ?></option>
							<?php }
						?>
                        </select>
                    </div>
                    <button class="large-1 mobile-large-1 columns mybutton" type="button" id="account-add-new-meta" ><i class="foundicon-plus"></i></button>

                </div>
                <div id='account_meta_container' class="large-block-grid-1">

                </div>
            </fieldset>

            <button class="mybutton right" type="submit" id="save-account" >Save Account</button>
        </form>
    </fieldset>
    <a class="close-reveal-modal">&#215;</a>
</div>
</div>
