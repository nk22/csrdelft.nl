{strip}
	<div class="item">
	{if date('d-m', $timestamp) === date('d-m')}
		{$timestamp|date_format:"%H:%M"}
	{elseif strftime('%U', $timestamp) === strftime('%U')}
		<div style="display: inline-block; width: 20px;">{$timestamp|date_format:"%a"}</div>{$timestamp|date_format:"%d"}
	{else}
		{$timestamp|date_format:"%d"}
		<div style="display: inline-block; width: 6px; text-align: right;">-</div>
		<div style="display: inline-block;">{$timestamp|date_format:"%m"}</div>
	{/if}
		&nbsp;
		<a href="/communicatie/forum/reactie/{$postID}" title="[{$titel}] {$naam}: {$postfragment}"{if $opvallend} class="opvallend"{/if}>{$linktekst}</a><br />
	</div>
{/strip}