<?php
/**
 * Help Center footer.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="vf-footer vf-footer--help">
	<div class="vf-footer__inner">
		<span>
			<?php
			/* translators: %s: year */
			echo esc_html( sprintf( __( '© %s ویدی‌فرم — مرکز راهنما', 'vidiform' ), vf_num( gmdate( 'Y' ) ) ) );
			?>
		</span>
		<div class="vf-spacer"></div>
		<?php
		if ( has_nav_menu( 'help_footer' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'help_footer',
				'container'      => 'nav',
				'container_aria_label' => __( 'منوی فوتر', 'vidiform' ),
				'menu_class'     => 'vf-footer__menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
		}
		?>
	</div>
</footer>
<?php vf_help_popups(); ?>
<?php wp_footer(); ?>
</body>
</html>
