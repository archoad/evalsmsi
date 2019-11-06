<?php
/*=========================================================
// File:        ajax.php
// Description: AJAX exchange for EvalSMSI
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
$authorizedRole = array('1', '2', '3', '4');
isSessionValid($authorizedRole);
header("Content-type: text/html; charset=utf-8");

$id = $_GET['query'];
$action = $_GET['action'];

$base = evalsmsiConnect();
switch ($action) {
	case 'sub_par':
		$request = sprintf("SELECT MAX(numero) FROM sub_paragraphe WHERE id_paragraphe='%d'", intval($id));
		break;
	case 'question':
		$request = sprintf("SELECT * FROM sub_paragraphe WHERE id_paragraphe='%d'", intval($id));
		break;
	case 'num_quest':
		$request = sprintf("SELECT MAX(numero) FROM question WHERE id_sub_paragraphe='%d'", intval($id));
		break;
	default:
		break;
}
$result=mysqli_query($base, $request);
evalsmsiDisconnect($base);


switch ($action) {
	case 'sub_par':
		$row=mysqli_fetch_array($result);
		$return = ($row[0] + 1);
		break;
	case 'question':
		$return = "<option value=''>&nbsp;</option>\n";
		while ($row=mysqli_fetch_object($result)) {
			$return .= sprintf("<option value='%s'>%s</option>\n", $row->id, $row->numero." - ".$row->libelle);
		}
		break;
	case 'num_quest':
		$row=mysqli_fetch_array($result);
		$return = ($row[0] + 1);
		break;
	default:
		break;
}


echo $return;
?>
