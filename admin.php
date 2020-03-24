<?php
/*=========================================================
// File:        admin.php
// Description: administrator page of EvalSMSI
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
include("funct_admin.php");
session_set_cookie_params([
	'lifetime' => $cookie_timeout,
	'path' => '/',
	'domain' => $cookie_domain,
	'secure' => $session_secure,
	'httponly' => $cookie_httponly,
	'samesite' => $cookie_samesite
]);
session_start();
$authorizedRole = array('1');
isSessionValid($authorizedRole);
headPage($appli_titre, "Administration");



if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'new_user':
		createUser();
		footPage();
		break;

	case 'record_user':
		if (recordUser('add')) {
			linkMsg($_SESSION['curr_script'], "Utilisateur ajouté dans la base", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'select_user':
		selectUserModif();
		footPage();
		break;

	case 'modif_user':
		$_SESSION['current_user'] = $_POST['user'];
		modifUser();
		footPage();
		break;

	case 'update_user':
		if (recordUser('update')) {
			linkMsg($_SESSION['curr_script'], "Utilisateur modifié dans la base", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de modification", "alert.png");
		}
		footPage();
		break;

	case 'new_regroup':
		createEtablissement('regroup');
		footPage();
		break;

	case 'maintenance':
		maintenanceBDD();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'select_etab':
		selectEtablissementModif();
		footPage();
		break;

	case 'modif_etab':
		$_SESSION['current_etab'] = intval($_POST['etablissement']);
		modifEtablissement();
		footPage();
		break;

	case 'update_etab':
		if (recordEtablissement('update')) {
			linkMsg($_SESSION['curr_script'], "Etablissement modifié dans la base", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de modification", "alert.png");
		}
		footPage();
		break;

	case 'update_regroup':
		if (recordEtablissement('update_regroup')) {
			linkMsg($_SESSION['curr_script'], "Etablissement modifié dans la base", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de modification", "alert.png");
		}
		footPage();
		break;

	case 'new_etab':
		createEtablissement();
		footPage();
		break;

	case 'record_etab':
		if (recordEtablissement('add')) {
			linkMsg($_SESSION['curr_script'], "Etablissement créé dans la base", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'record_regroup':
		if (recordEtablissement('add_regroup')) {
			linkMsg($_SESSION['curr_script'], "Etablissement créé dans la base", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'select_quiz':
		selectQuizModification();
		footPage();
		break;

	case 'modif_quiz':
		$_SESSION['quiz'] = intval($_POST['quiz']);
		modifications();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'bilan_etab':
		bilanByEtab();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'rm_token':
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuAdmin();
		footPage();
		break;

	default:
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuAdmin();
		footPage();
		break;
	}
} else {
	menuAdmin();
	footPage();
}

?>
