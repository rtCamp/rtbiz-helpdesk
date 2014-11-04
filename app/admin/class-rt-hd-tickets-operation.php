<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rt_HD_Tickets_Operation' ) ) {

	/**
	 * Class Rt_HD_Tickets
	 * This class is for tickets related functions
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rt_HD_Tickets_Operation {

		/**
		 * set hooks
		 *
		 * @since 0.1
		 */
		public function __construct() {

		}

		/**
		 * Create/Update Default ticket Fields
		 *
		 * @since 0.1
		 *
		 * @param      $postArray
		 * @param      $dataArray
		 * @param      $post_type
		 * @param null $post_id
		 *
		 * @return null
		 */
		function ticket_default_field_update( $postArray, $dataArray, $post_type, $post_id = null ) {

			global $rt_hd_cpt_tickets, $rt_hd_ticket_history_model;

			if ( ! isset( $post_type ) || empty( $post_type ) ) {
				return;
			}

			if ( isset( $postArray ) && ! empty( $postArray ) && isset( $dataArray ) && ! empty( $dataArray ) ) {

				$ticketModel = new Rt_HD_Ticket_Model();

				if ( is_null( $post_id ) ) { // new post
					$post_id = @wp_insert_post( $postArray );
					update_post_meta( $post_id, '_rtbiz_hd_created_by', get_current_user_id() );
					$dataArray = array_merge( $dataArray, array(
						'date_create'     => $postArray['post_date'],
						'date_create_gmt' => $postArray['post_date_gmt'],
						'user_created_by' => get_current_user_id(),
					) );
				} else { //update post
					// unhook this function so it doesn't loop infinitely
					remove_action( 'save_post', array( $rt_hd_cpt_tickets, 'save_meta_boxes' ), 1, 2 );
					remove_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );

					// update the post, which calls save_post again
					$postArray = array_merge( $postArray, array( 'ID' => $post_id ) );
					$post_id   = @wp_update_post( $postArray );

					// re-hook this function
					add_action( 'save_post', array( $rt_hd_cpt_tickets, 'save_meta_boxes' ), 1, 2 );
					add_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );
				}

				if ( is_wp_error( $post_id ) ) {
					return false;
				}

				update_post_meta( $post_id, '_rtbiz_hd_updated_by', get_current_user_id() );
				$dataArray = array_merge( $dataArray, array(
					'date_update'     => current_time( 'mysql' ),
					'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
					'user_updated_by' => get_current_user_id(),
				) );

				//Unique link
				$unique_id = get_post_meta( $post_id, '_rtbiz_hd_unique_id', true );
				if ( empty( $unique_id ) ) {
					$d             = new DateTime( $postArray['post_date'] );
					$timeStamp     = $d->getTimestamp();
					$post_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );
					$unique_id     = md5( 'rthd_' . $post_type . '_' . $post_date_gmt );
					update_post_meta( $post_id, '_rtbiz_hd_unique_id', $unique_id );
				}

				if ( $ticketModel->is_exist( $post_id ) ) {
					$where = array( 'post_id' => $post_id );
					$ticketModel->update_ticket( $dataArray, $where );
				} else {
					$data = array_merge( $dataArray, array( 'post_id' => $post_id ) );
					$ticketModel->add_ticket( $data );
				}

				//TODO : History table update
				return $post_id;
			}

			return false;
		}

		/**
		 * Create/update closing ticket Fields
		 *
		 * @since 0.1
		 *
		 * @param $newTicket
		 * @param $post_id
		 *
		 * @return bool
		 */
		function ticket_closing_field_update( $newTicket, $post_id ) {

			global $rt_hd_closing_reason, $rt_ticket_email_content, $rt_hd_ticket_history_model;

			if ( isset( $newTicket ) && ! empty( $newTicket ) && isset( $post_id ) && ! empty( $post_id ) ) {

				$dataArray                 = array();
				$ticketModel               = new Rt_HD_Ticket_Model();
				$closing_reason_history_id = $rt_ticket_email_content['closing_reason_history_id'];

				//closing date
				if ( isset( $newTicket['closing-date'] ) && ! empty( $newTicket['closing-date'] ) ) {
					update_post_meta( $post_id, '_rtbiz_hd_closing_date', $newTicket['closing-date'] );
					update_post_meta( $post_id, '_rtbiz_hd_closed_by', get_current_user_id() );
					$cd        = new DateTime( $newTicket['closing-date'] );
					$timeStamp = $cd->getTimestamp();
					$dataArray = array_merge( $dataArray, array(
						'date_closing'     => gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) ),
						'date_closing_gmt' => get_gmt_from_date( $cd->format( 'Y-m-d H:i:s' ) ),
						'user_closed_by'   => get_current_user_id(),
					) );

				}

				if ( isset( $newTicket['closing_reason'] ) && ! empty( $newTicket['closing_reason'] ) ) {
					$rt_hd_closing_reason->save_closing_reason( $post_id, $newTicket );
					$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( 'closing-reason' ) );

					$attr_val = ( ! isset( $newTicket['closing_reason'] ) ) ? array() : $newTicket['closing_reason'];

					$dataArray = array_merge( $dataArray, array(
						$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
					) );

					$terms   = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( 'closing-reason' ) );
					$message = '';
					foreach ( $terms as $term ) {
						if ( empty( $message ) ) {
							$message .= $term->name;
						} else {
							$message .= ( ' , ' . $term->name );
						}
					}

					if ( $closing_reason_history_id ) {
						$rt_hd_ticket_history_model->update( array( 'message' => $message ), array( 'id' => $closing_reason_history_id ) );
					}
				}

				if ( ! empty( $dataArray ) ) {
					update_post_meta( $post_id, '_rtbiz_hd_updated_by', get_current_user_id() );
					$dataArray = array_merge( $dataArray, array(
						'date_update'     => current_time( 'mysql' ),
						'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
						'user_updated_by' => get_current_user_id(),
					) );
					$where     = array( 'post_id' => $post_id );
					$ticketModel->update_ticket( $dataArray, $where );

					return true;
				}
			}

			return false;
		}

		/**
		 * create/update attributes of Ticket
		 *
		 * @since 0.1
		 *
		 * @param $newTicket
		 * @param $post_type
		 * @param $post_id
		 * @param $attribute_store_as
		 *
		 * @return bool
		 */
		function ticket_attribute_update( $newTicket, $post_type, $post_id, $attribute_store_as = 'taxonomy' ) {

			global $rt_hd_attributes;
			if ( isset( $newTicket ) && ! empty( $newTicket ) && isset( $post_id ) && ! empty( $post_id ) ) {
				$dataArray       = array();
				$ticketModel     = new Rt_HD_Ticket_Model();
				$meta_attributes = rthd_get_attributes( $post_type, $attribute_store_as );
				foreach ( $meta_attributes as $attr ) {
					$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $newTicket );
					if ( ! empty( $attr_diff ) ) {
						$rt_hd_attributes->save_attributes( $attr, $post_id, $newTicket );

						/* Update Index Table */
						if ( $attribute_store_as == 'taxonomy' ){
							$attr_name = str_replace( '-', '_', rtbiz_post_type_name( $attr->attribute_name ) );
						} else {
							$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
						}

						$attr_val  = ( ! isset( $newTicket[ $attr->attribute_name ] ) ) ? array() : $newTicket[ $attr->attribute_name ];
						$dataArray = array_merge( $dataArray, array(
							$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
						) );
					}
				}

				if ( ! empty( $dataArray ) ) {
					update_post_meta( $post_id, '_rtbiz_hd_updated_by', get_current_user_id() );
					$dataArray = array_merge( $dataArray, array(
						'date_update'     => current_time( 'mysql' ),
						'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
						'user_updated_by' => get_current_user_id(),
					) );
					$where     = array( 'post_id' => $post_id );
					$ticketModel->update_ticket( $dataArray, $where );

					return true;
				}
			}

			return false;
		}

		/**
		 * create/update attachment of Ticket
		 *
		 * @since 0.1
		 *
		 * @param $new_attachments
		 * @param $post_id
		 *
		 * @return bool
		 */
		function ticket_attachment_update( $new_attachments, $post_id ) {

			if ( isset( $post_id ) && ! empty( $post_id ) ) {
				$old_attachments = get_posts( array(
												'post_parent'    => $post_id,
												'post_type'      => 'attachment',
												'fields'         => 'ids',
												'posts_per_page' => - 1,
											) );

				if ( ! isset( $old_attachments ) ) {
					$old_attachments = array();
				}

				if ( isset( $new_attachments ) && ! empty( $new_attachments ) ) {
					foreach ( $new_attachments as $attachment ) {
						if ( ! in_array( $attachment, $old_attachments ) ) {
							$file     = get_post( $attachment );
							$filepath = get_attached_file( $attachment );

							$post_attachment_hashes = get_post_meta( $post_id, '_rtbiz_hd_attachment_hash' );
							if ( ! empty( $post_attachment_hashes ) && in_array( md5_file( $filepath ), $post_attachment_hashes ) ) {
								continue;
							}

							if ( ! empty( $file->post_parent ) ) {
								$args = array(
									'post_mime_type' => $file->post_mime_type,
									'guid'           => $file->guid,
									'post_title'     => $file->post_title,
									'post_content'   => $file->post_content,
									'post_parent'    => $post_id,
									'post_author'    => get_current_user_id(),
								);
								wp_insert_attachment( $args, $file->guid, $post_id );

								add_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $filepath ) );

							} else {
								wp_update_post( array( 'ID' => $attachment, 'post_parent' => $post_id ) );
								$filepath = get_attached_file( $attachment );
								add_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $filepath ) );
							}
						}
					}

					foreach ( $old_attachments as $attachment ) {
						if ( ! in_array( $attachment, $new_attachments ) ) {
							wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
							$filepath = get_attached_file( $attachment );
							delete_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $filepath ) );
						}
					}
				} else {
					foreach ( $old_attachments as $attachment ) {
						wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
						$filepath = get_attached_file( $attachment );
						delete_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $filepath ) );
					}
				}

				return true;
			}

			return false;
		}

		/**
		 * create/update external link of Ticket
		 *
		 * @since 0.1
		 *
		 * @param $new_ex_files
		 * @param $post_id
		 *
		 * @return bool
		 */
		function ticket_external_link_update( $new_ex_files, $post_id ) {
			$old_ex_files = get_post_meta( $post_id, '_rtbiz_hd_external_file' );
			if ( isset( $post_id ) && ! empty( $post_id ) ) {
				delete_post_meta( $post_id, '_rtbiz_hd_external_file' );
				if ( isset( $new_ex_files ) && ! empty( $new_ex_files ) ) {
					foreach ( $new_ex_files as $ex_file ) {
						if ( empty( $ex_file['link'] ) ) {
							continue;
						}
						if ( empty( $ex_file['title'] ) ) {
							$ex_file['title'] = $ex_file['link'];
						}
						add_post_meta( $post_id, '_rtbiz_hd_external_file', json_encode( $ex_file ) );
					}
				}

				return true;
			}

			return false;
		}

		/**
		 * create/update subscribe of Ticket
		 *
		 * @since 0.1
		 *
		 * @param $subscribe_to
		 * @param $post_author
		 * @param $post_id
		 *
		 * @return bool
		 */
		function ticket_subscribe_update( $subscribe_to, $post_author, $post_id ) {
			if ( isset( $post_id ) && ! empty( $post_id ) ) {

				if ( ! isset( $subscribe_to ) || empty( $subscribe_to ) ) {
					$subscribe_to = array();
				}

				if ( intval( $post_author ) != get_current_user_id() && ! in_array( get_current_user_id(), $subscribe_to ) ) {
					$subscribe_to[] = get_current_user_id();
				}
				//				if ( isset( $subscribe_to ) && ! empty( $subscribe_to ) ) {
					update_post_meta( $post_id, '_rtbiz_hd_subscribe_to', $subscribe_to );

					return true;
				//				}
			}

			return false;
		}
	}
}
