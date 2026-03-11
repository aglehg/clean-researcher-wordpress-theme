<?php
/**
 * Clean Researcher – functions.php
 *
 * Thin bootstrap file. All logic lives in inc/ and is loaded here in
 * dependency order: setup → enqueue → breadcrumbs → seo → user-profile → customizer.
 */

defined( 'ABSPATH' ) || exit;

require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/enqueue.php';
require get_template_directory() . '/inc/breadcrumbs.php';
require get_template_directory() . '/inc/seo.php';
require get_template_directory() . '/inc/user-profile.php';
require get_template_directory() . '/inc/customizer.php';

