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
		elt.style.backgroundColor='#FFC7C7';
	}
}


function champs_ok(form) {
	for(i=0; i<form.elements.length; i++) {
		if (form.elements[i].value === '') {
			myAlert('Formulaire incomplet', form.elements[i]);
			return false;
		}
	}
	return true;
}


function valideObj(elt) {
	if (elt.value != "") {
		if (isNaN(elt.value)) {
			myAlert("Vous devez saisir un chiffre.", elt);
		} else {
			if ((elt.value > 7) || (elt.value < 1)) {
				myAlert("Vous devez saisir un objectif entre 1 et 7.", elt);
			}
		}
	}
}


function user_champs_ok(form) {
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
	if (champs_ok(form)) {
		if (((role.value=='3') || (role.value=='4')) && (result.options.length>=2)) {
			myAlert('Veuillez sélectionner un seul établissement');
			return false;
		}
		return true;
	} else {
		return false;
	}
	return true;
}


function password_ok(form) {
	if (form.new1.value.length < 6) {
		myAlert('Le mot de passe doit contenir plus de 6 caractères', form.new1);
		return false;
	}
	if (form.new1.value.match(/^[a-zA-Z0-9]*$/) != form.new1.value) {
		myAlert('Le mot de passe ne doit contenir que des caractères alphanumériques', form.new1);
		return false;
	}
	if (form.new1.value != form.new2.value) {
		myAlert('Erreur de saisie', form.new2);
		return false;
	}
	return true;
}


function champs_na(form) {
	var tmp_elt = document.getElementById('nbr_questions');
	var nbr_quests = tmp_elt.value;
	var num_quest_ok = 0;
	var form = document.getElementById('make_assess');
	for (n=0; n<form.elements.length; n++) {
		if ((form.elements[n].value != 0) && (form.elements[n].id.substring(0,8) == 'question'))
			num_quest_ok++;
	}
	for(i=1; i<form.elements.length; i++) {
		if (form.elements[i].id.substring(0,8) == 'question') {
			var comment = 'comment'+form.elements[i].id.substring(8,18);
			if ((form.elements[i].value == 1) && (form.elements[comment].value == '')) {
				myAlert("Vous avez spécifié que certaines questions ne vous sont pas applicables sans le justifier.");
				var subpar = form.elements[comment].parentNode;
				var par = subpar.parentNode;
				par.style.display = 'block';
				subpar.style.display = 'block';
				form.elements[comment].focus();
				form.elements[comment].style.backgroundColor='#FFC7C7'
				return false;
			}
			if ((form.elements[i].value == 7) && (form.elements[comment].value == '')) {
				myAlert("Vous avez spécifié que certaines mesures sont existantes sans apporter les éléments de preuves.");
				var subpar = form.elements[comment].parentNode;
				var par = subpar.parentNode;
				par.style.display = 'block';
				subpar.style.display = 'block';
				form.elements[comment].focus();
				form.elements[comment].style.backgroundColor='#FFC7C7'
				return false;
			}
		}
	}
	if (nbr_quests == num_quest_ok) {
		var final_elt = document.getElementById('final_comment');
		if (final_elt.value == '<br />') {
			myAlert("Vous n'avez pas saisi de commentaire final pour l'évaluation.");
			return false;
		}
	}
	return true;
}


function display(elt) {
	if (elt.id.substring(0,2) == 'ti') {
		var new_elt = document.getElementById('dl'+elt.id.substring(2,4));
	} else {
		var new_elt = document.getElementById('dd'+elt.id.substring(2,6));
	}
	if (new_elt.style.display=='none') {
		elt.value='-';
		new_elt.style.display='block';
	} else {
		elt.value='+';
		new_elt.style.display='none';
	}
}

function progresse() {
	var tmp_elt = document.getElementById('nbr_questions');
	var nbr_quests = tmp_elt.value;
	var num_quest_ok = 0;
	var form = document.getElementById('make_assess');
	for (n=0; n<form.elements.length; n++) {
		if ((form.elements[n].value != 0) && (form.elements[n].id.substring(0,8) == 'question'))
			num_quest_ok++;
	}
	if (num_quest_ok <= nbr_quests) {
		if (num_quest_ok > 10) {
			document.getElementById("c").innerHTML=parseInt((100*num_quest_ok)/nbr_quests)+"%";
		}
		document.getElementById("b").style.width=parseInt((500*num_quest_ok)/nbr_quests)+"px";
	}
	if (nbr_quests == num_quest_ok) {
		var par = document.getElementById('final_comment');
		par.style.display = 'block';
		if (document.getElementById("final_comment").value == '' ) {
			alert("Questionnaire complété à 100%\nVEUILLEZ COMPLETER LE COMMENTAIRE FINAL EN FIN DE FORMULAIRE");
		}
	}
}


function isset(variable) {
	if ( typeof( window[variable] ) != "undefined" ) {
		return true;
	} else {
		return false;
	}
}


function getURLParam(strParamName){
	var strReturn = "";
	var strHref = window.location.href;
	if ( strHref.indexOf("?") > -1 ){
		var strQueryString = strHref.substr(strHref.indexOf("?")).toLowerCase();
		var aQueryString = strQueryString.split("&");
		for ( var iParam = 0; iParam < aQueryString.length; iParam++ ){
			if ( aQueryString[iParam].indexOf(strParamName.toLowerCase() + "=") > -1 ){
				var aParam = aQueryString[iParam].split("=");
				strReturn = aParam[1];
				break;
			}
		}
	}
	return unescape(strReturn);
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


(function() {
	var dndHandler = {
		draggedElement: null,

		applyDragEvents: function(element) {
			element.draggable = true;
			var dndHandler = this;
			element.addEventListener('dragstart', function(e) {
				dndHandler.draggedElement = e.target;
				this.classList.add('draggable-active');
				e.dataTransfer.setData('text/plain', e.target.id);
			}, false);
		},

		applyDropEvents: function(dropper) {
			dropper.addEventListener('dragover', function(e) {
				e.preventDefault();
				this.classList.add('drop_hover');
			}, false);
			dropper.addEventListener('dragleave', function(e) {
				this.classList.remove('drop_hover');
			}, false);
			var dndHandler = this;
			dropper.addEventListener('drop', function(e) {
				e.preventDefault();
				var target = e.target;
				draggedElement = dndHandler.draggedElement;
				clonedElement = draggedElement.cloneNode(true);
				while(target.className.indexOf('dropper') == -1) {
					target = target.parentNode;
				}
				target.classList.remove('drop_hover');
				clonedElement.classList.remove('draggable-active');
				clonedElement = target.appendChild(clonedElement);
				dndHandler.applyDragEvents(clonedElement);
				draggedElement.parentNode.removeChild(draggedElement);
			}, false);
		}
	};

	var elements = document.querySelectorAll('.draggable');
	var elementsLen = elements.length;
	for(var i = 0 ; i < elementsLen ; i++) {
		dndHandler.applyDragEvents(elements[i]);
	}
	var droppers = document.querySelectorAll('.dropper');
	var droppersLen = droppers.length;
	for(var i = 0 ; i < droppersLen ; i++) {
		dndHandler.applyDropEvents(droppers[i]);
	}
})();
