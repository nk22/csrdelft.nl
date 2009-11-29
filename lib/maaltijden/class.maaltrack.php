<?php
# C.S.R. Delft | pubcie@csrdelft.nl
# -------------------------------------------------------------------
# maaltijden/class.maaltijdvoorkeurcontent.php
# -------------------------------------------------------------------
# MaalTrack bevat overkoepelende functies voor maaltijden
# - maaltijd aanmaken
# - maaltijd verwijderen
# - maaltijdenlijst opvragen
# - abonnementen aan/uitzetten
# -------------------------------------------------------------------


require_once("maaltijden/class.maaltijd.php");

class MaalTrack {
	# MySQL connectie
	var $_db;


	# evt. foutboodschap
	var $_error = '';
	var $_proxyerror = '';

	function __construct() {
		$this->_db =MySql::instance();
	}

	function getError() { $error = $this->_error; $this->_error = ""; return $error; }
	function getProxyError() { $proxyerror = $this->_proxyerror; $this->_proxyerror = ""; return $proxyerror; }

	# datum - timestamp wanneer de maaltijd is
	# tekst - omschrijving/menu van de maaltijd
	# abosoort - enum-waarde van een abo dat geldt voor deze maaltijd
	# tp = tafelpraeses-uid
	# max - maximaal aantal inschrijvingen
	function addMaaltijd($datum, $tekst, $abosoort, $tp, $koks, $afwassers, $theedoeken, $max = MAX_MAALTIJD) {
		$datum = (int)$datum;
		$max = (int)$max;
		$tekst = mb_substr($tekst, 0, 200);
		$tekst = $this->_db->escape($tekst);

		$koks=abs((int)$koks);
		$afwassers=abs((int)$afwassers);
		$theedoeken=abs((int)$theedoeken);

		# bij fouten, niet doorgaan, false teruggeven.
		if(!$this->validateMaaltijd($datum, $tekst, $abosoort, $tp, $max)){
			return false;
		}

		# voeg de maaltijd toe en geef het maalid terug, of false als het  niet gelukt is.
		$maaltijd="
			INSERT INTO
				maaltijd
			(
				datum, tekst, abosoort, max, tp, koks, afwassers, theedoeken
			)VALUES(
				'".$datum."', '".$tekst."', '".$abosoort."', '".$max."',
				'".$tp."', '".$koks."', '".$afwassers."', '".$theedoeken."'
			);";

		if (!$this->_db->query($maaltijd)){
			$this->_error="Er is iets mis met de database/query";
			return false;
		}else{
			$maaltijd = new Maaltijd($this->_db->insert_id());
			# ook maar meteen even hertellen, dan zijn de mensen die daar blij van worden weer extra blij...
			$maaltijd->recount();
			return $maaltijd->getMaalId();
		}
	}

	# bestaande maaltijd bewerken. Niet veel verschil met addMaaltijd, behalve dat hier nog even
	# gekeken wordt of de maaltijd wel bestaat.
	function editMaaltijd($maalid, $datum, $tekst, $abosoort, $tp, $koks, $afwassers, $theedoeken, $max=MAX_MAALTIJD){
		if($maalid!=(int)$maalid){
			$this->_error="Ongeldig maaltijdID opgegeven.";
			return false;
		}
		if(!$this->isMaaltijd($maalid)){
			$this->_error="Opgegeven maaltijd bestaat niet.";
			return false;
		}
		$datum = (int)$datum;
		$max = (int)$max;
		$tekst = mb_substr($tekst, 0, 200);
		$tekst = $this->_db->escape($tekst);

		$koks=abs((int)$koks);
		$afwassers=abs((int)$afwassers);
		$theedoeken=abs((int)$theedoeken);

		# bij fouten, niet doorgaan, false teruggeven.
		if(!$this->validateMaaltijd($datum, $tekst, $abosoort, $tp, $max)){
			return false;
		}
		$maaltijd="
			UPDATE
				maaltijd
			SET
				datum=".$datum.",
				tekst='".$tekst."',
				abosoort='".$abosoort."',
				tp='".$tp."',
				koks='".$koks."',
				afwassers='".$afwassers."',
				theedoeken='".$theedoeken."',
				max=".$max."
			WHERE
				id=".$maalid."
			LIMIT 1;";
		if(!$this->_db->query($maaltijd)){
			$this->_error="Er is iets mis met de database/query.";
			return false;
		}else{
			$maaltijd = new Maaltijd ($maalid);
			# ook maar meteen even hertellen, dan zijn de mensen die daar blij van worden weer extra blij...
			$maaltijd->recount();
			return $maalid;
		}
	}
	
	# deze methode valideert de gemeenschappelijke waarden van addMaaltijd en editMaaltijd.
	# controle op specifieke dingen voor editMaaltijd gebeurt nog in de methode zelf.
	function validateMaaltijd($datum, $tekst, $abosoort, $tp, $max){
		# controleer of de datum niet in het verleden ligt
		if ($datum < time()) {
			$this->_error = "Het tijdstip van de maaltijd moet in de toekomst liggen";
			return false;
		}

		# tekst max 200 karakters
		if (!is_utf8($tekst)) {
			$this->_error = "De omschrijving bevat ongeldige tekens.";
			return false;
		}


		# kijk of $tp voorkomt in de ledenlijst
		if($tp != "" AND !Lid::exists($_POST['tp'])){
			$this->_error = "De tafelpraeses moet voorkomen in de ledenlijst.";
			return false;
		}

		# kijk of $abosoort voorkomt in tabel maaltijdabosoort
		if (!array_key_exists($abosoort, $this->getAbos())) {
			$this->_error = "Er is geen bestaande abonnementsvorm opgegeven.";
			return false;
		}

		# controleer of het maximum aantal > 0 en <= MAX_MAALTIJD is
		if ($max <= 0 or $max > MAX_MAALTIJD) {
			$this->_error = "Het maximaal aantal eters moet tussen 1 en " . MAX_MAALTIJD . " zijn.";
			return false;
		}
		# kijk of een gekozen abo niet meteen meer inschrijvingen oplevert dan het maximum wat ingesteld wordt
		if ($abosoort != "" and $this->getAboCount($abosoort) > $max) {
			$this->_error = "Het gekozen abonnement levert meer inschrijvingen op dan het maximaal ingestelde aantal.";
			return false;
		}
		return true;
	}
	
	function removeMaaltijd($maalid) {
		if (!is_numeric($maalid)) {
			$this->_error = "Gebruik een numerieke maaltijd-id waarde om te verwijderen";
			return false;
		}
		# kijk of de maaltijd wel bestaat.
		if(!$this->isMaaltijd($maalid)){
			$this->_error = "Deze maaltijd bestaat niet";
			return false;
		}

		# verwijder alle aan/afmeldingen voor deze maaltijd
		# ...van leden met de bijbehoorende gasten.
		$aanmeldingen="DELETE FROM maaltijdaanmelding WHERE maalid=".$maalid;

		# verwijder de maaltijd zelf
		$maaltijd="DELETE FROM maaltijd WHERE id=".$maalid;

		return $this->_db->query($aanmeldingen) AND $this->_db->query($maaltijd);
	}
	
	# bij bestaande maaltijd de relevante corveevelden bewerken
	function editCorveeMaaltijd($maalid, $koks, $afwassers, $theedoeken, $punten_kok, $punten_afwas, $punten_theedoek){
		
		if($maalid!=(int)$maalid){
			$this->_error="Ongeldig maaltijdID opgegeven.";
			return false;
		}
		if(!$this->isMaaltijd($maalid)){
			$this->_error="Opgegeven maaltijd bestaat niet.";
			return false;
		}

		$koks=abs((int)$koks);
		$afwassers=abs((int)$afwassers);
		$theedoeken=abs((int)$theedoeken);

		// controleer aantal aangemelde taken
		$maaltijd = $this->getMaaltijd($maalid);
		if($koks < $maaltijd['koks_aangemeld']){
			$this->_error="Het aantal koks kan niet lager zijn dan het aantal ingedeelde koks.";
			return false;
		}
		if($afwassers < $maaltijd['afwassers_aangemeld']){
			$this->_error="Het aantal afwassers kan niet lager zijn dan het aantal ingedeelde afwassers.";
			return false;
		}
		if($theedoeken < $maaltijd['theedoeken_aangemeld']){
			$this->_error="Het aantal theedoekwassers kan niet lager zijn dan het aantal ingedeelde theedoekwassers.";
			return false;
		}

		$punten_kok=abs((int)$punten_kok);
		$punten_afwas=abs((int)$punten_afwas);
		$punten_theedoek=abs((int)$punten_theedoek);

		$maaltijd="
			UPDATE
				maaltijd
			SET
				koks='".$koks."',
				afwassers='".$afwassers."',
				theedoeken='".$theedoeken."',
				punten_kok='".$punten_kok."',
				punten_afwas='".$punten_afwas."',
				punten_theedoek='".$punten_theedoek."'
			WHERE
				id=".$maalid."
			LIMIT 1;";
		if(!$this->_db->query($maaltijd)){
			$this->_error="Er is iets mis met de database/query.";
			return false;
		}else{
			$maaltijd = new Maaltijd ($maalid);
			return $maalid;
		}
	}
	
	# bij bestaande maaltijd de taken bewerken
	function editCorveeMaaltijdTaken($maalid, $kok, $afwas, $theedoek, $punten){
		if($maalid!=(int)$maalid){
			$this->_error="Ongeldig maaltijdID opgegeven.";
			return false;
		}
		if(!$this->isMaaltijd($maalid)){
			$this->_error="Opgegeven maaltijd bestaat niet.";
			return false;
		}
		
		//verwijder dubbele uids
		$kok = array_unique($kok);
		$afwas = array_unique($afwas);
		$theedoek = array_unique($theedoek);
		
		//verwerken punten
		//formulier toegekend 0=onbekend, 1=ja, 2=nee
		//van en naar 'ja'
		//van en naar 'nee'
		foreach($punten as $uid=>$form_toegekend){
			if (!$form_toegekend) continue;
			//haal op of punten al toegekend waren
			$sToegekendQuery="
			SELECT 
				punten_toegekend
			FROM 
				maaltijdcorvee 
			WHERE 
				maalid=".$maalid." 
				AND uid=".$uid."
			;";
			$dbresult = $this->_db->query($sToegekendQuery);
			$dbarray = $this->_db->next($dbresult);
			$db_toegekend = $dbarray['punten_toegekend'];

			//Als iemand nog geen punten toegekend had, maar nu wel, ken ze dan tpe
			if($db_toegekend!='ja' && $form_toegekend=='ja'){
				$maaltijd = $this->getMaaltijd($maalid);
				$punten_erbij = (in_array($uid,$kok)?1:0) * $maaltijd['punten_kok'] + 
								 (in_array($uid,$afwas)?1:0) * $maaltijd['punten_afwas'] + 
								 (in_array($uid,$theedoek)?1:0) * $maaltijd['punten_theedoek'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten=corvee_punten+'".$punten_erbij."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}
			//van ja naar iets anders: punten intrekken
			if($db_toegekend=='ja' && $form_toegekend!='ja'){
				$maaltijd = $this->getMaaltijd($maalid);
				$punten_eraf = (in_array($uid,$kok)?1:0) * $maaltijd['punten_kok'] + 
								 (in_array($uid,$afwas)?1:0) * $maaltijd['punten_afwas'] + 
								 (in_array($uid,$theedoek)?1:0) * $maaltijd['punten_theedoek'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten=corvee_punten-'".$punten_eraf."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}

			//Als iemand niet gefaald had, maar nu wel, ken dan strafpunten toe
			if($db_toegekend!='nee' && $form_toegekend=='nee'){
				$maaltijd = $this->getMaaltijd($maalid);
				$strafpunten = (in_array($uid,$kok)?1:0) * $maaltijd['punten_kok'] + 
								 (in_array($uid,$afwas)?1:0) * $maaltijd['punten_afwas'] + 
								 (in_array($uid,$theedoek)?1:0) * $maaltijd['punten_theedoek'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten_bonus=corvee_punten_bonus-'".$strafpunten."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}
			//van nee naar iets anders, dus strafpunten intrekken
			if($db_toegekend=='nee' && $form_toegekend!='nee'){
				$maaltijd = $this->getMaaltijd($maalid);
				$strafpunten = (in_array($uid,$kok)?1:0) * $maaltijd['punten_kok'] + 
								 (in_array($uid,$afwas)?1:0) * $maaltijd['punten_afwas'] + 
								 (in_array($uid,$theedoek)?1:0) * $maaltijd['punten_theedoek'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten_bonus=corvee_punten_bonus+'".$strafpunten."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}
			
		}
		
		//bestaande aanmeldingen verwijderen
		$query_maaltijd="
			DELETE FROM
				maaltijdcorvee
			WHERE
				maalid=".$maalid."";
		if(!$this->_db->query($query_maaltijd)){
			$this->_error="Er is iets mis met de database/query.";
			return false;
		}
				
		//aanmeldingen toevoegen
		foreach($kok as $uid) {
			if (!$uid) continue;
			$this->_db->query("INSERT INTO maaltijdcorvee (maalid, uid, kok) VALUES('".$maalid."', '".$uid."', 1) ON DUPLICATE KEY UPDATE kok = 1");
		}
		foreach($afwas as $uid) {
			if (!$uid) continue;
			$this->_db->query("INSERT INTO	maaltijdcorvee (maalid, uid, afwas) VALUES('".$maalid."', '".$uid."', 1) ON DUPLICATE KEY UPDATE afwas = 1");
		}
		foreach($theedoek as $uid) {
			if (!$uid) continue;
			$this->_db->query("INSERT INTO	maaltijdcorvee (maalid, uid, theedoek) VALUES('".$maalid."', '".$uid."', 1) ON DUPLICATE KEY UPDATE theedoek = 1");
		}
		//Punten_toegekend updaten
		foreach($punten as $uid=>$form_toegekend){
			if (!$uid) continue;
			//$toekenning = array('onbekend','ja','nee');
			$db_toegekend = $form_toegekend;
			$this->_db->query("INSERT INTO	maaltijdcorvee (maalid, uid, punten_toegekend) VALUES('".$maalid."', '".$uid."', '".$db_toegekend."') ON DUPLICATE KEY UPDATE punten_toegekend = '".$db_toegekend."'");
		}
		
		$maaltijd = new Maaltijd ($maalid);
		return $maalid;
	}

	function addSchoonmaakMaaltijd($datum, $tekst, $schoonmaken_frituur, $schoonmaken_afzuigkap, $schoonmaken_keuken, $punten_schoonmaken_frituur, $punten_schoonmaken_afzuigkap, $punten_schoonmaken_keuken) {
		echo 'check';
		$datum = (int)$datum;
		$tekst = mb_substr($tekst, 0, 200);
		$tekst = $this->_db->escape($tekst);

		$schoonmaken_frituur=abs((int)$schoonmaken_frituur);
		$schoonmaken_afzuigkap=abs((int)$schoonmaken_afzuigkap);
		$schoonmaken_keuken=abs((int)$schoonmaken_keuken);
		$punten_schoonmaken_frituur=abs((int)$punten_schoonmaken_frituur);
		$punten_schoonmaken_afzuigkap=abs((int)$punten_schoonmaken_afzuigkap);
		$punten_schoonmaken_keuken=abs((int)$punten_schoonmaken_keuken);
		
		# voeg de maaltijd toe en geef het maalid terug, of false als het  niet gelukt is.
		$maaltijd="
			INSERT INTO
				maaltijd
			(
				datum, 
				type,
				tekst, 
				koks,
				afwassers,
				theedoeken,
				punten_kok,
				punten_afwas,
				punten_theedoek,
				schoonmaken_frituur, 
				schoonmaken_afzuigkap, 
				schoonmaken_keuken,
				punten_schoonmaken_frituur, 
				punten_schoonmaken_afzuigkap, 
				punten_schoonmaken_keuken
			)VALUES(
				'".$datum."', 'corvee', '".$tekst."', 0, 0, 0, 0, 0, 0,
				'".$schoonmaken_frituur."', '".$schoonmaken_afzuigkap."', '".$schoonmaken_keuken."',
				'".$punten_schoonmaken_frituur."', '".$punten_schoonmaken_afzuigkap."', '".$punten_schoonmaken_keuken."'
			);";
		echo $maaltijd.'<br/>';
		if (!$this->_db->query($maaltijd)){
			echo $this->_db->getDebug(true, false, false, false, false, false);
			$this->_error="Er is iets mis met de database/query";
			return false;
		}else{
			echo 'Query ok. ';
			$maaltijd = new Maaltijd($this->_db->insert_id());
			# ook maar meteen even hertellen, dan zijn de mensen die daar blij van worden weer extra blij...
			$maaltijd->recount();
			return $maaltijd->getMaalId();
		}
	}
	
	function editSchoonmaakMaaltijd($maalid, $schoonmaken_frituur, $schoonmaken_afzuigkap, $schoonmaken_keuken, $punten_schoonmaken_frituur, $punten_schoonmaken_afzuigkap, $punten_schoonmaken_keuken){		
		if($maalid!=(int)$maalid){
			$this->_error="Ongeldig maaltijdID opgegeven.";
			return false;
		}
		if(!$this->isMaaltijd($maalid)){
			$this->_error="Opgegeven maaltijd bestaat niet.";
			return false;
		}

		$schoonmaken_frituur=abs((int)$schoonmaken_frituur);
		$schoonmaken_afzuigkap=abs((int)$schoonmaken_afzuigkap);
		$schoonmaken_keuken=abs((int)$schoonmaken_keuken);

		// controleer aantal aangemelde taken
		$maaltijd = $this->getMaaltijd($maalid);
		if($schoonmaken_frituur < $maaltijd['frituur_aangemeld']){
			$this->_error="Het aantal frituurschoonmakers kan niet lager zijn dan het aantal ingedeelde schoonmakers.";
			return false;
		}
		if($schoonmaken_afzuigkap < $maaltijd['afzuigkap_aangemeld']){
			$this->_error="Het aantal afzuigkapschoonmakers kan niet lager zijn dan het aantal ingedeelde schoonmakers.";
			return false;
		}
		if($schoonmaken_keuken < $maaltijd['keuken_aangemeld']){
			$this->_error="Het aantal keukenschoonmakers kan niet lager zijn dan het aantal ingedeelde schoonmakers.";
			return false;
		}

		$punten_schoonmaken_frituur=abs((int)$punten_schoonmaken_frituur);
		$punten_schoonmaken_afzuigkap=abs((int)$punten_schoonmaken_afzuigkap);
		$punten_schoonmaken_keuken=abs((int)$punten_schoonmaken_keuken);

		$maaltijd="
			UPDATE
				maaltijd
			SET
				schoonmaken_frituur='".$schoonmaken_frituur."',
				schoonmaken_afzuigkap='".$schoonmaken_afzuigkap."',
				schoonmaken_keuken='".$schoonmaken_keuken."',
				punten_schoonmaken_frituur='".$punten_schoonmaken_frituur."',
				punten_schoonmaken_afzuigkap='".$punten_schoonmaken_afzuigkap."',
				punten_schoonmaken_keuken='".$punten_schoonmaken_keuken."'
			WHERE
				id=".$maalid."
			LIMIT 1;";
		if(!$this->_db->query($maaltijd)){
			$this->_error="Er is iets mis met de database/query.";
			return false;
		}else{
			$maaltijd = new Maaltijd ($maalid);
			return $maalid;
		}
	}

	# bij bestaande maaltijd de taken bewerken
	function editSchoonmaakMaaltijdTaken($maalid, $frituur, $afzuigkap, $keuken, $punten){
		if($maalid!=(int)$maalid){
			$this->_error="Ongeldig maaltijdID opgegeven.";
			return false;
		}
		if(!$this->isMaaltijd($maalid)){
			$this->_error="Opgegeven maaltijd bestaat niet.";
			return false;
		}
		
		//verwijder dubbele uids
		$frituur = array_unique($frituur);
		$afzuigkap = array_unique($afzuigkap);
		$keuken = array_unique($keuken);
		
		//verwerken punten
		//formulier toegekend 0=onbekend, 1=ja, 2=nee
		//van en naar 'ja'
		//van en naar 'nee'
		foreach($punten as $uid=>$form_toegekend){
			if (!$form_toegekend) continue;
			//haal op of punten al toegekend waren
			$sToegekendQuery="
			SELECT 
				punten_toegekend
			FROM 
				maaltijdcorvee 
			WHERE 
				maalid=".$maalid." 
				AND uid=".$uid."
			;";
			$dbresult = $this->_db->query($sToegekendQuery);
			$dbarray = $this->_db->next($dbresult);
			$db_toegekend = $dbarray['punten_toegekend'];

			//Als iemand nog geen punten toegekend had, maar nu wel, ken ze dan toe
			if($db_toegekend!='ja' && $form_toegekend=='ja'){
				$maaltijd = $this->getMaaltijd($maalid);
				$punten_erbij = (in_array($uid,$frituur)?1:0) * $maaltijd['punten_schoonmaken_frituur'] + 
								 (in_array($uid,$afzuigkap)?1:0) * $maaltijd['punten_schoonmaken_afzuigkap'] + 
								 (in_array($uid,$keuken)?1:0) * $maaltijd['punten_schoonmaken_keuken'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten=corvee_punten+'".$punten_erbij."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}
			//van ja naar iets anders: punten intrekken
			if($db_toegekend=='ja' && $form_toegekend!='ja'){
				$maaltijd = $this->getMaaltijd($maalid);
				$punten_eraf = (in_array($uid,$frituur)?1:0) * $maaltijd['punten_schoonmaken_frituur'] + 
								 (in_array($uid,$afzuigkap)?1:0) * $maaltijd['punten_schoonmaken_afzuigkap'] + 
								 (in_array($uid,$keuken)?1:0) * $maaltijd['punten_schoonmaken_keuken'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten=corvee_punten-'".$punten_eraf."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}

			//Als iemand niet gefaald had, maar nu wel, ken dan strafpunten toe
			if($db_toegekend!='nee' && $form_toegekend=='nee'){
				$maaltijd = $this->getMaaltijd($maalid);
				$strafpunten = (in_array($uid,$frituur)?1:0) * $maaltijd['punten_schoonmaken_frituur'] + 
								 (in_array($uid,$afzuigkap)?1:0) * $maaltijd['punten_schoonmaken_afzuigkap'] + 
								 (in_array($uid,$keuken)?1:0) * $maaltijd['punten_schoonmaken_keuken'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten_bonus=corvee_punten_bonus-'".$strafpunten."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}
			//van nee naar iets anders, dus strafpunten intrekken
			if($db_toegekend=='nee' && $form_toegekend!='nee'){
				$maaltijd = $this->getMaaltijd($maalid);
				$strafpunten = (in_array($uid,$frituur)?1:0) * $maaltijd['punten_schoonmaken_frituur'] + 
								 (in_array($uid,$afzuigkap)?1:0) * $maaltijd['punten_schoonmaken_afzuigkap'] + 
								 (in_array($uid,$keuken)?1:0) * $maaltijd['punten_schoonmaken_keuken'];
				if(!$this->_db->query("UPDATE lid SET corvee_punten_bonus=corvee_punten_bonus+'".$strafpunten."' WHERE uid=".$uid.";")){
					$this->_error=$this->_db->debug("");
					return false;				
				}
			}
			
		}
		
		//bestaande aanmeldingen verwijderen
		$query_maaltijd="
			DELETE FROM
				maaltijdcorvee
			WHERE
				maalid=".$maalid."";
		if(!$this->_db->query($query_maaltijd)){
			$this->_error="Er is iets mis met de database/query.";
			return false;
		}
				
		//aanmeldingen toevoegen
		foreach($frituur as $uid) {
			if (!$uid) continue;
			$this->_db->query("INSERT INTO maaltijdcorvee (maalid, uid, schoonmaken_frituur) VALUES('".$maalid."', '".$uid."', 1) ON DUPLICATE KEY UPDATE schoonmaken_frituur = 1");
		}
		foreach($afzuigkap as $uid) {
			if (!$uid) continue;
			$this->_db->query("INSERT INTO	maaltijdcorvee (maalid, uid, schoonmaken_afzuigkap) VALUES('".$maalid."', '".$uid."', 1) ON DUPLICATE KEY UPDATE schoonmaken_afzuigkap = 1");
		}
		foreach($theedoek as $uid) {
			if (!$uid) continue;
			$this->_db->query("INSERT INTO	maaltijdcorvee (maalid, uid, schoonmaken_keuken) VALUES('".$maalid."', '".$uid."', 1) ON DUPLICATE KEY UPDATE schoonmaken_keuken = 1");
		}
		//Punten_toegekend updaten
		foreach($punten as $uid=>$form_toegekend){
			if (!$uid) continue;
			//$toekenning = array('onbekend','ja','nee');
			$db_toegekend = $form_toegekend;
			$this->_db->query("INSERT INTO	maaltijdcorvee (maalid, uid, punten_toegekend) VALUES('".$maalid."', '".$uid."', '".$db_toegekend."') ON DUPLICATE KEY UPDATE punten_toegekend = '".$db_toegekend."'");
		}
		
		$maaltijd = new Maaltijd ($maalid);
		return $maalid;
	}
	
	# haalt één enkele maaltijd op ter bewerking
	function getMaaltijd($maalid){
		$maalid=(int)$maalid;
		if($maalid==0){
			$this->_error="Geen geldig maaltijd-id";
			return false;
		}
		$sMaaltijdQuery="
			SELECT
				id, type, datum, gesloten, tekst, abosoort, max, aantal,
				tp, koks, afwassers, theedoeken, punten_kok, punten_afwas, punten_theedoek,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND kok = 1) AS koks_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND afwas = 1) AS afwassers_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND theedoek = 1) AS theedoeken_aangemeld,
				schoonmaken_frituur, schoonmaken_afzuigkap, schoonmaken_keuken, 
				punten_schoonmaken_frituur, punten_schoonmaken_afzuigkap, punten_schoonmaken_keuken,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND schoonmaken_frituur = 1) AS frituur_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND schoonmaken_afzuigkap = 1) AS afzuigkap_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND schoonmaken_keuken = 1) AS keuken_aangemeld,				
				(SELECT COUNT(*)>0 FROM maaltijdcorvee WHERE maalid = id AND punten_toegekend = 'ja') AS is_toegekend
			FROM
				maaltijd
			WHERE
				id=".$maalid."
			LIMIT 1;";
		$rMaaltijd=$this->_db->query($sMaaltijdQuery);
		$aMaal=$this->_db->next($rMaaltijd);
		
		//taken ophalen
		$maaltijd = new Maaltijd ($maalid);
		$aMaal['taken'] = $maaltijd->getTaken();

		return $aMaal;
	}
	
	# haalt de lijst met leden op die voor een taak ingedeeld kunnen worden
	function getTaakLeden(){
		$zoekLeden = Zoeker::zoekLeden('', 'uid', 'alle', 'achternaam', 'leden', array('uid', 'achternaam', 'voornaam', 'tussenvoegsel', 'corvee_punten'));
		
		//Todo: |csrnaam gebruiken
		$taakleden = array('' => '- Geen -');
		foreach ($zoekLeden as $lid) {
			$naam = $lid['achternaam'].', '.$lid['voornaam'];
			if($lid['tussenvoegsel'] != '')
				$naam .= ' '.$lid['tussenvoegsel'];
				
			$taakleden[$lid['uid']] = $naam.' ('.$lid['corvee_punten'].')';	
		}
		return $taakleden;
	}
	
	#haalt de lijst met leden op, en filtert deze op hun corveewensen en kwalikok zijn
	function getTaakLedenGefilterd($taak, $dag='', $puntentekort=0){		
		// Zet het filter op 
		//(Ma kok, Ma afw, Do kok, Do afw, Theedoek, Afzuigk, Frituur, Keuken, Puntentekort)
		
		// Op dag
		$dagfilter = bindec('111111110');
		switch($dag){
			case 'Mon':
				$filter = bindec('110000000');
		 	break;
			case 'Thu':
				$filter = bindec('001100000');
			break;
		}
		// Op taak
		$taakfilter = bindec('111111110');
		switch($taak){
			case 'kwalikok':
			case 'kok':
				$taakfilter = bindec('101000000');
			break;
			case 'afwas':
				$taakfilter = bindec('010100000');
			break;
			case 'theedoek':
				$taakfilter = bindec('000010000');
			break;
			case 'frituur':
				$taakfilter = bindec('000000100');
			break;
			case 'afzuigkap':
				$taakfilter = bindec('000001000');
			break;
			case 'keuken':
				$taakfilter = bindec('000000010');
			break;
		}
		
		//combineren van dag en taak
		$filtervoorkeur = $taakfilter & $dagfilter;
		$filtervoorkeur += $puntentekort;
		
		//de ledenlijst ophalen
		$zoekLeden = Zoeker::zoekLeden('', 'uid', 'alle', 'achternaam', 'leden', array('uid', 'achternaam', 'voornaam', 'tussenvoegsel', 'corvee_kwalikok', 'corvee_voorkeuren', 'corvee_vrijstelling', 'corvee_punten'));
		
		//Voorbewerken van de lijst: puntentekort berekenen, kwalikoks selecteren
		$zoekLeden_gefilterd = array();
		foreach ($zoekLeden as $lid){
			$heefttekort = (CORVEEPUNTEN - round(CORVEEPUNTEN*.01*$lid['corvee_vrijstelling'])-$lid['corvee_punten']) > 0 ? 1 : 0;			
			$eigenvoorkeur= bindec($lid['corvee_voorkeuren'] . $heefttekort); //Totaal 8+1 = 9 bits lang
			
			if($taak == 'kwalikok' && $lid['corvee_kwalikok']==0){
				$eigenvoorkeur=bindec('000000000');
			}
			if($taak == 'kok' && $lid['corvee_kwalikok']==1){
				$eigenvoorkeur=bindec('000000000');
			}
			
			/* Combineer de eigen voorkeur met de gezochte taak*/
			$bitstring = ''.decbin($eigenvoorkeur & $filtervoorkeur);
			// Tel het aantal enen
			if ((count(explode('1',$bitstring))-1) >= 1){
				$zoekLeden_gefilterd[$lid['uid']]=$lid;
			}			
		}
				
		//Todo: |csrnaam gebruiken
		$taakleden = array('' => '- Geen -');
		foreach ($zoekLeden_gefilterd as $lid) {
			$naam = $lid['achternaam'].', '.$lid['voornaam'];
			if($lid['tussenvoegsel'] != '')
				$naam .= ' '.$lid['tussenvoegsel'];
				
			$taakleden[$lid['uid']] = $naam.' ('.$lid['corvee_punten'].')';	
		}
		return $taakleden;
	}
	
	# haalt één enkele maaltijd op ter bewerking
	function getPuntenlijst($sorteer = 'corvee_tekort', $sorteer_richting = 'asc'){
		// TODO: leden meer filteren

		$sorteer_toegestaan = array('uid', 'kok', 'afwas', 'theedoek', 'schoonmaken_frituur', 'schoonmaken_afzuigkap', 'schoonmaken_keuken', 'corvee_kwalikok', 'corvee_punten', 'corvee_punten_bonus', 'corvee_vrijstelling', 'corvee_ingeroosterd', 'corvee_tekort');
		$sorteer_volgorde_toegestaan = array('asc', 'desc');
		if (!in_array($sorteer, $sorteer_toegestaan) || !in_array($sorteer_richting, $sorteer_volgorde_toegestaan))
			print('Ongeldige sorteeroptie');
	
		// TODO: corvee ingeroosterd berekenen...
		$sLedenQuery="
			SELECT
				uid, corvee_kwalikok, corvee_punten, corvee_punten_bonus, corvee_vrijstelling, corvee_voorkeuren,
				0 AS corvee_ingeroosterd,
				(".CORVEEPUNTEN."-CEIL(".CORVEEPUNTEN."*.01*corvee_vrijstelling)-corvee_punten_bonus-corvee_punten) AS corvee_tekort,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE uid = lid.uid AND kok = 1) AS kok,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE uid = lid.uid AND afwas = 1) AS afwas,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE uid = lid.uid AND theedoek = 1) AS theedoek,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE uid = lid.uid AND schoonmaken_frituur = 1) AS schoonmaken_frituur,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE uid = lid.uid AND schoonmaken_afzuigkap = 1) AS schoonmaken_afzuigkap,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE uid = lid.uid AND schoonmaken_keuken = 1) AS schoonmaken_keuken
			FROM
				lid
			WHERE
				status='S_LID' OR status='S_NOVIET'
			ORDER BY
				".$sorteer." ".strtoupper($sorteer_richting).", uid ASC";
		$rLeden=$this->_db->query($sLedenQuery);
		$aLeden=$this->_db->result2array($rLeden);
		
		// rood naar geel naar groen
		foreach($aLeden as &$rLid) {
			$kleur_start = 8;
			$tekort_offset = 2;
			
			$rRaw = 2 * (($rLid['corvee_tekort']/(CORVEEPUNTEN+$tekort_offset)));	// waarde tussen 0 en 1
			$gRaw = 2 * (1-$rLid['corvee_tekort']/(CORVEEPUNTEN+$tekort_offset));	// waarde tussen 0 en 1
			if ($rRaw < 0) $rRaw = 0; if ($rRaw > 1) $rRaw = 1;
			if ($gRaw < 0) $gRaw = 0; if ($gRaw > 1) $gRaw = 1;
			
			$r = $kleur_start + round($rRaw * (15-1-$kleur_start));
			$g = $kleur_start + round($gRaw * (15-1-$kleur_start));
			$b = $kleur_start;
						
			$rLid['corvee_tekort_rgb'] = dechex($r).dechex($g).dechex($b);
		}

		return $aLeden;
	}
	
	# bij bestaande maaltijd de taken bewerken
	function editLid($uid, $corvee_kwalikok, $corvee_punten, $corvee_punten_bonus, $corvee_vrijstelling){		
		// lid bewerken
		$this->_db->query("UPDATE lid SET corvee_kwalikok='".$corvee_kwalikok."', corvee_punten='".$corvee_punten."', corvee_punten_bonus='".$corvee_punten_bonus."', corvee_vrijstelling='".$corvee_vrijstelling."' WHERE uid = '".$uid."'");
		return true;
	}
	
	# haalt maaltijden uit de maaltijdentabel op, voor uitgebreidere info
	# voor in de kolommen op de maaltijdencontent pagina, zie getMaaltijden hieronder
	# als de gebruiker uit moot 1-4 is, hou daar dan rekening mee
	# deze functionaliteit kan uitgezet worden door $mootfilter = false te zetten als argument
	public static function getMaaltijdenRaw($van = 0, $tot = 0, $mootfilter = true, $corveefilter = true) {
		$lid=LoginLid::instance();
		$db=MySql::instance();
		# kijk in db en haal alle maaltijden op waarbij de begintijd
		# na $van is, en voor $tot

		# meestal zal voor $van time() gebruikt worden, als er niets is opgegeven
		# dan wordt ook de huidige tijd gebruikt
		if ($van == 0) $van = time();

		# als $tot niet is opgegeven, of 0 is, dan worden alle maaltijden vanaf
		# van teruggegeven, gesorteerd op tijd
		$tot = (int)$tot;
		$totsql = ($tot != 0) ? "datum < '".$tot."'" : "1";

		# mootfilter
		if(!$lid->hasPermission('P_MAAL_MOD')){
			if($mootfilter === true){
				$moot = $lid->getLid()->getVerticaleID();
			}
		}else{
			$mootfilter=false;
		}
		
		# corveefilter - filtert speciale "dummy" maaltijden bedoelt voor corvee/schoonmaak
		if ($corveefilter){
			$corveevelden = "";
			$corveefilter = "AND type = 'normaal'";
		} else {
			$corveevelden = ", schoonmaken_frituur, schoonmaken_afzuigkap, schoonmaken_keuken, punten_schoonmaken_frituur, punten_schoonmaken_afzuigkap, punten_schoonmaken_keuken,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND schoonmaken_frituur = 1) AS frituur_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND schoonmaken_afzuigkap = 1) AS afzuigkap_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND schoonmaken_keuken = 1) AS keuken_aangemeld
				";
			$corveefilter = "";
		}

		$maaltijden = array();
		$sMaaltijdQuery="
			SELECT
				id, datum, type, gesloten, tekst, abosoort, max, aantal, tp, koks, afwassers, theedoeken, punten_kok, punten_afwas, punten_theedoek, corvee_gemaild,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND kok = 1) AS koks_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND afwas = 1) AS afwassers_aangemeld,
				(SELECT COUNT(uid) FROM maaltijdcorvee WHERE maalid = id AND theedoek = 1) AS theedoeken_aangemeld,
				(SELECT COUNT(*)>0 FROM maaltijdcorvee WHERE maalid = id AND punten_toegekend = 'ja') AS is_toegekend
				".$corveevelden."
			FROM
				maaltijd
			WHERE
				datum > '".$van."' AND ".$totsql." ".$corveefilter."
			ORDER BY
				datum ASC;";
		$result=$db->select($sMaaltijdQuery);
		if (($result !== false) and $db->numRows($result) > 0) {
			while ($record = $db->next($result)) {
				if(!($mootfilter===true AND preg_match("/(VERT)[^{$moot}]{1}/", $record['abosoort']))){
					$maaltijden[] = $record;

				}
			}
		}
		# id, datum, gesloten, tekst, abosoort, max, aantal, tp
		return $maaltijden;
	}

	function getAboTekst($abosoort = '') {
		# abotekst: tekst van het abo dat van toepassing is of ''
		if ($abosoort == '') return '';
		$result = $this->_db->select("SELECT tekst FROM maaltijdabosoort WHERE abosoort = '{$abosoort}'");
		if (($result !== false) and $this->_db->numRows($result) > 0) {
			$record = $this->_db->next($result);
			return $record['tekst'];
		} else return '';
	}

	# haalt maaltijden op en voegt extra info toe voor op de maaltijdenpagina
	function getMaaltijden($van = 0, $tot = 0, $mootfilter = true, $corveefilter = true, $uid=null, $alsArray=true) {
		if($uid==null){
			$uid=LoginLid::instance()->getUid();
		}
		if($uid == 'x999'){ $mootfilter = false; }

		$maaltijdenRaw = $this->getMaaltijdenRaw($van,$tot,$mootfilter,$corveefilter);

		$maaltijden=array();
		
		if($alsArray){
			foreach($maaltijdenRaw as $maaltijd){
				$maaltijd['abotekst'] = $this->getAboTekst($maaltijd['abosoort']);
	
				if($maaltijd['gesloten']=='1'){
					//als de maaltijd al gesloten is, dan uit de maaltijdgesloten-tabel ophalen.
					$sAanmeldingen="SELECT uid, gasten, gasten_opmerking FROM maaltijdgesloten WHERE uid = '".$uid."' AND maalid = ".$maaltijd['id'].";";
					$rAanmeldingen = $this->_db->query($sAanmeldingen);
					if (($rAanmeldingen !== false) and $this->_db->numRows($rAanmeldingen) > 0) {
						$maaltijd['status']='AAN';
					}else{
						$maaltijd['status']='';
					}
					# Gasten ophalen
					$record = $this->_db->next($rAanmeldingen);
					$maaltijd['gasten'] = $record['gasten'];
					$maaltijd['opmerking'] = $record['gasten_opmerking'];
				}else{
					# status: AAN,AF ABO ''
					# 1a. is er een aan of afmelding voor deze maaltijd?
					$sAanmeldingen="
						SELECT
							status, gasten, gasten_opmerking
						FROM
							maaltijdaanmelding
						WHERE
							uid = '".$uid."' AND maalid = ".$maaltijd['id'].";";
					$rAanmeldingen = $this->_db->query($sAanmeldingen);
					if (($rAanmeldingen !== false) and $this->_db->numRows($rAanmeldingen) > 0) {
						$record = $this->_db->next($rAanmeldingen);
						$maaltijd['status'] = $record['status'];
						# Gasten ophalen
						$maaltijd['gasten'] = $record['gasten'];
						$maaltijd['opmerking'] = $record['gasten_opmerking'];
					} else {
						# 1b. zo nee, is er een abo actief?
						$sAbo="SELECT uid FROM maaltijdabo WHERE uid = '".$uid."' AND abosoort = '".$maaltijd['abosoort']."'";
						$rAbo = $this->_db->query($sAbo);
						if(($rAbo !== false) and $this->_db->numRows($rAbo) > 0) {
							$record = $this->_db->next($rAbo);
							$maaltijd['status'] = 'ABO';
						}else{
							# 1c. zo ook nee, dan status = ''
							$maaltijd['status'] = '';
						}
					}
				}
				
				# Corvee
				$sCorvee="
					SELECT
						kok, afwas, theedoek
					FROM
						maaltijdcorvee
					WHERE
						uid = '".$uid."' AND maalid = ".$maaltijd['id'].";";
				$rCorvee = $this->_db->query($sCorvee);
				if (($rCorvee !== false) and $this->_db->numRows($rCorvee) > 0) {
					$record = $this->_db->next($rCorvee);
					$maaltijd['kok'] = $record['kok'];
					$maaltijd['afwas'] = $record['afwas'];
					$maaltijd['theedoek'] = $record['theedoek'];
				}
	
				# 2. actie is afhankelijk van status en evt. gesloten zijn van de maaltijd
				# actie: AAN, AF, ''
				if(($maaltijd['status']=='AAN' OR $maaltijd['status']=='ABO') AND $maaltijd['gesloten']=='0' ){
					$maaltijd['actie'] = 'af';
				}elseif(($maaltijd['status']=='AF' OR $maaltijd['status']=='') AND $maaltijd['aantal'] != $maaltijd['max'] AND $maaltijd['gesloten'] == '0' ){
					$maaltijd['actie'] = 'aan';
				}else{
					$maaltijd['actie'] = '';
				}
				$maaltijden[]=$maaltijd;
			}
		}else{
			foreach($maaltijdenRaw as $maaltijd){ 
				$maaltijden[] = new Maaltijd($maaltijd['id']);
			}
		}
		return $maaltijden;

	}

	# kijkt of er een maaltijd bestaat met deze maalid
	# te gebruiken alvorens een object maaltijd aan te maken
	function isMaaltijd($maalid) {
		if (!is_numeric($maalid)) {
			$this->_error = "De opgegeven maaltijd bestaat niet.";
			return false;
		}
		$result = $this->_db->select("SELECT * from `maaltijd` WHERE `id` = '$maalid'");
		if (($result === false) or $this->_db->numRows($result) == 0) {
			$this->_error = "De opgegeven maaltijd bestaat niet.";
			return false;
		}
		return true;
	}

	# wrapper-functie voor aanmelden, die controleert of de maaltijd wel bestaat
	# en om te zorgen dat foutmeldingen goed terugkomen in de pagina
	function aanmelden($maalid, $uid = '') {
		# isMaaltijd zet zelf een error als het nodig is
		if (!$this->isMaaltijd($maalid)) return false;
		$maaltijd = new Maaltijd($maalid);
		if (!$maaltijd->aanmelden($uid)) {
			$this->_error = $maaltijd->getError();
			$this->_proxyerror = $maaltijd->getProxyError();
			return false;
		}
		return true;
	}

	# wrapper-functie voor afmelden, die controleert of de maaltijd wel bestaat
	# en om te zorgen dat foutmeldingen goed terugkomen in de pagina
	function afmelden($maalid, $uid = '') {
		# isMaaltijd zet zelf een error als het nodig is
		if (!$this->isMaaltijd($maalid)) return false;
		$maaltijd = new Maaltijd($maalid);
		if (!$maaltijd->afmelden($uid)) {
			$this->_error = $maaltijd->getError();
			$this->_proxyerror = $maaltijd->getProxyError();
			return false;
		}
		return true;
	}

	# wrapper-functie voor gasten aanmelden, die controleert of de maaltijd wel bestaat
	# en om te zorgen dat foutmeldingen goed terugkomen in de pagina
	function gastenAanmelden($maalid, $gasten, $opmerking) {
		# isMaaltijd zet zelf een error als het nodig is
		if (!$this->isMaaltijd($maalid)) return false;
		$maaltijd = new Maaltijd($maalid);
		if (!$maaltijd->gastAanmelden($gasten, $opmerking)) {
			$this->_error = $maaltijd->getError();
			$this->_proxyerror = $maaltijd->getProxyError();
			return false;
		}
		return true;
	}

	# abo aanzetten voor huidige gebruiker
	function addAbo($abosoort) {
		# Kijk of deze abosoort geldig is voor deze persoon, en of we m aan kunnen zetten
		$geenabo = $this->getNotAboSoort();
		if (!array_key_exists($abosoort, $geenabo)) {
			$this->_error = "Er is een ongeldige abonnementsvorm opgegeven, of dit abo is al ingeschakeld voor u.";
			return false;
		}

		# abo toevoegen
		$uid = LoginLid::instance()->getUid();
		$result = $this->_db->query("INSERT INTO `maaltijdabo` (`uid`,`abosoort`) VALUES ('{$uid}','{$abosoort}')");

		# kijken of er maaltijden zijn in de toekomst met dit abo die VOL zijn, en daar AFmeldingen voor maken
		# bij de andere maaltijden met dit abo een recount doen
		$van = time();
		$result = $this->_db->query("SELECT * FROM maaltijd WHERE abosoort = '{$abosoort}' AND gesloten = '0'");
		if (($result !== false) and $this->_db->numRows($result) > 0) {
			while ($record = $this->_db->next($result)) {
				$maaltijd = new Maaltijd ($record['id']);
				if ($record['aantal'] == $record['max']) $maaltijd->afmelden();
				else $maaltijd->recount();
				unset($maaltijd);
			}
		}

		return true;
	}

	# abo uitzetten voor huidige gebruiker
	function delAbo($abosoort) {
		# kijk of $abosoort voorkomt in de abo's van deze persoon
		$abos = $this->getAbo();
		if (!array_key_exists($abosoort, $abos)) {
			$this->_error = "Er is een ongeldige abonnementsvorm opgegeven, of dit abo is niet ingeschakeld voor u.";
			return false;
		}

		# abo verwijderen
		$uid = LoginLid::instance()->getUid();
		$result = $this->_db->query("DELETE FROM `maaltijdabo` WHERE `uid` = '{$uid}' AND `abosoort` = '{$abosoort}'");

		# bij de maaltijden met dit abo een recount doen
		$van = time();
		$result = $this->_db->query("SELECT * FROM maaltijd WHERE abosoort = '{$abosoort}' AND gesloten = '0'");
		if (($result !== false) and $this->_db->numRows($result) > 0) {
			while ($record = $this->_db->next($result)) {
				$maaltijd = new Maaltijd ($record['id']);
				# als gebruiker geen expliciete AAN of AF heeft kan het aantal inschrijvingen
				# veranderen doordat zijn abo's veranderen
				if ($maaltijd->getStatus() == 'AUTO') $maaltijd->recount();
				unset($maaltijd);
			}
		}
		return true;
	}

	# abo's opvragen voor huidige gebruiker
	public function getAbo($uid=null) {
		$abos = array();
		if($uid==null){
			$uid = LoginLid::instance()->getUid();
		}
		$qAbo="
			SELECT maaltijdabosoort.abosoort, maaltijdabosoort.tekst
			FROM maaltijdabo, maaltijdabosoort
			WHERE maaltijdabo.abosoort = maaltijdabosoort.abosoort
				AND maaltijdabo.uid = '".$uid."';";
		$rAbo=$this->_db->query($qAbo);
		if (($rAbo !== false) and $this->_db->numRows($rAbo) > 0) {
			while ($record = $this->_db->next($rAbo)){
				$abos[$record['abosoort']] = $record['tekst'];
			}
		}
		return $abos;
	}
	# alle abo's opvragen, ook het 'Geen' abo...
	# deze functie wordt gebruikt om het soort abo te kunnen kiezen bij maaltijdenbeheer
	function getAbos() {
		$abos = array();
		$result = $this->_db->select("
			SELECT
				maaltijdabosoort.abosoort AS abosoort,
				maaltijdabosoort.tekst AS tekst
			FROM
				maaltijdabosoort;");
		if (($result !== false) and $this->_db->numRows($result) > 0) {
			while ($record = $this->_db->next($result)) {
				$abos[$record['abosoort']] = $record['tekst'];
			}
		}
		return $abos;
	}
	# Controleer of het gegeven abonnement wel bestaat.
	function isValidAbo($abo){ return $abo != 'A_GEEN' and array_key_exists($abo, $this->getAbos()); }

	# alle abosoorten opvragen, als deze gebruiker uit moot 1-4 is, hou daar dan rekening mee
	# deze functionaliteit kan uitgezet worden door $mootfilter = false te zetten als argument
	# het 'Geen' abonnement wordt hier uitgefilterd
	function getAboSoort($mootfilter = true) {
		$abos = array();
		if($mootfilter === true){
			$moot = LoginLid::instance()->getLid()->getVerticaleID();
		}
		$result = $this->_db->select("SELECT * FROM maaltijdabosoort WHERE NOT abosoort='A_GEEN'");
		if (($result !== false) and $this->_db->numRows($result) > 0) {
			while ($record = $this->_db->next($result)) {
				if ($mootfilter === true and preg_match("/VERT[^{$moot}]{1}/", $record['abosoort'])) continue;
				$abos[$record['abosoort']] = $record['tekst'];
			}
		}
		return $abos;
	}

	# alle abosoorten die de ingelogde gebruiker *niet* heeft aanstaan
	function getNotAboSoort($mootfilter = true) {
		$abos = $this->getAbo();
		$abosoorten = $this->getAboSoort($mootfilter);
		return array_diff_key($abosoorten, $abos);
	}

	# array van uids/namen maken die (behalve zichzelf) door de ingelogde persoon zijn aangemeld voor deze maaltijd
	function getProxyAanmeldingen($uid, $maalid) {
		$wienogmeer = array();
		$result = $this->_db->select("
			SELECT uid
			FROM maaltijdaanmelding
			WHERE maalid = {$maalid} AND door = '{$uid}' AND uid <> door AND status = 'AAN'
		");
		if (($result !== false) and $this->_db->numRows($result) > 0)
			while ($record = $this->_db->next($result)){
				$wienogmeer[$record['uid']] = (string)LidCache::getLid($record['uid']);
			}
		return $wienogmeer;
	}

	function getAboCount ($abosoort) {
		$abosoort = $this->_db->escape($abosoort);
		$result = $this->_db->select("SELECT uid FROM maaltijdabo WHERE abosoort = '{$abosoort}'");
		if ($result !== false) return $this->_db->numRows($result);
		return 0;
	}
	
	# corvee automailer
	function corveeAutoMailer()
	{
		// get maaltijden waar datum < deze week
		$maaltijden = $this->getMaaltijdenRaw(0, time()+86400*7);
		
		echo "Start<br />Maaltijden ophalen binnen komende 7 dagen..<br />\n";
		foreach($maaltijden as $maaltijd)
		{
			echo "- Maaltijd ID ".$maaltijd['id'].": ";
			if ($maaltijd['corvee_gemaild']) {
				echo "Reeds gemaild.";
			} else {
				$lMaaltijd = new Maaltijd($maaltijd['id']);
				
				// taken ophalen
				$taken = $lMaaltijd->getTaken();
				$teller = 0;
				foreach($taken as $taak => $leden)
				{
					$template = null;
					switch ($taak) {
						case 'afwassers':
							$template = 'afwas.tpl';
						case 'koks' :
							$template = 'koks.tpl';
						case 'theedoeken':
							$template = 'theedoeken.tpl';
						case 'schoonmaken_frituur':
							$template = 'schoonmaken_frituur.tpl';
						case 'schoonmaken_afzuigkap':
							$template = 'schoonmaken_afzuigkap.tpl';
						case 'schoonmaken_keuken':
							$template = 'schoonmaken_keuken.tpl';
					}
					if (!$template) continue;
					
					// mailen
					setlocale(LC_ALL, 'nl_NL');
					$onderwerp = 'C.S.R. Delft Corvee - '.strftime('%d-%m-%Y', $maaltijd['datum']);
					$headers="From: PubCie (niet antwoorden) <noreply@csrdelft.nl>\n";
					$headers.="BCC: jjnederend@gmail.com\n"; // tijdelijk?
					$headers.="Content-Type: text/plain; charset=UTF-8\r\n";
					$headers.='X-Mailer: csrdelft.nl/PubCie'."\n\r";
					
					$mail = new Smarty_csr();
					$mail->assign('datum', strftime('%d-%m-%Y (%A)', $maaltijd['datum']));
					$bericht = $mail->fetch('maaltijdketzer/corveemail/'.$template);
					foreach($leden as $uid) {			
						mail($uid.'@csrdelft.nl', $onderwerp, $bericht, $headers);
						$teller++;
					}
				}
				echo "Mensen gemaild: ".$teller;
				
				// update corvee_gemaild
				$query = "UPDATE maaltijd SET corvee_gemaild = 1 WHERE id=".$maaltijd['id']."";
				if(!$this->_db->query($query)){
					$this->_error="Er is iets mis met de database/query.";
					return false;
				}
			}
			echo "<br />\n";
		}
		echo "Klaar!<br />\n";
	}

}

?>
