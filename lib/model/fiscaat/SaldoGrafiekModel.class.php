<?php

namespace CsrDelft\model\fiscaat;

use CsrDelft\model\entity\fiscaat\Saldo;
use CsrDelft\model\security\LoginModel;

class SaldoGrafiekModel {
	const ORM = Saldo::class;

	/**
	 * @param string $uid
	 * @param int $timespan
	 * @return array|null
	 */
	public static function getDataPoints($uid, $timespan) {
		if (!static::magGrafiekZien($uid)) {
			return null;
		}
		$model = CiviSaldoModel::instance();
		$klant = $model->find('uid = ?', array($uid), null, null, 1)->fetch();
		if (!$klant) {
			return null;
		}
		$saldo = $klant->saldo;
		// Teken het huidige saldo
		$data = [['t' => date(\DateTime::RFC2822), 'y' => $saldo]];
		$model = CiviBestellingModel::instance();
		$bestellingen = $model->find(
			'uid = ? AND deleted = FALSE AND moment>(NOW() - INTERVAL ? DAY)',
			[$klant->uid, $timespan],
			null,
			'moment DESC'
		);

		foreach ($bestellingen as $bestelling) {
			$data[] = ['t' => date(\DateTime::RFC2822, strtotime($bestelling->moment)), 'y' => $saldo];
			$saldo += $bestelling->totaal;
		}

		if (!empty($data)) {
			$row = end($data);
			$time = date(\DateTime::RFC2822, strtotime($timespan - 1 . ' days 23 hours ago'));
			array_push($data, ["t" => $time, 'y' => $row['y']]);
		}

		return [
			"labels" => [$time, date(\DateTime::RFC2822)],
			"datasets" => [
				[
					'label' => 'Civisaldo',
					'steppedLine' => true,
					'borderWidth' => 2,
					'pointRadius' => 0,
					'hitRadius' => 2,
					'fill' => false,
					'borderColor' => 'green',
					'data' => array_reverse($data),
				],
			],
		];
	}

	/**
	 * @param string $uid
	 * @return bool
	 */
	public static function magGrafiekZien($uid) {
		//mogen we uberhaupt een grafiek zien?
		return LoginModel::getUid() === $uid OR LoginModel::mag(P_LEDEN_MOD . ',commissie:SocCie,commissie:MaalCie');
	}
}
