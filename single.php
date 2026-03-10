<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<div>

  <!-- Main article -->
  <article id="post-<?php the_ID(); ?>" <?php post_class( 'clean-researcher-content mx-auto' ); ?>>

    <header class="mb-10">
      <h1 class="font-title text-[clamp(1.5rem,4vw,2rem)] font-bold leading-tight mb-3">
        <?php the_title(); ?>
      </h1>
      <div class="flex items-center gap-2 text-sm text-gray-500 border-b border-gray-200 pb-5 mb-8">
        <i class="fa-regular fa-user text-xs" aria-hidden="true"></i>
        <span><?php echo esc_html( get_the_author() ); ?></span>
        <span class="text-gray-300">&bull;</span>
        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
      </div>
      <?php if ( has_excerpt() ) : ?>
      <p class="text-gray-600 leading-relaxed mb-0"><?php the_excerpt(); ?></p>
      <?php endif; ?>

      <?php if ( has_post_thumbnail() ) : ?>
      <figure class="mt-8 mb-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
        <?php
        the_post_thumbnail(
          'clean-researcher-featured',
          [
            'class'   => 'block w-full h-auto',
            'loading' => 'eager',
            'decoding' => 'async',
            'fetchpriority' => 'high',
            'sizes' => esc_attr( clean_researcher_featured_image_sizes() ),
          ]
        );
        ?>
      </figure>
      <?php endif; ?>
    </header>

    <div class="prose prose-gray max-w-none prose-headings:font-title">
      <?php the_content(); ?>
    </div>

    <?php
    $tags_list = get_the_tag_list(
      '<span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">',
      '</span> <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">',
      '</span>'
    );
    ?>
    <?php if ( $tags_list ) : ?>
    <div class="mt-10 border-t border-gray-200 pt-6">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">
        <?php esc_html_e( 'Tags', 'clean-researcher' ); ?>
      </h2>
      <div class="flex flex-wrap gap-2 text-sm">
        <?php echo wp_kses_post( $tags_list ); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php wp_link_pages( [ 'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'clean-researcher' ), 'after' => '</div>' ] ); ?>

  </article>

</div>

<!-- Desktop TOC rail (outside content frame) -->
<aside class="toc-rail hidden xl:block"
       data-toc-sidebar
       aria-label="<?php esc_attr_e( 'Table of contents', 'clean-researcher' ); ?>">
  <div class="toc-scroll max-h-[calc(100vh-8rem)] overflow-y-auto">
    <div class="toc-panel bg-gray-50 border border-gray-200 rounded p-5">
      <div class="toc-header flex items-center justify-between gap-3 mb-3.5">
        <p class="toc-title text-[0.72rem] font-bold uppercase tracking-widest text-gray-500 mb-0">
          <?php esc_html_e( 'Table of Contents', 'clean-researcher' ); ?>
        </p>
        <button type="button"
                class="toc-collapse-btn inline-flex items-center justify-center w-7 h-7 rounded border border-gray-200 bg-white text-gray-500 hover:text-gray-900 transition-colors duration-150"
                data-toc-collapse-btn
                aria-controls="toc-nav"
                aria-expanded="true"
                aria-label="<?php esc_attr_e( 'Minimize table of contents', 'clean-researcher' ); ?>">
          <i class="fa-solid fa-arrow-left text-xs" data-toc-collapse-icon aria-hidden="true"></i>
        </button>
      </div>
      <nav id="toc-nav"><ol class="toc-list list-none p-0 m-0"></ol></nav>
    </div>
  </div>
</aside>

<!-- Mobile TOC toggle button -->
<button class="fixed top-4 right-4 z-50 xl:hidden flex items-center justify-center w-10 h-10 bg-white border border-gray-200 rounded shadow-sm cursor-pointer toc-mobile-btn"
        aria-label="<?php esc_attr_e( 'Open table of contents', 'clean-researcher' ); ?>"
        aria-expanded="false" aria-controls="toc-drawer">
  <i class="fa-solid fa-bars text-sm" aria-hidden="true"></i>
</button>

<div class="fixed inset-0 bg-black/25 z-40 hidden" id="toc-overlay" aria-hidden="true"></div>

<div class="toc-scroll toc-drawer fixed top-0 right-0 w-[min(24rem,88vw)] h-full bg-white border-l border-gray-200 shadow-lg z-50 overflow-y-auto px-6 pt-14 pb-8"
     id="toc-drawer" aria-hidden="true">
  <p class="toc-title text-[0.72rem] font-bold uppercase tracking-widest text-gray-500 mb-3.5">
    <?php esc_html_e( 'Contents', 'clean-researcher' ); ?>
  </p>
  <nav aria-label="<?php esc_attr_e( 'Table of contents', 'clean-researcher' ); ?>">
    <ol class="toc-list list-none p-0 m-0" id="toc-drawer-list"></ol>
  </nav>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>
