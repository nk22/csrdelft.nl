<?php

namespace CsrDelft\view\bbcode\tag;

use CsrDelft\model\LidInstellingenModel;
use CsrDelft\view\Icon;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 27/03/2019
 * @example [neuzen]2o13[/neuzen]
 */
class BbNeuzen extends BbTag {
	public function getTagName() {
		return 'neuzen';
	}

	public function parse($arguments = []) {
		$content = $this->getContent();

		if (LidInstellingenModel::get('layout', 'neuzen') != 'nee') {
			$neus = Icon::getTag('bullet_red', null, null, 'neus2013', 'o');
			$content = str_replace('o', $neus, $content);
		}

		return $content;
	}

	public function parseLight($arguments = []) {
		return $this->getContent();
	}
}
