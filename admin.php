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
session_start();
$authorizedRole = array('1');
isSessionValid($authorizedRole);
headPage($appli_titre, "Administration");
$script = sanitizePhpSelf($_SERVER['PHP_SELF']);


if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'new_user':
		createUser();
		footPage();
		break;

	case 'record_user':
		if (recordUser('add')) {
			linkMsg($script, "Utilisateur ajouté dans la base", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
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
			linkMsg($script, "Utilisateur modifié dans la base", "ok.png");
		} else {
			linkMsg($script, "Erreur de modification", "alert.png");
		}
		footPage();
		break;

	case 'new_regroup':
		createEtablissement('regroup');
		footPage();
		break;

	case 'maintenance':
		maintenanceBDD();
		footPage($script, "Accueil");
		break;

	case 'select_etab':
		selectEtablissementModif();
		footPage();
		break;

	case 'modif_etab':
		$_SESSION['current_etab'] = $_POST['etablissement'];
		modifEtablissement();
		footPage();
		break;

	case 'update_etab':
		if (recordEtablissement('update')) {
			linkMsg($script, "Etablissement modifié dans la base", "ok.png");
		} else {
			linkMsg($script, "Erreur de modification", "alert.png");
		}
		footPage();
		break;

	case 'update_regroup':
		if (recordEtablissement('update_regroup')) {
			linkMsg($script, "Etablissement modifié dans la base", "ok.png");
		} else {
			linkMsg($script, "Erreur de modification", "alert.png");
		}
		footPage();
		break;

	case 'new_etab':
		createEtablissement();
		footPage();
		break;

	case 'record_etab':
		if (recordEtablissement('add')) {
			linkMsg($script, "Etablissement créé dans la base", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'record_regroup':
		if (recordEtablissement('add_regroup')) {
			linkMsg($script, "Etablissement créé dans la base", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'add_par':
		addParagraphs();
		footPage();
		break;

	case 'modif_par':
		$_SESSION['modif_par'] = intval($_GET['value']);
		modifParagraphs();
		break;

	case 'update_par':
		if (recordParagraph($_POST, 'modif')) {
			linkMsg($script."?action=modifications", "Domaine modifié avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'new_par':
		if (recordParagraph($_POST, 'add')) {
			linkMsg($script, "Domaine ajouté avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'add_sub_par':
		addSubParagraphs();
		footPage();
		break;

	case 'new_sub_par':
		if (recordSubParagraph($_POST, 'add')) {
			linkMsg($script, "Sous-domaine ajouté avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'modif_sub_par':
		$_SESSION['modif_subpar'] = intval($_GET['value']);
		modifSubParagraphs();
		break;

	case 'update_subpar':
		if (recordSubParagraph($_POST, 'modif')) {
			linkMsg($script."?action=modifications", "Sous-domaine modifié avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'add_quest':
		addQuestion();
		footPage();
		break;

	case 'new_question':
		if (recordQuestion($_POST, 'add')) {
			linkMsg($script, "Question ajoutée avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'modif_question':
		$_SESSION['modif_quest'] = intval($_GET['value']);
		modifQuestion();
		break;

	case 'update_question':
		if (recordQuestion($_POST, 'modif')) {
			linkMsg($script."?action=modifications", "Question modifiée avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement", "alert.png");
		}
		footPage();
		break;

	case 'modifications':
		modifications();
		footPage($script, "Accueil");
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




<script type='text/javascript' src='js/evalsmsi.js'></script>
