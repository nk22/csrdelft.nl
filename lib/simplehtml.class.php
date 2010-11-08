<?php
# C.S.R. Delft | pubcie@csrdelft.nl
# -------------------------------------------------------------------
# class.simplehtml.php
# -------------------------------------------------------------------
# Van deze klasse worden alle klassen afgeleid die ervoor
# bedoeld zijn om uiteindelijk HTML uit te kotsen
# -------------------------------------------------------------------


abstract class SimpleHTML {

	private $_sMelding='';
	//html voor een pagina uitpoepen.
	public function view() {

	}
	public function getMelding(){
		if(isset($_SESSION['melding']) AND trim($_SESSION['melding'])!=''){
			$sError='<div id="melding">'.trim($_SESSION['melding']).'</div>';
			//maar één keer tonen, de melding.
			unset($_SESSION['melding']);
			return $sError;
		}elseif($this->_sMelding!=''){
			return '<div id="melding">'.$this->_sMelding.'</div>';
		}else{
			return '';
		}
	}
	public function setMelding($sMelding){
		$this->_sMelding.=trim($sMelding);
	}
	public static function invokeRefresh($sMelding, $url=null){
		if($sMelding!=''){
			$_SESSION['melding']=$sMelding;
		}
		if($url==null){
			$url=CSR_ROOT.$_SERVER['REQUEST_URI'];
		}
		header('location: '.$url);
		exit;
	}

	//eventueel titel voor een pagina geven
	function getTitel($sTitle=false){
		if($sTitle===false){
			return 'C.S.R. Delft';
		}else{
			return 'C.S.R. Delft - '.$sTitle;
		}
	}
}

class StringIncluder extends SimpleHTML{
	public $string='lege pagina';
	public $title;
	public function __construct($string, $title=''){
		$this->string=$string;
		$this->title=$title;
	}
	function getTitel(){ return $this->title; }
	function view(){
		echo $this->string;
	}
}
class string2object{
	private $_string;
	function string2object($string){
		$this->_string=$string;
	}
	function view(){
		echo $this->_string;
	}
}

class Kolom extends SimpleHTML {

	# Een object is een van SimpleHTML afgeleid object waarin een
	# stuk pagina zit, wat we er met view() uit kunnen krijgen.
	var $_objects = array();

	public function __construct(){

	}

	public function addObject($object){ $this->_objects[]=$object; }
	public function addTekst($string){ $this->addObject(new string2object($string)); }
	# Alias voor addObject
	public function add($object){ $this->addObject($object); }

	public function getTitel(){
		if(isset($this->_objects[0])){
			return $this->_objects[0]->getTitel();
		}
	}
	private function defaultView(){

			# ishetalvrijdag
			if(Instelling::get('zijbalk_ishetalvrijdag')=='ja'){
				echo '<div id="ishetalvrijdag">Is het al vrijdag?<br />';
				if(date('w')==5){
					echo '<div class="ja">JA!</div>';
				}else{
					echo '<div class="nee">NEE.</div>';
				}
				echo '</div><br />';
			}
			# Ga snel naar
			if(Instelling::get('zijbalk_gasnelnaar')=='ja'){
				require_once('menu.class.php');
				$this->add(new stringincluder(Menu::getGaSnelNaar()));
			}

			# Agenda
			if(LoginLid::instance()->hasPermission('P_AGENDA_READ')){
				if(Instelling::get('zijbalk_agendaweken')>0){
					require_once('agenda/agenda.class.php');
					require_once('agenda/agendacontent.class.php');
					$agenda=new Agenda();
					$agendacontent=new AgendaZijbalkContent($agenda, Instelling::get('zijbalk_agendaweken'));
					$this->add($agendacontent);
				}
			}

			# Laatste mededelingen
			if(Instelling::get('zijbalk_mededelingen')>0){
				require_once('mededelingen/mededeling.class.php');
				require_once('mededelingen/mededelingencontent.class.php');
				$content=new MededelingenZijbalkContent(Instelling::get('zijbalk_mededelingen'));
				$this->add($content);
			}

			# Laatste forumberichten
			if(Instelling::get('zijbalk_forum')>0){
				require_once 'forum/forumcontent.class.php';
				$forumcontent=new ForumContent('lastposts');
				$this->add($forumcontent);
			}
			if(Instelling::get('zijbalk_forum_zelf')>0){
				require_once 'forum/forumcontent.class.php';
				$forumcontent=new ForumContent('lastposts_zelf');
				$this->add($forumcontent);
			}

			# Komende 10 verjaardagen
			if(Instelling::get('zijbalk_verjaardagen')>0){
				require_once 'lid/verjaardagcontent.class.php';
				$this->add(new VerjaardagContent('komende'));
			}
	}
	public function view() {
		# Als er geen balk is laten we de standaard-inhoud zien
		if (count($this->_objects)==0){
			$this->defaultView();
		}

		foreach ($this->_objects as $object) {
			$object->view();
			echo '<br />';
		}
	}
}


?>
