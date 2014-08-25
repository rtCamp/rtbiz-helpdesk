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

if( !class_exists( 'RT_Meta_Box_Subscribers ' ) ) {
    class RT_Meta_Box_Subscribers  {

        /**
         * Output the metabox
         */
        public static function ui( $post ) {

            global $rt_hd_module;

            $post_type = $rt_hd_module->post_type;
            
            $all_hd_participants = array();
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
                            $all_hd_participants = array_merge($all_hd_participants, $p_arr);
                    }
                }
                $all_hd_participants = array_filter( array_unique( $all_hd_participants ) );
            }

            $get_assigned_to = get_post_meta($post->ID, "_subscribe_to", true);
            
            $results = Rt_HD_Utils::get_hd_rtcamp_user();
            $arrCommentReply = array();
            $arrSubscriberUser[] = array();
            $subScribetHTML = "";
            if( !empty( $results ) ) {
                foreach ( $results as $author ) {
                    if ($get_assigned_to && !empty($get_assigned_to) && in_array($author->ID, $get_assigned_to)) {
			if( in_array( $author->user_email, $all_hd_participants ) ) {
                            $key = array_search($author->user_email, $all_hd_participants);
                            if ( $key !== FALSE ) {
                                unset( $all_hd_participants[$key] );
                            }
			}
                       
                        $subScribetHTML .= "<li id='subscribe-auth-" . $author->ID . "' class='contact-list'>" .
                                get_avatar($author->user_email, 24) . 
                                "<a href='#removeSubscriber' class='delete_row'>×</a>" .
                                "<br/><a target='_blank' class='subscribe-title heading' title='".$author->display_name."' href='".get_edit_user_link($author->ID)."'>".$author->display_name."</a>" .
                                "<input type='hidden' name='subscribe_to[]' value='" . $author->ID . "' /></li>";
                    }
                    $arrSubscriberUser[] = array("id" => $author->ID, "label" => $author->display_name, "imghtml" => get_avatar($author->user_email, 24), 'user_edit_link'=>  get_edit_user_link($author->ID));
                    $arrCommentReply[] = array("userid" => $author->ID, "label" => $author->display_name, "email" => $author->user_email, "contact" => false, "imghtml" => get_avatar($author->user_email, 24));
                }
            }?>
            
            <div class="">
                <span class="prefix" title="<?php _e( 'Subscribers' ); ?>"><label><strong><?php _e('Subscribers'); ?></strong></label></span>
                <script>
                    var arr_subscriber_user =<?php echo json_encode($arrSubscriberUser); ?>;
                    var ac_auth_token = '<?php echo get_user_meta(get_current_user_id(), 'rthd_activecollab_token', true); ?>';
                    var ac_default_project = '<?php echo get_user_meta(get_current_user_id(), 'rthd_activecollab_default_project', true); ?>';
                </script>
                <input type="text" placeholder="Type Subscribers Name to select" id="subscriber_user_ac" />
                <ul id="divSubscriberList" class="">
                    <?php echo $subScribetHTML; ?>
                </ul>
            </div><?php 

            do_action( 'rt_hd_after_ticket_information', $post );
            
        }

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {

            // Subscribers
            if ( !isset( $_POST['subscribe_to'] ) ) {
                $_POST['subscribe_to'] = array();
                if( intval( $newTicket['post_author'] ) != get_current_user_id() ) {
                    $_POST['subscribe_to'][] = get_current_user_id();
                }
            }
            update_post_meta( $post_id, '_subscribe_to', $_POST['subscribe_to'] );
        }
    }
}