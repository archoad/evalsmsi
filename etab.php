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
$script = basename($_SERVER['PHP_SELF']);
$_SESSION['etab_graph'] = $_SESSION['etablissement'];

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'continue_assess':
		if (isThereAssessForEtab($_SESSION['etablissement'])) {
			printf("<script type='text/javascript'>window.onload = function() { progresse(); }</script>");
			displayAssessment();
		} else {
			if (createAssessment()) {
				$msg = sprintf("L'évaluation pour %s a été créée dans la base. Cliquer pour continuer...", $_SESSION['annee']);
				linkMsg("etab.php?action=continue_assess", $msg, "ok.png");
			} else {
				linkMsg($script, "Aucune évaluation disponible.", "alert.png");
			}
		}
		footPage();
		break;

	case 'make_assess':
		if (writeAssessment()) {
			linkMsg($script, "Evaluation mise à jour.", "ok.png");
		} else {
			linkMsg($script, "Erreur de mise à jour.", "alert.png");
		}
		footPage();
		break;

	case 'graph':
		if (isThereAssessForEtab($_SESSION['etablissement'])) {
			printf("<script type='text/javascript'>window.onload = function() { loadGraphYear(); }</script>");
			displayEtablissmentGraphs();
			footPage($script, "Accueil");
		} else {
			$msg = sprintf("L'évaluation pour %d n'a pas été créée.", $_SESSION['annee']);
			linkMsg($script, $msg, "alert.png");
			footPage();
		}
		break;

	case 'print':
		selectYearRapport();
		footPage();
		break;

	case 'do_print':
		exportRapport($script, intval($_POST['year']));
		footPage($script, "Accueil");
		break;

	case 'office':
		exportEval($script, $_SESSION['etablissement']);
		footPage($script, "Accueil");
		break;

	case 'rules':
		exportRules();
		footPage($script, "Accueil");
		break;

	case 'password':
		changePassword($script);
		footPage();
		break;

	case 'chg_password':
		if (recordNewPassword($_POST['new1'])) {
			linkMsg($script, "Mot de passe changé avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur de changement de mot de passe", "alert.png");
		}
		footPage();
		break;

	default:
		menuEtab();
		footPage();
	}
} else {
	menuEtab();
	footPage();
}

?>





<script type='text/javascript' src='js/chart.min.js'></script>
<script type='text/javascript' src='js/evalsmsi.js'></script>
<script type='text/javascript' src='js/graphs.js'></script>
