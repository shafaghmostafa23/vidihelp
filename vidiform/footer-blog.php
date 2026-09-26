<?php
/**
 * Blog footer.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="vf-footer vf-footer--blog">
	<div class="vf-footer__inner vf-footer__inner--blog">
		<div class="vf-footer__brand">
			<a class="vf-brand" href="<?php echo esc_url( vf_blog_home_url() ); ?>">
				<span class="vf-brand__mark"><?php vf_the_icon( 'video', 18 ); ?></span>
				<span class="vf-brand__name"><?php esc_html_e( 'وبلاگ ویدی‌فرم', 'vidiform' ); ?></span>
			</a>
			<p class="vf-footer__text"><?php echo esc_html( vf_opt( 'blog', 'footer_text' ) ); ?></p>
		</div>
		<?php
		if ( has_nav_menu( 'blog_footer' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'blog_footer',
				'container'      => 'nav',
				'container_aria_label' => __( 'منوی فوتر', 'vidiform' ),
				'menu_class'     => 'vf-footer__menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
		}
		?>
		<div class="vf-footer__copy">
			<?php
			/* translators: %s: year */
			echo esc_html( sprintf( __( '© %s ویدی‌فرم. همه‌ی حقوق محفوظ است.', 'vidiform' ), vf_num( gmdate( 'Y' ) ) ) );
			?>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
