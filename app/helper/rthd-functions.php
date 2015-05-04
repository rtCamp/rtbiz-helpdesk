<?php
/**
 *   Helper functions for rt-helpdesk
 * @author udit
 */

/**
 * rt-helpdesk Functions
 * used to render template

 */
function rthd_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	$located = rthd_locate_template( $template_name, $template_path, $default_path );

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
function rthd_locate_template( $template_name, $template_path = '', $default_path = '' ) {

	global $rt_wp_hd;
	if ( ! $template_path ) {
		$template_path = $rt_wp_hd->templateURL;
	}
	if ( ! $default_path ) {
		$default_path = RT_HD_PATH_TEMPLATES;
	}

	// Look within passed path within the theme - this is priority
	$template = locate_template( array( trailingslashit( $template_path ) . $template_name, $template_name ) );

	// Get default template
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters( 'rthd_locate_template', $template, $template_name, $template_path );
}

/**
 * @param $taxonomy
 * sanitize taxonomy name
 *
 * @return mixed|string
 */
function rthd_sanitize_taxonomy_name( $taxonomy ) {
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
function rthd_attribute_taxonomy_name( $name ) {
	return 'rtbiz_hd_' . rthd_sanitize_taxonomy_name( $name );
}

/**
 * @param $name
 *  adding prefix for RtBiz post type
 *
 * @return string
 */
function rtbiz_post_type_name( $name ) {
	return 'rt_' . rthd_sanitize_taxonomy_name( $name );
}

/**
 * @param $name
 *  adding prefix for HelpDesk post type
 *
 * @return string
 */
function rthd_post_type_name( $name ) {
	return 'rtbiz_hd_' . rthd_sanitize_taxonomy_name( $name );
}


/**
 * @param string $attribute_store_as
 *  get all attributes
 *
 * @return array
 */
function rthd_get_all_attributes( $attribute_store_as = '' ) {
	global $rt_hd_attributes_model;
	$attrs = $rt_hd_attributes_model->get_all_attributes();

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
function rthd_get_attributes( $post_type, $attribute_store_as = '' ) {
	global $rt_hd_attributes_relationship_model, $rt_hd_attributes_model;
	$relations = $rt_hd_attributes_relationship_model->get_relations_by_post_type( $post_type );
	$attrs     = array();

	foreach ( $relations as $relation ) {
		$attrs[] = $rt_hd_attributes_model->get_attribute( $relation->attr_id );
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
function rthd_post_term_to_string( $postid, $taxonomy, $termsep = ',' ) {
	$termsArr = get_the_terms( $postid, $taxonomy );
	$tmpStr   = '';
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
function rthd_extract_key_from_attributes( $attr ) {
	return $attr->attribute_name;
}

/**
 * check if given email is system email or not
 *
 * @param $email
 *
 * @return bool
 */
function rthd_is_system_email( $email ) {
	global $rt_mail_settings;
	$google_acs = $rt_mail_settings->get_user_google_ac();

	foreach ( $google_acs as $ac ) {
		$ac->email_data = unserialize( $ac->email_data );
		$ac_email          = filter_var( $ac->email_data['email'], FILTER_SANITIZE_EMAIL );
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
function rthd_get_all_participants( $ticket_id ) {
	$ticket       = get_post( $ticket_id );
	$participants = array();
	if ( isset( $ticket->post_author ) ) {
		$participants[] = $ticket->post_author;
	}
	$subscribers  = get_post_meta( $ticket_id, '_rtbiz_hd_subscribe_to', true );
	$participants = array_merge( $participants, $subscribers );

	//	TODO
	//	$contacts = wp_get_post_terms( $ticket_id, rthd_attribute_taxonomy_name( 'contacts' ) );
	//	foreach ( $contacts as $contact ) {
	//		$user_id = get_term_meta( $contact->term_id, 'user_id', true );
	//		if(!empty($user_id)) {
	//			$participants[] = $user_id;
	//		}
	//	}

	$comments = get_comments( array( 'order' => 'DESC', 'post_id' => $ticket_id, 'post_type' => $ticket->post_type ) );
	$all_p = array();
	foreach ( $comments as $comment ) {
		$p  = '';
		$to = get_comment_meta( $comment->comment_ID, '_email_to', true );
		if ( ! empty( $to ) ) {
			$p .= $to . ',';
		}
		$cc = get_comment_meta( $comment->comment_ID, '_email_cc', true );
		if ( ! empty( $cc ) ) {
			$p .= $cc . ',';
		}
		$bcc = get_comment_meta( $comment->comment_ID, '_email_bcc', true );
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
function rthd_get_ticket_table_name() {

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
function rthd_get_user_ids( $user ) {
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
function rthd_update_post_term_count( $terms, $taxonomy ) {
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
function rthd_encrypt_decrypt( $string ) {

	$string_length    = strlen( $string );
	$encrypted_string = '';

	/**
	 * For each character of the given string generate the code
	 */
	for ( $position = 0; $position < $string_length; $position ++ ) {
		$key                      = ( ( $string_length + $position ) + 1 );
		$key                      = ( 255 + $key ) % 255;
		$get_char_to_be_encrypted = substr( $string, $position, 1 );
		$ascii_char               = ord( $get_char_to_be_encrypted );
		$xored_char               = $ascii_char ^ $key; //xor operation
		$encrypted_char           = chr( $xored_char );
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
function rthd_text_diff( $left_string, $right_string, $args = null ) {
	$defaults = array( 'title' => '', 'title_left' => '', 'title_right' => '' );
	$args     = wp_parse_args( $args, $defaults );

	$left_string  = normalize_whitespace( $left_string );
	$right_string = normalize_whitespace( $right_string );
	$left_lines   = explode( "\n", $left_string );
	$right_lines  = explode( "\n", $right_string );

	$renderer  = new Rt_HD_Email_Diff();
	$text_diff = new Text_Diff( $left_lines, $right_lines );
	$diff      = $renderer->render( $text_diff );

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
function rthd_set_html_content_type() {
	return 'text/html';
}

function rthd_get_unique_hash_url( $ticket_id ) {
	global $rt_hd_module;
	$labels = $rt_hd_module->labels;
	$rthd_unique_id = get_post_meta( $ticket_id, '_rtbiz_hd_unique_id', true );
	return trailingslashit( site_url() ) . strtolower( $labels['name'] ) . '/?rthd_unique_id=' . $rthd_unique_id;
}

function rthd_is_unique_hash_enabled() {
	$settings = rthd_get_redux_settings();
	if ( ! empty( $settings['rthd_enable_ticket_unique_hash'] ) ) {
		return true;
	}
	return false;
}


// Setting ApI
function rthd_get_redux_settings() {
	if ( ! isset( $GLOBALS['redux_helpdesk_settings'] ) ) {
		$GLOBALS['redux_helpdesk_settings'] = get_option( 'redux_helpdesk_settings', array() );
	}

	return $GLOBALS['redux_helpdesk_settings'];
}

function rthd_my_mail_from( $email ) {
	$settings = rthd_get_redux_settings();
	return $settings['rthd_outgoing_email_from_address'];
}

// user notification preference
function rthd_get_user_notification_preference( $user_id, $email = '' ) {
	if ( empty( $email ) ){
		$user = get_user_by('id',$user_id);
		$email = $user->user_email;
	}
	$post = rt_biz_get_contact_by_email($email);
	if ( ! empty( $post[0] ) ) {
		$pref = Rt_Entity::get_meta( $post[ 0 ]->ID, 'rthd_receive_notification', true );
	}

	//	$pref = get_user_meta( $user_id, 'rthd_notification_pref', true );
	if ( empty( $pref ) ) {
		$pref = 'no';
	}
	return $pref;
}

//adult filter redux setting
function rthd_get_redux_adult_filter(){
	$settings = rthd_get_redux_settings();
	if ( ! empty( $settings['rthd_enable_ticket_adult_content'] ) ) {
		return true;
	}
	return false;
}

//adult content preference
function rthd_get_user_adult_preference( $user_id, $email = '' ) {
	if ( empty( $email ) ){
		$user = get_user_by( 'id', $user_id );
		$email = $user->user_email;
	}
	$post = rt_biz_get_contact_by_email( $email );

	if ( ! empty( $post[0] ) ){
		$pref = Rt_Entity::get_meta( $post[0]->ID, 'rthd_contact_adult_filter', true);
	}
	//  Old adult pref meta key
	//	$pref = get_user_meta( $user_id, 'rthd_adult_pref', true );
	if ( empty( $pref ) ) {
		$pref = 'no';
	}
	return $pref;
}

function rthd_add_user_fav_ticket( $userid, $postid ){
	add_user_meta( $userid, '_rthd_fav_tickets', $postid);
}

function rthd_get_user_fav_ticket( $userid ){
	$result = get_user_meta( $userid, '_rthd_fav_tickets' );
	if ( ! empty( $result ) ){
		$result = array_filter( $result );
		$result = array_unique( $result );
	}
	return $result;
}

function rthd_delete_user_fav_ticket( $user_id, $postid ){
	delete_user_meta( $user_id, '_rthd_fav_tickets', $postid );
}

function rthd_save_adult_ticket_meta( $post_id, $pref ){
	update_post_meta( $post_id, '_rthd_ticket_adult_content', $pref );
}

function rthd_get_adult_ticket_meta( $post_id ){
	return get_post_meta( $post_id, '_rthd_ticket_adult_content', true );
}

function rthd_create_new_ticket_title( $key, $post_id ){
	if (  rt_biz_is_email_template_addon_active() && rt_biz_is_email_template_on( Rt_HD_Module::$post_type ) ){
		$redux = rthd_get_redux_settings();
		$value = $redux[ $key ];
	} else {
		$value = rthd_get_default_email_template( $key );
	}
	return rthd_generate_email_title( $post_id, $value );
}

function rthd_get_email_signature_settings(){
	$redux = rthd_get_redux_settings();
	if ( isset( $redux['rthd_enable_signature'] ) && 1 == $redux['rthd_enable_signature'] && isset( $redux['rthd_email_signature'] ) ) {
		return $redux['rthd_email_signature'];
	}
	return '';
}

function rthd_get_auto_response_message(){
	$redux = rthd_get_redux_settings();
	if ( isset( $redux['rthd_enable_auto_response'] ) && 1 == $redux['rthd_enable_auto_response'] && isset( $redux['rthd_auto_response_message'] ) ) {
		return $redux['rthd_auto_response_message'];
	}
	return '';
}


function rthd_generate_email_title( $post_id, $title ) {
	$redux = rthd_get_redux_settings();
	$title = str_replace( '{module_name}', Rt_HD_Module::$name  , $title );
	$title = str_replace( '{ticket_id}', $post_id, $title );
	$title = str_replace( '{ticket_title}',html_entity_decode( get_the_title( $post_id ), ENT_COMPAT, 'UTF-8' ), $title );

	if ( strpos( $title, '{offerings_name}' ) !== false ) {
		global $rtbiz_offerings;
		$offering = '';
		$products = array();
		if ( ! empty( $rtbiz_offerings ) ) {
			$products = wp_get_post_terms( $post_id, Rt_Offerings::$offering_slug );
		}
		if ( ! $products instanceof WP_Error && ! empty( $products ) ) {
			$offering_names = wp_list_pluck( $products, 'name' );
			$offering = implode( ' ', $offering_names );
		}
		$title = str_replace( '{offerings_name}', $offering, $title );
	}
	return $title;
}

function rthd_render_comment( $comment, $user_edit, $type = 'right', $echo = true ) {

	ob_start();

	$cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' ); //todo: find employee users if then only visible
	$staffonly  = current_user_can( $cap );
	$private_text = '';
	$display_private_comment_flag = false;
	$is_comment_private = false;
	switch ( $comment->comment_type ) {
		case Rt_HD_Import_Operation::$FOLLOWUP_BOT:
			$display_private_comment_flag = true;
			if ( $user_edit ){
				$is_comment_private = true;
				$private_text = 'Bot';
			}
			$user_edit = false;
			break;
		case Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC:
			$display_private_comment_flag = true;
			$is_comment_private = false;
			break;
		case Rt_HD_Import_Operation::$FOLLOWUP_SENSITIVE:
			if ( $user_edit || ( get_current_user_id() == get_post_meta( $comment->comment_post_ID, '_rtbiz_hd_created_by' ,true ) ) ){
				$display_private_comment_flag = true;
			}
			$private_text = 'Sensitive';
			$is_comment_private = true;
			break;
		case Rt_HD_Import_Operation::$FOLLOWUP_STAFF:
			if ( $staffonly ){
				$display_private_comment_flag = true;
			}
			else {
				ob_end_flush();
				if ( ! $echo ) {
					return '';
				}
				return;
			}
			$private_text = 'Staff only';
			$is_comment_private = true;
			break;
		default:
			$display_private_comment_flag = true;
			break;
	}


	$side_class = ( $type == 'right' ) ? 'self' : ( ( $type == 'left' ) ? 'other' : '' );
	$editable_class = ( $display_private_comment_flag ) ? 'editable' : '';

	?>
	<li class="<?php echo $side_class . ' ' . $editable_class . ' ' . ( ( $display_private_comment_flag ) ? '' : 'private-comment-item' ); ?>" id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>">

		<div class="avatar">
			<?php echo get_avatar( $comment->comment_author_email, 48 ); ?>
		</div>
		<div class="messages <?php echo ( $display_private_comment_flag ) ? '' : 'private-comment-display'; ?>">
    <div class="followup-information">
	    <?php
	    if ( current_user_can( $cap ) ){
		    $commentAuthorLink = '<a class="rthd-ticket-author-link" href="'.rthd_biz_user_profile_link( $comment->comment_author_email ).'">'.$comment->comment_author.'</a>';
	    }
	    else{
		    $commentAuthorLink = $comment->comment_author;
	    }
	    ?>

        <span title="<?php echo esc_attr( ( $comment->comment_author_email == '' ) ? $comment->comment_author_IP : $comment->comment_author_email ); ?>"><?php echo ( ( $comment->comment_author == '' ) ? $comment->comment_author_email : $commentAuthorLink ); ?> </span>
            <time title="<?php echo esc_attr( mysql2date( get_option( 'date_format' ), $comment->comment_date ) . ' at '.mysql2date( get_option('time_format') , $comment->comment_date , true )); ?>" datetime="<?php echo esc_attr( $comment->comment_date ); ?>">
                <?php if ($user_edit){
                    ?>
                    <a href="#" class="editfollowuplink">Edit</a> |
	                <?php
	                $data = get_comment_meta( $comment->comment_ID, 'rt_hd_original_email', true );
	                if ( ! empty( $data ) ) {
		                $href =  get_post_permalink( $comment->comment_post_ID ). '?show_original=true&comment-id='.$comment->comment_ID ;
		                ?>
		                <a href="<?php echo $href;?>" class="show-original-email" target="_blank"> Show original email</a> |
                <?php }
                }
                if ( $is_comment_private == true ){
	                echo "<span class='private_comment_span'> $private_text </span> | ";
                }
                ?>
	            <?php echo '<a class="followup-hash-url" id="followup_'.$comment->comment_ID.'" href="#followup_'.$comment->comment_ID.'" >'.esc_attr( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ) . ' ago </a>';?>

            </time>
            </div>
			<input id="followup-id" type="hidden" value="<?php echo esc_attr( $comment->comment_ID ); ?>">
			<input id="is-private-comment" type="hidden" value="<?php echo esc_attr( $comment->comment_type); ?>">
			<div class="rthd-comment-content" data-content="<?php echo ( $display_private_comment_flag ) ? esc_attr( $comment->comment_content ): '' ; ?>">
			<?php if( $display_private_comment_flag ) {
				if ( isset( $comment->comment_content ) && $comment->comment_content != '' ) {
					$comment->comment_content = rthd_content_filter( $comment->comment_content );
				}
			?>
				<p><?php echo $comment->comment_content; ?></p>
			<?php } else { ?>
				<p><?php _e( 'This followup has been marked private.', RT_HD_TEXT_DOMAIN ); ?></p>
			<?php } ?>
			</div>
			<?php
			if ( $display_private_comment_flag ) {
				$comment_attechment = get_comment_meta( $comment->comment_ID, "attachment" );
				$comment_attechment = array_unique( $comment_attechment );
				if ( ! empty( $comment_attechment ) ) { ?>
					<ul class="comment_attechment">
						<?php foreach ( $comment_attechment as $a ) { ?>
							<li>
								<?php
								$attachment = get_post( $a );
								rt_hd_get_attchment_link_with_fancybox( $attachment, $comment->comment_ID ); ?>
							</li>
						<?php } ?>
					</ul>
				<?php }
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

function rthd_content_filter( $content ) {

	$content = balanceTags( $content, true );

	preg_match_all( '/<body\s[^>]*>(.*?)<\/body>/s', $content, $output_array );
	if ( count( $output_array ) > 0 && ! empty( $output_array[1] ) ) {
		$content = $output_array[1][0];
	}

	$offset = strpos( $content, '[!-------REPLY ABOVE THIS LINE-------!]' );
	$content = substr( $content, 0 , ( $offset === false ) ? strlen( $content ) : $offset );

	$content = balanceTags( $content, true );

	$content = Rt_HD_Utils::force_utf_8( $content );

	return balanceTags( wpautop( wp_kses_post( balanceTags( make_clickable( $content ), true ) ) ), true );
}


/**
 * check for rt biz dependency and if it does not find any single dependency then it returns false
 *
 * @since 0.1
 *
 * @return bool
 */
function rthd_check_plugin_dependecy() {

	global $rthd_plugin_check;
	$rthd_plugin_check = array(
		'rtbiz' => array(
			'project_type' => 'all',
			'name' => esc_html__( 'WordPress for Business.', RT_HD_TEXT_DOMAIN ),
			'active' => class_exists( 'Rt_Biz' ),
			'filename' => 'index.php',
		),
		'posts-to-posts' => array(
			'project_type' => 'all',
			'name' => esc_html__( 'Create many-to-many relationships between all types of posts.', RT_HD_TEXT_DOMAIN ),
			'active' => class_exists( 'P2P_Autoload' ),
			'filename' => 'posts-to-posts.php',
		),
	);

	$flag = true;

	if ( ! class_exists( 'Rt_Biz' ) || ! did_action( 'rt_biz_init' ) ) {
		$flag = false;
	}

	if ( ! $flag ) {
		add_action( 'admin_enqueue_scripts', 'rthd_plugin_check_enque_js' );
		add_action( 'wp_ajax_rthd_activate_plugin', 'rthd_activate_plugin_ajax' );
		add_action( 'wp_ajax_rthd_install_plugin', 'rthd_install_plugin_ajax' );
//		add_action( 'admin_notices', 'rthd_admin_notice_dependency_not_installed' );
		add_action( 'admin_init', 'rthd_install_dependency' );
	}

	return $flag;
}

/**
 * install depedency
 */
function rthd_install_dependency(){
	$biz_installed = rthd_is_plugin_installed( 'rtbiz' ) ;
	$p2p_installed = rthd_is_plugin_installed( 'posts-to-posts' ) ;
	$isRtbizActionDone = false;
	$isPtopActionDone = false;
	$string = '';

	if ( ! $biz_installed || ! $p2p_installed ) {
		if (  ! $biz_installed ){
			rthd_install_plugin( 'rtbiz' );
			$isRtbizActionDone = true;
			$string = 'installed and activated <strong>rtBiz</strong> plugin.';
		}
		if ( ! $p2p_installed ){
			rthd_install_plugin( 'posts-to-posts' );
			$isPtopActionDone = true;
			$string = 'installed and activated <strong>posts to posts</strong> plugin.';
		}
		if ( $isRtbizActionDone && $isPtopActionDone ){
			$string = 'installed and activated <strong>rtBiz</strong> plugin and <strong>posts to posts</strong> plugin.';
		}
	}

	$rtbiz_active = rthd_is_plugin_active( 'rtbiz' );
	$p2p_active = rthd_is_plugin_active( 'posts-to-posts' );
	if ( ! $rtbiz_active || ! $p2p_active ) {
		if ( ! $p2p_active ){
			$p2ppath = rthd_get_path_for_plugin( 'posts-to-posts' );
			rthd_activate_plugin( $p2ppath );
			$isRtbizActionDone = true;
			$string = 'activated <strong>posts to posts</strong> plugin.';
		}
		if ( ! $rtbiz_active ){
			$rtbizpath = rthd_get_path_for_plugin( 'rtbiz' );
			rthd_activate_plugin( $rtbizpath );
			$isPtopActionDone = true;
			$string = 'activated <strong>rtBiz</strong> plugin.';
		}
		if ( $isRtbizActionDone && $isPtopActionDone ){
			$string = 'activated <strong>rtBiz</strong> plugin and <strong>posts to posts</strong> plugin.';
		}
	}

	if ( !empty( $string ) ){
		$string = 'rtBiz Helpdesk has also  ' . $string;
		update_option( 'rtbiz_helpdesk_dependency_installed', $string );
	}

	if ( rthd_check_wizard_completed() ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rthd-rtbiz_hd_ticket-dashboard' ) );
	} else {
		wp_safe_redirect( admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rthd-setup-wizard' ) );
	}
}

function rthd_admin_notice_dependency_installed(){
	$string = get_option( 'rtbiz_helpdesk_dependency_installed' );
	if ( ! empty( $string ) ){ ?>
		<div class="updated">
			<p>
				<?php echo $string ;?>
				<a class="welcome-panel-close" style="margin-left: 10px" href="<?php echo  admin_url( 'edit.php?post_type=rtbiz_hd_ticket&page=rthd-setup-wizard&close_notice=true' ); ?>">Dismiss</a>
			</p>
		</div>
	<?php
	}
}

function rthd_install_plugin_ajax(){
		if ( empty( $_POST['plugin_slug'] ) ) {
			die( __( 'ERROR: No slug was passed to the AJAX callback.', RT_HD_TEXT_DOMAIN ) );
		}
		check_ajax_referer( 'rthd_install_plugin_rtbiz');

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			die( __( 'ERROR: You lack permissions to install and/or activate plugins.', RT_HD_TEXT_DOMAIN ) );
		}
		$biz_installed = rthd_is_plugin_installed( 'rtbiz' ) ;
		$p2p_installed = rthd_is_plugin_installed( 'posts-to-posts' ) ;

		if( ! $p2p_installed ){
			rthd_install_plugin('posts-to-posts');
		}
		if ( ! $biz_installed ){
			rthd_install_plugin( 'rtbiz' );
		}
		echo 'true';
		die();
}

function rthd_install_plugin( $plugin_slug ){
	include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

	$api = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug, 'fields' => array( 'sections' => false ) ) );

	if ( is_wp_error( $api ) ) {
		die( sprintf( __( 'ERROR: Error fetching plugin information: %s', RT_HD_TEXT_DOMAIN ), $api->get_error_message() ) );
	}

	if ( ! class_exists( 'Plugin_Upgrader' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	}

	if ( ! class_exists( 'Rt_HD_Plugin_Upgrader_Skin' ) ) {
		require_once( RT_HD_PATH . 'app/admin/class-rt-hd-plugin-upgrader-skin.php' );
	}

	$upgrader = new Plugin_Upgrader( new Rt_HD_Plugin_Upgrader_Skin( array(
		                                                                  'nonce'  => 'install-plugin_' . $plugin_slug,
		                                                                  'plugin' => $plugin_slug,
		                                                                  'api'    => $api,
	                                                                  ) ) );

	$install_result = $upgrader->install( $api->download_link );

	if ( ! $install_result || is_wp_error( $install_result ) ) {
		// $install_result can be false if the file system isn't writable.
		$error_message = __( 'Please ensure the file system is writable', RT_HD_TEXT_DOMAIN );

		if ( is_wp_error( $install_result ) ) {
			$error_message = $install_result->get_error_message();
		}

		die( sprintf( __( 'ERROR: Failed to install plugin: %s', RT_HD_TEXT_DOMAIN ), $error_message ) );
	}

	$activate_result = activate_plugin(rthd_get_path_for_plugin( $plugin_slug ) );
	if ( is_wp_error( $activate_result ) ) {
		die( sprintf( __( 'ERROR: Failed to activate plugin: %s', RT_HD_TEXT_DOMAIN ), $activate_result->get_error_message() ) );
	}
}

function rthd_plugin_check_enque_js() {
	wp_enqueue_script( 'rtbiz-hd-plugin-check', RT_HD_URL . 'app/assets/javascripts/rthd_plugin_check.js', '', false, true );
	wp_localize_script( 'rtbiz-hd-plugin-check', 'rthd_ajax_url', admin_url( 'admin-ajax.php' ) );
}

/**
 * if rtbiz plugin is not installed or activated it gives notification to user to do so.
 *
 * @since 0.1
 */
function rthd_admin_notice_dependency_not_installed() {
	$biz_installed = rthd_is_plugin_installed( 'rtbiz' ) ;
	$p2p_installed = rthd_is_plugin_installed( 'posts-to-posts' ) ;

	if ( ! $biz_installed || ! $p2p_installed ) {
		$msg = '';
		if ( ! $biz_installed && ! $p2p_installed ){
			$msg = 'rtBiz and Posts 2 Posts';
		}
		else if( ! $biz_installed ){
			$msg = 'rtBiz';
		}else if (! $p2p_installed ){
			$msg = 'Posts 2 Posts';
		}
		?>
		<div class="error rthd-plugin-not-installed-error">
<?php			$nonce = wp_create_nonce( 'rthd_install_plugin_rtbiz' ); ?>

			<p><b><?php _e( 'rtBiz Helpdesk:' ) ?></b> <?php _e( 'Click' ) ?> <a href="#"
			                                                                     onclick="install_rthd_plugin('<?php echo $msg ?>','rthd_install_plugin','<?php echo $nonce ?>')">here</a> <?php _e( 'to install '. $msg . '.', RT_HD_TEXT_DOMAIN ) ?>
			</p>
		</div>
	<?php }
		$rtbiz_active = rthd_is_plugin_active( 'rtbiz' );
		$p2p_active = rthd_is_plugin_active( 'posts-to-posts' );
		if ( ( $biz_installed && ! $rtbiz_active ) || ( $p2p_installed && ! $p2p_active ) ) {
			$msg  = '';
			if ( ( $biz_installed && ! $rtbiz_active ) && ( $p2p_installed && ! $p2p_active ) ) {
				$msg = 'rtBiz and Posts 2 Posts';
			}
			else if ( $biz_installed && ! $rtbiz_active ){
				$msg = 'rtBiz';
			}
			else if ( $p2p_installed && ! $p2p_active ){
				$msg = 'Posts 2 Posts';
			}

			$path  = rthd_get_path_for_plugin( 'rtbiz' );
			$nonce = wp_create_nonce( 'rthd_activate_plugin_' . $path );
		?>
		<div class="error rthd-plugin-not-active-error">
			<p><b><?php _e( 'rtBiz Helpdesk:' ) ?></b> <?php _e( 'Click' ) ?> <a href="#"
			                                                                     onclick="activate_rthd_plugin('<?php echo $msg ?>','rthd_activate_plugin','<?php echo $nonce; ?>')">here</a> <?php _e( 'to activate '.$msg.'.', RT_HD_TEXT_DOMAIN ) ?>
			</p>
		</div>
	<?php }

}

function rthd_get_path_for_plugin( $slug ) {
	global $rthd_plugin_check;
	$filename = ( ! empty( $rthd_plugin_check[ $slug ]['filename'] ) ) ? $rthd_plugin_check[ $slug ]['filename'] : $slug . '.php';

	return $slug . '/' . $filename;
}

function rthd_is_plugin_active( $slug ) {
	global $rthd_plugin_check;
	if ( empty( $rthd_plugin_check[ $slug ] ) ) {
		return false;
	}

	return $rthd_plugin_check[ $slug ]['active'];
}

function rthd_is_plugin_installed( $slug ) {
	global $rthd_plugin_check;
	if ( empty( $rthd_plugin_check[ $slug ] ) ) {
		return false;
	}

	if ( rthd_is_plugin_active( $slug ) || file_exists( WP_PLUGIN_DIR . '/' . rthd_get_path_for_plugin( $slug ) ) ) {
		return true;
	}

	return false;
}

/**
 * ajax call for active plugin
 */
function rthd_activate_plugin_ajax() {
	if ( empty( $_POST['path'] ) ) {
		die( __( 'ERROR: No slug was passed to the AJAX callback.', RT_HD_TEXT_DOMAIN ) );
	}
	$rtbizpath = rthd_get_path_for_plugin('rtbiz');
	check_ajax_referer( 'rthd_activate_plugin_' . $rtbizpath  );

	if ( ! current_user_can( 'activate_plugins' ) ) {
		die( __( 'ERROR: You lack permissions to activate plugins.', RT_HD_TEXT_DOMAIN ) );
	}
	$rtbiz_active = rthd_is_plugin_active( 'rtbiz' );
	$p2p_active = rthd_is_plugin_active( 'posts-to-posts' );
	if ( ! $p2p_active ){
		$p2ppath = rthd_get_path_for_plugin('posts-to-posts');
		rthd_activate_plugin( $p2ppath  );
	}

	if ( ! $rtbiz_active ){
		rthd_activate_plugin( $rtbizpath );
	}


	echo 'true';
	die();
}

/**
 * @param $plugin_path
 * ajax call for active plugin calls this function to active plugin
 */
function rthd_activate_plugin( $plugin_path ) {

	$activate_result = activate_plugin( $plugin_path );
	if ( is_wp_error( $activate_result ) ) {
		die( sprintf( __( 'ERROR: Failed to activate plugin: %s', RT_HD_TEXT_DOMAIN ), $activate_result->get_error_message() ) );
	}
}


function rthd_get_comment_type( $comment_type_value ){
    switch( $comment_type_value ){
        case Rt_HD_Import_Operation::$FOLLOWUP_PUBLIC:
            return 'Default';
            break;
        case Rt_HD_Import_Operation::$FOLLOWUP_SENSITIVE:
            return 'Sensitive';
            break;
        case Rt_HD_Import_Operation::$FOLLOWUP_STAFF:
            return 'Only Staff';
            break;
        default:
            return 'undefined';
    }
}


function rthd_edit_comment_type( $Comment_ID, $value ){
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare( "UPDATE $wpdb->comments SET comment_type=%s WHERE comment_ID = %d", $value, $Comment_ID )
	);

}

function rthd_status_markup( $pstatus ){
	global $rt_hd_module;
	$style         = 'padding: 5px; border: 1px solid black; border-radius: 5px;';
	$post_statuses = $rt_hd_module->get_custom_statuses();
	foreach ( $post_statuses as $status ) {
		if ( $status[ 'slug' ] == $pstatus ) {
			$pstatus = $status[ 'name' ];
			if ( ! empty( $status[ 'style' ] ) ) {
				$style = $status[ 'style' ];
			}
			$pstatus = ucfirst( $pstatus );
			break;
		}
	}
	if ( ! empty( $pstatus ) ) {
		return '<mark style="' . $style .  '" class="' . $pstatus .  ' tips" data-tip="' . $pstatus .  '">' . $pstatus .  '</mark>';
	}
	return '';
}

function rthd_biz_user_profile_link( $email ){
	$post = rt_biz_get_contact_by_email( $email );
	if (!empty($post)){
		return get_edit_post_link($post[0]->ID);
	}
	else {
		return '#';
	}
}

/**
 * This function is used to get attachment url from comments only( not post)
 * used in ticket front page / attachment metabox to hide followup attachments
 */
function rthd_get_attachment_url_from_followups( $postid ){
	$attach_comments = get_comments( array(
		                                'post_id' => $postid,
		                                'fields' => 'ids',
		                                'meta_query' => array(
			                                array(
				                                'key'   => 'attachment',
				                                'compare' => 'EXISTS',
			                                ), ),
	                                ));
	$attach_cmt = array();
	foreach ( $attach_comments as $comment ){
		$url_arr = get_comment_meta($comment, 'attachment' );
		foreach ( $url_arr as $url ){
			$attach_cmt[] = $url;
		}
	}
	return $attach_cmt;
}

function rthd_get_general_body_template( $body, $title, $replyflag = false ){
	$mail_template = apply_filters( 'rthd_email_template', 'email-template.php' );
	ob_start();
	rthd_get_template( $mail_template, array( 'body' => $body, 'title' => $title ,'replyflag' => $replyflag) );
	return ob_get_clean();
}

function rthd_update_ticket_updated_by_user( $post_id, $user_id ){
	update_post_meta( $post_id, '_rtbiz_hd_updated_by',$user_id );
}

/**
 * @param $str
 * @param $placeholder
 * @param $replacewith
 *
 * @return mixed
 */
function rthd_replace_placeholder( $str, $placeholder, $replacewith ){
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
function rthd_wp_new_user_notification($user_id, $plaintext_pass = '') {
	global $wpdb, $wp_hasher;

	$user = get_userdata( $user_id );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";

	@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

	if ( empty($plaintext_pass) )
		return;

	$settings = rthd_get_redux_settings();
	$module_label = 'Helpdesk' ;

	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	$reset_pass_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login');

	$message  = __( 'Howdy,' ) . "\r\n\r\n";
	$message .= sprintf( __( 'A new account on %s has been created for you.' ), $module_label ) . "\r\n\r\n";
	$message .= sprintf( __( 'Your username is: %s' ), $user->user_login ) . "\r\n";
	$message .= sprintf( __( "Please visit following link to activate the account." . "\r\n" . "%s" ), $reset_pass_link) . "\r\n\r\n";
	$message .= __( 'Thanks.' ) . "\r\n" . __( 'Admin.' );

	wp_mail($user->user_email, sprintf(__('Your New %s Account'), $module_label), $message);
}

function rthd_get_blacklist_emails(){
	$redux = rthd_get_redux_settings();
	$blacklist = array();
	if ( isset( $redux['rthd_blacklist_emails_textarea'] ) && ! empty( $redux['rthd_blacklist_emails_textarea'] ) ){
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
 function rthd_set_redux_settings( $option_name, $option_value ) {
	global $rt_hd_redux_framework_Helpdesk_Config;
	$rt_hd_redux_framework_Helpdesk_Config->ReduxFramework->set( $option_name, $option_value );
}

/**
 * Get taxonomy diff.
 */
function rthd_get_taxonomy_diff( $post_id, $tax_slug ) {

	$post_terms = wp_get_post_terms( $post_id, $tax_slug );
	$postterms  = array_filter( $_POST['tax_input'][ $tax_slug ] );
	$termids    = wp_list_pluck( $post_terms, 'term_id' );
	$diff       = array_diff( $postterms, $termids );
	$diff2      = array_diff( $termids, $postterms );
	$diff_tax1  = array();
	$diff_tax2  = array();
	foreach ( $diff as $tax_id ) {
		$tmp          = get_term_by( 'id', $tax_id, $tax_slug );
		$diff_tax1[] = $tmp->name;
	}

	foreach ( $diff2 as $tax_id ) {
		$tmp          = get_term_by( 'id', $tax_id, $tax_slug );
		$diff_tax2[] = $tmp->name;
	}



	$diff = rthd_text_diff( implode( ', ', $diff_tax2 ), implode( ', ', $diff_tax1 ) );

	return $diff;
}

/**
 * Check whether current user has contact connection to the ticket.
 */
function rthd_is_ticket_contact_connection( $post_id ) {
	$flag = false;

	$current_user = get_user_by( 'id', get_current_user_id() );
	$ticket_contacts = rt_biz_get_post_for_contact_connection( $post_id, Rt_HD_Module::$post_type );

	foreach ( $ticket_contacts as $ticket_contact ) {

		$contact_email = rt_biz_get_entity_meta( $ticket_contact->ID, 'contact_primary_email', true );

		if( $current_user->user_email == $contact_email ) {
			$flag = true;
		}
	}

	return $flag;
}

/**
 * Check whether current user is subscribe to the ticket.
 */
function rthd_is_ticket_subscriber( $post_id ) {
	$flag = false;

	$current_user = get_user_by( 'id', get_current_user_id() );

	$ticket_subscribers = get_post_meta( $post_id, '_rtbiz_hd_subscribe_to', true );

	if( in_array( get_current_user_id(), $ticket_subscribers ) ) {
		$flag = true;
	}

	return $flag;
}

/**
 * Display settings for setup weekdays and hours operation for day shift.
 */
function rthd_auto_response_dayshift_view() {
	global $rt_hd_auto_response;
	return $rt_hd_auto_response->setting_dayshift_ui();
}

/**
 * Display settings for setup weekdays and hours operation for night shift.
 */
function rthd_auto_response_daynightshift_view() {
	global $rt_hd_auto_response;
	return $rt_hd_auto_response->setting_daynightshift_ui();
}

function rthd_filter_emails( $allemails ){
	//subscriber diff
	$rtCampUser         = Rt_HD_Utils::get_hd_rtcamp_user();
	$hdUser             = array();
	foreach ( $rtCampUser as $rUser ) {
		$hdUser[ $rUser->user_email ] = $rUser->ID;
	}
	$subscriber = array();
	$allemail = array();
	foreach ( $allemails as $mail ) {
		if ( ! array_key_exists( $mail['address'], $hdUser ) ) {
			if ( ! rt_hd_check_email_blacklisted( $mail['address'] ) ){
				$allemail[]= $mail;
			}
		} else {
			$subscriber[]= $hdUser[$mail['address']];
		}
	}
	return array( 'subscriber' => $subscriber, 'allemail' => $allemail );
}

function rt_hd_check_email_blacklisted( $testemail ){
	$black_list_emails = rthd_get_blacklist_emails();
	if ( ! empty( $black_list_emails ) ){
		foreach ( $black_list_emails as $email ){
			$matching_string = str_replace( '*', '\/*', preg_replace('/\s+/', '', $email ) );
			if ( empty( $matching_string )){
				continue;
			}
			if ( preg_match( '/'.$matching_string.'/', $testemail ) ){
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
function rthd_is_enable_mailbox_reading(){
	$redux = rthd_get_redux_settings();
	$flag = ( isset( $redux['rthd_enable_mailbox_reading']) && $redux['rthd_enable_mailbox_reading'] == 1 );
	return $flag;
}

/**
 * get meta value for offering
 * @param $key
 * @param string $term_id
 * @return bool|mixed
 */
function get_offering_meta( $key, $term_id = '' ){

	if ( empty( $term_id ) && isset( $_GET['tag_ID'] ) ){
		$term_id = $_GET['tag_ID'];
	}

	if ( empty( $term_id ) ) {
		return false;
	}

	$term_meta = Rt_Lib_Taxonomy_Metadata\get_term_meta( $term_id, Rt_Offerings::$offering_slug   . '-meta', true );
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
 * update offering meta
 * @return bool
 */
function update_offering_meta ( $key, $value, $term_id ){
	if ( empty( $term_id ) ) {
		return false;
	}
	$old_value = Rt_Lib_Taxonomy_Metadata\get_term_meta( $term_id, Rt_Offerings::$offering_slug . '-meta', true );
	$new_value = $old_value;
	$new_value[ $key ] = $value;
	Rt_Lib_Taxonomy_Metadata\update_term_meta( $term_id, Rt_Offerings::$offering_slug  . '-meta', $new_value, $old_value );
}


/**
 * Returns boolean of setting for reply via email is unable or not
 * @return bool
 */
function rthd_get_reply_via_email(){
	$redux = rthd_get_redux_settings();
	return ( isset( $redux['rthd_reply_via_email']) && $redux['rthd_reply_via_email'] == 1 );
}

/**
 * get user is employee or not for helpdesk
 * @param $userid
 *
 * @return mixed
 */
function rthd_is_our_employee( $userid ){
	return rt_biz_is_our_employee( $userid, RT_HD_TEXT_DOMAIN );
}

/**
 * Get tickets
 *
 * @param $key : created_by, assignee, subscribe, order
 * @param $value
 *
 * @return bool
 */
function rthd_get_tickets( $key, $value ){

	$key_array = array( 'created_by', 'assignee', 'subscribe', 'order', 'favourite' );

	if ( ! in_array( $key, $key_array ) ){
		return false;
	}

	$args    = array(
		'post_type'   => Rt_HD_Module::$post_type,
		'post_status' => 'any',
		'nopaging'    => true,
	);

	if ( 'created_by' == $key ){
		$value= rthd_convert_into_userid( $value );
		$args['meta_query'] = array(
			array(
				'key' => '_rtbiz_hd_created_by',
				'value' => $value,
			),
		);
	} elseif ( 'assignee' == $key ){
		$value= rthd_convert_into_userid( $value );
		$args['author'] = $value;
	} elseif ( 'subscribe' == $key ){
		// check given user is staff or contact
		if ( rthd_is_our_employee( $value, RT_HD_TEXT_DOMAIN ) ){
			$value= rthd_convert_into_userid( $value );
			$args['meta_query'] = array(
				array(
					'key' => '_rtbiz_hd_subscribe_to',
					'value' => ':' . $value . ',',
					'compare' => 'LIKE',
				),
			);
		} else {
			$value= rthd_convert_into_useremail( $value );
			$person = rt_biz_get_contact_by_email( $value );
			if ( isset( $person ) && ! empty( $person ) ) {
				$args['connected_items'] = $person[0]->ID;
				$args['connected_type']  = Rt_HD_Module::$post_type . '_to_' . rtbiz_post_type_name( 'contact' );
			}
		}
	} elseif ( 'order' == $key ){
		if ( is_object( $value ) ){
			$value = $value->ID;
		}
		$args['meta_query'] = array(
			array(
				'key' => 'rtbiz_hd_order_id',
				'value' => $value,
			),
		);
	} elseif ( 'favourite' == $key ){
		$fav = rthd_get_user_fav_ticket( $value );
		// if there is no fav tickets post__in query will not work so return empty array
		if ( empty( $fav ) ){
			return array();
		} else{
			$args['post__in'] = $fav;
		}
	}
	if ( ! empty( $args) ){
		return get_posts( $args );
	}
	return false;
}

/**
 * To convert useremail or user object to userid
 *
 * @param $value : Should be object [ Post type ] object or email
 *
 * @return mixed
 */
function rthd_convert_into_userid( $value ){
	if ( ! is_numeric( $value ) ){
		if ( is_string( $value ) ){
			$value = get_user_by( 'email', $value );
			$value = $value->ID;
		} elseif ( ! is_object( $value ) ){
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
function rthd_convert_into_useremail( $value ){
	if ( ! is_string( $value ) ){
		if ( is_numeric( $value ) ){
			$value = get_user_by( 'id', $value );
			$value = $value->email;
		} elseif ( ! is_object( $value ) ){
			$value = $value->email;
		}
	}
	return $value;
}

/*
 * get attachment link with facybox
 */
function rt_hd_get_attchment_link_with_fancybox( $attachment, $post_id = '', $echo = true ){
	ob_start();
	$attachment_url = wp_get_attachment_url( $attachment->ID );
	$original_url = $attachment_url;
	$extn = rt_biz_get_attchment_extension( $attachment_url );
	$class = 'rthd_attachment fancybox';
	if ( rt_bix_is_google_doc_supported_type( $attachment->post_mime_type, $extn ) ){
		$attachment_url = rt_biz_google_doc_viewer_url( $attachment_url );
		$class .= ' fancybox.iframe';
	}elseif( rthd_is_fancybox_supported_type( $extn ) ){
		$class .= ' fancybox.iframe';
	}?>
	<a class="<?php echo $class; ?>" rel="rthd_attachment_<?php echo !empty( $post_id ) ? $post_id : $attachment->post_parent; ?>"
	   data-downloadlink="<?php echo esc_url( $original_url ); ?>"
	   title="<?php echo balanceTags( $attachment->post_title ); ?>"
	   href="<?php echo esc_url( $attachment_url ); ?>"> <img
			height="20px" width="20px"
			src="<?php echo esc_url( RT_HD_URL . 'app/assets/file-type/' . $extn . '.png' ); ?>"/>
				<span title="<?php echo balanceTags( $attachment->post_title ); ?>"> 	<?php echo esc_attr( strlen( balanceTags( $attachment->post_title ) ) > 40 ? substr( balanceTags( $attachment->post_title ), 0, 40 ) . '...' : balanceTags( $attachment->post_title ) ); ?> </span>
	</a>
	<?php
	$attachment_html = ob_get_clean();
	if ( $echo ) {
		echo $attachment_html ;
	} else {
		return $attachment_html ;
	}
}

/**
 * check givent extension is support by facncy box for iframe
 * @param string $extation
 *
 * @return bool
 */
function rthd_is_fancybox_supported_type( $extation = '' ){
	$extation_arr = array(
		'mp4','mp3',
	);
	return in_array( $extation, $extation_arr );
}

/**
 * Get helpdesk email template from redux if plugin is not active / on (from rtbiz) then use default email templates
 * @param $key
 *
 * @return mixed
 */
function rthd_get_email_template_body( $key ){
	if (  rt_biz_is_email_template_addon_active() && rt_biz_is_email_template_on( Rt_HD_Module::$post_type ) ){
		$redux = rthd_get_redux_settings();
		return $redux[$key];
	}
	return rthd_get_default_email_template( $key );
}


/**
 * Helpdesk default templates
 * @param string $key
 * @param bool   $all
 *
 * @return array|bool
 */
function rthd_get_default_email_template( $key = '' , $all = false ){
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


	// Ticket template default body
	$redux['rthd_email_template_followup_add'] = '<div>New Followup Added by <strong>{followup_author}</strong></div><hr style="color: #DCEAF5;" /><div style="display: inline-block;">{followup_content}</div>';
	$redux['rthd_email_template_followup_add_private'] = '<div>A private followup has been added by <strong>{followup_author}</strong>. Please go to ticket to view content. </div>';
	$redux['rthd_email_template_followup_deleted_private'] = '<div>A Private followup is deleted by <Strong>{followup_deleted_by}</Strong></div>';
	$redux['rthd_email_template_followup_deleted'] = '<div>A Followup is deleted by <Strong>{followup_deleted_by}</Strong></div><hr style="color: #DCEAF5;" /><div  style="display: inline-block;">{followup_content}</div>';
	$redux['rthd_email_template_followup_updated_private'] = '<div>A <strong>private</strong> followup has been edited by <strong>{followup_updated_by}</strong>. Please go to ticket to view content.</div> <div style="display: inline-block;">{visibility_diff}</div>';
	$redux['rthd_email_template_followup_updated'] = '<div>A Followup Updated by <strong>{followup_updated_by}.</strong></div><div> The changes are as follows: </div><div style="display: inline-block;">{visibility_diff}</div> <div style="display: inline-block;">{followup_diff}</div>';
	$redux['rthd_email_template_new_ticket_created_author'] = '<div>Thank you for opening a new support ticket. We will look into your request and respond as soon as possible.</div> <div style="display: inline-block;">{ticket_body}</div>';
	$redux['rthd_email_template_new_ticket_created_contacts'] = '<div>A new support ticket created by <strong> {ticket_author} </strong>. You have been subscribed to this ticket.</div><div style="display: inline-block;">{ticket_body}</div>';
	$redux['rthd_email_template_new_ticket_created_group_notification'] = '<div>A new support ticket created by <strong> {ticket_author} </strong>. </div> <div>Ticket Assigned to <strong>{ticket_assignee}</strong> </div><div>{ticket_offerings}</div> <div style="display: inline-block;">{ticket_body}</div>';
	$redux['rthd_email_template_new_ticket_created_assignee'] = '<div>A new support ticket created by <strong> {ticket_author} </strong> is assigned to you. </strong></div><div>{ticket_offerings}</div> <div style="display: inline-block;" >{ticket_body} </div>';
	$redux['rthd_email_template_new_ticket_created_subscriber'] = '<div>A new support ticket created by <strong>{ticket_author}</strong>. You have been subscribed to this ticket. </div><div>Ticket Assigned to <strong>{ticket_assignee}</strong></div><div>{ticket_offerings}</div><div style="display: inline-block;">{ticket_body}</div> ';
	$redux['rthd_email_template_ticket_subscribed'] = '<div>{ticket_subscribers} been subscribed to this ticket</div>';
	$redux['rthd_email_template_ticket_unsubscribed'] = '<div>{ticket_unsubscribers} been un-subscribed from this ticket</div>';
	$redux['rthd_email_template_ticket_reassigned_old_assignee'] = '<div>You are no longer responsible for this ticket.</div>';
	$redux['rthd_email_template_ticket_reassigned_new_assignee'] = '<div>A ticket is reassigned to {new_ticket_assignee}.</div>';
	$redux['rthd_email_template_ticket_updated'] = '<div>Ticket updated by : <strong>{ticket_updated_by}</strong>.</div><div style="display: inline-block;;">{ticket_diference}</div>';

	if ( ! empty( $key ) && isset( $redux[ $key ] ) ){
		return $redux[ $key ];
	}
	if ( $all ){
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
function rthd_search_non_helpdesk_users( $query, $domain_search = false,$count = false ){
	global $wpdb;
	$helpdesk_users = rthd_get_helpdesk_user_ids();
	$q = '';
	if ( ! empty( $helpdesk_users ) ){
		$q = 'AND ID not IN ('. implode( ',',$helpdesk_users ) .')';
	}
	if ( $count ){
		if ( $domain_search ){
			return $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users WHERE (user_email like '%{$query}')".$q );
		} else {
			return $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users WHERE (user_email like '%{$query}%' or display_name like '%{$query}%' or user_nicename like '%{$query}%')".$q );
		}
	} else {
		if ( $domain_search ){
			return $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users WHERE (user_email like '%{$query}')".$q );
		} else {
			return $wpdb->get_results( "SELECT ID,display_name,user_email FROM $wpdb->users WHERE (user_email like '%{$query}%' or display_name like '%{$query}%' or user_nicename like '%{$query}%')".$q );
		}
	}
}


/**
 * @return array
 * get Helpdesk user ids
 */
function rthd_get_helpdesk_user_ids(){
	global $wpdb, $rt_biz_acl_model;
	$admins = get_users( array( 'role'=>'Administrator','fields' =>'ID' ) );
//	$result  =$wpdb->get_col("SELECT DISTINCT(acl.userid) FROM ".$rt_biz_acl_model->table_name." acl where acl.module = '".RT_HD_TEXT_DOMAIN."' and acl.permission != 0 ");
	$result  =$wpdb->get_col("SELECT DISTINCT(acl.userid) FROM ".$rt_biz_acl_model->table_name." as acl INNER JOIN ".$wpdb->prefix."p2p as p2p on ( acl.userid = p2p.p2p_to ) INNER JOIN ".$wpdb->posts." as posts on (p2p.p2p_from = posts.ID )  where acl.module =  '".RT_HD_TEXT_DOMAIN."' and acl.permission != 0 and p2p.p2p_type = '".rt_biz_get_contact_post_type()."_to_user' and posts.post_status= 'publish' and posts.post_type= '".rt_biz_get_contact_post_type()."' ");
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
function rthd_give_user_access( $user, $access_role, $team_term_id = 0 ){
	global $rt_biz_acl_model,$rt_contact;

	if ( ! is_object( $user )){
		$user = get_userdata( $user );
	}
	// get rtbiz user and set term to that user

	$contact_ID = $rt_contact->export_biz_contact($user->ID);
	if ( ! empty( $team_term_id ) ){
		wp_set_post_terms( $contact_ID, array( $team_term_id ),RT_Departments::$slug );
	}
	// add new group level permission
	$data = array(
		'userid'     => $user->ID,
		'module'     => RT_HD_TEXT_DOMAIN,
		'groupid'    => $team_term_id,
		'permission' => $access_role,
	);
	$rt_biz_acl_model->add_acl( $data );
	return true;
}

/**
 * get helpdesk default support team
 * used in importer
 * @return mixed|void
 */
function rthd_get_default_support_team(){
	$isSyncOpt = get_option( 'rthd_default_support_team' );
	if ( empty( $isSyncOpt ) ) {
		$term = wp_insert_term( 'Support', // the term
			                        RT_Departments::$slug // the taxonomy
			                      );
		if ( ! empty( $term ) ) {
			$module_permissions = get_site_option( 'rt_biz_module_permissions' );
			$module_permissions[RT_HD_TEXT_DOMAIN][$term[ 'term_id' ]] = Rt_Access_Control::$permissions['author']['value'];
			update_site_option( 'rt_biz_module_permissions', $module_permissions );
			update_option( 'rthd_default_support_team', $term[ 'term_id' ] );
			$isSyncOpt = $term[ 'term_id' ];

		}
	}
	return $isSyncOpt;
}

/**
 * check wizard completed
 * @return bool
 */
function rthd_check_wizard_completed(){
	$option = get_option( 'rtbiz_helpdesk_setup_wizard_option' );
	if ( ! empty( $option ) && 'true' == $option ) {
		return true;
	}
	return false;
}

function rthd_get_redux_post_settings( $post ) {
	// NOTE : Make modifications for what value to return.
	if ( ! isset( $GLOBALS['redux_helpdesk_settings'] ) ) {
		$GLOBALS['redux_helpdesk_settings'] = get_option( 'redux_helpdesk_settings', array() );
	}
	$data = wp_parse_args( get_post_meta( $post->ID, 'redux_helpdesk_settings', true ), $GLOBALS['redux_helpdesk_settings'] );

	return $GLOBALS['redux_helpdesk_settings'];
}

function rthd_ticket_import_logs() {
	global $rt_hd_logs;
	$rt_hd_logs->ui();
}

function rthd_mailbox_setup_view(){
	global $rt_MailBox;
	$rt_MailBox->render_mailbox_setting_page( rt_biz_sanitize_module_key( RT_HD_TEXT_DOMAIN ) );

}

function rthd_gravity_importer_view(){
	$module_key = rt_biz_sanitize_module_key( RT_HD_TEXT_DOMAIN );
	return rt_biz_gravity_importer_view( $module_key );
}

function rthd_activation_view(){
	do_action( 'rthelpdesk_addon_license_details' );
}

function rthd_no_access_redux(){
	return '<p class="description">Currently there are no settings available for you.</p>';
}
