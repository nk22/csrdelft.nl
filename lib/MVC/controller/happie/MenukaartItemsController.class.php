<?php

require_once 'MVC/model/happie/MenukaartItemsModel.class.php';
require_once 'MVC/view/happie/MenukaartView.class.php';
require_once 'MVC/view/happie/forms/MenukaartForm.class.php';

/**
 * MenukaartItemsController.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Controller van de Happietaria menukaart-items.
 * 
 */
class HappieMenukaartItemsController extends AclController {

	public function __construct($query) {
		parent::__construct($query, HappieMenukaartItemsModel::instance());
		$this->acl = array(
			'overzicht'	 => 'groep:2014',
			'nieuw'		 => 'groep:2014',
			'wijzig'	 => 'groep:2014'
		);
	}

	public function performAction(array $args = array()) {
		$this->action = 'overzicht';
		if ($this->hasParam(3)) {
			$this->action = $this->getParam(3);
		}
		parent::performAction($this->getParams(4));
	}

	public function overzicht() {
		if ($this->isPosted()) {
			$data = $this->model->find();
			$this->view = new DataTableResponse($data);
		} else {
			$body = new HappieMenukaartItemsView();
			$this->view = new CsrLayout3Page($body);
		}
	}

	public function nieuw() {
		$item = $this->model->newItem();
		$form = new HappieMenukaartItemForm($item);
		if ($this->isPosted() AND $form->validate()) {
			$this->model->create($item);
			setMelding('Menukaart-item succesvol toegevoegd', 1);
			redirect(happieUrl);
		}
		$this->view = new CsrLayout3Page($form);
	}

	public function wijzig($id) {
		$item = $this->model->getItem((int) $id);
		if (!$item) {
			setMelding('Menukaart-item bestaat niet', -1);
			redirect(happieUrl);
		}
		$form = new HappieMenukaartItemWijzigenForm($item);
		if ($this->isPosted() AND $form->validate()) {
			$this->model->update($item);
			setMelding('Wijziging succesvol opgeslagen', 1);
			redirect(happieUrl);
		}
		$this->view = new CsrLayout3Page($form);
	}

}
