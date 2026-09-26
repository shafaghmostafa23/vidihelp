/**
 * Blog interactions: mobile menu, header search popover, copy-link share button.
 */
(function () {
	'use strict';

	var VF = window.VF || {};
	var CFG = window.VF_BLOG || {};

	document.addEventListener('click', function (e) {
		var menu = e.target.closest('.vf-blog-menu-btn');
		if (menu) {
			var nav = document.getElementById(menu.getAttribute('aria-controls'));
			var open = menu.getAttribute('aria-expanded') !== 'true';
			menu.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (nav) { nav.classList.toggle('is-open', open); }
			return;
		}

		var copy = e.target.closest('[data-vf-copy]');
		if (copy) {
			var url = copy.getAttribute('data-vf-copy');
			var done = function () { if (VF.toast) { VF.toast(CFG.copied || 'OK'); } };
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(done, function () { window.prompt('', url); });
			} else {
				window.prompt('', url);
			}
			return;
		}

		// Close the header search popover when clicking outside of it.
		document.querySelectorAll('.vf-blog-search-pop[open]').forEach(function (d) {
			if (!d.contains(e.target)) { d.removeAttribute('open'); }
		});
	});

	document.addEventListener('toggle', function (e) {
		if (e.target.classList && e.target.classList.contains('vf-blog-search-pop') && e.target.open) {
			var input = e.target.querySelector('input');
			if (input) { input.focus(); }
		}
	}, true);

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') { return; }
		document.querySelectorAll('.vf-blog-search-pop[open]').forEach(function (d) {
			d.removeAttribute('open');
			var s = d.querySelector('summary');
			if (s) { s.focus(); }
		});
	});
})();
