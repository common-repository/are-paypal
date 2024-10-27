{include file='last_action.inc.tpl'}
<div class="wrap">
	<h2>{$PageHeader}</h2>
	<form method="get" action="" style="margin: auto; width: 25em;">
		<input type="hidden" name="action" value="bonus" />
		<input type="hidden" name="page" value="{$Page}" />
		<input type="hidden" name="post_id" value="{$PostID}" />
		<table>
			<tr>
				<th colspan="3"><strong>{$PostLabel}: {$PostTitle}</strong></th>
			</tr>
			<tr>
				<th>{$AvailablePostsLabel}</th>
				<th>&#160;</th>
				<th>{$BonusPostsLabel}</th>
			</tr>
			<tr>
				<td>
				{if $AvailablePosts}
					<select name="post_to_package" size="10" style="width: 200px; height: 200px;">
						{foreach from=$AvailablePosts item=post}
							<option value="{$post->ID}">{$post->post_title}</option>
						{/foreach}
					</select>
				{else}
					Nothing here ...
				{/if}
				</td>
				<td>
				{if $AvailablePosts}
				<p class="submit" style="border-top: 0;">
					<input type="submit" name="BonusSubmit" value=">>" />
				</p>
				{/if}
				{if $BonusPosts}
				<p class="submit" style="border-top: 0;">
					<input name="BonusSubmit" type="submit" value="&lt;&lt;" />
				</p>
				{/if}
				</td>
				<td>
				{if $BonusPosts}
					<select name="post_in_package" size="10" style="width: 200px; height: 200px;">
						{foreach from=$BonusPosts item=post}
							<option value="{$post->ID}">{$post->post_title}</option>
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
		<input type="hidden" name="action" value="list" />
		<input type="hidden" name="page" value="{$Page}" />
		<p class="submit">
			<input type="submit" value="Cancel&raquo;" />
		</p>
	</form>
</div>
