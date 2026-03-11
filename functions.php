<?php
/**
 * Clean Researcher – functions.php
 */

defined( 'ABSPATH' ) || exit;

function clean_researcher_setup(): void {
    load_theme_textdomain( 'clean-researcher', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'custom-logo', [ 'flex-width' => true, 'flex-height' => true ] );

    // Large-enough source for desktop while keeping responsive srcset candidates.
    add_image_size( 'clean-researcher-featured', 1600, 0, false );

    if ( file_exists( get_theme_file_path( '/dist/main.css' ) ) ) {
        add_editor_style( 'dist/main.css' );
    }

    register_nav_menus( [ 'primary' => __( 'Primary Menu', 'clean-researcher' ) ] );
}
add_action( 'after_setup_theme', 'clean_researcher_setup' );

/**
 * Sizes hint for single post featured image.
 */
function clean_researcher_featured_image_sizes(): string {
    $content_width = clean_researcher_get_content_width();

    return '(max-width: 768px) 100vw, ' . (int) $content_width . 'px';
}

/**
 * Apply a mobile-friendly sizes hint to images rendered in post content.
 *
 * @param string $filtered_image Filtered HTML img tag.
 * @param string $context        Context where the image is rendered.
 * @param int    $attachment_id  Attachment ID.
 */
function clean_researcher_content_image_sizes( string $filtered_image, string $context, int $attachment_id ): string {
    unset( $context, $attachment_id );

    if ( ! is_singular() || false === strpos( $filtered_image, '<img' ) ) {
        return $filtered_image;
    }

    $sizes = esc_attr( clean_researcher_featured_image_sizes() );

    if ( false !== stripos( $filtered_image, ' sizes=' ) ) {
        $updated = preg_replace( '/\ssizes=("|\')(.*?)\1/i', ' sizes="' . $sizes . '"', $filtered_image, 1 );

        return is_string( $updated ) ? $updated : $filtered_image;
    }

    $updated = preg_replace( '/<img\b/i', '<img sizes="' . $sizes . '"', $filtered_image, 1 );

    return is_string( $updated ) ? $updated : $filtered_image;
}
add_filter( 'wp_content_img_tag', 'clean_researcher_content_image_sizes', 20, 3 );

function clean_researcher_content_width(): void {
    $GLOBALS['content_width'] = clean_researcher_get_content_width();
}
add_action( 'after_setup_theme', 'clean_researcher_content_width', 0 );

function clean_researcher_get_content_width(): int {
    $width = (int) get_theme_mod( 'clean_researcher_content_width', 760 );

    return max( 640, min( 840, $width ) );
}

function clean_researcher_show_toc_on_pages(): bool {
    return (bool) get_theme_mod( 'clean_researcher_show_toc_pages', true );
}

function clean_researcher_get_toc_max_depth(): int {
    $depth = (int) get_theme_mod( 'clean_researcher_toc_max_depth', 2 );

    return max( 2, min( 4, $depth ) );
}

function clean_researcher_google_font_url(): string {
    $title_font = get_theme_mod( 'clean_researcher_font_title', '' );
    $body_font  = get_theme_mod( 'clean_researcher_font_body', '' );
    $families = [];
    foreach ( array_unique( array_filter( [ $title_font, $body_font ] ) ) as $font ) {
        $families[] = str_replace( ' ', '+', $font ) . ':wght@400;700';
    }
    if ( empty( $families ) ) { return ''; }
    return 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( fn( $f ) => 'family=' . $f, $families ) ) . '&display=swap';
}

/**
 * Detect major SEO plugins to avoid duplicate tags.
 */
function clean_researcher_has_active_seo_plugin(): bool {
    return (
        defined( 'WPSEO_VERSION' ) ||
        defined( 'RANK_MATH_VERSION' ) ||
        defined( 'AIOSEO_VERSION' ) ||
        defined( 'SEOPRESS_VERSION' )
    );
}

/**
 * Build a meta description with sensible fallbacks.
 */
function clean_researcher_get_meta_description(): string {
    if ( is_singular() ) {
        $post = get_queried_object();

        if ( $post instanceof WP_Post ) {
            if ( has_excerpt( $post ) ) {
                return wp_trim_words( wp_strip_all_tags( (string) get_the_excerpt( $post ) ), 28, '...' );
            }

            $content = (string) get_post_field( 'post_content', $post );
            $content = strip_shortcodes( $content );
            $content = wp_strip_all_tags( $content );
            $content = preg_replace( '/\s+/', ' ', $content );

            if ( is_string( $content ) ) {
                $content = trim( $content );
            } else {
                $content = '';
            }

            if ( '' !== $content ) {
                return wp_trim_words( $content, 28, '...' );
            }
        }
    }

    $default = (string) get_theme_mod( 'clean_researcher_meta_description', '' );
    if ( '' !== trim( $default ) ) {
        return wp_trim_words( wp_strip_all_tags( $default ), 28, '...' );
    }

    return wp_trim_words( wp_strip_all_tags( (string) get_bloginfo( 'description' ) ), 28, '...' );
}

/**
 * Print meta description unless handled by a dedicated SEO plugin.
 */
function clean_researcher_meta_description_tag(): void {
    if ( is_admin() ) {
        return;
    }

    if ( clean_researcher_has_active_seo_plugin() ) {
        return;
    }

    $description = clean_researcher_get_meta_description();
    if ( '' === $description ) {
        return;
    }

    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
}
add_action( 'wp_head', 'clean_researcher_meta_description_tag', 5 );

/**
 * Canonical URL for the current request.
 */
function clean_researcher_get_canonical_url(): string {
    if ( is_singular() ) {
        $canonical = wp_get_canonical_url();
        return is_string( $canonical ) ? $canonical : '';
    }

    global $wp;

    if ( isset( $wp->request ) ) {
        return home_url( add_query_arg( [], $wp->request ) );
    }

    return home_url( '/' );
}

/**
 * Build breadcrumb items for UI and schema use.
 *
 * @return array<int, array{name:string,url:string}>
 */
function clean_researcher_get_breadcrumb_items(): array {
    $items = [
        [
            'name' => (string) get_bloginfo( 'name' ),
            'url'  => home_url( '/' ),
        ],
    ];

    if ( is_home() ) {
        $items[] = [ 'name' => single_post_title( '', false ), 'url' => '' ];
        return $items;
    }

    if ( is_singular( 'post' ) ) {
        $posts_page_id = (int) get_option( 'page_for_posts' );
        if ( $posts_page_id > 0 ) {
            $items[] = [
                'name' => get_the_title( $posts_page_id ),
                'url'  => get_permalink( $posts_page_id ),
            ];
        }

        $items[] = [ 'name' => get_the_title(), 'url' => '' ];
        return $items;
    }

    if ( is_singular( 'page' ) ) {
        $ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
        foreach ( $ancestors as $ancestor_id ) {
            $items[] = [
                'name' => get_the_title( $ancestor_id ),
                'url'  => get_permalink( $ancestor_id ),
            ];
        }

        $items[] = [ 'name' => get_the_title(), 'url' => '' ];
        return $items;
    }

    if ( is_category() || is_tag() || is_tax() || is_archive() ) {
        $items[] = [ 'name' => wp_strip_all_tags( get_the_archive_title() ), 'url' => '' ];
    }

    return $items;
}

/**
 * Render breadcrumb navigation in templates.
 */
function clean_researcher_render_breadcrumbs( bool $compact = false ): void {
    $items = clean_researcher_get_breadcrumb_items();

    if ( count( $items ) < 2 ) {
        return;
    }

    $nav_class = $compact ? 'text-sm text-gray-600' : 'mb-6 text-sm text-gray-500';
    $list_class = $compact
        ? 'm-0 p-0 list-none flex items-center gap-1.5 min-w-0 overflow-hidden whitespace-nowrap'
        : 'm-0 p-0 list-none flex flex-wrap items-center gap-1.5';
    $item_class = $compact ? 'inline-flex items-center gap-1.5 min-w-0' : 'inline-flex items-center gap-1.5';
    $current_class = $compact
        ? 'text-gray-700 truncate max-w-[9rem] sm:max-w-[14rem] md:max-w-[20rem]'
        : 'text-gray-700';
    $link_class = $compact
        ? 'text-gray-600 hover:text-gray-900 no-underline truncate max-w-[9rem] sm:max-w-[14rem] md:max-w-[20rem]'
        : 'text-gray-600 hover:text-gray-900 no-underline';

    echo '<nav class="' . esc_attr( $nav_class ) . '" aria-label="' . esc_attr__( 'Breadcrumb', 'clean-researcher' ) . '">';
    echo '<ol class="' . esc_attr( $list_class ) . '">';

    $last_index = count( $items ) - 1;

    foreach ( $items as $index => $item ) {
        $is_last = $index === $last_index;

        echo '<li class="' . esc_attr( $item_class ) . '">';
        if ( $is_last || '' === $item['url'] ) {
            echo '<span class="' . esc_attr( $current_class ) . '">' . esc_html( $item['name'] ) . '</span>';
        } else {
            echo '<a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a>';
        }

        if ( ! $is_last ) {
            echo '<span class="text-gray-300 shrink-0" aria-hidden="true">/</span>';
        }
        echo '</li>';
    }

    echo '</ol>';
    echo '</nav>';
}

/**
 * Extract the first image URL from post content.
 */
function clean_researcher_get_first_content_image_url( int $post_id ): string {
    $content = (string) get_post_field( 'post_content', $post_id );
    if ( '' === $content ) {
        return '';
    }

    if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
        return isset( $matches[1] ) ? esc_url_raw( $matches[1] ) : '';
    }

    return '';
}

/**
 * Resolve an Open Graph image URL for the current request.
 */
function clean_researcher_get_og_image_url(): string {
    $default_image_id = (int) get_theme_mod( 'clean_researcher_og_image_id', 0 );
    $post_id = (int) get_queried_object_id();

    if ( is_singular( [ 'post', 'page' ] ) && $post_id > 0 && has_post_thumbnail( $post_id ) ) {
        $url = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            return $url;
        }
    }

    if ( is_singular( [ 'post', 'page' ] ) && $post_id > 0 ) {
        $url = clean_researcher_get_first_content_image_url( $post_id );
        if ( '' !== $url ) {
            return $url;
        }
    }

    if ( $default_image_id > 0 ) {
        $url = wp_get_attachment_image_url( $default_image_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            return $url;
        }
    }

    $site_icon = get_site_icon_url( 512 );
    if ( is_string( $site_icon ) && '' !== $site_icon ) {
        return $site_icon;
    }

    $screenshot_path = get_theme_file_path( '/screenshot.png' );
    if ( file_exists( $screenshot_path ) ) {
        return get_theme_file_uri( '/screenshot.png' );
    }

    return '';
}

/**
 * Resolve a site logo URL for social tags and Organization schema.
 */
function clean_researcher_get_site_logo_url(): string {
    $custom_logo_id = (int) get_theme_mod( 'custom_logo', 0 );
    if ( $custom_logo_id > 0 ) {
        $logo = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        if ( is_string( $logo ) && '' !== $logo ) {
            return $logo;
        }
    }

    $site_icon = get_site_icon_url( 512 );

    return is_string( $site_icon ) ? $site_icon : '';
}

/**
 * Print explicit Open Graph image tags.
 */
function clean_researcher_og_image_tags(): void {
    if ( is_admin() ) {
        return;
    }

    if ( clean_researcher_has_active_seo_plugin() ) {
        return;
    }

    $image_url = clean_researcher_get_og_image_url();
    if ( '' === $image_url ) {
        return;
    }

    echo '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
}
/**
 * Print canonical + social meta tags.
 */
function clean_researcher_head_social_meta_tags(): void {
    if ( is_admin() || clean_researcher_has_active_seo_plugin() ) {
        return;
    }

    $title       = wp_get_document_title();
    $description = clean_researcher_get_meta_description();
    $canonical   = clean_researcher_get_canonical_url();
    $image_url   = clean_researcher_get_og_image_url();
    $site_name   = (string) get_bloginfo( 'name' );
    $og_type     = is_singular( 'post' ) ? 'article' : 'website';

    if ( '' !== $canonical ) {
        echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    }

    echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";

    if ( '' !== $canonical ) {
        echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

    if ( '' !== $image_url ) {
        echo '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'clean_researcher_head_social_meta_tags', 7 );

/**
 * Print structured data for article, organization, and breadcrumbs.
 */
function clean_researcher_schema_jsonld(): void {
    if ( is_admin() || clean_researcher_has_active_seo_plugin() ) {
        return;
    }

    $graph = [];

    if ( is_front_page() || is_home() ) {
        $org = [
            '@type' => 'Organization',
            'name'  => (string) get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        ];

        $logo = clean_researcher_get_site_logo_url();
        if ( '' !== $logo ) {
            $org['logo'] = $logo;
        }

        $graph[] = $org;
    }

    if ( is_singular( 'post' ) ) {
        $post_id = (int) get_queried_object_id();
        $author_id = (int) get_post_field( 'post_author', $post_id );

        $article = [
            '@type' => 'BlogPosting',
            'headline' => get_the_title( $post_id ),
            'datePublished' => get_post_time( DATE_W3C, true, $post_id ),
            'dateModified' => get_post_modified_time( DATE_W3C, true, $post_id ),
            'mainEntityOfPage' => clean_researcher_get_canonical_url(),
            'author' => [
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $author_id ),
                'url'   => get_author_posts_url( $author_id ),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => (string) get_bloginfo( 'name' ),
            ],
        ];

        $logo = clean_researcher_get_site_logo_url();
        if ( '' !== $logo ) {
            $article['publisher']['logo'] = [ '@type' => 'ImageObject', 'url' => $logo ];
        }

        $image_url = clean_researcher_get_og_image_url();
        if ( '' !== $image_url ) {
            $article['image'] = [ $image_url ];
        }

        $graph[] = $article;
    }

    $breadcrumbs = clean_researcher_get_breadcrumb_items();
    if ( count( $breadcrumbs ) >= 2 ) {
        $list = [];
        foreach ( $breadcrumbs as $index => $crumb ) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
            ];

            if ( '' !== $crumb['url'] ) {
                $item['item'] = $crumb['url'];
            }

            $list[] = $item;
        }

        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    if ( empty( $graph ) ) {
        return;
    }

    $json = [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'clean_researcher_schema_jsonld', 8 );

function clean_researcher_enqueue_assets(): void {
    $theme_version = wp_get_theme()->get( 'Version' );
    $style_path    = get_theme_file_path( '/dist/main.css' );
    $style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $theme_version;

    wp_enqueue_style( 'clean-researcher-main', get_template_directory_uri() . '/dist/main.css', [], $style_version );
    $gf_url = clean_researcher_google_font_url();
    if ( $gf_url ) {
        wp_enqueue_style( 'clean-researcher-google-fonts', $gf_url, [], null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
    }
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', [], '6.5.2' );
    wp_enqueue_script( 'clean-researcher-toc', get_template_directory_uri() . '/assets/js/toc.js', [], $theme_version, true );
    wp_localize_script(
        'clean-researcher-toc',
        'cleanResearcherToc',
        [
            'maxDepth' => clean_researcher_get_toc_max_depth(),
        ]
    );
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'clean_researcher_enqueue_assets' );

/**
 * Add editor hints in the block editor document sidebar.
 */
function clean_researcher_enqueue_editor_assets(): void {
    wp_enqueue_script(
        'clean-researcher-editor-hints',
        get_template_directory_uri() . '/assets/js/editor-hints.js',
        [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-i18n' ],
        wp_get_theme()->get( 'Version' ),
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'clean_researcher_enqueue_editor_assets' );

function clean_researcher_font_css_vars(): void {
    $title_font = get_theme_mod( 'clean_researcher_font_title', '' );
    $body_font  = get_theme_mod( 'clean_researcher_font_body', '' );
    $content_width = clean_researcher_get_content_width();
    $layout_width  = $content_width + 560;
    $vars = '--content-max:' . $content_width . 'px;--layout-max:' . $layout_width . 'px;';

    if ( $title_font ) { $vars .= '--font-title: "' . esc_attr( $title_font ) . '", serif;'; }
    if ( $body_font )  { $vars .= '--font-body: "'  . esc_attr( $body_font )  . '", sans-serif;'; }

    if ( $vars ) {
        echo '<style>:root{' . $vars . '}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
add_action( 'wp_head', 'clean_researcher_font_css_vars' );

/**
 * Render pingbacks/trackbacks in the comments template.
 *
 * @param WP_Comment $comment Current comment object.
 */
function clean_researcher_custom_pings( WP_Comment $comment ): void {
    ?>
    <li id="comment-<?php comment_ID(); ?>">
        <?php comment_author_link(); ?>
    </li>
    <?php
}

require get_template_directory() . '/inc/customizer.php';
