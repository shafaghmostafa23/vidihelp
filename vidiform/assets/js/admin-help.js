/**
 * Help Center admin app — guides list, categories, guide editor, settings actions.
 * Mirrors the admin panel of the VidiForm Content Architecture HTML.
 * All data goes through the vidiform/v1 REST API (nonce + capability checked server-side).
 */
(function () {
	'use strict';

	var A = window.VFA, VF = window.VF;
	if (!A) { return; }
	var esc = A.esc;
	var page = document.querySelector('[data-screen]');
	if (!page) { return; }
	var screen = page.getAttribute('data-screen');
	var S = A.json('vf-help-state') || { cats: [], guides: [], features: [], settings: {} };

	var T = {
		guides: 'راهنما', minutes: 'دقیقه', edit: 'ویرایش', copy: 'کپی', view: 'نمای کاربر', del: 'حذف',
		draft: 'پیش‌نویس', publish: 'منتشرشده', pending: 'در انتظار بازبینی', private: 'خصوصی', future: 'زمان‌بندی‌شده',
		noGuides: 'راهنمایی با این شرایط پیدا نشد', noGuidesDesc: 'فیلتر یا عبارت جست‌وجو را تغییر دهید.',
		uncategorized: 'بدون دسته‌بندی', dragOff: 'برای جابه‌جایی، جست‌وجو و فیلتر را پاک کنید.',
		moved: 'راهنما جابه‌جا شد.', catOrder: 'ترتیب دسته‌های راهنما ذخیره شد.'
	};

	function fa(n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
	function catById(id) { for (var i = 0; i < S.cats.length; i++) { if (S.cats[i].id === id) { return S.cats[i]; } } return null; }
	function topCats() { return S.cats.filter(function (c) { return !c.parent; }); }
	function subsOf(id) { return S.cats.filter(function (c) { return c.parent === id; }); }
	function guideById(id) { for (var i = 0; i < S.guides.length; i++) { if (S.guides[i].id === id) { return S.guides[i]; } } return null; }
	function statusPill(st) {
		if (st === 'publish') { return '<span class="vf-pill vf-pill--success">' + T.publish + '</span>'; }
		return '<span class="vf-pill vf-pill--warn">' + esc(T[st] || st) + '</span>';
	}
	function icon(name) {
		var p = {
			drag: '<path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/>',
			play: '<rect x="3" y="4" width="18" height="14" rx="3"/><path d="M10 9l5 2.5-5 2.5z"/>'
		}[name];
		return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' + p + '</svg>';
	}

	/* ------------------------------------------------------------------
	 * Duplicate confirm (text from the source UI)
	 * ------------------------------------------------------------------ */
	function confirmDuplicate(kindLabel, name) {
		return A.confirm('کپی کامل ' + kindLabel,
			'<p>«' + esc(name) + '» با تمام محتوای داخلی کپی می‌شود:</p><ul><li>بخش‌ها، ویژگی‌ها و ترتیب دقیق آن‌ها</li><li>مدیا، ویدی‌فرم‌ها و ارجاع‌ها</li><li>ارتباط با دسته‌بندی‌ها</li><li>شناسه‌ی جدید و مستقل برای نسخه‌ی کپی</li></ul>',
			'ساخت کپی');
	}

	function newGuide(cat) {
		return A.api('POST', 'vidiform/v1/help/guides', { cat: cat || 0 }).then(function (r) { window.location.href = r.editUrl; }).catch(A.fail);
	}

	/* ==================================================================
	 * Screen: guides list
	 * ================================================================== */
	function screenGuides() {
		var root = page.querySelector('[data-root]');
		var search = page.querySelector('[data-search]');
		var filter = 'all';

		function visible(g) {
			var q = search.value.trim();
			if (filter !== 'all' && g.status !== filter && !(filter === 'draft' && g.status !== 'publish')) { return false; }
			return !q || g.title.indexOf(q) > -1;
		}

		function row(g, i, draggable) {
			var term = g.term && g.term !== g.cat ? catById(g.term) : null;
			return '<div class="vf-a-row" data-sort-id="' + g.id + '"' + (draggable ? ' draggable="true"' : '') + '>' +
				'<span class="vf-a-handle" tabindex="' + (draggable ? '0' : '-1') + '" role="button" aria-label="جابه‌جایی (Alt + فلش بالا/پایین)">' + icon('drag') + '</span>' +
				'<span class="vf-a-num">' + fa(i + 1) + '</span>' +
				'<span class="vf-a-row__title">' + esc(g.title) + '</span>' +
				(term ? '<span class="vf-pill">' + esc(term.name) + '</span>' : '') +
				(g.status !== 'publish' ? statusPill(g.status) : '') +
				'<span class="vf-a-meta">' + fa(g.read) + ' ' + T.minutes + '</span>' +
				'<span class="vf-a-row__actions">' +
				'<a class="vf-btn vf-btn--secondary vf-btn--xs" href="' + esc(g.editUrl) + '">' + T.edit + '</a>' +
				'<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-dup="' + g.id + '">' + T.copy + '</button>' +
				'<a class="vf-btn vf-btn--soft vf-btn--xs" href="' + esc(g.viewUrl) + '" target="_blank" rel="noopener">' + T.view + '</a>' +
				'<button type="button" class="vf-btn vf-btn--ghost-danger vf-btn--xs" data-del="' + g.id + '">' + T.del + '</button>' +
				'</span></div>';
		}

		function render() {
			var q = search.value.trim();
			var draggable = !q && filter === 'all';
			var html = '';
			var shown = 0;
			var groups = topCats().map(function (c) { return { id: c.id, name: c.name }; });
			if (S.guides.some(function (g) { return !g.cat; })) { groups.push({ id: 0, name: T.uncategorized }); }
			groups.forEach(function (c) {
				var list = S.guides.filter(function (g) { return g.cat === c.id; });
				var vis = list.filter(visible);
				shown += vis.length;
				if (!draggable && !vis.length) { return; }
				html += '<section class="vf-a-card vf-a-group"><div class="vf-a-group__head"><h2 class="vf-a-group__title">' + esc(c.name) + '</h2>' +
					(c.id ? '<button type="button" class="vf-btn vf-btn--link vf-btn--xs" data-new-in="' + c.id + '">+ ' + 'راهنما در این دسته' + '</button>' : '') + '</div>' +
					'<div class="vf-a-list" data-cat="' + c.id + '">';
				vis.forEach(function (g, i) { html += row(g, i, draggable && c.id); });
				html += '<div class="vf-a-dropend" data-sort-end></div></div></section>';
			});
			if (!shown && (q || filter !== 'all')) {
				html = '<div class="vf-a-empty"><p class="vf-a-empty__title">' + T.noGuides + '</p><p class="vf-a-empty__desc">' + T.noGuidesDesc + '</p></div>';
			} else if (!S.guides.length && !topCats().length) {
				html = '<div class="vf-a-empty"><p class="vf-a-empty__title">هنوز دسته‌بندی یا راهنمایی ساخته نشده است</p><p class="vf-a-empty__desc">از «دسته‌بندی راهنما» شروع کنید یا ساختار پیش‌فرض را از «تنظیمات» بارگذاری کنید.</p></div>';
			}
			if (!draggable && shown) { html = '<p class="vf-a-note">' + T.dragOff + '</p>' + html; }
			root.innerHTML = html;
			if (draggable) {
				root.querySelectorAll('.vf-a-list[data-cat]').forEach(function (list) {
					if (list.getAttribute('data-cat') === '0') { return; }
					A.sortable(list, 'guides', saveOrder);
				});
			}
		}

		function saveOrder() {
			var groups = [];
			root.querySelectorAll('.vf-a-list[data-cat]').forEach(function (list) {
				var cat = parseInt(list.getAttribute('data-cat'), 10);
				if (cat) { groups.push({ cat: cat, ids: A.ids(list) }); }
			});
			A.api('POST', 'vidiform/v1/help/guides/order', { groups: groups }).then(function (st) {
				S = st; render(); VF.toast(T.moved);
			}).catch(function (e) { A.fail(e); render(); });
		}

		page.addEventListener('click', function (e) {
			var t;
			if ((t = e.target.closest('[data-filter]'))) {
				filter = t.getAttribute('data-filter');
				page.querySelectorAll('[data-filter]').forEach(function (b) {
					var on = b === t; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
				});
				render();
			} else if ((t = e.target.closest('[data-action="new-guide"]'))) {
				t.disabled = true; newGuide(0);
			} else if ((t = e.target.closest('[data-new-in]'))) {
				newGuide(parseInt(t.getAttribute('data-new-in'), 10));
			} else if ((t = e.target.closest('[data-dup]'))) {
				var g = guideById(parseInt(t.getAttribute('data-dup'), 10));
				confirmDuplicate('راهنما', g.title).then(function (ok) {
					if (!ok) { return; }
					A.api('POST', 'vidiform/v1/help/guides/' + g.id + '/duplicate').then(function (r) {
						VF.toast('راهنما به‌طور کامل کپی شد؛ بخش‌ها، مدیا، هینت‌ها و ارجاع‌ها منتقل شدند.');
						window.location.href = r.editUrl;
					}).catch(A.fail);
				});
			} else if ((t = e.target.closest('[data-del]'))) {
				var d = guideById(parseInt(t.getAttribute('data-del'), 10));
				A.confirm('حذف راهنما', '<p>«' + esc(d.title) + '» به زباله‌دان منتقل می‌شود.</p>', T.del, true).then(function (ok) {
					if (!ok) { return; }
					A.api('DELETE', 'vidiform/v1/help/guides/' + d.id).then(function () {
						S.guides = S.guides.filter(function (x) { return x.id !== d.id; });
						render(); VF.toast('راهنما حذف شد.');
					}).catch(A.fail);
				});
			}
		});
		search.addEventListener('input', render);
		render();
	}

	/* ==================================================================
	 * Screen: help categories
	 * ================================================================== */
	function screenCats() {
		var root = page.querySelector('[data-root]');

		function count(c) { return S.guides.filter(function (g) { return g.cat === c.id; }).length; }

		function render() {
			var cats = topCats();
			if (!cats.length) {
				root.innerHTML = '<div class="vf-a-empty"><p class="vf-a-empty__title">هنوز دسته‌ای ساخته نشده است</p><p class="vf-a-empty__desc">با «+ دسته‌ی جدید» شروع کنید.</p></div>';
				return;
			}
			var html = '<div class="vf-a-list vf-a-list--cats" data-cats>';
			cats.forEach(function (c) {
				html += '<div class="vf-a-catrow" data-sort-id="' + c.id + '" draggable="true">' +
					'<span class="vf-a-handle" tabindex="0" role="button" aria-label="جابه‌جایی (Alt + فلش بالا/پایین)">' + icon('drag') + '</span>' +
					'<div class="vf-a-catbadge">' + (c.imageUrl ? '<img src="' + esc(c.imageUrl) + '" alt="">' : icon('play')) + '</div>' +
					'<div class="vf-a-catrow__body">' +
					'<label class="screen-reader-text" for="cn-' + c.id + '">نام دسته</label>' +
					'<input id="cn-' + c.id + '" class="vf-a-input vf-a-input--name" value="' + esc(c.name) + '" data-cat-field="name" data-id="' + c.id + '">' +
					'<label class="screen-reader-text" for="cd-' + c.id + '">توضیح دسته</label>' +
					'<textarea id="cd-' + c.id + '" class="vf-a-textarea vf-a-textarea--sm" rows="1" placeholder="توضیح کوتاه دسته (در کارت صفحه‌ی اصلی)" data-cat-field="desc" data-id="' + c.id + '">' + esc(c.desc) + '</textarea>' +
					'<div class="vf-a-catrow__meta"><span class="vf-a-meta">' + fa(count(c)) + ' ' + T.guides + '</span>';
				subsOf(c.id).forEach(function (s) {
					html += '<button type="button" class="vf-pill vf-pill--btn" data-sub="' + s.id + '" title="ویرایش زیر‌دسته">' + esc(s.name) + '</button>';
				});
				html += '<button type="button" class="vf-a-dashed-btn" data-add-sub="' + c.id + '">+ زیر‌دسته</button></div></div>' +
					'<div class="vf-a-catrow__side">' +
					'<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-upload="' + c.id + '">آپلود تصویر دسته</button>' +
					'<span class="vf-a-tiny">اندازه‌ی پیشنهادی: ۴۰۰ × ۴۰۰ پیکسل</span>' +
					'<span class="vf-a-catrow__links">' +
					(c.image ? '<button type="button" class="vf-btn vf-btn--link vf-btn--xs" data-clear-img="' + c.id + '">حذف تصویر</button>' : '') +
					'<a class="vf-btn vf-btn--link vf-btn--xs" href="' + esc(c.url) + '" target="_blank" rel="noopener">' + T.view + '</a>' +
					'<button type="button" class="vf-btn vf-btn--link vf-btn--xs" data-dup-cat="' + c.id + '">' + T.copy + '</button>' +
					'<button type="button" class="vf-btn vf-btn--link vf-btn--xs vf-text-danger" data-del-cat="' + c.id + '">' + T.del + '</button>' +
					'</span></div></div>';
			});
			html += '</div>';
			root.innerHTML = html;
			A.sortable(root.querySelector('[data-cats]'), 'cats', function () {
				A.api('POST', 'vidiform/v1/help/categories/order', { ids: A.ids(root.querySelector('[data-cats]')) }).then(function (st) {
					S = st; render(); VF.toast(T.catOrder);
				}).catch(A.fail);
			});
		}

		function update(id, body, msg) {
			return A.api('POST', 'vidiform/v1/help/categories/' + id, body).then(function (st) { S = st; render(); if (msg) { VF.toast(msg); } }).catch(A.fail);
		}

		function nameModal(title, value, okLabel, extraHtml) {
			return new Promise(function (resolve) {
				A.modal(title, '<label class="vf-a-field">نام<input class="vf-a-input" data-v value="' + esc(value) + '" data-autofocus></label>' + (extraHtml || '') +
					'<div class="vf-a-modal-actions"><button type="button" class="vf-btn vf-btn--primary" data-ok>' + esc(okLabel) + '</button></div>', function (body, close) {
					var inp = body.querySelector('[data-v]');
					var ok = function () { close(); resolve({ action: 'save', value: inp.value.trim() }); };
					body.querySelector('[data-ok]').addEventListener('click', ok);
					inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { ok(); } });
					var del = body.querySelector('[data-delete]');
					if (del) { del.addEventListener('click', function () { close(); resolve({ action: 'delete' }); }); }
				});
			});
		}

		root.addEventListener('change', function (e) {
			var f = e.target.closest('[data-cat-field]');
			if (!f) { return; }
			var body = {};
			body[f.getAttribute('data-cat-field')] = f.value;
			A.api('POST', 'vidiform/v1/help/categories/' + f.getAttribute('data-id'), body).then(function (st) { S = st; VF.toast(A.i18n.saved); }).catch(A.fail);
		});

		page.addEventListener('change', function (e) {
			var l = e.target.closest('[data-landing]');
			if (!l) { return; }
			var body = {};
			body[l.getAttribute('data-landing')] = l.value;
			A.api('POST', 'vidiform/v1/help/settings', body).then(function () { VF.toast('متن صفحه‌ی اصلی مرکز راهنما ذخیره شد.'); }).catch(A.fail);
		});

		page.addEventListener('click', function (e) {
			var t;
			if ((t = e.target.closest('[data-action="add-cat"]'))) {
				A.api('POST', 'vidiform/v1/help/categories', { name: '' }).then(function (st) {
					S = st; render(); VF.toast('دسته‌ی جدید اضافه شد.');
					var rows = root.querySelectorAll('.vf-a-input--name');
					if (rows.length) { rows[rows.length - 1].focus(); rows[rows.length - 1].select(); }
				}).catch(A.fail);
			} else if ((t = e.target.closest('[data-add-sub]'))) {
				var pid = parseInt(t.getAttribute('data-add-sub'), 10);
				nameModal('زیر‌دسته جدید', 'زیر‌دسته جدید', 'افزودن').then(function (r) {
					if (r.action !== 'save') { return; }
					A.api('POST', 'vidiform/v1/help/categories', { name: r.value, parent: pid }).then(function (st) { S = st; render(); VF.toast('زیر‌دسته اضافه شد.'); }).catch(A.fail);
				});
			} else if ((t = e.target.closest('[data-sub]'))) {
				var sub = catById(parseInt(t.getAttribute('data-sub'), 10));
				nameModal('ویرایش زیر‌دسته', sub.name, 'ذخیره', '<p class="vf-a-card__hint" style="margin-top:10px">با حذف زیر‌دسته، راهنماهای آن به دسته‌ی اصلی منتقل می‌شوند.</p><button type="button" class="vf-btn vf-btn--ghost-danger vf-btn--xs" data-delete>حذف زیر‌دسته</button>').then(function (r) {
					if (r.action === 'delete') {
						A.api('DELETE', 'vidiform/v1/help/categories/' + sub.id).then(function (st) { S = st; render(); VF.toast('زیر‌دسته حذف شد.'); }).catch(A.fail);
					} else if (r.value) {
						update(sub.id, { name: r.value }, A.i18n.saved);
					}
				});
			} else if ((t = e.target.closest('[data-upload]'))) {
				var cid = parseInt(t.getAttribute('data-upload'), 10);
				A.pickMedia('image', function (m) { update(cid, { image: m.id }, 'فایل آپلود شد.'); });
			} else if ((t = e.target.closest('[data-clear-img]'))) {
				update(parseInt(t.getAttribute('data-clear-img'), 10), { image: 0 }, A.i18n.saved);
			} else if ((t = e.target.closest('[data-dup-cat]'))) {
				var dc = catById(parseInt(t.getAttribute('data-dup-cat'), 10));
				confirmDuplicate('دسته‌بندی', dc.name).then(function (ok) {
					if (!ok) { return; }
					A.api('POST', 'vidiform/v1/help/categories/' + dc.id + '/duplicate').then(function (st) { S = st; render(); VF.toast('دسته‌بندی با تمام زیر‌دسته‌ها کپی شد.'); }).catch(A.fail);
				});
			} else if ((t = e.target.closest('[data-del-cat]'))) {
				var xc = catById(parseInt(t.getAttribute('data-del-cat'), 10));
				A.confirm('حذف دسته', '<p>دسته‌ی «' + esc(xc.name) + '» حذف می‌شود. راهنماهای آن حذف نمی‌شوند و «بدون دسته‌بندی» می‌مانند.</p>', T.del, true).then(function (ok) {
					if (!ok) { return; }
					A.api('DELETE', 'vidiform/v1/help/categories/' + xc.id).then(function (st) { S = st; render(); VF.toast('دسته حذف شد.'); }).catch(A.fail);
				});
			}
		});
		render();
	}

	/* ==================================================================
	 * Screen: guide editor
	 * ================================================================== */
	function screenEditor() {
		var root = page.querySelector('[data-root]');
		var G = A.json('vf-help-guide');
		var dirty = false;
		var saving = false;
		var uid = function () { return 's' + Math.random().toString(36).slice(2, 8); };
		var HINT = { blue: 'آبی', green: 'سبز', purple: 'بنفش' };

		function setDirty(v) { dirty = v; page.classList.toggle('is-dirty', v); }
		window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = A.i18n.unsaved; } });

		function featureName(id) {
			for (var i = 0; i < S.features.length; i++) { if (S.features[i].id === id) { return S.features[i].name; } }
			return 'بدون ویژگی';
		}
		function guideTitle(id) { var g = guideById(id); return g ? g.title : ''; }

		function chip(label, active, attrs) {
			return '<button type="button" class="vf-a-chip' + (active ? ' is-active' : '') + '" aria-pressed="' + (active ? 'true' : 'false') + '" ' + attrs + '>' + esc(label) + '</button>';
		}

		function sectionHtml(x, i) {
			var hint = x.hint;
			var media = x.media;
			var mediaLabel = media ? ({ image: 'تصویر', video: 'ویدیو', audio: 'صدا' })[media.type] : '';
			var h = '<div class="vf-a-sec" data-sort-id="' + esc(x.id) + '" draggable="true" data-i="' + i + '">' +
				'<div class="vf-a-sec__head"><span class="vf-a-handle" tabindex="0" role="button" aria-label="جابه‌جایی بخش (Alt + فلش بالا/پایین)">' + icon('drag') + '</span>' +
				'<span class="vf-a-num vf-a-num--lg">' + fa(i + 1) + '</span><span class="vf-a-sec__label">بخش راهنما</span>' +
				'<button type="button" class="vf-btn vf-btn--ghost-danger vf-btn--xs" data-sec-del="' + i + '">حذف</button></div>' +
				'<div class="vf-a-sec__body">' +
				'<input class="vf-a-input vf-a-input--bold" placeholder="عنوان بخش (اختیاری)" aria-label="عنوان بخش" data-s="' + i + '" data-k="title" value="' + esc(x.title) + '">' +
				'<textarea class="vf-a-textarea" rows="3" placeholder="متن بخش" aria-label="متن بخش" data-s="' + i + '" data-k="desc">' + esc(x.desc) + '</textarea>' +
				'<div class="vf-a-sec__tools"><button type="button" class="vf-a-dashed-btn" data-insert-ref="' + i + '">+ لینک درون‌متنی به راهنما</button><span class="vf-a-tiny">قالب: [[متن لینک|شناسه راهنما]] · پاراگراف‌ها با یک خط خالی جدا می‌شوند.</span></div>' +

				'<div class="vf-a-box vf-a-box--dashed"><div class="vf-a-box__title">مدیا</div>' +
				(media ? '<div class="vf-a-media"><span class="vf-a-media__type">' + mediaLabel + '</span><span class="vf-a-media__name" dir="ltr">' + esc(media.name || ('#' + media.id)) + '</span><button type="button" class="vf-a-x" data-media-clear="' + i + '" aria-label="حذف مدیا">×</button></div>' : '') +
				'<div class="vf-a-btnrow"><button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-media="image" data-i="' + i + '">آپلود تصویر</button>' +
				'<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-media="video" data-i="' + i + '">آپلود ویدیو</button>' +
				'<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-media="audio" data-i="' + i + '">آپلود صدا</button></div></div>' +

				'<div class="vf-a-box"><div class="vf-a-box__row"><span class="vf-a-box__title vf-grow">هینت</span>' +
				(hint ? '<span class="vf-a-colors" role="group" aria-label="رنگ هینت">' + ['blue', 'green', 'purple'].map(function (c) {
					return '<button type="button" class="vf-a-color vf-a-color--' + c + (hint.color === c ? ' is-active' : '') + '" data-hint-color="' + c + '" data-i="' + i + '" aria-pressed="' + (hint.color === c) + '" aria-label="' + HINT[c] + '"></button>';
				}).join('') + '<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-hint-del="' + i + '">حذف</button></span>' : '') + '</div>' +
				(hint ? '<div class="vf-a-stack"><input class="vf-a-input vf-a-input--sm vf-a-input--bold" placeholder="عنوان هینت" aria-label="عنوان هینت" data-s="' + i + '" data-k="hint.title" value="' + esc(hint.title) + '">' +
					'<textarea class="vf-a-textarea vf-a-textarea--sm" rows="2" placeholder="متن هینت" aria-label="متن هینت" data-s="' + i + '" data-k="hint.desc">' + esc(hint.desc) + '</textarea></div>' : '') +
				'<button type="button" class="vf-a-dashed-btn" data-hint-add="' + i + '">+ افزودن / بازنشانی هینت</button></div>' +

				'<div class="vf-a-split"><div class="vf-a-field vf-a-field--sm">ویژگی از کتابخانه<button type="button" class="vf-a-pick" data-feature-pick="' + i + '">' + esc(featureName(x.feature)) + '</button></div>' +
				'<div class="vf-a-field vf-a-field--sm">ویدی‌فرم مرتبط<input class="vf-a-input vf-a-input--sm" placeholder="عنوان ویدی‌فرم" data-s="' + i + '" data-k="vf.title" value="' + esc(x.vf ? x.vf.title : '') + '">' +
				'<input class="vf-a-input vf-a-input--sm vf-ltr" placeholder="https://vidiform.ir/f/…" aria-label="لینک ویدی‌فرم" data-s="' + i + '" data-k="vf.url" value="' + esc(x.vf ? x.vf.url : '') + '"></div></div>' +

				'<div class="vf-a-box"><div class="vf-a-box__title">ارجاع به قالب</div><div class="vf-a-split">' +
				'<label class="vf-a-field vf-a-field--sm">عنوان قالب<input class="vf-a-input vf-a-input--sm" data-s="' + i + '" data-k="tpl.title" value="' + esc(x.tpl ? x.tpl.title : '') + '"></label>' +
				'<label class="vf-a-field vf-a-field--sm">لینک مستقیم قالب<input class="vf-a-input vf-a-input--sm vf-ltr" placeholder="https://vidiform.ir/templates/…" data-s="' + i + '" data-k="tpl.url" value="' + esc(x.tpl ? x.tpl.url : '') + '"></label></div>' +
				'<label class="vf-a-field vf-a-field--sm">درباره‌ی قالب (در پیش‌نمایش)<textarea class="vf-a-textarea vf-a-textarea--sm" rows="2" data-s="' + i + '" data-k="tpl.desc">' + esc(x.tpl ? x.tpl.desc : '') + '</textarea></label>' +
				'<label class="vf-a-check"><input type="checkbox" data-s="' + i + '" data-k="tpl.premium"' + (x.tpl && x.tpl.premium ? ' checked' : '') + '> نیازمند اشتراک</label></div>' +

				'<div class="vf-a-box"><div class="vf-a-box__title">راهنمای درون‌متنی</div>' +
				(x.inline ? '<div class="vf-a-inline"><input class="vf-a-input vf-a-input--sm" placeholder="متن دکمه‌ی درون‌متن" aria-label="متن دکمه" data-s="' + i + '" data-k="inline.label" value="' + esc(x.inline.label) + '">' +
					'<button type="button" class="vf-a-pick vf-a-pick--sm" data-inline-pick="' + i + '">' + esc(guideTitle(x.inline.guide) || 'انتخاب راهنما') + '</button>' +
					'<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-inline-del="' + i + '">حذف</button></div>' : '') +
				'<button type="button" class="vf-a-dashed-btn" data-inline-add="' + i + '">+ افزودن راهنمای مرتبط</button></div>' +
				'</div></div>';
			return h;
		}

		function render() {
			var cat = G.term ? (catById(G.term) || {}) : {};
			var root_id = cat.parent ? cat.parent : (cat.id || 0);
			var subs = root_id ? subsOf(root_id) : [];
			var st = G.status;
			var html =
				'<div class="vf-a-edhead"><div class="vf-grow"><h1 class="vf-a-title">' + esc(G.title) + '</h1><div class="vf-a-route" dir="ltr">' + esc(G.route) + '</div></div>' +
				'<div class="vf-a-btnrow"><button type="button" class="vf-btn vf-btn--secondary" data-act="dup">کپی عمیق</button>' +
				'<a class="vf-btn vf-btn--secondary" href="' + esc(G.viewUrl) + '" target="_blank" rel="noopener">پیش‌نمایش کاربر</a>' +
				'<button type="button" class="vf-btn vf-btn--primary" data-act="save">ذخیره</button></div></div>' +

				'<section class="vf-a-card"><h2 class="vf-a-card__title">۱. دسته‌بندی راهنما</h2><p class="vf-a-card__hint">هر راهنما باید به یک دسته تعلق داشته باشد؛ انتخاب زیر‌دسته اختیاری است.</p>' +
				'<div class="vf-a-chips">' + topCats().map(function (c) { return chip(c.name, c.id === root_id, 'data-set-cat="' + c.id + '"'); }).join('') + '</div>' +
				(subs.length ? '<div class="vf-a-chips vf-a-chips--sub"><span class="vf-a-chips__label">زیر‌دسته:</span>' + subs.map(function (c) { return chip(c.name, c.id === G.term, 'data-set-sub="' + c.id + '"'); }).join('') + '</div>' : '') +
				(!topCats().length ? '<p class="vf-a-note">ابتدا از «دسته‌بندی راهنما» یک دسته بسازید.</p>' : '') + '</section>' +

				'<section class="vf-a-card"><h2 class="vf-a-card__title">۲. اطلاعات راهنما</h2><div class="vf-a-stack">' +
				'<label class="vf-a-field">عنوان<input class="vf-a-input" data-f="title" value="' + esc(G.title) + '" required></label>' +
				'<label class="vf-a-field">خلاصه (در فهرست و مطالب مرتبط)<textarea class="vf-a-textarea" rows="2" data-f="excerpt">' + esc(G.excerpt) + '</textarea></label>' +
				'<div class="vf-a-grid3"><label class="vf-a-field">زمان مطالعه (دقیقه، ۰ = خودکار)<input class="vf-a-input" type="number" min="0" max="240" data-f="readSet" value="' + esc(G.readSet) + '"></label>' +
				'<label class="vf-a-field">نامک (Slug)<input class="vf-a-input vf-ltr" data-f="slug" value="' + esc(G.slug) + '"></label>' +
				'<div class="vf-a-field">وضعیت<div class="vf-a-chips">' + chip(T.draft, st === 'draft', 'data-set-status="draft"') +
				(G.canPublish ? chip(T.publish, st === 'publish', 'data-set-status="publish"') : chip(T.pending, st === 'pending', 'data-set-status="pending"')) + '</div></div></div>' +
				'</div></section>' +

				'<section class="vf-a-card"><div class="vf-a-card__row"><div class="vf-grow"><h2 class="vf-a-card__title">۳. بخش‌های راهنما</h2>' +
				'<p class="vf-a-card__hint">عنوان، متن، مدیا، هینت، ویژگی، ویدی‌فرم، ارجاع به قالب و راهنمای درون‌متنی.</p></div>' +
				'<button type="button" class="vf-btn vf-btn--primary vf-btn--sm" data-act="add-sec">+ افزودن بخش</button></div>' +
				'<div class="vf-a-secs" data-secs>' + G.sections.map(sectionHtml).join('') + '</div>' +
				(!G.sections.length ? '<div class="vf-a-empty vf-a-empty--sm"><p class="vf-a-empty__title">این راهنما هنوز بخشی ندارد</p><p class="vf-a-empty__desc">با «افزودن بخش» شروع کنید.</p></div>' : '') +
				'</section>' +

				'<section class="vf-a-card"><h2 class="vf-a-card__title">۴. سئو</h2><p class="vf-a-card__hint">اگر خالی بماند، عنوان و خلاصه‌ی راهنما استفاده می‌شود. در صورت فعال بودن افزونه‌ی سئو، تنظیمات آن افزونه اولویت دارد.</p><div class="vf-a-stack">' +
				'<label class="vf-a-field">عنوان سئو<input class="vf-a-input" data-f="seoTitle" maxlength="120" value="' + esc(G.seoTitle) + '"></label>' +
				'<label class="vf-a-field">توضیحات متا<textarea class="vf-a-textarea" rows="2" maxlength="320" data-f="seoDesc">' + esc(G.seoDesc) + '</textarea></label></div></section>' +

				(G.canDelete ? '<div class="vf-a-danger"><button type="button" class="vf-btn vf-btn--ghost-danger vf-btn--sm" data-act="delete">انتقال راهنما به زباله‌دان</button></div>' : '');
			root.innerHTML = html;
			var secs = root.querySelector('[data-secs]');
			A.sortable(secs, 'secs', function () {
				var ids = A.ids(secs).map(String);
				G.sections.sort(function (a, b) { return ids.indexOf(String(a.id)) - ids.indexOf(String(b.id)); });
				setDirty(true); render();
			});
		}

		function setPath(obj, path, value) {
			var parts = path.split('.');
			if (parts.length === 2) {
				if (!obj[parts[0]]) { obj[parts[0]] = parts[0] === 'hint' ? { title: '', desc: '', color: 'purple' } : parts[0] === 'inline' ? { label: '', guide: 0 } : { title: '', url: '', desc: '', premium: false }; }
				obj[parts[0]][parts[1]] = value;
			} else {
				obj[path] = value;
			}
		}

		root.addEventListener('input', function (e) {
			var t = e.target;
			if (t.hasAttribute('data-f')) {
				G[t.getAttribute('data-f')] = t.type === 'number' ? (parseInt(t.value, 10) || 0) : t.value;
				if (t.getAttribute('data-f') === 'title') { root.querySelector('.vf-a-edhead .vf-a-title').textContent = t.value; }
				setDirty(true);
			} else if (t.hasAttribute('data-s')) {
				setPath(G.sections[+t.getAttribute('data-s')], t.getAttribute('data-k'), t.type === 'checkbox' ? t.checked : t.value);
				setDirty(true);
			}
		});
		root.addEventListener('change', function (e) {
			var t = e.target;
			if (t.type === 'checkbox' && t.hasAttribute('data-s')) {
				setPath(G.sections[+t.getAttribute('data-s')], t.getAttribute('data-k'), t.checked);
				setDirty(true);
			}
		});

		function pickerModal(title, items, onPick, opts) {
			opts = opts || {};
			A.modal(title, '<input class="vf-a-input" placeholder="جست‌وجو…" data-q data-autofocus aria-label="جست‌وجو"><div class="vf-a-options" data-list></div>' + (opts.footer || ''), function (body, close) {
				var list = body.querySelector('[data-list]');
				var q = body.querySelector('[data-q]');
				var draw = function () {
					var v = q.value.trim();
					var f = items.filter(function (it) { return !v || it.name.indexOf(v) > -1; });
					list.innerHTML = f.length ? f.map(function (it) {
						return '<button type="button" class="vf-a-option" data-id="' + it.id + '"><span class="vf-a-option__name">' + esc(it.name) + '</span>' + (it.desc ? '<span class="vf-a-option__desc">' + esc(it.desc) + '</span>' : '') + '</button>';
					}).join('') : '<div class="vf-a-empty vf-a-empty--sm"><p class="vf-a-empty__desc">' + esc(opts.empty || 'موردی پیدا نشد.') + '</p></div>';
				};
				q.addEventListener('input', draw);
				list.addEventListener('click', function (e) {
					var b = e.target.closest('[data-id]');
					if (b) { close(); onPick(parseInt(b.getAttribute('data-id'), 10)); }
				});
				if (opts.onMount) { opts.onMount(body, close, q); }
				draw();
			});
		}

		function guideItems() {
			return S.guides.filter(function (g) { return g.id !== G.id; }).map(function (g) { return { id: g.id, name: g.title, desc: g.status === 'publish' ? '' : (T[g.status] || g.status) }; });
		}

		function featurePicker(i) {
			pickerModal('انتخاب ویژگی از کتابخانه', S.features.map(function (f) { return { id: f.id, name: f.name, desc: f.desc }; }), function (id) {
				G.sections[i].feature = id; setDirty(true); render(); VF.toast('ویژگی اضافه شد.');
			}, {
				empty: 'ویژگی‌ای پیدا نشد.',
				footer: (G.sections[i].feature ? '<button type="button" class="vf-a-dashed-btn vf-a-dashed-btn--block" data-none>بدون ویژگی</button>' : '') + '<button type="button" class="vf-a-dashed-btn vf-a-dashed-btn--block" data-create>+ ساخت ویژگی جدید</button>',
				onMount: function (body, close, q) {
					var none = body.querySelector('[data-none]');
					if (none) { none.addEventListener('click', function () { close(); G.sections[i].feature = 0; setDirty(true); render(); }); }
					body.querySelector('[data-create]').addEventListener('click', function () {
						var name = q.value.trim();
						A.modal('ویژگی جدید', '<div class="vf-a-stack"><input class="vf-a-input" placeholder="عنوان ویژگی" data-n data-autofocus value="' + esc(name) + '" aria-label="عنوان ویژگی"><textarea class="vf-a-textarea" rows="4" placeholder="توضیحی که در پاپ‌آپ کاربر نمایش داده می‌شود" data-d aria-label="توضیح ویژگی"></textarea><button type="button" class="vf-btn vf-btn--primary vf-btn--h44" data-save>ذخیره و افزودن به بخش</button></div>', function (b2, close2) {
							b2.querySelector('[data-save]').addEventListener('click', function () {
								A.api('POST', 'vidiform/v1/help/features', { name: b2.querySelector('[data-n]').value, desc: b2.querySelector('[data-d]').value }).then(function (f) {
									S.features.push(f); G.sections[i].feature = f.id; setDirty(true); close2(); render(); VF.toast('ویژگی ساخته و به بخش اضافه شد.');
								}).catch(A.fail);
							});
						});
					});
				}
			});
		}

		function save() {
			if (saving) { return; }
			if (!String(G.title).trim()) { VF.toast('عنوان راهنما را وارد کنید.'); return; }
			saving = true;
			var btn = root.querySelector('[data-act="save"]');
			if (btn) { btn.disabled = true; }
			var body = {
				title: G.title, excerpt: G.excerpt, slug: G.slug, status: G.status, readSet: G.readSet, term: G.term,
				seoTitle: G.seoTitle, seoDesc: G.seoDesc,
				sections: G.sections.map(function (s) {
					return { id: s.id, title: s.title, desc: s.desc, media: s.media ? { id: s.media.id, type: s.media.type } : null, hint: s.hint, feature: s.feature || 0, vf: s.vf, tpl: s.tpl, inline: s.inline };
				})
			};
			A.api('POST', 'vidiform/v1/help/guides/' + G.id, body).then(function (res) {
				G = res; setDirty(false); render();
				var row = guideById(G.id);
				if (row) { row.title = G.title; row.status = G.status; row.term = G.term; }
				VF.toast('راهنما ذخیره شد؛ ترتیب بخش‌ها دقیقاً در صفحه‌ی کاربر اعمال می‌شود.');
			}).catch(A.fail).then(function () {
				saving = false;
				var b = root.querySelector('[data-act="save"]'); if (b) { b.disabled = false; }
			});
		}

		document.addEventListener('keydown', function (e) {
			if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); }
		});

		root.addEventListener('click', function (e) {
			var t = e.target.closest('button');
			if (!t) { return; }
			var i = t.hasAttribute('data-i') ? +t.getAttribute('data-i') : -1;
			var a;
			if ((a = t.getAttribute('data-act'))) {
				if (a === 'save') { save(); }
				if (a === 'add-sec') {
					G.sections.push({ id: uid(), title: 'بخش جدید', desc: '', media: null, hint: null, feature: 0, vf: null, tpl: null, inline: null });
					setDirty(true); render();
					var inputs = root.querySelectorAll('[data-k="title"]');
					if (inputs.length) { inputs[inputs.length - 1].focus(); inputs[inputs.length - 1].select(); }
				}
				if (a === 'dup') {
					confirmDuplicate('راهنما', G.title).then(function (ok) {
						if (!ok) { return; }
						var go = function () {
							A.api('POST', 'vidiform/v1/help/guides/' + G.id + '/duplicate').then(function (r) { setDirty(false); window.location.href = r.editUrl; }).catch(A.fail);
						};
						if (dirty) { VF.toast(A.i18n.unsaved); } else { go(); }
					});
				}
				if (a === 'delete') {
					A.confirm('حذف راهنما', '<p>«' + esc(G.title) + '» به زباله‌دان منتقل می‌شود.</p>', T.del, true).then(function (ok) {
						if (!ok) { return; }
						A.api('DELETE', 'vidiform/v1/help/guides/' + G.id).then(function () { setDirty(false); window.location.href = page.querySelector('.vf-a-back').href; }).catch(A.fail);
					});
				}
				return;
			}
			if (t.hasAttribute('data-set-cat')) { G.term = +t.getAttribute('data-set-cat'); setDirty(true); render(); return; }
			if (t.hasAttribute('data-set-sub')) {
				var sid = +t.getAttribute('data-set-sub');
				var c = catById(G.term);
				G.term = G.term === sid ? (c && c.parent ? c.parent : G.term) : sid;
				setDirty(true); render(); return;
			}
			if (t.hasAttribute('data-set-status')) { G.status = t.getAttribute('data-set-status'); setDirty(true); render(); return; }
			if (t.hasAttribute('data-sec-del')) {
				var di = +t.getAttribute('data-sec-del');
				A.confirm('حذف بخش', '<p>این بخش از راهنما حذف می‌شود.</p>', T.del, true).then(function (ok) {
					if (ok) { G.sections.splice(di, 1); setDirty(true); render(); }
				});
				return;
			}
			if (t.hasAttribute('data-media')) {
				var type = t.getAttribute('data-media');
				A.pickMedia(type, function (m) { G.sections[i].media = { id: m.id, type: type, name: m.name, url: m.url }; setDirty(true); render(); VF.toast('فایل آپلود شد.'); });
				return;
			}
			if (t.hasAttribute('data-media-clear')) { G.sections[+t.getAttribute('data-media-clear')].media = null; setDirty(true); render(); return; }
			if (t.hasAttribute('data-hint-add')) { G.sections[+t.getAttribute('data-hint-add')].hint = { title: 'عنوان هینت', desc: '', color: 'purple' }; setDirty(true); render(); return; }
			if (t.hasAttribute('data-hint-del')) { G.sections[+t.getAttribute('data-hint-del')].hint = null; setDirty(true); render(); return; }
			if (t.hasAttribute('data-hint-color')) { G.sections[i].hint.color = t.getAttribute('data-hint-color'); setDirty(true); render(); return; }
			if (t.hasAttribute('data-feature-pick')) { featurePicker(+t.getAttribute('data-feature-pick')); return; }
			if (t.hasAttribute('data-inline-add')) {
				var ii = +t.getAttribute('data-inline-add');
				pickerModal('انتخاب راهنمای مرتبط', guideItems(), function (id) {
					G.sections[ii].inline = { label: G.sections[ii].inline && G.sections[ii].inline.label ? G.sections[ii].inline.label : 'راهنمای مرتبط', guide: id };
					setDirty(true); render();
				});
				return;
			}
			if (t.hasAttribute('data-inline-pick')) {
				var ip = +t.getAttribute('data-inline-pick');
				pickerModal('انتخاب راهنمای مرتبط', guideItems(), function (id) { G.sections[ip].inline.guide = id; setDirty(true); render(); });
				return;
			}
			if (t.hasAttribute('data-inline-del')) { G.sections[+t.getAttribute('data-inline-del')].inline = null; setDirty(true); render(); return; }
			if (t.hasAttribute('data-insert-ref')) {
				var ri = +t.getAttribute('data-insert-ref');
				var ta = root.querySelector('textarea[data-s="' + ri + '"][data-k="desc"]');
				var start = ta.selectionStart, end = ta.selectionEnd;
				var sel = ta.value.slice(start, end);
				pickerModal('لینک درون‌متنی به راهنما', guideItems(), function (id) {
					var label = (sel || guideTitle(id)).replace(/[\[\]|]/g, '');
					var token = '[[' + label + '|' + id + ']]';
					G.sections[ri].desc = ta.value.slice(0, start) + token + ta.value.slice(end);
					setDirty(true); render();
				});
			}
		});

		render();
	}

	/* ==================================================================
	 * Screen: create a new guide (/admin.php?page=vf-help-guide)
	 * ================================================================== */
	function screenNewGuide() {
		newGuide(0);
	}

	/* ==================================================================
	 * Screen: settings (seed button)
	 * ================================================================== */
	function screenSettings() {
		page.addEventListener('click', function (e) {
			var t = e.target.closest('[data-action="seed"]');
			if (!t) { return; }
			t.disabled = true;
			A.api('POST', 'vidiform/v1/help/seed').then(function (r) {
				VF.toast(r.created ? ('ساختار پیش‌فرض بارگذاری شد (' + fa(r.created) + ' مورد جدید).') : 'همه‌ی موارد از قبل وجود داشتند.');
			}).catch(A.fail).then(function () { t.disabled = false; });
		});
	}

	({ guides: screenGuides, 'help-cats': screenCats, 'guide-editor': screenEditor, 'new-guide': screenNewGuide, 'help-settings': screenSettings }[screen] || function () {})();
})();
