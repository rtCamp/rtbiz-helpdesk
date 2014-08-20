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

if( !class_exists( 'RT_Meta_Box_Ticket_Info' ) ) {
    class RT_Meta_Box_Ticket_Info {

        /**
         * Output the metabox
         */
        public static function ui( $post ) {

            global $rt_hd_module, $rt_hd_closing_reason, $rt_hd_attributes;
            $labels = $rt_hd_module->labels;
            $post_type = $rt_hd_module->post_type;

            $create = new DateTime($post->post_date);

            $modify = new DateTime($post->post_modified);
            $createdate = $create->format("M d, Y h:i A");
            $modifydate = $modify->format("M d, Y h:i A");

            $close_date_meta = get_post_meta($post->ID, '_ticket_closing_date', true);
            if(!empty($close_date_meta)) {
                $closingdate = new DateTime($close_date_meta);
                $closingdate = $closingdate->format('M d, Y h:i A');
            } else {
                $closingdate = '';
            }
            $rtcamp_users = Rt_HD_Utils::get_hd_rtcamp_user();
            ?>

            <style type="text/css">
               #minor-publishing-actions, #misc-publishing-actions, #visibility, #delete-action{ display:none }
            </style>
            
            <div class="large-12 small-12 ui-sortable meta-box-sortables">
                
                <div id="rthd-assignee" class="row collapse rthd-post-author-wrapper">
                    <div class="large-4 mobile-large-1 columns">
                            <span class="prefix" title="<?php _e( 'Assigned To' ); ?>"><label for="post[post_author]"><strong><?php _e('Assigned To'); ?></strong></label></span>
                    </div>
                    <div class="large-8 mobile-large-3 columns"> 
                        <select name="post[post_author]" ><?php
                            if ( !empty( $rtcamp_users ) ) {
                                foreach ( $rtcamp_users as $author ) {
                                    if ( $author->ID == $post_author ) {
                                            $selected = " selected";
                                    } else {
                                            $selected = " ";
                                    }
                                    echo '<option value="' . $author->ID . '"' . $selected . '>' . $author->display_name . '</option>';
                                }
                            } ?>
                        </select>
                    </div>
                </div>
                
                <div class="row collapse">
                    <div class="small-4 large-4 columns">
                        <span class="prefix" title="Status">Status</span>
                    </div>
                    <div class="small-8 large-8 columns"><?php
                        if (isset($post->ID))
                            $pstatus = $post->post_status;
                        else
                            $pstatus = "";
                        $post_status = $rt_hd_module->get_custom_statuses();
                        $custom_status_flag = true;?>
                        <select id="rthd_post_status" class="right" name="post[post_status]"><?php
                            foreach ( $post_status as $status ) {
                                if ( $status['slug'] == $pstatus ) {
                                    $selected = 'selected="selected"';
                                    $custom_status_flag = false;
                                } else {
                                    $selected = '';
                                }
                                printf('<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name']);
                            }
                            if ( $custom_status_flag && isset( $post->ID ) ) { echo '<option selected="selected" value="'.$pstatus.'">'.$pstatus.'</option>'; } ?>
                        </select>
                    </div>
                </div>

                <div id="rthd_closing_reason_wrapper" class="row collapse <?php echo ( $pstatus === 'trash' ) ? 'show' : 'hide'; ?>">
                    <div class="large-4 small-4 columns">
                        <span class="prefix" title="<?php _e('Closing Reason'); ?>"><label><?php _e('Closing Reason'); ?></label></span>
                    </div>
                    <div class="large-8 small-8 columns"><?php $rt_hd_closing_reason->get_closing_reasons( ( isset( $post->ID ) ) ? $post->ID : '', TRUE ); ?></div>
                </div>

                <div class="row collapse">
                    <div class="large-4 small-4 columns">
                        <span class="prefix" title="Create Date"><label>Create Date</label></span>
                    </div>
                    <div class="large-7 mobile-large-1 columns">
                        <input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
                               value="<?php echo ( isset( $createdate ) ) ? $createdate : ''; ?>"
                               title="<?php echo ( isset( $createdate ) ) ? $createdate : ''; ?>">
                        <input name="post[post_date]" type="hidden" value="<?php echo ( isset( $createdate ) ) ? $createdate : ''; ?>" />
                    </div>
                    <div class="large-1 mobile-large-1 columns">
                        <span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar"></label></span>
                    </div>
                </div><?php

                if (isset($post->ID)) { ?>

                    <div class="row collapse">
                        <div class="large-4 mobile-large-1 columns">
                            <span class="prefix" title="Modify Date"><label>Modify Date</label></span>
                        </div>
                        <div class="large-7 mobile-large-1 columns">
                            <input class="moment-from-now"  type="text" placeholder="Modified on Date"  value="<?php echo $modifydate; ?>"
                                       title="<?php echo $modifydate; ?>" readonly="readonly">
                        </div>
                        <div class="large-1 mobile-large-1 columns">
                            <span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar"></label></span>
                        </div>
                    </div>

                    <div class="row collapse">
                        <div class="large-4 mobile-large-1 columns">
                            <span class="prefix" title="<?php _e('Closing Date'); ?>"><label><?php _e('Closing Date'); ?></label></span>
                        </div>
                        <div class="large-7 mobile-large-2 columns">
                            <input class="datepicker moment-from-now" type="text" placeholder="Select Closing Date"
                                   value="<?php echo ( isset( $closingdate ) ) ? $closingdate : ''; ?>"
                                   title="<?php echo ( isset( $closingdate ) ) ? $closingdate : ''; ?>">
                            <input name="post[closing-date]" type="hidden" value="<?php echo ( isset( $closingdate ) ) ? $closingdate : ''; ?>" />
                        </div>
                        <div class="large-1 mobile-large-1 columns">
                            <span class="postfix datepicker-toggle" data-datepicker="closing-date"><label class="foundicon-calendar"></label></span>
                        </div>
                    </div><?php

                }

                if ( isset( $post->ID ) ) {
                    $rthd_unique_id = get_post_meta($post->ID, 'rthd_unique_id', true);
                    if(!empty($rthd_unique_id)) { ?>
                        <div class="row collapse">
                            <div class="large-4 mobile-large-1 columns">
                                <span class="prefix" title="<?php _e('Public URL'); ?>"><label><?php _e('Public URL'); ?></label></span>
                            </div>
                            <div class="large-8 mobile-large-3 columns">
                                <div class="rthd_attr_border"><a class="rthd_public_link" target="_blank" href="<?php echo trailingslashit(site_url()) . strtolower($labels['name']) . '/?rthd_unique_id=' . $rthd_unique_id; ?>"><?php _e('Link'); ?></a></div>
                            </div>
                        </div><?php
                    }
                }

                if ( isset( $post->ID ) ) { do_action( 'rt_hd_after_ticket_information', $post ); } ?>
            </div> <?php
        }

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {
            global $rt_hd_admin_meta_boxes, $ticketModel, $rt_hd_closing_reason; 

            $newTicket = $_POST['post']; //post data
            $ticketModel = new Rt_HD_Ticket_Model();
            
            //Create Date
            $creationdate = $newTicket['post_date'];
            if ( isset( $creationdate ) && $creationdate != '' ) {
                try {
                    $dr = date_create_from_format( 'M d, Y H:i A', $creationdate );
                                $UTC = new DateTimeZone('UTC');
                                $dr->setTimezone($UTC);
                                $timeStamp = $dr->getTimestamp();
                    $newTicket['post_date'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
                                $newTicket['post_date_gmt'] = gmdate('Y-m-d H:i:s', (intval($timeStamp)));
                } catch ( Exception $e ) {
                    $newTicket['post_date'] = current_time( 'mysql' );
                                $newTicket['post_date_gmt'] = gmdate('Y-m-d H:i:s');
                }
            } else {
                $newTicket['post_date'] = current_time( 'mysql' );
                        $newTicket['post_date_gmt'] = gmdate('Y-m-d H:i:s');
            }

            // Post Data to be saved.
            $newpost = array(
                'post_author' => $newTicket['post_author'],
                'post_status' => $newTicket['post_status'], 
                'post_date' => $newTicket['post_date'],
                'post_date_gmt' => $newTicket['post_date_gmt'],
            );
            $newpost = array_merge( $newpost, array( 'ID' => $post_id ) );
            
            // unhook this function so it doesn't loop infinitely
            remove_action( 'save_post', array( $rt_hd_admin_meta_boxes, 'save_meta_boxes' ), 1, 2 );

            // update the post, which calls save_post again
            @wp_update_post( $newpost );

            // re-hook this function
            add_action( 'save_post', array( $rt_hd_admin_meta_boxes, 'save_meta_boxes' ), 1, 2 );
            
            //closing date
            update_post_meta( $post_id, '_ticket_closing_date', $newTicket['closing-date'] );
            $cd = new DateTime( $newTicket['closing-date'] );
            $UTC = new DateTimeZone('UTC');
            $cd->setTimezone($UTC);
            $timeStamp = $cd->getTimestamp();
            
            //closing_reason
            $rt_hd_closing_reason->save_closing_reason( $post_id, $newTicket );
            $attr_name = str_replace('-', '_', rthd_attribute_taxonomy_name( 'closing_reason' ) );
            $attr_val = (!isset($newTicket['closing_reason'])) ? array() : $newTicket['closing_reason'];
            
            /* Update Index Table */
            $data = array(
                'assignee' => $newpost['post_author'],
                'post_content' => $post->post_content,
                'post_status' => $newpost['post_status'],
                'post_title' => $post->post_title,
                'date_create' => $newpost['post_date'],
                'date_create_gmt' => $newpost['post_date_gmt'],
                'date_update' => current_time( 'mysql' ),
                'date_update_gmt' => gmdate('Y-m-d H:i:s'),
                'user_updated_by' => get_current_user_id(),
                'date_closing' => gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 ))),
                'date_closing_gmt' => gmdate('Y-m-d H:i:s', (intval($timeStamp))),
                'user_closed_by' => get_current_user_id(),
                'user_updated_by' => get_current_user_id(),
                $attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
            );
            if ( $ticketModel->is_exist( $post_id ) ){
                $where = array( 'post_id' => $post_id );
                $ticketModel->update_ticket( $data, $where );
            }else{
                $data = array_merge( $data, array( 'post_id' => $post_id ) );
                $ticketModel->add_ticket( $data );
            }
        }
    }
}