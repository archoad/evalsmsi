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
session_set_cookie_params([
	'lifetime' => $cookie_timeout,
	'path' => '/',
	'domain' => $cookie_domain,
	'secure' => $session_secure,
	'httponly' => $cookie_httponly,
	'samesite' => $cookie_samesite
]);
session_start();
$authorizedRole = array('2');
isSessionValid($authorizedRole);
headPage($appli_titre, "Audit");
purgeRapportsFiles();


if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'office':
		selectEtablissementAudit();
		footPage();
		break;

	case 'do_office':
		if (isEtabLegitimate($_POST)) {
			exportEval();
			footPage($_SESSION['curr_script'], "Accueil");
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'graph':
		selectEtablissementAudit();
		footPage();
		break;

	case 'do_graph':
		if (isEtabLegitimate($_POST)) {
			if (isThereAssessForEtab()) {
				getObjectives();
				displayEtablissmentGraphs();
				footPage($_SESSION['curr_script'], "Accueil");
			} else {
				$msg = sprintf("L'évaluation pour %d n'a pas été créée.", $_SESSION['annee']);
				linkMsg($_SESSION['curr_script'], $msg, "alert.png");
				footPage();
			}
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'audit':
		selectEtablissementAudit();
		footPage();
		break;

	case 'display_audit':
		if (isEtabLegitimate($_POST)) {
			if (isRegroupEtab()) {
				if (isAssessGroupValidate()) {
					if (isThereAssessForEtab()) {
						displayAuditRegroup();
					} else {
						if (createAssessmentRegroup()) {
							$msg = sprintf("L'évaluation pour %s a été crée dans la base. Cliquer pour continuer...", $_SESSION['annee']);
							linkMsg($_SESSION['curr_script'], $msg, "ok.png");
						} else {
							linkMsg($_SESSION['curr_script'], "Aucune évaluation disponible.", "alert.png");
						}
					}

				}
			} else {
				displayAudit();
			}
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
		}
		footPage();
		break;

	case 'record_audit':
		if (writeAudit()) {
			linkMsg($_SESSION['curr_script'], "Evaluation mise à jour.", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de mise à jour.", "alert.png");
		}
		footPage();
		break;

	case 'rap_etab':
		selectEtablissementAudit();
		footPage();
		break;

	case 'prepare_rapport':
		if (isEtabLegitimate($_POST)) {
			getCommentGraphPar();
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
		}
		footPage();
		break;

	case 'record_comment':
		if (recordCommentGraph()) {
			generateRapport();
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur lors de la sauvegarde des commentaires.", "alert.png");
		}
		footPage($_SESSION['curr_script'], "Accueil");
		break;

	case 'journal':
		selectEtablissementAudit();
		footPage();
		break;

	case 'display_journal':
		if (isEtabLegitimate($_POST)) {
			journalisation();
			footPage($_SESSION['curr_script'], "Accueil");
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'password':
		changePassword();
		break;

	case 'chg_password':
		if (recordNewPassword($_POST['new1'])) {
			linkMsg($_SESSION['curr_script'], "Mot de passe changé avec succès", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur de changement de mot de passe", "alert.png");
		}
		footPage();
		break;

	case 'objectif':
		selectEtablissementAudit();
		footPage();
		break;

	case 'display_objectif':
		if (isEtabLegitimate($_POST)) {
			objectifs();
			footPage($_SESSION['curr_script'], "Accueil");
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'write_objectifs':
		if (recordObjectifs()) {
			linkMsg($_SESSION['curr_script'], "Objectifs enregistrés dans la base.", "ok.png");
		} else {
			linkMsg($_SESSION['curr_script'], "Erreur d'enregistrement.", "alert.png");
		}
		footPage();
		break;

	case 'delete':
		selectEtablissementAudit();
		footpage();
		break;

	case 'valid_delete':
		if (isEtabLegitimate($_POST)) {
			if (isThereAssessForEtab()) {
				confirmDeleteAssessment();
				footPage();
			} else {
				linkMsg($_SESSION['curr_script'], "Il n'y a pas d'évaluation pour cet établissement.", "alert.png");
				footpage();
			}
		} else {
			linkMsg($_SESSION['curr_script'], "Etablissement invalide", "alert.png");
			footPage();
		}
		break;

	case 'do_delete':
		deleteAssessment();
		footPage();
		break;

	case 'regwebauthn':
		registerWebauthnCred();
		footPage();
		break;

	case 'authentication':
		menuAuthentication();
		footPage($_SESSION['curr_script'], "Accueil");
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
