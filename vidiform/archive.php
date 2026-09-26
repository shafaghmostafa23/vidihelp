<?php
/**
 * Blog archives: category, tag, date.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

global $wp_query;
$vf_obj    = get_queried_object();
$vf_active = is_category() ? (int) $vf_obj->term_id : 0;
if ( is_category() && $vf_obj->parent ) {
	$vf_anc    = get_ancestors( $vf_obj->term_id, 'category', 'taxonomy' );
	$vf_active = (int) end( $vf_anc );
}
if ( is_category() ) {
	$vf_title = single_cat_title( '', false );
} elseif ( is_tag() ) {
	$vf_title = '#' . single_tag_title( '', false );
} else {
	$vf_title = wp_strip_all_tags( get_the_archive_title() );
}
$vf_desc = is_category() || is_tag() ? term_description() : '';
?>
<main id="vf-main" class="vf-container vf-blog-body">
	<header class="vf-archive-head">
		<?php vf_breadcrumbs( vf_blog_breadcrumb_items() ); ?>
		<h1 class="vf-page-title"><?php echo esc_html( $vf_title ); ?></h1>
		<?php if ( $vf_desc ) : ?>
			<div class="vf-page-desc"><?php echo wp_kses_post( $vf_desc ); ?></div>
		<?php endif; ?>
		<p class="vf-archive-head__count">
			<?php
			/* translators: %s number of posts */
			echo esc_html( sprintf( __( '%s نوشته', 'vidiform' ), vf_num( (int) $wp_query->found_posts ) ) );
			?>
		</p>
	</header>

	<?php
	if ( is_category() ) {
		vf_blog_category_nav( $vf_active );
		$vf_children = get_categories( array( 'parent' => $vf_obj->term_id, 'hide_empty' => true ) );
		if ( $vf_children ) {
			echo '<nav class="vf-chips vf-chips--sub" aria-label="' . esc_attr__( 'زیر‌دسته‌ها', 'vidiform' ) . '">';
			foreach ( $vf_children as $vf_child ) {
				echo '<a class="vf-chip" href="' . esc_url( get_category_link( $vf_child ) ) . '">' . esc_html( $vf_child->name ) . '</a>';
			}
			echo '</nav>';
		}
	}
	?>

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
		<?php vf_blog_empty( __( 'در این بخش هنوز نوشته‌ای منتشر نشده است', 'vidiform' ), __( 'از دسته‌بندی‌های دیگر یا جست‌وجو استفاده کنید.', 'vidiform' ) ); ?>
	<?php endif; ?>
</main>
<?php
get_footer( 'blog' );
