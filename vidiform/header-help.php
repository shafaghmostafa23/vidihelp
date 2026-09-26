<?php
/**
 * Help Center header.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="vf-skip" href="#vf-main"><?php esc_html_e( 'رفتن به محتوای اصلی', 'vidiform' ); ?></a>
<header class="vf-header">
	<div class="vf-header__inner">
		<a class="vf-brand" href="<?php echo esc_url( vf_help_url() ); ?>">
			<span class="vf-brand__mark"><?php vf_the_icon( 'help', 17 ); ?></span>
			<span class="vf-brand__name"><?php esc_html_e( 'مرکز راهنمای ویدی‌فرم', 'vidiform' ); ?></span>
		</a>
		<div class="vf-spacer"></div>
		<button type="button" class="vf-btn vf-btn--icon vf-theme-toggle" aria-pressed="false" aria-label="<?php esc_attr_e( 'تغییر حالت روشن و تیره', 'vidiform' ); ?>">
			<?php vf_the_icon( 'moon', 17, array( 'class' => 'vf-i-moon' ) ); ?>
			<?php vf_the_icon( 'sun', 17, array( 'class' => 'vf-i-sun' ) ); ?>
		</button>
		<a class="vf-btn vf-btn--secondary vf-help-back" href="<?php echo esc_url( vf_opt( 'general', 'panel_url' ) ); ?>">
			<?php vf_the_icon( 'chev-back', 15, array( 'stroke-width' => '2.2' ) ); ?>
			<span class="vf-help-back__long"><?php esc_html_e( 'بازگشت به پنل ویدی‌فرم', 'vidiform' ); ?></span>
			<span class="vf-help-back__short"><?php esc_html_e( 'پنل', 'vidiform' ); ?></span>
		</a>
	</div>
</header>
