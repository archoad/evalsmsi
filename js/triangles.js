function randomString(length) {
	return Math.random().toString(20).substr(2, length);
}


function getPaletteName() {
	// https://observablehq.com/@d3/color-schemes
	let palette = "";
	let backColor = getComputedStyle(document.documentElement).getPropertyValue('--bg0Color').trim();
	if (backColor === "#f0f0f0") {
		palette = 'RdYlBu'; // glp theme
	};
	if (backColor === "#d8c3a5") {
		console.log('green');
		palette = 'BrBG';
	};
	if (backColor === "#eae7dc") {
		palette = 'BrBG'; // beige theme
	};
	if (backColor === "#e9e4db") {
		palette = 'RdGy'; // blue theme
	};
	return palette;
}


function displayTriangles() {
	let palette = getPaletteName();
	const svgelt = document.getElementById('svg_element');
	const initseed = randomString(10);
	const options = {
		seed: initseed,
		width: window.innerWidth,
		height: window.innerHeight,
		cellSize: 60,
		variance: 0.75,
		xColors: palette,
		yColors: 'match',
		fill: true,
		colorFunction: trianglify.colorFunctions.sparkle(),
	};
	let pattern = trianglify(options);
	pattern.toSVG(svgelt);
}
