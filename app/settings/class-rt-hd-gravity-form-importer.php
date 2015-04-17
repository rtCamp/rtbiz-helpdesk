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
 * Description of Rt_HD_Gravity_Form_Importer
 * Use for Access gravity form data
 * @author udit
 *
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Gravity_Form_Importer' ) ) {

	/**
	 * Class Rt_HD_Gravity_Form_Importer
	 * Use for Access gravity form data
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rt_HD_Gravity_Form_Importer {

		/**
		 * @var
		 */
		var $ticket_field;

		/**
		 *  add hooks for importing Graviy form
		 */
		public function __construct() {

			add_action( 'rtlib_importer_posttype', array( $this, 'add_hd_importer' ) );
			add_action( 'rtlib_importer_fields', array( $this, 'init_importer' ) );
			add_action( 'rtlib_add_mapping_field_ui', array( $this, 'add_hd_mapping_field_ui' ) );

			add_action( 'rtlib_map_import_callback', array( $this, 'process_import' ), 1, 6 );

			add_action( 'rtlib_gravity_form_lead_meta', array( $this, 'gravity_form_lead_meta' ), 1, 2 );
			add_action( 'rtlib_gform_add_custome_field', array( $this, 'rthd_add_custome_field' ), 1, 2 );

		}

		function  add_hd_importer( $post_type ){
			$post_type[ Rt_HD_Module::$post_type ] = array(
				'module' => RT_HD_TEXT_DOMAIN,
				'lable' => 'Ticket',
			);
			return $post_type;
		}

		/**
		 *  for init hook
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function init_importer( $field_array ) {
			global $rt_hd_attributes_relationship_model;

			//check current page is helpdesk setting page
			if ( empty( $_REQUEST['rtbiz_hd_ticket'] ) || $_REQUEST['rtbiz_hd_ticket'] != Rt_HD_Module::$post_type || empty( $_REQUEST['page'] ) || $_REQUEST['page'] != Redux_Framework_Helpdesk_Config::$page_slug ){
				return $field_array;
			}

			$ticket_field = array(
				'title'        => array(
					'display_name' => 'Title',
					'slug'         => 'title',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'any',
				),
				'description'  => array(
					'display_name' => 'Description',
					'slug'         => 'description',
					'required'     => true,
					'multiple'     => false,
					'type'         => 'any',
				),
				'ticketmeta'   => array(
					'display_name' => 'Ticket Meta',
					'slug'         => 'ticketmeta',
					'required'     => false,
					'multiple'     => true,
					'type'         => 'key',
					'key_list'     => 'arr_ticketmeta_key',
				),
				'creationdate' => array(
					'display_name' => 'Create Date',
					'slug'         => 'creationdate',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'date',
				),
				'modifydate'   => array(
					'display_name' => 'Last Modify Date',
					'slug'         => 'modifydate',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'date',
				),
				'assignedto'   => array(
					'display_name'  => 'Assigne To',
					'slug'          => 'assignedto',
					'required'      => false,
					'multiple'      => false,
					'type'          => 'defined',
					'definedsource' => 'arr_assignedto',
				),
				'ticketstatus' => array(
					'display_name'  => 'Status',
					'slug'          => 'ticketstatus',
					'required'      => true,
					'multiple'      => false,
					'type'          => 'defined',
					'definedsource' => 'arr_ticketstatus',
				),
			);

			$attributes = rthd_get_attributes( Rt_HD_Module::$post_type );
			foreach ( $attributes as $attr ) {
				$relation = $rt_hd_attributes_relationship_model->get_relations_by_post_type( Rt_HD_Module::$post_type, $attr->id );
				if ( ! isset( $relation[0] ) ) {
					continue;
				}
				$relation                    = $relation[0];
				$attr_settings               = maybe_unserialize( $relation->settings );
				$slug                        = str_replace( array( '-' ), '_', $attr->attribute_name );
				$ticket_field[ $slug ] = array(
					'display_name' => $attr->attribute_label,
					'slug'         => $slug,
					'required'     => ( isset( $attr_settings['is_required'] ) && $attr_settings['is_required'] == 'yes' ) ? true : false,
				);
				switch ( $attr->attribute_store_as ) {
					case 'meta':
						$ticket_field[ $slug ]['multiple'] = false;
						$ticket_field[ $slug ]['type']     = $attr->attribute_render_type;
						break;
					case 'taxonomy':
						$ticket_field[ $slug ]['type']          = 'defined';
						$ticket_field[ $slug ]['definedsource'] = 'arr_' . $slug;
						if ( $attr->attribute_render_type == 'checklist' ) {
							$ticket_field[ $slug ]['multiple'] = true;
						} else {
							$ticket_field[ $slug ]['multiple'] = false;
						}
						break;
					default:
						do_action( 'rthd_gravity_form_fields_map' & $ticket_field[ $slug ], $attr );
						break;
				}
			}

			$temp_arr           = array(
				'contactname'      => array(
					'display_name' => 'Contact Name',
					'slug'         => 'contactname',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'text',
				),
				'contactfirstname' => array(
					'display_name' => 'Contact First Name',
					'slug'         => 'contactfirstname',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'text',
				),
				'contacttitle'     => array(
					'display_name' => 'Contact Title',
					'slug'         => 'contacttitle',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'text',
				),
				'contactlastname'  => array(
					'display_name' => 'Contact Last Name',
					'slug'         => 'contactlastname',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'text',
				),
				'contactaddress'   => array(
					'display_name' => 'Contact Address',
					'slug'         => 'contactaddress',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'text',
				),
				'contactemail'     => array(
					'display_name' => 'Contact Email',
					'slug'         => 'contactemail',
					'required'     => true,
					'multiple'     => true,
					'type'         => 'email',
				),
				'contactskypeid'   => array(
					'display_name' => 'Contact Skype Id',
					'slug'         => 'contactskypeid',
					'required'     => false,
					'multiple'     => true,
					'type'         => 'text',
				),
				'contactphoneno'   => array(
					'display_name' => 'Contact Phone No',
					'slug'         => 'contactphoneno',
					'required'     => false,
					'multiple'     => true,
					'type'         => 'text',
				),
				'contactmeta'      => array(
					'display_name' => 'Contact Meta',
					'slug'         => 'contactmeta',
					'required'     => false,
					'multiple'     => true,
					'type'         => 'any',
				),
			);
			$ticket_field = array_merge( $ticket_field, $temp_arr );

			$temp_arr           = array(
				'accountname'    => array(
					'display_name' => 'Account Name',
					'slug'         => 'accountname',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'any',
				),
				'accountaddress' => array(
					'display_name' => 'Account Address',
					'slug'         => 'accountaddress',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'any',
				),
				'accountcountry' => array(
					'display_name' => 'Account Country',
					'slug'         => 'accountcountry',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'any',
				),
				'accountmeta'    => array(
					'display_name' => 'Account Meta',
					'slug'         => 'accountmeta',
					'required'     => false,
					'multiple'     => true,
					'type'         => 'any',
				),
			);
			$field_array[ Rt_HD_Module::$post_type ] = array_merge( $ticket_field, $temp_arr );
			return $field_array;
		}

		function add_hd_mapping_field_ui( $post_type ){

			if ( $post_type != Rt_HD_Module::$post_type ){
				return;
			}

			global $rt_hd_module;
			ob_start();
			?>
			<tr>
				<td>
				 Status
				</td>
				<td>
			<?php
			$ticketStatus     = $rt_hd_module->statuses;
			$arr_ticketstatus = array();
			$form_fields      = '<select name="ticketstatus" class="map_form_fields">';
			foreach ( $ticketStatus as $key => $lfield ) {
				$form_fields .= '<option value="' . esc_attr( $lfield['slug'] ) . '">' . esc_html( $lfield['name'] ) . '</option>';
				$arr_ticketstatus[] = array( 'slug' => $lfield['slug'], 'display' => $lfield['name'] );
			}
			$form_fields .= '</select>';
			echo balanceTags( $form_fields );
			echo '<script> var arr_ticketstatus=' . json_encode( $arr_ticketstatus ) . '; </script>';
			?>
				</td>
				<td></td>
				<td></td>
			</tr>

			<tr>
				<td>
					Assignee
				</td>
				<td>
			<?php
			global $wpdb;
			$results          = Rt_HD_Utils::get_hd_rtcamp_user();
			$meta_key_results = $wpdb->get_results( " select distinct meta_key from $wpdb->postmeta inner join $wpdb->posts on post_id=ID and post_type='" . $post_type . "' and  not meta_key like '\_%' order by meta_key" );

			$arr_assignedto = array();
			if ( ! empty( $results ) ) {
				// Name is your custom field key
				echo "<select name='assignedto'>";
				// loop trough each author
				foreach ( $results as $author ) {
					echo '<option value=' . esc_attr( $author->ID ) . ' >' . esc_html( $author->display_name ) . '</option>';
					$arr_assignedto[] = array(
						'slug'    => $author->ID,
						'display' => $author->display_name . ' ' . $author->user_email,
					);
				}

				echo '</select>';
			} else {
				_e( 'No authors found', RT_HD_TEXT_DOMAIN );
			}
			echo '<script> var arr_assignedto=' . json_encode( $arr_assignedto ) . '; </script>';
			?>
				</td>
				<td></td>
				<td></td>
			</tr>

			<?php
			$attributes = rthd_get_attributes( Rt_HD_Module::$post_type );
			foreach ( $attributes as $attr ) {
				?>
				<tr>
					<td><?php echo esc_html( $attr->attribute_label ); ?></td>
					<td>
						<?php
						switch ( $attr->attribute_store_as ) {
							case 'taxonomy':
								$attr_terms = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
									'hide_empty' => false,
									'orderby'    => $attr->attribute_orderby,
									'order'      => 'ASC',
								) );
								$term_array = array();
								if ( is_array( $attr_terms ) && count( $attr_terms ) > 0 ) {
									echo '<select name="' . esc_attr( str_replace( array( '-' ), '_', $attr->attribute_name ) ) . '">';
									echo '<option value="" >Select ' . esc_html( $attr->attribute_label ) . '</option>';
									foreach ( $attr_terms as $term ) {
										echo '<option value="' . esc_attr( $term->slug ) . '" >' . esc_html( $term->name ) . '</option>';
										$term_array[] = array( 'slug' => $term->slug, 'display' => $term->name );
									}
									echo '</select>';
								} else {
									echo 'No ' . esc_html( $attr->attribute_label ) . ' found';
								}
								echo '<script> var arr_' . esc_attr( str_replace( array( '-' ), '_', $attr->attribute_name ) ) . '=' . json_encode( $term_array ) . '; </script>';
								break;
							case 'meta':
								echo '<input type="text" name="' . esc_attr( str_replace( array( '-' ), '_', $attr->attribute_name ) ) . '" />';
								break;
							default:
								do_action( 'rthd_gravity_form_fields_map_default_value' & $this->ticket_field[ $attr->attribute_name ], $attr );
								break;
						}
						?>
					</td>
					<td></td>
					<td></td>
				</tr>
			<?php
			}
			return ob_get_clean();
		}

		/**
		 * Add custom field
		 *
		 * @param $data
		 *
		 * @return mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function rthd_add_custome_field( $data, $form_mappings ) {

			foreach ( $form_mappings as $mapping ) {
				if ( $mapping->enable == 'yes' ) {
					$found_history_field = false;
					if ( ! $found_history_field && isset( $data['notification']['message'] ) ) {
						$data['notification']['message'] .= '<br />rtBiz Helpdesk Ticket :<a href="--rtcamp_hd_link--">rtBiz Helpdesk Link</a>';

						// Hides field output of fields set to a Visibility of Admin Only
						// Ref: http://www.gravityhelp.com/documentation/page/Merge_Tags
						if ( isset( $data['autoResponder']['message'] ) ) {
							$data['autoResponder']['message'] = str_replace( '{all_fields}', '{all_fields:noadmin}', $data['autoResponder']['message'] );
						}
						/* "icing on the cake" by rtCamp - End */
					}
				}
			}

			return $data;
		}

		/**
		 * gracity form lead meta
		 *
		 * @param $gr_lead_id
		 * @param $mappings
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function gravity_form_lead_meta( $gr_lead_id, $mappings ) {
			global $rt_importer;
			foreach ( $mappings as $mapping ) {
				if ( $mapping->enable == 'yes' ) {
					global $rt_hd_module;
					$labels       = $rt_hd_module->labels;
					$post_type    = Rt_HD_Module::$post_type;
					$hd_ticket_id = intval( $rt_importer->gform_get_meta( $gr_lead_id, 'helpdesk-' . $post_type . '-post-id' ) );
					if ( $hd_ticket_id ) {
						echo 'Linked ' . esc_html( Rt_HD_Module::$name ) . " Post : <a href='" . esc_url( get_edit_post_link( $hd_ticket_id ) ) . "' >" . esc_html( get_the_title( $hd_ticket_id ) ) . '</a><br/>';
					}
				}
			}
		}

		/**
		 * process import
		 *
		 * @param      $map_data
		 * @param      $form_id
		 * @param      $gravity_lead_id
		 * @param      $type
		 * @param bool $forceImport
		 * @param bool $autoDieFlag
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function process_import( $map_data, $form_id, $gravity_lead_id, $type, $forceImport = false, $autoDieFlag = true ) {
			//** remove woocommerce hooks **//
			global $rt_importer;

			remove_action( 'create_term', 'woocommerce_create_term', 5, 3 );
			remove_action( 'delete_term', 'woocommerce_delete_term', 5, 3 );

			//** END of remove woocommerce hooks **//

			$response    = array();
			$autoDieFlag = ! $autoDieFlag;

			if ( ! $autoDieFlag ) {
				header( 'Content-Type: application/json' );
			}
			if ( $type == 'gravity' ) {
				if ( is_array( $gravity_lead_id ) ) {
					$gravity_lead_id = $gravity_lead_id[0];
				}
				$response[0]['lead_id'] = $gravity_lead_id;
				if ( ! $forceImport ) {
					$alreadyImported = $rt_importer->gform_get_meta( $gravity_lead_id, 'import-to-helpdesk' );
					if ( $alreadyImported ) {
						if ( $autoDieFlag ) {
							return true;
						}
						$response[0]['status'] = true;
						ob_clean();
						echo json_encode( $response );
						die( 0 );
					}
				}
				$lead_data = RGFormsModel::get_lead( $gravity_lead_id );
				$form      = RGFormsModel::get_form_meta( $form_id );
				foreach ( $map_data as $key => &$field ) {
					if ( isset( $field[0] ) ) {
						foreach ( $field as &$meta ) {
							if ( ! ( strpos( $meta['fieldName'], 'field-' ) === false ) ) {
								$field_id = intval( str_replace( 'field-', '', $meta['fieldName'] ) );
								if ( $field_id < 1 ) {
									$field_id = str_replace( 'field-', '', $meta['fieldName'] );
								}
								$f_field = RGFormsModel::get_field( $form, intval( str_replace( 'field-', '', $meta['fieldName'] ) ) );
								$tValue  = '';
								if ( isset( $lead_data[ $field_id ] ) ) {
									$tValue = $lead_data[ $field_id ];
								} else {
									if ( isset( $f_field['inputs'] ) && ! empty( $f_field['inputs'] ) ) {
										$sep = '';
										foreach ( $f_field['inputs'] as $input ) {
											$tValue .= $sep . $lead_data[ strval( $input['id'] ) ];
											$sep = ' ';
										}
									}
								}

								if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'defined' ) {
									if ( trim( $meta['fieldName'] ) == '' ) {
										$tValue = $meta['defaultValue'];
									} else {
										if ( isset( $meta['mappingData'][ $tValue ] ) ) {
											$tValue = $meta['mappingData'][ $tValue ];
										} else {
											$tValue = $meta['defaultValue'];
										}
									}
								} else {
									$f_field = RGFormsModel::get_field( $form, $field_id );
									$tValue  = $lead_data[ $field_id ];
									if ( trim( $tValue ) == '' ) {
										$tValue = $meta['defaultValue'];
									}
								}
								$tKey = $f_field['label'];
								if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'type' ) {
									if ( isset( $meta['keyname'] ) && trim( $meta['keyname'] ) != '' ) {
										$tKey = $meta['keyname'];
									}
								}
								$meta = array( 'key' => ucfirst( $tKey ), 'value' => $tValue );
							} else {
								if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'defined' ) {
									if ( trim( $meta['fieldName'] ) == '' ) {
										$tfield = $meta['defaultValue'];
									} else {
										if ( isset( $meta['mappingData'][ $tValue ] ) ) {
											$tfield = $meta['mappingData'][ $tValue ];
										} else {
											$tfield = $meta['defaultValue'];
										}
									}
								} else {
									$tfield = $meta['fieldName'];
								}
								$tKey = $f_field['label'];
								if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'type' ) {
									if ( isset( $meta['keyname'] ) && trim( $meta['keyname'] ) != '' ) {
										$tKey = $meta['keyname'];
									}
								}

								$meta = array( 'key' => ucfirst( $tKey ), 'value' => $meta['fieldName'] );
							}
						}
					} else {
						if ( ! ( strpos( $field['fieldName'], 'field-' ) === false ) ) {
							$f_field = RGFormsModel::get_field( $form, intval( str_replace( 'field-', '', $field['fieldName'] ) ) );
							$tfield  = '';
							if ( isset( $lead_data[ intval( str_replace( 'field-', '', $field['fieldName'] ) ) ] ) ) {
								$tfield = $lead_data[ intval( str_replace( 'field-', '', $field['fieldName'] ) ) ];
							} else {
								if ( isset( $f_field['inputs'] ) && ! empty( $f_field['inputs'] ) ) {
									$sep = '';
									foreach ( $f_field['inputs'] as $input ) {
										$tfield .= $sep . $lead_data[ strval( $input['id'] ) ];
										$sep = ' ';
									}
								}
							}

							if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'defined' ) {
								if ( trim( $tfield ) == '' ) {
									$tfield = $field['defaultValue'];
								} else {
									if ( isset( $field['mappingData'][ $tfield ] ) ) {
										$tfield = $field['mappingData'][ $tfield ];
									} else {
										$tfield = $field['defaultValue'];
									}
								}
							}
							if ( trim( $tfield ) == '' ) {
								$tfield = $field['defaultValue'];
							}
							$field = $tfield;
						} else {
							$field = $field['fieldName'];
						}
					}
				}

				if ( ! isset( $map_data['fieldName'] ) ) {
					$map_data['creationdate'] = $lead_data['date_created'];
				}

				if ( $autoDieFlag ) {
					if ( ! isset( $map_data['titleprefix'] ) ) {
						$map_data['titleprefix'] = '';
					}
					if ( ! isset( $map_data['titlesuffix'] ) ) {
						$map_data['titlesuffix'] = '';
					}
					if ( ! isset( $map_data['contactname'] ) ) {
						$contactname = '';
					} else {
						$contactname = $map_data['contactname'];
					}
					if ( isset( $map_data['contactfirstname'] ) ) {
						$contactname = $map_data['contactfirstname'] . ' ' . $contactname;
					}

					if ( isset( $map_data['contactlastname'] ) ) {
						$contactname .= ' ' . $map_data['contactlastname'];
					}

					if ( isset( $map_data['contacttitle'] ) ) {
						$contactname = $map_data['contacttitle'] . ' ' . $contactname;
					}
					$siteurl = '';
					if ( isset( $map_data['ticketmeta'] ) ) {
						$ticketmeta = $map_data['ticketmeta'];
						foreach ( $ticketmeta as $ticketm ) {
							if ( strtolower( $ticketm['key'] ) == 'site url' ) {
								$siteurl = ' -' . $ticketm['value'];
								break;
							}
						}
					}
					if ( ! isset( $map_data['title'] ) ) {
						$map_data['title'] = 'Enquiry From ' . $contactname;
					}
				}

				$response[0] = $this->create_tickets_from_map_data( $map_data, $gravity_lead_id, $type );
				if ( $autoDieFlag ) {
					return $response[0]['status'];
				}
				//ob_end_clean();
				echo json_encode( $response );
				die( 0 );
			} else {
				if ( $type == 'csv' ) {
					$start_memory = memory_get_usage();
					$csv          = new parseCSV();
					$csv->auto( $form_id );
					if ( is_array( $gravity_lead_id ) ) {
						$sampleMap = $map_data;
						foreach ( $gravity_lead_id as $row_index ) {
							unset( $map_data );
							$map_data    = $sampleMap;
							$tmpArrayKey = array();
							foreach ( $map_data as $key => &$field ) {
								if ( isset( $field[0] ) ) {
									foreach ( $field as &$meta ) {
										if ( ! ( strpos( $meta['fieldName'], 'field-' ) === false ) ) {
											$field_id = str_replace( '-s-', ' ', str_replace( 'field-', '', $meta['fieldName'] ) );
											if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'defined' ) {
												if ( array_key_exists( $field_id, $csv->data[ $row_index ] ) ) {
													$tfield = $csv->data[ $row_index ][ $field_id ];
													if ( trim( $tfield ) == '' ) {
														$tfield = $meta['defaultValue'];
													} else {
														if ( isset( $meta['mappingData'][ $tfield ] ) ) {
															$tfield = $meta['mappingData'][ $tfield ];
														} else {
															$tfield = $meta['defaultValue'];
														}
													}
												} else {
													$tfield = $meta['defaultValue'];
												}
												$tKey = $field_id;
												if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'key' ) {
													if ( isset( $meta['keyname'] ) && trim( $meta['keyname'] ) != '' ) {
														$tKey = $meta['keyname'];
													}
												}
												$meta = array( 'key' => ucfirst( $tKey ), 'value' => $tfield );
											} else {
												if ( array_key_exists( $field_id, $csv->data[ $row_index ] ) ) {
													$tKey = $field_id;
													if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'key' ) {
														if ( isset( $meta['keyname'] ) && trim( $meta['keyname'] ) != '' ) {
															$tKey = $meta['keyname'];
														}
													}

													if ( $csv->data[ $row_index ][ $field_id ] == '' ) {
														$meta = array(
															'key'   => ucfirst( $tKey ),
															'value' => $meta['defaultValue'],
														);
													} else {
														$meta = array(
															'key'   => ucfirst( $tKey ),
															'value' => $csv->data[ $row_index ][ $field_id ],
														);
													}
												} else {
													$meta = array(
														'key'   => ucfirst( $tKey ),
														'value' => $meta['defaultValue'],
													);
												}
											}
										} else {
											$tKey = $field_id;
											if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'key' ) {
												if ( isset( $meta['keyname'] ) && trim( $meta['keyname'] ) != '' ) {
													$tKey = $meta['keyname'];
												}
											}
											$meta = array( 'key' => ucfirst( $tKey ), 'value' => $meta['fieldName'] );
										}
									}
								} else {
									if ( ! ( strpos( $field['fieldName'], 'field-' ) === false ) ) {
										$field_id = str_replace( '-s-', ' ', str_replace( 'field-', '', $field['fieldName'] ) );
										if ( array_key_exists( $field_id, $csv->data[ $row_index ] ) ) {
											$tfield = $csv->data[ $row_index ][ $field_id ];
											if ( isset( $this->ticket_field[ $key ]['type'] ) && $this->ticket_field[ $key ]['type'] == 'defined' ) {
												if ( trim( $tfield ) == '' ) {
													$tfield = $field['defaultValue'];
												} else {
													if ( isset( $field['mappingData'][ $tfield ] ) ) {
														$tfield = $field['mappingData'][ $tfield ];
													} else {
														$tfield = $field['defaultValue'];
													}
												}
											}
											if ( trim( $tfield ) == '' ) {
												$tfield = $field['defaultValue'];
											}
											$field = $tfield;
										} else {
											$tfield = $field['defaultValue'];
										}
										$tmpArrayKey[] = $field_id;
									} else {
										$field = $field['fieldName'];
									}
								}
							}

							$response[] = $this->create_tickets_from_map_data( $map_data, $row_index, $type );
						}
						unset( $csv );
						$response[0]['startmemory'] = $start_memory;
						$response[0]['endmemory']   = memory_get_usage( true );

						//ob_end_clean();
						echo json_encode( $response );
						die( 0 );
					}
				}
			}
			// IMP LINE
		}


		/**
		 * create tickets from map data
		 *
		 * @param $map_data
		 * @param $gravity_lead_id
		 * @param $type
		 *
		 * @return array
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function create_tickets_from_map_data( $map_data, $gravity_lead_id, $type ) {

			global $rt_importer;

			$contactemail = array();
			$description = '';
			$assignedto = false;

			extract( $map_data, EXTR_OVERWRITE );

			global $transaction_id;
			if ( empty( $transaction_id ) ){
				$transaction_id = esc_attr( time() );
			}

			$post_type       = Rt_HD_Module::$post_type;
			$ticketModel     = new Rt_HD_Ticket_Model();
			if ( isset( $creationdate ) ) {
				$creationdate = trim( $creationdate );

				if ( isset( $dateformat ) && $dateformat != '' ) {
					$dateformat = trim( $dateformat );
					$dr         = date_create_from_format( $dateformat, $creationdate );
					if ( $dr ) {
						$creationdate = $dr->format( 'Y-m-d H:i:s' );
					} else {
						try {
							$dt           = new DateTime( $creationdate );
							$creationdate = $dt->format( 'Y-m-d H:i:s' );
						} catch ( Exception $e ) {
							$creationdate = 'now';
						}
					}
				} else {
					try {
						$dt           = new DateTime( $creationdate );
						$creationdate = $dt->format( 'Y-m-d H:i:s' );
					} catch ( Exception $e ) {
						$creationdate = 'now';
					}
				}
			} else {
				$creationdate = 'now';
			}
			$fromemail = array();
			$allemail  = array();
			if ( ! isset( $title ) ) {
				$title = '';
			}

			if ( ! isset( $contactname ) ) {
				$contactname = '';
			}
			if ( isset( $contactfirstname ) ) {
				$contactname = $contactfirstname . ' ' . $contactname;
			}

			if ( isset( $contactlastname ) ) {
				$contactname .= ' ' . $contactlastname;
			}

			if ( isset( $contacttitle ) ) {
				$contactname = $contacttitle . ' ' . $contactname;
			}

			foreach ( $contactemail as $email ) {
				if ( empty( $fromemail ) ) {
					$fromemail['address'] = $email['value'];
					if ( isset( $contactname ) && trim( $contactname ) != '' ) {
						$fromemail['name'] = $contactname;
					}
				}
				$allemail[] = array( 'address' => $email['value'], 'name' => $contactname );
			}

			if ( ! isset( $titleprefix ) ) {
				$titleprefix = '';
			}
			if ( ! isset( $titlesuffix ) ) {
				$titlesuffix = '';
			}

			if ( $title == '' ) {
				if ( $type == 'csv' ) {
					$title = 'CSV Entry ' . $gravity_lead_id;
				} else {
					$title = 'Gravity Entry ' . $gravity_lead_id;
				}
			}

			$title = $titleprefix . ' ' . $title . ' ' . $titlesuffix;
			$title = trim( $title );
			global $rt_hd_import_operation;

			$ticket_id           = $rt_hd_import_operation->process_email_to_ticket( $title, $description, $fromemail, $creationdate, $allemail, array(), $description, false, $assignedto );
			$response            = array();
			$response['lead_id'] = $gravity_lead_id;
			if ( ! $ticket_id ) {
				$response['status'] = false;
			} else {
				if ( $type == 'gravity' ) {
					$rt_importer->gform_update_meta( $gravity_lead_id, 'import-to-helpdesk', 1 );
					$rt_importer->gform_update_meta( $gravity_lead_id, 'helpdesk-' . $post_type . '-post-id', $ticket_id );
					if ( isset( $transaction_id ) && $transaction_id > 0 ) {
						$rt_importer->gform_update_meta( $gravity_lead_id, '_transaction_id', $transaction_id );
					}
				}
				$response['status'] = true;
				update_post_meta( $ticket_id, '_rtbiz_hd_gravity_form_all_data', $_REQUEST );
				if ( isset( $ticketmeta ) ) {
					foreach ( $ticketmeta as $ticketm ) {
						update_post_meta( $ticket_id, $ticketm['key'], $ticketm['value'] );
					}
				}

				if ( isset( $modifydate ) ) {
					$modifydate = trim( $modifydate );
					if ( isset( $dateformat ) && trim( $dateformat ) != '' ) {
						$dr = date_create_from_format( $dateformat, $modifydate );
						if ( $dr ) {
							$modifydate      = $dr->format( 'Y-m-d H:i:s' );
							$timeStamp       = $dr->getTimestamp();
							$modify_date     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
							$modify_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );

							$my_post                      = array();
							$my_post['ID']                = $ticket_id;
							$my_post['post_modified']     = $modify_date;
							$my_post['post_modified_gmt'] = $modify_date_gmt;
							wp_update_post( $my_post );

							$where = array( 'post_id' => $ticket_id );
							$data  = array(
								'date_update'     => $modify_date,
								'date_update_gmt' => $modify_date_gmt,
								'user_updated_by' => get_current_user_id(),
							);
							$ticketModel->update_ticket( $data, $where );
						} else {
							try {
								$dt              = new DateTime( $modifydate );
								$modifydate      = $dt->format( 'Y-m-d H:i:s' );
								$timeStamp       = $dr->getTimestamp();
								$modify_date     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
								$modify_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );

								$my_post                      = array();
								$my_post['ID']                = $ticket_id;
								$my_post['post_modified']     = $modifydate;
								$my_post['post_modified_gmt'] = $modify_date_gmt;
								wp_update_post( $my_post );

								$where = array( 'post_id' => $ticket_id );
								$data  = array(
									'date_update'     => $modify_date,
									'date_update_gmt' => $modify_date_gmt,
									'user_updated_by' => get_current_user_id(),
								);
								$ticketModel->update_ticket( $data, $where );

							} catch ( Exception $e ) {
								$modifydate = 'now';
							}
						}
					} else {
						try {
							$dt              = new DateTime( $modifydate );
							$modifydate      = $dt->format( 'Y-m-d H:i:s' );
							$timeStamp       = $dr->getTimestamp();
							$modify_date     = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) + ( get_option( 'gmt_offset' ) * 3600 ) ) );
							$modify_date_gmt = gmdate( 'Y-m-d H:i:s', ( intval( $timeStamp ) ) );

							$my_post                      = array();
							$my_post['ID']                = $ticket_id;
							$my_post['post_modified']     = $modifydate;
							$my_post['post_modified_gmt'] = $modify_date_gmt;
							wp_update_post( $my_post );

							$where = array( 'post_id' => $ticket_id );
							$data  = array(
								'date_update'     => $modify_date,
								'date_update_gmt' => $modify_date_gmt,
								'user_updated_by' => get_current_user_id(),
							);
							$ticketModel->update_ticket( $data, $where );

						} catch ( Exception $e ) {
							$modifydate = 'now';
						}
					}
				}

				if ( isset( $ticketstatus ) && $ticketstatus != 'new' ) {
					$my_post                = array();
					$my_post['ID']          = $ticket_id;
					$my_post['post_status'] = $ticketstatus;
					wp_update_post( $my_post );

					// Update Index Table
					$where = array( 'post_id' => $ticket_id );
					$data  = array(
						'post_status'     => $ticketstatus,
						'date_update'     => current_time( 'mysql' ),
						'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
						'user_updated_by' => get_current_user_id(),
					);
					$ticketModel->update_ticket( $data, $where );

					// System Notification -- Meta Attribute updated
				}


				$attributes = rthd_get_attributes( Rt_HD_Module::$post_type );
				foreach ( $attributes as $attr ) {
					$slug = str_replace( array( '-' ), '_', $attr->attribute_name );
					switch ( $attr->attribute_store_as ) {
						case 'taxonomy':
							$tax_name = rthd_attribute_taxonomy_name( $attr->attribute_name );
							switch ( $attr->attribute_render_type ) {
								case 'dropdown':
								case 'radio':
									if ( isset( ${$slug} ) && ! empty( ${$slug} ) ) {
										$term_id = term_exists( ${$slug}, $tax_name );
										if ( ! $term_id ) {
											$term    = wp_insert_term( ${$slug}, $tax_name, array(
												'description' => ${$slug},
												'slug'        => strtolower( ${$slug} )
											) );
											$term_id = $term['term_id'];
											if ( isset( $transaction_id ) && $transaction_id > 0 ) {
												add_term_meta( $term_id, '_transaction_id', $transaction_id, true );
											}
										} else {
											$term_id = $term_id['term_id'];
											if ( isset( $transaction_id ) && $transaction_id > 0 ) {
												delete_term_meta( $term_id, '_transaction_id' );
												add_term_meta( $term_id, '_transaction_id', $transaction_id, true );
											}
										}
										$term     = get_term_by( 'id', $term_id, $tax_name );
										$termslug = $term->slug;
										@wp_set_object_terms( $ticket_id, $termslug, $tax_name, true );

										// Update Index Table
										$attr_name = str_replace( '-', '_', $tax_name );
										$where     = array( 'post_id' => $ticket_id );
										$data      = array(
											$attr_name        => $termslug,
											'date_update'     => current_time( 'mysql' ),
											'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
											'user_updated_by' => get_current_user_id(),
										);
										$ticketModel->update_ticket( $data, $where );

										// System Notification -- Tax Attribute updated
									}
									break;
								case 'checklist':
									if ( isset( ${$slug} ) && ! empty( ${$slug} ) && is_array( ${$slug} ) ) {
										$termslug = array();
										foreach ( ${$slug} as $attr_term ) {
											if ( $attr_term['value'] == '' ) {
												continue;
											}
											$term_id = term_exists( $attr_term['value'], $tax_name );
											if ( ! $term_id ) {
												$term    = wp_insert_term( $attr_term['value'], $tax_name, array(
													'description' => $attr_term['value'],
													'slug'        => strtolower( $attr_term['value'] )
												) );
												$term_id = $term['term_id'];
												if ( isset( $transaction_id ) && $transaction_id > 0 ) {
													add_term_meta( $term_id, '_transaction_id', $transaction_id, true );
												}
											} else {
												$term_id = $term_id['term_id'];
												if ( isset( $transaction_id ) && $transaction_id > 0 ) {
													delete_term_meta( $term_id, '_transaction_id' );
													add_term_meta( $term_id, '_transaction_id', $transaction_id, true );
												}
											}
											$term       = get_term_by( 'id', $term_id, $tax_name );
											$termslug[] = $term->slug;
										}
										@wp_set_object_terms( $ticket_id, $termslug, $tax_name, true );

										// Update Index Table
										$attr_name = str_replace( '-', '_', $tax_name );
										$where     = array( 'post_id' => $ticket_id );
										$data      = array(
											$attr_name        => implode( ',', $termslug ),
											'date_update'     => current_time( 'mysql' ),
											'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
											'user_updated_by' => get_current_user_id(),
										);
										$ticketModel->update_ticket( $data, $where );

										// System Notification -- Tax Attribute updated
									}
									break;
								default:
									do_action( 'rthd_save_gravity_import_attributes', $map_data, $gravity_lead_id, $type );
									break;
							}
							break;
						case 'meta':
							switch ( $attr->attribute_render_type ) {
								case 'text':
								case 'currency':
									if ( isset( ${$slug} ) ) {
										add_post_meta( $ticket_id, $attr->attribute_name, ${$slug} );

										// Update Index Table
										$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
										$where     = array( 'post_id' => $ticket_id );
										$data      = array(
											$attr_name        => ${$slug},
											'date_update'     => current_time( 'mysql' ),
											'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
											'user_updated_by' => get_current_user_id(),
										);
										$ticketModel->update_ticket( $data, $where );

										// System Notification -- Meta Attribute updated
									}
									break;
								case 'date':
									if ( isset( ${$slug} ) ) {
										if ( isset( $dateformat ) && trim( $dateformat ) != '' ) {
											$dr = date_create_from_format( $dateformat, ${$slug} );
											if ( ! $dr ) {
												try {
													$dr = new DateTime( ${$slug} );
												} catch ( Exception $e ) {
													$dr = false;
												}
											}
											if ( $dr ) {
												${$slug} = $dr->format( 'Y-m-d H:i:s' );
											}
										} else {
											try {
												$dr      = new DateTime( ${$slug} );
												${$slug} = $dr->format( 'Y-m-d H:i:s' );
											} catch ( Exception $e ) {

											}
										}
										add_post_meta( $ticket_id, $attr->attribute_name, ${$slug} );

										// Update Index Table
										$attr_name = str_replace( '-', '_', rthd_attribute_taxonomy_name( $attr->attribute_name ) );
										$where     = array( 'post_id' => $ticket_id );
										$data      = array(
											$attr_name        => ${$slug},
											'date_update'     => current_time( 'mysql' ),
											'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
											'user_updated_by' => get_current_user_id(),
										);
										$ticketModel->update_ticket( $data, $where );

										// System Notification -- Meta Attribute updated

									}
									break;
								default:
									do_action( 'rthd_save_gravity_import_attributes', $map_data, $gravity_lead_id, $type );
									break;
							}
							break;
						default:
							do_action( 'rthd_save_gravity_import_attributes', $map_data, $gravity_lead_id, $type );
							break;
					}
				}

				$contact = rt_biz_get_contact_by_email( $fromemail['address'] );
				if ( ! empty( $contact ) && isset( $contact[0] ) ) {
					$contact = $contact[0];
					if ( isset( $contactskypeid ) && ! empty( $contactskypeid ) ) {
						foreach ( $contactskypeid as $cSkype ) {
							rt_biz_add_entity_meta( $contact->ID, 'contact_skype_id', $cSkype['value'] );
						}
					}
					if ( isset( $contactphoneno ) && ! empty( $contactphoneno ) ) {
						foreach ( $contactphoneno as $cphone ) {
							rt_biz_add_entity_meta( $contact->ID, 'contact_phone', $cphone['value'] );
						}
					}
					if ( isset( $contactaddress ) && ! empty( $contactaddress ) ) {
						rt_biz_add_entity_meta( $contact->ID, 'contact_address', $contactaddress );
					}
					if ( isset( $contactmeta ) && ! empty( $contactmeta ) ) {
						foreach ( $contactmeta as $cmeta ) {
							rt_biz_add_entity_meta( $contact->ID, $cmeta['key'], $cmeta['value'] );
						}
					}
				}

				// Contact will be linked with the ticket later while creating the ticket.

				if ( isset( $accountname ) && trim( $accountname ) != '' ) {
					if ( ! isset( $accountaddress ) ) {
						$accountaddress = '';
					}
					if ( ! isset( $accountcountry ) ) {
						$accountcountry = '';
					}
					if ( ! isset( $accountnote ) ) {
						$accountnote = '';
					}
					if ( ! isset( $accountmeta ) ) {
						$accountmeta = array();
					}
					$account_id = $rt_hd_import_operation->post_exists( $accountname );

					if ( ! empty( $account_id ) && get_post_type( $account_id ) === rt_biz_get_company_post_type() ) {
						if ( isset( $transaction_id ) && $transaction_id > 0 ) {
							delete_post_meta( $account_id, '_transaction_id' );
							add_post_meta( $account_id, '_transaction_id', $transaction_id, true );
						}
					} else {
						$account_id = rt_biz_add_company( $accountname, $accountnote, $accountaddress, $accountcountry, $accountmeta );
						if ( isset( $transaction_id ) && $transaction_id > 0 ) {
							add_post_meta( $account_id, '_transaction_id', $transaction_id, true );
						}
					}
					$account = get_post( $account_id );

					rt_biz_connect_post_to_company( $post_type, $ticket_id, $account );

					// Update Index Table
					$attr_name = rt_biz_get_company_post_type();
					if ( ! empty( $attr_name ) ) {
						$where = array( 'post_id' => $ticket_id );
						$data  = array(
							$attr_name        => $account->ID,
							'date_update'     => current_time( 'mysql' ),
							'date_update_gmt' => gmdate( 'Y-m-d H:i:s' ),
							'user_updated_by' => get_current_user_id(),
						);
						$ticketModel->update_ticket( $data, $where );

						//	System Notification -- Accounts Updated
					}
				}
			}

			return $response;
		}
	}
}
