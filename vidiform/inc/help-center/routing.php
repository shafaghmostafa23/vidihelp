<?php
/**
 * Help Center URL structure.
 *
 *   /help/                         Help Center home               (post type archive of help_article)
 *   /help/search/?q=…              Help Center search results     (is_search, help_article only)
 *   /help/{category}/              Category                       (help_category archive)
 *   /help/{category}/{sub}/        Sub-category                   (help_category archive, child term)
 *   /help/{category}/{guide}/      Guide / article                (singular help_article)
 *   /help/{…}/page/{n}/            Paginated category lists
 *
 * Requests are mapped onto native WordPress query vars, so the main query produces the standard
 * conditionals (is_tax, is_singular, is_search, is_post_type_archive) that SEO plugins rely on.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rewrite rules.
 */
function vf_help_rewrites() {
	$b = preg_quote( vf_help_base(), '#' );
	add_rewrite_rule( '^' . $b . '/?$', 'index.php?vf_help=home', 'top' );
	add_rewrite_rule( '^' . $b . '/search/?$', 'index.php?vf_help=search', 'top' );
	add_rewrite_rule( '^' . $b . '/([^/]+)/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?vf_help_path=$matches[1]/$matches[2]&paged=$matches[3]', 'top' );
	add_rewrite_rule( '^' . $b . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?vf_help_path=$matches[1]&paged=$matches[2]', 'top' );
	add_rewrite_rule( '^' . $b . '/([^/]+)/([^/]+)/?$', 'index.php?vf_help_path=$matches[1]/$matches[2]', 'top' );
	add_rewrite_rule( '^' . $b . '/([^/]+)/?$', 'index.php?vf_help_path=$matches[1]', 'top' );
}
add_action( 'init', 'vf_help_rewrites' );

/**
 * Public query vars.
 *
 * @param string[] $vars Vars.
 * @return string[]
 */
function vf_help_query_vars( $vars ) {
	$vars[] = 'vf_help';
	$vars[] = 'vf_help_path';
	$vars[] = 'vf_help_search';
	return $vars;
}
add_filter( 'query_vars', 'vf_help_query_vars' );

/**
 * Resolve Help Center routes into native query vars.
 *
 * @param array $qv Query vars.
 * @return array
 */
function vf_help_request( $qv ) {
	if ( ! empty( $qv['vf_help'] ) ) {
		if ( 'search' === $qv['vf_help'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only search.
			$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
			return array(
				'post_type'      => 'help_article',
				's'              => $q,
				'vf_help_search' => 1,
				'paged'          => isset( $qv['paged'] ) ? $qv['paged'] : 0,
			);
		}
		return array(
			'post_type' => 'help_article',
			'vf_help'   => 'home',
		);
	}

	if ( empty( $qv['vf_help_path'] ) ) {
		return $qv;
	}

	$parts = array_values( array_filter( explode( '/', (string) $qv['vf_help_path'] ) ) );
	$paged = isset( $qv['paged'] ) ? (int) $qv['paged'] : 0;
	$first = sanitize_title( $parts[0] );

	if ( 1 === count( $parts ) ) {
		$term = get_term_by( 'slug', $first, 'help_category' );
		if ( $term && ! $term->parent ) {
			return array( 'help_category' => $term->slug, 'paged' => $paged );
		}
		// Guide without a category, or a moved guide.
		return array( 'help_article' => $first, 'post_type' => 'help_article', 'name' => $first );
	}

	$second = sanitize_title( $parts[1] );
	$parent = get_term_by( 'slug', $first, 'help_category' );
	$child  = get_term_by( 'slug', $second, 'help_category' );
	if ( $parent && $child && (int) $child->parent === (int) $parent->term_id ) {
		return array( 'help_category' => $child->slug, 'paged' => $paged );
	}
	return array( 'help_article' => $second, 'post_type' => 'help_article', 'name' => $second );
}
add_filter( 'request', 'vf_help_request' );

/**
 * Canonical redirects: a guide requested under the wrong category path → its real permalink.
 */
function vf_help_canonical_redirect() {
	if ( ! is_singular( 'help_article' ) || is_preview() ) {
		return;
	}
	global $wp;
	$expected = wp_parse_url( get_permalink(), PHP_URL_PATH );
	$current  = '/' . trim( (string) $wp->request, '/' ) . '/';
	$home     = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$current  = rtrim( (string) $home, '/' ) . $current;
	if ( $expected && trailingslashit( $expected ) !== trailingslashit( $current ) ) {
		wp_safe_redirect( get_permalink(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'vf_help_canonical_redirect', 9 );

/**
 * Empty Help Center search → Help Center home.
 */
function vf_help_empty_search_redirect() {
	if ( get_query_var( 'vf_help_search' ) && '' === trim( (string) get_query_var( 's' ) ) ) {
		wp_safe_redirect( vf_help_url(), 302 );
		exit;
	}
}
add_action( 'template_redirect', 'vf_help_empty_search_redirect', 8 );

/**
 * Main query tuning for Help Center views.
 *
 * @param WP_Query $q Query.
 */
function vf_help_pre_get_posts( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( 'home' === $q->get( 'vf_help' ) ) {
		// The home renders from the cached index; keep the main query as light as possible.
		$q->set( 'posts_per_page', 1 );
		$q->set( 'no_found_rows', true );
		return;
	}
	if ( $q->get( 'vf_help_search' ) ) {
		$q->set( 'post_type', 'help_article' );
		$q->set( 'posts_per_page', 20 );
		return;
	}
	if ( $q->is_tax( 'help_category' ) ) {
		$q->set( 'post_type', 'help_article' );
		$q->set( 'posts_per_page', (int) vf_opt( 'help', 'per_page', 30 ) );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'ASC' ) );
		return;
	}
	// Global / Blog search never includes help articles.
	if ( $q->is_search() ) {
		$q->set( 'post_type', 'post' );
	}
}
add_action( 'pre_get_posts', 'vf_help_pre_get_posts' );

/**
 * Guide permalink: /help/{root-category}/{slug}/.
 *
 * @param string  $link Link.
 * @param WP_Post $post Post.
 * @return string
 */
function vf_help_post_link( $link, $post ) {
	if ( 'help_article' !== $post->post_type ) {
		return $link;
	}
	$slug = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
	if ( ! $slug || ! get_option( 'permalink_structure' ) ) {
		return add_query_arg( array( 'post_type' => 'help_article', 'p' => $post->ID ), home_url( '/' ) );
	}
	$term = vf_guide_term( $post->ID );
	$root = $term ? get_term( vf_help_root_term( $term->term_id ), 'help_category' ) : null;
	$path = $root && ! is_wp_error( $root ) ? $root->slug . '/' . $slug : $slug;
	return vf_help_url( $path );
}
add_filter( 'post_type_link', 'vf_help_post_link', 10, 2 );

/**
 * Category permalink: /help/{slug}/ or /help/{parent}/{slug}/.
 *
 * @param string  $link     Link.
 * @param WP_Term $term     Term.
 * @param string  $taxonomy Taxonomy.
 * @return string
 */
function vf_help_term_link( $link, $term, $taxonomy ) {
	if ( 'help_category' !== $taxonomy || ! get_option( 'permalink_structure' ) ) {
		return $link;
	}
	if ( $term->parent ) {
		$parent = get_term( $term->parent, 'help_category' );
		if ( $parent && ! is_wp_error( $parent ) ) {
			return vf_help_url( $parent->slug . '/' . $term->slug );
		}
	}
	return vf_help_url( $term->slug );
}
add_filter( 'term_link', 'vf_help_term_link', 10, 3 );

/**
 * Archive link of the CPT points to the Help Center home.
 *
 * @param string $link      Link.
 * @param string $post_type Post type.
 * @return string
 */
function vf_help_archive_link( $link, $post_type ) {
	return 'help_article' === $post_type ? vf_help_url() : $link;
}
add_filter( 'post_type_archive_link', 'vf_help_archive_link', 10, 2 );

/**
 * Templates for Help Center views that are not covered by the native hierarchy.
 *
 * @param string $template Template.
 * @return string
 */
function vf_help_template_include( $template ) {
	if ( get_query_var( 'vf_help_search' ) ) {
		$t = locate_template( 'help-search.php' );
		return $t ? $t : $template;
	}
	if ( 'home' === get_query_var( 'vf_help' ) ) {
		$t = locate_template( 'help-home.php' );
		return $t ? $t : $template;
	}
	if ( is_404() && vf_request_is_help_path() ) {
		$t = locate_template( 'help-404.php' );
		return $t ? $t : $template;
	}
	return $template;
}
add_filter( 'template_include', 'vf_help_template_include' );

/**
 * Make the virtual Help Center home behave as the help_article archive for conditionals.
 *
 * @param WP_Query $q Query.
 */
function vf_help_home_flags( $q ) {
	if ( $q->is_main_query() && 'home' === $q->get( 'vf_help' ) ) {
		$q->is_post_type_archive = true;
		$q->is_archive           = true;
		$q->is_home              = false;
	}
}
add_action( 'parse_query', 'vf_help_home_flags' );
