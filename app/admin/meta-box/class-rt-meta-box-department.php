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

if( !class_exists( 'RT_Meta_Box_Department' ) ) {
    class RT_Meta_Box_Department {

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

            $terms = get_terms( 'user-group', array( 'hide_empty' => false ) );
            if ( isset( $_REQUEST['rt_ticket_id'] ) ){
                $selected_term_list = wp_get_post_terms($_REQUEST['rt_ticket_id'], 'user-group', array("fields" => "ids"));
            } ?>

            <div class="row collapse postbox">
                <ul class="rt-form-checkbox scroll-height">
                    <?php foreach( $terms as $key=>$term ){
                        $checked = '';
                        if ( isset( $selected_term_list ) && in_array( $term->term_id, $selected_term_list ) ){
                            $checked= 'checked';
                        }?>
                        <li><label for="user-group-<?php echo $key; ?>"><input title="" id="user-group-<?php echo $key; ?>" <?php echo $checked; ?> name="post[user-group][]" value="<?php echo $term->slug; ?>" type="checkbox"> <?php echo $term->name; ?> </label></li>
                    <?php } ?>
                </ul>
            </div><?php
        }

        /**
         * Save meta box data
         */
        public static function save( $post_id, $post ) {

        }
    }
}