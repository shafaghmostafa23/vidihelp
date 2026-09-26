<?php
/**
 * Generic helpers shared by both systems.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Convert Latin digits to Persian digits.
 *
 * @param string|int $value Value.
 * @return string
 */
function vf_fa_digits( $value ) {
	return strtr( (string) $value, array(
		'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
		'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
	) );
}

/**
 * Convert Persian/Arabic digits to Latin digits.
 *
 * @param string $value Value.
 * @return string
 */
function vf_en_digits( $value ) {
	return strtr( (string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

/**
 * Whether dates/digits should be localized to Persian.
 * Follows the site locale; filterable.
 *
 * @return bool
 */
function vf_is_persian() {
	return (bool) apply_filters( 'vf_is_persian', 0 === strpos( determine_locale(), 'fa' ) || ! vf_opt( 'general', 'latin_digits' ) );
}

/**
 * Localize a number for display.
 *
 * @param int|string $n Number.
 * @return string
 */
function vf_num( $n ) {
	return vf_is_persian() ? vf_fa_digits( $n ) : (string) $n;
}

/**
 * Gregorian → Jalali conversion.
 *
 * @param int $gy Year.
 * @param int $gm Month.
 * @param int $gd Day.
 * @return int[] [jy, jm, jd]
 */
function vf_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
	$days %= 12053;
	$jy   += 4 * intdiv( $days, 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		$jy  += intdiv( $days - 1, 365 );
		$days = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + intdiv( $days, 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + intdiv( $days - 186, 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/**
 * Display date for a post: Jalali in Persian, site date format otherwise.
 *
 * @param int|WP_Post|null $post     Post.
 * @param bool             $modified Use modified date.
 * @return string
 */
function vf_post_date( $post = null, $modified = false ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$ts = $modified ? get_post_modified_time( 'U', false, $post ) : get_post_time( 'U', false, $post );
	return vf_format_date( (int) $ts );
}

/**
 * Format a timestamp (site local time).
 *
 * @param int $ts Timestamp (already local, as returned by get_post_time with gmt=false).
 * @return string
 */
function vf_format_date( $ts ) {
	if ( ! vf_is_persian() || ! vf_opt( 'general', 'jalali', 1 ) ) {
		return date_i18n( get_option( 'date_format' ), $ts );
	}
	$months = array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	list( $jy, $jm, $jd ) = vf_gregorian_to_jalali( (int) gmdate( 'Y', $ts ), (int) gmdate( 'n', $ts ), (int) gmdate( 'j', $ts ) );
	return vf_fa_digits( $jd ) . ' ' . $months[ $jm - 1 ] . ' ' . vf_fa_digits( $jy );
}

/**
 * Estimate reading time in minutes.
 *
 * @param string $text Text/HTML.
 * @return int
 */
function vf_estimate_minutes( $text ) {
	$plain = trim( wp_strip_all_tags( (string) $text ) );
	if ( '' === $plain ) {
		return 1;
	}
	$words = count( preg_split( '/\s+/u', $plain ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * "N دقیقه" label.
 *
 * @param int $minutes Minutes.
 * @return string
 */
function vf_minutes_label( $minutes ) {
	/* translators: %s: number of minutes */
	return sprintf( __( '%s دقیقه', 'vidiform' ), vf_num( max( 1, (int) $minutes ) ) );
}

/**
 * Which public section the current request belongs to: 'help', 'blog' or ''.
 *
 * @return string
 */
function vf_section() {
	static $section = null;
	if ( null !== $section && did_action( 'wp' ) ) {
		return $section;
	}
	$current = '';
	if ( get_query_var( 'vf_help_search' ) || is_post_type_archive( 'help_article' ) || is_singular( 'help_article' ) || is_tax( 'help_category' ) ) {
		$current = 'help';
	} elseif ( is_404() && vf_request_is_help_path() ) {
		$current = 'help';
	} elseif ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date() || is_search() || is_404() || is_page() || is_front_page() ) {
		$current = 'blog';
	}
	if ( did_action( 'wp' ) ) {
		$section = $current;
	}
	return $current;
}

/**
 * Whether the raw request path lives under the Help Center base.
 *
 * @return bool
 */
function vf_request_is_help_path() {
	global $wp;
	$path = isset( $wp->request ) ? (string) $wp->request : '';
	$base = vf_help_base();
	return $path === $base || 0 === strpos( $path, $base . '/' );
}

/**
 * Render a template part and pass args (wrapper for readability).
 *
 * @param string $slug Slug under template-parts/.
 * @param array  $args Args.
 */
function vf_part( $slug, $args = array() ) {
	get_template_part( 'template-parts/' . $slug, null, $args );
}

/**
 * Clamp an integer.
 *
 * @param mixed $v   Value.
 * @param int   $min Min.
 * @param int   $max Max.
 * @return int
 */
function vf_clamp_int( $v, $min, $max ) {
	return max( $min, min( $max, (int) $v ) );
}
