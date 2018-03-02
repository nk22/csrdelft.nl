<?php

namespace CsrDelft\controller;
use CsrDelft\controller\framework\AclController;
use CsrDelft\model\ProfielModel;
use CsrDelft\view\CsrLayoutPage;
use CsrDelft\view\ledenlijst\LedenlijstTableResponse;
use CsrDelft\view\ledenlijst\LedenlijstView;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 01/03/2018
 */
class LedenlijstController extends AclController {
	public function __construct($query) {
		parent::__construct($query, ProfielModel::instance());

		if ($this->getModel() == 'POST') {
			$this->acl = [
				'overzicht' => 'P_LOGGED_IN',
			];
		} else {
			$this->acl = [
				'overzicht' => 'P_LOGGED_IN',
			];
		}
	}

	/**
	 * @param array $args
	 * @return mixed|void
	 * @throws \CsrDelft\common\CsrException
	 */
	public function performAction(array $args = array()) {
		$this->action = 'overzicht';

		parent::performAction($args);
	}

	public function GET_overzicht() {
		$this->view = new CsrLayoutPage(new LedenlijstView());
	}

	public function POST_overzicht() {
		$start = filter_input(INPUT_POST, 'start', FILTER_SANITIZE_NUMBER_INT);
		$length = filter_input(INPUT_POST, 'length', FILTER_SANITIZE_NUMBER_INT);
		$search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);
		$order = filter_input(INPUT_POST, 'order', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);
		$columns = filter_input(INPUT_POST, 'columns', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);

		$orderBy = [];
		foreach ($order as $item) {
			$orderBy[] = $columns[$item['column']]['name'] . ' ' . $item['dir'];
		}

		$criteria = 'voornaam LIKE CONCAT(\'%\', ?, \'%\') AND status in (\'S_LID\', \'S_NOVIET\', \'S_GASTLID\')';

		$this->view = new LedenlijstTableResponse(
			$this->model->find($criteria, [$search['value']], null, join(", ", $orderBy), $length, $start)->fetchAll(),
			$this->model->count(),
			$this->model->count($criteria, [$search['value']])
		);
	}
}
