<?php
/**
 * Help Center search results (/help/search/?q=…).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'help' );

$vf_q = get_search_query( false );
?>
<main id="vf-main" class="vf-help-home">
	<section class="vf-hero">
		<div class="vf-hero__inner">
			<h1 class="vf-hero__title">
				<?php
				/* translators: %s search term */
				echo esc_html( sprintf( __( 'نتایج جست‌وجو برای «%s»', 'vidiform' ), $vf_q ) );
				?>
			</h1>
			<?php vf_help_search_form( $vf_q, false ); ?>
		</div>
	</section>

	<div class="vf-container vf-help-home__body">
		<?php vf_breadcrumbs( vf_help_breadcrumb_items(), 'vf-crumbs--center' ); ?>
		<?php if ( have_posts() ) : ?>
			<div class="vf-results">
				<div class="vf-results__label">
					<?php
					global $wp_query;
					/* translators: %s: number of results */
					echo esc_html( sprintf( __( '%s نتیجه', 'vidiform' ), vf_num( (int) $wp_query->found_posts ) ) );
					?>
				</div>
				<?php
				while ( have_posts() ) :
					the_post();
					$vf_term = vf_guide_term( get_the_ID() );
					?>
					<a class="vf-result" href="<?php the_permalink(); ?>">
						<span class="vf-result__main">
							<span class="vf-result__title"><?php the_title(); ?></span>
							<?php if ( $vf_term ) : ?>
								<span class="vf-result__path"><?php echo esc_html( $vf_term->name ); ?></span>
							<?php endif; ?>
						</span>
						<span class="vf-result__read"><?php echo esc_html( vf_minutes_label( vf_guide_minutes( get_post() ) ) ); ?></span>
					</a>
				<?php endwhile; ?>
				<?php
				the_posts_pagination( array(
					'class'     => 'vf-pagination',
					'prev_text' => __( 'قبلی', 'vidiform' ),
					'next_text' => __( 'بعدی', 'vidiform' ),
				) );
				?>
			</div>
		<?php else : ?>
			<div class="vf-empty">
				<p class="vf-empty__title"><?php esc_html_e( 'چیزی پیدا نشد', 'vidiform' ); ?></p>
				<p class="vf-empty__desc"><?php esc_html_e( 'عبارت دیگری را امتحان کنید یا از دسته‌بندی‌های زیر شروع کنید.', 'vidiform' ); ?></p>
				<a class="vf-btn vf-btn--secondary" href="<?php echo esc_url( vf_help_url() ); ?>"><?php esc_html_e( 'مشاهده دسته‌بندی‌ها', 'vidiform' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer( 'help' );
