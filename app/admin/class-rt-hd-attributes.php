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
 * Description of Rt_HD_Attributes
 * Handel Custom page "Attribute"
 * @author udit
 *
 * @since rt-Helpdesk 0.1
 */
if ( !class_exists( 'Rt_HD_Attributes' ) ) {
	/**
	 * Class Rt_HD_Attributes
	 *
	 * @since rt-Helpdesk 0.1
	 */
	class Rt_HD_Attributes {

		/**
		 * @var string Page slug
		 *
		 * @since rt-Helpdesk 0.1
		 */
		var $attributes_page_slug = 'rthd-attributes';

		/**
		 * Construct
		 *
		 * @since rt-Helpdesk 0.1
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init_attributes' ) );
		}

		/**
		 * Initialise attributes
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function init_attributes() {
			global $rt_hd_rt_attributes, $rt_hd_attributes_model, $rt_hd_attributes_relationship_model;
			$rt_hd_rt_attributes = new RT_Attributes( RT_HD_TEXT_DOMAIN );

			$admin_cap  = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'admin' );
			$editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );

			$terms_caps = array(
				'manage_terms' => $editor_cap,
				'edit_terms'   => $editor_cap,
				'delete_terms' => $editor_cap,
				'assign_terms' => $editor_cap,
			);

			$rt_hd_rt_attributes->add_attributes_page( $this->attributes_page_slug, 'edit.php?post_type=' . Rt_HD_Module::$post_type, Rt_HD_Module::$post_type, $admin_cap, $terms_caps, $render_type = true, $storage_type = true, $orderby = true );
			$rt_hd_attributes_model              = new RT_Attributes_Model();
			$rt_hd_attributes_relationship_model = new RT_Attributes_Relationship_Model();
		}

		/**
		 * return different between to attributs
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
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
		 * returns different between taxonomy
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function taxonomy_diff( $attr, $post_id, $newTicket ) {
			$diffHTML                    = '';
			$attr->attribute_render_type = 'checklist';
			switch ( $attr->attribute_render_type ) {
//				case 'autocomplete':
//					break;
				case 'dropdown':
				case 'rating-stars':
					if ( !isset( $newTicket[$attr->attribute_name] ) ) {
						$newTicket[$attr->attribute_name] = array();
					}
					$newVals = $newTicket[$attr->attribute_name];
					$newVals = array_unique( $newVals );

					$get_post_terms = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
					if ( $get_post_terms ) {
						$post_term_slug = $get_post_terms[0]->term_id;
						$post_term_name = $get_post_terms[0]->name;
					} else {
						$post_term_slug = '';
						$post_term_name = '';
					}
					if ( !empty( $newVals ) ) {
						$newTerms           = get_term_by( 'id', $newVals[0], rthd_attribute_taxonomy_name( $attr->attribute_name ) );
						$post_new_term_slug = $newVals[0];
						$post_new_term_name = $newTerms->name;
					} else {
						$post_new_term_slug = '';
						$post_new_term_name = '';
					}
					$diff = rthd_text_diff( $post_term_name, $post_new_term_name );
					if ( $diff ) {
						$diffHTML .= '<tr><th style="padding: .5em;border: 0;">' . $attr->attribute_label . '</th><td>' . $diff . '</td><td></td></tr>';
					}
					break;
				case 'checklist':
					if ( !isset( $newTicket['rt_' . $attr->attribute_name] ) ) {
						$newTicket['rt_' . $attr->attribute_name] = array();
					}
					$newVals       = $newTicket['rt_' . $attr->attribute_name];
					$newVals       = array_unique( $newVals );
					$oldTermString = rthd_post_term_to_string( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
					$newTermString = '';
					if ( !empty( $newVals ) ) {
						$newTermArr = array();
						foreach ( $newVals as $value ) {
							$newTerm = get_term_by( 'id', $value, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
							if ( $newTerm ) {
								$newTermArr[] = $newTerm->name;
							}
						}
						$newTermString = implode( ',', $newTermArr );
					}
					$diff = rthd_text_diff( $oldTermString, $newTermString );
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
		 * returns difference between two meta
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @return string
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function meta_diff( $attr, $post_id, $newTicket ) {
			$diffHTML = '';

			$oldattr = get_post_meta( $post_id, '_rtbiz_helpdesk_' . $attr->attribute_name, true );
			if ( $oldattr != $newTicket[$attr->attribute_name] ) {
				$diffHTML .= '<tr><th style="padding: .5em;border: 0;">' . $attr->attribute_label . '</th><td>' . rthd_text_diff( $oldattr, $newTicket[$attr->attribute_name] ) . '</td><td></td></tr>';
			}

			//update_post_meta($post_id, '_'.$attr->attribute_name, $newTicket[$attr->attribute_name]);
			return $diffHTML;
		}

		/**
		 * Saves attributes
		 *
		 * @param $attr
		 * @param $post_id
		 * @param $newTicket
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function save_attributes( $attr, $post_id, $newTicket ) {
			switch ( $attr->attribute_store_as ) {
				case 'taxonomy':
					if ( !isset( $newTicket[$attr->attribute_name] ) ) {
						$newTicket[$attr->attribute_name] = array();
					}
					wp_set_post_terms( $post_id, implode( ',', $newTicket[$attr->attribute_name] ), rthd_attribute_taxonomy_name( $attr->attribute_name ) );
					break;
				case 'meta':
					update_post_meta( $post_id, '_rtbiz_helpdesk_' . $attr->attribute_name, $newTicket[$attr->attribute_name] );
					break;
				default:
					do_action( 'rthd_update_attribute', $attr, $post_id, $newTicket );
					break;
			}
		}

		/**
		 * Render UI for attribute
		 *
		 * @param $attr
		 * @param $post_id
		 * @param bool $edit
		 *
		 * @since rt-Helpdesk 0.1
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
		 * render ui for taxonomy
		 *
		 * @param $attr
		 * @param $post_id
		 * @param bool $edit
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_taxonomy( $attr, $post_id, $edit = true ) {
			switch ( $attr->attribute_render_type ) {
//				case 'autocomplete':
//					break;
				case 'dropdown':
					$options   = array();
					$terms     = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
							'hide_empty' => false,
							'orderby'    => $attr->attribute_orderby,
							'order'      => 'asc'
						) );
					$post_term = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( !empty( $post_term ) ) {
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
						$term = get_term( $selected_term, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
						echo '<span class="rthd_view_mode">' . $term->name . '</span>';
					}
					break;
				case 'checklist':
					$options    = array();
					$terms      = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
							'hide_empty' => false,
							'orderby'    => $attr->attribute_orderby,
							'order'      => 'asc'
						) );
					$post_terms = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
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
						echo '<span class="rthd_view_mode">' . implode( ',', $selected_terms ) . '</span>';
					}
					break;
				case 'rating-stars':
					$options   = array();
					$terms     = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
							'hide_empty' => false,
							'orderby'    => $attr->attribute_orderby,
							'order'      => 'asc'
						) );
					$post_term = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( !empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] = array(
//							'' => $term->term_id,
							'title'   => $term->name,
							'checked' => ( $term->term_id == $selected_term ) ? true : false,
						);
					}
					if ( $edit ) {
						$this->render_rating_stars( $attr, $options );
					} else {
						$term = get_term( $selected_term, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
						echo '<span class="rthd_view_mode">' . $term->name . '</span>';
					}
					break;
				default:
					do_action( 'rthd_render_taxonomy', $attr, $post_id, $edit );
					break;
			}
		}

		/**
		 * render ui for meta box
		 *
		 * @param $attr
		 * @param $post_id
		 * @param bool $edit
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_meta( $attr, $post_id, $edit = true ) {
			switch ( $attr->attribute_render_type ) {
				case 'dropdown':
					$options   = array();
					$terms     = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
							'hide_empty' => false,
							'orderby'    => $attr->attribute_orderby,
							'order'      => 'asc'
						) );
					$post_term = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( !empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] = array(
							$term->name => $term->term_id,
							'selected'  => ( $term->term_id == $selected_term ) ? true : false,
						);
					} ?>
					<div class="large-4 small-4 columns <?php echo ( !$edit ) ? 'rthd_attr_border' : ''; ?>">
						<span class="prefix" title="<?php echo $attr->attribute_label; ?>"><label
								for="post[<?php echo $attr->attribute_name; ?>]"><?php echo $attr->attribute_label; ?></label></span>
					</div>
					<div class="large-8 mobile-large-2 columns">
						<?php if ( $edit ) {
							$this->render_dropdown( $attr, $options );
						} else {
							$term = get_term( $selected_term, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
							echo '<span class="rthd_view_mode">' . $term->name . '</span>';
						} ?>
					</div>
					<?php break;
				case 'rating-stars':
					$options   = array();
					$terms     = get_terms( rthd_attribute_taxonomy_name( $attr->attribute_name ), array(
							'hide_empty' => false,
							'orderby'    => $attr->attribute_orderby,
							'order'      => 'asc'
						) );
					$post_term = wp_get_post_terms( $post_id, rthd_attribute_taxonomy_name( $attr->attribute_name ), array( 'fields' => 'ids' ) );
					// Default Selected Term for the attribute. can beset via settings -- later on
					$selected_term = '-11111';
					if ( !empty( $post_term ) ) {
						$selected_term = $post_term[0];
					}
					foreach ( $terms as $term ) {
						$options[] = array(
//							$term->name => $term->term_id,
							''        => $term->term_id,
							'title'   => $term->name,
							'checked' => ( $term->term_id == $selected_term ) ? true : false,
						);
					} ?>
					<div class="large-4 small-4 columns">
						<span class="prefix" title="<?php echo $attr->attribute_label; ?>"><label
								for="post[<?php echo $attr->attribute_name; ?>]"><?php echo $attr->attribute_label; ?></label></span>
					</div>
					<div class="large-8 mobile-large-2 columns rthd_attr_border">
						<?php if ( $edit ) {
							$this->render_rating_stars( $attr, $options );
						} else {
							$term = get_term( $selected_term, rthd_attribute_taxonomy_name( $attr->attribute_name ) );
							echo '<div class="rthd_attr_border rthd_view_mode">' . $term->name . '</div>';
						} ?>
					</div>
					<?php break;
				case 'date':
					$value = get_post_meta( $post_id, '_rtbiz_helpdesk_' . $attr->attribute_name, true ); ?>
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php echo $attr->attribute_label; ?>"><label
								for="post[<?php echo $attr->attribute_name; ?>]"><?php echo $attr->attribute_label; ?></label></span>
					</div>
					<div class="large-7 mobile-large-2 columns <?php echo ( !$edit ) ? 'rthd_attr_border' : ''; ?>">
						<?php if ( $edit ) {
							$this->render_date( $attr, $value );
						} else {
							echo '<span class="rthd_view_mode moment-from-now">' . $value . '</span>';
						} ?>
					</div>
					<?php if ( $edit ) { ?>
					<div class="large-1 mobile-large-1 columns">
						<span class="postfix datepicker-toggle"
						      data-datepicker="<?php echo $attr->attribute_name; ?>"><label
								class="foundicon-calendar"></label></span>
					</div>
				<?php
				}
					break;
				case 'datetime':
					$value = get_post_meta( $post_id, '_rtbiz_helpdesk_' . $attr->attribute_name, true ); ?>
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php echo $attr->attribute_label; ?>"><label
								for="post[<?php echo $attr->attribute_name; ?>]"><?php echo $attr->attribute_label; ?></label></span>
					</div>
					<div class="large-7 mobile-large-2 columns <?php echo ( !$edit ) ? 'rthd_attr_border' : ''; ?>">
						<?php if ( $edit ) {
							$this->render_datetime( $attr, $value );
						} else {
							echo '<span class="rthd_view_mode moment-from-now">' . $value . '</span>';
						} ?>
					</div>
					<?php if ( $edit ) { ?>
					<div class="large-1 mobile-large-1 columns">
						<span class="postfix datetimepicker-toggle"
						      data-datetimepicker="<?php echo $attribute_name; ?>"><label
								class="foundicon-calendar"></label></span>
					</div>
				<?php
				}
					break;
				case 'currency':
					$value = get_post_meta( $post_id, '_rtbiz_helpdesk_' . $attr->attribute_name, true ); ?>
					<div class="large-4 mobile-large-1 columns">
						<span class="prefix" title="<?php echo $attr->attribute_label; ?>"><label
								for="post[<?php echo $attr->attribute_name; ?>]"><?php echo $attr->attribute_label; ?></label></span>
					</div>
					<div class="large-7 mobile-large-2 columns <?php echo ( !$edit ) ? 'rthd_attr_border' : ''; ?>">
						<?php if ( $edit ) {
							$this->render_currency( $attr, $value );
						} else {
							echo '<span class="rthd_view_mode">' . $value . '</span>';
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
					$value = get_post_meta( $post_id, '_rtbiz_helpdesk_' . $attr->attribute_name, true ); ?>
					<div class="large-4 small-4 columns">
						<span class="prefix" title="<?php echo $attr->attribute_label; ?>"><label
								for="post[<?php echo $attr->attribute_name; ?>]"><?php echo $attr->attribute_label; ?></label></span>
					</div>
					<div class="large-8 mobile-large-2 columns <?php echo ( !$edit ) ? 'rthd_attr_border' : ''; ?>">
						<?php if ( $edit ) {
							$this->render_text( $attr, $value );
						} else {
							echo '<span class="rthd_view_mode">' . $value . '</span>';
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
		 * @param $attr
		 * @param $options
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_dropdown( $attr, $options ) {
			global $rthd_form;
			$args = array(
				'id'             => $attr->attribute_name,
				'name'           => 'post[' . $attr->attribute_name . '][]',
//				'class' => array('scroll-height'),
				'rtForm_options' => $options,
			);
			echo $rthd_form->get_select( $args );
		}

		/**
		 * render ui for rating stars
		 *
		 * @param $attr
		 * @param $options
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_rating_stars( $attr, $options ) {
			global $rthd_form;
			$args = array(
				'id'             => $attr->attribute_name,
				'name'           => 'post[' . $attr->attribute_name . '][]',
				'class'          => array( 'rthd-stars' ),
				'misc'           => array(
					'class' => 'star',
				),
				'rtForm_options' => $options,
			);
			echo $rthd_form->get_radio( $args );
		}

		/**
		 *  render ui for checklist
		 *
		 * @param $attr
		 * @param $options
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_checklist( $attr, $options ) {
			global $rthd_form;
			$args = array(
				'id'             => $attr->attribute_name,
				'name'           => 'post[' . $attr->attribute_name . '][]',
				'class'          => array( 'scroll-height' ),
				'rtForm_options' => $options,
			);
			echo $rthd_form->get_checkbox( $args );
		}

		/**
		 * render ui for date
		 *
		 * @param $attr
		 * @param $value
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_date( $attr, $value ) {
			global $rthd_form;
			$args = array(
				'id'    => $attr->attribute_name,
				'class' => array(
					'datepicker',
					'moment-from-now',
				),
				'misc'  => array(
					'placeholder' => 'Select ' . $attr->attribute_label,
					'readonly'    => 'readonly',
					'title'       => $value,
				),
				'value' => $value,
			);
			echo $rthd_form->get_textbox( $args );
			$args = array(
				'name'  => 'post[' . $attr->attribute_name . ']',
				'value' => $value,
			);
			echo $rthd_form->get_hidden( $args );
		}

		/**
		 * render ui for date and time
		 *
		 * @param $attr
		 * @param $value
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_datetime( $attr, $value ) {
			global $rthd_form;
			$args = array(
				'id'    => $attr->attribute_name,
				'class' => array(
					'datetimepicker',
					'moment-from-now',
				),
				'misc'  => array(
					'placeholder' => 'Select ' . $attr->attribute_label,
					'readonly'    => 'readonly',
					'title'       => $value,
				),
				'value' => $value,
			);
			echo $rthd_form->get_textbox( $args );
			$args = array(
				'name'  => 'post[' . $attr->attribute_name . ']',
				'value' => $value,
			);
			echo $rthd_form->get_hidden( $args );
		}

		/**
		 * render ui for currency
		 *
		 * @param $attr
		 * @param $value
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_currency( $attr, $value ) {
			global $rthd_form;
			$args = array(
				'name'  => 'post[' . $attr->attribute_name . ']',
				'value' => $value,
			);
			echo $rthd_form->get_textbox( $args );
		}

		/**
		 * render ui for text
		 *
		 * @param $attr
		 * @param $value
		 *
		 * @since rt-Helpdesk 0.1
		 */
		function render_text( $attr, $value ) {
			global $rthd_form;
			$args = array(
				'name'  => 'post[' . $attr->attribute_name . ']',
				'value' => $value,
			);
			echo $rthd_form->get_textbox( $args );
		}
	}
}
