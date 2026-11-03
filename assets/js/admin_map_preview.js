// Live map preview: ticking a layer checkbox shows/hides that top-level <g id> in the preview.
// Nothing ticked = whole map (matches the front end).
(function () {
	function init() {
		var preview = document.getElementById('conboard-map-preview');
		if (!preview) return;

		var svg = preview.querySelector('svg');
		var boxes = document.querySelectorAll('input[name="conboard_map_layers[]"]');
		if (!svg || !boxes.length) return;

		var groups = {};
		Array.prototype.forEach.call(svg.children, function (el) {
			if (el.tagName && el.tagName.toLowerCase() === 'g' && el.getAttribute('id')) {
				groups[el.getAttribute('id')] = el;
			}
		});

		function update() {
			var any = false;
			Array.prototype.forEach.call(boxes, function (b) {
				if (b.checked && groups[b.value]) any = true;
			});

			Object.keys(groups).forEach(function (id) {
				var on = true;
				if (any) {
					on = false;
					Array.prototype.forEach.call(boxes, function (b) {
						if (b.value === id && b.checked) on = true;
					});
				}
				groups[id].style.display = on ? '' : 'none';
			});
		}

		Array.prototype.forEach.call(boxes, function (b) {
			b.addEventListener('change', update);
		});
		update();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
