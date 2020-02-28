<?php
/*=========================================================
// File:        etab.php
// Description: user page of EvalSMSI
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
include("funct_etab.php");
session_start();
$authorizedRole = array('3', '4');
isSessionValid($authorizedRole);
headPage($appli_titre);
purgeRapportsFiles();




if (isset($_GET['action'])) {
	switch ($_GET['action']) {

	case 'continue_assess':
		if (isThereAssessForEtab()) {
			displayAssessment();
		} else {
			if (createAssessment()) {
				$msg = sprintf("L'évaluation pour %s a été créée dans la base. Cliquer pour continuer...", $_SESSION['annee']);
				linkMsg("etab.php?action=continue_assess", $msg, "ok.png");
			} else {
				linkMsg($_SESSION['curr_script'], "Aucune évaluation disponible.", "alert.png");
			}
		}
		footPage();
		break;

	case 'make_assess':
		if (writeAssessment()) {
			linkMsg($_SESSION['curr_script'], "Evaluation mise à jour.", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de mise à jour.", "alert.png");
		}
		footPage();
		break;

	case 'graph':
		if (isThereAssessForEtab()) {
			displayEtablissmentGraphs();
			footPage($_SESSION['curr_script'], "Accueil");
		} else {
			$msg = sprintf("L'évaluation pour %d n'a pas été créée.", $_SESSION['annee']);
			linkMsg($_SESSION['curr_script'], $msg, "alert.png");
			footPage();
		}
		break;

	case 'print':
		selectYearRapport();
		footPage();
		break;

	case 'do_print':
		exportRapport(intval($_POST['year']));
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'office':
		exportEval();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'rules':
		exportRules();
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'password':
		changePassword();
		footPage();
		break;

	case 'chg_password':
		if (recordNewPassword($_POST['new1'])) {
			linkMsg($_SESSION['curr_script'], "Mot de passe changé avec succès", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de changement de mot de passe", "alert.png");
		}
		footPage();
		break;

	case 'choose_quiz':
		chooseQuiz();
		footPage();
		break;

	case 'set_quiz':
		if (setRightQuiz($_POST['id_quiz'])) {
			header("Location: ".$_SESSION['curr_script']);
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de référentiel", "alert.png");
			footPage();
		}
		break;

	case 'rm_token':
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuEtab();
		footPage();
		break;

	default:
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuEtab();
		footPage();
	}
} else {
	menuEtab();
	footPage();
}


?>
