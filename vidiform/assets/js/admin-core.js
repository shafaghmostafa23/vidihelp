/**
 * VidiForm admin core: REST client (nonce-authenticated), escaping, confirm dialog,
 * modal helper, sortable lists (mouse drag + keyboard), media picker bridge.
 */
(function () {
	'use strict';

	var CFG = window.VF_ADMIN || { rest: '/wp-json/', nonce: '', i18n: {} };
	var VF = window.VF = window.VF || {};
	var A = window.VFA = {};

	A.i18n = CFG.i18n;

	A.esc = function (s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	};

	A.api = function (method, path, body) {
		var opts = {
			method: method,
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': CFG.nonce, 'Accept': 'application/json' }
		};
		if (body !== undefined) {
			opts.headers['Content-Type'] = 'application/json';
			opts.body = JSON.stringify(body);
		}
		var url = CFG.rest.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
		return fetch(url, opts).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (data) {
				if (!r.ok) {
					var err = new Error((data && data.message) || CFG.i18n.error);
					err.data = data;
					throw err;
				}
				return data;
			});
		});
	};

	A.fail = function (err) {
		VF.toast((err && err.message) || CFG.i18n.error);
	};

	A.json = function (id) {
		var el = document.getElementById(id);
		if (!el) { return null; }
		try { return JSON.parse(el.textContent); } catch (e) { return null; }
	};

	/* ---------- Generic modal (title + html body) ---------- */
	A.modal = function (title, html, onMount) {
		var m = document.getElementById('vf-a-modal');
		if (!m) { return null; }
		m.querySelector('.vf-modal__title').textContent = title;
		var body = m.querySelector('[data-modal-body]');
		body.innerHTML = html;
		VF.openModal(m);
		if (onMount) { onMount(body, function () { VF.closeModal(m); }); }
		return m;
	};

	A.confirm = function (title, html, okLabel, danger) {
		return new Promise(function (resolve) {
			var done = false;
			A.modal(title, '<div class="vf-a-confirm">' + html + '</div><div class="vf-a-modal-actions"><button type="button" class="vf-btn vf-btn--secondary" data-no>' + A.esc(CFG.i18n.cancel) + '</button><button type="button" class="vf-btn ' + (danger ? 'vf-btn--danger' : 'vf-btn--primary') + '" data-yes data-autofocus>' + A.esc(okLabel) + '</button></div>', function (body, close) {
				body.querySelector('[data-no]').addEventListener('click', function () { done = true; close(); resolve(false); });
				body.querySelector('[data-yes]').addEventListener('click', function () { done = true; close(); resolve(true); });
				var m = document.getElementById('vf-a-modal');
				var obs = new MutationObserver(function () { if (m.hidden) { obs.disconnect(); if (!done) { resolve(false); } } });
				obs.observe(m, { attributes: true, attributeFilter: ['hidden'] });
			});
		});
	};

	/* ---------- Media Library bridge ---------- */
	A.pickMedia = function (type, cb) {
		if (!window.wp || !wp.media) {
			VF.toast(CFG.i18n.error);
			return;
		}
		var frame = wp.media({ library: type ? { type: type } : {}, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			cb({ id: a.id, url: a.url, name: a.filename || a.title, type: a.type, thumb: (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url });
		});
		frame.open();
	};

	/* ---------- Sortable (drag & drop + keyboard) ----------
	 * container: element holding [data-sort-id] rows. group: string; lists with the same group
	 * accept each other's items. onDrop(ids per list) is called after a change.
	 */
	var drag = null;
	A.sortable = function (container, group, onChange) {
		container.setAttribute('data-sort-group', group);
		container.addEventListener('dragstart', function (e) {
			var row = e.target.closest('[data-sort-id]');
			if (!row || row.parentElement !== container) { return; }
			drag = { row: row, group: group, from: container };
			row.classList.add('is-dragging');
			if (e.dataTransfer) {
				e.dataTransfer.effectAllowed = 'move';
				try { e.dataTransfer.setData('text/plain', row.getAttribute('data-sort-id')); } catch (x) { /* ignore */ }
			}
			e.stopPropagation();
		});
		container.addEventListener('dragover', function (e) {
			if (!drag || drag.group !== group) { return; }
			e.preventDefault();
			e.stopPropagation();
			var over = e.target.closest('[data-sort-id]');
			container.classList.add('is-drop-target');
			if (over && over !== drag.row && over.parentElement === container) {
				var rect = over.getBoundingClientRect();
				var after = (e.clientY - rect.top) > rect.height / 2;
				container.insertBefore(drag.row, after ? over.nextSibling : over);
			} else if (!over) {
				var end = container.querySelector('[data-sort-end]');
				if (end) { container.insertBefore(drag.row, end); } else { container.appendChild(drag.row); }
			}
		});
		container.addEventListener('dragleave', function () { container.classList.remove('is-drop-target'); });
		container.addEventListener('drop', function (e) {
			if (!drag || drag.group !== group) { return; }
			e.preventDefault();
			e.stopPropagation();
		});
		container.addEventListener('dragend', function () {
			// The row may now live in another list of the same group; whichever list receives
			// the bubbling dragend first finalizes the move.
			if (!drag || drag.group !== group) { return; }
			var d = drag;
			drag = null;
			d.row.classList.remove('is-dragging');
			document.querySelectorAll('.is-drop-target').forEach(function (n) { n.classList.remove('is-drop-target'); });
			onChange(d.row);
		});
		// Keyboard: Alt+ArrowUp / Alt+ArrowDown on a focused row's handle.
		container.addEventListener('keydown', function (e) {
			if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) { return; }
			var row = e.target.closest('[data-sort-id]');
			if (!row || row.parentElement !== container) { return; }
			e.preventDefault();
			var sib = e.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
			if (!sib || !sib.hasAttribute('data-sort-id')) { return; }
			container.insertBefore(row, e.key === 'ArrowUp' ? sib : sib.nextSibling);
			e.target.focus();
			onChange(row);
		});
	};
	A.ids = function (container) {
		return Array.prototype.map.call(container.querySelectorAll(':scope > [data-sort-id]'), function (n) { return parseInt(n.getAttribute('data-sort-id'), 10) || n.getAttribute('data-sort-id'); });
	};

	/* ---------- Mobile sidebar toggle ---------- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('.vf-app__menu-btn');
		if (!b) { return; }
		var side = b.closest('.vf-app__side');
		var open = !side.classList.contains('is-open');
		side.classList.toggle('is-open', open);
		b.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
})();
