<?php
/*=========================================================
// File:        evalsmsi.php
// Description: main page and authentication process of EvalSMSI
// Created:     2009-01-01
// Licence:     GPL-3.0-or-later
// Copyright 2009-2019 Michel Dubois

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
=========================================================*/

include("functions.php");


function headPageAuth() {
	genSyslog(__FUNCTION__);
	$cspPolicy = genCspPolicy();
	set_var_utf8();
	header("cache-control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Content-type: text/html; charset=utf-8");
	header('X-Content-Type-Options: "nosniff"');
	header("X-XSS-Protection: 1; mode=block");
	header("X-Frame-Options: deny");
	header($cspPolicy);
	printf("<!DOCTYPE html><html lang='fr-FR'><head>");
	printf("<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />");
	printf("<link rel='icon' type='image/png' href='pict/favicon.png' />");
	printf("<link nonce='%s' href='styles/style.%s.css' rel='StyleSheet' type='text/css' media='all' />", $_SESSION['nonce'], $_SESSION['theme']);
	printf("<link nonce='%s' href='styles/style.base.css' rel='StyleSheet' type='text/css' media='all' />", $_SESSION['nonce']);
	printf("<title>Authentification</title>");
	printf("</head><body>");
}


function footPageAuth() {
	printf("</body></html>");
}


function menuAuth($msg='') {
	global $auhtPict;
	genSyslog(__FUNCTION__);
	initiateNullSession();
	headPageAuth();
	$_SESSION['rand'] = genNonce(16);
	printf("<div class='authcont'>");
	printf("<div class='auth'>");
	printf("<img src=%s alt='CyberSécurité' />", $auhtPict);
	printf("</div><div class='auth'>");
	printf("<form method='post' id='auth' action='evalsmsi.php?rand=%s&action=connect'>", $_SESSION['rand']);
	printf("<input type='text' size='20' maxlength='20' name='login' id='login' placeholder='Identifiant' autocomplete='username' required />");
	printf("<input type='password' size='20' maxlength='20' name='password' id='password' placeholder='Mot de passe' autocomplete='current-password' required />");
	printf("<div class='captcha'>");
	printf("<img src='captcha.php' alt='captcha'/>");
	printf("<input type='text' size='6' maxlength='6' name='captcha' id='captcha' placeholder='Saisir le code' required />");
	printf("</div>");
	printf("<input type='submit' id='valid' value='Connexion' />");
	if ($msg<>'') {
		printf("<div class='help'><img src='pict/help.png' alt='Aide' /></div>");
		printf("<p>%s</p>", $msg);
		printf("<a href='aide.php'>(Afficher l'aide en ligne)</a>");
	}
	printf("</form></div></div>");
	footPageAuth();
}


function authentification($login, $password) {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM users WHERE login='%s' LIMIT 1", $login);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	if (isset($row)) {
		if (($login === $row->login) and (password_verify($password, $row->password))) {
			return $row;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function initiateSession($data) {
	genSyslog(__FUNCTION__);
	global $cssTheme, $captchaMode;
	session_regenerate_id();
	date_default_timezone_set('Europe/Paris');
	$date = getdate();
	$annee = $date['year'];
	$_SESSION['theme'] = $cssTheme;
	$_SESSION['captchaMode'] = $captchaMode;
	$_SESSION['day'] = mb_strtolower(strftime("%A %d %B %Y", time()));
	$_SESSION['hour'] = mb_strtolower(strftime("%H:%M", time()));
	$_SESSION['os'] = detectOS();
	$_SESSION['browser'] = detectBrowser();
	$_SESSION['ipaddr'] = detectIP();
	$_SESSION['uid'] = $data->id;
	$_SESSION['nom'] = $data->nom;
	$_SESSION['prenom'] = $data->prenom;
	$_SESSION['role'] = $data->role;
	$_SESSION['login'] = $data->login;
	$_SESSION['annee'] = $annee;
	if ($data->role === '2') {
		$_SESSION['audit_etab']  = $data->etablissement;
	} else {
		$_SESSION['id_etab'] = $data->etablissement;
	}
}


function initiateNullSession() {
	genSyslog(__FUNCTION__);
	global $cssTheme, $captchaMode;
	session_regenerate_id();
	$_SESSION['theme'] = $cssTheme;
	$_SESSION['captchaMode'] = $captchaMode;
	$_SESSION['role'] = '100';
	$_SESSION['uid'] = 'null';
}


function validateCaptcha($captcha) {
	genSyslog(__FUNCTION__);
	if (strncmp($_SESSION['sess_captcha'], $captcha, 6) === 0) {
		return true;
	} else {
		return false;
	}
}


function redirectUser($data) {
	global $appli_titre;
	genSyslog(__FUNCTION__);
	initiateSession($data);
	if(isset($_SESSION['sess_captcha'])) {
		unset($_SESSION['sess_captcha']);
	}
	switch ($_SESSION['role']) {
		case '1': // Administrateur
			header('Location: admin.php');
			break;
		case '2': // Auditeur
			header('Location: audit.php');
			break;
		case '3': // Directeur
		case '4': // RSSI
		case '5': // Opérateur SSI
			header('Location: etab.php');
			break;
		default:
			destroySession();
			break;
	}
}


session_set_cookie_params([
	'lifetime' => $cookie_timeout,
	'path' => '/',
	'domain' => $cookie_domain,
	'secure' => $session_secure,
	'httponly' => $cookie_httponly,
	'samesite' => $cookie_samesite
]);
session_start();
if (isset($_GET['rand']) && ($_GET['rand'] === $_SESSION['rand'])) {
	if (isset($_GET['action'])) {
		switch ($_GET['action']) {
			case 'connect':
				if (validateCaptcha($_POST['captcha'])) {
					$data = authentification($_POST['login'], $_POST['password']);
					if ($data) {
						redirectUser($data);
					} else {
						menuAuth("Erreur d'authentification");
						exit();
					}
				} else {
					destroySession();
				}
				break;
			case 'disconnect':
				destroySession();
				break;
			default:
				destroySession();
				break;
		}
	} else {
		menuAuth();
		exit;
	}
} else {
	menuAuth();
	exit;
}

?>
