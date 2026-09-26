<?php
/**
 * Inline SVG icon set (paths taken from the VidiForm HTML source, lucide-style strokes).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return icon inner markup map.
 *
 * @return array<string,string>
 */
function vf_icon_paths() {
	return array(
		'help'      => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 013.9-1.6c1.6 1 1 2.8-.4 3.4-.7.3-1.1.9-1.1 1.7"/><path d="M12 17h.01"/>',
		'video'     => '<rect x="2" y="6" width="13" height="12" rx="3"/><path d="M15 11l6-3v8l-6-3z"/>',
		'play-card' => '<rect x="3" y="4" width="18" height="14" rx="3"/><path d="M10 9l5 2.5-5 2.5z"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21"/>',
		'chev-back' => '<path d="M15 6l-6 6 6 6"/>',
		'chev-fwd'  => '<path d="M9 6l6 6-6 6"/>',
		'chev-down' => '<path d="M6 9l6 6 6-6"/>',
		'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M12 11v5"/>',
		'templates' => '<rect x="3" y="3" width="18" height="6" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="14" y="13" width="7" height="8" rx="2"/>',
		'grid'      => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
		'grid3'     => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/>',
		'book'      => '<path d="M4 5.5A1.5 1.5 0 015.5 4H19v16H5.5A1.5 1.5 0 014 18.5z"/><path d="M8 8h7M8 12h5"/>',
		'list'      => '<path d="M4 6h16M7 12h13M10 18h10"/>',
		'shield'    => '<path d="M12 3l7 3.5v5c0 4-3 7.2-7 8.5-4-1.3-7-4.5-7-8.5v-5z"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7"/>',
		'drag'      => '<path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/>',
		'user'      => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/>',
		'card'      => '<rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="M2.5 10h19"/>',
		'arrow'     => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'      => '<path d="M20 14.5A8 8 0 019.5 4a8 8 0 1010.5 10.5z"/>',
		'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'tag'       => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
		'folder'    => '<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
		'image'     => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="M21 16l-5-5-9 9"/>',
		'gear'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>',
		'star'      => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'link'      => '<path d="M10 13a5 5 0 007 0l3-3a5 5 0 00-7-7l-1 1"/><path d="M14 11a5 5 0 00-7 0l-3 3a5 5 0 007 7l1-1"/>',
		'trash'     => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
		'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'home'      => '<path d="M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z"/>',
		'wp'        => '<circle cx="12" cy="12" r="9"/><path d="M4.5 8.5l4 11M9 6.5h6M11.5 6.5l4 12.5 3-9"/>',
		'share'     => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.4M8.2 13.2l7.6 4.4"/>',
		'telegram'  => '<path d="M21 4L3 11l6 2 2 6 3-4 5 4z"/><path d="M9 13l12-9"/>',
		'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
		'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 014 0v4M12 10v7"/>',
		'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1112 20a8 8 0 01-4-1z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 1c-1 0-2.5-1.5-2.5-2.5l1-1-1-2z"/>',
	);
}

/**
 * Render an SVG icon.
 *
 * @param string $name  Icon name.
 * @param int    $size  Size in px.
 * @param array  $attrs Extra attributes (stroke-width, class, fill...).
 * @return string
 */
function vf_icon( $name, $size = 18, $attrs = array() ) {
	$paths = vf_icon_paths();
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	$defaults = array(
		'width'           => $size,
		'height'          => $size,
		'viewBox'         => '0 0 24 24',
		'fill'            => 'none',
		'stroke'          => 'currentColor',
		'stroke-width'    => '2',
		'stroke-linecap'  => 'round',
		'stroke-linejoin' => 'round',
		'aria-hidden'     => 'true',
		'focusable'       => 'false',
	);
	$attrs = array_merge( $defaults, $attrs );
	$out   = '<svg';
	foreach ( $attrs as $k => $v ) {
		$out .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return $out . '>' . $paths[ $name ] . '</svg>';
}

/**
 * Echo an icon (markup is static and trusted).
 *
 * @param string $name  Icon.
 * @param int    $size  Size.
 * @param array  $attrs Attrs.
 */
function vf_the_icon( $name, $size = 18, $attrs = array() ) {
	echo vf_icon( $name, $size, $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG built with esc_attr.
}
