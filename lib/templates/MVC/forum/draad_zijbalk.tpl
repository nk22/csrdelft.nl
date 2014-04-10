{strip}
	{assign var=timestamp value=strtotime($draad->laatst_gewijzigd)}
	<div class="item hoverIntent">
		{*include file='MVC/forum/post_preview.tpl'*}
		{if date('d-m', $timestamp) === date('d-m')}
			{$timestamp|date_format:"%H:%M"}
		{elseif strftime('%U', $timestamp) === strftime('%U')}
			<div class="zijbalk-dag">{$timestamp|date_format:"%a"}&nbsp;</div>{$timestamp|date_format:"%d"}
		{else}
			{$timestamp|date_format:"%d-%m"}
		{/if}
		&nbsp;
		<a href="/forum/onderwerp/{$draad->draad_id}{if LidInstellingen::get('forum', 'openDraadPagina') == 'ongelezen'}#ongelezen{elseif LidInstellingen::get('forum', 'openDraadPagina') == 'laatste'}#reageren{/if}" title="{$draad->titel}"{if !$draad->alGelezen()} class="opvallend"{/if}>
			{$draad->titel|truncate:25:"…":true}
		</a>
	</div>
{/strip}