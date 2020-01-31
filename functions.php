<?php
/*=========================================================
// File:        functions.php
// Description: global functions of EvalSMSI
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




// --------------------
// Définition des variables de base
// Nom de la machine hébergeant le serveur MySQL
$servername = 'localhost';
// Nom de la base de données
$dbname = 'evalsmsi';
// Nom de l'utilisateur autorisé à se connecter sur la BDD
$login = 'web';
// Mot de passe de connexion
$passwd = 'webphpsql';
// Titre de l'application
//$appli_titre = ("Evaluation du Système de Management de la Sécurité de l'Information");
$appli_titre = ("Evaluation du SMSI");
$appli_titre_short = ("EvalSMSI");
// Thème CSS
$cssTheme = 'standard'; // 'laposte' , 'standard'
// Image accueil
$auhtPict = 'pict/accueil.png'; // 'pict/accueil.png', ''pict/auditics.png';'
// Mode captcha
$captchaMode = 'num'; // 'txt' or 'num'
// --------------------




// --------------------
// Définition des variables internes à l'application
// Ne pas modifier ces variables !
date_default_timezone_set('Europe/Paris');
setlocale(LC_ALL, 'fr_FR.utf8');

//ini_set('display_errors', 0);
//ini_set('error_reporting', 0);
ini_set('error_reporting', E_ALL);
ini_set('session.use_trans_sid', 0);
ini_set('session.use_cookie', 1);
//ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cache_limiter', 'nocache');
ini_set('session.gc_probability', 1);
ini_set('session.gc_maxlifetime', 1800); // 30 min
ini_set('session.cookie_httponly', 1);
ini_set('session.entropy_length', 32);
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.hash_function', 'sha256');
ini_set('filter.default', 'full_special_chars');
ini_set('filter.default_flags', 0);

$noteMax = 7;

$server_path = dirname($_SERVER['SCRIPT_FILENAME']);
$cheminRAP = sprintf("%s/rapports/", $server_path);
$cheminDATA = sprintf("%s/data/", $server_path);

require_once ('phpoffice/bootstrap.php');

use Dompdf\Dompdf;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\DocProtect;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Settings::loadConfig();
Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, 'phpoffice/vendor/dompdf/dompdf');

$largeur=798; // Largeur graphe
$hauteur=532; // Hauteur graphe
$txtGraph = "Evaluation du SMSI - (c)2020 Michel Dubois";

$colors = array('darkslateblue', 'darkorange', 'darkorchid', 'bisque4', 'aquamarine4', 'azure4', 'brown', 'cadetblue', 'chartreuse', 'chocolate', 'coral', 'cornflowerblue', 'darkgoldenrod', 'darkmagenta', 'darkolivegreen4', 'darksalmon', 'darkseagreen', 'darkslateblue', 'darkturquoise', 'deeppink', 'deepskyblue', 'goldenrod', 'indianred');
// --------------------


function menuAdmin() {
	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	linkMsg("admin.php?action=add_par", "Ajouter un domaine", "add_par.png", 'menu');
	linkMsg("admin.php?action=add_sub_par", "Ajouter un sous-domaine", "add_sub_par.png", 'menu');
	linkMsg("admin.php?action=add_quest", "Ajouter une question", "add_question.png", 'menu');
	linkMsg("admin.php?action=modifications", "Modifications du questionnaire", "eval_continue.png", 'menu');
	linkMsg("admin.php?action=maintenance", "Maintenance de la Base de Données", "bdd.png", 'menu');
	printf("</div>\n<div class='column right'>\n");
	linkMsg("admin.php?action=new_etab", "Créer un établissement", "add_etab.png", 'menu');
	linkMsg("admin.php?action=select_etab", "Modifier un établissement", "modif_etab.png", 'menu');
	linkMsg("admin.php?action=new_regroup", "Créer un établissement de regroupement", "add_regroup.png", 'menu');
	linkMsg("admin.php?action=new_user", "Ajouter un utilisateur", "add_user.png", 'menu');
	linkMsg("admin.php?action=select_user", "Modifier un utilisateur", "modif_user.png", 'menu');
	printf("</div>\n</div>");
}


function menuEtab() {
	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	linkMsg("etab.php?action=continue_assess", "Réaliser une évaluation", "eval_continue.png", 'menu');
	linkMsg("etab.php?action=print", "Imprimer les rapports et plans d'actions", "print.png", 'menu');
	linkMsg("etab.php?action=password", "Changer de mot de passe", "cadenas.png", 'menu');
	linkMsg("aide.php", "Aide et documentation", "help.png", 'menu');
	printf("</div><div class='column right'>\n");
	linkMsg("etab.php?action=graph", "Graphes établissement", "piechart.png", 'menu');
	linkMsg("etab.php?action=office", "Exporter l'évaluation", "docx.png", 'menu');
	linkMsg("etab.php?action=rules", "Exporter le référentiel", "pdf.png", 'menu');
	printf("</div>\n</div>");
}


function menuAudit() {
	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	linkMsg("audit.php?action=office", "Exporter une évaluation", "docx.png", 'menu');
	linkMsg("audit.php?action=graph", "Graphes par établissement", "piechart.png", 'menu');
	linkMsg("audit.php?action=objectif", "Gestion des objectifs", "objectifs.png", 'menu');
	linkMsg("audit.php?action=journal", "Journalisation", "journal.png", 'menu');
	printf("</div><div class='column right'>\n");
	linkMsg("audit.php?action=audit", "Evaluation auditeur", "audit.png", 'menu');
	linkMsg("audit.php?action=rap_etab", "Rapport par établissement", "rapport.png", 'menu');
	linkMsg("audit.php?action=delete", "Supprimer une évaluation", "remove.png", 'menu');
	linkMsg("audit.php?action=password", "Changer de mot de passe", "cadenas.png", 'menu');
	printf("</div>\n</div>");
}


function sanitizePhpSelf($phpself) {
	$phpself = trim($phpself);
	$phpself = htmlspecialchars($phpself, ENT_QUOTES, 'UTF-8');
	$phpself = basename($phpself);
	$valid = array('admin.php', 'aide.php', 'audit.php', 'etab.php', 'evalsmsi.php');
	if (in_array($phpself, $valid)) {
		return $phpself;
	} else {
			$path = dirname($_SERVER['SCRIPT_NAME']);
			destroySession();
			header("Location: ".$path."/evalsmsi.php");
	}
}


function dbConnect(){
	global $servername, $dbname, $login, $passwd;
	$dbh = mysqli_connect($servername, $login, $passwd) or die("Problème de connexion");
	mysqli_select_db($dbh, $dbname) or die("problème avec la table");
	mysqli_set_charset($dbh , 'utf8');
	return $dbh;
}


function dbDisconnect($dbh){
	mysqli_close($dbh);
	$dbh=0;
}


function destroySession() {
	session_destroy();
	unset($_SESSION);
	header('Location: evalsmsi.php');
}


function isSessionValid($role) {
	if (!isset($_SESSION['uid']) OR (!in_array($_SESSION['role'], $role))) {
		destroySession();
		exit();
	}
}


function infoSession() {
	$infoDay = sprintf("%s - %s", $_SESSION['day'], $_SESSION['hour']);
	$infoNav = sprintf("%s - %s - %s", $_SESSION['os'], $_SESSION['browser'], $_SESSION['ipaddr']);
	$infoUser = sprintf("Connecté en tant que <b>%s %s</b>", $_SESSION['prenom'], $_SESSION['nom']);
	$logoff = sprintf("<a href='evalsmsi.php?action=disconnect'>Déconnexion&nbsp;<img border='0' alt='logoff' src='pict/turnoff.png' width='10'></a>");
	return sprintf("Powered by EvalSMSI - %s - %s - %s - %s", $infoDay, $infoNav, $infoUser, $logoff);
}


function detectIP() {
	if (isset($_SERVER)) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$usedIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$usedIP = $_SERVER['HTTP_CLIENT_IP'];
		} else {
			$usedIP = $_SERVER['REMOTE_ADDR'];
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR'))
			$usedIP = getenv('HTTP_X_FORWARDED_FOR');
		elseif (getenv('HTTP_CLIENT_IP'))
			$usedIP = getenv('HTTP_CLIENT_IP');
		else
			$usedIP = getenv('REMOTE_ADDR');
	}
	return $usedIP;
}


function detectBrowser() {
	$BrowserList = array (
		'Firefox' => '/Firefox/',
		'Chrome' => '/Chrome/',
		'Opera' => '/Opera/',
		'Safari' => '/Safari/',
		'Internet Explorer V6' => '/MSIE 6/',
		'Internet Explorer V7' => '/MSIE 7/',
		'Internet Explorer V8' => '/MSIE 8/',
		'Internet Explorer' => '/MSIE/'
	);
	foreach($BrowserList as $CurrBrowser=>$Match) {
		if (preg_match($Match, $_SERVER['HTTP_USER_AGENT'])) {
			break;
		}
	}
	return $CurrBrowser;
}


function detectOS() {
	$txt = $_SERVER['HTTP_USER_AGENT'];
	$OSList = array (
		'Windows 3.11' => '/Win16/',
		'Windows 95' => '/(Windows 95)|(Win95)|(Windows_95)/',
		'Windows 98' => '/(Windows 98)|(Win98)/',
		'Windows 2000' => '/(Windows NT 5.0)|(Windows 2000)/',
 		'Windows XP' => '/(Windows NT 5.1)|(Windows XP)/',
		'Windows Server 2003' => '/(Windows NT 5.2)/',
		'Windows Vista' => '/(Windows NT 6.0)/',
		'Windows 7' => '/(Windows NT 6.1)/',
		'Windows NT 4.0' => '/(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)/',
		'Windows ME' => '/Windows ME/',
		'Open BSD' => '/OpenBSD/',
		'Sun OS' => '/SunOS/',
		'iOS' => '/(iPhone)|(iPad)/',
		'Android' => '/(Android)/',
		'Linux' => '/(Linux)|(X11)/',
		'Mac OSX' => '/(Mac_PowerPC)|(Macintosh)/',
		'QNX' => '/QNX/',
		'BeOS' => '/BeOS/',
		'Search Bot'=>'/(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)/'
	);
	foreach($OSList as $currOS=>$match) {
		if (preg_match($match, $txt)) {
			break;
		}
	}
	return $currOS;
}


function set_var_utf8(){
	ini_set('mbstring.internal_encoding', 'UTF-8');
	ini_set('mbstring.http_input', 'UTF-8');
	ini_set('mbstring.http_output', 'UTF-8');
	ini_set('mbstring.detect_order', 'auto');
}


function get_var_utf8(){
	$param1 = ini_get('mbstring.internal_encoding');
	$param2 = ini_get('mbstring.http_input');
	$param3 = ini_get('mbstring.http_output');
	$param4 = ini_get('mbstring.detect_order');
	printf("<b>%s %s %s %s</b>", $param1, $param2, $param3, $param4);
}


function headPage($titre, $sousTitre=''){
	set_var_utf8();
	header("cache-control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Content-type: text/html; charset=utf-8");
	header("X-XSS-Protection: 1; mode=block");
	header("X-Frame-Options: deny");
	printf("<!DOCTYPE html>\n<html lang='fr-FR'>\n<head>\n");
	printf("<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n");
	printf("<meta name='author' content='Michel Dubois' />\n");
	printf("<link rel='icon' type='image/png' href='pict/favicon.png' />\n");
	printf("<link href='js/chart.min.css' rel='stylesheet' type='text/css' media='all' />\n");
	printf("<link href='js/vis.min.css' rel='stylesheet' type='text/css' media='all' />\n");
	printf("<link href='styles.php' rel='StyleSheet' type='text/css' media='all' />\n");
	printf("<title>%s</title>\n", $titre);
	printf("</head>\n<body>\n<h1>%s</h1>\n", $titre);
	if ($sousTitre !== '') {
		printf("<h2>%s</h2>\n", $sousTitre);
	} else {
		printf("<h2>%s</h2>\n", uidToEtbs());
	}
}


function footPage($link='', $msg=''){
	if ($_SESSION['role']==='100') {
		printf("<div class='footer'>\n");
		printf("Aide en ligne - Retour à la page d'accueil <a href='evalsmsi.php' class='btnWarning'>cliquer ici</a>\n");
		printf("</div>\n");
		printf("</body>\n</html>\n");
	} else {
		if (strlen($link) AND strlen($msg)) {
			printf("<div class='foot'>\n");
			printf("<a href='%s'>%s</a>\n", $link, $msg);
			printf("</div>\n");
			printf("<p>&nbsp;</p>");
		}
		printf("<div class='footer'>\n");
		printf("%s\n", infoSession());
		printf("</div>\n");
		printf("</body>\n</html>\n");
	}
}


function validForms($msg, $url, $back=True) {
	$_SESSION['token'] = generateToken();
	printf("<fieldset>\n<legend>Validation</legend>\n");
	printf("<table><tr><td>\n");
	printf("<input type='submit' value='%s' />\n", $msg);
	if ($back) {
		printf("<input type='reset' value='Effacer' />\n");
	}
	printf("<a class='valid' href='%s?action=rm_token'>Revenir</a>\n", $url);
	printf("</td></tr>\n</table>\n</fieldset>\n");
}


function linkMsg($link, $msg, $img, $class='msg') {
	printf("<div class='%s'>\n", $class);
	printf("<div><img src='pict/%s' alt='info' /></div>\n", $img);
	if ($link==='#') {
		printf("<div><p>%s</p></div>\n", $msg);
	} else {
		printf("<div><a href='%s'>%s</a></div>\n", $link, $msg);
	}
	printf("</div>\n");
}


function recordLog() {
	$base = dbConnect();
	$etablissement = $_SESSION['id_etab'];
	$request = sprintf("SELECT reponses FROM assess WHERE (annee='%s' AND etablissement='%d') LIMIT 1", $_SESSION['annee'], $etablissement);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$rep = unserialize($row->reponses);
	if (empty($rep)) {
		$tabdiff = array();
		foreach($_POST as $key => $val) {
			if (($val != '') && ($val != 0))
				$tabdiff[$key]=$val;
		}
	} else {
		$tabdiff = array_diff_assoc($_POST, $rep);
	}
	$tabstr = mysqli_real_escape_string($base, serialize($tabdiff));
	$request=sprintf("INSERT INTO journal (ip, etablissement, navigateur, os, user, action) VALUES ('%s', '%d', '%s', '%s', '%s', '%s')", $_SESSION['ipaddr'], $etablissement, $_SESSION['browser'], $_SESSION['os'], $_SESSION['login'], $tabstr);
	mysqli_query($base, $request);
	dbDisconnect($base);
}


function traiteStringToBDD($str) {
	$str = str_split($str);
	$temp = '';
	for($i=0; $i<count($str); $i++) {
		switch ($str[$i]) {
			case '+':
			case '=':
			case '|':
				$temp .= ' ';
				break;
			default:
				$temp .= $str[$i];
				break;
		}
	}
	$temp = str_split($temp);
	$output = '';
	for($i=0; $i<count($temp); $i++) {
		if (isset($temp[$i+1])) {
			$chrNum = sprintf("%d%d", ord($temp[$i]), ord($temp[$i+1]));
			switch ($chrNum) {
				case '4039': // remove ('
				case '3941': // remove ')
				case '4041': // remove ()
				case '4747': // remove //
					$output .= ' ';
					$i += 1;
					break;
				default:
					$output .= $temp[$i];
					break;
			}
		} else {
			$output .= $temp[$i];
		}
	}
	$output = strip_tags($output);
	return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}


function traiteStringFromBDD($str){
	return htmlspecialchars_decode($str, ENT_QUOTES);
}


function generateToken() {
	$token = hash('sha3-256', random_bytes(32));
	return $token;
}


function controlAssessment($answer) {
	global $noteMax;
	foreach ($answer as $key => $value){
		if (substr($key, 0, 7) === 'comment') {
			$answer[$key] = traiteStringToBDD($value);
		}
		if (substr($key, 0, 8) === 'question') {
			$tmp = intval($value);
			if ($tmp<0 || $tmp>$noteMax) {
				$tmp = 0;
			}
			$answer[$key] = $tmp;
		}
	}
	$base = dbConnect();
	$answer = mysqli_real_escape_string($base, serialize($answer));
	dbDisconnect($base);
	return $answer;
}


function controlObjectifs($answer) {
	global $noteMax;
	foreach ($answer as $key => $value){
		$tmp = intval($value);
		if ($tmp<0 || $tmp>$noteMax) {
			$tmp = 0;
		}
		$answer[$key] = $tmp;
	}
	$base = dbConnect();
	$answer = mysqli_real_escape_string($base, serialize($answer));
	dbDisconnect($base);
	return $answer;
}


function uidToEtbs() {
	$base = dbConnect();
	$request = sprintf("SELECT nom FROM etablissement WHERE id='%d' LIMIT 1", intval($_SESSION['id_etab']));
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		return $row->nom;
	} else {
		return false;
	}
}


function getRole($id) {
	$base = dbConnect();
	$request = sprintf("SELECT intitule FROM role WHERE id='%d' LIMIT 1", intval($id));
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	return $row->intitule;
}


function getOneParAbrege($id_par) {
	$base = dbConnect();
	$request = sprintf("SELECT paragraphe.abrege FROM paragraphe WHERE (paragraphe.id='%d') LIMIT 1", $id_par);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	return traiteStringFromBDD($row->abrege);
}


function getAllParAbrege() {
	$base = dbConnect();
	$request = "SELECT abrege FROM paragraphe";
	$result = mysqli_query($base, $request);
	$par = array();
	while ($row = mysqli_fetch_object($result)) {
		$par[] = traiteStringFromBDD($row->abrege);
	}
	dbDisconnect($base);
	return $par;
}


function getSubParNum() {
	$base = dbConnect();
	$request = "SELECT paragraphe.numero AS 'par', sub_paragraphe.numero AS 'subpar' FROM sub_paragraphe JOIN paragraphe ON sub_paragraphe.id_paragraphe=paragraphe.id";
	$result = mysqli_query($base, $request);
	$subpar = array();
	while ($row = mysqli_fetch_object($result)) {
		$subpar[] = $row->par.$row->subpar;
	}
	dbDisconnect($base);
	return $subpar;
}


function getSubParLibelle($id_par) {
	$base = dbConnect();
	$request = sprintf("SELECT sub_paragraphe.libelle FROM sub_paragraphe WHERE (sub_paragraphe.id_paragraphe='%d') ", $id_par);
	$result = mysqli_query($base, $request);
	$titles_subpar = array();
	while ($row = mysqli_fetch_object($result)) {
		$titles_subpar[] = traiteStringFromBDD($row->libelle);
	}
	dbDisconnect($base);
	return $titles_subpar;
}


function getEtablissement($id_etab=0, $abrege=0) {
	$base = dbConnect();
	if ($id_etab<>0) {
		$request = sprintf("SELECT id, nom, abrege FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
		$result=mysqli_query($base, $request);
		$row = mysqli_fetch_object($result);
		if ($abrege) {
			return traiteStringFromBDD($row->abrege);
		} else {
			return traiteStringFromBDD($row->nom);
		}
	} else {
		if (intval($_SESSION['role']) == 2) {
			$request = sprintf("SELECT id, nom, abrege FROM etablissement WHERE id IN (%s)", $_SESSION['audit_etab']);
		} else {
			$request = "SELECT * FROM etablissement";
		}
		$result = mysqli_query($base, $request);
		dbDisconnect($base);
		return $result;
	}
}


function isRegroupEtab() {
	$id_etab = $_SESSION['id_etab'];
	$base = dbConnect();
	$request = sprintf("SELECT abrege FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	$row = mysqli_fetch_object($result);
	if (stripos($row->abrege, "_TEAM") === false) {
		return false;
	} else {
		return true;
	}
}


function changePassword($script) {
	$base = dbConnect();
	$request = sprintf("SELECT * FROM users WHERE login='%s' LIMIT 1", $_SESSION['login']);
	$result=mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_array($result);
		printf("<form method='post' id='chg_password' action='%s?action=chg_password' onsubmit='return password_ok(this)'>\n", $script);
		printf("<fieldset>\n<legend>Changement de mot de passe</legend>\n");
		printf("<table>\n<tr><td>\n");
		printf("<input type='password' size='50' maxlength='50' name='new1' id='new1' placeholder='Nouveau mot de passe' />\n");
		printf("</td></tr>\n<tr><td>\n");
		printf("<input type='password' size='50' maxlength='50' name='new2' id='new2' placeholder='Saisissez à nouveau le mot de passe'/>\n");
		printf("</td></tr>\n</table>\n");
		printf("</fieldset>\n");
		validForms('Enregistrer', $script);
		printf("</form>\n");
	} else {
		linkMsg("#", "Erreur de compte.", "alert.png");
		footPage($script, "Accueil");
	}
}


function recordNewPassword($passwd) {
	$base = dbConnect();
	$passwd = password_hash($passwd, PASSWORD_BCRYPT);
	$request = sprintf("UPDATE users SET password='%s' WHERE login='%s'", $passwd, $_SESSION['login']);
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
		if (mysqli_query($base, $request)) {
			dbDisconnect($base);
			return true;
		} else {
			dbDisconnect($base);
			return false;
		}
	} else {
		dbDisconnect($base);
		return false;
	}
}


function getAuditor($id_etab) {
	$auditor = '';
	$base = dbConnect();
	$request = sprintf("SELECT nom, prenom, etablissement FROM users WHERE role='2'");
	$result = mysqli_query($base, $request);
	while($row=mysqli_fetch_object($result)) {
		$etabs = explode(',', $row->etablissement);
		if (in_array($id_etab, $etabs)) {
			$auditor = sprintf("%s %s", htmlLatexParser($row->prenom), htmlLatexParser($row->nom));
		}
	}
	dbDisconnect($base);
	return $auditor;
}


function isThereAssessForEtab() {
	$base = dbConnect();
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' LIMIT 1", $id_etab, $annee);
	$result=mysqli_query($base, $request);
	dbDisconnect($base);
	if ($result->num_rows) {
		return true;
	} else {
		return false;
	}
}


function textItem($num){
	switch ($num) {
		case 1:
			return 'Non applicable';
			break;
		case 2:
			return 'Inexistant et investissement important';
			break;
		case 3:
			return 'Inexistant et investissement faible';
			break;
		case 4:
			return 'En cours et demande un ajustement';
			break;
		case 5:
			return 'En cours';
			break;
		case 6:
			return 'Existant et demande un ajustement';
			break;
		case 7:
			return 'Existant';
			break;
		default:
			return 'Pas de réponse';
			break;
	}
}


function paragraphCount() {
	$base = dbConnect();
	$request = "SELECT COUNT(id) FROM paragraphe";
	$result=mysqli_query($base, $request);
	$num = mysqli_fetch_array($result);
	dbDisconnect($base);
	return $num[0];
}


function subParagraphCount($id_par, $base) {
	$request = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d'", intval($id_par));
	$result=mysqli_query($base, $request);
	$num = mysqli_num_rows($result);
	return $num;
}


function questionsCount() {
	$base = dbConnect();
	$request = "SELECT COUNT(id) FROM question";
	$result=mysqli_query($base, $request);
	$num = mysqli_fetch_array($result);
	dbDisconnect($base);
	return $num[0];
}


function printSelect($num_par, $num_sub_par, $num_quest, $assessment=0) {
	$name='question'.$num_par.'_'.$num_sub_par.'_'.$num_quest;
	printf("<select name='%s' id='%s' onchange='progresse()'>\n", $name, $name);
	if ($assessment) {
		if ($assessment[$name] == 0) {
			printf("<option selected='selected' value='0'>&nbsp;</option>\n");
		} else {
			printf("<option selected='selected' value='%d'>%d - %s</option>\n",$assessment[$name], $assessment[$name], textItem($assessment[$name]));
			printf("<option value='0'>&nbsp;</option>\n");
		}
	} else {
		printf("<option selected='selected' value='0'>&nbsp;</option>\n");
	}
	for ($i=1; $i<8; $i++) {
		printf("<option value='%d'>%d - %s</option>\n", $i, $i, textItem($i));
	}
	printf("</select>\n");
}


function paragrapheComplete($assessment) {
	$val = 0;
	$cpt_total = 0;
	$cpt_encours = 0;
	$nbrPar = paragraphCount();
	$result = array_fill(0, $nbrPar+1, 0);
	if (empty($assessment)) return $result;
	foreach ($assessment as $key=>$value) {
		if (preg_match("/question/", $key)) {
			$temp = preg_replace("/question/", "", $key);
			$temp = explode("_", $temp);
			if ($val <> $temp[0]) {
				if ($cpt_encours == $cpt_total)
					$result[$val] = 0;
				elseif ($cpt_encours <> 0)
					$result[$val] = 1;
				else
					$result[$val] = 2;
				$val = $temp[0];
				$cpt_total = 0;
				$cpt_encours = 0;
			}
			if ($value == 0) {
				$cpt_encours++;
			}
			$cpt_total++;
		}
		if ($cpt_encours == $cpt_total)
			$result[$val] = 0;
		elseif ($cpt_encours <> 0)
			$result[$val] = 1;
		else
			$result[$val] = 2;
	}
	return $result;
}


function subParagrapheComplete($assessment, $par, $subpar, $base) {
	$cpt_total = 0;
	$cpt_encours = 0;
	$nbrSubPar = subParagraphCount($par, $base);
	$result = array_fill(0, $nbrSubPar+1, 0);
	if (empty($assessment)) return $result;
	foreach ($assessment as $key=>$value) {
		if (preg_match("/question/", $key)) {
			$temp = preg_replace("/question/", "", $key);
			$temp = explode("_", $temp);
			if (($temp[0] == $par) && ($temp[1] == $subpar)) {
				if ($value == 0) $cpt_encours++;
				$cpt_total++;
			}
		}
	}
	if ($cpt_encours == $cpt_total)
		$result[$subpar] = 0;
	elseif ($cpt_encours <> 0)
		$result[$subpar] = 1;
	else
		$result[$subpar] = 2;
	return $result;
}


function afficheNotesExplanation() {
	printf("<div class='column littleright'>\n");
	printf("<div class=event>");
	printf("<dl>\n");
	printf("<dt>1: Non Applicable</dt>\n");
	printf("<dd>La question est sans objet.</dd>\n");
	printf("<dt>2: Inexistant et investissement important (Inexistant pour longtemps)</dt>\n");
	printf("<dd>La disposition proposée n’est pas appliquée actuellement et ne le sera pas avant un délai important (mesure non planifiée, mesure nécessitant une étude préalable importante, mesure nécessitant un budget important, etc.).</dd>\n");
	printf("<dt>3: Inexistant et investissement peu important (Inexistant)</dt>\n");
	printf("<dd>La disposition proposée n’est pas appliquée actuellement, mais le sera rapidement, car sa mise en oeuvre est facile et/ou rapide.</dd>\n");
	printf("<dt>4: En cours et demande un ajustement (Réalisation avec difficultés non prévues)</dt>\n");
	printf("<dd>La disposition proposée est en cours de réalisation, mais quelques problèmes sont rencontrés et les plans prévus de réalisation doivent être modifiés.</dd>\n");
	printf("<dt>5: En cours (Réalisation sans encombre)</dt>\n");
	printf("<dd>La disposition proposée est en cours de réalisation et se déroule sans encombre.</dd>\n");
	printf("<dt>6: Existant et demande un ajustement (Opérationnel 90%%)</dt>\n");
	printf("<dd>La disposition est mise en place et il reste quelques ajustements à réaliser pour la rendre totalement opérationnelle.</dd>\n");
	printf("<dt>7: Existant (Totalement opérationnel) / oui (100%%)</dt>\n");
	printf("<dd>La disposition est opérationnelle et remplit entièrement les besoins demandés</dd>\n");
	printf("</dl>\n</div>\n");
	printf("</div>\n");
}


function extractSubParRep($id_par, $table) {
	$result = array();
	foreach ($table as $question => $eval) {
		$numQuestion = explode('_', $question);
		if ($numQuestion[0] == $id_par) {
			$result[$question] = $eval;
		}
	}
	return $result;
}


function calculNotes($table) {
	$base = dbConnect();
	$mem = 1; // numéro du premier paragraphe
	$sumEval = 0;
	$sumPoids = 0;
	$noteFinale = array();
	foreach ($table as $question => $eval) {
		$numQuestion = explode('_', $question);
		$nq = $numQuestion[0];
		if ($mem == $nq) {
			$poids = getPoidsQuestion($question, $base);
			$sumEval = $sumEval + ($eval * $poids);
			$sumPoids = $sumPoids + $poids;
		} else {
			$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
			$mem=$nq;
			$sumEval = ($eval * $poids);
			$sumPoids = $poids;
		}
	}
	dbDisconnect($base);
	$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
	return $noteFinale;
}


function calculNotesDetail($table, $mem=11) {
	//$mem = 11 -> numéro du premier sous-paragraphe
	$base = dbConnect();
	$sumEval = 0;
	$sumPoids = 0;
	$noteFinale = array();
	foreach ($table as $question => $eval) {
		$numQuestion = explode('_', $question);
		$nq = $numQuestion[0].$numQuestion[1];
		if ($mem == $nq) {
			$poids = getPoidsQuestion($question, $base);
			$sumEval = $sumEval + ($eval * $poids);
			$sumPoids = $sumPoids + $poids;
		} else {
			$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
			$mem=$nq;
			$sumEval = ($eval * $poids);
			$sumPoids = $poids;
		}
	}
	dbDisconnect($base);
	$noteFinale[$mem] = round($sumEval / $sumPoids, 2);
	return $noteFinale;
}


function getPoidsQuestion($num, $base) {
	$tab = explode('_', $num);
	$request = sprintf("SELECT question.poids FROM question JOIN sub_paragraphe ON question.id_sub_paragraphe=sub_paragraphe.id JOIN paragraphe ON question.id_paragraphe=paragraphe.id WHERE (paragraphe.numero='%d' AND sub_paragraphe.numero='%d' AND question.numero='%d') LIMIT 1", $tab[0], $tab[1], $tab[2]);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	return $row->poids;
}


function getObjectives() {
	$base = dbConnect();
	$request = sprintf("SELECT objectifs FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['id_etab']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	return array_values(unserialize($row->objectifs));
}


function isAssessComplete($table) {
	$result = true;
	$temp = array_values($table);
	if (in_array(0, $temp))
		$result = false;
	return $result;
}


function getAnswers() {
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' ORDER BY annee", $_SESSION['id_etab']);
	$result = mysqli_query($base, $request);
	$answers = array();
	while ($row = mysqli_fetch_object($result)) {
		if (!empty($row->reponses)) {
			$an = $row->annee;
			foreach(unserialize($row->reponses) as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$answers[$an][substr($quest, 8, 14)]=$rep;
				}
			}
		}
	}
	dbDisconnect($base);
	return $answers;
}


function defineGraphVariables($nbrPar) {
	global $noteMax;
	$tmp = sprintf("\\newcommand{\\dimension}{%d}\n", $nbrPar);
	$tmp .= sprintf("\\newcommand{\\unit}{%d}\n", $noteMax);
	$tmp .= "\\newcommand{\\axisAngle}{360/\\dimension}\n";
	$tmp .= "\\newdimen\\radiusMax \\setlength{\\radiusMax}{130pt}\n";
	$tmp .= "\\newdimen\\labelDist \\setlength{\\labelDist}{\\radiusMax + 15pt}\n";
	$tmp .= "\\definecolor{year1}{RGB}{255,205,86}\n";
	$tmp .= "\\definecolor{year2}{RGB}{75,192,192}\n";
	$tmp .= "\\definecolor{year3}{RGB}{153,102,255}\n";
	$tmp .= "\\definecolor{objectiv}{RGB}{255,99,132}\n\n";
	$tmp .= "\\tikzstyle{myShape} = [line width=1.5pt, opacity=0.9]\n";
	$tmp .= "\\tikzstyle{myShapeFill} = [opacity=0.5]\n";
	$tmp .= "\\tikzstyle{myLabel} = [font=\\scriptsize, text width=45pt, text badly centered, inner sep=0pt]\n";
	$tmp .= "\\tikzstyle{myLegend} = [font=\\scriptsize, anchor=west]\n";
	$tmp .= "\\tikzstyle{myTitle} = [font=\\bfseries, fill=myBeige, text width=90pt, text badly centered, inner sep=5pt, rounded corners=2pt]\n";
	$tmp .= "\\tikzstyle{myMatrix} = [fill=myBeige, inner sep=5pt, rounded corners=2pt]\n";
	return $tmp;
}


function simpleYearGraph($annee, $notes, $titles_par) {
	$objectifs = getObjectives();

	$txtGraph = "\\begin{center}\n\\begin{tikzpicture}[background rectangle/.style={fill=myBeige!45, draw=myBeige, rounded corners=4pt, inner sep=5pt}, show background rectangle]\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{ \\draw [color=black!80] (\\x*\\axisAngle:0) -- (\\x*\\axisAngle:\\radiusMax); }\n";
	$txtGraph .= "\\foreach \\y in {0,...,\\unit}{\n";
	$txtGraph .= "\\draw [color=black!40] (0:\\y*\\radiusMax/\\unit) \\foreach \\x in {1,...,\\dimension}{-- (\\x*\\axisAngle:\\y*\\radiusMax/\\unit)} -- cycle;\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{\n";
	$txtGraph .= "\\path (\\x*\\axisAngle:\\y*\\radiusMax/\\unit) coordinate (D\\x-\\y);\n";
	$txtGraph .= "\\fill [color=black!80] (D\\x-\\y) circle (1.2pt);\n}\n}\n";
	// label
	for ($i=0; $i<count($titles_par); $i++) {
		$txtGraph .= sprintf("\\path (%d*\\axisAngle:\\labelDist) node [myLabel] {%s};\n", $i+1, $titles_par[$i]);
	}
	$txtGraph .= "\n";
	// Année n
	$temp = "\\draw [myShape, color=year1] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($notes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	$temp = "\\fill [myShapeFill, color=year1!20] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($notes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// Objectifs
	$temp = "\\draw [myShape, color=objectiv] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, $objectifs[$i]);
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// legend
	$txtGraph .= "\\matrix [myMatrix, xshift=180pt, yshift=-100pt] {\n";
	$txtGraph .= "\\node [rectangle, fill=objectiv] {}; & \\node [myLegend] {Objectifs}; \\\\\n";
	$txtGraph .= sprintf("\\node [rectangle, fill=year1] {};  & \\node [myLegend] {Année %d}; \\\\\n};\n", $annee);
	// title
	$txtGraph .= "\\node [myTitle, xshift=180pt, yshift=40pt] {Résultats par domaines};\n";
	$txtGraph .= "\\end{tikzpicture}\n\\end{center}\n\n";
	return $txtGraph;
}


function cumulatedGraph($cumulNotes, $annee, $titles_par) {
	$objectifs = getObjectives();

	$txtGraph = "\\begin{center}\n\\begin{tikzpicture}[background rectangle/.style={fill=myBeige!45, draw=myBeige, rounded corners=4pt, inner sep=5pt}, show background rectangle]\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{ \\draw [color=black!80] (\\x*\\axisAngle:0) -- (\\x*\\axisAngle:\\radiusMax); }\n";
	$txtGraph .= "\\foreach \\y in {0,...,\\unit}{\n";
	$txtGraph .= "\\draw [color=black!40] (0:\\y*\\radiusMax/\\unit) \\foreach \\x in {1,...,\\dimension}{-- (\\x*\\axisAngle:\\y*\\radiusMax/\\unit)} -- cycle;\n";
	$txtGraph .= "\\foreach \\x in {1,...,\\dimension}{\n";
	$txtGraph .= "\\path (\\x*\\axisAngle:\\y*\\radiusMax/\\unit) coordinate (D\\x-\\y);\n";
	$txtGraph .= "\\fill [color=black!80] (D\\x-\\y) circle (1.2pt);\n}\n}\n";
	// label
	for ($i=0; $i<count($titles_par); $i++) {
		$txtGraph .= sprintf("\\path (%d*\\axisAngle:\\labelDist) node [myLabel] {%s};\n", $i+1, $titles_par[$i]);
	}
	$txtGraph .= "\n";
	// Année n-2
	if (isset($cumulNotes[$annee-2])) {
		$curNotes = $cumulNotes[$annee-2];
		$temp = "\\draw [myShape, color=year3] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
		$temp = "\\fill [myShapeFill, color=year3!20] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
	}
	// Année n-1
	if (isset($cumulNotes[$annee-1])) {
		$curNotes = $cumulNotes[$annee-1];
		$temp = "\\draw [myShape, color=year2] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
		$temp = "\\fill [myShapeFill, color=year2!20] ";
		for ($i=0; $i<count($titles_par); $i++) {
			$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
		}
		$txtGraph .=  $temp."cycle;\n\n";
	}
	// Année n
	$curNotes = $cumulNotes[$annee];
	$temp = "\\draw [myShape, color=year1] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	$temp = "\\fill [myShapeFill, color=year1!20] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, round($curNotes[$i+1]));
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// Objectifs
	$temp = "\\draw [myShape, color=objectiv] ";
	for ($i=0; $i<count($titles_par); $i++) {
		$temp .= sprintf("(D%d-%d) -- ", $i+1, $objectifs[$i]);
	}
	$txtGraph .=  $temp."cycle;\n\n";
	// legend
	$txtGraph .= "\\matrix [myMatrix, xshift=180pt, yshift=-100pt] {\n";
	$txtGraph .= "\\node [rectangle, fill=objectiv] {}; & \\node [myLegend] {Objectifs}; \\\\\n";
	if (isset($cumulNotes[$annee-2])) {
		$txtGraph .= sprintf("\\node [rectangle, fill=year3] {};  & \\node [myLegend] {Année %d}; \\\\\n", $annee-2);
	}
	if (isset($cumulNotes[$annee-1])) {
		$txtGraph .= sprintf("\\node [rectangle, fill=year2] {};  & \\node [myLegend] {Année %d}; \\\\\n", $annee-1);
	}
	$txtGraph .= sprintf("\\node [rectangle, fill=year1] {};  & \\node [myLegend] {Année %d}; \\\\\n};\n", $annee);
	// title
	$txtGraph .= "\\node [myTitle, xshift=180pt, yshift=40pt] {Résultats cumulés par domaines};\n";
	$txtGraph .= "\\end{tikzpicture}\n\\end{center}\n\n";
	return $txtGraph;
}


function displayEtablissmentGraphs() {
	$annee = $_SESSION['annee'];
	$reponses = getAnswers();
	if (sizeof(array_keys($reponses))) {
		if (isAssessComplete($reponses[$annee])) {
			linkMsg("#", "L'évaluation pour ".$annee." est complète.", "ok.png");
		} else {
			linkMsg("#", "L'évaluation pour ".$annee." est incomplète, les graphes sont donc partiellement justes.", "alert.png");
		}
		printf("<div class='onecolumn' id='graphs'>\n");
		assessSynthese();
		printf("<canvas id='currentYearGraphBar'></canvas>\n");
		printf("<a id='yearGraphBar' class='btnValid' download='yearGraphBar.png' type='image/png'>Télécharger le graphe</a>\n");
		printf("<p class='separation'>&nbsp;</p>\n");
		printf("<canvas id='currentYearGraphPolar'></canvas>\n");
		printf("<a id='yearGraphPolar' class='btnValid' download='yearGraphPolar.png' type='image/png'>Télécharger le graphe</a>\n");
		printf("<p class='separation'>&nbsp;</p>\n");
		printf("<canvas id='currentYearGraphScatter'></canvas><br />\n");
		printf("<a id='yearGraphScatter' class='btnValid' download='yearGraphScatter.png' type='image/png'>Télécharger le graphe</a>\n");
		printf("<p class='separation'>&nbsp;</p>\n");
		printf("</div>\n");
	} else {
		$msg = sprintf("L'évaluation %d est vide.", $annee);
		linkMsg("#", $msg, "alert.png");
	}
}


function assessSynthese() {
	$id_etab = $_SESSION['id_etab'];
	$annee_encours = $_SESSION['annee'];
	$titles_par = getAllParAbrege();

	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE annee='%d' AND etablissement='%s'", $annee_encours, $id_etab);
	$result = mysqli_query($base, $request);
	$reponses = array();
	while ($row = mysqli_fetch_object($result)) {
		if (!empty($row->reponses)) {
			foreach(unserialize($row->reponses) as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[substr($quest, 8, 14)]=$rep;
				}
			}
		}
	}

	printf("<table>\n<tr><th colspan='3'>Notes finale des établissements</th></tr>\n");
	printf("<tr><th>Etablissement</th><th>Détail des notes</th><th>Note finale</th></tr>\n");
	$name_etab = getEtablissement($id_etab);
	$notes = calculNotes($reponses);
	$text_note = "";
	$noteSum = 0;
	for ($i=0; $i<sizeof($titles_par); $i++) {
		$note = 20 * $notes[$i+1] / 7;
		$noteSum = $noteSum + $note;
		if ($note <= 10) {
			$text_note .= sprintf("<li>%s -> <b style='color:red;'>%d/20</b></li>", $titles_par[$i], $note);
		} else {
			$text_note .= sprintf("<li>%s -> <b>%d/20</b></li>", $titles_par[$i], $note);
		}
	}
	$noteFinale = 20 * $noteSum / (sizeof($titles_par)*20);
	printf("<tr>\n<td style='width:120px;'><b>%s</b></td><td><ul>%s</ul></td><td><b style='font-size:20pt'>%d/20</b></td>\n</tr>\n", $name_etab, $text_note, $noteFinale);
	dbDisconnect($base);
	printf("</table>\n");
}


function dateToTimestamp($date) {
	$temp = explode(' ', $date);
	$jour = explode('-', $temp[0]);
	$heure = explode(':', $temp[1]);
	return mktime($heure[0],$heure[1],$heure[2],$jour[1],$jour[2],$jour[0]);
}


function timestampToDate($time) {
	return strftime("%a %d %b", $time);
}


function fullTimestampToDate($time) {
	$part1 = strftime("%A %d %B %Y", $time);
	$part2 = strftime("%H:%M", $time);
	return sprintf("le %s à %s", $part1, $part2);
}


function beginningDay($time) {
	$temp = getdate($time);
	$temp['seconds'] = 0;
	$temp['minutes'] = 0;
	$temp['hours'] = 0;
	return mktime($temp['hours'], $temp['minutes'], $temp['seconds'], $temp['mon'], $temp['mday'], $temp['year']);
}


function finishingDay($time) {
	$temp = getdate($time);
	$temp['seconds'] = 59;
	$temp['minutes'] = 59;
	$temp['hours'] = 23;
	return mktime($temp['hours'], $temp['minutes'], $temp['seconds'], $temp['mon'], $temp['mday'], $temp['year']);
}


function getNavigateurName($str) {
	// find browser
	if (preg_match("/Trident/",$str)) {
		$browser = "Internet Explorer";
		$val = stristr($str, "Trident");
		$val = explode("/",$val);
		$val = explode(";",$val[1]);
		$version = $val[0];
	} elseif (preg_match("/Firefox/", $str)) {
		$browser = "Firefox";
		$val = stristr($str, "Firefox");
		$val = explode("/",$val);
		$version = $val[1];
	} elseif (preg_match("/Chrome/", $str)){
		$browser = "Chrome";
		$val = stristr($str, "Chrome");
		$val = explode("/",$val);
		$val = explode(".",$val[1]);
		$version = $val[0].".".$val[1];
	} elseif (preg_match("/Safari/", $str)) {
		$browser = "Safari";
		$val = stristr($str, "Version");
		$val = explode("/",$val);
		$val = explode(" ",$val[1]);
		$version = $val[0];
	} else {
		$browser = "Unknown";
		$version = "Unknown";
	}
	// find operating system
	if (preg_match("/Trident/", $str)) $platform = "Windows";
	elseif (preg_match("/Macintosh/", $str)) $platform = "Apple";
	elseif (preg_match("/linux/", $str)) $platform = "Linux";
	elseif (preg_match("/BeOS/", $str)) $platform = "BeOS";
	else $platform = "Unknown";
	return $browser." ".$version." sur ".$platform;
}


function isValidateRapport($id_etab, $annee=0) {
	$base = dbConnect();
	$id_etab = intval($id_etab);
	if (!$annee) {
		$annee = $_SESSION['annee'];
	}
	$request = sprintf("SELECT valide FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	if ($row->valide == 1) {
		return true;
	} else {
		return false;
	}
}


function parserLatex($text) {
	$patternList = array(
		"\\" => "$\\backslash$", // a laisser en premier
		chr(13) => "\n\\mbox{}\\\\", // Carriage Return (\r)
		chr(10) => "", // Line Feed (\n)
		"%" => "\\%",
		"_" => "\\_",
		"&" => "\\&",
		"°" => "\\textsuperscript{o}"
	);
	return(str_replace(array_keys($patternList), array_values($patternList), $text));
}


function htmlLatexParser($text) {
	$patternList = array(
		"<p>" => "",
		"</p>" => "\\par\n",
		"<ol>" => "\\begin{enumerate}\n",
		"</ol>" => "\\end{enumerate}\n",
		"<ul>" => "\\begin{itemize}\n",
		"</ul>" => "\\end{itemize}\n",
		"<li>" => "\\item ",
		"</li>" => "",
		"<strong>" => "\\textbf{",
		"</strong>" => "}",
		"<em>" => "\\textsl{",
		"</em>" => "}",
		"&nbsp;" => " ",
		"&#39;" => "\\textquotesingle{}",
		"&quot;" => "\"",
		"&agrave;" => "à",
		"&Agrave;" => "\`A",
		"&acirc;" => "â",
		"&Acirc;" => "\^A",
		"&auml;" => "ä",
		"&egrave;" => "è",
		"&Egrave;" => "\`E",
		"&eacute;" => "é",
		"&Eacute;" => "\'E",
		"&ecirc;" => "ê",
		"&Ecirc;" => "\^E",
		"&euml;" => "ë",
		"&icirc;" => "î",
		"&Icirc;" => "\^I",
		"&iuml;" => "ï",
		"&ocirc;" => "ô",
		"&ouml;" => "ö",
		"&ugrave;" => "ù",
		"&Ugrave;" => "\`U",
		"&ucirc;" => "û",
		"&uuml;" => "ü",
		"&ccedil;" => "ç",
		"&Ccedil;" => "\c C"
	);
	return(str_replace(array_keys($patternList), array_values($patternList), $text));
}


function latexHead($annee=0) {
	$id_etab = $_SESSION['id_etab'];
	$auditor = getAuditor($id_etab);
	if (!$annee) {
		$annee = $_SESSION['annee'];
	}
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row =mysqli_fetch_object($result);
	$etablissement = traiteStringFromBDD($row->nom);
	$req_dir = sprintf("SELECT prenom, nom FROM users WHERE (etablissement='%d' AND role='3') LIMIT 1", $id_etab);
	$res_dir = mysqli_query($base, $req_dir);
	$row_dir = mysqli_fetch_object($res_dir);
	$req_rssi = sprintf("SELECT prenom, nom FROM users WHERE (etablissement='%d' AND role='4') LIMIT 1", $id_etab);
	$res_rssi = mysqli_query($base, $req_rssi);
	$row_rssi = mysqli_fetch_object($res_rssi);
	dbDisconnect($base);

	$en_tete = "\\begin{filecontents*}{\jobname.xmpdata}\n\\Title{EvalSMSI}\n\\Author{Michel Dubois}\n\\Subject{Evaluation du SMSI}\n\\Publisher{Michel Dubois}\n\\end{filecontents*}\n\n";
	$en_tete .= "\\documentclass[a4paper,10pt]{article}\n\n\\input{header}\n\n";
	$en_tete .= sprintf("\\title{Rapport d'évaluation du\\\\Système de Management de la Sécurité de l'Information\\\\ \\textcolor{myRed}{%s}}\n\n", $etablissement);
	$en_tete .= sprintf("\\author{%s -- \\textcolor{myRed}{Auditeur}}\n\n", $auditor);
	$en_tete .= "\\date{\\today}\n\n";
	$en_tete .= "\\begin{document}\n\n\\renewcommand{\labelitemi}{\\ensuremath{\\bullet}}\n\\renewcommand{\\labelitemii}{\\ensuremath{\circ}}\n\\renewcommand{\\labelitemiii}{\\ensuremath{\\triangleright}}\n\n\\maketitle\n\n";
	$en_tete .= "\\bigskip\\bigskip\\bigskip\n\n";
	$en_tete .= sprintf("\\abstract{Ce rapport décrit le résultat de l'évaluation du Système de Management de la Sécurité de l'Information (SMSI) réalisé à \\textsl{%s} en %s. L'évaluation initiale a été contrôlée le \\today{} par %s. Cette évaluation repose sur un questionnaire établit conformément aux normes ISO~27001 et ISO~27002.}\n\n\\bigskip\\bigskip\\bigskip\n\n\\begin{itemize}\n", $etablissement, $annee, $auditor);
	$en_tete .= sprintf("\\item Directeur de l'établissement: \\textsl{%s %s}\n", htmlLatexParser(traiteStringFromBDD($row_dir->prenom)), htmlLatexParser(traiteStringFromBDD($row_dir->nom)));
	$en_tete .= sprintf("\\item RSSI de l'établissement: \\textsl{%s %s}\n", htmlLatexParser(traiteStringFromBDD($row_rssi->prenom)), htmlLatexParser(traiteStringFromBDD($row_rssi->nom)));
	$en_tete .= "\\end{itemize}\n\n\\bigskip\\bigskip\\bigskip\n\n";
	$en_tete .= "\\begin{center}\n";
	if (isValidateRapport($id_etab, $annee)) {
		$en_tete .= "\\Large{\\textcolor{myRed}{Rapport validé}}\n";
	} else {
		$en_tete .= "\\Large{\\textcolor{myRed}{Rapport NON validé !}}\n";
	}
	$en_tete .= "\\end{center}\n\n";
	if (isValidateRapport($id_etab, $annee)) {
		$en_tete .= "\\bigskip\\bigskip\\bigskip\n\n\\begin{flushright}\n\\textcolor{myRed}{Original signé}\n\n";
		$en_tete .= sprintf("\\textcolor{myRed}{%s}\\end{flushright}\n\n", $auditor);
	}
	$en_tete .= "\\clearpage\n\n";
	$en_tete .= "\\textcolor{myRed}{\\tableofcontents}\n\n\\clearpage\n\n";
	return $en_tete;
}


function latexFoot() {
	$foot = "\\end{document}\n\n";
	return $foot;
}


function printAssessment($assessment, $annee=0) {
	$text = "";
	if (!$annee) {
		$annee = $_SESSION['annee'];
	}
	$base = dbConnect();
	$request = sprintf("SELECT abrege FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['id_etab']);
	$result = mysqli_query($base, $request);
	$row_regroup = mysqli_fetch_object($result);
	if (stripos($row_regroup->abrege, "_TEAM") === false) {
		$regroup = false;
	} else {
		$regroup = true;
	}
	$req_par = "SELECT * FROM paragraphe ORDER BY numero";
	$res_par = mysqli_query($base, $req_par);
	$text .= sprintf("\\section{Résultats de l'évaluation du SMSI pour l'année %s}\n\n", $annee);
	while ($row_par = mysqli_fetch_object($res_par)) {
		$text .= sprintf("\\subsection{%s}\n\n", traiteStringFromBDD($row_par->libelle));
		$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d' ORDER BY numero", $row_par->id);
		$res_sub_par = mysqli_query($base, $req_sub_par);
		while ($row_sub_par = mysqli_fetch_object($res_sub_par)) {
			$text .= sprintf("\\subsubsection{%s}\n\n", traiteStringFromBDD($row_sub_par->libelle));
			$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
			$res_quest = mysqli_query($base, $req_quest);
			while ($row_quest = mysqli_fetch_object($res_quest)) {
				if ($regroup) {
					$text .= sprintf("\\paragraph{Question n\\textdegree %d.%d.%d}\n", $row_par->numero, $row_sub_par->numero, $row_quest->numero);
					$text .= sprintf("\\subparagraph{Libellé: }\n");
					$text .= sprintf("%s\n\n", traiteStringFromBDD($row_quest->libelle));
					$text .= sprintf("\\subparagraph{\\'Evaluation: }\n");
					$comm = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
					if ($assessment[$comm]<>"") {
						$commEtab = parserLatex(traiteStringFromBDD($assessment[$comm]));
						$text .= sprintf("%s\n", $commEtab);
					} else {
						$text .= sprintf("\\textsl{Néant}\n");
					}
				} else {
					$text .= sprintf("\\textbf{Question n\\textdegree %d.%d.%d} %s\n\n", $row_par->numero, $row_sub_par->numero, $row_quest->numero, traiteStringFromBDD($row_quest->libelle));
					$text .= "\\begin{center}\n";
					$text .= "\\begin{tabular}{ | >{\\centering}m{0.05\\textwidth} >{\\centering}m{0.25\\textwidth} | m{0.50\\textwidth} | }\n\\hline\n";
					$text .= "\\multicolumn{2}{|c|}{\\textbf{\\'Evaluation de l'établissement}} & \\centering\\textbf{Commentaire} \\tabularnewline\n";
					$quest='question'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
					if ($assessment[$quest]) {
						if ($assessment[$quest] == 1) { $color = "gray"; }
						if (($assessment[$quest] == 2) || ($assessment[$quest] == 3)) { $color = "red"; }
						if (($assessment[$quest] == 4) || ($assessment[$quest] == 5)) { $color = "orange"; }
						if (($assessment[$quest] == 6) || ($assessment[$quest] == 7)) { $color = "green"; }
						$text .= sprintf("\\tikz{\\node [rectangle, fill=%s, inner sep=10pt] {};} & ", $color);
						$text .= sprintf("\\textcolor{myRed}{%s (%s/7)} & ", textItem($assessment[$quest]), $assessment[$quest]);
					} else {
						$text .= sprintf(" & \\textcolor{myRed}{Néant} & ");
					}
					$comm = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
					if ($assessment[$comm]<>"") {
						$commEtab = parserLatex(traiteStringFromBDD($assessment[$comm]));
						$text .= sprintf("%s\\tabularnewline\n", $commEtab);
					} else {
						$text .= sprintf("Néant\\tabularnewline\n");
					}
					$text .= "\\hline\n";
					$text .= "\\multicolumn{3}{|>{\\centering}p{0.80\\textwidth}|}{\\textbf{Commentaire évaluateurs}}\\tabularnewline\n";
					$evalID = 'eval'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
					if ($assessment[$evalID]<>"") {
						$commEvaluateur = parserLatex(traiteStringFromBDD($assessment[$evalID]));
						$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{\\textcolor{myBlue}{%s}}\\tabularnewline\n", $commEvaluateur);
					} else {
						$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{\\textcolor{myBlue}{Avis conforme}}\\tabularnewline\n");
					}
					$text .= "\\hline\n";
					if ($assessment[$quest] < 5) {
						$text .= "\\multicolumn{3}{|c|}{\\textbf{Recommandations}}\\tabularnewline\n";
						if ($row_quest->mesure <> "") {
							$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{%s}\\tabularnewline\n", traiteStringFromBDD($row_quest->mesure));
						} else {
							$text .= sprintf("\\multicolumn{3}{|>{\\raggedright}p{0.80\\textwidth}|}{Pas de recommandations particulière.}\\tabularnewline\n");
						}
						$text .= "\\hline\n";
					}
					$text .= "\\end{tabular}\n\\end{center}\n\\bigskip\n\n";
				}
			}
		}
	}
	dbDisconnect($base);
	return $text."\\clearpage\n\n";
}


function printGraphsAndNotes($annee) {
	$id_etab = $_SESSION['id_etab'];
	$nbr_par = paragraphCount();
	$titles_par = getAllParAbrege();
	$name_etab = getEtablissement($id_etab);
	$reponses = getAnswers();
	$notes = calculNotes($reponses[$annee]);
	$noteSum = 0;
	$base = dbConnect();
	$request = sprintf("SELECT comment_graph_par, comments FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	foreach (array_keys($reponses) as $year){
		if (isValidateRapport($id_etab, $year) && $year<=$annee) {
			$cumulNotes[$year] = calculNotes($reponses[$year]);
		}
	}

	$text = sprintf("\\section{Analyse de l'évaluation du SMSI pour l'année %s}\n\n", $annee);
	$text .= "\\input{intro}\n\n";
	$text .= "\\subsection{Notes obtenues par l'établissement}\n\n";
	$text .= "\\begin{center}\n";
	$text .= "\\begin{tabular}{ | >{\\centering}m{0.20\\textwidth} | >{\\raggedright}m{0.30\\textwidth} @{\$\\quad\\rightarrow\\quad\$} >{\\raggedright}m{0.10\\textwidth} | >{\\centering}m{0.15\\textwidth} | }\n";
	$text .= "\\hline\n";
	$text .= "\\multicolumn{4}{| c |}{Notes finales de l'établissement}\\tabularnewline\n\\hline\n";
	$text .= "\\'Etablissement & \\multicolumn{2}{ c |}{\\centering{Détail des notes}} & Note finale \\tabularnewline\n";
	$text .= "\\hline\n";
	$text .= sprintf("\\multirow{%d}{*}{%s} & \\multicolumn{2}{ c |}{} & \\tabularnewline\n", $nbr_par+2, $name_etab);
	for ($i=0; $i<sizeof($titles_par); $i++) {
		$note = 20 * $notes[$i+1] / 7;
		$noteSum = $noteSum + $note;
		if ($note <= 10) {
			$text .= sprintf("& %s & \\textcolor{myRed}{\$%01.2f / 20\$} & \\tabularnewline\n", $titles_par[$i], $note);
		} else {
			$text .= sprintf("& %s & \$%01.2f / 20\$ & \\tabularnewline\n", $titles_par[$i], $note);
		}
	}
	$noteFinale = 20 * $noteSum / (sizeof($titles_par)*20);
	$text .= sprintf(" & \\multicolumn{2}{ c |}{} & \multirow{-%d}{*}{\$%01.2f / 20\$} \\tabularnewline\n", $nbr_par+2, $noteFinale);
	$text .= "\\hline\n";
	$text .= "\\end{tabular}\n";
	$text .= "\\end{center}\n\n";
	$text .= "\\clearpage\n\n";

	$text .= "\\subsection{Graphes de synthèses de l'établissement}\n\n";
	$text .= defineGraphVariables(count($titles_par));
	$text .= simpleYearGraph($annee, $notes, $titles_par);
	if (count($cumulNotes)>1) {
		$text .= "\\bigskip\n\n\\bigskip\n\n";
		$text .= cumulatedGraph($cumulNotes, $annee, $titles_par);
	}
	$text .= "\\clearpage\n\n";

	$text .= "\\subsection{Commentaires et conclusion}\n\n";
	$text .= "\\subsubsection{Commentaires de l'établissement}\n\n";
	$commEtab = htmlLatexParser(traiteStringFromBDD($row->comments));
	$text .= sprintf("\\par\n%s\n\\par", $commEtab);
	$text .= "\\subsubsection{Conclusion des évaluateurs}\n\n";
	$commEvals = htmlLatexParser(traiteStringFromBDD($row->comment_graph_par));
	$text .= sprintf("\\par\n%s\n\\par", $commEvals);
	return $text."\\clearpage\n\n";
}


function printAnnexes() {
	$text = "\\appendix\n";
	$text .= "\\input{methode}\n\n";
	return $text;
}


function purgeRapportsFiles() {
	global $cheminRAP;
	if (isset($_SESSION['id_etab'])) {
		$abrege_etab = getEtablissement($_SESSION['id_etab'], $abrege=1);
		$rapportPDF = sprintf("%s.pdf", $abrege_etab);
		$rapportXLSX = sprintf("plan_actions_%d.xlsx", $_SESSION['id_etab']);
		$evalDOCX = sprintf("evaluation_%s.docx", $abrege_etab);
		$evalXLSX = sprintf("evaluation_%s.xlsx", $abrege_etab);
		$currentDirectory = getcwd();
		chdir($cheminRAP);
		@unlink($rapportPDF);
		@unlink($rapportXLSX);
		@unlink($evalDOCX);
		@unlink($evalXLSX);
		@unlink("referentiel.docx");
		@unlink("referentiel.pdf");
		chdir($currentDirectory);
	}
}


function makeRapport($abrege_etab, $text, $annee) {
	global $cheminRAP, $cheminDATA;
	$script = dirname($_SERVER['PHP_SELF']);
	$file = sprintf("%s%s.tex", $cheminDATA, $abrege_etab);
	$pdffile = sprintf("%s%s.pdf", $cheminDATA, $abrege_etab);
	$newpdffile = sprintf("%s%s.pdf", $cheminRAP, $abrege_etab);
	$pdfLink = sprintf("%s/rapports/%s.pdf", dirname($_SERVER['REQUEST_URI']), $abrege_etab);
	if ($handle = fopen($file, "w")) {
		fwrite($handle, $text);
		fclose($handle);
		$rem_courant = getcwd();
		chdir($cheminDATA);
		exec("make");
		exec("make clean");
		unlink($file);
		rename($pdffile, $newpdffile);
		chdir($rem_courant);
		$msg = sprintf("Télécharger le rapport %d", $annee);
		linkMsg($pdfLink, $msg, "print_rapport.png", 'menu');
	} else {
		linkMsg("#", "Erreur de création du rapport", "alert.png");
	}
}


function generateRapport($script, $annee=0) {
	$id_etab = $_SESSION['id_etab'];
	$printByEtablissement = true;
	if (!$annee) {
		$annee = $_SESSION['annee'];
		$printByEtablissement = false;
	}
	$name_etab = getEtablissement($id_etab);
	$abrege_etab = getEtablissement($id_etab, $abrege=1);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	// Il existe une évaluation pour cet établissement
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		// L'évaluation n'est pas vide
		if (!empty($row->reponses)) {
			$assessment = unserialize($row->reponses);
			$reponses = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
			// L'évaluation est complète
			if (isAssessComplete($reponses[$annee])) {
				if ($row->valide) {
					$head = latexHead($annee);
					if ($printByEtablissement) {
						$head .= "\n\n\\begin{center}\n\n{\\Large{\\textcolor{myRed}{Rapport imprimé par l'établissement le \\today.}}}\n\n\\end{center}\n\n\\clearpage\n\n";
					}
					$first_section = printGraphsAndNotes($annee);
					$second_section = printAssessment($assessment, $annee);
					$annexes = printAnnexes();
					$foot = latexFoot();
					$rapport = $head."\n".$first_section."\n".$second_section."\n".$annexes."\n".$foot;
					makeRapport($abrege_etab, $rapport, $annee);
				} else {
					linkMsg($script, "L'évaluation de cet établissement n'a pas revue par les auditeurs.", "alert.png");
				}
			} else {
				linkMsg($script, "L'évaluation de cet établissement est incomplète.", "alert.png");
			}
		} else {
			linkMsg($script, "L'évaluation de cet établissement est vide.", "alert.png");
		}
	} else {
		linkMsg($script, "Il n'y a pas d'évaluation pour cet établissement.", "alert.png");
	}
}


function createWordDoc() {
	global $appli_titre_short, $appli_titre;
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->getCompatibility()->setOoxmlVersion(15);
	$phpWord->getSettings()->setThemeFontLang(new Language(Language::FR_FR));
	$phpWord->getSettings()->setDecimalSymbol(',');
	$phpWord->setDefaultFontName('Calibri');
	$phpWord->setDefaultFontSize(11);
	$phpWord->addTitleStyle(1, array('size' => 16, 'bold' => true), array('spaceBefore' => 600, 'spaceAfter' => 100));
	$phpWord->addTitleStyle(2, array('size' => 14, 'bold' => true), array('spaceBefore' => 400, 'spaceAfter' => 100));
	$phpWord->addTitleStyle(3, array('size' => 12, 'bold' => true), array('spaceBefore' => 200, 'spaceAfter' => 100));
	$properties = $phpWord->getDocInfo();
	$properties->setCreator($appli_titre_short);
	$properties->setTitle($appli_titre);
	$properties->setDescription($appli_titre);
	$properties->setCategory('Cybersecurity');
	$properties->setLastModifiedBy($appli_titre_short);
	return $phpWord;
}


function exportEval($script) {
	global $appli_titre, $appli_titre_short;
	$dir = dirname($_SERVER['PHP_SELF']);
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$abrege_etab = getEtablissement(intval($id_etab), $abrege=1);
	$fileDoc = sprintf("rapports/evaluation_%s.docx", $abrege_etab);
	$fileXlsx = sprintf("rapports/evaluation_%s.xlsx", $abrege_etab);

	$phpWord = createWordDoc();
	$myTableStyleName = 'myTable';
	$myTableStyle = array('borderColor'=>'444444', 'borderSize'=>8, 'cellMargin'=>50, 'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER, 'width'=>10000, 'unit'=>'pct');
	$myTableRowStyle = array('cantSplit'=>true);
	$myTableTitleCellStyle = array('bgColor'=>'eeeeee', 'valign'=>'center');
	$myTableCellStyle = array('valign'=>'center');
	$myTableSpanTitleCellStyle = array('bgColor'=>'eeeeee', 'valign'=>'center', 'gridSpan'=>2);
	$myTableSpanCellStyle = array('valign'=>'center', 'gridSpan'=>2);
	$phpWord->addTableStyle($myTableStyleName, $myTableStyle);

	$sectionStyle = array(
		'orientation' => 'portrait',
		'marginTop' => 800,
		'marginLeft' => 800,
		'marginRight' => 800,
		'marginBottom' => 800,
		'headerHeight' => 800,
		'footerHeight' => 800,
	);
	$section = $phpWord->addSection($sectionStyle);
	$header = $section->addHeader();
	$header->addPreserveText($appli_titre, null, array('spaceAfter' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
	$footer = $section->addFooter();
	$footer->addPreserveText('Page {PAGE}/{NUMPAGES}', null, array('spaceBefore' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle($appli_titre_short);
	$sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
	$sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
	$sheet->getSheetView()->setZoomScale(100);
	$sheet->getColumnDimension('A')->setWidth(40);
	$sheet->getColumnDimension('B')->setWidth(30);
	$sheet->getColumnDimension('C')->setWidth(45);
	$sheet->getColumnDimension('D')->setWidth(45);
	$sheet->getColumnDimension('E')->setWidth(8);
	$sheet->getColumnDimension('F')->setWidth(40);

	$eval = array();
	$eval[] = ['Thème', 'Domaine', 'Règle', 'Mesure', 'Note', 'Commentaire'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result=mysqli_query($base, $request);
	$row=mysqli_fetch_object($result);
	$nom=traiteStringFromBDD($row->nom);
	$req_assessment = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$res_assessment = mysqli_query($base, $req_assessment);

	if (mysqli_num_rows($res_assessment)) {
		$row_assessment=mysqli_fetch_object($res_assessment);
		if (!empty($row_assessment->reponses)) {
			$assessment = unserialize($row_assessment->reponses);
			$final_c = $row_assessment->comments;
			$reponses = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
			$req_par="SELECT * FROM paragraphe ORDER BY numero";
			$res_par=mysqli_query($base, $req_par);
			$section->addText($appli_titre, array('bold'=>true, 'size'=>20, 'smallCaps'=>true), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));
			if (intval($_SESSION['role']) == 2) {
				$msg = "Copie de travail de l'auditeur";
			} else {
				$msg = "Copie de travail de l'établissement";
			}
			$section->addText($msg, array('bold'=>true, 'size'=>16, 'smallCaps'=>true, 'color'=>'9e1e1e'), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));
			$section->addText($nom . " - " . $annee, array('bold'=>true, 'size'=>18, 'smallCaps'=>true, 'color'=>'444444'), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));

			while ($row_par=mysqli_fetch_object($res_par)) {
				$section->addTitle($row_par->numero . " " . traiteStringFromBDD($row_par->libelle), 1);
				$req_sub_par="SELECT * FROM sub_paragraphe WHERE id_paragraphe=\"$row_par->id\" ORDER BY numero";
				$res_sub_par=mysqli_query($base, $req_sub_par);
				while ($row_sub_par=mysqli_fetch_object($res_sub_par)) {
					$dtid = $row_par->numero.'.'.$row_sub_par->numero;
					$section->addTitle($dtid . " " . traiteStringFromBDD($row_sub_par->libelle), 2);
					$req_quest="SELECT * FROM question WHERE (id_paragraphe=\"$row_par->id\" AND id_sub_paragraphe=\"$row_sub_par->id\") ORDER BY numero";
					$res_quest=mysqli_query($base, $req_quest);
					while ($row_quest=mysqli_fetch_object($res_quest)) {
						$questID = $row_par->numero.'.'.$row_sub_par->numero.'.'.$row_quest->numero;
						$textID = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
						$noteID = 'question'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
						$rule = traiteStringFromBDD($row_quest->libelle);
						$measure = traiteStringFromBDD($row_quest->mesure);
						$comment = traiteStringFromBDD($assessment[$textID]);
						if (empty($comment)) { $comment = "Pas de commentaire"; }
						if (empty($measure)) { $measure = "Pas de recommandations particulière"; }

						$table = $section->addTable($myTableStyleName);
						$table->addRow(300, $myTableRowStyle);
						$table->addCell(7000, $myTableTitleCellStyle)->addText('Règle n°'.$questID);
						$table->addCell(3000, $myTableTitleCellStyle)->addText('Note');
						$table->addRow(500, $myTableRowStyle);
						$table->addCell(7000, $myTableCellStyle)->addText($rule);
						$table->addCell(3000, $myTableCellStyle)->addText($assessment[$noteID].' - '.textItem($assessment[$noteID]));
						$table->addRow(300, $myTableRowStyle);
						$table->addCell(10000, $myTableSpanTitleCellStyle)->addText('Commentaire');
						$table->addRow(500, $myTableRowStyle);
						$table->addCell(10000, $myTableSpanCellStyle)->addText($comment);
						$section->addTextBreak();

						$row = [traiteStringFromBDD($row_par->libelle), traiteStringFromBDD($row_sub_par->libelle), $rule, $measure, $assessment[$noteID], $comment];
						$eval[] = $row;
					}
				}
			}
			$sheet->fromArray($eval, NULL, 'A1');
			dbDisconnect($base);

			printf("<div class='row'>\n");
			printf("<div class='column left'>\n");
			$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
			$objWriter->save($fileDoc);
			$msg = "Télécharger l'évaluation (Word)";
			linkMsg($dir."/".$fileDoc, $msg, "docx.png", 'menu');
			printf("</div>\n<div class='column right'>\n");
			$objWriter = new Xlsx($spreadsheet);
			$objWriter->save($fileXlsx);
			$msg = "Télécharger l'évaluation (Excel)";
			linkMsg($dir."/".$fileXlsx, $msg, "xlsx.png", 'menu');
			printf("</div>\n</div>\n");
		} else {
			$msg = sprintf("L'évaluation pour l'année %d est vide", $annee);
			linkMsg($script, $msg, "alert.png");
		}
	} else {
		$msg = sprintf("Il n'y a pas d'évaluation pour l'année %d", $annee);
		linkMsg($script, $msg, "alert.png");
		footPage();
	}
}


function generateExcellRapport($annee) {
	$id_etab = $_SESSION['id_etab'];
	$fileXlsx = sprintf("rapports/plan_actions_%d.xlsx", $id_etab);

	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle('EvalSMSI');
	$sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
	$sheet->getParent()->getDefaultStyle()->getFont()->setSize(11);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	$sheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
	$sheet->getSheetView()->setZoomScale(100);
	$sheet->getColumnDimension('A')->setWidth(40);
	$sheet->getColumnDimension('B')->setWidth(30);
	$sheet->getColumnDimension('C')->setWidth(45);
	$sheet->getColumnDimension('D')->setWidth(8);
	$sheet->getColumnDimension('E')->setWidth(40);
	$sheet->getColumnDimension('F')->setWidth(40);
	$sheet->getColumnDimension('G')->setWidth(45);

	$eval = array();
	$eval[] = ['Thème', 'Domaine', 'Règle', 'Note', 'Commentaire', 'Commentaire évaluateur', 'Mesure'];
	$base = dbConnect();
	$nom = getEtablissement($id_etab);
	$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);

	// Il existe une évaluation pour cet établissement
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		// L'évaluation n'est pas vide
		if (!empty($row->reponses)) {
			$assessment = unserialize($row->reponses);
			$reponses = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
			// L'évaluation est complète
			if (isAssessComplete($reponses[$annee])) {
				if ($row->valide) {
					$req_par="SELECT * FROM paragraphe ORDER BY numero";
					$res_par=mysqli_query($base, $req_par);
					while ($row_par=mysqli_fetch_object($res_par)) {
						$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d' ORDER BY numero", $row_par->id);
						$res_sub_par=mysqli_query($base, $req_sub_par);
						while ($row_sub_par=mysqli_fetch_object($res_sub_par)) {
							$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
							$res_quest=mysqli_query($base, $req_quest);
							while ($row_quest=mysqli_fetch_object($res_quest)) {
								$questID = $row_par->numero.'.'.$row_sub_par->numero.'.'.$row_quest->numero;
								$commID = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
								$noteID = 'question'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
								$evalID = 'eval'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
								$par = traiteStringFromBDD($row_par->libelle);
								$subpar = traiteStringFromBDD($row_sub_par->libelle);
								$rule = traiteStringFromBDD($row_quest->libelle);
								$measure = traiteStringFromBDD($row_quest->mesure);
								$note = intval($assessment[$noteID]);
								$comment = traiteStringFromBDD($assessment[$commID]);
								$comeval = traiteStringFromBDD($assessment[$evalID]);
								if (empty($comment)) { $comment = "Pas de commentaire"; }
								if (empty($comeval)) { $comeval = "Avis conforme"; }
								if (empty($measure)) { $measure = "Pas de recommandations particulière"; }
								$row = [$par, $subpar, $rule, $note, $comment, $comeval, $measure];
								$eval[] = $row;
							}
						}
					}
					$sheet->fromArray($eval, NULL, 'A1');
				} else {
					linkMsg("etab.php", "L'évaluation de cet établissement n'a pas revue par les auditeurs.", "alert.png");
				}
			} else {
				linkMsg("etab.php", "L'évaluation de cet établissement est incomplète.", "alert.png");
			}
		} else {
			linkMsg("etab.php", "L'évaluation de cet établissement est vide.", "alert.png");
		}
	} else {
		linkMsg("etab.php", "Il n'y a pas d'évaluation pour cet établissement.", "alert.png");
	}
	dbDisconnect($base);

	foreach ($sheet->getRowIterator() as $row) {
		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(FALSE);
		foreach ($cellIterator as $key => $cell) {
			if ($key == 'D') {
				$note = $cell->getValue();
				if (is_numeric($note)) {
					if ($note == 1) { $color = "ffbebebe"; }
					if (($note == 2) || ($note == 3)) { $color = "ffff0000"; }
					if (($note == 4) || ($note == 5)) { $color = "ffffa500"; }
					if (($note == 6) || ($note == 7)) { $color = "ff00ff00"; }
					$cell->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
					$cell->getStyle()->getFill()->getStartColor()->setARGB($color);
				}
			}
		}
	}
	$objWriter = new Xlsx($spreadsheet);
	$objWriter->save($fileXlsx);
	return(dirname($_SERVER['PHP_SELF'])."/".$fileXlsx);
}


function exportRules() {
	global $appli_titre;
	$script = dirname($_SERVER['PHP_SELF']);
	$fileDoc = "rapports/referentiel.docx";
	$filePdf = "rapports/referentiel.pdf";

	$phpWord = createWordDoc();

	$protection = $phpWord->getSettings()->getDocumentProtection();
	$protection->setEditing(DocProtect::READ_ONLY);
	$protection->setPassword('qsldkeazrkjekqdsnvnxblgkjzerktjzretjhzer');

	$sectionStyle = array(
		'orientation' => 'portrait',
		'marginTop' => 800,
		'marginLeft' => 800,
		'marginRight' => 800,
		'marginBottom' => 800,
		'headerHeight' => 800,
		'footerHeight' => 800,
	);
	$section = $phpWord->addSection($sectionStyle);
	$header = $section->addHeader();
	$header->addPreserveText($appli_titre, null, array('spaceAfter' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
	$footer = $section->addFooter();
	$footer->addPreserveText('Page {PAGE}/{NUMPAGES}', null, array('spaceBefore' => 400, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
	$section->addText($appli_titre, array('bold'=>true, 'size'=>20, 'smallCaps'=>true), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter'=>500));

	$base = dbConnect();
	$req_par="SELECT * FROM paragraphe ORDER BY numero";
	$res_par=mysqli_query($base, $req_par);
	while ($row_par=mysqli_fetch_object($res_par)) {
		$msg = sprintf("%s\t%s", $row_par->numero, traiteStringFromBDD($row_par->libelle));
		if (intval($row_par->numero)>1) { $section->addPageBreak(); }
		$section->addTitle($msg, 1);
		$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d' ORDER BY numero", $row_par->id);
		$res_sub_par=mysqli_query($base, $req_sub_par);
		while ($row_sub_par=mysqli_fetch_object($res_sub_par)) {
			$msg = sprintf("%s.%s\t%s", $row_par->numero, $row_sub_par->numero, traiteStringFromBDD($row_sub_par->libelle));
			$section->addTitle($msg, 2);
			$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
			$res_quest=mysqli_query($base, $req_quest);
			while ($row_quest=mysqli_fetch_object($res_quest)) {
				$msg = sprintf("Règle %s.%s.%s ", $row_par->numero, $row_sub_par->numero, $row_quest->numero);
				$section->addTitle($msg, 3);
				$section->addText(traiteStringFromBDD($row_quest->libelle));
			}
		}
	}
	dbDisconnect($base);

	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
	$objWriter->save($fileDoc);
	$msg = "Télécharger le référentiel (Word)";
	linkMsg($script."/".$fileDoc, $msg, "docx.png", 'menu');
	printf("</div>\n<div class='column right'>\n");
	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
	$objWriter->save($filePdf);
	$msg = "Télécharger le référentiel (Adobe PDF)";
	linkMsg($script."/".$filePdf, $msg, "pdf.png", 'menu');
	printf("</div>\n</div>\n");
}


?>
