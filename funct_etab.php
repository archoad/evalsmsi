<?php
/*=========================================================
// File:        funct_etab.php
// Description: user functions of EvalSMSI
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

function createAssessment() {
	$base = dbConnect();
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$request = sprintf("INSERT INTO assess (etablissement, annee) VALUES ('%d', '%d')", $id_etab, $annee);
	if (mysqli_query($base, $request)){
		return mysqli_insert_id($base); // Création de l'évaluation
	} else {
		return false; // Erreur de création
	}
	dbDisconnect($base);
}


function displayAssessment() {
	$numQuestion = questionsCount();
	$annee = $_SESSION['annee'];
	printf("<h1>Evaluation pour l'année %s</h1>\n", $annee);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $_SESSION['id_etab'], $annee);
	$result = mysqli_query($base, $request);
	// un enregistrement a déjà été fait
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		$assessment = unserialize($row->reponses);
		$final_c = $row->comments;
	}
	if($row->valide) {
		linkMsg("etab.php", "L'évaluation pour ".$annee." est complète et validée par les évaluateurs. Vous ne pouvez plus la modifier.", "alert.png");
		footPage();
	} else {
		$par_complete = paragrapheComplete($assessment,$base);
		$req_par="SELECT * FROM paragraphe ORDER BY numero";
		$res_par=mysqli_query($base, $req_par);
		# affichage de la barre de progression
		printf("<div id='a'><div id='b'><div id='c'></div></div></div>");
		# affichage du formulaire
		printf("<div class='row'>\n");
		printf("<div class='column largeleft'>\n");
		printf("<div class='assess'>\n");
		printf("<form method='post' id='make_assess' action='etab.php?action=make_assess' onsubmit='return champs_na(this)'>\n");
		printf("<p><input type='hidden' id='nbr_questions' value='%s' /></p>\n", $numQuestion);
		while ($row_par=mysqli_fetch_object($res_par)) {
			if ($par_complete[$row_par->numero] == 0) {
				$fond = "<span class='redpoint'>&nbsp;</span>";
			} elseif ($par_complete[$row_par->numero] == 1) {
				$fond = "<span class='orangepoint'>&nbsp;</span>";
			} else {
				$fond = "<span class='greenpoint'>&nbsp;</span>";
			}
			printf("<p>%s<b>%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='ti%s' onclick='display(this)' /></p>\n", $fond, $row_par->numero, traiteStringFromBDD($row_par->libelle), $row_par->numero);
			$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d' ORDER BY numero", $row_par->id);
			$res_sub_par=mysqli_query($base, $req_sub_par);
			printf("<dl style='display:none;' id='dl%s'>\n", $row_par->numero);
			while ($row_sub_par=mysqli_fetch_object($res_sub_par)) {
				$dtid = $row_par->numero.'-'.$row_sub_par->numero;
				$subpar_complete = subParagrapheComplete($assessment, $row_par->numero, $row_sub_par->numero, $base);
				if ($subpar_complete[$row_sub_par->numero] == 0) {
					$fond = "<span class='redpoint'>&nbsp;</span>";
				} elseif ($subpar_complete[$row_sub_par->numero] == 1) {
					$fond = "<span class='orangepoint'>&nbsp;</span>";
				} else {
					$fond = "<span class='greenpoint'>&nbsp;</span>";
				}
				printf("<dt>%s<b>%s.%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='dt%s' onclick='display(this)' /></dt>\n", $fond, $row_par->numero, $row_sub_par->numero, traiteStringFromBDD($row_sub_par->libelle), $dtid);
				printf("<dd class='comment'>%s</dd>", $row_sub_par->comment);
				$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
				$res_quest=mysqli_query($base, $req_quest);
				$ddid = $row_par->numero.'-'.$row_sub_par->numero;
				printf("<dd style='display:none;' id='dd%s'>\n", $ddid);
				while ($row_quest=mysqli_fetch_object($res_quest)) {
					printf("<p><b>%s.%s.%s</b> %s</p>\n", $row_par->numero, $row_sub_par->numero, $row_quest->numero, traiteStringFromBDD($row_quest->libelle));
					if (isset($assessment)) {
						printSelect($row_par->numero, $row_sub_par->numero, $row_quest->numero, $assessment);
					} else {
						printSelect($row_par->numero, $row_sub_par->numero, $row_quest->numero);
					}
					$textID = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
					if (isset($assessment)) {
						printf("<br /><textarea placeholder='Commentaire' name='%s' id='%s' cols='80' rows='4'>%s</textarea>\n", $textID, $textID, traiteStringFromBDD($assessment[$textID]));
					} else {
						printf("<br /><textarea placeholder='Commentaire' name='%s' id='%s' cols='80' rows='4'></textarea>\n", $textID, $textID);
					}
					printf("<p class='separation'>&nbsp;</p>\n");
				}
				printf("</dd>\n");
			}
			printf("</dl>\n");
		}
		printf("<table>\n<tr>\n<td><b>Commentaire final - Conclusion</b></td>\n</tr>\n");
		printf("<tr>\n<td>\n<textarea name='final_comment' id='final_comment' cols='68' rows='5' style='display:none;'>%s</textarea>\n</td>\n</tr>\n</table>\n", traiteStringFromBDD($final_c));
		validForms('Enregistrer', 'etab.php', $back=False);
		printf("</form>\n");
		printf("</div>\n");
		printf("</div>\n");
		afficheNotesExplanation();
		printf("</div>\n");
	}
	dbDisconnect($base);
}


function writeAssessment(){
	recordLog();
	$etablissement = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$base = dbConnect();
	$record = mysqli_real_escape_string($base, serialize($_POST));
	$comment = isset($_POST['final_comment']) ? traiteStringToBDD($_POST['final_comment']) : NULL;
	$request = sprintf("UPDATE assess SET reponses='%s', comments='%s' WHERE (etablissement='%d' AND annee='%d')", $record, $comment, $etablissement, $annee);
	if (mysqli_query($base, $request)){
		return $etablissement;
	} else {
		return false;
	}
	dbDisconnect($base);
}


function exportRapport($script, $annee) {
	$xlsFile = generateExcellRapport($annee);
	$msg = sprintf("Télécharger le plan d'actions %s (Excel)", $annee);
	printf("<div class='row'>\n");
	printf("<div class='column left'>\n");
	generateRapport($script, $_SESSION['id_etab'], $annee);
	printf("</div>\n");
	printf("<div class='column right'>\n");
	linkMsg($xlsFile, $msg, "xlsx.png", 'menu');
	printf("</div>\n</div>\n");
}


function selectYearRapport() {
	$base = dbConnect();
	$id_etab = $_SESSION['id_etab'];
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d'", $id_etab);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	$list = array();
	while($row=mysqli_fetch_object($result)) {
		if ($row->valide) {
				$list[] = $row->annee;
		}
	}
	if (count($list)) {
		printf("<form method='post' id='select_print' action='etab.php?action=do_print' onsubmit='return champs_ok(this)'>\n");
		printf("<fieldset>\n<legend>Choix d'une année</legend>\n");
		printf("<table>\n<tr><td>\n");
		printf("Année:&nbsp;\n<select name='year' id='year'>\n");
		printf("<option selected='selected' value=''>&nbsp;</option>\n");
		foreach($list as $annee) {
			printf("<option value='%d'>%d</option>\n", $annee, $annee);
		}
		printf("</select>\n");
		printf("</td>\n</tr>\n</table>\n");
		printf("</fieldset>\n");
		validForms('Afficher le rapport', 'etab.php');
		printf("</form>\n");
	} else {
		linkMsg("etab.php", "Il n'y a pas d'évaluation validée pour cet établissement.", "alert.png");
	}
}




?>
