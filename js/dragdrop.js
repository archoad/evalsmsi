
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
