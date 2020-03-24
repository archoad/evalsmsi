<?php
	include("functions.php");
	session_set_cookie_params([
		'lifetime' => $cookie_timeout,
		'path' => '/',
		'domain' => $cookie_domain,
		'secure' => $session_secure,
		'httponly' => $cookie_httponly,
		'samesite' => $cookie_samesite
	]);
	session_start();
	$_SESSION['rand'] = genNonce(16);
	$temp = sprintf("Location: evalsmsi.php?rand=%s", $_SESSION['rand']);
	header($temp);
?>
