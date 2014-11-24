<?php

require_once 'MVC/model/ChangeLogModel.class.php';

/**
 * StatistiekModel.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Statistiek op basis van status change log.
 * 
 */
class HappieStatistiekModel extends ChangeLogModel {

	const orm = 'HappieStatusLog';

	protected static $instance;

	protected function __construct() {
		parent::__construct('happie/');
	}

}