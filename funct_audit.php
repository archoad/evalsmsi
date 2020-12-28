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


function isEtabLegitimate($tab) {
	genSyslog(__FUNCTION__);
	$id_etab = intval($tab['id_etab']);
	if(isset($_SESSION['id_etab'])) {
		unset($_SESSION['id_etab']);
	}
	$tmp = explode(',', $_SESSION['audit_etab']);
	if (in_array($id_etab, $tmp)) {
		$_SESSION['id_etab'] = $id_etab;
		if (isset($tab['id_quiz'])) {
			$_SESSION['quiz'] = $tab['id_quiz'];
		}
		return true;
	} else {
		return false;
	}
}


function createAssessmentRegroup() {
	genSyslog(__FUNCTION__);
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
	genSyslog(__FUNCTION__);
	$nonce = $_SESSION['nonce'];
	if (isset($_SESSION['quiz'])) { unset($_SESSION['quiz']); }
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
	printf("<form method='post' id='audit' action='audit.php?action=%s'>", $act);
	printf("<fieldset><legend>Choix d'un établissement</legend>");
	printf("<table><tr id='selectEtabRow'><td>");
	if ($action === 'objectif') {
		printf("Etablissement:&nbsp;<select name='id_etab' id='id_etab' required>");
	} else {
		printf("Etablissement:&nbsp;<select name='id_etab' id='id_etab' required>");
		printf("<script nonce='%s'>var id=document.getElementById('id_etab'); id.addEventListener('change', function(){xhrequest(id.value);});</script>", $nonce);
	}
	printf("<option selected='selected' value=''>&nbsp;</option>");
	while($row = mysqli_fetch_object($result)) {
		if (stripos($row->abrege, "_TEAM") === false) {
			printf("<option value='%s'>%s</option>", $row->id, $row->nom);
		} else {
			printf("<option value='%s'>%s (regroupement)</option>", $row->id, $row->nom);
		}
	}
	printf("</select></td>");
	printf("</tr></table></fieldset>");
	validForms('Continuer', 'audit.php', $back=False);
	printf("</form>");
}


function getAssessment() {
	genSyslog(__FUNCTION__);
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$id_quiz = $_SESSION['quiz'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
	$result=mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) {
		return $result;
	} else {
		linkMsg("evalsmsi.php", "Aucune évaluation disponible", "alert.png");
	}
}


function writeAudit() {
	genSyslog(__FUNCTION__);
	recordLog();
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$id_quiz = $_SESSION['quiz'];
	$assessment = getAssessment();
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
	return false;
}


function objectifs() {
	genSyslog(__FUNCTION__);
	global $noteMax;
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['id_etab']);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	dbDisconnect($base);
	$objectives = json_decode($row->objectifs, true);
	printf("<div class='row'>");
	printf("<div class='column largeleft'>");
	printf("<form method='post' id='objectifs' action='audit.php?action=write_objectifs' >");
	printf("<fieldset><legend>Gestion des objectifs pour <b>%s</b></legend>", $row->nom);
	foreach ($objectives as $numQuiz => $obj) {
		$domLibelle = getDomLibelle($numQuiz);
		$name_quiz = getQuizNameById($numQuiz);
		printf("<table>");
		printf("<tr><th colspan=3>%s</th></tr>", $name_quiz);
		printf("<tr><th>Numéro</th><th>Paragraphe</th><th>Objectif</th></tr>");
		foreach ($obj as $objectif => $value) {
			$num_dom = intval(explode('_', $objectif)[1]);
			$objCurr = sprintf("obj_%d_%d", $numQuiz, $num_dom);
			printf("<tr><td>%d</td><td class='pleft'>%s</td>", $num_dom, $domLibelle[$num_dom]);
			printf("<td><input type='number' name='%s' id='%s' value='%d' min='1' max='%d' required></td></tr>", $objCurr, $objCurr, $value, $noteMax);
		}
		printf("</table>");
		printf("<p class='separation'>&nbsp;</p>");
	}
	printf("</fieldset>");
	validForms('Enregistrer', 'audit.php', $back=False);
	printf("</form></div>");
	afficheNotesExplanation();
	printf("</div>");
}


function controlObjectifs($answer) {
	global $noteMax;
	genSyslog(__FUNCTION__);
	foreach ($answer as $key => $value) {
		$tmp = intval($value);
		if ($tmp<0 || $tmp>$noteMax) {
			$tmp = 0;
		}
		$answer[$key] = $tmp;
	}
	$objectives = array();
	foreach ($answer as $key => $value) {
		$keyDetail = explode('_', $key);
		$objCurr = sprintf("obj_%d", $keyDetail[2]);
		$objectives[$keyDetail[1]][$objCurr] = $value;
	}
	$output = json_encode($objectives);
	return $output;
}


function recordObjectifs() {
	genSyslog(__FUNCTION__);
	$objectives = controlObjectifs($_POST);
	$base = dbConnect();
	$request = sprintf("UPDATE etablissement SET objectifs='%s' WHERE id='%d' ", $objectives, $_SESSION['id_etab']);
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
	genSyslog(__FUNCTION__);
	$nonce = $_SESSION['nonce'];
	if (isset($_SESSION['quiz'])) {
		printf("<script nonce='%s'>document.body.addEventListener('load', loadLogs());</script>", $nonce);
		printf("<div class='onecolumn' id='graphs'>");
		$msg = sprintf("Journal des opérations - %s", uidToEtbs());
		printf("<div class='visualization' id='visualization'><p>%s</p></div>", $msg);
		printf("<p>&nbsp;</p>");
		printf("<textarea name='visdata' id='visdata' rows='15' cols='100' placeholder='Détails des opérations' readonly></textarea>");
		printf("</div><p>&nbsp;</p>");
	} else {
		linkMsg("audit.php", "Il n'y a pas d'évaluation pour cet établissement", "alert.png");
	}
}


function recordCommentGraph() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$id_assess = isset($_POST['id_assess']) ? intval(trim($_POST['id_assess'])) : NULL;
	$comment = isset($_POST['comments']) ? traiteStringToBDD($_POST['comments']) : NULL;
	$request = sprintf("UPDATE assess SET comment_graph_par='%s' WHERE id='%d'", $comment, $id_assess);
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


function isAssessGroupValidate() {
	genSyslog(__FUNCTION__);
	$id_etab = $_SESSION['id_etab'];
	$annee = $_SESSION['annee'];
	$id_quiz = $_SESSION['quiz'];
	$isOk = true;
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$team = explode(',', $row->regroupement);
	foreach ($team as $id_member) {
		$name_etab = getEtablissement($id_member);
		$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_member, $annee, $id_quiz);
		$result = mysqli_query($base, $request);
		if (!mysqli_num_rows($result)) {
			$isOk = false;
			$msg = sprintf("Il n'y a pas d'évaluation créée pour %s pour l'année %d", $name_etab, $annee);
			linkMsg("audit.php", $msg, "alert.png");
		} else {
			if (!isValidateRapport($id_member)) {
				$isOk = false;
				$msg = sprintf("L' évaluation de %s pour l'année %d n'est pas validée par un auditeur", $name_etab, $annee);
				linkMsg("audit.php", $msg, "alert.png");
			}
		}
	}
	dbDisconnect($base);
	return $isOk;
}


function displayAudit() {
	genSyslog(__FUNCTION__);
	if (isset($_SESSION['quiz'])) {
		$numQuestion = questionsCount();
		$nonce = $_SESSION['nonce'];
		$annee = $_SESSION['annee'];
		$id_quiz = $_SESSION['quiz'];
		$id_etab = $_SESSION['id_etab'];
		$quiz = getJsonFile();
		$name_etab = getEtablissement($id_etab);
		printf("<h1>%s - %s</h1>", $name_etab, $annee);
		$base = dbConnect();
		$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
		$result = mysqli_query($base, $request);
		dbDisconnect($base);
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
				# affichage du formulaire
				printf("<div class='row'>");
				printf("<div class='column largeleft'>");
				printf("<h3>Cette évaluation comprend %s questions</h3>", $numQuestion);
				printf("<div class='assess'>");
				printf("<form method='post' id='eval_auditeur' action='audit.php?action=record_audit'>");
				for ($d=0; $d<count($quiz); $d++) {
					$num_dom = $quiz[$d]['numero'];
					$subDom = $quiz[$d]['subdomains'];
					printf("<p><b>%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='ti%s'></p>", $num_dom, $quiz[$d]['libelle'], $num_dom);
					printf("<script nonce='%s'>document.getElementById('ti%s').addEventListener('click', function(){display('ti%s');});</script>", $nonce, $num_dom, $num_dom);
					printf("<dl class='none' id='dl%s'>", $num_dom);
					for ($sd=0; $sd<count($subDom); $sd++) {
						$num_sub_dom = $subDom[$sd]['numero'];
						$questions = $subDom[$sd]['questions'];
						$id = $num_dom.'-'.$num_sub_dom;
						printf("<dt><b>%s.%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='dt%s'></dt>", $num_dom, $num_sub_dom, $subDom[$sd]['libelle'], $id);
						printf("<script nonce='%s'>document.getElementById('dt%s').addEventListener('click', function(){display('dt%s');});</script>", $nonce, $id, $id);
						printf("<dd class='comment'>%s</dd>", $subDom[$sd]['comment']);
						printf("<dd class='none' id='dd%s'>", $id);
						for ($q=0; $q<count($questions); $q++) {
							$num_question = $questions[$q]['numero'];
							$textID = 'comment'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
							printf("<p><b>%s.%s.%s</b> %s</p>", $num_dom, $num_sub_dom, $num_question, $questions[$q]['libelle']);
							$mesure = $questions[$q]['mesure'];
							printf("<div class='reco_parent'>");
							printf("<div class='reco_child'>");
							printSelect($num_dom, $num_sub_dom, $num_question, $assessment);
							printf("</div><div class='reco_child'>");
							if ($mesure !== 'Néant') {
								printf("<span class='reco'>%s</span>", $mesure);
							} else {
								printf("<span class='reco'>Pas de recommandation spécifique</span>");
							}
							printf("</div></div>");
							printf("<br>Commentaire établissement<br><textarea name='%s' id='%s' cols='80' rows='4' readonly class='protected'>%s</textarea>", $textID, $textID, traiteStringFromBDD($assessment[$textID]));
							$evalID = 'eval'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
							if (isset($assessment[$evalID])) {
								printf("<br><textarea placeholder='Commentaire évaluateur' name='%s' id='%s' cols='80' rows='4'>%s</textarea>", $evalID, $evalID, traiteStringFromBDD($assessment[$evalID]));
							} else {
								printf("<br><textarea placeholder='Commentaire évaluateur' name='%s' id='%s' cols='80' rows='4'></textarea>", $evalID, $evalID);
							}
							printf("<p class='separation'>&nbsp;</p>");
						}
					printf("</dd>");
					}
					printf("</dl>");
				}
				validForms('Enregistrer', 'audit.php', $back=False);
				printf("</form>");
				printf("</div>");
				printf("</div>");
				afficheNotesExplanation();
				printf("</div>");
			} else {
				linkMsg("audit.php", "L'évaluation pour ".$annee." est incomplète.", "alert.png");
			}
		} else {
			$msg = sprintf("Il n'y a pas d'évaluation créée pour cet établissement pour l'année %d", $annee);
			linkMsg("audit.php", $msg, "alert.png");
		}
	} else {
		$msg = sprintf("Il n'y a pas d'évaluation créée pour cet établissement");
		linkMsg("audit.php", $msg, "alert.png");
	}
}


function displayAuditRegroup() {
	genSyslog(__FUNCTION__);
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$annee = $_SESSION['annee'];
	$quiz = getJsonFile();
	$name_etab = getEtablissement($id_etab);
	printf("<h1>%s - %s</h1>", $name_etab, $annee);
	$base = dbConnect();
	$request = sprintf("SELECT regroupement FROM etablissement WHERE id='%d' LIMIT 1", $id_etab);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$regroupement = $row->regroupement;
	$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
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
		$request = sprintf("SELECT * FROM assess WHERE annee='%d' AND quiz='%d' AND etablissement IN (%s)", $annee, $id_quiz, $regroupement);
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
	$dom_complete = domainComplete($assessment);
	# affichage du formulaire
	printf("<div class='row'>");
	printf("<div class='column largeleft'>");
	printf("<div class='assess'>");
	printf("<form method='post' id='eval_auditeur' action='audit.php?action=record_audit'>");
	for ($d=0; $d<count($quiz); $d++) {
		$num_dom = $quiz[$d]['numero'];
		$subDom = $quiz[$d]['subdomains'];
		$fond = getColorButton($dom_complete, $num_dom);
		printf("<p>%s<b>%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='ti%s' onclick='display(this)'></p>", $fond, $num_dom, $quiz[$d]['libelle'], $num_dom);
		printf("<dl class='none;' id='dl%s'>", $num_dom);
		for ($sd=0; $sd<count($subDom); $sd++) {
			$num_sub_dom = $subDom[$sd]['numero'];
			$questions = $subDom[$sd]['questions'];
			$id = $num_dom.'-'.$num_sub_dom;
			$subdom_complete = subDomainComplete($assessment, $num_dom, $num_sub_dom);
			$fond = getColorButton($subdom_complete, $num_sub_dom);
			printf("<dt>%s<b>%s.%s</b>&nbsp;%s&nbsp;<input type='button' value='+' id='dt%s' onclick='display(this)'></dt>", $fond, $num_dom, $num_sub_dom, $subDom[$sd]['libelle'], $id);
			printf("<dd class='none;' id='dd%s'>", $id);
			for ($q=0; $q<count($questions); $q++) {
				$num_question = $questions[$q]['numero'];
				$textID = 'comment'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
				$noteID = 'question'.$num_dom.'_'.$num_sub_dom.'_'.$num_question;
				printf("<p><b>%s.%s.%s</b> %s</p>", $num_dom, $num_sub_dom, $num_question, $questions[$q]['libelle']);
				if ($new) {
					printf("<input type='hidden' name='%s' id='%s' value='%s'>", $noteID, $noteID, $question[$noteID]);
					printf("Note: %d - %s", $question[$noteID], textItem($question[$noteID]));
					printf("<br><textarea name='%s' id='%s' cols='80' rows='4' >%s</textarea>", $textID, $textID, traiteStringFromBDD($eval[$textID]));
				} else {
					printf("<input type='hidden' name='%s' id='%s' value='%s'>", $noteID, $noteID, $assessment[$noteID]);
					printf("Note: %d - %s", $assessment[$noteID], textItem($assessment[$noteID]));
					printf("<br><textarea name='%s' id='%s' cols='80' rows='4' >%s</textarea>", $textID, $textID, traiteStringFromBDD($assessment[$textID]));
				}
				printf("<p class='separation'>&nbsp;</p>");
			}
			printf("</dd>");
		}
		printf("</dl>");
	}
	validForms('Enregistrer', 'audit.php', $back=False);
	printf("</form>");
	printf("</div>");
	printf("</div>");
	afficheNotesExplanation();
	printf("</div>");
	dbDisconnect($base);
}


function getCommentGraphPar() {
	genSyslog(__FUNCTION__);
	if (isset($_SESSION['quiz'])) {
		$id_etab = $_SESSION['id_etab'];
		$annee = $_SESSION['annee'];
		$id_quiz = $_SESSION['quiz'];
		$nonce = $_SESSION['nonce'];
		printf("<script nonce='%s'>document.body.addEventListener('load', loadGraphYear());</script>", $nonce);
		$base = dbConnect();
		$request = sprintf("SELECT * FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
		$result = mysqli_query($base, $request);
		dbDisconnect($base);
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
						$base = dbConnect();
						$request = sprintf("SELECT id, comment_graph_par FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d' LIMIT 1", $id_etab, $annee, $id_quiz);
						$result = mysqli_query($base, $request);
						dbDisconnect($base);
						$record = mysqli_fetch_object($result);
						printf("<div class='onecolumn'>");
						printf("<div id='graphs'>");
						printf("<canvas id='currentYearGraphBar'></canvas>");
						printf("<a href='' id='yearGraphBar' class='btnValid' download='yearGraphBar.png' type='image/png'>Télécharger le graphe</a>");
						printf("<canvas id='currentYearGraphPolar'></canvas>");
						printf("<a href='' id='yearGraphPolar' class='btnValid' download='yearGraphPolar.png' type='image/png'>Télécharger le graphe</a>");
						printf("<canvas id='currentYearGraphScatter'></canvas><br>");
						printf("<a href='' id='yearGraphScatter' class='btnValid' download='yearGraphScatter.png' type='image/png'>Télécharger le graphe</a>");
						printf("</div>");
						printf("<form method='post' id='comment_graph' action='audit.php?action=record_comment' >");
						printf("<input type='hidden' name='id_assess' id='id_assess' value='%s'/>", $record->id);
						printf("<textarea placeholder='Commentaire auditeur' name='comments' id='comments' cols='100' rows='10' required>%s</textarea>", traiteStringFromBDD($record->comment_graph_par));
						validForms('Continuer', 'audit.php', $back=False);
						printf("</form>");
						printf("</div>");
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
			$msg = sprintf("Il n'y a pas d'évaluation pour cet établissement pour l'année %d", $annee);
			linkMsg("audit.php", $msg, "alert.png");
		}
	} else {
		$msg = sprintf("Il n'y a pas d'évaluation pour cet établissement");
		linkMsg("audit.php", $msg, "alert.png");
	}
}


function confirmDeleteAssessment() {
	genSyslog(__FUNCTION__);
	$name_etab = getEtablissement($_SESSION['id_etab']);
	$nonce = $_SESSION['nonce'];
	$annee = $_SESSION['annee'];
	$script = $_SESSION['curr_script'];
	$msg = sprintf("Cliquer pour effacer l'évaluation<br>réalisée en <b>%d</b> par <b>%s</b>", $annee, $name_etab);
	linkMsg($script."?action=do_delete", $msg, "alert.png");
	linkMsg($script, "Annuler et revenir à la page d'acueil", "ok.png");
	printf("<div class='onecolumn' id='graphs'>");
	printf("<canvas id='currentYearGraphBar'></canvas>");
	printf("</div>");
	printf("<script nonce='%s'>document.body.addEventListener('load', loadGraphYear());</script>", $nonce);
}


function deleteAssessment() {
	genSyslog(__FUNCTION__);
	$id_etab = $_SESSION['id_etab'];
	$id_quiz = $_SESSION['quiz'];
	$annee = $_SESSION['annee'];
	$base = dbConnect();
	$request = sprintf("DELETE FROM assess WHERE etablissement='%d' AND annee='%d' AND quiz='%d'", $id_etab, $annee, $id_quiz);
	if (mysqli_query($base, $request)) {
		$request = sprintf("DELETE FROM journal WHERE etablissement='%d' AND YEAR(timestamp)='%d' AND quiz='%d'", $id_etab, $annee, $id_quiz);
		if (mysqli_query($base, $request)) {
			linkMsg("audit.php", "Evaluation supprimée de la base.", "ok.png");
		}
	} else {
		linkMsg("audit.php", "Echec de la suppression de l'évaluation.", "alert.png");
	}
	dbDisconnect($base);
}


function getNbrQuestionOK($answers) {
	$result = 0;
	foreach ($answers as $key => $value) {
		if ($value) { $result += 1; }
	}
	return $result;
}


function getMeanNote($notes) {
	$domainNbr = count($notes);
	$sum = 0;
	foreach($notes as $dom => $note) {
		$sum += $note;
	}
	$mean = $sum / $domainNbr;
	$mean = 20 * $mean / 7;
	$mean = round($mean, 2);
	return $mean;
}


function dataSettings($table) {
	$chartColors = ['#A0CBE8', '#FFBE7D', '#8CD17D', '#F1CE63', '#86BCB6', '#FF9D9A', '#BAB0AC', '#FABFD2',  '#D4A6C8', '#D7B5A6', '#4E79A7', '#F28E2B', '#59A14F', '#B6992D', '#499894', '#E15759', '#79706E', '#D37295', '#B07AA1', '#9D7660'];
	$quizIdList = array();
	$result = array();
	foreach ($table as $key => $value) {
		foreach ($value['data'] as $id => $val) {
			if (!in_array($id, $quizIdList)) { $quizIdList[] = $id; }
		}
	}
	$cpt = 0;
	foreach ($table as $key => $value) {
		$result[$cpt]['label'] = $value['label'];
		$result[$cpt]['type'] = 'bar';
		$result[$cpt]['borderWidth'] = 1;
		$result[$cpt]['borderColor'] = $chartColors[$cpt];
		$result[$cpt]['backgroundColor'] = $chartColors[$cpt].'cc';
		$newdata = array();
		foreach ($quizIdList as $id) {
			if (array_key_exists($id, $value['data'])) {
				$newdata[$id] = $value['data'][$id];
			} else {
				$newdata[$id] = floatval(0);
			}
		}
		$temp = array();
		foreach ($newdata as $key => $value) {
			$temp[] = $value;
		}
		$result[$cpt]['data'] = $temp;
		$cpt++;
	}
	return $result;
}


function displayEtabsReview() {
	$listEtabs = getEtablissement();
	$data = array();
	$etabs = array();
	$quiz = array();
	printf("<h4>Bilan de la complétion des évaluations au %s</h4>", $_SESSION['day']);
	printf("<div class='onecolumn'>");
	printf("<canvas id='progressReviewGraphBar'></canvas>");
	printf("<a href='' id='reviewGraphBar' class='btnValid' download='reviewGraphBar.png' type='image/png'>Télécharger le graphe</a>");
	while($row = mysqli_fetch_object($listEtabs)) {
		if (stripos($row->abrege, "_TEAM") === false) {
			$base = dbConnect();
			$request = sprintf("SELECT id, quiz, reponses FROM assess WHERE annee='%d' AND etablissement='%d' ORDER BY quiz", $_SESSION['annee'], $row->id);
			$result = mysqli_query($base, $request);
			dbDisconnect($base);
			$id_etab = $row->id;
			$name_etab = getEtablissement($id_etab);
			$etabs[] = $name_etab;
			printf("<dl>%s", $name_etab);
			while ($row = mysqli_fetch_object($result)) {
				if (!empty($row->reponses)) {
					$_SESSION['quiz'] = $row->quiz;
					$quiz_name = getQuizName();
					printf("<dt>%s</dt>", $quiz_name);
					$answers = array();
					foreach(unserialize($row->reponses) as $quest => $rep) {
						if (substr($quest, 0, 8) == 'question') {
							$answers[substr($quest, 8, 14)]=$rep;
						}
					}
					$nbrQuestionOK = getNbrQuestionOK($answers);
					$nbrQuestion = questionsCount();
					$percent = 100 * intval($nbrQuestionOK) / intval($nbrQuestion);
					$percent = round($percent, 2);
					$notes = calculNotes($answers);
					$mean = getMeanNote($notes);
					printf("<dd>Le questionnaire est complété à %s %% - La note actuelle est de %s/20</dd>", $percent, $mean);
					$quiz[$row->quiz]['label'] = $quiz_name;
					$quiz[$row->quiz]['data'][$id_etab] = $percent;
				}
			}
			printf("</dl>");
		}
	}
	$quiz = dataSettings($quiz);
	$data['labels'] = $etabs;
	$data['quiz'] = $quiz;
	printf("</div>");
	printf("<script nonce='%s'>document.body.addEventListener('load', displayProgressReviewGraphBar(%s));</script>", $_SESSION['nonce'], json_encode($data));
}


?>
