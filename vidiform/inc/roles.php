<?php
/**
 * Roles & capabilities.
 *
 * Help Center content uses its own capability type ("help_article"), so its permissions are
 * completely independent from Blog posts. A dedicated "Help Center Editor" role can manage the
 * Help Center without touching the Blog (and vice versa, Blog authors/editors use core caps).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Primitive capabilities for the help_article capability type.
 *
 * @return string[]
 */
function vf_help_caps() {
	return array(
		'edit_help_articles',
		'edit_others_help_articles',
		'edit_published_help_articles',
		'edit_private_help_articles',
		'publish_help_articles',
		'delete_help_articles',
		'delete_others_help_articles',
		'delete_published_help_articles',
		'delete_private_help_articles',
		'read_private_help_articles',
		'manage_help_center',
	);
}

/**
 * Create/refresh roles and grant caps.
 */
function vf_install_roles() {
	$help_caps = vf_help_caps();

	foreach ( array( 'administrator', 'editor' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $help_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	$caps = array(
		'read'         => true,
		'upload_files' => true,
	);
	foreach ( $help_caps as $cap ) {
		$caps[ $cap ] = true;
	}
	remove_role( 'vf_help_editor' );
	add_role( 'vf_help_editor', __( 'ویراستار مرکز راهنما', 'vidiform' ), $caps );

	update_option( 'vf_roles_version', VF_VERSION );
}

/**
 * Keep roles in sync when the theme is updated without re-activation.
 */
function vf_maybe_install_roles() {
	if ( get_option( 'vf_roles_version' ) !== VF_VERSION ) {
		vf_install_roles();
	}
}
add_action( 'init', 'vf_maybe_install_roles', 5 );
