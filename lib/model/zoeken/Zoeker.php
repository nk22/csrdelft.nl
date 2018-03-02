<?php

namespace CsrDelft\model\zoeken;
use CsrDelft\Orm\PersistenceModel;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 01/03/2018
 */
class Zoeker {
	/**
	 * @var PersistenceModel
	 */
	private $model;

	/**
	 * @var int
	 */
	private $limit;

	private $criteria;
	private $criteria_params;
	private $orderBy;
	private $offset;
	private $fields;

	/**
	 * @var callable[]
	 */
	protected $zoekMethoden = [];

	public function __construct(PersistenceModel $model) {
		$this->model = $model;
	}

	/**
	 * @param int $limit
	 */
	public function setLimit(int $limit) {
		$this->limit = $limit;
	}

	public function setOffset($offset) {
		$this->offset = $offset;
	}

	public function setFields($fields) {
		$this->fields = $fields;
	}

	public function zoekSimple($term) {
		$this->criteria = $this->createCriteria($term);

		return $this->model->find($this->criteria, [], null, $this->orderBy, $this->limit, $this->offset);
	}

	private function createCriteria($term) {
		$criteria = "";
		foreach ($this->zoekMethoden as $zoekMethode) {
			$criteria .= call_user_func($zoekMethode, $this->fields, $term);
		}

		return $criteria;
	}
}
