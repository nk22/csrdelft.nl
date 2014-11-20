<?php

require_once 'MVC/model/entity/happie/HappieGang.enum.php';

/**
 * BestelForm.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Bestelformulier voor complete menukaart.
 * 
 */
class HappieBestellingWijzigenForm extends Formulier {

	public function __construct(HappieBestelling $bestelling) {
		parent::__construct($bestelling, get_class($this), happieUrl . '/wijzigen/' . $bestelling->bestelling_id, 'Bestelling wijzigen');

		$fields[] = new DatumField('datum', $bestelling->datum, 'Datum');

		$fields[] = new SelectField('tafel', null, 'Tafel', range(0, 99)); // array index starts from 0
		// maak invoerveld voor elk item per groep
		$menukaart = HappieMenukaartItemsModel::instance()->getMenukaart();
		$options = array();
		$beschikbaar = 0;
		foreach ($menukaart as $gang) {
			foreach ($gang as $groep) {
				foreach ($groep->getItems() as $item) {
					$options[$groep->naam][$item->item_id] = $item->naam;
					if ($item->item_id == $bestelling->menukaart_item) {
						$beschikbaar = min($item->aantal_beschikbaar, $groep->aantal_beschikbaar);
					}
				}
			}
		}
		$fields[] = new SelectField('menukaart_item', $bestelling->menukaart_item, 'Menukaart item', $options, true);

		$fields[] = new IntField('aantal', $bestelling->aantal, 'Aantal', 0, $beschikbaar);
		$fields[] = new IntField('aantal_geserveerd', $bestelling->aantal_geserveerd, 'Aantal geserveerd', 0, $beschikbaar); // meer vrijheid dan $bestelling->aantal

		$options = array();
		foreach (HappieServeerStatus::getTypeOptions() as $option) {
			$options[$option] = $option;
		}
		$fields[] = new SelectField('serveer_status', $bestelling->serveer_status, 'Serveer status', $options);

		$options = array();
		foreach (HappieFinancienStatus::getTypeOptions() as $option) {
			$options[$option] = $option;
		}
		$fields[] = new SelectField('financien_status', $bestelling->financien_status, 'Financien status', $options);

		$fields[] = new TextareaField('opmerking', $bestelling->opmerking, 'Allergie/Opmerking');

		$fields[] = new FormDefaultKnoppen(happieUrl);

		$fields['l'] = new TextField('laatst_gewijzigd', $bestelling->laatst_gewijzigd, 'Laatst gewijzigd');
		$fields['l']->readonly = true;

		$fields['h'] = new TextareaField('wijzig_historie', $bestelling->wijzig_historie, 'Log');
		$fields['h']->readonly = true;

		$this->addFields($fields);
	}

}

class HappieBestelForm extends TabsForm {

	private $js = '';

	public function __construct() {
		parent::__construct(null, get_class($this), happieUrl . '/nieuw', 'Nieuwe bestelling');

		// tafel invoer
		$table = new SelectField('tafel', null, 'Tafel', range(0, 99)); // array index starts from 0
		$fields[] = $table;

		$fields['k'] = new FormDefaultKnoppen(happieUrl . '/serveer', false);
		$fields['k']->confirmAll();
		$fields['k']->css_classes[] = 'float-right';
		$this->addFields($fields, 'head');

		// maak invoerveld voor elk menukaart-item
		$menukaart = HappieMenukaartItemsModel::instance()->getMenukaart();
		foreach ($menukaart as $gang => $groepen) {
			$this->addTab($gang);

			// groepeer items
			foreach ($groepen as $groep_id => $groep) {
				$fields = array();

				$fields[] = new Subkopje('<div id="toggle_groep_' . $groep_id . '" class="toggle_groep">' . $groep->naam . '</div>');
				$fields[] = new HtmlComment('<div id="expand_groep_' . $groep_id . '">');

				$this->js .= <<<JS
$('#toggle_groep_{$groep_id}').click(function() {
	if ($('#expand_groep_{$groep_id}').is(":visible")) {
		$('#expand_groep_{$groep_id}').slideUp(200);
	} else {
		$('#expand_groep_{$groep_id}').slideDown(200);
	}
});
JS;
				foreach ($groep->getItems() as $item) {

					// preload bestelling aantal
					if (isset($bestellingen[$item->item_id])) {
						$aantal = $bestellingen[$item->item_id]->aantal;
						$opmerking = $bestellingen[$item->item_id]->opmerking;
					} else {
						$aantal = 0;
						$opmerking = '';
					}
					$beschikbaar = min($item->aantal_beschikbaar, $groep->aantal_beschikbaar);

					$int = new IntField('item' . $item->item_id, $aantal, $item->naam, 0, $beschikbaar);
					$int->min_alert = 'Minder dan 0';
					$int->max_alert = 'Te weinig beschikbaar';
					$fields[] = $int;

					if ($beschikbaar > 0 OR $aantal > 0) {
						$comment = '<div id="toggle_' . $item->item_id . '" data-allergie="&nbsp;' . $item->allergie_info . '&nbsp;" class="btn toggle-opmerking" style="margin-left:5px;padding:0 0.5em;"><img src="' . CSR_PICS . '/famfamfam/information.png" class="icon" width="16" height="16"></div>';
					} else {
						$comment = '<div id="toggle_' . $item->item_id . '" class="inline alert alert-warning" style="margin-left:5px;padding:0 .3em;">OP</div>';
					}
					$fields[] = new HtmlComment($comment . <<<HTML
<div id="expand_{$item->item_id}" style="display:none;">
	<div class="inline allergie-info alert alert-info" style="margin:0 5px;padding:0 .3em;">{$item->allergie_info}</div>
	<div class="inline prijs">{$item->getPrijsFormatted()}</div>
HTML
					);
					$opm = new TextareaField('opmerking' . $item->item_id, $opmerking);
					$opm->placeholder = 'Allergie van klant / opmerking';
					$fields[] = $opm;

					$fields[] = new HtmlComment('<div class="beschrijving" style="font-style:italic;">' . $item->beschrijving . '</div></div>'); // close expanded allergie

					$this->js .= <<<JS
$('#{$opm->getId()}').height('30px');
$('#toggle_{$item->item_id}').appendTo('#wrapper_{$int->getId()}').click(function() {
	$('#expand_{$item->item_id}').toggle().find('textarea:first').focus();
});
JS;
				}

				$fields[] = new HtmlComment('</div>'); // close expanded groep
				// voeg groep toe aan tab en maak tab voor elke gang
				$this->addFields($fields, $groep->gang);
			}
		}

		$fields = array();

		$fields['k'] = new FormDefaultKnoppen(happieUrl, false);
		$fields['k']->confirmAll();
		$this->addFields($fields, 'foot');

		$allergie = new FormulierKnop(null, 'toggle-allergie', 'Allergie-info', 'Toon allergie informatie', '/famfamfam/information.png', true);
		$fields['k']->addKnop($allergie);

		$this->js .= <<<JS
$('.Formulier label').css('width', '50%');
$('#wrapper_{$table->getId()} label').css('width', '20%');
$('.FormDefaultKnoppen:first').appendTo('#wrapper_field_tafel');
var flipAllergie = function(flip) {
	if (typeof flip !== 'boolean') {
		flip = $(this).prop('flip');
	}
	if (flip) {
		$('.toggle-opmerking').each(function() {
			$(this).attr('data-allergie', $(this).html());
			$(this).html($(this).attr('data-img'));
		});
	}
	else {
		$('.toggle-opmerking').each(function() {
			$(this).attr('data-img', $(this).html());
			$(this).html($(this).attr('data-allergie'));
		});
	}
	$(this).prop('flip', !flip);
};
$('#{$allergie->getId()}').click(flipAllergie);
JS;
	}

	public function getJavascript() {
		return parent::getJavascript() . $this->js;
	}

	/**
	 * Groepeer de waarden per item.
	 */
	public function getValues() {
		$tafel = (int) $this->findByName('tafel')->getValue();
		$values = array();
		foreach ($this->getFields() as $field) {
			// aantal veld
			if ($field instanceof IntField) {
				$item_id = (int) substr($field->getName(), 4);
				$values[$item_id]['aantal'] = $field->getValue();
				$values[$item_id]['tafel'] = $tafel;
			}
			// opmerking veld
			elseif ($field instanceof TextareaField) {
				$item_id = (int) substr($field->getName(), 9);
				$field->empty_null = true;
				$values[$item_id]['opmerking'] = $field->getValue();
			}
		}
		return $values;
	}

}
