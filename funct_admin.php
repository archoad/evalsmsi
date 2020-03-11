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
	genSyslog(__FUNCTION__);
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


function chooseEtablissement($record=0) {
	$nonce = $_SESSION['nonce'];
	$base = dbConnect();
	if ($record) {
		$req_etbs = sprintf("SELECT id,nom,abrege FROM etablissement WHERE id NOT IN (%s)", $record->etablissement);
		$listetbs = explode(',', $record->etablissement);
	} else {
		$req_etbs = "SELECT id,nom,abrege FROM etablissement";
	}
	$res_etbs = mysqli_query($base, $req_etbs);
	dbDisconnect($base);
	printf("<div class='grid'>\n");
	printf("<select id='result[]' name='result[]' multiple hidden></select>\n");
	printf("<div id='source' class='dropper'>\n");
	printf("<div class='grid_title'>Etablissements existants</div>\n");
	while ($row=mysqli_fetch_object($res_etbs)) {
		if (stripos($row->abrege, "_TEAM") !== false) {
			printf("<div id='%d' class='draggable'>%s (regroupement)</div>\n", $row->id, $row->nom);
		} else {
			printf("<div id='%d' class='draggable'>%s</div>\n", $row->id, $row->nom);
		}
	}
	printf("</div>\n");
	printf("<div id='destination' class='dropper'>\n");
	printf("<div class='grid_title'>Etablissements sélectionnés</div>");
	if ($record) {
		foreach ($listetbs as $id_etab) {
			$name_etab = getEtablissement($id_etab);
			printf("<div id='%d' class='draggable'>%s</div>\n", $id_etab, $name_etab);
		}
	}
	printf("</div>\n");
	printf("</div>\n");
	printf("<script nonce='%s' src='js/dragdrop.js'></script>", $nonce);
}


function createUser() {
	genSyslog(__FUNCTION__);
	$nonce = $_SESSION['nonce'];
	$base = dbConnect();
	$req_role = "SELECT id,intitule FROM role WHERE id<>'1'";
	$res_role = mysqli_query($base, $req_role);
	dbDisconnect($base);
	printf("<form method='post' id='user' action='admin.php?action=record_user'>\n");
	printf("<fieldset>\n<legend>Ajout d'un utilisateur</legend>\n");
	printf("<table>\n<tr><td colspan='3'>\n");
	printf("<input type='text' size='20' maxlength='20' name='prenom' id='prenom' placeholder='Prénom de l&apos;utilisateur' required>\n");
	printf("<input type='text' size='20' maxlength='20' name='nom' id='nom' placeholder='Nom de l&apos;utilisateur' required>\n");
	printf("Fonction:&nbsp;<select name='role' id='role' required>\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row=mysqli_fetch_object($res_role)) {
		printf("<option value='%d'>%s</option>\n", $row->id, $row->intitule);
	}
	printf("</select>\n");
	printf("</td></tr>\n<tr><td colspan='3'>\n");
	printf("<input type='text' size='50' maxlength='50' name='login' id='login' placeholder='Identifiant (prenom.nom)' autocomplete='username' required>\n");
	printf("<input type='password' size='20' maxlength='20' name='passwd' id='passwd' placeholder='Mot de passe' autocomplete='current-password' required>\n");
	printf("</td></tr>\n</table>\n");
	chooseEtablissement();
	printf("</fieldset>\n");
	validForms('Enregistrer', 'admin.php');
	printf("</form>\n");
	printf("<script nonce='%s'>document.getElementById('user').addEventListener('submit', function(){userFormValidity(event);});</script>", $nonce);
}


function selectUserModif() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = "SELECT * FROM users WHERE role<>'1'";
	$result = mysqli_query($base, $request);
	printf("<form method='post' id='modif_user' action='admin.php?action=modif_user'>\n");
	printf("<fieldset>\n<legend>Modification d'un utilisaeur</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Utilisateur:&nbsp;\n<select name='user' id='user' required>\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row=mysqli_fetch_object($result)) {
		printf("<option value='%s'>%s %s</option>\n", $row->id, $row->prenom, $row->nom);
	}
	printf("</select>\n");
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
}


function modifUser() {
	genSyslog(__FUNCTION__);
	$nonce = $_SESSION['nonce'];
	$base = dbConnect();
	$request = sprintf("SELECT * FROM users WHERE id='%d' LIMIT 1", $_SESSION['current_user']);
	$result = mysqli_query($base, $request);
	$record = mysqli_fetch_object($result);
	$listetbs = explode(',', $record->etablissement);
	$req_role = "SELECT id,intitule FROM role WHERE id<>'1'";
	$res_role = mysqli_query($base, $req_role);

	printf("<form method='post' id='user' action='admin.php?action=update_user'>\n");
	printf("<fieldset>\n<legend>Modification d'un utilisateur</legend>\n");
	printf("<table>\n<tr><td colspan='3'>\n");
	printf("Prénom:&nbsp;<input type='text' size='20' maxlength='20' name='prenom' id='prenom' value='%s' required>\n", traiteStringFromBDD($record->prenom));
	printf("Nom:&nbsp;<input type='text' size='20' maxlength='20' name='nom' id='nom' value='%s' required>\n", traiteStringFromBDD($record->nom));
	printf("Fonction:&nbsp;<select name='role' id='role' required>\n");
	printf("<option selected='selected' value='%d'>%s</option>\n", intval($record->role), getRole(intval($record->role)));
	while($row=mysqli_fetch_object($res_role)) {
		printf("<option value='%d'>%s</option>\n", $row->id, $row->intitule);
	}
	printf("</select>\n");
	printf("</td></tr>\n<tr><td colspan='3'>\n");
	printf("Identifiant&nbsp;<input type='text' size='50' maxlength='50' name='login' id='login' value='%s' required>\n", traiteStringFromBDD($record->login));
	printf("</td></tr>\n</table>\n");
	chooseEtablissement($record);
	printf("</fieldset>\n");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
	printf("<script nonce='%s'>document.getElementById('user').addEventListener('submit', function(){userFormValidity(event);});</script>", $nonce);
	dbDisconnect($base);
}


function recordUser($action) {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$prenom = isset($_POST['prenom']) ? traiteStringToBDD($_POST['prenom']) : NULL;
	$nom = isset($_POST['nom']) ? traiteStringToBDD($_POST['nom']) : NULL;
	$role = isset($_POST['role']) ? intval(trim($_POST['role'])) : NULL;
	$login = isset($_POST['login']) ? traiteStringToBDD($_POST['login']) : NULL;
	$etbs = isset($_POST['result']) ?  implode(",", $_POST['result']) : NULL;
	if ($role === 1) { return false; }
	switch ($action) {
		case 'add':
			$passwd = isset($_POST['passwd']) ?  traiteStringToBDD($_POST['passwd']) : NULL;
			$passwd = password_hash($passwd, PASSWORD_BCRYPT);
			$request = sprintf("INSERT INTO users (prenom, nom, role, login, password, etablissement) VALUES ('%s', '%s', '%d', '%s', '%s', '%s')", $prenom, $nom, $role, $login, $passwd, $etbs);
			break;
		case 'update':
			$id = intval($_SESSION['current_user']);
			$request = sprintf("UPDATE users SET prenom='%s', nom='%s', role='%d', login='%s', etablissement='%s' WHERE id='%d'", $prenom, $nom, $role, $login, $etbs, $id);
			break;
	}
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
		if (mysqli_query($base, $request)) {
			switch ($action) {
				case 'add':
					dbDisconnect($base);
					return true;
					break;
				case 'update':
					unset($_SESSION['current_user']);
					dbDisconnect($base);
					return true;
					break;
			}
		} else {
			dbDisconnect($base);
			return false;
		}
	} else {
		return false;
	}
}


function createEtablissement($action='') {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	if ($action === 'regroup') {
		printf("<form method='post' id='new_etablissement' action='admin.php?action=record_regroup'>\n");
		printf("<fieldset>\n<legend>Création d'un établissement de regroupement</legend>\n");
	} else {
		printf("<form method='post' id='new_etablissement' action='admin.php?action=record_etab'>\n");
		printf("<fieldset>\n<legend>Création d'un établissement</legend>\n");
	}
	printf("<table>\n<tr><td>\n");
	printf("<input type='text' size='65' maxlength='65' name='nom' id='nom' placeholder='Nom de l&apos;établissement' required>\n");
	printf("<input type='text' size='10' maxlength='10' name='abrege' id='abrege' placeholder='Nom abrégé' required>\n");
	printf("</td></tr>\n<tr><td>\n");
	printf("<input type='text' size='80' maxlength='80' name='adresse' id='adresse' placeholder='Adresse' required>\n");
	printf("</td></tr>\n<tr><td>\n");
	printf("<input type='text' size='5' maxlength='5' name='cp' id='cp' placeholder='CP' pattern='[0-9]{5}' required>\n");
	printf("<input type='text' size='20' maxlength='20' name='ville' id='ville' placeholder='Ville' required>\n");
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
	genSyslog(__FUNCTION__);
	$result=getEtablissement();
	printf("<form method='post' id='modif_etab' action='admin.php?action=modif_etab' >\n");
	printf("<fieldset>\n<legend>Modification d'un établissement</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Etablissement:&nbsp;\n<select name='etablissement' id='etablissement' required>\n");
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


function modifEtablissement() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['current_etab']);
	$result = mysqli_query($base, $request);
	$record = mysqli_fetch_object($result);

	if (stripos($record->abrege, "_TEAM") === false) {
		printf("<form method='post' id='modif_etablissement' action='admin.php?action=update_etab'>\n");
	} else {
		printf("<form method='post' id='modif_etablissement' action='admin.php?action=update_regroup'>\n");
	}
	printf("<fieldset>\n<legend>Modification d'un établissement</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Nom:&nbsp;<input type='text' size='65' maxlength='65' name='nom' id='nom' value='%s' required>\n", traiteStringFromBDD($record->nom));
	printf("</td></tr>\n<tr><td>\n");
	if (stripos($record->abrege, "_TEAM") === false) {
		printf("Nom abrégé:&nbsp;<input type='text' size='10' maxlength='10' name='abrege' id='abrege' value='%s' required>\n", traiteStringFromBDD($record->abrege));
	} else {
		printf("Nom abrégé:&nbsp;<input type='text' size='10' maxlength='10' name='abrege' id='abrege' value='%s' readonly='readonly' class='protected' />&nbsp;\n", traiteStringFromBDD($record->abrege));
	}
	printf("</td></tr>\n<tr><td>\n");
	printf("Adresse:&nbsp;<input type='text' size='80' maxlength='80' name='adresse' id='adresse' value='%s' required>&nbsp;\n", traiteStringFromBDD($record->adresse));
	printf("</td></tr>\n<tr><td>\n");
	printf("Code postal:&nbsp;<input type='text' size='5' maxlength='5' name='cp' id='cp' value='%s' pattern='[0-9]{5}'  required>&nbsp;\n", $record->code_postal);
	printf("Ville:&nbsp;<input type='text' size='20' maxlength='20' name='ville' id='ville' value='%s' required>&nbsp;\n", traiteStringFromBDD($record->ville));
	printf("</td></tr>\n</table>\n</fieldset>\n");

	if (stripos($record->abrege, "_TEAM") !== false) {
		$req_etab = "SELECT id,nom,abrege FROM etablissement";
		$res_etab = mysqli_query($base, $req_etab);
		$team = explode(',', $record->regroupement);
		printf("<fieldset>\n<legend>Comprend les établissements suivants</legend>\n");
		while($row=mysqli_fetch_object($res_etab)) {
			if (stripos($row->abrege, "_TEAM") === false) {
				if ( array_search($row->id, $team) !== false) {
					printf("<input type='checkbox' name='regroup[]' value='%d' checked='checked'>%s<br />\n", $row->id, $row->nom);
				} else {
					printf("<input type='checkbox' name='regroup[]' value='%d'>%s<br />\n", $row->id, $row->nom);
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
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$nom = isset($_POST['nom']) ? traiteStringToBDD($_POST['nom']) : NULL;
	$abrege = isset($_POST['abrege']) ? mb_strtoupper(traiteStringToBDD($_POST['abrege'])) : NULL;
	$adresse = isset($_POST['adresse']) ? traiteStringToBDD($_POST['adresse']) : NULL;
	$code_postal = isset($_POST['cp']) ? intval(trim($_POST['cp'])) : NULL;
	$ville = isset($_POST['ville']) ? traiteStringToBDD($_POST['ville']) : NULL;
	$regroup = isset($_POST['regroup']) ?  implode(",", $_POST['regroup']) : NULL;
	$objectifs = createDefaultObjectifs();
	switch ($action) {
		case 'add':
			$request = sprintf("INSERT INTO etablissement (nom, abrege, adresse, ville, code_postal, objectifs) VALUES ('%s', '%s', '%s', '%s', '%d', '%s')", $nom, $abrege, $adresse, $ville, $code_postal, $objectifs);
			break;
		case 'add_regroup':
			$abrege = $abrege."_TEAM";
			$request = sprintf("INSERT INTO etablissement (nom, abrege, adresse, ville, code_postal, regroupement, objectifs) VALUES ('%s', '%s', '%s', '%s', '%d', '%s', '%s')", $nom, $abrege, $adresse, $ville, $code_postal, $regroup, $objectifs);
			break;
		case 'update':
			$request = sprintf("UPDATE etablissement SET nom='%s', abrege='%s', adresse='%s', ville='%s', code_postal='%d' WHERE id='%d'", $nom, $abrege, $adresse, $ville, $code_postal, $_SESSION['current_etab']);
			break;
		case 'update_regroup':
			$request = sprintf("UPDATE etablissement SET nom='%s', abrege='%s', adresse='%s', ville='%s', code_postal='%d', regroupement='%s' WHERE id='%d'", $nom, $abrege, $adresse, $ville, $code_postal, $regroup, $_SESSION['current_etab']);
			break;
	}
	if (isset($_SESSION['token'])) {
		unset($_SESSION['token']);
		if (mysqli_query($base, $request)) {
			switch ($action) {
				case 'add':
					dbDisconnect($base);
					return true;
					break;
				case 'add_regroup':
					dbDisconnect($base);
					return true;
					break;
				case 'update':
					unset($_SESSION['current_etab']);
					dbDisconnect($base);
					return true;
					break;
				case 'update_regroup':
					unset($_SESSION['current_etab']);
					dbDisconnect($base);
					return true;
					break;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function selectQuizModification() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM quiz");
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	printf("<form method='post' id='modif_quiz' action='admin.php?action=modif_quiz' >\n");
	printf("<fieldset>\n<legend>Modification d'un questionnaire</legend>\n");
	printf("<table>\n<tr><td>\n");
	printf("Questionnaire:&nbsp;\n<select name='quiz' id='quiz' required>\n");
	printf("<option selected='selected' value=''>&nbsp;</option>\n");
	while($row = mysqli_fetch_object($result)) {
		printf("<option value='%s'>%s</option>\n", $row->id, $row->nom);
	}
	printf("</select>\n");
	printf("</td>\n</tr>\n</table>\n</fieldset>\n");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>\n");
}


function modifications() {
	genSyslog(__FUNCTION__);
	$quiz = getJsonFile();
	printf("<table>\n");
	printf("<tr><th class='modifquiz'>Domaine</th><th class='modifquiz'>Sous-domaine</th><th>Question</th><th>Poids</th><th>&nbsp;</th></tr>\n");
	for ($d=0; $d<count($quiz); $d++) {
		$num_dom = $quiz[$d]['numero'];
		$subDom = $quiz[$d]['subdomains'];
		printf("<tr>\n<td>%s %s</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n</tr>\n", $num_dom, $quiz[$d]['libelle']);
		for ($sd=0; $sd<count($subDom); $sd++) {
			$num_sub_dom = $subDom[$sd]['numero'];
			$questions = $subDom[$sd]['questions'];
			printf("<tr>\n<td>&nbsp;</td><td>%s.%s %s</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>\n</tr>\n", $num_dom, $num_sub_dom, $subDom[$sd]['libelle']);
			for ($q=0; $q<count($questions); $q++) {
				$num_question = $questions[$q]['numero'];
				printf("<tr>\n<td>&nbsp;</td><td>&nbsp;</td><td class='pleft'>%s.%s.%s %s</td><td>%s</td>\n<td>&nbsp;</td></tr>\n", $num_dom, $num_sub_dom, $num_question, $questions[$q]['libelle'], $questions[$q]['poids']);
			}
		}
	}
	printf("</table>\n");
}


function createDefaultObjectifs() {
	global $cheminDATA;
	genSyslog(__FUNCTION__);
	$objectives = array();
	$base = dbConnect();
	$request = sprintf("SELECT * FROM quiz");
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	while ($row = mysqli_fetch_object($result)) {
		$domains = array();
		$jsonFile = sprintf("%s%s", $cheminDATA, $row->filename);
		$jsonSource = file_get_contents($jsonFile);
		$jsonQuiz = json_decode($jsonSource, true);
		for ($i=0; $i<count($jsonQuiz); $i++) {
			$objCurr = sprintf("obj_%d", $jsonQuiz[$i]['numero']);
			$domains[$objCurr] = 4;
		}
		$objectives[$row->id] = $domains;
	}
	$output = json_encode($objectives);
	return $output;
}





?>
