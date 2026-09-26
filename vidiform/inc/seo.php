<?php
/**
 * SEO: titles, meta description, canonical, Open Graph, Twitter cards, JSON-LD
 * (BreadcrumbList, Article/BlogPosting, WebSite search) and robots rules.
 *
 * When a common SEO plugin is active, the theme stops printing its own meta/OG/JSON-LD to avoid
 * duplicates, and instead feeds its per-post SEO fields and breadcrumbs to the plugin where it can.
 *
 * @package VidiForm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a known SEO plugin handles meta output.
 *
 * @return bool
 */
function vf_seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
	return (bool) apply_filters( 'vf_seo_plugin_active', $active );
}

/**
 * Current canonical URL for views where WordPress core does not print one.
 *
 * @return string
 */
function vf_seo_canonical() {
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	if ( 'home' === get_query_var( 'vf_help' ) ) {
		$url = vf_help_url();
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_author() ) {
		$url = get_author_posts_url( get_queried_object_id() );
	} elseif ( is_home() ) {
		$url = vf_blog_home_url();
	}
	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}
	if ( $paged > 1 ) {
		$url = get_option( 'permalink_structure' ) ? trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' ) : add_query_arg( 'paged', $paged, $url );
	}
	return $url;
}

/**
 * Meta description for the current view.
 *
 * @return string
 */
function vf_seo_description() {
	$d = '';
	if ( is_singular() ) {
		$id = get_queried_object_id();
		$d  = (string) get_post_meta( $id, '_vf_seo_desc', true );
		if ( '' === $d ) {
			$post = get_post( $id );
			$d    = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '…' );
		}
	} elseif ( 'home' === get_query_var( 'vf_help' ) ) {
		$d = vf_opt( 'help', 'landing_desc' );
	} elseif ( is_home() ) {
		$d = vf_opt( 'blog', 'hero_desc' );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$d = wp_strip_all_tags( term_description() );
	} elseif ( is_author() ) {
		$d = get_the_author_meta( 'description', get_queried_object_id() );
	}
	return trim( preg_replace( '/\s+/u', ' ', (string) $d ) );
}

/**
 * Document title parts.
 *
 * @param array $parts Parts.
 * @return array
 */
function vf_seo_title_parts( $parts ) {
	if ( is_singular( array( 'post', 'help_article' ) ) ) {
		$custom = (string) get_post_meta( get_queried_object_id(), '_vf_seo_title', true );
		if ( '' !== $custom ) {
			$parts['title'] = $custom;
		}
	}
	if ( 'home' === get_query_var( 'vf_help' ) ) {
		$parts['title'] = __( 'مرکز راهنمای ویدی‌فرم', 'vidiform' );
	} elseif ( get_query_var( 'vf_help_search' ) ) {
		/* translators: %s search term */
		$parts['title'] = sprintf( __( 'جست‌وجوی «%s» در مرکز راهنما', 'vidiform' ), get_search_query( false ) );
	} elseif ( is_tax( 'help_category' ) ) {
		/* translators: %s category */
		$parts['title'] = sprintf( __( '%s — مرکز راهنما', 'vidiform' ), single_term_title( '', false ) );
	} elseif ( is_home() ) {
		$parts['title'] = vf_opt( 'blog', 'hero_title' );
	}
	if ( isset( $parts['page'] ) ) {
		/* translators: %s page number */
		$parts['page'] = sprintf( __( 'صفحه‌ی %s', 'vidiform' ), vf_num( max( 1, (int) get_query_var( 'paged' ) ) ) );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'vf_seo_title_parts' );

/**
 * Robots: search results are not indexed.
 *
 * @param array $robots Directives.
 * @return array
 */
function vf_seo_robots( $robots ) {
	if ( is_search() || get_query_var( 'vf_help_search' ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'vf_seo_robots' );

/**
 * Breadcrumb items for the current view (either system).
 *
 * @return array[]
 */
function vf_seo_breadcrumbs() {
	if ( 'help' === vf_section() ) {
		return vf_help_breadcrumb_items();
	}
	if ( is_home() || is_404() ) {
		return array();
	}
	return vf_blog_breadcrumb_items();
}

/**
 * Print meta tags, OG, Twitter and JSON-LD.
 */
function vf_seo_head() {
	if ( vf_seo_plugin_active() || is_404() ) {
		return;
	}
	$desc  = vf_seo_description();
	$canon = vf_seo_canonical();
	$title = wp_get_document_title();
	$url   = $canon ? $canon : ( is_singular() ? get_permalink() : '' );
	$image = '';
	$type  = 'website';

	if ( is_singular() && has_post_thumbnail() ) {
		$image = (string) get_the_post_thumbnail_url( get_queried_object_id(), 'vf-hero' );
	}
	if ( ! $image && vf_opt( 'general', 'og_image' ) ) {
		$image = (string) wp_get_attachment_image_url( (int) vf_opt( 'general', 'og_image' ), 'full' );
	}
	if ( ! $image ) {
		$image = VF_URI . '/assets/images/logo.png';
	}
	if ( is_singular( array( 'post', 'help_article' ) ) ) {
		$type = 'article';
	}

	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $desc, 300, '…' ) ) );
	}
	if ( $canon ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canon ) );
	}
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $desc, 300, '…' ) ) );
	}
	if ( $url ) {
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	}
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	if ( 'article' === $type ) {
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_post_time( 'c', true ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_post_modified_time( 'c', true ) ) );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( wp_html_excerpt( $desc, 200, '…' ) ) );
	}
	printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );

	// JSON-LD.
	$graph  = array();
	$crumbs = vf_seo_breadcrumbs();
	if ( count( $crumbs ) > 1 ) {
		$list = array();
		foreach ( $crumbs as $i => $c ) {
			$item = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( $c[0] ) );
			$item['item'] = $c[1] ? $c[1] : ( is_singular() ? get_permalink() : $url );
			$list[]       = $item;
		}
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
	}
	if ( is_singular( array( 'post', 'help_article' ) ) ) {
		$pid     = get_queried_object_id();
		$post    = get_post( $pid );
		$graph[] = array(
			'@type'            => is_singular( 'post' ) ? 'BlogPosting' : 'TechArticle',
			'headline'         => wp_strip_all_tags( get_the_title( $pid ) ),
			'description'      => $desc,
			'image'            => $image,
			'datePublished'    => get_post_time( 'c', true, $pid ),
			'dateModified'     => get_post_modified_time( 'c', true, $pid ),
			'mainEntityOfPage' => get_permalink( $pid ),
			'inLanguage'       => get_bloginfo( 'language' ),
			'author'           => array(
				'@type' => is_singular( 'post' ) ? 'Person' : 'Organization',
				'name'  => is_singular( 'post' ) ? get_the_author_meta( 'display_name', $post->post_author ) : get_bloginfo( 'name' ),
				'url'   => is_singular( 'post' ) ? get_author_posts_url( $post->post_author ) : home_url( '/' ),
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'logo'  => array( '@type' => 'ImageObject', 'url' => VF_URI . '/assets/images/logo.png' ),
			),
		);
	}
	if ( 'home' === get_query_var( 'vf_help' ) ) {
		$graph[] = array(
			'@type'           => 'WebSite',
			'url'             => vf_help_url(),
			'name'            => __( 'مرکز راهنمای ویدی‌فرم', 'vidiform' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => vf_help_search_url() . '?q={search_term_string}',
				'query-input' => 'required name=search_term_string',
			),
		);
	} elseif ( is_home() ) {
		$graph[] = array(
			'@type'           => 'WebSite',
			'url'             => vf_blog_home_url(),
			'name'            => vf_opt( 'blog', 'hero_title' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
	}
	if ( $graph ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
	}
}
add_action( 'wp_head', 'vf_seo_head', 2 );

/* -------------------------------------------------------------------------
 * SEO plugin integration: feed our per-post fields when the plugin has none.
 * ---------------------------------------------------------------------- */

add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( '' === (string) $desc && is_singular( array( 'post', 'help_article' ) ) ) {
		return (string) get_post_meta( get_queried_object_id(), '_vf_seo_desc', true );
	}
	return $desc;
} );
add_filter( 'rank_math/frontend/description', function ( $desc ) {
	if ( '' === (string) $desc && is_singular( array( 'post', 'help_article' ) ) ) {
		return (string) get_post_meta( get_queried_object_id(), '_vf_seo_desc', true );
	}
	return $desc;
} );

/**
 * Keep the virtual Help Center home in the core XML sitemap (wp-sitemap.xml includes the
 * help_article and help_category entries automatically; exclude the "home" router page).
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type.
 * @return array
 */
function vf_sitemap_posts_query( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$front = (int) get_option( 'vf_front_page_id' );
		if ( $front ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), array( $front ) );
		}
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'vf_sitemap_posts_query', 10, 2 );

/**
 * Exclude the private feature library taxonomy from sitemaps (it is not public anyway).
 *
 * @param array $taxonomies Taxonomies.
 * @return array
 */
function vf_sitemap_taxonomies( $taxonomies ) {
	unset( $taxonomies['help_feature'] );
	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'vf_sitemap_taxonomies' );

/**
 * Register a tiny sitemap provider for the Help Center home URL.
 */
function vf_register_sitemap_provider() {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}
	if ( ! class_exists( 'VF_Sitemaps_Hubs' ) ) {
		/**
		 * Help Center home (a routed view, so core sitemaps do not list it).
		 */
		class VF_Sitemaps_Hubs extends WP_Sitemaps_Provider { // phpcs:ignore Generic.Files.OneObjectStructurePerFile
			/** Constructor. */
			public function __construct() {
				$this->name        = 'vfhubs';
				$this->object_type = 'vfhubs';
			}
			/**
			 * URL list.
			 *
			 * @param int    $page_num       Page.
			 * @param string $object_subtype Subtype.
			 * @return array
			 */
			public function get_url_list( $page_num, $object_subtype = '' ) {
				// The Blog home is the WordPress posts page and is already listed by core.
				return array( array( 'loc' => vf_help_url() ) );
			}
			/**
			 * Max pages.
			 *
			 * @param string $object_subtype Subtype.
			 * @return int
			 */
			public function get_max_num_pages( $object_subtype = '' ) {
				return 1;
			}
		}
	}
	wp_register_sitemap_provider( 'vfhubs', new VF_Sitemaps_Hubs() );
}
add_action( 'init', 'vf_register_sitemap_provider' );
