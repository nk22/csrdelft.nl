<?php

namespace CsrDelft\view\bbcode\tag;

use CsrDelft\model\SavedQuery;
use CsrDelft\view\bbcode\CsrBbException;
use CsrDelft\view\SavedQueryContent;

/**
 * Deze methode kan resultaten van query's die in de database staan printen in een
 * tabelletje.
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 27/03/2019
 * @example [query=1]
 * @example [query]1[/query]
 */
class BbQuery extends BbTag {

	public function getTagName() {
		return 'query';
	}

	public function parseLight($arguments = []) {
		$queryID = (int)$this->getArgument($arguments);
		$this->assertId($queryID);
		$sqc = new SavedQueryContent(new SavedQuery($queryID));
		$url = '/tools/query?id=' . urlencode($queryID);
		return $this->lightLinkBlock('query', $url, $sqc->getModel()->getBeschrijving(), $sqc->getModel()->count() . ' regels');
	}

	/**
	 * @param int $queryID
	 */
	private function assertId(int $queryID) {
		if ($queryID == 0) {
			throw new CsrBbException('[query] Geen geldig query-id opgegeven');
		}
	}

	public function parse($arguments = []) {
		$queryID = (int)$this->getArgument($arguments);
		$this->assertId($queryID);
		$sqc = new SavedQueryContent(new SavedQuery($queryID));
		return $sqc->render_queryResult();
	}
}
