<?php
/**
 * Admin shell: two separate top-level menus (Help Center, Blog) and the full-screen
 * VidiForm admin frame (sidebar + main) recreated from the source admin UI.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pages belonging to each system.
 *
 * @return array<string,string> page slug => system
 */
function vf_admin_pages() {
	return array(
		'vf-help'          => 'help',
		'vf-help-guide'    => 'help',
		'vf-help-cats'     => 'help',
		'vf-help-settings' => 'help',
		'vf-blog'          => 'blog',
		'vf-blog-posts'    => 'blog',
		'vf-blog-content'  => 'blog',
		'vf-blog-cats'     => 'blog',
		'vf-blog-tags'     => 'blog',
		'vf-blog-authors'  => 'blog',
		'vf-blog-settings' => 'blog',
		'vf-blog-landing'  => 'blog',
	);
}

/**
 * Current VidiForm admin page slug (or '').
 *
 * @return string
 */
function vf_admin_current_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- routing only.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	return isset( vf_admin_pages()[ $page ] ) ? $page : '';
}

/**
 * Body class for the full-screen app frame.
 *
 * @param string $classes Classes.
 * @return string
 */
function vf_admin_body_class( $classes ) {
	$page = vf_admin_current_page();
	if ( $page ) {
		$classes .= ' vf-app-screen vf-app-' . vf_admin_pages()[ $page ];
	}
	return $classes;
}
add_filter( 'admin_body_class', 'vf_admin_body_class' );

/**
 * Keep the app screens clean from unrelated notices.
 */
function vf_admin_strip_notices() {
	if ( vf_admin_current_page() ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}
}
add_action( 'in_admin_header', 'vf_admin_strip_notices', 1000 );

/**
 * Shared admin assets for app screens.
 *
 * @param string $hook Hook suffix.
 */
function vf_admin_assets( $hook ) {
	$page = vf_admin_current_page();
	if ( ! $page ) {
		return;
	}
	wp_enqueue_style( 'vf-fonts', VF_URI . '/assets/css/fonts.css', array(), vf_asset_ver( 'assets/css/fonts.css' ) );
	wp_enqueue_style( 'vf-base', VF_URI . '/assets/css/base.css', array( 'vf-fonts' ), vf_asset_ver( 'assets/css/base.css' ) );
	wp_enqueue_style( 'vf-admin', VF_URI . '/assets/css/admin.css', array( 'vf-base' ), vf_asset_ver( 'assets/css/admin.css' ) );
	wp_enqueue_script( 'vf-theme', VF_URI . '/assets/js/theme.js', array(), vf_asset_ver( 'assets/js/theme.js' ), true );
	wp_enqueue_script( 'vf-admin-core', VF_URI . '/assets/js/admin-core.js', array( 'vf-theme' ), vf_asset_ver( 'assets/js/admin-core.js' ), true );
	wp_localize_script( 'vf-admin-core', 'VF_ADMIN', array(
		'rest'  => esc_url_raw( rest_url() ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'i18n'  => array(
			'saved'    => __( 'ذخیره شد.', 'vidiform' ),
			'error'    => __( 'خطا در انجام عملیات. دوباره تلاش کنید.', 'vidiform' ),
			'confirm'  => __( 'مطمئن هستید؟', 'vidiform' ),
			'cancel'   => __( 'انصراف', 'vidiform' ),
			'delete'   => __( 'حذف', 'vidiform' ),
			'unsaved'  => __( 'تغییرات ذخیره نشده‌اند.', 'vidiform' ),
		),
	) );
}
add_action( 'admin_enqueue_scripts', 'vf_admin_assets' );

/**
 * Sidebar navigation definitions for each system (never mixed).
 *
 * @param string $system help|blog.
 * @return array
 */
function vf_admin_nav( $system ) {
	if ( 'help' === $system ) {
		return array(
			'title' => __( 'مرکز راهنما', 'vidiform' ),
			'icon'  => 'help',
			'items' => array(
				array( 'vf-help', __( 'راهنماها', 'vidiform' ), 'book', 'edit_help_articles', array( 'vf-help-guide' ) ),
				array( 'vf-help-cats', __( 'دسته‌بندی راهنما', 'vidiform' ), 'grid3', 'manage_help_center', array() ),
				array( 'upload.php', __( 'رسانه', 'vidiform' ), 'image', 'upload_files', array() ),
				array( 'vf-help-settings', __( 'تنظیمات', 'vidiform' ), 'gear', 'manage_help_center', array() ),
			),
			'view'  => vf_help_url(),
		);
	}
	return array(
		'title' => __( 'وبلاگ', 'vidiform' ),
		'icon'  => 'book',
		'items' => array(
			array( 'vf-blog', __( 'نمای کلی', 'vidiform' ), 'grid', 'edit_posts', array() ),
			array( 'vf-blog-posts', __( 'نوشته‌ها', 'vidiform' ), 'list', 'edit_posts', array() ),
			array( 'vf-blog-content', __( 'استودیو محتوا', 'vidiform' ), 'templates', 'edit_posts', array() ),
			array( 'vf-blog-landing', __( 'صفحه بلاگ', 'vidiform' ), 'home', 'manage_categories', array() ),
			array( 'vf-blog-cats', __( 'دسته‌بندی‌ها', 'vidiform' ), 'folder', 'manage_categories', array() ),
			array( 'vf-blog-tags', __( 'برچسب‌ها', 'vidiform' ), 'tag', 'manage_categories', array() ),
			array( 'vf-blog-authors', __( 'نویسندگان', 'vidiform' ), 'user', 'list_users', array() ),
			array( 'upload.php', __( 'رسانه', 'vidiform' ), 'image', 'upload_files', array() ),
			array( 'vf-blog-settings', __( 'تنظیمات', 'vidiform' ), 'gear', 'manage_categories', array() ),
		),
		'view'  => vf_blog_home_url(),
	);
}

/**
 * Open the app frame.
 *
 * @param string $system help|blog.
 */
function vf_admin_open( $system ) {
	$nav     = vf_admin_nav( $system );
	$current = vf_admin_current_page();
	?>
	<div class="vf-app" dir="rtl" data-system="<?php echo esc_attr( $system ); ?>">
		<aside class="vf-app__side">
			<div class="vf-app__brand">
				<div class="vf-app__brand-mark"><?php vf_the_icon( 'shield', 17 ); ?></div>
				<div>
					<div class="vf-app__brand-title"><?php esc_html_e( 'پنل مدیریت', 'vidiform' ); ?></div>
					<div class="vf-app__brand-sub"><?php echo esc_html( $nav['title'] ); ?></div>
				</div>
				<button type="button" class="vf-app__menu-btn" aria-expanded="false" aria-controls="vf-app-nav" aria-label="<?php esc_attr_e( 'منو', 'vidiform' ); ?>"><?php vf_the_icon( 'menu', 18 ); ?></button>
			</div>
			<nav class="vf-app__nav" id="vf-app-nav" aria-label="<?php echo esc_attr( $nav['title'] ); ?>">
				<div class="vf-app__group"><?php echo esc_html( $nav['title'] ); ?></div>
				<?php
				foreach ( $nav['items'] as $it ) :
					if ( ! current_user_can( $it[3] ) ) {
						continue;
					}
					$active = $current === $it[0] || in_array( $current, $it[4], true );
					$url    = false !== strpos( $it[0], '.php' ) ? admin_url( $it[0] ) : admin_url( 'admin.php?page=' . $it[0] );
					?>
					<a class="vf-app__link<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
						<?php vf_the_icon( $it[2], 18, array( 'stroke-width' => '1.9' ) ); ?>
						<span><?php echo esc_html( $it[1] ); ?></span>
					</a>
				<?php endforeach; ?>
				<div class="vf-app__foot">
					<a class="vf-app__link vf-app__link--muted" href="<?php echo esc_url( $nav['view'] ); ?>" target="_blank" rel="noopener">
						<?php vf_the_icon( 'eye', 18, array( 'stroke-width' => '1.9' ) ); ?>
						<span><?php esc_html_e( 'مشاهده‌ی سایت', 'vidiform' ); ?></span>
					</a>
					<a class="vf-app__link vf-app__link--muted" href="<?php echo esc_url( admin_url() ); ?>">
						<?php vf_the_icon( 'wp', 18, array( 'stroke-width' => '1.9' ) ); ?>
						<span><?php esc_html_e( 'پیشخوان وردپرس', 'vidiform' ); ?></span>
					</a>
					<button type="button" class="vf-app__link vf-app__link--muted vf-theme-toggle" aria-pressed="false">
						<?php vf_the_icon( 'moon', 18, array( 'class' => 'vf-i-moon', 'stroke-width' => '1.9' ) ); ?>
						<?php vf_the_icon( 'sun', 18, array( 'class' => 'vf-i-sun', 'stroke-width' => '1.9' ) ); ?>
						<span><?php esc_html_e( 'حالت روشن / تیره', 'vidiform' ); ?></span>
					</button>
				</div>
			</nav>
		</aside>
		<main class="vf-app__main" id="vf-app-main">
	<?php
}

/**
 * Close the app frame.
 */
function vf_admin_close() {
	?>
		</main>
	</div>
	<div class="vf-toast" id="vf-toast" role="status" aria-live="polite" hidden></div>
	<?php
}

/**
 * Page header block.
 *
 * @param string $title  Title.
 * @param string $desc   Description.
 * @param string $action Right-side HTML (already escaped).
 */
function vf_admin_head( $title, $desc = '', $action = '' ) {
	?>
	<div class="vf-a-head">
		<div class="vf-a-head__text">
			<h1 class="vf-a-title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $desc ) : ?>
				<p class="vf-a-desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput -- callers pass escaped markup. ?>
	</div>
	<?php
}

/**
 * Register the two separate top-level admin menus.
 */
function vf_admin_menus() {
	// ---------------- Help Center ----------------
	add_menu_page( __( 'مرکز راهنما', 'vidiform' ), __( 'مرکز راهنما', 'vidiform' ), 'edit_help_articles', 'vf-help', 'vf_help_page_guides', 'dashicons-sos', 25 );
	add_submenu_page( 'vf-help', __( 'راهنماها', 'vidiform' ), __( 'راهنماها', 'vidiform' ), 'edit_help_articles', 'vf-help', 'vf_help_page_guides' );
	add_submenu_page( 'vf-help', __( 'ویرایش راهنما', 'vidiform' ), __( 'راهنمای جدید', 'vidiform' ), 'edit_help_articles', 'vf-help-guide', 'vf_help_page_editor' );
	add_submenu_page( 'vf-help', __( 'دسته‌بندی راهنما', 'vidiform' ), __( 'دسته‌بندی راهنما', 'vidiform' ), 'manage_help_center', 'vf-help-cats', 'vf_help_page_cats' );
	add_submenu_page( 'vf-help', __( 'رسانه', 'vidiform' ), __( 'رسانه', 'vidiform' ), 'upload_files', 'upload.php' );
	add_submenu_page( 'vf-help', __( 'تنظیمات مرکز راهنما', 'vidiform' ), __( 'تنظیمات', 'vidiform' ), 'manage_help_center', 'vf-help-settings', 'vf_help_page_settings' );

	// ---------------- Blog ----------------
	add_menu_page( __( 'وبلاگ', 'vidiform' ), __( 'وبلاگ', 'vidiform' ), 'edit_posts', 'vf-blog', 'vf_blog_page_dashboard', 'dashicons-welcome-write-blog', 26 );
	add_submenu_page( 'vf-blog', __( 'نمای کلی وبلاگ', 'vidiform' ), __( 'نمای کلی', 'vidiform' ), 'edit_posts', 'vf-blog', 'vf_blog_page_dashboard' );
	add_submenu_page( 'vf-blog', __( 'نوشته‌ها', 'vidiform' ), __( 'نوشته‌ها', 'vidiform' ), 'edit_posts', 'vf-blog-posts', 'vf_blog_page_posts' );
	add_submenu_page( 'vf-blog', __( 'استودیو محتوا', 'vidiform' ), __( 'استودیو محتوا', 'vidiform' ), 'edit_posts', 'vf-blog-content', 'vf_blog_content_page' );
	add_submenu_page( 'vf-blog', __( 'نوشته‌ی جدید', 'vidiform' ), __( 'نوشته‌ی جدید', 'vidiform' ), 'edit_posts', 'post-new.php' );
	add_submenu_page( 'vf-blog', __( 'تنظیمات صفحه بلاگ', 'vidiform' ), __( 'صفحه بلاگ', 'vidiform' ), 'manage_categories', 'vf-blog-landing', 'vf_blog_page_landing' );
	add_submenu_page( 'vf-blog', __( 'دسته‌بندی‌ها', 'vidiform' ), __( 'دسته‌بندی‌ها', 'vidiform' ), 'manage_categories', 'vf-blog-cats', 'vf_blog_page_cats' );
	add_submenu_page( 'vf-blog', __( 'برچسب‌ها', 'vidiform' ), __( 'برچسب‌ها', 'vidiform' ), 'manage_categories', 'vf-blog-tags', 'vf_blog_page_tags' );
	add_submenu_page( 'vf-blog', __( 'نویسندگان', 'vidiform' ), __( 'نویسندگان', 'vidiform' ), 'list_users', 'vf-blog-authors', 'vf_blog_page_authors' );
	add_submenu_page( 'vf-blog', __( 'رسانه', 'vidiform' ), __( 'رسانه', 'vidiform' ), 'upload_files', 'upload.php' );
	add_submenu_page( 'vf-blog', __( 'تنظیمات وبلاگ', 'vidiform' ), __( 'تنظیمات', 'vidiform' ), 'manage_categories', 'vf-blog-settings', 'vf_blog_page_settings' );

	// The Blog menu replaces the core "Posts" menu so the two systems stay clearly separated.
	remove_menu_page( 'edit.php' );
}
add_action( 'admin_menu', 'vf_admin_menus' );

/**
 * Keep the Blog menu highlighted while editing a post in the block editor, and redirect the
 * generic help_article screens to the dedicated Help Center editor.
 *
 * @param string $parent_file Parent.
 * @return string
 */
function vf_admin_parent_file( $parent_file ) {
	global $current_screen, $submenu_file;
	if ( $current_screen && 'post' === $current_screen->post_type && in_array( $current_screen->base, array( 'post', 'edit', 'edit-tags', 'term' ), true ) ) {
		$submenu_file = 'post' === $current_screen->base && 'add' === $current_screen->action ? 'post-new.php' : 'vf-blog-posts'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		return 'vf-blog';
	}
	return $parent_file;
}
add_filter( 'parent_file', 'vf_admin_parent_file' );

/**
 * Redirect core screens to the VidiForm screens.
 */
function vf_admin_redirects() {
	global $pagenow;
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
	$post = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$tax  = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
	// phpcs:enable

	if ( 'post-new.php' === $pagenow && 'help_article' === $type ) {
		wp_safe_redirect( admin_url( 'admin.php?page=vf-help-guide&new=1' ) );
		exit;
	}
	if ( 'post.php' === $pagenow && $post && 'help_article' === get_post_type( $post ) && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( admin_url( 'admin.php?page=vf-help-guide&guide=' . $post ) );
		exit;
	}
	if ( 'edit.php' === $pagenow && 'help_article' === $type ) {
		wp_safe_redirect( admin_url( 'admin.php?page=vf-help' ) );
		exit;
	}
	if ( 'edit.php' === $pagenow && ( '' === $type || 'post' === $type ) && ! isset( $_GET['post_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( admin_url( 'admin.php?page=vf-blog-posts' ) );
		exit;
	}
	if ( 'edit-tags.php' === $pagenow && 'category' === $tax ) {
		wp_safe_redirect( admin_url( 'admin.php?page=vf-blog-cats' ) );
		exit;
	}
	if ( 'edit-tags.php' === $pagenow && 'post_tag' === $tax ) {
		wp_safe_redirect( admin_url( 'admin.php?page=vf-blog-tags' ) );
		exit;
	}
}
add_action( 'admin_init', 'vf_admin_redirects' );

/**
 * Edit links for help_article point at the dedicated editor (admin bar, lists, etc.).
 *
 * @param string $link    Link.
 * @param int    $post_id Post id.
 * @return string
 */
function vf_help_edit_link( $link, $post_id ) {
	if ( 'help_article' === get_post_type( $post_id ) ) {
		return admin_url( 'admin.php?page=vf-help-guide&guide=' . (int) $post_id );
	}
	return $link;
}
add_filter( 'get_edit_post_link', 'vf_help_edit_link', 10, 2 );
