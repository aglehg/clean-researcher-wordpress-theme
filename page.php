<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<div>

  <article id="post-<?php the_ID(); ?>" <?php post_class( 'clean-researcher-content mx-auto' ); ?>>
    <header class="mb-8">
      <h1 class="font-title text-[clamp(1.5rem,4vw,2rem)] font-bold leading-tight"><?php the_title(); ?></h1>
    </header>
    <div class="prose prose-gray max-w-none prose-headings:font-title">
      <?php the_content(); ?>
    </div>
  </article>

  <?php if ( clean_researcher_show_toc_on_pages() ) : ?>
  <!-- Desktop TOC rail (outside content frame) -->
  <aside class="toc-rail hidden xl:block"
         data-toc-sidebar
         aria-label="<?php esc_attr_e( 'Table of contents', 'clean-researcher' ); ?>">
    <div class="toc-scroll max-h-[calc(100vh-8rem)] overflow-y-auto">
      <div class="toc-panel bg-gray-50 border border-gray-200 rounded p-5">
        <p class="toc-title text-[0.72rem] font-bold uppercase tracking-widest text-gray-500 mb-3.5">
          <?php esc_html_e( 'Contents', 'clean-researcher' ); ?>
        </p>
        <nav id="toc-nav"><ol class="toc-list list-none p-0 m-0"></ol></nav>
      </div>
    </div>
  </aside>
  <?php endif; ?>

</div>

<?php if ( clean_researcher_show_toc_on_pages() ) : ?>
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
<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
