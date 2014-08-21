<?php

require_once 'MVC/view/CsrSmarty.class.php';

/**
 * SmartyTemplateView.abstract.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Uses the template engine Smarty to compile and
 * display the template.
 * 
 */
abstract class SmartyTemplateView implements View {

	/**
	 * Data model
	 * @var PersistentEntity
	 */
	protected $model;
	/**
	 * Titel
	 * @var string
	 */
	protected $titel;
	/**
	 * Template engine
	 * @var CsrSmarty
	 */
	protected $smarty;

	public function __construct($model, $titel = '') {
		$this->model = $model;
		$this->titel = $titel;
		$this->smarty = new CsrSmarty();
		$this->smarty->assignByRef('view', $this);
	}

	public function getModel() {
		return $this->model;
	}

	public function getTitel() {
		return $this->titel;
	}

}
