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
	printf("<div class='project'>");
	foreach ($actions as $value) {
		$request = sprintf("%s TABLE %s", $value, $tableNames);
		if ($result = mysqli_query($base, $request)) {
			printf("<table>");
			printf("<tr><th>Nom de la table</th><th>Opération</th><th>Type de message</th><th>Message</th></tr>");
			while ($row = mysqli_fetch_object($result)) {
				printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>", $row->Table, $row->Op, $row->Msg_type, $row->Msg_text);
			}
			printf("</table>");
		} else {
			printf("%s: %s", mysqli_errno($base), mysqli_error($base));
		}
	}
	printf("</div>");
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
	printf("<div class='grid'>");
	printf("<select id='result[]' name='result[]' multiple hidden></select>");
	printf("<div id='source' class='dropper'>");
	printf("<div class='grid_title'>Etablissements existants</div>");
	while ($row=mysqli_fetch_object($res_etbs)) {
		if (stripos($row->abrege, "_TEAM") !== false) {
			printf("<div id='%d' class='draggable'>%s (regroupement)</div>", $row->id, $row->nom);
		} else {
			printf("<div id='%d' class='draggable'>%s</div>", $row->id, $row->nom);
		}
	}
	printf("</div>");
	printf("<div id='destination' class='dropper'>");
	printf("<div class='grid_title'>Etablissements sélectionnés</div>");
	if ($record) {
		foreach ($listetbs as $id_etab) {
			$name_etab = getEtablissement($id_etab);
			printf("<div id='%d' class='draggable'>%s</div>", $id_etab, $name_etab);
		}
	}
	printf("</div>");
	printf("</div>");
	printf("<script nonce='%s' src='js/dragdrop.js'></script>", $nonce);
}


function createUser() {
	genSyslog(__FUNCTION__);
	$nonce = $_SESSION['nonce'];
	$base = dbConnect();
	$req_role = "SELECT id,intitule FROM role WHERE id<>'1'";
	$res_role = mysqli_query($base, $req_role);
	dbDisconnect($base);
	printf("<form method='post' id='user' action='admin.php?action=record_user'>");
	printf("<fieldset><legend>Ajout d'un utilisateur</legend>");
	printf("<table><tr><td colspan='3'>");
	printf("<input type='text' size='20' maxlength='20' name='prenom' id='prenom' placeholder='Prénom de l&apos;utilisateur' autofocus required>");
	printf("<input type='text' size='20' maxlength='20' name='nom' id='nom' placeholder='Nom de l&apos;utilisateur' required>");
	printf("Fonction:&nbsp;<select name='role' id='role' required>");
	printf("<option selected='selected' value=''>&nbsp;</option>");
	while($row=mysqli_fetch_object($res_role)) {
		printf("<option value='%d'>%s</option>", $row->id, $row->intitule);
	}
	printf("</select>");
	printf("</td></tr><tr><td colspan='3'>");
	printf("<input type='text' size='50' maxlength='50' name='login' id='login' placeholder='Identifiant (prenom.nom)' autocomplete='username' required>");
	printf("<input type='password' size='20' maxlength='20' name='passwd' id='passwd' placeholder='Mot de passe' autocomplete='current-password' required>");
	printf("</td></tr></table>");
	chooseEtablissement();
	printf("</fieldset>");
	validForms('Enregistrer', 'admin.php');
	printf("</form>");
	printf("<script nonce='%s'>document.getElementById('user').addEventListener('submit', function(){userFormValidity(event);});</script>", $nonce);
}


function selectUserModif() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = "SELECT * FROM users WHERE role<>'1'";
	$result = mysqli_query($base, $request);
	printf("<form method='post' id='modif_user' action='admin.php?action=modif_user'>");
	printf("<fieldset><legend>Modification d'un utilisaeur</legend>");
	printf("<table><tr><td>");
	printf("Utilisateur:&nbsp;<select name='user' id='user' required>");
	printf("<option selected='selected' value=''>&nbsp;</option>");
	while($row=mysqli_fetch_object($result)) {
		printf("<option value='%s'>%s %s</option>", $row->id, $row->prenom, $row->nom);
	}
	printf("</select>");
	printf("</td></tr></table></fieldset>");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>");
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

	printf("<form method='post' id='user' action='admin.php?action=update_user'>");
	printf("<fieldset><legend>Modification d'un utilisateur</legend>");
	printf("<table><tr><td colspan='3'>");
	printf("Prénom:&nbsp;<input type='text' size='20' maxlength='20' name='prenom' id='prenom' value='%s' autofocus required>", traiteStringFromBDD($record->prenom));
	printf("Nom:&nbsp;<input type='text' size='20' maxlength='20' name='nom' id='nom' value='%s' required>", traiteStringFromBDD($record->nom));
	printf("Fonction:&nbsp;<select name='role' id='role' required>");
	printf("<option selected='selected' value='%d'>%s</option>", intval($record->role), getRole(intval($record->role)));
	while($row=mysqli_fetch_object($res_role)) {
		printf("<option value='%d'>%s</option>", $row->id, $row->intitule);
	}
	printf("</select>");
	printf("</td></tr><tr><td colspan='3'>");
	printf("Identifiant&nbsp;<input type='text' size='50' maxlength='50' name='login' id='login' value='%s' required>", traiteStringFromBDD($record->login));
	printf("</td></tr></table>");
	chooseEtablissement($record);
	printf("</fieldset>");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>");
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
		printf("<form method='post' id='new_etablissement' action='admin.php?action=record_regroup'>");
		printf("<fieldset><legend>Création d'un établissement de regroupement</legend>");
	} else {
		printf("<form method='post' id='new_etablissement' action='admin.php?action=record_etab'>");
		printf("<fieldset><legend>Création d'un établissement</legend>");
	}
	printf("<table><tr><td>");
	printf("<input type='text' size='65' maxlength='65' name='nom' id='nom' placeholder='Nom de l&apos;établissement' autofocus required>");
	printf("<input type='text' size='10' maxlength='10' name='abrege' id='abrege' placeholder='Nom abrégé' required>");
	printf("</td></tr><tr><td>");
	printf("<input type='text' size='80' maxlength='80' name='adresse' id='adresse' placeholder='Adresse' required>");
	printf("</td></tr><tr><td>");
	printf("<input type='text' size='5' maxlength='5' name='cp' id='cp' placeholder='CP' pattern='[0-9]{5}' required>");
	printf("<input type='text' size='20' maxlength='20' name='ville' id='ville' placeholder='Ville' required>");
	printf("</td></tr></table></fieldset>");

	if ($action === 'regroup') {
		$request = "SELECT id,nom,abrege FROM etablissement";
		$result = mysqli_query($base, $request);
		printf("<fieldset><legend>Comprend les établissements suivants</legend>");
		while($row=mysqli_fetch_object($result)) {
			if (stripos($row->abrege, "_TEAM") === false) {
				printf("<input type='checkbox' name='regroup[]' value='%d'>%s<br>", $row->id, $row->nom);
			}
		}
		printf("</fieldset>");
	}
	validForms('Enregistrer', 'admin.php');
	printf("</form>");
	dbDisconnect($base);
}


function selectEtablissementModif() {
	genSyslog(__FUNCTION__);
	$result=getEtablissement();
	printf("<form method='post' id='modif_etab' action='admin.php?action=modif_etab' >");
	printf("<fieldset><legend>Modification d'un établissement</legend>");
	printf("<table><tr><td>");
	printf("Etablissement:&nbsp;<select name='etablissement' id='etablissement' required>");
	printf("<option selected='selected' value=''>&nbsp;</option>");
	while($row=mysqli_fetch_object($result)) {
		if (stripos($row->abrege, "_TEAM") !== false) {
			printf("<option value='%s'>%s</option>", $row->id, $row->nom." (regroupement)");
		} else {
			printf("<option value='%s'>%s</option>", $row->id, $row->nom);
		}
	}
	printf("</select>");
	printf("</td></tr></table></fieldset>");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>");
}


function modifEtablissement() {
	genSyslog(__FUNCTION__);
	$base = dbConnect();
	$request = sprintf("SELECT * FROM etablissement WHERE id='%d' LIMIT 1", $_SESSION['current_etab']);
	$result = mysqli_query($base, $request);
	$record = mysqli_fetch_object($result);

	if (stripos($record->abrege, "_TEAM") === false) {
		printf("<form method='post' id='modif_etablissement' action='admin.php?action=update_etab'>");
	} else {
		printf("<form method='post' id='modif_etablissement' action='admin.php?action=update_regroup'>");
	}
	printf("<fieldset><legend>Modification d'un établissement</legend>");
	printf("<table><tr><td>");
	printf("Nom:&nbsp;<input type='text' size='65' maxlength='65' name='nom' id='nom' value='%s' autofocus required>", traiteStringFromBDD($record->nom));
	printf("</td></tr><tr><td>");
	if (stripos($record->abrege, "_TEAM") === false) {
		printf("Nom abrégé:&nbsp;<input type='text' size='10' maxlength='10' name='abrege' id='abrege' value='%s' required>", traiteStringFromBDD($record->abrege));
	} else {
		printf("Nom abrégé:&nbsp;<input type='text' size='10' maxlength='10' name='abrege' id='abrege' value='%s' readonly='readonly' class='protected'>&nbsp;", traiteStringFromBDD($record->abrege));
	}
	printf("</td></tr><tr><td>");
	printf("Adresse:&nbsp;<input type='text' size='80' maxlength='80' name='adresse' id='adresse' value='%s' required>&nbsp;", traiteStringFromBDD($record->adresse));
	printf("</td></tr><tr><td>");
	printf("Code postal:&nbsp;<input type='text' size='5' maxlength='5' name='cp' id='cp' value='%s' pattern='[0-9]{5}'  required>&nbsp;", $record->code_postal);
	printf("Ville:&nbsp;<input type='text' size='20' maxlength='20' name='ville' id='ville' value='%s' required>&nbsp;", traiteStringFromBDD($record->ville));
	printf("</td></tr></table></fieldset>");

	if (stripos($record->abrege, "_TEAM") !== false) {
		$req_etab = "SELECT id,nom,abrege FROM etablissement";
		$res_etab = mysqli_query($base, $req_etab);
		$team = explode(',', $record->regroupement);
		printf("<fieldset><legend>Comprend les établissements suivants</legend>");
		while($row=mysqli_fetch_object($res_etab)) {
			if (stripos($row->abrege, "_TEAM") === false) {
				if ( array_search($row->id, $team) !== false) {
					printf("<input type='checkbox' name='regroup[]' value='%d' checked='checked'>%s<br>", $row->id, $row->nom);
				} else {
					printf("<input type='checkbox' name='regroup[]' value='%d'>%s<br>", $row->id, $row->nom);
				}
			}
		}
		printf("</fieldset>");
	}
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>");
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
	printf("<form method='post' id='modif_quiz' action='admin.php?action=modif_quiz' >");
	printf("<fieldset><legend>Modification d'un questionnaire</legend>");
	printf("<table><tr><td>");
	printf("Questionnaire:&nbsp;<select name='quiz' id='quiz' required>");
	printf("<option selected='selected' value=''>&nbsp;</option>");
	while($row = mysqli_fetch_object($result)) {
		printf("<option value='%s'>%s</option>", $row->id, $row->nom);
	}
	printf("</select>");
	printf("</td></tr></table></fieldset>");
	validForms('Modifier', 'admin.php', $back=False);
	printf("</form>");
}


function modifications() {
	genSyslog(__FUNCTION__);
	$quiz = getJsonFile();
	printf("<table>");
	printf("<tr><th class='modifquiz'>Domaine</th><th class='modifquiz'>Sous-domaine</th><th>Question</th><th>Poids</th><th>&nbsp;</th></tr>");
	for ($d=0; $d<count($quiz); $d++) {
		$num_dom = $quiz[$d]['numero'];
		$subDom = $quiz[$d]['subdomains'];
		printf("<tr><td>%s %s</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>", $num_dom, $quiz[$d]['libelle']);
		for ($sd=0; $sd<count($subDom); $sd++) {
			$num_sub_dom = $subDom[$sd]['numero'];
			$questions = $subDom[$sd]['questions'];
			printf("<tr><td>&nbsp;</td><td>%s.%s %s</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>", $num_dom, $num_sub_dom, $subDom[$sd]['libelle']);
			for ($q=0; $q<count($questions); $q++) {
				$num_question = $questions[$q]['numero'];
				printf("<tr><td>&nbsp;</td><td>&nbsp;</td><td class='pleft'>%s.%s.%s %s</td><td>%s</td><td>&nbsp;</td></tr>", $num_dom, $num_sub_dom, $num_question, $questions[$q]['libelle'], $questions[$q]['poids']);
			}
		}
	}
	printf("</table>");
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


function bilanByEtab() {
	$base = dbConnect();
	$req_etab = sprintf("SELECT * FROM etablissement ORDER BY nom");
	$res_etab = mysqli_query($base, $req_etab);
	printf("<div class='bilan'>");
	while ($row_etab = mysqli_fetch_object($res_etab)) {
		printf("<table>");
		printf("<tr><th colspan='4'>%s - %s - %s %s </th></tr>", $row_etab->nom, $row_etab->adresse, $row_etab->code_postal, $row_etab->ville);
		printf("<tr>");
		printf("<th class='width25'>&nbsp;</th>");
		printf("<th class='width25'>Prénom</th>");
		printf("<th class='width25'>Nom</th>");
		printf("<th class='width25'>Login</th>");
		printf("</tr>");
		$req_auditor = sprintf("SELECT nom, prenom, login, etablissement FROM users WHERE role='2'");
		$res_auditor = mysqli_query($base, $req_auditor);
		$req_user = sprintf("SELECT role, nom, prenom, login FROM users WHERE etablissement = '%d' ORDER BY role", $row_etab->id);
		$res_user = mysqli_query($base, $req_user);
		$gotDirecteur = False;
		$gotRSSI = False;
		$gotOpeSSI = False;
		if (mysqli_num_rows($res_user)) {
			$users = mysqli_fetch_all($res_user, MYSQLI_ASSOC);
			$roles = array();
			foreach($users as $user) { $roles[] = $user['role']; }
			$roles = array_unique($roles);
			foreach($users as $user) {
				switch ($user['role']) {
					case '3':
						printf("<tr><th>Directeur</th><td>%s</td><td>%s</td><td>%s</td></tr>", $user['prenom'], $user['nom'], $user['login']);
						$gotDirecteur = True;
						break;
					case '4':
						printf("<tr><th>RSSI</th><td>%s</td><td>%s</td><td>%s</td></tr>", $user['prenom'], $user['nom'], $user['login']);
						$gotRSSI = True;
						break;
					case '5':
						printf("<tr><th>Opérateur SSI</th><td>%s</td><td>%s</td><td>%s</td></tr>", $user['prenom'], $user['nom'], $user['login']);
						$gotOpeSSI = True;
						break;
				}
			}
		}
		if (mysqli_num_rows($res_auditor)) {
			$gotAuditor = False;
			foreach (mysqli_fetch_all($res_auditor, MYSQLI_ASSOC) as $auditor) {
				if (in_array($row_etab->id, explode(',', $auditor['etablissement']))) {
					printf("<tr><th>Auditeur</th><td>%s</td><td>%s</td><td>%s</td></tr>", $auditor['prenom'], $auditor['nom'], $auditor['login']);
					$gotAuditor = True;
				}
			}
		}
		if (!$gotDirecteur or !$gotRSSI or !$gotOpeSSI or !$gotAuditor) {
			$missing = "";
			if (!$gotDirecteur) { $missing .= "Directeur, "; }
			if (!$gotRSSI) { $missing .= "RSSI, "; }
			if (!$gotOpeSSI) { $missing .= "Opérateur, "; }
			if (!$gotAuditor) { $missing .= "Auditeur, "; }
			$missing = rtrim($missing, ", ").".";
			printf("<tr><th>Problème</th><td colspan='3' class='notok'>%s</td></tr>", $missing);
		}
		printf("</table><br>");
	}
	printf("</div>");
	dbDisconnect($base);
}


?>
