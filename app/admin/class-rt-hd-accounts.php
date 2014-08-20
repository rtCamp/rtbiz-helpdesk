<?php

/**
 * Don't load this file directly!
 */
if (!defined('ABSPATH'))
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Accounts
 *
 * @author udit
 */
if ( ! class_exists( 'Rt_HD_Accounts' ) ) {

	class Rt_HD_Accounts {

		public $email_key = 'account_email';
		function __construct() {
			$this->hooks();
		}

		function hooks() {
			add_filter( 'rt_entity_columns', array( $this, 'accounts_columns' ), 10, 2 );
			add_action( 'rt_entity_manage_columns', array( $this, 'manage_accounts_columns' ), 10, 3 );

			add_action( 'wp_ajax_rthd_add_account', array( $this, 'add_new_account_ajax' ) );
			add_action( 'wp_ajax_rthd_search_account', array( $this, 'account_autocomplete_ajax' ) );
			add_action( 'wp_ajax_rthd_get_term_by_key', array( $this, 'get_term_by_key_ajax' ) );
		}

		public function account_autocomplete_ajax() {
			if (!isset($_POST["query"])) {
				wp_die("Opss!! Invalid request");
			}

			$accounts = rt_biz_search_organization( $_POST['query'] );
			$result = array();
			foreach ( $accounts as $account ) {
				$result[] = array(
					'label' => $account->post_title,
					'id' => $account->ID,
					'slug' => $account->post_name,
					'imghtml' => get_avatar( '', 24 ),
					'url' => admin_url( "edit.php?". $account->post_type."=" . $account->ID . "&post_type=".$_POST['post_type'] ),
				);
			}

			echo json_encode($result);
			die(0);
		}

		public function get_term_by_key_ajax() {
			if ( ! isset( $_POST['account_id'] ) ) {
				wp_die( 'Opss!! Invalid request' );
			}
			if ( ! isset( $_POST['post_type'] ) ) {
				wp_die( 'Opss!! Invalid request' );
			}

			$result = get_post( $_POST['account_id'] );
			$returnArray = array();
			if ( $result ) {
				$returnArray['url'] = admin_url( 'edit.php?'. $result->post_type.'=' . $result->ID . '&post_type=' . $_POST['post_type'] );
				$returnArray['label'] = $result->post_title;

				$returnArray['id'] = $result->ID;
				$returnArray["imghtml"] = get_avatar($result->post_title, 24);
			}
			echo json_encode($returnArray);
			die(0);
		}

		public function add_new_account_ajax() {

			$returnArray = array();
			$returnArray['status'] = false;
			$accountData = $_POST['data'];
			if (!isset($accountData['new-account-name'])) {
				$returnArray['status'] = false;
				$returnArray['message'] = 'Invalid Data Please Check';
			} else {
				$post_id = post_exists( $accountData['new-account-name'] );
				if( ! empty( $post_id ) && get_post_type( $post_id ) === rt_biz_get_organization_post_type() ) {
					$returnArray['status'] = false;
					$returnArray['message'] = 'Account Already Exits';
				} else {
					if ( ! isset( $accountData['new-account-note'] ) ) {
						$accountData['new-account-note'] = '';
					}
					if ( ! isset( $accountData['new-account-country'] ) ) {
						$accountData['new-account-country'] = '';
					}
					if ( ! isset( $accountData['new-account-address'] ) ) {
						$accountData['new-account-address'] = '';
					}
					if( ! isset( $accountData['accountmeta'] ) && !is_array( $accountData['accountmeta'] ) ) {
						$accountData['accountmeta'] = array();
					}

					$post_id = rt_biz_add_organization(
						$accountData['new-account-name'],
						$accountData['new-account-note'],
						$accountData['new-account-address'],
						$accountData['new-account-country'],
						$accountData['accountmeta']
					);

					$post = get_post( $post_id );
					$returnArray['status'] = true;
					$returnArray['data'] = array(
						'id' => $post_id,
						'label' => $accountData['new-account-name'],
						'url' => admin_url( 'edit.php?' . $post->post_type . '=' . $post->ID . '&post_type=' . $accountData['post_type'] ),
						'value' => $post->ID,
						'imghtml' => get_avatar( $accountData['new-account-name'], 24 ),
					);
				}
			}
			echo json_encode( $returnArray );
			die( 0 );
		}

		function accounts_columns( $columns, $rt_entity ) {

			global $rt_organization;
			if ( $rt_entity->post_type != $rt_organization->post_type ) {
				return $columns;
			}

			$columns['country'] = __( 'Country' );

			global $rt_hd_module;
			if ( in_array( Rt_HD_Module::$post_type, array_keys( $rt_entity->enabled_post_types ) ) ) {
				$columns[Rt_HD_Module::$post_type] = $rt_hd_module->labels['name'];
			}

			return $columns;
		}

		function manage_accounts_columns( $column, $post_id, $rt_entity ) {

			global $rt_organization;
			if ( $rt_entity->post_type != $rt_organization->post_type ) {
				return;
			}

			switch( $column ) {
				case 'country':
					if ( class_exists( 'Rt_Entity' ) ) {
						echo implode( ' , ', get_post_meta( $post_id, Rt_Entity::$meta_key_prefix.'account_country' ) );
					}
					break;
				default:
					if ( in_array( Rt_HD_Module::$post_type, array_keys( $rt_entity->enabled_post_types ) ) && $column == Rt_HD_Module::$post_type ) {
						$post_details = get_post( $post_id );
						$pages = rt_biz_get_post_for_organization_connection( $post_id, Rt_HD_Module::$post_type );
						echo '<a href = edit.php?' . $post_details->post_type . '=' . $post_details->ID . '&post_type='.Rt_HD_Module::$post_type.'>' . count( $pages ) . '</a>';
					}
					break;
			}
		}

		function accounts_diff_on_ticket( $post_id, $newTicket ) {

			$diffHTML = '';
			if ( !isset( $newTicket['accounts'] ) ) {
				$newTicket['accounts'] = array();
			}
			$accounts = $newTicket['accounts'];
			$accounts = array_unique($accounts);

			$oldAccountsString = rt_biz_organization_connection_to_string( $post_id );
			$newAccountsSring = '';
			if ( ! empty( $accounts ) ) {
				$accountsArr = array();
				foreach ( $accounts as $account ) {
					$newA = get_post( $account );
					$accountsArr[] = $newA->post_title;
				}
				$newAccountsSring = implode( ',', $accountsArr );
			}
			$diff = rthd_text_diff( $oldAccountsString, $newAccountsSring );
			if ( $diff ) {
				$diffHTML .= '<tr><th style="padding: .5em;border: 0;">Accounts</th><td>' . $diff . '</td><td></td></tr>';
			}

			return $diffHTML;
		}

		function accounts_save_on_ticket( $post_id, $newTicket ) {
			if ( !isset( $newTicket['accounts'] ) ) {
				$newTicket['accounts'] = array();
			}
			$accounts = array_map('intval', $newTicket['accounts']);
			$accounts = array_unique($accounts);

			$post_type = get_post_type( $post_id );

			rt_biz_clear_post_connections_to_organization( $post_type, $post_id );
			foreach ( $accounts as $account ) {
				rt_biz_connect_post_to_organization( $post_type, $post_id, $account );
			}
		}
	}

}
