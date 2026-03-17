<?php
/**
 * SEO: meta description, canonical URL, OG/Twitter social tags, JSON-LD schema.
 * All output is suppressed when a dedicated SEO plugin is active.
 *
 * Depends on: inc/breadcrumbs.php (clean_researcher_get_breadcrumb_items)
 */

defined( 'ABSPATH' ) || exit;

// ── Plugin detection ──────────────────────────────────────────────────────────

/**
 * Return true when Yoast SEO, RankMath, AIOSEO, or SEOPress is active.
 * Used as a guard before emitting any head tags or JSON-LD.
 */
function clean_researcher_has_active_seo_plugin(): bool {
    return (
        defined( 'WPSEO_VERSION' ) ||
        defined( 'RANK_MATH_VERSION' ) ||
        defined( 'AIOSEO_VERSION' ) ||
        defined( 'SEOPRESS_VERSION' )
    );
}

// ── Data helpers ──────────────────────────────────────────────────────────────

/**
 * Return sanitized social profile URLs stored in the Customizer.
 *
 * @return string[]
 */
function clean_researcher_get_social_profile_urls(): array {
    $raw = (string) get_theme_mod( 'clean_researcher_social_profiles', '' );
    if ( '' === trim( $raw ) ) {
        return [];
    }

    $parts = preg_split( '/[\r\n,]+/', $raw );
    if ( ! is_array( $parts ) ) {
        return [];
    }

    $urls = [];
    foreach ( $parts as $part ) {
        $url = esc_url_raw( trim( $part ) );
        if ( '' !== $url ) {
            $urls[] = $url;
        }
    }

    return array_values( array_unique( $urls ) );
}

/**
 * Build a meta description string with sensible fallbacks:
 * trimmed post/page content → Customizer default → bloginfo description.
 */
function clean_researcher_get_meta_description(): string {
    if ( is_singular() ) {
        $post = get_queried_object();

        if ( $post instanceof WP_Post ) {
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
 * Resolve the canonical URL for the current request.
 */
function clean_researcher_get_canonical_url(): string {
    if ( is_singular() ) {
        $canonical = wp_get_canonical_url();
        return is_string( $canonical ) ? $canonical : '';
    }

    if ( is_front_page() ) {
        return home_url( '/' );
    }

    if ( is_home() || is_archive() || is_search() ) {
        $paged = max( 1, (int) get_query_var( 'paged' ) );

        return $paged > 1 ? get_pagenum_link( $paged ) : get_pagenum_link( 1 );
    }

    global $wp;

    if ( isset( $wp->request ) ) {
        return home_url( add_query_arg( [], $wp->request ) );
    }

    return home_url( '/' );
}

/**
 * Extract the URL of the first <img> found in post content (content-image fallback).
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
 * Resolve the best available OG image for the current request, including
 * dimensions and alt text when the source is a WordPress attachment.
 *
 * Priority: featured image → first content image → Customizer default → site icon → screenshot.
 * Results are statically cached so the waterfall only runs once per request.
 *
 * @return array{url:string,width:int,height:int,alt:string}
 */
function clean_researcher_get_og_image_data(): array {
    static $cache = null;
    if ( null !== $cache ) {
        return $cache;
    }

    $cache            = [ 'url' => '', 'width' => 0, 'height' => 0, 'alt' => '' ];
    $default_image_id = (int) get_theme_mod( 'clean_researcher_og_image_id', 0 );
    $post_id          = (int) get_queried_object_id();

    if ( is_singular( [ 'post', 'page' ] ) && $post_id > 0 && has_post_thumbnail( $post_id ) ) {
        $thumb_id = (int) get_post_thumbnail_id( $post_id );
        $url      = get_the_post_thumbnail_url( $post_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            $meta            = wp_get_attachment_metadata( $thumb_id );
            $cache['url']    = $url;
            $cache['width']  = is_array( $meta ) && isset( $meta['width'] ) ? (int) $meta['width'] : 0;
            $cache['height'] = is_array( $meta ) && isset( $meta['height'] ) ? (int) $meta['height'] : 0;
            $cache['alt']    = (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
            return $cache;
        }
    }

    if ( is_singular( [ 'post', 'page' ] ) && $post_id > 0 ) {
        $url = clean_researcher_get_first_content_image_url( $post_id );
        if ( '' !== $url ) {
            $cache['url'] = $url;
            return $cache;
        }
    }

    if ( $default_image_id > 0 ) {
        $url = wp_get_attachment_image_url( $default_image_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            $meta            = wp_get_attachment_metadata( $default_image_id );
            $cache['url']    = $url;
            $cache['width']  = is_array( $meta ) && isset( $meta['width'] ) ? (int) $meta['width'] : 0;
            $cache['height'] = is_array( $meta ) && isset( $meta['height'] ) ? (int) $meta['height'] : 0;
            $cache['alt']    = (string) get_post_meta( $default_image_id, '_wp_attachment_image_alt', true );
            return $cache;
        }
    }

    $custom_logo_id = (int) get_theme_mod( 'custom_logo', 0 );
    if ( $custom_logo_id > 0 ) {
        $url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
        if ( is_string( $url ) && '' !== $url ) {
            $meta            = wp_get_attachment_metadata( $custom_logo_id );
            $cache['url']    = $url;
            $cache['width']  = is_array( $meta ) && isset( $meta['width'] ) ? (int) $meta['width'] : 0;
            $cache['height'] = is_array( $meta ) && isset( $meta['height'] ) ? (int) $meta['height'] : 0;
            $cache['alt']    = (string) get_bloginfo( 'name' );
            return $cache;
        }
    }

    $site_icon = get_site_icon_url( 512 );
    if ( is_string( $site_icon ) && '' !== $site_icon ) {
        $cache['url']    = $site_icon;
        $cache['width']  = 512;
        $cache['height'] = 512;
        return $cache;
    }

    $screenshot_path = get_theme_file_path( '/screenshot.png' );
    if ( file_exists( $screenshot_path ) ) {
        $cache['url'] = get_theme_file_uri( '/screenshot.png' );
        return $cache;
    }

    return $cache;
}

/**
 * Convenience wrapper — returns just the URL from get_og_image_data().
 */
function clean_researcher_get_og_image_url(): string {
    return clean_researcher_get_og_image_data()['url'];
}

/**
 * Resolve the site logo URL for social tags and Organization schema.
 * Falls back to site icon.
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

// ── Head tag output ───────────────────────────────────────────────────────────

/**
 * Print <meta name="description"> (priority 5).
 */
function clean_researcher_meta_description_tag(): void {
    if ( is_admin() || clean_researcher_has_active_seo_plugin() ) {
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
 * Print canonical link + Open Graph + Twitter Card meta tags (priority 7).
 */
function clean_researcher_head_social_meta_tags(): void {
    if ( is_admin() || clean_researcher_has_active_seo_plugin() ) {
        return;
    }

    $title        = wp_get_document_title();
    $description  = clean_researcher_get_meta_description();
    $canonical    = clean_researcher_get_canonical_url();
    $image_data   = clean_researcher_get_og_image_data();
    $image_url    = $image_data['url'];
    $site_name    = (string) get_bloginfo( 'name' );
    $locale       = str_replace( '-', '_', (string) get_locale() );
    $og_type      = is_singular( 'post' ) ? 'article' : 'website';
    $twitter_site = (string) get_theme_mod( 'clean_researcher_twitter_site', '' );

    if ( '' !== $canonical ) {
        echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    }

    echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '">' . "\n";

    if ( '' !== $canonical ) {
        echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
    }

    $creator_handle = '';

    if ( is_singular( 'post' ) ) {
        $post_id    = (int) get_queried_object_id();
        $author_id  = (int) get_post_field( 'post_author', $post_id );
        $author_url = get_author_posts_url( $author_id );

        echo '<meta property="article:published_time" content="' . esc_attr( get_post_time( DATE_W3C, true, $post_id ) ) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr( get_post_modified_time( DATE_W3C, true, $post_id ) ) . '">' . "\n";
        if ( '' !== $author_url ) {
            echo '<meta property="article:author" content="' . esc_url( $author_url ) . '">' . "\n";
        }

        $categories = get_the_category( $post_id );
        if ( ! empty( $categories ) ) {
            echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . '">' . "\n";
        }

        $tags = get_the_tags( $post_id );
        if ( is_array( $tags ) ) {
            foreach ( $tags as $tag ) {
                echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '">' . "\n";
            }
        }

        $creator_handle = ltrim( (string) get_user_meta( $author_id, 'clean_researcher_twitter_handle', true ), '@' );
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

    if ( '' !== $twitter_site ) {
        echo '<meta name="twitter:site" content="' . esc_attr( $twitter_site ) . '">' . "\n";
    }

    if ( '' !== $creator_handle ) {
        echo '<meta name="twitter:creator" content="@' . esc_attr( $creator_handle ) . '">' . "\n";
    }

    if ( '' !== $image_url ) {
        echo '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
        if ( $image_data['width'] > 0 ) {
            echo '<meta property="og:image:width" content="' . esc_attr( (string) $image_data['width'] ) . '">' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr( (string) $image_data['height'] ) . '">' . "\n";
        }
        if ( '' !== $image_data['alt'] ) {
            echo '<meta property="og:image:alt" content="' . esc_attr( $image_data['alt'] ) . '">' . "\n";
        }
        echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
        if ( '' !== $image_data['alt'] ) {
            echo '<meta name="twitter:image:alt" content="' . esc_attr( $image_data['alt'] ) . '">' . "\n";
        }
    }
}
add_action( 'wp_head', 'clean_researcher_head_social_meta_tags', 7 );

/**
 * Print JSON-LD structured data: Organization, BlogPosting, BreadcrumbList (priority 8).
 */
function clean_researcher_schema_jsonld(): void {
    if ( is_admin() || clean_researcher_has_active_seo_plugin() ) {
        return;
    }

    $graph = [];

    if ( is_front_page() || is_home() ) {
        $org = [
            '@type' => 'Organization',
            '@id'   => home_url( '/' ) . '#organization',
            'name'  => (string) get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        ];

        $site_description = (string) get_bloginfo( 'description' );
        if ( '' !== trim( $site_description ) ) {
            $org['description'] = $site_description;
        }

        $logo = clean_researcher_get_site_logo_url();
        if ( '' !== $logo ) {
            $org['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $logo,
            ];
        }

        $same_as = clean_researcher_get_social_profile_urls();
        if ( ! empty( $same_as ) ) {
            $org['sameAs'] = $same_as;
        }

        $graph[] = $org;
    }

    if ( is_singular( 'post' ) ) {
        $post_id   = (int) get_queried_object_id();
        $author_id = (int) get_post_field( 'post_author', $post_id );

        $article = [
            '@type'            => 'BlogPosting',
            'headline'         => get_the_title( $post_id ),
            'datePublished'    => get_post_time( DATE_W3C, true, $post_id ),
            'dateModified'     => get_post_modified_time( DATE_W3C, true, $post_id ),
            'mainEntityOfPage' => clean_researcher_get_canonical_url(),
            'author'           => [
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $author_id ),
                'url'   => get_author_posts_url( $author_id ),
            ],
            'publisher'        => [
                '@type' => 'Organization',
                '@id'   => home_url( '/' ) . '#organization',
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
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $crumb['name'],
            ];

            if ( '' !== $crumb['url'] ) {
                $item['item'] = $crumb['url'];
            }

            $list[] = $item;
        }

        $graph[] = [
            '@type'           => 'BreadcrumbList',
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
