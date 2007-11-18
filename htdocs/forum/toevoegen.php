<?php
# C.S.R. Delft | pubcie@csrdelft.nl
# -------------------------------------------------------------------
# toevoegen.php
# -------------------------------------------------------------------
# Verwerkt het toevoegen van berichten en ondewerpen in het forum.
# Het formulier bevat: (bericht en topic of title)
# -------------------------------------------------------------------

require_once('include.config.php');

//we laden hier forumonderwerp omdat we in onderwerpen werken.
require_once('class.forumonderwerp.php');
$forum = new ForumOnderwerp();

//als er geen bericht is gaan we sowieso niets doen.
if(!isset($_POST['bericht'])){
	header('location: '.CSR_ROOT.'forum/');
	$_SESSION['melding']='Helaas, er gaat iets goed mis. Er niet eens een bericht.';
	exit;
}

//een nieuw topic toevoegen?
if(!isset($_GET['topic']) AND isset($_GET['forum'])){
	$forum->setCat((int)$_GET['forum']);

	if(strlen(trim($_POST['titel']))<1){
		header('location: '.CSR_ROOT.'forum/');
		$_SESSION['melding']='De titel mag niet leeg zijn.';
		exit;
	}

	//addTopic laadt zelf de boel in die hij net heeft toegevoegd...
	if($forum->addTopic($_POST['titel'])===false){
		header('location: '.CSR_ROOT.'forum/');
		$_SESSION['melding']='Helaas, er gaat iets goed mis bij het toevoegen van het onderwerp.....';
		exit;
	}
}else{
	if($_GET['topic']==(int)$_GET['topic']){
		//niets nieuws toevoegen, het opgegeven onderwerp gebruiken.
		$iTopicID=(int)$_GET['topic'];
		$forum->load($iTopicID);
	}else{
		//kennelijk een brak topicID, dan maar weer terug naar het phorum...
		header('location: '.CSR_ROOT.'forum/');
		$_SESSION['melding']='Helaas, er moet wel een correct onderwerp-nummer opgegeven worden.';
		exit;
	}
}

# er is een onderwerp geselecteerd, nu nog even het bericht er aan toevoegen...
if(!(isset($_POST['submit']) AND $_POST['submit']=='voorbeeld')){
	if($forum->magToevoegen()){
		if(strlen(trim($_POST['bericht']))>0){
			if($forum->addPost($_POST['bericht'])!==false){
				if($forum->needsModeration()){
					header('location: '.CSR_ROOT.'forum/categorie/'.$forum->getCatID());
					$_SESSION['melding']='Uw bericht is verwerkt, het zal binnenkort goedgekeurd worden.';
				}else{
					header('location: '.CSR_ROOT.'forum/onderwerp/'.$forum->getID().'#laatste');
				}
			}else{
				header('location: '.CSR_ROOT.'forum/onderwerp/'.$forum->getID().'#laatste');
				$_SESSION['melding']='Helaas ging er iets mis met het toevoegen van het bericht (forumOnderwerp::addPost()).';
			}
		}else{
			header('location: '.CSR_ROOT.'forum/onderwerp/'.$forum->getID().'#laatste');
			$_SESSION['melding']='Uw bericht is leeg, lege berichten worden niet geaccepteerd.';
		}
	}else{
		header('location: '.CSR_ROOT.'forum/onderwerp/'.$forum->getID().'#laatste');
		$_SESSION['melding']='Hela, volgens mij mag u dit niet... (forumOnderwerp::magToevoegen())';
	}
}else{
	require_once('class.forumonderwerpcontent.php');
	$midden=new Forumonderwerpcontent($forum);
	
	## zijkolom in elkaar jetzen
	$zijkolom=new kolom();
	require_once('class.forumcontent.php');
	$forumcontent=new forumcontent($forum, 'lastposts');
	$zijkolom->add($forumcontent);
	
	$page=new csrdelft($midden);
	$page->setZijkolom($zijkolom);
	$page->view();

}

?>
