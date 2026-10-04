(function () {
	'use strict';
	var root = document.querySelector('.edit-post-layout, #postdivrich, #post-body');
	if (!root) return;
	var panel = document.createElement('section');
	panel.className = 'vf-a-infobox vf-editorial-counter';
	panel.setAttribute('aria-live', 'polite');
	panel.innerHTML = '<strong>شمارش متن مقاله</strong><div class="vf-editorial-counter__value">در حال آماده‌سازی…</div><label><input type="checkbox" class="vf-editorial-counter__spaces" checked> فاصله‌ها در شمارش لحاظ شوند</label><p class="vf-editorial-counter__note">هدف کاراکتر در جعبهٔ «استودیو محتوا — بریف و طبقه‌بندی» قابل تنظیم است. این راهنما مانع ذخیره یا انتشار نمی‌شود.</p><button type="button" class="button vf-editorial-image-marker">افزودن نشانگر تصویر</button>';
	var mount = document.querySelector('.edit-post-layout__metaboxes') || document.querySelector('#postbox-container-1') || document.querySelector('#post-body-content');
	if (mount) mount.prepend(panel);
	var value = panel.querySelector('.vf-editorial-counter__value');
	function visibleText(html) {
		var doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
		doc.querySelectorAll('script,style,template').forEach(function (el) { el.remove(); });
		return (doc.body.textContent || '').replace(/\u00a0/g, ' ');
	}
	function update() {
		var html = '';
		if (window.wp && wp.data && wp.data.select('core/editor')) {
			html = wp.data.select('core/editor').getEditedPostContent();
		} else {
			var editor = document.getElementById('content');
			html = editor ? editor.value : '';
		}
		var text = visibleText(html);
		if (!panel.querySelector('.vf-editorial-counter__spaces').checked) text = text.replace(/\s/g, '');
		var count = Array.from(text).length;
		var targetField = document.querySelector('[name="vf_editorial[char_target]"]');
		var target = targetField ? parseInt(targetField.value, 10) || 0 : 0;
		var suffix = target ? ' · هدف: ' + target.toLocaleString('fa-IR') + (count < target ? ' · پایین‌تر از هدف' : count > target * 1.15 ? ' · بالاتر از هدف' : ' · در محدودهٔ هدف') : ' · هدف تنظیم نشده';
		value.textContent = count.toLocaleString('fa-IR') + ' کاراکتر' + suffix;
	}
	panel.querySelector('.vf-editorial-counter__spaces').addEventListener('change', update);
	if (window.wp && wp.data && wp.data.subscribe) wp.data.subscribe(update);
	document.addEventListener('input', function (event) {
		if (event.target && (event.target.id === 'content' || event.target.name === 'vf_editorial[char_target]')) update();
	});
	panel.querySelector('.vf-editorial-image-marker').addEventListener('click', function () {
		var marker = '<!-- VidiForm image brief: edit description, reason, media type, alt text, caption, source/permission, and preparation status. -->\n<p><strong>[نیاز به تصویر]</strong> آنچه تصویر نشان می‌دهد: … | دلیل ارتباط با متن: … | نوع رسانه: … | متن جایگزین: … | کپشن: … | منبع/مجوز: … | وضعیت آماده‌سازی: نیازمند تهیه</p>';
		if (window.wp && wp.data && wp.blocks && wp.data.dispatch('core/editor')) {
			wp.data.dispatch('core/editor').insertBlocks(wp.blocks.createBlock('core/html', { content: marker }));
		} else {
			var editor = document.getElementById('content');
			if (editor) { editor.value += (editor.value ? '\n\n' : '') + marker; editor.dispatchEvent(new Event('input', { bubbles: true })); }
		}
		update();
	});
	update();
}());
