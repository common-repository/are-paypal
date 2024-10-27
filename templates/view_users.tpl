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
					<td>{$item->user_login}</td>
					<td>
						{foreach from=$Posts[$k] item=post}
								{$post->post_title}<br />
						{/foreach}
					</td>
				</tr>
			{/foreach}
		</table>
		{else}
			<div class="error">{$NoPaidUsersFoundInDB}</div>
		{/if}
</div>