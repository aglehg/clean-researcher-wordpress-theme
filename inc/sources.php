<?php
/**
 * Article sources and inline citations.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Sources block and its editor script.
 */
function clean_researcher_register_sources_block(): void {
    $theme_version  = wp_get_theme()->get( 'Version' );
    $script_path    = get_theme_file_path( '/assets/js/editor-sources.js' );
    $script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $theme_version;

    wp_register_script(
        'clean-researcher-editor-sources',
        get_template_directory_uri() . '/assets/js/editor-sources.js',
        [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-i18n', 'wp-rich-text' ],
        $script_version,
        true
    );

    register_block_type(
        'clean-researcher/sources',
        [
            'editor_script' => 'clean-researcher-editor-sources',
        ]
    );
}
add_action( 'init', 'clean_researcher_register_sources_block' );

/**
 * Replace inline citation markers with numbered links based on the rendered sources block.
 *
 * @param string $content Rendered post content.
 */
function clean_researcher_render_citations( string $content ): string {
    if ( is_admin() || '' === trim( $content ) ) {
        return $content;
    }

    if ( false === strpos( $content, 'cr-citation' ) || false === strpos( $content, 'data-source-id' ) ) {
        return $content;
    }

    if ( ! class_exists( 'DOMDocument' ) ) {
        return $content;
    }

    $document = new DOMDocument();
    $previous = libxml_use_internal_errors( true );
    $encoded  = mb_encode_numericentity( $content, [ 0x80, 0x10ffff, 0, 0xffffff ], 'UTF-8' );
    $loaded   = $document->loadHTML(
        $encoded,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors( $previous );

    if ( ! $loaded ) {
        return $content;
    }

    $xpath      = new DOMXPath( $document );
    $source_map = [];
    $source_nodes = $xpath->query( '//*[@data-source-id]' );

    if ( $source_nodes instanceof DOMNodeList ) {
        $position = 1;

        foreach ( $source_nodes as $source_node ) {
            if ( ! $source_node instanceof DOMElement ) {
                continue;
            }

            $source_id = trim( (string) $source_node->getAttribute( 'data-source-id' ) );

            if ( '' === $source_id ) {
                continue;
            }

            $anchor_id = trim( (string) $source_node->getAttribute( 'id' ) );
            $source_map[ $source_id ] = [
                'number' => $position,
                'href'   => '' !== $anchor_id ? '#' . $anchor_id : '',
            ];
            $position++;
        }
    }

    if ( empty( $source_map ) ) {
        return $content;
    }

    $citation_nodes = $xpath->query(
        '//*[contains(concat(" ", normalize-space(@class), " "), " cr-citation ")]'
    );

    if ( ! $citation_nodes instanceof DOMNodeList || 0 === $citation_nodes->length ) {
        return $content;
    }

    $replacements = [];

    foreach ( $citation_nodes as $citation_node ) {
        if ( ! $citation_node instanceof DOMElement ) {
            continue;
        }

        $source_ids = array_filter(
            array_map(
                'trim',
                explode( ',', (string) $citation_node->getAttribute( 'data-source-ids' ) )
            )
        );

        if ( empty( $source_ids ) ) {
            $replacements[] = [
                'target' => $citation_node,
                'node'   => $document->createTextNode( '' ),
            ];
            continue;
        }

        $valid_sources = [];

        foreach ( $source_ids as $source_id ) {
            if ( isset( $source_map[ $source_id ] ) ) {
                $valid_sources[] = $source_map[ $source_id ];
            }
        }

        if ( empty( $valid_sources ) ) {
            $replacements[] = [
                'target' => $citation_node,
                'node'   => $document->createTextNode( '' ),
            ];
            continue;
        }

        $sup = $document->createElement( 'sup' );
        $sup->setAttribute( 'class', 'cr-citation-ref not-prose' );

        $sup->appendChild( $document->createTextNode( '[' ) );

        foreach ( $valid_sources as $index => $source ) {
            if ( $index > 0 ) {
                $sup->appendChild( $document->createTextNode( ', ' ) );
            }

            $label = (string) $source['number'];

            if ( '' === $source['href'] ) {
                $sup->appendChild( $document->createTextNode( $label ) );
                continue;
            }

            $link = $document->createElement( 'a', $label );
            $link->setAttribute( 'href', $source['href'] );
            $link->setAttribute( 'class', 'cr-citation-link' );
            $sup->appendChild( $link );
        }

        $sup->appendChild( $document->createTextNode( ']' ) );

        $replacements[] = [
            'target' => $citation_node,
            'node'   => $sup,
        ];
    }

    foreach ( $replacements as $replacement ) {
        $replacement['target']->parentNode?->replaceChild( $replacement['node'], $replacement['target'] );
    }

    return $document->saveHTML() ?: $content;
}
add_filter( 'the_content', 'clean_researcher_render_citations', 20 );
