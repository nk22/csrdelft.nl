{strip}
	{assign var=draad value=$draden[$post->draad_id]}
	{assign var=timestamp value=strtotime($post->datum_tijd)}
	<div class="item hoverIntent">
		<div class="hoverIntentContent" style="margin-left: 175px; cursor: pointer;" onclick="location.href = '/forum/reactie/{$post->post_id}#{$post->post_id}';">
			<table id="forumtabel">
				<tbody>
					<tr>
						<td class="auteur">
							{$post->lid_id|csrnaam:'user':'pain'}:<br />
							<span class="moment">
								{if LoginLid::instelling('forum_datumWeergave') === 'relatief'}
									{$post->datum_tijd|reldate}
								{else}
									{$post->datum_tijd}
								{/if}
							</span>
						</td>
						<td class="bericht1">
							<div class="bericht forcebreak">{$post->tekst|ubb|html_substr:"300":"…"}</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		{if date('d-m', $timestamp) === date('d-m')}
			{$timestamp|date_format:"%H:%M"}
		{elseif strftime('%U', $timestamp) === strftime('%U')}
			<div class="zijbalk-dag">{$timestamp|date_format:"%a"}&nbsp;</div>{$timestamp|date_format:"%d"}
		{else}
			{$timestamp|date_format:"%d-%m"}
		{/if}
		&nbsp;
		<a href="/forum/reactie/{$post->post_id}#{$post->post_id}" title="{$draad->titel}"{if !$draad->alGelezen()} class="opvallend"{/if}>
			{$draad->titel|truncate:25:"…":true}
		</a>
	</span>
</div>
{/strip}