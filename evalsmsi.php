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
startSession();


function headPageAuth() {
	$cspPolicy = genCspPolicy();
	$_SESSION['rand'] = base64UrlEncode(genNonce(16));
	header("cache-control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Content-type: text/html; charset=utf-8");
	header('X-Content-Type-Options: "nosniff"');
	header("X-XSS-Protection: 1; mode=block");
	header("X-Frame-Options: deny");
	header($cspPolicy);
	ini_set('default_charset', 'UTF-8');
	printf("<!DOCTYPE html><html lang='fr-FR'><head>");
	printf("<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>");
	printf("<link rel='apple-touch-icon' href='pict/logoArchoadApple.png'>");
	printf("<link rel='icon' type='image/png' href='pict/favicon.png'>");
	printf("<link nonce='%s' href='styles/style.%s.css' rel='StyleSheet' type='text/css' media='all'>", $_SESSION['nonce'], $_SESSION['theme']);
	printf("<link nonce='%s' href='styles/style.base.css' rel='StyleSheet' type='text/css' media='all'>", $_SESSION['nonce']);
	printf("<script nonce='%s' src='js/mfa.js'></script>", $_SESSION['nonce']);
	printf("<title>Authentification</title>");
	printf("</head><body>");
}


function footPageAuth() {
	printf("</body></html>");
}


function menuLogin() {
	global $auhtPict;
	genSyslog(__FUNCTION__);
	printf("<div class='authcont'>");
	printf("<div class='auth'>");
	printf("<img src=%s alt='CyberSécurité'>", $auhtPict);
	printf("</div><div class='auth'>");
	printf("<form method='post' id='auth' action='evalsmsi.php?rand=%s'>", $_SESSION['rand']);
	printf("<input type='text' size='20' maxlength='20' name='login' id='login' placeholder='Identifiant' autocomplete='username' autofocus required>");
	printf("<input type='submit' id='valid' value='Continuer'>");
	printf("</form></div></div>");
}


function menuPassword($msg='') {
	global $auhtPict;
	genSyslog(__FUNCTION__);
	printf("<div class='authcont'>");
	printf("<div class='auth'><img src=%s alt='CyberSécurité'></div>", $auhtPict);
	printf("<div class='auth'>");
	if (isset($_SESSION['registration'])) {
		printf("<div class='fido2'>");
		printf("<div><img id='authenticateImg' src='pict/fido2key.png' alt='info'></div>");
		printf("<div><p id='authenticateMsg'></p></div>");
		printf("<div><a class='none' id='endAuthLink' href=''>Continuer</a></div>", $_SESSION['rand']);
		printf("</div>");
		printf("<script nonce='%s'>document.body.addEventListener('load', newAuthentication());</script>", $_SESSION['nonce']);
	} else {
		printf("<form method='post' id='auth' action='evalsmsi.php?rand=%s&action=connect'>", $_SESSION['rand']);
		printf("<input type='password' size='30' maxlength='30' name='password' id='password' placeholder='Mot de passe' autocomplete='current-password' autofocus required>");
		printf("<div id='divcaptcha' class='captcha'>");
		printf("<img src='captcha.php' alt='captcha'/>");
		printf("<input type='text' size='6' maxlength='6' name='captcha' id='captcha' placeholder='Saisir le code' required>");
		printf("</div>");
		printf("<input type='submit' id='valid' value='Connexion'>");
		if ($msg<>'') {
			printf("<div class='help'><img src='pict/help.png' alt='Aide'></div>");
			printf("<p>%s</p>", $msg);
			printf("<a href='aide.php'>(Afficher l'aide en ligne)</a>");
		}
		printf("</form>");
	}
	printf("</div></div>");
}


function getUserData() {
	$base = dbConnect();
	$request = sprintf("SELECT * FROM users WHERE login='%s' LIMIT 1", $_SESSION['login']);
	$result = mysqli_query($base, $request);
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		dbDisconnect($base);
		return $row;
	} else {
		dbDisconnect($base);
		return false;
	}
}


function authentification($password) {
	genSyslog(__FUNCTION__);
	$data = getUserData();
	if ($data) {
		if (($_SESSION['login'] === $data->login) and (password_verify($password, $data->password))) {
			return $data;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function initiateSession($data) {
	genSyslog(__FUNCTION__);
	global $cssTheme, $captchaMode, $sessionDuration;
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
	$_SESSION['expire'] = time() + $sessionDuration;
	if ($data->role === '2') {
		$_SESSION['audit_etab']  = $data->etablissement;
	} else {
		$_SESSION['id_etab'] = $data->etablissement;
	}
}


function initiateNullSession() {
	global $cssTheme, $captchaMode, $sessionDuration;
	session_regenerate_id();
	unset($_SESSION['uid']);
	unset($_SESSION['login']);
	unset($_SESSION['webauthn']);
	unset($_SESSION['sess_captcha']);
	unset($_SESSION['registration']);
	$_SESSION['theme'] = $cssTheme;
	$_SESSION['captchaMode'] = $captchaMode;
	$_SESSION['role'] = '100';
	$_SESSION['uid'] = 'null';
	$_SESSION['curr_script'] = 'evalsmsi.php';
	$_SESSION['expire'] = time() + $sessionDuration;
}


function validateCaptcha($captcha) {
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
			$_SESSION['curr_script'] = 'admin.php';
			header('Location: admin.php');
			break;
		case '2': // Auditeur
			$_SESSION['curr_script'] = 'audit.php';
			header('Location: audit.php');
			break;
		case '3': // Directeur
		case '4': // RSSI
		case '5': // Opérateur SSI
			$_SESSION['curr_script'] = 'etab.php';
			header('Location: etab.php?action=choose_quiz');
			break;
		default:
			destroySession();
			break;
	}
}




if (isset($_GET['rand']) && ($_GET['rand'] === $_SESSION['rand'])) {
	if (isset($_GET['action'])) {
		switch ($_GET['action']) {
			case 'connect':
				if (isset($_SESSION['webauthn'])) { //Webauthn authentication
					if ($_SESSION['webauthn']) {
						$data = getUserData();
						if ($data) {
							redirectUser($data);
						} else {
							destroySession();
						}
					} else {
						destroySession();
					}
				} else { // Password authentication
					if (validateCaptcha($_POST['captcha'])) {
						$data = authentification($_POST['password']);
						if ($data) {
							redirectUser($data);
						} else {
							headPageAuth();
							menuPassword("Erreur d'authentification");
							footPageAuth();
						}
					} else {
						destroySession();
					}
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
		if (isset($_POST['login'])) {
			$_SESSION['login'] = traiteStringToBDD($_POST['login']);
			getCredentialFromDb();
			headPageAuth();
			menuPassword();
			footPageAuth();
		} else {
			initiateNullSession();
			headPageAuth();
			menuLogin();
			footPageAuth();
		}
	}
} else {
	initiateNullSession();
	headPageAuth();
	menuLogin();
	footPageAuth();
}

?>
