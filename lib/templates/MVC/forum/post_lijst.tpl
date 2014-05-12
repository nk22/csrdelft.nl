{if $post->gefilterd}
	<tr>
		<td colspan="2" class="filtered">
			<a class="weergeeflink" onclick="jQuery('#forumpost-row-{$post->post_id}').show();
					jQuery(this).remove()">
				&gt;&gt {$post->gefilterd}, klik om weer te geven. &lt;&lt;
			</a>
		</td>
	</tr>
{/if}
<tr id="forumpost-row-{$post->post_id}"{if $post->gefilterd} style="display:none;"{/if}>
	<td class="auteur">
		<a href="/forum/reactie/{$post->post_id}#{$post->post_id}" id="{$post->post_id}" class="postlink" title="Link naar deze post">&rarr;</a>
		{$post->lid_id|csrnaam:'user':'visitekaartje'}
		{if LoginLid::mag('P_LEDEN_READ')}
			<span tabindex="0" id="t{$post->lid_id}-{$post->post_id}" class="togglePasfoto"{if LoginLid::instelling('forum_toonpasfotos') == 'nee'} title="Toon pasfoto">&raquo;{else}>{/if}</span>
		{/if}<br />
		<div id="p{$post->post_id}" class="forumpasfoto{if LoginLid::instelling('forum_toonpasfotos') == 'nee'} verborgen">{elseif LoginLid::mag('P_LEDEN_READ')}">{$post->lid_id|csrnaam:'pasfoto'}{/if}</div>
		<span class="moment">
			{if LoginLid::instelling('forum_datumWeergave') === 'relatief'}
				{$post->datum_tijd|reldate}
			{else}
				{$post->datum_tijd}
			{/if}
		</span>
		{if isset($deel)}
			<div class="forumpostKnoppen">
				{if !$draad->gesloten AND $deel->magPosten() AND !$post->wacht_goedkeuring}
					<a href="#reageren" class="knop" onclick="forumCiteren({$post->post_id});" title="Citeer bericht">{icon get="comments"}</a>
				{/if}
				{if (($deel->magPosten() AND !$draad->gesloten AND $post->lid_id === LoginLid::instance()->getUid() AND LoginLid::mag('P_LOGGED_IN')) OR $deel->magModereren())}
					<a href="#{$post->post_id}" class="knop
					   {if $deel->magModereren() AND $post->lid_id !== LoginLid::instance()->getUid() AND !$post->wacht_goedkeuring} forummodknop
					   {/if}" onclick="forumBewerken({$post->post_id});" title="Bewerk bericht">{icon get="pencil"}</a>
				{/if}
				{if $deel->magModereren()}
					<a href="/forum/offtopic/{$post->post_id}" class="knop post confirm{if !$post->wacht_goedkeuring} forummodknop{/if}" title="Offtopic markeren">{icon get="thumb_down"}</a>
					<a href="/forum/verwijderen/{$post->post_id}" class="knop post confirm{if !$post->wacht_goedkeuring} forummodknop{/if}" title="Verwijder bericht">{icon get="cross"}</a>
					<a href="/forum/verplaatsen/{$post->post_id}" class="knop post prompt{if !$post->wacht_goedkeuring} forummodknop{/if}" title="Verplaats bericht" postdata="draad_id={$post->draad_id}">{icon get=arrow_right}</a>
				{/if}
				{if $post->verwijderd}
					<span style="color: red;">Deze reactie is verwijderd.</span>
				{/if}
			</div>
		{else if $post->wacht_goedkeuring}
			<br /><br />
			<a href="/forum/goedkeuren/{$post->post_id}" class="knop post confirm" title="Bericht goedkeuren">goedkeuren</a>
			<br /><br />
			<a href="/tools/stats.php?ip={$post->auteur_ip}" class="knop" title="IP-log">IP-log</a>
			<a href="/forum/verwijderen/{$post->post_id}" class="knop post confirm" title="Verwijder bericht of draad">{icon get="cross"}</a>
		{/if}
	</td>
	<td class="bericht{cycle values="0,1"}" id="post{$post->post_id}">
		<div class="bericht">
			{$post->tekst|ubb}
			{if $post->bewerkt_tekst}
				<div class="bewerkt clear">
					<hr />
					{$post->bewerkt_tekst|ubb}
				</div>
			{/if}
		</div>
	</td>
</tr>