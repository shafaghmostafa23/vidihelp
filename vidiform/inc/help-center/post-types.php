<?php
/**
 * Help Center content types.
 *
 *  help_article   CPT  — «راهنما» (guide). Ordered with menu_order inside its category.
 *  help_category  tax  — hierarchical: top-level = main category, child = optional sub-category.
 *                         Term meta: vf_order (int), vf_image (attachment id).
 *  help_feature   tax  — non-public «کتابخانه‌ی ویژگی‌ها» (name + description) referenced by sections.
 *
 * Post meta on help_article:
 *  _vf_sections     array  structured sections (see sections.php)
 *  _vf_read_minutes int    configurable reading time (0 = automatic)
 *  _vf_seo_title    string optional SEO title
 *  _vf_seo_desc     string optional meta description
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Help Center base path (without slashes).
 *
 * @return string
 */
function vf_help_base() {
	return trim( (string) apply_filters( 'vf_help_base', 'help' ), '/' );
}

/**
 * Register CPT + taxonomies.
 */
function vf_register_help_types() {
	register_post_type( 'help_article', array(
		'labels'              => array(
			'name'               => __( 'راهنماها', 'vidiform' ),
			'singular_name'      => __( 'راهنما', 'vidiform' ),
			'add_new'            => __( 'راهنمای جدید', 'vidiform' ),
			'add_new_item'       => __( 'افزودن راهنمای جدید', 'vidiform' ),
			'edit_item'          => __( 'ویرایش راهنما', 'vidiform' ),
			'view_item'          => __( 'مشاهده راهنما', 'vidiform' ),
			'search_items'       => __( 'جست‌وجوی راهنما', 'vidiform' ),
			'not_found'          => __( 'راهنمایی پیدا نشد', 'vidiform' ),
			'all_items'          => __( 'همه‌ی راهنماها', 'vidiform' ),
			'archives'           => __( 'مرکز راهنما', 'vidiform' ),
		),
		'public'              => true,
		'publicly_queryable'  => true,
		'exclude_from_search' => true, // Blog/global search never mixes in help articles; Help search is separate.
		'show_ui'             => true,
		'show_in_menu'        => false, // Managed from the dedicated Help Center admin.
		'show_in_nav_menus'   => true,
		'show_in_rest'        => true,
		'rest_base'           => 'help-articles',
		'hierarchical'        => false,
		'has_archive'         => false, // /help/ is routed by routing.php.
		'rewrite'             => false, // Permalinks are built by routing.php.
		'query_var'           => 'help_article',
		'capability_type'     => array( 'help_article', 'help_articles' ),
		'map_meta_cap'        => true,
		'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'revisions', 'page-attributes', 'custom-fields' ),
		'menu_icon'           => 'dashicons-sos',
	) );

	$term_caps = array(
		'manage_terms' => 'manage_help_center',
		'edit_terms'   => 'manage_help_center',
		'delete_terms' => 'manage_help_center',
		'assign_terms' => 'edit_help_articles',
	);

	register_taxonomy( 'help_category', array( 'help_article' ), array(
		'labels'            => array(
			'name'          => __( 'دسته‌بندی راهنما', 'vidiform' ),
			'singular_name' => __( 'دسته‌ی راهنما', 'vidiform' ),
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_ui'           => false,
		'show_in_nav_menus' => true,
		'show_in_rest'      => true,
		'show_admin_column' => false,
		'rewrite'           => false,
		'query_var'         => 'help_category',
		'capabilities'      => $term_caps,
	) );

	register_taxonomy( 'help_feature', array( 'help_article' ), array(
		'labels'            => array(
			'name'          => __( 'ویژگی‌ها', 'vidiform' ),
			'singular_name' => __( 'ویژگی', 'vidiform' ),
		),
		'public'            => false,
		'show_ui'           => false,
		'show_in_rest'      => false,
		'hierarchical'      => false,
		'rewrite'           => false,
		'query_var'         => false,
		'capabilities'      => $term_caps,
	) );

	$auth = function () {
		return current_user_can( 'edit_help_articles' );
	};
	register_post_meta( 'help_article', '_vf_read_minutes', array(
		'type'              => 'integer',
		'single'            => true,
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth,
	) );
	foreach ( array( '_vf_seo_title', '_vf_seo_desc' ) as $key ) {
		register_post_meta( 'help_article', $key, array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth,
		) );
	}
	register_term_meta( 'help_category', 'vf_order', array( 'type' => 'integer', 'single' => true, 'sanitize_callback' => function ( $v ) {
		return (int) $v;
	} ) );
	register_term_meta( 'help_category', 'vf_image', array( 'type' => 'integer', 'single' => true, 'sanitize_callback' => function ( $v ) {
		return absint( $v );
	} ) );
}
add_action( 'init', 'vf_register_help_types', 0 );
