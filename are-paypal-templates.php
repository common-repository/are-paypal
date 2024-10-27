<?php
if (!class_exists('Are_PayPal_Templates')) {

	class Are_PayPal_Templates{
		var $prefix;
		function Are_PayPal_Templates(){
			global $wpdb;
			$wpdb->show_errors();
			$configuration= new  Are_PayPal_Configuration();

			//Initialize properties
			$this->prefix=$configuration->prefix;
		}

		function set_templates(){
			$InstantPaymentTemplate = get_option($this->prefix."_InstantPaymentTemplate");
			if (!$InstantPaymentTemplate){
				$template="
	<div class='ArePayPalContent'>
	<p class='ArePayPalTextToShowIfNotPurchased'>%EXPLANATION%</p>
	<form action='%PAYPALURL%' method='post'>
	<p class='ArePayPalContentTitle'>%ITEMNAME%: %ITEMNUMBER%</p>
	<p class='ArePayPalContentPrice'>%ITEMPRICE% %ITEMCURRENCY%</p>
	<p class='ArePayPalBonusList'>%BONUSLIST%</p>
	<input type='hidden' name='cmd' value='_xclick'/>
	<input type='hidden' name='business' value='%PAYPALEMAIL%'/>
	<input type='hidden' name='item_name' value='%ITEMNAME%'/>
	<input type='hidden' name='item_number' value='%ITEMNUMBER%'/>
	<input type='hidden' name='amount' value='%ITEMPRICE%'/>
	<input type='hidden' name='no_shipping' value='1'/>
	<input type='hidden' name='no_note' value='1'/>
	<input type='hidden' name='return' value='%ITEMRETURN%'/>
	<input type='hidden' name='custom' value='%ITEMCUSTOM%'/>
	<input type='hidden' name='currency_code' value='%ITEMCURRENCY%'/>
	<input type='hidden' name='bn' value='IC_ArePayPal'/>
	<input type='image' src='https://www.paypal.com/en_US/i/btn/x-click-butcc.gif' name='submit' alt='%BUTTONALT%'/>
	<img alt='%BUTTONALT%' src='https://www.paypal.com/en_US/i/scr/pixel.gif' width='1' height='1'/>
	</form>
	</div>
						";
				update_option($this->prefix."_InstantPaymentTemplate", $template);
			}
			$RecurentPaymentTemplate = get_option($this->prefix."_RecurentPaymentTemplate");
			if (!$RecurentPaymentTemplate){
				$template="
	<div class='ArePayPalContent'>
	<p class='ArePayPalTextToShowIfNotPurchased'>%EXPLANATION%</p>
	<form action='%PAYPALURL%' method='post'>
	<p class='ArePayPalContentTitle'>%ITEMNAME%: %ITEMNUMBER%</p>
	<p class='ArePayPalContentPrice'>%ITEMPRICE% %ITEMCURRENCY%</p>
	<p class='ArePayPalBonusList'>%BONUSLIST%</p>
	<input type='hidden' name='cmd' value='_xclick-subscriptions'/>
	<input type='hidden' name='business' value='%PAYPALEMAIL%'/>
	<input type='hidden' name='item_name' value='%ITEMNAME%'/>
	<input type='hidden' name='item_number' value='%ITEMNUMBER%'/>
	<input type='hidden' name='amount' value='%ITEMPRICE%'/>
	<input type='hidden' name='no_shipping' value='1'/>
	<input type='hidden' name='no_note' value='1'/>
	<input type='hidden' name='return' value='%ITEMRETURN%'/>
	<input type='hidden' name='custom' value='%ITEMCUSTOM%'/>
	<input type='hidden' name='currency_code' value='%ITEMCURRENCY%'/>
	<input type='hidden' name='a3' value='%ITEMPRICE%'/>
	<input type='hidden' name='p3' value='%EXPIRATION%'/>
	<input type='hidden' name='t3' value='%EXPIRATIONUNITS%'/>
	<input type='hidden' name='src' value='1'/>
	<input type='hidden' name='sra' value='1'/>
	<input type='hidden' name='bn' value='IC_ArePayPal'/>
	<input type='image' src='http://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' name='submit' alt='%BUTTONALT%'/>
	<img alt='%BUTTONALT%' src='https://www.paypal.com/en_US/i/scr/pixel.gif' width='1' height='1'/>
	</form>
	</div>";
				update_option($this->prefix."_RecurentPaymentTemplate", $template);
			}
			$LoginButtonTemplate = get_option($this->prefix."_LoginButtonTemplate");
			if (!$LoginButtonTemplate){
				$template="
	<div class='ArePayPalContent'>
	<p class='ArePayPalTextToShowIfNotLogedIn'>%EXPLANATION%</p>
	<a class='ArePayPalLoginButton' href='%LOGINURL%'>%LOGINURLTEXT%</a>
	</div>";
				update_option($this->prefix."_LoginButtonTemplate", $template);
			}
	
		}
	
	}
}
?>
