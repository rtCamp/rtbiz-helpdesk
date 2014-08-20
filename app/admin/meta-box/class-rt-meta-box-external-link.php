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

            $post_type = $post->post_type;

            if ( isset( $post->ID ) ) {
                $ticket_ex_files = get_post_meta( $post->ID, '_ticket_external_file' );
                $count = 1;
                foreach ( $ticket_ex_files as $ex_file ) {
                    $ex_file = (array)json_decode( $ex_file );?>
                    <div class="row collapse">
                        <div class="large-12 small-12 columns">
                            <div class="large-3 small-3 columns">
                                <input type="text" name="ticket_ex_files[<?php echo $count; ?>'][title]" value="<?php echo $ex_file['title']; ?>" />
                            </div>
                            <div class="large-8 small-8 columns">
                                <input type="text" name="ticket_ex_files[<?php echo $count; ?>'][link]" value="<?php echo $ex_file['link']; ?>" />
                            </div>
                            <div class="large-1 small-1 columns">
                                <button class="removeMeta"><i class="foundicon-minus"></i></button>
                            </div>
                        </div>
                    </div><?php
                    $count++;
                }
            }?>

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

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {

            // External File Links
            $old_ex_files = get_post_meta( $post_id, '_ticket_external_file' );
            $new_ex_files = array();
            if ( isset( $_POST['ticket_ex_files'] ) ) {
                    $new_ex_files = $_POST['ticket_ex_files'];

                    delete_post_meta( $post_id, '_ticket_external_file' );

                    foreach ( $new_ex_files as $ex_file ) {
                            if ( empty( $ex_file['link'] ) ) {
                                    continue;
                            }
                            if( empty( $ex_file['title'] ) ) {
                                    $ex_file['title'] = $ex_file['link'];
                            }
                            add_post_meta( $post_id, '_ticket_external_file', json_encode( $ex_file ) );
                    }
            } else {
                    delete_post_meta( $post_id, '_ticket_external_file' );
            }
        }
    }
}