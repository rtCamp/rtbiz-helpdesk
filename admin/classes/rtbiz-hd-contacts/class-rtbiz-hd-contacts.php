<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Contacts' ) ) {

	/**
	 * Class Rt_HD_Contacts
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rtbiz_HD_Contacts {

		/**
		 * @var string
		 */
		public $user_id = 'contact_user_id';

		public function __construct() {
			Rtbiz_HD::$loader->add_filter( 'rtbiz_entity_columns', $this, 'contacts_columns', 10, 2 );
			Rtbiz_HD::$loader->add_filter( 'rtbiz_entity_rearrange_columns', $this, 'contacts_rearrange_columns', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'rtbiz_entity_manage_columns', $this, 'manage_contacts_columns', 10, 3 );

			Rtbiz_HD::$loader->add_action( 'bulk_edit_custom_box', $this, 'contact_quick_action', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'quick_edit_custom_box', $this, 'contact_quick_action', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'save_post', $this, 'save_helpdesk_role', 20, 2 );

			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_search_contact', $this, 'ajax_contact_autocomplete' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_get_term_meta', $this, 'ajax_get_taxonomy_meta' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_get_account_contacts', $this, 'ajax_get_account_contacts' );
			Rtbiz_HD::$loader->add_action( 'wp_ajax_rtbiz_hd_add_contact', $this, 'ajax_add_new_contact' );

			Rtbiz_HD::$loader->add_filter( 'rtbiz_contact_meta_fields', $this, 'add_hd_additional_details' );

			Rtbiz_HD::$loader->add_action( 'rtbiz_after_delete_staff_acl_remove-' . RTBIZ_HD_TEXT_DOMAIN, $this, 'before_delete_staff', 10, 3 );

			//update contact lable for staff and customer
			Rtbiz_HD::$loader->add_filter( 'rtbiz_contact_labels', $this, 'change_contact_lablels' );

			//update contact page module wise

			Rtbiz_HD::$loader->add_action( 'rtbiz_entity_meta_boxes', $this, 'contact_custom_metabox' );
			Rtbiz_HD::$loader->add_filter( 'get_edit_post_link', $this, 'edit_contact_link', 10, 2 );
			Rtbiz_HD::$loader->add_action( 'add_meta_boxes_'. rtbiz_get_contact_post_type(), $this, 'metabox_rearrange', 20 );
			Rtbiz_HD::$loader->add_filter( 'redirect_post_location', $this, 'redirect_post_location_filter', 99 );

			Rtbiz_HD::$loader->add_filter( 'views_edit-' . rtbiz_get_contact_post_type(), $this, 'display_custom_views' );
			Rtbiz_HD::$loader->add_action( 'pre_get_posts', $this, 'contact_posts_filter' );

		}

		public function contact_custom_metabox( ) {
			add_meta_box( 'rthd-ticket-listing', __( 'Tickets' ), array(
				$this,
				'rthd_ticket_listing_metabox',
			), rtbiz_get_contact_post_type(), 'normal', 'default' );
		}

		public function rthd_ticket_listing_metabox( $post ) {
			//if ( ! empty( $_REQUEST['module'] ) && RTBIZ_HD_TEXT_DOMAIN == $_REQUEST['module'] ) {
//			$user = rtbiz_get_wp_user_for_contact( $post->ID );
//				if ( empty($user[0]) ) {
//					return;
//				}
				echo balanceTags( do_shortcode( '[rtbiz_hd_tickets contactid = ' . $post->ID . " title='no' ]" ) );
			//}
		}

		/**
		 * Filter contact
		 * @param $query
		 */
		public function contact_posts_filter( $query ) {
			global $wpdb, $rtbiz_acl_model;
			if ( isset( $_GET['post_type'] ) && rtbiz_get_contact_post_type() == $_GET['post_type'] && rtbiz_get_contact_post_type() == $query->get( 'post_type'    )  ) {
				if ( ! empty( $_GET['fall_back'] ) ){
					return ;
				}
				$_GET['fall_back'] = 'yes';
				if ( isset( $_GET['contact_group'] ) && 'customer' == $_GET['contact_group'] && isset( $_REQUEST['tickets'] ) ) {
					$sql = "SELECT DISTINCT meta_value FROM $wpdb->posts, $wpdb->postmeta WHERE post_id = id and post_status <> 'trash' and meta_key = '_rtbiz_hd_created_by'";
					$contacts_with_ticket = $wpdb->get_col( $sql );
					if ( empty( $contacts_with_ticket ) ) {
						$contacts_with_ticket = array( -1 );
					}
					if ( isset( $_GET['tickets'] ) && 'yes' == $_GET['tickets'] ) {
						$query->set( 'post__in', $contacts_with_ticket );
					} elseif ( isset( $_GET['tickets'] ) && 'no' == $_GET['tickets'] ) {
						$contacts_with_ticket = array_merge( $query->get( 'post__not_in' ), $contacts_with_ticket );
						$query->set( 'post__not_in', $contacts_with_ticket );
					}
				} elseif ( isset( $_GET['contact_group'] ) && 'staff' == $_GET['contact_group'] && isset( $_REQUEST['role'] ) ) {
					$permissions = Rtbiz_Access_Control::$permissions;
					$module_where = isset( $_GET['module'] ) ? "acl.module =  '" . $_GET['module'] . "' and" : '';
					$where = ' and acl.permission = ' . $permissions[ $_REQUEST['role'] ]['value'];
					$sql = 'SELECT DISTINCT(posts.ID) FROM '.$rtbiz_acl_model->table_name.' as acl INNER JOIN '.$wpdb->prefix.'p2p as p2p on ( acl.userid = p2p.p2p_to' . $where . ' ) INNER JOIN '.$wpdb->posts.' as posts on (p2p.p2p_from = posts.ID )  where ' . $module_where . " acl.permission > 0 and p2p.p2p_type = '".rtbiz_get_contact_post_type()."_to_user' and posts.post_status= 'publish' and posts.post_type= '".rtbiz_get_contact_post_type()."' ";
					$contacts = $wpdb->get_col( $sql );

					if ( 'admin' == $_REQUEST['role'] ) {
						$module_user = get_users( array( 'fields' => 'ID', 'role' => 'administrator' ) );
						$admin_contact = rtbiz_get_contact_for_wp_user( $module_user );
						foreach ( $admin_contact as $contact ) {
							$contacts[] = $contact->ID;
						}
					}
					if ( empty( $contacts ) ) {
						$contacts = array( -1 );
					}
					if ( isset( $_GET['role'] ) && in_array( $_REQUEST['role'], array( 'admin', 'editor', 'author' ) ) ) {
						$query->set( 'post__in', $contacts );
					}
				}
				$_GET['fall_back'] = '';
			}
		}

		/**
		 * add filter link for staff & customer
		 *
		 * @param $views
		 *
		 * @return mixed
		 */
		public function display_custom_views( $views ) {
			if ( ! empty( $_REQUEST['module'] ) && RTBIZ_HD_TEXT_DOMAIN == $_REQUEST['module'] && isset( $_REQUEST['contact_group'] ) ) {
				if ( 'staff' == $_REQUEST['contact_group'] ) {
					if ( ! isset( $_GET['role'] ) ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['All'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=staff&module=' . RTBIZ_HD_TEXT_DOMAIN . "' $class>" . __( 'All' ) . '</a>';
					if ( isset( $_GET['role'] ) && 'admin' == $_GET['role'] ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['Admin'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=staff&module=' . RTBIZ_HD_TEXT_DOMAIN . "&role=admin' $class>" . __( 'Admin' ) . '</a>';
					if ( isset( $_GET['role'] ) && 'editor' == $_GET['role'] ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['Editor'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=staff&module=' . RTBIZ_HD_TEXT_DOMAIN . "&role=editor' $class>" . __( 'Editor' ) . '</a>';
					if ( isset( $_GET['role'] ) && 'author' == $_GET['role'] ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['Author'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=staff&module=' . RTBIZ_HD_TEXT_DOMAIN . "&role=author' $class>" . __( 'Author' ) . '</a>';
				} elseif ( 'customer' == $_REQUEST['contact_group'] ) {
					if ( ! isset( $_GET['tickets'] ) ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['All'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=customer&module=' . RTBIZ_HD_TEXT_DOMAIN . "' $class>" . __( 'All' ) . '</a>';
					//$temp_view['All'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . "&contact_group=customer&module=" . RT_BIZ_HD_TEXT_DOMAIN . "&tickets=all' $class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', 0, RT_BIZ_HD_TEXT_DOMAIN ), number_format_i18n( 0 ) ) . '</a>';
					if ( isset( $_GET['tickets'] ) && 'yes' == $_GET['tickets'] ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['With_Ticket'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=customer&module=' . RTBIZ_HD_TEXT_DOMAIN . "&tickets=yes' $class>" . __( 'With Tickets' ) . '</a>';
					//$temp_view['With_Ticket'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . "&contact_group=customer&module=" . RT_BIZ_HD_TEXT_DOMAIN . "&tickets=yes' $class>" . sprintf( _nx( 'With Tickets <span class="count">(%s)</span>', 'With Tickets <span class="count">(%s)</span>', 0, RT_BIZ_HD_TEXT_DOMAIN ), number_format_i18n( 0 ) ) . '</a>';
					if ( isset( $_GET['tickets'] ) && 'no' == $_GET['tickets'] ) { $class = ' class="current"'; } else { $class = ''; }
					$temp_view['Without_Ticket'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . '&contact_group=customer&module=' . RTBIZ_HD_TEXT_DOMAIN . "&tickets=no' $class>" . __( 'Without Tickets' ) . '</a>';
					//$temp_view['Without_Ticket'] = "<a href='edit.php?post_type=" . rtbiz_get_contact_post_type() . "&contact_group=customer&module=" . RT_BIZ_HD_TEXT_DOMAIN . "&tickets=no' $class>" . sprintf( _nx( 'Without Tickets <span class="count">(%s)</span>', 'Without Tickets <span class="count">(%s)</span>', 0, RT_BIZ_HD_TEXT_DOMAIN ), number_format_i18n( 0 ) ) . '</a>';
				}
				$views = $temp_view;
			}
			return $views;
		}

		/**
		 * add query parameter after post update redirection url
		 * @param $location
		 */
		public function redirect_post_location_filter( $location ) {
			if ( ! empty( $_POST['post_type'] ) && rtbiz_get_contact_post_type() == $_POST['post_type']
			     &&  strpos( $_POST['_wp_http_referer'], 'module=' . RTBIZ_HD_TEXT_DOMAIN ) !== false ) {
				$location = add_query_arg( 'module', RTBIZ_HD_TEXT_DOMAIN, $location );
			}
			return $location;
		}

		public function metabox_rearrange() {
			global $wp_meta_boxes;

			if ( empty ($_REQUEST['post']) ) {
				return;
			}
			$users = rtbiz_get_wp_user_for_contact( $_REQUEST['post'] );
			if ( ! empty( $users ) && in_array( 'administrator', $users[0]->roles ) ) {
				$is_staff_member = 'yes';
			} else {
				$is_staff_member  = get_post_meta( $_REQUEST['post'], 'rtbiz_is_staff_member', true );
			}

			$contact_metaboxes['normal']['default']['rthd-ticket-listing'] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['normal']['default']['rthd-ticket-listing'];
			$contact_metaboxes['normal']['default']['rt-biz-entity-details'] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['normal']['default']['rt-biz-entity-details'];
			$contact_metaboxes['normal']['core']['commentsdiv'] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['normal']['core']['commentsdiv'];
			$contact_metaboxes['side']['core']['submitdiv'] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['side']['core']['submitdiv'];
//			$contact_metaboxes['side']['core'][ 'p2p-from-' . rtbiz_get_contact_post_type() . '_to_user' ] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['side']['default'][ 'p2p-from-' . rtbiz_get_contact_post_type() . '_to_user' ];
			$contact_metaboxes['side']['core']['rt-biz-acl-details'] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['side']['default']['rt-biz-acl-details'];
			$contact_metaboxes['side']['core'][ Rt_Products::$product_slug . 'div' ] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['side']['core'][ Rt_Products::$product_slug . 'div' ];
			$contact_metaboxes['side']['core'][ Rtbiz_Teams::$slug . 'div' ] = $wp_meta_boxes[ rtbiz_get_contact_post_type() ]['side']['core'][ Rtbiz_Teams::$slug . 'div' ];

			if ( ! empty( $is_staff_member ) && 'yes' == $is_staff_member ) {
				// remove metabox only staff
			} else {
				//remove metabox only customer
				unset( $contact_metaboxes['side']['core'][ Rt_Products::$product_slug . 'div' ] );
				unset( $contact_metaboxes['side']['core'][ Rtbiz_Teams::$slug . 'div' ] );
			}

			$wp_meta_boxes[ rtbiz_get_contact_post_type() ] = $contact_metaboxes;
		}

		/**
		 * add module query argument
		 * @param $url
		 * @param $postid
		 */
		public function edit_contact_link( $url, $postid ) {
			if ( ! empty( $_REQUEST['module'] ) && ! empty( $postid ) && get_post_type( $postid ) == rtbiz_get_contact_post_type() ) {
				$url = esc_url( add_query_arg( 'module', $_REQUEST['module'], $url ) );
			}

			global $pagenow;
			if ( 'users.php' == $pagenow && ! empty( $postid ) && get_post_type( $postid ) == rtbiz_get_contact_post_type() ) {
				$url = esc_url( add_query_arg( 'module', RTBIZ_HD_TEXT_DOMAIN, $url ) );
			}

			return $url;
		}

		/*
		 * change label for staff and customer
		 */
		public function change_contact_lablels( $labels ) {
			$label  = '';
			$labelp = '';
			if ( isset( $_GET['contact_group'] ) && 'staff' == $_GET['contact_group'] ) {
				$label  = 'Staff';
				$labelp = $label;
			} elseif ( isset( $_GET['contact_group'] ) && 'customer' == $_GET['contact_group'] ) {
				$label  = 'Customer';
				$labelp = $label . 's';
			}

			if ( ! empty( $label ) ) {
				$labels = array(
					'name'               => __( $labelp ),
					'singular_name'      => __( $label ),
					'menu_name'          => __( $labelp ),
					'all_items'          => __( 'All ' . $labelp ),
					'add_new'            => __( 'New ' . $label ),
					'add_new_item'       => __( 'Add ' . $label ),
					'edit_item'          => __( 'Edit ' . $label ),
					'new_item'           => __( 'New ' . $label ),
					'view_item'          => __( 'View ' . $label ),
					'search_items'       => __( 'Search ' . $label ),
					'not_found'          => __( 'No ' . $label . ' found' ),
					'not_found_in_trash' => __( 'No ' . $label . ' found in Trash' ),
				);
			}

			return $labels;
		}

		/**
		 * assigne ticket to admin
		 *
		 * @param $contactid
		 * @param $userid
		 */
		public function before_delete_staff( $contactid, $userid, $permission ) {
			$settings = rtbiz_hd_get_redux_settings();
			$args     = array(
				'numberposts' => - 1,
				'post_type'   => Rtbiz_HD_Module::$post_type,
				'author'      => $userid,
			);
			$tickets  = new WP_Query( $args );
			if ( $tickets->have_posts() ) {
				$tickets = $tickets->posts;
				foreach ( $tickets as $ticket ) {
					$data = array(
						'ID'          => $ticket->ID,
						'post_author' => $settings['rthd_default_user'],
					);
					// Update the post into the database
					wp_update_post( $data );
				}
			}
		}

		/**
		 * bulk/Quick action ui added for helpdesk role
		 *
		 * @param $col
		 * @param $type
		 */
		public function contact_quick_action( $col, $type ) {
			if ( rtbiz_get_contact_post_type() != $type || 'rtbiz_hd_ticket' != $col ) {
				return;
			}
			$permissions = rtbiz_get_acl_permissions(); ?>
			<fieldset id="rtbiz_contact_helpdesk_access" class="inline-edit-col-right">
				<input type="hidden" name="contact_group" value="<?php echo isset( $_GET['contact_group'] ) ? $_GET['contact_group'] : ''; ?>" >
				<input type="hidden" name="module" value="<?php echo isset( $_GET['module'] ) ? $_GET['module'] : ''; ?>" >
				<div class="inline-edit-col">
					<?php $selected = ( isset( $_REQUEST['contact_group'] ) && 'staff' == $_REQUEST['contact_group'] ) ? 'Checked="Checked"' : ''; ?>
					<label><input type="checkbox" id="rtbiz_is_staff_member" <?php echo $selected; ?>
					              name="rtbiz_is_staff_member" value="yes"><span
							class="checkbox-title"><?php _e( 'Staff Member ', RTBIZ_HD_TEXT_DOMAIN ) ?></span></label>
				</div>
				<div id="rtbiz-permission-container" class="inline-edit-col <?php if ( ! $selected ) {
					echo 'rtbiz-hide'; } ?> ">
					<label class="alignleft">
						<span>Helpdesk Role</span>
						<input type="hidden" name="rtbiz_action" value="rtbiz_helpdesk_role_updated">
						<select name="rtbiz_profile_permissions[rtbiz-helpdesk]">
							<?php foreach ( $permissions as $pkey => $p ) { ?>
								<option title="<?php echo $p['tooltip']; ?>"
								        value="<?php echo $p['value']; ?>"><?php echo $p['name']; ?></option>
							<?php } ?>
						</select>
					</label>
				</div>
			</fieldset>
		<?php
		}

		/**
		 * update helpdesk role bulk action
		 *
		 * @param $post_id
		 * @param $post
		 *
		 * @return mixed
		 */
		public function save_helpdesk_role( $post_id, $post ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			if ( isset( $post->post_type ) && $post->post_type != rtbiz_get_contact_post_type() ) {
				return $post_id;
			}

			if ( isset( $_REQUEST['rtbiz_action'] ) && 'rtbiz_helpdesk_role_updated' == $_REQUEST['rtbiz_action'] ) {
				global $rtbiz_acl_model;

				// rtbiz has same acl as helpdesk
				$_REQUEST['rtbiz_profile_permissions'][ RTBIZ_TEXT_DOMAIN ] = $_REQUEST['rtbiz_profile_permissions'][ RTBIZ_HD_TEXT_DOMAIN ];
				$profile_permissions                                          = $_REQUEST['rtbiz_profile_permissions'];

				$contactIds = array();
				if ( isset( $_REQUEST['post_ID'] ) ) {
					$contactIds = array( $_REQUEST['post_ID'] );
				} else {
					$contactIds = $_REQUEST['post'];
				}
				$users = rtbiz_get_wp_user_for_contact( $contactIds );
				foreach ( $users as $user ) {
					if ( in_array( 'administrator', $user->roles ) ) {
						continue;
					}
					if ( 'yes' == $_REQUEST['rtbiz_is_staff_member'] ) {
						foreach ( $profile_permissions as $module_Key => $module_permission ) {
							switch ( $module_permission ) {
								case 0:
									//if group level permission is enable for helpdesk then write group level code here
									//remove all permission
									$where = array(
										'userid' => $user->ID,
										'module' => $module_Key,
									);
									$rtbiz_acl_model->remove_acl( $where );
									break;
								case 10:
								case 20:
								case 30:
									$where = array(
										'userid' => $user->ID,
										'module' => $module_Key,
									);
									$acl   = $rtbiz_acl_model->get_acl( $where );
									if ( ! empty( $acl ) ) {
										$data  = array(
											'permission' => $module_permission,
										);
										$where = array(
											'userid'  => $user->ID,
											'module'  => $module_Key,
											'groupid' => 0,
										);
										$rtbiz_acl_model->update_acl( $data, $where );
									} else {
										$data = array(
											'userid'     => $user->ID,
											'module'     => $module_Key,
											'groupid'    => 0,
											'permission' => $module_permission,
										);
										$rtbiz_acl_model->add_acl( $data );
									}
									break;
							}
						}
					} else {
						$where = array(
							'userid' => $user->ID,
						);
						$rtbiz_acl_model->remove_acl( $where );
						$profile_permissions = array();
					}
				}
				foreach ( $contactIds as $contactId ) {
					$user_permissions                       = get_post_meta( $contactId, 'rtbiz_profile_permissions', true );
					$user_permissions[ RTBIZ_TEXT_DOMAIN ] = $profile_permissions[ RTBIZ_TEXT_DOMAIN ];
					$user_permissions[ RTBIZ_HD_TEXT_DOMAIN ]  = $profile_permissions[ RTBIZ_HD_TEXT_DOMAIN ];
					update_post_meta( $contactId, 'rtbiz_profile_permissions', $user_permissions );
					update_post_meta( $contactId, 'rtbiz_is_staff_member', $_REQUEST['rtbiz_is_staff_member'] );
				}
			}
		}

		public function add_hd_additional_details( $fields ) {
			$custom_filed = array();
			$post_type = isset( $_REQUEST['post_type'] )? $_REQUEST['post_type'] : '';
			if ( empty( $post_type ) && ! empty( $_REQUEST['post'] ) ) {
				$post_type = get_post_type( $_REQUEST['post'] );
			}
			if ( ( ( ! empty( $_REQUEST['module'] ) &&  RTBIZ_HD_TEXT_DOMAIN == $_REQUEST['module'] )
			       || ( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], 'module=' . RTBIZ_HD_TEXT_DOMAIN ) !== false ) )
			     && rtbiz_get_contact_post_type() == $post_type ) {
				$custom_filed[] = $fields[0];
				$custom_filed[] = $fields[1];
				$fields = $custom_filed;
			}

			if ( rtbiz_hd_get_redux_adult_filter() ) {
				$fields[] = array(
					'key'      => 'rthd_contact_adult_filter',
					'text'     => __( 'Don\'t show Adult content' ),
					'label'    => __( 'Helpdesk Content Preference' ),
					'type'     => 'checkbox',
					'name'     => 'contact_meta[rthd_contact_adult_filter]',
					'id'       => 'rthd_contact_adult_filter',
					'category' => 'Helpdesk',
				);
			};
			$fields[] = array(
				'key'      => 'rthd_receive_notification',
				'text'     => __( 'Turn Off Event Notification' ),
				'label'    => __( 'Helpdesk Notification Preference' ),
				'type'     => 'checkbox',
				'name'     => 'contact_meta[rthd_receive_notification]',
				'id'       => 'rthd_receive_notification',
				'category' => 'Helpdesk',
			);

			return $fields;
		}

		public function contacts_rearrange_columns( $columns, $rt_entity ) {

			global $rtbiz_contact;
			if ( $rt_entity->post_type != $rtbiz_contact->post_type ) {
				return $columns;
			}

			if ( ! empty( $_REQUEST['module'] ) &&  RTBIZ_HD_TEXT_DOMAIN == $_REQUEST['module'] ) {
				$hd_columns = array();
				$hd_columns['cb'] = $columns['cb'];
				if ( ! empty( $_REQUEST['contact_group'] ) && 'staff' == $_REQUEST['contact_group'] ) {
					$hd_columns['title'] = $columns['title'];
					$hd_columns[ 'taxonomy-' . Rtbiz_Teams::$slug ] = $columns[ 'taxonomy-' . Rtbiz_Teams::$slug ];
				} else {
					$hd_columns['title'] = $columns['title'];
				}
				$hd_columns[ Rtbiz_HD_Module::$post_type ] = $columns[ Rtbiz_HD_Module::$post_type ];
				$columns = $hd_columns;
			}

			return $columns;
		}

		/**
		 * Create custom column 'Tickets' for Contacts taxonomy
		 *
		 * @since 0.1
		 *
		 * @param $columns
		 * @param $rt_entity
		 *
		 * @return mixed
		 */
		public function contacts_columns( $columns, $rt_entity ) {

			global $rtbiz_contact, $rtbiz_hd_module;
			if ( $rt_entity->post_type != $rtbiz_contact->post_type ) {
				return $columns;
			}

			$columns[ Rtbiz_HD_Module::$post_type ] = $rtbiz_hd_module->labels['all_items'];

			return $columns;
		}

		/**
		 * Get count of contact terms used in individual ticket. This public function returns the exact count
		 *
		 * @since    0.1
		 *
		 * @param $column
		 * @param $post_id
		 * @param $rt_entity
		 *
		 * @internal param string $out
		 * @internal param string $column_name
		 * @internal param int $term_id
		 * @return string $out
		 */
		public function manage_contacts_columns( $column, $post_id, $rt_entity ) {

			global $rtbiz_contact, $rtbiz_acl_model;
			if ( $rt_entity->post_type != $rtbiz_contact->post_type ) {
				return;
			}

			switch ( $column ) {
				case Rtbiz_HD_Module::$post_type:
					if ( ! empty( $_REQUEST['contact_group'] ) && 'staff' == $_REQUEST['contact_group'] ) {
						$userid = rtbiz_get_wp_user_for_contact( $post_id );
						if ( ! empty( $userid[0] ) ) {
							$args  = array(
								'post_type'   => Rtbiz_HD_Module::$post_type,
								'post_status' => 'any',
								'author'      => $userid[0]->ID,
							);
							$query = new WP_Query( $args );
							$link  = get_admin_url() . 'edit.php?post_type=' . Rtbiz_HD_Module::$post_type . '&assigned=' . $userid[0]->ID;
							echo '<a href="' . $link . '" target="_blank">' . $query->found_posts . '</a>';
						} else {
							echo '0';
						}
					} else {
						$args  = array(
							'post_type'   => Rtbiz_HD_Module::$post_type,
							'post_status' => 'any',
							'meta_key'    => '_rtbiz_hd_created_by',
							'meta_value'  => $post_id,
						);
						//$_REQUEST['tickets'] = null; // With/without pre post query override due to this argument. For preventing such condition argument set as null
						$query = new WP_Query( $args );
						$link  = get_admin_url() . 'edit.php?post_type=' . Rtbiz_HD_Module::$post_type . '&created_by=' . $post_id;
						echo '<a href="' . $link . '" target="_blank">' . $query->found_posts . '</a>';
					}
					break;
				default:
					if ( Rtbiz_HD_Module::$post_type == $column ) {
						$post_details = get_post( $post_id );
						$pages        = rtbiz_get_post_for_contact_connection( $post_id, Rtbiz_HD_Module::$post_type );
						echo balanceTags( sprintf( '<a href="%s">%d</a>', esc_url( add_query_arg( array(
							'contact_id' => $post_details->ID,
							'post_type'  => Rtbiz_HD_Module::$post_type,
						), admin_url( 'edit.php' ) ) ), count( $pages ) ) );
					}
					break;
			}
		}

		/**
		 * add new contact
		 *
		 * @since 0.1
		 *
		 * @param      $email
		 * @param      $title
		 *
		 * @param bool $create_wp_user to Create wordpress user or not
		 *
		 * @return mixed|null|WP_Post
		 */
		public function insert_new_contact( $email, $title, $create_wp_user = false ) {

			global $transaction_id;
			$contact = rtbiz_get_contact_by_email( $email );
			if ( ! $contact ) {
				if ( trim( $title ) == '' ) {
					$title = $email;
				}
				$contact_id = rtbiz_add_contact( $title, '', $email );
				$contact    = get_post( $contact_id );
				if ( ! $create_wp_user ) {
					return $contact;
				}
				$userid     = $this->get_user_from_email( $email );

				if ( ! empty( $userid ) ) {
					rtbiz_add_entity_meta( $contact->ID, $this->user_id, $userid );
				}

				rtbiz_connect_contact_to_user( $contact->ID, $userid );

				if ( isset( $transaction_id ) && $transaction_id > 0 ) {
					add_post_meta( $contact->ID, '_transaction_id', $transaction_id, true );
				}
			}

			return $contact;
		}

		/**
		 * get user from email id
		 *
		 * @since 0.1
		 *
		 * @param        $email
		 *
		 * @param string $name
		 *
		 * @return bool|int|null
		 */
		public function get_user_from_email( $email, $name = '' ) {

			$userid = email_exists( $email );
//			if ( ! $userid && ! is_wp_error( $userid ) ) {
				//add_filter( 'wpmu_welcome_user_notification', '__return_false' );
//				$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
//				$userid          = wp_create_user( $email, $random_password, $email );
//				rtbiz_hd_wp_new_user_notification( $userid, $random_password );
//			} else {
//			}
			if ( !is_wp_error( $userid ) && ! empty( $userid ) ){
				rtbiz_export_contact( $userid );
			}
			$contact = rtbiz_get_contact_by_email( $email );
//			$contact = rtbiz_get_contact_for_wp_user( $userid );
			if ( ! empty( $contact[0] ) ) {
				$userid = $contact[0]->ID;
				if ( ! empty( $name ) && $contact[0]->post_title != $name ) {
					$contact[0]->post_title = $name;
					wp_update_post( $contact[0] );
				}
			} else {
				$userid = rtbiz_add_contact( ( ! empty( $name )? $name :$email ),'', $email );
			}

			return $userid;
		}

		/**
		 * contacts different on ticket
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 */
		public function contacts_diff_on_ticket( $post_id, $newTicket ) {

			$diffHTML = '';
			if ( ! isset( $newTicket['contacts'] ) ) {
				$newTicket['contacts'] = array();
			}
			$contacts = $newTicket['contacts'];
			$contacts = array_unique( $contacts );

			$oldContactsString = rtbiz_contact_connection_to_string( $post_id );
			$newContactsSring  = '';
			if ( ! empty( $contacts ) ) {
				$contactsArr = array();
				foreach ( $contacts as $contact ) {
					$newC          = get_post( $contact );
					$contactsArr[] = $newC->post_title;
				}
				$newContactsSring = implode( ',', $contactsArr );
			}
			$diff = rtbiz_hd_text_diff( $oldContactsString, $newContactsSring );
			if ( $diff ) {
				$diffHTML .= '<tr><th style="padding: .5em;border: 0;">Contacts</th><td>' . $diff . '</td><td></td></tr>';
			}

			return $diffHTML;
		}

		/**
		 * contact save on tickets
		 *
		 * @since 0.1
		 *
		 * @param $post_id
		 * @param $newTicket
		 */
		public function contacts_save_on_ticket( $post_id, $newTicket ) {
			if ( ! isset( $newTicket['contacts'] ) ) {
				$newTicket['contacts'] = array();
			}
			$contacts = array_map( 'intval', $newTicket['contacts'] );
			$contacts = array_unique( $contacts );

			$post_type = get_post_type( $post_id );

			rtbiz_clear_post_connections_to_contact( $post_type, $post_id );
			foreach ( $contacts as $contact ) {
				rtbiz_connect_post_to_contact( $post_type, $post_id, $contact );
			}
		}

		/**
		 * AJAX call to get accounts
		 *
		 * @since 0.1
		 */
		public function ajax_get_account_contacts() {

			$contacts = rtbiz_get_company_to_contact_connection( $_POST['query'] );
			$result   = array();
			foreach ( $contacts as $contact ) {
				$email    = rtbiz_get_entity_meta( $contact->ID, Rtbiz_Contact::$primary_email_key, true );
				$result[] = array(
					'label'   => $contact->post_title,
					'id'      => $contact->ID,
					'slug'    => $contact->post_name,
					'email'   => $email,
					'imghtml' => get_avatar( $email, 24 ),
					'url'     => admin_url( 'edit.php?' . $contact->post_type . '=' . $contact->ID . '&post_type=' . $_POST['post_type'] ),
				);
			}

			echo json_encode( $result );
			die( 0 );
		}

		/**
		 * AJAX call to get taxonomy meta
		 *
		 * @since 0.1
		 */
		public function ajax_get_taxonomy_meta() {
			if ( ! isset( $_POST['query'] ) ) {
				wp_die( 'Opss!! Invalid request' );
			}

			$post_id   = $_POST['query'];
			$post_type = get_post_type( $post_id );
			$result    = get_post_meta( $post_id );

			$accounts = rtbiz_get_post_for_company_connection( $post_id, $post_type, $fetch_account = true );
			foreach ( $accounts as $account ) {
				$result['account_id'] = $account->ID;
			}
			echo json_encode( $result );
			die( 0 );
		}

		/**
		 * AJAX call for autocomplete contact
		 *
		 * @since 0.1
		 */
		public function ajax_contact_autocomplete() {
			if ( ! isset( $_POST['query'] ) ) {
				wp_die( 'Opss!! Invalid request' );
			}

			$contacts = rtbiz_search_contact( $_POST['query'] );
			$result   = array();
			foreach ( $contacts as $contact ) {
				$result[] = array(
					'label'   => $contact->post_title,
					'id'      => $contact->ID,
					'slug'    => $contact->post_name,
					'imghtml' => get_avatar( '', 24 ),
					'url'     => admin_url( 'edit.php?' . $contact->post_type . '=' . $contact->ID . '&post_type=' . $_POST['post_type'] ),
				);
			}

			echo json_encode( $result );
			die( 0 );
		}

		/**
		 * add new contact AJAX call
		 *
		 * @since 0.1
		 */
		public function ajax_add_new_contact() {
			$returnArray           = array();
			$returnArray['status'] = false;
			$accountData           = $_POST['data'];
			if ( ! isset( $accountData['new-contact-name'] ) ) {
				$returnArray['status']  = false;
				$returnArray['message'] = 'Invalid Data Please Check';
			} else {
				$post_id = post_exists( $accountData['new-contact-name'] );
				if ( ! empty( $post_id ) && get_post_type( $post_id ) === rtbiz_get_contact_post_type() ) {
					$returnArray['status']  = false;
					$returnArray['message'] = 'Term Already Exits';
				} else {
					if ( isset( $accountData['contactmeta'] ) && ! empty( $accountData['contactmeta'] ) ) {
						foreach ( $accountData['contactmeta'] as $cmeta => $metavalue ) {
							foreach ( $metavalue as $metadata ) {
								if ( false != strstr( $cmeta, 'email' ) ) {
									$result = rtbiz_get_contact_by_email( $metadata );
									if ( $result && ! empty( $result ) ) {
										$returnArray['status']  = false;
										$returnArray['message'] = $metadata . ' is already exits';
										echo json_encode( $returnArray );
										die( 0 );
									}
								}
							}
						}
					}

					$post_id = rtbiz_add_contact( $accountData['new-contact-name'], $accountData['new-contact-description'] );

					if ( isset( $accountData['new-contact-account'] ) && trim( $accountData['new-contact-account'] ) != ''
					) {

						rtbiz_connect_company_to_contact( $accountData['new-contact-account'], $post_id );
					}

					$email = $accountData['new-contact-name'];

					if ( isset( $accountData['contactmeta'] ) && ! empty( $accountData['contactmeta'] ) ) {

						foreach ( $accountData['contactmeta'] as $cmeta => $metavalue ) {
							foreach ( $metavalue as $metadata ) {
								if ( false != strstr( $cmeta, 'email' ) ) {
									$email = $metadata;
								}

								rtbiz_add_entity_meta( $post_id, $cmeta, $metadata );
							}
						}
					}
					$returnArray['status'] = true;

					$post                = get_post( $post_id );
					$returnArray['data'] = array(
						'id'      => $post_id,
						'value'   => $post->ID,
						'label'   => $accountData['new-contact-name'],
						'url'     => admin_url( 'edit.php?' . $post->post_type . '=' . $post->ID . '&post_type=' . $accountData['post_type'] ),
						'imghtml' => get_avatar( $email, 50 ),
					);
				}
			}
			echo json_encode( $returnArray );
			die( 0 );
		}
	}
}
