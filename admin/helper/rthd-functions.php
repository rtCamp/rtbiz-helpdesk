<?php
/**
 *   Helper functions for rt-helpdesk
 * @author udit
 */

/**
 * rt-helpdesk Functions
 * used to render template

 */
function rtbiz_hd_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	$located = rtbiz_hd_locate_template( $template_name, $template_path, $default_path );

	do_action( 'rthd_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'rthd_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * @param        $template_name
 * @param string $template_path
 * @param string $default_path
 * used to locate / get template path
 *
 * @return mixed|void
 */
function rtbiz_hd_locate_template( $template_name, $template_path = '', $default_path = '' ) {

	global $rtbiz_hd;
	if ( ! $template_path ) {
		$template_path = Rtbiz_HD::$templateURL;
	}
	if ( ! $default_path ) {
		$default_path = RTBIZ_HD_PATH_TEMPLATES;
	}

	// Look within passed path within the theme - this is priority
	$template = locate_template( array( trailingslashit( $template_path ) . $template_name, $template_name ) );

	// Get default template
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters( 'rtbiz_hd_locate_template', $template, $template_name, $template_path );
}

/**
 * @param $taxonomy
 * sanitize taxonomy name
 *
 * @return mixed|string
 */
function rtbiz_hd_sanitize_taxonomy_name( $taxonomy ) {
	$taxonomy = strtolower( stripslashes( strip_tags( $taxonomy ) ) );
	$taxonomy = preg_replace( '/&.+?;/', '', $taxonomy ); // Kill entities
	$taxonomy = str_replace( array( '.', '\'', '"' ), '', $taxonomy ); // Kill quotes and full stops.
	$taxonomy = str_replace( array( ' ', '_' ), '-', $taxonomy ); // Replace spaces and underscores.

	return $taxonomy;
}

/**
 * @param $name
 * adding prefix for taxonomy
 *
 * @return string
 */
function rtbiz_hd_attribute_taxonomy_name( $name ) {
	return 'rtbiz_hd_' . rtbiz_hd_sanitize_taxonomy_name( $name );
}

/**
 * @param $name
 *  adding prefix for RtBiz post type
 *
 * @return string
 */
function rtbiz_post_type_name( $name ) {
	return 'rt_' . rtbiz_hd_sanitize_taxonomy_name( $name );
}

/**
 * @param $name
 *  adding prefix for HelpDesk post type
 *
 * @return string
 */
function rthd_post_type_name( $name ) {
	return 'rtbiz_hd_' . rtbiz_hd_sanitize_taxonomy_name( $name );
}

/**
 * @param string $attribute_store_as
 *  get all attributes
 *
 * @return array
 */
function rthd_get_all_attributes( $attribute_store_as = '' ) {
	global $rtbiz_hd_attributes_model;
	$attrs = $rtbiz_hd_attributes_model->get_all_attributes();

	if ( empty( $attribute_store_as ) ) {
		return $attrs;
	}

	$newAttr = array();
	foreach ( $attrs as $attr ) {
		if ( $attr->attribute_store_as == $attribute_store_as ) {
			$newAttr[] = $attr;
		}
	}

	return $newAttr;
}

/**
 * @param        $post_type
 * @param string $attribute_store_as
 * get single attribute
 *
 * @return array
 */
function rtbiz_hd_get_attributes( $post_type, $attribute_store_as = '' ) {
	global $rtbiz_hd_attributes_relationship_model, $rtbiz_hd_attributes_model;
	$relations = $rtbiz_hd_attributes_relationship_model->get_relations_by_post_type( $post_type );
	$attrs = array();

	foreach ( $relations as $relation ) {
		$attrs[] = $rtbiz_hd_attributes_model->get_attribute( $relation->attr_id );
	}

	if ( empty( $attribute_store_as ) ) {
		return $attrs;
	}

	$newAttr = array();
	foreach ( $attrs as $attr ) {
		if ( $attr->attribute_store_as == $attribute_store_as ) {
			$newAttr[] = $attr;
		}
	}

	return $newAttr;
}

/* * ********* Post Term To String **** */

/**
 * Post Term To String
 *
 * @param        $postid
 * @param        $taxonomy
 * @param string $termsep
 *
 * @return string
 */
function rtbiz_hd_post_term_to_string( $postid, $taxonomy, $termsep = ',' ) {
	$termsArr = get_the_terms( $postid, $taxonomy );
	$tmpStr = '';
	if ( $termsArr ) {
		$sep = '';
		foreach ( $termsArr as $tObj ) {

			if ( isset( $tObj->name ) ) {
				$tmpStr .= $sep . $tObj->name;
				$sep = $termsep;
			}
		}
	}

	return $tmpStr;
}

/**
 * extract key from attributes
 *
 * @param $attr
 *
 * @return mixed
 */
function rtbiz_hd_extract_key_from_attributes( $attr ) {
	return $attr->attribute_name;
}

/**
 * check if given email is system email or not
 *
 * @param $email
 *
 * @return bool
 */
function rtbiz_hd_is_system_email( $email ) {
	global $rt_mail_settings;
	$google_acs = $rt_mail_settings->get_user_google_ac();

	foreach ( $google_acs as $ac ) {
		$ac->email_data = unserialize( $ac->email_data );
		$ac_email = filter_var( $ac->email_data['email'], FILTER_SANITIZE_EMAIL );
		if ( $ac_email == $email ) {
			return true;
		}
	}
	return false;
}

/**
 * get all participants list array
 *
 * @param $ticket_id
 *
 * @return array
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_get_all_participants( $ticket_id ) {
	$ticket = get_post( $ticket_id );
	$participants = array();
	if ( isset( $ticket->post_author ) ) {
		$participants[] = $ticket->post_author;
	}
	$subscribers = get_post_meta( $ticket_id, '_rtbiz_hd_subscribe_to', true );
	$participants = array_merge( $participants, $subscribers );

	//	$contacts = wp_get_post_terms( $ticket_id, rtbiz_hd_attribute_taxonomy_name( 'contacts' ) );
	//	foreach ( $contacts as $contact ) {
	//		$user_id = get_term_meta( $contact->term_id, 'user_id', true );
	//		if(!empty($user_id)) {
	//			$participants[] = $user_id;
	//		}
	//	}

	$comments = get_comments( array( 'order' => 'DESC', 'post_id' => $ticket_id, 'post_type' => $ticket->post_type ) );
	$all_p = array();
	foreach ( $comments as $comment ) {
		$p = '';
		$to = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_email_to', true );
		if ( ! empty( $to ) ) {
			$p .= $to . ',';
		}
		$cc = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_email_cc', true );
		if ( ! empty( $cc ) ) {
			$p .= $cc . ',';
		}
		$bcc = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_email_bcc', true );
		if ( ! empty( $bcc ) ) {
			$p .= $bcc;
		}

		if ( ! empty( $p ) ) {
			$p_arr = explode( ',', $p );
			$p_arr = array_unique( $p_arr );
			$all_p = array_merge( $all_p, $p_arr );
		}
	}
	$all_p = array_unique( $all_p );
	foreach ( $all_p as $p ) {
		$user = get_user_by( 'email', $p );
		if ( $user ) {
			$participants[] = $user->ID;
		}
	}

	return array_unique( $participants );
}

/**
 * get ticket table name
 * @return string
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_get_ticket_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'rt_wp_hd_ticket_index';
}

/**
 * get user ids
 *
 * @param $user
 *
 * @return mixed
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_get_user_ids( $user ) {
	return $user->ID;
}

/**
 * update post term count
 *
 * @param $terms
 * @param $taxonomy
 *
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_update_post_term_count( $terms, $taxonomy ) {
	global $wpdb;

	$object_types = (array) $taxonomy->object_type;

	foreach ( $object_types as &$object_type ) {
		list( $object_type ) = explode( ':', $object_type );
	}

	$object_types = array_unique( $object_types );

	if ( false !== ( $check_attachments = array_search( 'attachment', $object_types ) ) ) {
		unset( $object_types[ $check_attachments ] );
		$check_attachments = true;
	}

	if ( $object_types ) {
		$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );
	}

	foreach ( (array) $terms as $term ) {
		$count = 0;

		// Attachments can be 'inherit' status, we need to base count off the parent's status if so
		if ( $check_attachments ) {
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id  AND post_type = 'attachment' AND term_taxonomy_id = %d", $term ) );
		}

		if ( $object_types ) {
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id  AND post_type IN ('" . implode( "', '", $object_types ) . "') AND term_taxonomy_id = %d", $term ) );
		}

		do_action( 'edit_term_taxonomy', $term, $taxonomy );
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		do_action( 'edited_term_taxonomy', $term, $taxonomy );
	}
}

/**
 * Function to encrypt or decrypt the given value
 *
 * @param encrypted /decrypted $string
 *
 * @return string Return the encrypted/decrypted string
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_encrypt_decrypt( $string ) {

	$string_length = strlen( $string );
	$encrypted_string = '';

	/**
	 * For each character of the given string generate the code
	 */
	for ( $position = 0; $position < $string_length; $position ++ ) {
		$key = ( ( $string_length + $position ) + 1 );
		$key = ( 255 + $key ) % 255;
		$get_char_to_be_encrypted = substr( $string, $position, 1 );
		$ascii_char = ord( $get_char_to_be_encrypted );
		$xored_char = $ascii_char ^ $key; //xor operation
		$encrypted_char = chr( $xored_char );
		$encrypted_string .= $encrypted_char;
	}

	/**
	 * Return the encrypted/decrypted string
	 */
	return $encrypted_string;
}

/**
 * wp1_text_diff
 *
 * @param      $left_string
 * @param      $right_string
 * @param null $args
 *
 * @return string
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_text_diff( $left_string, $right_string, $args = null ) {
	$defaults = array( 'title' => '', 'title_left' => '', 'title_right' => '' );
	$args = wp_parse_args( $args, $defaults );

	$left_string = normalize_whitespace( $left_string );
	$right_string = normalize_whitespace( $right_string );
	$left_lines = explode( "\n", $left_string );
	$right_lines = explode( "\n", $right_string );

	$renderer = new Rtbiz_HD_Email_Diff();
	$text_diff = new Text_Diff( $left_lines, $right_lines );
	$diff = $renderer->render( $text_diff );

	if ( ! $diff ) {
		return '';
	}

	$r = "<table class='diff' style='width: 100%;background: white;margin-bottom: 1.25em;border: solid 1px #dddddd;border-radius: 3px;margin: 0 0 18px;'>\n";
	$r .= "<col class='ltype' /><col class='content' /><col class='ltype' /><col class='content' />";

	if ( $args['title'] || $args['title_left'] || $args['title_right'] ) {
		$r .= '<thead>';
	}
	if ( $args['title'] ) {
		$r .= "<tr class='diff-title'><th colspan='4'>" . $args['title'] . '</th></tr>\n';
	}
	if ( $args['title_left'] || $args['title_right'] ) {
		$r .= "<tr class='diff-sub-title'>\n";
		$r .= "\t<td></td><th>{$args['title_left']}</th>\n";
		$r .= "\t<td></td><th>{$args['title_right']}</th>\n";
		$r .= "</tr>\n";
	}
	if ( $args['title'] || $args['title_left'] || $args['title_right'] ) {
		$r .= "</thead>\n";
	}
	$r .= "<tbody>\n$diff\n</tbody>\n";
	$r .= '</table>';

	return $r;
}

/**
 * return content type
 * @return string
 * @since rt-Helpdesk 0.1
 */
function rtbiz_hd_set_html_content_type() {
	return 'text/html';
}

function rtbiz_hd_get_unique_hash_url( $ticket_id ) {
	global $rtbiz_hd_module;
	$labels = $rtbiz_hd_module->labels;
	$rthd_unique_id = get_post_meta( $ticket_id, '_rtbiz_hd_unique_id', true );
	return trailingslashit( site_url() ) . strtolower( $labels['singular_name'] ) . '/?rthd_unique_id=' . $rthd_unique_id;
}

function rtbiz_hd_is_unique_hash_enabled() {
	$settings = rtbiz_hd_get_redux_settings();
	if ( ! empty( $settings['rthd_enable_ticket_unique_hash'] ) ) {
		return true;
	}
	return false;
}

// Setting ApI
function rtbiz_hd_get_redux_settings() {
	if ( ! isset( $GLOBALS['redux_helpdesk_settings'] ) ) {
		$GLOBALS['redux_helpdesk_settings'] = get_option( 'redux_helpdesk_settings', array() );
	}

	return $GLOBALS['redux_helpdesk_settings'];
}

function rtbiz_hd_my_mail_from( $email ) {
	$settings = rtbiz_hd_get_redux_settings();
	return $settings['rthd_outgoing_email_from_address'];
}

// user notification preference
function rtbiz_hd_get_user_notification_preference( $user_id, $email = '' ) {
	if ( empty( $email ) ) {
		$user = get_user_by( 'id', $user_id );
		$email = $user->user_email;
	}
	$post = rtbiz_get_contact_by_email( $email );
	if ( ! empty( $post[0] ) ) {
		$pref = Rtbiz_Entity::get_meta( $post[0]->ID, 'rthd_receive_notification', true );
	}

	//	$pref = get_user_meta( $user_id, 'rthd_notification_pref', true );
	if ( empty( $pref ) ) {
		$pref = 'no';
	}
	return $pref;
}

//adult filter redux setting
function rtbiz_hd_get_redux_adult_filter() {
	$settings = rtbiz_hd_get_redux_settings();
	if ( ! empty( $settings['rthd_enable_ticket_adult_content'] ) ) {
		return true;
	}
	return false;
}

//adult content preference
function rtbiz_hd_get_user_adult_preference( $user_id, $email = '' ) {
	if ( empty( $email ) ) {
		$user = get_user_by( 'id', $user_id );
		$email = $user->user_email;
	}
	$post = rtbiz_get_contact_by_email( $email );

	if ( ! empty( $post[0] ) ) {
		$pref = Rtbiz_Entity::get_meta( $post[0]->ID, 'rthd_contact_adult_filter', true );
	}
	//  Old adult pref meta key
	//	$pref = get_user_meta( $user_id, 'rthd_adult_pref', true );
	if ( empty( $pref ) ) {
		$pref = 'no';
	}
	return $pref;
}

function rtbiz_hd_add_user_fav_ticket( $userid, $postid ) {
	add_user_meta( $userid, '_rtbiz_hd_fav_tickets', $postid );
}

function rtbiz_hd_get_user_fav_ticket( $userid ) {
	$fav_list = array();
	$result = get_user_meta( $userid, '_rtbiz_hd_fav_tickets' );
	if ( ! empty( $result ) ) {
		foreach( $result as $postid ){
			$post_status = get_post_status( $postid );
			if ( ! in_array( $post_status, array( 'auto-draft', 'trash' ) ) ){
				$fav_list[] = $postid;
			}
		}
		$fav_list = array_filter( $fav_list );
		$fav_list = array_unique( $fav_list );
	}

	return $fav_list;
}

/**
 * get subscribe ticket of user
 *
 * @param $current_userid
 *
 * @return mixed
 */
function rtbiz_hd_get_user_subscribe_ticket( $current_userid ) {
	global $wpdb;
	$lenght = strlen( (string) $current_userid );
	$current_userid_str = '"' . $current_userid .'"';
	$sql = $wpdb->prepare( "SELECT $wpdb->posts.ID FROM $wpdb->postmeta, $wpdb->posts where $wpdb->postmeta.post_id = $wpdb->posts.ID and $wpdb->postmeta.meta_key = '_rtbiz_hd_subscribe_to' and ( $wpdb->postmeta.meta_value like '%s' or $wpdb->postmeta.meta_value like '%s' ) ", "%s:$lenght:$current_userid_str%", "%i:$current_userid%" );
	return $wpdb->get_col( $sql );
}

function rtbiz_hd_delete_user_fav_ticket( $user_id, $postid ) {
	delete_user_meta( $user_id, '_rtbiz_hd_fav_tickets', $postid );
}

function rtbiz_hd_save_adult_ticket_meta( $post_id, $pref ) {
	update_post_meta( $post_id, '_rtbiz_hd_ticket_adult_content', $pref );
}

function rtbiz_hd_get_adult_ticket_meta( $post_id ) {
	return get_post_meta( $post_id, '_rtbiz_hd_ticket_adult_content', true );
}

function rtbiz_hd_create_new_ticket_title( $key, $post_id ) {
	//if ( rtbiz_is_email_template_addon_active() && rtbiz_is_email_template_on( Rtbiz_HD_Module::$post_type ) ) {
		//$redux = rtbiz_hd_get_redux_settings();
		//$value = $redux[ $key ];
	//} else {
		$value = rtbiz_hd_get_default_email_template( $key );
	//}
	return rtbiz_hd_generate_email_title( $post_id, $value );
}

function rtbiz_hd_get_email_signature_settings() {
	$redux = rtbiz_hd_get_redux_settings();
	if ( isset( $redux['rthd_enable_signature'] ) && 1 == $redux['rthd_enable_signature'] && isset( $redux['rthd_email_signature'] ) ) {
		return wp_kses( $redux['rthd_email_signature'], array(
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array(),
			),
			'br' => array(),
			'em' => array(),
			'strong' => array(),
		) );
	}
	return '';
}

function rtbiz_hd_get_auto_response_message() {
	$redux = rtbiz_hd_get_redux_settings();
	if ( isset( $redux['rthd_enable_auto_response'] ) && 1 == $redux['rthd_enable_auto_response'] && isset( $redux['rthd_auto_response_message'] ) ) {
		return wp_kses( $redux['rthd_auto_response_message'], array(
			'a' => array(
				'href' => array(),
				'title' => array(),
				'target' => array(),
			),
			'br' => array(),
			'em' => array(),
			'strong' => array(),
		) );
	}
	return '';
}

function rtbiz_hd_generate_email_title( $post_id, $title ) {
	$redux = rtbiz_hd_get_redux_settings();
	$title = str_replace( '{module_name}', Rtbiz_HD_Module::$name, $title );
	$title = str_replace( '{ticket_id}', $post_id, $title );
	$title = str_replace( '{ticket_title}', html_entity_decode( get_the_title( $post_id ), ENT_COMPAT, 'UTF-8' ), $title );

	if ( false !== strpos( $title, '{products_name}' ) ) {
		global $rtbiz_products;
		$product = '';
		$products = array();
		if ( ! empty( $rtbiz_products ) ) {
			$products = wp_get_post_terms( $post_id, Rt_Products::$product_slug );
		}
		if ( ! $products instanceof WP_Error && ! empty( $products ) ) {
			$product_names = wp_list_pluck( $products, 'name' );
			$product = implode( ' ', $product_names );
		}
		$title = str_replace( '{products_name}', $product, $title );
	}
	return $title;
}

function rtbiz_hd_render_comment( $comment, $user_edit, $type = 'right', $echo = true ) {
	ob_start();
	$cap               = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'author' ); //todo: find employee users if then only visible
	$staffonly         = current_user_can( $cap );
	$comment_type_text = '';
	$comment_type_class = '';
	$sensitive         = true;
	$is_staff_followup = false;
	switch ( $comment->comment_type ) {
		case Rtbiz_HD_Import_Operation::$FOLLOWUP_BOT:
			$sensitive = false;
			if ( $user_edit ) {
				$comment_type_text = 'Bot';
				$comment_type_class = 'Bot';
			}
			$user_edit = false;
			break;
		case Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC:
			$sensitive = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_sensitive', true ) == 1 ? true : false;
			break;
		case Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF:
			if ( $staffonly ) {
				$comment_type_text = 'Staff Note';
				$comment_type_class = 'staff_only';
				$is_staff_followup = true;
				$sensitive = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_sensitive', true ) == 1 ? true : false;
			} else {
				ob_end_flush();
				if ( ! $echo ) {
					return '';
				}
				return;
			}
			break;
		default:
			$sensitive = false;
			break;
	}

	$side_class = ( 'right' == $type ) ? 'rthd-self' : ( ( 'left' == $type ) ? 'rthd-other' : '' );
	?>
	<li class="<?php echo $side_class . ' editable  ' . $comment_type_class; ?> <?php echo $sensitive ? 'sensitive' : ''; ?>"
	    id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>">
		<div class="avatar">
			<?php echo get_avatar( $comment->comment_author_email, 48 ); ?>
		</div>
		<div class="rthd-messages">
			<div class="followup-information clearfix"> <?php
				if ( current_user_can( $cap ) ) {
					$commentAuthorLink = '<a class="rthd-ticket-author-link" href="' . rtbiz_hd_biz_user_profile_link( $comment->comment_author_email ) . '">' . $comment->comment_author . '</a>';
				} else {
					$commentAuthorLink = $comment->comment_author;
				} ?>

				<span
					title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>">
					<?php echo( ( $comment->comment_author == '' ) ? $comment->comment_author_email : $commentAuthorLink ); ?>
				</span>
				<time
					title="<?php echo esc_attr( mysql2date( get_option( 'date_format' ), $comment->comment_date ) . ' at ' . mysql2date( get_option( 'time_format' ), $comment->comment_date, true ) ); ?>"
					datetime="<?php echo esc_attr( $comment->comment_date ); ?>"><?php
					if ( $user_edit ) { ?>
						<a href="#" class="editfollowuplink">Edit</a> | <?php
						$data = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_original_email', true );
						if ( ! empty( $data ) ) {
							$href = get_post_permalink( $comment->comment_post_ID ) . '?show_original=true&comment-id=' . $comment->comment_ID; ?>
							<a href="<?php echo $href; ?>" class="show-original-email" target="_blank"> Show original
								email</a> | <?php
						}
					}
					if ( true == $comment_type_text ) {
						echo "<span class='comment_type'> $comment_type_text </span> | ";
					} ?>
					<?php echo '<a class="followup-hash-url" id="followup-' . $comment->comment_ID . '" href="#followup-' . $comment->comment_ID . '" >' . esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ) . ' ago </a>'; ?>
				</time>
			</div>
			<input id="followup-id" type="hidden" value="<?php echo esc_attr( $comment->comment_ID ); ?>">
			<input id="followup-type" type="hidden" value="<?php echo esc_attr( $comment->comment_type ); ?>">
			<input id="followup-senstive" type="hidden" value="<?php echo esc_attr( $sensitive ); ?>">

			<?php
				$markdown_content = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_markdown_data', true );
				if ( !isset( $markdown_content ) || empty( $markdown_content ) ){
					$markdown_content = $comment->comment_content;
				}
			?>
			<div class="rthd-comment-content" data-rthdcontent="<?php echo rtbiz_hd_data_rthd_content( esc_attr( $markdown_content ) ); ?>"><?php
				if ( isset( $comment->comment_content ) && $comment->comment_content != '' ) {
					$comment->comment_content = rtbiz_hd_content_filter( $comment->comment_content );
				}?>
				<p><?php echo $comment->comment_content; ?></p>
			</div><?php
			$comment_attechment = get_comment_meta( $comment->comment_ID, '_rtbiz_hd_attachment' );
			$comment_attechment = array_unique( $comment_attechment );
			if ( ! empty( $comment_attechment ) ) {
				?>
				<ul class="comment_attechment">
					<?php foreach ( $comment_attechment as $a ) { ?>
						<li>
							<?php
							$attachment = get_post( $a );
							rtbiz_hd_get_attchment_link_with_fancybox( $attachment, $comment->comment_ID );
							?>
						</li>
					<?php } ?>
				</ul> <?php
			}?>
		</div>
	</li>
	<?php
	$comment_html = ob_get_clean();
	if ( $echo ) {
		echo $comment_html;
	} else {
		return $comment_html;
	}
}

function rtbiz_hd_content_filter( $content ) {

	$content = balanceTags( $content, true );

	preg_match_all( '/<body\s[^>]*>(.*?)<\/body>/s', $content, $output_array );
	if ( count( $output_array ) > 0 && ! empty( $output_array[1] ) ) {
		$content = $output_array[1][0];
	}

	$offset = strpos( $content, ':: Reply Above This Line ::' );
	$content = substr( $content, 0, ( false === $offset ) ? strlen( $content ) : $offset );

	$content = balanceTags( $content, true );

	$content = Rtbiz_HD_Utils::force_utf_8( $content );

	return balanceTags( wpautop( wp_kses_post( balanceTags( make_clickable( $content ), true ) ) ), true );
}

function rtbiz_hd_content_filter_without_apautop( $content ) {

	$content = balanceTags( $content, true );

	preg_match_all( '/<body\s[^>]*>(.*?)<\/body>/s', $content, $output_array );
	if ( count( $output_array ) > 0 && ! empty( $output_array[1] ) ) {
		$content = $output_array[1][0];
	}

	$offset = strpos( $content, ':: Reply Above This Line ::' );
	$content = substr( $content, 0, ( false === $offset ) ? strlen( $content ) : $offset );

	$content = balanceTags( $content, true );

	$content = Rtbiz_HD_Utils::force_utf_8( $content );

	return balanceTags( wp_kses_post( balanceTags( make_clickable( $content ), true ) ), true );
}


function rtbiz_hd_admin_notice_dependency_installed() {
	$string = get_option( 'rtbiz_helpdesk_dependency_installed' );
	if ( ! empty( $string ) ) {
		?>
		<div class="updated">
			<p>
				<?php echo $string; ?>
				<a class="welcome-panel-close rthd-dependency-notice-closed" style="margin-left: 10px" href="#">Dismiss</a>
			</p>
		</div>
		<?php
		delete_option( 'rtbiz_helpdesk_dependency_installed' );
	}
}




/**
 * if rtbiz plugin is not installed or activated it gives notification to user to do so.
 *
 * @since 0.1
 */
function rtbiz_hd_admin_notice_dependency_not_installed() {
	$biz_installed = rtbiz_hd_is_plugin_installed( 'rtbiz' );
	$p2p_installed = rtbiz_hd_is_plugin_installed( 'posts-to-posts' );

	if ( ! $biz_installed || ! $p2p_installed ) {
		$msg = '';
		if ( ! $biz_installed && ! $p2p_installed ) {
			$msg = 'rtBiz and Posts 2 Posts';
		} else if ( ! $biz_installed ) {
			$msg = 'rtBiz';
		} else if ( ! $p2p_installed ) {
			$msg = 'Posts 2 Posts';
		}
		?>
		<div class="error rthd-plugin-not-installed-error">
			<?php $nonce = wp_create_nonce( 'rthd_install_plugin_rtbiz' ); ?>

			<p><b><?php _e( 'rtBiz Helpdesk:' ) ?></b> <?php _e( 'Click' ) ?> <a href="#"
																				 onclick="install_rthd_plugin( '<?php echo $msg ?>', 'rthd_install_plugin', '<?php echo $nonce ?>' )">here</a> <?php _e( 'to install ' . $msg . '.', RTBIZ_HD_TEXT_DOMAIN ) ?>
			</p>
		</div>
		<?php
	}
	$rtbiz_active = rtbiz_hd_is_plugin_active( 'rtbiz' );
	$p2p_active = rtbiz_hd_is_plugin_active( 'posts-to-posts' );
	if ( ( $biz_installed && ! $rtbiz_active ) || ( $p2p_installed && ! $p2p_active ) ) {
		$msg = '';
		if ( ( $biz_installed && ! $rtbiz_active ) && ( $p2p_installed && ! $p2p_active ) ) {
			$msg = 'rtBiz and Posts 2 Posts';
		} else if ( $biz_installed && ! $rtbiz_active ) {
			$msg = 'rtBiz';
		} else if ( $p2p_installed && ! $p2p_active ) {
			$msg = 'Posts 2 Posts';
		}

		$path = rtbiz_hd_get_path_for_plugin( 'rtbiz' );
		$nonce = wp_create_nonce( 'rthd_activate_plugin_' . $path );
		?>
		<div class="error rthd-plugin-not-active-error">
			<p><b><?php _e( 'rtBiz Helpdesk:' ) ?></b> <?php _e( 'Click' ) ?> <a href="#"
																				 onclick="activate_rthd_plugin( '<?php echo $msg ?>', 'rthd_activate_plugin', '<?php echo $nonce; ?>' )">here</a> <?php _e( 'to activate ' . $msg . '.', RTBIZ_HD_TEXT_DOMAIN ) ?>
			</p>
		</div>
		<?php
	}
}



function rtbiz_hd_get_comment_type( $comment_type_value ) {
	switch ( $comment_type_value ) {
		case Rtbiz_HD_Import_Operation::$FOLLOWUP_PUBLIC:
			return 'Public Reply';
			break;
		case Rtbiz_HD_Import_Operation::$FOLLOWUP_STAFF:
			return 'Staff Note';
			break;
		default:
			return 'undefined';
	}
}

function rtbiz_hd_edit_comment_type( $Comment_ID, $value ) {
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare( "UPDATE $wpdb->comments SET comment_type=%s WHERE comment_ID = %d", $value, $Comment_ID )
	);
}

function rtbiz_hd_status_markup( $pstatus ) {
	global $rtbiz_hd_module;
	$post_statuses = $rtbiz_hd_module->get_custom_statuses();
	$key = array_search( $pstatus, wp_list_pluck( $post_statuses, 'slug' ) );
	if ( false !== $key ) {
		$pstatus = ucfirst( $post_statuses[ $key ]['name'] );
		$tstatus = $post_statuses[ $key ]['slug'];
		return '<mark class="rt' . $tstatus . ' rthd-status tips" data-tip="' . $pstatus . '">' . $pstatus . '</mark>';
	}
	return '';
}

function rtbiz_hd_biz_user_profile_link( $email ) {
	$post = rtbiz_get_contact_by_email( $email );
	if ( ! empty( $post ) ) {
		return get_edit_post_link( $post[0]->ID );
	} else {
		return '#';
	}
}

/**
 * This function is used to get attachment url from comments only( not post)
 * used in ticket front page / attachment metabox to hide followup attachments
 */
function rtbiz_hd_get_attachment_url_from_followups( $postid ) {

	if ( empty( $postid ) ) {
		return array();
	}

	$attach_comments = get_comments( array(
		'post_id' => $postid,
		'fields' => 'ids',
		'meta_query' => array(
			array(
				'key' => '_rtbiz_hd_attachment',
				'compare' => 'EXISTS',
			),
		),
			) );
	$attach_cmt = array();
	foreach ( $attach_comments as $comment ) {
		$url_arr = get_comment_meta( $comment, '_rtbiz_hd_attachment' );
		foreach ( $url_arr as $url ) {
			$attach_cmt[] = $url;
		}
	}
	return $attach_cmt;
}

function rtbiz_hd_get_general_body_template( $body, $title, $post_id = '', $replyflag = false ) {
	$mail_template = apply_filters( 'rthd_email_template', 'email-template.php' );
	ob_start();
	rtbiz_hd_get_template( $mail_template, array( 'body' => $body, 'title' => $title, 'post_id' => $post_id, 'replyflag' => $replyflag ) );
	return ob_get_clean();
}

function rtbiz_hd_update_ticket_updated_by_user( $post_id, $user_id ) {
	update_post_meta( $post_id, '_rtbiz_hd_updated_by', $user_id );
}

/**
 * @param $str
 * @param $placeholder
 * @param $replacewith
 *
 * @return mixed
 */
function rtbiz_hd_replace_placeholder( $str, $placeholder, $replacewith ) {
	return str_replace( $placeholder, $replacewith, $str );
}

/**
 * Email login credentials to a newly-registered user for rtHelpdesk system.
 *
 * A new user registration notification is also sent to admin email.
 *
 * @param int    $user_id        User ID.
 * @param string $plaintext_pass Optional. The user's plaintext password. Default empty.
 */
function rtbiz_hd_wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
	global $wpdb, $wp_hasher;

	$user = get_userdata( $user_id );
	if ( empty( $user_id ) ) {
		return;
	}
	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	$message = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
	$message .= sprintf( __( 'E-mail: %s' ), $user->user_email ) . "\r\n";

	try {
		wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration' ), $blogname ), $message );
	} catch ( Exception $e ) {
	}

	if ( empty( $plaintext_pass ) ) {
		return; }

	$settings = rtbiz_hd_get_redux_settings();
	$module_label = 'Helpdesk';

	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	$reset_pass_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );

	$message = __( 'Howdy,' ) . "\r\n\r\n";
	$message .= sprintf( __( 'A new account on %s has been created for you.' ), $module_label ) . "\r\n\r\n";
	$message .= sprintf( __( 'Your username is: %s' ), $user->user_login ) . "\r\n";
	$message .= sprintf( __( 'Please visit following link to activate the account.' . "\r\n" . '%s' ), $reset_pass_link ) . "\r\n\r\n";
	$message .= __( 'Thanks.' ) . "\r\n" . __( 'Admin.' );

	try {
		wp_mail( $user->user_email, sprintf( __( 'Your New %s Account' ), $module_label ), $message );
	} catch ( Exception $e ) {
	}

}

function rtbiz_hd_get_blacklist_emails() {
	$redux = rtbiz_hd_get_redux_settings();
	$blacklist = array();
	if ( isset( $redux['rthd_blacklist_emails_textarea'] ) && ! empty( $redux['rthd_blacklist_emails_textarea'] ) ) {
		$blacklist = explode( "\n", $redux['rthd_blacklist_emails_textarea'] );
	}
	return array_filter( $blacklist );
}

/**
 * Update rtHelpdesk settings.
 * This function used after p2p_init hook with priority more than 30 before that ReduxFramework will be empty
 *
 * @param string    $option_name        Setting option name.
 * @param string    $option_value       Setting option value.
 */
function rtbiz_hd_set_redux_settings( $option_name, $option_value ) {
	global $rtbiz_hd_settings;
	$rtbiz_hd_settings->ReduxFramework->set( $option_name, $option_value );
}

/**
 * Get taxonomy diff.
 */
function rtbiz_hd_get_taxonomy_diff( $post_id, $tax_slug ) {

	$post_terms = wp_get_post_terms( $post_id, $tax_slug );
	$postterms = array_filter( $_POST['tax_input'][ $tax_slug ] );
	$termids = wp_list_pluck( $post_terms, 'term_id' );
	$diff = array_diff( $postterms, $termids );
	$diff2 = array_diff( $termids, $postterms );
	$diff_tax1 = array();
	$diff_tax2 = array();
	foreach ( $diff as $tax_id ) {
		$tmp = get_term_by( 'id', $tax_id, $tax_slug );
		$diff_tax1[] = $tmp->name;
	}

	foreach ( $diff2 as $tax_id ) {
		$tmp = get_term_by( 'id', $tax_id, $tax_slug );
		$diff_tax2[] = $tmp->name;
	}

	$diff = rtbiz_hd_text_diff( implode( ', ', $diff_tax2 ), implode( ', ', $diff_tax1 ) );

	return $diff;
}

/**
 * Check whether current user has contact connection to the ticket.
 */
function rtbiz_hd_is_ticket_contact_connection( $post_id ) {
	$flag = false;

	$current_user = get_user_by( 'id', get_current_user_id() );
	$ticket_contacts = rtbiz_get_post_for_contact_connection( $post_id, Rtbiz_HD_Module::$post_type );

	foreach ( $ticket_contacts as $ticket_contact ) {

		$contact_email = rtbiz_get_entity_meta( $ticket_contact->ID, 'contact_primary_email', true );

		if ( $current_user->user_email == $contact_email ) {
			$flag = true;
		}
	}

	return $flag;
}

/**
 * Check whether current user is subscribe to the ticket.
 */
function rtbiz_hd_is_ticket_subscriber( $post_id ) {
	$flag = false;

	$current_user = get_user_by( 'id', get_current_user_id() );

	$ticket_subscribers = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );

	if ( ! empty( $ticket_subscribers ) ) {
		if ( in_array( get_current_user_id(), $ticket_subscribers ) ) {
			$flag = true;
		}
	}

	return $flag;
}

/**
 * Display settings for setup weekdays and hours operation for day shift.
 */
function rtbiz_hd_auto_response_dayshift_view() {
	global $rtbiz_hd_auto_response;
	return $rtbiz_hd_auto_response->setting_dayshift_ui();
}

/**
 * Display settings for setup weekdays and hours operation for night shift.
 */
function rtbiz_hd_auto_response_daynightshift_view() {
	global $rtbiz_hd_auto_response;
	return $rtbiz_hd_auto_response->setting_daynightshift_ui();
}

function rtbiz_hd_filter_emails( $allemails ) {
	//subscriber diff
	$rtCampUser = Rtbiz_HD_Utils::get_hd_rtcamp_user();
	$hdUser = array();
	foreach ( $rtCampUser as $rUser ) {
		$hdUser[ $rUser->user_email ] = $rUser->ID;
	}
	$subscriber = array();
	$allemail = array();
	foreach ( $allemails as $mail ) {
		if ( ! array_key_exists( $mail['address'], $hdUser ) ) {
			if ( ! rtbiz_hd_check_email_blacklisted( $mail['address'] ) ) {
				$allemail[] = $mail;
			}
		} else {
			$subscriber[] = $hdUser[ $mail['address'] ];
		}
	}
	return array( 'subscriber' => $subscriber, 'allemail' => $allemail );
}

function rtbiz_hd_check_email_blacklisted( $testemail ) {
	$black_list_emails = rtbiz_hd_get_blacklist_emails();
	if ( ! empty( $black_list_emails ) ) {
		foreach ( $black_list_emails as $email ) {
			$matching_string = str_replace( '*', '\/*', preg_replace( '/\s+/', '', $email ) );
			if ( empty( $matching_string ) ) {
				continue;
			}
			if ( preg_match( '/' . $matching_string . '/', $testemail ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * get mailbox reading is enable or disable
 * @return bool
 */
function rtbiz_hd_is_enable_mailbox_reading() {
	$redux = rtbiz_hd_get_redux_settings();
	$flag = ( isset( $redux['rthd_enable_mailbox_reading'] ) && 1 == $redux['rthd_enable_mailbox_reading'] );
	return $flag;
}

/**
 * get meta value for product
 * @param $key
 * @param string $term_id
 * @return bool|mixed
 */
function rtbiz_hd_get_product_meta( $key, $term_id = '' ) {

	if ( empty( $term_id ) && isset( $_GET['tag_ID'] ) ) {
		$term_id = $_GET['tag_ID'];
	}

	if ( empty( $term_id ) ) {
		return false;
	}

	$term_meta = Rt_Lib_Taxonomy_Metadata\get_term_meta( $term_id, '_' . Rt_Products::$product_slug . '_meta', true );
	if ( ! empty( $term_meta ) ) {
		if ( ! empty( $key ) ) {
			return isset( $term_meta[ $key ] ) ? $term_meta[ $key ] : false;
		} else {
			return $term_meta;
		}
	}
	return false;
}

/**
 * @param $key
 * @param $value
 * @param $term_id
 * update product meta
 */
function rtbiz_hd_update_product_meta( $key, $value, $term_id ) {
	if ( empty( $term_id ) ) {
		return false;
	}
	$old_value = Rt_Lib_Taxonomy_Metadata\get_term_meta( $term_id, '_' . Rt_Products::$product_slug . '_meta', true );
	$new_value = $old_value;
	$new_value[ $key ] = $value;
	Rt_Lib_Taxonomy_Metadata\update_term_meta( $term_id, '_' . Rt_Products::$product_slug . '_meta', $new_value, $old_value );
}

/**
 * Returns boolean of setting for reply via email is unable or not
 * @return bool
 */
function rtbiz_hd_get_reply_via_email() {
	$redux = rtbiz_hd_get_redux_settings();
	return ( isset( $redux['rthd_reply_via_email'] ) && 1 == $redux['rthd_reply_via_email'] );
}

/**
 * get user is employee or not for helpdesk
 * @param $userid
 *
 * @return mixed
 */
function rtbiz_hd_is_our_employee( $userid ) {
	return rtbiz_is_our_employee( $userid, RTBIZ_HD_TEXT_DOMAIN );
}

/**
 * Get tickets
 *
 * @param $key : created_by, assignee, subscribe, order
 * @param $value
 *
 * @return bool
 */
function rtbiz_hd_get_tickets( $key, $value, $offset = 0, $limit = 0, $nopaging = true ) {

	$key_array = array( 'created_by', 'assignee', 'subscribe', 'order', 'favourite' );

	if ( ! in_array( $key, $key_array ) ) {
		return false;
	}

	$args = array(
		'post_type' => Rtbiz_HD_Module::$post_type,
		'post_status' => 'any',
		'orderby' => 'modified',
	);

	if ( $nopaging ) {
		$args['nopaging'] = true;
	} else {
		if ( ! empty( $offset ) ) {
			$args['offset'] = $offset;
		}
		if ( ! empty( $limit ) ) {
			$args['posts_per_page'] = $limit;
		}
	}

	if ( 'created_by' == $key ) {
		$value = rtbiz_hd_convert_into_userid( $value );
		$args['meta_query'] = array(
			array(
				'key' => '_rtbiz_hd_created_by',
				'value' => $value,
			),
		);
	} elseif ( 'assignee' == $key ) {
		$value = rtbiz_hd_convert_into_userid( $value );
		$args['author'] = $value;
	} elseif ( 'subscribe' == $key ) {
		// check given user is staff or contact
		if ( rtbiz_hd_is_our_employee( $value, RTBIZ_HD_TEXT_DOMAIN ) ) {
			$value = rtbiz_hd_convert_into_userid( $value );
			$args['meta_query'] = array(
				array(
					'key' => '_rtbiz_hd_subscribe_to',
					'value' => ':' . $value . ',',
					'compare' => 'LIKE',
				),
			);
		} else {
			$value = rtbiz_hd_convert_into_useremail( $value );
			$person = rtbiz_get_contact_by_email( $value );
			if ( isset( $person ) && ! empty( $person ) ) {
				$args['connected_items'] = $person[0]->ID;
				$args['connected_type'] = Rtbiz_HD_Module::$post_type . '_to_' . rtbiz_post_type_name( 'contact' );
			}
		}
	} elseif ( 'order' == $key ) {
		if ( is_object( $value ) ) {
			$value = $value->ID;
		}
		$user_id = rtbiz_hd_get_user_id_from_order_id( $value );
		if ( is_admin() ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => '_rtbiz_hd_order_id',
					'value' => $value,
					'compare' => '=',
				),
				array(
					'key' => '_rtbiz_hd_created_by',
					'value' => $user_id,
					'compare' => '=',
				),
			);
		} else {
			$args['meta_query'] = array(
				array(
					'key' => '_rtbiz_hd_order_id',
					'value' => $value,
					'compare' => '=',
				),
			);
		}
	} elseif ( 'favourite' == $key ) {
		$fav = rtbiz_hd_get_user_fav_ticket( $value );
		// if there is no fav tickets post__in query will not work so return empty array
		if ( empty( $fav ) ) {
			return array();
		} else {
			$args['post__in'] = $fav;
		}
	}
	if ( ! empty( $args ) )
		return new WP_Query( $args );
	return false;
}

/**
 * To convert useremail or user object to userid
 *
 * @param $value : Should be object [ Post type ] object or email
 *
 * @return mixed
 */
function rtbiz_hd_convert_into_userid( $value ) {
	if ( ! is_numeric( $value ) ) {
		if ( is_string( $value ) ) {
			$value = get_user_by( 'email', $value );
			$value = $value->ID;
		} elseif ( ! is_object( $value ) ) {
			$value = $value->ID;
		}
	}
	return $value;
}

/**
 * To convert userid or user object to user email
 *
 * @param $value it shoud be userid or user object
 *
 * @return mixed
 */
function rtbiz_hd_convert_into_useremail( $value ) {
	if ( ! is_string( $value ) ) {
		if ( is_numeric( $value ) ) {
			$value = get_user_by( 'id', $value );
			$value = $value->email;
		} elseif ( ! is_object( $value ) ) {
			$value = $value->email;
		}
	}
	return $value;
}

/*
 * get attachment link with fancybox
 */

function rtbiz_hd_get_attchment_link_with_fancybox( $attachment, $post_id = '', $echo = true ) {
	if ( empty( $attachment ) ) {
		return '';
	}
	ob_start();
	$attachment_url = wp_get_attachment_url( $attachment->ID );
	$original_url = $attachment_url;
	$extn = rtbiz_get_attchment_extension( $attachment_url );
	$class = 'rthd_attachment fancybox';
	if ( rtbiz_is_google_doc_supported_type( $attachment->post_mime_type, $extn ) ) {
		$attachment_url = rtbiz_google_doc_viewer_url( $attachment_url );
		$class .= ' fancybox.iframe';
	} elseif ( rtbiz_hd_is_fancybox_supported_type( $extn ) ) {
		$class .= ' fancybox.iframe';
	}
	?>
	<a class="<?php echo $class; ?>" rel="rthd_attachment_<?php echo ! empty( $post_id ) ? $post_id : $attachment->post_parent; ?>"
	   data-downloadlink="<?php echo esc_url( $original_url ); ?>"
	   title="<?php echo balanceTags( $attachment->post_title ); ?>"
	   href="<?php echo esc_url( $attachment_url ); ?>"> <img
			height="20px" width="20px"
			src="<?php echo esc_url( RTBIZ_HD_URL. 'public/file-type/' . $extn . '.png' ); ?>"/>
		<span title="<?php echo balanceTags( $attachment->post_title ); ?>"> 	<?php echo esc_attr( strlen( balanceTags( $attachment->post_title ) ) > 40 ? substr( balanceTags( $attachment->post_title ), 0, 40 ) . '...' : balanceTags( $attachment->post_title ) ); ?> </span>
	</a>
	<?php
	$attachment_html = ob_get_clean();
	if ( $echo ) {
		echo $attachment_html;
	} else {
		return $attachment_html;
	}
}

/**
 * check givent extension is support by facncy box for iframe
 * @param string $extation
 *
 * @return bool
 */
function rtbiz_hd_is_fancybox_supported_type( $extation = '' ) {
	$extation_arr = array(
		'mp4',
	'mp3',
	);
	return in_array( $extation, $extation_arr );
}

/**
 * Get helpdesk email template from redux if plugin is not active / on (from rtbiz) then use default email templates
 * @param $key
 *
 * @return mixed
 */
function rtbiz_hd_get_email_template_body( $key ) {
	/*if ( rtbiz_is_email_template_addon_active() && rtbiz_is_email_template_on( Rtbiz_HD_Module::$post_type ) ) {
		$redux = rtbiz_hd_get_redux_settings();
		return $redux[ $key ];
	}*/
	return rtbiz_hd_get_default_email_template( $key );
}

/**
 * Helpdesk default templates
 * @param string $key
 * @param bool   $all
 *
 * @return array|bool
 */
function rtbiz_hd_get_default_email_template( $key = '', $all = false ) {
	$redux = array();

	//Ticket default title
	$redux['rthd_new_ticket_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_ticket_email_title_contacts'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_ticket_email_title_group'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_ticket_email_title_assignee'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_ticket_email_title_subscriber'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_update_ticket_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_ticket_reassign_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_ticket_reassign_email_title_old_assignee'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_followup_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_followup_email_title_private'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_update_followup_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_update_followup_email_title_private'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_delete_followup_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_delete_followup_email_title_private'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_ticket_subscribe_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_ticket_unsubscribe_email_title'] = '[{module_name} #{ticket_id}] {ticket_title}';
	$redux['rthd_new_followup_email_title_staff_note'] = '[{module_name} #{ticket_id}] {ticket_title}';

	// Ticket template default body

	/*	<div style="font-size: 16px; line-height: 26px; color: #888888;">
	           Visibility: <div style="color: #333333; ">{visibility_diff}</div>
			</div>*/
	$redux['rthd_email_template_followup_add'] = '
		<div style="color: #888888; font-size: 14px;">
				New followup added by <strong>{followup_author}</strong>. {ticket_link}
		</div>
		<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; ">
				{followup_content}
			</div>
		<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_followup_add_staff_note'] = '
		<div style="color: #888888; font-size: 14px;">
				New staff note added by <strong>{followup_author}</strong>. {ticket_link}
		</div>
		<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; ">
				{followup_content}
			</div>
		<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_followup_add_private'] = '
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0;" />
			<div style="color: #333333; line-height: 26px; font-size: 16px; ">
				A <strong>private</strong> {followup_type}followup has been added by <strong>{followup_author}</strong>. {ticket_link}
			</div>
		    <hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_followup_deleted_private'] = '
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0;" />
				<div style="color: #333333; line-height: 26px; font-size: 16px; ">
					A <strong>private</strong> {followup_type}followup is deleted by <Strong>{followup_deleted_by}</Strong> {ticket_link}
				</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_followup_deleted'] = '
			<div style="color: #888888; font-size: 14px;">
				A followup is deleted by <Strong>{followup_deleted_by}</Strong> {ticket_link}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0;" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; ">
				{followup_content}
			</div>

			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px;" />';

	$redux['rthd_email_template_followup_updated_private'] = '
			<div style="color: #888888; font-size: 14px;">
				A <strong>private</strong> {followup_type}followup has been edited by <strong>{followup_updated_by}</strong>. {ticket_link}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_followup_updated'] = '
			<div style="color: #888888; font-size: 14px;">
				A followup updated by <strong>{followup_updated_by}.</strong> {ticket_link}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color: #888888;">
				The changes are as follows: <div style="color: #333333; ">{followup_diff}</div>
			</div>
		    <hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_new_ticket_created_author'] = '
			<div style="color: #333333; font-size: 16px; line-height: 26px">
				Thank you for opening a new support ticket. We will look into your request and respond as soon as possible.
				<br/>
				{ticket_link}
			</div>
		    <hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
		    <div style="font-size: 14px; color:#888888; line-height: 24px; ">
		        {ticket_body}
		    </div>
		    <hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_new_ticket_created_contacts'] = '
			<div style="color: #888888; font-size: 14px;">
				A new support ticket created by <strong>{ticket_author}</strong>. You have been subscribed to this ticket.{ticket_link}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; ">
				{ticket_body}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_new_ticket_created_group_notification'] = '
			<div style="color: #888888; font-size: 14px;  margin-bottom: 25px;">
				A new support ticket created. {ticket_link}
			</div>
			<div style="font-size: 16px; line-height: 26px; color: #888888; ">
				Product: <strong style="color: #333333;">{ticket_products}</strong>
			</div>
		    <div style="font-size: 16px; line-height: 26px; color: #888888;">
		        Created by: <strong style="color: #333333; ">{ticket_author}</strong>
	        </div>
	        <div style="font-size: 16px; line-height: 26px; color: #888888;">
	            Assigned to: <strong style="color: #333333;">{ticket_assignee}</strong>
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; ">
				{ticket_body}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_new_ticket_created_assignee'] = '
			<div style="color: #888888; font-size: 14px;  margin-bottom: 25px;">
				A new support ticket created by {ticket_author} is assigned to you. </strong> {ticket_link}
			</div>
			<div style="font-size: 16px; line-height: 26px; color: #888888;">
				Product: <strong style="color: #333333;">{ticket_products}</strong>
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; " >
				{ticket_body}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';

	$redux['rthd_email_template_new_ticket_created_subscriber'] = '
			<div style="color: #888888; font-size: 14px; margin-bottom: 25px;">
				A new support ticket created by <strong>{ticket_author}</strong>. You have been subscribed to this ticket. {ticket_link}
			</div>
		    <div style="font-size: 16px; line-height: 26px; color: #888888;">
				Product: <strong style="color: #333333;">{ticket_products}</strong>
			</div>
			<div style="font-size: 16px; line-height: 26px; color: #888888;">
				Assigned to: <strong style="color: #333333;">{ticket_assignee}</strong>
			</div>
		    <hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; ">
			    {ticket_body}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin-top: 25px" />';
	// If product not assigned use -
	$redux['rthd_email_template_ticket_subscribed'] = '
			<div style="color: #333333; font-size: 16px; line-height: 26px; ">
				{ticket_subscribers} been subscribed to this ticket {ticket_link}
			</div>';
	$redux['rthd_email_template_ticket_unsubscribed'] = '
			<div style="color: #333333; font-size: 16px; line-height: 26px; ">
				{ticket_unsubscribers} been un-subscribed from this ticket{ticket_link}
			</div> ';
	$redux['rthd_email_template_ticket_reassigned_old_assignee'] = '
			<div style="color: #333333; font-size: 16px; line-height: 26px; ">
				You are no longer responsible for this ticket.{ticket_link}
			</div>';

	$redux['rthd_email_template_ticket_reassigned_new_assignee'] = '
			<div style="color: #333333; font-size: 16px; line-height: 26px; ">
				A ticket is reassigned to {new_ticket_assignee}. {ticket_link}
			</div>';

	$redux['rthd_email_template_ticket_updated'] = '
			<div style="color: #888888; font-size: 14px;">
				Ticket updated by <strong>{ticket_updated_by}</strong>. {ticket_link}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />
			<div style="font-size: 16px; line-height: 26px; color:#333333; margin: 25px 0">
				{ticket_difference}
			</div>
			<hr style="background-color: #eee; border: 0 none; height: 1px; margin: 25px 0" />';

	if ( ! empty( $key ) && isset( $redux[ $key ] ) ) {
		return $redux[ $key ];
	}
	if ( $all ) {
		return $redux;
	}
	return false;
}

/**
 * Search user who don't have helpdesk access
 *
 * @param      $query
 * @param bool $domain_search
 * @param bool $count
 *
 * @return mixed
 */
function rtbiz_hd_search_non_helpdesk_users( $query, $domain_search = false, $count = false ) {
	global $wpdb;
	$helpdesk_users = rtbiz_hd_get_helpdesk_user_ids();
	$q = '';
	if ( ! empty( $helpdesk_users ) ) {
		$q = 'AND ID not IN (' . implode( ',', $helpdesk_users ) . ')';
	}
	if ( $count ) {
		if ( $domain_search ) {
			return $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users WHERE (user_email like '%@{$query}')" . $q );
		} else {
			return $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users WHERE (user_email like '%{$query}%' or display_name like '%{$query}%' or user_nicename like '%{$query}%')" . $q );
		}
	} else {
		if ( $domain_search ) {
			return $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users WHERE (user_email like '%@{$query}')" . $q );
		} else {
			return $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users WHERE (user_email like '%{$query}%' or display_name like '%{$query}%' or user_nicename like '%{$query}%')" . $q );
		}
	}
}

/**
 * @return array
 * get Helpdesk user ids
 */
function rtbiz_hd_get_helpdesk_user_ids() {
	global $wpdb, $rtbiz_acl_model;
	$admins = get_users( array( 'role' => 'Administrator', 'fields' => 'ID' ) );
	//  $result  =$wpdb->get_col("SELECT DISTINCT(acl.userid) FROM ".$rtbiz_acl_model->table_name." acl where acl.module = '".RT_BIZ_HD_TEXT_DOMAIN."' and acl.permission != 0 ");
	$result = $wpdb->get_col( 'SELECT DISTINCT(acl.userid) FROM ' . $rtbiz_acl_model->table_name . ' as acl INNER JOIN ' . $wpdb->prefix . 'p2p as p2p on ( acl.userid = p2p.p2p_to ) INNER JOIN ' . $wpdb->posts . " as posts on (p2p.p2p_from = posts.ID )  where acl.module =  '" . RTBIZ_HD_TEXT_DOMAIN . "' and acl.permission != 0 and p2p.p2p_type = '" . rtbiz_get_contact_post_type() . "_to_user' and posts.post_status= 'publish' and posts.post_type= '" . rtbiz_get_contact_post_type() . "' " );
	return array_merge( $admins, $result );
}

/**
 * @param      $user
 * @param      $access_role
 * Give user access of helpdesk and create rtbiz user if not exist and set user to support group
 * @param int  $team_term_id
 *
 * @return bool
 *
 */
function rtbiz_hd_give_user_access( $user, $access_role, $team_term_id = 0 ) {
	global $rtbiz_acl_model;

	if ( ! is_object( $user ) ) {
		$user = get_userdata( $user );
	}
	// get rtbiz user and set term to that user

	$contact_ID = rtbiz_export_contact( $user->ID );
	if ( ! empty( $team_term_id ) ) {
		wp_set_post_terms( $contact_ID, array( $team_term_id ), Rtbiz_Teams::$slug );
	}
	// Add helpdesk role in custome table
	$data = array(
		'userid' => $user->ID,
		'module' => RTBIZ_HD_TEXT_DOMAIN,
		'groupid' => $team_term_id,
		'permission' => $access_role,
	);
	$rtbiz_acl_model->add_acl( $data );
	// Add rtbiz role in custom table
	$data = array(
		'userid' => $user->ID,
		'module' => RTBIZ_TEXT_DOMAIN,
		'groupid' => $team_term_id,
		'permission' => $access_role,
	);
	$rtbiz_acl_model->add_acl( $data );

	if ( ! empty( $contact_ID ) && empty( $team_term_id ) ) {
		$user_permissions = get_post_meta( $contact_ID, 'rtbiz_profile_permissions', true );
		$value = array(
			RTBIZ_HD_TEXT_DOMAIN  => $access_role,
			RTBIZ_TEXT_DOMAIN => $access_role,
		);
		// check existing if exist merge with that
		if ( ! empty( $user_permissions ) && is_array( $user_permissions ) ) {
			$value = array_merge( $value, $user_permissions );
		}
		update_post_meta( $contact_ID, 'rtbiz_profile_permissions', $value );
		update_post_meta( $contact_ID, 'rtbiz_is_staff_member', 'yes' );
	}
	return true;
}

/**
 * get helpdesk default support team
 * used in importer
 * @return mixed|void
 */
function rtbiz_hd_get_default_support_team() {
	$isSyncOpt = get_option( 'rtbiz_hd_default_support_team' );
	if ( empty( $isSyncOpt ) ) {
		$term = wp_insert_term( 'General Support', // the term
			Rtbiz_Teams::$slug // the taxonomy
		);
		if ( ! empty( $term ) ) {
			$module_permissions = get_site_option( 'rtbiz_acl_module_permissions' );
			$module_permissions[ RTBIZ_HD_TEXT_DOMAIN ][ $term['term_id'] ] = Rtbiz_Access_Control::$permissions['author']['value'];
			update_site_option( 'rtbiz_acl_module_permissions', $module_permissions );
			update_option( 'rtbiz_hd_default_support_team', $term['term_id'] );
			$isSyncOpt = $term['term_id'];
		}
	}
	return $isSyncOpt;
}

/**
 * check wizard completed
 * @return bool
 */
function rtbiz_hd_check_wizard_completed() {
	$option = get_option( 'rtbiz_hd_setup_wizard_option' );
	if ( ! empty( $option ) && 'true' == $option ) {
		return true;
	}
	return false;
}

/**
 * store helpdesk setting manually
 * @param $key
 * @param $val
 */
function rtbiz_hd_set_redux_setting( $key, $val ) {
	global $rtbiz_hd_settings;
	$rtbiz_hd_settings->ReduxFramework->set( $key, $val );
	$GLOBALS[ Rtbiz_HD_Settings::$hd_opt ] = get_option( Rtbiz_HD_Settings::$hd_opt, array() );
}

function rtbiz_hd_get_redux_post_settings( $post ) {
	// NOTE : Make modifications for what value to return.
	if ( ! isset( $GLOBALS['redux_helpdesk_settings'] ) ) {
		$GLOBALS['redux_helpdesk_settings'] = get_option( 'redux_helpdesk_settings', array() );
	}
	$data = wp_parse_args( get_post_meta( $post->ID, 'redux_helpdesk_settings', true ), $GLOBALS['redux_helpdesk_settings'] );

	return $GLOBALS['redux_helpdesk_settings'];
}

function rtbiz_hd_ticket_import_logs() {
	global $rtbiz_hd_logs;
	$rtbiz_hd_logs->ui();
}

function rtbiz_hd_mailbox_setup_view( $isredirect = true ) {
	global $rtbiz_mailBox;
	if ( $isredirect ){
		$rtbiz_mailBox->render_mailbox_setting_page( rtbiz_sanitize_module_key( RTBIZ_HD_TEXT_DOMAIN ), add_query_arg( array( 'post_type' => Rtbiz_HD_Module::$post_type, 'page' => Rtbiz_HD_Settings::$page_slug ), admin_url( 'edit.php' ) ) );
	} else {
		$rtbiz_mailBox->render_mailbox_setting_page( rtbiz_sanitize_module_key( RTBIZ_HD_TEXT_DOMAIN ) );
	}

}

function rtbiz_hd_gravity_importer_view() {
	$module_key = rtbiz_sanitize_module_key( RTBIZ_HD_TEXT_DOMAIN );
	return rtbiz_gravity_importer_view( $module_key );
}

function rtbiz_hd_activation_view() {
	do_action( 'rthelpdesk_addon_license_details' );
}

function rtbiz_hd_no_access_redux() {
	return '<p class="description">Currently there are no settings available for you.</p>';
}

/**
 * Sidebar for admin
 *
 * @access public
 * @global type $bp_media
 *
 * @param       void
 *
 * @return void
 */
function rtbiz_hd_admin_sidebar() {
	do_action( 'rthd_before_default_admin_widgets' );
	$current_user = wp_get_current_user();
	$message = sprintf( __( 'I use @rtbizwp on %s', RTBIZ_HD_TEXT_DOMAIN ), home_url() ); ?>

	<div class="metabox-holder bp-media-metabox-holder rthd-sidebar">
		<div id="spread-the-word" class="postbox">
			<h3 class="hndle"><span>Spread the Word</span></h3>
			<div class="inside">
				<div class="rthd-social-share" id="social">
					<p><a href="<?php echo 'http://twitter.com/home/?status=' . $message; ?>" class="button twitter" target= "_blank" title="<?php _e( 'Post to Twitter Now', RTBIZ_HD_TEXT_DOMAIN ) ?>"><?php _e( 'Post to Twitter', RTBIZ_HD_TEXT_DOMAIN ) ?><span class="dashicons dashicons-twitter"></span></a></p>
					<p><a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=http://rtcamp.com/helpdesk/' ); ?>" class="button facebook" target="_blank" title="<?php  _e( 'Share on Facebook Now', RTBIZ_HD_TEXT_DOMAIN ) ?>"><?php  _e( 'Share on Facebook', RTBIZ_HD_TEXT_DOMAIN ) ?><span class="dashicons dashicons-facebook"></span></a></p>
					<p><a href="<?php echo esc_url( 'https://wordpress.org/support/view/plugin-reviews/rtbiz?rate=5#postform' ); ?>" class="button wordpress" target= "_blank" title="<?php _e( 'Rate rtBiz on Wordpress.org', RTBIZ_HD_TEXT_DOMAIN ) ?>"><?php _e( 'Rate on Wordpress.org', RTBIZ_HD_TEXT_DOMAIN ) ?><span class="dashicons dashicons-wordpress"></span></a></p>
					<p><a href="<?php echo sprintf( '%s', 'https://rtcamp.com/feed/' ) ?>"  class="button rss" target="_blank" title="<?php _e( 'Subscribe to our Feeds', RTBIZ_HD_TEXT_DOMAIN )  ?>"><?php _e( 'Subscribe to our Feeds', RTBIZ_HD_TEXT_DOMAIN ) ?><span class="dashicons dashicons-rss"></span></a></p>
				</div>
			</div>
		</div>

		<div id="branding" class="postbox">
			<h3 class="hndle"><span>Subscribe</span></h3>
			<div class="inside">
				<form action="<?php echo esc_url( 'http://rtcamp.us1.list-manage1.com/subscribe/post?u=85b65c9c71e2ba3fab8cb1950&amp;id=9e8ded4470' ); ?>" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<div class="mc-field-group">
						<input type="email" value="<?php echo $current_user->user_email; ?>" name="EMAIL" placeholder="Email" class="required email" id="mce-EMAIL">
						<input style="display:none;" type="checkbox" checked="checked" value="1" name="group[1721][1]" id="mce-group[1721]-1721-0">
						<div id="mce-responses" class="clear">
							<div class="response" id="mce-error-response" style="display:none"></div>
							<div class="response" id="mce-success-response" style="display:none"></div>
						</div>
						<input type="submit" value="<?php _e( 'Subscribe', RTBIZ_HD_TEXT_DOMAIN ) ?>" name="subscribe" id="mc-embedded-subscribe" class="button">
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php do_action( 'rthd_after_default_admin_widgets' );
}

/**
 * get contact of setup page
 */
function rtbiz_hd_get_setup_team_ui() {
	global $rtbiz_hd_setup_wizard;
	ob_start();
	$rtbiz_hd_setup_wizard -> setup_team( false );
	return ob_get_clean();
}

/**
 *
 * Returns user id from Order
 * @param $order_id
 *
 * @return mixed
 */
function rtbiz_hd_get_user_id_from_order_id( $order_id ) {
	$post_type = get_post_type( $order_id );
	if ( is_object( $order_id ) ) {
		$order_id = $order_id->ID;
	}
	if ( 'edd_payment' == $post_type ) {
		// find in edd
		return get_post_meta( $order_id, '_edd_payment_user_id', true );
	} else if ( 'shop_order' == $post_type ) {
		// find in woo
		return get_post_meta( $order_id, '_customer_user', true );
	}
}

function rtbiz_hd_compare_wp_post( $objA, $objB ) {
	if ( $objA->ID == $objB->ID ) {
		return 0;
	} return ( $objA->ID > $objB->ID )?1:-1;
}

function rtbiz_hd_update_assignee( $postid, $post_author ) {
	$user = get_userdata( $post_author );
	if ( empty( $postid ) || empty( $post_author ) || ! $user ) {
		return false;
	}
	$ticket = array(
		'ID'          => $postid,
		'post_author' => $post_author,
	);
	wp_update_post( $ticket );
	global $rtbiz_hd_ticket_index_model;
	$rtbiz_hd_ticket_index_model->update_ticket_assignee( $post_author, $postid );
	return true;
}

/**
* Function to get all the file extensions 
* which supported by the wordpress
*/
function rtbiz_hd_get_supported_extensions() {
	$mimes = get_allowed_mime_types();
	foreach ( $mimes as $type => $mime ) {
		$seprate_extensions = explode( '|', $type );
		foreach ( $seprate_extensions as $seprate_extension ) {
			$types[] = $seprate_extension;
		}
	}

	return $types;
}

function rtbiz_hd_data_rthd_content( $textarea ){
	$lines = explode("\n", $textarea);
	foreach( $lines as $line ){
		echo $line;
	}
}