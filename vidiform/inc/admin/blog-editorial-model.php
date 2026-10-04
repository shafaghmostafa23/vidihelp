<?php
/**
 * Editorial metadata for the Blog Content Studio.
 *
 * Editorial labels stay private to WordPress administration and do not change
 * the public blog URLs, SEO output, or the separate Help Center content model.
 *
 * @package VidiForm
 */
defined( 'ABSPATH' ) || exit;

function vf_blog_editorial_taxonomies() {
	$taxonomies = array(
		'vf_content_type' => __( 'نوع محتوا', 'vidiform' ),
		'vf_journey_stage' => __( 'مرحلهٔ سفر مخاطب', 'vidiform' ),
		'vf_seo_phase'     => __( 'فاز SEO', 'vidiform' ),
		'vf_topic_cluster' => __( 'خوشهٔ موضوعی', 'vidiform' ),
		'vf_editorial_status' => __( 'وضعیت تحریریه', 'vidiform' ),
	);
	foreach ( $taxonomies as $taxonomy => $label ) {
		register_taxonomy( $taxonomy, array( 'post' ), array(
			'labels'            => array( 'name' => $label, 'singular_name' => $label ),
			'public'            => false,
			'publicly_queryable' => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => false,
			'rewrite'           => false,
			'query_var'         => false,
			'show_in_nav_menus' => false,
		) );
	}
}
add_action( 'init', 'vf_blog_editorial_taxonomies', 20 );

function vf_blog_editorial_seed_terms() {
	if ( get_option( 'vf_blog_editorial_terms_seeded' ) ) {
		return;
	}
	$defaults = array(
		'vf_content_type' => array( 'آموزش', 'راهنمای کاربرد', 'مقایسه', 'کیس‌استادی', 'پاسخ به سؤال', 'صفحهٔ مرجع' ),
		'vf_journey_stage' => array( 'آگاهی', 'بررسی مسئله و راه‌حل', 'ارزیابی', 'شروع استفاده', 'نگهداشت', 'معرفی به دیگران' ),
		'vf_seo_phase'     => array( 'فاز ۱' ),
		'vf_editorial_status' => array( 'ایده', 'نیازمند تحقیق', 'بریف', 'پیش‌نویس', 'بازبینی', 'آمادهٔ انتشار', 'نیازمند به‌روزرسانی', 'متوقف‌شده' ),
	);
	foreach ( $defaults as $taxonomy => $labels ) {
		foreach ( $labels as $label ) {
			if ( ! term_exists( $label, $taxonomy ) ) {
				wp_insert_term( $label, $taxonomy, array( 'description' => '' ) );
			}
		}
	}
	update_option( 'vf_blog_editorial_terms_seeded', 1, false );
}
add_action( 'admin_init', 'vf_blog_editorial_seed_terms' );

function vf_blog_seo_phase_add_fields() {
	echo '<div class="form-field"><label for="vf_phase_order">' . esc_html__( 'ترتیب فاز', 'vidiform' ) . '</label><input id="vf_phase_order" name="vf_phase_order" type="number" min="0" value="0"></div>';
	echo '<div class="form-field"><label for="vf_phase_done">' . esc_html__( 'معیار تکمیل', 'vidiform' ) . '</label><textarea id="vf_phase_done" name="vf_phase_done" rows="3"></textarea></div>';
}
add_action( 'vf_seo_phase_add_form_fields', 'vf_blog_seo_phase_add_fields' );

function vf_blog_seo_phase_edit_fields( $term ) {
	echo '<tr class="form-field"><th><label for="vf_phase_order">' . esc_html__( 'ترتیب فاز', 'vidiform' ) . '</label></th><td><input id="vf_phase_order" name="vf_phase_order" type="number" min="0" value="' . esc_attr( get_term_meta( $term->term_id, 'vf_phase_order', true ) ) . '"></td></tr>';
	echo '<tr class="form-field"><th><label for="vf_phase_done">' . esc_html__( 'معیار تکمیل', 'vidiform' ) . '</label></th><td><textarea id="vf_phase_done" name="vf_phase_done" rows="3">' . esc_textarea( get_term_meta( $term->term_id, 'vf_phase_done', true ) ) . '</textarea></td></tr>';
}
add_action( 'vf_seo_phase_edit_form_fields', 'vf_blog_seo_phase_edit_fields' );

function vf_blog_seo_phase_save_fields( $term_id ) {
	if ( isset( $_POST['vf_phase_order'] ) ) {
		update_term_meta( $term_id, 'vf_phase_order', absint( wp_unslash( $_POST['vf_phase_order'] ) ) );
	}
	if ( isset( $_POST['vf_phase_done'] ) ) {
		update_term_meta( $term_id, 'vf_phase_done', sanitize_textarea_field( wp_unslash( $_POST['vf_phase_done'] ) ) );
	}
}
add_action( 'created_vf_seo_phase', 'vf_blog_seo_phase_save_fields' );
add_action( 'edited_vf_seo_phase', 'vf_blog_seo_phase_save_fields' );

function vf_blog_register_persona() {
	register_post_type( 'vf_persona', array(
		'labels' => array(
			'name'          => __( 'پرسوناها', 'vidiform' ),
			'singular_name' => __( 'پرسونا', 'vidiform' ),
			'add_new_item'  => __( 'افزودن پرسونا', 'vidiform' ),
			'edit_item'     => __( 'ویرایش پرسونا', 'vidiform' ),
		),
		'public'              => false,
		'publicly_queryable'   => false,
		'exclude_from_search'  => true,
		'show_ui'              => true,
		'show_in_menu'         => 'vf-blog-content',
		'show_in_rest'         => false,
		'supports'             => array( 'title', 'editor' ),
		'capability_type'      => 'post',
		'map_meta_cap'         => true,
		'has_archive'          => false,
		'rewrite'              => false,
		'query_var'            => false,
	) );
}
add_action( 'init', 'vf_blog_register_persona', 21 );

function vf_blog_persona_meta_box() {
	add_meta_box( 'vf-persona-evidence', __( 'پروفایل و شواهد پرسونا', 'vidiform' ), 'vf_blog_persona_meta_box_render', 'vf_persona', 'normal', 'high' );
}
add_action( 'add_meta_boxes_vf_persona', 'vf_blog_persona_meta_box' );

function vf_blog_persona_meta_box_render( $post ) {
	wp_nonce_field( 'vf_persona_save', 'vf_persona_nonce' );
	$fields = array(
		'role' => __( 'نقش شغلی و نوع/اندازهٔ کسب‌وکار', 'vidiform' ),
		'goal' => __( 'هدف و محرک جست‌وجو', 'vidiform' ),
		'problem' => __( 'مشکل اصلی و راه‌حل فعلی', 'vidiform' ),
		'outcome' => __( 'نتیجهٔ مورد انتظار', 'vidiform' ),
		'concerns' => __( 'نگرانی‌ها، موانع و سؤال‌های پرتکرار', 'vidiform' ),
		'voice' => __( 'واژه‌ها و لحن رایج مخاطب', 'vidiform' ),
		'use_cases' => __( 'موارد استفادهٔ محتمل VidiForm', 'vidiform' ),
		'evidence' => __( 'شواهد و منبع (مصاحبه، بازخورد، داده یا فرضیه)', 'vidiform' ),
	);
	echo '<div class="vf-a-stack">';
	foreach ( $fields as $key => $label ) {
		echo '<label class="vf-a-field">' . esc_html( $label ) . '<textarea class="vf-a-textarea" rows="2" name="vf_persona[' . esc_attr( $key ) . ']">' . esc_textarea( get_post_meta( $post->ID, '_vf_persona_' . $key, true ) ) . '</textarea></label>';
	}
	$status = get_post_meta( $post->ID, '_vf_persona_evidence_status', true ) ?: 'hypothesis';
	echo '<label class="vf-a-field">' . esc_html__( 'وضعیت اعتبار', 'vidiform' ) . '<select class="vf-a-select" name="vf_persona_status">';
	foreach ( array( 'hypothesis' => __( 'فرضیه', 'vidiform' ), 'reviewing' => __( 'در حال بررسی', 'vidiform' ), 'verified' => __( 'تأییدشده', 'vidiform' ), 'retired' => __( 'بازنشسته', 'vidiform' ) ) as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></label></div>';
}

function vf_blog_persona_save( $post_id ) {
	if ( ! isset( $_POST['vf_persona_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vf_persona_nonce'] ) ), 'vf_persona_save' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = array( 'role', 'goal', 'problem', 'outcome', 'concerns', 'voice', 'use_cases', 'evidence' );
	$values = isset( $_POST['vf_persona'] ) && is_array( $_POST['vf_persona'] ) ? wp_unslash( $_POST['vf_persona'] ) : array();
	foreach ( $fields as $key ) {
		update_post_meta( $post_id, '_vf_persona_' . $key, sanitize_textarea_field( $values[ $key ] ?? '' ) );
	}
	$allowed = array( 'hypothesis', 'reviewing', 'verified', 'retired' );
	$status  = sanitize_key( wp_unslash( $_POST['vf_persona_status'] ?? 'hypothesis' ) );
	update_post_meta( $post_id, '_vf_persona_evidence_status', in_array( $status, $allowed, true ) ? $status : 'hypothesis' );
}
add_action( 'save_post_vf_persona', 'vf_blog_persona_save' );

function vf_blog_editorial_meta_box() {
	add_meta_box( 'vf-editorial-brief', __( 'استودیو محتوا — بریف و طبقه‌بندی', 'vidiform' ), 'vf_blog_editorial_meta_box_render', 'post', 'normal', 'high' );
}
add_action( 'add_meta_boxes_post', 'vf_blog_editorial_meta_box', 20 );

function vf_blog_editorial_meta_box_render( $post ) {
	wp_nonce_field( 'vf_editorial_save', 'vf_editorial_nonce' );
	$personas = get_posts( array( 'post_type' => 'vf_persona', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$selected = array_map( 'absint', (array) get_post_meta( $post->ID, '_vf_personas', true ) );
	$fields   = array(
		'problem' => __( 'مسئله و سؤال اصلی خواننده', 'vidiform' ),
		'angle' => __( 'زاویه و ارزش افزودهٔ مقاله', 'vidiform' ),
		'facts' => __( 'منابع و حقایق تأییدشدهٔ محصول', 'vidiform' ),
		'claims' => __( 'ادعاهای نیازمند بررسی و سؤال‌های باز', 'vidiform' ),
		'headings' => __( 'تیترهای پیشنهادی و لینک‌های داخلی', 'vidiform' ),
		'cta' => __( 'CTA متناسب با مرحلهٔ سفر', 'vidiform' ),
		'prompt' => __( 'دستور لحن و prompt اختصاصی این مقاله', 'vidiform' ),
	);
	echo '<div class="vf-a-stack"><div class="vf-a-grid vf-a-grid--2">';
	echo '<label class="vf-a-field">' . esc_html__( 'پرسوناهای هدف', 'vidiform' ) . '<select class="vf-a-select" name="vf_editorial[personas][]" multiple size="4">';
	foreach ( $personas as $persona ) {
		$status = get_post_meta( $persona->ID, '_vf_persona_evidence_status', true ) ?: 'hypothesis';
		$label  = 'verified' === $status ? $persona->post_title : $persona->post_title . ' — ' . __( 'فرضیه یا نیازمند بررسی', 'vidiform' );
		echo '<option value="' . (int) $persona->ID . '" ' . selected( in_array( (int) $persona->ID, $selected, true ), true, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select><span class="vf-a-note">' . esc_html__( 'چند گزینه را با Ctrl یا Command انتخاب کنید. فرضیه‌ها به‌عنوان تأییدشده به AI معرفی نمی‌شوند.', 'vidiform' ) . '</span></label>';
	echo '<label class="vf-a-field">' . esc_html__( 'پرسونای اصلی', 'vidiform' ) . '<select class="vf-a-select" name="vf_editorial[primary_persona]">';
	echo '<option value="0">' . esc_html__( 'انتخاب نشده', 'vidiform' ) . '</option>';
	$primary = absint( get_post_meta( $post->ID, '_vf_primary_persona', true ) );
	foreach ( $personas as $persona ) {
		echo '<option value="' . (int) $persona->ID . '" ' . selected( $primary, $persona->ID, false ) . '>' . esc_html( $persona->post_title ) . '</option>';
	}
	echo '</select></label></div>';
	foreach ( $fields as $key => $label ) {
		$value = get_post_meta( $post->ID, '_vf_brief_' . $key, true );
		if ( 'prompt' === $key ) {
			$value = (string) $value;
		}
		echo '<label class="vf-a-field">' . esc_html( $label ) . '<textarea class="vf-a-textarea" rows="2" name="vf_editorial[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea></label>';
	}
	echo '<div class="vf-a-grid vf-a-grid--3">';
	foreach ( array( 'primary_keyword' => __( 'کلمهٔ اصلی', 'vidiform' ), 'related_keywords' => __( 'عبارت‌های مرتبط', 'vidiform' ), 'intent' => __( 'نیت جست‌وجو', 'vidiform' ), 'owner' => __( 'مالک محتوا', 'vidiform' ), 'review_date' => __( 'موعد بازبینی', 'vidiform' ), 'char_target' => __( 'هدف کاراکتر (قابل‌ویرایش)', 'vidiform' ) ) as $key => $label ) {
		$type  = 'review_date' === $key ? 'date' : ( 'char_target' === $key ? 'number' : 'text' );
		$value = get_post_meta( $post->ID, '_vf_' . $key, true );
		echo '<label class="vf-a-field">' . esc_html( $label ) . '<input class="vf-a-input" type="' . esc_attr( $type ) . '" name="vf_editorial[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '"></label>';
	}
	echo '</div>';
	$pillar_id = absint( get_post_meta( $post->ID, '_vf_cluster_pillar', true ) );
	echo '<label class="vf-a-field">' . esc_html__( 'مقالهٔ مرجع خوشه', 'vidiform' ) . '<select class="vf-a-select" name="vf_editorial[cluster_pillar]"><option value="0">' . esc_html__( 'بدون مقالهٔ مرجع', 'vidiform' ) . '</option>';
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $candidate ) {
		if ( (int) $candidate->ID === (int) $post->ID ) continue;
		echo '<option value="' . (int) $candidate->ID . '" ' . selected( $pillar_id, $candidate->ID, false ) . '>' . esc_html( get_the_title( $candidate ) ?: __( '(بدون عنوان)', 'vidiform' ) ) . '</option>';
	}
	echo '</select></label><div class="vf-a-infobox">' . esc_html__( 'نوع محتوا، مرحلهٔ سفر، فاز SEO، دستهٔ عمومی WordPress و خوشهٔ موضوعی فیلدهای جدا هستند. از ستون‌های طبقه‌بندی برای نوع، مرحله، فاز و خوشه استفاده کنید؛ دسته را با دسته‌بندی عادی وردپرس تنظیم کنید.', 'vidiform' ) . '</div>';
	echo '<details style="margin-top:14px"><summary>' . esc_html__( 'چک‌لیست بازبینی پیش از انتشار', 'vidiform' ) . '</summary><div class="vf-a-grid vf-a-grid--2" style="margin-top:10px">';
	$checks = array(
		'intent' => __( 'پرسونا، نیت و مرحلهٔ مخاطب مشخص است', 'vidiform' ),
		'answer' => __( 'پاسخ روشن و تجربه یا شاهد اختصاصی دارد', 'vidiform' ),
		'seo' => __( 'عنوان، H1، توضیح متا و URL هماهنگ و یکتا هستند', 'vidiform' ),
		'links' => __( 'لینک‌های بلاگ، Help Center و محصول بررسی شده‌اند', 'vidiform' ),
		'media' => __( 'تصویرها، alt، منابع و مجوز استفاده بررسی شده‌اند', 'vidiform' ),
		'numeric' => __( 'ادعاهای عددی منبع، بازه، روش و اجازه دارند', 'vidiform' ),
		'reader' => __( 'خوانایی موبایل و چیدمان راست‌چین بررسی شده', 'vidiform' ),
	);
	$saved_checks = (array) get_post_meta( $post->ID, '_vf_editorial_checks', true );
	foreach ( $checks as $key => $label ) {
		echo '<label><input type="checkbox" name="vf_editorial_checks[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, $saved_checks, true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
	}
	echo '<p class="vf-a-note">' . esc_html__( 'Canonical، indexability، noindex و structured data از این متاباکس قابل راستی‌آزمایی نیستند؛ آن‌ها را بیرونی بررسی کنید و دادهٔ نادیده را تأییدشده فرض نکنید.', 'vidiform' ) . '</p></div></details>';
}

function vf_blog_editorial_meta_save( $post_id ) {
	if ( ! isset( $_POST['vf_editorial_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vf_editorial_nonce'] ) ), 'vf_editorial_save' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$values = isset( $_POST['vf_editorial'] ) && is_array( $_POST['vf_editorial'] ) ? wp_unslash( $_POST['vf_editorial'] ) : array();
	foreach ( array( 'problem', 'angle', 'facts', 'claims', 'headings', 'cta', 'prompt' ) as $key ) {
		update_post_meta( $post_id, '_vf_brief_' . $key, sanitize_textarea_field( $values[ $key ] ?? '' ) );
	}
	foreach ( array( 'primary_keyword', 'related_keywords', 'intent', 'owner', 'review_date' ) as $key ) {
		update_post_meta( $post_id, '_vf_' . $key, sanitize_text_field( $values[ $key ] ?? '' ) );
	}
	update_post_meta( $post_id, '_vf_char_target', min( 50000, absint( $values['char_target'] ?? 0 ) ) );
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $values['personas'] ?? array() ) ) ) ) );
	update_post_meta( $post_id, '_vf_personas', $ids );
	$primary = absint( $values['primary_persona'] ?? 0 );
	update_post_meta( $post_id, '_vf_primary_persona', in_array( $primary, $ids, true ) ? $primary : 0 );
	$pillar_id = absint( $values['cluster_pillar'] ?? 0 );
	$pillar = $pillar_id ? get_post( $pillar_id ) : null;
	update_post_meta( $post_id, '_vf_cluster_pillar', $pillar && 'post' === $pillar->post_type ? $pillar_id : 0 );
	$allowed_checks = array( 'intent', 'answer', 'seo', 'links', 'media', 'numeric', 'reader' );
	$checks = array_values( array_intersect( $allowed_checks, array_map( 'sanitize_key', (array) wp_unslash( $_POST['vf_editorial_checks'] ?? array() ) ) ) );
	update_post_meta( $post_id, '_vf_editorial_checks', $checks );
}
add_action( 'save_post_post', 'vf_blog_editorial_meta_save' );

function vf_blog_editorial_editor_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'post' !== get_post_type() ) {
		return;
	}
	wp_enqueue_script( 'vf-editorial-counter', VF_URI . '/assets/js/editorial-counter.js', array(), VF_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'vf_blog_editorial_editor_assets' );

function vf_blog_editorial_summary( $post_id ) {
	if ( ! $post_id ) return '';
	$labels = array();
	$persona_id = absint( get_post_meta( $post_id, '_vf_primary_persona', true ) );
	if ( $persona_id ) $labels[] = get_the_title( $persona_id );
	foreach ( array( 'vf_content_type', 'vf_seo_phase' ) as $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) $labels[] = $terms[0]->name;
	}
	$categories = get_the_category( $post_id );
	if ( $categories ) $labels[] = $categories[0]->name;
	return implode( ' · ', array_filter( $labels ) );
}
