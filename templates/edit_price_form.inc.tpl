	<form method="post" action="" style="margin: auto; width: 25em;">
		{if $Mode == "post"}
			<input type="hidden" name="action" value="write" />
			<input type="hidden" name="post_id" value="{$PostID}" />
		{/if}
		<input type="hidden" name="page" value="{$Page}" />
		<table width="100%" border="0" cellspacing="3" cellpadding="3">
		{foreach from=$PriceData item=field}
			<tr>
				<td width="28%" style="background-color: #eee">
					{$field.fieldname}
				</td>
				<td>
					{if $field.formfieldname}
						<input type="text" name="{$field.formfieldname}" value="{$field.formfieldvalue}" />
					{else}
						{$field.formfieldvalue}
					{/if}
				</td>
			</tr>
		{/foreach}
			<tr>
				<td width="28%" style="background-color: #eee">
					{$ExpirationUnitsData.label}
				</td>
				<td>
					<select name="expiration_units">
						{foreach from=$ExpirationUnitsData.units key=k item=unit}
						<option value="{$unit}"	{if $ExpirationUnitsData.value == $unit} selected="selected" {/if}>{$ExpirationUnitsData.translated_units[$k]}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			{if $Mode=="post"}
			<tr>
				<td width="28%" style="background-color: #eee">
					{$PurchasersLabel}
				</td>
				<td>
					{foreach from=$Purchasers item=user}
						{$user->user_login}<br/>
					{/foreach}
					<a href="?page={$Prefix}_PostSetup&amp;action=purchasers&amp;post_id={$PostID}" title="{$EditPurchasersLabel}">
						<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/purchasers.gif" alt="{$EditPurchasersLabel}"/>	
					</a>
				</td>
			</tr>
			{/if}
		</table>
		<p class="submit">
			<input type="submit" name="submit" value="Submit&raquo;" />
		</p>
		{if $Mode != "post"}
			<p class="submit">
				<input type="submit" name="clear" value="Delete&raquo;" />
			</p>
		{/if}
	</form>