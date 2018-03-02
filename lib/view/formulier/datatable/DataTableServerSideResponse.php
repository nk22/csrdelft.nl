<?php

namespace CsrDelft\view\formulier\datatable;
use CsrDelft\view\JsonResponse;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 01/03/2018
 */
class DataTableServerSideResponse extends JsonResponse {
	public $autoUpdate = false;
	public $modal = null;
	private $recordsTotal;
	private $recordsFiltered;

	public function __construct($model, $recordsTotal, $recordsFiltered) {
		parent::__construct($model);
		$this->recordsTotal = $recordsTotal;
		$this->recordsFiltered = $recordsFiltered;
	}

	public function view() {
		http_response_code($this->code);
		header('Content-Type: application/json');
		echo "{\n";
		echo '"modal":' . json_encode($this->modal) . ",\n";
		echo '"draw":' . filter_input(INPUT_POST, 'draw', FILTER_SANITIZE_NUMBER_INT) . ",\n";
		echo '"recordsTotal":' . $this->recordsTotal . ",\n";
		echo '"recordsFiltered":' . $this->recordsFiltered . ",\n";
		echo '"autoUpdate":' . json_encode($this->autoUpdate) . ",\n";
		echo '"lastUpdate":' . json_encode(time() - 1) . ",\n";
		echo '"data":[' . "\n";
		$comma = false;
		foreach ($this->model as $entity) {
			if ($comma) {
				echo ",\n";
			} else {
				$comma = true;
			}
			$json = $this->getJson($entity);
			if ($json) {
				echo $json;
			} else {
				$comma = false;
			}
		}
		echo "\n]}";
	}
}
