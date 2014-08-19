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

if( !class_exists( 'RT_Meta_Box_Custom_Fields' ) ) {
    class RT_Meta_Box_Custom_Fields {

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

            $arrayMeta = array();
            if (isset($post->ID)) {
                $post_metas = get_post_custom( $post->ID );
                $post_users_meta = array( "subscribe_to", "ticket_closing_date", 'ticket_external_file', 'rthd_unique_id' );
                $post_users_meta = array_merge( $post_users_meta, array_map( 'rthd_extract_key_from_attributes', rthd_get_attributes( $post_type, 'meta' ) ) );
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
                        }?>
                        <div class="row collapse">
                            <div class="large-12 small-12 columns">
                                <div class="large-4 small-4 columns"><?php
                                    if( $user_edit ) { ?>
                                        <input type="text" name="postmeta[<?php echo $count; ?>'][key]" value="<?php echo $key; ?>" /><?php
                                    } else { ?>
                                        <span><?php echo $key.': '; ?></span><?php
                                    } ?>
                                </div>
                                <div class="large-8 small-8 columns"><?php
                                    if( $user_edit ) { ?>
                                        <input type="text" name="postmeta[<?php echo $count; ?>'][value]" value="<?php echo $pmeta; ?>" /><?php
                                    } else { ?>
                                        <span><?php echo $pmeta; ?></span><?php
                                    } ?>
                                </div>
                            </div>
                        </div><?php
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
                </div><?php
            }
        }

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {
            if ( isset( $_POST[ 'postmeta' ] ) ) {
                $postmeta = apply_filters( 'rt_hd_ticket_meta', $_POST['postmeta'] );
                if ( ! empty( $postmeta ) ) {
                    foreach ( $postmeta as $meta ) {
                        $postmeta = apply_filters( 'rt_hd_ticket_meta', $_POST['postmeta'] );
                        update_post_meta( $post_id, $meta['key'], $meta['value'] );
                    }
                }
            }
        }
    }
}