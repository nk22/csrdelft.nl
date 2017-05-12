<?php
use CsrDelft\Orm\Entity\PersistentEntity;
use CsrDelft\Orm\Entity\T;

/**
 * CiviSaldo.class.php
 *
 * Bewaart het saldo van een lid, uid is een verwijzing naar account.
 *
 * Uid kan ook een niet bestaande uid bevatten voor profielen die niet kunnen inloggen en alleen via SocCie kunnen
 * afrekenen.
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @date 07/04/2017
 */
class CiviSaldo extends PersistentEntity {
	public $id;
	public $uid;
	public $naam;
	public $saldo;
	public $laatst_veranderd;
	public $deleted = false;

	protected static $persistent_attributes = [
		'id' => array(T::Integer, false, 'auto_increment'),
		'uid' => array(T::UID),
		'naam' => array(T::Text, true),
		'saldo' => array(T::Integer),
		'laatst_veranderd' => array(T::Timestamp),
		'deleted' => array(T::Boolean),
	];
	protected static $table_name = 'CiviSaldo';
	protected static $primary_key = array('id');
}