<?php
/**
 * Comments (blog posts and pages).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="vf-comments" aria-labelledby="vf-comments-title">
	<?php if ( have_comments() ) : ?>
		<h2 class="vf-blog-section__title" id="vf-comments-title">
			<?php
			/* translators: %s: number of comments */
			echo esc_html( sprintf( __( '%s دیدگاه', 'vidiform' ), vf_num( get_comments_number() ) ) );
			?>
		</h2>
		<ol class="vf-comments__list">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 40,
			) );
			?>
		</ol>
		<?php the_comments_pagination( array( 'class' => 'vf-pagination' ) ); ?>
	<?php else : ?>
		<h2 class="screen-reader-text" id="vf-comments-title"><?php esc_html_e( 'دیدگاه‌ها', 'vidiform' ); ?></h2>
	<?php endif; ?>

	<?php
	comment_form( array(
		'class_form'         => 'vf-comment-form',
		'class_submit'       => 'vf-btn vf-btn--primary',
		'title_reply'        => __( 'دیدگاه خود را بنویسید', 'vidiform' ),
		'title_reply_before' => '<h3 id="reply-title" class="vf-blog-section__title">',
		'title_reply_after'  => '</h3>',
		'label_submit'       => __( 'ارسال دیدگاه', 'vidiform' ),
	) );
	?>
</section>
