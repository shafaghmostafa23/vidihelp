<?php
/**
 * Fallback template (required by WordPress). Specific templates handle every view:
 * Help Center → help-home.php, taxonomy-help_category.php, single-help_article.php, help-search.php, help-404.php
 * Blog        → home.php, single.php, archive.php, author.php, search.php, 404.php, page.php
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );
?>
<main id="vf-main" class="vf-container vf-blog-body">
	<?php if ( have_posts() ) : ?>
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
		<?php vf_blog_empty( __( 'چیزی پیدا نشد', 'vidiform' ) ); ?>
	<?php endif; ?>
</main>
<?php
get_footer( 'blog' );
