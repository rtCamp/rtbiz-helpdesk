<?php
if ( ! isset( $_REQUEST['trans_id'] ) ) {
	return;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

global $wpdb, $rt_hd_contacts, $rt_hd_accounts;
$post_id_sql     = "select p.id as ID ,p.post_title as post_title ,p.post_status as post_status , p.post_type as post_type,
    date_format(p.post_date,'%d-%m-%Y %H:%i:%s ') as post_date,date_format(p.post_modified,'%d-%m-%y %h:%i') as post_modified,
    u.display_name as uname,u.Id as user_id from $wpdb->posts p inner join $wpdb->users u on p.post_author=u.ID where p.ID in ( select post_id from $wpdb->postmeta where meta_key like '_transaction_id' and meta_value = '{$_REQUEST["trans_id"]}') ";
$example_data    = $wpdb->get_results( $post_id_sql );

$post_type       = '';
if ( isset( $example_data[0] ) ) {
	$post_type       = get_post_type( $example_data[0]->ID );
}
?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Ticket Created in Transaction <?php $_REQUEST['trans_id']; ?> </h2>    <br>
	<table class="wp-list-table widefat fixed">
		<thead>
		<tr>
			<th style='width:80px'>
				ID
			</th>
			<th>
				Title
			</th>

			<th>
				Status
			</th>
			<th>
				author
			</th>
			<th>
				Created On
			</th>
			<th>
				Contacts
			</th>
			<th>
				Accounts
			</th>
		</tr>
		</thead>

<?php
foreach ( $example_data as $rslt ) {
	?>
	<tr>
		<td>
			<?php echo esc_html( $rslt->ID ); ?>
		</td>
		<td>
			<a target="_blank"
			   href="<?php echo esc_url( get_edit_post_link( $rslt->ID ) ); ?>"><?php echo esc_html( $rslt->post_title ); ?></a>
		</td>


		<td>
			<?php echo esc_html( $rslt->post_status ); ?>
		</td>
		<td>
			<?php
			$url = esc_url( add_query_arg(
				array(
					'post_type' => $rslt->post_type,
					'page'      => 'rthd-all-' . $rslt->post_type,
				), admin_url( 'edit.php' ) ) );
			$url = esc_url( add_query_arg( 'assignee', $rslt->user_id, $url ) );
			?>
			<a target="_blank" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $rslt->uname ); ?></a>
		</td>
		<td>
			<?php echo esc_html( $rslt->post_date ); ?>
		</td>
		<td>
			<?php
			$post_terms   = rt_biz_get_post_for_contact_connection( $rslt->ID, $rslt->post_type, $fetch_contact = true );
			$contact_name = rt_biz_get_contact_post_type();

			$sep      = '';
			$base_url = esc_url( add_query_arg(
				array(
					'post_type' => $rslt->post_type,
					'page'      => 'rthd-all-' . $rslt->post_type,
				), admin_url( 'edit.php' ) ) );
	if ( $post_terms ) {
		foreach ( $post_terms as $pterm ) {
			$contact = get_post( $pterm );
			$url     = esc_url( add_query_arg( $contact_name, $contact->ID, $base_url ) );
			$email   = rt_biz_get_entity_meta( $contact->ID, Rt_Contact::$primary_email_key, true );
			echo esc_attr( $sep ) . "<a target='_blank' href='" . esc_url( $url ) . "'>" . get_avatar( $email, 24 ) . esc_attr( $contact->post_title ). '</a>';
			$sep = ',';
		}
	}
			?>
		</td>
		<td>
			<?php
			$post_terms   = rt_biz_get_post_for_company_connection( $rslt->ID, $rslt->post_type, $fetch_company = true );
			$account_name = rt_biz_get_company_post_type();

			$sep = '';
	if ( $post_terms ) {
		foreach ( $post_terms as $pterm ) {
			$account = get_post( $pterm );
			$url     = esc_url( add_query_arg( $account_name, $account->ID, $base_url ) );
			$email   = rt_biz_get_entity_meta( $account->ID, Rt_Contact::$primary_email_key, true );
			echo esc_attr( $sep ) . "<a target='_blank' href='" . esc_url( $url ) . "'>" . get_avatar( $email, 24 ) . esc_html( $account->post_title ). '</a>';
			$sep = ',';
		}
	}
			?>
		</td>
			</tr>
		<?php
		}
		?>
	</table>
</div>
