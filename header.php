<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="profile" href="https://gmpg.org/xfn/11">
  <?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-white text-gray-900 antialiased' ); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'clean-researcher' ); ?></a>

<header class="border-b border-gray-200 px-6 py-4">
  <div class="clean-researcher-frame flex items-center justify-between gap-4">
    <a class="font-title text-lg font-bold no-underline text-gray-900 hover:opacity-75 transition-opacity duration-150"
       href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
      <?php bloginfo( 'name' ); ?>
    </a>
    <?php if ( has_nav_menu( 'primary' ) ) : ?>
    <nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'clean-researcher' ); ?>">
      <?php wp_nav_menu( [ 'theme_location' => 'primary', 'menu_class' => '', 'container' => false, 'depth' => 1 ] ); ?>
    </nav>
    <?php endif; ?>
  </div>
</header>

<div id="main-content" class="clean-researcher-shell">
