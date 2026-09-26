<?php
/**
 * Help Center admin screens (راهنماها، ویرایش راهنما، دسته‌بندی راهنما، تنظیمات).
 * Layouts reproduce the admin panel of the VidiForm Content Architecture HTML.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assets for Help Center screens.
 */
function vf_help_admin_assets() {
	$page = vf_admin_current_page();
	if ( ! $page || 0 !== strpos( $page, 'vf-help' ) ) {
		return;
	}
	if ( in_array( $page, array( 'vf-help-guide', 'vf-help-cats' ), true ) ) {
		wp_enqueue_media();
	}
	wp_enqueue_script( 'vf-admin-help', VF_URI . '/assets/js/admin-help.js', array( 'vf-admin-core' ), vf_asset_ver( 'assets/js/admin-help.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'vf_help_admin_assets', 20 );

/**
 * Print preloaded JSON for the admin app.
 *
 * @param string $id   Element id.
 * @param mixed  $data Data.
 */
function vf_admin_json( $id, $data ) {
	printf(
		'<script type="application/json" id="%s">%s</script>',
		esc_attr( $id ),
		wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
}

/**
 * Guides list (grouped by category, drag & drop between categories).
 */
function vf_help_page_guides() {
	if ( ! current_user_can( 'edit_help_articles' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	vf_admin_open( 'help' );
	$new = '<button type="button" class="vf-btn vf-btn--primary vf-btn--h44" data-action="new-guide">' . vf_icon( 'plus', 17, array( 'stroke-width' => '2.4' ) ) . '<span>' . esc_html__( 'راهنمای جدید', 'vidiform' ) . '</span></button>';
	?>
	<div class="vf-a-page vf-a-page--1000" data-screen="guides">
		<?php vf_admin_head( __( 'راهنماها', 'vidiform' ), __( 'راهنماها را بین دسته‌ها بکشید تا هم دسته و هم ترتیبشان تغییر کند.', 'vidiform' ), $new ); ?>
		<div class="vf-a-toolbar">
			<div class="vf-a-search">
				<?php vf_the_icon( 'search', 17, array( 'stroke-width' => '2.2' ) ); ?>
				<label class="screen-reader-text" for="vf-guide-search"><?php esc_html_e( 'جست‌وجوی راهنما', 'vidiform' ); ?></label>
				<input id="vf-guide-search" type="search" class="vf-a-input" placeholder="<?php esc_attr_e( 'جست‌وجوی راهنما…', 'vidiform' ); ?>" data-search>
			</div>
			<div class="vf-a-chips" role="group" aria-label="<?php esc_attr_e( 'فیلتر وضعیت', 'vidiform' ); ?>">
				<button type="button" class="vf-a-chip is-active" data-filter="all" aria-pressed="true"><?php esc_html_e( 'همه', 'vidiform' ); ?></button>
				<button type="button" class="vf-a-chip" data-filter="publish" aria-pressed="false"><?php esc_html_e( 'منتشرشده', 'vidiform' ); ?></button>
				<button type="button" class="vf-a-chip" data-filter="draft" aria-pressed="false"><?php esc_html_e( 'پیش‌نویس', 'vidiform' ); ?></button>
			</div>
		</div>
		<div data-root><div class="vf-a-loading" aria-busy="true"></div></div>
	</div>
	<?php
	vf_admin_json( 'vf-help-state', vf_help_admin_state() );
	vf_help_admin_modals();
	vf_admin_close();
}

/**
 * Guide editor (sections, media, hints, features, VidiForm, template reference, inline guide).
 */
function vf_help_page_editor() {
	if ( ! current_user_can( 'edit_help_articles' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing parameter.
	$id = isset( $_GET['guide'] ) ? absint( $_GET['guide'] ) : 0;
	vf_admin_open( 'help' );
	if ( ! $id ) {
		?>
		<div class="vf-a-page vf-a-page--1000" data-screen="new-guide">
			<div class="vf-a-empty">
				<p class="vf-a-empty__title"><?php esc_html_e( 'در حال ساخت راهنمای جدید…', 'vidiform' ); ?></p>
			</div>
		</div>
		<?php
		vf_admin_json( 'vf-help-state', vf_help_admin_state() );
		vf_admin_close();
		return;
	}
	$post = vf_help_load_guide( $id );
	if ( is_wp_error( $post ) ) {
		echo '<div class="vf-a-page"><div class="vf-a-empty"><p class="vf-a-empty__title">' . esc_html( $post->get_error_message() ) . '</p><a class="vf-btn vf-btn--secondary" href="' . esc_url( admin_url( 'admin.php?page=vf-help' ) ) . '">' . esc_html__( 'بازگشت به راهنماها', 'vidiform' ) . '</a></div></div>';
		vf_admin_close();
		return;
	}
	?>
	<div class="vf-a-page vf-a-page--1000 vf-a-page--editor" data-screen="guide-editor" data-id="<?php echo esc_attr( $id ); ?>">
		<a class="vf-a-back" href="<?php echo esc_url( admin_url( 'admin.php?page=vf-help' ) ); ?>">
			<?php vf_the_icon( 'chev-back', 15, array( 'stroke-width' => '2.4' ) ); ?>
			<span><?php esc_html_e( 'راهنماها', 'vidiform' ); ?></span>
		</a>
		<div data-root><div class="vf-a-loading" aria-busy="true"></div></div>
	</div>
	<?php
	vf_admin_json( 'vf-help-state', vf_help_admin_state() );
	vf_admin_json( 'vf-help-guide', vf_help_guide_payload( $post ) );
	vf_help_admin_modals();
	vf_admin_close();
}

/**
 * Categories + landing texts.
 */
function vf_help_page_cats() {
	if ( ! current_user_can( 'manage_help_center' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	vf_admin_open( 'help' );
	$add = '<button type="button" class="vf-btn vf-btn--primary vf-btn--h44" data-action="add-cat">' . esc_html__( '+ دسته‌ی جدید', 'vidiform' ) . '</button>';
	?>
	<div class="vf-a-page vf-a-page--960" data-screen="help-cats">
		<?php vf_admin_head( __( 'دسته‌بندی مرکز راهنما', 'vidiform' ), __( 'دسته‌ی اصلی ← زیر‌دسته (اختیاری) ← راهنماها. ترتیب کارت‌ها با کشیدن تغییر می‌کند.', 'vidiform' ), $add ); ?>

		<section class="vf-a-card vf-a-card--stack">
			<div>
				<h2 class="vf-a-card__title"><?php esc_html_e( 'صفحه‌ی اصلی مرکز راهنما', 'vidiform' ); ?></h2>
				<p class="vf-a-card__hint"><?php esc_html_e( 'این متن‌ها هاردکد نیستند و از همین‌جا در صفحه‌ی عمومی راهنما نمایش داده می‌شوند.', 'vidiform' ); ?></p>
			</div>
			<label class="vf-a-field"><?php esc_html_e( 'عنوان صفحه', 'vidiform' ); ?>
				<input class="vf-a-input" type="text" data-landing="landing_title" value="<?php echo esc_attr( vf_opt( 'help', 'landing_title' ) ); ?>">
			</label>
			<label class="vf-a-field"><?php esc_html_e( 'توضیح صفحه', 'vidiform' ); ?>
				<textarea class="vf-a-textarea" rows="2" data-landing="landing_desc"><?php echo esc_textarea( vf_opt( 'help', 'landing_desc' ) ); ?></textarea>
			</label>
		</section>

		<div data-root><div class="vf-a-loading" aria-busy="true"></div></div>
	</div>
	<?php
	vf_admin_json( 'vf-help-state', vf_help_admin_state() );
	vf_help_admin_modals();
	vf_admin_close();
}

/**
 * Help Center settings (Settings API) + default-structure import.
 */
function vf_help_page_settings() {
	if ( ! current_user_can( 'manage_help_center' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	vf_admin_open( 'help' );
	?>
	<div class="vf-a-page vf-a-page--960" data-screen="help-settings">
		<?php vf_admin_head( __( 'تنظیمات مرکز راهنما', 'vidiform' ), __( 'این تنظیمات فقط روی مرکز راهنما اثر دارند.', 'vidiform' ) ); ?>
		<?php settings_errors(); ?>
		<form class="vf-a-card vf-a-card--stack" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php settings_fields( 'vf_help_group' ); ?>
			<h2 class="vf-a-card__title"><?php esc_html_e( 'صفحه‌ی اصلی', 'vidiform' ); ?></h2>
			<label class="vf-a-field"><?php esc_html_e( 'عنوان صفحه', 'vidiform' ); ?>
				<input class="vf-a-input" type="text" name="vf_help[landing_title]" value="<?php echo esc_attr( vf_opt( 'help', 'landing_title' ) ); ?>">
			</label>
			<label class="vf-a-field"><?php esc_html_e( 'توضیح صفحه', 'vidiform' ); ?>
				<textarea class="vf-a-textarea" rows="2" name="vf_help[landing_desc]"><?php echo esc_textarea( vf_opt( 'help', 'landing_desc' ) ); ?></textarea>
			</label>
			<div class="vf-a-grid3">
				<label class="vf-a-field"><?php esc_html_e( 'تعداد راهنما در هر کارت صفحه‌ی اصلی', 'vidiform' ); ?>
					<input class="vf-a-input" type="number" min="1" max="10" name="vf_help[home_items]" value="<?php echo esc_attr( vf_opt( 'help', 'home_items' ) ); ?>">
				</label>
				<label class="vf-a-field"><?php esc_html_e( 'راهنما در هر صفحه‌ی دسته', 'vidiform' ); ?>
					<input class="vf-a-input" type="number" min="5" max="100" name="vf_help[per_page]" value="<?php echo esc_attr( vf_opt( 'help', 'per_page' ) ); ?>">
				</label>
				<label class="vf-a-field"><?php esc_html_e( 'تعداد مطالب مرتبط', 'vidiform' ); ?>
					<input class="vf-a-input" type="number" min="0" max="12" name="vf_help[related_count]" value="<?php echo esc_attr( vf_opt( 'help', 'related_count' ) ); ?>">
				</label>
			</div>
			<div><button type="submit" class="vf-btn vf-btn--primary"><?php esc_html_e( 'ذخیره', 'vidiform' ); ?></button></div>
		</form>

		<section class="vf-a-card vf-a-card--stack">
			<h2 class="vf-a-card__title"><?php esc_html_e( 'ساختار پیش‌فرض مرکز راهنما', 'vidiform' ); ?></h2>
			<p class="vf-a-card__hint"><?php esc_html_e( 'دسته‌بندی‌ها، زیر‌دسته‌ها، راهنماها و کتابخانه‌ی ویژگی‌های پیش‌فرض ویدی‌فرم را اضافه می‌کند. موارد موجود تغییر نمی‌کنند.', 'vidiform' ); ?></p>
			<div><button type="button" class="vf-btn vf-btn--secondary" data-action="seed"><?php esc_html_e( 'بارگذاری ساختار پیش‌فرض', 'vidiform' ); ?></button></div>
		</section>

		<section class="vf-a-card vf-a-card--stack">
			<h2 class="vf-a-card__title"><?php esc_html_e( 'آدرس‌ها', 'vidiform' ); ?></h2>
			<ul class="vf-a-routes" dir="ltr">
				<li><code><?php echo esc_html( wp_make_link_relative( vf_help_url() ) ); ?></code></li>
				<li><code><?php echo esc_html( wp_make_link_relative( vf_help_url( '{category}' ) ) ); ?></code></li>
				<li><code><?php echo esc_html( wp_make_link_relative( vf_help_url( '{category}/{sub-category}' ) ) ); ?></code></li>
				<li><code><?php echo esc_html( wp_make_link_relative( vf_help_url( '{category}/{guide}' ) ) ); ?></code></li>
				<li><code><?php echo esc_html( wp_make_link_relative( vf_help_search_url() ) ); ?>?q=…</code></li>
			</ul>
		</section>
	</div>
	<?php
	vf_help_admin_modals();
	vf_admin_close();
}

/**
 * Modal containers used by the Help Center admin app (filled by admin-help.js).
 */
function vf_help_admin_modals() {
	?>
	<div class="vf-modal" id="vf-a-modal" role="dialog" aria-modal="true" aria-labelledby="vf-a-modal-title" hidden>
		<div class="vf-modal__box vf-modal__box--wide">
			<div class="vf-modal__head">
				<h2 class="vf-modal__title" id="vf-a-modal-title"></h2>
				<button type="button" class="vf-modal__close" data-vf-close aria-label="<?php esc_attr_e( 'بستن', 'vidiform' ); ?>">×</button>
			</div>
			<div data-modal-body></div>
		</div>
	</div>
	<?php
}
