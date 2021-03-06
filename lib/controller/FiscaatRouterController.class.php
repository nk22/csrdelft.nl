<?php

namespace CsrDelft\controller;

use CsrDelft\model\fiscaat\CiviSaldoModel;
use CsrDelft\view\fiscaat\producten\CiviProductTable;
use CsrDelft\view\fiscaat\saldo\CiviSaldoTable;
use CsrDelft\view\fiscaat\saldo\SaldiSomForm;

class FiscaatRouterController {
	/** @var CiviSaldoModel */
	private $civiSaldoModel;

	public function __construct() {
		$this->civiSaldoModel = CiviSaldoModel::instance();
	}

	public function overzicht() {
		return view('fiscaat.overzicht', [
			'saldisomform' => new SaldiSomForm($this->civiSaldoModel),
			'saldisom' => $this->civiSaldoModel->getSomSaldi(),
			'saldisomleden' => $this->civiSaldoModel->getSomSaldi(true),
			'productenbeheer' => new CiviProductTable(),
			'saldobeheer' => new CiviSaldoTable(),
		]);
	}
}
