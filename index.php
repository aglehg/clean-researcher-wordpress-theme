<?php get_header(); ?>

<div class="clean-researcher-content">

  <?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'py-8 border-b border-gray-200 first:pt-0' ); ?>>
      <h2 class="font-title text-xl font-bold mb-1.5">
        <a class="no-underline hover:underline text-gray-900" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
      </h2>
      <div class="flex items-center gap-2 text-[0.8125rem] text-gray-500 mb-2.5">
        <span><?php echo esc_html( get_the_author() ); ?></span>
        <span class="text-gray-300">&bull;</span>
        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
      </div>
      <?php if ( has_excerpt() || get_the_excerpt() ) : ?>
      <p class="text-[0.9375rem] text-gray-600 leading-relaxed">
        <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 30 ) ); ?>
      </p>
      <?php endif; ?>
      <a class="inline-flex items-center gap-1.5 mt-3 text-sm font-medium text-gray-900 no-underline hover:opacity-70 transition-opacity"
        href="<?php the_permalink(); ?>"
        aria-label="<?php echo esc_attr( sprintf( __( 'Read more about %s', 'clean-researcher' ), get_the_title() ) ); ?>">
        <?php esc_html_e( 'Read more', 'clean-researcher' ); ?>
        <span class="text-xs leading-none" aria-hidden="true">&rarr;</span>
      </a>
    </article>

    <?php endwhile; ?>

    <div class="pagination flex flex-wrap gap-2 mt-12">
      <?php echo paginate_links( [ 'prev_text' => '<span class="text-xs leading-none" aria-hidden="true">&larr;</span><span>' . esc_html__( 'Previous page', 'clean-researcher' ) . '</span>', 'next_text' => '<span>' . esc_html__( 'Next page', 'clean-researcher' ) . '</span><span class="text-xs leading-none" aria-hidden="true">&rarr;</span>' ] ); // phpcs:ignore ?>
    </div>

  <?php else : ?>
    <p class="text-gray-500"><?php esc_html_e( 'No posts found.', 'clean-researcher' ); ?></p>
  <?php endif; ?>

</div>

<?php get_footer(); ?>
