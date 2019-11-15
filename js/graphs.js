Chart.platform.disableCSSInjection = true;

const chartColors = ['#4E79A7', '#A0CBE8', '#F28E2B', '#FFBE7D', '#59A14F', '#8CD17D', '#B6992D', '#F1CE63', '#499894', '#86BCB6', '#E15759', '#FF9D9A', '#79706E', '#BAB0AC', '#D37295', '#FABFD2', '#B07AA1', '#D4A6C8', '#9D7660', '#D7B5A6'];

const namedColors = {
	red: 'rgba(255, 99, 132, 1)',
	orange: 'rgba(255, 159, 64, 1)',
	yellow: 'rgba(255, 205, 86, 1)',
	green: 'rgba(75, 192, 192, 1)',
	blue: 'rgba(54, 162, 235, 1)',
	purple: 'rgba(153, 102, 255, 1)',
	grey: 'rgba(231,233,237, 1)'
};

const scoreColors = ['#FF6384', '#F6845F', '#EDC25C', '#CDE458', '#87DB55', '#51D25C', '#4EC990', '#4BC0C0'];


function getSum(total, num) {
	return total + num;
}


function loadLogs() {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			generateLogsGraph(this.responseText);
		}
	};
	xhttp.open('POST', 'logs.php', true);
	xhttp.send();
}


function stringifyArray(data) {
	temp = ''
	for (let [key, value] of Object.entries(data)) {
		temp += key + ' => ' + value + '\n';
	}
	return temp;
}


function generateLogsGraph(datas) {
	var result = JSON.parse(datas);
	var container = document.getElementById('visualization');
	var items = new vis.DataSet(result);
	var options = {
		height: '200px',
		showCurrentTime: true,
		editable: false,
	};
	var timeline = new vis.Timeline(container, items, options);
	timeline.on('mouseDown', function(properties) {
		var elt = document.getElementById('visdata');
		elt.value = stringifyArray(result[properties.item].actions);
	});
}


function loadGraphYear() {
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			displayYearGraphBar(this.responseText);
			displayYearGraphPolar(this.responseText);
			displayYearGraphScatter(this.responseText);
			displayDetailGraph(this.responseText);
		}
	};
	xhttp.open('POST', 'graphs.php', true);
	xhttp.send();
}


function generateScatterData(dat) {
	var data = [];
	var i;
	for(var elt in dat) {
		for(var sub in dat[elt].subdomain) {
			data.push({
				x: elt,
				y: sub,
				r: dat[elt].notes[sub]
			});
		}
	}
	return data;
}


function displayYearGraphBar(datas) {
	var color = Chart.helpers.color;
	var jsonObj = JSON.parse(datas);
	var labels = jsonObj.labels;
	var goals = jsonObj.goals;
	var results = jsonObj.results;
	var l = Object.keys(results).length;
	var currentYear = Object.keys(results)[l-1];

	var barData = {
		labels: labels,
		datasets: [{
			type: 'line',
			label: 'Objectifs',
			borderColor: namedColors.red,
			backgroundColor: color(namedColors.red).alpha(0.5).rgbString(),
			borderWidth: 2,
			fill: false,
			data: goals,
		}]
	};
	var barOption = {
		responsive: true,
		title: {
			display: true,
			text: 'Résultats par domaine - diagramme à barres'
		},
		scales: {
			yAxes: [{ ticks: { min:0, max:8 } }]
		},
		animation: {
			onComplete: function(animation) {
				var elt = document.getElementById('yearGraphBar');
				elt.setAttribute('href', this.toBase64Image());
			}
		}
	};
	var barConfig = {
		type: 'bar',
		data: barData,
		options: barOption
	};
	var barContext = document.getElementById('currentYearGraphBar').getContext('2d');
	var yearChartBar = new Chart(barContext, barConfig);
	var i = 0;
	for(var year in results) {
		var hide = true;
		if (year == currentYear) { hide = false; }
		var newDataset = {
			type: 'bar',
			label: year,
			borderColor: chartColors[i],
			backgroundColor: color(chartColors[i]).alpha(0.5).rgbString(),
			borderWidth: 1,
			data: results[year],
			hidden: hide,
		};
		i++;
		barData.datasets.push(newDataset);
		yearChartBar.update();
	}
}


function displayYearGraphPolar(datas) {
	var color = Chart.helpers.color;
	var jsonObj = JSON.parse(datas);
	var labels = jsonObj.labels;
	var goals = jsonObj.goals;
	var results = jsonObj.results;
	var l = Object.keys(results).length;
	var currentYear = Object.keys(results)[l-1];

	var polarData = {
		labels: labels,
		datasets: [{
			label: 'Note ' + currentYear,
			data: results[currentYear],
			backgroundColor: chartColors,
			borderWidth: 1
		}]
	};
	var polarOption = {
		responsive: true,
		title: {
			display: true,
			text: 'Résultats par domaine - diagramme polaire'
		},
		legend: {
			display: false,
		},
		scales: {
			ticks: { beginAtZero: true },
		},
		animation: {
			onComplete: function(animation) {
				var elt = document.getElementById('yearGraphPolar');
				elt.setAttribute('href', this.toBase64Image());
			}
		}
	};
	var polarConfig = {
		type: 'polarArea',
		data: polarData,
		options: polarOption
	};
	var polarContext = document.getElementById('currentYearGraphPolar').getContext('2d');
	var yearChartRadar = new Chart(polarContext, polarConfig);
}


function displayYearGraphScatter(datas) {
	var color = Chart.helpers.color;
	var jsonObj = JSON.parse(datas);
	var labels = jsonObj.labels;
	var goals = jsonObj.goals;
	var results = jsonObj.results;
	var l = Object.keys(results).length;
	var currentYear = Object.keys(results)[l-1];

	var scatterData = {
		datasets: [{
			data: generateScatterData(jsonObj.data),
		}]
	};
	var scatterOption = {
		responsive: true,
		legend: false,
		tooltips: false,
		title: {
			display: true,
			text: 'Résultats par domaine - diagramme matriciel'
		},
		elements: {
			point: {
				backgroundColor: function(context) {
					var value = context.dataset.data[context.dataIndex];
					var c = Math.round(value.r);
					return scoreColors[c];
				},
				radius: function(context) {
					var value = context.dataset.data[context.dataIndex];
					return 3 + (1.5*value.r);
				},
				hoverRadius: function(context) {
					var value = context.dataset.data[context.dataIndex];
					return 4 + (1.5*value.r);
				},
			}
		},
		scales: {
			xAxes: [{
				ticks: {
					padding: 8,
					callback: function(value, index, values) { return labels[value]; }
				}
			}],
			yAxes: [{
				ticks: {
					padding: 8,
					callback: function(value, index, values) { return 'Sous-domaine ' + value; }
				}
			}]
		},
		animation: {
			onComplete: function(animation) {
				var elt = document.getElementById('yearGraphScatter');
				elt.setAttribute('href', this.toBase64Image());
			}
		}
	};
	var scatterConfig = {
		type: 'scatter',
		data: scatterData,
		options: scatterOption
	};
	var scatterContext = document.getElementById('currentYearGraphScatter').getContext('2d');
	var yearChartScatter = new Chart(scatterContext, scatterConfig);
}


function displayDetailGraph(datas) {
	var color = Chart.helpers.color;
	var jsonObj = JSON.parse(datas);
	var details = jsonObj.data;

	for(var elt in details) {
		var e = details[elt];
		var sum = e.notes.reduce(getSum);
		if (sum != 0) {
			var current_par = document.createElement('p');
			current_par.className = 'separation';
			current_par.innerHTML = '&nbsp;';
			var current_canvas = document.createElement('canvas');
			current_canvas.id = 'doughnutGraph_' + elt;
			document.getElementById('graphs').appendChild(current_canvas);
			document.getElementById('graphs').appendChild(current_par);

			var doughnutData = {
				datasets: [{
					data: e.notes,
					backgroundColor: function(context) {
						var value = context.dataset.data[context.dataIndex];
						var c = Math.round(value);
						return scoreColors[c];
					}
				}],
				labels: e.subdomain,
			};
			var doughnutOption = {
				responsive: true,
				legend: {
					display: false,
				},
				title: {
					display: true,
					text: 'Résultat par sous-domaine - ' + e.domain,
				},
				animation: {
					animateScale: true,
					animateRotate: true
				}
			};
			var doughnutConfig = {
				type: 'doughnut',
				data: doughnutData,
				options: doughnutOption
			};
			var doughnutContext = document.getElementById(current_canvas.id).getContext('2d');
			var detailChartDoughnut = new Chart(doughnutContext, doughnutConfig);
		}
	}
}