<?php
/**
 * Static pages (rendered in the Blog chrome).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );

while ( have_posts() ) :
	the_post();
	?>
	<main id="vf-main" class="vf-container vf-single">
		<article <?php post_class( 'vf-single__article' ); ?> aria-labelledby="vf-page-title">
			<header class="vf-single__head">
				<h1 class="vf-single__title" id="vf-page-title"><?php the_title(); ?></h1>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="vf-single__cover"><?php the_post_thumbnail( 'vf-hero' ); ?></figure>
			<?php endif; ?>
			<div class="vf-prose vf-single__content">
				<?php
				the_content();
				wp_link_pages();
				?>
			</div>
		</article>
		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="vf-single__comments"><?php comments_template(); ?></div>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer( 'blog' );
