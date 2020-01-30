<?php
/*=========================================================
// File:        funct_audit.php
// Description: auditor functions of EvalSMSI
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


function isEtabLegitimate($id_etab) {
	$id_etab = intval($id_etab);
	if(isset($_SESSION['id_etab'])) {
		unset($_SESSION['id_etab']);
	}
	$tmp = explode(',', $_SESSION['audit_etab']);
	if (in_array($id_etab, $tmp)) {
		$_SESSION['id_etab'] = $id_etab;
		return true;
	} else {
		return false;
	}
}


function createAssessmentRegroup() {
	$base = dbConnect();
	$request = sprintf("INSERT INTO assess (etablissement, annee) VALUES ('%d', '%d')", $_SESSION['etablissement'], $_SESSION['annee']);
	if (mysqli_query($base, $request)){
		dbDisconnect($base);
		return true;
	} else {
		dbDisconnect($base);
		return false;
	}
}


function selectEtablissementAudit() {
	$action = explode('=', $_SERVER['QUERY_STRING'])[1];
	$result = getEtablissement();
	switch ($action) {
		case 'audit':
			$act = 'display_audit';
			break;
		case 'office':
			$act = 'do_office';
			break;
		case 'graph':
			$act = 'do_graph';
			break;
		case 'rap_etab':
			$act = 'prepare_rapport';
			break;
		case 'journal':
			$act = 'display_journal';
			break;
		case 'objectif':
			$act = 'display_objectif';
			break;
		case 'delete':
			$act = 'valid_delete';
			break;
		default:
			break;
	}
	printf("<form method='post' id='audit' action='audit.php?action=%s' onsubmit='return champs_ok(this)'>\n", $act);
	printf("<fieldset>\n<legend>Choix d'un établissement</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Etablissement:&nbsp;\n<select name='id_etab' id='id_etab'>\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row = mysqli_fetch_object($result)) {
		if (stripos($row->abrege, "_TEAM") === false) {
			printf("<option value='%s'>%s</option>\n", $row->id, $row->nom);
		} else {
			printf("<option value='%s'>%s (regroupement)</option>\n", $row->id, $row->nom);
		}
	}
	printf("</select>\n");
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Continuer', 'audit.php');
	printf("</form>\n");
}


function getAssessment($id_etab=0, $annee=0) {
	$base = dbConnect();
	if (($id_etab<>0) && ($annee<>0)) {
		$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", intval($id_etab), intval($annee));
	} else {
		$request = "SELECT * FROM assess";
	}
	$result=mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		return $result;
	} else {
		linkMsg("evalsmsi.php", "Aucune évaluation disponible", "alert.png");
	}
}


function writeAudit() {
	recordLog();
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$assessment = getAssessment($id_etab, $annee);
	$record = controlAssessment($_POST);
	$request = sprintf("UPDATE assess SET reponses='%s', valide=1 WHERE (etablissement='%d' AND annee='%d')", $record, $id_etab, $annee);
	$base = dbConnect();
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
		if (mysqli_query($base, $request)){
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


function objectifs() {
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['id_etab']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$obj = unserialize($row->objectifs);
	$req_par = "SELECT * FROM paragraphe ORDER BY numero";
	$res_par = mysqli_query($base, $req_par);
	dbDisconnect($base);
	printf("<div class='row'>\n");
	printf("<div class='column largeleft'>\n");
	printf("<form method='post' id='objectifs' action='audit.php?action=write_objectifs' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Gestion des objectifs pour <b>%s</b></legend>\n", $row->nom);
	printf("<table>\n");
	printf("<tr><th>Numéro</th><th>Paragraphe</th><th>Objectif</th></tr>\n");
	while ($row_par=mysqli_fetch_object($res_par)) {
		$objCurr = sprintf("obj_%d", $row_par->id);
		printf("<tr><td>%d</td><td class='pleft'>%s</td><td><input type='text' size='1' maxlength='1' name='obj_%s' id='obj_%s' onblur='valideObj(this)' value='%d' /></td></tr>\n", $row_par->numero, $row_par->libelle, $row_par->id, $row_par->id, $obj[$objCurr]);
	}
	printf("</table>\n</fieldset>\n");
	validForms('Enregistrer', 'audit.php', $back=False);
	printf("</form>\n</div>\n");
	afficheNotesExplanation();
	printf("</div>\n");
}


function recordObjectifs() {
	$objectifs = controlObjectifs($_POST);
	$base = dbConnect();
	$request = sprintf("UPDATE etablissement SET objectifs='%s' WHERE id='%d' ", $objectifs, $_SESSION['id_etab']);
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


function journalisation() {
	printf("<div class='onecolumn' id='graphs'>\n");
	$msg = sprintf("Journal des opérations - %s", uidToEtbs($_SESSION['id_etab']));
	printf("<div class='visualization' id='visualization'><p>%s</p></div>", $msg);
	printf("<p>&nbsp;</p>");
	printf("<textarea name='visdata' id='visdata' rows='15' cols='100' placeholder='Détails des opérations' readonly></textarea>");
	printf("</div>\n<p>&nbsp;</p>\n");
}


function recordCommentGraph() {
	$base = dbConnect();
	$id_assess = isset($_POST['id_assess']) ? intval(trim($_POST['id_assess'])) : NULL;
	$comment = isset($_POST['comments']) ? traiteStringToBDD($_POST['comments']) : NULL;
	$request = sprintf("UPDATE assess SET comment_graph_par='%s' WHERE id='%d'", $comment, $id_assess);
	if (mysqli_query($base, $request)){
		dbDisconnect($base);
		return true;
	} else {
		dbDisconnect($base);
		return false;
	}
}


function isAssessGroupValidate() {
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$isOk = true;
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$team = explode(',', $row->regroupement);
	foreach ($team as $id_member) {
		$nom = getEtablissement($id_member);
		$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_member, $annee);
		$result = mysqli_query($base, $request);
		if (!mysqli_num_rows($result)) {
			$isOk = false;
			$msg = sprintf("Il n'y a pas d'évaluation créée pour %s pour l'année %d", $nom, $annee);
			linkMsg("audit.php", $msg, "alert.png");
		} else {
			if (!isValidateRapport($id_member)) {
				$isOk = false;
				$msg = sprintf("L' évaluation de %s pour l'année %d n'est pas validée par un auditeur", $nom, $annee);
				linkMsg("audit.php", $msg, "alert.png");
			}
		}
	}
	dbDisconnect($base);
	return $isOk;
}


function displayAudit() {
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$nom = getEtablissement($id_etab);
	printf("<h1>%s - %s</h1>\n", $nom, $annee);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);
	// un enregistrement a déjà été fait
	if ($result->num_rows) {
		$row = mysqli_fetch_object($result);
		$assessment = unserialize($row->reponses);
		$final_c = $row->comments;
		$reponses = array();
		if (is_array($assessment)) {
			// il y a une au moins une question de saisie
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
			}
		} else {
			// sinon la réponse est vide
			$reponses[$annee]['1_1_1']=0;
		}
		if (isAssessComplete($reponses[$annee])) {
			linkMsg("#", "L'évaluation pour ".$annee." est complète.", "ok.png");
			$req_par = "SELECT * FROM paragraphe ORDER BY numero";
			$res_par = mysqli_query($base, $req_par);
			# affichage du formulaire
			printf("<div class='row'>\n");
			printf("<div class='column largeleft'>\n");
			printf("<div class='assess'>\n");
			printf("<form method='post' id='eval_auditeur' action='audit.php?action=record_audit'>\n");
			while ($row_par=mysqli_fetch_object($res_par)) {
				printf("<p><b>%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='ti%s' onclick='display(this)' /></p>\n", $row_par->numero, traiteStringFromBDD($row_par->libelle), $row_par->numero);
				$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d' ORDER BY numero", $row_par->id);
				$res_sub_par = mysqli_query($base, $req_sub_par);
				printf("<dl style='display:none;' id='dl%s'>\n", $row_par->numero);
				while ($row_sub_par=mysqli_fetch_object($res_sub_par)) {
					$dtid = $row_par->numero.'-'.$row_sub_par->numero;
					printf("<dt><b>%s.%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='dt%s' onclick='display(this)' /></dt>\n", $row_par->numero, $row_sub_par->numero, traiteStringFromBDD($row_sub_par->libelle), $dtid);
					printf("<dd class='comment'>%s</dd>", $row_sub_par->comment);
					$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
					$res_quest = mysqli_query($base, $req_quest);
					$ddid = $row_par->numero.'-'.$row_sub_par->numero;
					printf("<dd style='display:none;' id='dd%s'>\n", $ddid);
					while ($row_quest=mysqli_fetch_object($res_quest)) {
						$textID = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
						printf("<p><b>%s.%s.%s</b> %s</p>\n", $row_par->numero, $row_sub_par->numero, $row_quest->numero, traiteStringFromBDD($row_quest->libelle));
						printSelect($row_par->numero, $row_sub_par->numero, $row_quest->numero, $assessment);
						printf("<br />Commentaire établissement<br /><textarea name='%s' id='%s' cols='80' rows='4' readonly='readonly' style='background-color:#FFC7C7'>%s</textarea>\n", $textID, $textID, traiteStringFromBDD($assessment[$textID]));
						$evalID = 'eval'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
						if (isset($assessment[$evalID])) {
							printf("<br /><textarea placeholder='Commentaire évaluateur' name='%s' id='%s' cols='80' rows='4'>%s</textarea>\n", $evalID, $evalID, traiteStringFromBDD($assessment[$evalID]));
						} else {
							printf("<br /><textarea placeholder='Commentaire évaluateur' name='%s' id='%s' cols='80' rows='4'></textarea>\n", $evalID, $evalID);
						}
						printf("<p class='separation'>&nbsp;</p>\n");
					}
				printf("</dd>\n");
				}
				printf("</dl>\n");
			}
			validForms('Enregistrer', 'audit.php', $back=False);
			printf("</form>\n");
			printf("</div>\n");
			printf("</div>\n");
			afficheNotesExplanation();
			printf("</div>\n");
			dbDisconnect($base);
		} else {
			linkMsg("audit.php", "L'évaluation pour ".$annee." est incomplète.", "alert.png");
		}
	} else {
		$msg = sprintf("Il n'y a pas d'évaluation créée pour cet établissement pour l'année %d", $annee);
		linkMsg("audit.php", $msg, "alert.png");
	}
}


function displayAuditRegroup() {
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$nom = getEtablissement($id_etab);
	printf("<h1>%s - %s</h1>\n", $nom, $annee);
	$base = dbConnect();
	$request = sprintf("SELECT regroupement FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$regroupement = $row->regroupement;
	$request = sprintf("SELECT * FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);
	$new = true;
	$row = mysqli_fetch_object($result);
	if (!empty($row->reponses)) {
		// un enregistrement a déjà été fait
		$new = false;
		$assessment = unserialize($row->reponses);
		$final_c = $row->comments;
		$reponses = array();
		foreach($assessment as $quest => $rep) {
			if (substr($quest, 0, 8) == 'question') {
				$reponses[$annee][substr($quest, 8, 14)]=$rep;
			}
		}
	} else {
		// création du premier rapport -- concaténation des différents avis
		$request = sprintf("SELECT * FROM assess WHERE annee='%d' AND etablissement IN (%s)", $annee, $regroupement);
		$result = mysqli_query($base, $request);
		$reponses = array();
		while ($row = mysqli_fetch_object($result)) {
			if (!empty($row->reponses)) {
				$id_etab = $row->etablissement;
				$assessment = unserialize($row->reponses);
				foreach($assessment as $item => $rep) {
					if (substr($item, 0, 8) == 'question') {
						$reponses[$id_etab][$item]=$rep;
					}
					if (substr($item, 0, 4) == 'eval') {
						$reponses[$id_etab][$item]=$rep;
					}
				}
			}
		}
		// On construit les tableaux de résulats
		$question = array();
		$eval = array();
		foreach ($reponses as $etab => $result) {
			foreach (array_keys($result) as $val) {
				if (substr($val, 0, 8) == 'question') {
					$record = "question".substr($val, 8, 14);
					if (!isset($question[$val])) {
						$question[$record] = $result[$val];
					} else {
						if ($result[$val] <= $question[$record]) {
							$question[$record] = $result[$val];
						}
					}
				}
				if (substr($val, 0, 4) == 'eval') {
					$record = "comment".substr($val, 4, 10);
					if (!isset($eval[$val])) {
						if (empty($result[$val])) {
							$eval[$record] = 'Pas de commentaire';
						} else {
							$eval[$record] = $result[$val];
						}
					} else {
						$eval[$record] = $eval[$record]."\r\n".$result[$val];
					}
				}
			}
		}
	}
	$par_complete = paragrapheComplete($assessment,$base);
	$req_par = "SELECT * FROM paragraphe ORDER BY numero";
	$res_par = mysqli_query($base, $req_par);
	# affichage du formulaire
	printf("<div class='row'>\n");
	printf("<div class='column largeleft'>\n");
	printf("<div class='assess'>\n");
	printf("<form method='post' id='eval_auditeur' action='audit.php?action=record_audit'>\n");
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
		$res_sub_par = mysqli_query($base, $req_sub_par);
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
			$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
			$res_quest = mysqli_query($base, $req_quest);
			$ddid = $row_par->numero.'-'.$row_sub_par->numero;
			printf("<dd style='display:none;' id='dd%s'>\n", $ddid);
			while ($row_quest=mysqli_fetch_object($res_quest)) {
				$textID = 'comment'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
				$noteID = 'question'.$row_par->numero.'_'.$row_sub_par->numero.'_'.$row_quest->numero;
				printf("<p><b>%s.%s.%s</b> %s</p>\n", $row_par->numero, $row_sub_par->numero, $row_quest->numero, traiteStringFromBDD($row_quest->libelle));
				if ($new) {
					printf("<input type='hidden' name='%s' id='%s' value='%s' />\n", $noteID, $noteID, $question[$noteID]);
					printf("Note: %d - %s\n", $question[$noteID], textItem($question[$noteID]));
					printf("<br /><textarea name='%s' id='%s' cols='80' rows='4' >%s</textarea>\n", $textID, $textID, traiteStringFromBDD($eval[$textID]));
				} else {
					printf("<input type='hidden' name='%s' id='%s' value='%s' />\n", $noteID, $noteID, $assessment[$noteID]);
					printf("Note: %d - %s\n", $assessment[$noteID], textItem($assessment[$noteID]));
					printf("<br /><textarea name='%s' id='%s' cols='80' rows='4' >%s</textarea>\n", $textID, $textID, traiteStringFromBDD($assessment[$textID]));
				}
				printf("<p class='separation'>&nbsp;</p>\n");
			}
			printf("</dd>\n");
		}
		printf("</dl>\n");
	}
	validForms('Enregistrer', 'audit.php', $back=False);
	printf("</form>\n");
	printf("</div>\n");
	printf("</div>\n");
	afficheNotesExplanation();
	printf("</div>\n");
	dbDisconnect($base);
}


function getCommentGraphPar() {
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE(etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
	$result = mysqli_query($base, $request);
	// Il existe une évaluation pour cet établissement
	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_object($result);
		// L'évaluation n'est pas vide
		if (!empty($row->reponses)) {
			$assessment = unserialize($row->reponses);
			$reponses = array();
			$evals = array();
			foreach($assessment as $quest => $rep) {
				if (substr($quest, 0, 8) == 'question') {
					$reponses[$annee][substr($quest, 8, 14)]=$rep;
				}
				if (substr($quest, 0,4) == 'eval') {
					$evals[$annee][substr($quest, 4, 14)]=$rep;
				}
			}
			// L'évaluation est complète
			if (isAssessComplete($reponses[$annee])) {
				linkMsg("#", "L'évaluation pour ".$annee." est complète.", "ok.png");
				if ($row->valide) {
					linkMsg("#", "L'évaluation pour ".$annee." a été revue par les auditeurs.", "ok.png");
					$request = sprintf("SELECT id, comment_graph_par FROM assess WHERE (etablissement='%d' AND annee='%d') LIMIT 1", $id_etab, $annee);
					$result = mysqli_query($base, $request);
					$record = mysqli_fetch_object($result);
					printf("<div class='onecolumn'>\n");
					printf("<div id='graphs'>\n");
					printf("<canvas id='currentYearGraphBar'></canvas>\n");
					printf("<a id='yearGraphBar' class='btnValid' download='yearGraphBar.png' type='image/png'>Télécharger le graphe</a>\n");
					printf("<canvas id='currentYearGraphPolar'></canvas>\n");
					printf("<a id='yearGraphPolar' class='btnValid' download='yearGraphPolar.png' type='image/png'>Télécharger le graphe</a>\n");
					printf("</div>\n");
					printf("<form method='post' id='comment_graph' action='audit.php?action=record_comment' onsubmit='return champs_ok(this)'>\n");
					printf("<input type='hidden' size='3' maxlength='3' name='id_assess' id='id_assess' value='%s'/>\n", $record->id);
					printf("<textarea placeholder='Commentaire auditeur' name='comments' id='comments' cols='100' rows='10'>%s</textarea>\n", traiteStringFromBDD($record->comment_graph_par));
					validForms('Continuer', 'audit.php', $back=False);
					printf("</form>\n");
					printf("</div>\n");
				} else {
					linkMsg("audit.php", "L'évaluation pour ".$annee." n'a pas été revue par les auditeurs", "alert.png");
				}
			} else {
				linkMsg("audit.php", "L'évaluation pour ".$annee." est incomplète", "alert.png");
			}
		} else {
			$msg = sprintf("L'évaluation de cet établissement pour l'année %d est vide", $annee);
			linkMsg("audit.php", $msg, "alert.png");
		}
	} else {
		$msg = sprintf("Il n'y a pas d'évaluation créée pour cet établissement pour l'année %d", $annee);
		linkMsg("audit.php", $msg, "alert.png");
	}
	dbDisconnect($base);
}


function confirmDeleteAssessment($script) {
	$msg = sprintf("Cliquer pour effacer l'évaluation<br />réalisée en <b>%d</b> par <b>%s</b>", $_SESSION['annee'], getEtablissement($_SESSION['id_etab']));
	linkMsg($script."?action=do_delete", $msg, "alert.png");
	linkMsg($script, "Annuler et revenir à la page d'acueil", "ok.png");
	printf("<div class='onecolumn' id='graphs'>\n");
	printf("<canvas id='currentYearGraphBar'></canvas>\n");
	printf("</div>\n");
}


function deleteAssessment() {
	$base = dbConnect();
	$request = sprintf("DELETE FROM assess WHERE etablissement='%d' AND annee='%d'", $_SESSION['id_etab'], $_SESSION['annee']);
	if (mysqli_query($base, $request)) {
		$request = sprintf("DELETE FROM journal WHERE etablissement='%d' AND YEAR(timestamp)='%d'", $_SESSION['id_etab'], $_SESSION['annee']);
		if (mysqli_query($base, $request)) {
			linkMsg("audit.php", "Evaluation supprimée de la base.", "ok.png");
		}
	} else {
		linkMsg("audit.php", "Echec de la suppression de l'évaluation.", "alert.png");
	}
	dbDisconnect($base);
}


?>
