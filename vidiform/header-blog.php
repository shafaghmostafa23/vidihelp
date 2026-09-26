<?php
/**
 * Blog header.
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
		<a class="vf-brand" href="<?php echo esc_url( vf_blog_home_url() ); ?>">
			<span class="vf-brand__mark"><?php vf_the_icon( 'video', 18 ); ?></span>
			<span class="vf-brand__name"><?php esc_html_e( 'وبلاگ ویدی‌فرم', 'vidiform' ); ?></span>
		</a>

		<nav class="vf-blog-nav" id="vf-blog-nav" aria-label="<?php esc_attr_e( 'منوی وبلاگ', 'vidiform' ); ?>">
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
				$vf_cats = get_categories( array( 'parent' => 0, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 5 ) );
				echo '<ul class="vf-blog-nav__list">';
				foreach ( $vf_cats as $vf_c ) {
					$vf_on = is_category( $vf_c->term_id );
					echo '<li><a href="' . esc_url( get_category_link( $vf_c ) ) . '"' . ( $vf_on ? ' aria-current="page" class="is-active"' : '' ) . '>' . esc_html( $vf_c->name ) . '</a></li>';
				}
				echo '</ul>';
			}
			?>
		</nav>

		<div class="vf-spacer"></div>

		<details class="vf-blog-search-pop">
			<summary class="vf-btn vf-btn--icon" aria-label="<?php esc_attr_e( 'جست‌وجو', 'vidiform' ); ?>"><?php vf_the_icon( 'search', 17, array( 'stroke-width' => '2.2' ) ); ?></summary>
			<div class="vf-blog-search-pop__panel"><?php vf_blog_search_form( 'vf-blog-q-top' ); ?></div>
		</details>
		<button type="button" class="vf-btn vf-btn--icon vf-theme-toggle" aria-pressed="false" aria-label="<?php esc_attr_e( 'تغییر حالت روشن و تیره', 'vidiform' ); ?>">
			<?php vf_the_icon( 'moon', 17, array( 'class' => 'vf-i-moon' ) ); ?>
			<?php vf_the_icon( 'sun', 17, array( 'class' => 'vf-i-sun' ) ); ?>
		</button>
		<a class="vf-btn vf-btn--primary vf-blog-cta" href="<?php echo esc_url( vf_opt( 'general', 'panel_url' ) ); ?>"><?php esc_html_e( 'ورود به ویدی‌فرم', 'vidiform' ); ?></a>
		<button type="button" class="vf-btn vf-btn--icon vf-blog-menu-btn" aria-expanded="false" aria-controls="vf-blog-nav" aria-label="<?php esc_attr_e( 'منو', 'vidiform' ); ?>"><?php vf_the_icon( 'menu', 18 ); ?></button>
	</div>
</header>
