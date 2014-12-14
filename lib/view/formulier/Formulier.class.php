<?php

require_once 'view/View.interface.php';
require_once 'view/Validator.interface.php';
require_once 'view/formulier/FormElement.abstract.php';
require_once 'view/formulier/InvoerVelden.class.php';
require_once 'view/formulier/GetalVelden.class.php';
require_once 'view/formulier/KeuzeVelden.class.php';
require_once 'view/formulier/UploadVelden.class.php';
require_once 'view/formulier/FormKnoppen.class.php';

/**
 * Formulier.class.php
 * 
 * @author Jan Pieter Waagmeester <jieter@jpwaag.com>
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * 
 * Voorbeeld:
 *
 * $form = new Formulier(
 * 		$model,
 * 		'formulier-ID',
 * 		'/example.php',
 * 		array(
 * 			InputField('naam', $value, 'Naam'),
 * 			SubmitResetCancel()
 * 		);
 * 
 * Alle dingen die we in de field-array van een Formulier stoppen
 * moeten een uitbreiding zijn van FormElement.
 *
 * @see FormElement
 */
class Formulier implements View, Validator {

	protected $model;
	private $formId;
	protected $action = null;
	public $post = true;
	private $enctype = 'multipart/form-data';
	/**
	 * Fields must be added via addFields()
	 * or insertElementBefore() methods,
	 * and retrieved with getFields() method.
	 * 
	 * @var FormElement[]
	 */
	private $fields = array();
	public $css_classes = array();
	protected $javascript = '';
	public $titel;

	public function __construct($model, $formId, $action, $titel = false) {
		$this->model = $model;
		$this->formId = $formId;
		$this->action = $action;
		$this->titel = $titel;
		$this->css_classes[] = 'Formulier';
	}

	public function getFormId() {
		return $this->formId;
	}

	public function getTitel() {
		return $this->titel;
	}

	public function getModel() {
		return $this->model;
	}

	public function getBreadcrumbs() {
		return null;
	}

	private function loadProperty(InputField $field) {
		$fieldName = $field->getName();
		if ($this->model instanceof PersistentEntity AND property_exists($this->model, $fieldName)) {
			$this->model->$fieldName = $field->getValue();
		}
	}

	public function generateFields() {
		if (!$this->model instanceof PersistentEntity) {
			return;
		}
		$fields = array();
		foreach ($this->model->getAttributes() as $fieldName) {
			$definition = $this->model->getAttributeDefinition($fieldName);
			if (!isset($definition[1]) OR $definition[1] === false) {
				$class = 'Required';
			} else {
				$class = '';
			}
			$desc = ucfirst(str_replace('_', ' ', $fieldName));
			switch ($definition[0]) {
				case T::String: $class .= 'TextField';
					break;
				case T::Boolean: $class .= 'VinkField';
					break;
				case T::Integer: $class .= 'IntField';
					break;
				case T::Float: $class .= 'DecimalField';
					break;
				case T::DateTime: $class .= 'DatumField';
					break;
				case T::Text: $class .= 'TextareaField';
					break;
				case T::LongText: $class .= 'TextareaField';
					break;
				case T::Enumeration: $class .= 'SelectField';
					break;
				case T::UID: $class .='LidField';
					break;
			}
			if ($definition[0] == T::Enumeration) {
				$options = array();
				foreach ($definition[2]::getTypeOptions() as $option) {
					$options[$option] = $option;
				}
				$fields[$fieldName] = new $class($fieldName, $this->model->$fieldName, $desc, $options);
			} else {
				$fields[$fieldName] = new $class($fieldName, $this->model->$fieldName, $desc);
			}
		}
		foreach ($this->model->getPrimaryKey() as $fieldName) {
			$fields[$fieldName]->readonly = true;
			$fields[$fieldName]->hidden = true;
		}
		$this->addFields($fields);
	}

	public function getFields() {
		return $this->fields;
	}

	public function hasFields() {
		return !empty($this->fields);
	}

	/**
	 * Zoekt een InputField met exact de gegeven naam.
	 *
	 * @param string $fieldName
	 * @return InputField OR false if not found
	 */
	public function findByName($fieldName) {
		foreach ($this->fields as $field) {
			if (($field instanceof InputField OR $field instanceof FileField) AND $field->getName() === $fieldName) {
				return $field;
			}
		}
		return false;
	}

	public function addFields(array $fields) {
		foreach ($fields as $field) {
			if ($field instanceof InputField) {
				$this->loadProperty($field);
			}
		}
		$this->fields = array_merge($this->fields, $fields);
	}

	public function insertAtPos($pos, FormElement $field) {
		if ($field instanceof InputField) {
			$this->loadProperty($field);
		}
		array_splice($this->fields, $pos, 0, array($field));
	}

	public function removeField(FormElement $field) {
		$pos = array_search($field, $this->fields);
		unset($this->fields[$pos]);
	}

	/**
	 * Is het formulier *helemaal* gePOST?
	 */
	public function isPosted() {
		foreach ($this->fields as $field) {
			if ($field instanceof InputField AND ! $field->isPosted()) {
				//setMelding($field->getName() . ' is niet gepost', 2); //DEBUG
				return false;
			}
		}
		return true;
	}

	/**
	 * Alle valideer-functies kunnen het model gebruiken bij het valideren
	 * dat meegegeven is bij de constructie van het InputField.
	 */
	public function validate() {
		if (!$this->isPosted()) {
			return false;
		}
		$valid = true;
		foreach ($this->fields as $field) {
			if ($field instanceof Validator AND ! $field->validate()) { // geen comments bijv.
				$valid = false; // niet gelijk retourneren om voor alle velden eventueel errors te zetten
			}
		}
		if (!$valid) {
			$this->css_classes[] = 'metFouten';
		}
		return $valid;
	}

	/**
	 * Geeft waardes van de formuliervelden terug.
	 */
	public function getValues() {
		$values = array();
		foreach ($this->fields as $field) {
			if ($field instanceof InputField) {
				$values[$field->getName()] = $field->getValue();
			}
		}
		return $values;
	}

	/**
	 * Geeft errors van de formuliervelden terug.
	 */
	public function getError() {
		$errors = array();
		foreach ($this->fields as $field) {
			if ($field instanceof Validator) {
				$fieldName = $field->getName();
				if ($field->getError() !== '') {
					$errors[$fieldName] = $field->getError();
				}
			}
		}
		if (empty($errors)) {
			return null;
		}
		return $errors;
	}

	protected function getJavascript() {
		foreach ($this->fields as $field) {
			$this->javascript .= $field->getJavascript();
		}
		return $this->javascript;
	}

	protected function getFormTag() {
		return '<form enctype="' . $this->enctype . '" action="' . $this->action . '" id="' . $this->getFormId() . '" class="' . implode(' ', $this->css_classes) . '" method="' . ($this->post ? 'post' : 'get') . '">';
	}

	protected function getScriptTag() {
		return <<<JS
<script type="text/javascript">
$(document).ready(function () {
	var form = document.getElementById('{$this->getFormId()}');
	{$this->getJavascript()}
});
</script>
JS;
	}

	/**
	 * Toont het formulier en javascript van alle fields.
	 */
	public function view() {
		echo getMelding();
		echo $this->getFormTag();
		if ($this->getTitel()) {
			echo '<h1 class="Titel">' . $this->getTitel() . '</h1>';
		}
		//debugprint($this->getError()); //DEBUG
		foreach ($this->fields as $field) {
			$field->view();
		}
		echo $this->getScriptTag();
		echo '</form>';
	}

}

/**
 * Formulier as modal content.
 */
class ModalForm extends Formulier {

	public function view() {
		$this->css_classes[] = 'ModalForm';
		echo '<div id="modal" class="modal-content outer-shadow dragobject" tabindex="-1" style="display: block;">';
		parent::view();
		printDebug();
		echo '</div>';
	}

}

/**
 * InlineForm with single InputField and SubmitResetCancel.
 */
class InlineForm extends Formulier {

	public function __construct($model, $formId, $action, InputField $field, $buttons = false, $label = false) {
		parent::__construct($model, uniqid($formId), $action);
		$this->css_classes[] = 'InlineForm';
		$fields = array();

		if (!isset($field->title)) {
			$field->title = $field->description;
		}
		$fields['input'] = $field;

		if ($buttons) {
			$fields['btn'] = new FormDefaultKnoppen(null, false, true, $label, true);
		} else {
			$fields['input']->enter_submit = true;
			$fields['input']->escape_cancel = true;
		}

		$this->addFields($fields);
	}

	public function getHtml() {
		$fields = $this->getFields();
		$html = '<div id="wrapper_' . $this->getFormId() . '" class="InlineForm">';
		$html .= '<div id="toggle_' . $this->getFormId() . '" class="InlineFormToggle">' . $fields['input']->getValue() . '</div>';
		$html .= $this->getFormTag();
		$html .= $fields['input']->getHtml();
		if (isset($fields['btn'])) {
			$html .= $fields['btn']->getHtml();
		}
		$html .= $this->getScriptTag();
		return $html . '</form></div>';
	}

	public function view() {
		echo $this->getHtml();
	}

	public function getValue() {
		$fields = $this->getFields();
		return $fields['input']->getValue();
	}

}