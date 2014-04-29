<?php

require_once 'configuratie.include.php';

if (isset($_GET['exit'])) {
	unset($_SESSION['pauper']);
	header("Location: " . CSR_ROOT);
	exit;
}

$_SESSION['pauper'] = true;

require_once 'MVC/model/CmsPaginaModel.class.php';
require_once 'MVC/view/CmsPaginaView.class.php';
$body = new CmsPaginaView(CmsPaginaModel::instance()->getPagina('mobiel'));

$pagina = new CsrLayoutPage($body);
$pagina->view();

// als er een error is geweest, die unsetten...
if (isset($_SESSION['auth_error'])) {
	unset($_SESSION['auth_error']);
}