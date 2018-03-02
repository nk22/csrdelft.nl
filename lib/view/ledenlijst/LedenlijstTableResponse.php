<?php

namespace CsrDelft\view\ledenlijst;

use CsrDelft\model\entity\LidStatus;
use CsrDelft\model\entity\Profiel;
use CsrDelft\model\ProfielModel;
use CsrDelft\model\security\LoginModel;
use CsrDelft\view\formulier\datatable\DataTableServerSideResponse;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 01/03/2018
 */
class LedenlijstTableResponse extends DataTableServerSideResponse {
	/**
	 * Constants.
	 */
	const EMPTY_FIELD = '-';

	/**
	 * @param Profiel $profiel
	 * @return string
	 * @throws \Exception
	 */
	public function getJson($profiel) {
		if (LoginModel::mag('P_LEDEN_MOD')) {
			$modFields = [
				'studienr' => $profiel->studienr,
				'muziek' => $profiel->muziek,
				'ontvangtcontactueel' => $profiel->ontvangtcontactueel,
				'kerk' => $profiel->kerk,
				'lidafdatum' => $profiel->lidafdatum,
				'echtgenoot' => $this->formatEchtgenoot($profiel),
				'adresseringechtpaar' => $profiel->adresseringechtpaar,
				'land' => $profiel->land,
				'bankrekening' => $profiel->bankrekening,
				'machtiging' => $profiel->machtiging,
			];
		} else {
			$modFields = [];
		}

		$fields = [
			'pasfoto' => $profiel->getPasfotoTag(),
			'uid' => $profiel->uid,
			'naam' => [
				'display' => $profiel->getLink('volledig'),
				'export' => $profiel->getNaam('volledig'),
			],
			'voorletters' => $profiel->voorletters,
			'voornaam' => $profiel->voornaam,
			'tussenvoegsel' => $profiel->tussenvoegsel,
			'achternaam' => $profiel->achternaam,
			'nickname' => $profiel->nickname,
			'duckname' => $profiel->duckname,
			'geslacht' => $profiel->geslacht,
			'email' => $this->formatEmail($profiel),
			'adres' => htmlspecialchars($profiel->getAdres()),
			'telefoon' => $profiel->telefoon,
			'mobiel' => $profiel->mobiel,
			'linkedin' => $this->formatUrl($profiel->linkedin),
			'website' => $this->formatUrl($profiel->website),
			'studie' => $profiel->studie,
			'status' => LidStatus::getDescription($profiel->status),
			'gebdatum' => $profiel->gebdatum,
			'beroep' => $profiel->beroep,
			'verticale' => htmlspecialchars($profiel->getVerticale()->naam),
			'moot' => $profiel->moot,
			'lidjaar' => $profiel->lidjaar,
			'kring' => $this->formatKring($profiel),
			'patroon' => $this->formatPatroon($profiel),
			'woonoord' => $this->formatWoonoord($profiel),
			'bankrekening' => $profiel->bankrekening,
			'eetwens' => $profiel->eetwens,
		];

		return parent::getJson(array_merge($fields, $modFields));
	}

	/**
	 * @param Profiel $profiel
	 * @return string
	 */
	public function formatPatroon($profiel) {
		$patroon = ProfielModel::get($profiel->patroon);
		if ($patroon) {
			return $patroon->getLink('volledig');
		} else {
			return self::EMPTY_FIELD;
		}
	}

	/**
	 * @param Profiel $profiel
	 * @return string
	 */
	public function formatKring($profiel): string {
		$kring = $profiel->getKring();
		if ($kring) {
			return '<a href="' . $kring->getUrl() . '">' . $kring->naam . '</a>';
		} else {
			return self::EMPTY_FIELD;
		}
	}

	/**
	 * @param Profiel $profiel
	 * @return string
	 */
	public function formatEmail($profiel): string {
		$email = $profiel->getPrimaryEmail();
		if ($email) {
			return '<a href="mailto:' . $email . '">' . $email . '</a>';
		} else {
			return self::EMPTY_FIELD;
		}
	}

	/**
	 * @param string url
	 * @return string
	 */
	public function formatUrl($url): string {
		return '<a target="_blank" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
	}

	/**
	 * @param Profiel $profiel
	 * @return string
	 */
	public function formatWoonoord($profiel) {
		$woonoord = $profiel->getWoonoord();
		if ($woonoord) {
			return $woonoord->naam;
		} else {
			return self::EMPTY_FIELD;
		}
	}

	/**
	 * @param Profiel $profiel
	 * @return string
	 */
	public function formatEchtgenoot($profiel) {
		$echtgenoot = ProfielModel::get($profiel->echtgenoot);
		if ($echtgenoot) {
			return $echtgenoot->getLink('volledig');
		} else {
			return self::EMPTY_FIELD;
		}
	}
}
