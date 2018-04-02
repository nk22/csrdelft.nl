<?php

namespace CsrDelft\view\fiscaat\pin;
use CsrDelft\view\formulier\elementen\HtmlComment;
use CsrDelft\view\formulier\invoervelden\TextField;
use CsrDelft\view\formulier\knoppen\FormDefaultKnoppen;
use CsrDelft\view\formulier\ModalForm;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 24/02/2018
 */
class PinBestellingVerwijderenForm extends ModalForm {
	public function __construct($model) {
		parent::__construct($model, '/fiscaat/pin/verwijder', 'Verwijder bestelling.', true);

		$fields[] = new HtmlComment('Er is geen transactie gevonden voor deze bestelling, druk op opslaan om deze bestelling te verwijderen.');
		$fields['id'] = new TextField('id', $model->id, 'Id');
		$fields['id']->hidden = true;

		$fields['btn'] = new FormDefaultKnoppen(null, false);

		$this->addFields($fields);
	}
}