<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rtbiz_HD_Attributes' ) ) {
	/**
	 * Class Rt_HD_Attributes
	 * Handel Custom page "Attribute"
	 *
	 * @since  0.1
	 *
	 * @author udit
	 */
	class Rtbiz_HD_Attributes {

		/**
		 * @var string attributes Page slug
		 *
		 * @since 0.1
		 */
		var $attributes_page_slug = 'rthd-attributes';

		/**
		 * Construct
		 *
		 * @since 0.1
		 */
		public function __construct() {
			global $rt_hd_rt_attributes, $rtbiz_hd_attributes_model, $rtbiz_hd_attributes_relationship_model;
			$rt_hd_rt_attributes                 = new RT_Attributes( RTBIZ_HD_TEXT_DOMAIN );
			$rtbiz_hd_attributes_model              = new RT_Attributes_Model();
			$rtbiz_hd_attributes_relationship_model = new RT_Attributes_Relationship_Model();

			add_action( 'init', array( $this, 'init_attributes' ) );
		}

		/**
		 * Add attributes page for rtbiz-HelpDesk
		 *
		 * @since 0.1
		 */
		function init_attributes() {

			global $rt_hd_rt_attributes;

			$admin_cap  = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'admin' );
			$editor_cap = rtbiz_get_access_role_cap( RTBIZ_HD_TEXT_DOMAIN, 'editor' );

			$terms_caps = array(
				'manage_terms' => $editor_cap,
				'edit_terms'   => $editor_cap,
				'delete_terms' => $editor_cap,
				'assign_terms' => $editor_cap,
			);
			if ( rtbiz_hd_check_wizard_completed() ) {
				$rt_hd_rt_attributes->add_attributes_page( $this->attributes_page_slug, 'edit.php?post_type=' . Rtbiz_HD_Module::$post_type, Rtbiz_HD_Module::$post_type, $admin_cap, $terms_caps, $render_type = true, $storage_type = true, $orderby = true );
			}
		}

		/**
		 * get Diff of attributes for given post
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 */
		function attribute_diff( $attr, $post_id, $newTicket ) {

			$diffHTML = '';
			switch ( $attr->attribute_store_as ) {
				case 'taxonomy':
					$diffHTML = $this->taxonomy_diff( $attr, $post_id, $newTicket );
					break;
				case 'meta':
					$diffHTML = $this->meta_diff( $attr, $post_id, $newTicket );
					break;
				default:
					$diffHTML = apply_filters( 'rthd_attribute_diff', $diffHTML, $attr, $post_id, $newTicket );
					break;
			}

			return $diffHTML;
		}

		/**
		 * get Diff of taxonomy for given post
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 */
		function taxonomy_diff( $attr, $post_id, $newTicket ) {
			$diffHTML                    = '';
			$attr->attribute_render_type = 'checklist';
			switch ( $attr->attribute_render_type ) {
				//				case 'autocomplete':
				//					break;
				case 'dropdown':
				case 'rating-stars':
					if ( ! isset( $newTicket[ 'rt_' . $attr->attribute_name ] ) ) {
						$newTicket[ 'rt_' . $attr->attribute_name ] = array();
					}
					$newVals = $newTicket[ 'rt_' . $attr->attribute_name ];
					$newVals = array_unique( $newVals );

					$get_post_terms = wp_get_post_terms( $post_id, rtbiz_post_type_name( $attr->attribute_name ) );
					if ( $get_post_terms ) {
						$post_term_slug = $get_post_terms[0]->term_id;
						$post_term_name = $get_post_terms[0]->name;
					} else {
						$post_term_slug = '';
						$post_term_name = '';
					}
					if ( ! empty( $newVals ) ) {
						$newTerms           = get_term_by( 'id', $newVals[0], rtbiz_post_type_name( $attr->attribute_name ) );
						$post_new_term_slug = $newVals[0];
						$post_new_term_name = $newTerms->name;
					} else {
						$post_new_term_slug = '';
						$post_new_term_name = '';
					}
					$diff = rtbiz_hd_text_diff( $post_term_name, $post_new_term_name );
					if ( $diff ) {
						$diffHTML .= '<tr><th style="padding: .5em;border: 0;">' . $attr->attribute_label . '</th><td>' . $diff . '</td><td></td></tr>';
					}
					break;
				case 'checklist':
					if ( ! isset( $newTicket[ 'rt_' . $attr->attribute_name ] ) ) {
						$newTicket[ 'rt_' . $attr->attribute_name ] = array();
					}
					$newVals       = $newTicket[ 'rt_' . $attr->attribute_name ];
					$newVals       = array_unique( $newVals );
					$oldTermString = rtbiz_hd_post_term_to_string( $post_id, rtbiz_post_type_name( $attr->attribute_name ) );
					$newTermString = '';
					if ( ! empty( $newVals ) ) {
						$newTermArr = array();
						foreach ( $newVals as $value ) {
							$newTerm = get_term_by( 'id', $value, rtbiz_post_type_name( $attr->attribute_name ) );
							if ( $newTerm ) {
								$newTermArr[] = $newTerm->name;
							}
						}
						$newTermString = implode( ',', $newTermArr );
					}
					$diff = rtbiz_hd_text_diff( $oldTermString, $newTermString );
					if ( $diff ) {
						$diffHTML .= '<tr><th style="padding: .5em;border: 0;">' . $attr->attribute_label . '</th><td>' . $diff . '</td><td></td></tr>';
					}
					break;
				default:
					$diffHTML = apply_filters( 'rthd_attribute_diff', $diffHTML, $attr, $post_id, $newTicket );
					break;
			}

			return $diffHTML;
		}

		/**
		 * get Diff of Meta for given post
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 */
		function meta_diff( $attr, $post_id, $newTicket ) {
			$diffHTML = '';

			$oldattr = get_post_meta( $post_id, '_rtbiz_hd_' . $attr->attribute_name, true );
			if ( $oldattr != $newTicket[ $attr->attribute_name ] ) {
				$diffHTML .= '<tr><th style="padding: .5em;border: 0;">' . $attr->attribute_label . '</th><td>' . rtbiz_hd_text_diff( $oldattr, $newTicket[ $attr->attribute_name ] ) . '</td><td></td></tr>';
			}

			//update_post_meta($post_id, '_'.$attr->attribute_name, $newTicket[$attr->attribute_name]);
			return $diffHTML;
		}

		/**
		 * Saves attributes for given post
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 */
		function save_attributes( $attr, $post_id, $newTicket ) {
			switch ( $attr->attribute_store_as ) {
				case 'taxonomy':
					if ( ! isset( $newTicket[ 'rt_' . $attr->attribute_name ] ) ) {
						$newTicket[ 'rt_' . $attr->attribute_name ] = array();
					}
					wp_set_post_terms( $post_id, implode( ',', $newTicket[ 'rt_' . $attr->attribute_name ] ), rtbiz_post_type_name( $attr->attribute_name ) );
					break;
				case 'meta':
					update_post_meta( $post_id, '_rtbiz_hd_' . $attr->attribute_name, $newTicket[ $attr->attribute_name ] );
					break;
				default:
					do_action( 'rthd_update_attribute', $attr, $post_id, $newTicket );
					break;
			}
		}

		/**
		 * Render DOM element for attribute
		 *
		 * @since 0.1
		 *
		 * @param      $attr
		 * @param      $post_id
		 * @param bool $edit
		 */
		function render_attribute( $attr, $post_id, $edit = true ) {
			switch ( $attr->attribute_store_as ) {
				case 'taxonomy':
					$this->render_taxonomy( $attr, $post_id, $edit );
					break;
				case 'meta':
					$this->render_meta( $attr, $post_id, $edit );
					break;
				default:
					do_action( 'rthd_render_attribute', $attr, $post_id, $edit );
					break;
			}
		}

		/**
		 * Render DOM element for taxonomy
		 *
		 * @since 0.1
		 *
		 * @param      $attr
		 * @param      $post_id
		 * @param bool $edit
		 */
		function render_taxonomy( $attr, $post_id, $edit = true ) {
			switch ( $attr->attribute_render_type ) {
				//				case 'autocomplete':
				//					break;
				case 'dropdown':
					$options   = array();
					$terms     = get_terms( rtbiz_post_type_name( $attr->attribute_name ), array(
						'hide_empty' => false,
						'orderby'    => $attr->attribute_orderby,
						'order'      => 'asc',
					) );
					$post_term = wp_get_post_terms( $post_id, rtbiz_post_type_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( ! empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] = array(
							$term->name => $term->term_id,
							'selected'  => ( $term->term_id == $selected_term ) ? true : false,
						);
					}
					if ( $edit ) {
						$this->render_dropdown( $attr, $options );
					} else {
						$term = get_term( $selected_term, rtbiz_post_type_name( $attr->attribute_name ) );
						?><span class="rthd_view_mode"><?php echo esc_html( $term->name ); ?></span><?php
					}
					break;
				case 'checklist':
					$options    = array();
					$terms      = get_terms( rtbiz_post_type_name( $attr->attribute_name ), array(
						'hide_empty' => false,
						'orderby'    => $attr->attribute_orderby,
						'order'      => 'asc',
					) );
					$post_terms = wp_get_post_terms( $post_id, rtbiz_post_type_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					if ( empty( $post_terms ) ) {
						$post_terms = array();
					}
					foreach ( $terms as $term ) {
						$options[] = array(
							$term->name => $term->term_id,
							'checked'   => ( in_array( $term->term_id, $post_terms ) ) ? true : false,
						);
					}
					if ( $edit ) {
						$this->render_checklist( $attr, $options );
					} else {
						$selected_terms = array();
						foreach ( $terms as $term ) {
							if ( in_array( $term->term_id, $post_terms ) ) {
								$selected_terms[] = $term->name;
							}
						}
						?><span
							class="rthd_view_mode"><?php echo esc_html( implode( ',', $selected_terms ) ); ?></span><?php
					}
					break;
				case 'rating-stars':
					$options   = array();
					$terms     = get_terms( rtbiz_post_type_name( $attr->attribute_name ), array(
						'hide_empty' => false,
						'orderby'    => $attr->attribute_orderby,
						'order'      => 'asc',
					) );
					$post_term = wp_get_post_terms( $post_id, rtbiz_post_type_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( ! empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] =
							array( //							'' => $term->term_id,
								'title'   => $term->name,
								'checked' => ( $term->term_id == $selected_term ) ? true : false,
							);
					}
					if ( $edit ) {
						$this->render_rating_stars( $attr, $options );
					} else {
						$term = get_term( $selected_term, rtbiz_post_type_name( $attr->attribute_name ) );
						?><span class="rthd_view_mode"><?php echo esc_html( $term->name ); ?></span><?php
					}
					break;
				default:
					do_action( 'rthd_render_taxonomy', $attr, $post_id, $edit );
					break;
			}
		}

		/**
		 * Render DOM element for meta
		 *
		 * @since 0.1
		 *
		 * @param      $attr
		 * @param      $post_id
		 * @param bool $edit
		 */
		function render_meta( $attr, $post_id, $edit = true ) {
			switch ( $attr->attribute_render_type ) {
				case 'dropdown':
					$options   = array();
					$terms     = get_terms( rtbiz_post_type_name( $attr->attribute_name ), array(
						'hide_empty' => false,
						'orderby'    => $attr->attribute_orderby,
						'order'      => 'asc',
					) );
					$post_term = wp_get_post_terms( $post_id, rtbiz_post_type_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( ! empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] = array(
							$term->name => $term->term_id,
							'selected'  => ( $term->term_id == $selected_term ) ? true : false,
						);
					} ?>
					<div
						class="large-4 small-4 columns <?php echo sanitize_html_class( ( ! $edit ) ? 'rthd_attr_border' : '' ); ?>">
						<span class="prefix" title="<?php echo esc_attr( $attr->attribute_label ); ?>"><label
								for="post[<?php echo esc_attr( $attr->attribute_label ); ?>]"><?php echo esc_html( $attr->attribute_label ); ?></label></span>
					</div>
					<div class="large-8 mobile-large-2 columns">
					<?php if ( $edit ) {
						$this->render_dropdown( $attr, $options );
} else {
	$term = get_term( $selected_term, rtbiz_post_type_name( $attr->attribute_name ) );
	?><span class="rthd_view_mode"><?php echo esc_html( $term->name ); ?></span><?php
} ?>
					</div>
					<?php break;
				case 'rating-stars':
					$options   = array();
					$terms     = get_terms( rtbiz_post_type_name( $attr->attribute_name ), array(
						'hide_empty' => false,
						'orderby'    => $attr->attribute_orderby,
						'order'      => 'asc',
					) );
					$post_term = wp_get_post_terms( $post_id, rtbiz_post_type_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( ! empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] =
							array( //							$term->name => $term->term_id,
									''        => $term->term_id,
									'title'   => $term->name,
									'checked' => ( $term->term_id == $selected_term ) ? true : false,
							);
					} ?>
					<div class="large-4 small-4 columns">
						<span class="prefix" title="<?php echo esc_attr( $attr->attribute_label ); ?>"><label
								for="post[<?php echo esc_attr( $attr->attribute_name ); ?>]"><?php echo esc_html( $attr->attribute_label ); ?></label></span>
					</div>
					<div class="large-8 mobile-large-2 columns rthd_attr_border"><?php
					if ( $edit ) {
						$this->render_rating_stars( $attr, $options );
					} else {
							$term = get_term( $selected_term, rtbiz_post_type_name( $attr->attribute_name ) );
							?>
							<div
								class="rthd_attr_border rthd_view_mode"><?php echo esc_html( $term->name ); ?></div><?php
					} ?>
					</div>
					<?php break;
				case 'date':
					$value = get_post_meta( $post_id, '_rtbiz_hd_' . $attr->attribute_name, true ); ?>
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php echo esc_attr( $attr->attribute_label ); ?>"><label
								for="post[<?php echo esc_attr( $attr->attribute_name ); ?>]"><?php echo esc_html( $attr->attribute_label ); ?></label></span>
					</div>
					<div
						class="large-7 mobile-large-2 columns <?php echo sanitize_html_class( ( ! $edit ) ? 'rthd_attr_border' : '' ); ?>">
					<?php if ( $edit ) {
						$this->render_date( $attr, $value );
} else {
	?><span class="rthd_view_mode moment-from-now"><?php echo esc_html( $value ) ?></span><?php
} ?>
					</div>
					<?php if ( $edit ) { ?>
					<div class="large-1 mobile-large-1 columns">
						<span class="postfix datepicker-toggle"
						      data-datepicker="<?php echo esc_attr( $attr->attribute_name ); ?>"><label
								class="foundicon-calendar"></label></span>
					</div>
				<?php
}
					break;
				case 'datetime':
					$value = get_post_meta( $post_id, '_rtbiz_hd_' . $attr->attribute_name, true ); ?>
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php echo esc_attr( $attr->attribute_label ); ?>"><label
								for="post[<?php echo esc_attr( $attr->attribute_name ); ?>]"><?php echo esc_html( $attr->attribute_label ); ?></label></span>
					</div>
					<div
						class="large-7 mobile-large-2 columns <?php echo sanitize_html_class( ( ! $edit ) ? 'rthd_attr_border' : '' ); ?>">
					<?php if ( $edit ) {
						$this->render_datetime( $attr, $value );
} else {
	?><span class="rthd_view_mode moment-from-now"><?php echo esc_html( $value ); ?></span><?php
} ?>
					</div>
					<?php if ( $edit ) { ?>
					<div class="large-1 mobile-large-1 columns">
						<span class="postfix datetimepicker-toggle"
						      data-datetimepicker="<?php echo esc_attr( $attr->attribute_name ); ?>"><label
								class="foundicon-calendar"></label></span>
					</div>
				<?php
}
					break;
				case 'currency':
					$value = get_post_meta( $post_id, '_rtbiz_hd_' . $attr->attribute_name, true ); ?>
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php echo esc_attr( $attr->attribute_label ); ?>"><label
								for="post[<?php echo esc_attr( $attr->attribute_name ); ?>]"><?php echo esc_html( $attr->attribute_label ); ?></label></span>
					</div>
					<div
						class="large-7 mobile-large-2 columns <?php echo sanitize_html_class( ( ! $edit ) ? 'rthd_attr_border' : '' ); ?>">
					<?php if ( $edit ) {
						$this->render_currency( $attr, $value );
} else {
	?><span class="rthd_view_mode"><?php echo esc_html( $value ); ?></span><?php
} ?>
					</div>
					<?php if ( $edit ) { ?>
					<div class="large-1 mobile-large-1 columns">
						<span class="postfix">$</span>
					</div>
				<?php
}
					break;
				case 'text':
					$value = get_post_meta( $post_id, '_rtbiz_hd_' . $attr->attribute_name, true ); ?>
					<div class="large-4 small-4 columns">
						<span class="prefix" title="<?php echo esc_attr( $attr->attribute_label ); ?>"><label
								for="post[<?php echo esc_attr( $attr->attribute_name ); ?>]"><?php echo esc_html( $attr->attribute_label ); ?></label></span>
					</div>
					<div
						class="large-8 mobile-large-2 columns <?php echo sanitize_html_class( ( ! $edit ) ? 'rthd_attr_border' : '' ); ?>">
					<?php if ( $edit ) {
						$this->render_text( $attr, $value );
} else {
	?><span class="rthd_view_mode"><?php esc_htm( $value ); ?></span><?php
} ?>
					</div>
					<?php break;
				default:
					do_action( 'rthd_render_meta', $attr, $post_id, $edit );
					break;
			}
		}

		/**
		 * render ui for dropdown
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $options
		 */
		function render_dropdown( $attr, $options ) {
			global $rthd_form;
			$args = array(
				'id'             => $attr->attribute_name,
				'name'           => 'post[' . $attr->attribute_name . '][]', //				'class' => array('scroll-height'),
				'rtForm_options' => $options,
			);
			echo balanceTags( $rthd_form->get_select( $args ) );
		}

		/**
		 * render ui for rating stars
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $options
		 */
		function render_rating_stars( $attr, $options ) {
			global $rthd_form;
			$args = array(
				'id'             => $attr->attribute_name,
				'name'           => 'post[' . $attr->attribute_name . '][]',
				'class'          => array( 'rthd-stars' ),
				'misc'           => array( 'class' => 'star' ),
				'rtForm_options' => $options,
			);
			echo balanceTags( $rthd_form->get_radio( $args ) );
		}

		/**
		 * render ui for checklist
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $options
		 */
		function render_checklist( $attr, $options ) {
			global $rthd_form;
			$args = array(
				'id'             => $attr->attribute_name,
				'name'           => 'post[' . $attr->attribute_name . '][]',
				'class'          => array( 'scroll-height' ),
				'rtForm_options' => $options,
			);
			echo balanceTags( $rthd_form->get_checkbox( $args ) );
		}

		/**
		 * render ui for date
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $value
		 */
		function render_date( $attr, $value ) {
			global $rthd_form;
			$args = array(
				'id'    => $attr->attribute_name,
				'class' => array( 'datepicker', 'moment-from-now' ),
				'misc'  => array(
					'placeholder' => 'Select ' . $attr->attribute_label,
					'readonly'    => 'readonly',
					'title'       => $value,
				),
				'value' => $value,
			);
			echo balanceTags( $rthd_form->get_textbox( $args ) );
			$args = array( 'name' => 'post[' . $attr->attribute_name . ']', 'value' => $value );
			echo balanceTags( $rthd_form->get_hidden( $args ) );
		}

		/**
		 * render ui for date and time
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $value
		 */
		function render_datetime( $attr, $value ) {
			global $rthd_form;
			$args = array(
				'id'    => $attr->attribute_name,
				'class' => array( 'datetimepicker', 'moment-from-now' ),
				'misc'  => array(
					'placeholder' => 'Select ' . $attr->attribute_label,
					'readonly'    => 'readonly',
					'title'       => $value,
				),
				'value' => $value,
			);
			echo balanceTags( $rthd_form->get_textbox( $args ) );
			$args = array( 'name' => 'post[' . $attr->attribute_name . ']', 'value' => $value );
			echo balanceTags( $rthd_form->get_hidden( $args ) );
		}

		/**
		 * render ui for currency
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $value
		 */
		function render_currency( $attr, $value ) {
			global $rthd_form;
			$args = array( 'name' => 'post[' . $attr->attribute_name . ']', 'value' => $value );
			echo balanceTags( $rthd_form->get_textbox( $args ) );
		}

		/**
		 * render ui for text
		 *
		 * @since 0.1
		 *
		 * @param $attr
		 * @param $value
		 */
		function render_text( $attr, $value ) {
			global $rthd_form;
			$args = array( 'name' => 'post[' . $attr->attribute_name . ']', 'value' => $value );
			echo balanceTags( $rthd_form->get_textbox( $args ) );
		}
	}
}
