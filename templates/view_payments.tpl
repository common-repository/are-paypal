<div class="wrap">
	<h2>{$PageHeader}</h2>
	{if count($Data) > 1}
	Items {$paginate.first}-{$paginate.last} out of {$paginate.total} displayed.
	{/if}
	<table width="100%" border="0" cellspacing="3" cellpadding="3">
		{foreach from=$Data item=rq key=k name=data_props}
			{if $smarty.foreach.data_props.total == "1"}
				<tr>
					<td colspan="2">
						<a href="?page={$Prefix}_PaypalData" title="Back">
							<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/back.gif" alt="Back"/>
						</a>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<strong>Details</strong>
					</td>
				</tr>
				{foreach from=$rq item=rqfield}
				<tr>
					<td>
						{$rqfield.name}
					</td>
					<td>
						{$rqfield.value}
					</td>
				</tr>
				{/foreach}	
			{else}
				{if $smarty.foreach.data_props.first}
					<tr>
						<th>Date</th>
						<th>Type</th>
						<th>Email</th>
						<th>Amount</th>
						<th>Login</th>
						<th>Post Title</th>
						<th>Action</th>
					</tr>
				{/if}
				<tr>
					<td>{$rq.payment_date.value}</td>
					<td>{$rq.txn_type.value}</td>
					<td>{$rq.payer_email.value}</td>
					<td>{$rq.mc_gross.value}{$rq.mc_amount3.value}</td>
					<td>{$rq.login.value}</td>
					<td>{$rq.post_title.value}</td>
					<td>
						<a href="?page={$Prefix}_PaypalData&amp;action=details&amp;RequestID={$k}" title="Details">
							<img src="{$HomeUrl}/wp-content/plugins/are-paypal/images/details.gif" alt="Details"/>							
						</a>
					</td>
				</tr>
			{/if}
		{/foreach}
	</table>
	{if count($Data) > 1}
	{paginate_prev} {paginate_middle} {paginate_next}
	{/if}
</div>


