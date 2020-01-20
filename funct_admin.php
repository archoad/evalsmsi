<?php
/*=========================================================
// File:        funct_admin.php
// Description: admin functions of EvalSMSI
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

function maintenanceBDD() {
	$base = dbConnect();
	$request = "select table_name from information_schema.tables
where table_schema='evalsmsi' ";
	$result = mysqli_query($base, $request);
	$tableNames = '';
	while ($row = mysqli_fetch_object($result)) {
		$tableNames = $tableNames.$row->table_name.', ';
	}
	$tableNames = rtrim($tableNames, ', ');
	$actions = ['CHECK', 'OPTIMIZE', 'REPAIR', 'ANALYZE'];
	printf("<div class='project'>\n");
	foreach ($actions as $value) {
		$request = sprintf("%s TABLE %s", $value, $tableNames);
		if ($result = mysqli_query($base, $request)) {
			printf("<table>\n");
			printf("<tr><th>Nom de la table</th><th>Opération</th><th>Type de message</th><th>Message</th></tr>\n");
			while ($row = mysqli_fetch_object($result)) {
				printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $row->Table, $row->Op, $row->Msg_type, $row->Msg_text);
			}
			printf("</table>\n");
		} else {
			printf("%s: %s\n", mysqli_errno($base), mysqli_error($base));
		}
	}
	printf("</div>\n");
	dbDisconnect($base);
}


function addParagraphs() {
	$base = dbConnect();
	$request = "SELECT MAX(numero) FROM paragraphe";
	$result=mysqli_query($base, $request);
	$row=mysqli_fetch_array($result);
	dbDisconnect($base);
	printf("<form method='post' id='new_par' action='admin.php?action=new_par' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Saisie d'un nouveau domaine</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Numero:&nbsp;<input type='text' size='2' maxlength='2' style='background:lightgrey;' name='num_par' id='num_par' value='%s' readonly='readonly' />&nbsp;\n", ($row[0]+1));
	printf("Libellé:&nbsp;<input type='text' size='70' maxlength='70' name='lib_par' id='lib_par' placeholder='Nom du domaine' />\n");
	printf("</td></tr>\n<tr><td>\n");
	printf("Abrégé:&nbsp;<input type='text' size='50' maxlength='50' name='abre_par' id='abre_par' placeholder='Version courte du libellé' />\n");
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Enregistrer', 'admin.php');
	printf("</form>\n");
}


function modifParagraphs($id_par) {
	$id_par = intval($id_par);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM paragraphe WHERE id='%d' LIMIT 1", $id_par);
	$result=mysqli_query($base, $request);
	$row=mysqli_fetch_object($result);
	dbDisconnect($base);
	printf("<form method='post' id='modif_par' action='modif.php?action=record_modif_par' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Modification de domaine</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Numero:&nbsp;<input type='text' size='2' maxlength='2' style='background:lightgrey;' name='num_par' id='num_par' value='%s' readonly='readonly' />&nbsp;\n", $row->numero);
	printf("Libellé:&nbsp;<input type='text' size='70' maxlength='70' name='lib_par' id='lib_par' value=\"%s\" />\n", traiteStringFromBDD($row->libelle));
	printf("<input type='hidden' name='id_par' id='id_par' value='%s' />\n", $row->id);
	printf("</td></tr>\n<tr><td>\n");
	printf("Abrégé:&nbsp;<input type='text' size='50' maxlength='50' name='abre_par' id='abre_par' value=\"%s\" />\n", traiteStringFromBDD($row->abrege));
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php?action=modifications', $back=False);
	printf("</form>\n");
}


function supprParagraphs($id_par) {
	$id_par = intval($id_par);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d'", $id_par);
	$result = mysqli_query($base, $request);
	if (mysqli_num_rows($result)) {
		linkMsg("#", "Il existe au moins un sous-domaine. Supprimez le avant d'effacer le domaine.", "alert.png");
	} else {
		$request = sprintf("DELETE FROM paragraphe WHERE id='%d'", $id_par);
		if (mysqli_query($base, $request)) {
			linkMsg("#", "Domaine effacé.", "ok.png");
		} else {
			linkMsg("#", "Echec de la suppression du domaine.", "alert.png");
		}
	}
	dbDisconnect($base);
}


function recordParagraph($tab, $action) {
	$numero = isset($tab['num_par']) ? intval(trim($tab['num_par'])) : NULL;
	$libelle = isset($tab['lib_par']) ? ucfirst(traiteStringToBDD($tab['lib_par'])) : NULL;
	$abrege = isset($tab['abre_par']) ? ucfirst(traiteStringToBDD($tab['abre_par'])) : NULL;
	$id = isset($tab['id_par']) ? intval(trim($tab['id_par'])) : NULL;
	$base = dbConnect();
	if ($action=='add') {
		$request = sprintf("INSERT INTO paragraphe (numero, libelle, abrege) VALUES ('%d', '%s', '%s')", $numero, $libelle, $abrege);
	} elseif ($action=='modif') {
		$request = sprintf("UPDATE paragraphe SET libelle='%s', abrege='%s' WHERE id='%d'",$libelle, $abrege, $id);
	}
	if (mysqli_query($base, $request)){
		linkMsg('#', sprintf("Enregistrement du domaine <b>%s</b> effectué", traiteStringFromBDD($libelle)), 'ok.png');
	} else {
		linkMsg('admin.png', "Erreur d'enregistrement", 'alert.png');
	}
	dbDisconnect($base);
	if ($action=='modif') {
		linkMsg('admin.php?action=modifications', 'Réaliser une autre modification', 'ok.png');
	}
}


function addSubParagraphs() {
	$base = dbConnect();
	$request = "SELECT * FROM paragraphe";
	$result=mysqli_query($base, $request);
	dbDisconnect($base);
	if (mysqli_num_rows($result)) { // au moins un domain doit exister pour un sous-domaine
		printf("<form method='post' id='new_sub_par' action='admin.php?action=new_sub_par' onsubmit='return champs_ok(this)'>\n");
		printf("<fieldset>\n<legend>Saisie d'un nouveau sous-domaine</legend>\n");
		printf("<table>\n<tr><td>\n");
		printf("Domaine:&nbsp;<select name='id_par' id='id_par' onchange='complete(this.value, \"sub_par\")'>\n");
		printf("<option selected='selected' value=''>&nbsp;</option>\n");
		while($row=mysqli_fetch_object($result)) {
			printf("<option value='%s'>%s</option>\n", $row->id, $row->numero." - ".traiteStringFromBDD($row->libelle));
		}
		printf("</select>\n");
		printf("</td></tr>\n<tr><td>\n");
		printf("Numero:&nbsp;<input type='text' size='2' maxlength='2' style='background:lightgrey;' name='num_sub_par' id='num_sub_par' readonly='readonly' />&nbsp;\n");
		printf("Libellé:&nbsp;<input type='text' size='70' maxlength='100' name='lib_sub_par' id='lib_sub_par' placeholder='Nom du sous-domaine' />\n");
		printf("</td></tr>\n<tr><td>\n");
		printf("<textarea name='comment' id='comment' cols='70' rows='2' placeholder='Résumé des thèmes abordés'></textarea>");
		printf("</td>\n</tr>\n</table>\n</fieldset>\n");
		validForms('Enregistrer', 'admin.php');
		printf("</form>\n");
	}
}


function modifSubParagraphs($id_sub_par) {
	$id_sub_par = intval($id_sub_par);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM sub_paragraphe WHERE id='%d' LIMIT 1", $id_sub_par);
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$id_par = $row->id_paragraphe;
	$req_par = sprintf("SELECT * FROM paragraphe WHERE id='%d' LIMIT 1", $id_par);
	$res_par = mysqli_query($base, $req_par);
	$row_par = mysqli_fetch_array($res_par);
	dbDisconnect($base);
	printf("<form method='post' id='modif_sub_par' action='modif.php?action=record_modif_sub_par' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Modification de sous-domaine</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Domaine:&nbsp;<input type='text' size='70' maxlength='70' name='lib_par' id='lib_par' readonly='readonly' value=\"%s\" style='background:lightgrey;' />\n", traiteStringFromBDD($row_par['libelle']));
	printf("</td></tr>\n<tr><td>\n");
	printf("Numero:&nbsp;<input type='text' size='2' maxlength='2' style='background:lightgrey;' name='num_sub_par' id='num_sub_par' readonly='readonly' value=\"%s\" />&nbsp;\n", $row->numero);
	printf("<input type='hidden' size='3' maxlength='3' name='id_sub_par' id='id_sub_par' value=\"%s\" />\n", $row->id);
	printf("Libellé:&nbsp;<input type='text' size='70' maxlength='100' name='lib_sub_par' id='lib_sub_par' value=\"%s\" />\n", traiteStringFromBDD($row->libelle));
	printf("</td></tr>\n<tr><td>\n");
	printf("Commentaire:&nbsp;<textarea name='comment' id='comment' cols='70' rows='2'>%s</textarea>", traiteStringFromBDD($row->comment));
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php?action=modifications', $back=False);
	printf("\n</form>\n");
}


function supprSubParagraphs($id_sub_par) {
	$id_sub_par = intval($id_sub_par);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM question WHERE id_sub_paragraphe='%d'", $id_sub_par);
	$result = mysqli_query($base, $request);
	if (mysqli_num_rows($result)) {
		linkMsg("#", "Il existe au moins une question rattachée à ce sous-domaine. Supprimez la avant d'effacer le sous-domaine.", "alert.png");
	} else {
		$request = sprintf("DELETE FROM sub_paragraphe WHERE id = '%d'", $id_sub_par);
		if (mysqli_query($base, $request)) {
			linkMsg("#", "Sous-domaine effacé.", "ok.png");
		} else {
			linkMsg("#", "Echec de la suppression du sous-domaine.", "alert.png");
		}
	}
	dbDisconnect($base);
}


function recordSubParagraph($tab, $action) {
	$id_par = isset($tab['id_par']) ? intval(trim($tab['id_par'])) : NULL;
	$id_sub_par = isset($tab['id_sub_par']) ? intval(trim($tab['id_sub_par'])) : NULL;
	$numero = isset($tab['num_sub_par']) ? intval(trim($tab['num_sub_par'])) : NULL;
	$libelle = isset($tab['lib_sub_par']) ? ucfirst(mb_strtolower(traiteStringToBDD($tab['lib_sub_par']))) : NULL;
	$comment = isset($tab['comment']) ? ucfirst(mb_strtolower(traiteStringToBDD($tab['comment']))) : NULL;
	$base = dbConnect();
	if ($action === 'add') {
		$request = sprintf("INSERT INTO sub_paragraphe (id_paragraphe, numero, libelle, comment) VALUES ('%d', '%d', '%s', '%s')", $id_par, $numero, $libelle, $comment);
	} elseif ($action === 'modif') {
		$request = sprintf("UPDATE sub_paragraphe SET libelle='%s', comment='%s' WHERE id='%d'", $libelle, $comment, $id_sub_par);
	}
	if (mysqli_query($base, $request)){
		linkMsg('#', sprintf("Enregistrement du sous-domaine <b>%s</b> effectué", traiteStringFromBDD($libelle)), 'ok.png');
	} else {
		linkMsg('admin.png', "Erreur d'enregistrement", 'alert.png');
	}
	dbDisconnect($base);
	if ($action === 'modif') {
		linkMsg('admin.php?action=modifications', 'Réaliser une autre modification', 'ok.png');
	}
}


function addQuestion() {
	$base = dbConnect();
	$request = "SELECT * FROM paragraphe";
	$result=mysqli_query($base, $request);
	dbDisconnect($base);
	// au moins un domaine et un sous-domaine doit exister pour une question
	if (mysqli_num_rows($result)) {
		printf("<form method='post' id='new_question' action='admin.php?action=new_question' onsubmit='return champs_ok(this)'>\n");
		printf("<fieldset>\n<legend>Saisie d'une nouvelle question</legend>\n");
		printf("<table>\n<tr><td colspan='3'>\n");
		printf("Domaine:&nbsp;<select name='quest_id_par' id='quest_id_par' onchange='complete(this.value, \"question\")'>\n");
		printf("<option selected='selected' value=''>&nbsp;</option>\n");
		while($row=mysqli_fetch_object($result)) {
			printf("<option value='%s'>%s</option>\n", $row->id, $row->numero." - ".traiteStringFromBDD($row->libelle));
		}
		printf("</select>\n");
		printf("</td></tr>\n<tr><td colspan='3'>\n");
		printf("Sous-domaine:&nbsp;<select name='quest_id_sub_par' id='quest_id_sub_par' onchange='complete(this.value, \"num_quest\")'>\n");
		printf("<option value=''>&nbsp;</option>");
		printf("</select>\n");
		printf("</td></tr>\n<tr>\n");
		printf("<td>Numero:&nbsp;<input type='text' size='2' maxlength='2' style='background:lightgrey;' name='num_quest' id='num_quest' readonly='readonly' /></td>\n");
		printf("<td><textarea name='libelle' id='libelle' cols='60' rows='2' placeholder='Libellé de la question'></textarea></td>\n");
		printf("<td>Poids:&nbsp;<input type='text' size='2' maxlength='2' name='poids' id='poids' /></td>\n");
		printf("</tr>\n<tr><td colspan='3'>\n");
		printf("<textarea name='quest_action' id='quest_action' cols='70' rows='2' placeholder='Détail de la mesure corrective'></textarea>");
		printf("</td>\n</tr>\n</table>\n</fieldset>\n");
		validForms('Enregistrer', 'admin.php');
		printf("</form>\n");
	}
}


function modifQuestion($id_ques) {
	$base = dbConnect();
	$request = sprintf("SELECT * FROM question WHERE id='%d' LIMIT 1", intval($id_ques));
	$result = mysqli_query($base, $request);
	$row = mysqli_fetch_object($result);
	$id_par = $row->id_paragraphe;
	$id_sub_par = $row->id_sub_paragraphe;
	$req_par = sprintf("SELECT * FROM paragraphe WHERE id='%d' LIMIT 1", $id_par);
	$res_par = mysqli_query($base, $req_par);
	$row_par = mysqli_fetch_object($res_par);
	$lib_par = $row_par->libelle;
	$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id='%d' LIMIT 1", $id_sub_par);
	$res_sub_par = mysqli_query($base, $req_sub_par);
	$row_sub_par = mysqli_fetch_object($res_sub_par);
	$lib_sub_par = $row_sub_par->libelle;
	dbDisconnect($base);
	printf("<form method='post' id='modif_question' action='modif.php?action=record_modif_question' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Modification de question</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Domaine:&nbsp;<input type='text' size='70' maxlength='70' style='background:lightgrey;' name='lib_par' id='lib_par' readonly='readonly' value=\"%s\" />\n", traiteStringFromBDD($lib_par));
	printf("</td></tr>\n<tr><td>\n");
	printf("Sous-domaine:&nbsp;<input type='text' size='70' maxlength='70' style='background:lightgrey;' name='lib_sub_par' id='lib_sub_par' readonly='readonly' value=\"%s\" />\n", traiteStringFromBDD($lib_sub_par));
	printf("</td></tr>\n<tr><td>\n");
	printf("Numero:&nbsp;<input type='text' size='2' maxlength='2' style='background:lightgrey;' name='num_quest' id='num_quest' readonly='readonly' value=\"%s\" />&nbsp;\n", $row->numero);
	printf("<input type='hidden' size='3' maxlength='3' name='id_ques' id='id_ques' value=\"%s\" />\n", $row->id);
	printf("Libellé:&nbsp;<textarea name='libelle' id='libelle' cols='60' rows='2'>%s</textarea>\n", traiteStringFromBDD($row->libelle));
	printf("Poids:&nbsp;<input type='text' size='2' maxlength='2' name='poids' id='poids' value=\"%s\" />\n", $row->poids);
	printf("</td></tr>\n<tr><td>\n");
	printf("Mesure corrective:&nbsp;<textarea name='quest_action' id='quest_action' cols='70' rows='2'>%s</textarea>\n", traiteStringFromBDD($row->mesure));
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php?action=modifications', $back=False);
	printf("</form>\n");
}


function supprQuestion($id_ques) {
	$id_ques = intval($id_ques);
	$base = dbConnect();
	$req_assess = "SELECT assess.annee, assess.reponses, etablissement.nom FROM assess JOIN etablissement ON assess.etablissement=etablissement.id";
	$res_assess = mysqli_query($base, $req_assess);
	$req_quest = sprintf("SELECT question.numero AS 'num_quest', sub_paragraphe.numero AS 'num_sub_par', paragraphe.numero AS 'num_par' FROM question JOIN sub_paragraphe ON question.id_sub_paragraphe=sub_paragraphe.id JOIN paragraphe ON question.id_paragraphe=paragraphe.id WHERE question.id='%d' LIMIT 1", $id_ques);
	$res_quest = mysqli_query($base, $req_quest);
	$row_quest = mysqli_fetch_object($res_quest);
	$question = sprintf("question%s_%s_%s", $row_quest->num_par, $row_quest->num_sub_par, $row_quest->num_quest);
	$ok_for_delete=false;
	while ($row_assess = mysqli_fetch_object($res_assess)) {
		$tab_reponses = unserialize($row_assess->reponses);
		if (isset($tab_reponses[$question])) {
			if ($tab_reponses[$question]) {
				$reponse = textItem($tab_reponses[$question]);
				$msg = sprintf("L'établissement \"%s\" a répondu \"%s\" à cette question en %s.", $row_assess->nom, $reponse, $row_assess->annee);
				linkMsg("#", $msg, "alert.png");
				$ok_for_delete=false;
			}
		} else {
			$ok_for_delete=true;
		}
	}
	if ($ok_for_delete) {
		$request = sprintf("DELETE FROM question WHERE id = '%d'", $id_ques);
		if (mysqli_query($base, $request)) {
			linkMsg("#", "Question effacée.", "ok.png");
		} else {
			linkMsg("#", "Echec de la suppression de la question.", "alert.png");
		}
	} else {
		linkMsg("#", "Suppression impossible.", "alert.png");
	}
	dbDisconnect($base);
}


function recordQuestion($tab, $action) {
	$id_paragraphe = isset($tab['quest_id_par']) ? intval(trim($tab['quest_id_par'])) : NULL;
	$id_sub_paragraphe = isset($tab['quest_id_sub_par']) ? intval(trim($tab['quest_id_sub_par'])) : NULL;
	$id_question = isset($tab['id_ques']) ? intval(trim($tab['id_ques'])) : NULL;
	$numero = isset($tab['num_quest']) ? intval(trim($tab['num_quest'])) : NULL;
	$libelle = isset($tab['libelle']) ? traiteStringToBDD($tab['libelle']) : NULL;
	$mesure = isset($tab['quest_action']) ? traiteStringToBDD($tab['quest_action']) : NULL;
	$poids = isset($tab['poids']) ? intval(trim($tab['poids'])) : NULL;
	$base = dbConnect();
	if ($action === 'add') {
		$request = sprintf("INSERT INTO question (id_paragraphe, id_sub_paragraphe, numero, libelle, mesure, poids) VALUES ('%d', '%d', '%d', '%s', '%s', '%d')", $id_paragraphe, $id_sub_paragraphe, $numero, $libelle, $mesure, $poids);
	} elseif ($action === 'modif') {
		$request = sprintf("UPDATE question SET libelle='%s', mesure='%s', poids='%d' WHERE id='%d'", $libelle, $mesure, $poids, $id_question);
	}
	if (mysqli_query($base, $request)){
		linkMsg('#', sprintf("Enregistrement de la question <b>%s</b> effectué", traiteStringFromBDD($libelle)), 'ok.png');
	} else {
		linkMsg('admin.png', "Erreur d'enregistrement", 'alert.png');
	}
	dbDisconnect($base);
	if ($action === 'modif') {
		linkMsg('admin.php?action=modifications', 'Réaliser une autre modification', 'ok.png');
	}
}


function chooseEtablissement($record=0) {
	$base = dbConnect();
	if ($record) {
		$req_etbs = sprintf("SELECT id,nom FROM etablissement WHERE id NOT IN (%s)", $record->etablissement);
		$listetbs = explode(',', $record->etablissement);
	} else {
		$req_etbs = "SELECT id,nom FROM etablissement";
	}
	$res_etbs = mysqli_query($base, $req_etbs);
	dbDisconnect($base);
	printf("<div class='grid'>\n");

	printf("<select id='result[]' name='result[]' multiple hidden></select>\n");

	printf("<div id='source' class='dropper'>\n");
	printf("<div class='grid_title'>Etablissements existants</div>\n");
	while ($row=mysqli_fetch_object($res_etbs)) {
		printf("<div id='%d' class='draggable'>%s</div>\n", $row->id, $row->nom);
	}
	printf("</div>\n");

	printf("<div id='destination' class='dropper'>\n");
	printf("<div class='grid_title'>Etablissements sélectionnés</div>");
	if ($record) {
		foreach ($listetbs as $id_etab) {
			printf("<div id='%d' class='draggable'>%s</div>\n", intval($id_etab), getEtablissement(intval($id_etab)));
		}
	}
	printf("</div>\n");

	printf("</div>\n");
}


function createUser() {
	$base = dbConnect();
	$req_role = "SELECT id,intitule FROM role WHERE id<>'1'";
	$res_role = mysqli_query($base, $req_role);
	dbDisconnect($base);
	printf("<form method='post' id='new_user' action='admin.php?action=record_user' onsubmit='return user_champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Ajout d'un utilisateur</legend>\n");
	printf("<table>\n<tr><td colspan='3'>\n");
	printf("<input type='text' size='20' maxlength='20' name='prenom' id='prenom' placeholder='Prénom de l&apos;utilisateur' />\n");
	printf("<input type='text' size='20' maxlength='20' name='nom' id='nom' placeholder='Nom de l&apos;utilisateur' />\n");
	printf("Fonction:&nbsp;<select name='role' id='role' >\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row=mysqli_fetch_object($res_role)) {
		printf("<option value='%d'>%s</option>\n", $row->id, $row->intitule);
	}
	printf("</select>\n");
	printf("</td></tr>\n<tr><td colspan='3'>\n");
	printf("<input type='text' size='50' maxlength='50' name='login' id='login' placeholder='Identifiant (prenom.nom)' autocomplete='username' />\n");
	printf("<input type='password' size='20' maxlength='20' name='passwd' id='passwd' placeholder='Mot de passe' autocomplete='current-password' />\n");
	printf("</td></tr>\n</table>\n");
	chooseEtablissement();
	printf("</fieldset>\n");
	validForms('Enregistrer', 'admin.php');
	printf("</form>\n");
}


function selectUserModif() {
	$base = dbConnect();
	$request = "SELECT * FROM users WHERE role<>'1'";
	$result = mysqli_query($base, $request);
	printf("<form method='post' id='modif_user' action='admin.php?action=modif_user' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Modification d'un utilisaeur</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Utilisateur:&nbsp;\n<select name='user' id='user'>\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row=mysqli_fetch_object($result)) {
		printf("<option value='%s'>%s %s</option>\n", $row->id, $row->prenom, $row->nom);
	}
	printf("</select>\n");
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
}


function modifUser($id) {
	$id = intval($id);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM users WHERE id='%d' LIMIT 1", $id);
	$result = mysqli_query($base, $request);
	$record = mysqli_fetch_object($result);
	$listetbs = explode(',', $record->etablissement);
	$req_role = "SELECT id,intitule FROM role WHERE id<>'1'";
	$res_role = mysqli_query($base, $req_role);

	printf("<form method='post' id='modif_user' action='admin.php?action=update_user' onsubmit='return user_champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Modification d'un utilisateur</legend>\n");
	printf("<table>\n<tr><td colspan='3'>\n");
	printf("<input type='hidden' size='3' maxlength='3' name='id_user' id='id_user' value='%s'/>\n", $id);
	printf("Prénom:&nbsp;<input type='text' size='20' maxlength='20' name='prenom' id='prenom' value=\"%s\" />\n", traiteStringFromBDD($record->prenom));
	printf("Nom:&nbsp;<input type='text' size='20' maxlength='20' name='nom' id='nom' value=\"%s\" />\n", traiteStringFromBDD($record->nom));
	printf("Fonction:&nbsp;<select name='role' id='role'>\n");
	printf("<option selected='selected' value='%d'>%s</option>\n", intval($record->role), getRole(intval($record->role)));
	while($row=mysqli_fetch_object($res_role)) {
		printf("<option value='%d'>%s</option>\n", $row->id, $row->intitule);
	}
	printf("</select>\n");
	printf("</td></tr>\n<tr><td colspan='3'>\n");
	printf("Identifiant&nbsp;<input type='text' size='50' maxlength='50' name='login' id='login' value=\"%s\" />\n", traiteStringFromBDD($record->login));
	printf("</td></tr>\n</table>\n");
	chooseEtablissement($record);
	printf("</fieldset>\n");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
	dbDisconnect($base);
}


function recordUser($action) {
	$base = dbConnect();
	if ($action === 'update') {
		$id = isset($_POST['id_user']) ? intval(trim($_POST['id_user'])) : NULL;
	}
	$prenom = isset($_POST['prenom']) ? traiteStringToBDD($_POST['prenom']) : NULL;
	$nom = isset($_POST['nom']) ? traiteStringToBDD($_POST['nom']) : NULL;
	$role = isset($_POST['role']) ? intval(trim($_POST['role'])) : NULL;
	$login = isset($_POST['login']) ? traiteStringToBDD($_POST['login']) : NULL;
	$etbs = isset($_POST['result']) ?  implode(",", $_POST['result']) : NULL;
	switch ($action) {
		case 'add':
			$passwd = isset($_POST['passwd']) ?  traiteStringToBDD($_POST['passwd']) : NULL;
			$passwd = password_hash($passwd, PASSWORD_BCRYPT);
			$request = sprintf("INSERT INTO users (prenom, nom, role, login, password, etablissement) VALUES ('%s', '%s', '%d', '%s', '%s', '%s')", $prenom, $nom, $role, $login, $passwd, $etbs);
			break;
		case 'update':
			$request = sprintf("UPDATE users SET prenom='%s', nom='%s', role='%d', login='%s', etablissement='%s' WHERE id='%d'", $prenom, $nom, $role, $login, $etbs, $id);
			break;
	}
	if (mysqli_query($base, $request)) {
		switch ($action) {
			case 'add':
				return mysqli_insert_id($base);
				break;
			case 'update':
				return $id;
				break;
		}
	} else {
		return false;
	}
	dbDisconnect($base);
}


function createEtablissement($action='') {
	$base = dbConnect();
	if ($action === 'regroup') {
		printf("<form method='post' id='new_etablissement' action='admin.php?action=record_regroup' onsubmit='return champs_ok(this)'>\n");
		printf("<fieldset>\n<legend>Création d'un établissement de regroupement</legend>\n");
	} else {
		printf("<form method='post' id='new_etablissement' action='admin.php?action=record_etab' onsubmit='return champs_ok(this)'>\n");
		printf("<fieldset>\n<legend>Création d'un établissement</legend>\n");
	}
	printf("<table>\n<tr><td>\n");
	printf("<input type='text' size='65' maxlength='65' name='nom' id='nom' placeholder='Nom de l&apos;établissement' />\n");
	printf("<input type='text' size='10' maxlength='10' name='abrege' id='abrege' placeholder='Nom abrégé' />\n");
	printf("</td></tr>\n<tr><td>\n");
	printf("<input type='text' size='80' maxlength='80' name='adresse' id='adresse' placeholder='Adresse' />\n");
	printf("</td></tr>\n<tr><td>\n");
	printf("<input type='text' size='5' maxlength='5' name='cp' id='cp' placeholder='CP' />\n");
	printf("<input type='text' size='20' maxlength='20' name='ville' id='ville' placeholder='Ville' />\n");
	printf("</td></tr>\n</table>\n</fieldset>\n");

	if ($action === 'regroup') {
		$request = "SELECT id,nom,abrege FROM etablissement";
		$result = mysqli_query($base, $request);
		printf("<fieldset>\n<legend>Comprend les établissements suivants</legend>\n");
		while($row=mysqli_fetch_object($result)) {
			if (stripos($row->abrege, "_TEAM") === false) {
				printf("<input type='checkbox' name='regroup[]' value='%d' />%s<br />\n", $row->id, $row->nom);
			}
		}
		printf("</fieldset>\n");
	}
	validForms('Enregistrer', 'admin.php');
	printf("</form>\n");
	dbDisconnect($base);
}


function selectEtablissementModif() {
	$result=getEtablissement();
	printf("<form method='post' id='modif_etab' action='admin.php?action=modif_etab' onsubmit='return champs_ok(this)'>\n");
	printf("<fieldset>\n<legend>Modification d'un établissement</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Etablissement:&nbsp;\n<select name='etablissement' id='etablissement'>\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row=mysqli_fetch_object($result)) {
		if (stripos($row->abrege, "_TEAM") !== false) {
			printf("<option value='%s'>%s</option>\n", $row->id, $row->nom." (regroupement)");
		} else {
			printf("<option value='%s'>%s</option>\n", $row->id, $row->nom);
		}
	}
	printf("</select>\n");
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
}


function modifEtablissement($id) {
	$id = intval($id);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $id);
	$result = mysqli_query($base, $request);
	$record = mysqli_fetch_object($result);

	if (stripos($record->abrege, "_TEAM") === false) {
		printf("<form method='post' id='modif_etablissement' action='admin.php?action=update_etab' onsubmit='return champs_ok(this)'>\n");
	} else {
		printf("<form method='post' id='modif_etablissement' action='admin.php?action=update_regroup' onsubmit='return champs_ok(this)'>\n");
	}
	printf("<fieldset>\n<legend>Modification d'un établissement</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("<input type='hidden' size='3' maxlength='3' name='id_etab' id='id_etab' value='%s'/>\n", $id);
	printf("Nom:&nbsp;<input type='text' size='65' maxlength='65' name='nom' id='nom' value=\"%s\" />\n", traiteStringFromBDD($record->nom));
	printf("</td></tr>\n<tr><td>\n");
	if (stripos($record->abrege, "_TEAM") === false) {
		printf("Nom abrégé:&nbsp;<input type='text' size='10' maxlength='10' name='abrege' id='abrege' value='%s'/>\n", traiteStringFromBDD($record->abrege));
	} else {
		printf("Nom abrégé:&nbsp;<input type='text' size='10' maxlength='10' name='abrege' id='abrege' value='%s' readonly='readonly' style='background:#777777;' />&nbsp;\n", traiteStringFromBDD($record->abrege));
	}
	printf("</td></tr>\n<tr><td>\n");
	printf("Adresse:&nbsp;<input type='text' size='80' maxlength='80' name='adresse' id='adresse' value=\"%s\"/>&nbsp;\n", traiteStringFromBDD($record->adresse));
	printf("</td></tr>\n<tr><td>\n");
	printf("Code postal:&nbsp;<input type='text' size='5' maxlength='5' name='cp' id='cp' value='%s'/>&nbsp;\n", $record->code_postal);
	printf("Ville:&nbsp;<input type='text' size='20' maxlength='20' name='ville' id='ville' value='%s'/>&nbsp;\n", traiteStringFromBDD($record->ville));
	printf("</td></tr>\n</table>\n</fieldset>\n");

	if (stripos($record->abrege, "_TEAM") !== false) {
		$req_etab = "SELECT id,nom,abrege FROM etablissement";
		$res_etab = mysqli_query($base, $req_etab);
		$team = explode(',', $record->regroupement);
		printf("<fieldset>\n<legend>Comprend les établissements suivants</legend>\n");
		while($row=mysqli_fetch_object($res_etab)) {
			if (stripos($row->abrege, "_TEAM") === false) {
				if ( array_search($row->id, $team) !== false) {
					printf("<input type='checkbox' name='regroup[]' value='%d' checked='checked' />%s<br />\n", $row->id, $row->nom);
				} else {
					printf("<input type='checkbox' name='regroup[]' value='%d' />%s<br />\n", $row->id, $row->nom);
				}
			}
		}
		printf("</fieldset>\n");
	}
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
	dbDisconnect($base);
}


function recordEtablissement($action) {
	$base = dbConnect();
	if (($action === 'update') || ($action === 'update_regroup')) {
		$id = isset($_POST['id_etab']) ? intval(trim($_POST['id_etab'])) : NULL;
	}
	$nom = isset($_POST['nom']) ? traiteStringToBDD($_POST['nom']) : NULL;
	$abrege = isset($_POST['abrege']) ? mb_strtoupper(traiteStringToBDD($_POST['abrege'])) : NULL;
	$adresse = isset($_POST['adresse']) ? traiteStringToBDD($_POST['adresse']) : NULL;
	$code_postal = isset($_POST['cp']) ? intval(trim($_POST['cp'])) : NULL;
	$ville = isset($_POST['ville']) ? traiteStringToBDD($_POST['ville']) : NULL;
	$regroup = isset($_POST['regroup']) ?  implode(",", $_POST['regroup']) : NULL;
	$objectifs = createDefaultObjectifs($base);
	switch ($action) {
		case 'add':
			$request = sprintf("INSERT INTO etablissement (nom, abrege, adresse, ville, code_postal, objectifs) VALUES ('%s', '%s', '%s', '%s', '%d', '%s')", $nom, $abrege, $adresse, $ville, $code_postal, $objectifs);
			break;
		case 'add_regroup':
			$abrege = $abrege."_TEAM";
			$request = sprintf("INSERT INTO etablissement (nom, abrege, adresse, ville, code_postal, regroupement, objectifs) VALUES ('%s', '%s', '%s', '%s', '%d', '%s', '%s')", $nom, $abrege, $adresse, $ville, $code_postal, $regroup, $objectifs);
			break;
		case 'update':
			$request = sprintf("UPDATE etablissement SET nom='%s', abrege='%s', adresse='%s', ville='%s', code_postal='%d' WHERE id='%d'", $nom, $abrege, $adresse, $ville, $code_postal, $id);
			break;
		case 'update_regroup':
			$request = sprintf("UPDATE etablissement SET nom='%s', abrege='%s', adresse='%s', ville='%s', code_postal='%d', regroupement='%s' WHERE id='%d'", $nom, $abrege, $adresse, $ville, $code_postal, $regroup, $id);
			break;
	}
	if (mysqli_query($base, $request)) {
		switch ($action) {
			case 'add':
				return mysqli_insert_id($base);
				break;
			case 'add_regroup':
				return mysqli_insert_id($base);
				break;
			case 'update':
				return $id;
				break;
			case 'update_regroup':
				return $id;
				break;
		}
	} else {
		return false;
	}
	dbDisconnect($base);
}


function modifications() {
	$base = dbConnect();
	$req_par = "SELECT * FROM paragraphe ORDER BY numero";
	$res_par = mysqli_query($base, $req_par);
	printf("<form action='%s' method='post' id='form_mod_sup'>", $_SERVER['PHP_SELF']);
	printf("<table>\n");
	printf("<tr><th style='width:12%%'>Domaine</th><th style='width:12%%'>Sous-domaine</th><th>Question</th><th>Poids</th><th colspan='2'>&nbsp;</th></tr>\n");
	while ($row_par=mysqli_fetch_object($res_par)) {
		printf("<tr>\n<td>%s %s</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n", $row_par->numero, traiteStringFromBDD($row_par->libelle));
		printf("<td><a href='modif.php?action=%s&amp;value=%s'><img src='pict/edit.png' alt='modif' title='Modifier' width='24px' /></a></td>\n", 'modif_par', $row_par->id);
		printf("<td><input type='button' class='btn_suppr' onclick='valid_suppr(\"%s\",%s)' /></td>\n", 'suppr_par', $row_par->id);
		printf("</tr>\n");
		$req_sub_par = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d' ORDER BY numero", $row_par->id);
		$res_sub_par = mysqli_query($base, $req_sub_par);
		while ($row_sub_par=mysqli_fetch_object($res_sub_par)) {
			printf("<tr>\n<td>&nbsp;</td><td>%s.%s %s</td><td>&nbsp;</td><td>&nbsp;</td>\n", $row_par->numero, $row_sub_par->numero, traiteStringFromBDD($row_sub_par->libelle));
			printf("<td><a href='modif.php?action=%s&amp;value=%s'><img src='pict/edit.png' alt='modif' title='Modifier' width='24px' /></a></td>\n", 'modif_sub_par', $row_sub_par->id);
			printf("<td><input type='button' class='btn_suppr' onclick='valid_suppr(\"%s\",%s)' /></td>\n", 'suppr_sub_par', $row_sub_par->id);
			printf("</tr>\n");
			$req_quest = sprintf("SELECT * FROM question WHERE (id_paragraphe='%d' AND id_sub_paragraphe='%d') ORDER BY numero", $row_par->id, $row_sub_par->id);
			$res_quest = mysqli_query($base, $req_quest);
			while ($row_quest=mysqli_fetch_object($res_quest)) {
				printf("<tr>\n<td>&nbsp;</td><td>&nbsp;</td><td style='text-align:left;'>%s.%s.%s %s</td><td>%s</td>\n", $row_par->numero, $row_sub_par->numero, $row_quest->numero, traiteStringFromBDD($row_quest->libelle), $row_quest->poids);
				printf("<td><a href='modif.php?action=%s&amp;value=%s'><img src='pict/edit.png' alt='modif' title='Modifier' width='24px' /></a></td>\n", 'modif_question', $row_quest->id);
				printf("<td><input type='button' class='btn_suppr' onclick='valid_suppr(\"%s\",%s)' /></td>\n", 'suppr_question', $row_quest->id);
				printf("</tr>\n");
			}
		}
	}
	printf("</table>\n");
	printf("</form>");
	dbDisconnect($base);
}


function createDefaultObjectifs($base) {
	$objs = array();
	$req_par = "SELECT * FROM paragraphe ORDER BY numero";
	$res_par = mysqli_query($base, $req_par);
	while ($row_par=mysqli_fetch_object($res_par)) {
		$objCurr = sprintf("obj_%d", $row_par->id);
		$objs[$objCurr] = 7;
	}
	$result = mysqli_real_escape_string($base, serialize($objs));
	return $result;
}





?>
