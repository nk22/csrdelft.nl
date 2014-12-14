{*
	menu_item.tpl	|	P.W.G. Brussee (brussee@live.nl)
*}
<li id="menu-item-{$item->item_id}" parentid="{$item->parent_id}" class="menu-item">
	<span class="lichtgrijs">{$item->prioriteit}</span>
	<a href="/menubeheer/bewerken/{$item->item_id}" class="btn post popup" title="Dit menu-item bewerken">{icon get="bewerken"}</a>
	{if LoginModel::mag('P_ADMIN')}
		<a href="/menubeheer/toevoegen/{$item->item_id}" class="btn post popup" title="Sub-menu-item toevoegen">{icon get="add"}</a>
	{/if}
	<a href="/menubeheer/zichtbaar/{$item->item_id}" class="btn post ReloadPage" title="Menu-item is nu {if !$item->zichtbaar}on{/if}zichtbaar"><img src="{$CSR_PICS}/famfamfam/{if $item->zichtbaar}eye{else}shading{/if}.png" /></a>
	<span>{$item->tekst}</span>
	{if LoginModel::mag('P_ADMIN')}
		<span class="lichtgrijs">{$item->item_id}</span>
	{/if}
	<div class="float-right">
		{if $item->rechten_bekijken !== 'P_PUBLIC' and $item->rechten_bekijken != LoginModel::getUid()}
			&nbsp;{icon get="group_key" title="Rechten bekijken:&#013;"|cat:$item->rechten_bekijken}&nbsp;
		{/if}
		<a href="{$item->link}">{$item->link}</a>
		<a href="/menubeheer/verwijderen/{$item->item_id}" class="btn post confirm ReloadPage" title="Dit menu-item definitief verwijderen">{icon get="cross"}</a>
	</div>
	{if $item->children}
		<ul class="menubeheer-tree">
			{foreach from=$item->children item=child}
				{include file='menu/beheer/menu_item.tpl' item=$child}
			{/foreach}
		</ul>
		<hr />
	{/if}
</li>