<?php
/**
 * Help Center template tags.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of a help category by id (from the index, falls back to get_term_link).
 *
 * @param int $term_id Term id.
 * @return string
 */
function vf_help_cat_url( $term_id ) {
	$link = get_term_link( (int) $term_id, 'help_category' );
	return is_wp_error( $link ) ? vf_help_url() : $link;
}

/**
 * Guide URL from index row.
 *
 * @param array $g Guide row.
 * @return string
 */
function vf_help_guide_url( $g ) {
	return get_permalink( $g['id'] );
}

/**
 * Category image (circle) markup.
 *
 * @param array $cat  Category row from index.
 * @param int   $size Circle size (css class variant).
 * @return string
 */
function vf_help_cat_badge( $cat, $size = 88 ) {
	$class = 88 === $size ? 'vf-cat-badge' : 'vf-cat-badge vf-cat-badge--sm';
	if ( ! empty( $cat['image'] ) ) {
		$img = wp_get_attachment_image( $cat['image'], 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
		if ( $img ) {
			return '<div class="' . esc_attr( $class ) . ' has-image">' . $img . '</div>';
		}
	}
	return '<div class="' . esc_attr( $class ) . '">' . vf_icon( 'play-card', 88 === $size ? 36 : 24, array( 'stroke-width' => '1.7' ) ) . '</div>';
}

/**
 * Sidebar navigation (category accordion) used on category & article views.
 *
 * @param array $args {title, active_cat, active_guide}.
 */
function vf_help_sidebar( $args ) {
	$idx          = vf_help_index();
	$active_cat   = (int) ( $args['active_cat'] ?? 0 );
	$active_guide = (int) ( $args['active_guide'] ?? 0 );
	$title        = $args['title'] ?? __( 'دسته‌بندی راهنما', 'vidiform' );
	?>
	<aside class="vf-hnav" aria-label="<?php echo esc_attr( $title ); ?>">
		<button type="button" class="vf-hnav__mobile-toggle" aria-expanded="false" aria-controls="vf-hnav-list">
			<span><?php echo esc_html( $title ); ?></span>
			<?php vf_the_icon( 'chev-down', 14, array( 'stroke-width' => '2.4' ) ); ?>
		</button>
		<div class="vf-hnav__title"><?php echo esc_html( $title ); ?></div>
		<ul class="vf-hnav__list" id="vf-hnav-list">
			<?php
			foreach ( $idx['order'] as $cid ) :
				$c      = $idx['cats'][ $cid ];
				$active = $cid === $active_cat;
				$open   = $active;
				$list   = 'vf-hnav-g-' . $cid;
				?>
				<li class="vf-hnav__item<?php echo $open ? ' is-open' : ''; ?>">
					<div class="vf-hnav__row">
						<a class="vf-hnav__cat<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( vf_help_cat_url( $cid ) ); ?>"<?php echo $active && ! $active_guide ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c['name'] ); ?></a>
						<?php if ( $c['guides'] ) : ?>
							<button type="button" class="vf-hnav__caret" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $list ); ?>" aria-label="<?php esc_attr_e( 'باز و بسته کردن دسته', 'vidiform' ); ?>">
								<?php vf_the_icon( 'chev-down', 13, array( 'stroke-width' => '2.4' ) ); ?>
							</button>
						<?php endif; ?>
					</div>
					<?php if ( $c['guides'] ) : ?>
						<ul class="vf-hnav__guides" id="<?php echo esc_attr( $list ); ?>"<?php echo $open ? '' : ' hidden'; ?>>
							<?php
							foreach ( $c['guides'] as $gid ) :
								$g   = $idx['guides'][ $gid ];
								$cur = $gid === $active_guide;
								?>
								<li><a class="vf-hnav__guide<?php echo $cur ? ' is-active' : ''; ?>" href="<?php echo esc_url( vf_help_guide_url( $g ) ); ?>"<?php echo $cur ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $g['title'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</aside>
	<?php
}

/**
 * Breadcrumb trail items for Help Center views.
 *
 * @return array[] [ [label, url|null] ]
 */
function vf_help_breadcrumb_items() {
	$items = array( array( __( 'مرکز راهنما', 'vidiform' ), vf_help_url() ) );
	if ( is_tax( 'help_category' ) ) {
		$term = get_queried_object();
		if ( $term->parent ) {
			$p = get_term( $term->parent, 'help_category' );
			if ( $p && ! is_wp_error( $p ) ) {
				$items[] = array( $p->name, get_term_link( $p ) );
			}
		}
		$items[] = array( $term->name, null );
	} elseif ( is_singular( 'help_article' ) ) {
		$term = vf_guide_term( get_queried_object_id() );
		if ( $term ) {
			$root = get_term( vf_help_root_term( $term->term_id ), 'help_category' );
			if ( $root && ! is_wp_error( $root ) ) {
				$items[] = array( $root->name, get_term_link( $root ) );
			}
		}
		$items[] = array( get_the_title(), null );
	} elseif ( get_query_var( 'vf_help_search' ) ) {
		$items[] = array( __( 'نتایج جست‌وجو', 'vidiform' ), null );
	}
	return $items;
}

/**
 * Print breadcrumbs (visible trail; JSON-LD handled in seo.php).
 *
 * @param array[] $items Items.
 * @param string  $class Extra class.
 */
function vf_breadcrumbs( $items, $class = '' ) {
	echo '<nav aria-label="' . esc_attr__( 'مسیر صفحه', 'vidiform' ) . '"><ol class="vf-crumbs ' . esc_attr( $class ) . '">';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $it ) {
		echo '<li>';
		if ( $it[1] && $i !== $last ) {
			echo '<a href="' . esc_url( $it[1] ) . '">' . esc_html( $it[0] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $it[0] ) . '</span>';
		}
		echo '</li>';
		if ( $i !== $last ) {
			echo '<li class="vf-crumbs__sep" aria-hidden="true">/</li>';
		}
	}
	echo '</ol></nav>';
}

/**
 * Hero search form (home + search page).
 *
 * @param string $value Current query.
 * @param bool   $live  Enable live results.
 */
function vf_help_search_form( $value = '', $live = true ) {
	?>
	<form class="vf-search" role="search" method="get" action="<?php echo esc_url( vf_help_search_url() ); ?>"<?php echo $live ? ' data-vf-live-search' : ''; ?>>
		<label class="screen-reader-text" for="vf-help-q"><?php esc_html_e( 'جست‌وجو در مرکز راهنما', 'vidiform' ); ?></label>
		<span class="vf-search__icon"><?php vf_the_icon( 'search', 18, array( 'stroke-width' => '2.2' ) ); ?></span>
		<input id="vf-help-q" class="vf-search__input" type="search" name="q" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'دنبال چه چیزی می‌گردید؟', 'vidiform' ); ?>" autocomplete="off" enterkeyhint="search">
		<span class="vf-search__spin" aria-hidden="true"></span>
	</form>
	<?php
}

/**
 * Shared popups used by guide sections (hint/feature, related guide, template preview).
 */
function vf_help_popups() {
	?>
	<div class="vf-modal" id="vf-pop-feature" role="dialog" aria-modal="true" aria-labelledby="vf-pop-feature-title" hidden>
		<div class="vf-modal__box vf-modal__box--hint">
			<div class="vf-modal__head">
				<div class="vf-hint-badge" aria-hidden="true"><?php vf_the_icon( 'info', 17, array( 'stroke-width' => '2.2' ) ); ?></div>
				<h2 class="vf-modal__title" id="vf-pop-feature-title"></h2>
				<button type="button" class="vf-modal__close" data-vf-close aria-label="<?php esc_attr_e( 'بستن', 'vidiform' ); ?>">×</button>
			</div>
			<p class="vf-modal__body" data-body></p>
		</div>
	</div>

	<div class="vf-modal" id="vf-pop-guide" role="dialog" aria-modal="true" aria-labelledby="vf-pop-guide-title" hidden>
		<div class="vf-modal__box">
			<div class="vf-modal__head">
				<h2 class="vf-modal__title" id="vf-pop-guide-title"></h2>
				<button type="button" class="vf-modal__close" data-vf-close aria-label="<?php esc_attr_e( 'بستن', 'vidiform' ); ?>">×</button>
			</div>
			<p class="vf-modal__body" data-body style="margin-bottom:18px"></p>
			<a class="vf-btn vf-btn--primary vf-btn--block" data-link href="#"><?php esc_html_e( 'خواندن راهنمای کامل', 'vidiform' ); ?></a>
		</div>
	</div>

	<div class="vf-modal vf-modal--sheet" id="vf-pop-tpl" role="dialog" aria-modal="true" aria-labelledby="vf-pop-tpl-title" hidden>
		<div class="vf-sheet">
			<div class="vf-sheet__head">
				<div class="vf-sheet__headtext">
					<div class="vf-sheet__eyebrow">
						<span><?php esc_html_e( 'پیش‌نمایش قالب', 'vidiform' ); ?></span>
						<span class="vf-pill" data-badge></span>
					</div>
					<h2 class="vf-sheet__title" id="vf-pop-tpl-title"></h2>
				</div>
				<button type="button" class="vf-modal__close" data-vf-close aria-label="<?php esc_attr_e( 'بستن', 'vidiform' ); ?>">×</button>
			</div>
			<div class="vf-sheet__body">
				<div data-about-wrap>
					<div class="vf-sheet__label"><?php esc_html_e( 'درباره این قالب', 'vidiform' ); ?></div>
					<p class="vf-sheet__text" data-about></p>
				</div>
				<div>
					<div class="vf-sheet__label"><?php esc_html_e( 'پیش‌نمایش کنوس', 'vidiform' ); ?></div>
					<div class="vf-canvas-frame">
						<div class="vf-canvas-frame__bar"><span class="vf-canvas-frame__dot"></span><span class="vf-canvas-frame__url" dir="ltr" data-url></span></div>
						<div class="vf-canvas-frame__view"><iframe title="<?php esc_attr_e( 'پیش‌نمایش قالب', 'vidiform' ); ?>" data-src loading="lazy"></iframe></div>
					</div>
				</div>
			</div>
			<div class="vf-sheet__foot">
				<a class="vf-btn vf-btn--link" data-use target="_blank" rel="noopener" href="#"><?php esc_html_e( 'استفاده از قالب', 'vidiform' ); ?></a>
				<div class="vf-spacer"></div>
				<button type="button" class="vf-btn vf-btn--primary vf-btn--lg" data-vf-close><?php esc_html_e( 'ادامه راهنما', 'vidiform' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
