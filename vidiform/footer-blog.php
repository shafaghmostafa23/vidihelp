<?php
/**
 * Blog footer (reference: VidiForm Content Hub) — brand, blog categories, product links, newsletter.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

$vf_foot_cats = get_categories( array( 'parent' => 0, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 6 ) );
$vf_news      = (string) vf_opt( 'blog', 'newsletter_url' );
list( $vf_jy ) = vf_gregorian_to_jalali( (int) wp_date( 'Y' ), (int) wp_date( 'n' ), (int) wp_date( 'j' ) );
$vf_year       = vf_opt( 'general', 'jalali', 1 ) && vf_is_persian() ? vf_fa_digits( $vf_jy ) : vf_num( wp_date( 'Y' ) );
?>
<footer class="vf-footer vf-footer--blog">
	<div class="vf-footer__grid">
		<div class="vf-footer__col vf-footer__col--brand">
			<a class="vf-brand" href="<?php echo esc_url( vf_blog_home_url() ); ?>">
				<span class="vf-brand__mark vf-brand__mark--v" aria-hidden="true">V</span>
				<span class="vf-brand__name"><?php esc_html_e( 'ویدی‌فرم', 'vidiform' ); ?></span>
			</a>
			<?php if ( vf_opt( 'blog', 'footer_text' ) ) : ?>
				<p class="vf-footer__text"><?php echo esc_html( vf_opt( 'blog', 'footer_text' ) ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $vf_foot_cats ) : ?>
			<nav class="vf-footer__col" aria-labelledby="vf-foot-cats">
				<h2 class="vf-footer__title" id="vf-foot-cats"><?php esc_html_e( 'دسته‌بندی‌های بلاگ', 'vidiform' ); ?></h2>
				<ul class="vf-footer__links">
					<?php foreach ( $vf_foot_cats as $vf_c ) : ?>
						<li><a href="<?php echo esc_url( get_category_link( $vf_c ) ); ?>"><?php echo esc_html( $vf_c->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<nav class="vf-footer__col" aria-labelledby="vf-foot-product">
			<h2 class="vf-footer__title" id="vf-foot-product"><?php esc_html_e( 'محصول', 'vidiform' ); ?></h2>
			<?php
			if ( has_nav_menu( 'blog_footer' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'blog_footer',
					'container'      => false,
					'menu_class'     => 'vf-footer__links',
					'depth'          => 1,
					'fallback_cb'    => false,
				) );
			} else {
				?>
				<ul class="vf-footer__links">
					<?php if ( vf_opt( 'general', 'main_url' ) ) : ?>
						<li><a href="<?php echo esc_url( vf_opt( 'general', 'main_url' ) ); ?>"><?php esc_html_e( 'ویدی‌فرم', 'vidiform' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( vf_help_url() ); ?>"><?php esc_html_e( 'مرکز راهنما', 'vidiform' ); ?></a></li>
					<li><a href="<?php echo esc_url( vf_blog_home_url() ); ?>"><?php esc_html_e( 'بلاگ', 'vidiform' ); ?></a></li>
				</ul>
				<?php
			}
			?>
		</nav>

		<?php if ( $vf_news ) : ?>
			<div class="vf-footer__col vf-footer__col--news">
				<h2 class="vf-footer__title" id="vf-foot-news"><?php esc_html_e( 'خبرنامه محتوا', 'vidiform' ); ?></h2>
				<p class="vf-footer__text"><?php esc_html_e( 'هر دو هفته، یک مقاله کاربردی درباره جذب مشتری.', 'vidiform' ); ?></p>
				<form class="vf-news" method="post" action="<?php echo esc_url( $vf_news ); ?>" aria-labelledby="vf-foot-news">
					<label class="screen-reader-text" for="vf-news-email"><?php esc_html_e( 'ایمیل شما', 'vidiform' ); ?></label>
					<input id="vf-news-email" type="email" name="email" required placeholder="<?php esc_attr_e( 'ایمیل شما', 'vidiform' ); ?>" autocomplete="email">
					<button type="submit" class="vf-btn vf-btn--accent"><?php esc_html_e( 'عضویت', 'vidiform' ); ?></button>
				</form>
			</div>
		<?php endif; ?>
	</div>
	<div class="vf-footer__bottom">
		<?php
		/* translators: %s: year */
		echo esc_html( sprintf( __( '© %s ویدی‌فرم — تمام حقوق محفوظ است.', 'vidiform' ), $vf_year ) );
		?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
