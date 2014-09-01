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
 * Description of Rt_HD_Email_Diff
 * email difference
 * @author udit
 */
if( !class_exists( 'Rt_HD_Email_Diff') ) {

	if ( !class_exists( 'WP_Text_Diff_Renderer_Table' ) ) {
		require( ABSPATH . WPINC . '/wp-diff.php' );
	}

	class Rt_HD_Email_Diff extends WP_Text_Diff_Renderer_Table {

		var $_leading_context_lines = 2;
		var $_trailing_context_lines = 2;

		function addedLine($line) {
			return "<td style='padding: .5em;border: 0;width:25px;'>+</td><td style='padding: .5em;border: 0;background-color: #dfd;'>{$line}</td>";
		}

		/**
		 * @ignore
		 *
		 * @param string $line HTML-escape the value.
		 * @return string
		 */
		function deletedLine($line) {
			return "<td style='padding: .5em;border: 0;width:25px;' >-</td><td style='padding: .5em;border: 0;background-color: #fdd;' >{$line}</td>";
		}

		/**
		 * @ignore
		 *
		 * @param string $line HTML-escape the value.
		 * @return string
		 */
		function contextLine($line) {
			return "<td style='padding: .5em;border: 0;' > </td><td style='padding: .5em;border: 0;'>{$line}</td>";
		}
	}
}

