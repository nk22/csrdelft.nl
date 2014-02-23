<?php
/*
 * zoeken.php	| 	C.S.R. Delft
 *
 * Zoeken in het csrdelft.nl-forum
 */

require_once 'configuratie.include.php';

if($loginlid->hasPermission('P_FORUM_READ')) {
	require_once 'forum/forumcontent.class.php';
	$midden = new ForumContent('zoeken');
} else {
	# geen rechten
	require_once 'paginacontent.class.php';
	$pagina=new CmsPagina('geentoegang');
	$midden = new CmsPaginaView($pagina);
}

# pagina weergeven
if(LoginLid::instance()->hasPermission('P_LEDEN_READ')){
	$pagina=new csrdelft($midden);
}
else {
	//uitgelogd heeft nieuwe layout
	$pagina=new csrdelft($midden, 'csrdelft2');
}
$pagina->addStylesheet('forum.css');
$pagina->view();
