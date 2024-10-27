<?php
if (!class_exists('Are_PayPal_Configuration')) {
	class Are_PayPal_Configuration{
		var $prefix = "Are_PayPal";

		var $paid_users_table;
		var $paid_items_table;
		var $bonus_posts_table;
		var $paypal_requests_table;
		var $paypal_field_types_table;
		var $paypal_fields_table;
		
		var $paypal_url;
		var $paypal_postback_url;
		var $paypal_email;
		var $Suppress_Notification_Emails;
		var $delimiter_tag_name;
		var $paytoregisterdelimiter_tag_name;
		var $start_delimiter;
		var $end_delimiter;
		var $users_library_page;
		var $purchased_posts_list_placeholder;
		
		function Are_PayPal_Configuration(){
			global $wpdb;
			$this->delimiter_tag_name=$this->prefix."_LoginPlease";
			$this->paytoregisterdelimiter_tag_name=$this->prefix."_PayToRegister";
			$this->purchased_posts_list_placeholder="[Are-PayPal Users Purchased Posts/]";
			$this->start_delimiter="[".$this->delimiter_tag_name."]";
			$this->end_delimiter="[/".$this->delimiter_tag_name."]";
			$this->paid_users_table = $this->TransformTableName($wpdb->prefix . $this->prefix . "_users");
			$this->paid_items_table = $this->TransformTableName($wpdb->prefix . $this->prefix . "_items");
			$this->bonus_posts_table = $this->TransformTableName($wpdb->prefix . $this->prefix . "_bonus");
			$this->paypal_requests_table = $this->TransformTableName($wpdb->prefix . $this->prefix ."_paypal_requests");
			$this->paypal_field_types_table = $this->TransformTableName($wpdb->prefix . $this->prefix ."_paypal_field_types");
			$this->paypal_fields_table = $this->TransformTableName($wpdb->prefix . $this->prefix ."_paypal_fields");

			$this->paypal_url="https://www.paypal.com/cgi-bin/webscr";
			if (get_option($this->prefix.'_test')=='checked'){
				$this->paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
			}

			$this->paypal_postback_url="www.paypal.com";
			if (get_option($this->prefix.'_test')=='checked'){
				$this->paypal_postback_url = "www.sandbox.paypal.com";
			}
			
			$this->paypal_email = get_option($this->prefix.'_PayPal_Email');
			$this->Suppress_Notification_Emails = get_option($this->prefix.'_Suppress_Notification_Emails');
			$this->users_library_page = get_option($this->prefix.'_Users_Library_Page');
		}
		function TransformTableName($tableName){
			global $wpdb;
			$db_table_name_case = get_option($this->prefix.'_db_table_name_case');
			if ($db_table_name_case=='originalcase'){
				return $tableName;				
			}else if($db_table_name_case=='lowercase'){
				return strtolower($tableName);
			}
			
			$tables = $wpdb->get_results("SHOW TABLES LIKE '$tableName'");
			if ($tables){
				update_option($this->prefix.'_db_table_name_case','originalcase');
				return $tableName;
			}else{
				update_option($this->prefix.'_db_table_name_case','lowercase');
				return strtolower($tableName);
			}
		}
	}
}
?>
