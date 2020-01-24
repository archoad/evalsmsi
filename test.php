<?php



function generateToken() {
	$token = hash('sha3-256', random_bytes(32));
	return $token;
}


echo generateToken();
echo "\n";
echo generateToken();
echo "\n";
echo generateToken();
echo "\n";

?>
