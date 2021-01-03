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
startSession();
$authorizedRole = array('2', '3', '4', '5', '100');
isSessionValid($authorizedRole);
headPage($appli_titre, "Aide et documentation");


function about() {
	global $progVersion, $progDate;
	genSyslog(__FUNCTION__);
	printf("<div class='row'>");
	printf("<div class='column left'>");
	printf("<p><a href='https://www.archoad.io/'><img src='pict/logoArchoad.png' alt='logo SSI'></a></p>");
	printf("</div><div class='column right'>");
	printf("<p><b>EvalSMSI version %s - %s</b></p>", $progVersion, $progDate);
	printf("<p><b>Copyright (C) 2009 - %s Michel Dubois</b></p>", mb_strtolower(strftime("%Y", time())));
	printf("<p><b>Remerciements:</b> Merci à Philippe Loudenot pour son aide et son soutien, merci à Ange Ferrari, à Christophe Grenier, à Sébastien Duquette de Corelan Team et à Xavier Beule pour leurs contributions dans la correction de bugs.</p>");
	printf("<p>Le programme d'évaluation du SMSI - <i>evalSMSI</i> - est écrit en PHP et utilise une base MySQL. Son objectif est de fournir un outil d'évaluation simple à mettre en oeuvre et facile d'utilisation.</p>");
	printf("<p>The ISMS assessment program - <i>evalSMSI</i> - is written in PHP and uses a MySQL database. Its objective is to provide an evaluation tool that is simple to implement and easy to use.</p>");
	printf("<p>This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.</p>");
	printf("<p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.</p>");
	printf("<p><b>You should have received a copy of the GNU General Public License along with this program.</b></p><p>If not, see <a href='http://www.gnu.org/licenses/'>the GNU web site</a>.</p>");
	printf("<table><tr>");
	printf("<td><a href='https://www.gnu.org/licenses/gpl-3.0.txt'><img src='pict/gplv3.png' alt='logo GPL V3'></a></td>");
	printf("<td><a href='https://github.com/PHPOffice'><img src='pict/phpoffice.png' alt='logo phpoffice'></a></td>");
	printf("<td><img src='pict/html5.png' alt='valid html5'></td>");
	printf("<td><img src='pict/css3.png' alt='valid css3'></td>");
	printf("<td><a href='https://github.com/lbuchs/WebAuthn'><img src='pict/lbuchs.png' alt='lbuchs'></a></td>");
	printf("</tr></table></div></div>");
}


function doc_global() {
	printf("<h3>Fonctionnement global</h3>");
	printf("<p>Le principe de fonctionnement d'EvalSMSI est le suivant:</p>");
	printf("<ol>");
	printf("<li>Autoévaluation de l'entité</li>");
	printf("<li>Audit de l'entité, l'auditeur annote l'évaluation réalisée par l'entité</li>");
	printf("<li>Validation de l'audit et émission du rapport</li>");
	printf("</ol>");
	printf("<p>Ces étapes se déroulent sur une période de 1 an (du 1er janvier au 31 décembre).</p>");
}


function doc_connexion() {
	printf("<div class='onecolumn'>");
	printf("</div>");
}


function doc_etab() {
	printf("<div class='onecolumn'>");
	doc_global();
	printf("<img src='pict/docpict/etab1.png' alt='etab1'><br />");
	printf("<img src='pict/docpict/etab2.png' alt='etab1'><br />");
	printf("<img src='pict/docpict/etab3.png' alt='etab1'><br />");
	printf("<img src='pict/docpict/etab4.png' alt='etab1'><br />");
	printf("</div>");
}


function doc_audit() {
	printf("<div class='onecolumn'>");
	doc_global();
	printf("<img src='pict/docpict/audit1.png' alt='etab1'><br />");
	printf("<img src='pict/docpict/audit2.png' alt='etab1'><br />");
	printf("<img src='pict/docpict/audit3.png' alt='etab1'><br />");
	printf("</div>");
}


function docSoftware() {
	genSyslog(__FUNCTION__);
	switch ($_SESSION['role']) {
		case '100':
			doc_connexion();
			break;
		case '3':
		case '4':
		case '5':
			doc_etab();
			break;
		case '2':
			doc_audit();
			break;
		default:
			menu();
			break;
	}
}


function docISMSeval() {
	genSyslog(__FUNCTION__);
	printf("<div class='onecolumn'>");
	printf("<h2>EvalSMSI</h2>");
	printf("<p>L'objectif de ce questionnaire est d'évaluer, par rapport à un référentiel précis, le système de management de la sécurité de l'information (SMSI).</p>");
	printf("<p>L'approche processus pour le management de la sécurité de l'information incite ses utilisateurs à souligner l'importance de:</p>");
	printf("<ul><li>la compréhension des exigences relatives à la sécurité de l'information d'un organisme, et la nécessité de mettre en place une politique et des objectifs en matière de sécurité de l'information;</li>");
	printf("<li>la mise en oeuvre et l'exploitation des mesures de gestion des risques liés à la sécurité de l'information d'un organisme dans le contexte des risques globaux liés à l'activité de l'organisme;</li>");
	printf("<li>la surveillance et le réexamen des performances et de l'efficacité du SMSI;</li>");
	printf("<li>l'amélioration continue du système sur la base de mesures objectives.</li></ul>");
	printf("<p>C'est le modèle de processus Planifier - Déployer - Contrôler - Agir (PDCA) qui est appliqué à la structure des processus du SMSI.</p>");
	printf("<h2>Le modèle PDCA et la roue de Deming</h2>");
	printf("<p>La roue de Deming est une illustration de la méthode qualité PDCA (Plan-Do-Check-Act). Son nom vient du statisticien William Edwards Deming. Ce dernier n'a pas inventé le principe du PDCA, mais il l'a popularisé dans les années 50 en présentant cet outil au Nippon Keidanren.</p>");
	printf("<p>La méthode comporte quatre étapes, chacune entraînant l'autre, et vise à établir un cercle vertueux. Sa mise en place doit permettre d'améliorer sans cesse la qualité d'un produit, d'une œuvre, d'un service...</p>");
	printf("<ol><li><b>Plan</b>: Préparer, Planifier (ce que l'on va réaliser)</li><li><b>Do</b>: Développer, réaliser, mettre en œuvre</li><li><b>Check</b>: Contrôler, vérifier</li><li><b>Act</b>: Agir, réagir</li></ol>");
	printf("<h2>Le modèle PDCA appliqué au SMSI</h2>");
	printf("<p>Appliqué au Système de Management de la Sécurité de l'Information, le PDCA se traduit selon le schéma suivant:</p>");
	printf("<p><b>Planifier</b>: Etablir la politique, les objectifs, les processus et les procédures du SMSI relatives à la gestion du risque et à l'amélioration de la sécurité de l'information de manière à fournir des résultats conformément aux politiques et aux objectifs globaux de l'organisme.</p>");
	printf("<p><b>Déployer</b>: Mettre en oeuvre et exploiter la politique, les mesures, les processus et les procédures du SMSI.</p>");
	printf("<p><b>Contrôler</b>: Evaluer et, le cas échéant, mesurer les performances des processus par rapport à la politique, aux objectifs et à l'expérience pratique et rendre compte des résultats à la direction pour réexamen.</p>");
	printf("<p><b>Agir</b>: Entreprendre les actions correctives et préventives, sur la base des résultats de l'audit interne du SMSI et la revue de direction, ou d'autres informations pertinentes, pour une amélioration continue dudit système.</p>");
	printf("</div>");
}


function afficheLexique() {
	global $cheminDATA;
	genSyslog(__FUNCTION__);
	$jsonFile = sprintf("%slexique.json", $cheminDATA);
	$jsonSource = file_get_contents($jsonFile);
	$jsonLexique = json_decode($jsonSource);
	printf("<div class='onecolumn'>");
	printf("<dl>");
	foreach ($jsonLexique as $item) {
		printf("<dt>%s</dt>", $item->terme);
		printf("<dd>%s</dd>", $item->definition);
	}
	printf("</dl>");
	printf("</div>");
}


function menu() {
	genSyslog(__FUNCTION__);
	printf("<div class='row'>");
	printf("<div class='column left'>");
	linkMsg("aide.php?action=h_logiciel", "Aide sur le logiciel", "help.png", "menu");
	linkMsg("aide.php?action=h_eval", "L'évaluation du SMSI", "rapport.png", "menu");
	printf("</div><div class='column right'>");
	linkMsg("aide.php?action=lexique", "Lexique", "lexique.png", "menu");
	linkMsg("aide.php?action=about", "A propos", "about.png", "menu");
	printf("</div></div>");
}


switch ($_SESSION['role']) {
	case '100':
		$script = 'evalsmsi.php';
		break;
	case '3':
	case '4':
	case '5':
		$script = 'etab.php';
		break;
	case '2':
		$script = 'audit.php';
		break;
	default:
		$script = 'evalsmsi.php';
		break;
}


if (isset($_GET['action'])) {
	switch ($_GET['action']) {
	case 'h_logiciel':
		docSoftware();
		footPage('aide.php', "Retour");
		break;

	case 'h_eval':
		docISMSeval();
		footPage('aide.php', "Retour");
		break;

	case 'lexique':
		afficheLexique();
		footPage('aide.php', "Retour");
		break;

	case 'about':
		about();
		footPage('aide.php', "Retour");
		break;

	default:
		menu();
		footPage($script, "Retour");
	}
} else {
	menu();
	footPage($script, "Retour");
}

?>
