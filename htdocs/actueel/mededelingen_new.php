<?php
require_once('include.config.php');

if(!LoginLid::instance()->hasPermission('P_NEWS_MOD')){
	header('location: '.CSR_ROOT);
	exit;
}

$mededelingId=0;
if(isset($_GET['mededelingId'])){
	$mededelingId=(int)$_GET['mededelingId'];
}

$actie='default';
if(isset($_GET['actie'])){
	$actie=$_GET['actie'];
}

require_once('mededelingen/class.mededeling.php');
require_once('mededelingen/class.mededelingcontent.php');

switch($actie){
	case 'verwijderen':
		if($mededelingId>0){
			$mededeling=new Mededeling($mededelingId);
			$mededeling->delete();
		}
		$content=new MededelingenContent();
		// TODO: refreshen.
	break; 

	case 'bewerken':
		echo '<pre>'.print_r($_POST, true).'</pre>';
		echo '<pre>'.print_r($_FILES, true).'</pre>';
		$_SESSION['melding']='';
		if(	isset($_POST['titel'],$_POST['tekst'],$_POST['categorie'],$_POST['rank']) )
		{	// Edit an existing Mededeling.
			// Get properties from $_POST.
			$mededelingProperties=array();
			$mededelingProperties['id']=		$mededelingId;
			$mededelingProperties['titel']=		$_POST['titel'];
			$mededelingProperties['tekst']=		$_POST['tekst'];
			$mededelingProperties['datum']=		getDateTime();
			$mededelingProperties['uid']=		LoginLid::instance()->getUid();
			$mededelingProperties['rank']=		(int)$_POST['rank'];
			$mededelingProperties['prive']=		isset($_POST['prive']) ? 1 : 0;
			$mededelingProperties['verborgen']=	isset($_POST['verborgen']) ? 1 : 0;
			$mededelingProperties['categorie']=	(int)$_POST['categorie'];

			// Special treatment for the picture.
			$mededelingProperties['plaatje']='';
			if(isset($_FILES['plaatje']) AND $_FILES['plaatje']['name']!=''){
				$info=getimagesize($_FILES['plaatje']['tmp_name']);
				// Check the ratio.
				if(($info[0]/$info[1])==1){
					$pictureFilename=$_FILES['plaatje']['name'];
					$pictureFullPath=PICS_PATH.'/nieuws/'.$pictureFilename;
					if( move_uploaded_file($_FILES['plaatje']['tmp_name'], $pictureFullPath)!==false ){
						$mededelingProperties['plaatje']=$pictureFilename;
						if($info[0]!=200){ // Too big, resize it.
							resize_plaatje($pictureFullPath);
						}
						chmod($pictureFullPath, 0644);
					}else{
						$_SESSION['melding'].='Plaatje verplaatsen is mislukt.<br />';
					}
				}else{
					$_SESSION['melding'].='Plaatje is niet in de juiste verhouding.<br />';
				}
			}
			
			// Create a Mededeling so we can use getRanks() and getCategorie() below.
			$mededeling=new Mededeling($mededelingId);

			// Check if all values appear to be OK.
			$allOK=true;
			if(strlen($mededelingProperties['titel'])<2){
				$_SESSION['melding'].='Het veld <b>Titel</b> moet minstens 2 tekens bevatten.<br />';
				$allOK=false;
			}
			if(strlen($mededelingProperties['tekst'])<5){
				$_SESSION['melding'].='Het veld <b>Tekst</b> moet minstens 5 tekens bevatten.<br />';
				$allOK=false;
			}
			if(	$mededelingProperties['rank']<1 OR
				array_search($mededelingProperties['rank'],array_keys($mededeling->getRanks()))!==false )
			{
				$mededelingProperties['rank']=0;
			}
			// Check categorie.
			$categorieValid=false;
			foreach($mededeling->getCategorie()->getAll() as $categorie){
				if($mededelingProperties['categorie']==$categorie->getId())
					$categorieValid=true;
			}
			if(	!$categorieValid ){
				$mededelingProperties['categorie']=null;
			}
			if(isset($_FILES['plaatje']['error'])){
				// There's a picture missing.
				$errorNumber=$_FILES['plaatje']['error'];
				if($mededelingId==0 AND $errorNumber==4){
					$_SESSION['melding'].='Het toevoegen van een plaatje is verplicht.<br />';
					$allOK=false;
				}else if($errorNumber!=UPLOAD_ERR_OK AND $errorNumber!=UPLOAD_ERR_NO_FILE AND $mededelingProperties['plaatje']==''){
					// Uploading the picture failed.
					$allOK=false;
				}
			}else{ // The picture-field did not exist at all.
				$_SESSION['melding'].='Het toevoegen van een plaatje is verplicht.<br />';
				$allOK=false;
			}
			
			// Overwrite old Mededeling with one that contains the (maybe) corrected data.
			$mededeling=new Mededeling($mededelingProperties);
			echo '<pre>'.print_r($mededelingProperties, true).'</pre>';
			// Save the mededeling to the database. (Either via UPDATE or INSERT).
			if($allOK){
				$mededeling->save();
				//TODO: Melding weergeven dat er iets toegevoegd is (?)
				//TODO: Mededeling-pagina laden.
			}
		}else{ // Edit an existing Mededeling or display an empty form.
			$mededeling=new Mededeling($mededelingId);
		}
		$content=new MededelingContent($mededeling);
	break; 

	default:
		$content=new MededelingenContent();
	break;
}

$page=new csrdelft($content);
$page->view();

function resize_plaatje($file) {
	list($owdt,$ohgt,$otype)=@getimagesize($file);
	switch($otype) {
		case 1:  $oldimg=imagecreatefromgif($file); break;
		case 2:  $oldimg=imagecreatefromjpeg($file); break;
		case 3:  $oldimg=imagecreatefrompng($file); break;
	}
	if($oldimg) {
		$newimg=imagecreatetruecolor(200, 200);
		if(imagecopyresampled($newimg, $oldimg, 0, 0, 0, 0, 200, 200, $owdt, $ohgt)){
			switch($otype) {
				case 1: imagegif($newimg,$file); break;
				case 2: imagejpeg($newimg,$file,90); break;
				case 3: imagepng($newimg,$file);  break;
			}
			imagedestroy($newimg);
		}else{
			//mislukt
		}
	}
}
?>