<?php
/**
 * Blog author page (/blog/author/{name}/).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

global $wp_query;
$vf_author = get_queried_object();
$vf_id     = (int) $vf_author->ID;
$vf_bio    = get_the_author_meta( 'description', $vf_id );
$vf_site   = get_the_author_meta( 'user_url', $vf_id );
?>
<main id="vf-main" class="vf-container vf-blog-body">
	<?php vf_breadcrumbs( vf_blog_breadcrumb_items() ); ?>
	<header class="vf-author-hero">
		<?php echo get_avatar( $vf_id, 96, '', '', array( 'class' => 'vf-author-hero__avatar' ) ); ?>
		<div class="vf-author-hero__body">
			<h1 class="vf-page-title"><?php echo esc_html( get_the_author_meta( 'display_name', $vf_id ) ); ?></h1>
			<?php if ( $vf_bio ) : ?>
				<p class="vf-page-desc"><?php echo esc_html( $vf_bio ); ?></p>
			<?php endif; ?>
			<div class="vf-author-hero__meta">
				<span>
					<?php
					/* translators: %s number of posts */
					echo esc_html( sprintf( __( '%s نوشته', 'vidiform' ), vf_num( (int) $wp_query->found_posts ) ) );
					?>
				</span>
				<?php if ( $vf_site ) : ?>
					<a href="<?php echo esc_url( $vf_site ); ?>" rel="noopener me" target="_blank"><?php echo esc_html( wp_parse_url( $vf_site, PHP_URL_HOST ) ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>
		<h2 class="vf-blog-section__title">
			<?php
			/* translators: %s author name */
			echo esc_html( sprintf( __( 'نوشته‌های %s', 'vidiform' ), get_the_author_meta( 'display_name', $vf_id ) ) );
			?>
		</h2>
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
		<?php vf_blog_empty( __( 'این نویسنده هنوز نوشته‌ای منتشر نکرده است', 'vidiform' ) ); ?>
	<?php endif; ?>
</main>
<?php
get_footer( 'blog' );
