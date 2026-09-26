<?php
/**
 * Blog post settings box in the block editor: featured flag, related posts,
 * reading time and SEO fields. Saved with nonce + capability checks.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the meta box for posts.
 */
function vf_blog_add_metabox() {
	add_meta_box( 'vf-post-settings', __( 'ویدی‌فرم — تنظیمات نوشته', 'vidiform' ), 'vf_blog_render_metabox', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes_post', 'vf_blog_add_metabox' );

/**
 * Render the box.
 *
 * @param WP_Post $post Post.
 */
function vf_blog_render_metabox( $post ) {
	wp_nonce_field( 'vf_post_settings', 'vf_post_settings_nonce' );
	$featured = (bool) get_post_meta( $post->ID, '_vf_featured', true );
	$related  = array_map( 'absint', (array) get_post_meta( $post->ID, '_vf_related', true ) );
	$minutes  = (int) get_post_meta( $post->ID, '_vf_read_minutes', true );
	$candidates = get_posts( array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'numberposts'   => 100,
		'exclude'       => array( $post->ID ),
		'no_found_rows' => true,
	) );
	?>
	<p>
		<label><input type="checkbox" name="vf_featured" value="1"<?php checked( $featured ); ?>> <?php esc_html_e( 'نوشته‌ی ویژه (نمایش در صفحه‌ی اصلی وبلاگ)', 'vidiform' ); ?></label>
	</p>
	<p>
		<label for="vf-related"><strong><?php esc_html_e( 'نوشته‌های مرتبط (اختیاری)', 'vidiform' ); ?></strong></label><br>
		<select id="vf-related" name="vf_related[]" multiple size="6" style="width:100%">
			<?php foreach ( $candidates as $c ) : ?>
				<option value="<?php echo esc_attr( $c->ID ); ?>"<?php selected( in_array( (int) $c->ID, $related, true ) ); ?>><?php echo esc_html( get_the_title( $c ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e( 'اگر انتخاب نشود، نوشته‌های هم‌دسته و هم‌برچسب نمایش داده می‌شوند.', 'vidiform' ); ?></span>
	</p>
	<p>
		<label for="vf-read"><strong><?php esc_html_e( 'زمان مطالعه (دقیقه)', 'vidiform' ); ?></strong></label><br>
		<input id="vf-read" type="number" min="0" max="240" name="vf_read_minutes" value="<?php echo esc_attr( $minutes ); ?>" style="width:100%">
		<span class="description"><?php esc_html_e( '۰ = محاسبه‌ی خودکار', 'vidiform' ); ?></span>
	</p>
	<p>
		<label for="vf-seo-title"><strong><?php esc_html_e( 'عنوان سئو', 'vidiform' ); ?></strong></label><br>
		<input id="vf-seo-title" type="text" name="vf_seo_title" maxlength="120" value="<?php echo esc_attr( get_post_meta( $post->ID, '_vf_seo_title', true ) ); ?>" style="width:100%">
	</p>
	<p>
		<label for="vf-seo-desc"><strong><?php esc_html_e( 'توضیحات متا', 'vidiform' ); ?></strong></label><br>
		<textarea id="vf-seo-desc" name="vf_seo_desc" rows="3" maxlength="320" style="width:100%"><?php echo esc_textarea( get_post_meta( $post->ID, '_vf_seo_desc', true ) ); ?></textarea>
		<?php if ( vf_seo_plugin_active() ) : ?>
			<span class="description"><?php esc_html_e( 'افزونه‌ی سئو فعال است؛ عنوان و توضیحات آن افزونه اولویت دارد.', 'vidiform' ); ?></span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Save the box.
 *
 * @param int $post_id Post id.
 */
function vf_blog_save_metabox( $post_id ) {
	if ( ! isset( $_POST['vf_post_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vf_post_settings_nonce'] ) ), 'vf_post_settings' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_vf_featured', ! empty( $_POST['vf_featured'] ) );
	$related = isset( $_POST['vf_related'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['vf_related'] ) ) : array();
	update_post_meta( $post_id, '_vf_related', array_slice( array_values( array_unique( array_filter( $related ) ) ), 0, 12 ) );
	update_post_meta( $post_id, '_vf_read_minutes', isset( $_POST['vf_read_minutes'] ) ? min( 240, absint( $_POST['vf_read_minutes'] ) ) : 0 );
	update_post_meta( $post_id, '_vf_seo_title', isset( $_POST['vf_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['vf_seo_title'] ) ) : '' );
	update_post_meta( $post_id, '_vf_seo_desc', isset( $_POST['vf_seo_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['vf_seo_desc'] ) ) : '' );
}
add_action( 'save_post_post', 'vf_blog_save_metabox' );
