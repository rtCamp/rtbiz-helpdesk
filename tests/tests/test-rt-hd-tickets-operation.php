<?php

/**
 * Created by PhpStorm.
 * User: sai
 * Date: 11/9/14
 * Time: 7:30 PM
 */
class test_Rt_HD_Tickets_Operation extends RT_WP_TestCase {
	/**
	 * @var $rthdModule object of Rt_HD_Tickets_Operation
	 */
	var $rthdTicketOperation;
	var $rthdticketModel;
	var $post_ID;

	/**
	 * Setup Class Object and Parent Test Suite
	 *
	 */
	function setUp() {
		parent::setUp();
		$this->rthdTicketOperation = new Rt_HD_Tickets_Operation();
		$this->rthdticketModel     = new Rt_HD_Ticket_Model();
		$this->test_ticket_default_field_update_create_ticket();
	}

	/**
	 * Test ticket_default_field_update [ Create New Ticket ]
	 */
	function  test_ticket_default_field_update_create_ticket() {
		$post_date     = current_time( 'mysql' );
		$post_date_gmt = gmdate( 'Y-m-d H:i:s' );
		$postArray     = array(
			'post_author'   => 1,
			'post_content'  => 'Unit test yoyo !!',
			'post_date'     => $post_date,
			'post_status'   => 'unanswered',
			'post_title'    => 'Unit test',
			'post_type'     => Rt_HD_Module::$post_type,
			'post_date_gmt' => $post_date_gmt,
		);

		$dataArray = array(
			'assignee'     => $postArray['post_author'],
			'post_content' => $postArray['post_content'],
			'post_status'  => $postArray['post_status'],
			'post_title'   => $postArray['post_title'],
		);

		$this->post_ID = $this->rthdTicketOperation->ticket_default_field_update( $postArray, $dataArray, Rt_HD_Module::$post_type );
		$this->assertTrue( is_int( $this->post_ID ) );

		//Compare post
		$post = get_post( $this->post_ID );
		$this->assertTrue( is_object( $post ) );
		$this->assertEquals( $this->post_ID, $post->ID );
		$this->assertEquals( '1', $post->post_author );
		$this->assertEquals( 'Unit test yoyo !!', $post->post_content );
		$this->assertEquals( $post_date, $post->post_date );
		$this->assertEquals( 'unanswered', $post->post_status );
		$this->assertEquals( 'Unit test', $post->post_title );
		$this->assertEquals( Rt_HD_Module::$post_type, $post->post_type );
		$this->assertEquals( $post_date_gmt, $post->post_date_gmt );

		$this->assertEquals( '1', get_post_meta( $this->post_ID, '_rtbiz_hd_created_by', true ) );
		$this->assertEquals( '1', get_post_meta( $this->post_ID, '_rtbiz_hd_updated_by', true ) );
		$this->assertFalse( is_null( get_post_meta( $this->post_ID, '_rtbiz_hd_unique_id', true ) ) );

		$this->assertTrue( $this->rthdticketModel->is_exist( $this->post_ID ) );
	}

	/**
	 * Test ticket_default_field_update [ Update Ticket ]
	 */
	function  test_ticket_default_field_update() {
		$post_date     = current_time( 'mysql' );
		$post_date_gmt = gmdate( 'Y-m-d H:i:s' );
		$postArray     = array(
			'post_content' => 'Unit test yoyo updated !!',
			'post_status'  => 'answered',
			'post_title'   => 'Unit test done',
		);

		$dataArray = array(
			'post_content' => $postArray['post_content'],
			'post_status'  => $postArray['post_status'],
			'post_title'   => $postArray['post_title'],
		);
		$this->rthdTicketOperation->ticket_default_field_update( $postArray, $dataArray, Rt_HD_Module::$post_type, $this->post_ID );
		$this->assertTrue( is_int( $this->post_ID ) );

		//Compare post
		$post = get_post( $this->post_ID );
		$this->assertTrue( is_object( $post ) );
		$this->assertEquals( $this->post_ID, $post->ID );
		$this->assertEquals( 'Unit test yoyo updated !!', $post->post_content );
		$this->assertEquals( 'answered', $post->post_status );
		$this->assertEquals( 'Unit test done', $post->post_title );
	}

	/**
	 * Test ticket_closing_field_update
	 */
	function  test_ticket_closing_field_update() {
		$closing_date = current_time( 'mysql' );
		$term         = wp_insert_term(
			'Demo', // the term
			rthd_attribute_taxonomy_name( 'closing-reason' ), // the taxonomy
			array(
				'description' => 'demo closing reason',
				'slug'        => 'demo',
			)
		);
		$this->assertTrue( is_array( $term ) );
		$newTicket = array(
			'closing-date'   => $closing_date,
			'closing_reason' => array( $term['term_id'] ),
		);
		$this->rthdTicketOperation->ticket_closing_field_update( $newTicket, $this->post_ID );
		$this->assertEquals( $closing_date, get_post_meta( $this->post_ID, '_rtbiz_hd_closing_date', true ) );
		$this->assertEquals( '1', get_post_meta( $this->post_ID, '_rtbiz_hd_closed_by', true ) );

		$post_terms = wp_get_post_terms( $this->post_ID, rthd_attribute_taxonomy_name( 'closing-reason' ) );
		$this->assertTrue( is_array( $post_terms ) );

		//Compare term
		foreach ( $post_terms as $post_term ) {
			$this->assertTrue( is_object( $post_term ) );
			$this->assertEquals( $term['term_id'], $post_term->term_id );
			$this->assertEquals( 'Demo', $post_term->name );
			$this->assertEquals( 'demo', $post_term->slug );
			$this->assertEquals( 'demo closing reason', $post_term->description );
			$this->assertEquals( $term['term_taxonomy_id'], $post_term->term_taxonomy_id );
			$this->assertEquals( rthd_attribute_taxonomy_name( 'closing-reason' ), $post_term->taxonomy );
		}
	}

	/**
	 * Test ticket_attribute_update
	 */
	function  test_ticket_attribute_update() {
		global $rt_hd_rt_attributes, $rt_hd_module;

		if ( ! ( taxonomy_exists( rtbiz_post_type_name( 'demo_unittest_taxo' ) ) ) ) {
			$attid = $rt_hd_rt_attributes->add_attribute( 'demo_unittest_taxo', 'demo_unittest_taxo', 'taxonomy', 'checklist' );
			$rt_hd_rt_attributes->add_attribute_relations( $attid, array( Rt_HD_Module::$post_type ) );

			$attid = $rt_hd_rt_attributes->add_attribute( 'demo_unittest_meta', 'demo_unittest_mata', 'meta', 'text' );
			$rt_hd_rt_attributes->add_attribute_relations( $attid, array( Rt_HD_Module::$post_type ) );
		}

		$term = wp_insert_term(
			'Demo', // the term
			rtbiz_post_type_name( 'demo_unittest_taxo' ),
			array(
				'description' => 'demo attr taxonomy',
				'slug'        => 'demo',
			)
		);

		$this->assertTrue( is_array( $term ) );

		$newTicket = array(
			'rt_demo_unittest_taxo' => array( $term['term_id'] ),
			'demo_unittest_mata'    => 'unit test',
		);

		$this->rthdTicketOperation->ticket_attribute_update( $newTicket, Rt_HD_Module::$post_type, $this->post_ID );
		$this->rthdTicketOperation->ticket_attribute_update( $newTicket, Rt_HD_Module::$post_type, $this->post_ID, 'meta' );

		$post_terms = wp_get_post_terms( $this->post_ID, rtbiz_post_type_name( 'demo_unittest_taxo' ) );
		foreach ( $post_terms as $post_term ) {
			$this->assertTrue( is_object( $post_term ) );
			$this->assertEquals( $term['term_id'], $post_term->term_id );
			$this->assertEquals( 'Demo', $post_term->name );
			$this->assertEquals( 'demo', $post_term->slug );
			$this->assertEquals( 'demo attr taxonomy', $post_term->description );
			$this->assertEquals( $term['term_taxonomy_id'], $post_term->term_taxonomy_id );
			$this->assertEquals( rtbiz_post_type_name( 'demo_unittest_taxo' ), $post_term->taxonomy );
		}
		$this->assertEquals( 'unit test', get_post_meta( $this->post_ID, '_rtbiz_hd_demo_unittest_mata', true ) );
	}

	/**
	 * Test ticket_attachment_update
	 */
	function  test_ticket_attachment_update() {

	}


	/**
	 * Test ticket_external_link_update
	 */
	function  test_ticket_external_link_update() {
		$new_ex_files = array(
			array(
				'title' => 'google',
				'link'  => 'www.google.com',
			),
			array(
				'title' => 'yahoo',
				'link'  => 'www.yahoo.com',
			),
		);
		$this->rthdTicketOperation->ticket_external_link_update( $new_ex_files, $this->post_ID );

		$ext_files = get_post_meta( $this->post_ID, '_rtbiz_hd_external_file' );
		foreach ( $ext_files as $key => $ext_file ) {
			$this->assertEquals( json_encode( $new_ex_files[ $key ] ), $ext_file );
		}
	}

	/**
	 * Test ticket_subscribe_update
	 */
	function  test_ticket_subscribe_update() {
		//current user 1
		$subscribe_to = array( '2' );
		$this->rthdTicketOperation->ticket_subscribe_update( null, '1', $this->post_ID );
		$this->assertEquals( array(), get_post_meta( $this->post_ID, '_rtbiz_hd_subscribe_to', true ) );
		$this->rthdTicketOperation->ticket_subscribe_update( $subscribe_to, '1', $this->post_ID );
		$this->assertEquals( array( '2' ), get_post_meta( $this->post_ID, '_rtbiz_hd_subscribe_to', true ) );
		$this->rthdTicketOperation->ticket_subscribe_update( $subscribe_to, '3', $this->post_ID );
		$this->assertEquals( array( '2', '1'), get_post_meta( $this->post_ID, '_rtbiz_hd_subscribe_to', true ) );
	}
}
 