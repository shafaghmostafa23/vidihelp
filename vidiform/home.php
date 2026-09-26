<?php
/**
 * Blog home (/blog/) — hero, category navigation, featured posts, latest posts.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

$vf_paged    = max( 1, (int) get_query_var( 'paged' ) );
$vf_featured = 1 === $vf_paged ? vf_blog_featured( (int) vf_opt( 'blog', 'featured_count', 3 ) ) : array();
?>
<main id="vf-main" class="vf-blog-home">
	<section class="vf-hero">
		<div class="vf-hero__inner">
			<h1 class="vf-hero__title"><?php echo esc_html( vf_opt( 'blog', 'hero_title' ) ); ?></h1>
			<?php if ( vf_opt( 'blog', 'hero_desc' ) ) : ?>
				<p class="vf-hero__desc"><?php echo esc_html( vf_opt( 'blog', 'hero_desc' ) ); ?></p>
			<?php endif; ?>
			<?php vf_blog_search_form( 'vf-blog-q-hero' ); ?>
		</div>
	</section>

	<div class="vf-container vf-blog-body">
		<?php vf_blog_category_nav(); ?>

		<?php if ( $vf_featured ) : ?>
			<section class="vf-blog-section" aria-labelledby="vf-featured-title">
				<h2 class="vf-blog-section__title" id="vf-featured-title"><?php esc_html_e( 'مطالب ویژه', 'vidiform' ); ?></h2>
				<div class="vf-featured<?php echo count( $vf_featured ) > 1 ? ' has-side' : ''; ?>">
					<?php vf_post_card( $vf_featured[0], 'featured', 3 ); ?>
					<?php if ( count( $vf_featured ) > 1 ) : ?>
						<div class="vf-featured__side">
							<?php
							foreach ( array_slice( $vf_featured, 1 ) as $vf_fp ) {
								vf_post_card( $vf_fp, 'row', 3 );
							}
							?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="vf-blog-section" aria-labelledby="vf-latest-title">
			<h2 class="vf-blog-section__title" id="vf-latest-title"><?php esc_html_e( 'تازه‌ترین نوشته‌ها', 'vidiform' ); ?></h2>
			<?php if ( have_posts() ) : ?>
				<div class="vf-post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						vf_post_card( get_post(), 'card', 3 );
					endwhile;
					?>
				</div>
				<?php vf_blog_pagination(); ?>
			<?php else : ?>
				<?php vf_blog_empty( __( 'هنوز نوشته‌ای منتشر نشده است', 'vidiform' ), __( 'به‌زودی مقاله‌های تازه این‌جا منتشر می‌شوند.', 'vidiform' ), false ); ?>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
get_footer( 'blog' );
