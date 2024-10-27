{include file='last_action.inc.tpl'}
<div class="wrap">
<h2>{$PageHeader}</h2>
	{assign var="PriceData" value="$PostPriceData"}
	{include file='edit_price_form.inc.tpl'}
	<form method="get" action="" style="margin: auto; width: 25em;">
		<input type="hidden" name="action" value="delete" />
		<input type="hidden" name="page" value="{$Page}" />
		<input type="hidden" name="post_id" value="{$PostID}" />
		<p class="submit">
			<input type="submit" value="Clear Pricing&raquo;" />
		</p>
	</form>
	<form method="get" action="" style="margin: auto; width: 25em;">
		<input type="hidden" name="action" value="list" />
		<input type="hidden" name="page" value="{$Page}" />
		<p class="submit">
			<input type="submit" value="Cancel&raquo;" />
		</p>
	</form>
</div>

