/* FreshUI - TakePOS-Design: gewählte Kategorie markieren, Kacheln ohne Produktfoto mit
 * Anfangsbuchstaben statt grauem Platzhalterbild. Eigenentwicklung, GPL v3+. */
(function () {
	'use strict';

	var root = document.documentElement;
	if (!root.classList.contains('fu-tp')) { return; }

	// Gewählte Kategorie (TakePOS selbst zeigt nicht, wo man gerade ist)
	document.addEventListener('click', function (e) {
		var tile = e.target.closest && e.target.closest('.div4 div.wrapper');
		if (!tile || tile.classList.contains('divempty') || !tile.querySelector('img.imgwrapper')) { return; }
		Array.prototype.forEach.call(document.querySelectorAll('.div4 div.wrapper.fu-active'), function (t) {
			t.classList.remove('fu-active');
		});
		tile.classList.add('fu-active');
	}, true);

	// Platzhalterbild erkennen: TakePOS leitet ohne Foto auf nophoto.png um. Das sieht man
	// der Bild-URL nicht an, also wird der Bildinhalt mit nophoto.png verglichen.
	var refData = null;
	var pending = [];

	function pixels(img) {
		try {
			var c = document.createElement('canvas');
			c.width = 32;
			c.height = 32;
			var ctx = c.getContext('2d');
			ctx.drawImage(img, 0, 0, 32, 32);
			return c.toDataURL();
		} catch (e) {
			return null;
		}
	}

	function initial(tile) {
		var d = tile.querySelector('.description_content');
		var t = d ? d.textContent.trim() : '';
		return t ? t.charAt(0).toUpperCase() : '';
	}

	function check(img) {
		var tile = img.closest && img.closest('div.wrapper, div.wrapper2');
		if (!tile) { return; }
		if (refData === null) { pending.push(img); return; }
		var isPlaceholder = !tile.classList.contains('divempty')
			&& img.naturalWidth === 128 && img.naturalHeight === 128
			&& refData !== false && pixels(img) === refData;
		tile.classList.toggle('fu-nophoto', isPlaceholder);
		if (isPlaceholder) {
			tile.setAttribute('data-initial', initial(tile));
		}
	}

	var ref = new Image();
	ref.onload = function () {
		refData = pixels(ref) || false;
		pending.splice(0).forEach(check);
	};
	ref.onerror = function () { refData = false; pending.splice(0).forEach(check); };
	// Pfad relativ zu /takepos/, wie TakePOS' eigene Umleitung (../../public/... von genimg/)
	ref.src = (window.location.pathname.replace(/\/takepos\/.*$/, '') || '') + '/public/theme/common/nophoto.png';

	document.addEventListener('load', function (e) {
		if (e.target && e.target.tagName === 'IMG' && e.target.classList.contains('imgwrapper')) { check(e.target); }
	}, true);

	// TakePOS setzt beim Blättern die Klassen der Kacheln neu: Markierung danach wiederherstellen
	function observe() {
		['.div4', '.div5'].forEach(function (sel) {
			var box = document.querySelector(sel);
			if (!box || !window.MutationObserver) { return; }
			new MutationObserver(function (list) {
				list.forEach(function (m) {
					var tile = m.target;
					if (m.attributeName !== 'class' || tile.classList.contains('fu-nophoto')) { return; }
					var img = tile.querySelector('img.imgwrapper');
					if (img && img.complete && img.naturalWidth) { check(img); }
				});
			}).observe(box, { attributes: true, attributeFilter: ['class'], subtree: true });
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', observe); } else { observe(); }
})();
