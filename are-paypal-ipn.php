<?php

//Wordpress context
define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');
require_once(ABSPATH . WPINC . '/registration.php');

@ini_set('display_errors',0);
define('WP_DEBUG',         true);  // Turn debugging ON
define('WP_DEBUG_DISPLAY', false); // Turn forced display OFF
define('WP_DEBUG_LOG',     true);  // Turn logging to wp-content/debug.log ON


require('are-paypal-configuration.php');

$appcfg = new Are_PayPal_Configuration();


$prefix = $appcfg->prefix;

/////////////////////////////////////////////////
/////////////Begin Script below./////////////////
/////////////////////////////////////////////////

// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';

foreach ($_REQUEST as $key => $value) {
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

// post back to PayPal system to validate
$paypal_postback_url=$appcfg->paypal_postback_url;

$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n";
$header .= "Host: " .$paypal_postback_url."\r\n\r\n";


arepaypal_ipn_log("ARE PAYPAL: ".$paypal_postback_url);
$fp = fsockopen ("ssl://".$paypal_postback_url, 443, $errno, $errstr, 30);

$notify_email =  get_option('admin_email');		 //email address to which debug emails are sent to
error_reporting(E_ALL);
$RequestID = store_paypal_request_fields();

if (!$fp) {
	// HTTP ERROR
	arepaypal_ipn_log("ARE PAYPAL HTTP ERROR");
} else {
	arepaypal_ipn_log("ARE PAYPAL fp OK");
	fputs ($fp, $header . $req);
	arepaypal_ipn_log("ARE PAYPAL fputs OK");
	while (!feof($fp)) {
		$res = fgets ($fp, 1024);
		arepaypal_ipn_log("ARE PAYPAL fgets result: ".$res);
		if (strcmp ($res, "VERIFIED") == 0) {
			$txn_type = $_REQUEST['txn_type'];
			arepaypal_ipn_log("ARE PAYPAL txn_type: ".$txn_type);			
			process_txn_type($txn_type,$RequestID);
			ArePayPalMail($notify_email, "VERIFIED IPN", "$res\n $req");
		}else if (strcmp ($res, "INVALID") == 0) {
			// log for manual investigation
			ArePayPalMail($notify_email, "INVALID IPN", "$res\n $req");
		}
	}
	fclose ($fp);
}
function store_paypal_request_fields(){
	global $wpdb;
	global $appcfg;
	$wpdb->show_errors();
	$wpdb->query("insert into $appcfg->paypal_requests_table(RequestID)Values('')");
	$requestid=$wpdb->insert_id;
	foreach ($_REQUEST as $key => $value) {
		$value = mysql_escape_string($value);
		$key = mysql_escape_string($key);
		store_field($key);
		$wpdb->query("INSERT INTO $appcfg->paypal_fields_table(RequestID,Name,Value) Values('$requestid','$key','$value')");
	}
	return $requestid;
}
function store_field($fieldName){
	global $wpdb;
	global $appcfg;
	$sihay = $wpdb->get_results("SELECT * FROM $appcfg->paypal_field_types_table WHERE FieldTypeName='$fieldName'");
	if (!$sihay){
		$wpdb->query("INSERT INTO $appcfg->paypal_field_types_table (FieldTypeName) Values('$fieldName')");
	}
}
function process_txn_type($txn_type,$RequestID){
	mailIPN($RequestID,$txn_type);
	if  (function_exists($txn_type.'_handler')){
		$TxnTypeHandler = create_function('$RequestID', 'return '.$txn_type.'_handler($RequestID);');
		return $TxnTypeHandler($RequestID);
	}
}

function cart_handler($RequestID){
}
function express_checkout_handler($RequestID){
}
function merch_pmt_handler($RequestID){
}
function send_money_handler($RequestID){
}
function virtual_terminal_handler($RequestID){
}
function web_accept_handler($RequestID){
	global $wpdb;
	global $appcfg;

	$IPNVarArr = IPNVariableArray($RequestID);

	if(stripos($IPNVarArr["custom"],"are-paypal-custom#")!==false){
		$str = $IPNVarArr["custom"];
		$str = str_ireplace("are-paypal-custom#", "", $str);
		$pairs = explode("&",$str);
		foreach($pairs as &$pair){
			list($key,$value) = explode("=",$pair);
			$customInfo[$key]=urldecode($value);
		}
		$user_id = mysql_escape_string ( $customInfo["user_id"] );
		$post_id = mysql_escape_string ( $customInfo["post_id"] );
		$wp_affiliate_id = ( $customInfo["wp_affiliate_id"] );
	}else{
		list ( $post_id, $user_id ) = explode ( "|", $IPNVarArr["custom"] );
		$user_id = mysql_escape_string ( $user_id );
		$post_id = mysql_escape_string ( $post_id );
	}

	if ($wp_affiliate_id){
		wp_aff_award_commission($wp_affiliate_id,$IPNVarArr["mc_gross"],$IPNVarArr["txn_id"],$post_id,$IPNVarArr["payer_email"]);
	}


	//Duplicate txn_id
	$txn_id = $IPNVarArr['txn_id'];
	$cnt = $wpdb->get_results("SELECT count(*) as Cnt FROM $appcfg->paypal_fields_table WHERE Name='txn_id' and Value='$txn_id' ");
	if ($cnt[0]->Cnt > 1){
		mailIPNStatus("Duplicate txt","Duplicate Txn");
		return;
	}
	//False email
	$paypal_email = get_option($appcfg->prefix.'_PayPal_Email');
	if ($paypal_email != $IPNVarArr ['receiver_email']){
		mailIPNStatus("Wrong email","Wrong email");
		return;
	}
	//Wrong amount
	if ($post_id>0){
		$sql = "SELECT items.* FROM $appcfg->paid_items_table items WHERE items.post_id='$post_id'";
		$items = $wpdb->get_results($sql);
		if($items) {
			if ($items[0]->amount != $IPNVarArr['mc_gross']){
				mailIPNStatus("Wrong amount","Wrong amount");
				return;
			}
		}
	}else if($post_id==-1){
		if (get_option($appcfg->prefix.'_BlogAmount') != $IPNVarArr['mc_gross']){
			mailIPNStatus("Wrong amount","Wrong amount");
			return;
		}
	}else if($post_id==-2){
		if (get_option($appcfg->prefix.'_PayToRegisterAmount') != $IPNVarArr['mc_gross']){
			mailIPNStatus("Wrong amount","Wrong amount");
			return;
		}
		$user_name = $IPNVarArr['payer_email'];
		$user_email = $IPNVarArr['payer_email'];
		 
		$user_id = username_exists( $user_name );
		arepaypal_ipn_log("web_accept_handler user id: ".$user_id);
		if ( !$user_id ) {
			$random_password = wp_generate_password( 12, false );
			$user_id = wp_create_user( $user_name, $random_password, $user_email );
			update_usermeta( $user_id, $appcfg->prefix."_UserMeta", "<PayToRegisterUserMetaData/>" );
			mailIPNStatus("User created","email: $user_name, username: $user_name, password: $random_password");
		}else{
			mailIPNStatus("User retrieved by email","email: $user_name. User reniewed.");
		}
	}

	PayForItem($post_id,$user_id);
}
function masspay_handler($RequestID){
}
function subscr_failed_handler($RequestID){
	global $wpdb;
	global $appcfg;

	$IPNVarArr = IPNVariableArray($RequestID);

	if(stripos($IPNVarArr["custom"],"are-paypal-custom#")!==false){
		$str = $IPNVarArr["custom"];
		$str = str_ireplace("are-paypal-custom#", "", $str);
		$pairs = explode("&",$str);
		foreach($pairs as &$pair){
			list($key,$value) = explode("=",$pair);
			$customInfo[$key]=urldecode($value);
		}
		$user_id = mysql_escape_string ( $customInfo["user_id"] );
		$post_id = mysql_escape_string ( $customInfo["post_id"] );
	}else{
		list ( $post_id, $user_id ) = explode ( "|", $IPNVarArr["custom"] );
		$user_id = mysql_escape_string ( $user_id );
		$post_id = mysql_escape_string ( $post_id );
	}
	if ($post_id="-2"){
		$user_name = $IPNVarArr['payer_email'];
		$user_id = username_exists( $user_name );
		mailIPNStatus("User retrieved by email","email: $user_name. User suspended");
	}
	UnPayForItem($post_id,$user_id);
}
function subscr_cancel_handler($RequestID){
	subscr_failed_handler($RequestID);
}
function subscr_payment_handler($RequestID){
	web_accept_handler($RequestID);
}
function subscr_signup_handler($RequestID){
}
function subscr_eot_handler($RequestID){
	subscr_failed_handler($RequestID);
}
function subscr_modify_handler($RequestID){
}
function new_case_handler($RequestID){
}
function IPNVariableArray($RequestID){
	$RequestID = mysql_escape_string($RequestID);
	global $wpdb;
	global $appcfg;
	$items = $wpdb->get_results("SELECT * FROM $appcfg->paypal_fields_table WHERE RequestID='$RequestID'");
	$IPNVariableArr = array();
	if ($items){
		foreach($items as $item){
			$IPNVariableArr	[$item->Name]=$item->Value;
		}
	}
	return $IPNVariableArr;
}
function IPNVariableArray2EmailText($IPNVariableArr){
	$str="";
	foreach($IPNVariableArr as $key => $value){
		$str.=$key."\t".$value."\n";
	}
	return $str;
}
function mailIPNStatus($Subj,$Content){
	$notify_email =  get_option('admin_email');
	ArePayPalMail($notify_email, "IPN ".$Subj, $Content);
	echo($Subj." ".$Content);
}
function mailIPN($RequestID,$txnType){
	$notify_email =  get_option('admin_email');
	ArePayPalMail($notify_email, "IPN ".$txnType, IPNVariableArray2EmailText(IPNVariableArray($RequestID)));
}

function UnPayForItem($post_id,$user_id){
	global $wpdb;
	global $appcfg;
	$post_id=mysql_escape_string($post_id);
	$user_id=mysql_escape_string($user_id);
	$wpdb->query("DELETE FROM $appcfg->paid_users_table WHERE post_id='$post_id' AND user_id='$user_id'");
	$bonuses = $wpdb->get_results("SELECT post2_id FROM . $appcfg->bonus_posts_table WHERE post1_id='$post_id'");
	foreach($bonuses as $bonus) {
		UnPayForItem($bonus->post2_id,$user_id);
	}
}
function PayForItem($post_id,$user_id){
	arepaypal_ipn_log("Pay For Item ".$post_id." user id: ".$user_id);
	global $wpdb;
	global $appcfg;
	$post_id=mysql_escape_string($post_id);
	$user_id=mysql_escape_string($user_id);

	$items = $wpdb->get_results("SELECT * FROM . $appcfg->paid_items_table WHERE post_id='$post_id'");
	$expire='';
	$expiration_unit = 'D';
	if ($items){
		$expire=$items[0]->expire;
		$expiration_unit = $items[0]->expiration_unit;
	}
	if ($post_id=="-1"){
		$expire = get_option($appcfg->prefix.'_BlogExpire');
		$expiration_unit = get_option($appcfg->prefix.'_BlogExpirationUnits');
	}
	if ($post_id=="-2"){
		$expire = get_option($appcfg->prefix.'_PayToRegisterExpire');
		$expiration_unit = get_option($appcfg->prefix.'_PayToRegisterExpirationUnits');
	}
	
	$wpdb->query("INSERT INTO $appcfg->paid_users_table(post_id,user_id,expire,expiration_unit) VALUES('$post_id','$user_id','$expire','$expiration_unit')");
	$bonuses = $wpdb->get_results("SELECT post2_id FROM . $appcfg->bonus_posts_table WHERE post1_id='$post_id'");
	foreach($bonuses as $bonus) {
		PayForItem($bonus->post2_id,$user_id);
	}
}
function ArePayPalMail($email,$subject,$content){
	global $appcfg;
	if ($appcfg->Suppress_Notification_Emails != 'checked'){
		mail($email,$subject,$content);
	}
	arepaypal_ipn_log("email: ".$email." subject: ".$subject." content: ".$content);
}

function arepaypal_ipn_log( $message ) {
  if( WP_DEBUG === true ){
    if( is_array( $message ) || is_object( $message ) ){
      error_log( print_r( $message, true ) );
    } else {
      error_log( $message );
    }
  }
}

?>