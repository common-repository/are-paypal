{include file='last_action.inc.tpl'}
<div class="wrap">
	<h2>{$PageHeader}</h2>
	<form method="get" action="" style="margin: auto; width: 25em;">
		<input type="hidden" name="action" value="purchasers" />
		<input type="hidden" name="page" value="{$Page}" />
		<input type="hidden" name="post_id" value="{$PostID}" />
		<table>
			<tr>
				<th colspan="3"><strong>{$PostLabel}: {$PostTitle}</strong></th>
			</tr>
			<tr>
				<th>{$AvailableUsersLabel}</th>
				<th>&#160;</th>
				<th>{$PaidUsersLabel}</th>
			</tr>
			<tr>
				<td>
				{if $AvailableUsers}
					<select name="available_users" size="10" style="width: 200px; height: 200px;">
						{foreach from=$AvailableUsers item=user}
						<option value="{$user->id}">{$user->user_login}</option>
						{/foreach}
					</select>
				{else}
					Nothing here ...
				{/if}

				</td>
				<td>
				{if $AvailableUsers}
					<p class="submit" style="border-top: 0;">
						<input type="submit" name="PurchasersSubmit" value=">>" />
					</p>
				{/if}
				{if $PaidUsers}
					<p class="submit" style="border-top: 0;">
						<input name="PurchasersSubmit" type="submit" value="&lt;&lt;" />
					</p>
				{/if}
				</td>
				<td>
				{if $PaidUsers}
					<select name="paid_users" size="10" style="width: 200px; height: 200px;">
						{foreach from=$PaidUsers item=user}
						<option value="{$user->id}">{$user->user_login}</option>
						{/foreach}
					</select>
				{else}
					Nothing here ...
				{/if}
				</td>
			</tr>
		</table>
	</form>
	<form method="get" action="" style="margin: auto; width: 25em;">
		<input type="hidden" name="action" value="edit" />
		<input type="hidden" name="post_id" value="{$PostID}" />
		<input type="hidden" name="page" value="{$Page}" />
		<p class="submit">
			<input type="submit" value="Cancel&raquo;" />
		</p>
	</form>
</div>
