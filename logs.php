<?php
/*=========================================================
// File:        logs.php
// Description: timeline with vis.js page of EvalSMSI
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
session_start();
$authorizedRole = array('2');
isSessionValid($authorizedRole);
header('Content-Type: application/json');


function processAction($action) {
	$temp = array();
	foreach ($action as $e => $r) {
		if (preg_match("/question/", $e)) {
			if ($r<>0) {
				$temp[$e] = textItem($r);
			}
		} else {
			if (!empty($r)) {
				$temp[$e] = traiteStringFromBDD($r);
			}
		}
	}
	return $temp;
}


$base = dbConnect();
$request = sprintf("SELECT * FROM journal WHERE (YEAR(timestamp)='%d' AND etablissement='%d' AND quiz='%d')", $_SESSION['annee'], $_SESSION['id_etab'], $_SESSION['quiz']);
$result = mysqli_query($base, $request);
$rawdata = [];
$cpt = 0;
$temp = [];
while($row = mysqli_fetch_object($result)) {
	$currDate = explode(' ', $row->timestamp)[0];
	$action = unserialize($row->action);
	$detailActions = processAction($action);
	$nbrActions = count($action);
	if (!isset($temp[$currDate])) {
		$temp[$currDate]['id'] = $cpt;
		$temp[$currDate]['start'] = $currDate;
		$temp[$currDate]['content'] = $nbrActions;
		$temp[$currDate]['title'] = sprintf('<span style="font-size: 8pt;"><b>Utilisateur</b>: %s<br/><b>OS</b>: %s<br /><b>Navigateur</b>: %s<br /><b>Adresse IP</b>: %s<br /></span>', $row->user, $row->os, $row->navigateur, $row->ip);
		$temp[$currDate]['actions'] = $detailActions;
		$cpt ++;
	} else {
		$temp[$currDate]['content'] += $nbrActions;
		$temp[$currDate]['actions'] = array_merge($temp[$currDate]['actions'], $detailActions);
	}
}
dbDisconnect($base);

foreach ($temp as $key => $value) {
	$value['content'] = strval($value['content']);
	$nbrActions = $value['content'];
	if ($nbrActions < 20) {
		$value['style'] = "border-color: var(--myGreenDark); background-color: var(--myGreenLight);";
	} else if ($nbrActions < 50) {
		$value['style'] = "border-color: var(--myOrangeDark); background-color: var(--myOrangeLight);";
	} else {
		$value['style'] = "border-color: var(--myRedDark); background-color: var(--myRedLight);";
	}
	$rawdata[] = $value;
}







echo json_encode($rawdata);

?>
