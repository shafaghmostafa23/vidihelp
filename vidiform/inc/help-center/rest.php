<?php
/**
 * Help Center REST API (namespace vidiform/v1).
 *
 * Public:  GET  /help/search?q=                     live search (published guides only)
 * Admin:   all other routes. Every admin route requires a logged-in user with the matching
 *          Help Center capability; the WordPress REST cookie nonce (X-WP-Nonce) is verified by
 *          core for cookie-authenticated requests. All input is sanitized server-side.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register routes.
 */
function vf_help_register_rest() {
	$ns = 'vidiform/v1';

	$can_edit   = function () {
		return current_user_can( 'edit_help_articles' );
	};
	$can_manage = function () {
		return current_user_can( 'manage_help_center' );
	};
	$id_arg     = array(
		'id' => array(
			'validate_callback' => function ( $v ) {
				return is_numeric( $v ) && (int) $v > 0;
			},
		),
	);

	register_rest_route( $ns, '/help/search', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'vf_rest_help_search',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );

	register_rest_route( $ns, '/help/admin/state', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'vf_rest_help_state',
		'permission_callback' => $can_edit,
	) );

	register_rest_route( $ns, '/help/guides', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_create_guide',
		'permission_callback' => $can_edit,
	) );
	register_rest_route( $ns, '/help/guides/order', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_order_guides',
		'permission_callback' => $can_edit,
	) );
	register_rest_route( $ns, '/help/guides/(?P<id>\d+)', array(
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'vf_rest_help_get_guide',
			'permission_callback' => $can_edit,
			'args'                => $id_arg,
		),
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vf_rest_help_save_guide',
			'permission_callback' => $can_edit,
			'args'                => $id_arg,
		),
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'vf_rest_help_delete_guide',
			'permission_callback' => $can_edit,
			'args'                => $id_arg,
		),
	) );
	register_rest_route( $ns, '/help/guides/(?P<id>\d+)/duplicate', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_duplicate_guide',
		'permission_callback' => $can_edit,
		'args'                => $id_arg,
	) );

	register_rest_route( $ns, '/help/categories', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_create_cat',
		'permission_callback' => $can_manage,
	) );
	register_rest_route( $ns, '/help/categories/order', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_order_cats',
		'permission_callback' => $can_manage,
	) );
	register_rest_route( $ns, '/help/categories/(?P<id>\d+)', array(
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vf_rest_help_update_cat',
			'permission_callback' => $can_manage,
			'args'                => $id_arg,
		),
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'vf_rest_help_delete_cat',
			'permission_callback' => $can_manage,
			'args'                => $id_arg,
		),
	) );
	register_rest_route( $ns, '/help/categories/(?P<id>\d+)/duplicate', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_duplicate_cat',
		'permission_callback' => $can_manage,
		'args'                => $id_arg,
	) );

	register_rest_route( $ns, '/help/features', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_save_feature',
		'permission_callback' => $can_edit,
	) );
	register_rest_route( $ns, '/help/features/(?P<id>\d+)', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_save_feature',
		'permission_callback' => $can_manage,
		'args'                => $id_arg,
	) );

	register_rest_route( $ns, '/help/settings', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_settings',
		'permission_callback' => $can_manage,
	) );
	register_rest_route( $ns, '/help/seed', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'vf_rest_help_seed',
		'permission_callback' => $can_manage,
	) );
}
add_action( 'rest_api_init', 'vf_help_register_rest' );

/* -------------------------------------------------------------------------
 * Public search
 * ---------------------------------------------------------------------- */

/**
 * GET /help/search.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function vf_rest_help_search( $req ) {
	$q = trim( (string) $req['q'] );
	if ( '' === $q || mb_strlen( $q ) > 100 ) {
		return rest_ensure_response( array( 'items' => array() ) );
	}
	$query = vf_help_search_query( $q, 10 );
	$items = array();
	foreach ( $query->posts as $p ) {
		$term    = vf_guide_term( $p->ID );
		$items[] = array(
			'id'       => $p->ID,
			'title'    => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
			'url'      => get_permalink( $p ),
			'read'     => vf_minutes_label( vf_guide_minutes( $p ) ),
			'category' => $term ? $term->name : '',
		);
	}
	$res = rest_ensure_response( array( 'items' => $items ) );
	$res->header( 'Cache-Control', 'public, max-age=60' );
	return $res;
}

/* -------------------------------------------------------------------------
 * Admin helpers
 * ---------------------------------------------------------------------- */

/**
 * Serialize a category for the admin app.
 *
 * @param WP_Term $t Term.
 * @return array
 */
function vf_help_admin_cat( $t ) {
	$img = (int) get_term_meta( $t->term_id, 'vf_image', true );
	return array(
		'id'       => (int) $t->term_id,
		'name'     => $t->name,
		'slug'     => urldecode( $t->slug ),
		'desc'     => $t->description,
		'parent'   => (int) $t->parent,
		'order'    => (int) get_term_meta( $t->term_id, 'vf_order', true ),
		'image'    => $img,
		'imageUrl' => $img ? (string) wp_get_attachment_image_url( $img, 'thumbnail' ) : '',
		'url'      => vf_help_cat_url( $t->term_id ),
	);
}

/**
 * Serialize a guide row for lists.
 *
 * @param WP_Post $p Post.
 * @return array
 */
function vf_help_admin_row( $p ) {
	$term = vf_guide_term( $p->ID );
	return array(
		'id'      => (int) $p->ID,
		'title'   => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
		'status'  => $p->post_status,
		'term'    => $term ? (int) $term->term_id : 0,
		'cat'     => $term ? vf_help_root_term( $term->term_id ) : 0,
		'order'   => (int) $p->menu_order,
		'read'    => vf_guide_minutes( $p ),
		'excerpt' => $p->post_excerpt,
		'viewUrl' => 'publish' === $p->post_status ? get_permalink( $p ) : get_preview_post_link( $p ),
		'editUrl' => admin_url( 'admin.php?page=vf-help-guide&guide=' . $p->ID ),
		'canEdit' => current_user_can( 'edit_post', $p->ID ),
		'modified'=> vf_format_date( (int) get_post_modified_time( 'U', false, $p ) ),
	);
}

/**
 * Full admin state (categories, guides, features, settings).
 *
 * @return array
 */
function vf_help_admin_state() {
	$terms = get_terms( array( 'taxonomy' => 'help_category', 'hide_empty' => false ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	update_termmeta_cache( wp_list_pluck( $terms, 'term_id' ) );
	$terms = vf_sort_help_terms( $terms );

	$posts = get_posts( array(
		'post_type'              => 'help_article',
		'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'numberposts'            => 2000,
		'orderby'                => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	) );

	$features = get_terms( array( 'taxonomy' => 'help_feature', 'hide_empty' => false, 'orderby' => 'name' ) );
	$features = is_wp_error( $features ) ? array() : $features;

	return array(
		'cats'     => array_map( 'vf_help_admin_cat', $terms ),
		'guides'   => array_map( 'vf_help_admin_row', $posts ),
		'features' => array_map( function ( $f ) {
			return array( 'id' => (int) $f->term_id, 'name' => $f->name, 'desc' => $f->description );
		}, $features ),
		'settings' => array(
			'landing_title' => vf_opt( 'help', 'landing_title' ),
			'landing_desc'  => vf_opt( 'help', 'landing_desc' ),
		),
	);
}

/**
 * GET /help/admin/state.
 *
 * @return WP_REST_Response
 */
function vf_rest_help_state() {
	return rest_ensure_response( vf_help_admin_state() );
}

/**
 * Validate a help_category term id.
 *
 * @param mixed $id Id.
 * @return int 0 when invalid.
 */
function vf_help_valid_term( $id ) {
	$id = absint( $id );
	if ( ! $id ) {
		return 0;
	}
	$t = get_term( $id, 'help_category' );
	return ( $t && ! is_wp_error( $t ) ) ? (int) $t->term_id : 0;
}

/**
 * Next menu_order at the end of a category.
 *
 * @param int $cat_id Root term id.
 * @return int
 */
function vf_help_next_order( $cat_id ) {
	if ( ! $cat_id ) {
		return 0;
	}
	$last = get_posts( array(
		'post_type'      => 'help_article',
		'post_status'    => 'any',
		'numberposts'    => 1,
		'orderby'        => 'menu_order',
		'order'          => 'DESC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array( array( 'taxonomy' => 'help_category', 'terms' => $cat_id, 'include_children' => true ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	return $last ? (int) get_post_field( 'menu_order', $last[0] ) + 1 : 0;
}

/**
 * Full guide payload for the editor.
 *
 * @param WP_Post $p Post.
 * @return array
 */
function vf_help_guide_payload( $p ) {
	$sections = vf_get_sections( $p->ID );
	foreach ( $sections as &$s ) {
		if ( ! empty( $s['media']['id'] ) ) {
			$s['media']['name'] = basename( (string) get_attached_file( $s['media']['id'] ) );
			$s['media']['url']  = (string) wp_get_attachment_url( $s['media']['id'] );
		}
	}
	unset( $s );
	$row = vf_help_admin_row( $p );
	return array_merge( $row, array(
		'slug'     => urldecode( $p->post_name ),
		'readSet'  => (int) get_post_meta( $p->ID, '_vf_read_minutes', true ),
		'sections' => $sections,
		'seoTitle' => (string) get_post_meta( $p->ID, '_vf_seo_title', true ),
		'seoDesc'  => (string) get_post_meta( $p->ID, '_vf_seo_desc', true ),
		'route'    => wp_make_link_relative( get_permalink( $p ) ),
		'canPublish' => current_user_can( 'publish_help_articles' ),
		'canDelete'  => current_user_can( 'delete_post', $p->ID ),
	) );
}

/* -------------------------------------------------------------------------
 * Guides
 * ---------------------------------------------------------------------- */

/**
 * POST /help/guides — create a draft guide.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_create_guide( $req ) {
	$cat = vf_help_valid_term( $req['cat'] );
	if ( ! $cat ) {
		$first = get_terms( array( 'taxonomy' => 'help_category', 'hide_empty' => false, 'parent' => 0 ) );
		$first = is_wp_error( $first ) ? array() : vf_sort_help_terms( $first );
		$cat   = $first ? (int) $first[0]->term_id : 0;
	}
	$title = sanitize_text_field( (string) $req['title'] );
	$id    = wp_insert_post( array(
		'post_type'   => 'help_article',
		'post_status' => 'draft',
		'post_title'  => '' !== $title ? $title : __( 'راهنمای بدون عنوان', 'vidiform' ),
		'menu_order'  => vf_help_next_order( $cat ),
	), true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	if ( $cat ) {
		wp_set_object_terms( $id, array( $cat ), 'help_category' );
	}
	update_post_meta( $id, '_vf_sections', array() );
	return rest_ensure_response( array(
		'id'      => $id,
		'editUrl' => admin_url( 'admin.php?page=vf-help-guide&guide=' . $id ),
	) );
}

/**
 * Load & authorize a guide.
 *
 * @param int    $id  Id.
 * @param string $cap Meta cap.
 * @return WP_Post|WP_Error
 */
function vf_help_load_guide( $id, $cap = 'edit_post' ) {
	$p = get_post( (int) $id );
	if ( ! $p || 'help_article' !== $p->post_type ) {
		return new WP_Error( 'vf_not_found', __( 'راهنما پیدا نشد.', 'vidiform' ), array( 'status' => 404 ) );
	}
	if ( ! current_user_can( $cap, $p->ID ) ) {
		return new WP_Error( 'vf_forbidden', __( 'اجازه‌ی این کار را ندارید.', 'vidiform' ), array( 'status' => 403 ) );
	}
	return $p;
}

/**
 * GET /help/guides/{id}.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_get_guide( $req ) {
	$p = vf_help_load_guide( $req['id'] );
	return is_wp_error( $p ) ? $p : rest_ensure_response( vf_help_guide_payload( $p ) );
}

/**
 * POST /help/guides/{id} — save everything from the editor.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_save_guide( $req ) {
	$p = vf_help_load_guide( $req['id'] );
	if ( is_wp_error( $p ) ) {
		return $p;
	}
	$body = $req->get_json_params();
	$body = is_array( $body ) ? $body : array();

	$sections = vf_sanitize_sections( $body['sections'] ?? array() );
	$title    = sanitize_text_field( (string) ( $body['title'] ?? $p->post_title ) );
	if ( '' === $title ) {
		return new WP_Error( 'vf_title', __( 'عنوان راهنما را وارد کنید.', 'vidiform' ), array( 'status' => 400 ) );
	}

	$status = sanitize_key( (string) ( $body['status'] ?? $p->post_status ) );
	if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ) {
		$status = 'draft';
	}
	if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( 'publish_help_articles' ) ) {
		$status = 'pending';
	}

	$term    = vf_help_valid_term( $body['term'] ?? 0 );
	$old     = vf_guide_term( $p->ID );
	$old_cat = $old ? vf_help_root_term( $old->term_id ) : 0;
	$new_cat = $term ? vf_help_root_term( $term ) : 0;

	$update = array(
		'ID'           => $p->ID,
		'post_title'   => $title,
		'post_excerpt' => sanitize_textarea_field( (string) ( $body['excerpt'] ?? '' ) ),
		'post_status'  => $status,
		'post_content' => vf_sections_to_content( $sections ),
	);
	if ( isset( $body['slug'] ) && '' !== trim( (string) $body['slug'] ) ) {
		$update['post_name'] = sanitize_title( (string) $body['slug'] );
	}
	if ( $new_cat !== $old_cat ) {
		$update['menu_order'] = vf_help_next_order( $new_cat );
	}

	update_post_meta( $p->ID, '_vf_sections', $sections );
	update_post_meta( $p->ID, '_vf_read_minutes', absint( $body['readSet'] ?? 0 ) );
	update_post_meta( $p->ID, '_vf_seo_title', sanitize_text_field( (string) ( $body['seoTitle'] ?? '' ) ) );
	update_post_meta( $p->ID, '_vf_seo_desc', sanitize_textarea_field( (string) ( $body['seoDesc'] ?? '' ) ) );
	wp_set_object_terms( $p->ID, $term ? array( $term ) : array(), 'help_category' );

	$res = wp_update_post( wp_slash( $update ), true );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	vf_help_bump();
	return rest_ensure_response( vf_help_guide_payload( get_post( $p->ID ) ) );
}

/**
 * DELETE /help/guides/{id} — move to trash.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_delete_guide( $req ) {
	$p = vf_help_load_guide( $req['id'], 'delete_post' );
	if ( is_wp_error( $p ) ) {
		return $p;
	}
	wp_trash_post( $p->ID );
	vf_help_bump();
	return rest_ensure_response( array( 'deleted' => true ) );
}

/**
 * POST /help/guides/{id}/duplicate — deep copy (sections, media refs, hints, references, category).
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_duplicate_guide( $req ) {
	$p = vf_help_load_guide( $req['id'] );
	if ( is_wp_error( $p ) ) {
		return $p;
	}
	$sections = vf_get_sections( $p->ID );
	foreach ( $sections as &$s ) {
		$s['id'] = 's' . wp_generate_password( 6, false, false );
	}
	unset( $s );

	$new = wp_insert_post( wp_slash( array(
		'post_type'    => 'help_article',
		'post_status'  => 'draft',
		/* translators: %s: original title */
		'post_title'   => sprintf( __( '%s (کپی)', 'vidiform' ), $p->post_title ),
		'post_excerpt' => $p->post_excerpt,
		'post_content' => $p->post_content,
		'menu_order'   => (int) $p->menu_order,
	) ), true );
	if ( is_wp_error( $new ) ) {
		return $new;
	}
	update_post_meta( $new, '_vf_sections', $sections );
	foreach ( array( '_vf_read_minutes', '_vf_seo_title', '_vf_seo_desc' ) as $k ) {
		update_post_meta( $new, $k, get_post_meta( $p->ID, $k, true ) );
	}
	$terms = wp_get_object_terms( $p->ID, 'help_category', array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) ) {
		wp_set_object_terms( $new, $terms, 'help_category' );
	}

	// Place the copy directly after the original inside its category.
	$term = vf_guide_term( $p->ID );
	if ( $term ) {
		$root = vf_help_root_term( $term->term_id );
		$ids  = wp_list_pluck( wp_list_filter( vf_help_admin_state()['guides'], array( 'cat' => $root ) ), 'id' );
		$ids  = array_values( array_diff( $ids, array( $new ) ) );
		$pos  = array_search( $p->ID, $ids, true );
		array_splice( $ids, false === $pos ? count( $ids ) : $pos + 1, 0, array( $new ) );
		vf_help_apply_order( $ids );
	}
	vf_help_bump();
	return rest_ensure_response( array(
		'id'      => $new,
		'editUrl' => admin_url( 'admin.php?page=vf-help-guide&guide=' . $new ),
	) );
}

/**
 * Persist menu_order for a list of guide ids (0..n) without firing full save hooks.
 *
 * @param int[] $ids Ordered ids.
 */
function vf_help_apply_order( $ids ) {
	global $wpdb;
	foreach ( array_values( $ids ) as $i => $id ) {
		$wpdb->update( $wpdb->posts, array( 'menu_order' => $i ), array( 'ID' => (int) $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( (int) $id );
	}
}

/**
 * POST /help/guides/order — reorder and move guides between categories (drag & drop).
 * Body: { groups: [ { cat: termId, ids: [guideId, …] }, … ] }
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_order_guides( $req ) {
	$groups = $req->get_param( 'groups' );
	if ( ! is_array( $groups ) ) {
		return new WP_Error( 'vf_bad', __( 'داده‌ی نامعتبر.', 'vidiform' ), array( 'status' => 400 ) );
	}
	foreach ( $groups as $g ) {
		$cat = vf_help_valid_term( $g['cat'] ?? 0 );
		$ids = array_map( 'absint', is_array( $g['ids'] ?? null ) ? $g['ids'] : array() );
		if ( ! $cat ) {
			continue;
		}
		$ok = array();
		foreach ( $ids as $id ) {
			$p = get_post( $id );
			if ( ! $p || 'help_article' !== $p->post_type || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			$cur = vf_guide_term( $id );
			if ( ! $cur || vf_help_root_term( $cur->term_id ) !== $cat ) {
				wp_set_object_terms( $id, array( $cat ), 'help_category' );
			}
			$ok[] = $id;
		}
		vf_help_apply_order( $ok );
	}
	vf_help_bump();
	return rest_ensure_response( vf_help_admin_state() );
}

/* -------------------------------------------------------------------------
 * Categories
 * ---------------------------------------------------------------------- */

/**
 * POST /help/categories — create a main category or a sub-category.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_create_cat( $req ) {
	$parent = vf_help_valid_term( $req['parent'] );
	if ( $parent && get_term( $parent, 'help_category' )->parent ) {
		return new WP_Error( 'vf_depth', __( 'زیر‌دسته فقط زیر دسته‌ی اصلی ساخته می‌شود.', 'vidiform' ), array( 'status' => 400 ) );
	}
	$name = sanitize_text_field( (string) $req['name'] );
	if ( '' === $name ) {
		$name = $parent ? __( 'زیر‌دسته جدید', 'vidiform' ) : __( 'دسته‌ی جدید راهنما', 'vidiform' );
	}
	$base = $name;
	$i    = 2;
	while ( term_exists( $name, 'help_category', $parent ) ) {
		$name = $base . ' ' . vf_num( $i++ );
	}
	$args = array( 'parent' => $parent );
	$slug = sanitize_title( (string) $req['slug'] );
	if ( $slug ) {
		$args['slug'] = $slug;
	}
	$res = wp_insert_term( $name, 'help_category', $args );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$siblings = get_terms( array( 'taxonomy' => 'help_category', 'parent' => $parent, 'hide_empty' => false, 'fields' => 'ids' ) );
	update_term_meta( $res['term_id'], 'vf_order', is_array( $siblings ) ? count( $siblings ) : 0 );
	vf_help_bump();
	return rest_ensure_response( vf_help_admin_state() );
}

/**
 * POST /help/categories/{id} — update name / description / image / slug.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_update_cat( $req ) {
	$id = vf_help_valid_term( $req['id'] );
	if ( ! $id ) {
		return new WP_Error( 'vf_not_found', __( 'دسته پیدا نشد.', 'vidiform' ), array( 'status' => 404 ) );
	}
	$args = array();
	if ( null !== $req['name'] ) {
		$name = sanitize_text_field( (string) $req['name'] );
		if ( '' !== $name ) {
			$args['name'] = $name;
		}
	}
	if ( null !== $req['desc'] ) {
		$args['description'] = sanitize_textarea_field( (string) $req['desc'] );
	}
	if ( null !== $req['slug'] && '' !== trim( (string) $req['slug'] ) ) {
		$args['slug'] = sanitize_title( (string) $req['slug'] );
	}
	if ( $args ) {
		$res = wp_update_term( $id, 'help_category', $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
	}
	if ( null !== $req['image'] ) {
		$img = absint( $req['image'] );
		if ( $img && ! wp_attachment_is_image( $img ) ) {
			return new WP_Error( 'vf_image', __( 'فایل انتخاب‌شده تصویر نیست.', 'vidiform' ), array( 'status' => 400 ) );
		}
		update_term_meta( $id, 'vf_image', $img );
	}
	vf_help_bump();
	return rest_ensure_response( vf_help_admin_state() );
}

/**
 * DELETE /help/categories/{id}.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_delete_cat( $req ) {
	$id = vf_help_valid_term( $req['id'] );
	if ( ! $id ) {
		return new WP_Error( 'vf_not_found', __( 'دسته پیدا نشد.', 'vidiform' ), array( 'status' => 404 ) );
	}
	$term = get_term( $id, 'help_category' );
	// Guides of a removed sub-category move up to the parent category.
	if ( $term->parent ) {
		$ids = get_objects_in_term( $id, 'help_category' );
		foreach ( (array) $ids as $gid ) {
			wp_set_object_terms( (int) $gid, array( (int) $term->parent ), 'help_category' );
		}
	}
	wp_delete_term( $id, 'help_category' );
	vf_help_bump();
	return rest_ensure_response( vf_help_admin_state() );
}

/**
 * POST /help/categories/order — body { ids: [topLevelTermId…] } or { parent, ids }.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function vf_rest_help_order_cats( $req ) {
	$ids = array_map( 'absint', (array) $req->get_param( 'ids' ) );
	foreach ( array_values( $ids ) as $i => $id ) {
		if ( vf_help_valid_term( $id ) ) {
			update_term_meta( $id, 'vf_order', $i );
		}
	}
	vf_help_bump();
	return rest_ensure_response( vf_help_admin_state() );
}

/**
 * POST /help/categories/{id}/duplicate — copy a category with its sub-categories (guide links kept).
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_duplicate_cat( $req ) {
	$id = vf_help_valid_term( $req['id'] );
	if ( ! $id ) {
		return new WP_Error( 'vf_not_found', __( 'دسته پیدا نشد.', 'vidiform' ), array( 'status' => 404 ) );
	}
	$src = get_term( $id, 'help_category' );
	/* translators: %s: category name */
	$new = wp_insert_term( sprintf( __( '%s (کپی)', 'vidiform' ), $src->name ), 'help_category', array(
		'parent'      => (int) $src->parent,
		'description' => $src->description,
	) );
	if ( is_wp_error( $new ) ) {
		return $new;
	}
	update_term_meta( $new['term_id'], 'vf_image', (int) get_term_meta( $id, 'vf_image', true ) );
	update_term_meta( $new['term_id'], 'vf_order', (int) get_term_meta( $id, 'vf_order', true ) + 1 );
	$children = get_terms( array( 'taxonomy' => 'help_category', 'parent' => $id, 'hide_empty' => false ) );
	foreach ( is_wp_error( $children ) ? array() : $children as $c ) {
		$nc = wp_insert_term( $c->name, 'help_category', array( 'parent' => $new['term_id'], 'description' => $c->description, 'slug' => $c->slug . '-copy' ) );
		if ( ! is_wp_error( $nc ) ) {
			update_term_meta( $nc['term_id'], 'vf_order', (int) get_term_meta( $c->term_id, 'vf_order', true ) );
		}
	}
	vf_help_bump();
	return rest_ensure_response( vf_help_admin_state() );
}

/* -------------------------------------------------------------------------
 * Features, settings, seed
 * ---------------------------------------------------------------------- */

/**
 * POST /help/features[/{id}] — create or update a library feature.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function vf_rest_help_save_feature( $req ) {
	$name = sanitize_text_field( (string) $req['name'] );
	$desc = sanitize_textarea_field( (string) $req['desc'] );
	if ( '' === $name ) {
		return new WP_Error( 'vf_name', __( 'عنوان ویژگی را وارد کنید.', 'vidiform' ), array( 'status' => 400 ) );
	}
	if ( $req['id'] ) {
		$res = wp_update_term( absint( $req['id'] ), 'help_feature', array( 'name' => $name, 'description' => $desc ) );
	} else {
		$exists = term_exists( $name, 'help_feature' );
		$res    = $exists ? $exists : wp_insert_term( $name, 'help_feature', array( 'description' => $desc ) );
	}
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	vf_help_bump();
	$t = get_term( (int) $res['term_id'], 'help_feature' );
	return rest_ensure_response( array( 'id' => (int) $t->term_id, 'name' => $t->name, 'desc' => $t->description ) );
}

/**
 * POST /help/settings — landing texts (live-saved from the categories screen).
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function vf_rest_help_settings( $req ) {
	$input = array();
	foreach ( array( 'landing_title', 'landing_desc' ) as $k ) {
		if ( null !== $req[ $k ] ) {
			$input[ $k ] = (string) $req[ $k ];
		}
	}
	update_option( 'vf_help', vf_sanitize_settings( 'help', wp_slash( $input ) ) );
	return rest_ensure_response( array( 'saved' => true ) );
}

/**
 * POST /help/seed — import the default Help Center structure.
 *
 * @return WP_REST_Response
 */
function vf_rest_help_seed() {
	$n = vf_help_seed();
	return rest_ensure_response( array( 'created' => $n, 'state' => vf_help_admin_state() ) );
}
