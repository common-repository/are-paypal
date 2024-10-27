<?php
/*
Plugin Name: Are PayPal
Plugin URI: http://arepaypal.ehibou.com/
Description: This plugin is used to monetize wordpress blog content using PayPal.
Version: 1.9.2.4
Author: Aurimas Norkevicius
Author URI: http://are.ehibou.com
*/
/*  Copyright 2007-2014  Aurimas Norkevicius  (email : Aurimas.Norkevicius@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
any later version. Provide the author with your changes to the code.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include_once (dirname ( __FILE__ ) . "/are-paypal-configuration.php");
include_once (dirname ( __FILE__ ) . "/are-paypal-install.php");
include_once (dirname ( __FILE__ ) . "/are-paypal-templates.php");
include_once (dirname ( __FILE__ ) . "/smarty/Smarty.class.php");
include_once (dirname ( __FILE__ ) . "/smarty/SmartyPaginate.class.php");
if (! class_exists ( 'Are_PayPal' )) {
	class Are_PayPal {
		
		var $prefix = "Are_PayPal";
		
		var $paid_users_table;
		var $paid_items_table;
		var $bonus_posts_table;
		var $paypal_requests_table;
		var $paypal_field_types_table;
		var $paypal_fields_table;
		
		var $paypal_url;
		var $paypal_email;
		var $start_delimiter;
		var $end_delimiter;
		var $delimiter_tag_name;
		var $paytoregisterdelimiter_tag_name;
		var $post_type_clause = "(posts.post_type='post' OR posts.post_type='page' OR posts.post_type='')";
		
		var $paid_user_id_sql_criteria = "
							(
								DATE_ADD(up.purchase_date, INTERVAL up.expire DAY) > CURDATE()
								AND up.expiration_unit = 'D'
							) 
							OR
							(
								DATE_ADD(up.purchase_date, INTERVAL up.expire WEEK) > CURDATE()
								AND up.expiration_unit = 'W'
							) 
							OR
							(
								DATE_ADD(up.purchase_date, INTERVAL up.expire MONTH) > CURDATE()
								AND up.expiration_unit = 'M'
							) 
							OR
							(
								DATE_ADD(up.purchase_date, INTERVAL up.expire YEAR) > CURDATE()
								AND up.expiration_unit = 'Y'
							) 
							OR up.expire is null 
							OR up.expire = 0
					";
		
		var $users_library_page;
		var $purchased_posts_list_placeholder;
		var $smarty;
		var $msg_writable_folders;
		
		function load_scripts() {
			wp_enqueue_script ( 'jquery' );
			wp_enqueue_script ( 'jquery-ui-core' );
			wp_enqueue_script ( 'jquery-ui-tabs' );
			wp_enqueue_script ( 'jquery-cookie' );
		}
		function load_styles() {
		
		}
		function init() {
			wp_deregister_script ( 'jquery-cookie' );
			wp_register_script ( 'jquery-cookie', get_bloginfo ( 'wpurl' ) . '/wp-content/plugins/are-paypal/js/jquery.cookie.js' );
		}
		function Are_PayPal() {
			global $wpdb;
			$configuration = new Are_PayPal_Configuration ();
			
			$this->smarty = & new Smarty ();
			$this->smarty->template_dir = dirname ( __FILE__ ) . "/templates/";
			$this->smarty->config_dir = dirname ( __FILE__ ) . "/configs/";
			$this->smarty->assign ( "Prefix", $this->prefix );
			$this->smarty->assign ( "HomeUrl", get_option ( "home" ) );
			
			$SmartyCacheDirectory = get_option ( $this->prefix . '_' . 'SmartyCacheDirectory' );
			if (! $SmartyCacheDirectory || ! is_writable ( $SmartyCacheDirectory )) {
				$SmartyCacheDirectory = dirname ( __FILE__ ) . "/cache/";
				update_option ( $this->prefix . '_' . 'SmartyCacheDirectory', $SmartyCacheDirectory );
			}
			
			$this->smarty->compile_dir = $SmartyCacheDirectory;
			$this->smarty->cache_dir = $SmartyCacheDirectory;
			
			$this->check_writable_folders ();
			
			//Initialize properties
			$this->start_delimiter = $configuration->start_delimiter;
			$this->end_delimiter = $configuration->end_delimiter;
			$this->delimiter_tag_name = $configuration->delimiter_tag_name;
			$this->paytoregisterdelimiter_tag_name = $configuration->paytoregisterdelimiter_tag_name;
			$this->paid_users_table = $configuration->paid_users_table;
			$this->paid_items_table = $configuration->paid_items_table;
			$this->bonus_posts_table = $configuration->bonus_posts_table;
			$this->paypal_requests_table = $configuration->paypal_requests_table;
			$this->paypal_field_types_table = $configuration->paypal_field_types_table;
			$this->paypal_fields_table = $configuration->paypal_fields_table;
			$this->users_library_page = $configuration->users_library_page;
			$this->purchased_posts_list_placeholder = $configuration->purchased_posts_list_placeholder;
			
			$this->paypal_url = $configuration->paypal_url;
			$this->paypal_email = $configuration->paypal_email;
			
			//Hook into wordpress
			add_action ( 'admin_menu', array (&$this, 'Are_PayPal_Configuration' ) );
			if (! $this->msg_writable_folders) {
				add_shortcode ( $this->delimiter_tag_name, array (&$this, 'paid_shortcode_handler' ) );
				add_shortcode ( $this->paytoregisterdelimiter_tag_name, array (&$this, 'paytoregister_shortcode_handler' ) );
				add_filter ( 'the_content', array (&$this, 'purchased_posts' ) );
				add_filter ( 'authenticate', array (&$this, 'wp_authenticate_username_password' ), 20, 3 );
				add_action ( 'wp_head', array (&$this, 'add_html_headers' ) );
				add_action ( 'wp_footer', array (&$this, 'put_my_url_to_footer' ) );
				add_action ( 'admin_head', array (&$this, 'admin_head' ) );
				add_action ( 'init', array (&$this, 'init' ) );
				add_action ( 'admin_print_scripts', array (&$this, 'load_scripts' ) );
				add_action ( 'admin_print_styles', array (&$this, 'load_styles' ) );
			}
			$install = new Are_PayPal_Install ();
			register_activation_hook ( __FILE__, array (&$install, 'install' ) );
			$this->set_templates ();
			
			$sql = "SELECT version() as mysql_version";
			$mysql_version = $wpdb->get_results ( $sql, OBJECT );
			if ($mysql_version) {
				if ($mysql_version [0]->mysql_version < "5") {
					$this->paid_user_id_sql_criteria = "
							(
								ADDDATE(up.purchase_date, up.expire * 1) > CURDATE()
								AND up.expiration_unit = 'D'
							) 
							OR
							(
								ADDDATE(up.purchase_date, up.expire * 7) > CURDATE()
								AND up.expiration_unit = 'W'
							) 
							OR
							(
								ADDDATE(up.purchase_date, up.expire * 30) > CURDATE()
								AND up.expiration_unit = 'M'
							) 
							OR
							(
								ADDDATE(up.purchase_date, up.expire * 365) > CURDATE()
								AND up.expiration_unit = 'Y'
							) 
							OR up.expire is null 
							OR up.expire = 0
					";
				}
			}
		
		}
		
		function admin_head() {
			echo '<script type="text/javascript">';
			require_once ('are-paypal-js.php');
			echo '</script>';
			echo ('<link type="text/css" rel="stylesheet" href="' . get_bloginfo ( 'wpurl' ) . '/wp-content/plugins/are-paypal/css/jquery-ui.css" />');
			echo ('<link type="text/css" rel="stylesheet" href="' . get_bloginfo ( 'wpurl' ) . '/wp-content/plugins/are-paypal/css/are-paypal-admin.css" />');
		}
		
		function check_writable_folders() {
			$this->msg_writable_folders = $this->is_folder_writable ( $this->smarty->cache_dir );
		}
		
		function is_folder_writable($folder) {
			if (! is_writable ( $folder )) {
				return ($folder . " " . __ ( "must be writable" ));
			}
			return "";
		}
		
		function put_my_url_to_footer() {
			$suppress = get_option ( $this->prefix . '_Suppress_MonetizedBy_Link' );
			if ($suppress!='checked'){
				$this->smarty->assign ( 'my_url_text', __ ( "This blog is monetized using Are-PayPal WP Plugin" ) );
				$this->smarty->display ( 'my_url_in_footer.tpl' );
			}
		}
		
		function set_templates() {
			$templates = new Are_PayPal_Templates ();
			$templates->set_templates ();
		}
		function show_login_button($url, $urltext) {
			$templateName = $this->prefix . "_LoginButtonTemplate";
			$result = stripslashes ( get_option ( $templateName ) );
			$explanation = get_option ( $this->prefix . '_TextToShowIfNotLogedIn' );
			$result = str_replace ( '%EXPLANATION%', htmlentities ( $explanation ), $result );
			$result = str_replace ( '%LOGINURL%', htmlentities ( $url ), $result );
			$result = str_replace ( '%LOGINURLTEXT%', htmlentities ( $urltext ), $result );
			return $result;
		}
		function purchased_posts($content) {
			global $wpdb;
			if (strpos ( $content, $this->purchased_posts_list_placeholder ) === false) {
				if (is_page ( $this->users_library_page )) {
					$content .= $this->purchased_posts_list_placeholder;
				}
			}
			if (! (strpos ( $content, $this->purchased_posts_list_placeholder ) === false)) {
				global $current_user, $user_ID;
				$userID = $user_ID;
				if ($userID == 0) {
					$userID = $current_user->ID;
				}
				$sql = "SELECT DISTINCT posts.post_title,posts.guid, items.post_id FROM $wpdb->posts posts INNER JOIN $this->paid_users_table items ON items.post_id=posts.id WHERE user_id ='$userID' AND $this->post_type_clause";
				$purchased_posts = $wpdb->get_results ( $sql, OBJECT );
				if ($purchased_posts) {
					$purchasedPostsList = "";
					foreach ( $purchased_posts as $post ) {
						if ($this->IsPostPurchased ( $post->post_id, $userID )) {
							$purchasedPostsList .= "<p><a href='$post->guid'>$post->post_title</a></p>";
						}
					}
				}
				$content = str_replace ( $this->purchased_posts_list_placeholder, $purchasedPostsList, $content );
			}
			return $content;
		}
		function paytoregister_shortcode_handler($atts, $content = null) {
			global $current_user, $user_ID;
			$userID = $user_ID;
			if ($userID == 0) {
				$userID = $current_user->ID;
			}
			if ($userID) {
				$result = $content;
			} else {
				$PayToRegister_item_amount = get_option ( $this->prefix . '_PayToRegisterAmount' );
				if ($PayToRegister_item_amount) {
					$PayToRegister_item_amount = get_option ( $this->prefix . '_PayToRegisterAmount' );
					$PayToRegister_item_currency = get_option ( $this->prefix . '_PayToRegisterCurrency' );
					$PayToRegister_item_name = get_option ( $this->prefix . '_PayToRegisterName' );
					$PayToRegister_item_number = get_option ( $this->prefix . '_PayToRegisterNumber' );
					$PayToRegister_item_expire = get_option ( $this->prefix . '_PayToRegisterExpire' );
					$PayToRegister_item_expiration_unit = get_option ( $this->prefix . '_PayToRegisterExpirationUnits' );
					$PayToRegister_item_expire = $this->native_expiration_message ( $PayToRegister_item_expire, $PayToRegister_item_expiration_unit );
					$PayToRegisterButton = $this->paypal_buy_now_form ( $PayToRegister_item_name . " " . $PayToRegister_item_expire, $PayToRegister_item_number, $PayToRegister_item_amount, $PayToRegister_item_currency, - 2, $userID, get_option ( $this->prefix . '_BlogExpire' ), $PayToRegister_item_expiration_unit ); //-2 post id for PayToRegister
					$result = $PayToRegisterButton;
				}
			}
			return do_shortcode ( $result );
		}
		function paid_shortcode_handler($atts, $content = null) {
			global $current_user, $user_ID, $post_ID, $post, $id;
			$postID = $post_ID;
			$userID = $user_ID;
			if ($userID == 0) {
				$userID = $current_user->ID;
			}
			if ($postID == 0) {
				if (isset($post->id)){
					$postID = $post->id;
				}
			}
			if ($postID == 0) {
				$postID = $id;
			}
			$result = $content;
			$isPostPurchased = $this->IsPostPurchased ( $postID, $userID );
			if ((! $this->IsGooglebot ()) && (($userID == 0) || (! $isPostPurchased))) {
				if ($userID == 0) {
					$domain = $_SERVER ['HTTP_HOST'];
					$url = "http://" . $domain . $_SERVER ['REQUEST_URI'];
					$LoginButton = $this->show_login_button ( get_option ( 'siteurl' ) . "/wp-login.php?redirect_to=$url", __ ( "Log in" ) );
					$result = $LoginButton;
				} else {
					global $wpdb;
					$sql = "SELECT posts.ID,posts.post_title, items.* FROM $wpdb->posts posts INNER JOIN $this->paid_items_table items ON items.post_id=posts.id WHERE posts.id='$postID'";
					$items = $wpdb->get_results ( $sql );
					if ($items) {
						$item = $items [0];
						$item_id = $item->ID;
						$item_title = $item->post_title;
						$item_amount = $item->amount;
						$item_currency = $item->currency;
						$item_name = $item->name;
						$item_number = $item->number;
						$item_expiration_unit = $item->expiration_unit;
						$item_expire = $this->native_expiration_message ( $item->expire, $item_expiration_unit );
						$postButton = $this->paypal_buy_now_form ( $item_name . ":" . $item_title . " " . $item_expire, $item_number, $item_amount, $item_currency, $postID, $userID, $item->expire, $item_expiration_unit );
					}
					$blog_item_amount = get_option ( $this->prefix . '_BlogAmount' );
					if ($blog_item_amount) {
						$blog_item_amount = get_option ( $this->prefix . '_BlogAmount' );
						$blog_item_currency = get_option ( $this->prefix . '_BlogCurrency' );
						$blog_item_name = get_option ( $this->prefix . '_BlogName' );
						$blog_item_title = $item_title;
						$blog_item_number = get_option ( $this->prefix . '_BlogNumber' );
						$blog_item_expire = get_option ( $this->prefix . '_BlogExpire' );
						$blog_item_expiration_unit = get_option ( $this->prefix . '_BlogExpirationUnits' );
						$blog_item_expire = $this->native_expiration_message ( $blog_item_expire, $blog_item_expiration_unit );
						$blogButton = $this->paypal_buy_now_form ( $blog_item_name . ":" . $blog_item_title . " " . $blog_item_expire, $blog_item_number, $blog_item_amount, $blog_item_currency, - 1, $userID, get_option ( $this->prefix . '_BlogExpire' ), $blog_item_expiration_unit ); //-1 post id for whole site
					}
					if ($blogButton || $postButton) {
						$result = $postButton . $blogButton;
					}
				
				}
			}
			return do_shortcode ( $result );
		}
		
		function translate_expiration_unit($item_expiration_unit) {
			switch ($item_expiration_unit) {
				case "D" :
					return "days";
					break;
				case "W" :
					return "weeks";
					break;
				case "M" :
					return "months";
					break;
				case "Y" :
					return "years";
					break;
				default :
					return "days";
					break;
			}
		}
		
		function native_expiration_message($item_expire, $item_expiration_unit = "D") {
			if ($item_expire) {
				$item_expire = __ ( " Expires in " ) . $item_expire . " " . __ ( $this->translate_expiration_unit ( $item_expiration_unit ) );
			} else {
				$item_expire = "";
			}
			return $item_expire;
		}
		function Are_PayPal_Configuration() {
			global $wpdb;
			if (! $this->msg_writable_folders) {
				if (function_exists ( 'add_submenu_page' )) {
					add_menu_page ( __ ( $this->prefix ), __ ( $this->prefix ), 'edit_posts', __FILE__, array (&$this, 'Main_Configuration_Page' ), '/wp-content/plugins/are-paypal/images/icon_paypal_2Ps_16x14.gif' );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 2' ), __ ( 'Post Prices' ), 'edit_posts', $this->prefix . '_PostSetup', array (&$this, 'Configure_Prices_For_Posts' ) );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 3' ), __ ( 'Paypal data' ), 'edit_posts', $this->prefix . '_PaypalData', array (&$this, 'View_Payments' ) );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 4' ), __ ( 'Blog Price' ), 'edit_posts', $this->prefix . '_BlogPrice', array (&$this, 'Blog_Price' ) );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 5' ), __ ( 'Pay To Register Price' ), 'edit_posts', $this->prefix . '_PayToRegisterPrice', array (&$this, 'PayToRegister_Price' ) );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 6' ), __ ( 'How to use' ), 'edit_posts', $this->prefix . '_HowToUse', array (&$this, 'How_To_Use' ) );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 7' ), __ ( 'Donate' ), 'edit_posts', $this->prefix . '_Donate', array (&$this, 'Donate' ) );
					add_submenu_page ( __FILE__, __ ( $this->prefix . ' Configuration 8' ), __ ( 'View Users' ), 'edit_posts', $this->prefix . '_ViewUsers', array (&$this, 'view_users' ) );
				}
			} else {
				add_menu_page ( __ ( $this->prefix ), __ ( $this->prefix ), 'edit_posts', __FILE__, array (&$this, 'Protected_Folders_Detected' ), '/wp-content/plugins/are-paypal/images/icon_paypal_2Ps_16x14.gif' );
			}
		}
		
		function Protected_Folders_Detected() {
			?>
<div class="wrap">
<h2>Protected Folders Detected</h2>
<p style="font-weight: bold; color: red;">
					<?php
			echo ($this->msg_writable_folders);
			?>
				</p>
</div>
<?php
		}
		
		function Main_Configuration_Page() {
			$LastAction ="";
			global $wpdb;
			check_admin_referer ();
			if (isset ( $_POST ['restoretemplatedefaults'] )) {
				$this->delete_options ( "_InstantPaymentTemplate,_RecurentPaymentTemplate,_LoginButtonTemplate" );
				$this->set_templates ();
			}
			
			if (isset ( $_POST ['restoreloginmessagedefaults'] )) {
				$this->delete_options ( "_LoginMessages_EmptyUserName,_LoginMessages_EmptyPassword,_LoginMessages_InvalidUserName,_LoginMessages_IncorrectPassword,_LoginMessages_PayToRegisterAuthFailed" );
			}
			
			if (isset ( $_REQUEST ['submit'] )) {
				
				$this->set_option ( "_TextToShowIfNotLogedIn", "TextToShowIfNotLogedIn" );
				$this->set_option ( "_PayPal_Email", "PayPal_Email" );
				$this->set_option ( "_test", "test" );
				$this->set_option ( "_TextToShowIfNotPurchased", "TextToShowIfNotPurchased" );
				$this->set_option ( "_InstantPaymentTemplate", "InstantPaymentTemplate" );
				$this->set_option ( "_RecurentPaymentTemplate", "RecurentPaymentTemplate" );
				$this->set_option ( "_LoginButtonTemplate", "LoginButtonTemplate" );
				$this->set_option ( "_Suppress_Notification_Emails", "Suppress_Notification_Emails" );
				$this->set_option ( "_Suppress_MonetizedBy_Link", "Suppress_MonetizedBy_Link" );
				$this->set_option ( "_BonusPostManualSetup", "BonusPostManualSetup" );
				$this->set_option ( "_Users_Library_Page", "users_library_page" );
				$this->set_option ( "_Users_PayToRegister_Page", "paytoregister_page" );
				
				$this->set_option ( "_LoginMessages_EmptyUserName", "LoginMessages_EmptyUserName" );
				$this->set_option ( "_LoginMessages_EmptyPassword", "LoginMessages_EmptyPassword" );
				$this->set_option ( "_LoginMessages_InvalidUserName", "LoginMessages_InvalidUserName" );
				$this->set_option ( "_LoginMessages_IncorrectPassword", "LoginMessages_IncorrectPassword" );
				$this->set_option ( "_LoginMessages_PayToRegisterAuthFailed", "LoginMessages_PayToRegisterAuthFailed" );
				$this->set_option ( "_SmartyCacheDirectory", "SmartyCacheDirectory" );
				
				$LastAction = __ ( "Updated successfully ..." );
			}
			$this->smarty->assign ( 'TabDivID', $this->prefix . '_tabs' );
			$this->smarty->assign ( 'BasicSettingsTabText', __ ( 'Basic Settings' ) );
			$this->smarty->assign ( 'MessagesTabText', __ ( 'UI Messages' ) );
			$this->smarty->assign ( 'TemplatesTabText', __ ( 'PayPal Button Templates' ) );
			$this->smarty->assign ( 'PurchasedItemLibraryTabText', __ ( 'Purchased Item Library' ) );
			$this->smarty->assign ( 'PayToRegisterTabText', __ ( 'Pay To Register' ) );
			$this->smarty->assign ( 'SmartyTabText', __ ( 'Smarty' ) );
			$this->smarty->assign ( 'BonusPostSetupTabText', __ ( 'Bonus Posts' ) );
			
			$this->smarty->assign ( 'LastAction', $LastAction );
			$this->smarty->assign ( 'PageHeader', __ ( $this->prefix . ' Configuration' ) );
			$this->smarty->assign ( 'PayPalSandBoxMode', get_option ( $this->prefix . '_test' ) );
			$this->smarty->assign ( 'UsePayPalSandBoxLinkText', __ ( 'Use PayPal Sandbox' ) );
			$this->smarty->assign ( 'UsePayPalSandBoxNextToLinkText', __ ( '(testing only)' ) );
			$this->smarty->assign ( 'Suppress_Notification_EmailsMode', get_option ( $this->prefix . '_Suppress_Notification_Emails' ) );
			$this->smarty->assign ( 'Suppress_Notification_EmailsLabel', __ ( 'Suppress notification emails' ) );
			
			$this->smarty->assign ( 'Suppress_MonetizedBy_LinkMode', get_option ( $this->prefix . '_Suppress_MonetizedBy_Link' ) );
			$this->smarty->assign ( 'Suppress_MonetizedBy_LinkLabel', __ ( 'Suppress "Monetized By" link' ) );
			
			$this->smarty->assign ( 'BonusPostManualSetup', get_option ( $this->prefix . '_BonusPostManualSetup' ) );
			$this->smarty->assign ( 'BonusPostManualSetupLabel', __ ( 'Setup "Bonus Posts" manually.' ) );
			$this->smarty->assign ( 'BonusPostManualSetupDescription', __ ( 'In case checkbox is unchecked the plugin will set automatically post A as bonus post for post B while manually setting post B as bonus post for post A.In case checkbox is unchecked you will have to set both ends manually.' ) );
			
			$this->smarty->assign ( 'PayPal_EmailLabel', __ ( 'PayPal email' ) );
			$this->smarty->assign ( 'PayPal_Email', stripslashes ( get_option ( $this->prefix . '_PayPal_Email' ) ) );
			$this->smarty->assign ( 'InstantPaymentTemplateLabel', __ ( 'Instant Payment Button Template' ) );
			$this->smarty->assign ( 'InstantPaymentTemplate', htmlentities ( stripslashes ( get_option ( $this->prefix . '_InstantPaymentTemplate' ) ) ) );
			$this->smarty->assign ( 'RecurentPaymentTemplateLabel', __ ( 'Recurent Payment Button Template' ) );
			$this->smarty->assign ( 'RecurentPaymentTemplate', htmlentities ( stripslashes ( get_option ( $this->prefix . '_RecurentPaymentTemplate' ) ) ) );
			$this->smarty->assign ( 'LoginButtonTemplateLabel', __ ( 'Login Button Template' ) );
			$this->smarty->assign ( 'LoginButtonTemplate', htmlentities ( stripslashes ( get_option ( $this->prefix . '_LoginButtonTemplate' ) ) ) );
			$this->smarty->assign ( 'users_library_pageLabel', __ ( 'Page or Post to show users purchased posts' ) );
			$users_library_page = null;
			if (isset($_REQUEST ['users_library_page'])){
				$users_library_page = $_REQUEST ['users_library_page'];
			}
			$this->smarty->assign ( 'Users_Library_Page', get_option ( $this->prefix . '_Users_Library_Page', $users_library_page ) );
			
			$this->smarty->assign ( 'SmartyCacheDirectory' . 'Label', __ ( 'Smarty Cache Directory' ) );
			$SmartyCacheDirectory = null;
			if (isset($_REQUEST ['SmartyCacheDirectory'])){
			
				$SmartyCacheDirectory = $_REQUEST ['SmartyCacheDirectory'];
			}
			$this->smarty->assign ( 'SmartyCacheDirectory', get_option ( $this->prefix . '_' . 'SmartyCacheDirectory', $SmartyCacheDirectory ) );
			
			$SmartyCacheDirectory = get_option ( $this->prefix . '_' . 'SmartyCacheDirectory' );
			if (! $SmartyCacheDirectory) {
				$SmartyCacheDirectory = dirname ( __FILE__ ) . "/cache/";
				update_option ( $this->prefix . '_' . 'SmartyCacheDirectory', $SmartyCacheDirectory );
			}
			
			$OptionFieldName = 'TextToShowIfNotLogedIn';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( 'Text to show if visitor is not logged in' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName ) ) ) );
			
			$OptionFieldName = 'TextToShowIfNotPurchased';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( 'Text to show if content is not purchased by a visitor' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName ) ) ) );
			
			$OptionFieldName = 'LoginMessages_EmptyUserName';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( 'The username field is empty.' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName, '<strong>ERROR</strong>: The username field is empty.' ) ) ) );
			
			$OptionFieldName = 'LoginMessages_EmptyPassword';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( ' The password field is empty.' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName, '<strong>ERROR</strong>: The password field is empty.' ) ) ) );
			
			$OptionFieldName = 'LoginMessages_InvalidUserName';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( ' Invalid user name.' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName, '<strong>ERROR</strong>: Invalid username. <a href="%s" title="Password Lost and Found">Lost your password</a>?' ) ) ) );
			
			$OptionFieldName = 'LoginMessages_IncorrectPassword';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( ' Incorrect password.' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName, '<strong>ERROR</strong>: Incorrect password. <a href="%s" title="Password Lost and Found">Lost your password</a>?' ) ) ) );
			
			$OptionFieldName = 'LoginMessages_PayToRegisterAuthFailed';
			$this->smarty->assign ( $OptionFieldName . 'Label', __ ( '"Pay To Register" authentication failed.' ) );
			$this->smarty->assign ( $OptionFieldName, htmlentities ( stripslashes ( get_option ( $this->prefix . '_' . $OptionFieldName, '<strong>ERROR</strong>: Your paid registration is not valid any more. <a href="%s" title="Pay to Register">Pay To Register</a>?' ) ) ) );
			
			$this->smarty->assign ( 'paytoregister_pageLabel', __ ( 'Page or Post to show PayPal payment button' ) );
			
			$paytoregister_page = null;
			if (isset($_REQUEST ['paytoregister_page'])){
			
				$paytoregister_page = $_REQUEST ['paytoregister_page'];
			}
			
			$this->smarty->assign ( 'Users_PayToRegister_Page', get_option ( $this->prefix . '_Users_PayToRegister_Page', $paytoregister_page ) );
			
			$this->smarty->assign ( 'TextsToShowInPayToRegister', __ ( 'Texts to show in "Pay To Register" routines' ) );
			$this->smarty->assign ( 'TextsToShowInPayForContent', __ ( 'Texts to show in "Pay for Content" routines' ) );
			
			$this->smarty->assign ( 'UpdateOptions', __ ( 'Update Options&raquo;' ) );
			$this->smarty->assign ( 'RestoreDefaults', __ ( 'Restore Template Defaults&raquo;' ) );
			$this->smarty->assign ( 'RestoreLoginMessageDefaults', __ ( 'Restore Pay To Register Message Defaults&raquo;' ) );
			
			$all_posts = $wpdb->get_results ( "SELECT posts.* FROM $wpdb->posts posts WHERE 1=1 AND $this->post_type_clause AND posts.post_type='page'" );
			$this->smarty->assign ( 'Posts', $all_posts );
			$TemplateExamples = array ("InstantPayment" => $this->paypal_buy_now_form ( "Item Name", "Item Number", "1", "EUR", 0, 0, 0, "D" ), "RecurentPayment" => $this->paypal_buy_now_form ( "Item Name", "Item Number", "1", "EUR", 0, 0, 30, "D" ), "LoginButton" => $this->show_login_button ( "URL", "URLTEXT" ) );
			$this->smarty->assign ( 'TemplateExamples', $TemplateExamples );
			$this->smarty->display ( 'main_configuration_page.tpl' );
		}
		
		function Donate() {
			check_admin_referer ();
			$this->smarty->assign ( 'PageHeader', __ ( 'Donate' ) );
			$this->smarty->assign ( 'DonateInfo', __ ( 'Click the button below to donate. Any amount is highly appreciated.' ) );
			$this->smarty->display ( 'donate.tpl' );
		}
		
		function How_To_Use() {
			check_admin_referer ();
			$this->smarty->assign ( 'PageHeader', __ ( 'How to use' ) );
			$this->smarty->assign ( 'StartDelimiter', $this->start_delimiter );
			$this->smarty->assign ( 'EndDelimiter', $this->end_delimiter );
			$this->smarty->assign ( 'PurchasedPostsListPlaceholder', $this->purchased_posts_list_placeholder );
			$this->smarty->assign ( 'PayToRegisterShortcode', $this->paytoregisterdelimiter_tag_name );
			$this->smarty->assign ( 'PayPalUrl', $this->paypal_url );
			$this->smarty->display ( 'how_to_use.tpl' );
		}
		
		function delete_options($options) {
			$options = explode ( ",", $options );
			foreach ( $options as $option ) {
				delete_option ( $this->prefix . $option );
			}
		}
		
		function set_option($option, $rqname) {
			$Value = $_REQUEST [$rqname];
			update_option ( $this->prefix . $option, $Value );
		}
		
		function Blog_Price() {
			check_admin_referer ();
			$action = mysql_escape_string ( $_REQUEST ["action"] );
			$post_id = mysql_escape_string ( $_REQUEST ["post_id"] );
			$this->ManagePurchasers ( $action, $post_id );
			if (isset ( $_REQUEST ['submit'] )) {
				$this->set_option ( "_BlogAmount", "amount" );
				$this->set_option ( "_BlogCurrency", "currency" );
				$this->set_option ( "_BlogName", "name" );
				$this->set_option ( "_BlogNumber", "number" );
				$this->set_option ( "_BlogExpire", "expire" );
				$this->set_option ( "_BlogExpirationUnits", "expiration_units" );
				$LastAction = __ ( " Updated successfully ..." );
			}
			if (isset ( $_REQUEST ['clear'] )) {
				$this->delete_options ( "_BlogAmount,_BlogCurrency,_BlogName,_BlogNumber,_BlogExpire,_BlogExpirationUnits" );
				$LastAction = __ ( " Deleted successfully ..." );
			
			}
			if (! $action || $action == "edit") {
				$blog_price_data = array (array ("fieldname" => "Name", "formfieldname" => "name", "formfieldvalue" => get_option ( $this->prefix . '_BlogName' ) ), array ("fieldname" => "Number", "formfieldname" => "number", "formfieldvalue" => get_option ( $this->prefix . '_BlogNumber' ) ), array ("fieldname" => "Price", "formfieldname" => "amount", "formfieldvalue" => get_option ( $this->prefix . '_BlogAmount' ) ), array ("fieldname" => "Currency", "formfieldname" => "currency", "formfieldvalue" => get_option ( $this->prefix . '_BlogCurrency' ) ), array ("fieldname" => "Expire", "formfieldname" => "expire", "formfieldvalue" => get_option ( $this->prefix . '_BlogExpire' ) ) );
				//SMARTY
				$expiration_units_data = array ("label" => __ ( 'Expiration units' ), "value" => get_option ( $this->prefix . '_BlogExpirationUnits' ), "units" => explode ( ",", "D,W,M,Y" ), "translated_units" => explode ( ",", "Days,Weeks,Months,Years" ) );
				$this->smarty->assign ( 'LastAction', $LastAction );
				$this->smarty->assign ( 'PageHeader', __ ( 'Edit Blog Price' ) );
				$this->smarty->assign ( 'Page', $_REQUEST ["page"] );
				$this->smarty->assign ( 'BlogPriceData', $blog_price_data );
				$this->smarty->assign ( 'ExpirationUnitsData', $expiration_units_data );
				$this->smarty->assign ( 'EditPurchasersLabel', __ ( 'Edit Purchasers' ) );
				$this->smarty->display ( 'blog_price.tpl' );
			}
		}
		
		function PayToRegister_Price() {
			check_admin_referer ();
			$action = mysql_escape_string ( $_REQUEST ["action"] );
			$post_id = mysql_escape_string ( $_REQUEST ["post_id"] );
			$this->ManagePurchasers ( $action, $post_id );
			if (isset ( $_REQUEST ['submit'] )) {
				$this->set_option ( "_PayToRegisterAmount", "amount" );
				$this->set_option ( "_PayToRegisterCurrency", "currency" );
				$this->set_option ( "_PayToRegisterName", "name" );
				$this->set_option ( "_PayToRegisterNumber", "number" );
				$this->set_option ( "_PayToRegisterExpire", "expire" );
				$this->set_option ( "_PayToRegisterExpirationUnits", "expiration_units" );
				$LastAction = __ ( " Updated successfully ..." );
			}
			if (isset ( $_REQUEST ['clear'] )) {
				$this->delete_options ( "_PayToRegisterAmount,_PayToRegisterCurrency,_PayToRegisterName,_PayToRegisterNumber,_PayToRegisterExpire,_PayToRegisterExpirationUnits" );
				$LastAction = __ ( " Deleted successfully ..." );
			
			}
			if (! $action || $action == "edit") {
				$PayToRegister_price_data = array (array ("fieldname" => "Name", "formfieldname" => "name", "formfieldvalue" => get_option ( $this->prefix . '_PayToRegisterName' ) ), array ("fieldname" => "Number", "formfieldname" => "number", "formfieldvalue" => get_option ( $this->prefix . '_PayToRegisterNumber' ) ), array ("fieldname" => "Price", "formfieldname" => "amount", "formfieldvalue" => get_option ( $this->prefix . '_PayToRegisterAmount' ) ), array ("fieldname" => "Currency", "formfieldname" => "currency", "formfieldvalue" => get_option ( $this->prefix . '_PayToRegisterCurrency' ) ), array ("fieldname" => "Expire", "formfieldname" => "expire", "formfieldvalue" => get_option ( $this->prefix . '_PayToRegisterExpire' ) ) );
				//SMARTY
				$expiration_units_data = array ("label" => __ ( 'Expiration units' ), "value" => get_option ( $this->prefix . '_PayToRegisterExpirationUnits' ), "units" => explode ( ",", "D,W,M,Y" ), "translated_units" => explode ( ",", "Days,Weeks,Months,Years" ) );
				$this->smarty->assign ( 'LastAction', $LastAction );
				$this->smarty->assign ( 'PageHeader', __ ( 'Edit "Pay To Register" Price' ) );
				$this->smarty->assign ( 'Page', $_REQUEST ["page"] );
				$this->smarty->assign ( 'PayToRegisterPriceData', $PayToRegister_price_data );
				$this->smarty->assign ( 'ExpirationUnitsData', $expiration_units_data );
				$this->smarty->assign ( 'EditPurchasersLabel', __ ( 'Edit Purchasers' ) );
				$this->smarty->display ( 'paytoregister_price.tpl' );
			}
		}
		
		function View_Payments() {
			global $wpdb;
			check_admin_referer ();
			
			$sql = "
			SELECT r.cnt,f.* FROM (select count(*) as cnt from $this->paypal_requests_table %WHERE%) r 
			CROSS JOIN $this->paypal_fields_table f %WHERE% ORDER BY f.RequestID desc" ;
			
			if ($_REQUEST ["action"] == "details") {
				$RequestID = $_REQUEST ["RequestID"];
				$sql = str_ireplace ( "%WHERE%", "WHERE RequestID='$RequestID'", $sql );
			} else {
				$sql = str_ireplace ( "%WHERE%", "", $sql );
			}
			
			$requests = $wpdb->get_results ( $sql );
			
			$data = array ();
			
			foreach ( $requests as $field ) {
				$data [$field->RequestID] [$field->Name] = array ("value" => $field->Value, "name" => $field->Name );
				if ($field->Name == "custom") {
					if (stripos ( $field->Value, "are-paypal-custom#" ) !== false) {
						$str = $field->Value;
						$str = str_ireplace ( "are-paypal-custom#", "", $str );
						$pairs = explode ( "&", $str );
						foreach ( $pairs as &$pair ) {
							list ( $key, $value ) = explode ( "=", $pair );
							$customInfo [$key] = urldecode ( $value );
						}
						$user_id = mysql_escape_string ( $customInfo ["user_id"] );
						$post_id = mysql_escape_string ( $customInfo ["post_id"] );
					} else {
						list ( $post_id, $user_id ) = explode ( "|", $field->Value );
						$user_id = mysql_escape_string ( $user_id );
						$post_id = mysql_escape_string ( $post_id );
					}
					$login = $wpdb->get_results ( "SELECT user_login FROM $wpdb->users where ID='$user_id'" );
					$post = $wpdb->get_results ( "SELECT post_title FROM $wpdb->posts where ID='$post_id'" );
					$data [$field->RequestID] ["login"] = array ("value" => $login [0]->user_login, "name" => "login" );
					$data [$field->RequestID] ["post_title"] = array ("value" => $post [0]->post_title, "name" => "post_title" );
				}
			}
			SmartyPaginate::connect ();
			SmartyPaginate::setLimit ( 25 );
			SmartyPaginate::setUrl ( 'admin.php?page=Are_PayPal_PaypalData' );
			
			SmartyPaginate::setTotal ( count ( $data ) );
			
			$chunked_data = array_chunk ( $data, SmartyPaginate::getLimit (), true );
			if ($_REQUEST ["action"] != "details") {
				$pageNumber = SmartyPaginate::getCurrentIndex () / SmartyPaginate::getLimit ();
				$pageNumber = floor ( $pageNumber );
				$data = $chunked_data [$pageNumber];
			} else {
				$data = $chunked_data [0];
			}
			
			$this->smarty->assign ( 'PageHeader', __ ( 'View Payments' ) );
			$this->smarty->assign ( 'Data', $data );
			SmartyPaginate::assign ( $this->smarty );
			
			$this->smarty->display ( 'view_payments.tpl' );
		}
		
		function ManagePurchasers($action, $post_id) {
			check_admin_referer ();
			global $wpdb;
			if ($_REQUEST ["PurchasersSubmit"]) {
				if ($_REQUEST ["PurchasersSubmit"] == ">>") {
					$item_purchaser = $_REQUEST ["available_users"];
					$wpdb->query ( "INSERT INTO $this->paid_users_table(post_id,user_id) VALUES($post_id,$item_purchaser)" );
					$LastAction = __ ( "Added successfully ..." );
				} else {
					$item_purchaser = $_REQUEST ["paid_users"];
					$wpdb->query ( "DELETE FROM $this->paid_users_table WHERE post_id=$post_id AND user_id=$item_purchaser" );
					$LastAction = __ ( "Removed successfully ..." );
				}
			}
			
			if (($action == "purchasers") && ($post_id)) {
				
				$items = $wpdb->get_results ( "SELECT posts.ID,posts.post_title FROM $wpdb->posts posts WHERE posts.id='$post_id'" );
				if ($items || $post_id == - 1 || $post_id == - 2) {
					if ($post_id == - 1) {
						$item_id = - 1;
						$item_title = "Entire blog ...";
					} else if ($post_id == - 2) {
						$item_id = - 2;
						$item_title = "Pay To Register ...";
					} else {
						$item = $items [0];
						$item_id = $item->ID;
						$item_title = $item->post_title;
					}
					
					$available_users_sql = "
						SELECT DISTINCT
							u.id, 
							user_login 
						FROM $wpdb->users u 
						WHERE u.id not in 
							(
								SELECT user_id FROM $this->paid_users_table up 
								WHERE 
									post_id = $item_id 
									AND ($this->paid_user_id_sql_criteria)
							)";
					$paid_users_sql = "
					SELECT DISTINCT
						u.id,
						user_login 
					FROM $wpdb->users u 
					INNER JOIN 
						$this->paid_users_table up ON up.user_id = u.id and up.post_id=$item_id 
						AND ($this->paid_user_id_sql_criteria)
					";
					$available_users = $wpdb->get_results ( $available_users_sql );
					echo ("<!-- $paid_users_sql -->");
					$paid_users = $wpdb->get_results ( $paid_users_sql );
					
					$this->smarty->assign ( 'LastAction', $LastAction );
					$this->smarty->assign ( 'PageHeader', __ ( 'Post purchasers' ) );
					$this->smarty->assign ( 'Page', $_REQUEST ["page"] );
					$this->smarty->assign ( 'PostID', $post_id );
					$this->smarty->assign ( 'PostLabel', __ ( 'Post' ) );
					$this->smarty->assign ( 'PostTitle', $item_title );
					$this->smarty->assign ( 'AvailableUsersLabel', __ ( 'Available users' ) );
					$this->smarty->assign ( 'PaidUsersLabel', __ ( 'Paid users' ) );
					$this->smarty->assign ( 'AvailableUsers', $available_users );
					$this->smarty->assign ( 'PaidUsers', $paid_users );
					$this->smarty->display ( 'manage_purchasers.tpl' );
				}
			}
		}
		function Configure_Prices_For_Posts() {
			check_admin_referer ();
			global $wpdb;
			### Get The Posts
			$action = mysql_escape_string ( $_REQUEST ["action"] );
			$post_id = mysql_escape_string ( $_REQUEST ["post_id"] );
			
			if ($_REQUEST ["BonusSubmit"]) {
				
				$manual = get_option ( $this->prefix . '_BonusPostManualSetup' ) == 'checked';
				
				if ($_REQUEST ["BonusSubmit"] == ">>") {
					$post2_id = $_REQUEST ["post_to_package"];
					$wpdb->query ( "INSERT INTO $this->bonus_posts_table VALUES($post_id,$post2_id)" );
					if (! $manual) {
						$wpdb->query ( "INSERT INTO $this->bonus_posts_table VALUES($post2_id,$post_id)" );
					}
					$LastAction = __ ( "Added successfully ..." );
				} else {
					$post2_id = $_REQUEST ["post_in_package"];
					$wpdb->query ( "DELETE FROM $this->bonus_posts_table WHERE post1_id=$post_id AND post2_id=$post2_id" );
					if (! $manual) {
						$wpdb->query ( "DELETE FROM $this->bonus_posts_table WHERE post1_id=$post2_id AND post2_id=$post_id" );
					}
					$LastAction = __ ( "Removed successfully ..." );
				}
			}
			
			if (($action == "delete") && ($post_id)) {
				$wpdb->query ( "DELETE FROM $this->paid_items_table WHERE post_id=$post_id" );
				$wpdb->query ( "DELETE FROM $this->paid_users_table WHERE post_id=$post_id" );
				$LastAction = __ ( "Deleted successfully ..." );
			}
			if (($action == "write") && ($post_id)) {
				$items = $wpdb->get_results ( "SELECT posts.ID,posts.post_title, items.* FROM $wpdb->posts posts LEFT OUTER JOIN $this->paid_items_table items ON items.post_id=posts.id WHERE posts.id='$post_id'" );
				$item = $items [0];
				$item_id = $item->ID;
				$item_amount = mysql_escape_string ( $_REQUEST ["amount"] );
				$item_currency = mysql_escape_string ( $_REQUEST ["currency"] );
				$item_name = mysql_escape_string ( $_REQUEST ["name"] );
				$item_number = mysql_escape_string ( $_REQUEST ["number"] );
				$item_expire = $_REQUEST ["expire"];
				$item_expiration_units = $_REQUEST ["expiration_units"];
				if ($item->post_id) {
					//UPDATE
					$sql = "UPDATE $this->paid_items_table SET name='$item_name',number='$item_number',amount='$item_amount',currency='$item_currency',expire='$item_expire', expiration_unit='$item_expiration_units' WHERE post_id=$item_id";
					$LastAction = __ ( "Updated successfully ..." );
				} else {
					//INSERT
					$sql = "INSERT INTO $this->paid_items_table (name,number,amount,currency, post_id,expire,expiration_unit) VALUES('$item_name','$item_number','$item_amount','$item_currency','$item_id','$item_expire','$item_expiration_units')";
					$LastAction = __ ( "Inserted successfully ..." );
				}
				$wpdb->query ( $sql );
			}
			if (($action == "purchasers") && ($post_id)) {
				$this->ManagePurchasers ( $action, $post_id );
			} elseif (($action == "bonus") && ($post_id)) {
				$items = $wpdb->get_results ( "SELECT posts.ID,posts.post_title, items.* FROM $wpdb->posts posts LEFT OUTER JOIN $this->paid_items_table items ON items.post_id=posts.id WHERE posts.id='$post_id' AND $this->post_type_clause" );
				if ($items) {
					$item = $items [0];
					$item_id = $item->ID;
					$item_title = $item->post_title;
					
					$available_posts = $wpdb->get_results ( "SELECT posts.* FROM $wpdb->posts posts WHERE posts.id!=$item_id AND  posts.post_content LIKE '%$this->start_delimiter%'AND $this->post_type_clause AND posts.id NOT IN(select post2_id from $this->bonus_posts_table WHERE post1_id='$item_id')" );
					$bonus_posts = $wpdb->get_results ( "SELECT posts.* FROM $wpdb->posts posts WHERE posts.id!=$item_id AND posts.post_content LIKE '%$this->start_delimiter%' AND $this->post_type_clause AND posts.id IN(select post2_id from $this->bonus_posts_table WHERE post1_id='$item_id')" );
					
					$this->smarty->assign ( 'LastAction', $LastAction );
					$this->smarty->assign ( 'PageHeader', __ ( 'Post purchasers' ) );
					$this->smarty->assign ( 'Page', $_REQUEST ["page"] );
					$this->smarty->assign ( 'PostID', $item_id );
					$this->smarty->assign ( 'PostLabel', __ ( 'Post' ) );
					$this->smarty->assign ( 'PostTitle', $item_title );
					$this->smarty->assign ( 'AvailablePostsLabel', __ ( 'Available posts' ) );
					$this->smarty->assign ( 'BonusPostsLabel', __ ( 'Bonus posts' ) );
					$this->smarty->assign ( 'AvailablePosts', $available_posts );
					$this->smarty->assign ( 'BonusPosts', $bonus_posts );
					$this->smarty->display ( 'bonus_posts.tpl' );
				
				}
			} else if (($action == "edit") && ($post_id)) {
				$items = $wpdb->get_results ( "SELECT posts.ID,posts.post_title, items.* FROM $wpdb->posts posts LEFT OUTER JOIN $this->paid_items_table items ON items.post_id=posts.id WHERE posts.id='$post_id'" );
				if ($items) {
					$item = $items [0];
					$item_id = $item->ID;
					
					$post_price_data = array (array ("fieldname" => "ID", "formfieldvalue" => $item->ID ), 

					array ("fieldname" => "Post Title", "formfieldvalue" => $item->post_title ), 

					array ("fieldname" => "Name", "formfieldname" => "name", "formfieldvalue" => $item->name ), array ("fieldname" => "Number", "formfieldname" => "number", "formfieldvalue" => $item->number ), array ("fieldname" => "Price", "formfieldname" => "amount", "formfieldvalue" => $item->amount ), array ("fieldname" => "Currency", "formfieldname" => "currency", "formfieldvalue" => $item->currency ), array ("fieldname" => "Expire", "formfieldname" => "expire", "formfieldvalue" => $item->expire ) );
					$expiration_units_data = array ("label" => __ ( 'Expiration units' ), "value" => $item->expiration_unit, "units" => explode ( ",", "D,W,M,Y" ), "translated_units" => explode ( ",", "Days,Weeks,Months,Years" ) );
					$post_purchasers_sql = "
						SELECT 
							user_login 
						FROM $wpdb->users u 
						INNER JOIN $this->paid_users_table up ON up.user_id=u.id and up.post_id=$item_id 
						AND ($this->paid_user_id_sql_criteria)
					";
					
					$post_purchasers = $wpdb->get_results ( $post_purchasers_sql );
					
					$this->smarty->assign ( 'Mode', "post" );
					$this->smarty->assign ( 'LastAction', $LastAction );
					$this->smarty->assign ( 'PageHeader', __ ( 'Edit Post Price' ) );
					$this->smarty->assign ( 'Page', $_REQUEST ["page"] );
					$this->smarty->assign ( 'PostID', $item_id );
					$this->smarty->assign ( 'PostPriceData', $post_price_data );
					$this->smarty->assign ( 'ExpirationUnitsData', $expiration_units_data );
					$this->smarty->assign ( 'PurchasersLabel', __ ( 'Purchasers' ) );
					$this->smarty->assign ( 'Purchasers', $post_purchasers );
					$this->smarty->assign ( 'EditPurchasersLabel', __ ( 'Edit purchasers' ) );
					$this->smarty->display ( 'post_price.tpl' );
				}
			} else {
				$sql = "SELECT posts.ID,posts.post_title, items.* FROM $wpdb->posts posts LEFT OUTER JOIN $this->paid_items_table items ON items.post_id=posts.id WHERE posts.post_content LIKE '%$this->start_delimiter%' AND $this->post_type_clause";
				$Data = $wpdb->get_results ( $sql );
				$Purchasers = array ();
				foreach ( $Data as $item ) {
					$purchasers_sql = "
						SELECT user_login FROM $wpdb->users u INNER JOIN $this->paid_users_table up ON up.user_id=u.id and up.post_id=$item->ID 
						AND ($this->paid_user_id_sql_criteria)
					";
					$Purchasers [] = $wpdb->get_results ( $purchasers_sql );
				}
				
				$field_names = explode ( ",", "ID,Post Title,Purchasers,Name,Number,Price,Currency,Expire,Expiration units" );
				$this->smarty->assign ( 'LastAction', $LastAction );
				$this->smarty->assign ( 'PageHeader', __ ( 'Post Prices' ) );
				$this->smarty->assign ( 'FieldNames', $field_names );
				$this->smarty->assign ( 'Data', $Data );
				$this->smarty->assign ( 'Purchasers', $Purchasers );
				$this->smarty->assign ( 'NoPaidPostsPagesFoundInDB', __ ( '<strong>WARNING:</strong> There are no paid posts or pages in the DB. You have to use paid content shortcode in post or page first and only after it your post or page appears here. Take a look at "How To Use"' ) );
				$this->smarty->display ( 'view_prices.tpl' );
			}
		}
		function view_users() {
			check_admin_referer ();
			global $wpdb;
			$sql = "SELECT distinct u.id as ID, u.user_login FROM $this->paid_users_table appu INNER JOIN $wpdb->users u ON u.id=appu.user_id";
			$Data = $wpdb->get_results ( $sql );
			$Posts = array ();
			foreach ( $Data as $item ) {
				$posts_sql = "
					SELECT distinct p.id as ID, p.post_title 
					FROM $this->paid_users_table appu 
					INNER JOIN $wpdb->posts p ON p.id=appu.post_id 
					WHERE appu.user_id=$item->ID
				";
				$Posts [] = $wpdb->get_results ( $posts_sql );
			}
			
			$field_names = explode ( ",", "ID,User Login" );
			$this->smarty->assign ( 'LastAction', $LastAction );
			$this->smarty->assign ( 'PageHeader', __ ( 'Paid Users' ) );
			$this->smarty->assign ( 'FieldNames', $field_names );
			$this->smarty->assign ( 'Data', $Data );
			$this->smarty->assign ( 'Posts', $Posts );
			$this->smarty->assign ( 'NoPaidUsersFoundInDB', __ ( '<strong>WARNING:</strong> There are no paid users"' ) );
			$this->smarty->display ( 'view_users.tpl' );
		}
		function IsGooglebot() {
			// check if user agent contains googlebot
			if (eregi ( "Googlebot", $_SERVER ['HTTP_USER_AGENT'] )) {
				$ip = $_SERVER ['REMOTE_ADDR'];
				//server name e.g. crawl-66-249-66-1.googlebot.com
				$name = gethostbyaddr ( $ip );
				//check if name ciontains googlebot
				if (eregi ( "Googlebot", $name )) {
					//list of IP's
					$hosts = gethostbynamel ( $name );
					foreach ( $hosts as $host ) {
						if ($host == $ip) {
							return true;
						}
					}
					return false; // Pretender, take some action if needed
				} else {
					return false; // Pretender, take some action if needed
				}
			} else {
				// Not googlebot, take some action if needed
			}
			return false;
		}
		function isPayToRegisterPurchased($user_id) {
			$key = $this->prefix . "_UserMeta";
			$UserPayToRegisterMetaData = get_user_meta( $user_id, $key, false );
			if ($UserPayToRegisterMetaData) {
				if (! $this->IsPostPurchasedSql ( - 2, $user_id )) {
					return false;
				}
			}
			return true;
		}
		function IsPostPurchased($post_id, $user_id) {
			$user_id = mysql_escape_string ( $user_id );
			$post_id = mysql_escape_string ( $post_id );
			
			if ($this->IsPostPurchasedSql ( "-1", $user_id )) {
				return true;
			}
			if ($this->IsPostPurchasedSql ( $post_id, $user_id )) {
				return true;
			}
			return false;
		}
		function IsPostPurchasedSql($post_id, $user_id) {
			global $wpdb;
			$sql = "
				SELECT up.* FROM $this->paid_users_table up WHERE (up.post_id='$post_id') AND up.user_id='$user_id' 
				AND ($this->paid_user_id_sql_criteria)";
			$users = $wpdb->get_results ( $sql );
			if ($users) {
				return true;
			}
			return false;
		}
		function paypal_buy_now_form($item_name, $item_number, $item_price, $item_currency, $post_id, $user_id, $item_expiration, $item_expiration_unit) {
			$paypal_url = $this->paypal_url;
			$paypal_email = $this->paypal_email;
			
			$wp_affiliate_id = "";
			if (isset($_SESSION ['ap_id'])){
				$wp_affiliate_id = $_SESSION ['ap_id'];
			}else{
				if (isset($_COOKIE ['ap_id'])){
					$wp_affiliate_id = $_COOKIE ['ap_id'];
				}
			}
			
			$item_custom = "are-paypal-custom#post_id=" . urlencode ( $post_id ) . "&user_id=" . urlencode ( $user_id ) . "&wp_affiliate_id=" . urlencode ( $wp_affiliate_id );
			$domain = $_SERVER ['HTTP_HOST'];
			$item_return = "http://" . $domain . $_SERVER ['REQUEST_URI'];
			$textExplanation = stripslashes ( get_option ( $this->prefix . '_TextToShowIfNotPurchased' ) );
			$templateName = $this->prefix . "_RecurentPaymentTemplate";
			if (! $item_expiration) {
				$templateName = $this->prefix . "_InstantPaymentTemplate";
			}
			$result = stripslashes ( get_option ( $templateName ) );
			$result = str_replace ( '%EXPLANATION%', htmlentities ( $textExplanation ), $result );
			$result = str_replace ( '%PAYPALURL%', htmlentities ( $paypal_url ), $result );
			$result = str_replace ( '%ITEMNAME%', htmlentities ( $item_name ), $result );
			$result = str_replace ( '%ITEMNUMBER%', htmlentities ( $item_number ), $result );
			$result = str_replace ( '%ITEMPRICE%', htmlentities ( $item_price ), $result );
			$result = str_replace ( '%ITEMCURRENCY%', htmlentities ( $item_currency ), $result );
			$result = str_replace ( '%BONUSLIST%', $this->BonusPostsList ( $post_id ), $result );
			$result = str_replace ( '%PAYPALEMAIL%', $paypal_email, $result );
			$result = str_replace ( '%ITEMRETURN%', htmlentities ( $item_return ), $result );
			$result = str_replace ( '%ITEMCUSTOM%', htmlentities ( $item_custom ), $result );
			$result = str_replace ( '%EXPIRATION%', htmlentities ( $item_expiration ), $result );
			$result = str_replace ( '%EXPIRATIONUNITS%', htmlentities ( $item_expiration_unit ), $result );
			$result = str_replace ( '%BUTTONALT%', '', $result );
			return $result;
		}
		function BonusPostsList($post_id) {
			global $wpdb;
			$post_id = mysql_escape_string ( $post_id );
			$sql = "select posts.post_title,posts.id from $this->bonus_posts_table bonuses INNER JOIN $wpdb->posts posts ON posts.id=bonuses.post2_id WHERE bonuses.post1_id='$post_id'";
			$bonuses = $wpdb->get_results ( $sql );
			$result = "";
			if ($bonuses) {
				$result .= "<p>" . __ ( "Purchasing current content you will also get access for posts below" ) . "</p><ul>";
				foreach ( $bonuses as $bonus ) {
					$result .= "<li><a href='?p=$bonus->id'>$bonus->post_title</a></li>";
				}
				$result .= "</ul>";
			}
			return $result;
		}
		function add_html_headers() {
			echo ('<META NAME="ROBOTS" CONTENT="NOARCHIVE"/>');
			echo ('<link type="text/css" rel="stylesheet" href="' . get_bloginfo ( 'wpurl' ) . '/wp-content/plugins/are-paypal/css/are-paypal.css" />');
		}
		function wp_authenticate_username_password($user, $username, $password) {
			if (! is_a ( $user, 'WP_User' )) {
				if (empty ( $username ) || empty ( $password )) {
					$error = new WP_Error ();
					
					if (empty ( $username ))
						$error->add ( 'empty_username', get_option ( $this->prefix . '_' . 'LoginMessages_EmptyUserName' ) );
					
					if (empty ( $password ))
						$error->add ( 'empty_password', get_option ( $this->prefix . '_' . 'LoginMessages_EmptyPassword' ) );
					
					return $error;
				}
				
				$userdata = get_user_by("login", $username);
				
				if (! $userdata) {
					return new WP_Error ( 'invalid_username', sprintf ( get_option ( $this->prefix . '_' . 'LoginMessages_InvalidUserName' ), site_url ( 'wp-login.php?action=lostpassword', 'login' ) ) );
				}
				
				$userdata = apply_filters ( 'wp_authenticate_user', $userdata, $password );
				if (is_wp_error ( $userdata )) {
					return $userdata;
				}
				
				if (! wp_check_password ( $password, $userdata->user_pass, $userdata->ID )) {
					return new WP_Error ( 'incorrect_password', sprintf ( get_option ( $this->prefix . '_' . 'LoginMessages_IncorrectPassword' ), site_url ( 'wp-login.php?action=lostpassword', 'login' ) ) );
				}
				
				$user = new WP_User ( $userdata->ID );
			}
			if (! $this->isPayToRegisterPurchased ( $user->ID )) {
				
				$PayToRegisterPageID = get_option ( $this->prefix . '_Users_PayToRegister_Page', '#' );
				$post = get_post ( $PayToRegisterPageID );
				$PayToRegisterPage = $post->guid;
				return new WP_Error ( 'paytoregister_auth_failed', sprintf ( get_option ( $this->prefix . '_' . 'LoginMessages_PayToRegisterAuthFailed' ), $PayToRegisterPage ) );
			}
			return $user;
		}
	
	}

}
//instantiate the class
if (class_exists ( 'Are_PayPal' )) {
	$Are_PayPal = new Are_PayPal ();
}

add_action('activated_plugin','save_error');
function save_error(){
    update_option('arepaypal_plugin_error',  ob_get_contents());
}

?>
