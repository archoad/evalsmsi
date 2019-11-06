<?php
/*=========================================================
// File:        modi.php
// Description: intermediate page for administrator of EvalSMSI
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
include("funct_admin.php");
session_start();
$authorizedRole = array('1');
isSessionValid($authorizedRole);
headPage($appli_titre, "Administration");

switch ($_GET['action']) {
	case 'record_modif_par':
		recordParagraph($_POST, 'modif');
		break;

	case 'record_modif_sub_par':
		recordSubParagraph($_POST, 'modif');
		break;

	case 'record_modif_question':
		recordQuestion($_POST, 'modif');
		break;

	case 'modif_par':
		modifParagraphs($_GET['value']);
		break;

	case 'modif_sub_par':
		modifSubParagraphs($_GET['value']);
		break;

	case 'modif_question':
		modifQuestion($_GET['value']);
		break;

	default:
		break;
}

footPage();
?>

<script type='text/javascript' src='js/evalsmsi.js'></script>
