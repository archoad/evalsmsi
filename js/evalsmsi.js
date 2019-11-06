function myAlert(txt, elt=null) {
	var msg = document.createElement('p');
	msg.appendChild(document.createTextNode(txt));

	var btnAlert = document.createElement('a')
	btnAlert.setAttribute('class', 'btnDanger');
	btnAlert.appendChild(document.createTextNode('OK'));
	btnAlert.href = '#';
	btnAlert.onclick = function() { removeAlert(elt); }

	var divAlert = document.createElement('div');
	divAlert.setAttribute('class', 'alert');
	divAlert.appendChild(msg);
	divAlert.appendChild(btnAlert);

	var divBackground = document.createElement('div');
	divBackground.setAttribute('class', 'alertbackground');
	divBackground.id = 'caution';
	divBackground.appendChild(divAlert);

	document.body.appendChild(divBackground);
}


function removeAlert(elt) {
	divAlert = document.getElementById('caution');
	document.body.removeChild(divAlert);
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
			alert("Vous devez saisir un chiffre.");
		} else {
			if ((elt.value > 7) || (elt.value < 1)) {
				alert("Vous devez saisir un objectif entre 1 et 7.");
			}
		}
	}
}


var xhr;


function complete(input, action) {
	xhr=createObject();
	if (xhr==null) {
		alert ("Browser does not support HTTP Request");
		return;
	}
	var url="ajax.php";
	url=url+"?query="+input;
	url=url+"&action="+action;
	switch (action) {
		case 'question':
			xhr.onreadystatechange=stateQuestionChanged;
			break;
		case 'sub_par':
			xhr.onreadystatechange=stateSubParChanged;
			break
		case 'num_quest':
			xhr.onreadystatechange=stateNumQuestChanged;
			break
		default:
			break;
	}
	xhr.open("GET",url,true);
	xhr.send(null);
}


function stateQuestionChanged() {
	if (xhr.readyState==4 || xhr.readyState=="complete"){
		var result = xhr.responseText;
		document.getElementById("quest_id_sub_par").innerHTML=result;
	}
}


function stateSubParChanged() {
	if (xhr.readyState==4 || xhr.readyState=="complete"){
		var result = xhr.responseText;
		document.getElementById("num_sub_par").value=result;
	}
}


function stateNumQuestChanged() {
	if (xhr.readyState==4 || xhr.readyState=="complete"){
		var result = xhr.responseText;
		document.getElementById("num_quest").value=result;
	}
}


function cleanEtbs() {
	var l1 = document.getElementById('listetbs');
	var l2 = document.getElementById('chosenetbs[]');
	var l2len = l2.length;
	for (i=(l2len -1); i>=0; i--) {
		l1.add(l2.options[i]);
		l2.options[i] = null;
	}
}


function user_champs_ok(form) {
	var l1 = document.getElementById('listetbs');
	var l2 = document.getElementById('chosenetbs[]');
	l1.options[0].selected = true;
	for (i=0; i<l2.length; i++) {
		l2.options[i].selected = true;
	}
	if (champs_ok(form)) {
		return true;
	} else {
		return false;
	}
}


function left2right() {
	var role = document.getElementById('role');
	if (role.value === '') {
		myAlert('Saisisez un rôle avant de choisir un établissement', role);
	} else {
		var l1 = document.getElementById('listetbs');
		var l2 = document.getElementById('chosenetbs[]');
		var l1len = l1.length;
		var l2len = l2.length;
		var doit =  false;
		if (((role.value==='3') || (role.value==='4')) && (l2len==0)) { doit = true; }
		if (role.value==='2') { doit = true; }
		if (doit) {
			for (i=0; i<l1len ; i++) {
				if (l1.options[i].selected == true) {
					var option = document.createElement('option');
					option.text = l1.options[i].text;
					option.value = l1.options[i].value;
					l2.add(option);
				}
			}
			for (i=(l1len -1); i>=0; i--) {
				if (l1.options[i].selected == true) {
					l1.options[i] = null;
				}
			}
			l1.options[0].selected = true;
			l2.options[0].selected = true;
		}
	}
}


function right2left() {
	var l1 = document.getElementById('listetbs');
	var l2 = document.getElementById('chosenetbs[]');
	var l2len = l2.length;
	for (i=0; i<l2len ; i++) {
		if (l2.options[i].selected == true) {
			var l1len = l1.length;
			var option = document.createElement('option');
			option.text = l2.options[i].text;
			option.value = l2.options[i].value;
			l1.add(option);
		}
	}
	for (i=(l2len -1); i>=0; i--) {
		if (l2.options[i].selected == true) {
			l2.options[i] = null;
		}
	}
}


function createObject() {
	var xhr=null;
	try {
		xhr=new XMLHttpRequest();
	}catch (e){
		try {
			xhr=new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			xhr=new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xhr;
}


function valid_suppr(item, id) {
	var cible;
	check = confirm("Voulez vous vraiment supprimer cet item ?");
	if(check == true) {
		cible = "suppr.php?action="+item+"&value="+id;
	} else {
		cible = "admin.php?action=modifications";
	}
	window.location.href=cible;
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


function textEditor() {
	for(i=0; i<self.document.forms[0].elements.length; i++) {
		elt = self.document.forms[0].elements[i];
		if (elt.type == 'textarea') {
			if ((elt.id == 'final_comment') || (elt.id == 'comments')) {
				CKEDITOR.replace( elt.name,
					{
						toolbar :
						[
							['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Preview', 'Print']
						],
						uiColor : '#2E7CC3',
						language : 'fr',
						resize_enabled : false,
						toolbarCanCollapse : false,
						disableNativeSpellChecker : true,
						removePlugins : 'elementspath,save,font,file,scayt,wsc,dialog',
						height : '250px',
						width : '98%'
					} );
			}
		}
	}
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
				alert("Vous avez spécifié que certaines questions ne vous sont pas applicables sans le justifier.");
				var subpar = form.elements[comment].parentNode;
				var par = subpar.parentNode;
				par.style.display = 'block';
				subpar.style.display = 'block';
				form.elements[comment].focus();
				form.elements[comment].style.backgroundColor='#FFC7C7'
				return false;
			}
			if ((form.elements[i].value == 7) && (form.elements[comment].value == '')) {
				alert("Vous avez spécifié que certaines mesures sont existantes sans apporter les éléments de preuves.");
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
			alert("Vous n'avez pas saisi de commentaire final pour l'évaluation.");
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
		textEditor();
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
