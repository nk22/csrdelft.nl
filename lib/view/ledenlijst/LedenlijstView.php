<?php

namespace CsrDelft\view\ledenlijst;

use CsrDelft\view\SmartyTemplateView;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 01/03/2018
 */
class LedenlijstView extends SmartyTemplateView {
	public function __construct() {
		parent::__construct(null, 'Ledenlijst');
	}

	function view() {
		$this->smarty->assign('ledenlijstTable', new LedenlijstTable());
		$this->smarty->display('ledenlijst/lijst.tpl');
	}
}
