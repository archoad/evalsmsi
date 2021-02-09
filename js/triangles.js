function displayTriangles() {
	const options = {
		width: window.innerWidth,
		height: window.innerHeight,
		cellSize: 60,
		xColors: ['#f0f0f0','#c0c0c0','#a0a0a0', '#f0f0f0'],
	};
	var pattern = trianglify(options);
	document.body.appendChild(pattern.toCanvas());
}
