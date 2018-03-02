<?php

namespace CsrDelft\view\ledenlijst;

use CsrDelft\Icon;
use CsrDelft\model\ProfielModel;
use CsrDelft\model\security\LoginModel;
use CsrDelft\view\formulier\datatable\DataTable;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 01/03/2018
 */
class LedenlijstTable extends DataTable {

	/**
	 * Kolommen zichtbaar voor iedereen.
	 */
	const COLUMN_NAMES = ['pasfoto', 'uid', 'naam', 'voorletters', 'voornaam', 'tussenvoegsel', 'achternaam', 'nickname',
		'duckname', 'geslacht', 'email', 'adres', 'telefoon', 'mobiel', 'linkedin', 'website', 'studie', 'status',
		'gebdatum', 'beroep', 'verticale', 'moot', 'lidjaar', 'kring', 'patroon', 'woonoord', 'bankrekening', 'eetwens'];

	/**
	 * Kolommen zichtbaar voor gebruikers met P_LEDEN_MOD rechten.
	 */
	const COLUMN_NAMES_MOD = ['studienr', 'muziek', 'ontvangtcontactueel', 'kerk', 'lidafdatum', 'echtgenoot',
		'adresseringechtpaar', 'land', 'bankrekening', 'machtiging'];

	/**
	 * Kolommen die default zichtbaar zijn.
	 */
	const COLUMN_NAMES_DEFAULT = ['naam', 'email', 'telefoon', 'mobiel'];

	public function __construct() {
		parent::__construct(ProfielModel::ORM, '/ledenlijst', 'Ledenlijst');

		// Zet een vaste dataTableId om stateSave te laten werken.
		$this->dataTableId = 'LedenlijstTable';
		$this->settings['stateSave'] = true;

		// Verwijder default datatable kolom.
		$this->deleteColumn('details');

		// Registreer orthogonale data source voor naam.
		$this->addColumn('naam', null, null, ['_' => 'display', 'export' => 'export'], 'achternaam');

		// Pasfoto is niet orderable
		$this->addColumn('pasfoto', null, null, null, null, 'string', false);

		// Laat een '+' zien als de tabel te breed wordt.
		$this->settings['responsive'] = true;

		// Laad tabel updates van de server.
		$this->settings['serverSide'] = true;

		// Laat een laadbalk zien als er gewerkt wordt.
		$this->settings['processing'] = true;

		// Reageer niet op selecties.
		$this->settings['select'] = false;

		// Override standaard knoppen.
		//  - Laat de export knoppen alleen zichtbare kolommen downloaden
		//  - Geef een override voor naam.
		$this->settings['buttons'] = [
			[
				'extend' => 'colvis',
				'className' => 'dt-button-ico dt-ico-' . Icon::get('table'),
			],
			[
				'extend' => 'copy',
				'exportOptions' => [
					'columns' => ':visible',
					'orthogonal' => 'export',
				]
			],
			[
				'extend' => 'csv',
				'exportOptions' => [
					'columns' => ':visible',
					'orthogonal' => 'export',
				]
			],
			[
				'extend' => 'excel',
				'exportOptions' => [
					'columns' => ':visible',
					'orthogonal' => 'export',
				]
			],
			[
				'extend' => 'print',
				'exportOptions' => [
					'columns' => ':visible',
					'orthogonal' => 'export',
				]
			],
		];
	}

	protected function getColumnNames() {
		if (LoginModel::mag('P_LEDEN_MOD')) {
			return array_merge(self::COLUMN_NAMES, self::COLUMN_NAMES_MOD);
		} else {
			return self::COLUMN_NAMES;
		}
	}

	protected function getHiddenColumnNames() {
		if (LoginModel::mag('P_LEDEN_MOD')) {
			return array_diff(array_merge(self::COLUMN_NAMES, self::COLUMN_NAMES_MOD), self::COLUMN_NAMES_DEFAULT);
		} else {
			return array_diff(self::COLUMN_NAMES, self::COLUMN_NAMES_DEFAULT);
		}
	}
}
