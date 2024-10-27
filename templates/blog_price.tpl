{include file='last_action.inc.tpl'}
<div class="wrap">
	<h2>{$PageHeader}</h2>
	{assign var="PriceData" value="$BlogPriceData"}
	{include file='edit_price_form.inc.tpl'}
	<a href="?page={$Prefix}_BlogPrice&amp;action=purchasers&amp;post_id=-1">{$EditPurchasersLabel}</a>
</div>