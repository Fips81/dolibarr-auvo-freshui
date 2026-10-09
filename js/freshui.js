/* FreshUI - Menue-Filter, Strg+K, Dark-Mode- und Sidebar-Umschalter. Eigenentwicklung, GPL v3+. */
(function () {
	'use strict';

	var root = document.documentElement;
	var L = (window.FRESHUI && window.FRESHUI.labels) || {};

	function store(key, value) {
		try {
			if (value === undefined) { return localStorage.getItem(key); }
			localStorage.setItem(key, value);
		} catch (e) { /* Speicher gesperrt: Einstellung gilt nur für diese Seite */ }
		return null;
	}

	function el(tag, cls, html) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (html) { n.innerHTML = html; }
		return n;
	}

	var ICON_MOON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';
	var ICON_SEARCH = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>';
	var ICON_ENTER = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 5v7a3 3 0 0 1-3 3H5"/><path d="m9 11-4 4 4 4"/></svg>';
	var ICON_PANEL = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16"/></svg>';

	function ready(fn) {
		if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
	}

	function norm(s) { return (s || '').toLowerCase(); }

	// Anmeldeseite: Begrüßung und Markenfläche
	function initLogin() {
		var data = window.FRESHUI || {};
		var L2 = data.labels || {};
		var form = document.getElementById('login');
		if (!form) { return; }

		var h = new Date().getHours();
		var hello = h < 11 ? L2.morning : (h < 18 ? L2.day : L2.evening);
		var greet = el('div', 'fu-logingreet');
		var t = el('h2');
		t.textContent = hello || '';
		var p = el('p');
		p.textContent = (L2.loginTo || '%s').replace('%s', data.company || '');
		greet.appendChild(t);
		greet.appendChild(p);
		var right = document.getElementById('login_right');
		if (right && right.parentNode) { right.parentNode.insertBefore(greet, right); }

		var brand = el('div', 'fu-loginbrand');
		var name = el('h1');
		name.textContent = data.company || '';
		brand.appendChild(el('span', 'fu-loginbar'));
		brand.appendChild(name);
		document.body.appendChild(brand);

		var eye = document.getElementById('togglepassword');
		if (eye && L2.show) { eye.title = L2.show; }
	}

	// Such-Palette für Dolibarrs globale Suche: direkte Treffer + "Suchen in"-Liste.
	// Ersetzt die Select2-Liste (#searchselectcombo), Tastenkürzel bleiben gleich.
	function initPalette() {
		var url = window.FRESHUI.search;
		var back = el('div', 'fu-cmdk-back');
		var box = el('div', 'fu-cmdk');
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		var head = el('div', 'fu-cmdk-head', ICON_SEARCH);
		var input = el('input', 'fu-cmdk-input');
		input.type = 'text';
		input.autocomplete = 'off';
		input.spellcheck = false;
		input.placeholder = L.searchPh || 'Search…';
		input.setAttribute('aria-label', input.placeholder);
		var esc = el('kbd', 'fu-kbd');
		esc.textContent = 'Esc';
		esc.addEventListener('click', function () { close(); });
		head.appendChild(input);
		head.appendChild(esc);
		var list = el('div', 'fu-cmdk-list');
		list.setAttribute('role', 'listbox');
		var foot = el('div', 'fu-cmdk-foot');
		[['↑', '↓', L.nav || 'Navigate'], [ICON_ENTER, L.open || 'Open'], [/Mac/.test(navigator.platform) ? '⌘' : 'Strg', ICON_ENTER, L.newTab || 'New tab'], ['Esc', L.close || 'Close']].forEach(function (h) {
			var span = el('span', 'fu-cmdk-hint');
			h.forEach(function (part, i) {
				var last = i === h.length - 1;
				var n = el(last ? 'span' : 'kbd', last ? '' : 'fu-kbd');
				// Symbole (SVG) als Markup, Beschriftungen als Text
				if (part.charAt(0) === '<') { n.innerHTML = part; } else { n.textContent = part; }
				span.appendChild(n);
			});
			foot.appendChild(span);
		});
		box.appendChild(head);
		box.appendChild(list);
		box.appendChild(foot);
		back.appendChild(box);
		back.hidden = true;
		document.body.appendChild(back);

		var items = [], active = -1, timer = null, ctrl = null, seq = 0, lastQ = null, prevFocus = null, pending = null;

		function words(q) {
			return q.split(/\s+/).filter(Boolean).map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
		}
		// Text mit hervorgehobenen Suchwörtern einsetzen (ohne innerHTML)
		function mark(node, text, w) {
			if (!w.length) { node.textContent = text; return; }
			var re = new RegExp('(' + w.join('|') + ')', 'ig');
			text.split(re).forEach(function (part, i) {
				if (!part) { return; }
				if (i % 2) { var m = el('mark'); m.textContent = part; node.appendChild(m); } else { node.appendChild(document.createTextNode(part)); }
			});
		}
		function setActive(i, scroll) {
			if (items[active]) { items[active].classList.remove('fu-active'); items[active].removeAttribute('aria-selected'); }
			active = items.length ? (i + items.length) % items.length : -1;
			if (items[active]) {
				items[active].classList.add('fu-active');
				items[active].setAttribute('aria-selected', 'true');
				if (scroll) { items[active].scrollIntoView({ block: 'nearest' }); }
			}
		}
		function item(it, w, big) {
			var a = el('a', 'fu-cmdk-item' + (big ? ' fu-cmdk-hit' : '') + (it.off ? ' fu-cmdk-off' : ''));
			a.href = it.url;
			a.setAttribute('role', 'option');
			a.appendChild(el('span', 'fu-cmdk-icon', it.icon || ''));
			var txt = el('span', 'fu-cmdk-txt');
			var title = el('span', 'fu-cmdk-title');
			mark(title, it.title, big ? w : []);
			txt.appendChild(title);
			if (it.sub) {
				var sub = el('span', 'fu-cmdk-sub');
				mark(sub, it.sub, w);
				txt.appendChild(sub);
			}
			a.appendChild(txt);
			a.appendChild(el('kbd', 'fu-kbd fu-cmdk-enter', ICON_ENTER));
			a.addEventListener('mousemove', function () {
				var i = items.indexOf(a);
				if (i !== active) { setActive(i, false); }
			});
			items.push(a);
			return a;
		}
		function section(label) {
			var h = el('div', 'fu-cmdk-sec');
			h.textContent = label;
			list.appendChild(h);
		}
		function render(data) {
			var q = data.q || '';
			var w = words(q);
			list.textContent = '';
			items = [];
			active = -1;
			(data.groups || []).forEach(function (g) {
				section(g.label);
				g.items.forEach(function (it) { list.appendChild(item(it, w, true)); });
			});
			if (q.length >= 2 && !(data.groups || []).length) {
				var none = el('div', 'fu-cmdk-none');
				none.textContent = L.none || 'No direct hits';
				list.appendChild(none);
			}
			if ((data.into || []).length) {
				section(q ? (L.searchIn || '%s').replace('%s', q) : (L.goTo || 'Search in'));
				data.into.forEach(function (it) { list.appendChild(item(it, [], false)); });
			}
			list.scrollTop = 0;
			setActive(0, false);
			// Enter schon vor der Antwort gedrückt: ersten Treffer öffnen
			if (pending) { var nt = pending.newTab; pending = null; go(items[0], nt); }
		}
		function load() {
			var q = input.value.trim();
			if (q === lastQ) { return; }
			lastQ = q;
			if (ctrl) { ctrl.abort(); }
			ctrl = window.AbortController ? new AbortController() : null;
			var my = ++seq;
			box.classList.add('fu-busy');
			fetch(url + '?q=' + encodeURIComponent(q), { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (my !== seq) { return; }
					box.classList.remove('fu-busy');
					if (data.error === 'notlogged') { window.location.href = data.login; return; }
					render(data);
				})
				.catch(function (e) { if (my === seq && e.name !== 'AbortError') { box.classList.remove('fu-busy'); } });
		}
		function go(a, newTab) {
			if (!a) { return; }
			if (newTab) { window.open(a.href, '_blank'); } else { close(); window.location.href = a.href; }
		}
		function open(text) {
			if (!back.hidden) { input.focus(); return; }
			prevFocus = document.activeElement;
			back.hidden = false;
			root.classList.add('fu-cmdk-open');
			if (typeof text === 'string') { input.value = text; }
			input.focus();
			input.select();
			load();
		}
		function close() {
			if (back.hidden) { return; }
			back.hidden = true;
			pending = null;
			root.classList.remove('fu-cmdk-open');
			if (prevFocus && prevFocus.focus) { try { prevFocus.focus(); } catch (e) { /* weg */ } }
		}

		input.addEventListener('input', function () {
			pending = null;
			clearTimeout(timer);
			timer = setTimeout(load, 140);
		});
		input.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				e.preventDefault();
				setActive(active + (e.key === 'ArrowDown' ? 1 : -1), true);
			} else if (e.key === 'Enter') {
				e.preventDefault();
				clearTimeout(timer);
				if (input.value.trim() !== lastQ || box.classList.contains('fu-busy')) {
					pending = { newTab: e.ctrlKey || e.metaKey };
					load();
					return;
				}
				go(items[active], e.ctrlKey || e.metaKey);
			} else if (e.key === 'Escape') {
				e.preventDefault();
				close();
			} else if (e.key === 'Tab') {
				e.preventDefault();
			}
		});
		list.addEventListener('click', function (e) {
			var a = e.target.closest && e.target.closest('a.fu-cmdk-item');
			if (a && !e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) { e.preventDefault(); go(a, false); }
		});
		back.addEventListener('mousedown', function (e) { if (e.target === back) { close(); } });

		// Dolibarrs Tastenkürzel (Alt+S, Strg+Umschalt+F) rufen openGlobalSearchDropDown() auf
		window.openGlobalSearchDropDown = function () { open(); };
		// ... und wer die Select2-Liste selbst öffnet (Klick im Menü), bekommt die Palette
		if (window.jQuery) {
			window.jQuery(document).on('select2:opening', '#searchselectcombo', function (e) {
				e.preventDefault();
				open();
			});
		}
		return { open: open, close: close };
	}

	// Listen auf Bildschirmhöhe: die (einzige) Liste einer Listenseite bekommt den
	// Platz bis zum Fensterrand und scrollt darin; Kopf- und Filterzeile kleben oben.
	function fitList() {
		var boxes = Array.prototype.filter.call(
			document.querySelectorAll('#id-right div.div-table-responsive'),
			function (d) {
				var t = d.querySelector(':scope > table.liste');
				return t && t.querySelector('tr.liste_titre');
			}
		);
		var wide = window.matchMedia('(min-width: 1001px)').matches;
		Array.prototype.forEach.call(document.querySelectorAll('.fu-listbox'), function (d) {
			if (!wide || boxes.length !== 1 || d !== boxes[0]) {
				d.classList.remove('fu-listbox');
				d.style.maxHeight = '';
			}
		});
		// Mehrere Listen auf einer Seite (z.B. Registerkarten mit Untertabellen): normal lassen
		if (!wide || boxes.length !== 1) { return; }
		var box = boxes[0];
		box.classList.add('fu-listbox');
		box.style.maxHeight = '';

		// Platz = Fensterhöhe minus alles oberhalb und unterhalb der Liste
		var top = box.getBoundingClientRect().top + window.pageYOffset;
		var below = document.documentElement.scrollHeight - (top + box.offsetHeight);
		below = Math.max(0, Math.min(below, window.innerHeight * 0.3));
		var h = Math.max(240, Math.floor(window.innerHeight - top - below));
		box.style.maxHeight = h + 'px';
		// Was trotzdem noch übersteht (Ränder, Abstände unter der Liste), von der Liste abziehen
		var over = document.documentElement.scrollHeight - window.innerHeight;
		if (over > 0 && h > 240) {
			box.style.maxHeight = Math.max(240, h - over) + 'px';
		}

		// Kopfzeilen (Filter- und Titelzeile) übereinander festkleben
		var offset = 0;
		var rows = box.querySelectorAll(':scope > table.liste > tbody > tr, :scope > table.liste > thead > tr, :scope > table.liste > tr');
		for (var i = 0; i < rows.length; i++) {
			var tr = rows[i];
			if (!tr.classList.contains('liste_titre') && !tr.classList.contains('liste_titre_filter')) { break; }
			Array.prototype.forEach.call(tr.children, function (cell) { cell.style.top = offset + 'px'; });
			offset += tr.offsetHeight;
		}
	}

	ready(function () {
		if (root.classList.contains('fu-login')) { initLogin(); return; }
		if (root.classList.contains('fu-listfit') && root.classList.contains('fu-layout')) {
			fitList();
			window.addEventListener('load', fitList);
			var t;
			window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(fitList, 120); });
		}
		if (!root.classList.contains('fu-on')) { return; }

		var palette = root.classList.contains('fu-palette') && window.FRESHUI && window.FRESHUI.search ? initPalette() : null;
		var top = document.getElementById('id-top');
		var left = document.getElementById('id-left');
		var hasLayout = root.classList.contains('fu-layout') && top;

		// Logo-Leiste über Icon-Leiste und Seitenmenü. Daneben ist noch Platz, da
		// landen unten Werkzeuge + Benutzermenü/Version - die Icon-Leiste bleibt frei.
		var brandbar = null;
		if (hasLayout && root.classList.contains('fu-brand') && window.FRESHUI && window.FRESHUI.logo) {
			brandbar = el('div', 'fu-brandbar');
			var home = el('a', 'fu-brandhome');
			home.href = window.FRESHUI.home || '/';
			home.title = window.FRESHUI.name || '';
			var img = el('img', 'fu-brandlogo');
			img.src = window.FRESHUI.logo;
			img.alt = window.FRESHUI.name || '';
			home.appendChild(img);
			brandbar.appendChild(home);
			// Dolibarrs globale Suche (Seitenmenü, #blockvmenusearch) in die Mitte der
			// Logo-Leiste: als Ganzes verschoben, damit Select2 und die Tastenkürzel
			// (Alt+S, Strg+Umschalt+F) unverändert weiter funktionieren.
			var center = el('div', 'fu-brandspacer');
			var search = document.getElementById('blockvmenusearch');
			// Nur auf breiten Bildschirmen - schmal ist die Logo-Leiste nicht gestaltet,
			// dort bleibt die Suche im (Hamburger-)Menü.
			if (search && window.matchMedia('(min-width: 1001px)').matches) {
				center.classList.add('fu-brandsearch');
				if (palette) {
					// Mit Such-Palette: eigenes Feld als Auslöser, das Original bleibt versteckt im Menü
					var trigger = el('button', 'fu-searchtrigger', ICON_SEARCH);
					var ph = el('span', 'fu-searchph');
					ph.textContent = L.searchPh || L.search || 'Search';
					var k = el('kbd', 'fu-kbd');
					k.textContent = 'Alt S';
					trigger.type = 'button';
					trigger.appendChild(ph);
					trigger.appendChild(k);
					trigger.addEventListener('click', function () { palette.open(); });
					center.appendChild(trigger);
					root.classList.add('fu-searchmoved');
				} else {
					center.appendChild(search);
				}
			}
			brandbar.appendChild(center);
			document.body.appendChild(brandbar);
		}

		// Werkzeuge: Dark Mode, Seitenmenü
		if (hasLayout) {
			var tools = el('div', 'fu-tools');
			var dark = el('button', 'fu-iconbtn', ICON_MOON);
			dark.type = 'button';
			dark.title = L.dark || 'Dark mode';
			dark.addEventListener('click', function () {
				var on = root.classList.toggle('fu-dark');
				store('freshui.theme', on ? 'dark' : 'light');
			});
			tools.appendChild(dark);

			if (left) {
				var coll = el('button', 'fu-iconbtn', ICON_PANEL);
				coll.type = 'button';
				coll.title = L.collapse || 'Side menu';
				coll.addEventListener('click', function () {
					var on = root.classList.toggle('fu-collapsed');
					store('freshui.collapsed', on ? '1' : '0');
				});
				tools.appendChild(coll);
			}
			var lb = top.querySelector('.login_block');
			if (brandbar) {
				// Neben das Logo in die Kopfleiste verschieben (nicht kopieren,
				// damit die Dropdown-Logik des Benutzermenüs erhalten bleibt) -
				// die Icon-Leiste bleibt dadurch komplett fürs Hauptmenü frei.
				brandbar.appendChild(tools);
				if (lb) { brandbar.appendChild(lb); }
			} else {
				top.appendChild(tools);
			}
		}

		// Seitenmenü: Titel + Filter
		if (!left) { return; }
		var sel = top && top.querySelector('li.tmenusel .mainmenuaspan');
		var bar = el('div', 'fu-leftbar');
		var head = el('div', 'fu-lefthead');
		var title = el('span', 'fu-lefttitle');
		title.textContent = sel ? sel.textContent : '';
		var kbd = el('kbd', 'fu-kbd');
		kbd.textContent = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Strg K';
		head.appendChild(title);
		head.appendChild(kbd);

		var input = el('input', 'fu-filter');
		input.type = 'search';
		input.autocomplete = 'off';
		input.placeholder = L.filter || 'Filter menu…';
		input.setAttribute('aria-label', input.placeholder);

		bar.appendChild(head);
		bar.appendChild(input);
		left.insertBefore(bar, left.firstChild);

		function applyFilter() {
			var q = norm(input.value).trim();
			var blocks = left.querySelectorAll('.blockvmenu');
			Array.prototype.forEach.call(blocks, function (b) {
				var t = b.querySelector('.menu_titre');
				var titleHit = !!q && !!t && norm(t.textContent).indexOf(q) > -1;
				var any = false;
				Array.prototype.forEach.call(b.querySelectorAll('.menu_contenu'), function (item) {
					var hit = !q || titleHit || norm(item.textContent).indexOf(q) > -1;
					item.classList.toggle('fu-hidden', !hit);
					if (hit) { any = true; }
				});
				b.classList.toggle('fu-hidden', !!q && !any && !titleHit);
			});
		}

		input.addEventListener('input', applyFilter);
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				input.value = '';
				applyFilter();
				input.blur();
			} else if (e.key === 'Enter') {
				var a = left.querySelector('.blockvmenu:not(.fu-hidden) .menu_contenu:not(.fu-hidden) a[href]');
				if (a) { e.preventDefault(); a.click(); }
			}
		});

		document.addEventListener('keydown', function (e) {
			if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'k' || e.key === 'K')) {
				e.preventDefault();
				if (root.classList.contains('fu-collapsed')) {
					root.classList.remove('fu-collapsed');
					store('freshui.collapsed', '0');
				}
				input.focus();
				input.select();
			}
		});
	});
})();
