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

	ready(function () {
		if (root.classList.contains('fu-login')) { initLogin(); return; }
		if (!root.classList.contains('fu-on')) { return; }

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
			brandbar.appendChild(el('div', 'fu-brandspacer'));
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
