</div><!-- /#main-content -->

<footer class="border-t border-gray-200 px-6 py-8 text-sm text-gray-500 text-center">
  <p>
    &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
    <a class="hover:text-gray-900 transition-colors" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>.
    <?php printf( esc_html__( 'Powered by %s.', 'clean-researcher' ), '<a class="hover:text-gray-900 transition-colors" href="https://wordpress.org">WordPress</a>' ); ?>
  </p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
