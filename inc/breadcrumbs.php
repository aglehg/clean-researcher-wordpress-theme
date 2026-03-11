<?php
/**
 * Breadcrumb trail: data builder and HTML renderer.
 * Used by both templates (header.php) and the SEO schema (seo.php).
 */

defined( 'ABSPATH' ) || exit;

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
 *
 * @param bool $compact Compact single-row mode with truncation (used in header).
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
    $item_class    = $compact ? 'inline-flex items-center gap-1.5 min-w-0' : 'inline-flex items-center gap-1.5';
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
            echo '<span class="' . esc_attr( $current_class ) . '" aria-current="page">' . esc_html( $item['name'] ) . '</span>';
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
