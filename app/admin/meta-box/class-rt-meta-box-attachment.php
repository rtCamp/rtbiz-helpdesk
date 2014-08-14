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

if( !class_exists( 'RT_Meta_Box_Attachment' ) ) {
    class RT_Meta_Box_Attachment {

        /**
         * Output the metabox
         */
        public static function ui( $post ) {

            global $rt_hd_module, $rt_hd_closing_reason, $rt_hd_attributes;
            $post_type = $rt_hd_module->post_type;

            $user_edit = false;
            if ( current_user_can( "edit_{$post_type}" ) ) {
                $user_edit = 'true';
            } else if ( current_user_can( "read_{$post_type}" ) ) {
                $user_edit = 'false';
            } else {
                wp_die("Opsss!! You are in restricted area");
            }

            $attachments = array();
            if ( isset( $post->ID ) ) {
                $attachments = get_posts( array(
                    'posts_per_page' => -1,
                    'post_parent' => $post->ID,
                    'post_type' => 'attachment',
                ));
            }?>

            <div class="row collapse" id="attachment-container"><?php
                if( $user_edit ) { ?>
                    <a href="#" class="button" id="add_ticket_attachment"><?php _e('Add'); ?></a><?php
                } ?>
                <div class="scroll-height"><?php
                    foreach ($attachments as $attachment) {
                        $extn_array = explode('.', $attachment->guid); $extn = $extn_array[count($extn_array) - 1]; ?>
                        <div class="large-12 mobile-large-3 columns attachment-item" data-attachment-id="<?php echo $attachment->ID; ?>">
                            <a target="_blank" href="<?php echo wp_get_attachment_url($attachment->ID); ?>">
                                <img height="20px" width="20px" src="<?php echo RT_HD_URL . "assets/file-type/" . $extn . ".png"; ?>" /><?php
                                echo $attachment->post_title; ?>
                            </a><?php
                            if( $user_edit ) { ?>
                                <a href="#" class="rthd_delete_attachment right">x</a><?php
                            } ?>
                            <input type="hidden" name="attachment[]" value="<?php echo $attachment->ID; ?>" />
                        </div><?php
                    } ?>
                </div>
            </div><?php
        }

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {

        }
    }
}