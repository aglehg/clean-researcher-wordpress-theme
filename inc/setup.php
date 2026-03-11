<?php
/**
 * Theme setup: supports, image sizes, menus, content width, template utilities.
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
