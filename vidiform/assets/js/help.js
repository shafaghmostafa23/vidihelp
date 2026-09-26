/**
 * Help Center interactions: sidebar accordion, live search, section popups.
 * Progressive enhancement — every view works without JavaScript.
 */
(function () {
	'use strict';

	var VF = window.VF || {};
	var CFG = window.VF_HELP || { i18n: {} };

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	/* ---------- Sidebar accordion ---------- */
	document.addEventListener('click', function (e) {
		var caret = e.target.closest('.vf-hnav__caret');
		if (caret) {
			var list = document.getElementById(caret.getAttribute('aria-controls'));
			var open = caret.getAttribute('aria-expanded') !== 'true';
			caret.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (list) { list.hidden = !open; }
			return;
		}
		var mt = e.target.closest('.vf-hnav__mobile-toggle');
		if (mt) {
			var nav = mt.closest('.vf-hnav');
			var o = !nav.classList.contains('is-open');
			nav.classList.toggle('is-open', o);
			mt.setAttribute('aria-expanded', o ? 'true' : 'false');
		}
	});
	document.querySelectorAll('.vf-hnav').forEach(function (n) { n.classList.add('is-collapsible'); });

	/* ---------- Live search on the Help Center home ---------- */
	var form = document.querySelector('[data-vf-live-search]');
	var live = document.querySelector('[data-vf-live-results]');
	var grid = document.querySelector('[data-vf-grid]');
	if (form && live && grid && window.fetch) {
		var input = form.querySelector('input[name="q"]');
		var timer, ctrl, seq = 0;

		var render = function (q, items) {
			if (!items.length) {
				live.innerHTML = '<div class="vf-empty"><p class="vf-empty__title">' + esc(CFG.i18n.noResults || 'چیزی پیدا نشد') + '</p><p class="vf-empty__desc">' + esc(CFG.i18n.noResultsDesc || 'عبارت دیگری را امتحان کنید یا از دسته‌بندی‌های زیر شروع کنید.') + '</p></div>';
				grid.hidden = false; // Source UI: the empty state sits above the category grid.
				return;
			}
			var html = '<div class="vf-results__label">' + esc(CFG.i18n.results) + '</div>';
			items.forEach(function (it) {
				html += '<a class="vf-result" href="' + esc(it.url) + '"><span class="vf-result__main"><span class="vf-result__title">' + esc(it.title) + '</span>' +
					(it.category ? '<span class="vf-result__path">' + esc(it.category) + '</span>' : '') +
					'</span><span class="vf-result__read">' + esc(it.read) + '</span></a>';
			});
			live.innerHTML = html;
			grid.hidden = true;
		};

		var run = function () {
			var q = input.value.trim();
			if (ctrl) { ctrl.abort(); }
			if (!q) {
				live.hidden = true;
				live.innerHTML = '';
				grid.hidden = false;
				form.classList.remove('is-loading');
				return;
			}
			var my = ++seq;
			ctrl = window.AbortController ? new AbortController() : null;
			form.classList.add('is-loading');
			fetch(CFG.searchUrl + (CFG.searchUrl.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q), { signal: ctrl ? ctrl.signal : undefined, credentials: 'same-origin' })
				.then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
				.then(function (data) {
					if (my !== seq) { return; }
					live.hidden = false;
					render(q, data.items || []);
				})
				.catch(function (err) {
					if (err && err.name === 'AbortError') { return; }
					live.hidden = false;
					live.innerHTML = '<div class="vf-empty"><p class="vf-empty__title">' + esc(CFG.i18n.error) + '</p></div>';
				})
				.then(function () { if (my === seq) { form.classList.remove('is-loading'); } });
		};

		input.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(run, 220);
		});
	}

	/* ---------- Section popups ---------- */
	var dataEl = document.getElementById('vf-help-data');
	var DATA = { guides: {}, features: {} };
	if (dataEl) {
		try { DATA = JSON.parse(dataEl.textContent); } catch (err) { /* ignore */ }
	}

	document.addEventListener('click', function (e) {
		var ref = e.target.closest('.vf-inline-ref, .vf-inline-btn');
		if (ref) {
			var g = DATA.guides[ref.getAttribute('data-guide')];
			var m = document.getElementById('vf-pop-guide');
			if (!m) { return; }
			m.querySelector('.vf-modal__title').textContent = g ? g.title : ref.textContent;
			m.querySelector('[data-body]').textContent = g ? g.body : (DATA.notFound || '');
			var link = m.querySelector('[data-link]');
			link.hidden = !g;
			if (g) { link.setAttribute('href', g.url); }
			VF.openModal(m);
			return;
		}

		var fb = e.target.closest('.vf-feature-btn');
		if (fb) {
			var f = DATA.features[fb.getAttribute('data-feature')];
			var fm = document.getElementById('vf-pop-feature');
			if (!f || !fm) { return; }
			fm.querySelector('.vf-modal__title').textContent = f.name;
			fm.querySelector('[data-body]').textContent = f.desc;
			VF.openModal(fm);
			return;
		}

		var tb = e.target.closest('.vf-tpl-btn');
		if (tb) {
			var t;
			try { t = JSON.parse(tb.getAttribute('data-tpl')); } catch (err) { return; }
			var sm = document.getElementById('vf-pop-tpl');
			if (!sm) { return; }
			sm.querySelector('.vf-sheet__title').textContent = t.title || t.url;
			var badge = sm.querySelector('[data-badge]');
			badge.textContent = t.premium ? DATA.premium : DATA.free;
			badge.className = 'vf-pill ' + (t.premium ? 'vf-pill--premium' : 'vf-pill--success');
			sm.querySelector('[data-about]').textContent = t.desc || '';
			sm.querySelector('[data-about-wrap]').hidden = !t.desc;
			sm.querySelector('[data-url]').textContent = t.url || '';
			var frame = sm.querySelector('iframe');
			if (t.url && /^https?:\/\//i.test(t.url)) { frame.setAttribute('src', t.url); }
			var use = sm.querySelector('[data-use]');
			use.hidden = !t.url;
			if (t.url) { use.setAttribute('href', t.url); }
			VF.openModal(sm);
		}
	});
})();
