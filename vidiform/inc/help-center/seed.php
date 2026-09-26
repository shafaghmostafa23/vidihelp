<?php
/**
 * Default Help Center structure — imported on demand from the Help Center settings screen.
 * Content is taken verbatim from "VidiForm Content Architecture" (Help Center portion).
 * Existing categories/guides with the same slug are left untouched, so the import is idempotent.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Structure definition.
 *
 * @return array
 */
function vf_help_seed_data() {
	return array(
		'features' => array(
			'f1' => array( 'چند مرحله‌ای', 'مسیر این قالب از چند استپ ویدیویی تشکیل شده؛ مخاطب پس از هر ویدیو یک گزینه انتخاب می‌کند و به استپ بعدی می‌رود.' ),
			'f2' => array( 'منطق شرطی', 'هر گزینه می‌تواند مخاطب را به استپ متفاوتی هدایت کند، بنابراین هر نفر فقط محتوای مرتبط با نیاز خودش را می‌بیند.' ),
			'f3' => array( 'فرم تماس', 'در انتهای مسیر نام و شماره‌ی مخاطب دریافت می‌شود و در بخش «اطلاعات تماس» پنل شما ثبت می‌شود.' ),
			'f4' => array( 'دریافت عکس از مخاطب', 'مخاطب می‌تواند عکس بفرستد؛ فایل‌ها در پروفایل همان پاسخ‌دهنده ذخیره می‌شوند.' ),
			'f5' => array( 'اتصال به واتس‌اپ', 'در استپ پایانی مخاطب مستقیم به گفت‌وگوی واتس‌اپ شما هدایت می‌شود.' ),
			'f6' => array( 'گزارش نرخ تکمیل', 'می‌بینید مخاطب‌ها در کدام استپ مسیر را رها می‌کنند.' ),
		),
		'cats'     => array(
			array( 'start', 'شروع کار با ویدی‌فرم', 'اولین ویدی‌فرم خود را بسازید و منتشر کنید.', array() ),
			array( 'builder', 'ساخت استپ‌ها', 'انواع استپ و نحوه‌ی تنظیم هرکدام.', array( array( 'step-types', 'انواع استپ' ), array( 'advanced-settings', 'تنظیمات پیشرفته' ) ) ),
			array( 'logic', 'منطق شرطی و کنوس', 'مسیرهای شاخه‌ای بسازید و کنوس را مدیریت کنید.', array() ),
			array( 'video', 'ضبط ویدیوی خوب', 'نور، صدا و اسکریپت ویدیوی سلفی.', array() ),
			array( 'stats', 'آمار و پاسخ‌ها', 'بفهمید مخاطب در کدام استپ خارج می‌شود.', array() ),
			array( 'leads', 'مدیریت لیدها', 'پیگیری و خروجی گرفتن از اطلاعات تماس.', array() ),
		),
		// key, slug, title, category slug, sub slug, minutes, excerpt.
		'guides'   => array(
			array( 'g1', 'create-first-vidiform', 'ساخت اولین ویدی‌فرم', 'start', '', 6, 'از انتخاب قالب تا انتشار و گرفتن لینک، در شش دقیقه.' ),
			array( 'g2', 'publish-and-get-link', 'انتشار و گرفتن لینک', 'start', '', 3, 'لینک اختصاصی ویدی‌فرم و راه‌های اشتراک‌گذاری.' ),
			array( 'g3', 'descriptive-step', 'استپ تشریحی چطور تنظیم می‌شود؟', 'builder', 'step-types', 4, 'ویدیو، متن سؤال و پاسخ باز.' ),
			array( 'g4', 'multiple-choice-step', 'استپ چندگزینه‌ای و مسیر هر گزینه', 'builder', 'advanced-settings', 5, 'برای هر گزینه یک مسیر متفاوت تعریف کنید.' ),
			array( 'g5', 'conditional-path-in-canvas', 'تعریف مسیر شرطی در کنوس', 'logic', '', 7, 'اتصال استپ‌ها و بررسی مسیر در پیش‌نمایش.' ),
			array( 'g6', 'selfie-video-script', 'نوشتن اسکریپت ویدیوی سلفی', 'video', '', 5, 'الگوی آماده برای متن ویدیوی هر استپ.' ),
			array( 'g7', 'reading-completion-rate', 'خواندن نرخ تکمیل', 'stats', '', 4, 'بفهمید مخاطب کجا مسیر را رها می‌کند.' ),
			array( 'g8', 'export-leads-to-excel', 'خروجی اکسل از لیدها', 'leads', '', 2, 'فیلتر، جست‌وجو و خروجی گرفتن.' ),
		),
	);
}

/**
 * Import the default structure.
 *
 * @return int Number of created items.
 */
function vf_help_seed() {
	$data    = vf_help_seed_data();
	$created = 0;

	// Feature library.
	$feat = array();
	foreach ( $data['features'] as $key => $f ) {
		$t = term_exists( $f[0], 'help_feature' );
		if ( ! $t ) {
			$t = wp_insert_term( $f[0], 'help_feature', array( 'description' => $f[1] ) );
			if ( ! is_wp_error( $t ) ) {
				++$created;
			}
		}
		$feat[ $key ] = is_array( $t ) ? (int) $t['term_id'] : 0;
	}

	// Categories + sub-categories.
	$terms = array();
	foreach ( $data['cats'] as $i => $c ) {
		$t = get_term_by( 'slug', $c[0], 'help_category' );
		if ( ! $t ) {
			$r = wp_insert_term( $c[1], 'help_category', array( 'slug' => $c[0], 'description' => $c[2] ) );
			if ( is_wp_error( $r ) ) {
				continue;
			}
			update_term_meta( $r['term_id'], 'vf_order', $i );
			$t = get_term( $r['term_id'], 'help_category' );
			++$created;
		}
		$terms[ $c[0] ] = (int) $t->term_id;
		foreach ( $c[3] as $j => $sub ) {
			$s = get_term_by( 'slug', $sub[0], 'help_category' );
			if ( ! $s ) {
				$r = wp_insert_term( $sub[1], 'help_category', array( 'slug' => $sub[0], 'parent' => $t->term_id ) );
				if ( is_wp_error( $r ) ) {
					continue;
				}
				update_term_meta( $r['term_id'], 'vf_order', $j );
				$s = get_term( $r['term_id'], 'help_category' );
				++$created;
			}
			$terms[ $sub[0] ] = (int) $s->term_id;
		}
	}

	// Guides (first pass: create posts so inline references can point at real ids).
	$ids = array();
	foreach ( $data['guides'] as $i => $g ) {
		$existing = get_page_by_path( $g[1], OBJECT, 'help_article' );
		if ( $existing ) {
			$ids[ $g[0] ] = (int) $existing->ID;
			continue;
		}
		$id = wp_insert_post( wp_slash( array(
			'post_type'    => 'help_article',
			'post_status'  => 'publish',
			'post_title'   => $g[2],
			'post_name'    => $g[1],
			'post_excerpt' => $g[6],
			'menu_order'   => $i,
		) ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$term = $g[4] && isset( $terms[ $g[4] ] ) ? $terms[ $g[4] ] : ( $terms[ $g[3] ] ?? 0 );
		if ( $term ) {
			wp_set_object_terms( $id, array( $term ), 'help_category' );
		}
		update_post_meta( $id, '_vf_read_minutes', (int) $g[5] );
		$ids[ $g[0] ]         = (int) $id;
		$ids[ '_new_' . $g[0] ] = true;
		++$created;
	}

	// Second pass: sections.
	foreach ( $data['guides'] as $g ) {
		if ( empty( $ids[ '_new_' . $g[0] ] ) ) {
			continue;
		}
		$id = $ids[ $g[0] ];
		if ( 'g1' === $g[0] ) {
			$sections = array(
				array(
					'id'   => 'gs1',
					'desc' => 'اگر تازه وارد ویدی‌فرم شده‌اید، ساده‌ترین راه این است که از [[قالب‌ها|' . ( $ids['g2'] ?? 0 ) . ']] شروع کنید و بعد ویدیوهای آن را با ویدیوی خودتان جایگزین کنید. سپس [[تنظیمات استپ|' . ( $ids['g3'] ?? 0 ) . ']] را باز کنید و متن سؤال را بنویسید.',
				),
				array(
					'id'   => 'gs2',
					'hint' => array( 'title' => 'استپ چیست؟', 'desc' => 'هر استپ یک مرحله از مسیر است: یک ویدیو، یک متن سؤال و نوع پاسخ. مسیر مخاطب از اتصال همین استپ‌ها ساخته می‌شود.', 'color' => 'blue' ),
				),
				array(
					'id'    => 'gs3',
					'title' => '۱. انتخاب قالب مناسب',
					'desc'  => 'قالب‌ها بر اساس نوع کسب‌وکار دسته‌بندی شده‌اند. قالبی را انتخاب کنید که مسیرش به خدمت شما نزدیک باشد.',
					'tpl'   => array(
						'title'   => 'تست ترمیم فیلر لب',
						'url'     => 'https://vidiform.ir/f/lip',
						'desc'    => 'با تعداد بیشتری از مشتریان بالقوه در زمان کوتاه‌تری ارتباط برقرار کنید، اطلاعات آن‌ها را جمع‌آوری کنید و واجد شرایط بودن آن‌ها را برای کسب‌وکار خود تعیین کنید. این قالب برای کلینیک‌هایی طراحی شده که پیش از رزرو نوبت به یک ارزیابی کوتاه نیاز دارند.',
						'premium' => true,
					),
				),
				array(
					'id'      => 'gs4',
					'title'   => '۲. ضبط ویدیوی استپ شروع',
					'desc'    => 'خودتان را در ۱۵ ثانیه معرفی کنید و بگویید مخاطب با انتخاب گزینه‌ها چه چیزی به دست می‌آورد.',
					'hint'    => array( 'title' => 'طول مناسب ویدیو', 'desc' => 'ویدیوهای زیر ۳۰ ثانیه بیشترین نرخ تکمیل را دارند.', 'color' => 'green' ),
					'feature' => $feat['f1'],
					'inline'  => array( 'label' => 'راهنمای نوشتن اسکریپت ویدیو', 'guide' => $ids['g6'] ?? 0 ),
				),
				array(
					'id'    => 'gs5',
					'title' => '۳. انتشار و اشتراک‌گذاری',
					'desc'  => 'پس از انتشار، لینک ویدی‌فرم را در بایو یا استوری قرار دهید.',
					'vf'    => array( 'title' => 'ویدی‌فرم نمونه — معرفی محصول', 'url' => '' ),
				),
			);
		} else {
			$sections = array( array( 'id' => 's1', 'desc' => $g[6] ) );
		}
		$sections = vf_sanitize_sections( $sections );
		update_post_meta( $id, '_vf_sections', $sections );
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => vf_sections_to_content( $sections ) ) ) );
	}

	vf_help_bump();
	return $created;
}
