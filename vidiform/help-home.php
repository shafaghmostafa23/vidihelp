<?php
/**
 * Help Center home (/help/).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'help' );

$vf_idx   = vf_help_index();
$vf_items = (int) vf_opt( 'help', 'home_items', 3 );
?>
<main id="vf-main" class="vf-help-home">
	<section class="vf-hero">
		<div class="vf-hero__inner">
			<h1 class="vf-hero__title"><?php echo esc_html( vf_opt( 'help', 'landing_title' ) ); ?></h1>
			<p class="vf-hero__desc"><?php echo esc_html( vf_opt( 'help', 'landing_desc' ) ); ?></p>
			<?php vf_help_search_form( '', true ); ?>
		</div>
	</section>

	<div class="vf-container vf-help-home__body">
		<div class="vf-live" data-vf-live-results aria-live="polite" hidden></div>

		<div data-vf-grid>
			<?php if ( empty( $vf_idx['order'] ) ) : ?>
				<div class="vf-empty">
					<p class="vf-empty__title"><?php esc_html_e( 'هنوز دسته‌بندی‌ای منتشر نشده است', 'vidiform' ); ?></p>
					<p class="vf-empty__desc"><?php esc_html_e( 'به‌زودی آموزش‌ها و پاسخ سؤال‌ها در این‌جا قرار می‌گیرند.', 'vidiform' ); ?></p>
				</div>
			<?php else : ?>
				<h2 class="screen-reader-text"><?php esc_html_e( 'دسته‌بندی‌های راهنما', 'vidiform' ); ?></h2>
				<div class="vf-cat-grid">
					<?php
					foreach ( $vf_idx['order'] as $vf_cid ) :
						$vf_cat = $vf_idx['cats'][ $vf_cid ];
						$vf_url = vf_help_cat_url( $vf_cid );
						?>
						<article class="vf-cat-card">
							<?php echo vf_help_cat_badge( $vf_cat ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<div class="vf-cat-card__head">
								<h3 class="vf-cat-card__name"><a href="<?php echo esc_url( $vf_url ); ?>"><?php echo esc_html( $vf_cat['name'] ); ?></a></h3>
								<?php if ( $vf_cat['desc'] ) : ?>
									<p class="vf-cat-card__desc"><?php echo esc_html( $vf_cat['desc'] ); ?></p>
								<?php endif; ?>
							</div>
							<?php if ( $vf_cat['guides'] ) : ?>
								<ul class="vf-cat-card__items">
									<?php foreach ( array_slice( $vf_cat['guides'], 0, $vf_items ) as $vf_gid ) : ?>
										<li><a href="<?php echo esc_url( get_permalink( $vf_gid ) ); ?>"><span class="vf-bullet" aria-hidden="true"></span><span><?php echo esc_html( $vf_idx['guides'][ $vf_gid ]['title'] ); ?></span></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<a class="vf-cat-card__all" href="<?php echo esc_url( $vf_url ); ?>">
								<?php esc_html_e( 'مشاهده همه ←', 'vidiform' ); ?>
								<span class="screen-reader-text"><?php echo esc_html( $vf_cat['name'] ); ?></span>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer( 'help' );
