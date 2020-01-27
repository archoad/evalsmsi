<?php
include("functions.php");
session_start();
$authorizedRole = array('3', '4');
isSessionValid($authorizedRole);
headPage($appli_titre);






footPage();
?>
