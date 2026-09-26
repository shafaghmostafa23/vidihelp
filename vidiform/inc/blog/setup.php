<?php
/**
 * Blog / Content Hub data model.
 *
 * Uses WordPress-native content: post + category + post_tag + users (authors).
 * Extra post meta:
 *  _vf_featured      bool    featured on the blog home
 *  _vf_related       int[]   manually chosen related posts (fallback: shared categories/tags)
 *  _vf_read_minutes  int     reading time override (0 = automatic)
 *  _vf_seo_title     string  optional SEO title
 *  _vf_seo_desc      string  optional meta description
 *
 * URLs (with the permalink structure set on activation, /blog/%postname%/):
 *  /blog/                     blog home (WordPress "posts page")
 *  /blog/page/{n}/            pagination
 *  /blog/{post}/              single post
 *  /blog/category/{slug}/     category archive
 *  /blog/tag/{slug}/          tag archive
 *  /blog/author/{name}/       author archive
 *  /?s={query}                search (blog posts only)
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Blog home URL.
 *
 * @return string
 */
function vf_blog_home_url() {
	$page = (int) get_option( 'page_for_posts' );
	if ( $page && 'page' === get_option( 'show_on_front' ) ) {
		return get_permalink( $page );
	}
	return home_url( '/' );
}

/**
 * Register post meta.
 */
function vf_blog_register_meta() {
	$auth = function ( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	};
	register_post_meta( 'post', '_vf_featured', array(
		'type'              => 'boolean',
		'single'            => true,
		'default'           => false,
		'show_in_rest'      => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'auth_callback'     => $auth,
	) );
	register_post_meta( 'post', '_vf_related', array(
		'type'              => 'array',
		'single'            => true,
		'default'           => array(),
		'show_in_rest'      => array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ) ),
		'sanitize_callback' => function ( $v ) {
			return array_slice( array_values( array_unique( array_filter( array_map( 'absint', (array) $v ) ) ) ), 0, 12 );
		},
		'auth_callback'     => $auth,
	) );
	register_post_meta( 'post', '_vf_read_minutes', array(
		'type'              => 'integer',
		'single'            => true,
		'default'           => 0,
		'show_in_rest'      => true,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth,
	) );
	foreach ( array( '_vf_seo_title', '_vf_seo_desc' ) as $key ) {
		register_post_meta( 'post', $key, array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth,
		) );
	}
}
add_action( 'init', 'vf_blog_register_meta' );

/**
 * Reading time of a blog post (minutes).
 *
 * @param int|WP_Post|null $post Post.
 * @return int
 */
function vf_post_minutes( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$m = (int) get_post_meta( $post->ID, '_vf_read_minutes', true );
	return $m > 0 ? $m : vf_estimate_minutes( $post->post_content );
}

/**
 * Featured posts for the blog home.
 *
 * @param int $count Count.
 * @return WP_Post[]
 */
function vf_blog_featured( $count ) {
	if ( $count < 1 ) {
		return array();
	}
	return get_posts( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'numberposts'         => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_key'            => '_vf_featured', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'          => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
}

/**
 * Related posts: manual picks first, then posts sharing categories/tags (one query).
 *
 * @param int $post_id Post.
 * @param int $count   Count.
 * @return WP_Post[]
 */
function vf_blog_related( $post_id, $count ) {
	if ( $count < 1 ) {
		return array();
	}
	$manual = array_filter( array_map( 'absint', (array) get_post_meta( $post_id, '_vf_related', true ) ) );
	$out    = array();
	if ( $manual ) {
		$out = get_posts( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post__in'      => $manual,
			'orderby'       => 'post__in',
			'numberposts'   => $count,
			'no_found_rows' => true,
		) );
	}
	if ( count( $out ) < $count ) {
		$cats = wp_get_post_categories( $post_id );
		$tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
		$tax  = array( 'relation' => 'OR' );
		if ( $cats ) {
			$tax[] = array( 'taxonomy' => 'category', 'terms' => $cats );
		}
		if ( $tags ) {
			$tax[] = array( 'taxonomy' => 'post_tag', 'terms' => $tags );
		}
		if ( count( $tax ) > 1 ) {
			$more = get_posts( array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'numberposts'         => $count - count( $out ),
				'post__not_in'        => array_merge( array( $post_id ), wp_list_pluck( $out, 'ID' ) ),
				'tax_query'           => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			) );
			$out = array_merge( $out, $more );
		}
	}
	return $out;
}

/**
 * Blog archives only ever list blog posts.
 *
 * @param WP_Query $q Query.
 */
function vf_blog_pre_get_posts( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_home() || $q->is_category() || $q->is_tag() || $q->is_author() || $q->is_date() ) {
		$q->set( 'post_type', 'post' );
		$q->set( 'posts_per_page', (int) vf_opt( 'blog', 'per_page', 6 ) );
	}
	// Blog landing (/blog/): ordering, category filter and the featured article from «صفحه بلاگ».
	if ( $q->is_home() ) {
		$orders = vf_blog_order_options();
		$order  = $orders[ vf_opt( 'blog', 'order', 'date_desc' ) ] ?? $orders['date_desc'];
		$q->set( 'orderby', $order[0] );
		$q->set( 'order', $order[1] );
		$q->set( 'ignore_sticky_posts', true );
		$cats = array_filter( array_map( 'absint', (array) vf_opt( 'blog', 'latest_cats', array() ) ) );
		if ( $cats ) {
			$q->set( 'category__in', $cats );
		}
		$featured = vf_blog_landing_featured();
		if ( $featured ) {
			// Excluded on every page so pagination stays consistent.
			$q->set( 'post__not_in', array( $featured->ID ) );
		}
	}
}
add_action( 'pre_get_posts', 'vf_blog_pre_get_posts' );

/**
 * Whether the current view is the blog home (posts page).
 *
 * @return bool
 */
function vf_is_blog_home() {
	return is_home();
}

/**
 * Featured article of the Blog landing: the post chosen in «صفحه بلاگ», otherwise the latest
 * post flagged as featured (star), otherwise the newest post. Null when disabled.
 *
 * @return WP_Post|null
 */
function vf_blog_landing_featured() {
	global $vf_blog_featured;
	if ( isset( $vf_blog_featured ) ) {
		return $vf_blog_featured ? $vf_blog_featured : null;
	}
	$vf_blog_featured = false;
	if ( ! vf_opt( 'blog', 'show_featured', 1 ) ) {
		return null;
	}
	$picked = (int) vf_opt( 'blog', 'featured_post', 0 );
	if ( $picked ) {
		$p = get_post( $picked );
		if ( $p && 'post' === $p->post_type && 'publish' === $p->post_status && ! post_password_required( $p ) ) {
			$vf_blog_featured = $p;
			return $p;
		}
	}
	$base = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'numberposts'         => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'suppress_filters'    => false,
	);
	$cats = array_filter( array_map( 'absint', (array) vf_opt( 'blog', 'latest_cats', array() ) ) );
	if ( $cats ) {
		$base['category__in'] = $cats;
	}
	$flagged = get_posts( array_merge( $base, array( 'meta_key' => '_vf_featured', 'meta_value' => '1' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$list    = $flagged ? $flagged : get_posts( $base );
	$vf_blog_featured = $list ? $list[0] : false;
	return $vf_blog_featured ? $vf_blog_featured : null;
}
