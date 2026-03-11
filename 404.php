<?php get_header(); ?>

<section class="clean-researcher-content mx-auto">
	<article id="post-0" class="post not-found py-10">
		<header class="mb-6">
			<h1 class="font-title text-[clamp(1.5rem,4vw,2rem)] font-bold leading-tight mb-2">
				<?php esc_html_e( 'Page Not Found', 'clean-researcher' ); ?>
			</h1>
			<p class="text-gray-600 leading-relaxed">
				<?php esc_html_e( 'The page you requested could not be found. Try searching, or use one of the links below.', 'clean-researcher' ); ?>
			</p>
		</header>

		<div class="mb-8">
			<?php get_search_form(); ?>
		</div>

		<div class="grid gap-4 sm:grid-cols-3">
			<a class="inline-flex items-center justify-center px-4 py-2 rounded border border-gray-200 text-gray-800 no-underline hover:bg-gray-900 hover:text-white transition-colors" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Go to Homepage', 'clean-researcher' ); ?>
			</a>
			<a class="inline-flex items-center justify-center px-4 py-2 rounded border border-gray-200 text-gray-800 no-underline hover:bg-gray-900 hover:text-white transition-colors" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Browse Articles', 'clean-researcher' ); ?>
			</a>
			<a class="inline-flex items-center justify-center px-4 py-2 rounded border border-gray-200 text-gray-800 no-underline hover:bg-gray-900 hover:text-white transition-colors" href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>">
				<?php esc_html_e( 'View Sitemap', 'clean-researcher' ); ?>
			</a>
		</div>
	</article>
</section>

<?php get_footer(); ?>