<?php
/**
 * Asset enqueueing: front-end styles/scripts, block editor assets, font CSS vars.
 */

defined( 'ABSPATH' ) || exit;

function clean_researcher_google_font_url(): string {
    $title_font = get_theme_mod( 'clean_researcher_font_title', '' );
    $body_font  = get_theme_mod( 'clean_researcher_font_body', '' );
    $families   = [];

    foreach ( array_unique( array_filter( [ $title_font, $body_font ] ) ) as $font ) {
        $families[] = str_replace( ' ', '+', $font ) . ':wght@400;700';
    }

    if ( empty( $families ) ) {
        return '';
    }

    return 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( fn( $f ) => 'family=' . $f, $families ) ) . '&display=optional';
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
 * Remove jQuery from frontend requests.
 */
function clean_researcher_remove_frontend_jquery(): void {
    if ( is_admin() ) {
        return;
    }

    wp_dequeue_script( 'jquery' );
    wp_dequeue_script( 'jquery-core' );
    wp_dequeue_script( 'jquery-migrate' );
    wp_deregister_script( 'jquery' );
    wp_deregister_script( 'jquery-core' );
    wp_deregister_script( 'jquery-migrate' );
}
add_action( 'wp_enqueue_scripts', 'clean_researcher_remove_frontend_jquery', 100 );

/**
 * Inline only the above-the-fold baseline styles to reduce render blocking.
 */
function clean_researcher_print_critical_css(): void {
    if ( is_admin() ) {
        return;
    }

    $css = <<<'CSS'
body{margin:0;background:#fff}
.skip-link{position:absolute;left:-9999px;top:0;background:#111827;color:#fff;padding:.5rem 1rem;font-size:.875rem;z-index:9999}
.skip-link:focus{left:0}
header{border-bottom:1px solid #e5e7eb;padding:1rem 1.5rem;min-height:72px;box-sizing:border-box}
[data-cr-loading] header>*{visibility:hidden}
[data-cr-loading] #main-content{visibility:hidden}
CSS;

    echo '<style id="clean-researcher-critical-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'clean_researcher_print_critical_css', 2 );

/**
 * Load selected stylesheets asynchronously to move them out of the critical path.
 */
function clean_researcher_async_style_loader_tag( string $html, string $handle, string $href, string $media ): string {
    if ( is_admin() ) {
        return $html;
    }

    $async_handles = [
        'clean-researcher-main',
        'clean-researcher-google-fonts',
        'cmplz-general-css',
    ];

    if ( ! in_array( $handle, $async_handles, true ) ) {
        return $html;
    }

    $final_media = '' !== $media ? $media : 'all';

    $remove_loading = 'clean-researcher-main' === $handle ? 'document.documentElement.removeAttribute(\'data-cr-loading\');' : '';
    $async_link = '<link rel="stylesheet" id="' . esc_attr( $handle ) . '-css" href="' . esc_url( $href ) . '" media="print" onload="this.onload=null;this.media=\'' . esc_attr( $final_media ) . '\';' . $remove_loading . '">';
    $noscript_extra = 'clean-researcher-main' === $handle ? '<noscript><style>[data-cr-loading] header>*,[data-cr-loading] #main-content{visibility:visible}</style></noscript>' : '';
    $fallback        = '<noscript><link rel="stylesheet" id="' . esc_attr( $handle ) . '-css-noscript" href="' . esc_url( $href ) . '" media="' . esc_attr( $final_media ) . '"></noscript>';

    return $async_link . $fallback . $noscript_extra;
}
add_filter( 'style_loader_tag', 'clean_researcher_async_style_loader_tag', 10, 4 );

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
    $title_font    = get_theme_mod( 'clean_researcher_font_title', '' );
    $body_font     = get_theme_mod( 'clean_researcher_font_body', '' );
    $content_width = clean_researcher_get_content_width();
    $layout_width  = $content_width + 560;
    $vars          = '--content-max:' . $content_width . 'px;--layout-max:' . $layout_width . 'px;';

    if ( $title_font ) {
        $vars .= '--font-title: "' . esc_attr( $title_font ) . '", serif;';
    }
    if ( $body_font ) {
        $vars .= '--font-body: "' . esc_attr( $body_font ) . '", sans-serif;';
    }

    if ( $vars ) {
        echo '<style>:root{' . $vars . '}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
add_action( 'wp_head', 'clean_researcher_font_css_vars' );
