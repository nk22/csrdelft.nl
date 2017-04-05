<?php

require_once 'model/fiscaat/CiviProductModel.class.php';
require_once 'view/fiscaat/BeheerCiviProductenView.class.php';
require_once 'view/fiscaat/CiviProductenSuggestiesView.class.php';

/**
 * Class BeheerProductenController
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 *
 * @property CiviProductModel $model
 */
class BeheerCiviProductenController extends AclController {
	public function __construct($query) {
		parent::__construct($query, CiviProductModel::instance());

		if ($this->getMethod() == "POST") {
			$this->acl = [
				'overzicht' => 'P_MAAL_MOD',
				'toevoegen' => 'P_MAAL_MOD',
				'bewerken' => 'P_MAAL_MOD',
				'opslaan' => 'P_MAAL_MOD',
				'verwijderen' => 'P_MAAL_MOD'
			];
		} else {
			$this->acl = [
				'overzicht' => 'P_MAAL_MOD',
				'suggesties' => 'P_MAAL_MOD'
			];
		}
	}

	public function performAction(array $args = array()) {
		$this->action = 'overzicht';

		if ($this->hasParam(3)) {
			$this->action = $this->getParam(3);
		}
		return parent::performAction($args);
	}

	public function GET_suggesties() {
		$query = '%' . $this->getParam('q') . '%';
		$this->view = new CiviProductenSuggestiesView($this->model->find('beschrijving LIKE ?', array($query)));
	}

	public function POST_overzicht() {
		$this->view = new BeheerProductenResponse($this->model->find());
	}

	public function GET_overzicht() {
		$this->view = new CsrLayoutPage(new BeheerCiviProductenView());
	}

	public function POST_toevoegen() {
		$form = new CiviProductForm(new CiviProduct(), 'opslaan/nieuw');

		if ($this->getMethod() == "POST" AND $form->validate()) {
			$product = $form->getModel();

			if ($this->model->exists($product)) {
				$this->model->update($product);
			} else {
				$this->model->create($product);
			}

			$this->view = new BeheerProductenResponse(array($product));
			return;
		}

		$this->view = $form;
	}

	public function POST_bewerken() {
		$selection = filter_input(INPUT_POST, 'DataTableSelection', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);

		/** @var CiviProduct $product */
		$product = $this->model->retrieveByUUID($selection[0]);
		$product->prijs = $this->model->getPrijs($product)->prijs;
		$this->view = new CiviProductForm($product, 'opslaan');
	}

	public function POST_opslaan() {
		if ($this->hasParam(4) AND $this->getParam(4) == "nieuw") {
			$form = new CiviProductForm(new CiviProduct(), 'opslaan/nieuw');
			if ($form->validate()) {
				$product = $form->getModel();
				$this->model->create($product);

				$this->view = new BeheerProductenResponse(array($product));
				return;
			}
		} else {
			$form = new CiviProductForm(new CiviProduct(), 'opslaan');
			var_dump($form->getFields());
			if ($form->validate()) {
				$product = $form->getModel();
				$this->model->update($product);

				$this->view = new BeheerProductenResponse(array($product));
				return;
			}
		}

		$this->view = $form;
	}
}