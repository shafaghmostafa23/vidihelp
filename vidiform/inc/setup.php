<?php
/**
 * Theme setup, activation routine, menus and front-page handling.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme supports & menus.
 */
function vf_setup() {
	load_theme_textdomain( 'vidiform', VF_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/fonts.css', 'assets/css/editor.css' ) );
	add_theme_support( 'editor-color-palette', array(
		array( 'name' => __( 'بنفش ویدی‌فرم', 'vidiform' ), 'slug' => 'vf-primary', 'color' => '#7132F5' ),
		array( 'name' => __( 'بنفش تیره', 'vidiform' ), 'slug' => 'vf-primary-ink', 'color' => '#5A1FD6' ),
		array( 'name' => __( 'متن', 'vidiform' ), 'slug' => 'vf-text', 'color' => '#1B1630' ),
		array( 'name' => __( 'متن ثانویه', 'vidiform' ), 'slug' => 'vf-text-3', 'color' => '#5A5473' ),
		array( 'name' => __( 'پس‌زمینه‌ی برند', 'vidiform' ), 'slug' => 'vf-tint', 'color' => '#F9F6FF' ),
	) );

	add_image_size( 'vf-card', 720, 405, true );
	add_image_size( 'vf-hero', 1440, 810, true );

	register_nav_menus( array(
		'blog_primary' => __( 'وبلاگ — منوی اصلی', 'vidiform' ),
		'blog_footer'  => __( 'وبلاگ — منوی فوتر', 'vidiform' ),
		'help_footer'  => __( 'مرکز راهنما — منوی فوتر', 'vidiform' ),
	) );
}
add_action( 'after_setup_theme', 'vf_setup' );

/**
 * Content width for embeds.
 */
function vf_content_width() {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'vf_content_width', 0 );

/**
 * Activation: roles, pages, permalinks, rewrite flush.
 */
function vf_activate() {
	vf_install_roles();

	// Blog index page (/blog/) — WordPress-native "posts page".
	$blog_page = (int) get_option( 'page_for_posts' );
	if ( ! $blog_page || ! get_post( $blog_page ) ) {
		$existing  = get_page_by_path( 'blog' );
		$blog_page = $existing ? $existing->ID : wp_insert_post( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => __( 'وبلاگ', 'vidiform' ),
			'post_name'   => 'blog',
		) );
		if ( $blog_page && ! is_wp_error( $blog_page ) ) {
			update_option( 'page_for_posts', (int) $blog_page );
		}
	}

	// Static front page (it only routes visitors to the chosen section's home).
	$front = (int) get_option( 'page_on_front' );
	if ( 'page' !== get_option( 'show_on_front' ) || ! $front || ! get_post( $front ) ) {
		$front = wp_insert_post( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => __( 'خانه', 'vidiform' ),
			'post_name'   => 'home',
		) );
		if ( $front && ! is_wp_error( $front ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $front );
			update_option( 'vf_front_page_id', (int) $front );
		}
	}

	// Clean URLs: posts live under /blog/. Only applied when permalinks are still "plain".
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/blog/%postname%/' );
	}

	vf_register_help_types();
	flush_rewrite_rules();
	set_transient( 'vf_activated_notice', 1, HOUR_IN_SECONDS );
}
add_action( 'after_switch_theme', 'vf_activate' );

/**
 * Flush rewrites once after an update to the routing version.
 */
function vf_maybe_flush_rewrites() {
	if ( get_option( 'vf_rewrite_version' ) !== VF_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'vf_rewrite_version', VF_VERSION );
	}
}
add_action( 'init', 'vf_maybe_flush_rewrites', 99 );

/**
 * The generated "Home" front page only routes to the chosen section home.
 */
function vf_front_redirect() {
	if ( ! is_front_page() || is_home() ) {
		return;
	}
	$generated = (int) get_option( 'vf_front_page_id' );
	if ( ! $generated || (int) get_queried_object_id() !== $generated ) {
		return; // A custom front page chosen by the site owner renders normally.
	}
	$target = 'blog' === vf_opt( 'general', 'front' ) ? vf_blog_home_url() : vf_help_url();
	wp_safe_redirect( $target, 302 );
	exit;
}
add_action( 'template_redirect', 'vf_front_redirect', 5 );

/**
 * Admin notice after activation.
 */
function vf_activation_notice() {
	if ( ! get_transient( 'vf_activated_notice' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	delete_transient( 'vf_activated_notice' );
	$seed = admin_url( 'admin.php?page=vf-help-settings' );
	echo '<div class="notice notice-success is-dismissible"><p>' .
		esc_html__( 'قالب ویدی‌فرم فعال شد. مرکز راهنما و وبلاگ هرکدام منوی مدیریت مستقل دارند.', 'vidiform' ) .
		' <a href="' . esc_url( $seed ) . '">' . esc_html__( 'بارگذاری ساختار پیش‌فرض مرکز راهنما', 'vidiform' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'vf_activation_notice' );

/**
 * Force the RTL direction the design is built for; keep lang from the site.
 *
 * @param string $output Attributes.
 * @return string
 */
function vf_language_attributes( $output ) {
	if ( is_admin() ) {
		return $output;
	}
	$output = preg_replace( '/\sdir="[^"]*"/', '', ' ' . $output );
	return trim( 'dir="rtl" ' . $output );
}
add_filter( 'language_attributes', 'vf_language_attributes' );

/**
 * Body classes per section.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function vf_body_class( $classes ) {
	$section = vf_section();
	if ( $section ) {
		$classes[] = 'vf-' . $section;
	}
	$classes[] = 'vf-body';
	return $classes;
}
add_filter( 'body_class', 'vf_body_class' );

/**
 * Print the pre-paint theme-mode script (prevents a light/dark flash).
 */
function vf_theme_mode_script() {
	$mode = vf_opt( 'general', 'theme_mode', 'system' );
	// The Blog has its own default (set in «صفحه بلاگ»); visitors' own choice always wins.
	if ( ! is_admin() && 'blog' === vf_section() ) {
		$mode = vf_opt( 'blog', 'default_theme', $mode );
	}
	?>
<script>(function(){try{var d=document.documentElement,s=localStorage.getItem('vf-theme'),m=<?php echo wp_json_encode( $mode ); ?>;var t=s||(m==='system'?(window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):m);d.setAttribute('data-theme',t);}catch(e){}})();</script>
	<?php
}
add_action( 'wp_head', 'vf_theme_mode_script', 0 );
add_action( 'admin_head', 'vf_theme_mode_script', 0 );

/**
 * Remove the emoji detection script on the front end (performance; design ships its own icons).
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
