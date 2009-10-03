<?php
/*
 * class.formulier.php	| 	Jan Pieter Waagmeester (jieter@jpwaag.com)
 *
 * Dit is een poging om maar op één plek dingen over een formulier te defenieren:
 *  - validatorfuncties
 *  - Html voor het formulier
 *  - suggesties voor formuliervelden
 *
 * Alle Veldobjecten stammen af van FormField, dat regelt een hoop basismeuk.
 */

abstract class FormField{
	public $name;				//naam van het veld in POST
	public $value;				//welke initiele waarde heeft het veld?
	public $notnull=false; 		//mag het veld leeg zijn?
	public $autocomplete=true; 	//browser laten autoaanvullen?
	public $error='';			//foutmelding van dit veld

	public $suggestions=array();

	public function __construct($name, $value, $description=null){
		$this->name=$name;
		$this->value=$value;
		$this->description=$description;
		if($this->isPosted()!==false){
			$this->value=$this->getValue();
		}
	}
	public function getName(){	return $this->name; }
	public function isPosted(){	return isset($_POST[$this->name]); }
	
	public function setSuggestions($array){	$this->suggestions=$array; }
	
	public function getValue(){
		if($this->isPosted()){
			return trim($_POST[$this->name]);
		}
		return '';
	}
	
	//Is de invoer voor het veld correct?
	//standaard krijgt deze functie de huidige waarde mee als argument
	public function valid(){
		if(!$this->isPosted()){
			$this->error='Veld is niet gepost.';
		//vallen over lege velden als dat aangezet is voor het veld en als gebruiker geen LEDEN_MOD heeft.
		}elseif($this->notnull AND !LoginLid::instance()->hasPermission('P_LEDEN_MOD') AND $this->getValue()==''){
			$this->error='Dit is een verplicht veld.';
		}
		return $this->error=='';
	}
	protected function getDiv(){
		$cssclass='veld';
		if($this->error!=''){
			$cssclass.=' metfouten';
		}
		return '<div class="'.$cssclass.'">';
	}
	protected function getLabel(){
		if($this->description!=null){
			echo '<label for="field_'.$this->name.'">'.mb_htmlentities($this->description).'</label>';
		}
	}
	
	protected function getError(){
		if($this->error!=''){
			return '<div class="waarschuwing">'.$this->error.'</div>';
		}
	}
		
	public function view(){
		echo $this->getDiv();
		echo $this->getLabel();
		echo $this->getError();
		echo '<input type="text" id="field_'.$this->name.'" name="'.$this->name.'" class="regular" value="'.htmlspecialchars($this->value).'" ';
		if(!$this->autocomplete OR count($this->suggestions)>0){
			echo 'autocomplete="off" ';
		}
		echo ' />';
		if(count($this->suggestions)>0){
			echo '<script language="javascript"> ';
			echo 'var sug_'.$this->name.'=new Array("'.implode('","', $this->suggestions).'"); ';
			echo 'new actb(document.getElementById("field_'.$this->name.'"), sug_'.$this->name.'); ';
			echo '</script>';
		}
		
		echo '</div>';
	}
}

/*
 * Textarea's
 */
class TextField extends FormField{

	public function view(){
		echo $this->getDiv();
		echo $this->getLabel();
		echo $this->getError();
		echo '<textarea id="field_'.$this->name.'" name="'.$this->name.'" class="regular" rows="5">'.htmlspecialchars($this->value).'</textarea>';

		echo '</div>';
	}
}

/*
 * Een InputField heeft een maximale lengte.
 */
class InputField extends FormField{
	public $max_len=255;
	
	public function __construct($name, $value, $description, $max_len=255){
		parent::__construct($name, $value, $description);
		$this->max_len=(int)$max_len;
	}
	public function valid(){
		if(!parent::valid()){ return false; }
		if($this->getValue()==''){ return true; }
		
		if(mb_strlen($this->getValue())>$this->max_len){
			$this->error='Maximaal '.$this->max_len.' karakters toegestaan.';
		}
		return $this->error=='';
	}
}
class LandField extends FormField{
	public function __construct($name, $value, $description){
		parent::__construct($name, $value, $description);
		$landsuggesties=array('Nederland', 'België', 'Duitsland', 'Frankrijk', 'Verenigd Koninkrijk', 'Verenigde Staten');
		$this->setSuggestions($landsuggesties);
	}
}
class RequiredLandField extends LandField{
	public $notnull=true;
}
class UidField extends InputField{
	public function __construct($name, $value, $description){
		parent::__construct($name, $value, $description, 4);
	}
	public function valid(){
		if(!parent::valid()){ return false; }
		//leeg veld wel accepteren.
		if($this->getValue()==''){ return true; }
		if(!Lid::isValidUid($this->getValue())){
			$this->error='Geen geldig uid opgegeven';
		}
		return $this->error=='';
	}
	public function view(){
		echo $this->getDiv();
		echo $this->getLabel();
		echo $this->getError();
		echo '<input type="text" id="field_'.$this->name.'" name="'.$this->name.'" class="uid" value="'.htmlspecialchars($this->value).'" ';
		echo ' autocomplete="off" onKeyUp="uidPreview(\''.$this->name.'\')" maxlength="4" />';
		echo '<div class="uidPreview" id="preview_'.$this->name.'"></div>';
		echo '<script>uidPreview(\''.$this->name.'\');</script>';
		echo '</div>';
	}
}
class RequiredInputField extends InputField{
	public $notnull=true;
}

class EmailField extends FormField{
	public function valid(){
		if(!parent::valid()){ return false; }
		if($this->getValue()==''){ return true; }
		//bevat het email-adres een @
		if(strpos($this->getValue(), '@') === false){
			$this->error='Ongeldig formaat email-adres';
		}else{
			# anders gaan we m ontleden en controleren
			list ($usr,$dom) = split ('@', $this->getValue());
			if(mb_strlen($usr) > 50){
				$this->error='Gebruik max. 50 karakters voor de @:';
			} elseif(mb_strlen($dom) > 50){
				$this->error='Gebruik max. 50 karakters na de @:';
			# RFC 821 <- voorlopig voor JabberID even zelfde regels aanhouden
			# http://www.lookuptables.com/
			# Hmmmz, \x2E er uit gehaald ( . )
			}elseif(preg_match('/[^\x21-\x7E]/', $usr) OR preg_match('/[\x3C\x3E\x28\x29\x5B\x5D\x5C\x2C\x3B\x40\x22]/', $usr)){
				$this->error='Het adres bevat ongeldige karakters voor de @:';
			}elseif(!preg_match('/^[a-z0-9]+([-.][a-z0-9]+)*\\.[a-z]{2,4}$/i', $dom)){
				$this->error='Het domein is ongeldig:';
			}elseif(!checkdnsrr($dom, 'A') and !checkdnsrr($dom, 'MX')){
				$this->error='Het domein bestaat niet (IPv4):';
			}elseif(!checkdnsrr($dom, 'MX')){
				$this->error='Het domein is niet geconfigureerd om email te ontvangen:';
			}
		}
		return $this->error=='';
	}
}
class RequiredEmailField extends EmailField{
	public $notnull=true;
}
class UrlField extends FormField{
	public function valid(){
		if(!parent::valid()){ return false; }
		if($this->getValue()==''){ return true; }

		if(!is_utf8($this->getValue()) OR !preg_match("#([\w]+?://[^ \"\n\r\t<]*?)#is",$this->getValue())){
			$this->error='Geen geldige website';
		}
		return $this->error=='';
	}
}


class WebsiteField extends InputField{
	public function valid(){
		if(!parent::valid()){ return false; }
		if($this->getValue()==''){ return true; }
		
		# controleren of het een geldige url is...
		if(!is_utf8($this->getValue()) OR !preg_match('#([\w]+?://[^ "\n\r\t<]*?)#is', $this->getValue())){
			$this->error='Ongeldige karakters:';
		} elseif(mb_strlen($this->getValue()) > $this->max_len) {
			$this->error='Gebruik maximaal '.$this->max_len.' karakters:';
		}
		return $this->error=='';
	}
}

class IntField extends FormField{
	public $min=null;
	public $max=null;
	
	public function __construct($name, $value, $description, $max=null, $min=null){
		parent::__construct($name, $value, $description);
		$this->max=(int)$max;
		$this->min=(int)$min;

		$this->notnull=true;
	}
	public function getValue(){ return (int)parent::getValue(); }
	
	public function valid(){
		if(!parent::valid()){ return false; }
		//als een veld verplicht is heeft het in FormField::valid() al een foutmelding opgeleverd.
		if($this->getValue()==0){ return true; }
		
		if(!preg_match('/\d+/', $this->getValue())){
			$this->error='Alleen getallen toegestaan';
		}elseif($this->max!==null AND $this->getValue()>$this->max){
			$this->error='Maximale waarde is '.$this->max.' ';
		}elseif($this->min!==null AND $this->getValue()<$this->min){
			$this->error='Minimale waarde is '.$this->min.' ';
		}
		return $this->error=='';
	}
}
class NickField extends FormField{
	public $max_len=20;
	public function valid($lid){
		if(!parent::valid()){ return false; }
		if($this->getValue()==''){ return true; }
		
		if(!is_utf8($this->getValue())) {
			$this->error='Ongeldige karakters, gebruik reguliere tekst.';
		}elseif(mb_strlen($this->getValue()) > $this->max_len){
			$this->error='Gebruik maximaal '.$this->max_len.' karakters.';
		# 2e check met strtolower is toegevoegd omdat je anders je eigen nick niet van case kan veranderen
		# omdat this->nickExists in mysql case-insensitive zoek
		}elseif(strtolower($lid->getNickname())!=strtolower($this->getValue()) AND Lid::nickExists($this->getValue())) {
			$this->error='Deze bijnaam is al in gebruik.';
		}
		return $this->error=='';
	}
}
class TelefoonField extends InputField{
	public function valid(){
		if(!parent::valid()){ return false; }
		if($this->getValue()==''){ return true; }
		if(!preg_match('/^(\d{4}-\d{6}|\d{3}-\d{7}|\d{2}-\d{8}|\+\d{10,20})$/', $this->getValue())){
			$this->error='Geen geldig telefoonnummer.';
		}		

		return $this->error=='';
	}
}
class StudieField extends InputField{
	public function __construct($name, $value, $description){
		parent::__construct($name, $value, $description, 100);
		$this->setSuggestions(array('TUDelft - BK', 'TUDelft - CT', 'TUDelft - ET', 'TUDelft - IO', 'TUDelft - LST', 'TUDelft - LR', 'TUDelft - MT', 'TUDelft - MST', 'TUDelft - TA', 'TUDelft - TB', 'TUDelft - TI', 'TUDelft - TN', 'TUDelft - TW', 'TUDelft - WB'));
	}
}
/*
 * Vreemde eend in de 'bijt', deze unit produceert 3 velden: oud, nieuw en bevestiging.
 */
class PassField extends FormField{
	public function __construct($name){
		$this->name=$name;
	}
	public function isPosted(){
		return isset($_POST[$this->name.'_current'], $_POST[$this->name.'_new'], $_POST[$this->name.'_confirm']);
	}
	public function getValue(){
		if($this->isPosted()){
			return $_POST[$this->name.'_new'];
		}
		return false;
	}
		
	public function valid($lid){
		if(!parent::valid()){ return false; }
		$current=$_POST[$this->name.'_current'];
		$new=$_POST[$this->name.'_new'];
		$confirm=$_POST[$this->name.'_confirm'];
		if($current!=''){
			if(!$lid->checkpw($current)){
				$this->error='Uw huidige wachtwoord is niet juist';
			}else{
				if($new=='' OR $confirm==''){
					$this->error='Vul uw nieuwe wachtwoord twee keer in';
				}elseif($new!=$confirm){
					$this->error='Nieuwe wachtwoorden komen niet overeen';
				}elseif(preg_match('/^[0-9]*$/', $new)) {
		            $this->error='Het nieuwe wachtwoord moet ook letters of leestekens bevatten... :-|';
				}elseif(mb_strlen($new) < 6 OR mb_strlen($new) > 60){
					$this->error='Het wachtwoord moet minimaal 6 en maximaal 16 tekens bevatten';
				}
			}
		}
		if($new!='' AND $current==''){
			$this->error='U dient uw huidige wachtwoord ook in te voeren';
		}
		return $this->error=='';
	}
	public function view(){
		echo $this->getDiv();
		echo '<div class="password">';
		echo $this->getError();
		echo '<label for="field_'.$this->name.'_current">Huidige wachtwoord</label>';
		echo '<input type="password" autocomplete="off" id="field_'.$this->name.'_current" name="'.$this->name.'_current" /></div>';
		echo '<div class="password"><label for="field_'.$this->name.'_new">Nieuw wachtwoord</label>';
		echo '<input type="password" autocomplete="off" id="field_'.$this->name.'_new" name="'.$this->name.'_new" /></div>';
		echo '<div class="password"><label for="field_'.$this->name.'_confirm">Nogmaals</label>';
		echo '<input type="password" autocomplete="off" id="field_'.$this->name.'_confirm" name="'.$this->name.'_confirm" /></div>';
		echo '</div>';
	}
}
class SelectField extends FormField{
	public $options=array();
	
	public function __construct($name, $value, $description=null, $options){
		parent::__construct($name, $value, $description);
		$this->options=$options;
		if(count($this->options)<2){
			throw new Exception('Tenminste twee opties nodig.');
		}
		$this->notnull=true;
	}
	public function valid(){
		if(!parent::valid()){ return false; }
		if(!array_key_exists($this->getValue(), $this->options)){
			$this->error='Onbekende optie gekozen';
		}
		return $this->error=='';
	}
	public function view(){
		echo $this->getDiv();
		echo $this->getLabel();
		echo $this->getError();
		echo '<select id="field_'.$this->name.'" name="'.$this->name.'" />';
		foreach($this->options as $value => $description){
			echo '<option value="'.$value.'"';
			if($value==$this->value){
				echo 'selected="selected" ';
			}
			echo '>'.htmlspecialchars($description).'</option>';
		}
		echo '</select>';
		
		echo '</div>';
	}
}
class GeslachtField extends SelectField{
	public function __construct($name, $value, $description=null){
		parent::__construct($name, $value, $description, array('m'=> 'Man', 'v'=>'Vrouw'));
	}
}
class VerticaleField extends SelectField{
	
	public function __construct($name, $value, $description=null){
		$verticalen=array_merge(array('Geen'), range('A', 'H'));
		parent::__construct($name, $value, $description, $verticalen);
	}
}


class DatumField extends FormField{
	public $maxyear;
	
	public function __construct($name, $value, $description, $maxyear=null){
		parent::__construct($name, $value, $description);
		if($maxyear===null){
			$this->maxyear=date('Y');
		}else{
			$this->maxyear=(int)$maxyear;
		}
	}
	public function isPosted(){
		return isset($_POST[$this->name.'_jaar'], $_POST[$this->name.'_maand'], $_POST[$this->name.'_dag']);
	}
	public function getValue(){
		if($this->isPosted()){
			return $_POST[$this->name.'_jaar'].'-'.$_POST[$this->name.'_maand'].'-'.$_POST[$this->name.'_dag'];
		}
		return false;
	}
	public function valid(){
		if(!parent::valid()){ return false; }
		if(!preg_match('/^(\d{4})-(\d\d?)-(\d\d?)$/', $this->getValue())){
			$this->error='Ongeldige datum';
		}elseif(substr($this->getValue(), 0, 4)>$this->maxyear){
			$this->error='Er kunnen geen data later dan '.$this->maxyear.' worden weergegeven';
		}
		return $this->error=='';
	}
	public function view(){
		echo $this->getDiv();
		echo $this->getLabel();
		echo $this->getError();

		$years=range(1940, $this->maxyear);
		$mounths=range(1,12);
		$days=range(1,31);
		if($this->getValue()=='0000-00-00' OR $this->getValue()==0){
			$years[]='0000';
			$mounths[]=0;
			$days[]=0;
		}
		echo '<select id="field_'.$this->name.'" name="'.$this->name.'_jaar" />';
		foreach($years as $value){
			echo '<option value="'.$value.'"';
			if($value==substr($this->value, 0,4)){
				echo 'selected="selected" ';
			}
			echo '>'.$value.'</option>';
		}
		echo '</select>&nbsp;';
		
		echo '<select id="field_'.$this->name.'" name="'.$this->name.'_maand" />';
		foreach($mounths as $value){
			$value=sprintf('%02d', $value);
			echo '<option value="'.$value.'"';
			if($value==substr($this->value, 5, 2)){
				echo 'selected="selected" ';
			}
			
			echo '>'.$value.'</option>';
		}
		echo '</select>&nbsp;';
		
		echo '<select id="field_'.$this->name.'" name="'.$this->name.'_dag" />';
		foreach($days as $value){
			$value=sprintf('%02d', $value);
			echo '<option value="'.$value.'"';
			if($value==substr($this->value, 8, 2)){
				echo 'selected="selected" ';
			}
			echo '>'.$value.'</option>';
		}
		echo '</select>';
		echo '</div>';
	}
}

/*
 * Commentaardingen voor formulieren
 */

class HTMLComment{
	public $comment;
	public function __construct($comment){
		$this->comment=$comment;
	}
	public function view(){
		echo $this->comment;
	}
}
class UBBComment extends HTMLComment{
	public function view(){
		echo CsrUBB::instance()->getHtml($this->comment);
	}
}
class Comment extends HTMLComment{
	public function view(){
		echo '<h3>'.$this->comment.'</h3>';
	}
}
