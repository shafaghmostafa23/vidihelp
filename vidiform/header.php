<?php
/**
 * Default header: delegates to the Help Center or Blog header for the current section
 * (used when plugins call get_header() without a name).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_template_part( 'help' === vf_section() ? 'header-help' : 'header-blog' );
