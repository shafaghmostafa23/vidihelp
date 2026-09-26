<?php
/**
 * Settings storage (WordPress Options API, one option per system) and sanitizers.
 *
 * Options:
 *  - vf_general : shared, site-level settings (panel link, front page, dates, theme mode)
 *  - vf_help    : Help Center settings
 *  - vf_blog    : Blog settings
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defaults for each option group.
 *
 * @return array
 */
function vf_settings_defaults() {
	return array(
		'general' => array(
			'panel_url'    => 'https://vidiform.ir/dashboard',
			'main_url'     => 'https://vidiform.ir',
			'front'        => 'help',
			'jalali'       => 1,
			'latin_digits' => 0,
			'theme_mode'   => 'system',
			'og_image'     => 0,
		),
		'help'    => array(
			'landing_title' => 'چطور می‌توانیم کمکتان کنیم؟',
			'landing_desc'  => 'صفر تا صد ساخت ویدی‌فرم هوشمند — از اولین استپ تا تحلیل پاسخ‌ها.',
			'per_page'      => 30,
			'home_items'    => 3,
			'related_count' => 3,
		),
		'blog'    => array(
			// Landing — hero.
			'eyebrow'        => 'بلاگ ویدی‌فرم',
			'hero_title'     => 'محتوایی برای کسب‌وکارهایی که از اینستاگرام مشتری می‌گیرند',
			'hero_desc'      => 'راهنماها، تحلیل‌ها و تجربه‌های واقعی درباره جذب سرنخ، فرم‌های ویدیویی و افزایش نرخ تبدیل — نوشته تیم ویدی‌فرم.',
			'btn1_text'      => 'ساخت فرم ویدیویی',
			'btn1_url'       => 'https://vidiform.ir/register',
			'btn2_text'      => 'آخرین مقالات',
			'btn2_url'       => '#latest',
			'media_type'     => 'image',
			'media_desktop'  => 0,
			'media_mobile'   => 0,
			'media_desktop_url' => '',
			'media_mobile_url'  => '',
			// Landing — articles.
			'show_featured'  => 1,
			'featured_post'  => 0,
			'latest_title'   => 'آخرین مقالات',
			'per_page'       => 6,
			'order'          => 'date_desc',
			'latest_cats'    => array(),
			// Landing — CTA.
			'cta_title'      => 'فرم ویدیویی خودتان را در ۱۰ دقیقه بسازید',
			'cta_desc'       => 'همان چیزی که در مقاله‌ها می‌خوانید، داخل ویدی‌فرم قابل ساخت است: سوال ویدیویی، منطق شرطی و اتصال به CRM.',
			'cta_btn1_text'  => 'ساخت فرم رایگان',
			'cta_btn1_url'   => 'https://vidiform.ir/register',
			'cta_btn2_text'  => 'دیدن قالب‌ها',
			'cta_btn2_url'   => 'https://vidiform.ir/templates',
			// Header / footer / appearance.
			'default_theme'  => 'dark',
			'header_cta_text' => 'شروع رایگان',
			'header_cta_url'  => 'https://vidiform.ir/register',
			'newsletter_url' => '',
			// Post page.
			'featured_count' => 3,
			'related_count'  => 3,
			'show_share'     => 1,
			'show_author'    => 1,
			'footer_text'    => 'فرم و پرسشنامه ویدیویی برای کسب‌وکارهایی که از اینستاگرام مشتری می‌گیرند.',
		),
	);
}

/**
 * Read a setting.
 *
 * @param string $group   general|help|blog.
 * @param string $key     Key.
 * @param mixed  $default Fallback when the group has no default.
 * @return mixed
 */
function vf_opt( $group, $key, $default = null ) {
	global $vf_opt_cache;
	if ( ! is_array( $vf_opt_cache ) ) {
		$vf_opt_cache = array();
	}
	if ( ! isset( $vf_opt_cache[ $group ] ) ) {
		$defaults               = vf_settings_defaults();
		$stored                 = get_option( 'vf_' . $group, array() );
		$vf_opt_cache[ $group ] = array_merge( isset( $defaults[ $group ] ) ? $defaults[ $group ] : array(), is_array( $stored ) ? $stored : array() );
	}
	return array_key_exists( $key, $vf_opt_cache[ $group ] ) ? $vf_opt_cache[ $group ][ $key ] : $default;
}

/**
 * Drop the in-request settings cache whenever one of our options changes.
 */
function vf_opt_flush() {
	$GLOBALS['vf_opt_cache'] = array();
}
foreach ( array( 'vf_general', 'vf_help', 'vf_blog' ) as $vf_option_name ) {
	add_action( 'update_option_' . $vf_option_name, 'vf_opt_flush' );
	add_action( 'add_option_' . $vf_option_name, 'vf_opt_flush' );
}
unset( $vf_option_name );

/**
 * Sanitize a settings group.
 *
 * @param string $group Group.
 * @param array  $input Raw input.
 * @return array
 */
function vf_sanitize_settings( $group, $input ) {
	$input    = is_array( $input ) ? wp_unslash( $input ) : array();
	$defaults = vf_settings_defaults();
	$current  = get_option( 'vf_' . $group, array() );
	$out      = array_merge( $defaults[ $group ], is_array( $current ) ? $current : array() );

	switch ( $group ) {
		case 'general':
			foreach ( array( 'panel_url', 'main_url' ) as $k ) {
				if ( isset( $input[ $k ] ) ) {
					$out[ $k ] = esc_url_raw( trim( $input[ $k ] ) );
				}
			}
			if ( isset( $input['front'] ) ) {
				$out['front'] = in_array( $input['front'], array( 'help', 'blog' ), true ) ? $input['front'] : 'help';
			}
			if ( isset( $input['theme_mode'] ) ) {
				$out['theme_mode'] = in_array( $input['theme_mode'], array( 'system', 'light', 'dark' ), true ) ? $input['theme_mode'] : 'system';
			}
			foreach ( array( 'jalali', 'latin_digits' ) as $k ) {
				$out[ $k ] = empty( $input[ $k ] ) ? 0 : 1;
			}
			if ( isset( $input['og_image'] ) ) {
				$out['og_image'] = absint( $input['og_image'] );
			}
			break;

		case 'help':
			if ( isset( $input['landing_title'] ) ) {
				$out['landing_title'] = sanitize_text_field( $input['landing_title'] );
			}
			if ( isset( $input['landing_desc'] ) ) {
				$out['landing_desc'] = sanitize_textarea_field( $input['landing_desc'] );
			}
			if ( isset( $input['per_page'] ) ) {
				$out['per_page'] = vf_clamp_int( $input['per_page'], 5, 100 );
			}
			if ( isset( $input['home_items'] ) ) {
				$out['home_items'] = vf_clamp_int( $input['home_items'], 1, 10 );
			}
			if ( isset( $input['related_count'] ) ) {
				$out['related_count'] = vf_clamp_int( $input['related_count'], 0, 12 );
			}
			break;

		case 'blog':
			foreach ( array( 'eyebrow', 'hero_title', 'btn1_text', 'btn2_text', 'latest_title', 'cta_title', 'cta_btn1_text', 'cta_btn2_text', 'header_cta_text', 'footer_text' ) as $k ) {
				if ( isset( $input[ $k ] ) ) {
					$out[ $k ] = sanitize_text_field( $input[ $k ] );
				}
			}
			foreach ( array( 'hero_desc', 'cta_desc' ) as $k ) {
				if ( isset( $input[ $k ] ) ) {
					$out[ $k ] = sanitize_textarea_field( $input[ $k ] );
				}
			}
			// URLs: absolute http(s), site-relative paths and in-page anchors (#latest).
			foreach ( array( 'btn1_url', 'btn2_url', 'cta_btn1_url', 'cta_btn2_url', 'header_cta_url', 'newsletter_url', 'media_desktop_url', 'media_mobile_url' ) as $k ) {
				if ( isset( $input[ $k ] ) ) {
					$v         = trim( (string) $input[ $k ] );
					$out[ $k ] = ( '' !== $v && '#' === $v[0] ) ? '#' . sanitize_title( substr( $v, 1 ) ) : esc_url_raw( $v, array( 'http', 'https' ) );
				}
			}
			foreach ( array( 'media_desktop', 'media_mobile', 'featured_post' ) as $k ) {
				if ( isset( $input[ $k ] ) ) {
					$out[ $k ] = absint( $input[ $k ] );
				}
			}
			if ( isset( $input['media_type'] ) ) {
				$out['media_type'] = in_array( $input['media_type'], array( 'image', 'video', 'vidiform' ), true ) ? $input['media_type'] : 'image';
			}
			if ( isset( $input['order'] ) ) {
				$out['order'] = array_key_exists( $input['order'], vf_blog_order_options() ) ? $input['order'] : 'date_desc';
			}
			if ( isset( $input['default_theme'] ) ) {
				$out['default_theme'] = in_array( $input['default_theme'], array( 'system', 'light', 'dark' ), true ) ? $input['default_theme'] : 'dark';
			}
			if ( isset( $input['per_page'] ) ) {
				$out['per_page'] = vf_clamp_int( $input['per_page'], 1, 48 );
			}
			if ( isset( $input['_form'] ) && 'landing' === $input['_form'] ) {
				$out['show_featured'] = empty( $input['show_featured'] ) ? 0 : 1;
				$out['latest_cats']   = isset( $input['latest_cats'] ) ? array_values( array_filter( array_map( 'absint', (array) $input['latest_cats'] ) ) ) : array();
			}
			if ( isset( $input['featured_count'] ) ) {
				$out['featured_count'] = vf_clamp_int( $input['featured_count'], 0, 6 );
			}
			if ( isset( $input['related_count'] ) ) {
				$out['related_count'] = vf_clamp_int( $input['related_count'], 0, 12 );
			}
			// Checkboxes are only present in the full settings form.
			if ( isset( $input['_form'] ) && 'settings' === $input['_form'] ) {
				foreach ( array( 'show_share', 'show_author' ) as $k ) {
					$out[ $k ] = empty( $input[ $k ] ) ? 0 : 1;
				}
			}
			break;
	}
	return $out;
}

/**
 * Register settings with the Settings API (used by the settings screens that post to options.php).
 */
function vf_register_settings() {
	register_setting( 'vf_general_group', 'vf_general', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $v ) {
			return vf_sanitize_settings( 'general', $v );
		},
	) );
	register_setting( 'vf_blog_group', 'vf_blog', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $v ) {
			return vf_sanitize_settings( 'blog', $v );
		},
	) );
	register_setting( 'vf_help_group', 'vf_help', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $v ) {
			return vf_sanitize_settings( 'help', $v );
		},
	) );
}
add_action( 'admin_init', 'vf_register_settings' );
add_action( 'rest_api_init', 'vf_register_settings' );

// Each system's settings group is guarded by that system's own capability.
add_filter( 'option_page_capability_vf_help_group', function () {
	return 'manage_help_center';
} );
add_filter( 'option_page_capability_vf_blog_group', function () {
	return 'manage_categories';
} );

/**
 * Ordering methods for the Blog landing article list.
 *
 * @return array<string,array> key => [orderby, order, label]
 */
function vf_blog_order_options() {
	return array(
		'date_desc' => array( 'date', 'DESC', __( 'تاریخ انتشار — جدیدترین', 'vidiform' ) ),
		'date_asc'  => array( 'date', 'ASC', __( 'تاریخ انتشار — قدیمی‌ترین', 'vidiform' ) ),
		'modified'  => array( 'modified', 'DESC', __( 'آخرین به‌روزرسانی', 'vidiform' ) ),
		'comments'  => array( 'comment_count', 'DESC', __( 'بیشترین دیدگاه', 'vidiform' ) ),
		'title'     => array( 'title', 'ASC', __( 'عنوان (الفبایی)', 'vidiform' ) ),
	);
}
