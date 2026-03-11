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
