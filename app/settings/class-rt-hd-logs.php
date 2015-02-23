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
 * Description of Rt_HD_Logs
 * render UI of logs
 *
 * @author udit
 *
 * @since  rt-Helpdesk 0.1
 */
if ( ! class_exists( 'Rt_HD_Logs' ) ) {
	class Rt_HD_Logs {
		function ui() {

			if ( ! isset( $_REQUEST['generate_log'] ) ) {
			?>
				<p class="redux-container-multi_text rthd_log"><span class="redux-multi-text-remove"><?php _e( 'Log generation is a heavy process. So please be patient.' ); ?></span></p>
				<a class="button rthd-generate-log-button" href="<?php echo add_query_arg( 'generate_log', 'yes' ); ?>"><?php _e( 'Generate Log' ); ?></a>
			<?php
				return;
			}

			global $wpdb;
			$editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );
			//			if( !current_user_can( $editor_cap ) ) {
			//				wp_die("Opsss!! You are in restricted area");
			//			}
			//Delete lead code
			if ( isset( $_REQUEST['log-list'] ) ) {
				rthd_get_template( 'admin/list-transaction-post.php' );

				return;
			}
			if ( isset( $_REQUEST['transa_id'] ) ) {
				global $wpdb;
				$tran_id     = $_REQUEST['transa_id'];
				$post_id_sql = $wpdb->prepare( "select post_id from $wpdb->postmeta where meta_key like '_transaction_id' and meta_value = %s ", $tran_id );

				$post_ids = $wpdb->get_results( $post_id_sql );
				$strPost  = '';
				$sep      = '';
				foreach ( $post_ids as $pid ) {
					$strPost .= $sep . $pid->post_id;
					$sep = '.';
				}
				if ( $strPost != '' ) {
					$wpdb->get_results( "delete from $wpdb->posts where ID in ({$strPost})" );
					$wpdb->get_results( "delete from $wpdb->term_relationships where object_id in ({$strPost})" );
					$wpdb->get_results( "delete from $wpdb->postmeta where post_id in ({$strPost})" );
				}
				if ( class_exists( 'RGForms' ) ) {
					$table_name = RGFormsModel::get_lead_meta_table_name();
					$strPost    = '';
					$sep        = '';


					$lead_id_sql = $wpdb->prepare( "select lead_id from $table_name where meta_key like '_transaction_id' and meta_value like %s ", $tran_id );
					$lead_ids    = $wpdb->get_results( $lead_id_sql );
					foreach ( $lead_ids as $pid ) {
						$strPost .= $sep . $pid->lead_id;
						$sep = '.';
					}
					if ( $strPost != '' ) {
						$wpdb->get_results( "delete from $table_name where lead_id in ({$strPost})" );
					}
				}
			}

			// -- End --

			if ( isset( $_REQUEST['size'] ) ) {
				$size = intval( $_REQUEST['size'] );
				if ( $size > 100 ) {
					$size = 100;
				}
			} else {
				$size = 20;
			}
			if ( isset( $_REQUEST['page'] ) && intval( $_REQUEST['page'] ) > 1 ) {
				$left = ( ( intval( $_REQUEST['page'] ) - 1 ) * $size );
			} else {
				$left = 0;
			}
			$taxmeta = $wpdb->prefix . 'taxonomymeta';
			$post_type = Rt_HD_Module::$post_type;

			$sql         = $wpdb->prepare( 'select gf2.meta_value as trans_id from ' . RGFormsModel::get_lead_meta_table_name() . ' as gf1, ' . RGFormsModel::get_lead_meta_table_name() . " as gf2 where gf1.meta_key LIKE 'helpdesk-" . $post_type . "-post-id' and gf2.meta_key LIKE '_transaction_id' and gf1.lead_id = gf2.lead_id group by convert( gf2.meta_value, UNSIGNED INTEGER) order by convert( gf2.meta_value, UNSIGNED INTEGER) desc limit %d, %d", $left, $size );
			//$sql         = $wpdb->prepare( "select p.meta_value as trans_id from (select distinct meta_value from $wpdb->posts as p left join $wpdb->postmeta as m on p.ID = m.post_id where p.post_type=$post_type m.meta_key like '_transaction_id' order by convert(meta_value, UNSIGNED INTEGER) desc limit %d, %d) as p;", $left, $size );
			$result      = $wpdb->get_results( $sql );
			?>
			<br/>
			<table class="wp-list-table widefat fixed">
				<thead>
				<tr>
					<th>
						<?php _e( 'Transaction Id', RT_HD_TEXT_DOMAIN ); ?>
					</th>
<!--					<th>-->
<!--						--><?php //_e( 'Title', RT_HD_TEXT_DOMAIN ); ?>
<!--					</th>-->
					<th>
						<?php _e( 'First Date', RT_HD_TEXT_DOMAIN ); ?>
					</th>
					<th>
						<?php _e( 'Last Date', RT_HD_TEXT_DOMAIN ); ?>
					</th>
					<th>
						<?php _e( 'Post Count', RT_HD_TEXT_DOMAIN ); ?>
					</th>
					<th>
						<?php _e( 'Taxonomy Count', RT_HD_TEXT_DOMAIN ); ?>
					</th>
					<th>
						<?php _e( 'Transaction Start Time', RT_HD_TEXT_DOMAIN ); ?>
					</th>
					<!--<th>

					</th>-->
				</tr>
				</thead>

				<?php
			foreach ( $result as $rslt ) {
					?>
					<tr>
						<td>
							<?php echo esc_attr( $rslt->trans_id ); ?>
						</td>
<!--						<td>-->
<!--							--><?php
//								$title = $wpdb->get_var( "select a.post_title from $wpdb->posts a left join $wpdb->postmeta b on b.post_id=a.id where b.meta_value=$rslt->trans_id and b.post_id=$rslt->post_id limit 1" );
//								echo ( ! empty( $title ) ) ? $title : "-NA-";
//							?>
<!--						</td>-->
						<td>
							<?php
								$first_date = $wpdb->get_var( "select a.post_date from $wpdb->posts a left join $wpdb->postmeta b on b.post_id=a.id where b.meta_value=$rslt->trans_id order by a.post_date asc limit 1" );
								echo ( ! empty( $first_date ) ) ? $first_date : "-NA-";
							?>
						</td>
						<td>
							<?php
								$last_date = $wpdb->get_var( "select a.post_date from $wpdb->posts a left join $wpdb->postmeta b on b.post_id=a.id where b.meta_value=$rslt->trans_id order by a.post_date desc limit 1" );
								echo ( ! empty( $last_date ) ) ? $last_date : "-NA-";
							?>
						</td>
						<td>
							<?php
								$post_count = $wpdb->get_var( "select count(*) as post_count from $wpdb->postmeta where meta_value like $rslt->trans_id" );
								echo ( ! empty( $post_count ) ) ? $post_count : "-NA-";
							?>
						</td>
						<td>
							<?php
								$tax_count = $wpdb->get_var( "select count(*) as tax_count from $taxmeta where meta_value like $rslt->trans_id" );
								echo ( ! empty( $tax_count ) ) ? $tax_count : "-NA-";
							?>
						</td>
						<td>
							<?php echo esc_attr( date( 'Y-m-d H:i:s', intval( $rslt->trans_id ) ) );
							?>
						</td>
						<!--<td>
							<a class="revertChanges"
							   href="edit.php?post_type=rt_ticket&page=rthd-settings&transa_id=<?php /*echo esc_attr( $rslt->trans_id ); */?>"
							   data-trans="<?php /*echo esc_attr( $rslt->trans_id ); */?>"> Revert Changes </a> &nbsp; |
							&nbsp; <a
								href="edit.php?post_type=rt_ticket&page=rthd-settings&log-list=log-list&trans_id=<?php /*echo esc_attr( $rslt->trans_id ); */?>"
								data-trans="<?php /*echo esc_attr( $rslt->trans_id ); */?>"> View Post </a>

						</td>-->
					</tr>


				<?php
				}
				?>
			</table>
		<?php
		}
	}
}
