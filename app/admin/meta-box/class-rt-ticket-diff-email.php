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
        
        public static function store_old_post_data( $post_id, $post ){
            global $rt_hd_ticket_history_model, $rt_hd_closing_reason, $rt_hd_module, $rt_hd_attributes, $rt_ticket_email_content;
            
            // $post_id and $post are required
            if ( empty( $post_id ) || empty( $post ) ) {
                    return;
            }

            // Dont' save meta boxes for revisions or autosaves
            if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
                    return;
            }

            // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
            if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
                    return;
            }

            // Check user has permission to edit
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return;
            }

            if ( ! in_array( $post['post_type'], array( Rt_HD_Module::$post_type ) ) ) {
                    return;
            }
            
            $rt_ticket_email_content = array();

            $module_settings = rthd_get_settings();
            $oldpost = get_post( $post_id );
            $newTicket = $_POST['post'];
            
            $emailHTML = $diff = $closing_reason_history_id = '';
            
            // Title Diff
            $diff = rthd_text_diff( $oldpost->post_title, $_POST['post_title'] );
            if ( $diff ) {
                $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Title</th><td>' . $diff . '</td><td></td></tr>';
            }
            
            // Status Diff
            $diff = rthd_text_diff( $oldpost->post_status, $_POST['post_status'] );
            if ( $diff ) {
                $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Status </th><td>' . $diff . '</td><td></td></tr>';
                /* Insert History for status */
	        $id = $rt_hd_ticket_history_model->insert( array(
                    'ticket_id' => $post_id,
                    'type' => 'post_status',
                    'old_value' => $oldpost->post_status,
                    'new_value' => $_POST['post_status'],
                    'update_time' => current_time( 'mysql' ),
                    'updated_by' => get_current_user_id(),
                ));
	        if ( $_POST['post_status'] === 'closed' ) {
		        $closing_reason_history_id = $id;
	        }
            }
            
            $rt_ticket_email_content['closing_reason_history_id'] = $closing_reason_history_id;
            
            // Author
            $oldUser = get_user_by( 'id', $oldpost->post_author );
            $newUser = get_user_by( 'id', $_POST['post_author'] );
            
            $rt_ticket_email_content['oldUser'] = $oldUser;
            $rt_ticket_email_content['newUser'] = $newUser;
            
            $diff = rthd_text_diff( $oldUser->display_name, $newUser->display_name );
            if ( $diff ) {
                $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Assigned To</th><td>' . $diff . '</td><td></td></tr>';
            }
            
            //Content
            $diff = rthd_text_diff( strip_tags( $oldpost->post_content ), strip_tags( $_POST['post_content'] ) );
            if ( $diff ) {
                $emailHTML .= '<tr><th style="padding: .5em;border: 0;"> Ticket Content</th><td>' . $diff . '</td><td></td></tr>';
            }
            
            // Closing Date Diff
            if ( isset( $newTicket['closing-date'] ) && !empty( $newTicket['closing-date'] )) {
                $oldclosingdate = get_post_meta( $post_id, "_rtbiz_helpdesk_closing_date", true );
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
                $old_ex_files = get_post_meta( $post_id, '_rtbiz_helpdesk_external_file' );
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
            
            // Attributes-meta Diff
            $attributes = rthd_get_attributes( Rt_HD_Module::$post_type, 'meta' );
            foreach ( $attributes as $attr ) {
		$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $newTicket );
                $emailHTML .= $attr_diff;
            }
            
            // Attributes-texonomies Diff
            $attributes = rthd_get_attributes( Rt_HD_Module::$post_type, 'taxonomy' );
            foreach ( $attributes as $attr ) {
		$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $_POST['tax_input'] );
                $emailHTML .= $attr_diff;
            }
            
            // Subscribers List
            if ( !isset( $_POST['subscribe_to'] ) ) {
                $_POST['subscribe_to'] = array();
                if( intval( $newTicket['post_author'] ) != get_current_user_id() ) {
                    $_POST['subscribe_to'][] = get_current_user_id();
                }
            }
            
            //Unscuscribe logic
            $oldSubscriberArr = get_post_meta( $post_id, '_rtbiz_helpdesk_subscribe_to', true );
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
            $rt_ticket_email_content['bccemails'] = $bccemails;
            
            if ( !empty( $removedSUbscriber ) ) {
                foreach ( $removedSUbscriber as $emailsubscriber ) {
                    $userSub = get_user_by( 'id', intval( $emailsubscriber ) );
                    $oldSubscriberList[] = array( 'email' => $userSub->user_email, 'name' => $userSub->display_name );
                }
            }
            
            if ( isset( $newSubscriberList ) ){
                $rt_ticket_email_content['newSubscriberList'] = $newSubscriberList;
            }
            
            if ( isset( $oldSubscriberList ) ){
                $rt_ticket_email_content['oldSubscriberList'] = $oldSubscriberList;
            }
            
            if ( isset( $emailHTML ) ){
                $rt_ticket_email_content['emailHTML'] = $emailHTML;
            }
        }

        /**
         * Diff Email between new ticket data & old ticket data -- can be new function
         * Save meta box data
         */
        public static function save( $post_id, $post ) {
            global $rt_ticket_email_content, $rt_hd_settings, $rt_hd_tickets, $rt_hd_module;
            
            $emailTable = "<table style='width: 100%; border-collapse: collapse; border: none;'>";
            $post_type = Rt_HD_Module::$post_type;
            $module_settings = rthd_get_settings();
            $updateFlag = true;
            $oldUser = $rt_ticket_email_content['oldUser'];
            $newUser = $rt_ticket_email_content['newUser'];
            $newSubscriberList = $rt_ticket_email_content['newSubscriberList'];
            $oldSubscriberList = $rt_ticket_email_content['oldSubscriberList'];
            $emailHTML = $rt_ticket_email_content['emailHTML'];
            $bccemails = $rt_ticket_email_content['bccemails'];
            
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
                $accessToken = $rt_hd_settings->get_accesstoken_from_email( $systemEmail, $signature, $email_type, $imap_server );
                $hdZendEmail = new Rt_HD_Zend_Mail();
                if (strpos($signature, "</") == false) {
                    $signature = htmlentities($signature);
                    $signature = preg_replace('/(\n|\r|\r\n)/i', "<br />", $signature);
                    $signature = preg_replace('/  /i', "  ", $signature);
                }
                $title_suffix = $rt_hd_tickets->create_title_for_mail( $post_id );
                $current_user = wp_get_current_user();
                if ($updateFlag) {
                    if ($oldUser->ID != $newUser->ID) {
                        $title = "[Assigned You] " . $title_suffix;
                        $emailHTML1 = "<b>" . $current_user->display_name ."</b> assigned you new ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";

                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), array(array("email" => $newUser->user_email, "name" => $newUser->display_name)), array(), $post_id, "post");
                        $title = "[Reassigned] " . $title_suffix;
                        $emailHTML1 = "You are no longer responsible for this ticket. It has been reassigned to " . $newUser->display_name . " .<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), array(array("email" => $oldUser->user_email, "name" => $oldUser->display_name)), array(), $post_id, "post");
                    }

                    if (!empty($newSubscriberList)) {
                        $title = "[Subscribe] " . $title_suffix;
                        $emailHTML1 = "You have been <b>subscribed</b> to this ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), $newSubscriberList, array(), $post_id, "post");
                    }

                    if (!empty($oldSubscriberList)) {
                        $title = "[Unsubscribe] " . $title_suffix;
                        $emailHTML1 = "You have been <b>unsubscribed</b> to this ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML1, array(), array(), $oldSubscriberList, array(), $post_id, "post");
                    }
                    if ($emailHTML != "" && !empty($bccemails)) {
                        var_dump( 'send ');
                        $emailHTML = $emailTable . $emailHTML . "</table> </br> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $emailHTML .= "<br />" . 'Ticket updated by : <a target="_blank" href="">'.get_the_author_meta( 'display_name', get_current_user_id() ).'</a>';
                        $emailHTML .= "<br />" . $signature;
                        $title = "[Ticket Updated] " . $title_suffix;
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, stripslashes($emailHTML), array(), array(), $bccemails, array(), $post_id, "post");
                    }

                } else {
                    $newUser = get_user_by("id", $post["post_author"]);
                    if ($newUser) {
                        $title = "[Assigned You] " . $title_suffix;
                        $emailHTML = "New ticket assigned to you.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML, array(), array(), array(array("email" => $newUser->user_email, "name" => $newUser->display_name)), array(), $post_id, "post");
                    }
                    if (!empty($bccemails)) {
                        $title = "[Subscribe] " . $title_suffix;
                        $emailHTML = "You have been <b>subscribed</b> to this ticket.<br /> To View Ticket Click <a href='" . admin_url("edit.php?post_type={$post_type}&page=rthd-add-".$post_type."&".$post_type."_id=" . $post_id) . "'>here</a>.";
                        $rt_hd_settings->insert_new_send_email($systemEmail, $title, $emailHTML, array(), array(), $bccemails, array(), $post_id, "post");
                    }
                }
            }
        }
    }
}