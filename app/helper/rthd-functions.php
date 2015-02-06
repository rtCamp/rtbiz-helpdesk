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
function rthd_get_user_notification_preference( $user_id ) {
	$pref = get_user_meta( $user_id, 'rthd_notification_pref', true );
	if ( empty( $pref ) ) {
		update_user_meta( $user_id, 'rthd_notification_pref', 'yes' );
		$pref = 'yes';
	}
	return $pref;
}

function rthd_create_new_ticket_title( $key, $post_id ){
	$redux = rthd_get_redux_settings();
	if ( isset( $redux[ $key ] ) ) {
		return rthd_generate_email_title( $post_id, $redux[ $key ] );
	}
	$prefix = '[' . ucfirst( Rt_HD_Module::$name ) . ' #' . $post_id . ']';
	return $prefix;
}

function rthd_get_email_signature_settings(){
	$redux = rthd_get_redux_settings();
	if ( isset( $redux['rthd_enable_signature'] ) && 1 == $redux['rthd_enable_signature'] && isset( $redux['rthd_email_signature'] ) ) {
		return $redux['rthd_email_signature'];
	}
	return '';
}


function rthd_generate_email_title( $post_id, $title ) {
	$prefix = '[' . ucfirst( Rt_HD_Module::$name ) . ' #' . $post_id . ']';
	$title = $prefix.' '.$title;
	$title = str_replace( '{ticket_title}',get_the_title( $post_id ), $title );
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
            <time title="<?php echo esc_attr( $comment->comment_date ); ?>" datetime="<?php echo esc_attr( $comment->comment_date ); ?>">
                <?php if ($user_edit){
                    ?>
                    <a href="#" class="editfollowuplink">Edit</a> |
	                <?php
	                $data = get_comment_meta( $comment->comment_ID, 'rt_hd_original_email', true );
	                if ( ! empty( $data ) ) {
		                $href =  get_post_permalink( $comment->comment_post_ID ). '?show_original=true&comment-id='.$comment->comment_ID ;
		                ?>
		                <a href="<?php echo $href;?>" class="show-original-email"> Show original email</a> |
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
						<?php foreach ( $comment_attechment as $a ) {
							$extn_array = explode( '.', $a );
							$extn       = $extn_array[ count( $extn_array ) - 1 ];

							$file_array = explode( '/', $a );
							$fileName   = $file_array[ count( $file_array ) - 1 ];
							?>
							<li>
								<a href="<?php echo $a; ?>" title="<?php echo $fileName; ?>"> <img height="20px"
								                                                                   width="20px"
								                                                                   src="<?php echo RT_HD_URL . "app/assets/file-type/" . $extn . ".png"; ?>"/>
									<span><?php echo( ( strlen( $fileName ) ) > 12 ? substr( $fileName, 0, 12 ) . '...' : $fileName ); ?></span>
								</a>
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

function rthd_toggle_status( $postid ){
    $post = get_post($postid);
    $authorcap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'author' );
    if (  current_user_can( $authorcap ) ) {
        if( $post->post_status != 'hd-answered' ){
            wp_update_post(array( 'ID'=>$postid ,'post_status'=>'hd-answered'));
            return 'answered';
        }
    }
    else{
        if( $post->post_status != 'hd-unanswered' ){
            wp_update_post(array( 'ID'=>$postid ,'post_status'=>'hd-unanswered'));
            return 'unanswered';
        }

    }
    return false;
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
		add_action( 'admin_notices', 'rthd_admin_notice_dependency_not_installed' );
	}

	return $flag;
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
		// $install_result can be false if the file system isn't writeable.
		$error_message = __( 'Please ensure the file system is writeable', RT_HD_TEXT_DOMAIN );

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
			$msg = 'rtBiz and post to post';
		}
		else if( ! $biz_installed ){
			$msg = 'rtBiz';
		}else if (! $p2p_installed ){
			$msg = 'post to post';
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
				$msg = 'rtBiz and post to post';
			}
			else if ( $biz_installed && ! $rtbiz_active ){
				$msg = 'rtBiz';
			}
			else if ( $p2p_installed && ! $p2p_active ){
				$msg = 'post to post';
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

function rthd_get_general_body_template( $body ){
	$date = strtotime( current_time( 'mysql', 1 ) );
	return '<div style="border: 1px solid #DFE9f2;padding: 20px;background: #f1f6fa;">' . rthd_content_filter( $body ) . '<br/><div style="float: right;color: gray;">' . date( 'D, M, d, Y, H:i', $date ) . '</div></div>';
}

function rthd_update_ticket_updated_by_user( $post_id, $user_id ){
	update_post_meta( $post_id, '_rtbiz_hd_updated_by',$user_id );
}

function rthd_replace_followup_placeholder( $body , $name ){
	return str_replace( '{comment_author}',$name, $body );
}

function rthd_is_mailbox_configured(){
	$system_emails = rt_get_all_system_emails( array( 'module' => RT_HD_TEXT_DOMAIN, ) );
	if ( ! empty( $system_emails ) ){
		return true;
	}
	return false;
}
