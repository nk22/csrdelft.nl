<?php

namespace CsrDelft\view\groepen;

use CsrDelft\model\entity\Geslacht;
use CsrDelft\model\entity\groepen\AbstractGroep;
use CsrDelft\model\ProfielModel;
use CsrDelft\view\View;

class GroepenDeelnameGrafiek implements View {

	private $series = array();
	private $step = array();

	private $mannen = [];
	private $vrouwen = [];

	/**
	 * GroepenDeelnameGrafiek constructor.
	 * @param AbstractGroep[] $groepen
	 */
	public function __construct($groepen) {
		$aantalMannen = [];
		$aantalVrouwen = [];
		$groepNamen = [];
		$groepJaren = [];
		foreach ($groepen as $groep) {
			$mannen = 0;
			$vrouwen = 0;

			var_dump($groep->getLeden());

			foreach ($groep->getLeden() as $lid) {
				$profiel = ProfielModel::get($lid->uid);
				if ($profiel->geslacht === Geslacht::Man) {
					$mannen += 1;
				} else {
					$vrouwen += 1;
				}
			}

			$this->series[] = [
				"moment" => strtotime($groep->begin_moment) * 1000,
				"aantalMannen" => $mannen,
				"aantalVrouwen" => $vrouwen,
				"naam" => $groep->naam,
			];

			$aantalMannen[] = $mannen;
			$aantalVrouwen[] = $vrouwen;
			$groepNamen[] = $groep->naam;
			$groepJaren[] = strftime('%Y', strtotime($groep->begin_moment));
		}
		$this->series = [
			'labels'=> $groepNamen,
			'jaren' => $groepJaren,
			'datasets' => [
				[
					'label' => 'Aantal mannen',
					'data' => $aantalMannen,
					'backgroundColor' => '#AFD8F8',
				],
				[
					'label' => 'Aantal vrouwen',
					'data' => $aantalVrouwen,
					'backgroundColor' => '#FFCBDB',
				]
			]
		];
	}

	public function getBreadcrumbs() {
		return null;
	}

	public function getModel() {
		return $this->series;
	}

	public function getTitel() {
		return null;
	}

	public function view() {
		$step = htmlspecialchars(json_encode($this->step));

		$series = htmlspecialchars(json_encode($this->series));

		echo <<<HTML
<div id="deelnamegrafiek">
	<div class="ctx-deelnamegrafiek" style="height: 360px;width:100%;" data-data="{$series}" data-step="{$step}"></svg>
</div>
HTML;
	}

}
