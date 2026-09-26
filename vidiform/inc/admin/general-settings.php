<?php
/**
 * Site-wide settings shared by both systems (Settings → ویدی‌فرم).
 * Kept outside both the Help Center and the Blog menus on purpose.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the page.
 */
function vf_general_settings_menu() {
	add_options_page( __( 'تنظیمات عمومی ویدی‌فرم', 'vidiform' ), __( 'ویدی‌فرم', 'vidiform' ), 'manage_options', 'vf-general', 'vf_general_settings_page' );
}
add_action( 'admin_menu', 'vf_general_settings_menu' );

/**
 * Render the page (standard WordPress settings screen).
 */
function vf_general_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_enqueue_media();
	$og = (int) vf_opt( 'general', 'og_image' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'تنظیمات عمومی ویدی‌فرم', 'vidiform' ); ?></h1>
		<p><?php esc_html_e( 'این تنظیمات بین مرکز راهنما و وبلاگ مشترک است. تنظیمات هر سیستم در منوی خودش قرار دارد.', 'vidiform' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php settings_fields( 'vf_general_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="vf-panel-url"><?php esc_html_e( 'آدرس پنل ویدی‌فرم', 'vidiform' ); ?></label></th>
					<td><input id="vf-panel-url" class="regular-text code" dir="ltr" type="url" name="vf_general[panel_url]" value="<?php echo esc_attr( vf_opt( 'general', 'panel_url' ) ); ?>">
						<p class="description"><?php esc_html_e( 'مقصد دکمه‌ی «بازگشت به پنل ویدی‌فرم» و «ورود به ویدی‌فرم».', 'vidiform' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'صفحه‌ی اصلی سایت', 'vidiform' ); ?></th>
					<td>
						<label><input type="radio" name="vf_general[front]" value="help"<?php checked( vf_opt( 'general', 'front' ), 'help' ); ?>> <?php esc_html_e( 'مرکز راهنما', 'vidiform' ); ?></label><br>
						<label><input type="radio" name="vf_general[front]" value="blog"<?php checked( vf_opt( 'general', 'front' ), 'blog' ); ?>> <?php esc_html_e( 'وبلاگ', 'vidiform' ); ?></label>
						<p class="description"><?php esc_html_e( 'بازدیدکننده‌ی ریشه‌ی سایت به این بخش هدایت می‌شود.', 'vidiform' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'حالت نمایش پیش‌فرض', 'vidiform' ); ?></th>
					<td>
						<select name="vf_general[theme_mode]">
							<option value="system"<?php selected( vf_opt( 'general', 'theme_mode' ), 'system' ); ?>><?php esc_html_e( 'مطابق سیستم کاربر', 'vidiform' ); ?></option>
							<option value="light"<?php selected( vf_opt( 'general', 'theme_mode' ), 'light' ); ?>><?php esc_html_e( 'روشن', 'vidiform' ); ?></option>
							<option value="dark"<?php selected( vf_opt( 'general', 'theme_mode' ), 'dark' ); ?>><?php esc_html_e( 'تیره', 'vidiform' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'کاربران همیشه می‌توانند با دکمه‌ی ماه/خورشید حالت را عوض کنند.', 'vidiform' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'تاریخ و اعداد', 'vidiform' ); ?></th>
					<td>
						<label><input type="checkbox" name="vf_general[jalali]" value="1"<?php checked( vf_opt( 'general', 'jalali' ) ); ?>> <?php esc_html_e( 'نمایش تاریخ شمسی', 'vidiform' ); ?></label><br>
						<label><input type="checkbox" name="vf_general[latin_digits]" value="1"<?php checked( vf_opt( 'general', 'latin_digits' ) ); ?>> <?php esc_html_e( 'استفاده از اعداد لاتین به‌جای اعداد فارسی', 'vidiform' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="vf-og"><?php esc_html_e( 'تصویر پیش‌فرض اشتراک‌گذاری (OG)', 'vidiform' ); ?></label></th>
					<td>
						<input id="vf-og" type="number" min="0" name="vf_general[og_image]" value="<?php echo esc_attr( $og ); ?>" class="small-text">
						<button type="button" class="button" id="vf-og-pick"><?php esc_html_e( 'انتخاب از کتابخانه', 'vidiform' ); ?></button>
						<p class="description"><?php esc_html_e( 'شناسه‌ی پیوست تصویر؛ برای صفحه‌هایی که تصویر شاخص ندارند.', 'vidiform' ); ?></p>
						<script>
						document.getElementById('vf-og-pick').addEventListener('click', function () {
							if (!window.wp || !wp.media) { return; }
							var f = wp.media({ library: { type: 'image' }, multiple: false });
							f.on('select', function () { document.getElementById('vf-og').value = f.state().get('selection').first().id; });
							f.open();
						});
						</script>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
