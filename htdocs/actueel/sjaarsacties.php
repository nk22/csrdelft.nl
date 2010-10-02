<?php

require_once 'configuratie.include.php';

# Het middenstuk
if ($loginlid->hasPermission('P_LEDEN_READ')) {
	require_once('sjaarsactie.class.php');
	$sjaarsactie = new Sjaarsactie();

	//nieuwe aanmelding voor een bestaande sjaarsactie
	if(isset($_GET['actieID'], $_GET['aanmelden']) AND
		$sjaarsactie->isSjaars() AND !$sjaarsactie->isVol((int)$_GET['actieID'])){
		//nieuw persoon aanmelden voor een actie
		$sjaarsactie->meldAan((int)$_GET['actieID'], $loginlid->getUid());
		header('location: '.CSR_ROOT.'actueel/sjaarsacties/');
		exit;
	}
	//nieuwe sjaarsactie aanmelden
	if(isset($_POST['verzenden']) AND $sjaarsactie->validateSjaarsactie() AND !$sjaarsactie->isSjaars()){
		$sjaarsactie->newSjaarsactie($_POST['actieNaam'], $_POST['beschrijving'], $_POST['limiet']);
		header('location: '.CSR_ROOT.'actueel/sjaarsacties/');
		exit;
	}

	require_once 'sjaarsactiecontent.class.php';
	$midden = new SjaarsactieContent($sjaarsactie);
} else {
	# geen rechten
	require_once 'paginacontent.class.php';
	$pagina=new Pagina('geentoegang');
	$midden = new PaginaContent($pagina);
}

# pagina weergeven
$pagina=new csrdelft($midden);
$pagina->view();

?>
