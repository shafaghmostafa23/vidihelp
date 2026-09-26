<?php
/**
 * Blog header (reference: VidiForm Content Hub).
 *
 * Nav links come from the «وبلاگ — منوی اصلی» menu location; without a menu the header shows
 * «محصول» (main site) and «بلاگ». Buttons are configured in Blog → تنظیمات.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="vf-skip" href="#vf-main"><?php esc_html_e( 'رفتن به محتوای اصلی', 'vidiform' ); ?></a>
<header class="vf-header vf-blog-header">
	<div class="vf-header__inner">
		<a class="vf-brand" href="<?php echo esc_url( vf_opt( 'general', 'main_url' ) ? vf_opt( 'general', 'main_url' ) : home_url( '/' ) ); ?>">
			<span class="vf-brand__mark vf-brand__mark--v" aria-hidden="true">V</span>
			<span class="vf-brand__name"><?php esc_html_e( 'ویدی‌فرم', 'vidiform' ); ?></span>
		</a>

		<nav class="vf-blog-nav" id="vf-blog-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'vidiform' ); ?>">
			<?php
			if ( has_nav_menu( 'blog_primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'blog_primary',
					'container'      => false,
					'menu_class'     => 'vf-blog-nav__list',
					'depth'          => 1,
					'fallback_cb'    => false,
				) );
			} else {
				$vf_blog_on = is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date() || is_search();
				?>
				<ul class="vf-blog-nav__list">
					<?php if ( vf_opt( 'general', 'main_url' ) ) : ?>
						<li><a href="<?php echo esc_url( vf_opt( 'general', 'main_url' ) ); ?>"><?php esc_html_e( 'محصول', 'vidiform' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( vf_help_url() ); ?>"><?php esc_html_e( 'مرکز راهنما', 'vidiform' ); ?></a></li>
					<li><a class="<?php echo $vf_blog_on ? 'is-active' : ''; ?>" href="<?php echo esc_url( vf_blog_home_url() ); ?>"<?php echo is_home() ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'بلاگ', 'vidiform' ); ?></a></li>
				</ul>
				<?php
			}
			?>
		</nav>

		<div class="vf-spacer"></div>

		<details class="vf-blog-search-pop">
			<summary class="vf-btn vf-btn--icon vf-btn--icon-sm" aria-label="<?php esc_attr_e( 'جست‌وجو', 'vidiform' ); ?>"><?php vf_the_icon( 'search', 16, array( 'stroke-width' => '2.2' ) ); ?></summary>
			<div class="vf-blog-search-pop__panel"><?php vf_blog_search_form( 'vf-blog-q-top' ); ?></div>
		</details>
		<button type="button" class="vf-btn vf-btn--icon vf-btn--icon-sm vf-theme-toggle" aria-pressed="false" aria-label="<?php esc_attr_e( 'تغییر حالت روشن و تیره', 'vidiform' ); ?>">
			<?php vf_the_icon( 'moon', 16, array( 'class' => 'vf-i-moon' ) ); ?>
			<?php vf_the_icon( 'sun', 16, array( 'class' => 'vf-i-sun' ) ); ?>
		</button>
		<a class="vf-btn vf-btn--ghost vf-blog-panel-btn" href="<?php echo esc_url( vf_opt( 'general', 'panel_url' ) ); ?>"><?php esc_html_e( 'پنل مدیریت', 'vidiform' ); ?></a>
		<?php if ( vf_opt( 'blog', 'header_cta_text' ) ) : ?>
			<a class="vf-btn vf-btn--accent vf-blog-cta" href="<?php echo vf_button_url( vf_opt( 'blog', 'header_cta_url' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by vf_button_url(). ?>"><?php echo esc_html( vf_opt( 'blog', 'header_cta_text' ) ); ?></a>
		<?php endif; ?>
		<button type="button" class="vf-btn vf-btn--icon vf-btn--icon-sm vf-blog-menu-btn" aria-expanded="false" aria-controls="vf-blog-nav" aria-label="<?php esc_attr_e( 'منو', 'vidiform' ); ?>"><?php vf_the_icon( 'menu', 17 ); ?></button>
	</div>
</header>
