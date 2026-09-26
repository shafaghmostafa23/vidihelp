<?php
/**
 * Blog landing (/blog/) — reference: VidiForm Content Hub.
 * Every text, link, media item and list option is read from Blog → «صفحه بلاگ» (vf_blog option).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

global $wp_query;
$vf_paged    = max( 1, (int) get_query_var( 'paged' ) );
$vf_featured = 1 === $vf_paged ? vf_blog_landing_featured() : null;
$vf_total    = (int) $wp_query->found_posts + ( vf_blog_landing_featured() ? 1 : 0 );
$vf_o        = function ( $k ) {
	return vf_opt( 'blog', $k );
};
?>
<main id="vf-main" class="vf-landing">
	<?php if ( 1 === $vf_paged ) : ?>
		<section class="vf-lhero" aria-labelledby="vf-lhero-title">
			<div class="vf-lhero__text">
				<?php if ( $vf_o( 'eyebrow' ) ) : ?>
					<span class="vf-eyebrow"><?php echo esc_html( $vf_o( 'eyebrow' ) ); ?></span>
				<?php endif; ?>
				<h1 class="vf-lhero__title" id="vf-lhero-title"><?php echo esc_html( $vf_o( 'hero_title' ) ); ?></h1>
				<?php if ( $vf_o( 'hero_desc' ) ) : ?>
					<p class="vf-lhero__desc"><?php echo esc_html( $vf_o( 'hero_desc' ) ); ?></p>
				<?php endif; ?>
				<div class="vf-lhero__actions">
					<?php if ( $vf_o( 'btn1_text' ) ) : ?>
						<a class="vf-btn vf-btn--accent vf-btn--xl" href="<?php echo vf_button_url( $vf_o( 'btn1_url' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $vf_o( 'btn1_text' ) ); ?></a>
					<?php endif; ?>
					<?php if ( $vf_o( 'btn2_text' ) ) : ?>
						<a class="vf-btn vf-btn--ghost vf-btn--xl" href="<?php echo vf_button_url( $vf_o( 'btn2_url' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $vf_o( 'btn2_text' ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="vf-lhero__media">
				<div class="vf-laptop">
					<div class="vf-laptop__screen">
						<span class="vf-laptop__dots" aria-hidden="true"><i></i><i></i><i></i></span>
						<div class="vf-laptop__view"><?php vf_blog_hero_screen( 'desktop' ); ?></div>
					</div>
					<div class="vf-laptop__base" aria-hidden="true"></div>
				</div>
				<div class="vf-phone">
					<div class="vf-phone__view"><?php vf_blog_hero_screen( 'mobile' ); ?></div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="vf-landing__rule" role="presentation"></div>

	<?php vf_blog_category_nav(); ?>

	<section class="vf-latest" id="latest" aria-labelledby="vf-latest-title">
		<div class="vf-latest__head">
			<h2 class="vf-latest__title" id="vf-latest-title"><?php echo esc_html( $vf_o( 'latest_title' ) ); ?></h2>
			<span class="vf-latest__count">
				<?php
				/* translators: %s number of articles */
				echo esc_html( sprintf( __( '%s مقاله', 'vidiform' ), vf_num( $vf_total ) ) );
				?>
			</span>
		</div>

		<?php if ( $vf_featured ) : ?>
			<?php vf_post_feature( $vf_featured ); ?>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="vf-tiles">
				<?php
				while ( have_posts() ) :
					the_post();
					vf_post_tile( get_post(), 3 );
				endwhile;
				?>
			</div>
			<?php vf_blog_pagination(); ?>
		<?php elseif ( ! $vf_featured ) : ?>
			<?php vf_blog_empty( __( 'هنوز مقاله‌ای منتشر نشده است', 'vidiform' ), __( 'به‌زودی مقاله‌های تازه این‌جا منتشر می‌شوند.', 'vidiform' ), false ); ?>
		<?php endif; ?>
	</section>

	<?php if ( $vf_o( 'cta_title' ) ) : ?>
		<section class="vf-cta" aria-labelledby="vf-cta-title">
			<div class="vf-cta__text">
				<h2 class="vf-cta__title" id="vf-cta-title"><?php echo esc_html( $vf_o( 'cta_title' ) ); ?></h2>
				<?php if ( $vf_o( 'cta_desc' ) ) : ?>
					<p class="vf-cta__desc"><?php echo esc_html( $vf_o( 'cta_desc' ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="vf-cta__actions">
				<?php if ( $vf_o( 'cta_btn1_text' ) ) : ?>
					<a class="vf-btn vf-btn--accent vf-btn--xl" href="<?php echo vf_button_url( $vf_o( 'cta_btn1_url' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $vf_o( 'cta_btn1_text' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $vf_o( 'cta_btn2_text' ) ) : ?>
					<a class="vf-btn vf-btn--ghost vf-btn--xl" href="<?php echo vf_button_url( $vf_o( 'cta_btn2_url' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $vf_o( 'cta_btn2_text' ) ); ?></a>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer( 'blog' );
