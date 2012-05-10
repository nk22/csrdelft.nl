<?php
/*
 * boek.class.php	| 	Gerrit Uitslag
 *
 * boeken
 *
 */
require_once 'rubriek.class.php';
require_once 'formulier.class.php';
require_once 'beschrijving.class.php';

class Boek{

	protected $id=0;			//boekId
	protected $titel;			//String
	protected $auteur;			//String Auteur
	protected $rubriek=null;	//Rubriek object
	protected $uitgavejaar;
	protected $uitgeverij;
	protected $paginas;
	protected $taal='Nederlands';
	protected $isbn;
	protected $code;

	protected $status;				//'beschikbaar'/'teruggeven'/'geen'
	protected $biebboek = 'nee';	//'ja'/'nee'
	protected $error = '';

	protected $exemplaren = null;	// array

	public function __construct($init){
		$this->load($init);
	}
	/*
	 * Laad object Boek afhankelijk van parameters van de constructor
	 * 
	 * @param	$array met eigenschappen of integer boekId (niet 0)
	 * @return	void
	 */
	private function load($init=0){
		if(is_array($init)){
			$this->array2properties($init);
		}else{
			$this->id=(int)$init;
			if($this->getId()!=0){
				$db=MySql::instance();
				$query="
					SELECT id, titel, auteur, categorie_id, uitgavejaar, uitgeverij, paginas, taal, isbn, code,
					IF((
						SELECT count( * )
						FROM biebexemplaar e2
						WHERE e2.boek_id = biebboek.id AND e2.status='beschikbaar'
						) > 0, 
					'beschikbaar', 
						IF((
							SELECT count( * )
							FROM biebexemplaar e2
							WHERE e2.boek_id = biebboek.id AND e2.status='teruggegeven'
							) > 0,
						'teruggegeven',
						'geen'
						)
					) AS status
					FROM biebboek
					WHERE Id=".$this->getId().";";
				$boek=$db->getRow($query);
				if(is_array($boek)){
					$this->array2properties($boek);
				}else{
					throw new Exception('load() mislukt. Bestaat het boek wel? '.mysql_error());
				}
			}else{
				throw new Exception('load() mislukt. Boekid = 0');
			}
		}
	}

	/*
	 * Eigenschappen in object stoppen
	 * @param	array met eigenschappen, setValue() moet de keys kennen
	 * @return	void
	 */ 
	private function array2properties($properties){
		foreach ($properties as $prop => $value){
			$this->setValue($prop, $value);
		}
	}

	public function getId(){			return $this->id;}
	public function getTitel(){			return $this->titel;}
	public function getUitgavejaar(){	return $this->uitgavejaar;}
	public function getUitgeverij(){	return $this->uitgeverij;}
	public function getPaginas(){		return $this->paginas;}
	public function getTaal(){			return $this->taal;}
	public function getISBN(){			return $this->isbn;}
	public function getCode(){			return $this->code;}
	public function getAuteur(){		return $this->auteur;}
	public function getRubriek(){		return $this->rubriek;}

	public function getStatus(){		return $this->status;}
	public function getError(){			return $this->error;}
	//url naar dit boek
	public function getUrl(){			return CSR_ROOT.'communicatie/bibliotheek/boek/'.$this->getId();}

	/* 
	 * controleert rechten voor wijderactie
	 * @return	bool
	 * 		boek mag alleen door admins verwijdert worden
	 */
	static public function magVerwijderen(){
		return Loginlid::instance()->hasPermission('groep:BASFCie,P_BIEB_MOD,P_ADMIN');
	}
	/* 
	 * controleert rechten voor bewerkactie
	 * @return	bool
	 * 		boek mag alleen door admins of door eigenaar v.e. exemplaar bewerkt worden
	 */
	public function magBewerken(){
		if($this->magVerwijderen() OR Loginlid::instance()->hasPermission('P_BIEB_EDIT')){ return true;}

		return $this->isEigenaar();
	}
	/*
	 * Iedereen met extra rechten en zij met BIEB_READ mogen
	 */
	public function magBekijken(){
		return Loginlid::instance()->hasPermission('P_BIEB_READ') OR $this->magBewerken();
	}

	/*
	 * Controleert of ingelogd eigenaar is van boek/exemplaar
	 *  - Basfcieleden zijn eigenaar van boeken van de bibliotheek
	 *
	 * @param geen of $exemplaarid integer
	 * @return	true
	 * 				of ingelogd eigenaar is v.e. exemplaar van het boek 
	 * 				of van het specifieke exemplaar als exemplaarid is gegeven.
	 * 			false
	 * 				geen geen resultaat of niet de eigenaar
	 */
	public function isEigenaar($exemplaarid=null){
		$db=MySql::instance();
		if($exemplaarid==null){
			$where="WHERE boek_id =".(int)$this->getId();
		}else{
			$where="WHERE id =".(int)$exemplaarid;
		}
		$qEigenaar="
			SELECT eigenaar_uid
			FROM  `biebexemplaar` 
			".$where.";";
		$result=$db->query($qEigenaar);

		$return = false;
		if($db->numRows($result)>0){
			while($eigenaar=$db->next($result)){
				if($eigenaar['eigenaar_uid']==Loginlid::instance()->getUid()){
					$return = true;
				}elseif($eigenaar['eigenaar_uid']=='x222' AND $this->isBASFCie()){
					$return = true;
				}
			}
		}else{
			$this->error.= mysql_error();
		}
		return $return;
	}

	public function isBASFCie(){
		return Loginlid::instance()->hasPermission('groep:BASFCie');
	}
	/*
	 * Check of ingelogd lener is van exemplaar
	 * 
	 * @param $exemplaarid 
	 * @return bool
	 */
	public function isLener($exemplaarid){
		$db=MySql::instance();
		$qLener="
			SELECT uitgeleend_uid 
			FROM `biebexemplaar`
			WHERE id=".(int)$exemplaarid.";";
		$result=$db->query($qLener);
		if($db->numRows($result)>0){
			$lener=$db->next($result);
			return $lener['uitgeleend_uid']==Loginlid::instance()->getUid();
		}else{
			$this->error.= mysql_error();
			return false;
		}
	}

	/*
	 * Verwijder een boek
	 */
	public function delete(){
		if($this->getId()==0){
			$this->error.='Kan geen lege boek met id=0 wegkekken. Boek::delete()';
			return false;
		}
		$db=MySql::instance();
		$qDeleteBeschrijvingen="DELETE FROM biebbeschrijving WHERE boek_id=".$this->getId().";";
		$qDeleteExemplaren="DELETE FROM biebexemplaar WHERE boek_id=".$this->getId()." LIMIT 1;";
		$qDeleteBoek="DELETE FROM biebboek WHERE id=".$this->getId()." LIMIT 1;";
		if($db->query($qDeleteBeschrijvingen) AND $db->query($qDeleteExemplaren) AND $db->query($qDeleteBoek)){
			return true;
		}else{
			$this->error.='Fout bij verwijderen. Boek::delete() '.mysql_error();
			return false;
		}
	}


	/**************
	 * Exemplaren *
	 **************

	/* 
	 * laad exemplaren van dit boek in Boek
	 * @return void
	 */
	public function loadExemplaren(){
		$db=MySql::instance();
		$query="
			SELECT id, eigenaar_uid, opmerking, uitgeleend_uid, toegevoegd, status, uitleendatum
			FROM biebexemplaar
			WHERE boek_id=".(int)$this->getId()."
			ORDER BY toegevoegd;";
		$result=$db->query($query);
		
		if($db->numRows($result)>0){
			while($exemplaar=$db->next($result)){
				$this->exemplaren[$exemplaar['id']]=$exemplaar;
			}
		}else{
			$this->error .= mysql_error();
			return false;
		}
		return $db->numRows($result);
	}
	/*
	 * Geeft alle exemplaren van dit boek
	 * @return array met exemplaren
	 */
	public function getExemplaren(){
		if($this->exemplaren===null){
			$this->loadExemplaren();
		}
		return $this->exemplaren; 
	}
	/*
	 * Aantal exemplaren
	 * @return int
	 */
	public function countExemplaren(){
		if($this->exemplaren===null){
			$this->loadExemplaren();
		}
		return count($this->exemplaren);
	}
	/*
	 * Geeft status van exemplaar
	 * 
	 * @param $exemplaarid int 
	 * @return 	statuswaarde uit db van $exemplaarid
	 * 			of anders lege string
	 */
	public function getStatusExemplaar($exemplaarid){
		$db=MySql::instance();
		$query="
			SELECT id, status
			FROM biebexemplaar
			WHERE id=".(int)$exemplaarid.";";
		$result=$db->query($query);
		if($db->numRows($result)>0){
			$exemplaar=$db->next($result);
			return $exemplaar['status'];
		}else{
			$this->error.= mysql_error();
			return '';
		}
	}

	/* 
	 * voeg exemplaar toe
	 * @param $eigenaar
	 * @return  true geslaagd
	 * 			false 	mislukt
	 * 					$eigenaar is ongeldig uid
	 */
	public function addExemplaar($eigenaar){
		if(!Lid::isValidUid($eigenaar)){
			return false;
		}
		$db=MySql::instance();
		$qSave="
			INSERT INTO biebexemplaar (
				boek_id, eigenaar_uid, toegevoegd, status
			) VALUES (
				".(int)$this->getId().",
				'".$db->escape($eigenaar)."',
				'".getDateTime()."',
				'beschikbaar'
			);";
		if($db->query($qSave)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::addExemplaar()';
		return false;
	}
	/*
	 * verwijder exemplaar
	 * @param $id exemplaarid
	 * @return 	true geslaagd
	 * 			false mislukt
	 */
	public function verwijderExemplaar($id){
		$db=MySql::instance();
		$qDeleteExemplaar="DELETE FROM biebexemplaar WHERE id=".(int)$id." LIMIT 1;";
		return $db->query($qDeleteExemplaar);
	}

	/******************************************************************************
	 * methodes voor gewone formulieren *
	 ******************************************************************************/

	/*
	 * Definiëren van de velden van het nieuw boek formulier
	 * Als we ze hier toevoegen, dan verschijnen ze ook automagisch in het boekaddding,
	 * en ze worden gecontroleerd met de eigen valideerfuncties.
	 */
	protected function getCommonFields($naamtitelveld='Titel'){
		$fields['titel']=new TitelField('titel', $this->getTitel(), $naamtitelveld, 200, 'Titel ontbreekt!');
		$fields['auteur']=new InputField('auteur', $this->getAuteur(), 'Auteur', 100);
		$fields['auteur']->setRemoteSuggestionsSource("/communicatie/bibliotheek/autocomplete/auteur");
		$fields['auteur']->setPlaceholder('Achternaam, Voornaam V.L. van de');
		$fields['paginas']=new IntField('paginas', $this->getPaginas() , "Pagina's", 10000, 0);
		$fields['taal']=new InputField('taal', $this->getTaal(), 'Taal', 25);
		$fields['taal']->setRemoteSuggestionsSource("/communicatie/bibliotheek/autocomplete/taal");
		$fields['isbn']=new InputField('isbn', $this->getISBN(), 'ISBN',15);
		$fields['isbn']->setPlaceholder('Uniek nummer');
		$fields['uitgeverij']=new InputField('uitgeverij', $this->getUitgeverij(), 'Uitgeverij', 100);
		$fields['uitgeverij']->setRemoteSuggestionsSource("/communicatie/bibliotheek/autocomplete/uitgeverij");
		$fields['uitgavejaar']=new IntField('uitgavejaar', $this->getUitgavejaar(), 'Uitgavejaar', 2100, 0);
		$fields['rubriek']=new SelectField('rubriek', $this->getRubriek()->getId(), 'Rubriek', Rubriek::getAllRubrieken($samenvoegen=true,$short=true));
		$fields['code']=new InputField('code', $this->getCode(), 'Biebcode', 7);
		return $fields;
	}

	/*
	 * Geeft formulier terug
	 */
	public function getFormulier(){
		return $this->formulier;
	}

	/**
	 * Controleren of alle velden van formulier correct zijn
	 */
	public function validFormulier(){
		return $this->getFormulier()->valid('');
	}

	/*
	 * Plaats waardes van formulier in object
	 */
	public function setValuesFromFormulier(){
		//object Boek vullen
		foreach($this->getFormulier()->getFields() as $field){
			if($field instanceof FormField){
				$this->setValue($field->getName(), $field->getValue());
			}
		}
	}
	/* 
	 * set gegeven waardes in Boek
	 * @param	$key moet bekend zijn, anders exception
	 * @return	void
	 */
	public function setValue($key, $value){
		//$key voor leners en opmerkingen eerst opsplitsen
		if(substr($key,0,6)=='lener_'){
			$exemplaarid = substr($key,6);
			$key='lener';
		}elseif(substr($key,0,10)=='opmerking_'){
			$exemplaarid = substr($key,10);
			$key='opmerking';
		}

		switch ($key) {
			//integers
			case 'id':
			case 'uitgavejaar':
			case 'paginas':
				$this->$key=(int)trim($value);
				break;
			//strings
			case 'categorie':
				$this->rubriek = new Rubriek(explode(' - ' , $value));
				break;
			case 'categorie_id':
			case 'rubriek':
				try{
					$this->rubriek = new Rubriek($value);
				}catch(Exception $e){
					throw new Exception($e->getMessage().' Boek::setValue "'.$key.'"');
				}
				break;
			case 'titel':
			case 'uitgeverij':
			case 'taal':
			case 'code':
			case 'isbn':
			case 'status':
			case 'auteur':
				$this->$key=trim($value);
				break;
			case 'beschrijving':
				$this->getEditBeschrijving()->setTekst($value);
				break;
			case 'biebboek':
				$this->biebboek=$value;
				break;
			case 'lener':
				$this->exemplaren[$exemplaarid]['uitgeleend_uid']=$value;
				break;
			case 'opmerking':
				$this->exemplaren[$exemplaarid]['opmerking']=$value;
				break;
			default:
				throw new Exception('Veld ['.$key.'] is niet toegestaan Boek::setValue()');
		}
	}

}

class NieuwBoek extends Boek {

	protected $formulier;			// Form objecten voor nieuwboekformulier

	public function __construct(){
		$this->id=0;
		//zetten we de defaultwaarden voor het nieuwe boek.
		$this->rubriek = new Rubriek(108);
		if($this->isBASFCie()){
			$this->biebboek = 'ja';
		}
		$this->createBoekformulier();
	}

	public function createBoekformulier(){
		//Iedereen die bieb mag bekijken mag nieuwe boeken toevoegen
		if($this->magBekijken()){
			$nieuwboekformulier['boekgeg']=new Comment('Boekgegevens:');
			$nieuwboekformulier=$nieuwboekformulier+$this->getCommonFields();
			if($this->isBASFCie()){
				$nieuwboekformulier['biebboek']=new SelectField('biebboek', $this->biebboek, 'Is een biebboek?', array('ja'=>'C.S.R. boek', 'nee'=>'Eigen boek'));
			}
			$nieuwboekformulier['submit']=new SubmitButton('opslaan', '<a class="knop" href="/communicatie/bibliotheek/">Annuleren</a>');

			$this->formulier=new Formulier('/communicatie/bibliotheek/nieuwboek/0', $nieuwboekformulier);
			$this->formulier->cssID='boekaddForm';
		}
	}
	/*
	 * waarden uit nieuw boek formulier opslaan
	 */
	public function saveFormulier(){
		$this->setValuesFromFormulier();
		//object Boek opslaan
		return $this->save();
	}

	/*
	 * Voeg het object Boek toe aan de db
	 */
	public function save(){

		$db=MySql::instance();
		$qSave="
			INSERT INTO biebboek (
				titel, auteur, categorie_id, uitgavejaar, uitgeverij, paginas, taal, isbn, code
			) VALUES (
				'".$db->escape($this->getTitel())."',
				'".$db->escape($this->getAuteur())."',
				".(int)$this->getRubriek()->getId().",
				".(int)$this->getUitgavejaar().",
				'".$db->escape($this->getUitgeverij())."',
				".(int)$this->getPaginas().",
				'".$db->escape($this->getTaal())."',
				'".$db->escape($this->getISBN())."',
				'".$db->escape($this->getCode())."'
			);";
		if($db->query($qSave)){
			//id ook opslaan in object Boek.
			$this->id=$db->insert_id();
			if($this->biebboek=='ja'){
				$eigenaar = 'x222';//C.S.R.Bieb is eigenaar
			}else{
				$eigenaar = Loginlid::instance()->getUid();
			}
			return $this->addExemplaar($eigenaar);
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::save()';
		return false;
	}

}

class BewerkBoek extends Boek {

	protected $formulier;				// Form objecten voor recensieformulier
	public $ajaxformuliervelden;		// Form objecten info v. boek
	//protected $beschrijving;			// recensie tijdens toevoegen/bewerken
	protected $beschrijvingen = array();
	protected $editbeschrijving;		// id van beschrijving die toegevoegd/bewerkt/verwijderd wordt

	public function __construct($init, $beschrijvingid){
		parent::__construct($init);
		$this->editbeschrijving=$beschrijvingid;

		$this->createBoekformulier();

		$this->loadBeschrijvingen();
		$this->getEditBeschrijving()->setEditFlag();
		$this->createBeschrijvingformulier();
	}

	/****************************
	 * Ajax formuliervelden		*
	 ****************************/

	/*
	 * maakt objecten voor de bewerkbare velden van een boek
	 * 
	 */
	public function createBoekformulier(){
		$ajaxformuliervelden=array();
		//Eigenaar een exemplaar v.h. boek mag alleen bewerken
		if($this->isEigenaar()){
			$ajaxformuliervelden=$this->getCommonFields('Boek');
		}

		//voor eigenaars een veldje maken om boek uit te lenen.
		if($this->exemplaren===null){
			$this->loadExemplaren();
		}
		if(count($this->exemplaren)>0){
			foreach($this->exemplaren as $exemplaar){//id, eigenaar_uid, uitgeleend_uid, toegevoegd, status, uitleendatum
				if($this->isEigenaar($exemplaar['id'])){
					$ajaxformuliervelden['lener_'.$exemplaar['id']]=new RequiredLidField('lener_'.$exemplaar['id'], $exemplaar['uitgeleend_uid'], 'Uitgeleend aan', 'alleleden');
					$ajaxformuliervelden['opmerking_'.$exemplaar['id']]=new AutoresizeTextField('opmerking_'.$exemplaar['id'], $exemplaar['opmerking'], 'Opmerking', 255, 'Geef opmerking over exemplaar..');
				}
			}
		}
		$this->ajaxformuliervelden=new Formulier('', $ajaxformuliervelden);
	}

	/*
	 * Geeft één veldobject $entry terug
	 */
	public function getField($entry){ 
		return $this->ajaxformuliervelden->findByName($entry);
	}
	/*
	 * Controleren of het gevraagde veld $entry correct is
	 */
	public function validField($entry){
		//we checken alleen de formfields, niet de comments enzo.
		$field = $this->getField($entry);
		return $field instanceof FormField AND $field->valid('');
	}
	/*
	 * Slaat één veld $entry op in db
	 */
	public function saveField($entry){
		//waarde van $entry in Boek invullen
		$field = $this->getField($entry);
		if($field instanceof FormField){
			$this->setValue($field->getName(), $field->getValue());
		}else{
			$this->error .= 'saveField(): '.$entry.' Geen instanceof FormField.';
			return false;
		}
		//waarde van $entry uit Boek opslaan
		if($this->saveProperty($entry)){
			return true;
		}else{
			$this->error .= 'saveField(): saveProperty mislukt. ';
		}
		return false;
	}
	/*
	 * Opslaan van waarde van een bewerkbaar veld in db
	 */
	public function saveProperty($entry){
		$db=MySql::instance();
		$key = $entry;//op een enkele uitzondering na
		$table = "biebboek";
		$id = $this->getId();

		//$entry voor leners en opmerkingen eerst opsplitsen
		if(substr($entry,0,6)=='lener_'){
			$exemplaarid = substr($entry,6);
			$entry='lener';
		}elseif(substr($entry,0,10)=='opmerking_'){
			$exemplaarid = substr($entry,10);
			$entry='opmerking';
		}

		switch($entry){
			case 'rubriek':
				$value = (int)$this->getRubriek()->getId();
				$key = "categorie_id";
				break;
			case 'uitgavejaar':
			case 'paginas':
				$value = (int)$this->$entry;
				break;
			case 'titel':
			case 'uitgeverij':
			case 'taal':
			case 'isbn':
			case 'code':
			case 'auteur':
				$value = "'".$db->escape($this->$entry)."'";
				break;
			case 'lener':
				return $this->leenExemplaar($exemplaarid, $this->exemplaren[$exemplaarid]['uitgeleend_uid']);
			case 'opmerking':
				$table = "biebexemplaar";
				$key = "opmerking";
				$value = "'".$db->escape($this->exemplaren[$exemplaarid]['opmerking'])."'";
				$id = (int)$exemplaarid;
				break;
			default:
				$this->error.='Veld ['.$entry.'] is niet toegestaan Boek::saveProperty()';
				return false;
		}

		$qSave="
			UPDATE ".$table." SET
				".$key."= ".$value."
			WHERE id= ".$id."
			LIMIT 1;";
		if($db->query($qSave)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::saveProperty()';
		return false;
	}
	/*
	 * retourneert strings.
	 * @param $entry string eigenschapnaam, waarbij leners en opmerkingen ook exemplaarid bevatten 
	 * @return string waarde zals in object opgeslagen
	 */
	public function getProperty($entry){
		//$entry voor leners eerst opsplitsen
		if(substr($entry,0,6)=='lener_'){
			$exemplaarid=substr($entry,6);
			$entry='lener';
		}elseif(substr($entry,0,10)=='opmerking_'){
			$exemplaarid = substr($entry,10);
			$entry='opmerking';
		}

		switch($entry){
			case 'rubriek':
				$return = $this->getRubriek()->getId();
				break;
			case 'rubriekid':
				$return = $this->getRubriek()->getId();
				break;
			case 'titel':
			case 'uitgavejaar':
			case 'uitgeverij':
			case 'paginas':
			case 'taal':
			case 'isbn':
			case 'code':
			case 'auteur':
				$return = $this->$entry;
				break;
			case 'lener':
				$uid=$this->exemplaren[$exemplaarid]['uitgeleend_uid'];
				$lid=LidCache::getLid($uid);
				if($lid instanceof Lid){
					$return = $lid->getNaamLink('full', 'plain');
				}else{
					$return = 'Geen geldig lid getProperty()';
				}
				break;
			case 'opmerking':
				$return = $this->exemplaren[$exemplaarid]['opmerking'];
				break;
			default:
				return 'entry "'.$entry.'" is niet toegestaan. Boek::getProperty()';
		}
		return htmlspecialchars($return);
	}

	/**************
	 * Exemplaren *
	 **************/


	/*
	 * slaat op dat een exemplaar is geleend
	 * 
	 * @param $exemplaarid wordt status 'uitgeleend' in db
	 * @return	true geslaagd
	 * 			false mislukt
	 */
	public function leenExemplaar($exemplaarid,$lener=null){
		//alleen status beschikbaar toegestaan, of je moet eigenaar zijn die iemand toevoegd (tbv editable fields)
		if($this->getStatusExemplaar($exemplaarid)!='beschikbaar' ){
			$this->error.='Boek is niet beschikbaar. leenExemplaar()';
			return false;
		}
		if($lener==null){
			$lener=Loginlid::instance()->getUid();
		}

		$db=MySql::instance();
		$query="
			UPDATE biebexemplaar SET
				uitgeleend_uid = '".$db->escape($lener)."',
				status = 'uitgeleend',
				uitleendatum = '".getDateTime()."',
				leningen=leningen +1
			WHERE id = ".(int)$exemplaarid."
			LIMIT 1;";
		if($db->query($query)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::leenExemplaar()';
		return false;
	}
	/*
	 * slaat op dat een exemplaar iemand exemplaar teruggeeft
	 * 
	 * @param $exemplaarid wordt status 'terugegeven' in db
	 * @return	true geslaagd
	 * 			false mislukt
	 */
	public function teruggevenExemplaar($exemplaarid){
		if($this->getStatusExemplaar($exemplaarid)!='uitgeleend'){
			$this->error.='Boek is niet uitgeleend. ';
			return false;
		}

		$db=MySql::instance();
		$query="
			UPDATE biebexemplaar SET
				status = 'teruggegeven'
			WHERE id = ".(int)$exemplaarid."
			LIMIT 1;";
		if($db->query($query)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::teruggegevenExemplaar()';
		return false;
	}
	/*
	 * slaat op dat een exemplaar iemand exemplaar heeft ontvangen
	 * 
	 * @param $exemplaarid wordt status 'beschikbaar' in db
	 * @return	true geslaagd
	 * 			false mislukt
	 */
	public function terugontvangenExemplaar($exemplaarid){
		if(!in_array($this->getStatusExemplaar($exemplaarid), array('uitgeleend', 'teruggegeven'))){
			$this->error.='Boek is niet uitgeleend. ';
			return false;
		}
		$db=MySql::instance();
		$query="
			UPDATE biebexemplaar SET
				uitgeleend_uid = '',
				status = 'beschikbaar'
			WHERE id = ".(int)$exemplaarid."
			LIMIT 1;";
		if($db->query($query)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::terugontvangenExemplaar()';
		return false;
	}
	/*
	 * markeert exemplaar als vermist
	 * 
	 * @param $exemplaarid wordt status 'vermist' in db
	 * @return	true gelukt
	 * 			false mislukt
	 */
	public function vermistExemplaar($exemplaarid){
		if($this->getStatusExemplaar($exemplaarid)=='vermist'){
			$this->error.='Boek is al vermist. ';
			return false;
		}elseif($this->getStatusExemplaar($exemplaarid)!='beschikbaar'){
			$this->error.='Boek is nog uitgeleend. ';
			return false;
		}

		$db=MySql::instance();
		$query="
			UPDATE biebexemplaar SET
				status = 'vermist',
				uitleendatum = '".getDateTime()."'
			WHERE id = ".(int)$exemplaarid."
			LIMIT 1;";
		if($db->query($query)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::vermistExemplaar()';
		return false;
	}
	/*
	 * markeert exemplaar als beschikbaar
	 * 
	 * @param $exemplaarid wordt status 'beschikbaar' in db
	 * @return	true gelukt
	 * 			false mislukt
	 */
	public function gevondenExemplaar($exemplaarid){
		if($this->getStatusExemplaar($exemplaarid)!='vermist'){
			$this->error.='Boek is niet vermist gemeld. ';
			return false;
		}

		$db=MySql::instance();
		$query="
			UPDATE biebexemplaar SET
				status = 'beschikbaar'
			WHERE id = ".(int)$exemplaarid."
			LIMIT 1;";
		if($db->query($query)){
			return true;
		}
		$this->error.='Fout in query, mysql gaf terug: '.mysql_error().' Boek::gevondenExemplaar()';
		return false;
	}

	/********************************
	 * Boekrecensies/beschrijvingen *
	 ********************************
	/* 
	 * maakt objecten van formulier om beschrijving toe te voegen of te bewerken
	 */
	public function createBeschrijvingformulier(){
		if($this->magBekijken()){
			$schrijver='.';
			if($this->editbeschrijving==0){
				$boekbeschrijvingform[]=new Comment('Geef uw beschrijving of recensie van het boek:');
			}else{
				$boekbeschrijvingform[]=new Comment('Bewerk uw beschrijving of recensie van het boek:');
				
				$lid=LidCache::getLid($this->getEditBeschrijving()->getSchrijver());
				if($lid instanceof Lid){
					$schrijver = $lid->getNaamLink('full', 'plain').':';
				}
			}
			$textfield=new RequiredPreviewTextField('beschrijving', $this->getEditBeschrijving()->getTekst(), $schrijver);
			$textfield->previewOnEnter();
			$boekbeschrijvingform[]=$textfield;
			$boekbeschrijvingform[]=new SubmitButton();

			$posturl='/communicatie/bibliotheek/bewerkbeschrijving/'.$this->getId();
			if($this->editbeschrijving!=0){ 
				$posturl.='/'.$this->editbeschrijving;
			}

			$this->formulier=new Formulier($posturl, $boekbeschrijvingform);
			$this->formulier->cssID='Beschrijvingsformulier';
		}
	}

	/** 
	 * laad beschrijvingen van dit boek, inclusief Beschrijving(0) indien nodig.
	 * @return void
	 */
	protected function loadBeschrijvingen(){
		$db=MySql::instance();
		$query="
			SELECT id, boek_id, schrijver_uid, beschrijving, toegevoegd, bewerkdatum
			FROM biebbeschrijving
			WHERE boek_id=".(int)$this->getId()."
			ORDER BY toegevoegd;";
		$result=$db->query($query);
		if($db->numRows($result)>0){
			while($beschrijving=$db->next($result)){
				$this->beschrijvingen[$beschrijving['id']]=new Beschrijving($beschrijving);
			}
		}else{
			$this->error .= mysql_error();
		}
		//als er een nieuwe beschrijving toegevoegd kan worden is een leeg object nodig 
		if($this->editbeschrijving==0){
			$this->beschrijvingen[0]=new Beschrijving(0, $this->getId());
		}
	}
	// Geeft array met beschrijvingen van dit boek
	public function getBeschrijvingen(){	return $this->beschrijvingen;}
	public function countBeschrijvingen(){	return count($this->beschrijvingen);}

	//geeft Beschrijving-object dat bewerkt/toegevoegd/verwijdert wordt
	public function getEditBeschrijving(){
		if(array_key_exists($this->editbeschrijving, $this->beschrijvingen)){
			return $this->beschrijvingen[$this->editbeschrijving];
		}else{
			throw new Exception('Beschrijving niet bij dit boek gevonden! Boek::getEditBeschrijving() mislukt. ');
		}
	}
	/* 
	 * controleert rechten voor bewerkactie
	 * @param	id van een beschrijving 
	 * 			of null: in Boek geladen beschrijving wordt bekeken
	 * @return	bool
	 * 		een beschrijving mag door schrijver van beschrijving en door admins bewerkt worden.
	 */
	public function magBeschrijvingVerwijderen($beschrijvingsid=null){
		if($this->magVerwijderen()){ return true;}
		if($beschrijvingsid===null){
			$beschrijvingsid=$this->editbeschrijving;
		}
		return $this->beschrijvingen[$beschrijvingsid]->isSchrijver();
	}
	/**
	 * verwijdert in Boek geladen beschrijving
	 */
	public function verwijderBeschrijving(){
		$this->getEditBeschrijving()->verwijder();
	}

	/**
	 * Plaatst gegevens in geladen object Beschrijving en slaat beschrijving op
	 */
	public function saveFormulier(){
		$this->setValuesFromFormulier();
		//de beschrijving/recensie opslaan
		return $this->getEditBeschrijving()->save();
	}

}

class TitelField extends RequiredAutoresizeTextField {

	public function valid(){
		if(!parent::valid()){ return false; }
		if($this->notnull AND $this->getValue()==''){
			$this->error='Dit is een verplicht veld.';
		}elseif(Catalogus::existsProperty('titel', $this->getValue())){
			$this->error='Titel bestaat al.';
		}
		return $this->error=='';
	}

}
?>
