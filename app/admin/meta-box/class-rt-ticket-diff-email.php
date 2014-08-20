<?php
/**
* Don't load this file directly!
*/
if ( ! defined( 'ABSPATH' ) )
exit;

/*
* To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

/**
* Description of RT_HD_Admin_Meta_Boxes
*/

if( !class_exists( 'RT_Ticket_Diff_Email' ) ) {
    class RT_Ticket_Diff_Email {

        /**
         * Diff Email between new ticket data & old ticket data -- can be new function
         * Save meta box data
         */
        public static function save( $post_id, $post ) {
            global $rt_hd_module, $rt_hd_attributes, $rt_hd_closing_reason, $rt_hd_contacts, $rt_hd_accounts, $rt_hd_settings, $rt_hd_tickets, $rt_hd_ticket_history_model;
            
            $module_settings = rthd_get_settings();
            
            //get Old & New post Data
            $newTicket = $_POST['post'];
            $revisions = wp_get_post_revisions( $post_id );
            $post_revisions = array();
            foreach ( $revisions as $revision ) {
                $post_revisions[] = $revision;
                if (count($post_revisions) == 2 ){
                    break;
                }
            }
            $oldpost = NULL;
            if ( isset( $post_revisions ) && count($post_revisions) == 2 ){
                $oldpost = $post_revisions[1];
            }
            
            $closing_reason_history_id = '';
            
            $emailTable = "<table style='width: 100%; border-collapse: collapse; border: none;'>";
            $emailHTML = $diff = "";

            //Post Data Diff
            if ( isset( $oldpost ) && !empty( $oldpost ) ){
                
                $diff = rthd_text_diff( $oldpost->post_title, $post->post_title );
                if ( $diff ) {
                    $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Title</th><td>' . $diff . '</td><td></td></tr>';
                }
                
                $diff = rthd_text_diff( $post->post_status, $newTicket['post_status'] );
                if ( $diff ) {
                    $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Status </th><td>' . $diff . '</td><td></td></tr>';
                    /* Insert History for status */
                    $id = $rt_hd_ticket_history_model->insert( array(
                        'ticket_id' => $post_id,
                        'type' => 'post_status',
                        'old_value' => $post->post_status,
                        'new_value' => $newTicket['post_status'],
                        'update_time' => current_time( 'mysql' ),
                        'updated_by' => get_current_user_id(),
                    ));
                    
                    if ( $newTicket['post_status'] === 'closed' ) {
		        $closing_reason_history_id = $id;
                    }
                }
                
                $oldUser = get_user_by( 'id', $oldpost->post_author );
                $newUser = get_user_by( 'id', $post->post_author );
                $diff = rthd_text_diff( $oldUser->display_name, $newUser->display_name );
                if ( $diff ) {
                    $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Assigned To</th><td>' . $diff . '</td><td></td></tr>';
                }

                $diff = rthd_text_diff( strip_tags( $oldpost->post_content ), strip_tags( $post->post_content ) );
                if ( $diff ) {
                    $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Ticket Content</th><td>' . $diff . '</td><td></td></tr>';
                }

            }else{
                $newUser = get_user_by( 'id', $post->post_author );
                $emailHTML .= '<tr><td> Title </td><td>' . $post->post_title . '</td></tr>';
                $emailHTML .= '<tr><td> Status </td><td>' . $post->post_status . '</td></tr>';
                $emailHTML .= '<tr><td> Assigned To </td><td>' . $newUser->display_name . '</td></tr>';
                $emailHTML .= '<tr><td> Content </td><td>' . $post->post_content . '</td></tr>';
                
            }
               
            // Closing Date Diff
            if ( isset( $newTicket['closing-date'] ) && !empty( $newTicket['closing-date'] )) {
                $oldclosingdate = get_post_meta( $post_id, "_ticket_closing_date", true );
                $diff = rthd_text_diff( $oldclosingdate, $newTicket["closing-date"] );
                if ( $diff ) {
                    $emailHTML .= '<tr><th style="padding: .5em;border: 0;">Closing Date</th><td>' . $diff . '</td><td></td></tr>';
                }
            }
            
            // Attachments Diff
            if ( isset( $_POST['attachment'] ) ) {
                $old_attachments = get_posts( array(
                    'post_parent' => $post_id,
                    'post_type' => 'attachment',
                    'fields' => 'ids',
                    'posts_per_page' => -1,
                ));
                $new_attachments = $_POST['attachment'];
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
                $diff = rthd_text_diff( $old_attachments_str, $new_attachments_str );
                if ( $diff ) {
                        $emailHTML .= '<tr><th style="padding: .5em;border: 0;">Attachments</th><td>' . $diff . '</td><td></td></tr>';
                }
            }
            
            // External File Links Diff
            if ( isset( $_POST['ticket_ex_files'] ) ) {
                $old_ex_files = get_post_meta( $post_id, '_ticket_external_file' );
                $new_ex_files = $_POST['ticket_ex_files'];
                $old_ex_links = array();
                $new_ex_links = array();
                foreach ( $old_ex_files as $ex_file ) {
                        $data = json_decode( $ex_file );
                        $old_ex_links[] = '<a href="'.$data->link.'" target="_blank">'.$data->title.'</a>';
                }
                foreach ( $new_ex_files as $ex_file ) {
                        if ( empty( $ex_file['title'] ) ) {
                                $ex_file['title'] = $ex_file['link'];
                        }
                        $new_ex_links[] = '<a href="'.$ex_file['link'].'" target="_blank">'.$ex_file['title'].'</a>';
                }
                $diff = rthd_text_diff( implode( ' , ', $old_ex_links ), implode( ' , ', $new_ex_links ) );
                if ( $diff ) {
                        $emailHTML .= '<tr><th style="padding: .5em;border: 0;">External Files Links</th><td>' . $diff . '</td><td></td></tr>';
                }
            }
            
            // Cloasing Reason Diff
            $closing_reason_diff = $rt_hd_closing_reason->closing_reason_diff( $post_id, $newTicket );
            $emailHTML .= $closing_reason_diff;
            if ( !empty( $closing_reason_diff ) ) {
                /* Insert History for status - closing reason */
		if ( $closing_reason_history_id ) {
			$terms = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( 'closing_reason' ) );
			$message = '';
			foreach ( $terms as $term ) {
				if ( empty( $message ) ) {
					$message .= $term->name;
				} else {
					$message .= ( ' , ' . $term->name );
				}
			}
			$rt_hd_ticket_history_model->update( array( 'message' => $message ), array( 'id' => $closing_reason_history_id ) );
		} else {
			$terms = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( 'closing_reason' ) );
			$message = '';
			foreach ( $terms as $term ) {
				if ( empty( $message ) ) {
					$message .= $term->name;
				} else {
					$message .= ( ' , ' . $term->name );
				}
			} 
			$rt_hd_ticket_history_model->insert(array(
					'ticket_id' => $post_id,
					'type' => 'post_status',
					'old_value' => $post->post_status,
					'new_value' => $newTicket['post_status'],
					'message' => $message,
					'update_time' => current_time( 'mysql' ),
					'updated_by' => get_current_user_id(),
				)
			);
		}
            }
            
            print_r( $emailHTML );
            
            $flag = true;
            $systemEmail = ( isset( $module_settings['system_email'] ) ) ? $module_settings['system_email'] : '';
            if ($systemEmail) {
                if (!is_email($systemEmail)) {
                    $flag = false;
                }
            } else {
                $flag = false;
            }
            var_dump($flag);
            if ($flag) {
                $accessToken = $rt_hd_settings->get_accesstoken_from_email( $systemEmail, $signature, $email_type, $imap_server );
                $hdZendEmail = new Rt_HD_Zend_Mail();
                if (strpos($signature, "</") == false) {
                    $signature = htmlentities($signature);
                    $signature = preg_replace('/(\n|\r|\r\n)/i', "<br />", $signature);
                    $signature = preg_replace('/  /i', "  ", $signature);
                }
                $title_suffix = $rt_hd_tickets->create_title_for_mail( $post_id );
                $current_user = wp_get_current_user();
                if ( isset( $oldUser ) ) {
                    if ($oldUser->ID != $newUser->ID) {
                        
                        $title = "[Assigned You] " . $title_suffix;
                        $emailHTML1 = "<b>" . $current_user->display_name ."</b> assigned you new ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";

                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), array(array("email" => $newUser->user_email, "name" => $newUser->display_name)), array(), $post_id, "post");
                        $title = "[Reassigned] " . $title_suffix;
                        $emailHTML1 = "You are no longer responsible for this ticket. It has been reassigned to " . $newUser->display_name . " .<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), array(array("email" => $oldUser->user_email, "name" => $oldUser->display_name)), array(), $post_id, "post");
                        var_dump('Assigned You');
                    }

//                    if (!empty($newSubscriberList)) {
//                        $title = "[Subscribe] " . $title_suffix;
//                        $emailHTML1 = "You have been <b>subscribed</b> to this ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
//                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), $newSubscriberList, array(), $post_id, "post");
//                    }
//                    if (!empty($oldSubscriberList)) {
//                        $title = "[Unsubscribe] " . $title_suffix;
//                        $emailHTML1 = "You have been <b>unsubscribed</b> to this ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
//                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), $oldSubscriberList, array(), $post_id, "post");
//                    }
//                    if ($emailHTML != "" && !empty($bccemails)) {
//                        $emailHTML = $emailTable . $emailHTML . "</table> </br> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
//                        $emailHTML .= "<br />" . 'Ticket updated by : <a target="_blank" href="">'.get_the_author_meta( 'display_name', get_current_user_id() ).'</a>';
//                        $emailHTML .= "<br />" . $signature;
//                        $title = "[Ticket Updated] " . $title_suffix;
//                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, stripslashes($emailHTML), array(), array(), $bccemails, array(), $post_id, "post");
//                    }
                } else {
                    $newUser = get_user_by("id", $post["post_author"]);
                    if ($newUser) {
                        $title = "[Assigned You] " . $title_suffix;
                        $emailHTML = "New ticket assigned to you.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML, array(), array(), array(array("email" => $newUser->user_email, "name" => $newUser->display_name)), array(), $post_id, "post");
                        var_dump('Assigned You');
                    }
//                    if (!empty($bccemails)) {
//                        $title = "[Subscribe] " . $title_suffix;
//                        $emailHTML = "You have been <b>subscribed</b> to this ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
//                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML, array(), array(), $bccemails, array(), $post_id, "post");
//                    }
                }
            }
        }
    }
}