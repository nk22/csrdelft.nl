<?php

namespace CsrDelft\view\formulier\invoervelden;

/**
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @date 30/03/2017
 *
 * Suggereert een aantal studies, doet verder geen controle op invoer.
 */
class StudieField extends TextField {

	public function __construct($name, $value, $description) {
		parent::__construct($name, $value, $description, 100);
		$tustudies = array('BK', 'CT', 'ET', 'IO', 'LST', 'LR', 'MT', 'MST', 'TA', 'TB', 'TI', 'TN', 'TW', 'WB');
		// de studies aan de TU, even prefixen met 'TU Delft - '
		$this->suggestions['TU Delft'] = array_map(create_function('$value', 'return "TU Delft - ".$value;'), $tustudies);
		$this->suggestions[] = array('INHolland', 'Haagse Hogeschool', 'EURotterdam', 'ULeiden');
	}

}
