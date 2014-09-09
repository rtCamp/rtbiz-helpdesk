<?php
/**
 * Created by PhpStorm.
 * User: spock
 * Date: 9/9/14
 * Time: 12:27 PM
 */

class Rt_HD_function {

	// = null;
	// = new Rt_HD_Ticket_Model();
	var $ticketModel;
	function __construct(){
		$this->$ticketModel = new Rt_HD_Ticket_Model();
	}
	/**
	 * @param      $postArray array of post data to be updated
	 * @param null $postid post id if post need to update
	 *
	 * @return bool|null returns id if sucess else returns false
	 */
	function wp_native_field_update( $postArray, $dataforCustomTable, $postid = null ){

		global $rt_hd_admin_meta_boxes;
		//	$ticketModel = new Rt_HD_Ticket_Model();

		$data = array(
			'assignee'        => $postArray['post_author'],
			'post_content'    => $postArray['post_content'],
			'post_status'     => $postArray['post_status'],
			'post_title'      => $postArray['post_title'],
			'date_create'     => $postArray['post_date'],
			'date_create_gmt' => $postArray['post_date_gmt'],
			'date_update'     => current_time( 'mysql' ),
			'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
			'user_updated_by' => get_current_user_id(),

		);

		if ( is_null( $postid ) ){ // new post
			$post_id = @wp_insert_post( $postArray );

		}
		else { // update post
			// unhook this function so it doesn't loop infinitely
			remove_action( 'save_post', array( $rt_hd_admin_meta_boxes, 'save_meta_boxes' ), 1, 2 );
			remove_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );

			// update the post, which calls save_post again
			$post_id = @wp_update_post( $postArray );

			// re-hook this function
			add_action( 'save_post', array( $rt_hd_admin_meta_boxes, 'save_meta_boxes' ), 1, 2 );
			add_action( 'pre_post_update', 'RT_Ticket_Diff_Email::store_old_post_data', 1, 2 );

		}
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		if ( $this->$ticketModel->is_exist( $post_id ) ) {
			$where = array( 'post_id' => $post_id );
			$ticketModel->update_ticket( $data, $where );
		} else {
			$data  = array_merge( $data, array( 'post_id' => $post_id, 'user_created_by' => get_current_user_id(), ) );
			$ticketModel->add_ticket( $data );
		}

		return $postid;
	}

	function wp_meta_update( $post, $post_id, $newTicket, $data ){
		global $rt_hd_attributes;
		$meta_attributes = rthd_get_attributes( $post->post_type, 'meta' );
		foreach ( $meta_attributes as $attr ) {
			$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $newTicket );
			if ( ! empty( $attr_diff ) ) {
				$rt_hd_attributes->save_attributes( $attr, $post_id, $newTicket );
				/* Update Index Table */
				$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
				$attr_val  = ( ! isset( $newTicket[ $attr->attribute_name ] ) ) ? array() : $newTicket[ $attr->attribute_name ];
				$data      = array_merge( $data, array(
					$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
				) );
				$where     = array( 'post_id' => $post_id );
				$this->$ticketModel->update_ticket( $data, $where );
			}
		}
	}

	function wp_closing_ticket( $post_id, $newTicket, $data, $closing_reason_history_id ){
		global $rt_hd_closing_reason, $rt_hd_ticket_history_model;
		//closing date

		if ( isset( $newTicket['closing-date'] ) && ! empty( $newTicket['closing-date'] ) ) {
			update_post_meta( $post_id, '_rtbiz_hd_closing_date', $newTicket['closing-date'] );
			update_post_meta( $post_id, '_rtbiz_hd_closed_by', get_current_user_id() );
			$cd  = new DateTime( $newTicket['closing-date'] );
			$timeStamp = $cd->getTimestamp();
			$data      = array_merge( $data, array(
				'date_closing'     => gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) ),
				'date_closing_gmt' => get_gmt_from_date( $cd->format( 'Y-m-d H:i:s' ) ) ,
				'user_closed_by'   => get_current_user_id(),
			) );

		}

		//closing_reason
		if ( isset( $newTicket['closing_reason'] ) && ! empty( $newTicket['closing_reason'] ) ) {
			$rt_hd_closing_reason->save_closing_reason( $post_id, $newTicket );
			$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( 'closing-reason' ) );

			$attr_val  = ( ! isset( $newTicket['closing_reason'] ) ) ? array() : $newTicket['closing_reason'];
			$data      = array_merge( $data, array(
				$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
			) );
			$terms     = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( 'closing-reason' ) );
			$message   = '';
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
	}

	function wp_attribute( $post, $post_id, $newTicket, $data ){
		global $rt_hd_attributes;
		//attributes- mata store
		$meta_attributes = rthd_get_attributes( $post->post_type, 'meta' );
		foreach ( $meta_attributes as $attr ) {
			$attr_diff = $rt_hd_attributes->attribute_diff( $attr, $post_id, $newTicket );
			if ( ! empty( $attr_diff ) ) {
				$rt_hd_attributes->save_attributes( $attr, $post_id, $newTicket );
				/* Update Index Table */
				$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
				$attr_val  = ( ! isset( $newTicket[ $attr->attribute_name ] ) ) ? array() : $newTicket[ $attr->attribute_name ];
				$data      = array_merge( $data, array(
					$attr_name => ( is_array( $attr_val ) ) ? implode( ',', $attr_val ) : $attr_val,
				) );
				$where     = array( 'post_id' => $post_id );
				$this->$ticketModel->update_ticket( $data, $where );
			}
		}


		update_post_meta( $post_id, '_rtbiz_hd_updated_by', get_current_user_id() );

	}

	function wp_add_attachment_to_post( $uploaded, $post_id, $post_type, $mainTicket = true, $comment_id = 0 ) {
		if ( isset( $uploaded ) && is_array( $uploaded ) ) {

			foreach ( $uploaded as $upload ) {

				$post_attachment_hashes = get_post_meta( $post_id, '_rtbiz_hd_attachment_hash' );
				if ( ! empty( $post_attachment_hashes ) && in_array( md5_file( $upload['file'] ), $post_attachment_hashes ) ) {
					continue;
				}

				//$uploaded["filename"]
				$attachment = array(
					'post_title'     => $upload['filename'],
					'image_alt'      => $upload['filename'],
					'post_type'      => $post_type,
					'post_content'   => '',
					'post_excerpt'   => '',
					'post_parent'    => $post_id,
					'post_mime_type' => $this->get_mime_type_from_extn( $upload['extn'] ),
					'guid'           => $upload['url'],
				);
				$attach_id  = wp_insert_attachment( $attachment );

				add_post_meta( $attach_id, '_wp_attached_file', $upload['file'] );
				add_post_meta( $post_id, '_rtbiz_hd_attachment_hash', md5_file( $upload['file'] ) );

				if ( $mainTicket ) {
					add_post_meta( $attach_id, 'show-in-main', 'true' );
				}
				if ( $comment_id > 0 ) {
					add_comment_meta( $comment_id, 'attachment', $upload['url'] );
				}
			}
		}
	}

	public static function save( $post_id, $post, $new_attachments ) {

		$old_attachments = get_posts( array(
										'post_parent'    => $post_id,
										'post_type'      => 'attachment',
										'fields'         => 'ids',
										'posts_per_page' => - 1,
									) );
		//$new_attachments = null;
		if ( isset( $new_attachments ) ) {
			//$new_attachments = $_POST['attachment'];
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
						$file = get_attached_file( $attachment );
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
	}
} 