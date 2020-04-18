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
	pre.appendChild(document.createTextNode("Clef publique\n"));
	pre.appendChild(document.createTextNode("x: "+data.x+"\n"));
	pre.appendChild(document.createTextNode("y: "+data.y+"\n"));
	msg.appendChild(pre);
	mydiv.className = "msg";
}


function addReturnMessage() {
	let txt = document.createTextNode("Retour à l'accueil");
	let anchor = document.createElement('a');
	anchor.href = "mfa.php?action=endprocess";
	anchor.appendChild(txt);
	let mydiv = document.createElement('div');
	mydiv.classList.add('foot');
	mydiv.appendChild(anchor);
	document.body.appendChild(mydiv);
}


function displayResultButton(rand) {
	let mylink = document.getElementById('endAuthLink');
	if (rand) {
		mylink.href = "evalsmsi.php?rand="+rand+"&action=connect";
	} else {
		mylink.href = "evalsmsi.php";
	}
	mylink.classList.add('block');
	mylink.classList.remove('none');
}


function newRegistration() {
	let msg = document.getElementById('registerMsg');
	let txt = document.createTextNode("Insérez votre clef FIDO2");
	msg.appendChild(txt);
	setTimeout(getRegistration, 1000)
}


function getRegistration() {
	let msg = document.getElementById('registerMsg');
	console.log('Sending attestation request');
	window.fetch('mfa.php?action=generatePKCCOreg', { method:'POST', cache:'no-cache' }).then(function(response) {
		return response.json();
	}).then(function(credOpt) {
		credOpt['publicKey']['challenge'] = strToBin(credOpt['publicKey']['challenge']);
		credOpt['publicKey']['user']['id'] = strToBin(credOpt['publicKey']['user']['id']);
		console.log(credOpt);
		return credOpt;
	}).then(function(createCredential) {
		let txt = document.createTextNode("Touchez votre clef FIDO2");
		msg.replaceChild(txt, msg.childNodes[0]);
		return navigator.credentials.create(createCredential);
	}).then(function(attestation) {
		console.log('Received attestation response');
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
		console.log('Sending attestation response');
		console.log(attestationResponse);
		window.fetch('mfa.php?action=processCreate', { method:'POST', body:JSON.stringify(attestationResponse), cache:'no-cache' })
		.then(function(response) {
			return response.json();
		}).then(function(parameters) {
			console.log('Parameters', parameters);
			if (parameters.success) {
				document.getElementById('registerImg').src = 'pict/valid.png';
				let txt = document.createTextNode("Votre clef FIDO2 a été enregistrée avec succès");
				msg.replaceChild(txt, msg.childNodes[0]);
				displayPublicKey(parameters.credentials.publicKey);
			} else {
				document.getElementById('registerImg').src = 'pict/invalid.png';
				let txt = document.createTextNode("Erreur d'enregistrement de votre clef FIDO2");
				msg.replaceChild(txt, msg.childNodes[0]);
			}
			addReturnMessage();
		});
	}).catch(function(err) {
		console.log(err.message || 'Unknown error occured');
		document.getElementById('registerImg').src = 'pict/invalid.png';
		let txt = document.createTextNode("Erreur d'enregistrement de votre clef FIDO2");
		msg.replaceChild(txt, msg.childNodes[0]);
		addReturnMessage();
	});
}


function newAuthentication() {
	let msg = document.getElementById('authenticateMsg');
	let txt = document.createTextNode("Insérez votre clef FIDO2");
	msg.appendChild(txt);
	setTimeout(webauthnAuthentication, 1000)
}


function webauthnAuthentication() {
	let msg = document.getElementById('authenticateMsg');
	console.log('Fetching options for new assertion');
	window.fetch('mfa.php?action=generatePKCCOauth', { method:'POST', cache:'no-cache' }).then(function(response) {
		return response.json();
	}).then(function(credOpt) {
		credOpt['publicKey']['challenge'] = strToBin(credOpt['publicKey']['challenge']);
		credOpt['publicKey']['allowCredentials'][0]['id'] = strToBin(credOpt['publicKey']['allowCredentials'][0]['id']);
		console.log(credOpt);
		return credOpt;
	}).then(function(getCredentialArgs) {
		let txt = document.createTextNode("Touchez votre clef FIDO2");
		msg.replaceChild(txt, msg.childNodes[0]);
		return navigator.credentials.get(getCredentialArgs);
	}).then(function(assertion) {
		console.log('Received assertion');
		console.log(assertion);
		const publicKeyCredential = {};
		publicKeyCredential.id = binToStr(assertion.rawId);
		publicKeyCredential.clientDataJSON = binToStr(assertion.response.clientDataJSON);
		publicKeyCredential.authenticatorData = binToStr(assertion.response.authenticatorData);
		publicKeyCredential.signature = binToStr(assertion.response.signature);
		return publicKeyCredential;
	}).then(function(assertionResponse) {
		console.log('Sending assertion response');
		console.log(assertionResponse);
		window.fetch('mfa.php?action=processGet', { method:'POST', body:JSON.stringify(assertionResponse), cache:'no-cache' })
		.then(function(response) {
			return response.json();
		}).then(function(parameters) {
			console.log('Parameters', parameters);
			if (parameters.success) {
				document.getElementById('authenticateImg').src = 'pict/valid.png';
				let txt = document.createTextNode(parameters.msg);
				msg.replaceChild(txt, msg.childNodes[0]);
			} else {
				document.getElementById('authenticateImg').src = 'pict/invalid.png';
				let txt = document.createTextNode(parameters.msg);
				msg.replaceChild(txt, msg.childNodes[0]);
			}
			displayResultButton(parameters.rand);
		});
	}).catch(function(err) {
		console.log(err.message || 'Unknown error occured');
		document.getElementById('authenticateImg').src = 'pict/invalid.png';
		let txt = document.createTextNode("Erreur d'authentification");
		msg.replaceChild(txt, msg.childNodes[0]);
		displayResultButton(false);
	});
}
