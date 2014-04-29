<?php

require_once 'taken/model/MaaltijdenModel.class.php';
require_once 'taken/model/AanmeldingenModel.class.php';
require_once 'taken/model/MaaltijdRepetitiesModel.class.php';
require_once 'taken/view/BeheerMaaltijdenView.class.php';
require_once 'taken/view/forms/MaaltijdFormView.class.php';
require_once 'taken/view/forms/RepetitieMaaltijdenFormView.class.php';
require_once 'taken/view/forms/AanmeldingFormView.class.php';

/**
 * BeheerMaaltijdenController.class.php	| 	P.W.G. Brussee (brussee@live.nl)
 * 
 */
class BeheerMaaltijdenController extends AclController {

	public function __construct($query) {
		parent::__construct($query);
		if (!$this->isPosted()) {
			$this->acl = array(
				'beheer' => 'P_MAAL_MOD',
				'prullenbak' => 'P_MAAL_MOD',
				//'leegmaken' => 'P_MAAL_MOD',
				'archief' => 'P_MAAL_MOD',
				'fiscaal' => 'P_MAAL_MOD'
			);
		} else {
			$this->acl = array(
				'sluit' => 'P_MAAL_MOD',
				'open' => 'P_MAAL_MOD',
				'nieuw' => 'P_MAAL_MOD',
				'bewerk' => 'P_MAAL_MOD',
				'opslaan' => 'P_MAAL_MOD',
				'verwijder' => 'P_MAAL_MOD',
				'herstel' => 'P_MAAL_MOD',
				'anderaanmelden' => 'P_MAAL_MOD',
				'anderafmelden' => 'P_MAAL_MOD',
				'aanmaken' => 'P_MAAL_MOD'
			);
		}
		$this->action = 'beheer';
		if ($this->hasParam(2)) {
			$this->action = $this->getParam(2);
		}
		$mid = null;
		if ($this->hasParam(3)) {
			$mid = intval($this->getParam(3));
		}
		$this->performAction(array($mid));
	}

	public function beheer($mid = null) {
		$popup = null;
		if (is_int($mid) && $mid > 0) {
			$this->bewerk($mid);
			$popup = $this->getContent();
		}
		$this->view = new BeheerMaaltijdenView(MaaltijdenModel::getAlleMaaltijden(), false, false, MaaltijdRepetitiesModel::getAlleRepetities(), $this->getContent());
		$this->view = new CsrLayoutPage($this->getContent());
		$this->view->addStylesheet('js/autocomplete/jquery.autocomplete.css');
		$this->view->addStylesheet('taken.css');
		$this->view->addScript('autocomplete/jquery.autocomplete.min.js');
		$this->view->addScript('taken.js');
		$this->view->popup = $popup;
	}

	public function prullenbak() {
		$this->view = new BeheerMaaltijdenView(MaaltijdenModel::getVerwijderdeMaaltijden(), true);
		$this->view = new CsrLayoutPage($this->getContent());
		$this->view->addStylesheet('taken.css');
		$this->view->addScript('taken.js');
	}

	public function archief() {
		$this->view = new BeheerMaaltijdenView(MaaltijdenModel::getArchiefMaaltijdenTussen(), false, true);
		$this->view = new CsrLayoutPage($this->getContent());
		$this->view->addStylesheet('taken.css');
		$this->view->addScript('taken.js');
	}

	public function fiscaal($mid) {
		$maaltijd = MaaltijdenModel::getMaaltijd($mid, true);
		$aanmeldingen = AanmeldingenModel::getAanmeldingenVoorMaaltijd($maaltijd);
		require_once 'taken/view/MaaltijdLijstView.class.php';
		$this->view = new MaaltijdLijstView($maaltijd, $aanmeldingen, null, true);
	}

	public function sluit($mid) {
		$maaltijd = MaaltijdenModel::getMaaltijd($mid);
		MaaltijdenModel::sluitMaaltijd($maaltijd);
		$this->view = new BeheerMaaltijdenView($maaltijd);
	}

	public function open($mid) {
		$maaltijd = MaaltijdenModel::getMaaltijd($mid);
		MaaltijdenModel::openMaaltijd($maaltijd);
		$this->view = new BeheerMaaltijdenView($maaltijd);
	}

	public function nieuw() {
		if (isset($_POST['mrid'])) {
			$mrid = (int) filter_input(INPUT_POST, 'mrid', FILTER_SANITIZE_NUMBER_INT);
			$repetitie = MaaltijdRepetitiesModel::getRepetitie($mrid);
			// start at first occurence
			$datum = time();
			$shift = $repetitie->getDagVanDeWeek() - date('w', $datum) + 7;
			$shift %= 7;
			if ($shift > 0) {
				$datum = strtotime('+' . $shift . ' days', $datum);
			}
			$beginDatum = date('Y-m-d', $datum);
			if ($repetitie->getPeriodeInDagen() > 0) {
				$this->view = new RepetitieMaaltijdenFormView($repetitie, $beginDatum, $beginDatum); // fetches POST values itself
			} else {
				$this->view = new MaaltijdFormView(0, $repetitie->getMaaltijdRepetitieId(), $repetitie->getStandaardTitel(), intval($repetitie->getStandaardLimiet()), $beginDatum, $repetitie->getStandaardTijd(), $repetitie->getStandaardPrijs(), $repetitie->getAbonnementFilter()); // fetches POST values itself
			}
		} else {
			$maaltijd = new Maaltijd();
			$this->view = new MaaltijdFormView($maaltijd->getMaaltijdId(), $maaltijd->getMaaltijdRepetitieId(), $maaltijd->getTitel(), $maaltijd->getAanmeldLimiet(), $maaltijd->getDatum(), $maaltijd->getTijd(), $maaltijd->getPrijs(), $maaltijd->getAanmeldFilter()); // fetches POST values itself
		}
	}

	public function bewerk($mid) {
		$maaltijd = MaaltijdenModel::getMaaltijd($mid);
		$this->view = new MaaltijdFormView($maaltijd->getMaaltijdId(), $maaltijd->getMaaltijdRepetitieId(), $maaltijd->getTitel(), $maaltijd->getAanmeldLimiet(), $maaltijd->getDatum(), $maaltijd->getTijd(), $maaltijd->getPrijs(), $maaltijd->getAanmeldFilter()); // fetches POST values itself
	}

	public function opslaan($mid) {
		if ($mid > 0) {
			$this->bewerk($mid);
		} else {
			$this->view = new MaaltijdFormView($mid); // fetches POST values itself
		}
		if ($this->view->validate()) {
			$values = $this->view->getValues();
			$mrid = ($values['mlt_repetitie_id'] === '' ? null : intval($values['mlt_repetitie_id']));
			$maaltijd_aanmeldingen = MaaltijdenModel::saveMaaltijd($mid, $mrid, $values['titel'], $values['aanmeld_limiet'], $values['datum'], $values['tijd'], $values['prijs'], $values['aanmeld_filter']);
			$this->view = new BeheerMaaltijdenView($maaltijd_aanmeldingen[0]);
			if ($maaltijd_aanmeldingen[1] > 0) {
				setMelding($maaltijd_aanmeldingen[1] . ' aanmelding' . ($maaltijd_aanmeldingen[1] !== 1 ? 'en' : '') . ' verwijderd vanwege aanmeldrestrictie: ' . $maaltijd_aanmeldingen[0]->getAanmeldFilter(), 2);
			}
		}
	}

	public function verwijder($mid) {
		MaaltijdenModel::verwijderMaaltijd($mid);
		$this->view = new BeheerMaaltijdenView($mid);
	}

	public function herstel($mid) {
		$maaltijd = MaaltijdenModel::herstelMaaltijd($mid);
		$this->view = new BeheerMaaltijdenView($maaltijd->getMaaltijdId());
	}

	public function anderaanmelden($mid) {
		$form = new AanmeldingFormView($mid, true); // fetches POST values itself
		if ($form->validate()) {
			$values = $form->getValues();
			$aanmelding = AanmeldingenModel::aanmeldenVoorMaaltijd($mid, $values['voor_lid'], \LoginLid::instance()->getUid(), $values['aantal_gasten'], true);
			$this->view = new BeheerMaaltijdenView($aanmelding->getMaaltijd());
		} else {
			$this->view = $form;
		}
	}

	public function anderafmelden($mid) {
		$form = new AanmeldingFormView($mid, false); // fetches POST values itself
		if ($form->validate()) {
			$values = $form->getValues();
			$maaltijd = AanmeldingenModel::afmeldenDoorLid($mid, $values['voor_lid'], true);
			$this->view = new BeheerMaaltijdenView($maaltijd);
		} else {
			$this->view = $form;
		}
	}

	public function leegmaken() {
		$aantal = MaaltijdenModel::prullenbakLeegmaken();
		invokeRefresh(Instellingen::get('taken', 'url') . '/prullenbak', $aantal . ($aantal === 1 ? ' maaltijd' : ' maaltijden') . ' definitief verwijderd.', ($aantal === 0 ? 0 : 1));
	}

	// Repetitie-Maaltijden ############################################################

	public function aanmaken($mrid) {
		$repetitie = MaaltijdRepetitiesModel::getRepetitie($mrid);
		$form = new RepetitieMaaltijdenFormView($repetitie); // fetches POST values itself
		if ($form->validate()) {
			$values = $form->getValues();
			$maaltijden = MaaltijdenModel::maakRepetitieMaaltijden($repetitie, strtotime($values['begindatum']), strtotime($values['einddatum']));
			if (empty($maaltijden)) {
				throw new Exception('Geen nieuwe maaltijden aangemaakt');
			}
			$this->view = new BeheerMaaltijdenView($maaltijden);
		} else {
			$this->view = $form;
		}
	}

}

?>