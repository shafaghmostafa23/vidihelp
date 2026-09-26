/**
 * Blog admin app: featured toggle + trash on the posts list, category/tag create/edit/delete.
 * Uses the WordPress core REST API (wp/v2) with the REST nonce; core enforces capabilities.
 */
(function () {
	'use strict';

	var A = window.VFA, VF = window.VF;
	if (!A) { return; }
	var esc = A.esc;
	var page = document.querySelector('[data-screen]');
	if (!page) { return; }
	var screen = page.getAttribute('data-screen');

	/* ---------- Posts ---------- */
	if (screen === 'blog-posts') {
		page.addEventListener('click', function (e) {
			var star = e.target.closest('[data-feature-toggle]');
			if (star) {
				var on = star.getAttribute('aria-pressed') !== 'true';
				star.disabled = true;
				A.api('POST', 'wp/v2/posts/' + star.getAttribute('data-feature-toggle'), { meta: { _vf_featured: on } }).then(function () {
					star.setAttribute('aria-pressed', on ? 'true' : 'false');
					VF.toast(on ? 'نوشته ویژه شد و در صفحه‌ی اصلی وبلاگ نمایش داده می‌شود.' : 'نوشته از ویژه‌ها خارج شد.');
				}).catch(A.fail).then(function () { star.disabled = false; });
				return;
			}
			var tr = e.target.closest('[data-trash]');
			if (tr) {
				A.confirm('حذف نوشته', '<p>«' + esc(tr.getAttribute('data-title')) + '» به زباله‌دان منتقل می‌شود.</p>', 'حذف', true).then(function (ok) {
					if (!ok) { return; }
					A.api('DELETE', 'wp/v2/posts/' + tr.getAttribute('data-trash')).then(function () {
						var row = tr.closest('tr');
						if (row) { row.remove(); }
						VF.toast('نوشته به زباله‌دان منتقل شد.');
					}).catch(A.fail);
				});
			}
		});
	}

	/* ---------- Categories / tags ---------- */
	if (screen === 'blog-terms') {
		var rest = page.getAttribute('data-rest');
		var hier = page.getAttribute('data-hier') === '1';
		var parents = A.json('vf-blog-parents') || [];

		var form = function (t) {
			var opts = '<option value="0">— بدون والد —</option>' + parents.filter(function (p) { return !t || p.id !== t.id; }).map(function (p) {
				return '<option value="' + p.id + '"' + (t && t.parent === p.id ? ' selected' : '') + '>' + esc(p.name) + '</option>';
			}).join('');
			return '<div class="vf-a-stack">' +
				'<label class="vf-a-field">نام<input class="vf-a-input" data-k="name" value="' + esc(t ? t.name : '') + '" data-autofocus required></label>' +
				'<label class="vf-a-field">نامک (اختیاری)<input class="vf-a-input vf-ltr" data-k="slug" value="' + esc(t ? t.slug : '') + '"></label>' +
				(hier ? '<label class="vf-a-field">دسته‌ی والد<select class="vf-a-select" data-k="parent">' + opts + '</select></label>' : '') +
				'<label class="vf-a-field">توضیحات (در صفحه‌ی آرشیو نمایش داده می‌شود)<textarea class="vf-a-textarea" rows="3" data-k="description">' + esc(t ? t.description : '') + '</textarea></label>' +
				'<button type="button" class="vf-btn vf-btn--primary vf-btn--h44" data-save>ذخیره</button></div>';
		};

		var open = function (t) {
			A.modal(t ? 'ویرایش' : (hier ? 'دسته‌ی جدید' : 'برچسب جدید'), form(t), function (body, close) {
				body.querySelector('[data-save]').addEventListener('click', function () {
					var data = {};
					body.querySelectorAll('[data-k]').forEach(function (f) { data[f.getAttribute('data-k')] = f.getAttribute('data-k') === 'parent' ? parseInt(f.value, 10) : f.value; });
					if (!data.name.trim()) { VF.toast('نام را وارد کنید.'); return; }
					if (!data.slug) { delete data.slug; }
					A.api('POST', 'wp/v2/' + rest + (t ? '/' + t.id : ''), data).then(function () {
						close(); VF.toast(A.i18n.saved); window.location.reload();
					}).catch(A.fail);
				});
			});
		};

		page.addEventListener('click', function (e) {
			if (e.target.closest('[data-term-new]')) { open(null); return; }
			var ed = e.target.closest('[data-term-edit]');
			if (ed) { open(JSON.parse(ed.getAttribute('data-term-edit'))); return; }
			var del = e.target.closest('[data-term-del]');
			if (del) {
				A.confirm('حذف', '<p>«' + esc(del.getAttribute('data-name')) + '» حذف می‌شود. نوشته‌ها حذف نمی‌شوند.</p>', 'حذف', true).then(function (ok) {
					if (!ok) { return; }
					A.api('DELETE', 'wp/v2/' + rest + '/' + del.getAttribute('data-term-del') + '?force=true').then(function () {
						var row = del.closest('tr'); if (row) { row.remove(); }
						VF.toast('حذف شد.');
					}).catch(A.fail);
				});
			}
		});
	}
})();
