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

            $post_type = $post->post_type;

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

                                    $post_attachment_hashes = get_post_meta( $post_id, '_rt_wp_hd_attachment_hash' );
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

                                            add_post_meta( $post_id, '_rt_wp_hd_attachment_hash', md5_file( $filepath ) );

                                    } else {
                                            wp_update_post( array( 'ID' => $attachment, 'post_parent' => $post_id ) );
                                            $file = get_attached_file( $attachment );
                                            add_post_meta( $post_id, '_rt_wp_hd_attachment_hash', md5_file( $filepath ) );
                                    }
                            }
                    }

                    foreach ( $old_attachments as $attachment ) {
                            if( !in_array( $attachment, $new_attachments ) ) {
                                    wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
                                    $filepath = get_attached_file( $attachment );
                                    delete_post_meta($post_id, '_rt_wp_hd_attachment_hash', md5_file( $filepath ) );
                            }
                    }
            } else {
                    foreach ( $old_attachments as $attachment ) {
                            wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
                            $filepath = get_attached_file( $attachment );
                            delete_post_meta($post_id, '_rt_wp_hd_attachment_hash', md5_file( $filepath ) );
                    }
            }
        }
    }
}