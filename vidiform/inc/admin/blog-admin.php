<?php
/**
 * Blog admin screens (نمای کلی، نوشته‌ها، دسته‌بندی‌ها، برچسب‌ها، نویسندگان، تنظیمات).
 * Built in the same VidiForm admin frame as the Help Center, but fully separate:
 * its own menu, capabilities (core post caps), data (post/category/post_tag) and settings (vf_blog).
 *
 * Writes go through the WordPress core REST API (wp/v2) with the REST nonce, so core handles
 * capability checks and sanitization; settings use the Settings API (options.php + nonce).
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Assets for Blog screens.
 */
function vf_blog_admin_assets() {
	$page = vf_admin_current_page();
	if ( ! $page || 0 !== strpos( $page, 'vf-blog' ) ) {
		return;
	}
	wp_enqueue_script( 'vf-admin-blog', VF_URI . '/assets/js/admin-blog.js', array( 'vf-admin-core' ), vf_asset_ver( 'assets/js/admin-blog.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'vf_blog_admin_assets', 20 );

/**
 * Status label + pill class.
 *
 * @param string $status Status.
 * @return array [label, class]
 */
function vf_blog_status( $status ) {
	$map = array(
		'publish' => array( __( 'منتشرشده', 'vidiform' ), 'vf-pill--success' ),
		'draft'   => array( __( 'پیش‌نویس', 'vidiform' ), 'vf-pill--warn' ),
		'pending' => array( __( 'در انتظار بازبینی', 'vidiform' ), 'vf-pill--warn' ),
		'future'  => array( __( 'زمان‌بندی‌شده', 'vidiform' ), '' ),
		'private' => array( __( 'خصوصی', 'vidiform' ), 'vf-pill--premium' ),
	);
	return $map[ $status ] ?? array( $status, '' );
}

/**
 * Blog overview.
 */
function vf_blog_page_dashboard() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	$counts = wp_count_posts( 'post' );
	$base   = admin_url( 'admin.php?page=vf-blog-posts' );
	$new    = '<a class="vf-btn vf-btn--primary vf-btn--h44" href="' . esc_url( admin_url( 'post-new.php' ) ) . '">' . vf_icon( 'plus', 17, array( 'stroke-width' => '2.4' ) ) . '<span>' . esc_html__( 'نوشته‌ی جدید', 'vidiform' ) . '</span></a>';
	$stats  = array(
		array( __( 'منتشرشده', 'vidiform' ), (int) $counts->publish, add_query_arg( 'status', 'publish', $base ) ),
		array( __( 'پیش‌نویس', 'vidiform' ), (int) $counts->draft, add_query_arg( 'status', 'draft', $base ) ),
		array( __( 'زمان‌بندی‌شده', 'vidiform' ), (int) $counts->future, add_query_arg( 'status', 'future', $base ) ),
		array( __( 'در انتظار بازبینی', 'vidiform' ), (int) $counts->pending, add_query_arg( 'status', 'pending', $base ) ),
		array( __( 'دسته‌بندی‌ها', 'vidiform' ), (int) wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ), admin_url( 'admin.php?page=vf-blog-cats' ) ),
		array( __( 'برچسب‌ها', 'vidiform' ), (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ), admin_url( 'admin.php?page=vf-blog-tags' ) ),
	);
	$recent = get_posts( array(
		'post_type'   => 'post',
		'post_status' => array( 'publish', 'draft', 'future', 'pending', 'private' ),
		'numberposts' => 6,
		'orderby'     => 'modified',
	) );
	vf_admin_open( 'blog' );
	?>
	<div class="vf-a-page vf-a-page--1000" data-screen="blog-dashboard">
		<?php vf_admin_head( __( 'نمای کلی وبلاگ', 'vidiform' ), __( 'وضعیت نوشته‌ها و آخرین تغییرات وبلاگ.', 'vidiform' ), $new ); ?>
		<div class="vf-a-stats">
			<?php foreach ( $stats as $s ) : ?>
				<a class="vf-a-stat" href="<?php echo esc_url( $s[2] ); ?>">
					<span class="vf-a-stat__label"><?php echo esc_html( $s[0] ); ?></span>
					<span class="vf-a-stat__value"><?php echo esc_html( vf_num( $s[1] ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<section class="vf-a-card">
			<div class="vf-a-card__row">
				<h2 class="vf-a-card__title vf-grow"><?php esc_html_e( 'آخرین تغییرات', 'vidiform' ); ?></h2>
				<a class="vf-btn vf-btn--secondary vf-btn--sm" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'همه‌ی نوشته‌ها', 'vidiform' ); ?></a>
			</div>
			<?php if ( $recent ) : ?>
				<div class="vf-a-list">
					<?php
					foreach ( $recent as $p ) :
						$st = vf_blog_status( $p->post_status );
						?>
						<div class="vf-a-row">
							<span class="vf-a-row__title"><?php echo esc_html( get_the_title( $p ) ? get_the_title( $p ) : __( '(بدون عنوان)', 'vidiform' ) ); ?></span>
							<span class="vf-pill <?php echo esc_attr( $st[1] ); ?>"><?php echo esc_html( $st[0] ); ?></span>
							<span class="vf-a-meta"><?php echo esc_html( vf_format_date( (int) get_post_modified_time( 'U', false, $p ) ) ); ?></span>
							<span class="vf-a-row__actions">
								<?php if ( current_user_can( 'edit_post', $p->ID ) ) : ?>
									<a class="vf-btn vf-btn--secondary vf-btn--xs" href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php esc_html_e( 'ویرایش', 'vidiform' ); ?></a>
								<?php endif; ?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="vf-a-empty vf-a-empty--sm"><p class="vf-a-empty__title"><?php esc_html_e( 'هنوز نوشته‌ای ساخته نشده است', 'vidiform' ); ?></p></div>
			<?php endif; ?>
		</section>
	</div>
	<?php
	vf_admin_close();
}

/**
 * Posts list with search, filters, sorting, pagination, featured toggle and trash.
 */
function vf_blog_page_posts() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
	$s      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'all';
	$cat    = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
	$sort   = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'date_desc';
	$feat   = ! empty( $_GET['featured'] );
	$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$author = isset( $_GET['author'] ) ? absint( $_GET['author'] ) : 0;
	// phpcs:enable

	$statuses = array( 'publish', 'draft', 'future', 'pending', 'private' );
	$sorts    = array(
		'date_desc' => array( 'date', 'DESC', __( 'جدیدترین', 'vidiform' ) ),
		'date_asc'  => array( 'date', 'ASC', __( 'قدیمی‌ترین', 'vidiform' ) ),
		'modified'  => array( 'modified', 'DESC', __( 'آخرین ویرایش', 'vidiform' ) ),
		'title'     => array( 'title', 'ASC', __( 'عنوان', 'vidiform' ) ),
	);
	if ( ! isset( $sorts[ $sort ] ) ) {
		$sort = 'date_desc';
	}
	$args = array(
		'post_type'      => 'post',
		'post_status'    => in_array( $status, $statuses, true ) ? $status : $statuses,
		'posts_per_page' => 20,
		'paged'          => $paged,
		'orderby'        => $sorts[ $sort ][0],
		'order'          => $sorts[ $sort ][1],
		's'              => $s,
	);
	if ( $cat ) {
		$args['cat'] = $cat;
	}
	if ( $feat ) {
		$args['meta_key']   = '_vf_featured'; // phpcs:ignore WordPress.DB.SlowDBQuery
		$args['meta_value'] = '1'; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( $author ) {
		$args['author'] = $author;
	}
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		$args['author'] = get_current_user_id();
	}
	$q    = new WP_Query( $args );
	$base = admin_url( 'admin.php?page=vf-blog-posts' );
	$keep = array_filter( array( 's' => $s, 'status' => 'all' !== $status ? $status : '', 'cat' => $cat, 'sort' => 'date_desc' !== $sort ? $sort : '', 'featured' => $feat ? 1 : '', 'author' => $author ) );
	$new  = '<a class="vf-btn vf-btn--primary vf-btn--h44" href="' . esc_url( admin_url( 'post-new.php' ) ) . '">' . vf_icon( 'plus', 17, array( 'stroke-width' => '2.4' ) ) . '<span>' . esc_html__( 'نوشته‌ی جدید', 'vidiform' ) . '</span></a>';

	vf_admin_open( 'blog' );
	?>
	<div class="vf-a-page vf-a-page--1000" data-screen="blog-posts">
		<?php
		/* translators: %s: number of posts */
		vf_admin_head( __( 'نوشته‌ها', 'vidiform' ), sprintf( __( '%s نوشته · جست‌وجو، فیلتر، مرتب‌سازی، ویژه‌کردن و ویرایش', 'vidiform' ), vf_num( (int) $q->found_posts ) ), $new );
		?>
		<form class="vf-a-toolbar" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="vf-blog-posts">
			<div class="vf-a-search">
				<?php vf_the_icon( 'search', 17, array( 'stroke-width' => '2.2' ) ); ?>
				<label class="screen-reader-text" for="vf-post-search"><?php esc_html_e( 'جست‌وجوی نوشته', 'vidiform' ); ?></label>
				<input id="vf-post-search" class="vf-a-input" type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="<?php esc_attr_e( 'جست‌وجوی عنوان یا متن نوشته…', 'vidiform' ); ?>">
			</div>
			<label class="screen-reader-text" for="vf-post-cat"><?php esc_html_e( 'دسته‌بندی', 'vidiform' ); ?></label>
			<?php
			wp_dropdown_categories( array(
				'show_option_all' => __( 'همه‌ی دسته‌ها', 'vidiform' ),
				'hide_empty'      => false,
				'hierarchical'    => true,
				'name'            => 'cat',
				'id'              => 'vf-post-cat',
				'class'           => 'vf-a-select',
				'selected'        => $cat,
			) );
			?>
			<label class="screen-reader-text" for="vf-post-sort"><?php esc_html_e( 'مرتب‌سازی', 'vidiform' ); ?></label>
			<select id="vf-post-sort" class="vf-a-select" name="sort">
				<?php foreach ( $sorts as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $sort, $k ); ?>><?php echo esc_html( $v[2] ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
			<?php if ( $author ) : ?>
				<input type="hidden" name="author" value="<?php echo esc_attr( $author ); ?>">
			<?php endif; ?>
			<button type="submit" class="vf-btn vf-btn--secondary"><?php esc_html_e( 'اعمال', 'vidiform' ); ?></button>
		</form>
		<div class="vf-a-chips" style="margin-bottom:16px">
			<?php
			$chips = array( 'all' => __( 'همه', 'vidiform' ) );
			foreach ( $statuses as $st_key ) {
				$chips[ $st_key ] = vf_blog_status( $st_key )[0];
			}
			foreach ( $chips as $k => $label ) :
				$url = add_query_arg( array_merge( $keep, array( 'status' => 'all' === $k ? false : $k ) ), $base );
				?>
				<a class="vf-a-chip<?php echo $status === $k && ! $feat ? ' is-active' : ''; ?>" href="<?php echo esc_url( remove_query_arg( 'featured', $url ) ); ?>"<?php echo $status === $k && ! $feat ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<a class="vf-a-chip<?php echo $feat ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'featured' => 1 ), $base ) ); ?>"><?php esc_html_e( 'ویژه', 'vidiform' ); ?></a>
		</div>

		<?php if ( $q->have_posts() ) : ?>
			<table class="vf-a-table">
				<thead>
					<tr>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'تصویر', 'vidiform' ); ?></span></th>
						<th scope="col"><?php esc_html_e( 'نوشته', 'vidiform' ); ?></th>
						<th scope="col"><?php esc_html_e( 'وضعیت', 'vidiform' ); ?></th>
						<th scope="col"><?php esc_html_e( 'ویژه', 'vidiform' ); ?></th>
						<th scope="col"><?php esc_html_e( 'عملیات', 'vidiform' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $q->have_posts() ) :
						$q->the_post();
						$pid  = get_the_ID();
						$st   = vf_blog_status( get_post_status() );
						$feat_on = (bool) get_post_meta( $pid, '_vf_featured', true );
						$can  = current_user_can( 'edit_post', $pid );
						?>
						<tr data-post="<?php echo esc_attr( $pid ); ?>">
							<td style="width:86px">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( 'thumbnail', array( 'class' => 'vf-a-thumb', 'alt' => '' ) );
								} else {
									echo '<span class="vf-a-thumb" aria-hidden="true"></span>';
								}
								?>
							</td>
							<td>
								<a class="vf-a-post__title" href="<?php echo esc_url( $can ? get_edit_post_link( $pid ) : get_permalink() ); ?>"><?php echo esc_html( get_the_title() ? get_the_title() : __( '(بدون عنوان)', 'vidiform' ) ); ?></a>
								<div class="vf-a-post__meta">
									<span><?php echo esc_html( get_the_author() ); ?></span><span>·</span>
									<span><?php echo esc_html( vf_post_date() ); ?></span>
									<?php foreach ( get_the_category() as $c ) : ?>
										<span class="vf-pill"><?php echo esc_html( $c->name ); ?></span>
									<?php endforeach; ?>
								</div>
							</td>
							<td><span class="vf-pill <?php echo esc_attr( $st[1] ); ?>"><?php echo esc_html( $st[0] ); ?></span></td>
							<td>
								<button type="button" class="vf-a-star" data-feature-toggle="<?php echo esc_attr( $pid ); ?>" aria-pressed="<?php echo $feat_on ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'نوشته‌ی ویژه', 'vidiform' ); ?>"<?php disabled( ! $can ); ?>><?php vf_the_icon( 'star', 16 ); ?></button>
							</td>
							<td>
								<span class="vf-a-row__actions">
									<?php if ( $can ) : ?>
										<a class="vf-btn vf-btn--secondary vf-btn--xs" href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php esc_html_e( 'ویرایش', 'vidiform' ); ?></a>
									<?php endif; ?>
									<a class="vf-btn vf-btn--soft vf-btn--xs" href="<?php echo esc_url( 'publish' === get_post_status() ? get_permalink() : get_preview_post_link() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'نمای کاربر', 'vidiform' ); ?></a>
									<?php if ( current_user_can( 'delete_post', $pid ) ) : ?>
										<button type="button" class="vf-btn vf-btn--ghost-danger vf-btn--xs" data-trash="<?php echo esc_attr( $pid ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>"><?php esc_html_e( 'حذف', 'vidiform' ); ?></button>
									<?php endif; ?>
								</span>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			<?php
			wp_reset_postdata();
			$links = paginate_links( array(
				'base'      => add_query_arg( array_merge( $keep, array( 'paged' => '%#%' ) ), $base ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $q->max_num_pages,
				'prev_text' => __( 'قبلی', 'vidiform' ),
				'next_text' => __( 'بعدی', 'vidiform' ),
			) );
			if ( $links ) {
				echo '<nav class="vf-a-pagination" aria-label="' . esc_attr__( 'صفحه‌بندی', 'vidiform' ) . '">' . wp_kses_post( $links ) . '</nav>';
			}
			?>
		<?php else : ?>
			<div class="vf-a-empty">
				<p class="vf-a-empty__title"><?php esc_html_e( 'نوشته‌ای با این شرایط پیدا نشد', 'vidiform' ); ?></p>
				<p class="vf-a-empty__desc"><?php esc_html_e( 'فیلتر یا عبارت جست‌وجو را تغییر دهید.', 'vidiform' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
	<div class="vf-modal" id="vf-a-modal" role="dialog" aria-modal="true" aria-labelledby="vf-a-modal-title" hidden>
		<div class="vf-modal__box vf-modal__box--wide">
			<div class="vf-modal__head"><h2 class="vf-modal__title" id="vf-a-modal-title"></h2><button type="button" class="vf-modal__close" data-vf-close aria-label="<?php esc_attr_e( 'بستن', 'vidiform' ); ?>">×</button></div>
			<div data-modal-body></div>
		</div>
	</div>
	<?php
	vf_admin_close();
}

/**
 * Terms screen (categories or tags).
 *
 * @param string $taxonomy category|post_tag.
 */
function vf_blog_terms_screen( $taxonomy ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	$is_cat = 'category' === $taxonomy;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
	$s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'search'     => $s,
		'orderby'    => $is_cat ? 'name' : 'count',
		'order'      => $is_cat ? 'ASC' : 'DESC',
		'number'     => $is_cat ? 0 : 200,
	) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	if ( $is_cat && '' === $s ) {
		// Hierarchical order: parents followed by their children.
		$sorted = array();
		$walk   = function ( $parent, $depth ) use ( &$walk, &$sorted, $terms ) {
			foreach ( $terms as $t ) {
				if ( (int) $t->parent === $parent ) {
					$t->vf_depth = $depth;
					$sorted[]    = $t;
					$walk( (int) $t->term_id, $depth + 1 );
				}
			}
		};
		$walk( 0, 0 );
		$terms = $sorted;
	}
	$rest   = $is_cat ? 'categories' : 'tags';
	$title  = $is_cat ? __( 'دسته‌بندی‌های وبلاگ', 'vidiform' ) : __( 'برچسب‌های وبلاگ', 'vidiform' );
	$desc   = $is_cat ? __( 'دسته‌بندی‌ها ساختار اصلی وبلاگ هستند و در منو و آدرس‌ها استفاده می‌شوند.', 'vidiform' ) : __( 'برچسب‌ها موضوعات جزئی‌تر را به هم وصل می‌کنند.', 'vidiform' );
	$add    = '<button type="button" class="vf-btn vf-btn--primary vf-btn--h44" data-term-new>' . esc_html( $is_cat ? __( '+ دسته‌ی جدید', 'vidiform' ) : __( '+ برچسب جدید', 'vidiform' ) ) . '</button>';
	$parents = $is_cat ? array_map( function ( $t ) {
		return array( 'id' => (int) $t->term_id, 'name' => $t->name, 'parent' => (int) $t->parent );
	}, get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ) ) : array();

	vf_admin_open( 'blog' );
	?>
	<div class="vf-a-page vf-a-page--1000" data-screen="blog-terms" data-rest="<?php echo esc_attr( $rest ); ?>" data-hier="<?php echo $is_cat ? '1' : '0'; ?>">
		<?php vf_admin_head( $title, $desc, $add ); ?>
		<form class="vf-a-toolbar" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( $is_cat ? 'vf-blog-cats' : 'vf-blog-tags' ); ?>">
			<div class="vf-a-search">
				<?php vf_the_icon( 'search', 17, array( 'stroke-width' => '2.2' ) ); ?>
				<label class="screen-reader-text" for="vf-term-search"><?php esc_html_e( 'جست‌وجو', 'vidiform' ); ?></label>
				<input id="vf-term-search" class="vf-a-input" type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="<?php esc_attr_e( 'جست‌وجو…', 'vidiform' ); ?>">
			</div>
		</form>
		<?php if ( $terms ) : ?>
			<table class="vf-a-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'نام', 'vidiform' ); ?></th>
						<th scope="col"><?php esc_html_e( 'نامک', 'vidiform' ); ?></th>
						<th scope="col"><?php esc_html_e( 'نوشته‌ها', 'vidiform' ); ?></th>
						<th scope="col"><?php esc_html_e( 'عملیات', 'vidiform' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $terms as $t ) : ?>
						<tr>
							<td>
								<span class="vf-a-post__title" style="padding-inline-start:<?php echo esc_attr( 18 * (int) ( $t->vf_depth ?? 0 ) ); ?>px"><?php echo esc_html( ( ! empty( $t->vf_depth ) ? '— ' : '' ) . $t->name ); ?></span>
								<?php if ( $t->description ) : ?>
									<div class="vf-a-post__meta"><?php echo esc_html( wp_trim_words( $t->description, 16 ) ); ?></div>
								<?php endif; ?>
							</td>
							<td><code dir="ltr"><?php echo esc_html( urldecode( $t->slug ) ); ?></code></td>
							<td><?php echo esc_html( vf_num( (int) $t->count ) ); ?></td>
							<td>
								<span class="vf-a-row__actions">
									<button type="button" class="vf-btn vf-btn--secondary vf-btn--xs" data-term-edit="<?php echo esc_attr( wp_json_encode( array( 'id' => (int) $t->term_id, 'name' => $t->name, 'slug' => urldecode( $t->slug ), 'description' => $t->description, 'parent' => (int) $t->parent ) ) ); ?>"><?php esc_html_e( 'ویرایش', 'vidiform' ); ?></button>
									<a class="vf-btn vf-btn--soft vf-btn--xs" href="<?php echo esc_url( get_term_link( $t ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'نمای کاربر', 'vidiform' ); ?></a>
									<?php if ( ! $is_cat || (int) get_option( 'default_category' ) !== (int) $t->term_id ) : ?>
										<button type="button" class="vf-btn vf-btn--ghost-danger vf-btn--xs" data-term-del="<?php echo esc_attr( $t->term_id ); ?>" data-name="<?php echo esc_attr( $t->name ); ?>"><?php esc_html_e( 'حذف', 'vidiform' ); ?></button>
									<?php endif; ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="vf-a-empty">
				<p class="vf-a-empty__title"><?php echo esc_html( $s ? __( 'موردی پیدا نشد', 'vidiform' ) : __( 'هنوز موردی ساخته نشده است', 'vidiform' ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>
	<?php vf_admin_json( 'vf-blog-parents', $parents ); ?>
	<div class="vf-modal" id="vf-a-modal" role="dialog" aria-modal="true" aria-labelledby="vf-a-modal-title" hidden>
		<div class="vf-modal__box vf-modal__box--wide">
			<div class="vf-modal__head"><h2 class="vf-modal__title" id="vf-a-modal-title"></h2><button type="button" class="vf-modal__close" data-vf-close aria-label="<?php esc_attr_e( 'بستن', 'vidiform' ); ?>">×</button></div>
			<div data-modal-body></div>
		</div>
	</div>
	<?php
	vf_admin_close();
}

/**
 * Categories screen.
 */
function vf_blog_page_cats() {
	vf_blog_terms_screen( 'category' );
}

/**
 * Tags screen.
 */
function vf_blog_page_tags() {
	vf_blog_terms_screen( 'post_tag' );
}

/**
 * Authors screen.
 */
function vf_blog_page_authors() {
	if ( ! current_user_can( 'list_users' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	$users  = get_users( array(
		'capability' => array( 'edit_posts' ),
		'orderby'    => 'display_name',
		'number'     => 200,
	) );
	$counts = count_many_users_posts( wp_list_pluck( $users, 'ID' ), 'post', true );
	$add    = current_user_can( 'create_users' ) ? '<a class="vf-btn vf-btn--primary vf-btn--h44" href="' . esc_url( admin_url( 'user-new.php' ) ) . '">' . esc_html__( '+ نویسنده‌ی جدید', 'vidiform' ) . '</a>' : '';
	vf_admin_open( 'blog' );
	?>
	<div class="vf-a-page vf-a-page--1000" data-screen="blog-authors">
		<?php vf_admin_head( __( 'نویسندگان', 'vidiform' ), __( 'کاربرانی که می‌توانند در وبلاگ بنویسند. زندگی‌نامه و تصویر از پروفایل کاربر در صفحه‌ی نویسنده نمایش داده می‌شود.', 'vidiform' ), $add ); ?>
		<table class="vf-a-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'نویسنده', 'vidiform' ); ?></th>
					<th scope="col"><?php esc_html_e( 'نقش', 'vidiform' ); ?></th>
					<th scope="col"><?php esc_html_e( 'نوشته‌های منتشرشده', 'vidiform' ); ?></th>
					<th scope="col"><?php esc_html_e( 'عملیات', 'vidiform' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				global $wp_roles;
				foreach ( $users as $u ) :
					$role = $u->roles ? $u->roles[0] : '';
					?>
					<tr>
						<td>
							<span class="vf-a-author">
								<?php echo get_avatar( $u->ID, 40 ); ?>
								<span>
									<span class="vf-a-post__title"><?php echo esc_html( $u->display_name ); ?></span>
									<span class="vf-a-post__meta"><?php echo esc_html( wp_trim_words( get_the_author_meta( 'description', $u->ID ), 12 ) ); ?></span>
								</span>
							</span>
						</td>
						<td><span class="vf-pill"><?php echo esc_html( $role && isset( $wp_roles->role_names[ $role ] ) ? translate_user_role( $wp_roles->role_names[ $role ] ) : $role ); ?></span></td>
						<td><?php echo esc_html( vf_num( (int) ( $counts[ $u->ID ] ?? 0 ) ) ); ?></td>
						<td>
							<span class="vf-a-row__actions">
								<?php if ( current_user_can( 'edit_user', $u->ID ) ) : ?>
									<a class="vf-btn vf-btn--secondary vf-btn--xs" href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>"><?php esc_html_e( 'ویرایش پروفایل', 'vidiform' ); ?></a>
								<?php endif; ?>
								<a class="vf-btn vf-btn--soft vf-btn--xs" href="<?php echo esc_url( get_author_posts_url( $u->ID ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'صفحه‌ی نویسنده', 'vidiform' ); ?></a>
								<a class="vf-btn vf-btn--link vf-btn--xs" href="<?php echo esc_url( add_query_arg( array( 'page' => 'vf-blog-posts', 'author' => $u->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'نوشته‌ها', 'vidiform' ); ?></a>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
	vf_admin_close();
}

/**
 * Blog settings (Settings API).
 */
function vf_blog_page_settings() {
	if ( ! current_user_can( 'manage_categories' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	vf_admin_open( 'blog' );
	?>
	<div class="vf-a-page vf-a-page--960" data-screen="blog-settings">
		<?php vf_admin_head( __( 'تنظیمات وبلاگ', 'vidiform' ), __( 'این تنظیمات فقط روی وبلاگ اثر دارند.', 'vidiform' ) ); ?>
		<?php settings_errors(); ?>
		<form class="vf-a-card vf-a-card--stack" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php settings_fields( 'vf_blog_group' ); ?>
			<input type="hidden" name="vf_blog[_form]" value="settings">
			<p class="vf-a-card__hint">
				<?php esc_html_e( 'هیرو، مدیا، مقاله‌ی ویژه، فهرست مقالات و بخش دعوت به اقدام در', 'vidiform' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=vf-blog-landing' ) ); ?>"><?php esc_html_e( 'صفحه بلاگ', 'vidiform' ); ?></a>
				<?php esc_html_e( 'تنظیم می‌شوند.', 'vidiform' ); ?>
			</p>
			<h2 class="vf-a-card__title"><?php esc_html_e( 'هدر', 'vidiform' ); ?></h2>
			<div class="vf-a-grid3">
				<label class="vf-a-field"><?php esc_html_e( 'متن دکمه‌ی هدر', 'vidiform' ); ?>
					<input class="vf-a-input" type="text" name="vf_blog[header_cta_text]" value="<?php echo esc_attr( vf_opt( 'blog', 'header_cta_text' ) ); ?>">
				</label>
				<label class="vf-a-field"><?php esc_html_e( 'آدرس دکمه‌ی هدر', 'vidiform' ); ?>
					<input class="vf-a-input vf-ltr" type="text" name="vf_blog[header_cta_url]" value="<?php echo esc_attr( vf_opt( 'blog', 'header_cta_url' ) ); ?>">
				</label>
			</div>
			<p class="vf-a-card__hint">
				<?php
				printf(
					/* translators: %s: link to menus screen */
					esc_html__( 'لینک‌های منوی هدر و ستون «محصول» فوتر از %s (جایگاه‌های «وبلاگ — منوی اصلی» و «وبلاگ — منوی فوتر») خوانده می‌شوند.', 'vidiform' ),
					'<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'فهرست‌ها', 'vidiform' ) . '</a>'
				);
				?>
			</p>
			<div class="vf-a-grid3">
				<label class="vf-a-field"><?php esc_html_e( 'تعداد نوشته‌های مرتبط', 'vidiform' ); ?>
					<input class="vf-a-input" type="number" min="0" max="12" name="vf_blog[related_count]" value="<?php echo esc_attr( vf_opt( 'blog', 'related_count' ) ); ?>">
				</label>
			</div>
			<h2 class="vf-a-card__title"><?php esc_html_e( 'صفحه‌ی نوشته', 'vidiform' ); ?></h2>
			<label class="vf-a-check"><input type="checkbox" name="vf_blog[show_share]" value="1"<?php checked( vf_opt( 'blog', 'show_share' ) ); ?>> <?php esc_html_e( 'نمایش دکمه‌های اشتراک‌گذاری', 'vidiform' ); ?></label>
			<label class="vf-a-check"><input type="checkbox" name="vf_blog[show_author]" value="1"<?php checked( vf_opt( 'blog', 'show_author' ) ); ?>> <?php esc_html_e( 'نمایش کادر نویسنده', 'vidiform' ); ?></label>
			<h2 class="vf-a-card__title"><?php esc_html_e( 'فوتر', 'vidiform' ); ?></h2>
			<label class="vf-a-field"><?php esc_html_e( 'متن فوتر', 'vidiform' ); ?>
				<input class="vf-a-input" type="text" name="vf_blog[footer_text]" value="<?php echo esc_attr( vf_opt( 'blog', 'footer_text' ) ); ?>">
			</label>
			<label class="vf-a-field"><?php esc_html_e( 'آدرس فرم خبرنامه (اختیاری)', 'vidiform' ); ?>
				<input class="vf-a-input vf-ltr" type="url" name="vf_blog[newsletter_url]" value="<?php echo esc_attr( vf_opt( 'blog', 'newsletter_url' ) ); ?>" placeholder="https://…">
				<span class="vf-a-tiny"><?php esc_html_e( 'ستون «خبرنامه محتوا» فقط وقتی نمایش داده می‌شود که آدرس سرویس خبرنامه (مقصد ارسال فرم با فیلد email) وارد شده باشد.', 'vidiform' ); ?></span>
			</label>
			<div><button type="submit" class="vf-btn vf-btn--primary"><?php esc_html_e( 'ذخیره', 'vidiform' ); ?></button></div>
		</form>
		<section class="vf-a-card vf-a-card--stack">
			<h2 class="vf-a-card__title"><?php esc_html_e( 'آدرس‌ها', 'vidiform' ); ?></h2>
			<ul class="vf-a-routes" dir="ltr">
				<li><code><?php echo esc_html( wp_make_link_relative( vf_blog_home_url() ) ); ?></code></li>
				<li><code><?php echo esc_html( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : '?p={id}' ); ?></code></li>
				<li><code><?php echo esc_html( wp_make_link_relative( home_url( '/' ) ) ); ?>?s=…</code></li>
			</ul>
			<p class="vf-a-card__hint"><?php esc_html_e( 'دسته، برچسب و نویسنده زیر همان پیشوند نوشته‌ها ساخته می‌شوند (مثلاً /blog/category/…).', 'vidiform' ); ?> <a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'تنظیمات پیوندهای یکتا', 'vidiform' ); ?></a></p>
		</section>
	</div>
	<?php
	vf_admin_close();
}

/**
 * Blog landing (/blog/) configuration — «صفحه بلاگ».
 * Saved through the Settings API into the vf_blog option (nonce + manage_categories).
 */
function vf_blog_page_landing() {
	if ( ! current_user_can( 'manage_categories' ) ) {
		wp_die( esc_html__( 'اجازه‌ی دسترسی ندارید.', 'vidiform' ) );
	}
	$o = function ( $k ) {
		return vf_opt( 'blog', $k );
	};
	$posts = get_posts( array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'numberposts'   => 100,
		'no_found_rows' => true,
	) );
	$cats       = get_categories( array( 'hide_empty' => false ) );
	$sel_cats   = array_map( 'absint', (array) $o( 'latest_cats' ) );
	$media_type = $o( 'media_type' );
	$thumb      = function ( $id ) {
		if ( ! $id ) {
			return '';
		}
		$mime = (string) get_post_mime_type( $id );
		if ( 0 === strpos( $mime, 'image/' ) ) {
			return '<img src="' . esc_url( (string) wp_get_attachment_image_url( $id, 'large' ) ) . '" alt="">';
		}
		if ( 0 === strpos( $mime, 'video/' ) ) {
			return '<video src="' . esc_url( (string) wp_get_attachment_url( $id ) ) . '" muted playsinline></video>';
		}
		return '<span>' . esc_html( basename( (string) get_attached_file( $id ) ) ) . '</span>';
	};
	vf_admin_open( 'blog' );
	?>
	<form class="vf-a-page vf-a-page--wide" data-screen="blog-landing" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
		<?php settings_fields( 'vf_blog_group' ); ?>
		<input type="hidden" name="vf_blog[_form]" value="landing">

		<div class="vf-a-head vf-a-head--bar">
			<div class="vf-a-head__text">
				<h1 class="vf-a-title"><?php esc_html_e( 'تنظیمات صفحه بلاگ', 'vidiform' ); ?> <span class="vf-a-title__sub"><?php esc_html_e( 'هیرو، مدیا و چیدمان فهرست', 'vidiform' ); ?></span></h1>
			</div>
			<div class="vf-a-btnrow">
				<label class="vf-a-inline-field"><?php esc_html_e( 'حالت پیش‌فرض', 'vidiform' ); ?>
					<select class="vf-a-select vf-a-select--sm" name="vf_blog[default_theme]">
						<option value="dark"<?php selected( $o( 'default_theme' ), 'dark' ); ?>><?php esc_html_e( 'تیره', 'vidiform' ); ?></option>
						<option value="light"<?php selected( $o( 'default_theme' ), 'light' ); ?>><?php esc_html_e( 'روشن', 'vidiform' ); ?></option>
						<option value="system"<?php selected( $o( 'default_theme' ), 'system' ); ?>><?php esc_html_e( 'مطابق سیستم', 'vidiform' ); ?></option>
					</select>
				</label>
				<a class="vf-btn vf-btn--outline" href="<?php echo esc_url( vf_blog_home_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'مشاهده صفحه', 'vidiform' ); ?></a>
				<button type="submit" class="vf-btn vf-btn--primary"><?php esc_html_e( 'ذخیره', 'vidiform' ); ?></button>
			</div>
		</div>
		<?php settings_errors(); ?>

		<div class="vf-a-cols">
			<div class="vf-a-col">
				<section class="vf-a-card">
					<h2 class="vf-a-card__title"><?php esc_html_e( 'هیرو صفحه بلاگ', 'vidiform' ); ?></h2>
					<div class="vf-a-stack">
						<label class="vf-a-field"><?php esc_html_e( 'برچسب بالای عنوان (Eyebrow)', 'vidiform' ); ?>
							<input class="vf-a-input" type="text" name="vf_blog[eyebrow]" value="<?php echo esc_attr( $o( 'eyebrow' ) ); ?>">
						</label>
						<label class="vf-a-field"><?php esc_html_e( 'عنوان اصلی', 'vidiform' ); ?>
							<input class="vf-a-input vf-a-input--lg" type="text" name="vf_blog[hero_title]" value="<?php echo esc_attr( $o( 'hero_title' ) ); ?>" required>
						</label>
						<label class="vf-a-field"><?php esc_html_e( 'توضیح', 'vidiform' ); ?>
							<textarea class="vf-a-textarea" rows="3" name="vf_blog[hero_desc]"><?php echo esc_textarea( $o( 'hero_desc' ) ); ?></textarea>
						</label>
						<div class="vf-a-grid2">
							<label class="vf-a-field"><?php esc_html_e( 'دکمه اصلی', 'vidiform' ); ?>
								<input class="vf-a-input" type="text" name="vf_blog[btn1_text]" value="<?php echo esc_attr( $o( 'btn1_text' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'آدرس دکمه اصلی', 'vidiform' ); ?>
								<input class="vf-a-input vf-ltr" type="text" name="vf_blog[btn1_url]" value="<?php echo esc_attr( $o( 'btn1_url' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'دکمه دوم', 'vidiform' ); ?>
								<input class="vf-a-input" type="text" name="vf_blog[btn2_text]" value="<?php echo esc_attr( $o( 'btn2_text' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'آدرس دکمه دوم', 'vidiform' ); ?>
								<input class="vf-a-input vf-ltr" type="text" name="vf_blog[btn2_url]" value="<?php echo esc_attr( $o( 'btn2_url' ) ); ?>">
							</label>
						</div>
						<p class="vf-a-tiny"><?php esc_html_e( 'برای رفتن به فهرست مقالات همین صفحه از #latest استفاده کنید. خالی گذاشتن متن یک دکمه، آن را پنهان می‌کند.', 'vidiform' ); ?></p>
					</div>
				</section>

				<section class="vf-a-card">
					<h2 class="vf-a-card__title"><?php esc_html_e( 'فهرست آخرین مقالات', 'vidiform' ); ?></h2>
					<div class="vf-a-stack">
						<label class="vf-a-field"><?php esc_html_e( 'عنوان بخش', 'vidiform' ); ?>
							<input class="vf-a-input" type="text" name="vf_blog[latest_title]" value="<?php echo esc_attr( $o( 'latest_title' ) ); ?>">
						</label>
						<div class="vf-a-grid2">
							<label class="vf-a-field"><?php esc_html_e( 'تعداد در هر صفحه', 'vidiform' ); ?>
								<input class="vf-a-input" type="number" min="1" max="48" name="vf_blog[per_page]" value="<?php echo esc_attr( $o( 'per_page' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'ترتیب پیش‌فرض', 'vidiform' ); ?>
								<select class="vf-a-select" name="vf_blog[order]">
									<?php foreach ( vf_blog_order_options() as $k => $v ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $o( 'order' ), $k ); ?>><?php echo esc_html( $v[2] ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>
						<label class="vf-a-toggle">
							<input type="checkbox" name="vf_blog[show_featured]" value="1"<?php checked( $o( 'show_featured' ) ); ?>>
							<span class="vf-a-toggle__ui" aria-hidden="true"></span>
							<span><?php esc_html_e( 'نمایش مقاله ویژه در بالای فهرست', 'vidiform' ); ?></span>
						</label>
						<label class="vf-a-field"><?php esc_html_e( 'مقاله ویژه', 'vidiform' ); ?>
							<select class="vf-a-select" name="vf_blog[featured_post]">
								<option value="0"><?php esc_html_e( 'خودکار — آخرین نوشته‌ی ستاره‌دار، وگرنه جدیدترین نوشته', 'vidiform' ); ?></option>
								<?php foreach ( $posts as $p ) : ?>
									<option value="<?php echo esc_attr( $p->ID ); ?>"<?php selected( (int) $o( 'featured_post' ), (int) $p->ID ); ?>><?php echo esc_html( get_the_title( $p ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<fieldset class="vf-a-field">
							<legend><?php esc_html_e( 'فقط این دسته‌ها در فهرست (اختیاری)', 'vidiform' ); ?></legend>
							<div class="vf-a-checks">
								<?php foreach ( $cats as $c ) : ?>
									<label class="vf-a-checkchip"><input type="checkbox" name="vf_blog[latest_cats][]" value="<?php echo esc_attr( $c->term_id ); ?>"<?php checked( in_array( (int) $c->term_id, $sel_cats, true ) ); ?>><span><?php echo esc_html( $c->name ); ?></span></label>
								<?php endforeach; ?>
							</div>
						</fieldset>
						<p class="vf-a-tiny"><?php esc_html_e( 'ناوبری دسته‌بندی‌ها و فهرست مقالات به‌صورت پویا از دسته‌ها و نوشته‌های منتشرشده خوانده می‌شوند؛ این‌جا فقط چیدمان تنظیم می‌شود.', 'vidiform' ); ?></p>
					</div>
				</section>

				<section class="vf-a-card">
					<h2 class="vf-a-card__title"><?php esc_html_e( 'بخش دعوت به اقدام (CTA)', 'vidiform' ); ?></h2>
					<div class="vf-a-stack">
						<label class="vf-a-field"><?php esc_html_e( 'عنوان', 'vidiform' ); ?>
							<input class="vf-a-input" type="text" name="vf_blog[cta_title]" value="<?php echo esc_attr( $o( 'cta_title' ) ); ?>">
						</label>
						<label class="vf-a-field"><?php esc_html_e( 'توضیح', 'vidiform' ); ?>
							<textarea class="vf-a-textarea" rows="2" name="vf_blog[cta_desc]"><?php echo esc_textarea( $o( 'cta_desc' ) ); ?></textarea>
						</label>
						<div class="vf-a-grid2">
							<label class="vf-a-field"><?php esc_html_e( 'متن دکمه', 'vidiform' ); ?>
								<input class="vf-a-input" type="text" name="vf_blog[cta_btn1_text]" value="<?php echo esc_attr( $o( 'cta_btn1_text' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'آدرس دکمه', 'vidiform' ); ?>
								<input class="vf-a-input vf-ltr" type="text" name="vf_blog[cta_btn1_url]" value="<?php echo esc_attr( $o( 'cta_btn1_url' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'دکمه دوم (اختیاری)', 'vidiform' ); ?>
								<input class="vf-a-input" type="text" name="vf_blog[cta_btn2_text]" value="<?php echo esc_attr( $o( 'cta_btn2_text' ) ); ?>">
							</label>
							<label class="vf-a-field"><?php esc_html_e( 'آدرس دکمه دوم', 'vidiform' ); ?>
								<input class="vf-a-input vf-ltr" type="text" name="vf_blog[cta_btn2_url]" value="<?php echo esc_attr( $o( 'cta_btn2_url' ) ); ?>">
							</label>
						</div>
						<p class="vf-a-tiny"><?php esc_html_e( 'اگر عنوان خالی باشد، این بخش نمایش داده نمی‌شود.', 'vidiform' ); ?></p>
					</div>
				</section>
			</div>

			<div class="vf-a-col">
				<section class="vf-a-card" data-media-card>
					<h2 class="vf-a-card__title"><?php esc_html_e( 'مدیای هیرو', 'vidiform' ); ?></h2>
					<div class="vf-a-tabs" role="radiogroup" aria-label="<?php esc_attr_e( 'نوع مدیا', 'vidiform' ); ?>">
						<?php
						foreach ( array( 'image' => __( 'تصویر', 'vidiform' ), 'video' => __( 'ویدیو', 'vidiform' ), 'vidiform' => __( 'فرم ویدی‌فرم', 'vidiform' ) ) as $k => $label ) :
							?>
							<label class="vf-a-tab"><input type="radio" name="vf_blog[media_type]" value="<?php echo esc_attr( $k ); ?>"<?php checked( $media_type, $k ); ?>><span><?php echo esc_html( $label ); ?></span></label>
						<?php endforeach; ?>
					</div>
					<p class="vf-a-tiny" data-type-note></p>

					<?php
					$slots = array(
						'desktop' => array( __( 'مدیای دسکتاپ (موکاپ لپ‌تاپ)', 'vidiform' ), '۱۶۰۰×۱۰۰۰', 'vf-a-drop--desktop' ),
						'mobile'  => array( __( 'مدیای موبایل (موکاپ گوشی)', 'vidiform' ), '۹:۱۹', 'vf-a-drop--mobile' ),
					);
					foreach ( $slots as $slot => $meta ) :
						$att = (int) $o( 'media_' . $slot );
						?>
						<div class="vf-a-field vf-a-media-slot" data-slot="<?php echo esc_attr( $slot ); ?>">
							<span><?php echo esc_html( $meta[0] ); ?></span>
							<input type="hidden" name="vf_blog[media_<?php echo esc_attr( $slot ); ?>]" value="<?php echo esc_attr( $att ); ?>" data-media-id>
							<button type="button" class="vf-a-drop <?php echo esc_attr( $meta[2] ); ?><?php echo $att ? ' has-media' : ''; ?>" data-media-pick aria-label="<?php echo esc_attr( $meta[0] ); ?>">
								<span class="vf-a-drop__preview" data-media-preview><?php echo $thumb( $att ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in closure. ?></span>
								<span class="vf-a-drop__empty">
									<?php vf_the_icon( 'image', 30, array( 'stroke-width' => '1.5' ) ); ?>
									<span class="vf-a-drop__size"><?php echo esc_html( $meta[1] ); ?></span>
									<span><?php esc_html_e( 'انتخاب از کتابخانه‌ی رسانه یا', 'vidiform' ); ?> <u><?php esc_html_e( 'آپلود فایل', 'vidiform' ); ?></u></span>
								</span>
							</button>
							<button type="button" class="vf-btn vf-btn--link vf-btn--xs" data-media-clear<?php echo $att ? '' : ' hidden'; ?>><?php esc_html_e( 'حذف فایل', 'vidiform' ); ?></button>
							<input class="vf-a-input vf-ltr" type="text" name="vf_blog[media_<?php echo esc_attr( $slot ); ?>_url]" value="<?php echo esc_attr( $o( 'media_' . $slot . '_url' ) ); ?>" placeholder="<?php esc_attr_e( 'یا آدرس ویدیو / لینک ویدی‌فرم', 'vidiform' ); ?>" aria-label="<?php esc_attr_e( 'یا آدرس ویدیو / لینک ویدی‌فرم', 'vidiform' ); ?>">
						</div>
					<?php endforeach; ?>

					<div class="vf-a-infobox">
						<strong><?php esc_html_e( 'اندازه پیشنهادی:', 'vidiform' ); ?></strong>
						<?php esc_html_e( 'دسکتاپ ۱۶۰۰×۱۰۰۰ (۱۶:۱۰) · موبایل ۹۰۰×۱۹۰۰ (۹:۱۹)', 'vidiform' ); ?><br>
						<?php esc_html_e( 'ویدیو: MP4/WebM بی‌صدا که به‌صورت خودکار و تکراری پخش می‌شود. تصویرها با نسخه‌های ریسپانسیو (srcset) وردپرس سرو می‌شوند.', 'vidiform' ); ?>
					</div>
				</section>
			</div>
		</div>

		<div class="vf-a-savebar"><button type="submit" class="vf-btn vf-btn--primary vf-btn--h44"><?php esc_html_e( 'ذخیره‌ی تنظیمات صفحه بلاگ', 'vidiform' ); ?></button></div>
	</form>
	<?php
	vf_admin_close();
}

/**
 * Media Library on the landing settings screen.
 */
function vf_blog_landing_media() {
	if ( 'vf-blog-landing' === vf_admin_current_page() ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'vf_blog_landing_media' );
