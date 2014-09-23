<?php

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Tickets_Front
 *
 * @author udit
 *
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Tickets_Front' ) ) {

	/**
	 * Class Rt_HD_Tickets_Front
	 * Initialize the frontend
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rt_HD_Tickets_Front {

		/**
		 * change the title of frontend and template to front end.
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {
			add_filter( 'template_include', array( $this, 'template_include' ), 1, 1 );
			add_filter( 'wp_title', array( $this, 'change_title' ), 9999, 1 );
			//hook the Ajax call
			//for logged-in users
			add_action( 'wp_ajax_my_upload_action', array( $this, 'my_ajax_upload' ) );
			//for none logged-in users
			add_action( 'wp_ajax_nopriv_my_upload_action', array( $this, 'my_ajax_upload' ) );

		}

		/**
		 * uploading file using ajax returns json of new created file details.
		 *@since rt-Helpdesk 0.1
		 */
		function my_ajax_upload() {

			//simple Security check
			//check_ajax_referer('upload_thumb');
			//get POST data
			$post_id = $_POST['post_id'];
			//require the needed files
			require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );
			$returnData = array();
			//then loop over the files that were sent and store them using  media_handle_upload();
			//			var_dump($_FILES);
			if ( $_FILES ) {
				foreach ( $_FILES as $file => $array ) {
					if ( $_FILES[ $file ]['error'] !== UPLOAD_ERR_OK ) {
						$returnData['msg']    = 'upload error : ' . $_FILES[ $file ]['error'];
						$returnData['status'] = false;
						die();
					}
					$attach_id = media_handle_upload( $file, $post_id );
				}
			}


			//and if you want to set that image as Post  then use:
			update_post_meta( $post_id, '_thumbnail_id', $attach_id );
			$returnData['status']    = true;
			$returnData['attach_id'] = $attach_id;
			$returnData['url']       = esc_url( wp_get_attachment_url( $attach_id ) );
			$returnData['name']      = basename( get_attached_file( $attach_id ) );
			$returnData['img']       = wp_mime_type_icon( 'image/jpeg' );
			$returnData['msg']       = 'uploaded the new Thumbnail';
			echo json_encode( $returnData );
			die();
		}

		/**
		 *
		 * Change the Title of frontend.
		 *
		 * @param $title
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function change_title( $title ) {
			global $rthd_front_page_title;
			if ( isset( $rthd_front_page_title ) && ! empty( $rthd_front_page_title ) ) {
				return $rthd_front_page_title;
			}

			return $title;
		}

		/**
		 * include template for ticket on frontend
		 *
		 * @param $template
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function template_include( $template ) {
			global $wp_query;

			if ( ! isset( $wp_query->query_vars['name'] ) ) {
				return $template;
			}

			$name = $wp_query->query_vars['name'];

			$post_type = rthd_post_type_name( $name );
			if ( $post_type != Rt_HD_Module::$post_type ) {
				return $template;
			}

			if ( ! isset( $_REQUEST['rthd_unique_id'] ) || ( isset( $_REQUEST['rthd_unique_id'] ) && empty( $_REQUEST['rthd_unique_id'] ) ) ) {
				return $template;
			}

			$args = array(
				'meta_key'    => '_rtbiz_hd_unique_id',
				'meta_value'  => $_REQUEST['rthd_unique_id'],
				'post_status' => 'any',
				'post_type'   => $post_type,
			);

			$ticketpost = get_posts( $args );
			if ( empty( $ticketpost ) ) {
				return $template;
			}
			$ticket = $ticketpost[0];
			if ( $post_type != $ticket->post_type ) {
				return $template;
			}

			global $rthd_ticket;
			$rthd_ticket = $ticket;
			global $rthd_front_page_title;
			$rthd_front_page_title = ucfirst( $name ) . ' | ' . $rthd_ticket->post_title;

			return rthd_locate_template( 'ticket-front-page.php' );
		}

	}

}
