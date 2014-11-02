{*
	instelling_row.tpl	|	P.W.G. Brussee (brussee@live.nl)
*}
{strip}
<tr id="instelling-row-{$id}">
	<td>
		<a title="Instelling wijzigen" class="knop rounded wijzigknop" onclick="
			if (confirm($(this).attr('title') + '.\n\nWeet u het zeker?')) {
				var form = $('#form-{$id}');
				form_inline_toggle(form);
				form.find('.InstellingToggle').toggle();
				$(this).toggle();
			}
		   ">{icon get="pencil"}</a>
	</td>
	<td><nobr>{$id|replace:'_':' '}</nobr></td>
	<td>
		<form id="form-{$id}" method="post" action="/instellingenbeheer/opslaan/{$module}/{$id}" class="Formulier InlineForm">
			<div class="InstellingToggle">{$waarde}</div>
			<div class="InstellingToggle verborgen">&nbsp;</div>
			<div class="InputField">
				<textarea name="waarde" origvalue="{htmlspecialchars($waarde)}" class="FormElement" rows="1">{$waarde}</textarea>
			</div>
			<div class="InstellingToggle verborgen"></div>
			<div class="FormKnoppen">
				<a class="knop submit" title="Wijzigingen opslaan">{icon get="accept"} Opslaan</a>
				<a class="knop reset cancel" title="Annuleren" onclick="
					$(this).parent().find('.InstellingToggle').toggle();
					$(this).parent().parent().parent().find('.wijzigknop').toggle();
				">{icon get="delete"} Annuleren</a>
			</div>
		</form>
	</td>
	<td class="col-del">
		<a href="/instellingenbeheer/reset/{$module}/{$id}" title="Instelling resetten" class="knop rounded post confirm">{icon get="arrow_rotate_anticlockwise"}</a>
	</td>
</tr>
{/strip}