<?php
/**
 * Blog template tags.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Primary category of a post.
 *
 * @param int|null $post_id Post.
 * @return WP_Term|null
 */
function vf_post_primary_cat( $post_id = null ) {
	$cats = get_the_category( $post_id );
	return $cats ? $cats[0] : null;
}

/**
 * Post card (grid).
 *
 * @param WP_Post|int|null $post    Post.
 * @param string           $variant card|featured|row.
 * @param int              $level   Heading level.
 */
function vf_post_card( $post = null, $variant = 'card', $level = 3 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$url   = get_permalink( $post );
	$cat   = vf_post_primary_cat( $post->ID );
	$tag   = 'h' . vf_clamp_int( $level, 2, 4 );
	$size  = 'featured' === $variant ? 'vf-hero' : 'vf-card';
	?>
	<article class="vf-post-card vf-post-card--<?php echo esc_attr( $variant ); ?>">
		<a class="vf-post-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			if ( has_post_thumbnail( $post ) ) {
				echo get_the_post_thumbnail( $post, $size, array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) );
			} else {
				echo '<span class="vf-post-card__ph">' . vf_icon( 'video', 30, array( 'stroke-width' => '1.6' ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</a>
		<div class="vf-post-card__body">
			<?php if ( $cat ) : ?>
				<a class="vf-pill vf-post-card__cat" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
			<?php endif; ?>
			<<?php echo esc_html( $tag ); ?> class="vf-post-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></<?php echo esc_html( $tag ); ?>>
			<?php if ( 'row' !== $variant ) : ?>
				<p class="vf-post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 'featured' === $variant ? 34 : 22 ) ); ?></p>
			<?php endif; ?>
			<?php vf_post_meta( $post ); ?>
		</div>
	</article>
	<?php
}

/**
 * Meta line: author · date · reading time.
 *
 * @param WP_Post $post       Post.
 * @param bool    $with_avatar Show avatar.
 */
function vf_post_meta( $post, $with_avatar = false ) {
	$author = (int) $post->post_author;
	?>
	<div class="vf-post-meta">
		<?php if ( $with_avatar ) : ?>
			<?php echo get_avatar( $author, 32, '', '', array( 'class' => 'vf-post-meta__avatar' ) ); ?>
		<?php endif; ?>
		<a class="vf-post-meta__author" href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $author ) ); ?></a>
		<span aria-hidden="true">·</span>
		<time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( vf_post_date( $post ) ); ?></time>
		<span aria-hidden="true">·</span>
		<span>
			<?php
			/* translators: %s: reading time */
			echo esc_html( sprintf( __( '%s مطالعه', 'vidiform' ), vf_minutes_label( vf_post_minutes( $post ) ) ) );
			?>
		</span>
	</div>
	<?php
}

/**
 * Breadcrumb items for blog views.
 *
 * @return array[]
 */
function vf_blog_breadcrumb_items() {
	$items = array( array( __( 'وبلاگ', 'vidiform' ), vf_blog_home_url() ) );
	if ( is_singular( 'post' ) ) {
		$cat = vf_post_primary_cat( get_queried_object_id() );
		if ( $cat ) {
			foreach ( array_reverse( get_ancestors( $cat->term_id, 'category', 'taxonomy' ) ) as $anc ) {
				$t       = get_term( $anc, 'category' );
				$items[] = array( $t->name, get_category_link( $t ) );
			}
			$items[] = array( $cat->name, get_category_link( $cat ) );
		}
		$items[] = array( get_the_title( get_queried_object_id() ), null );
	} elseif ( is_category() ) {
		$cat = get_queried_object();
		foreach ( array_reverse( get_ancestors( $cat->term_id, 'category', 'taxonomy' ) ) as $anc ) {
			$t       = get_term( $anc, 'category' );
			$items[] = array( $t->name, get_category_link( $t ) );
		}
		$items[] = array( $cat->name, null );
	} elseif ( is_tag() ) {
		$items[] = array( '#' . single_tag_title( '', false ), null );
	} elseif ( is_author() ) {
		$items[] = array( get_the_author_meta( 'display_name', get_queried_object_id() ), null );
	} elseif ( is_search() ) {
		$items[] = array( __( 'نتایج جست‌وجو', 'vidiform' ), null );
	} elseif ( is_date() ) {
		$items[] = array( wp_strip_all_tags( get_the_archive_title() ), null );
	} elseif ( is_page() ) {
		$items[] = array( get_the_title(), null );
	}
	return $items;
}

/**
 * Blog search form.
 *
 * @param string $id    Input id.
 * @param string $class Extra class.
 */
function vf_blog_search_form( $id = 'vf-blog-q', $class = '' ) {
	?>
	<form class="vf-search <?php echo esc_attr( $class ); ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'جست‌وجو در وبلاگ', 'vidiform' ); ?></label>
		<span class="vf-search__icon"><?php vf_the_icon( 'search', 18, array( 'stroke-width' => '2.2' ) ); ?></span>
		<input id="<?php echo esc_attr( $id ); ?>" class="vf-search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'جست‌وجو در مقاله‌ها…', 'vidiform' ); ?>" enterkeyhint="search">
	</form>
	<?php
}

/**
 * Top-level categories navigation (chips).
 *
 * @param int $active Active term id.
 */
function vf_blog_category_nav( $active = 0 ) {
	$cats = get_categories( array(
		'parent'     => 0,
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 12,
	) );
	if ( ! $cats ) {
		return;
	}
	?>
	<nav class="vf-chips vf-blog-cats" aria-label="<?php esc_attr_e( 'دسته‌بندی‌های وبلاگ', 'vidiform' ); ?>">
		<a class="vf-chip<?php echo is_home() ? ' is-active' : ''; ?>" href="<?php echo esc_url( vf_blog_home_url() ); ?>"<?php echo is_home() ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'همه', 'vidiform' ); ?></a>
		<?php foreach ( $cats as $c ) : ?>
			<a class="vf-chip<?php echo $c->term_id === $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo $c->term_id === $active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Share buttons for a post.
 *
 * @param WP_Post $post Post.
 */
function vf_share_buttons( $post ) {
	$url   = rawurlencode( get_permalink( $post ) );
	$title = rawurlencode( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );
	$links = array(
		'telegram' => array( 'https://t.me/share/url?url=' . $url . '&text=' . $title, __( 'تلگرام', 'vidiform' ) ),
		'whatsapp' => array( 'https://wa.me/?text=' . $title . '%20' . $url, __( 'واتس‌اپ', 'vidiform' ) ),
		'x'        => array( 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title, __( 'ایکس', 'vidiform' ) ),
		'linkedin' => array( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url, __( 'لینکدین', 'vidiform' ) ),
	);
	?>
	<div class="vf-share">
		<span class="vf-share__label"><?php esc_html_e( 'اشتراک‌گذاری', 'vidiform' ); ?></span>
		<?php foreach ( $links as $icon => $l ) : ?>
			<a class="vf-btn vf-btn--icon" href="<?php echo esc_url( $l[0] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( sprintf( /* translators: %s network */ __( 'اشتراک در %s', 'vidiform' ), $l[1] ) ); ?>"><?php vf_the_icon( $icon, 17 ); ?></a>
		<?php endforeach; ?>
		<button type="button" class="vf-btn vf-btn--icon" data-vf-copy="<?php echo esc_url( get_permalink( $post ) ); ?>" aria-label="<?php esc_attr_e( 'کپی لینک', 'vidiform' ); ?>"><?php vf_the_icon( 'link', 17 ); ?></button>
	</div>
	<?php
}

/**
 * Author box.
 *
 * @param int $author_id Author.
 */
function vf_author_box( $author_id ) {
	$bio = get_the_author_meta( 'description', $author_id );
	?>
	<section class="vf-author-box" aria-label="<?php esc_attr_e( 'درباره‌ی نویسنده', 'vidiform' ); ?>">
		<?php echo get_avatar( $author_id, 64, '', '', array( 'class' => 'vf-author-box__avatar' ) ); ?>
		<div class="vf-author-box__body">
			<div class="vf-author-box__label"><?php esc_html_e( 'نویسنده', 'vidiform' ); ?></div>
			<a class="vf-author-box__name" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></a>
			<?php if ( $bio ) : ?>
				<p class="vf-author-box__bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Pagination for the main query.
 */
function vf_blog_pagination() {
	the_posts_pagination( array(
		'class'     => 'vf-pagination',
		'mid_size'  => 1,
		'prev_text' => __( 'قبلی', 'vidiform' ),
		'next_text' => __( 'بعدی', 'vidiform' ),
	) );
}

/**
 * Empty state.
 *
 * @param string $title Title.
 * @param string $desc  Description.
 * @param bool   $home  Show "back to blog" button.
 */
function vf_blog_empty( $title, $desc = '', $home = true ) {
	?>
	<div class="vf-empty">
		<p class="vf-empty__title"><?php echo esc_html( $title ); ?></p>
		<?php if ( $desc ) : ?>
			<p class="vf-empty__desc"><?php echo esc_html( $desc ); ?></p>
		<?php endif; ?>
		<?php if ( $home ) : ?>
			<a class="vf-btn vf-btn--secondary" href="<?php echo esc_url( vf_blog_home_url() ); ?>"><?php esc_html_e( 'بازگشت به وبلاگ', 'vidiform' ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Localize numbers in the core pagination output.
 *
 * @param string $html HTML.
 * @return string
 */
function vf_localize_pagination( $html ) {
	return vf_is_persian() ? preg_replace_callback( '/>(\d+)</', function ( $m ) {
		return '>' . vf_fa_digits( $m[1] ) . '<';
	}, $html ) : $html;
}
add_filter( 'paginate_links_output', 'vf_localize_pagination' );

/**
 * Image block for landing cards (featured image or the reference's dashed placeholder).
 *
 * @param WP_Post $post  Post.
 * @param string  $size  Image size.
 * @param string  $class Wrapper class.
 */
function vf_card_media( $post, $size, $class ) {
	echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( get_permalink( $post ) ) . '" tabindex="-1" aria-hidden="true">';
	if ( has_post_thumbnail( $post ) ) {
		echo get_the_post_thumbnail( $post, $size, array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) );
	} else {
		echo '<span class="vf-ph">' . vf_icon( 'image', 26, array( 'stroke-width' => '1.5' ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</a>';
}

/**
 * Article tile (landing grid, archives, search, related).
 *
 * @param WP_Post|int $post  Post.
 * @param int         $level Heading level.
 */
function vf_post_tile( $post, $level = 3 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$cat = vf_post_primary_cat( $post->ID );
	$tag = 'h' . vf_clamp_int( $level, 2, 4 );
	?>
	<article class="vf-tile">
		<?php vf_card_media( $post, 'vf-card', 'vf-tile__media' ); ?>
		<div class="vf-tile__body">
			<div class="vf-tile__top">
				<?php if ( $cat ) : ?>
					<a class="vf-tag" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
				<?php endif; ?>
				<span class="vf-tile__read"><?php echo esc_html( sprintf( /* translators: %s minutes */ __( '%s مطالعه', 'vidiform' ), vf_minutes_label( vf_post_minutes( $post ) ) ) ); ?></span>
			</div>
			<<?php echo esc_html( $tag ); ?> class="vf-tile__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></<?php echo esc_html( $tag ); ?>>
			<p class="vf-tile__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 26 ) ); ?></p>
			<div class="vf-tile__foot">
				<a href="<?php echo esc_url( get_author_posts_url( (int) $post->post_author ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ); ?></a>
				<span aria-hidden="true">·</span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( vf_post_date( $post ) ); ?></time>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Wide featured article card (Blog landing).
 *
 * @param WP_Post $post Post.
 */
function vf_post_feature( $post ) {
	$cat = vf_post_primary_cat( $post->ID );
	?>
	<article class="vf-feature">
		<div class="vf-feature__body">
			<div class="vf-tile__top">
				<?php if ( $cat ) : ?>
					<a class="vf-tag" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
				<?php endif; ?>
				<span class="vf-tile__read"><?php esc_html_e( 'مقاله ویژه', 'vidiform' ); ?></span>
			</div>
			<h3 class="vf-feature__title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
			<p class="vf-feature__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 40 ) ); ?></p>
			<div class="vf-tile__foot vf-feature__foot">
				<a href="<?php echo esc_url( get_author_posts_url( (int) $post->post_author ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ); ?></a>
				<span aria-hidden="true">·</span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( vf_post_date( $post ) ); ?></time>
				<span aria-hidden="true">·</span>
				<span><?php echo esc_html( sprintf( /* translators: %s minutes */ __( '%s مطالعه', 'vidiform' ), vf_minutes_label( vf_post_minutes( $post ) ) ) ); ?></span>
			</div>
		</div>
		<?php vf_card_media( $post, 'vf-hero', 'vf-feature__media' ); ?>
	</article>
	<?php
}

/**
 * Hero media for one mockup screen (desktop laptop or mobile phone), read from «صفحه بلاگ».
 *
 * @param string $slot desktop|mobile.
 */
function vf_blog_hero_screen( $slot ) {
	$type = vf_opt( 'blog', 'media_type', 'image' );
	$att  = (int) vf_opt( 'blog', 'media_' . $slot, 0 );
	$url  = (string) vf_opt( 'blog', 'media_' . $slot . '_url', '' );
	$size = 'desktop' === $slot ? 'full' : 'large';
	$alt  = 'desktop' === $slot ? __( 'نمای دسکتاپ ویدی‌فرم', 'vidiform' ) : __( 'نمای موبایل ویدی‌فرم', 'vidiform' );

	if ( 'vidiform' === $type && $url ) {
		printf( '<iframe src="%1$s" title="%2$s" loading="lazy" allow="camera; microphone; autoplay; fullscreen" referrerpolicy="strict-origin-when-cross-origin"></iframe>', esc_url( $url ), esc_attr( $alt ) );
		return;
	}
	if ( 'video' === $type ) {
		$src = $url ? $url : ( $att ? (string) wp_get_attachment_url( $att ) : '' );
		if ( $src ) {
			printf( '<video src="%1$s" autoplay muted loop playsinline preload="metadata" aria-label="%2$s"></video>', esc_url( $src ), esc_attr( $alt ) );
			return;
		}
	}
	if ( $att && wp_attachment_is_image( $att ) ) {
		echo wp_get_attachment_image( $att, $size, false, array(
			'alt'           => trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) ) ? trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) ) : $alt,
			'loading'       => 'eager',
			'fetchpriority' => 'desktop' === $slot ? 'high' : 'auto',
			'sizes'         => 'desktop' === $slot ? '(max-width: 900px) 92vw, 620px' : '160px',
		) );
		return;
	}
	echo '<span class="vf-ph">' . vf_icon( 'image', 'desktop' === $slot ? 30 : 22, array( 'stroke-width' => '1.5' ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Safe URL for a configurable button: in-page anchors stay as is.
 *
 * @param string $url URL.
 * @return string
 */
function vf_button_url( $url ) {
	$url = (string) $url;
	if ( '' !== $url && '#' === $url[0] ) {
		return '#' . sanitize_title( substr( $url, 1 ) );
	}
	return esc_url( $url );
}
