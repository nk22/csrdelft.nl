<h1>Agenda {$datum|date_format:"%B %Y"}</h1>

{$melding}

<table class="agenda" id="maand">
	<a class="knop" href="{$urlVorige}" style="float: left;" >&laquo; Vorige maand</a></td>
	<a class="knop" href="{$urlVolgende}" style="float: right;">Volgende maand &raquo;</a>
	<br /><br style="clear: both;" />
	<tr>
		<th> </th>
		<th>Zondag</th>
		<th>Maandag</th>
		<th>Dinsdag</th>
		<th>Woensdag</th>
		<th>Donderdag</th>
		<th>Vrijdag</th>
		<th>Zaterdag</th>
	</tr>
	{foreach from=$weken key=weeknr item=dagen}
		<tr id="{if strftime('%U', $dag.datum) == strftime('%U')}dezeweek{/if}">
			<th>{$weeknr}</th>
			{foreach from=$dagen key=dagnr item=dag}
				<td class="dag {if strftime('%m', $dag.datum) != strftime('%m', $datum)}anderemaand{/if}"
					{if date('d-m', $dag.datum)==date('d-m')}id="vandaag"{/if}>
					<div class="meta">
						{if	$magToevoegen}
							<a class="toevoegen" href="/actueel/agenda/toevoegen/{$dag.datum|date_format:"%Y-%m-%d"}/" 
								title="Item toevoegen">
								{icon get="toevoegen"}
							</a>
						{/if}
						{$dagnr}
					</div>
					<ul class="items">
						{foreach from=$dag.items item=item name=agendaItems}
							<li {if $smarty.foreach.agendaItems.iteration % 2==1}class="odd"{/if}>
							{if $magBeheren && $item instanceof AgendaItem}
								 <a class="beheren" href="/actueel/agenda/verwijderen/{$item->getItemID()}/" onclick="return confirm('Weet u zeker dat u dit agenda-item wilt verwijderen?');" title="verwijderen">
									{icon get="verwijderen"}
								</a>
								 <a class="beheren" href="/actueel/agenda/bewerken/{$item->getItemID()}/" title="bewerken">
									{icon get="bewerken"}
								</a>
							{/if}
							<div class="tijd">
								{$item->getBeginMoment()|date_format:"%R"}-{$item->getEindMoment()|date_format:"%R"}
							</div>
							<strong title="{$item->getBeschrijving()|escape:'htmlall'}">{$item->getTitel()}</strong>
							</li>
						{/foreach}
					</ul>
				</td>
			{/foreach}
		</tr>
	{/foreach}
</table>
