<?php
/**
 * Help Center "not found" state.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

get_header( 'help' );
?>
<main id="vf-main" class="vf-help-home">
	<section class="vf-hero">
		<div class="vf-hero__inner">
			<p class="vf-hero__code" aria-hidden="true"><?php echo esc_html( vf_num( 404 ) ); ?></p>
			<h1 class="vf-hero__title"><?php esc_html_e( 'این راهنما پیدا نشد', 'vidiform' ); ?></h1>
			<p class="vf-hero__desc"><?php esc_html_e( 'ممکن است آدرس تغییر کرده یا راهنما حذف شده باشد. جست‌وجو کنید یا به صفحه‌ی اصلی مرکز راهنما برگردید.', 'vidiform' ); ?></p>
			<?php vf_help_search_form( '', false ); ?>
		</div>
	</section>
	<div class="vf-container vf-help-home__body" style="text-align:center">
		<a class="vf-btn vf-btn--primary" href="<?php echo esc_url( vf_help_url() ); ?>"><?php esc_html_e( 'صفحه‌ی اصلی مرکز راهنما', 'vidiform' ); ?></a>
	</div>
</main>
<?php
get_footer( 'help' );
