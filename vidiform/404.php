<?php
/**
 * Not found (blog / site). The Help Center has its own 404 (help-404.php).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'blog' );
?>
<main id="vf-main" class="vf-blog-home">
	<section class="vf-hero">
		<div class="vf-hero__inner">
			<p class="vf-hero__code" aria-hidden="true"><?php echo esc_html( vf_num( 404 ) ); ?></p>
			<h1 class="vf-hero__title"><?php esc_html_e( 'این صفحه پیدا نشد', 'vidiform' ); ?></h1>
			<p class="vf-hero__desc"><?php esc_html_e( 'ممکن است آدرس تغییر کرده یا صفحه حذف شده باشد.', 'vidiform' ); ?></p>
			<?php vf_blog_search_form( 'vf-blog-q-404' ); ?>
		</div>
	</section>
	<div class="vf-container vf-blog-body vf-center">
		<a class="vf-btn vf-btn--primary" href="<?php echo esc_url( vf_blog_home_url() ); ?>"><?php esc_html_e( 'بازگشت به وبلاگ', 'vidiform' ); ?></a>
		<a class="vf-btn vf-btn--secondary" href="<?php echo esc_url( vf_help_url() ); ?>"><?php esc_html_e( 'مرکز راهنما', 'vidiform' ); ?></a>
	</div>
</main>
<?php
get_footer( 'blog' );
