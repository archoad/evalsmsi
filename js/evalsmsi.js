function createAlertBox(txt) {
	var msg = document.createElement('p');
	msg.appendChild(document.createTextNode(txt));

	var btnClose = document.createElement('a')
	btnClose.setAttribute('id', 'closeAlert');
	btnClose.appendChild(document.createTextNode('OK'));
	btnClose.href = '#';

	var divContent = document.createElement('div');
	divContent.setAttribute('class', 'modal-content');
	divContent.appendChild(msg);
	divContent.appendChild(btnClose);

	var divAlert = document.createElement('div');
	divAlert.setAttribute('class', 'modal');
	divAlert.setAttribute('id', 'modalAlert');
	divAlert.appendChild(divContent);

	document.body.appendChild(divAlert);
}


function myAlert(txt, elt=null) {
	createAlertBox(txt);
	var modal = document.getElementById('modalAlert');
	var btn = document.getElementById('closeAlert');
	btn.onclick = function() {
		modal.style.display = 'none';
		document.body.removeChild(modal);
	}
	modal.style.display = 'block';
	window.onclick = function(event) {
		if (event.target == modal) {
			modal.style.display = 'none';
			document.body.removeChild(modal);
		}
	}
	if (elt != null) {
		elt.focus();
		elt.setCustomValidity(txt);
		elt.style.backgroundColor='#FFC7C7';
	}
}


function userFormValidity() {
	var form = document.getElementById('user');
	var l1 = document.getElementById('destination');
	var result = document.getElementById('result[]');
	var role = document.getElementById('role');
	if (result.options.length > 0) {
		var i = result.options.length;
		while (i--) {
			result.remove(i);
		}
	}
	for (var i=1; i<l1.children.length; i++) {
		var option = document.createElement('option');
		option.value = l1.children[i].id;
		option.id = 'etab'+l1.children[i].id;
		option.selected = true;
		result.add(option);
	}
	if (result.options.length == '0') {
		myAlert('Veuillez sélectionner un établissement');
		event.preventDefault();
	}
	if (((role.value=='3') || (role.value=='4')|| (role.value=='5')) && (result.options.length>=2)) {
		myAlert('Veuillez sélectionner un seul établissement');
		event.preventDefault();
	}
}


function validatePattern() {
	var pattern = /^(?=.*[a-z])(?=.*[0-9])(?=.{6,})/;
	var pass1 = document.getElementById('new1').value;
	if (pass1.match(pattern)) {
		document.getElementById('new1').setCustomValidity('');
	} else {
		document.getElementById('new1').setCustomValidity('Doit contenir majuscules, minuscules, chiffres et au moins 6 caractères');
	}
}


function validatePassword() {
	var pass1 = document.getElementById('new1').value;
	var pass2 = document.getElementById('new2').value;
	if (pass1 != pass2) {
		document.getElementById('new2').setCustomValidity('Les mots de passe ne correspondent pas');
	} else {
		document.getElementById('new2').setCustomValidity('');
	}
}


function display(eltName) {
	var elt = document.getElementById(eltName);
	if (elt.id.substring(0,2) == 'ti') {
		var new_elt = document.getElementById('dl'+elt.id.substring(2,4));
	} else {
		var new_elt = document.getElementById('dd'+elt.id.substring(2,6));
	}
	if (new_elt.className=='none') {
		elt.value='-';
		new_elt.className='block';
	} else {
		elt.value='+';
		new_elt.className='none';
	}
}


function countSetQuestion(form) {
	var num_quest_ok = 0;
	for (n=0; n<form.elements.length; n++) {
		if ((form.elements[n].value != 0) && (form.elements[n].id.substring(0,8) == 'question'))
			num_quest_ok++;
	}
	return num_quest_ok;
}


function controlAssessAnswers(form) {
	for(i=1; i<form.elements.length; i++) {
		if (form.elements[i].id.substring(0,8) == 'question') {
			var q = form.elements[i];
			var c_id = 'comment'+q.id.substring(8,18);
			var e_id = 'error'+q.id.substring(8,18);
			var c =  document.getElementById(c_id);
			var e = document.getElementById(e_id);
			if ((q.value == 1) || (q.value == 7)) {
				c.required = true;
				if (c.value == '') {
					c.setCustomValidity('Ajouter une justification');
					e.className = "error active";
					if (q.value == 1) {e.innerHTML = "Vous avez spécifié que certaines questions ne vous sont pas applicables sans le justifier.";}
					if (q.value == 7) {e.innerHTML = "Vous avez spécifié que certaines mesures sont existantes sans apporter les éléments de preuves.";}
				} else {
					c.setCustomValidity('');
					e.innerHTML = "";
					e.className = "error";
				}
			} else {
				c.required = false;
				c.setCustomValidity('');
				e.innerHTML = "";
				e.className = "error";
			}
		}
	}
}


function assessFormValidity(event) {
	var form = document.getElementById('make_assess');
	for(i=1; i<form.elements.length; i++) {
		if (form.elements[i].id.substring(0,8) == 'question') {
			var q = form.elements[i];
			var c_id = 'comment'+q.id.substring(8,18);
			var c =  document.getElementById(c_id);
			if (!c.validity.valid) {
				var subpar = c.parentNode;
				var par = subpar.parentNode;
				par.className = 'block';
				subpar.className = 'block';
				c.focus();
				event.preventDefault();
			}
		}
	}
	var nbr_quests = document.getElementById('nbr_questions').value;
	var num_quest_ok = countSetQuestion(form);
	if (nbr_quests == num_quest_ok) {
		var final_elt = document.getElementById('final_comment');
		if (!final_elt.validity.valid) {
			final_elt.className = 'block';
			final_elt.focus();
			event.preventDefault();
		}
	}
}


function progresse() {
	var form = document.getElementById('make_assess');
	var nbr_quests = document.getElementById('nbr_questions').value;
	var num_quest_ok = countSetQuestion(form);
	var final_elt = document.getElementById('final_comment');
	var final_comment_error = document.getElementById('final_comment_error');
	controlAssessAnswers(form);

	if (num_quest_ok <= nbr_quests) {
		if (num_quest_ok > 10) {
			document.getElementById("c").innerHTML=parseInt((100*num_quest_ok)/nbr_quests)+"%";
		}
		document.getElementById("b").style.width=parseInt((500*num_quest_ok)/nbr_quests)+"px";
	}
	if (nbr_quests == num_quest_ok) {
		final_elt.className='block';
		final_elt.required = true;
		if (final_elt.value == '') {
			final_elt.setCustomValidity('Ajouter un commentaire final');
			final_comment_error.className = "error active";
			final_comment_error.innerHTML = "Vous devez spécifier un commentaire final.";
		} else {
			final_elt.setCustomValidity('');
			final_comment_error.innerHTML = "";
			final_comment_error.className = "error";
		}
	} else {
		final_elt.className='none';
		final_elt.required = false;
	}
}


function xhrequest(input) {
	var xhr = new XMLHttpRequest();
	var url="ajax.php?query="+input;
	xhr.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var row = document.getElementById('selectEtabRow');
			if (row.cells.length > 1) { row.deleteCell(-1); }
			var newCell = row.insertCell(-1);
			newCell.innerHTML = xhr.responseText;
		}
	};
	xhr.open("GET", url, true);
	xhr.send();
}


function alertSession() {
	var currentTime = Math.round(Date.now() / 1000);
	var elapsedTime = Math.round((timeout - currentTime) / 60);
	if (elapsedTime <= 10) {
		myAlert('Votre session va expirer dans 5 minutes.');
	}
}
