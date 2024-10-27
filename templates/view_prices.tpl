{include file='last_action.inc.tpl'}
<div class="wrap">
	<h2>{$PageHeader}</h2>
	{if !empty($Data)}
	<table width="100%" border="0" cellspacing="3" cellpadding="3">
		<tr>
			{foreach from=$FieldNames item=fieldname}
			<th>{$fieldname}</th>
			{/foreach}
		</tr>
			{foreach from=$Data item=item key=k}
				<tr>
					<td>{$item->ID}</td>
					<td>{$item->post_title}</td>
					<td>
						{foreach from=$Purchasers[$k] item=purchaser}
								{$purchaser->user_login}<br />
						{/foreach}
					</td>
					<td>{$item->name}</td>
					<td>{$item->number}</td>
					<td>{$item->amount}</td>
					<td>{$item->currency}</td>
					<td>{$item->expire}</td>
					<td>{$item->expiration_unit}</td>
					<td style="white-space:nowrap;">
					{if $item->post_id}
						<a href="?page={$Prefix}_PostSetup&amp;action=delete&amp;post_id={$item->ID}" title="Clear Pricing">
							<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/clear.gif" alt="Clear Pricing"/>
						</a> 
						<a href="?page={$Prefix}_PostSetup&amp;action=bonus&amp;post_id={$item->ID}" title="Edit bonus posts">
							<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/bonus.gif" alt="Edit bonus posts"/>
						</a> 
						<a href="?page={$Prefix}_PostSetup&amp;action=purchasers&amp;post_id={$item->ID}" title="Edit purchasers">
							<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/purchasers.gif" alt="Edit purchasers"/>
						</a> 
					{/if}
					<a href="?page={$Prefix}_PostSetup&amp;action=edit&amp;post_id={$item->ID}" title="Edit">
						<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/edit.gif" alt="Edit"/>
					</a>
					</td>
				</tr>
			{/foreach}
		</table>
		{else}
			<div class="error">{$NoPaidPostsPagesFoundInDB}</div>
		{/if}
</div>