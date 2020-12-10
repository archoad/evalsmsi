<?php
/*=========================================================
// File:		mfa.php
// Description: multi factor authentication process of EvalSMSI
// Created:	 2009-01-01
// Licence:	 GPL-3.0-or-later
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
include("data/cbor/ByteBuffer.php");
include("data/cbor/CborDecoder.php");
startSession();
$authorizedRole = array('1', '2', '3', '4', '5', '100');
isSessionValid($authorizedRole);


//https://www.iana.org/assignments/cose/cose.xhtml#algorithms
$coseAlgoECDSAwSHA256 = -7;


function checkOrigin($origin) {
	$success = true;
	$rpId = $_SERVER['SERVER_NAME'];
	$scheme = parse_url($origin, PHP_URL_SCHEME);
	$host = parse_url($origin, PHP_URL_HOST);
	if (($rpId !== 'localhost') &&  ($scheme !== 'https')) {
		$success = false;
	}
	if ($rpId !== $host) {
		$success = false;
	}
	return $success;
}



function readFlags($binFlag) {
	$flags = array();
	$flags['userPresent'] = !!($binFlag & 1);
	$flags['bit_1'] = !!($binFlag & 2);
	$flags['userVerified'] = !!($binFlag & 4);
	$flags['bit_3'] = !!($binFlag & 8);
	$flags['bit_4'] = !!($binFlag & 16);
	$flags['bit_5'] = !!($binFlag & 32);
	$flags['attestedDataIncluded'] = !!($binFlag & 64); // bit 6 - AT
	$flags['extensionDataIncluded'] = !!($binFlag & 128); // bit 7 - ED
	return $flags;
}


function readCredentialPublicKey($binary, $offset, &$endOffset) {
	$_COSE_KTY = 1;
	$_COSE_ALG = 3;
	$_COSE_CRV = -1;
	$_COSE_X = -2;
	$_COSE_Y = -3;
	$enc = WebAuthn\CBOR\CborDecoder::decodeInPlace($binary, $offset, $endOffset);
	$credPKey = array();
	$credPKey['kty'] = $enc[$_COSE_KTY];
	$credPKey['alg'] = $enc[$_COSE_ALG];
	$credPKey['crv'] = $enc[$_COSE_CRV];
	$credPKey['x'] = $enc[$_COSE_X]->getBinaryString();
	$credPKey['y'] = $enc[$_COSE_Y]->getBinaryString();
	unset($enc);
	return $credPKey;
}


function readAttestData($binary, &$endOffset) {
	$attestationData = array();
	$attestationData['aaguid'] = substr($binary, 37, 16);
	$length = unpack('nlength', substr($binary, 53, 2))['length'];
	$attestationData['credentialId'] = substr($binary, 55, $length);
	$endOffset = 55 + $length;
	$attestationData['length'] = $length;
	return $attestationData;
}


function readExtensionData($binary) {
	$ext = WebAuthn\CBOR\CborDecoder::decode($binary);
	return $ext;
}


function DER_length($len) {
	if ($len < 128) {
		return chr($len);
	}
	$lenBytes = '';
	while ($len > 0) {
		$lenBytes = chr($len % 256) . $lenBytes;
		$len = intdiv($len, 256);
	}
	return chr(0x80 | strlen($lenBytes)) . $lenBytes;
}


function DER_bitString($bytes) {
	return "\x03" . DER_length(strlen($bytes) + 1) . "\x00" . $bytes;
}


function DER_oid($bytes) {
	return "\x06" . DER_length(strlen($bytes)) . $bytes;
}


function DER_sequence($bytes) {
	return "\x30" . DER_length(strlen($bytes)) . $bytes;
}


function getPublicKeyPem($credPKey) {
	$u2f = "\x04".$credPKey['x'].$credPKey['y'];
	$der = DER_sequence(
		DER_sequence(
			DER_oid("\x2A\x86\x48\xCE\x3D\x02\x01") . // OID 1.2.840.10045.2.1 ecPublicKey
			DER_oid("\x2A\x86\x48\xCE\x3D\x03\x01\x07")  // 1.2.840.10045.3.1.7 prime256v1
		). DER_bitString($u2f)
	);
	$pem = '-----BEGIN PUBLIC KEY-----'."\n";
	$pem .= chunk_split(base64_encode($der), 64, "\n");
	$pem .= '-----END PUBLIC KEY-----'."\n";
	return $pem;
}


function getCertificatePem($publicKey) {
	$pem = '-----BEGIN CERTIFICATE-----' . "\n";
	$pem .= chunk_split($publicKey, 64, "\n");
	$pem .= '-----END CERTIFICATE-----' . "\n";
	return $pem;
}


function generatePKCCOregistration() {
	global $coseAlgoECDSAwSHA256, $attestationMode;
	if (isset($_SESSION['challenge'])) { unset($_SESSION['challenge']); }
	$authenticatorSelection = [
		'authenticatorAttachment' => 'cross-platform',
		'requireResidentKey' => false,
		'userVerification' => 'discouraged'
	];
	$pubKeyCredParams = [
		['type' => 'public-key', 'alg' => $coseAlgoECDSAwSHA256],
	];
	$rp = [
		'name' => 'Archoad WebAuthn',
		'id' => $_SERVER['SERVER_NAME']
	];
	$user = [
		'displayName' => $_SESSION['login'],
		'name' => $_SESSION['prenom']." ".$_SESSION['nom'],
		'id' => base64_encode(random_bytes(16)),
	];
	$extensions = [
		'txAuthSimple' => "Authentification EvalSMSI",
		'loc' => true,
		'uvi'=> true,
	];
	$result = array();
	$result['rp'] = $rp;
	$result['challenge'] = base64_encode(random_bytes(32));
	$result['user'] = $user;
	$result['pubKeyCredParams'] = $pubKeyCredParams;
	$result['timeout'] = 60000;
	$result['attestation'] = $attestationMode;
	$result['extensions'] = $extensions;
	$result['authenticatorSelection'] = $authenticatorSelection;
	$result['excludeCredentials'] = [];
	$_SESSION['challenge'] = $result['challenge'];
	return json_encode(array('publicKey' => $result));
}


function generatePKCCOauthentication() {
	if (isset($_SESSION['challenge'])) { unset($_SESSION['challenge']); }
	$allowCredentials = [
		'type' => 'public-key',
		'id' => $_SESSION['registration']['credentialId'],
		'transports' => ['usb', 'ble', 'nfc'],
	];
	$result = array();
	$result['challenge'] = base64_encode(random_bytes(32));
	$result['allowCredentials'] = [$allowCredentials];
	$result['timeout'] = 60000;
	$result['rpId'] = $_SERVER['SERVER_NAME'];
	$result['userVerification'] = 'discouraged';
	$_SESSION['challenge'] = $result['challenge'];
	return json_encode(array('publicKey' => $result));
}


function verifyCredPKey($credPKey) {
	$_EC2_TYPE = 2;
	$_EC2_ES256 = -7;
	$_EC2_P256 = 1;
	$success = true;
	if ($credPKey['kty'] !== $_EC2_TYPE) {
		genSyslog(__FUNCTION__, $msg='public key not in EC2 format');
		$success = false;
	}
	if ($credPKey['alg'] !== $_EC2_ES256) {
		genSyslog(__FUNCTION__, $msg='signature algorithm not ES256');
		$success = false;
	}
	if ($credPKey['crv'] !== $_EC2_P256) {
		genSyslog(__FUNCTION__, $msg='curve not P-256');
		$success = false;
	}
	if (strlen($credPKey['x']) !== 32) {
		genSyslog(__FUNCTION__, $msg='invalid x-coordinate');
		$success = false;
	}
	if (strlen($credPKey['y']) !== 32) {
		genSyslog(__FUNCTION__, $msg='invalid y-coordinate');
		$success = false;
	}
	return $success;
}


function verifyAttestationData($attestationObject) {
	global $coseAlgoECDSAwSHA256;
	$success = true;
	$offset = 37;

	$attStmt = $attestationObject['attStmt'];
	$signature = base64_encode($attStmt['sig']->getBinaryString());
	$x5c = array();
	for ($i=0; $i<count($attStmt['x5c']); $i++) {
		$temp = $attStmt['x5c'][$i]->getBinaryString();
		$x5c[] = base64_encode($temp);
	}
	$certificate = getCertificatePem($x5c[0]);
	if (openssl_pkey_get_public($certificate) === false) {
		genSyslog(__FUNCTION__, $msg='invalid public key');
		$success = false;
	}
	if (array_key_exists('alg', $attStmt) && $attStmt['alg'] !== $coseAlgoECDSAwSHA256) {
		genSyslog(__FUNCTION__, $msg='only SHA256 acceptable');
		$success = false;
	}
	if (!array_key_exists('sig', $attStmt) || !is_object($attStmt['sig'])) {
		genSyslog(__FUNCTION__, $msg='no signature found');
		$success = false;
	}
	if (!array_key_exists('x5c', $attStmt) || !is_array($attStmt['x5c'])) {
		genSyslog(__FUNCTION__, $msg='invalid x5c certificate');
		$success = false;
	}
	if (($attestationObject['fmt'] === 'packed') && (count($attStmt['x5c']) < 1)) {
		genSyslog(__FUNCTION__, $msg='invalid x5c length (packed)');
		$success = false;
	}
	for ($i=0; $i<count($attStmt['x5c']); $i++) {
		if (!is_object($attStmt['x5c'][$i])) {
			genSyslog(__FUNCTION__, $msg='invalid x5c certificate '.$i);
			$success = false;
		}
	}
	$binAuthData = $attestationObject['authData']->getBinaryString();
	$rpIdHash = base64_encode(substr($binAuthData, 0, 32));
	$flags = unpack('Cflags', substr($binAuthData, 32, 1))['flags'];
	$flags = readFlags($flags);
	$signCount = unpack('Nsigncount', substr($binAuthData, 33, 4))['signcount'];
	if ($flags['attestedDataIncluded']) {
		if (strlen($binAuthData) <= 55) {
			genSyslog(__FUNCTION__, $msg='attested data should be present but is missing');
			$success = false;
		}
		$attestationData = readAttestData($binAuthData, $offset);
		$credPKey = readCredentialPublicKey($binAuthData, 55+$attestationData['length'], $offset);
		$success = verifyCredPKey($credPKey);
	}
	if ($flags['extensionDataIncluded']) {
		$ext = readExtensionData(substr($binAuthData, $offset));
		if (!is_array($ext)) {
			genSyslog(__FUNCTION__, $msg='invalid extension data');
			$success = false;
		}
	}
	$aaguid = base64_encode($attestationData['aaguid']);
	$credentialId = base64_encode($attestationData['credentialId']);

	$data = array();
	$data['success'] = $success;
	$data['attStmt']['alg'] = $coseAlgoECDSAwSHA256;
	$data['attStmt']['sig'] = $signature;
	$data['attStmt']['x5c'] = $x5c;
	$data['authData']['credentialData']['aaguid'] = $aaguid;
	$data['authData']['credentialData']['credentialId'] = $credentialId;
	$data['authData']['credentialData']['publicKey']['kty'] = $credPKey['kty'];
	$data['authData']['credentialData']['publicKey']['alg'] = $credPKey['alg'];
	$data['authData']['credentialData']['publicKey']['crv'] = $credPKey['crv'];
	$data['authData']['credentialData']['publicKey']['x'] = base64_encode($credPKey['x']);
	$data['authData']['credentialData']['publicKey']['y'] = base64_encode($credPKey['y']);
	$data['authData']['flags']['ED'] = $flags['extensionDataIncluded'];
	$data['authData']['flags']['AT'] = $flags['attestedDataIncluded'];
	$data['authData']['flags']['UV'] = $flags['userVerified'];
	$data['authData']['flags']['UP'] = $flags['userPresent'];
	$data['authData']['rpIdHash'] = $rpIdHash;
	$data['authData']['signatureCounter'] = $signCount;
	$data['fmt'] = $attestationObject['fmt'];
	$data['certificate'] = $certificate;
	if ($x509 = openssl_x509_read($certificate)) {
		$result = openssl_x509_parse($x509);
		$temp = array();
		$temp['issuer'] = trim($result['issuer']['CN']);
		$temp['subject'] = trim($result['subject']['CN']);
		$temp['signatureTypeSN'] = trim($result['signatureTypeSN']);
		$temp['signatureTypeLN'] = trim($result['signatureTypeLN']);
		$temp['signatureTypeNID'] = trim($result['signatureTypeNID']);
		$data['x509'] = $temp;
	}
	$data['credentialPublicKeyPEM'] = getPublicKeyPem($credPKey);
	$data['binAuthData'] = base64_encode($binAuthData);
	return $data;
}


function verifyAttestationDataNone($attestationObject) {
	$success = true;
	$offset = 37;
	$binAuthData = $attestationObject['authData']->getBinaryString();
	$rpIdHash = base64_encode(substr($binAuthData, 0, 32));
	$flags = unpack('Cflags', substr($binAuthData, 32, 1))['flags'];
	$flags = readFlags($flags);
	if ($flags['attestedDataIncluded']) {
		if (strlen($binAuthData) <= 55) {
			genSyslog(__FUNCTION__, $msg='attested data should be present but is missing');
			$success = false;
		}
		$attestationData = readAttestData($binAuthData, $offset);
		$credPKey = readCredentialPublicKey($binAuthData, 55+$attestationData['length'], $offset);
		$success = verifyCredPKey($credPKey);
	}
	if ($flags['extensionDataIncluded']) {
		$ext = readExtensionData(substr($binAuthData, $offset));
		if (!is_array($ext)) {
			genSyslog(__FUNCTION__, $msg='invalid extension data');
			$success = false;
		}
	}
	$signCount = unpack('Nsigncount', substr($binAuthData, 33, 4))['signcount'];
	$aaguid = base64_encode($attestationData['aaguid']);
	$credentialId = base64_encode($attestationData['credentialId']);

	$data = array();
	$data['success'] = $success;
	$data['authData']['credentialData']['aaguid'] = $aaguid;
	$data['authData']['credentialData']['credentialId'] = $credentialId;
	$data['authData']['credentialData']['publicKey']['kty'] = $credPKey['kty'];
	$data['authData']['credentialData']['publicKey']['alg'] = $credPKey['alg'];
	$data['authData']['credentialData']['publicKey']['crv'] = $credPKey['crv'];
	$data['authData']['credentialData']['publicKey']['x'] = base64_encode($credPKey['x']);
	$data['authData']['credentialData']['publicKey']['y'] = base64_encode($credPKey['y']);
	$data['authData']['flags']['ED'] = $flags['extensionDataIncluded'];
	$data['authData']['flags']['AT'] = $flags['attestedDataIncluded'];
	$data['authData']['flags']['UV'] = $flags['userVerified'];
	$data['authData']['flags']['UP'] = $flags['userPresent'];
	$data['authData']['rpIdHash'] = $rpIdHash;
	$data['authData']['signatureCounter'] = $signCount;
	$data['fmt'] = $attestationObject['fmt'];
	$data['credentialPublicKeyPEM'] = getPublicKeyPem($credPKey);
	$data['binAuthData'] = base64_encode($binAuthData);
	return $data;
}


function validateAttestation($clientDataHash, $attestationData) {
	$fmt = $attestationData['fmt'];
	if ($fmt === 'packed') {
		$publicKey = openssl_pkey_get_public($attestationData['certificate']);
		$signature = base64_decode($attestationData['attStmt']['sig']);
		$dataToVerify = base64_decode($attestationData['binAuthData']);
		$dataToVerify .= $clientDataHash;
	} else {
		$publicKey = openssl_pkey_get_public($attestationData['certificate']);
		$x = base64_decode($attestationData['authData']['credentialData']['publicKey']['x']);
		$y = base64_decode($attestationData['authData']['credentialData']['publicKey']['y']);
		$signature = base64_decode($attestationData['attStmt']['sig']);
		$dataToVerify = "\x00";
		$dataToVerify .= base64_decode($attestationData['authData']['rpIdHash']);
		$dataToVerify .= $clientDataHash;
		$dataToVerify .= base64_decode($attestationData['authData']['credentialData']['credentialId']);
		$dataToVerify .= "\x04".$x.$y;
	}
	return openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);
}


function verifyRegistration($clientDataJSON, $attestationObject) {
	// source: https://w3c.github.io/webauthn/#sctn-registering-a-new-credential
	$success = true;
	$data = array();
	$clientDataJSON = base64_decode($clientDataJSON);
	$attestationObject = base64_decode($attestationObject);
	$rpId = $_SERVER['SERVER_NAME'];
	$rpIdHash = hash('sha256', $rpId, true);
	$clientDataHash = hash('sha256', $clientDataJSON, true);
	$clientData = json_decode($clientDataJSON);
	if (!is_object($clientData)) {
		genSyslog(__FUNCTION__, $msg='invalid client data');
		$success = false;
	}
	// Verify that the value of C.type is webauthn.create.
	if (!property_exists($clientData, 'type') || $clientData->type !== 'webauthn.create') {
		genSyslog(__FUNCTION__, $msg='invalid type');
		$success = false;
	}
	//Verify that the value of C.challenge equals the base64url encoding of options.challenge.
	if (!property_exists($clientData, 'challenge') || (base64_encode(base64UrlDecode($clientData->challenge)) !== $_SESSION['challenge'])) {
		genSyslog(__FUNCTION__, $msg='invalid challenge');
		$success = false;
	}
	//Verify that the value of C.origin matches the Relying Party's origin.
	if (!property_exists($clientData, 'origin') || !checkOrigin($clientData->origin)) {
		genSyslog(__FUNCTION__, $msg='invalid origin');
		$success = false;
	}

	$decodedData = readExtensionData($attestationObject);
	//Verify the attestation statement format fmt
	if (!is_array($decodedData) || !array_key_exists('fmt', $decodedData) || !is_string($decodedData['fmt'])) {
		genSyslog(__FUNCTION__, $msg='invalid attestation format');
		$success = false;
	}
	$fmt = $decodedData['fmt'];
	$allowedFormats = array('fido-u2f', 'packed', 'none');
	if (!in_array($fmt, $allowedFormats)) {
		genSyslog(__FUNCTION__, $msg='format not supported');
		$success = false;
	}
	//Verify the attestation statement format authData
	if (!array_key_exists('authData', $decodedData) || !is_object($decodedData['authData'])) {
		genSyslog(__FUNCTION__, $msg='invalid attestation format (authData not available)');
		$success = false;
	}
	//Verify the attestation statement format attStmt
	if (!array_key_exists('attStmt', $decodedData) || !is_array($decodedData['attStmt'])) {
		genSyslog(__FUNCTION__, $msg='invalid attestation format (attStmt not available)');
		$success = false;
	}
	if ($fmt !== 'none') {
		$attestationData = verifyAttestationData($decodedData);
		$success = $attestationData['success'];
	} else {
		$attestationData = verifyAttestationDataNone($decodedData);
		$success = $attestationData['success'];
	}
	//Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
	if (base64_decode($attestationData['authData']['rpIdHash']) !== $rpIdHash) {
		genSyslog(__FUNCTION__, $msg='invalid rpID hash');
		$success = false;
	}
	// Verify that the User Present bit of the flags in authData is set.
	if (!$attestationData['authData']['flags']['UP']) {
		genSyslog(__FUNCTION__, $msg='user not present during authentication');
		$success = false;
	}
	if ($fmt !== 'none') {
		if (validateAttestation($clientDataHash, $attestationData) !== 1) {
			genSyslog(__FUNCTION__, $msg='invalid certificate signature: '.openssl_error_string());
			$success = false;
		}
	}
	$data['credentialId'] = $attestationData['authData']['credentialData']['credentialId'];
	$data['credentialPublicKeyPEM'] = $attestationData['credentialPublicKeyPEM'];
	$data['signCount'] = $attestationData['authData']['signatureCounter'];
	$data['rpIdHash'] = $attestationData['authData']['rpIdHash'];
	$data['aaguid'] = $attestationData['authData']['credentialData']['aaguid'];
	$data['publicKey']['x'] = $attestationData['authData']['credentialData']['publicKey']['x'];
	$data['publicKey']['y'] = $attestationData['authData']['credentialData']['publicKey']['y'];
	$data['fmt'] = $attestationData['fmt'];
	//file_put_contents('attestation_data.json', json_encode($attestationData));
	//file_put_contents('create_credential.json', json_encode($data));
	return array($success, $data);
}


function registerNewCredential($post) {
	$post = json_decode($post, true);
	$attestationObject = $post['response']['attestationObject'];
	$clientDataJSON = $post['response']['clientDataJSON'];
	$result = verifyRegistration($clientDataJSON, $attestationObject);
	$return = array();
	if ($result[0]) {
		$base = dbConnect();
		$request = sprintf("UPDATE users SET credential_id='%s', public_key='%s', sign_count='%d' WHERE login='%s'", $result[1]['credentialId'], $result[1]['credentialPublicKeyPEM'], $result[1]['signCount'], $_SESSION['login']);
		$query = mysqli_query($base, $request);
		dbDisconnect($base);
		if ($query) {
			$return['success'] = true;
			$return['msg'] = 'Successfully created credential';
			$return['credentials'] = $result[1];
		} else {
			$return['success'] = false;
			$return['msg'] = 'No credential created';
		}
	} else {
		$return['success'] = false;
		$return['msg'] = 'No credential created';
	}
	return json_encode($return);
}


function verifyAssertion($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey) {
	$success = true;
	$clientDataJSON = base64_decode($clientDataJSON);
	$authenticatorData = base64_decode($authenticatorData);
	$signature = base64_decode($signature);
	$rpId = $_SERVER['SERVER_NAME'];
	$rpIdHash = hash('sha256', $rpId, true);
	$clientDataHash = hash('sha256', $clientDataJSON, true);
	$clientData = json_decode($clientDataJSON);
	// Let JSONtext be the result of running UTF-8 decode on the value of cData.
	if (!is_object($clientData)) {
		genSyslog(__FUNCTION__, $msg='invalid client data');
		$success = false;
	}
	// Verify that the value of C.type is the string webauthn.get.
	if (!property_exists($clientData, 'type') || $clientData->type !== 'webauthn.get') {
		genSyslog(__FUNCTION__, $msg='invalid type');
		$success = false;
	}
	// Verify that the value of C.challenge matches the challenge that was sent to the authenticator in the PublicKeyCredentialRequestOptions passed to the get() call.
	if (!property_exists($clientData, 'challenge') || (base64_encode(base64UrlDecode($clientData->challenge)) !== $_SESSION['challenge'])) {
		genSyslog(__FUNCTION__, $msg='invalid challenge');
		$success = false;
	}
	//Verify that the value of C.origin matches the Relying Party's origin.
	if (!property_exists($clientData, 'origin') || !checkOrigin($clientData->origin)) {
		genSyslog(__FUNCTION__, $msg='invalid origin');
		$success = false;
	}
	// Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
	if (substr($authenticatorData, 0, 32) !== $rpIdHash) {
		genSyslog(__FUNCTION__, $msg='invalid rpID hash');
		$success = false;
	}
	// Verify that the User Present bit of the flags in authData is set.
	$flags = unpack('Cflags', substr($authenticatorData, 32, 1))['flags'];
	$flags = readFlags($flags);
	if (!$flags['userPresent']) {
		genSyslog(__FUNCTION__, $msg='user not present during authentication');
		$success = false;
	}
	// Let hash be the result of computing a hash over the cData using SHA-256.
	// Using the credential public key looked up in step 3, verify that sig is a valid signature over the binary concatenation of authData and hash.
	$publicKey = openssl_pkey_get_public($credentialPublicKey);
	if ($publicKey === false) {
		genSyslog(__FUNCTION__, $msg='public key invalid');
		$success = false;
	}
	if (openssl_verify($authenticatorData.$clientDataHash, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
		genSyslog(__FUNCTION__, $msg='invalid signature');
		$success = false;
	}
	$signatureCounter = unpack('Nsigncount', substr($authenticatorData, 33, 4))['signcount'];
	if ($signatureCounter > 0) {
		if (($_SESSION['registration']['signCount'] !== null) && ($_SESSION['registration']['signCount'] >= $signatureCounter)) {
			genSyslog(__FUNCTION__, $msg='signature counter not valid');
			$success = false;
		}
	}
	return $success;
}


function validateNewAssertion($post) {
	$post = json_decode($post, true);
	$success = true;
	$return = array();
	$clientDataJSON = $post['clientDataJSON'];
	$authenticatorData = $post['authenticatorData'];
	$signature = $post['signature'];
	$credentialPublicKey = null;
	if (isset($_SESSION['registration']['credentialId'])) {
		if ($_SESSION['registration']['credentialId'] === $post['id']) {
			$credentialPublicKey = $_SESSION['registration']['credentialPublicKeyPEM'];
		}
		if ($credentialPublicKey === null) {
			$success = false;
		} else {
			$success = verifyAssertion($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey);
		}
		if ($success) {
			$return['msg'] = "Authentification réussie";
		} else {
			$return['msg'] = "Erreur d'authentification";
		}
		unset($_SESSION['registration']);
		$_SESSION['webauthn'] = $success;
		$_SESSION['rand'] = base64UrlEncode(genNonce(16));
		$return['rand'] = $_SESSION['rand'];
		$return['success'] = $success;
	} else {
		$return['msg'] = "Erreur d'authentification";
		$return['success'] = false;
	}
	return json_encode($return);
}


if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'generatePKCCOreg':
			header('Content-Type: application/json');
			echo generatePKCCOregistration();
			break;
		case 'generatePKCCOauth':
			header('Content-Type: application/json');
			echo generatePKCCOauthentication();
			break;
		case 'processCreate':
			$post = trim(file_get_contents('php://input'));
			header('Content-Type: application/json');
			echo registerNewCredential($post);
			break;
		case 'processGet':
			$post = trim(file_get_contents('php://input'));
			header('Content-Type: application/json');
			echo validateNewAssertion($post);
			break;
		case 'endprocess':
			header('Location: '.$_SESSION['curr_script']);
			break;
		default:
			break;
	}
} else {
	header("Location: ".$_SESSION['curr_script']);
}

?>
