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
