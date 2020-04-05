function strToBin(str) {
	return Uint8Array.from(atob(str), c => c.charCodeAt(0));
}


function binToStr(bin) {
	return btoa(new Uint8Array(bin).reduce(
		(s, byte) => s + String.fromCharCode(byte), ''
	));
}


function displayPublicKey(data) {
	let mydiv = document.getElementById('pubKey');
	let msg = document.getElementById('msgPubKey');
	let pre = document.createElement('pre');
	let txtalgo = '';
	console.log('PublicKeyDetails', data);
	switch (data.alg) {
		case 'ES256':
			txtalgo = "ECDSA avec la courbe P-256 et l'algorithme de hashage SHA-256";
			break;
		case 'ES384':
			txtalgo = "ECDSA avec la courbe P-384 et l'algorithme de hashage SHA-384";
			break;
		case 'ES512':
			txtalgo = "ECDSA avec la courbe P-521 et l'algorithme de hashage SHA-512";
			break;
	}
	pre.appendChild(document.createTextNode("Clef publique\n"));
	pre.appendChild(document.createTextNode(txtalgo+"\n"));
	pre.appendChild(document.createTextNode("x: "+data.x+"\n"));
	pre.appendChild(document.createTextNode("y: "+data.y+"\n"));
	msg.appendChild(pre);
	mydiv.className = "msg";
}


function addReturnMessage() {
	let txt = document.createTextNode("Retour à l'accueil");
	let anchor = document.createElement('a');
	anchor.href = "mfa.php?action=registered";
	anchor.appendChild(txt);
	let mydiv = document.createElement('div');
	mydiv.classList.add('foot');
	mydiv.appendChild(anchor);
	document.body.appendChild(mydiv);
}


function newRegistration() {
	let msg = document.getElementById('registerMsg');
	let txt = document.createTextNode("Insérez votre clef Yubikey");
	msg.appendChild(txt);
	setTimeout(getRegistration, 1000)
}


function getRegistration() {
	let msg = document.getElementById('registerMsg');
	console.log('sending attestation request:');
	window.fetch('mfa.php?action=generatePKCCOreg', { method:'POST', cache:'no-cache' }).then(function(response) {
		return response.json();
	}).then(function(credOpt) {
		credOpt['publicKey']['challenge'] = strToBin(credOpt['publicKey']['challenge']);
		credOpt['publicKey']['user']['id'] = strToBin(credOpt['publicKey']['user']['id']);
		console.log(credOpt);
		return credOpt;
	}).then(function(createCredential) {
		let txt = document.createTextNode("Touchez votre clef Yubikey");
		msg.replaceChild(txt, msg.childNodes[0]);
		return navigator.credentials.create(createCredential);
	}).then(function(attestation) {
		console.log('received attestation response:');
		console.log(attestation);
		const publicKeyCredential = {};
		publicKeyCredential.id = attestation.id;
		publicKeyCredential.type = attestation.type;
		publicKeyCredential.rawId = binToStr(attestation.rawId);
		const response = {};
		response.clientDataJSON = binToStr(attestation.response.clientDataJSON);
		response.attestationObject = binToStr(attestation.response.attestationObject);
		publicKeyCredential.response = response;
		return publicKeyCredential;
	}).then(function(attestationResponse) {
		console.log('send attestation response:');
		console.log(attestationResponse);
		window.fetch('mfa.php?action=processCreate', { method:'POST', body:JSON.stringify(attestationResponse), cache:'no-cache' })
		.then(function(response) {
			return response.json();
		})
		.then(function(parameters) {
			console.log('parameters', parameters);
			let txt = document.createTextNode("Votre clef Yubikey a été enregistrée avec succès");
			msg.replaceChild(txt, msg.childNodes[0]);
			displayPublicKey(parameters.credentials.publicKeyDetails);
			addReturnMessage();
		})
	}).catch(function(err) {
		console.log(err.message || 'unknown error occured');
		let txt = document.createTextNode("Erreur d'enregistrement de votre clef Yubikey");
		msg.replaceChild(txt, msg.childNodes[0]);
		addReturnMessage();
	});
}


function newAuthentication() {
	let msg = document.getElementById('authenticateMsg');
	let txt = document.createTextNode("Insérez votre clef Yubikey");
	msg.appendChild(txt);
	setTimeout(webauthnAuthentication, 1000)
}


function webauthnAuthentication() {
	let msg = document.getElementById('authenticateMsg');
	console.log('sending credential request:');
	window.fetch('mfa.php?action=generatePKCCOauth', { method:'POST', cache:'no-cache' }).then(function(response) {
		return response.json();
	}).then(function(credOpt) {
		credOpt['publicKey']['challenge'] = strToBin(credOpt['publicKey']['challenge']);
		credOpt['publicKey']['allowCredentials'][0]['id'] = strToBin(credOpt['publicKey']['allowCredentials'][0]['id']);
		console.log(credOpt);
		return credOpt;
	}).then(function(getCredentialArgs) {
		let txt = document.createTextNode("Touchez votre clef Yubikey");
		msg.replaceChild(txt, msg.childNodes[0]);
		return navigator.credentials.get(getCredentialArgs);
	}).then(function(cred) {
		console.log('received credential:');
		console.log(cred);
	});
}
