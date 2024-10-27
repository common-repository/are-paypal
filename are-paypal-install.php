<?php
if (!class_exists('Are_PayPal_Install')) {
	class Are_PayPal_Install{

		var $prefix;

		var $paid_users_table;
		var $paid_items_table;
		var $bonus_posts_table;
		var $paypal_requests_table;
		var $paypal_field_types_table;
		var $paypal_fields_table;

		function Are_PayPal_Install(){
			global $wpdb;
			$wpdb->show_errors();
			$configuration= new  Are_PayPal_Configuration();

			//Initialize properties
			$this->prefix=$configuration->prefix;
			$this->start_delimiter=$configuration->start_delimiter;
			$this->end_delimiter=$configuration->end_delimiter;
			$this->paid_users_table = $configuration->paid_users_table;
			$this->paid_items_table = $configuration->paid_items_table;
			$this->bonus_posts_table = $configuration->bonus_posts_table;
			$this->paypal_requests_table = $configuration->paypal_requests_table;
			$this->paypal_field_types_table = $configuration->paypal_field_types_table;
			$this->paypal_fields_table = $configuration->paypal_fields_table;
		}

		function install(){
			global $wpdb;
			$sql = "
CREATE TABLE $this->paypal_fields_table (
  `RequestID` bigint(20) NOT NULL,
  `Name` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
  `Value` varchar(255) character set utf8 collate utf8_unicode_ci default NULL
);

CREATE TABLE $this->paypal_field_types_table (
  `FieldTypeName` varchar(255) NOT NULL
);


CREATE TABLE $this->paypal_requests_table (
  `RequestID` bigint(20) NOT NULL auto_increment,
  `When` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`RequestID`)
);

CREATE TABLE $this->bonus_posts_table (
  `post1_id` bigint(20) NOT NULL,
  `post2_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`post1_id`,`post2_id`)
);

CREATE TABLE $this->paid_items_table (
  `id` bigint(20) NOT NULL auto_increment,
  `post_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` varchar(255) default NULL,
  `amount` double NOT NULL,
  `currency` char(3) NOT NULL,
  `expire` bigint(20) default NULL,
  `expiration_unit` enum('D','W','M','Y') NOT NULL,
  UNIQUE KEY `id` (`id`)
);

CREATE TABLE $this->paid_users_table (
  `id` bigint(20) NOT NULL auto_increment,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `expire` bigint(20) default NULL,
  `expiration_unit` enum('D','W','M','Y') NOT NULL,
  `purchase_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`)
);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			update_option($this->prefix."_db_version", "4.0");
		}
	}
}
?>