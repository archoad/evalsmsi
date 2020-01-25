<?php
/*=========================================================
// File:        audit.php
// Description: auditor page of EvalSMSI
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
include("funct_audit.php");
session_start();
$authorizedRole = array('2');
isSessionValid($authorizedRole);
headPage($appli_titre, "Audit");
$script = basename($_SERVER['PHP_SELF']);


if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'office':
		selectEtablissementAudit();
		footPage();
		break;

	case 'do_office':
		if (isEtabLegitimate($_POST['id_etab'])) {
			exportEval($script);
			footPage($script, "Accueil");
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'graph':
		selectEtablissementAudit();
		footPage();
		break;

	case 'do_graph':
		if (isEtabLegitimate($_POST['id_etab'])) {
			if (isThereAssessForEtab()) {
				if (isRegroupEtab()) {
					menu_synthese();
					footPage($script, "Accueil");
				} else {
					printf("<script type='text/javascript'>window.onload = function() { loadGraphYear(); }</script>");
					displayEtablissmentGraphs();
					footPage($script, "Accueil");
				}
			} else {
				$msg = sprintf("L'évaluation pour %d n'a pas été créée.", $_SESSION['annee']);
				linkMsg($script, $msg, "alert.png");
				footPage();
			}
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'synthese':
		graphSynthese();
		footPage($script, "Accueil");
		break;

	case 'bilan':
		graphBilan();
		footPage($script, "Accueil");
		break;

	case 'audit':
		selectEtablissementAudit();
		footPage();
		break;

	case 'display_audit':
		if (isEtabLegitimate($_POST['id_etab'])) {
			if (isRegroupEtab()) {
				if (isAssessGroupValidate()) {
					if (isThereAssessForEtab()) {
						displayAuditRegroup();
					} else {
						if (createAssessmentRegroup()) {
							$msg = sprintf("L'évaluation pour %s a été crée dans la base. Cliquer pour continuer...", $_SESSION['annee']);
							linkMsg($script, $msg, "ok.png");
						} else {
							linkMsg($script, "Aucune évaluation disponible.", "alert.png");
						}
					}

				}
			} else {
				displayAudit();
			}
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
		}
		footPage();
		break;

	case 'record_audit':
		if (writeAudit()) {
			linkMsg($script, "Evaluation mise à jour.", "ok.png");
		} else {
			linkMsg($script, "Erreur de mise à jour.", "alert.png");
		}
		footPage();
		break;

	case 'rap_etab':
		selectEtablissementAudit();
		footPage();
		break;

	case 'prepare_rapport':
		if (isEtabLegitimate($_POST['id_etab'])) {
			printf("<script type='text/javascript'>window.onload = function() { loadGraphYear(); }</script>");
			getCommentGraphPar();
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
		}
		footPage();
		break;

	case 'record_comment':
		if (recordCommentGraph()) {
			generateRapport($script, $result);
		} else {
			linkMsg($script, "Erreur lors de la sauvegarde des commentaires.", "alert.png");
		}
		footPage($script, "Accueil");
		break;

	case 'journal':
		selectEtablissementAudit();
		footPage();
		break;

	case 'display_journal':
		if (isEtabLegitimate($_POST['id_etab'])) {
			printf("<script type='text/javascript'>window.onload = function() { loadLogs(); }</script>");
			journalisation();
			footPage($script, "Accueil");
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'password':
		changePassword($script);
		break;

	case 'chg_password':
		if (recordNewPassword($_POST['new1'])) {
			linkMsg($script, "Mot de passe changé avec succès", "ok.png");
		} else {
			linkMsg($script, "Erreur de changement de mot de passe", "alert.png");
		}
		footPage();
		break;

	case 'objectif':
		selectEtablissementAudit();
		footPage();
		break;

	case 'display_objectif':
		if (isEtabLegitimate($_POST['id_etab'])) {
			objectifs();
			footPage($script, "Accueil");
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'write_objectifs':
		if (recordObjectifs()) {
			linkMsg($script, "Objectifs enregistrés dans la base.", "ok.png");
		} else {
			linkMsg($script, "Erreur d'enregistrement.", "alert.png");
		}
		footPage();
		break;

	case 'delete':
		selectEtablissementAudit();
		footpage();
		break;

	case 'valid_delete':
		if (isEtabLegitimate($_POST['id_etab'])) {
			if (isThereAssessForEtab()) {
				printf("<script type='text/javascript'>window.onload = function() { loadGraphYear(); }</script>");
				confirmDeleteAssessment($script);
				footPage();
			} else {
				linkMsg($script, "Il n'y a pas d'évaluation pour cet établissement.", "alert.png");
				footpage();
			}
		} else {
			linkMsg($script, "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'do_delete':
		deleteAssessment();
		footPage();
		break;

	case 'rm_token':
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuAudit();
		footPage();
		break;

	default:
		if (isset($_SESSION['token'])) {
			unset($_SESSION['token']);
		}
		menuAudit();
		footPage();
	}
} else {
	menuAudit();
	footPage();
}



?>





<script type='text/javascript' src='js/chart.min.js'></script>
<script type='text/javascript' src='js/vis.min.js'></script>
<script type='text/javascript' src='js/evalsmsi.js'></script>
<script type='text/javascript' src='js/graphs.js'></script>
