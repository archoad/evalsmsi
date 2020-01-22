<?php

$strTest = "</textarea><script>alert('xss')</script>";


function traiteStringToBDD($str) {
	$str = trim($str);
	if (!get_magic_quotes_gpc()) { $str = addslashes($str); }
	return htmlentities($str, ENT_QUOTES, 'UTF-8');
	//return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}


function antixss($str) {
	$temp = '';
	$temp = strip_tags($str);
	$temp = stripslashes($temp);
	$temp = htmlspecialchars($temp, ENT_QUOTES, 'UTF-8');
	return $temp;
}


function jsEscape($str) {
	$output = '';
	$str = str_split($str);
	for($i=0; $i<count($str); $i++) {
		$chrNum = ord($str[$i]);
		$chr = $str[$i];
		if($chrNum === 226) {
			if(isset($str[$i+1]) && ord($str[$i+1]) === 128) {
				if(isset($str[$i+2]) && ord($str[$i+2]) === 168) {
					$output .= '\u2028';
					$i += 2;
					continue;
				}
				if(isset($str[$i+2]) && ord($str[$i+2]) === 169) {
					$output .= '\u2029';
					$i += 2;
					continue;
				}
			}
		}
		switch($chr) {
			case "'":
			case '"':
			case "\n":
			case "\r":
			case "&":
			case "\\":
			case "<":
			case ">":
				$output .= sprintf("\\u%04x", $chrNum);
				break;
			default:
				$output .= $str[$i];
				break;
		}
	}
	return $output;
}


echo traiteStringToBDD($strTest);
echo "\n\n";
echo antixss($strTest);
echo "\n\n";
echo jsEscape($strTest);
?>
