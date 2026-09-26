<?php
/**
 * Front-end assets. Each system only loads its own stylesheet/script.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versioned asset URL helper (file mtime in debug, theme version otherwise).
 *
 * @param string $rel Relative path.
 * @return string|false
 */
function vf_asset_ver( $rel ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( VF_DIR . '/' . $rel ) ) {
		return (string) filemtime( VF_DIR . '/' . $rel );
	}
	return VF_VERSION;
}

/**
 * Enqueue public assets.
 */
function vf_enqueue_assets() {
	wp_enqueue_style( 'vf-fonts', VF_URI . '/assets/css/fonts.css', array(), vf_asset_ver( 'assets/css/fonts.css' ) );
	wp_enqueue_style( 'vf-base', VF_URI . '/assets/css/base.css', array( 'vf-fonts' ), vf_asset_ver( 'assets/css/base.css' ) );
	wp_enqueue_script( 'vf-theme', VF_URI . '/assets/js/theme.js', array(), vf_asset_ver( 'assets/js/theme.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );

	if ( 'help' === vf_section() ) {
		wp_enqueue_style( 'vf-help', VF_URI . '/assets/css/help.css', array( 'vf-base' ), vf_asset_ver( 'assets/css/help.css' ) );
		wp_enqueue_script( 'vf-help', VF_URI . '/assets/js/help.js', array(), vf_asset_ver( 'assets/js/help.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script( 'vf-help', 'VF_HELP', array(
			'searchUrl' => esc_url_raw( rest_url( 'vidiform/v1/help/search' ) ),
			'i18n'      => array(
				'results'   => __( 'نتایج جست‌وجو', 'vidiform' ),
				'error'     => __( 'جست‌وجو انجام نشد. دوباره تلاش کنید.', 'vidiform' ),
				'searching'     => __( 'در حال جست‌وجو…', 'vidiform' ),
				'noResults'     => __( 'چیزی پیدا نشد', 'vidiform' ),
				'noResultsDesc' => __( 'عبارت دیگری را امتحان کنید یا از دسته‌بندی‌های زیر شروع کنید.', 'vidiform' ),
			),
		) );
	} else {
		wp_enqueue_style( 'vf-blog', VF_URI . '/assets/css/blog.css', array( 'vf-base' ), vf_asset_ver( 'assets/css/blog.css' ) );
		wp_enqueue_script( 'vf-blog', VF_URI . '/assets/js/blog.js', array(), vf_asset_ver( 'assets/js/blog.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script( 'vf-blog', 'VF_BLOG', array(
			'copied' => __( 'لینک کپی شد.', 'vidiform' ),
		) );
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	// Block library CSS is only useful where post content is rendered.
	if ( ! is_singular() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'vf_enqueue_assets', 20 );

/**
 * Preload the main Persian font file.
 */
function vf_preload_fonts() {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( VF_URI . '/assets/fonts/vazirmatn-arabic.woff2' ) );
}
add_action( 'wp_head', 'vf_preload_fonts', 1 );
