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
 * Description of class-rt-hd-help
 *
 * @author Utkarsh
 */
if ( ! class_exists( 'Rtbiz_Hd_Help' ) ) {

	class Rtbiz_Hd_Help {

		var $tabs = array();
		var $help_sidebar_content;

		public function __construct() {
			Rtbiz_HD::$loader->add_action( 'init', $this, 'init_help' );
		}

		function init_help() {
			$this->tabs = apply_filters( 'rtbiz_help_tabs', array(
				'post-new.php'  => array(
					array(
						'id'        => 'create_Ticket_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'create_Ticket_screen_content',
						'title'     => __( 'Screen Content', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'create_organization_screen_content',
						'title'     => __( 'Screen Content', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => rtbiz_get_company_post_type(),
					),
				),
				'post.php'      => array(
					array(
						'id'        => 'edit_ticket_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'edit_ticket_screen_content',
						'title'     => __( 'Screen Content', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'edit_organization_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => rtbiz_get_company_post_type(),
					),
					array(
						'id'        => 'edit_organization_screen_content',
						'title'     => __( 'Screen Content', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => rtbiz_get_company_post_type(),
					),
				),
				'edit.php'      => array(
					array(
						'id'        => 'dashboard_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
						'page'      => 'rthd-' . esc_html( Rtbiz_HD_Module::$post_type ) . '-dashboard',
					),
					array(
						'id'        => 'ticket_list_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'ticket_list_screen_content',
						'title'     => __( 'Screen Content', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'attribute_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'post_type' => Rtbiz_HD_Module::$post_type,
						'page'      => 'rthd-attributes',
					),
				),
				'admin.php'     => array(
					array(
						'id'        => 'dashboard_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'page'      => 'rthd-' . esc_html( Rtbiz_HD_Module::$post_type ) . '-dashboard',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'dashboard_screen_content',
						'title'     => __( 'Screen Content', 'rtbiz-helpdesk' ),
						'content'   => '',
						'page'      => 'rthd-' . esc_html( Rtbiz_HD_Module::$post_type ) . '-dashboard',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
					array(
						'id'        => 'settings_overview',
						'title'     => __( 'Overview', 'rtbiz-helpdesk' ),
						'content'   => '',
						'page'      => 'srthd-settings',
						'post_type' => Rtbiz_HD_Module::$post_type,
					),
				),
				'edit-tags.php' => array(
					/*array(
						'id'       => 'user_group_overview',
						'title'    => __( 'Overview' ),
						'content'  => '',
						'taxonomy' => 'user-group',
					),
					array(
						'id'       => 'user_group_screen_content',
						'title'    => __( 'Screen Content' ),
						'content'  => '',
						'taxonomy' => 'user-group',
					),*/
				),
			) );

			$documentation_link         = apply_filters( 'rt_hd_help_documentation_link', 'http://docs.rtcamp.com/rtbiz/' );
			$support_forum_link         = apply_filters( 'rt_hd_help_support_forum_link', 'https://rtcamp.com/premium-support/' );
			$this->help_sidebar_content = apply_filters( 'rt_hd_help_sidebar_content', '<p><strong>' . esc_attr( __( 'For More Information : ', 'rtbiz-helpdesk' ) ) . '</strong></p><p><a href="' . esc_url( $documentation_link ) . '">' . esc_attr( __( 'Documentation', 'rtbiz-helpdesk' ) ) . '</a></p><p><a href="' . esc_url( $support_forum_link ). '">' . esc_attr( __( 'Support Forum', 'rtbiz-helpdesk' ) ) . '</a></p>' );

			add_action( 'current_screen', array( $this, 'check_tabs' ) );
		}

		function check_tabs() {
			if ( isset( $this->tabs[ $GLOBALS['pagenow'] ] ) ) {
				switch ( $GLOBALS['pagenow'] ) {
					case 'post-new.php':
					case 'edit.php':
						if ( isset( $_GET['post_type'] ) ) {
							foreach ( $this->tabs[ $GLOBALS['pagenow'] ] as $args ) {
								if ( isset( $_GET['page'] ) && isset( $args['page'] ) && $args['page'] == $_GET['page'] ) {
									$this->add_tab( $args );
								} else if ( empty( $args['page'] ) && empty( $_GET['page'] ) && $args['post_type'] == $_GET['post_type'] ) {
									$this->add_tab( $args );
								}
							}
						}
						break;
					case 'post.php':
						if ( isset( $_GET['post'] ) ) {
							$post_type = get_post_type( $_GET['post'] );
							foreach ( $this->tabs[ $GLOBALS['pagenow'] ] as $args ) {
								if ( $args['post_type'] == $post_type ) {
									$this->add_tab( $args );
								}
							}
						}
						break;
					case 'admin.php':
						if ( isset( $_GET['page'] ) ) {
							foreach ( $this->tabs[ $GLOBALS['pagenow'] ] as $args ) {
								if ( $args['page'] == $_GET['page'] ) {
									$this->add_tab( $args );
								}
							}
						}
						break;
					case 'edit-tags.php':
						if ( isset( $_GET['taxonomy'] ) ) {
							foreach ( $this->tabs[ $GLOBALS['pagenow'] ] as $args ) {
								if ( $args['taxonomy'] == $_GET['taxonomy'] ) {
									$this->add_tab( $args );
								}
							}
						}
						break;
				}
			}
		}

		function add_tab( $args ) {
			get_current_screen()->add_help_tab( array(
				                                    'id'       => $args['id'],
				                                    'title'    => $args['title'],
				                                    // You can directly set content as well.
				                                    //				'content' => $args[ 'content' ],
				                                    // This is for some extra content & logic
				                                    'callback' => array( $this, 'tab_content' ),
			                                    ) );
			get_current_screen()->set_help_sidebar( $this->help_sidebar_content );
		}

		function tab_content( $screen, $tab ) {
			// Some Extra content with logic
			switch ( $tab['id'] ) {
				case 'create_Ticket_overview':
				case 'edit_ticket_overview':
					?>
					<p>
						<?php _e( 'Screen to add a new ticket. Only staff can create ticket from this screen. ', 'rtbiz-helpdesk' ); ?>
					</p>
					<?php
					break;
				case 'create_Ticket_screen_content':
				case 'edit_ticket_screen_content':
					?>
					<ul>
						<li><strong><?php _e( 'Title - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Ticket title', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Ticket Information - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Has information about ticket creations date, customer who created ticket, ticket assignee and status.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Products - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'The product/product for which customer has created a ticket', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Team - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( ' Staff members can update the relevant user departments the ticket belongs to.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Participant (Customers) - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Non-staff people on ticket who have been added by ticket author/customer', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Participant (Staff) - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Staff people (other than assignee) on ticket.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Related Tickets - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Option for staff to connect two tickets ', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Teams - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Team that is handling a ticket', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Blacklist Contacts - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( "To blacklist contacts from which spam tickets are being created. A blacklisted contact can't create tickets and can't add replies to a ticket.", 'rtbiz-helpdesk' ); ?></li>
					</ul>
					<?php
					break;
				case 'edit_organization_screen_content':
					?>
					<p><?php _e( 'There are a few sections where you can save essential information about an company : ', 'rtbiz-helpdesk' ); ?></p>
					<ul>
						<li><?php _e( 'There is a textbox for the title of a company.', 'rtbiz-helpdesk' ); ?></li>
						<li><?php _e( 'You can also put any description/comments related to the company in to the rich text editor provided.', 'rtbiz-helpdesk' ); ?></li>
						<li>
							<?php _e( 'There might be other extra attributes metaboxes depending upon how you add an attribute from the attributes page', 'rtbiz-helpdesk' ); ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => Rt_Biz_Attributes::$attributes_page_slug ), admin_url( 'admin.php' ) ) ); ?>"><?php _e( 'here', 'rtbiz-helpdesk' ); ?></a>.
						</li>
						<li>
							<?php _e( 'You will see a "Connected X" metaboxes in the side column.', 'rtbiz-helpdesk' ); ?>
							<?php _e( 'You can select any entity from the metabox to connect it to the company.', 'rtbiz-helpdesk' ); ?>
						</li>
					</ul>
					<?php
					break;
				case 'ticket_list_overview':
					?>
					<p>
						<?php echo esc_attr( __( 'This screen lists all the Helpdesk tickets on which the logged in user is a part of. The WordPress admin will see all tickets by default.  ', 'rtbiz-helpdesk' ) );?>
					</p>
					<?php
					break;
				case 'ticket_list_screen_content':
					?>
					<ul>
						<li><strong><?php _e( 'All, Unanswered, Answered, Solved - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Filters to see ticket sorted by status. All tickets are listed in latest first order.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Filter by status, Filter by assignee, Filter by product - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Used to sort tickets.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Customers - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'Non-staff people on ticket who have been added by ticket author/customer.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Staff - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'staff people on ticket who have access to view ticket.', 'rtbiz-helpdesk' ); ?></li>
						<li><strong><?php _e( 'Reply count  - ', 'rtbiz-helpdesk' ); ?></strong><?php _e( 'The number of replies added on a ticket.', 'rtbiz-helpdesk' ); ?></li>
					</ul>
					<?php
					break;
				case 'dashboard_overview':
					?>
					<p>
						<?php echo esc_attr( sprintf( __( 'Welcome to your %s Dashboard!', 'rtbiz-helpdesk' ), 'Ticket' ) ); ?>
						<?php _e( 'You can get help for any screen by clicking the Help tab in the upper corner.', 'rtbiz-helpdesk' ); ?>
					</p>
					<?php
					break;
				case 'dashboard_screen_content':
					?>
					<p>
						<?php _e( 'This screen will give you the generic overview of the Tickets, states within the system.', 'rtbiz-helpdesk' ) ?>
						<?php _e( 'It will show the various chart distribution based on the attributes assigned to the contacts & their terms.', 'rtbiz-helpdesk' ); ?>
					</p>
					<?php
					break;
				case 'settings_overview':
					$menu_label = Rt_Biz_Settings::$settings['menu_label'];
					?>
					<p>
						<?php echo esc_attr( sprintf( __( 'This screen consists of all the %s settings.', 'rtbiz-helpdesk' ), $menu_label ) ); ?>
						<?php _e( 'The settings are divided into different tabs depending upon their functionality.', 'rtbiz-helpdesk' ); ?>
						<?php _e( 'You can configure & update them according to your choice from here.', 'rtbiz-helpdesk' ); ?>
						<?php _e( 'There\'s also a buttom named "Reset to Default" which will put all settings to its default values.', 'rtbiz-helpdesk' ); ?>
					</p>
					<?php
					break;
				case 'user_group_overview':
					?>
					<p>
						<?php _e( 'This screen is useful when you have to introduce departments within your company.', 'rtbiz-helpdesk' ); ?>
						<?php _e( 'You can create, edit, delete departments & perfom other CRUD operations from here.', 'rtbiz-helpdesk' ); ?>
						<?php _e( 'These departments can be later assigned to contacts to further categorize them.', 'rtbiz-helpdesk' ); ?>
						<?php _e( 'They will also be useful in defining Access Control for the system & its other modules.', 'rtbiz-helpdesk' ); ?>
					</p>
					<?php
					break;
				case 'user_group_screen_content':
					?>
					<ul>
						<li><?php _e( 'Using the left column form, you can create new departments.', 'rtbiz-helpdesk' ); ?></li>
						<li><?php _e( 'You can assign an group email address to the department as well, if in use.', 'rtbiz-helpdesk' ); ?></li>
						<li><?php _e( 'You can also assign a color code to the department. It will help you identify the department or the user from which department he is just by the color.', 'rtbiz-helpdesk' ); ?></li>
						<li><?php _e( 'On the right column, there will be existing departments listed along with basic information related to the department.', 'rtbiz-helpdesk' ); ?></li>
						<li><?php _e( 'You can edit an individual department on the Edit Department Screen.', 'rtbiz-helpdesk' ); ?></li>
					</ul>
					<?php
					break;
				case 'attribute_overview':
					?>
					<p>
						<?php _e( 'This screen will let you add attribute.', 'rtbiz-helpdesk' ) ?>
						<?php //_e( 'It will show the various chart distribution based on the attributes assigned to the contacts & their terms.' ); ?>
					</p>
					<?php
					break;
				default:
					do_action( 'rtbiz_hd_help_tab_content', $screen, $tab );
					break;
			}
		}

	}

}
