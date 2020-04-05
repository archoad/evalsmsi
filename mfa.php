<?php
/*=========================================================
// File:        mfa.php
// Description: multi factor authentication process of EvalSMSI
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
include("data/cbor/ByteBuffer.php");
include("data/cbor/CborDecoder.php");
include("data/cbor/AuthenticatorData.php");
include("data/cbor/FormatBase.php");
include("data/cbor/U2f.php");
include("data/cbor/None.php");
include("data/cbor/Packed.php");
session_set_cookie_params([
	'lifetime' => $cookie_timeout,
	'path' => '/',
	'domain' => $cookie_domain,
	'secure' => $session_secure,
	'httponly' => $cookie_httponly,
	'samesite' => $cookie_samesite
]);
session_start();
$authorizedRole = array('2', '3', '4', '5');
isSessionValid($authorizedRole);


function base64UrlEncode($data) {
	$b64 = base64_encode($data);
	$url = strtr($b64, '+/', '-_');
	return rtrim($url, '=');
}


function base64UrlDecode($data) {
	$b64 = strtr($data, '-_', '+/');
	$end = str_repeat('=', 3 - (3 + strlen($data)) % 4);
	return base64_decode($b64.$end);
}


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


function generatePKCCOregistration() {
	//https://www.iana.org/assignments/cose/cose.xhtml#algorithms
	$coseAlgoECDSAwSHA256 = -7;
	$coseAlgoECDSAwSHA384 = -35;
	$coseAlgoECDSAwSHA512 = -36;

	$authenticatorSelection = [
		'authenticatorAttachment' => 'cross-platform',
		'requireResidentKey' => false,
		'userVerification' => 'discouraged'
	];
	$pubKeyCredParams = [
		['type' => 'public-key', 'alg' => $coseAlgoECDSAwSHA256],
		['type' => 'public-key', 'alg' => $coseAlgoECDSAwSHA384],
		['type' => 'public-key', 'alg' => $coseAlgoECDSAwSHA512],
	];
	$rp = [
		'name' => 'Archoad WebAuthn',
		'id' => $_SERVER['SERVER_NAME']
	];
	$user = [
		'displayName' => $_SESSION['login'],
		'name' => $_SESSION['prenom']." ".$_SESSION['nom'],
		'id' => $_SESSION['uid'],
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
	$result['attestation'] = "direct";
	$result['extensions'] = $extensions;
	$result['authenticatorSelection'] = $authenticatorSelection;
	$result['excludeCredentials'] = [];
	$_SESSION['challenge'] = $result['challenge'];
	return json_encode(array('publicKey' => $result));
}


function generatePKCCOauthentication() {
	$allowCredentials = [
		'type' => 'public-key',
		'id' => $_SESSION['credentialId'],
		'transports' => ['usb', 'ble', 'nfc'],
	];
	$result = array();
	$result['challenge'] = base64_encode(random_bytes(32));
	$result['allowCredentials'] = [$allowCredentials];
	$result['timeout'] = 60000;
	$result['rpId'] = $_SERVER['SERVER_NAME'];
	$_SESSION['challenge'] = $result['challenge'];
	return json_encode(array('publicKey' => $result));
}


function validateRegistration($clientDataJSON, $attestationObject, $rawId) {
	global $cheminDATA;
	// source: https://w3c.github.io/webauthn/#sctn-registering-a-new-credential
	$success = true;
	$rootCAfile = [$cheminDATA.'yubico_ca.pem'];
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
	//Verify CBOR decoding on the attestationObject field of the AuthenticatorAttestationResponse structure.
	$attestationData = WebAuthn\CBOR\CborDecoder::decode($attestationObject);
	//Verify the attestation statement format fmt
	if (!is_array($attestationData) || !array_key_exists('fmt', $attestationData) || !is_string($attestationData['fmt'])) {
		genSyslog(__FUNCTION__, $msg='invalid attestation format');
		$success = false;
	}
	//Verify the attestation statement format authData
	if (!array_key_exists('authData', $attestationData) || !is_object($attestationData['authData'])) {
		genSyslog(__FUNCTION__, $msg='invalid attestation format (authData not available)');
		$success = false;
	}
	//Verify the attestation statement format attStmt
	if (!array_key_exists('attStmt', $attestationData) || !is_array($attestationData['attStmt'])) {
		genSyslog(__FUNCTION__, $msg='invalid attestation format (attStmt not available)');
		$success = false;
	}
	$authenticatorData = new WebAuthn\Attestation\AuthenticatorData($attestationData['authData']->getBinaryString());
	switch ($attestationData['fmt']) {
		case 'packed':
			$attestationFormat = new WebAuthn\Attestation\Format\Packed($attestationData, $authenticatorData);
			genSyslog(__FUNCTION__, $msg='packed attestation');
			break;
		case 'fido-u2f':
			$attestationFormat = new WebAuthn\Attestation\Format\U2f($attestationData, $authenticatorData);
			genSyslog(__FUNCTION__, $msg='fido-u2f attestation');
			break;
		case 'none':
			$attestationFormat = new WebAuthn\Attestation\Format\None($attestationData, $authenticatorData);
			genSyslog(__FUNCTION__, $msg='none attestation');
			break;
		default:
			genSyslog(__FUNCTION__, $msg='invalid attestation format: '.$attestationData['fmt']);
			$success = false;
	}
	//Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
	if ($authenticatorData->getRpIdHash() !== $rpIdHash) {
		genSyslog(__FUNCTION__, $msg='invalid rpID hash');
		$success = false;
	}
	// Verify that the User Present bit of the flags in authData is set.
	if (!$authenticatorData->getUserPresent()) {
		genSyslog(__FUNCTION__, $msg='user not present during authentication');
		$success = false;
	}
	if (!$attestationFormat->validateAttestation($clientDataHash)) {
		genSyslog(__FUNCTION__, $msg='invalid certificate signature');
		$success = false;
	}
	if (!$attestationFormat->validateRootCertificate($rootCAfile)) {
		genSyslog(__FUNCTION__, $msg='invalid root signature');
		$success = false;
	}
	$data = array();
	$data['rpId'] = $rpId;
	$data['credentialId'] = $rawId;
	$_SESSION['credentialId'] = $data['credentialId'];
	$data['credentialPublicKeyDER'] = base64_encode($authenticatorData->getPublicKeyU2F());
	$data['credentialPublicKeyPEM'] = $authenticatorData->getPublicKeyPem();
	$data['signCount'] = $authenticatorData->getSignCount();
	$data['aaguid'] = base64_encode($authenticatorData->getAAGUID());
	$data['publicKeyDetails'] = $authenticatorData->getPubKeyDetails();
	$certificate = $attestationFormat->getCertificatePem();
	if ($certificate) {
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
		} else {
			$data['x509'] = false;
		}
	} else {
		$data['x509'] = false;
	}
	file_put_contents('create_credential.json', json_encode($data));
	return array($success, $data);
}


function registerNewCredential($post) {
	$post = json_decode($post, true);
	$attestationObject = $post['response']['attestationObject'];
	$clientDataJSON = $post['response']['clientDataJSON'];
	$rawId = $post['rawId'];
	$result = validateRegistration($clientDataJSON, $attestationObject, $rawId);
	$return = array();
	if ($result[0]) {
		$return['success'] = true;
		$return['msg'] = 'Successfully created credential';
		$return['credentials'] = $result[1];
	} else {
		$return['success'] = false;
		$return['msg'] = 'No credential created';
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
		case 'registered':
			header('Location: '.$_SESSION['curr_script']);
			break;
		default:
			break;
	}
} else {
	header("Location: ".$_SESSION['curr_script']);
}







?>
