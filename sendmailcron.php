<?php
if ( ! defined( 'WP_LOAD_PATH' ) ) {
        $path ="../../../";
	if ( file_exists( $path . 'wp-load.php' ) )
		define( 'WP_LOAD_PATH', $path );
	else
		exit( "Could not find wp-load.php" );
}

require_once( WP_LOAD_PATH . 'wp-load.php');
global $rt_crm_settings;
$emailRow = $rt_crm_settings->get_new_sent_mail();
if( empty( $emailRow ) )
    return ;
$crmZendEmail = new Rt_CRM_Zend_Mail();
$accessTokenArray= array();
$signature = "";
foreach($emailRow as $email){
   if(!isset($accessTokenArray[$email->fromemail])){
	   $email_type = '';
	   $imap_server = '';
       $accessTokenArray[$email->fromemail] = array(
		   'token' => $rt_crm_settings->get_accesstoken_from_email( $email->fromemail,$signature, $email_type, $imap_server ),
		   'email_type' => $email_type,
		   'imap_server' => $imap_server,
		);
   }
   if ( $rt_crm_settings->update_sent_email( $email->id, 'p', 'no' ) > 0 ) {
      echo $email->id;
       $updateFlag=false;
       try{
            $crmZendEmail->sendemail(
				$email->fromemail,
				$accessTokenArray[$email->fromemail]['token'],
				$accessTokenArray[$email->fromemail]['email_type'],
				$accessTokenArray[$email->fromemail]['imap_server'],
				$email->subject,
				$email->body,
				unserialize( $email->toemail ),
				unserialize( $email->ccemail ),
				unserialize( $email->bccemail ),
				unserialize( $email->attachement )
			);
            $updateFlag= true;
       } catch( Exception $e ) {
		   var_dump($e->getMessage());
           $updateFlag=false;
       }
       if($updateFlag){
           $rt_crm_settings->update_sent_email($email->id,'yes','p');
       }else{
           $rt_crm_settings->update_sent_email($email->id,'error','p');
		   echo "Error: " .$email->id ."<br />";
       }
   } else {
       echo "Error: " .$email->id ."<br />";
   }

}