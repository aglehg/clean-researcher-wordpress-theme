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
 * Return true when a URL points to a domain different from the current site.
 *
 * @param string $url URL to evaluate.
 */
function clean_researcher_is_external_source_url( string $url ): bool {
    $target_host = wp_parse_url( $url, PHP_URL_HOST );
    $site_host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

    if ( ! is_string( $target_host ) || ! is_string( $site_host ) ) {
        return false;
    }

    return strtolower( $target_host ) !== strtolower( $site_host );
}

/**
 * Ensure external source links open in a new tab.
 *
 * @param string $block_content Block HTML.
 * @param array  $block         Full block data.
 */
function clean_researcher_add_external_source_link_attrs( string $block_content, array $block ): string {
    if ( 'clean-researcher/sources' !== ( $block['blockName'] ?? '' ) || '' === trim( $block_content ) ) {
        return $block_content;
    }

    if ( ! class_exists( 'DOMDocument' ) ) {
        return $block_content;
    }

    $document = new DOMDocument();
    $previous = libxml_use_internal_errors( true );
    $encoded  = mb_encode_numericentity( $block_content, [ 0x80, 0x10ffff, 0, 0xffffff ], 'UTF-8' );
    $loaded   = $document->loadHTML( $encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors();
    libxml_use_internal_errors( $previous );

    if ( ! $loaded ) {
        return $block_content;
    }

    $links = $document->getElementsByTagName( 'a' );

    foreach ( $links as $link ) {
        if ( ! $link instanceof DOMElement ) {
            continue;
        }

        $href = trim( (string) $link->getAttribute( 'href' ) );

        if ( '' === $href || ! clean_researcher_is_external_source_url( $href ) ) {
            continue;
        }

        $link->setAttribute( 'target', '_blank' );

        $existing_rel = trim( (string) $link->getAttribute( 'rel' ) );
        $rel_parts = '' !== $existing_rel ? preg_split( '/\s+/', $existing_rel ) : [];
        $rel_parts = is_array( $rel_parts ) ? array_map( 'strtolower', $rel_parts ) : [];
        $rel_parts[] = 'noopener';
        $rel_parts[] = 'noreferrer';
        $rel_parts = array_values( array_unique( array_filter( $rel_parts ) ) );

        $link->setAttribute( 'rel', implode( ' ', $rel_parts ) );
    }

    return $document->saveHTML() ?: $block_content;
}
add_filter( 'render_block', 'clean_researcher_add_external_source_link_attrs', 20, 2 );

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
