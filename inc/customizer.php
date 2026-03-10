<?php
/**
 * Theme Customizer integration.
 */

defined( 'ABSPATH' ) || exit;

function clean_researcher_get_google_font_choices(): array {
    return [
        '' => __( 'System default', 'clean-researcher' ),
        'Inter' => 'Inter',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Manrope' => 'Manrope',
        'DM Sans' => 'DM Sans',
        'Source Sans 3' => 'Source Sans 3',
        'Merriweather' => 'Merriweather',
        'Lora' => 'Lora',
        'Playfair Display' => 'Playfair Display',
        'Cormorant Garamond' => 'Cormorant Garamond',
        'Libre Baskerville' => 'Libre Baskerville',
        'Space Grotesk' => 'Space Grotesk',
        'Fraunces' => 'Fraunces',
    ];
}

function clean_researcher_sanitize_font_choice( string $value ): string {
    $choices = clean_researcher_get_google_font_choices();

    return array_key_exists( $value, $choices ) ? $value : '';
}

function clean_researcher_sanitize_content_width( $value ): int {
    $width = absint( $value );

    if ( $width < 640 ) {
        return 640;
    }

    if ( $width > 840 ) {
        return 840;
    }

    return $width;
}

function clean_researcher_sanitize_checkbox( $value ): bool {
    return (bool) $value;
}

function clean_researcher_sanitize_toc_depth( $value ): int {
    $depth = absint( $value );

    if ( $depth < 2 ) {
        return 2;
    }

    if ( $depth > 4 ) {
        return 4;
    }

    return $depth;
}

function clean_researcher_customize_register( WP_Customize_Manager $wp_customize ): void {
    $wp_customize->add_section(
        'clean_researcher_theme_options',
        [
            'title'       => __( 'Clean Researcher Theme', 'clean-researcher' ),
            'priority'    => 30,
            'description' => __( 'Typography and article layout settings for the Clean Researcher theme.', 'clean-researcher' ),
        ]
    );

    $wp_customize->add_setting(
        'clean_researcher_font_title',
        [
            'default'           => '',
            'sanitize_callback' => 'clean_researcher_sanitize_font_choice',
            'transport'         => 'postMessage',
            'type'              => 'theme_mod',
        ]
    );

    $wp_customize->add_control(
        'clean_researcher_font_title',
        [
            'label'   => __( 'Heading font', 'clean-researcher' ),
            'section' => 'clean_researcher_theme_options',
            'type'    => 'select',
            'choices' => clean_researcher_get_google_font_choices(),
        ]
    );

    $wp_customize->add_setting(
        'clean_researcher_font_body',
        [
            'default'           => '',
            'sanitize_callback' => 'clean_researcher_sanitize_font_choice',
            'transport'         => 'postMessage',
            'type'              => 'theme_mod',
        ]
    );

    $wp_customize->add_control(
        'clean_researcher_font_body',
        [
            'label'   => __( 'Body font', 'clean-researcher' ),
            'section' => 'clean_researcher_theme_options',
            'type'    => 'select',
            'choices' => clean_researcher_get_google_font_choices(),
        ]
    );

    $wp_customize->add_setting(
        'clean_researcher_content_width',
        [
            'default'           => 760,
            'sanitize_callback' => 'clean_researcher_sanitize_content_width',
            'transport'         => 'postMessage',
            'type'              => 'theme_mod',
        ]
    );

    $wp_customize->add_control(
        'clean_researcher_content_width',
        [
            'label'       => __( 'Article width', 'clean-researcher' ),
            'description' => __( 'Adjust the readable width of posts and pages.', 'clean-researcher' ),
            'section'     => 'clean_researcher_theme_options',
            'type'        => 'range',
            'input_attrs' => [
                'min'  => 640,
                'max'  => 840,
                'step' => 10,
            ],
        ]
    );

    $wp_customize->add_setting(
        'clean_researcher_show_toc_pages',
        [
            'default'           => true,
            'sanitize_callback' => 'clean_researcher_sanitize_checkbox',
            'transport'         => 'refresh',
            'type'              => 'theme_mod',
        ]
    );

    $wp_customize->add_control(
        'clean_researcher_show_toc_pages',
        [
            'label'   => __( 'Show table of contents on pages', 'clean-researcher' ),
            'section' => 'clean_researcher_theme_options',
            'type'    => 'checkbox',
        ]
    );

    $wp_customize->add_setting(
        'clean_researcher_toc_max_depth',
        [
            'default'           => 2,
            'sanitize_callback' => 'clean_researcher_sanitize_toc_depth',
            'transport'         => 'refresh',
            'type'              => 'theme_mod',
        ]
    );

    $wp_customize->add_control(
        'clean_researcher_toc_max_depth',
        [
            'label'       => __( 'TOC maximum depth', 'clean-researcher' ),
            'description' => __( 'Choose how deep the table of contents should include headings.', 'clean-researcher' ),
            'section'     => 'clean_researcher_theme_options',
            'type'        => 'select',
            'choices'     => [
                2 => __( 'H2 only', 'clean-researcher' ),
                3 => __( 'H2 + H3', 'clean-researcher' ),
                4 => __( 'H2 + H3 + H4', 'clean-researcher' ),
            ],
        ]
    );
}
add_action( 'customize_register', 'clean_researcher_customize_register' );

function clean_researcher_customize_preview_js(): void {
    wp_enqueue_script(
        'clean-researcher-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        [ 'customize-preview' ],
        wp_get_theme()->get( 'Version' ),
        true
    );
}
add_action( 'customize_preview_init', 'clean_researcher_customize_preview_js' );
