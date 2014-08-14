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
         * Add WC Meta boxes
         */
        public function add_meta_boxes() {
            global $rt_hd_module;

            add_meta_box( 'rt-hd-ticket-data', __( 'Ticket Information', 'rtbiz' ), 'RT_Meta_Box_Ticket_Info::ui', $rt_hd_module->post_type, 'side', 'default' );
            add_meta_box( 'rt-hd-custom-fields', __( 'Custom Fields', 'rtbiz' ), 'RT_Meta_Box_Custom_Fields::ui', $rt_hd_module->post_type, 'normal', 'high' );
            add_meta_box( 'rt-hd-attachment', __( 'Attachment', 'rtbiz' ), 'RT_Meta_Box_Attachment::ui', $rt_hd_module->post_type, 'normal', 'high' );
            add_meta_box( 'rt-hd-external-link', __( 'External Link', 'rtbiz' ), 'RT_Meta_Box_External_Link::ui', $rt_hd_module->post_type, 'normal', 'high' );
            add_meta_box( 'rt-hd-department', __( 'Department', 'rtbiz' ), 'RT_Meta_Box_Department::ui', $rt_hd_module->post_type, 'side', 'default' );
        }


    }
}

