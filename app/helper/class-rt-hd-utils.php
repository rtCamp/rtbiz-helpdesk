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
 * Description of Rt_HD_Utils
 * Help desk utility functions
 * @author udit
 */
if ( ! class_exists( 'Rt_HD_Utils' ) ) {

	/**
	 * Class Rt_HD_Utils
	 */
	class Rt_HD_Utils {

		/**
		 * @param $tmpStr
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		static public function forceUFT8( $tmpStr ) {
			//			return preg_replace( '/[^(\x20-\x7F)]*/', '', $tmpStr );
			return $tmpStr;
		}

		/**
		 * mime type key being extension
		 *
		 * @var array
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static $mime_types = array( "pdf" => "application/pdf", "exe" => "application/octet-stream", "zip" => "application/zip", "docx" => "application/msword", "doc" => "application/msword", "xls" => "application/vnd.ms-excel", "ppt" => "application/vnd.ms-powerpoint", "gif" => "image/gif", "png" => "image/png", "jpeg" => "image/jpg", "jpg" => "image/jpg", "mp3" => "audio/mpeg", "wav" => "audio/x-wav", "mpeg" => "video/mpeg", "mpg" => "video/mpeg", "mpe" => "video/mpeg", "mov" => "video/quicktime", "avi" => "video/x-msvideo", "3gp" => "video/3gpp", "css" => "text/css", "jsc" => "application/javascript", "js" => "application/javascript", "php" => "text/html", "htm" => "text/html", "html" => "text/html" );

		/**
		 * Logging errors
		 *
		 * @param        $msg
		 * @param string $filename
		 *
		 * @since rt-Helpdesk 0.1
		 */
		static public function log( $msg, $filename = "error_log.txt" ) {
			$log_file = "/tmp/rt_helpdesk" . $filename;
			if ( $fp = fopen( $log_file, "a+" ) ) {
				fwrite( $fp, "\n" . '[' . date( DATE_RSS ) . '] ' . $msg . "\n" );
				fclose( $fp );
			}
		}

		/**
		 * Set accounts
		 *
		 * @param $rCount
		 *
		 * @since rt-Helpdesk 0.1
		 */
		static public function setAccounts( $rCount ) {
			$log_file = RT_HD_PATH . "mailaccount.txt";
			if ( $fp = fopen( $log_file, "w+" ) ) {
				fwrite( $fp, $rCount );
				fclose( $fp );
			}
		}

		/**
		 * Determine if a post exists based on title, content, and date
		 *
		 * @param string $title Post title
		 * @param string $content Optional post content
		 * @param string $date Optional post date
		 *
		 * @return int Post ID if post exists, 0 otherwise.
		 *
		 * @since rt-Helpdesk 0.1
		 */
		static public function post_exists( $title, $content = '', $date = '' ) {
			global $wpdb;

			$post_title   = stripslashes( sanitize_post_field( 'post_title', $title, 0, 'db' ) );
			$post_content = stripslashes( sanitize_post_field( 'post_content', $content, 0, 'db' ) );
			$post_date    = stripslashes( sanitize_post_field( 'post_date', $date, 0, 'db' ) );

			$query = "SELECT ID FROM $wpdb->posts WHERE 1=1";
			$args  = array();

			if ( ! empty( $date ) ) {
				$query .= ' AND post_date = %s';
				$args[ ] = $post_date;
			}

			if ( ! empty( $title ) ) {
				$query .= ' AND post_title = %s';
				$args[ ] = $post_title;
			}

			if ( ! empty( $content ) ) {
				$query .= 'AND post_content = %s';
				$args[ ] = $post_content;
			}

			if ( ! empty( $args ) ) {
				return $wpdb->get_var( $wpdb->prepare( $query, $args ) );
			}

			return 0;
		}

		/**
		 * Get user
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function get_hd_rtcamp_user() {
			$users = rt_biz_get_module_users( RT_HD_TEXT_DOMAIN );

			return $users;
		}

		/**
		 * Get mime type of file
		 *
		 * @param $file
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function get_mime_type( $file ) {

			// our list of mime types

			$extension = strtolower( end( explode( '.', $file ) ) );
			if ( isset( self::$mime_types[ $extension ] ) ) {
				return self::$mime_types[ $extension ];
			} else {
				return "application/octet-stream";
			}
		}

		/**
		 * Get extension of file
		 *
		 * @param $file
		 *
		 * @return int|string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public static function get_extention( $file ) {

			foreach ( self::$mime_types as $key => $mime ) {
				if ( $mime == $file ) {
					return $key;
				}
			}

			return "tmp";
		}

	}

}
