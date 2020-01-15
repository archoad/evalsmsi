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


session_start();
if(isset($_SESSION['sess_captcha'])) {
	unset($_SESSION['sess_captcha']);
}

header('Content-Type: image/png');

$imgWidth = 80;
$imgHeight = 25;
$nbrLines = 5;

$img = imagecreatetruecolor($imgWidth, $imgHeight);
$bg = imagecolorallocate($img, 0, 0, 0);
imagecolortransparent($img, $bg);

for($i=0; $i<=$nbrLines; $i++) {
	$lineColor = imagecolorallocate($img, rand(0,255), rand(0,255), rand(0,255));
	imageline($img, rand(1, $imgWidth-$imgHeight), rand(1, $imgHeight), rand(1, $imgWidth+$imgHeight), rand(1, $imgHeight), $lineColor);
}

$captchaString = "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";
$captchaString = str_shuffle($captchaString);
$_SESSION['sess_captcha'] = substr($captchaString, 0, 6);
$textColor = imagecolorallocate($img, 40, 45, 50);
imagestring($img, 5, 10, 4, $_SESSION['sess_captcha'], $textColor);

imagepng($img);
imagedestroy($img);

?>




















?>
