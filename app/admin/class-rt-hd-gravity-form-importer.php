<?php
/**
 * Don't load this file directly!
 */
if ( !defined( 'ABSPATH' ) ) {
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
 * @since rt-Helpdesk 0.1
 */
if ( !class_exists( 'Rt_HD_Gravity_Form_Importer' ) ) {

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
			add_action( 'init', array( $this, 'init_importer' ) );
		}

		/**
		 *  for init hook
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function init_importer() {
			global $rt_hd_attributes_relationship_model;
			$this->ticket_field = array(
				"title"        => array(
					'display_name' => 'Title',
					'slug'         => 'title',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'any'
				),
				"description"  => array(
					'display_name' => 'Description',
					'slug'         => 'description',
					'required'     => true,
					'multiple'     => false,
					'type'         => 'any'
				),
				"ticketmeta"   => array(
					'display_name' => 'Ticket Meta',
					'slug'         => 'ticketmeta',
					'required'     => false,
					'multiple'     => true,
					'type'         => 'key',
					'key_list'     => 'arr_ticketmeta_key'
				),
				"creationdate" => array(
					'display_name' => 'Create Date',
					'slug'         => 'creationdate',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'date'
				),
				"modifydate"   => array(
					'display_name' => 'Last Modify Date',
					'slug'         => 'modifydate',
					'required'     => false,
					'multiple'     => false,
					'type'         => 'date'
				),
				"assignedto"   => array(
					'display_name'  => 'Assigne To',
					'slug'          => 'assignedto',
					'required'      => false,
					'multiple'      => false,
					'type'          => 'defined',
					'definedsource' => 'arr_assignedto'
				),
				"ticketstatus" => array(
					'display_name'  => 'Status',
					'slug'          => 'ticketstatus',
					'required'      => true,
					'multiple'      => false,
					'type'          => 'defined',
					'definedsource' => 'arr_ticketstatus'
				),
			);

			$attributes = rthd_get_attributes( Rt_HD_Module::$post_type );
			foreach ( $attributes as $attr ) {
				$relation = $rt_hd_attributes_relationship_model->get_relations_by_post_type( Rt_HD_Module::$post_type, $attr->id );
				if ( !isset( $relation[0] ) ) {
					continue;
				}
				$relation                  = $relation[0];
				$attr_settings             = maybe_unserialize( $relation->settings );
				$slug                      = str_replace( array( '-' ), '_', $attr->attribute_name );
				$this->ticket_field[$slug] = array(
					'display_name' => $attr->attribute_label,
					'slug'         => $slug,
					'required'     => ( isset( $attr_settings['is_required'] ) && $attr_settings['is_required'] == 'yes' ) ? true : false,
				);
				switch ( $attr->attribute_store_as ) {
					case 'meta':
						$this->ticket_field[$slug]['multiple'] = false;
						$this->ticket_field[$slug]['type']     = $attr->attribute_render_type;
						break;
					case 'taxonomy':
						$this->ticket_field[$slug]['type']          = 'defined';
						$this->ticket_field[$slug]['definedsource'] = 'arr_' . $slug;
						if ( $attr->attribute_render_type == 'checklist' ) {
							$this->ticket_field[$slug]['multiple'] = true;
						} else {
							$this->ticket_field[$slug]['multiple'] = false;
						}
						break;
					default:
						do_action( 'rthd_gravity_form_fields_map' & $this->ticket_field[$slug], $attr );
						break;
				}
			}

			$module_settings = rthd_get_settings();
			if ( isset( $module_settings['attach_contacts'] ) && $module_settings['attach_contacts'] == 'yes' ) {
				$temp_arr           = array(
					"contactname"      => array(
						'display_name' => 'Contact Name',
						'slug'         => 'contactname',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'text'
					),
					"contactfirstname" => array(
						'display_name' => 'Contact First Name',
						'slug'         => 'contactfirstname',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'text'
					),
					"contacttitle"     => array(
						'display_name' => 'Contact Title',
						'slug'         => 'contacttitle',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'text'
					),
					"contactlastname"  => array(
						'display_name' => 'Contact Last Name',
						'slug'         => 'contactlastname',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'text'
					),
					"contactaddress"   => array(
						'display_name' => 'Contact Address',
						'slug'         => 'contactaddress',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'text'
					),
					"contactemail"     => array(
						'display_name' => 'Contact Email',
						'slug'         => 'contactemail',
						'required'     => true,
						'multiple'     => true,
						'type'         => 'email'
					),
					"contactskypeid"   => array(
						'display_name' => 'Contact Skype Id',
						'slug'         => 'contactskypeid',
						'required'     => false,
						'multiple'     => true,
						'type'         => 'text'
					),
					"contactphoneno"   => array(
						'display_name' => 'Contact Phone No',
						'slug'         => 'contactphoneno',
						'required'     => false,
						'multiple'     => true,
						'type'         => 'text'
					),
					"contactmeta"      => array(
						'display_name' => 'Contact Meta',
						'slug'         => 'contactmeta',
						'required'     => false,
						'multiple'     => true,
						'type'         => 'any'
					),
				);
				$this->ticket_field = array_merge( $this->ticket_field, $temp_arr );
			}

			if ( isset( $module_settings['attach_accounts'] ) && $module_settings['attach_accounts'] == 'yes' ) {
				$temp_arr           = array(
					"accountname"    => array(
						'display_name' => 'Account Name',
						'slug'         => 'accountname',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'any'
					),
					"accountaddress" => array(
						'display_name' => 'Account Address',
						'slug'         => 'accountaddress',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'any'
					),
					"accountcountry" => array(
						'display_name' => 'Account Country',
						'slug'         => 'accountcountry',
						'required'     => false,
						'multiple'     => false,
						'type'         => 'any'
					),
					"accountmeta"    => array(
						'display_name' => 'Account Meta',
						'slug'         => 'accountmeta',
						'required'     => false,
						'multiple'     => true,
						'type'         => 'any'
					),
				);
				$this->ticket_field = array_merge( $this->ticket_field, $temp_arr );
			}
		}

		/**
		 * import call from ajax
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function hd_importer_ajax_hooks() {
			add_action( 'wp_ajax_rthd_map_import', array( $this, 'rthd_map_import_callback' ) );
			add_action( 'wp_ajax_rthd_map_import_feauture', array( $this, 'rthd_map_import_feauture' ) );
			add_action( 'wp_ajax_rthd_import', array( $this, 'importer' ) );
			add_action( 'init', array( $this, 'install_gravity_form_hook' ) );
			add_action( 'wp_ajax_rthd_gravity_dummy_data', array( $this, 'get_random_gravity_data' ) );
			add_action( 'wp_ajax_rthd_defined_map_feild_value', array( $this, 'rthd_defined_map_field_value' ) );
		}

		/**
		 * install gravity form Hooks
		 */
		public function install_gravity_form_hook() {
			add_action( 'gform_entry_info', array( $this, 'gravity_form_lead_meta' ), 1, 2 );
			add_action( 'gform_entry_created', array( $this, 'rthd_auto_import' ), 1, 2 );
			add_filter( 'gform_pre_submission_filter', array( $this, 'rthd_add_custome_field' ), 1, 1 );
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
		public function rthd_add_custome_field( $data ) {

			global $rt_hd_gravity_fields_mapping_model;
			$form_id       = $data["id"];
			$form_mappings = $rt_hd_gravity_fields_mapping_model->get_mapping( $form_id );
			foreach ( $form_mappings as $mapping ) {
				if ( $mapping->enable == 'yes' ) {
					$found_history_field = false;
					if ( !$found_history_field && isset( $data['notification']['message'] ) ) {
						$data['notification']['message'] .= '<br />rtHelpdesk Ticket :<a href="--rtcamp_hd_link--">rtHelpdesk Link</a>';

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
		 * define map field value
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function rthd_defined_map_field_value() {
			$form_id = $_REQUEST["map_form_id"];
			if ( isset( $_REQUEST["mapSourceType"] ) && $_REQUEST["mapSourceType"] == "gravity" ) {
				$field_id  = intval( $_REQUEST["field_id"] );
				$tableName = RGFormsModel::get_lead_details_table_name();
				global $wpdb;
				$result = $wpdb->get_results( $wpdb->prepare( "select distinct value from $tableName where form_id= %d and field_number = %d ", $form_id, $field_id ) );
			} else {
				$field_id = $_REQUEST["field_id"];
				$csv      = new parseCSV();
				$csv->auto( $form_id );
				$result   = array();
				$field_id = str_replace( "-s-", " ", $field_id );
				foreach ( $csv->data as $cdt ) {
					$tmpArr = array( "value" => $cdt[$field_id] );
					if ( !in_array( $tmpArr, $result ) ) {
						$result[] = $tmpArr;
					}
					if ( count( $result ) > 15 ) {
						break;
					}
				}
			}
			header( 'Content-Type: application/json' );
			if ( count( $result ) < 15 ) {
				echo json_encode( $result );
			} else {
				echo json_encode( array() );
			}
			die( 0 );
		}


		/**
		 * gracity form lead meta
		 *
		 * @param $form_id
		 * @param $gr_lead
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function gravity_form_lead_meta( $form_id, $gr_lead ) {
			global $rt_hd_gravity_fields_mapping_model;
			$gr_lead_id = absint( $gr_lead["id"] );
			$mappings   = $rt_hd_gravity_fields_mapping_model->get_mapping( $form_id );
			foreach ( $mappings as $mapping ) {
				if ( $mapping->enable == 'yes' ) {
					global $rt_hd_module;
					$labels       = $rt_hd_module->labels;
					$post_type    = Rt_HD_Module::$post_type;
					$hd_ticket_id = intval( $this->gform_get_meta( $gr_lead_id, "helpdesk-" . $post_type . "-post-id" ) );
					if ( $hd_ticket_id ) {
						echo "Linked " . $rt_hd_module->name . " Post : <a href='" . get_edit_post_link( $hd_ticket_id ) . "' >" . get_the_title( $hd_ticket_id ) . "</a><br/>";
					}
				}
			}
		}

		/**
		 * Load handlebars template
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function load_handlebars_Templates() {
			?>
			<script id="map_table_content" type="text/x-handlebars-template">
				<table>
					{{#each data}}
					<tr>
						<td>{{this.value}}</td>
						<td>
							<select data-map-value='{{this.value}}'>
								{{#each ../mapData}}
								<option value="{{this.slug}}"
								{{{mapfieldnew this.display ../value}}}>{{this.display}}</option>
								{{/each}}
							</select>
						</td>
						{{/each}}
				</table>
			</script>
			<script id="defined_filed-option" type="text/x-handlebars-template">
				{{#each this}}
				<option value="{{this.slug}}">{{this.display}}</option>
				{{/each}}
			</script>
			<script id="key-type-option" type="text/x-handlebars-template">
				<option value="">--Select Key--</option>
				{{#each this}}
				<option value="{{this.meta_key}}">{{this.meta_key}}</option>
				{{/each}}
			</script>

		<?php
		}


		/**
		 * UI render
		 * @since rt-Helpdesk 0.1
		 */
		public function ui() {
			global $rt_hd_module;
			$post_type = Rt_HD_Module::$post_type;

			if ( !isset( $_REQUEST["type"] ) ) {
				$_REQUEST["type"] = "csv";
			}
			$this->load_handlebars_Templates();
			?>
			<ul class="subsubsub">
				<li>
					<a href="<?php echo admin_url( "edit.php?post_type=$post_type&page=rthd-settings&type=csv" ); ?>" <?php if ( $_REQUEST["type"] == "csv" ) {
						echo " class='current'";
					} ?>>CSV</a> |
				</li>
				<li>
					<a href="<?php echo admin_url( "edit.php?post_type=$post_type&page=rthd-settings&type=gravity" ); ?>" <?php if ( $_REQUEST["type"] == "gravity" ) {
						echo " class='current'";
					} ?> >Gravity</a></li>

			</ul>
			<?php
			if ( $_REQUEST["type"] == "gravity" ) {
				$formname = "";
				$forms    = $this->get_forms();
				if ( isset( $forms ) && !empty( $forms ) ) {
					$noFormflag = false;
					if ( isset( $_POST["mapSource"] ) && trim( $_POST["mapSource"] ) == '' ) {
						$class = ' class="form-invalid" ';
					} else {
						$class = '';
					}
					$form_select = '<select name="mapSource" id="mapSource" ' . $class . '>';
					$form_select .= '<option value="">' . __( 'Please select a form', RT_HD_TEXT_DOMAIN ) . '</option>';
					foreach ( $forms as $id => $form ) {
						if ( isset( $_POST["mapSource"] ) && intval( $_POST["mapSource"] ) == $id ) {
							$selected = "selected='selected'";
							$formname = $form;
						} else {
							$selected = "";
						}
						$form_select .= '<option value="' . $id . '"' . $selected . '>' . $form . '</option>';
					}
				} else {
					$form_select = '<strong>Please create some forms!</strong>';
					$noFormflag  = true;
				}
				?>
				<form action="" method="post">
					<table class="form-table">
						<tr>
							<th scope="row"><label
									for="mapSource"><?php _e( 'Select a Form:', RT_HD_TEXT_DOMAIN ); ?></label></th>
							<td>
								<?php echo $form_select; ?>
							</td>
						</tr>
						<?php if ( !$noFormflag ) : ?>
							<tr>
								<th scope="row"></th>
								<td><input type="button" id="map_submit" name="map_submit" value="Next"
								           class="button button-primary"/></td>
							</tr>
						<?php endif; ?>
					</table>
					<div id="mapping-form"></div>
				</form>
			<?php
			} else if ( $_REQUEST["type"] == "csv" ) {
				?>
				<form action="" method="post" enctype="multipart/form-data">
					<table class="form-table">
						<tr>
							<th scope="row"><label
									for="map_upload"><?php _e( 'Upload a data file:', RT_HD_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="file" name="map_upload" id="map_upload"/>
							</td>
						</tr>
						<tr>
							<td><input type="submit" name="map_submit" value="Upload" class="button"/></td>
						</tr>
					</table>
				</form>
			<?php
			}


		}


		/**
		 * Importer
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function importer() {
			global $rt_hd_module;
			$post_type = Rt_HD_Module::$post_type;


			$flag      = true;
			$post_type = Rt_HD_Module::$post_type;

			if ( $_REQUEST["type"] == "csv" ) {
				if ( isset( $_FILES['map_upload'] ) && $_FILES['map_upload']['error'] == 0 ) {
					if ( $_FILES['map_upload']['type'] != 'text/csv' ) {
						echo "<div class='error'>" . __( 'Please upload a CSV file only!', RT_HD_TEXT_DOMAIN ) . "</div>";

						return;
					}
					//Upload the file to 'Uploads' folder
					$file   = $_FILES['map_upload'];
					$upload = wp_handle_upload( $file, array( 'test_form' => false ) );
					if ( isset( $upload['error'] ) ) {
						?>
						<div id="map_message" class="error"><p><?php echo $upload['error']; ?>  </p></div>

						<?php
						return false;;
					}
					if ( !$flag ) {
						return;
					}
					$csv = new parseCSV();
					$csv->auto( $upload['file'] );
					$data = $csv->data[rand( 1, count( $csv->data ) - 1 )];
					?>
					<div id="map_message" class="updated map_message"><p>
							<?php _e( 'File uploaded:', RT_HD_TEXT_DOMAIN ); ?>
							<strong><?php echo $_FILES['map_upload']['name']; ?></strong>
							<?php _e( 'Total Rows:', RT_HD_TEXT_DOMAIN ); ?>
							<strong><?php echo count( $csv->data ); ?></strong></p>
					</div>

					<form method="post" action="" id="rtHelpdeskMappingForm" name="rtHelpdeskMappingForm">
					<input type="hidden" name="mapSource" id="mapSource" value="<?php echo $upload['file']; ?>"/>
					<input type="hidden" name="mapSourceType" id="mapSourceType"
					       value="<?php echo $_REQUEST['type']; ?>"/>
					<input type="hidden" name="mapEntryCount" id="mapEntryCount"
					       value="<?php echo count( $csv->data ) ?>"/>
					<table class="wp-list-table widefat fixed" id="map_mapping_table">
					<thead>
					<tr>
						<th scope="row"><?php _e( 'Column Name', RT_HD_TEXT_DOMAIN ); ?></th>
						<th scope="row"><?php _e( 'Field Name', RT_HD_TEXT_DOMAIN ); ?></th>
						<th scope="row"><?php _e( 'Default Value', RT_HD_TEXT_DOMAIN ); ?></th>
						<th scope="row"><a href="#dummyDataPrev"> << </a><?php _e( 'Sample', RT_HD_TEXT_DOMAIN ); ?> <a
								href="#dummyDataNext"> >> </a></th>
					</tr>
					</thead>
					<tbody style="background: white;">
					<?php foreach ( $csv->titles as $value ) { ?>
						<tr>
							<td><?php echo ucfirst( $value ); ?></td>

							<td>
								<?php
								$fieldname   = str_replace( " ", "-s-", $value );
								$form_fields = '<select data-og="' . $fieldname . '" name="field-' . $fieldname . '"  id="field-' . $fieldname . '" class="map_form_fields map_form_fixed_fields">';
								$form_fields .= '<option value="">Choose a field or Skip it</option>';
								foreach ( $this->ticket_field as $key => $lfield ) {
//                                                if ($lfield["type"] == 'defined')
//                                                    continue;
									$form_fields .= '<option value="' . $lfield["slug"] . '">' . ucfirst( $lfield["display_name"] ) . '</option>';
								}
								//$form_fields .= '<option value="ticketmeta">Other Field</option>';
								$form_fields .= '</select>';
								echo $form_fields;
								?>
							</td>
							<td></td>
							<td class='helpdesk-dummy-data'
							    data-field-name="<?php echo $value; ?>"><?php echo $data[$value]; ?></td>

						</tr>
					<?php } ?>
					</tbody>

				<?php
				} else {
					echo "<div class='error'><p>" . __( 'Please Select File', RT_HD_TEXT_DOMAIN ) . "</p></div>";

					return false;
				}
			} else {
				global $rt_hd_gravity_fields_mapping_model;
				$form_id    = intval( $_REQUEST['mapSource'] );
				$form_data  = RGFormsModel::get_form_meta( $form_id );
				$form_count = RGFormsModel::get_form_counts( $form_id );
				if ( !$form_data ) {
					?>
					<div id="map_message" class="error">Invalid Form</div>

					<?php
					return false;
				}
				if ( !$flag ) {
					return;
				}
				?>
				<div id="map_message" class="updated map_message">
					Form Selected : <strong><?php echo $form_data["title"]; ?></strong><br/>
					Total Entries: <strong><?php echo $form_count["total"]; ?></strong>
				</div>
				<form method="post" action="" id="rtHelpdeskMappingForm" name="rtHelpdeskMappingForm">
				<input type="hidden" name="mapSource" id="mapSource" value="<?php echo $form_id; ?>"/>
				<input type="hidden" name="mapSourceType" id="mapSourceType" value="<?php echo $_REQUEST['type']; ?>"/>
				<input type="hidden" name="mapEntryCount" id="mapEntryCount"
				       value="<?php echo $form_count["total"]; ?>"/>
				<table class="wp-list-table widefat fixed posts" >
				<thead>
				<tr>
					<th scope="row"><?php _e( 'Field Name', RT_HD_TEXT_DOMAIN ); ?></th>
					<th scope="row"><?php _e( 'Helpdesk Column Name', RT_HD_TEXT_DOMAIN ); ?></th>
					<th scope="row"><?php _e( 'Default Value', RT_HD_TEXT_DOMAIN ); ?></th>
					<th scope="row"><a href="#dummyDataPrev"> << </a><?php _e( 'Sample', RT_HD_TEXT_DOMAIN ); ?><a
							href="#dummyDataNext"> >> </a></th>
				</tr>
				</thead>
				<tbody style=" background: white; ">
				<?php
				$formdummydata = RGFormsModel::get_leads( $form_id, 0, 'ASC', '', 0, 1 );
				foreach ( $form_data['fields'] as &$field ) {
					?>
					<tr data-field-name="<?php echo $field['label']; ?>">
						<td><?php echo ucfirst( $field['label'] ); ?> <input type="hidden"
						                                                     value="<?php echo ucfirst( $field['type'] ); ?>"/>
						</td>
						<td>
							<?php

							$form_fields = '<select name="field-' . $field['id'] . '"  id="field-' . $field['id'] . '" class="map_form_fields map_form_fixed_fields">';
							$form_fields .= '<option value="">Choose a field or Skip it</option>';
							foreach ( $this->ticket_field as $key => $lfield ) {
//                                                    if (isset($lfield["type"]) &&  $lfield["type"]== 'defined')
//                                                        continue;
								$form_fields .= '<option value="' . $lfield["slug"] . '">' . ucfirst( $lfield["display_name"] ) . '</option>';
							}
							//                                                /$form_fields .= '<option value="ticketmeta">Other Field</option>';
							$form_fields .= '</select>';
							echo $form_fields;
							?>
						</td>
						<td></td>
						<td class='helpdesk-dummy-data'
						    data-field-name="<?php echo $field['id']; ?>"><?php echo ( isset( $formdummydata[0][$field['id']] ) ) ? $formdummydata[0][$field['id']] : ''; ?></td>
					</tr>
				<?php
				}
				?>
				</tbody>
			<?php } ?>
			<tfoot>
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
						$form_fields .= '<option value="' . $lfield["slug"] . '">' . $lfield["name"] . '</option>';
						$arr_ticketstatus[] = array( "slug" => $lfield["slug"], "display" => $lfield["name"] );
					}
					$form_fields .= '</select>';
					echo $form_fields;
					echo "<script> var arr_ticketstatus=" . json_encode( $arr_ticketstatus ) . "; </script>"
					?>
				</td>
				<td></td>
				<td>
				</td>
			</tr>

			<tr>
				<td>
					Assigned To
				</td>
				<td>
					<?php
					global $wpdb;
					$results          = Rt_HD_Utils::get_hd_rtcamp_user();
					$meta_key_results = $wpdb->get_results( " select distinct meta_key from $wpdb->postmeta inner join $wpdb->posts on post_id=ID
								 and post_type='" . $post_type . "' and  not meta_key like '\_%' order by meta_key" );


					$arr_assignedto = array();
					if ( !empty( $results ) ) {
						// Name is your custom field key
						echo "<select name='assignedto'>";
						// loop trough each author
						foreach ( $results as $author ) {
							echo '<option value=' . $author->ID . ' >' . $author->display_name . '</option>';
							$arr_assignedto[] = array(
								"slug"    => $author->ID,
								"display" => $author->display_name . " " . $author->user_email
							);
						}

						echo "</select>";
					} else {
						echo 'No authors found';
					}
					echo "<script> var arr_assignedto=" . json_encode( $arr_assignedto ) . "; </script>";
					echo "<script> var arr_ticketmeta_key=" . json_encode( $meta_key_results ) . "; </script>";
					?>
				</td>
				<td></td>
				<td>
				</td>
			</tr>

			<?php
			$attributes = rthd_get_attributes( Rt_HD_Module::$post_type );
			foreach ( $attributes as $attr ) {
				?>
				<tr>
					<td><?php echo $attr->attribute_label; ?></td>
					<td>
						<?php
						switch ( $attr->attribute_store_as ) {
							case 'taxonomy':
								$attr_terms = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
										"hide_empty" => false,
										"orderby"    => $attr->attribute_orderby,
										"order"      => "ASC"
									) );
								$term_array = array();
								if ( is_array( $attr_terms ) && count( $attr_terms ) > 0 ) {
									echo '<select name="' . str_replace( array( '-' ), '_', $attr->attribute_name ) . '">';
									echo '<option value="" >Select ' . $attr->attribute_label . '</option>';
									foreach ( $attr_terms as $term ) {
										echo '<option value="' . $term->slug . '" >' . $term->name . '</option>';
										$term_array[] = array( "slug" => $term->slug, "display" => $term->name );
									}
									echo '</select>';
								} else {
									echo 'No ' . $attr->attribute_label . ' found';
								}
								echo '<script> var arr_' . str_replace( array( '-' ), '_', $attr->attribute_name ) . '=' . json_encode( $term_array ) . '; </script>';
								break;
							case 'meta':
								echo '<input type="text" name="' . str_replace( array( '-' ), '_', $attr->attribute_name ) . '" />';
								break;
							default:
								do_action( 'rthd_gravity_form_fields_map_default_value' & $this->ticket_field[$attr->attribute_name], $attr );
								break;
						}
						?>
					</td>
					<td></td>
					<td></td>
				</tr>
			<?php
			}
			?>

			<tr>
				<td>
					Date Format
				</td>
				<td>
					<input type="text" value="" name="dateformat"/>
					<a href='http://www.php.net/manual/en/datetime.createfromformat.php' target='_blank'>Refrence</a>
				</td>
				<td></td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					Title Prefix
				</td>
				<td>
					<input type="text" value="" name="titleprefix"/>
				</td>
				<td></td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					Title Suffix
				</td>
				<td>
					<input type="text" value="" name="titlesuffix"/>
				</td>
				<td></td>
				<td>
				</td>
			</tr>

			<tr>
				<td>
					<?php
					$form_fields = '<select name="otherfield0" class="other-field">';
					$form_fields .= '<option value="">Select</option>';
					foreach ( $this->ticket_field as $lfield ) {
						if ( isset( $lfield["type"] ) && $lfield["type"] == 'defined' ) {
							continue;
						}
						$form_fields .= '<option value="' . $lfield["slug"] . '">' . ucfirst( $lfield["display_name"] ) . '</option>';
					}
					$form_fields .= '</select>';
					echo $form_fields;
					?>

				</td>
				<td>
					<input type="text" value="" id="otherfield0"/>
				</td>
				<td></td>
				<td>
				</td>
			</tr>
			<tr>
				<td>

				</td>
				<td>
					<label><input type="checkbox" value="" id="forceimport"/>Also Import previously Imported
						Entry(Duplicate)</label>
				</td>
				<td>

				</td>
				<td>
				</td>
			</tr>
			</tfoot>
		</table>
			<script>
				var transaction_id =<?php echo time(); ?>;
				var arr_map_fields =<?php echo json_encode($this->ticket_field); ?>;
				<?php if ($_REQUEST["type"] == "gravity") { ?>
				var arr_lead_id =
				<?php $this->get_all_gravity_lead($form_id); ?>
				<?php
			} else {
				$jsonArray = array();
				$rCount = 0;
				foreach ($csv->data as $cdata) {
					$jsonArray[] = array("id" => $rCount++);
				}
				?>
				var arr_lead_id =
				<?php echo json_encode($jsonArray); ?>
				<?php } ?>
			</script>
			<input type="button" name="map_mapping_import" id="map_mapping_import" value="Import"
			       class="button button-primary"/>
		</form>
			<div id='startImporting'>
				<h2> <?php _e( sprintf( 'Importing %s into Helpdesk...', isset( $formname ) ? $formname : '' ), RT_HD_TEXT_DOMAIN ); ?></h2>

				<div id="progressbar"></div>
				<div class="myupdate">
					<p> <?php _e( 'Successfully imported :', RT_HD_TEXT_DOMAIN ); ?> <span
							id='sucessfullyImported'>0</span></p>
				</div>
				<div class="myerror">
					<p> <?php _e( 'Failed to import :', RT_HD_TEXT_DOMAIN ); ?> <span id='failImported'>0</span></p>
				</div>
				<div class="importloading">

				</div>
				<div class="sucessmessage">
					<?php if ($_REQUEST["type"] == "gravity") {
					_e( 'Would u like to import future entries automatically?', RT_HD_TEXT_DOMAIN );?> &nbsp;
					<input type='button' id='futureYes' value='Yes' class="button button-primary"/>&nbsp;<input
						type='button' id='futureNo' value='No' class="button "/></div>
				<?php } else { ?>
					<h3><?php _e( 'Done !', RT_HD_TEXT_DOMAIN ); ?></h3>
					<span id="extra-data-importer"></span>

				<?php } ?>

			</div>
			<?php    die();

		}


		/**
		 * map import feature
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function rthd_map_import_feauture() {
			global $rt_hd_gravity_fields_mapping_model;

			$response = array();
			header( 'Content-Type: application/json' );
			if ( !isset( $_REQUEST["map_form_id"] ) ) {
				$response['status'] = false;
			} else {
				$form_id  = $_REQUEST["map_form_id"];
				$map_data = maybe_serialize( $_REQUEST["map_data"] );

				$mapping = $rt_hd_gravity_fields_mapping_model->get_mapping( $form_id );
				if ( !empty( $mapping ) ) {
					$data  = array(
						'mapping' => $map_data,
					);
					$where = array(
						'form_id' => $form_id,
					);
					$rt_hd_gravity_fields_mapping_model->update_mapping( $data, $where );
				} else {
					$data = array(
						'form_id' => $form_id,
						'mapping' => $map_data,
					);
					$rt_hd_gravity_fields_mapping_model->add_mapping( $data );
				}
				$response["status"] = true;
			}
			echo json_encode( $response );
			die( 0 );
		}


		/**
		 * process import
		 *
		 * @param $map_data
		 * @param $form_id
		 * @param $gravity_lead_id
		 * @param $type
		 * @param bool $forceImport
		 * @param bool $autoDieFlag
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function process_import( $map_data, $form_id, $gravity_lead_id, $type, $forceImport = false, $autoDieFlag = true ) {
			//** remove woocommerce hooks **//

			remove_action( "create_term", 'woocommerce_create_term', 5, 3 );
			remove_action( "delete_term", 'woocommerce_delete_term', 5, 3 );

			//** END of remove woocommerce hooks **//

			$module_settings = rthd_get_settings();

			$response    = array();
			$autoDieFlag = !$autoDieFlag;

			if ( !$autoDieFlag ) {
				header( 'Content-Type: application/json' );
			}
			if ( $type == "gravity" ) {
				if ( is_array( $gravity_lead_id ) ) {
					$gravity_lead_id = $gravity_lead_id[0];
				}
				$response[0]["lead_id"] = $gravity_lead_id;
				if ( !$forceImport ) {
					$alreadyImported = $this->gform_get_meta( $gravity_lead_id, "import-to-helpdesk" );
					if ( $alreadyImported ) {
						if ( $autoDieFlag ) {
							return true;
						}
						$response[0]["status"] = true;
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
							if ( !( strpos( $meta["fieldName"], "field-" ) === false ) ) {
								$field_id = intval( str_replace( "field-", "", $meta["fieldName"] ) );
								if ( $field_id < 1 ) {
									$field_id = str_replace( "field-", "", $meta["fieldName"] );
								}
								$f_field = RGFormsModel::get_field( $form, intval( str_replace( "field-", "", $meta["fieldName"] ) ) );
								$tValue  = "";
								if ( isset( $lead_data[$field_id] ) ) {
									$tValue = $lead_data[$field_id];
								} else {
									if ( isset( $f_field["inputs"] ) && !empty( $f_field["inputs"] ) ) {
										$sep = "";
										foreach ( $f_field["inputs"] as $input ) {
											$tValue .= $sep . $lead_data[strval( $input["id"] )];
											$sep = " ";
										}
									}
								}

								if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "defined" ) {
									if ( trim( $meta["fieldName"] ) == "" ) {
										$tValue = $meta["defaultValue"];
									} else if ( isset( $meta["mappingData"][$tValue] ) ) {
										$tValue = $meta["mappingData"][$tValue];
									} else {
										$tValue = $meta["defaultValue"];
									}
								} else {
									$f_field = RGFormsModel::get_field( $form, $field_id );
									$tValue  = $lead_data[$field_id];
									if ( trim( $tValue ) == "" ) {
										$tValue = $meta["defaultValue"];
									}
								}
								$tKey = $f_field['label'];
								if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "type" ) {
									if ( isset( $meta["keyname"] ) && trim( $meta["keyname"] ) != "" ) {
										$tKey = $meta["keyname"];
									}
								}
								$meta = array( "key" => ucfirst( $tKey ), "value" => $tValue );
							} else {
								if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "defined" ) {
									if ( trim( $meta["fieldName"] ) == "" ) {
										$tfield = $meta["defaultValue"];
									} else if ( isset( $meta["mappingData"][$tValue] ) ) {
										$tfield = $meta["mappingData"][$tValue];
									} else {
										$tfield = $meta["defaultValue"];
									}
								} else {
									$tfield = $meta["fieldName"];
								}
								$tKey = $f_field['label'];
								if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "type" ) {
									if ( isset( $meta["keyname"] ) && trim( $meta["keyname"] ) != "" ) {
										$tKey = $meta["keyname"];
									}
								}

								$meta = array( "key" => ucfirst( $tKey ), "value" => $meta["fieldName"] );
							}
						}
					} else {
						if ( !( strpos( $field["fieldName"], "field-" ) === false ) ) {
							$f_field = RGFormsModel::get_field( $form, intval( str_replace( "field-", "", $field["fieldName"] ) ) );
							$tfield  = "";
							if ( isset( $lead_data[intval( str_replace( "field-", "", $field["fieldName"] ) )] ) ) {
								$tfield = $lead_data[intval( str_replace( "field-", "", $field["fieldName"] ) )];
							} else {
								if ( isset( $f_field["inputs"] ) && !empty( $f_field["inputs"] ) ) {
									$sep = "";
									foreach ( $f_field["inputs"] as $input ) {
										$tfield .= $sep . $lead_data[strval( $input["id"] )];
										$sep = " ";
									}
								}
							}

							if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "defined" ) {
								if ( trim( $tfield ) == "" ) {
									$tfield = $field["defaultValue"];
								} else if ( isset( $field["mappingData"][$tfield] ) ) {
									$tfield = $field["mappingData"][$tfield];
								} else {
									$tfield = $field["defaultValue"];
								}
							}
							if ( trim( $tfield ) == "" ) {
								$tfield = $field["defaultValue"];
							}
							$field = $tfield;
						} else {
							$field = $field["fieldName"];
						}
					}
				}

				if ( !isset( $map_data["creationdate"] ) ) {
					$map_data["creationdate"] = $lead_data["date_created"];
				}

				if ( $autoDieFlag && isset( $module_settings['attach_contacts'] ) && $module_settings['attach_contacts'] == 'yes' ) {
					$map_data["titleprefix"] = "";
					$map_data["titlesuffix"] = "";
					if ( !isset( $map_data["contactname"] ) ) {
						$contactname = "";
					} else {
						$contactname = $map_data["contactname"];
					}
					if ( isset( $map_data["contactfirstname"] ) ) {
						$contactname = $map_data["contactfirstname"] . " " . $contactname;
					}

					if ( isset( $map_data["contactlastname"] ) ) {
						$contactname .= " " . $map_data["contactlastname"];
					}

					if ( isset( $map_data["contacttitle"] ) ) {
						$contactname = $map_data["contacttitle"] . " " . $contactname;
					}
					$siteurl = "";
					if ( isset( $map_data["ticketmeta"] ) ) {
						$ticketmeta = $map_data["ticketmeta"];
						foreach ( $ticketmeta as $ticketm ) {
							if ( strtolower( $ticketm["key"] ) == 'site url' ) {
								$siteurl = " -" . $ticketm["value"];
								break;
							}
						}
					}
					$map_data["title"] = "Enquiry From " . $contactname;
				}

				$response[0] = $this->create_tickets_from_map_data( $map_data, $gravity_lead_id, $type );
				if ( $autoDieFlag ) {
					return $response[0]["status"];
				}
				//ob_end_clean();
				echo json_encode( $response );
				die( 0 );
			} else if ( $type == "csv" ) {
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
									if ( !( strpos( $meta["fieldName"], "field-" ) === false ) ) {
										$field_id = str_replace( "-s-", " ", str_replace( "field-", "", $meta["fieldName"] ) );
										if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "defined" ) {
											if ( array_key_exists( $field_id, $csv->data[$row_index] ) ) {
												$tfield = $csv->data[$row_index][$field_id];
												if ( trim( $tfield ) == "" ) {
													$tfield = $meta["defaultValue"];
												} else if ( isset( $meta["mappingData"][$tfield] ) ) {
													$tfield = $meta["mappingData"][$tfield];
												} else {
													$tfield = $meta["defaultValue"];
												}
											} else {
												$tfield = $meta["defaultValue"];
											}
											$tKey = $field_id;
											if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "key" ) {
												if ( isset( $meta["keyname"] ) && trim( $meta["keyname"] ) != "" ) {
													$tKey = $meta["keyname"];
												}
											}
											$meta = array( "key" => ucfirst( $tKey ), "value" => $tfield );
										} else {
											if ( array_key_exists( $field_id, $csv->data[$row_index] ) ) {
												$tKey = $field_id;
												if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "key" ) {
													if ( isset( $meta["keyname"] ) && trim( $meta["keyname"] ) != "" ) {
														$tKey = $meta["keyname"];
													}
												}

												if ( $csv->data[$row_index][$field_id] == "" ) {
													$meta = array(
														"key"   => ucfirst( $tKey ),
														"value" => $meta["defaultValue"]
													);
												} else {
													$meta = array(
														"key"   => ucfirst( $tKey ),
														"value" => $csv->data[$row_index][$field_id]
													);
												}
											} else {
												$meta = array(
													"key"   => ucfirst( $tKey ),
													"value" => $meta["defaultValue"]
												);
											}
										}
									} else {
										$tKey = $field_id;
										if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "key" ) {
											if ( isset( $meta["keyname"] ) && trim( $meta["keyname"] ) != "" ) {
												$tKey = $meta["keyname"];
											}
										}
										$meta = array( "key" => ucfirst( $tKey ), "value" => $meta["fieldName"] );
									}
								}
							} else {
								if ( !( strpos( $field["fieldName"], "field-" ) === false ) ) {
									$field_id = str_replace( "-s-", " ", str_replace( "field-", "", $field["fieldName"] ) );
									if ( array_key_exists( $field_id, $csv->data[$row_index] ) ) {
										$tfield = $csv->data[$row_index][$field_id];
										if ( isset( $this->ticket_field[$key]["type"] ) && $this->ticket_field[$key]["type"] == "defined" ) {
											if ( trim( $tfield ) == "" ) {
												$tfield = $field["defaultValue"];
											} else if ( isset( $field["mappingData"][$tfield] ) ) {
												$tfield = $field["mappingData"][$tfield];
											} else {
												$tfield = $field["defaultValue"];
											}
										}
										if ( trim( $tfield ) == "" ) {
											$tfield = $field["defaultValue"];
										}
										$field = $tfield;
									} else {
										$tfield = $field["defaultValue"];
									}
									$tmpArrayKey[] = $field_id;
								} else {
									$field = $field["fieldName"];
								}
							}
						}

						$response[] = $this->create_tickets_from_map_data( $map_data, $row_index, $type );
					}
					unset( $csv );
					$response[0]["startmemory"] = $start_memory;
					$response[0]["endmemory"]   = memory_get_usage( true );

					//ob_end_clean();
					echo json_encode( $response );
					die( 0 );
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
			extract( $map_data, EXTR_OVERWRITE );

			global $transaction_id;
			$module_settings = rthd_get_settings();
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
			if ( !isset( $title ) ) {
				$title = "";
			}

			if ( isset( $module_settings['attach_contacts'] ) && $module_settings['attach_contacts'] == 'yes' ) {
				if ( !isset( $contactname ) ) {
					$contactname = "";
				}
				if ( isset( $contactfirstname ) ) {
					$contactname = $contactfirstname . " " . $contactname;
				}

				if ( isset( $contactlastname ) ) {
					$contactname .= " " . $contactlastname;
				}

				if ( isset( $contacttitle ) ) {
					$contactname = $contacttitle . " " . $contactname;
				}

				foreach ( $contactemail as $email ) {
					if ( empty( $fromemail ) ) {
						$fromemail["address"] = $email["value"];
						if ( isset( $contactname ) && trim( $contactname ) != '' ) {
							$fromemail["name"] = $contactname;
						}
					}
					$allemail[] = array( "address" => $email["value"], "name" => $contactname );
				}
			}

			if ( !isset( $titleprefix ) ) {
				$titleprefix = "";
			}
			if ( !isset( $titlesuffix ) ) {
				$titlesuffix = "";
			}
			if ( $title == "" ) {
				if ( $type == "csv" ) {
					$title = "CSV Entry " . $gravity_lead_id;
				} else {
					$title = "Gravity Entry " . $gravity_lead_id;
				}
			}

			$title = $titleprefix . " " . $title . " " . $titlesuffix;
			$title = trim( $title );
			global $rt_hd_tickets;

			$ticket_id           = $rt_hd_tickets->process_email_to_ticket(
				$title,
				$description,
				$fromemail,
				$creationdate,
				$allemail,
				array(),
				$description,
				false,
				$assignedto
			);
			$response            = array();
			$response["lead_id"] = $gravity_lead_id;
			if ( !$ticket_id ) {
				$response["status"] = false;
			} else {
				if ( $type == "gravity" ) {
					$this->gform_update_meta( $gravity_lead_id, "import-to-helpdesk", 1 );
					$this->gform_update_meta( $gravity_lead_id, "helpdesk-" . $post_type . "-post-id", $ticket_id );
					if ( isset( $transaction_id ) && $transaction_id > 0 ) {
						$this->gform_update_meta( $gravity_lead_id, "_transaction_id", $transaction_id );
					}
				}
				$response["status"] = true;
				update_post_meta( $ticket_id, "_rtbiz_helpdesk_gravity_form_all_data", $_REQUEST );
				if ( isset( $ticketmeta ) ) {
					foreach ( $ticketmeta as $ticketm ) {
						update_post_meta( $ticket_id, $ticketm["key"], $ticketm["value"] );
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

				if ( isset( $ticketstatus ) && $ticketstatus != "new" ) {
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
									if ( isset( ${$slug} ) && !empty( ${$slug} ) ) {
										$term_id = term_exists( ${$slug}, $tax_name );
										if ( !$term_id ) {
											$term    = wp_insert_term( ${$slug}, $tax_name, array(
													'description' => ${$slug},
													'slug'        => strtolower( ${$slug} )
												) );
											$term_id = $term["term_id"];
											if ( isset( $transaction_id ) && $transaction_id > 0 ) {
												add_term_meta( $term_id, "_transaction_id", $transaction_id, true );
											}
										} else {
											$term_id = $term_id["term_id"];
											if ( isset( $transaction_id ) && $transaction_id > 0 ) {
												delete_term_meta( $term_id, "_transaction_id" );
												add_term_meta( $term_id, "_transaction_id", $transaction_id, true );
											}
										}
										$term     = get_term_by( "id", $term_id, $tax_name );
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
									if ( isset( ${$slug} ) && !empty( ${$slug} ) && is_array( ${$slug} ) ) {
										$termslug = array();
										foreach ( ${$slug} as $attr_term ) {
											if ( $attr_term["value"] == "" ) {
												continue;
											}
											$term_id = term_exists( $attr_term["value"], $tax_name );
											if ( !$term_id ) {
												$term    = wp_insert_term( $attr_term["value"], $tax_name, array(
														'description' => $attr_term["value"],
														'slug'        => strtolower( $attr_term["value"] )
													) );
												$term_id = $term["term_id"];
												if ( isset( $transaction_id ) && $transaction_id > 0 ) {
													add_term_meta( $term_id, "_transaction_id", $transaction_id, true );
												}
											} else {
												$term_id = $term_id["term_id"];
												if ( isset( $transaction_id ) && $transaction_id > 0 ) {
													delete_term_meta( $term_id, "_transaction_id" );
													add_term_meta( $term_id, "_transaction_id", $transaction_id, true );
												}
											}
											$term       = get_term_by( "id", $term_id, $tax_name );
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
										if ( isset( $dateformat ) && trim( $dateformat ) != "" ) {
											$dr = date_create_from_format( $dateformat, ${$slug} );
											if ( !$dr ) {
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

				if ( isset( $module_settings['attach_contacts'] ) && $module_settings['attach_contacts'] == 'yes' ) {

					$contact = rt_biz_get_person_by_email( $fromemail['address'] );
					if ( !empty( $contact ) && isset( $contact[0] ) ) {
						$contact = $contact[0];
						if ( isset( $contactskypeid ) && !empty( $contactskypeid ) ) {
							foreach ( $contactskypeid as $cSkype ) {
								rt_biz_add_entity_meta( $contact->ID, 'contact_skype_id', $cSkype['value'] );
							}
						}
						if ( isset( $contactphoneno ) && !empty( $contactphoneno ) ) {
							foreach ( $contactphoneno as $cphone ) {
								rt_biz_add_entity_meta( $contact->ID, 'contact_phone', $cphone['value'] );
							}
						}
						if ( isset( $contactaddress ) && !empty( $contactaddress ) ) {
							rt_biz_add_entity_meta( $contact->ID, 'contact_address', $contactaddress );
						}
						if ( isset( $contactmeta ) && !empty( $contactmeta ) ) {
							foreach ( $contactmeta as $cmeta ) {
								rt_biz_add_entity_meta( $contact->ID, $cmeta["key"], $cmeta['value'] );
							}
						}
					}

					// Contact will be linked with the ticket later while creating the ticket.
				}

				if ( isset( $module_settings['attach_accounts'] ) && $module_settings['attach_accounts'] == 'yes' ) {
					if ( isset( $accountname ) && trim( $accountname ) != '' ) {
						if ( !isset( $accountaddress ) ) {
							$accountaddress = '';
						}
						if ( !isset( $accountcountry ) ) {
							$accountcountry = '';
						}
						if ( !isset( $accountnote ) ) {
							$accountnote = '';
						}
						if ( !isset( $accountmeta ) ) {
							$accountmeta = array();
						}
						$account_id = $rt_hd_tickets->post_exists( $accountname );

						if ( !empty( $account_id ) && get_post_type( $account_id ) === rt_biz_get_organization_post_type() ) {
							if ( isset( $transaction_id ) && $transaction_id > 0 ) {
								delete_post_meta( $account_id, "_transaction_id" );
								add_post_meta( $account_id, "_transaction_id", $transaction_id, true );
							}
						} else {
							$account_id = rt_biz_add_organization(
								$accountname,
								$accountnote,
								$accountaddress,
								$accountcountry,
								$accountmeta
							);
							if ( isset( $transaction_id ) && $transaction_id > 0 ) {
								add_post_meta( $account_id, "_transaction_id", $transaction_id, true );
							}
						}
						$account = get_post( $account_id );

						rt_biz_connect_post_to_organization( $post_type, $ticket_id, $account );

						// Update Index Table
						$attr_name = rt_biz_get_organization_post_type();
						if ( !empty( $attr_name ) ) {
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
			}

			return $response;
		}


		/**
		 * map import call back
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function rthd_map_import_callback() {
			if ( !isset( $_REQUEST["gravity_lead_id"] ) ) {
				echo json_encode( array( array( "status" => false ) ) );
				die( 0 );
			}
			global $bulkimport;
			$bulkimport         = true;
			$map_index_lead_id  = $_REQUEST["gravity_lead_id"];
			$map_source_form_id = $_REQUEST["map_form_id"];
			$map_data           = $_REQUEST["map_data"];
			if ( isset( $_REQUEST["forceimport"] ) && $_REQUEST["forceimport"] == "false" ) {
				$forceImport = false;
			} else {
				$forceImport = true;
			}
			global $transaction_id;
			$transaction_id = $_REQUEST["trans_id"];
			$type           = $_REQUEST["mapSourceType"];
			$this->process_import( $map_data, $map_source_form_id, $map_index_lead_id, $type, $forceImport );
		}

		/**
		 * auto import
		 *
		 * @param $lead
		 * @param $form
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function rthd_auto_import( $lead, $form ) {
			//gform_after_submission
			global $rt_hd_gravity_fields_mapping_model;
			$form_id       = $form["id"];
			$form_mappings = $rt_hd_gravity_fields_mapping_model->get_mapping( $form_id );
			foreach ( $form_mappings as $fm ) {
				$map_data = maybe_unserialize( $fm->mapping );
				if ( !empty( $map_data ) && $fm->enable == 'yes' ) {
					global $gravity_auto_import;
					$gravity_auto_import = true;
					$forceImport         = false;
					$gravity_lead_id     = $lead["id"];
					$type                = "gravity";
					$this->process_import( $map_data, $form_id, $gravity_lead_id, $type, $forceImport, false );
				}
			}
		}

		/**
		 * get forms
		 *
		 * @return bool
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_forms() {
			if ( !class_exists( "RGForms" ) ) {
				return false;
			}
			$active = RGForms::get( "active" ) == "" ? null : RGForms::get( "active" );
			$forms  = RGFormsModel::get_forms( $active, "title" );
			if ( isset( $forms ) && !empty( $forms ) ) {
				foreach ( $forms as $form ) {
					$return[$form->id] = $form->title;
				}

				return $return;
			} else {
				return false;
			}
		}

		/**
		 *
		 * get all gravity lead
		 *
		 * @param $form_id
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_all_gravity_lead( $form_id ) {
			$gravityLeadTableName = RGFormsModel::get_lead_table_name();
			global $wpdb;
			$sql = $wpdb->prepare( "SELECT id FROM $gravityLeadTableName WHERE form_id=%d AND status='active'", $form_id );
			echo json_encode( $wpdb->get_results( $sql, ARRAY_A ) );
		}

		/**
		 * function to handle lead meta
		 *
		 * @param $entry_id
		 * @param $meta_key
		 *
		 * @return bool|mixed
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function gform_get_meta( $entry_id, $meta_key ) {
			global $wpdb, $_gform_lead_meta;

			//get from cache if available
			$cache_key = $entry_id . "_" . $meta_key;
			if ( array_key_exists( $cache_key, $_gform_lead_meta ) ) {
				return $_gform_lead_meta[$cache_key];
			}

			$table_name                   = RGFormsModel::get_lead_meta_table_name();
			$value                        = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$table_name} WHERE lead_id=%d AND meta_key=%s", $entry_id, $meta_key ) );
			$meta_value                   = $value == null ? false : maybe_unserialize( $value );
			$_gform_lead_meta[$cache_key] = $meta_value;

			return $meta_value;
		}

		/**
		 * gform update meta
		 *
		 * @param $entry_id
		 * @param $meta_key
		 * @param $meta_value
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function gform_update_meta( $entry_id, $meta_key, $meta_value ) {
			global $wpdb, $_gform_lead_meta;
			$table_name = RGFormsModel::get_lead_meta_table_name();

			$meta_value  = maybe_serialize( $meta_value );
			$meta_exists = gform_get_meta( $entry_id, $meta_key ) !== false;
			if ( $meta_exists ) {
				$wpdb->update( $table_name, array( "meta_value" => $meta_value ), array(
						"lead_id"  => $entry_id,
						"meta_key" => $meta_key
					), array( "%s" ), array( "%d", "%s" ) );
			} else {
				$wpdb->insert( $table_name, array(
						"lead_id"    => $entry_id,
						"meta_key"   => $meta_key,
						"meta_value" => $meta_value
					), array( "%d", "%s", "%s" ) );
			}

			//updates cache
			$cache_key = $entry_id . "_" . $meta_key;
			if ( array_key_exists( $cache_key, $_gform_lead_meta ) ) {
				$_gform_lead_meta[$cache_key] = maybe_unserialize( $meta_value );
			}
		}

		/**
		 * gform delete meta
		 *
		 * @param $entry_id
		 * @param string $meta_key
		 *
		 * @since rt-Helpdesk 0.1
		 *
		 */
		function gform_delete_meta( $entry_id, $meta_key = "" ) {
			global $wpdb, $_gform_lead_meta;
			$table_name  = RGFormsModel::get_lead_meta_table_name();
			$meta_filter = empty( $meta_key ) ? "" : $wpdb->prepare( "AND meta_key=%s", $meta_key );

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE lead_id=%d {$meta_filter}", $entry_id ) );

			//clears cache.
			$_gform_lead_meta = array();
		}

		/**
		 * get random gravity data
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function get_random_gravity_data() {
			//mapSourceType

			header( 'Content-Type: application/json' );
			$form_id = $_REQUEST["map_form_id"];
			if ( isset( $_REQUEST["mapSourceType"] ) && $_REQUEST["mapSourceType"] == "gravity" ) {
				$lead_id       = intval( $_REQUEST["dummy_lead_id"] );
				$formdummydata = RGFormsModel::get_lead( $lead_id );

				foreach ( $formdummydata as $key => $val ) {
					if ( !( strpos( strval( $key ), "." ) === false ) ) {
						$pieces = explode( ".", $key );

						if ( !isset( $formdummydata[intval( $pieces[0] )] ) ) {
							$formdummydata[intval( $pieces[0] )] = "";
						}
						$formdummydata[intval( $pieces[0] )] .= $val . " ";
					}
				}
				echo json_encode( $formdummydata );
			} else {
				$lead_id = intval( $_REQUEST["dummy_lead_id"] );
				$csv     = new parseCSV();
				$csv->auto( $form_id );
				echo json_encode( $csv->data[$lead_id] );
			}
			die( 0 );
		}
	}
}