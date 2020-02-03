<?php

//header('Content-Type: application/json');

include("functions.php");

function getDomains() {
	$base = dbConnect();
	$request = sprintf("SELECT id, libelle, abrege FROM paragraphe");
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	return $result;
}


function getSubDomains($id_domain) {
	$base = dbConnect();
	$request = sprintf("SELECT id, numero, libelle, comment FROM sub_paragraphe WHERE id_paragraphe='%d'", $id_domain);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	return $result;
}


function getQuestion($id_sub_domain) {
	$base = dbConnect();
	$request = sprintf("SELECT numero, libelle, mesure, poids FROM question WHERE id_sub_paragraphe='%d'", $id_sub_domain);
	$result = mysqli_query($base, $request);
	dbDisconnect($base);
	return $result;
}


function readFromBDD() {
	$BDDdomains = getDomains();
	$quiz = array();
	while($rowDom = mysqli_fetch_object($BDDdomains)) {
		$BDDSubDomains = getSubDomains($rowDom->id);
		$tempSubDom = array();
		while($rowSubDom = mysqli_fetch_object($BDDSubDomains)) {
			$BDDQuestion = getQuestion($rowSubDom->id);
			$tempQuestion = array();
			while($rowQuestion = mysqli_fetch_object($BDDQuestion)) {
				$tempQuestion[] = array('numero'=>intval($rowQuestion->numero), 'libelle'=>$rowQuestion->libelle, 'mesure'=>$rowQuestion->mesure, 'poids'=>intval($rowQuestion->poids), 'level'=>1);
			}
			$tempSubDom[] = array('numero'=>intval($rowSubDom->numero), 'libelle'=>$rowSubDom->libelle, 'comment'=>$rowSubDom->comment, 'questions'=>$tempQuestion);
		}
		$quiz[] = array('numero'=>intval($rowDom->id), 'libelle'=>$rowDom->libelle,  'abrege'=>$rowDom->abrege, 'subdomains'=>$tempSubDom);
	}
	$fp = fopen('quiz_from_bdd.json', 'w');
	fwrite($fp, json_encode($quiz));
	fclose($fp);
	return json_encode($quiz);
}


function readFromFile() {
	global $cheminDATA;
	//$jsonFile = sprintf("%squiz_iso_27002.json", $cheminDATA);
	$jsonFile = sprintf("%squiz_hygiene_rules.json", $cheminDATA);
	$jsonSource = file_get_contents($jsonFile);
	$jsonQuiz = json_decode($jsonSource, true);
	for ($d=0; $d<count($jsonQuiz); $d++) {
		printf("<p>%s</p>\n", $jsonQuiz[$d]['libelle']);
		$subDom = $jsonQuiz[$d]['subdomains'];
		for ($sd=0; $sd<count($subDom); $sd++) {
			printf("<p>%s</p>\n", $subDom[$sd]['libelle']);
		}
	}
}



//echo readFromBDD();
echo readFromFile();
?>
