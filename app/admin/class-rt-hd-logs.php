<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_HD_Logs
 *
 * @author udit
 */
if( !class_exists( 'Rt_HD_Logs' ) ) {
	class Rt_HD_Logs {
		function ui() {
			global $wpdb;
			$editor_cap = rt_biz_get_access_role_cap( RT_HD_TEXT_DOMAIN, 'editor' );
			if( !current_user_can( $editor_cap ) ) {
				wp_die("Opsss!! You are in restricted area");
			}
			//Delete lead code
			if(isset($_REQUEST["log-list"])){
				rthd_get_template( 'admin/list-transaction-post.php' );
				return;
			}
			if (isset($_REQUEST["transa_id"])) {
				global $wpdb;
				$tran_id = $_REQUEST["transa_id"];
				$post_id_sql = $wpdb->prepare("select post_id from $wpdb->postmeta where meta_key like '_transaction_id' and meta_value = %s ", $tran_id);

				$post_ids = $wpdb->get_results($post_id_sql);
				$strPost = "";
				$sep = "";
				foreach ($post_ids as $pid) {
					$strPost .= $sep . $pid->post_id;
					$sep=",";
				}
				if($strPost !=""){
					$wpdb->get_results("delete from $wpdb->posts where ID in ({$strPost})");
					$wpdb->get_results("delete from $wpdb->term_relationships where object_id in ({$strPost})");
					$wpdb->get_results("delete from $wpdb->postmeta where post_id in ({$strPost})");
				}
				if (class_exists("RGForms")) {
					$table_name = RGFormsModel::get_lead_meta_table_name();
					$strPost = "";
					$sep = "";


					$lead_id_sql = $wpdb->prepare("select lead_id from $table_name where meta_key like '_transaction_id' and meta_value like %s ", $tran_id);
					$lead_ids= $wpdb->get_results($lead_id_sql);
					foreach ($lead_ids as $pid) {
						$strPost .= $sep . $pid->lead_id;
						$sep=",";
					}
					if($strPost!=""){
						$wpdb->get_results("delete from $table_name where lead_id in ({$strPost})");
					}

				}
			}

			// -- End --

			if (isset($_REQUEST["size"])) {
				$size = intval($_REQUEST["size"]);
				if ($size > 100) {
					$size = 100;
				}
			} else {
				$size = 20;
			}
			if (isset($_REQUEST["page"]) && intval($_REQUEST["page"]) > 1) {
				$left = ((intval($_REQUEST["page"]) - 1) * $size);
			} else {
				$left = 0;
			}
			$taxmeta = $wpdb->prefix . "taxonomymeta";
			if (class_exists("RGFormsModel")) {
				$gravity_query = ",(select count(*) from " . RGFormsModel::get_lead_meta_table_name() . " where meta_value like p.meta_value) as gravity_meta ";
			} else {
				$gravity_query = "";
			}
			$gravitymeta = "";
			$sql = $wpdb->prepare("select p.meta_value as trans_id, (select count(*) from $wpdb->postmeta where meta_value
			  like p.meta_value) as post, (select count(*) from $taxmeta where meta_value like p.meta_value) as texonomy {$gravity_query} ,
					(select a.post_title from $wpdb->posts a left join $wpdb->postmeta b on b.post_id=a.id where b.meta_value=p.meta_value limit 1) as title,
					(select a.post_date from $wpdb->posts a left join $wpdb->postmeta b on b.post_id=a.id where b.meta_value=p.meta_value order by a.post_date asc limit 1) as firstdate,
					(select a.post_date from $wpdb->posts a left join $wpdb->postmeta b on b.post_id=a.id where b.meta_value=p.meta_value order by a.post_date desc limit 1) as lastdate
					from (select distinct meta_value from $wpdb->postmeta where meta_key like '_transaction_id' order by convert(meta_value, UNSIGNED INTEGER) desc limit %d, %d) as p;", $left, $size);
			$result = $wpdb->get_results($sql);
			?>
<br/>
				<table class="wp-list-table widefat fixed">
					<thead>
						<tr>
							<th>
								Transaction Id
							</th>
							<th>
								Title
							</th>
							<th>
								First Date
							</th>
							<th>
								Last Date
							</th>
							<th>
								Post Count
							</th>
							<th>
								Taxonomy Count
							</th>
	<!--                        <th>
								Gravity Meta Count
							</th>-->
							<th>
								Transaction Start Time
							</th>
							<th>

							</th>
						</tr>
					</thead>

			<?php
			foreach ($result as $rslt) {
				?>
						<tr>
							<td>
				<?php echo $rslt->trans_id; ?>
							</td>
							<td>
				<?php echo $rslt->title; ?>
							</td>
							<td>
				<?php echo $rslt->firstdate; ?>
							</td>
							<td>
				<?php echo $rslt->lastdate; ?>
							</td>
							<td>
				<?php echo $rslt->post; ?>
							</td>

							<td>
				<?php echo $rslt->texonomy; ?>
							</td>
	<!--                        <td>
				<?php if (isset($rslt->gravity_meta)) echo $rslt->gravity_meta; ?>
							</td>-->

							<td>
				<?php echo date('Y-m-d H:i:s', intval($rslt->trans_id));
				?>
							</td>
							<td>
								<a class="revertChanges" href="admin.php?page=rthd-logs&transa_id=<?php echo $rslt->trans_id; ?>" data-trans="<?php echo $rslt->trans_id; ?>" > Revert Changes </a> &nbsp; | &nbsp;
								<a href="admin.php?page=rthd-logs&log-list=log-list&trans_id=<?php echo $rslt->trans_id; ?>" data-trans="<?php echo $rslt->trans_id; ?>" > View Post </a>
							</td>
						</tr>


			<?php }
			?>
				</table>
			<?php
		}
	}
}
