<?php

/**
 * MaaltijdRepetitieForm.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Formulier voor een nieuwe of te bewerken maaltijd-repetitie.
 * 
 */
class MaaltijdRepetitieForm extends ModalForm {

	public function __construct($mrid, $dag = null, $periode = null, $titel = null, $tijd = null, $prijs = null, $abo = null, $limiet = null, $filter = null, $verplaats = null) {
		parent::__construct(null, 'maalcie-maaltijd-repetitie-form', maalcieUrl . '/opslaan/' . $mrid);

		if (!is_int($mrid) || $mrid < 0) {
			throw new Exception('invalid mrid');
		}
		if ($mrid === 0) {
			$this->titel = 'Maaltijdrepetitie aanmaken';
		} else {
			$this->titel = 'Maaltijdrepetitie wijzigen';
			$this->css_classes[] = 'PreventUnchanged';
		}

		$fields[] = new RequiredTextField('standaard_titel', $titel, 'Standaard titel', 255);
		$fields[] = new TijdField('standaard_tijd', $tijd, 'Standaard tijd', 15);
		$fields['dag'] = new WeekdagField('dag_vd_week', $dag, 'Dag v/d week');
		$fields['dag']->title = 'Als de periode ongelijk is aan 7 is dit de start-dag bij het aanmaken van periodieke maaltijden';
		$fields[] = new IntField('periode_in_dagen', $periode, 'Periode (in dagen)', 0, 183);
		$fields['abo'] = new VinkField('abonneerbaar', $abo, 'Abonneerbaar');
		if ($mrid !== 0) {
			$fields['abo']->onchange = "if (!this.checked && $(this).attr('origvalue') == 1) if (!confirm('Alle abonnementen zullen worden verwijderd!')) this.checked = true;";
		}
		$fields[] = new BedragField('standaard_prijs', $prijs, 'Standaard prijs', '€', 0, 50);
		$fields[] = new IntField('standaard_limiet', $limiet, 'Standaard limiet', 0, 200);
		$fields[] = new RechtenField('abonnement_filter', $filter, 'Aanmeldrestrictie');
		if ($mrid !== 0) {
			$fields['ver'] = new VinkField('verplaats_dag', $verplaats, 'Verplaatsen');
			$fields['ver']->title = 'Verplaats naar dag v/d week bij bijwerken';
			$fields['ver']->onchange = <<<JS
var txt = $('#extraButton').html();
if (this.checked) {
	txt = txt.replace('bijwerken', 'bijwerken en verplaatsen');
} else {
	txt = txt.replace(' en verplaatsen', '');
}
$('#extraButton').html(txt);
JS;
		}
		$fields['btn'] = new FormKnoppen();
		$fields['btn']->extraText = 'Alles bijwerken';
		$fields['btn']->extraTitle = 'Opslaan & alle maaltijden bijwerken';
		$fields['btn']->extraIcon = 'disk_multiple';
		$fields['btn']->extraUrl = maalcieUrl . '/bijwerken/' . $mrid;
		$fields['btn']->extraAction = 'submit';

		$this->addFields($fields);
	}

}
