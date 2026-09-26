<?php
/**
 * Help Center data layer.
 *
 * The public Help Center renders from one cached "index" (categories → sub-categories → ordered
 * guides) built with a single posts query + batched term cache. Home cards, sidebar navigation,
 * prev/next and related guides all read from it, so there are no N+1 query patterns.
 * The cache is versioned and invalidated whenever guides or help categories change.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current cache version.
 *
 * @return string
 */
function vf_help_cache_ver() {
	$v = get_option( 'vf_help_ver' );
	if ( ! $v ) {
		$v = (string) time();
		update_option( 'vf_help_ver', $v, true );
	}
	return (string) $v;
}

/**
 * Invalidate the Help Center index cache.
 */
function vf_help_bump() {
	update_option( 'vf_help_ver', (string) microtime( true ), true );
	$GLOBALS['vf_help_index_mem'] = null;
}
add_action( 'save_post_help_article', 'vf_help_bump' );
add_action( 'deleted_post', function ( $id ) {
	if ( 'help_article' === get_post_type( $id ) ) {
		vf_help_bump();
	}
} );
add_action( 'trashed_post', function ( $id ) {
	if ( 'help_article' === get_post_type( $id ) ) {
		vf_help_bump();
	}
} );
foreach ( array( 'created_help_category', 'edited_help_category', 'delete_help_category', 'edited_help_feature', 'delete_help_feature' ) as $vf_hook ) {
	add_action( $vf_hook, 'vf_help_bump' );
}
unset( $vf_hook );
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'help_article' === $post->post_type && $new !== $old ) {
		vf_help_bump();
	}
}, 10, 3 );

/**
 * Reading time (minutes) for a guide.
 *
 * @param WP_Post|int $post Post.
 * @return int
 */
function vf_guide_minutes( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$m = (int) get_post_meta( $post->ID, '_vf_read_minutes', true );
	return $m > 0 ? $m : vf_estimate_minutes( $post->post_content );
}

/**
 * Sort help category terms by vf_order then name.
 *
 * @param WP_Term[] $terms Terms.
 * @return WP_Term[]
 */
function vf_sort_help_terms( $terms ) {
	usort( $terms, function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'vf_order', true );
		$ob = (int) get_term_meta( $b->term_id, 'vf_order', true );
		return $oa === $ob ? strcmp( $a->name, $b->name ) : $oa - $ob;
	} );
	return $terms;
}

/**
 * Top-level ancestor term id of a help category.
 *
 * @param int $term_id Term id.
 * @return int
 */
function vf_help_root_term( $term_id ) {
	$anc = get_ancestors( $term_id, 'help_category', 'taxonomy' );
	return $anc ? (int) end( $anc ) : (int) $term_id;
}

/**
 * Primary help_category term of a guide (sub-category if assigned, else main).
 *
 * @param int $post_id Post id.
 * @return WP_Term|null
 */
function vf_guide_term( $post_id ) {
	$terms = get_the_terms( $post_id, 'help_category' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	// Prefer the deepest term.
	usort( $terms, function ( $a, $b ) {
		return (int) ( 0 === (int) $a->parent ) - (int) ( 0 === (int) $b->parent );
	} );
	return $terms[0];
}

/**
 * Build (or read from cache) the public Help Center index.
 *
 * @return array{cats: array, order: int[], guides: array}
 */
function vf_help_index() {
	if ( ! empty( $GLOBALS['vf_help_index_mem'] ) ) {
		return $GLOBALS['vf_help_index_mem'];
	}
	$ver    = vf_help_cache_ver();
	$cached = wp_cache_get( 'vf_help_index', 'vidiform' );
	if ( false === $cached ) {
		$cached = get_transient( 'vf_help_index' );
	}
	if ( is_array( $cached ) && isset( $cached['ver'], $cached['data'] ) && $cached['ver'] === $ver ) {
		$GLOBALS['vf_help_index_mem'] = $cached['data'];
		return $cached['data'];
	}

	$terms = get_terms( array(
		'taxonomy'   => 'help_category',
		'hide_empty' => false,
	) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	update_termmeta_cache( wp_list_pluck( $terms, 'term_id' ) );
	$terms = vf_sort_help_terms( $terms );

	$cats  = array();
	$order = array();
	foreach ( $terms as $t ) {
		$cats[ $t->term_id ] = array(
			'id'     => (int) $t->term_id,
			'name'   => $t->name,
			'slug'   => $t->slug,
			'desc'   => $t->description,
			'image'  => (int) get_term_meta( $t->term_id, 'vf_image', true ),
			'parent' => (int) $t->parent,
			'subs'   => array(),
			'guides' => array(),
		);
	}
	foreach ( $terms as $t ) {
		if ( $t->parent && isset( $cats[ $t->parent ] ) ) {
			$cats[ $t->parent ]['subs'][] = (int) $t->term_id;
		} elseif ( ! $t->parent ) {
			$order[] = (int) $t->term_id;
		}
	}

	$posts = get_posts( array(
		'post_type'              => 'help_article',
		'post_status'            => 'publish',
		'numberposts'            => 2000,
		'orderby'                => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		'no_found_rows'          => true,
		'suppress_filters'       => false,
		'update_post_term_cache' => true,
		'update_post_meta_cache' => true,
	) );

	$guides = array();
	foreach ( $posts as $p ) {
		$term    = vf_guide_term( $p->ID );
		$term_id = $term ? (int) $term->term_id : 0;
		$root    = $term_id ? vf_help_root_term( $term_id ) : 0;
		$guides[ $p->ID ] = array(
			'id'      => (int) $p->ID,
			'title'   => get_the_title( $p ),
			'slug'    => $p->post_name,
			'excerpt' => $p->post_excerpt ? $p->post_excerpt : wp_trim_words( wp_strip_all_tags( $p->post_content ), 24 ),
			'read'    => vf_guide_minutes( $p ),
			'cat'     => $root,
			'term'    => $term_id,
		);
		if ( $root && isset( $cats[ $root ] ) ) {
			$cats[ $root ]['guides'][] = (int) $p->ID;
		}
	}

	$index = array(
		'cats'   => $cats,
		'order'  => $order,
		'guides' => $guides,
	);
	$store = array( 'ver' => $ver, 'data' => $index );
	wp_cache_set( 'vf_help_index', $store, 'vidiform', DAY_IN_SECONDS );
	set_transient( 'vf_help_index', $store, DAY_IN_SECONDS );
	$GLOBALS['vf_help_index_mem'] = $index;
	return $index;
}

/**
 * Guides of a category in order, optionally filtered by a sub-category.
 *
 * @param int $cat_id Top category id.
 * @param int $sub_id Sub id (0 = all).
 * @return array[] Guide rows.
 */
function vf_help_cat_guides( $cat_id, $sub_id = 0 ) {
	$idx = vf_help_index();
	if ( empty( $idx['cats'][ $cat_id ] ) ) {
		return array();
	}
	$out = array();
	foreach ( $idx['cats'][ $cat_id ]['guides'] as $gid ) {
		$g = $idx['guides'][ $gid ];
		if ( $sub_id && (int) $g['term'] !== (int) $sub_id ) {
			continue;
		}
		$out[] = $g;
	}
	return $out;
}

/**
 * Public URL of the Help Center home.
 *
 * @param string $path Optional sub-path.
 * @return string
 */
function vf_help_url( $path = '' ) {
	return home_url( user_trailingslashit( vf_help_base() . ( $path ? '/' . ltrim( $path, '/' ) : '' ) ) );
}

/**
 * Help Center search URL.
 *
 * @param string $q Query.
 * @return string
 */
function vf_help_search_url( $q = '' ) {
	$url = home_url( user_trailingslashit( vf_help_base() . '/search' ) );
	return '' === $q ? $url : add_query_arg( 'q', rawurlencode( $q ), $url );
}

/**
 * Search published guides (title + content + excerpt), ordered by relevance.
 *
 * @param string $q     Query.
 * @param int    $limit Max results.
 * @param int    $paged Page.
 * @return WP_Query
 */
function vf_help_search_query( $q, $limit = 20, $paged = 1 ) {
	return new WP_Query( array(
		'post_type'      => 'help_article',
		'post_status'    => 'publish',
		's'              => $q,
		'posts_per_page' => $limit,
		'paged'          => max( 1, (int) $paged ),
		'orderby'        => 'relevance',
	) );
}
