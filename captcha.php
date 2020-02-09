<?php
/*=========================================================
// File:        captcha.php
// Description: captcha page of EvalSMSI
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
if(isset($_SESSION['sess_captcha'])) {
	unset($_SESSION['sess_captcha']);
}


$imgWidth = 85;
$imgHeight = 25;


function generateLines($image, $nbr) {
	global $imgWidth, $imgHeight;
	genSyslog(__FUNCTION__);
	for($i=0; $i<=$nbr; $i++) {
		$lineColor = imagecolorallocate($image, rand(0,255), rand(0,255), rand(0,255));
		imageline($image, rand(1, $imgWidth-$imgHeight), rand(1, $imgHeight), rand(1, $imgWidth+$imgHeight), rand(1, $imgHeight), $lineColor);
	}
}


function txtCaptcha($image) {
	genSyslog(__FUNCTION__);
	$captchaString = "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";
	$captchaString = str_shuffle($captchaString);
	$_SESSION['sess_captcha'] = substr($captchaString, 0, 6);
	$textColor = imagecolorallocate($image, 40, 45, 50);
	imagestring($image, 5, 10, 4, $_SESSION['sess_captcha'], $textColor);
}


function numCaptcha($image) {
	genSyslog(__FUNCTION__);
	$captchaNumber = ["un", "deux", "trois", "quatre", "cinq"];
	$val1 = rand(1, 5);
	$val2 = rand(1, 5);
	$_SESSION['sess_captcha'] = $val1 * $val2;
	$captchaString = $captchaNumber[$val1-1].'*'.$captchaNumber[$val2-1];
	$textColor = imagecolorallocate($image, 40, 45, 50);
	imagestring($image, 3, 0, 4, $captchaString, $textColor);
}


header('Content-Type: image/png');
$img = imagecreatetruecolor($imgWidth, $imgHeight);
$bg = imagecolorallocate($img, 0, 0, 0);
imagecolortransparent($img, $bg);
generateLines($img, 5);

switch ($_SESSION['captchaMode']) {
	case 'txt':
		txtCaptcha($img);
		break;
	case 'num':
		numCaptcha($img);
		break;
	default:
		numCaptcha($img);
		break;
}

imagepng($img);
imagedestroy($img);

?>




















?>
