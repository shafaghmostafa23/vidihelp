<?php
/**
 * Guide sections: schema, sanitization and rendering.
 *
 * A guide is an ordered list of sections, exactly as in the VidiForm Content Architecture:
 *  {
 *    id:     string   stable id
 *    title:  string   optional heading
 *    desc:   string   text; supports inline guide links written as [[label|guideId]]
 *    media:  {id:int, type:image|video|audio} | null     (WordPress Media Library attachment)
 *    hint:   {title, desc, color:blue|green|purple} | null
 *    feature:int      help_feature term id (0 = none)
 *    vf:     {title, url} | null                          related/embedded VidiForm
 *    tpl:    {title, url, desc, premium:bool} | null      template reference (preview sheet)
 *    inline: {label, guide:int} | null                    related guide button
 *  }
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed inline HTML inside section text.
 *
 * @return array
 */
function vf_section_kses() {
	return array(
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'code'   => array(),
		'br'     => array(),
		'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
	);
}

/**
 * Sanitize a list of sections coming from the admin editor.
 *
 * @param mixed $raw Raw array (decoded JSON).
 * @return array
 */
function vf_sanitize_sections( $raw ) {
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$out = array();
	foreach ( array_slice( array_values( $raw ), 0, 200 ) as $s ) {
		if ( ! is_array( $s ) ) {
			continue;
		}
		$sec = array(
			'id'      => isset( $s['id'] ) ? substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $s['id'] ), 0, 40 ) : '',
			'title'   => isset( $s['title'] ) ? sanitize_text_field( $s['title'] ) : '',
			'desc'    => isset( $s['desc'] ) ? trim( wp_kses( str_replace( "\r", '', (string) $s['desc'] ), vf_section_kses() ) ) : '',
			'media'   => null,
			'hint'    => null,
			'feature' => isset( $s['feature'] ) ? absint( $s['feature'] ) : 0,
			'vf'      => null,
			'tpl'     => null,
			'inline'  => null,
		);
		if ( '' === $sec['id'] ) {
			$sec['id'] = 's' . wp_generate_password( 6, false, false );
		}
		if ( ! empty( $s['media']['id'] ) && in_array( $s['media']['type'] ?? '', array( 'image', 'video', 'audio' ), true ) ) {
			$att = absint( $s['media']['id'] );
			if ( $att && 'attachment' === get_post_type( $att ) ) {
				$sec['media'] = array( 'id' => $att, 'type' => $s['media']['type'] );
			}
		}
		if ( is_array( $s['hint'] ?? null ) ) {
			$sec['hint'] = array(
				'title' => sanitize_text_field( $s['hint']['title'] ?? '' ),
				'desc'  => sanitize_textarea_field( $s['hint']['desc'] ?? '' ),
				'color' => in_array( $s['hint']['color'] ?? '', array( 'blue', 'green', 'purple' ), true ) ? $s['hint']['color'] : 'purple',
			);
		}
		if ( is_array( $s['vf'] ?? null ) && ( ! empty( $s['vf']['title'] ) || ! empty( $s['vf']['url'] ) ) ) {
			$sec['vf'] = array(
				'title' => sanitize_text_field( $s['vf']['title'] ?? '' ),
				'url'   => esc_url_raw( $s['vf']['url'] ?? '', array( 'https', 'http' ) ),
			);
		}
		if ( is_array( $s['tpl'] ?? null ) && ( ! empty( $s['tpl']['title'] ) || ! empty( $s['tpl']['url'] ) ) ) {
			$sec['tpl'] = array(
				'title'   => sanitize_text_field( $s['tpl']['title'] ?? '' ),
				'url'     => esc_url_raw( $s['tpl']['url'] ?? '', array( 'https', 'http' ) ),
				'desc'    => sanitize_textarea_field( $s['tpl']['desc'] ?? '' ),
				'premium' => ! empty( $s['tpl']['premium'] ),
			);
		}
		if ( is_array( $s['inline'] ?? null ) && ! empty( $s['inline']['guide'] ) ) {
			$sec['inline'] = array(
				'label' => sanitize_text_field( $s['inline']['label'] ?? '' ),
				'guide' => absint( $s['inline']['guide'] ),
			);
		}
		$out[] = $sec;
	}
	return $out;
}

/**
 * Get stored sections for a guide.
 *
 * @param int $post_id Post id.
 * @return array
 */
function vf_get_sections( $post_id ) {
	$s = get_post_meta( $post_id, '_vf_sections', true );
	return is_array( $s ) ? $s : array();
}

/**
 * Guide ids referenced by a section list ([[..|id]] links + inline buttons).
 *
 * @param array $sections Sections.
 * @return int[]
 */
function vf_sections_guide_refs( $sections ) {
	$ids = array();
	foreach ( $sections as $s ) {
		if ( preg_match_all( '/\[\[[^|\]]+\|(\d+)\]\]/u', (string) $s['desc'], $m ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
		}
		if ( ! empty( $s['inline']['guide'] ) ) {
			$ids[] = (int) $s['inline']['guide'];
		}
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Convert section text into paragraphs of safe HTML, turning [[label|id]] into
 * inline-guide buttons (front end) or plain links (post_content).
 *
 * @param string $text  Sanitized text.
 * @param string $mode  'button' | 'link'.
 * @return string HTML (already safe).
 */
function vf_section_text_html( $text, $mode = 'button' ) {
	$text  = wp_kses( (string) $text, vf_section_kses() );
	$paras = preg_split( "/\n\s*\n/u", trim( $text ) );
	$html  = '';
	foreach ( $paras as $p ) {
		if ( '' === trim( $p ) ) {
			continue;
		}
		$p = nl2br( $p, false );
		$p = preg_replace_callback(
			'/\[\[([^|\]]+)\|(\d+)\]\]/u',
			function ( $m ) use ( $mode ) {
				$label = esc_html( wp_strip_all_tags( $m[1] ) );
				$id    = (int) $m[2];
				if ( 'link' === $mode ) {
					$url = get_permalink( $id );
					return $url ? '<a href="' . esc_url( $url ) . '">' . $label . '</a>' : $label;
				}
				return '<button type="button" class="vf-inline-ref" data-guide="' . esc_attr( $id ) . '" aria-haspopup="dialog">' . $label . '</button>';
			},
			$p
		);
		$html .= '<p class="vf-sec__text">' . $p . '</p>';
	}
	return $html;
}

/**
 * Build plain semantic HTML for post_content (search index, feeds, SEO plugins).
 *
 * @param array $sections Sections.
 * @return string
 */
function vf_sections_to_content( $sections ) {
	$html = '';
	foreach ( $sections as $s ) {
		if ( '' !== $s['title'] ) {
			$html .= '<h2>' . esc_html( $s['title'] ) . "</h2>\n";
		}
		if ( '' !== $s['desc'] ) {
			$html .= vf_section_text_html( $s['desc'], 'link' ) . "\n";
		}
		if ( $s['hint'] ) {
			$html .= '<aside><strong>' . esc_html( $s['hint']['title'] ) . '</strong> ' . esc_html( $s['hint']['desc'] ) . "</aside>\n";
		}
		if ( $s['media'] && 'image' === $s['media']['type'] ) {
			$html .= wp_get_attachment_image( $s['media']['id'], 'large' ) . "\n";
		}
	}
	return $html;
}

/**
 * Render sections on the article page.
 *
 * @param array $sections Sections.
 * @param array $features help_feature data map id => [name, desc] (filled by reference).
 */
function vf_render_sections( $sections, &$features ) {
	foreach ( $sections as $s ) {
		echo '<div class="vf-sec" id="' . esc_attr( 'sec-' . $s['id'] ) . '">';

		if ( '' !== $s['title'] ) {
			echo '<h2 class="vf-sec__title">' . esc_html( $s['title'] ) . '</h2>';
		}
		if ( '' !== $s['desc'] ) {
			echo vf_section_text_html( $s['desc'], 'button' ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from kses'd text + escaped parts.
		}

		if ( $s['hint'] ) {
			printf(
				'<aside class="vf-hint vf-hint--%1$s"><span class="vf-hint__dot" aria-hidden="true"></span><div><div class="vf-hint__title">%2$s</div><div class="vf-hint__desc">%3$s</div></div></aside>',
				esc_attr( $s['hint']['color'] ),
				esc_html( $s['hint']['title'] ),
				nl2br( esc_html( $s['hint']['desc'] ), false )
			);
		}

		if ( $s['media'] ) {
			vf_render_section_media( $s['media'] );
		}

		$feature = $s['feature'] ? get_term( $s['feature'], 'help_feature' ) : null;
		$has_row = ( $feature && ! is_wp_error( $feature ) ) || $s['inline'] || $s['tpl'];
		if ( $has_row ) {
			echo '<div class="vf-sec__actions">';
			if ( $feature && ! is_wp_error( $feature ) ) {
				$features[ $feature->term_id ] = array( 'name' => $feature->name, 'desc' => $feature->description );
				printf(
					'<button type="button" class="vf-feature-btn" data-feature="%1$d" aria-haspopup="dialog">%2$s<span>%3$s</span></button>',
					(int) $feature->term_id,
					vf_icon( 'info', 14, array( 'stroke-width' => '2.2' ) ), // phpcs:ignore WordPress.Security.EscapeOutput
					esc_html( $feature->name )
				);
			}
			if ( $s['inline'] && get_post_status( $s['inline']['guide'] ) === 'publish' ) {
				printf(
					'<button type="button" class="vf-inline-btn" data-guide="%1$d" aria-haspopup="dialog">%2$s</button>',
					(int) $s['inline']['guide'],
					esc_html( $s['inline']['label'] ? $s['inline']['label'] : get_the_title( $s['inline']['guide'] ) )
				);
			}
			if ( $s['tpl'] ) {
				printf(
					'<button type="button" class="vf-tpl-btn" data-tpl="%1$s" aria-haspopup="dialog">%2$s<span>%3$s</span></button>',
					esc_attr( wp_json_encode( $s['tpl'] ) ),
					vf_icon( 'templates', 14 ), // phpcs:ignore WordPress.Security.EscapeOutput
					/* translators: %s template title */
					esc_html( sprintf( __( 'قالب: %s', 'vidiform' ), $s['tpl']['title'] ? $s['tpl']['title'] : $s['tpl']['url'] ) )
				);
			}
			echo '</div>';
		}

		if ( $s['vf'] ) {
			vf_render_vidiform_embed( $s['vf'] );
		}

		echo '</div>';
	}
}

/**
 * Render a media block (image / video / audio) in the source's frames.
 *
 * @param array $media Media data.
 */
function vf_render_section_media( $media ) {
	$id  = (int) $media['id'];
	$url = wp_get_attachment_url( $id );
	if ( ! $url ) {
		return;
	}
	$name = get_the_title( $id );
	switch ( $media['type'] ) {
		case 'image':
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
			echo '<figure class="vf-media vf-media--image">';
			echo wp_get_attachment_image( $id, 'large', false, array( 'alt' => $alt ? $alt : $name, 'loading' => 'lazy', 'decoding' => 'async' ) );
			$caption = wp_get_attachment_caption( $id );
			if ( $caption ) {
				echo '<figcaption>' . esc_html( $caption ) . '</figcaption>';
			}
			echo '</figure>';
			break;
		case 'video':
			printf(
				'<div class="vf-media vf-media--video"><video controls preload="metadata" playsinline src="%1$s" aria-label="%2$s"></video></div>',
				esc_url( $url ),
				esc_attr( $name )
			);
			break;
		case 'audio':
			printf(
				'<div class="vf-media vf-media--audio"><span class="vf-media__audio-icon" aria-hidden="true">%3$s</span><audio controls preload="none" src="%1$s" aria-label="%2$s"></audio><span class="vf-media__name">%4$s</span></div>',
				esc_url( $url ),
				esc_attr( $name ),
				vf_icon( 'play-card', 15 ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $name )
			);
			break;
	}
}

/**
 * Embedded VidiForm card (phone frame + title), live iframe when a URL is set.
 *
 * @param array $vf {title,url}.
 */
function vf_render_vidiform_embed( $vf ) {
	?>
	<div class="vf-embed">
		<div class="vf-embed__phone">
			<div class="vf-embed__screen">
				<?php if ( ! empty( $vf['url'] ) ) : ?>
					<iframe src="<?php echo esc_url( $vf['url'] ); ?>" title="<?php echo esc_attr( $vf['title'] ? $vf['title'] : __( 'ویدی‌فرم', 'vidiform' ) ); ?>" loading="lazy" allow="camera; microphone; autoplay; fullscreen" referrerpolicy="strict-origin-when-cross-origin"></iframe>
				<?php else : ?>
					<div class="vf-embed__ph-media"></div>
					<div class="vf-embed__ph-body"><div class="vf-embed__ph-line"></div><div class="vf-embed__ph-btn"></div></div>
				<?php endif; ?>
			</div>
		</div>
		<div class="vf-embed__text">
			<div class="vf-embed__title"><?php echo esc_html( $vf['title'] ? $vf['title'] : __( 'ویدی‌فرم', 'vidiform' ) ); ?></div>
			<div class="vf-embed__desc"><?php esc_html_e( 'این ویدی‌فرم به‌صورت زنده داخل مطلب اجرا می‌شود.', 'vidiform' ); ?></div>
			<?php if ( ! empty( $vf['url'] ) ) : ?>
				<a class="vf-btn vf-btn--link" href="<?php echo esc_url( $vf['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'باز کردن در صفحه‌ی جدید', 'vidiform' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
