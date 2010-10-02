<?php

# login.php


require_once 'configuratie.include.php';


# ok_url en user/pass invoer checken
if (isset($_POST['url']) and preg_match("/^[-\w?&=.\/]+$/", $_POST['url'])
	and isset($_POST['user']) and isset($_POST['pass'])) {

	$checkip = isset($_POST['checkip']) and $_POST['checkip'] == 'true';

	if ($loginlid->login(strval($_POST['user']), strval($_POST['pass']), $checkip)) {
		header("Location: ". CSR_SERVER . $_POST['url']);
	} else {
		$_SESSION['auth_error'] = "Login gefaald!";
		header("Location: ". CSR_SERVER . $_POST['url']);
	}
}

?>
