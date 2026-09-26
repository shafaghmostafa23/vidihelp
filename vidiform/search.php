<?php
/**
 * Blog search results (/?s=…). Help Center search has its own template (help-search.php).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

global $wp_query;
$vf_q = get_search_query( false );
?>
<main id="vf-main" class="vf-blog-home">
	<section class="vf-hero">
		<div class="vf-hero__inner">
			<h1 class="vf-hero__title">
				<?php
				/* translators: %s search term */
				echo esc_html( '' !== $vf_q ? sprintf( __( 'نتایج جست‌وجو برای «%s»', 'vidiform' ), $vf_q ) : __( 'جست‌وجو در وبلاگ', 'vidiform' ) );
				?>
			</h1>
			<?php vf_blog_search_form( 'vf-blog-q-search' ); ?>
		</div>
	</section>
	<div class="vf-container vf-blog-body">
		<?php vf_breadcrumbs( vf_blog_breadcrumb_items() ); ?>
		<?php if ( have_posts() && '' !== $vf_q ) : ?>
			<p class="vf-results__label">
				<?php
				/* translators: %s number of results */
				echo esc_html( sprintf( __( '%s نتیجه', 'vidiform' ), vf_num( (int) $wp_query->found_posts ) ) );
				?>
			</p>
			<div class="vf-tiles">
				<?php
				while ( have_posts() ) :
					the_post();
					vf_post_tile( get_post(), 2 );
				endwhile;
				?>
			</div>
			<?php vf_blog_pagination(); ?>
		<?php else : ?>
			<?php vf_blog_empty( __( 'چیزی پیدا نشد', 'vidiform' ), __( 'عبارت دیگری را امتحان کنید یا از دسته‌بندی‌ها شروع کنید.', 'vidiform' ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer( 'blog' );
