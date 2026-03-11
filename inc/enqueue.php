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
 * Inline only the above-the-fold baseline styles to reduce render blocking.
 */
function clean_researcher_print_critical_css(): void {
    if ( is_admin() ) {
        return;
    }

    $css = <<<'CSS'
body{margin:0;background:#fff;color:#111827;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;font-family:var(--font-body,system-ui,sans-serif)}
h1,h2,h3,h4,h5,h6{font-family:var(--font-title,Georgia,serif)}
.skip-link{position:absolute;left:-9999px;top:0;background:#111827;color:#fff;padding:.5rem 1rem;font-size:.875rem;z-index:9999}
.skip-link:focus{left:0}
.clean-researcher-frame{max-width:var(--layout-max,1320px);margin-left:auto;margin-right:auto}
.clean-researcher-shell{max-width:var(--layout-max,1320px);margin-left:auto;margin-right:auto;padding:3rem 1.5rem}
.site-nav ul{display:flex;gap:1.5rem;list-style:none;margin:0;padding:0}
.site-nav a{text-decoration:none;color:#6b7280;font-size:.875rem}
.site-nav a:hover{color:#111827}
.clean-researcher-content{max-width:var(--content-max,760px)}
.h-10{height:2.5rem}
.w-auto{width:auto}
.block{display:block}
.object-contain{object-fit:contain}
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
    ];

    if ( ! in_array( $handle, $async_handles, true ) ) {
        return $html;
    }

    $media_attr = '';
    if ( '' !== $media && 'all' !== $media ) {
        $media_attr = ' media="' . esc_attr( $media ) . '"';
    }

    $preload  = '<link rel="preload" as="style" id="' . esc_attr( $handle ) . '-css" href="' . esc_url( $href ) . '" onload="this.onload=null;this.rel=\'stylesheet\'"' . $media_attr . '>';
    $fallback = '<noscript><link rel="stylesheet" id="' . esc_attr( $handle ) . '-css-noscript" href="' . esc_url( $href ) . '"' . $media_attr . '></noscript>';

    return $preload . $fallback;
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
