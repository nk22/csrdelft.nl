{foreach from=$items item=item}
{if $item instanceof Lid}{* geen verjaardagen *}{else}
{$item->getBeginMoment()|date_format:"%A %d-%m %H:%M"} [url=http://csrdelft.nl/agenda/maand/{$item->getBeginMoment()|date_format:"%Y-%m"}/]{$item->getTitel()|bbcode|strip_tags}[/url]
{/if}
{/foreach}