<?php
/*=========================================================
// File:		aide.php
// Description: Help page of EvalSMSI
// Created:	 2009-01-01
// Licence:	 GPL-3.0-or-later
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
session_start();
$authorizedRole = array('3', '4', '100');
isSessionValid($authorizedRole);
headPage($appli_titre, "Aide et documentation");
$script = basename($_SERVER['PHP_SELF']);

function about() {
	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	printf("<p><img src='pict/logoCerbere.png' alt='logo SSI' /></p>\n");
	printf("<p><b>Remerciements:</b> Merci à Philippe Loudenot pour son aide et son soutien, merci à Ange Ferrari, à Christophe Grenier, à Sébastien Duquette de Corelan Team et à Xavier Beule pour leurs contributions dans la correction de bugs.</p>\n");
	printf("<p><b>EvalSMSI version 4.2 - 12/05/2019</b></p>\n");
	printf("</div>\n<div class='column right'>\n");
	printf("<p>Le programme d'évaluation du SMSI - <i>evalSMSI</i> - est écrit en PHP et utilise une base MySQL. Son objectif est de fournir un outil d'évaluation simple à mettre en oeuvre et facile d'utilisation.</p>\n");
	printf("<p>The ISMS assessment program - <i>evalSMSI</i> - is written in PHP and uses a MySQL database. Its objective is to provide an evaluation tool that is simple to implement and easy to use.</p>\n");
	printf("<p>Copyright (C) 2009 - 2019 Michel Dubois</p>\n");
	printf("<p>This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.</p>\n");
	printf("<p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.</p>\n");
	printf("<p><b>You should have received a copy of the GNU General Public License along with this program.</b></p><p>If not, see <a href='http://www.gnu.org/licenses/'>the GNU web site</a>.</p>\n");
	printf("<table style='background:none;'>\n<tr style='background:none;'>\n");
	printf("<td><img src='pict/gplv3.png' alt='logo GPL V3' /></td>\n");
	printf("<td><img src='pict/html5.png' alt='valid html5' /></td>\n");
	printf("<td><img src='pict/css3.png' alt='valid css3' /></td>\n</tr>\n");
	printf("<tr style='background:none;'>\n");
	printf("<td><a href='https://jpgraph.net'><img src='pict/jpgraph.png' alt='logo jpgraph' /></a></td>\n");
	printf("<td><a href='https://github.com/PHPOffice'><img src='pict/phpoffice.png' alt='logo phpoffice' /></a></td>\n");
	printf("</tr>\n</table>\n</div>\n</div>\n");
}


function docSoftware() {
	printf("<div class='onecolumn'>\n");
	printf("<h2>Fonctionnement du logiciel</h2>\n");
	printf("<h3>Fonctionnement global</h3>\n");
	printf("<p>Le principe de fonctionnement d'EvalSMSI est le suivant:</p>\n");
	printf("<ol>\n");
	printf("<li>Information de l'entité du déroulement d'un audit de son SMSI</li>\n");
	printf("<li>Autoévaluation de l'entité</li>\n");
	printf("<li>Audit de l'entité, l'auditeur annote l'évaluation réalisée par l'entité</li>\n");
	printf("<li>Validation de l'audit et émission du rapport</li>\n");
	printf("</ol>\n");
	printf("<h3>Utilisateur (RSSI)</h3>\n");
	printf("<p>Une fois en possession de son login et de son mot de passe, le RSSI peut se connecter à l'application en cliquant sur le cartouche \"Accès établissement\".</p><p>La page qui s'affiche alors présente 4 cartouches:</p>\n");
	printf("<ul>\n");
	printf("<li>Réaliser une évaluation</li>\n<p>Ce menu affiche le formulaire du questionnaire d'autoévaluation.</p>\n");
	printf("<li>Imprimer les rapports</li>\n<p>Ce menu permet d'imprimer les rapports validés des années précédentes.</p>\n");
	printf("<li>Graphes établissement</li>\n<p>Ce menu affiche les graphes de notation avec superposition des années précédentes.</p>\n");
	printf("<li>Export OpenOffice</li>\n<p>Menu d'export du formulaire en cours.</p>\n");
	printf("</ul>");
	printf("<h3>Administrateur (Auditeur)</h3>\n");
	printf("</div>\n");
}


function docISMSeval() {
	printf("<div class='onecolumn'>\n");
	printf("<h2>EvalSMSI</h2>\n");
	printf("<p>L'objectif de ce questionnaire est d'évaluer, par rapport à un référentiel précis, le système de management de la sécurité de l'information (SMSI).</p>\n");
	printf("<p>L'approche processus pour le management de la sécurité de l'information incite ses utilisateurs à souligner l'importance de:</p>\n");
	printf("<ul>\n<li>la compréhension des exigences relatives à la sécurité de l'information d'un organisme, et la nécessité de mettre en place une politique et des objectifs en matière de sécurité de l'information;</li>\n");
	printf("<li>la mise en oeuvre et l'exploitation des mesures de gestion des risques liés à la sécurité de l'information d'un organisme dans le contexte des risques globaux liés à l'activité de l'organisme;</li>\n");
	printf("<li>la surveillance et le réexamen des performances et de l'efficacité du SMSI;</li>\n");
	printf("<li>l'amélioration continue du système sur la base de mesures objectives.</li>\n</ul>");
	printf("<p>C'est le modèle de processus Planifier - Déployer - Contrôler - Agir (PDCA) qui est appliqué à la structure des processus du SMSI.</p>\n");
	printf("<h2>Le modèle PDCA et la roue de Deming</h2>\n");
	printf("<p>La roue de Deming est une illustration de la méthode qualité PDCA (Plan-Do-Check-Act). Son nom vient du statisticien William Edwards Deming. Ce dernier n'a pas inventé le principe du PDCA, mais il l'a popularisé dans les années 50 en présentant cet outil au Nippon Keidanren.</p>\n");
	printf("<p>La méthode comporte quatre étapes, chacune entraînant l'autre, et vise à établir un cercle vertueux. Sa mise en place doit permettre d'améliorer sans cesse la qualité d'un produit, d'une œuvre, d'un service...</p>\n");
	printf("<ol>\n<li><b>Plan</b>: Préparer, Planifier (ce que l'on va réaliser)</li>\n<li><b>Do</b>: Développer, réaliser, mettre en œuvre</li>\n<li><b>Check</b>: Contrôler, vérifier</li>\n<li><b>Act</b>: Agir, réagir</li></ol>\n");
	printf("<h2>Le modèle PDCA appliqué au SMSI</h2>\n");
	printf("<p>Appliqué au Système de Management de la Sécurité de l'Information, le PDCA se traduit selon le schéma suivant:</p>\n");
	printf("<img src='pict/pdca.png' alt='PDCA' />");
	printf("<p><b>Planifier</b>: Etablir la politique, les objectifs, les processus et les procédures du SMSI relatives à la gestion du risque et à l'amélioration de la sécurité de l'information de manière à fournir des résultats conformément aux politiques et aux objectifs globaux de l'organisme.</p>\n");
	printf("<p><b>Déployer</b>: Mettre en oeuvre et exploiter la politique, les mesures, les processus et les procédures du SMSI.</p>");
	printf("<p><b>Contrôler</b>: Evaluer et, le cas échéant, mesurer les performances des processus par rapport à la politique, aux objectifs et à l'expérience pratique et rendre compte des résultats à la direction pour réexamen.</p>");
	printf("<p><b>Agir</b>: Entreprendre les actions correctives et préventives, sur la base des résultats de l'audit interne du SMSI et la revue de direction, ou d'autres informations pertinentes, pour une amélioration continue dudit système.</p>");
	printf("</div>\n");
}


function afficheLexique() {
	global $cheminJSON;
	$jsonFile = sprintf("%slexique.json", $cheminJSON);
	$jsonSource = file_get_contents($jsonFile);
	$jsonLexique = json_decode($jsonSource);
	printf("<div class='onecolumn'>\n");
	printf("<dl>\n");
	foreach ($jsonLexique as $item) {
		printf("<dt>%s</dt>\n", $item->terme);
		printf("<dd>%s</dd>\n", $item->definition);
	}
	printf("</dl>\n");
	printf("</div>\n");
}


function menu() {
	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	linkMsg("aide.php?action=h_logiciel", "Aide sur le logiciel", "help.png", "menu");
	linkMsg("aide.php?action=h_eval", "L'évaluation du SMSI", "eval_ssi.png", "menu");
	printf("</div><div class='column right'>\n");
	linkMsg("aide.php?action=lexique", "Lexique", "lexique.png", "menu");
	linkMsg("aide.php?action=about", "A propos", "about.png", "menu");
	printf("</div>\n</div>\n");
}


if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'h_logiciel':
		docSoftware();
		footPage($script, "Retour");
		break;

	case 'h_eval':
		docISMSeval();
		footPage($script, "Retour");
		break;

	case 'lexique':
		afficheLexique();
		footPage($script, "Retour");
		break;

	case 'about':
		about();
		footPage($script, "Retour");
		break;

	default:
		menu();
		footPage("etab.php", "Accueil");
	}
} else {
	menu();
	if ($_SESSION['role'] === '100') {
		footPage("evalsmsi.php", "Accueil");
	} else {
		footPage("etab.php", "Accueil");
	}
}

?>
