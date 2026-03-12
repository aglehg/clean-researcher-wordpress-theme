</div><!-- /#main-content -->

<footer class="border-t border-gray-200 px-6 py-8 text-sm text-gray-500 text-center">
  <?php if ( has_nav_menu( 'footer' ) ) : ?>
  <nav class="mb-4" aria-label="<?php esc_attr_e( 'Footer menu', 'clean-researcher' ); ?>">
    <?php
    wp_nav_menu(
      [
        'theme_location' => 'footer',
        'container'      => false,
        'depth'          => 1,
        'menu_class'     => 'm-0 p-0 list-none flex flex-wrap items-center justify-center gap-4 text-sm',
      ]
    );
    ?>
  </nav>
  <?php endif; ?>

  <p>
    &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
    <a class="hover:text-gray-900 transition-colors" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>.
    <?php printf( esc_html__( 'Powered by %s.', 'clean-researcher' ), '<a class="hover:text-gray-900 transition-colors" href="https://wordpress.org">WordPress</a>' ); ?>
  </p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
