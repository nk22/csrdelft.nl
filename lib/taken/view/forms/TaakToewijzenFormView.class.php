<?php
namespace Taken\CRV;

require_once 'formulier.class.php';

/**
 * TaakToewijzenFormView.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 *
 * Formulier om een corveetaak toe te wijzen aan een lid.
 * 
 */
class TaakToewijzenFormView extends \SimpleHtml {

	private $_form;
	private $_taak;
	private $_suggesties;
	private $_jong;
	
	public function __construct(CorveeTaak $taak, array $suggesties) {
		$this->_taak = $taak;
		$this->_suggesties = $suggesties;
		$this->_jong = (int) \Lichting::getJongsteLichting();
		
		$formFields[] = new \LidField('lid_id', $taak->getLidId(), 'Naam of lidnummer', 'leden');
		
		$this->_form = new \Formulier('taken-taak-toewijzen-form', $GLOBALS['taken_module'] .'/toewijzen/'. $this->_taak->getTaakId(), $formFields);
	}
	
	public function getTitel() {
		return 'Taak toewijzen aan lid';
	}
	
	public function getLidnaam($uid) {
		return \LidCache::getLid($uid)->getNaamLink($GLOBALS['weergave_ledennamen_beheer'], 'link');
	}
	
	public function getIsJongsteLichting($uid) {
		return ($this->_jong === (int) \LidCache::getLid($uid)->getLichting());
	}
	
	public function view() {
		$smarty = new \Smarty_csr();
		$smarty->assign('melding', $this->getMelding());
		$smarty->assign('kop', $this->getTitel());
		$this->_form->cssClass .= ' popup';
		
		$smarty->assign('taak', $this->_taak);
		$smarty->assign('suggesties', $this->_suggesties);
		$smarty->assign('this', $this);
		$lijst = $smarty->fetch('taken/corveetaak/suggesties_lijst.tpl');
		$formFields[] = new \HTMLComment($lijst);
		
		$formFields['voorkeur'] = new \VinkField('voorkeur', true, 'Met voorkeur');
		
		if ($this->_taak->getCorveeRepetitieId() !== null) {
			$repetitie = CorveeRepetitiesModel::getRepetitie($this->_taak->getCorveeRepetitieId());
			
			if ($repetitie->getIsVoorkeurbaar()) {
				$formFields['voorkeur']->setOnChangeScript("taken_toggle_suggestie('geenvoorkeur');");
				$formFields[] = new \HTMLComment('<script type="text/javascript">$(document).ready(function(){taken_toggle_suggestie(\'geenvoorkeur\');});</script>');
			}
			else {
				$formFields['voorkeur']->value = false;
				$formFields['voorkeur']->disabled = true;
				$formFields['voorkeur']->title = 'Deze corveerepetitie is niet voorkeurbaar.';
			}
		}
		else {
			$formFields['voorkeur']->value = false;
			$formFields['voorkeur']->disabled = true;
			$formFields['voorkeur']->title = 'Dit is geen periodieke taak dus zijn er geen voorkeuren.';
		}
		
		$formFields['recent'] = new \VinkField('recent', true, 'Niet recent gecorveed');
		$formFields['recent']->setOnChangeScript("taken_toggle_suggestie('recent');");
		$formFields[] = new \HTMLComment('<script type="text/javascript">$(document).ready(function(){taken_toggle_suggestie(\'recent\');});</script>');
		
		$formFields['jongste'] = new \VinkField('jongste', false, 'Geen novieten/sjaars');
		$formFields['jongste']->setOnChangeScript("taken_toggle_suggestie('jongste');");
		
		$this->_form->addFields($formFields);
		
		$smarty->assign('form', $this->_form);
		$smarty->display('taken/popup_form.tpl');
	}
	
	public function validate() {
		if (!is_int($this->_taak->getTaakId()) || $this->_taak->getTaakId() <= 0) {
			return false;
		}
		return $this->_form->valid(null);
	}
	
	public function getValues() {
		return $this->_form->getValues(); // escapes HTML
	}
}

?>