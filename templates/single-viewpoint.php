<?php
/**
 * Template for displaying single viewpoints posts
 *
 * @package Viewpoints_Plugin
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>
    <main id="main" class="<?php echo esc_attr(flatsome_main_classes()); ?>">
        <!-- Header Block (full width) -->
        <div class="vp-section-wrapper vp-viewpoint-header">
			<?php echo do_shortcode('[block id="single-viewpoint-header"]'); ?>
        </div>

        <!-- Start the Loop -->
		<?php while (have_posts()) : the_post(); ?>

            <!-- Bio Section with Featured Image -->
            <div class="vp-bio-section">
                <div class="row">
                    <!-- Bio Content Column (Left) -->
                    <div class="large-8 medium-8 small-12 col vp-bio-column">
                        <div class="vp-bio-content">
							<?php
							// Display the full bio content
							if (function_exists('get_field') && $viewpoint_bio = get_field('viewpoint_bio')) {
								echo wp_kses_post($viewpoint_bio);
							}

							// Add line break
							echo '<hr class="vp-content-divider">';

							// Display the full excerpt content
							if (function_exists('get_field') && $viewpoint_excerpt = get_field('viewpoint_excerpt')) {
								echo wp_kses_post($viewpoint_excerpt);
							} elseif (has_excerpt()) {
								the_excerpt();
							}
							?>
                        </div>
                    </div>
                    <!-- Featured Image Column (Right) -->
                    <div class="large-4 medium-4 small-12 col vp-featured-image-column">
						<?php if (has_post_thumbnail()) : ?>
                            <div class="vp-featured-image">
								<?php the_post_thumbnail('large', array('class' => 'vp-profile-image')); ?>
                            </div>
						<?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Repeater Content Section -->
			<?php if (function_exists('get_field') && have_rows('content')) : ?>
                <div class="vp-content-section container">
					<?php while (have_rows('content')) : the_row(); ?>
                        <div class="vp-content-row">
							<?php if ($heading = get_sub_field('heading')) : ?>
                                <h4 class="vp-content-heading">
									<?php echo esc_html($heading); ?>
                                </h4>
							<?php endif; ?>
							<?php if ($content = get_sub_field('content')) : ?>
                                <div class="vp-content-body">
									<?php
									// Output the WYSIWYG content with all formatting intact
									// Images will maintain their alignment as set in the editor
									echo wp_kses_post($content);
									?>
                                </div>
							<?php endif; ?>
                        </div>
					<?php endwhile; ?>
                </div>
			<?php endif; ?>

            <!-- Post Navigation -->
            <nav class="es-employer-story-navigation container">
                <div class="es-nav-links">
                    <div class="es-nav-button es-nav-previous">
						<?php if (get_previous_post()) : ?>
							<?php previous_post_link('%link', 'See Previous'); ?>
						<?php else: ?>
                            <span class="empty-nav-button"></span>
						<?php endif; ?>
                    </div>
                    <div class="es-nav-button es-nav-all">
                        <a href="<?php echo esc_url(home_url('/viewpoints/')); ?>">See All</a>
                    </div>
                    <div class="es-nav-button es-nav-next">
						<?php if (get_next_post()) : ?>
							<?php next_post_link('%link', 'See Next'); ?>
						<?php else: ?>
                            <span class="empty-nav-button"></span>
						<?php endif; ?>
                    </div>
                </div>
            </nav>

		<?php endwhile; // End of the loop. ?>
    </main>
<?php get_footer(); ?>
