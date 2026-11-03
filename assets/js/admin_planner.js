// Program Planner (step 1): unscheduled cards + a day grid with rooms as columns.
(function () {
	var cfg = window.CONBOARD_PLANNER;
	var root = document.getElementById('conboard-planner');
	if (!cfg || !root) return;

	var data = cfg.data;
	var SNAP = data.snap || 15;
	var HOUR = 52;                 // px per hour
	var PPM = HOUR / 60;           // px per minute
	var sessions = data.sessions;
	var rooms = data.rooms;
	var days = data.days;
	var dayIndex = 0;
	var filter = '';
	var drag = null;               // {id, grab} while an HTML5 drag is in flight

	// Start on the first day that has sessions
	for (var i = 0; i < days.length; i++) {
		if (sessions.some(function (s) { return s.date === days[i].date; })) { dayIndex = i; break; }
	}

	function el(tag, cls, text) {
		var e = document.createElement(tag);
		if (cls) e.className = cls;
		if (text != null) e.textContent = text;
		return e;
	}
	function byId(id) { return sessions.filter(function (s) { return s.id === id; })[0]; }
	function pad(n) { return n < 10 ? '0' + n : '' + n; }
	function clock(m) {
		m = ((m % 1440) + 1440) % 1440;
		var h = Math.floor(m / 60), mm = m % 60, ap = h >= 12 ? 'PM' : 'AM';
		return ((h + 11) % 12 + 1) + (mm ? ':' + pad(mm) : ':00') + ' ' + ap;
	}
	function snap(m) { return Math.round(m / SNAP) * SNAP; }
	function durText(d) { return d >= 60 ? (Math.floor(d / 60) + 'h' + (d % 60 ? ' ' + (d % 60) + 'm' : '')) : d + 'm'; }
	function roomName(id) {
		var r = rooms.filter(function (x) { return x.id === id; })[0];
		return r ? r.title : '';
	}

	function save(session, body) {
		session.saving = true;
		render();
		return fetch(cfg.root + session.id, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify(body)
		}).then(function (r) {
			return r.json().then(function (j) {
				if (!r.ok) throw new Error(j && j.message ? j.message : 'Save failed');
				return j;
			});
		}).then(function (j) {
			Object.keys(j).forEach(function (k) { session[k] = j[k]; });
			// A saved date outside the day list gets its own day
			if (j.date && !days.some(function (d) { return d.date === j.date; })) {
				days.push({ date: j.date, label: j.date });
				days.sort(function (a, b) { return a.date < b.date ? -1 : 1; });
				dayIndex = days.map(function (d) { return d.date; }).indexOf(j.date);
			}
		}).catch(function (e) {
			window.alert('Could not save: ' + e.message);
		}).then(function () {
			session.saving = false;
			render();
		});
	}

	function schedule(session, date, start, room) {
		start = Math.max(0, Math.min(1440 - SNAP, snap(start)));
		return save(session, { date: date, start: start, duration: session.duration, location: room || session.location || 0 });
	}
	function unschedule(session) { return save(session, { unschedule: true }); }

	// ---- drag helpers ----
	function startDrag(e, session, grab) {
		drag = { id: session.id, grab: grab || 0 };
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/plain', String(session.id));
	}
	function endDrag() {
		drag = null;
		Array.prototype.forEach.call(root.querySelectorAll('.cbp-over, .cbp-zone-over'), function (n) { n.classList.remove('cbp-over'); n.classList.remove('cbp-zone-over'); });
	}
	function dropZone(node, onDrop, overClass) {
		overClass = overClass || 'cbp-over';
		node.addEventListener('dragover', function (e) {
			if (!drag) return;
			e.preventDefault();
			node.classList.add(overClass);
		});
		node.addEventListener('dragleave', function () { node.classList.remove(overClass); });
		node.addEventListener('drop', function (e) {
			if (!drag) return;
			e.preventDefault();
			var s = byId(drag.id), grab = drag.grab;
			endDrag();
			if (s) onDrop(s, e, grab);
		});
	}

	// ---- rendering ----
	function overlaps(a, b) {
		return a.start < b.start + b.duration && b.start < a.start + a.duration;
	}

	function renderCards(box) {
		var list = sessions.filter(function (s) {
			return s.start == null && (!filter || s.title.toLowerCase().indexOf(filter) !== -1);
		});
		if (!list.length) box.appendChild(el('div', 'cbp-empty', filter ? 'No matching sessions.' : 'Every session is scheduled.'));
		list.forEach(function (s) {
			var c = el('div', 'cbp-card');
			c.draggable = true;
			c.appendChild(el('b', '', s.title));
			c.appendChild(el('small', '', durText(s.duration) + (s.location ? ' · ' + roomName(s.location) : '')));
			c.addEventListener('dragstart', function (e) { startDrag(e, s, 0); });
			c.addEventListener('dragend', endDrag);
			box.appendChild(c);
		});
	}

	function renderGrid(host, day) {
		var minHour = 8, maxHour = 20;
		sessions.forEach(function (s) {
			if (s.date !== day.date || s.start == null) return;
			minHour = Math.min(minHour, Math.floor(s.start / 60));
			maxHour = Math.max(maxHour, Math.ceil((s.start + s.duration) / 60));
		});
		// The whole day is reachable; scroll puts the busy hours in view
		var totalH = 24 * HOUR;

		var inner = el('div', 'cbp-gridin');
		var axis = el('div', 'cbp-axis');
		axis.style.height = (totalH + 36) + 'px';
		for (var h = 0; h < 24; h++) {
			var t = el('div', '', h === 0 ? '' : clock(h * 60));
			t.style.top = (36 + h * HOUR) + 'px';
			axis.appendChild(t);
		}
		inner.appendChild(axis);

		if (!rooms.length) {
			inner.appendChild(el('div', 'cbp-empty', 'Add locations (rooms) to plan sessions.'));
		}

		var cols = rooms.slice();
		var known = rooms.map(function (r) { return r.id; });
		var loose = sessions.some(function (s) { return s.start != null && known.indexOf(s.location) === -1; });
		if (loose) cols.push({ id: 0, title: '(Other / no room)', parent: '' });

		cols.forEach(function (room) {
			var col = el('div', 'cbp-col');
			var head = el('div', 'cbp-colhead', room.title);
			if (room.parent) head.title = room.parent + ' › ' + room.title;
			col.appendChild(head);

			var body = el('div', 'cbp-colbody');
			body.style.height = totalH + 'px';
			body.style.setProperty('--hour', HOUR + 'px');

			dropZone(body, function (s, e, grab) {
				var y = e.clientY - body.getBoundingClientRect().top;
				schedule(s, day.date, y / PPM - grab, room.id);
			});

			var here = sessions.filter(function (s) { return s.date === day.date && s.start != null && (room.id === 0 ? known.indexOf(s.location) === -1 : s.location === room.id); });
			here.forEach(function (s) {
				var b = el('div', 'cbp-block' + (s.cancelled ? ' cbp-cancelled' : '') + (s.saving ? ' cbp-saving' : ''));
				if (here.some(function (o) { return o !== s && !o.cancelled && !s.cancelled && overlaps(s, o); })) b.classList.add('cbp-conflict');
				b.style.top = (s.start * PPM) + 'px';
				b.style.height = Math.max(18, s.duration * PPM - 1) + 'px';
				b.title = s.title + '\n' + clock(s.start) + ' – ' + clock(s.start + s.duration);
				b.appendChild(el('b', '', s.title));
				b.appendChild(el('span', '', clock(s.start) + ' – ' + clock(s.start + s.duration)));
				b.draggable = true;
				b.addEventListener('dragstart', function (e) {
					startDrag(e, s, (e.clientY - b.getBoundingClientRect().top) / PPM);
				});
				b.addEventListener('dragend', endDrag);
				b.addEventListener('dblclick', function () { window.open(s.edit, '_blank'); });

				var handle = el('div', 'cbp-resize');
				handle.title = 'Drag to change the length';
				handle.addEventListener('mousedown', function (e) {
					e.preventDefault(); e.stopPropagation();
					b.draggable = false;
					var startY = e.clientY, startDur = s.duration;
					function move(ev) {
						var d = Math.max(SNAP, snap(startDur + (ev.clientY - startY) / PPM));
						d = Math.min(d, 1440 - s.start);
						b.style.height = Math.max(18, d * PPM - 1) + 'px';
						s._pending = d;
					}
					function up() {
						document.removeEventListener('mousemove', move);
						document.removeEventListener('mouseup', up);
						b.draggable = true;
						if (s._pending && s._pending !== s.duration) {
							var d = s._pending; s._pending = null;
							save(s, { date: s.date, start: s.start, duration: d, location: s.location });
						}
					}
					document.addEventListener('mousemove', move);
					document.addEventListener('mouseup', up);
				});
				b.appendChild(handle);
				body.appendChild(b);
			});

			col.appendChild(body);
			inner.appendChild(col);
		});

		host.appendChild(inner);
		return { minHour: minHour };
	}


	// ---- map pane ----
	var maps = data.maps || [];
	var mapChoice = null;          // location id of the chosen map, or 0 for "no map"
	var cursor = 9 * 60;           // the time shown on the map (follows the grid scroll)
	var mapEl = null, gridEl = null, cursorTag = null, cursorLine = null;

	function pickDefaultMap() {
		var withZones = maps.filter(function (m) {
			return rooms.some(function (r) { return r.map === m.id && r.layers.length; });
		})[0];
		return withZones ? withZones.id : (maps.length ? maps[0].id : 0);
	}

	function activeAt(t) {
		var date = days[dayIndex].date;
		return sessions.filter(function (s) {
			return s.date === date && s.start != null && !s.cancelled && s.start <= t && t < s.start + s.duration;
		});
	}
	function placeHere(s, room) {
		return schedule(s, days[dayIndex].date, cursor, room.id);
	}

	function renderMapPane() {
		if (!mapEl) return;
		if (mapChoice === null) mapChoice = pickDefaultMap();
		mapEl.innerHTML = '';

		var head = el('div', 'cbp-maphead');
		head.appendChild(el('strong', '', 'Map'));
		var sel = el('select');
		maps.forEach(function (m) {
			var o = el('option', '', m.title); o.value = String(m.id);
			if (m.id === mapChoice) o.selected = true;
			sel.appendChild(o);
		});
		var none = el('option', '', 'No map (room boxes)'); none.value = '0';
		if (mapChoice === 0) none.selected = true;
		sel.appendChild(none);
		sel.addEventListener('change', function () { mapChoice = parseInt(sel.value, 10) || 0; renderMapPane(); });
		head.appendChild(sel);
		cursorTag = el('span', 'cbp-maptime', 'Map time: ' + clock(cursor));
		head.appendChild(cursorTag);
		mapEl.appendChild(head);

		var body = el('div', 'cbp-mapbody');
		mapEl.appendChild(body);

		var map = maps.filter(function (m) { return m.id === mapChoice; })[0];
		var chipRooms = rooms;
		var zoned = [];                // {room, groups}
		var stage = null;

		if (map) {
			stage = el('div', 'cbp-mapstage');
			stage.innerHTML = map.html;       // sanitized on the server
			body.appendChild(stage);

			var svg = map.svg ? stage.querySelector('svg') : null;
			var groups = {};
			if (svg) {
				Array.prototype.forEach.call(svg.children, function (g) {
					if (g.tagName && g.tagName.toLowerCase() === 'g' && g.getAttribute('id')) groups[g.getAttribute('id')] = g;
				});
			}

			var onMap = rooms.filter(function (r) { return r.map === map.id; });
			var labelsImg = stage.querySelector('.conboard-map-labels');
			var stack = stage.querySelector('.conboard-map-stack');
			var loose = [];

			onMap.forEach(function (room) {
				var gs = room.layers.map(function (id) { return groups[id]; }).filter(Boolean);
				var active = activeAt(cursor).filter(function (s) { return s.location === room.id; });

				if (active.length && room.highlight && stack) {
					var img = el('img', 'conboard-map-overlay conboard-map-highlight');
					img.src = room.highlight; img.alt = '';
					stack.insertBefore(img, labelsImg || null);
				}

				if (!gs.length) { loose.push(room); return; }

				gs.forEach(function (g) {
					g.classList.add('cbp-zone');
					if (active.length) g.classList.add('cbp-hit');
					var tip = document.createElementNS('http://www.w3.org/2000/svg', 'title');
					tip.textContent = room.title;
					g.appendChild(tip);
					dropZone(g, function (s) { placeHere(s, room); }, 'cbp-zone-over');
				});
				zoned.push({ room: room, groups: gs, active: active });
			});

			// Rooms with no layer on this map are dropped from the list below the map
			chipRooms = onMap.length ? loose : rooms;
			if (!zoned.length) {
				var note = el('div', 'cbp-empty', onMap.length ? 'This map has no room layers assigned, so it is a visual guide. Drop sessions on a room below.' : 'No rooms use this map yet, so it is a visual guide. Drop sessions on a room below.');
				body.insertBefore(note, stage);
			}
		}

		if (chipRooms.length) {
			var list = el('div', 'cbp-rooms');
			chipRooms.forEach(function (room) {
				var active = activeAt(cursor).filter(function (s) { return s.location === room.id; });
				var box = el('div', 'cbp-roombox' + (active.length ? ' cbp-hit' : ''));
				box.appendChild(el('strong', '', room.title));
				if (active.length) {
					var a = active[0];
					var line = el('div', '', a.title + ' · ' + clock(a.start) + '–' + clock(a.start + a.duration));
					line.draggable = true;
					line.style.cursor = 'grab';
					line.addEventListener('dragstart', function (e) { startDrag(e, a, 0); });
					line.addEventListener('dragend', endDrag);
					box.appendChild(line);
					if (active.length > 1) box.appendChild(el('small', '', '+' + (active.length - 1) + ' more'));
				} else {
					box.appendChild(el('small', '', 'Free at ' + clock(cursor)));
				}
				dropZone(box, function (s) { placeHere(s, room); });
				list.appendChild(box);
			});
			body.appendChild(list);
		}

		// Title and time pills sit over the highlighted rooms (positions need the stage to be laid out)
		if (stage) {
			var sr = stage.getBoundingClientRect();
			zoned.forEach(function (z) {
				if (!z.active.length) return;
				var r = z.groups[0].getBoundingClientRect();
				var a = z.active[0];
				var pill = el('div', 'cbp-pill');
				pill.style.left = (r.left - sr.left + r.width / 2) + 'px';
				pill.style.top = (r.top - sr.top + r.height / 2) + 'px';
				pill.appendChild(el('b', '', a.title));
				pill.appendChild(el('span', '', clock(a.start) + ' – ' + clock(a.start + a.duration) + (z.active.length > 1 ? ' (+' + (z.active.length - 1) + ')' : '')));
				pill.draggable = true;
				pill.addEventListener('dragstart', function (e) { startDrag(e, a, 0); });
				pill.addEventListener('dragend', endDrag);
				dropZone(pill, function (s) { placeHere(s, z.room); }, 'cbp-zone-over');
				stage.appendChild(pill);
			});
		}
	}

	// The red line in the grid marks the time the map shows; scrolling the grid moves it
	var rafPending = false;
	function updateCursor() {
		if (!gridEl || !cursorLine) return;
		var yView = 36 + Math.round((gridEl.clientHeight - 36) * 0.3);
		cursorLine.style.top = yView + 'px';
		var m = snap((gridEl.scrollTop + (yView - 36)) / PPM);
		m = Math.max(0, Math.min(1440 - SNAP, m));
		if (m !== cursor) { cursor = m; renderMapPane(); }
		else if (cursorTag) cursorTag.textContent = 'Map time: ' + clock(cursor);
		cursorLine.firstChild.textContent = clock(cursor);
	}
	function onGridScroll() {
		if (rafPending) return;
		rafPending = true;
		window.requestAnimationFrame(function () { rafPending = false; updateCursor(); });
	}

	var keepScroll = null;

	function render() {
		var grid = root.querySelector('.cbp-grid');
		if (grid) keepScroll = { top: grid.scrollTop, left: grid.scrollLeft };
		root.innerHTML = '';

		// Left: map placeholder (step 2) + unscheduled
		var left = el('div', 'cbp-left');
		mapEl = el('div', 'cbp-map');
		left.appendChild(mapEl);

		var un = el('div', 'cbp-unscheduled');
		var uh = el('header');
		uh.appendChild(el('strong', '', 'Unscheduled'));
		var search = el('input');
		search.type = 'search'; search.placeholder = 'Filter sessions'; search.value = filter;
		search.addEventListener('input', function () {
			filter = search.value.trim().toLowerCase();
			var cards = un.querySelector('.cbp-cards');
			cards.innerHTML = '';
			renderCards(cards);
		});
		uh.appendChild(search);
		un.appendChild(uh);
		var cards = el('div', 'cbp-cards');
		renderCards(cards);
		un.appendChild(cards);
		dropZone(un, function (s) { if (s.start != null) unschedule(s); });
		left.appendChild(un);
		root.appendChild(left);

		// Right: day bar, slivers and grid
		var right = el('div', 'cbp-right');
		var day = days[dayIndex];

		var bar = el('div', 'cbp-daybar');
		var prevBtn = el('button', 'button', '‹'); prevBtn.type = 'button'; prevBtn.disabled = dayIndex === 0;
		var nextBtn = el('button', 'button', '›'); nextBtn.type = 'button'; nextBtn.disabled = dayIndex >= days.length - 1;
		prevBtn.addEventListener('click', function () { dayIndex--; render(); });
		nextBtn.addEventListener('click', function () { dayIndex++; render(); });
		bar.appendChild(prevBtn);
		bar.appendChild(el('span', '', day.label));
		bar.appendChild(nextBtn);
		right.appendChild(bar);

		var wrap = el('div', 'cbp-daywrap');

		function sliver(cls, idx) {
			var d = days[idx];
			var s = el('div', 'cbp-sliver ' + cls);
			if (!d) { s.style.cursor = 'default'; return s; }
			var count = sessions.filter(function (x) { return x.date === d.date; }).length;
			s.appendChild(el('span', '', (cls === 'prev' ? '‹ ' : '') + d.label + ' (' + count + ')' + (cls === 'next' ? ' ›' : '')));
			s.title = 'Click to go to ' + d.label + ', or drop a session here to move it to that day';
			s.addEventListener('click', function () { dayIndex = idx; render(); });
			dropZone(s, function (sess) {
				if (sess.start == null) return schedule(sess, d.date, 9 * 60, sess.location);
				schedule(sess, d.date, sess.start, sess.location);
			});
			return s;
		}

		wrap.appendChild(sliver('prev', dayIndex - 1));
		var grid2 = el('div', 'cbp-grid');
		var info = renderGrid(grid2, day);
		wrap.appendChild(grid2);
		cursorLine = el('div', 'cbp-cursor');
		cursorLine.style.left = '46px'; cursorLine.style.right = '46px';
		cursorLine.appendChild(el('span', '', clock(cursor)));
		wrap.appendChild(cursorLine);
		wrap.appendChild(sliver('next', dayIndex + 1));
		gridEl = grid2;
		grid2.addEventListener('scroll', onGridScroll);
		right.appendChild(wrap);
		root.appendChild(right);

		if (keepScroll) { grid2.scrollTop = keepScroll.top; grid2.scrollLeft = keepScroll.left; }
		else grid2.scrollTop = Math.max(0, (info.minHour - 1) * HOUR);

		updateCursor();
		renderMapPane();
	}

	render();
})();
