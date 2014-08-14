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

if( !class_exists( 'RT_Meta_Box_External_Link' ) ) {
    class RT_Meta_Box_External_Link {

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

            if ( isset( $post->ID ) ) {
                $ticket_ex_files = get_post_meta( $post->ID, 'ticket_external_file' );
                $count = 1;
                foreach ( $ticket_ex_files as $ex_file ) {
                    $ex_file = (array)json_decode( $ex_file );?>
                    <div class="row collapse">
                        <div class="large-12 small-12 columns">
                            <div class="large-3 small-3 columns"><?php
                                if( $user_edit ) { ?>
                                    <input type="text" name="ticket_ex_files[<?php echo $count; ?>'][title]" value="<?php echo $ex_file['title']; ?>" /><?php
                                } else { ?>
                                    <span><?php echo $ex_file['title'].': '; ?></span><?php
                                } ?>
                            </div><?php
                            if( $user_edit ) { ?>
                                <div class="large-8 small-8 columns">
                                    <input type="text" name="ticket_ex_files[<?php echo $count; ?>'][link]" value="<?php echo $ex_file['link']; ?>" />
                                </div>
                                <div class="large-1 small-1 columns">
                                    <button class="removeMeta"><i class="foundicon-minus"></i></button>
                                </div><?php
                            } else { ?>
                                <div class="large-9 small-9 columns">
                                    <span><?php echo $ex_file['link']; ?></span>
                                </div><?php
                            } ?>
                        </div>
                    </div><?php
                    $count++;
                }
            }

            if( $user_edit ) { ?>
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
                </div><?php
            }
        }

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {

        }
    }
}