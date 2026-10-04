<?php
/**
 * VidiForm theme bootstrap.
 *
 * The theme hosts two independent content systems:
 *  - Help Center  (help_article CPT + help_category / help_feature taxonomies, routes under /help/)
 *  - Blog         (core post + category + post_tag + users, routes under /blog/)
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

define( 'VF_VERSION', '1.2.0' );
define( 'VF_DIR', get_template_directory() );
define( 'VF_URI', get_template_directory_uri() );

$vf_includes = array(
	'inc/helpers.php',
	'inc/icons.php',
	'inc/settings.php',
	'inc/setup.php',
	'inc/roles.php',
	'inc/assets.php',
	'inc/seo.php',

	// Help Center.
	'inc/help-center/post-types.php',
	'inc/help-center/sections.php',
	'inc/help-center/data.php',
	'inc/help-center/routing.php',
	'inc/help-center/template-tags.php',
	'inc/help-center/rest.php',
	'inc/help-center/seed.php',

	// Blog.
	'inc/blog/setup.php',
	'inc/blog/template-tags.php',

	// Admin (both systems, kept in separate files).
	'inc/admin/shell.php',
	'inc/admin/help-admin.php',
	'inc/admin/blog-admin.php',
	'inc/admin/blog-content-studio.php',
	'inc/admin/blog-metabox.php',
	'inc/admin/general-settings.php',
);

foreach ( $vf_includes as $vf_file ) {
	require_once VF_DIR . '/' . $vf_file;
}
unset( $vf_includes, $vf_file );
