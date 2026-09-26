<?php
/**
 * Default footer: delegates to the Help Center or Blog footer for the current section.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_template_part( 'help' === vf_section() ? 'footer-help' : 'footer-blog' );
