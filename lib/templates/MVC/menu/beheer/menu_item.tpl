{*
	menu_item.tpl	|	P.W.G. Brussee (brussee@live.nl)
*}
<li id="menu-item-{$item->menu_id}" parentid="{$item->parent_id}" class="menu-item"{if !$item->menu_id} style="list-style-type: none; background: none;"{/if}>
	<div class="inline-edit-{$item->menu_id}" style="text-align: left;">
		<div style="display: inline-block; width: 25px;">
{if $item->menu_id}
			<a title="Item wijzigen" class="knop" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();">{icon get="pencil"}</a>
{/if}
		</div>
		<div style="display: inline-block; width: 40px;">
			<a title="Nieuw sub-item" class="knop" onclick="menubeheer_clone({$item->menu_id});">{icon get="add"}</a>
		</div>
		<div style="display: inline-block; width: 50px; color: grey;">
			{$item->menu_id}
		</div>
		<div style="display: inline-block; width: 160px;{if $item->children} font-weight: bold;{/if}">
			{$item->tekst}
		</div>
		<div style="display: inline-block; width: 275px;">
			<a href="{$item->link}">{$item->link}</a>
		</div>
{if $item->menu_id}
		<div style="display: inline-block; width: 25px;">
			<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/zichtbaar">
				<input type="hidden" name="Zichtbaar" value="{if $item->zichtbaar}0{else}1{/if}" />
				<input type="image" src="{$CSR_PICS}/famfamfam/{if $item->zichtbaar}eye{else}shading{/if}.png" title="{if $item->zichtbaar}Menu-item is nu zichtbaar.&#013;Klik om onzichtbaar te maken.{else}Menu-item is nu onzichtbaar.&#013;Klik om zichtbaar te maken{/if}" />
			</form>
		</div>
		<div style="display: inline-block; width: 60px; text-align: center;">
			{$item->prioriteit}
		</div>
		<div style="display: inline-block; width: 25px;">
			<a href="/menubeheer/verwijder/{$item->menu_id}" title="Menu-item definitief verwijderen" class="knop confirm">{icon get="cross"}</a>
		</div>
{/if}
	</div>
{if $item->menu_id}
	<div class="inline-edit-{$item->menu_id}" style="display: none;">
		<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/parentId">
			<div style="display: inline-block; width: 75px;">Parent id:</div>
			<input type="text" name="ParentId" maxlength="5" size="60" value="{$item->parent_id}" />
			&nbsp;<input type="submit" value="opslaan" />
			&nbsp;<input type="reset" value="annuleren" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();" />
		</form>
		<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/prioriteit">
			<div style="display: inline-block; width: 75px;">Prioriteit:</div>
			<input type="text" name="Prioriteit" maxlength="5" size="60" value="{$item->prioriteit}" />
			&nbsp;<input type="submit" value="opslaan" />
			&nbsp;<input type="reset" value="annuleren" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();" />
		</form>
		<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/tekst">
			<div style="display: inline-block; width: 75px;">Label:</div>
			<input type="text" name="Tekst" maxlength="255" size="60" value="{$item->tekst}" />
			&nbsp;<input type="submit" value="opslaan" />
			&nbsp;<input type="reset" value="annuleren" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();" />
		</form>
		<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/link">
			<div style="display: inline-block; width: 75px;">Url:</div>
			<input type="text" name="Link" maxlength="255" size="60" value="{$item->link}" />
			&nbsp;<input type="submit" value="opslaan" />
			&nbsp;<input type="reset" value="annuleren" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();" />
		</form>
		<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/permission">
			<div style="display: inline-block; width: 75px;">Rechten:</div>
			<input type="text" name="Permission" maxlength="255" size="60" value="{$item->permission}" />
			&nbsp;<input type="submit" value="opslaan" />
			&nbsp;<input type="reset" value="annuleren" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();" />
		</form>
		<form method="post" action="/menubeheer/wijzig/{$item->menu_id}/menu">
			<div style="display: inline-block; width: 75px;">Menu:</div>
			<input type="text" name="Menu" maxlength="255" size="60" value="{$item->menu}" />
			&nbsp;<input type="submit" value="opslaan" />
			&nbsp;<input type="reset" value="annuleren" onclick="$('.inline-edit-{$item->menu_id}').slideDown();$(this).parent().parent().slideUp();" />
		</form>
	</div>
{/if}
	<ul id="children-{$item->menu_id}">
		<li id="inline-newchild-{$item->menu_id}" style="display: none;">
			<form method="post" action="/menubeheer/nieuw/{$item->menu_id}">
				<div style="display: inline-block; width: 75px;">Parent id:</div>
				<input type="text" name="ParentId" maxlength="5" size="60" value="{$item->menu_id}" /><br />
				<div style="display: inline-block; width: 75px;">Prioriteit:</div>
				<input type="text" name="Prioriteit" maxlength="5" size="60" value="0" /><br />
				<div style="display: inline-block; width: 75px;">Label:</div>
				<input type="text" name="Tekst" maxlength="255" size="60" value="Tekst" /><br />
				<div style="display: inline-block; width: 75px;">Url:</div>
				<input type="text" name="Link" maxlength="255" size="60" value="/url" /><br />
				<div style="display: inline-block; width: 75px;">Rechten:</div>
				<input type="text" name="Permission" maxlength="255" size="60" value="P_NOBODY" /><br />
				<div style="display: inline-block; width: 75px;">&nbsp</div>
				<input type="hidden" name="Menu" value="{$item->menu}" />
				<input type="submit" value="opslaan" />&nbsp;
				<input type="reset" value="annuleren" onclick="$(this).parent().parent().slideUp(400, function() {ldelim} $(this).remove(); {rdelim});" />
			</form>
		</li>
	{foreach from=$item->children item=child}
		{include file='MVC/menu/beheer/menu_item.tpl' item=$child}
	{/foreach}
	</ul>
	{if $item->children}
	<hr />
	{/if}
</li>