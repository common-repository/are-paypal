<div class="wrap">
	<h2>{$PageHeader}</h2>
	<p style="font-weight: bold;">
		How to hide content and show paypal button?
	</p>
	<ul>
		<li>Use pseudo tags '{$StartDelimiter}' and '{$EndDelimiter}' to mark hidden/paid content.</li>
		<li>Goto configuration section and enter a price for a post.</li>
	</ul>
	<p style="font-weight: bold;">
		How to add purchased posts list into any page/post?
	</p>
	<ul>
		<li>Use placeholder '{$PurchasedPostsListPlaceholder}' in your post/page.</li>
	</ul>
	<p style="font-weight: bold;">
		How to configure "Pay to Register" Post/Page?
	</p>
	<ul>
		<li>Use shortcode '{$PayToRegisterShortcode}' in your post/page to define a place where the button mus appear. Between shortcode tags you can place some information to show for registered users. So that users who are registered will see this information instead of buy now button.</li>
	</ul>
	<p style="font-weight: bold;">
		How to configure paypal's Instant Payment Notification (IPN)?
	</p>
	<ul>
		<li>Follow the <a href="{$PayPalUrl}?cmd=_profile-ipn-notify">link</a></li>
		<li>Set IPN "On"</li>
		<li>Set IPN Url to '{$HomeUrl}/wp-content/plugins/are-paypal/are-paypal-ipn.php'</li>
	</ul>
</div>
