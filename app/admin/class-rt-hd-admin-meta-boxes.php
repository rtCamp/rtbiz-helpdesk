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

if( !class_exists( 'RT_HD_Admin_Meta_Boxes' ) ) {
    class RT_HD_Admin_Meta_Boxes {

        private static $meta_box_errors = array();

        /**
         * Constructor
         */
        public function __construct() {
            add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
            //add_action( 'add_meta_boxes', array( $this, 'rename_meta_boxes' ), 20 );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
            add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );
            
            add_action( 'rt_hd_process_rt_ticket_meta', 'RT_Meta_Box_Ticket_Info::save', 10, 2 );
            add_action( 'rt_hd_process_rt_ticket_meta', 'RT_Meta_Box_Custom_Fields::save', 10, 2 );
            add_action( 'rt_hd_process_rt_ticket_meta', 'RT_Meta_Box_Attachment::save', 10, 2 );
            add_action( 'rt_hd_process_rt_ticket_meta', 'RT_Meta_Box_External_Link::save', 10, 2 );
            add_action( 'rt_hd_process_rt_ticket_meta', 'RT_Meta_Box_Department::save', 10, 2 );
            
        }

        /**
         * Remove bloat
         */
        public function remove_meta_boxes() {
            global $rt_hd_module;

            remove_meta_box( 'postcustom', $rt_hd_module->post_type , 'normal' );
            remove_meta_box('tagsdiv-rt_closing-reason', $rt_hd_module->post_type, 'side' );
            remove_meta_box('p2p-from-rt_ticket_to_rt_contact', $rt_hd_module->post_type, 'side' );
            remove_meta_box('p2p-from-rt_ticket_to_rt_account', $rt_hd_module->post_type, 'side' );
            remove_meta_box( 'commentsdiv', $rt_hd_module->post_type , 'normal' );
            remove_meta_box( 'commentstatusdiv', $rt_hd_module->post_type , 'normal' );
            remove_meta_box( 'slugdiv', $rt_hd_module->post_type , 'normal' );
        }

        /**
         * Rename core meta boxes
         */
        public function rename_meta_boxes() {
            global $rt_hd_module, $post;

            if ( isset( $post ) ) {
                add_meta_box( 'commentsdiv', __( 'Reviews', 'rtbiz' ), 'post_comment_meta_box', $rt_hd_module->post_type , 'normal' );
            }
        }

        /**
         * Add rtbiz Meta boxes
         */
        public function add_meta_boxes() {
            global $rt_hd_module;

            add_meta_box( 'rt-hd-ticket-data', __( 'Ticket Information', 'rtbiz' ), 'RT_Meta_Box_Ticket_Info::ui', $rt_hd_module->post_type, 'side', 'default' );
            add_meta_box( 'rt-hd-custom-fields', __( 'Custom Fields', 'rtbiz' ), 'RT_Meta_Box_Custom_Fields::ui', $rt_hd_module->post_type, 'normal', 'high' );
            add_meta_box( 'rt-hd-attachment', __( 'Attachment', 'rtbiz' ), 'RT_Meta_Box_Attachment::ui', $rt_hd_module->post_type, 'normal', 'high' );
            add_meta_box( 'rt-hd-external-link', __( 'External Link', 'rtbiz' ), 'RT_Meta_Box_External_Link::ui', $rt_hd_module->post_type, 'normal', 'high' );
            add_meta_box( 'rt-hd-department', __( 'Department', 'rtbiz' ), 'RT_Meta_Box_Department::ui', $rt_hd_module->post_type, 'side', 'default' );
        }
        
        public function save_meta_boxes( $post_id, $post ) {
            global $rt_hd_module;
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

            // Check the post type
            if ( ! in_array( $post->post_type, array( $rt_hd_module->post_type ) ) ) {
                    return;
            }
            
            do_action( 'rt_hd_process_' . $post->post_type . '_meta', $post_id, $post );
	}

    }
}

