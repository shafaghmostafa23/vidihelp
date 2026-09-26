<?php
/**
 * Help Center category / sub-category (/help/{cat}/ and /help/{cat}/{sub}/).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'help' );

$vf_term = get_queried_object();
$vf_root = $vf_term->parent ? get_term( vf_help_root_term( $vf_term->term_id ), 'help_category' ) : $vf_term;
$vf_subs = get_terms( array(
	'taxonomy'   => 'help_category',
	'parent'     => $vf_root->term_id,
	'hide_empty' => false,
) );
$vf_subs = is_wp_error( $vf_subs ) ? array() : vf_sort_help_terms( $vf_subs );
?>
<div class="vf-container vf-help-layout">
	<?php vf_help_sidebar( array( 'title' => __( 'دسته‌بندی راهنما', 'vidiform' ), 'active_cat' => (int) $vf_root->term_id ) ); ?>

	<main id="vf-main" class="vf-help-main">
		<a class="vf-back-link" href="<?php echo esc_url( vf_help_url() ); ?>">
			<?php vf_the_icon( 'chev-back', 15, array( 'stroke-width' => '2.4' ) ); ?>
			<span><?php esc_html_e( 'مرکز راهنما', 'vidiform' ); ?></span>
		</a>
		<?php if ( $vf_term->parent ) : ?>
			<?php vf_breadcrumbs( vf_help_breadcrumb_items() ); ?>
		<?php endif; ?>

		<h1 class="vf-page-title"><?php echo esc_html( $vf_term->name ); ?></h1>
		<?php
		$vf_desc = $vf_term->description ? $vf_term->description : $vf_root->description;
		if ( $vf_desc ) :
			?>
			<p class="vf-page-desc"><?php echo esc_html( $vf_desc ); ?></p>
		<?php endif; ?>

		<?php if ( $vf_subs ) : ?>
			<nav class="vf-chips" aria-label="<?php esc_attr_e( 'زیر‌دسته‌ها', 'vidiform' ); ?>">
				<a class="vf-chip<?php echo $vf_term->parent ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_term_link( $vf_root ) ); ?>"<?php echo $vf_term->parent ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'همه', 'vidiform' ); ?></a>
				<?php foreach ( $vf_subs as $vf_sub ) : ?>
					<a class="vf-chip<?php echo $vf_sub->term_id === $vf_term->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $vf_sub ) ); ?>"<?php echo $vf_sub->term_id === $vf_term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $vf_sub->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="vf-guide-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<a class="vf-guide-card" href="<?php the_permalink(); ?>">
						<h2 class="vf-guide-card__title"><?php the_title(); ?></h2>
						<?php if ( has_excerpt() ) : ?>
							<p class="vf-guide-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
						<span class="vf-guide-card__meta">
							<?php
							/* translators: %s: reading time */
							echo esc_html( sprintf( __( 'زمان مطالعه %s', 'vidiform' ), vf_minutes_label( vf_guide_minutes( get_post() ) ) ) );
							?>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php
			the_posts_pagination( array(
				'class'              => 'vf-pagination',
				'prev_text'          => __( 'قبلی', 'vidiform' ),
				'next_text'          => __( 'بعدی', 'vidiform' ),
				'before_page_number' => '',
			) );
			?>
		<?php else : ?>
			<div class="vf-empty">
				<p class="vf-empty__title"><?php esc_html_e( 'هنوز راهنمایی در این دسته منتشر نشده است', 'vidiform' ); ?></p>
				<p class="vf-empty__desc"><?php esc_html_e( 'از دسته‌بندی‌های دیگر یا جست‌وجو استفاده کنید.', 'vidiform' ); ?></p>
				<a class="vf-btn vf-btn--secondary" href="<?php echo esc_url( vf_help_url() ); ?>"><?php esc_html_e( 'بازگشت به مرکز راهنما', 'vidiform' ); ?></a>
			</div>
		<?php endif; ?>
	</main>
</div>
<?php
get_footer( 'help' );
