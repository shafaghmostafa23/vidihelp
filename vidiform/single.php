<?php
/**
 * Blog single post.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

while ( have_posts() ) :
	the_post();
	$vf_post = get_post();
	$vf_cat  = vf_post_primary_cat();
	?>
	<main id="vf-main" class="vf-container vf-single">
		<article <?php post_class( 'vf-single__article' ); ?> aria-labelledby="vf-post-title">
			<header class="vf-single__head">
				<?php vf_breadcrumbs( vf_blog_breadcrumb_items() ); ?>
				<?php if ( $vf_cat ) : ?>
					<a class="vf-pill" href="<?php echo esc_url( get_category_link( $vf_cat ) ); ?>"><?php echo esc_html( $vf_cat->name ); ?></a>
				<?php endif; ?>
				<h1 class="vf-single__title" id="vf-post-title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="vf-single__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<?php vf_post_meta( $vf_post, true ); ?>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="vf-single__cover">
					<?php the_post_thumbnail( 'vf-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
					<?php if ( get_the_post_thumbnail_caption() ) : ?>
						<figcaption><?php the_post_thumbnail_caption(); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="vf-prose vf-single__content">
				<?php
				the_content();
				wp_link_pages( array(
					'before' => '<nav class="vf-page-links" aria-label="' . esc_attr__( 'صفحه‌های نوشته', 'vidiform' ) . '">',
					'after'  => '</nav>',
				) );
				?>
			</div>

			<footer class="vf-single__foot">
				<?php
				$vf_tags = get_the_tags();
				if ( $vf_tags ) :
					?>
					<div class="vf-chips vf-single__tags" aria-label="<?php esc_attr_e( 'برچسب‌ها', 'vidiform' ); ?>">
						<?php foreach ( $vf_tags as $vf_t ) : ?>
							<a class="vf-chip" href="<?php echo esc_url( get_tag_link( $vf_t ) ); ?>" rel="tag">#<?php echo esc_html( $vf_t->name ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php
				if ( vf_opt( 'blog', 'show_share', 1 ) ) {
					vf_share_buttons( $vf_post );
				}
				if ( vf_opt( 'blog', 'show_author', 1 ) ) {
					vf_author_box( (int) $vf_post->post_author );
				}
				?>
			</footer>

			<?php
			$vf_prev = get_previous_post();
			$vf_next = get_next_post();
			if ( $vf_prev || $vf_next ) :
				?>
				<nav class="vf-pn" aria-label="<?php esc_attr_e( 'نوشته‌ی قبلی و بعدی', 'vidiform' ); ?>">
					<?php if ( $vf_prev ) : ?>
						<a class="vf-pn__item" href="<?php echo esc_url( get_permalink( $vf_prev ) ); ?>" rel="prev">
							<span class="vf-pn__label"><?php esc_html_e( 'نوشته‌ی قبلی', 'vidiform' ); ?></span>
							<span class="vf-pn__title"><?php echo esc_html( get_the_title( $vf_prev ) ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( $vf_next ) : ?>
						<a class="vf-pn__item vf-pn__item--next" href="<?php echo esc_url( get_permalink( $vf_next ) ); ?>" rel="next">
							<span class="vf-pn__label"><?php esc_html_e( 'نوشته‌ی بعدی', 'vidiform' ); ?></span>
							<span class="vf-pn__title"><?php echo esc_html( get_the_title( $vf_next ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</article>

		<?php
		$vf_related = vf_blog_related( get_the_ID(), (int) vf_opt( 'blog', 'related_count', 3 ) );
		if ( $vf_related ) :
			?>
			<section class="vf-blog-section vf-single__related" aria-labelledby="vf-related-posts">
				<h2 class="vf-blog-section__title" id="vf-related-posts"><?php esc_html_e( 'نوشته‌های مرتبط', 'vidiform' ); ?></h2>
				<div class="vf-post-grid">
					<?php
					foreach ( $vf_related as $vf_r ) {
						vf_post_card( $vf_r, 'card', 3 );
					}
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="vf-single__comments"><?php comments_template(); ?></div>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer( 'blog' );
