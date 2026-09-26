<?php
/**
 * Help Center guide / article (/help/{cat}/{guide}/).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'help' );

while ( have_posts() ) :
	the_post();

	$vf_id       = get_the_ID();
	$vf_idx      = vf_help_index();
	$vf_term     = vf_guide_term( $vf_id );
	$vf_root_id  = $vf_term ? vf_help_root_term( $vf_term->term_id ) : 0;
	$vf_order    = ( $vf_root_id && isset( $vf_idx['cats'][ $vf_root_id ] ) ) ? $vf_idx['cats'][ $vf_root_id ]['guides'] : array();
	$vf_pos      = array_search( $vf_id, $vf_order, true );
	$vf_prev     = ( false !== $vf_pos && $vf_pos > 0 ) ? $vf_order[ $vf_pos - 1 ] : 0;
	$vf_next     = ( false !== $vf_pos && $vf_pos < count( $vf_order ) - 1 ) ? $vf_order[ $vf_pos + 1 ] : 0;
	$vf_related  = array_slice( array_values( array_diff( $vf_order, array( $vf_id ) ) ), 0, (int) vf_opt( 'help', 'related_count', 3 ) );
	$vf_sections = vf_get_sections( $vf_id );
	$vf_features = array();
	?>
	<div class="vf-container vf-help-layout">
		<?php vf_help_sidebar( array( 'title' => __( 'آموزش و راهنما', 'vidiform' ), 'active_cat' => $vf_root_id, 'active_guide' => $vf_id ) ); ?>

		<main id="vf-main" class="vf-help-main">
			<article class="vf-article" aria-labelledby="vf-article-title">
				<?php vf_breadcrumbs( vf_help_breadcrumb_items() ); ?>
				<h1 class="vf-article__title" id="vf-article-title"><?php the_title(); ?></h1>
				<div class="vf-article__meta">
					<?php
					/* translators: %s reading time */
					echo esc_html( sprintf( __( 'زمان مطالعه %s', 'vidiform' ), vf_minutes_label( vf_guide_minutes( get_post() ) ) ) );
					?>
					<span aria-hidden="true">·</span>
					<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
						<?php
						/* translators: %s date */
						echo esc_html( sprintf( __( 'به‌روزرسانی %s', 'vidiform' ), vf_post_date( null, true ) ) );
						?>
					</time>
				</div>

				<div class="vf-article__body">
					<?php
					if ( $vf_sections ) {
						vf_render_sections( $vf_sections, $vf_features );
					} else {
						echo '<div class="vf-prose">';
						the_content();
						echo '</div>';
					}
					?>
				</div>

				<?php if ( $vf_prev || $vf_next ) : ?>
					<nav class="vf-pn" aria-label="<?php esc_attr_e( 'مطلب قبلی و بعدی', 'vidiform' ); ?>">
						<?php if ( $vf_prev ) : ?>
							<a class="vf-pn__item vf-pn__item--prev" href="<?php echo esc_url( get_permalink( $vf_prev ) ); ?>" rel="prev">
								<span class="vf-pn__label"><?php esc_html_e( 'مطلب قبلی', 'vidiform' ); ?></span>
								<span class="vf-pn__title"><?php echo esc_html( $vf_idx['guides'][ $vf_prev ]['title'] ); ?></span>
							</a>
						<?php endif; ?>
						<?php if ( $vf_next ) : ?>
							<a class="vf-pn__item vf-pn__item--next" href="<?php echo esc_url( get_permalink( $vf_next ) ); ?>" rel="next">
								<span class="vf-pn__label"><?php esc_html_e( 'مطلب بعدی', 'vidiform' ); ?></span>
								<span class="vf-pn__title"><?php echo esc_html( $vf_idx['guides'][ $vf_next ]['title'] ); ?></span>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>

				<?php if ( $vf_related ) : ?>
					<section class="vf-related" aria-labelledby="vf-related-title">
						<h2 class="vf-related__title" id="vf-related-title"><?php esc_html_e( 'مطالب مرتبط', 'vidiform' ); ?></h2>
						<div class="vf-related__grid">
							<?php foreach ( $vf_related as $vf_rid ) : ?>
								<a class="vf-related__item" href="<?php echo esc_url( get_permalink( $vf_rid ) ); ?>">
									<span class="vf-related__name"><?php echo esc_html( $vf_idx['guides'][ $vf_rid ]['title'] ); ?></span>
									<span class="vf-related__meta">
										<?php
										/* translators: %s reading time */
										echo esc_html( sprintf( __( 'زمان مطالعه %s', 'vidiform' ), vf_minutes_label( $vf_idx['guides'][ $vf_rid ]['read'] ) ) );
										?>
									</span>
								</a>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</article>
		</main>
	</div>
	<?php
	// Data for popups (inline guide references + feature descriptions), escaped as JSON.
	$vf_refs = array();
	foreach ( vf_sections_guide_refs( $vf_sections ) as $vf_ref_id ) {
		if ( 'publish' === get_post_status( $vf_ref_id ) && 'help_article' === get_post_type( $vf_ref_id ) ) {
			$vf_refs[ $vf_ref_id ] = array(
				'title' => get_the_title( $vf_ref_id ),
				'body'  => isset( $vf_idx['guides'][ $vf_ref_id ] ) ? $vf_idx['guides'][ $vf_ref_id ]['excerpt'] : get_the_excerpt( $vf_ref_id ),
				'url'   => get_permalink( $vf_ref_id ),
			);
		}
	}
	?>
	<script type="application/json" id="vf-help-data"><?php echo wp_json_encode( array( 'guides' => (object) $vf_refs, 'features' => (object) $vf_features, 'notFound' => __( 'راهنمای مرتبط پیدا نشد.', 'vidiform' ), 'free' => __( 'رایگان', 'vidiform' ), 'premium' => __( 'نیازمند اشتراک', 'vidiform' ) ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ); ?></script>
	<?php
endwhile;

get_footer( 'help' );
