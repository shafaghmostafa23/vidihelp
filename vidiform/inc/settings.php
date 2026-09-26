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
			'hero_title'     => 'وبلاگ ویدی‌فرم',
			'hero_desc'      => 'مقاله‌ها، تجربه‌ها و راهکارهای فروش آنلاین با ویدی‌فرم‌های تعاملی.',
			'featured_count' => 3,
			'related_count'  => 3,
			'show_share'     => 1,
			'show_author'    => 1,
			'footer_text'    => 'ویدی‌فرم — انقلاب در فروش آنلاین.',
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
			if ( isset( $input['hero_title'] ) ) {
				$out['hero_title'] = sanitize_text_field( $input['hero_title'] );
			}
			if ( isset( $input['hero_desc'] ) ) {
				$out['hero_desc'] = sanitize_textarea_field( $input['hero_desc'] );
			}
			if ( isset( $input['footer_text'] ) ) {
				$out['footer_text'] = sanitize_text_field( $input['footer_text'] );
			}
			if ( isset( $input['featured_count'] ) ) {
				$out['featured_count'] = vf_clamp_int( $input['featured_count'], 0, 6 );
			}
			if ( isset( $input['related_count'] ) ) {
				$out['related_count'] = vf_clamp_int( $input['related_count'], 0, 12 );
			}
			// Checkboxes are only present in the full settings form.
			if ( isset( $input['_form'] ) ) {
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
