<?php
require_once 'configuratie.include.php';

require_once 'model/CourantModel.class.php';
require_once 'view/courant/CourantView.class.php';
$courant = new CourantModel();

//niet verzenden bij geen rechten, en niet bij een lege courant.
if (!$courant->magVerzenden()) {
	setMelding('U heeft geen rechten om de courant te verzenden.', -1);
	redirect(CSR_ROOT . '/courant/');
	exit;
} elseif ($courant->getBerichtenCount() < 1) {
	setMelding('Lege courant kan niet worden verzonden', 0);
	redirect(CSR_ROOT . '/courant/');
	exit;
}

$mail = new CourantView($courant);

if (isset($_GET['iedereen'])) {
	$mail->zend('csrmail@lists.knorrie.org');
	$courant->leegCache();
} else {
	$mail->zend('pubcie@csrdelft.nl');
}
?><a href="verzenden.php?iedereen=true"> aan iedereen verzenden</a>
