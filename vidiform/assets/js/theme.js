/**
 * VidiForm shared front-end runtime: light/dark toggle, accessible modals, toast.
 * No dependencies.
 */
(function () {
	'use strict';

	var root = document.documentElement;
	var VF = window.VF = window.VF || {};

	/* ---------- Theme toggle ---------- */
	function applyTheme(t) {
		root.setAttribute('data-theme', t);
		document.querySelectorAll('.vf-theme-toggle').forEach(function (b) {
			b.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
		});
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.vf-theme-toggle');
		if (!btn) { return; }
		var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
		try { localStorage.setItem('vf-theme', next); } catch (err) { /* private mode */ }
		applyTheme(next);
	});
	applyTheme(root.getAttribute('data-theme') || 'light');

	/* ---------- Toast ---------- */
	var toastTimer;
	VF.toast = function (msg) {
		var el = document.getElementById('vf-toast');
		if (!el) {
			el = document.createElement('div');
			el.id = 'vf-toast';
			el.className = 'vf-toast';
			el.setAttribute('role', 'status');
			el.setAttribute('aria-live', 'polite');
			document.body.appendChild(el);
		}
		el.textContent = msg;
		el.hidden = false;
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { el.hidden = true; }, 2800);
	};

	/* ---------- Modals (dialog pattern with focus trap) ---------- */
	var lastFocus = null;
	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, iframe, [tabindex]:not([tabindex="-1"])';

	VF.openModal = function (modal) {
		if (!modal) { return; }
		lastFocus = document.activeElement;
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		var f = modal.querySelector('[data-autofocus]') || modal.querySelector(FOCUSABLE);
		if (f) { setTimeout(function () { f.focus(); }, 20); }
	};
	VF.closeModal = function (modal) {
		if (!modal || modal.hidden) { return; }
		modal.hidden = true;
		document.body.style.overflow = '';
		var frame = modal.querySelector('iframe[data-src]');
		if (frame) { frame.removeAttribute('src'); }
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	};

	document.addEventListener('click', function (e) {
		var closer = e.target.closest('[data-vf-close]');
		if (closer) {
			VF.closeModal(closer.closest('.vf-modal'));
			return;
		}
		// Click on the overlay itself (outside the box) closes, as in the source UI.
		if (e.target.classList && e.target.classList.contains('vf-modal')) {
			VF.closeModal(e.target);
		}
	});

	document.addEventListener('keydown', function (e) {
		var open = Array.prototype.slice.call(document.querySelectorAll('.vf-modal:not([hidden])')).pop();
		if (!open) { return; }
		if (e.key === 'Escape') {
			VF.closeModal(open);
			return;
		}
		if (e.key === 'Tab') {
			var items = Array.prototype.filter.call(open.querySelectorAll(FOCUSABLE), function (n) { return n.offsetParent !== null; });
			if (!items.length) { return; }
			var first = items[0], last = items[items.length - 1];
			if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});
})();
