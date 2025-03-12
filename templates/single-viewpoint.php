<?php
/**
 * Template for displaying single viewpoint posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Viewpoints_Plugin
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

    <div id="primary" class="viewp-content-area">
        <main id="main" class="viewp-main">

            <?php while (have_posts()) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class('viewp-article'); ?>>

                    <header class="viewp-header">
                        <h1 class="viewp-title"><?php the_title(); ?></h1>

                        <?php
                        /*
                        * Commented out metadata section - can be enabled later if needed
                        * Remove comment tags to activate
                        */
                        /*
                        <div class="viewp-meta">
                            <span class="viewp-date">
                                <?php echo esc_html(get_the_date()); ?>
                            </span>

                            <span class="viewp-author">
                                <?php esc_html_e('By', 'viewpoints-plugin'); ?>
                                <?php the_author(); ?>
                            </span>

                            <?php if (has_category()) : ?>
                            <span class="viewp-categories">
                                <?php esc_html_e('Categories:', 'viewpoints-plugin'); ?>
                                <?php the_category(', '); ?>
                            </span>
                            <?php endif; ?>

                            <?php if (has_tag()) : ?>
                            <span class="viewp-tags">
                                <?php esc_html_e('Tags:', 'viewpoints-plugin'); ?>
                                <?php the_tags('', ', ', ''); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        */
                        ?>
                    </header>

                    <?php if (has_post_thumbnail()) : ?>
                        <div class="viewp-thumbnail">
                            <?php the_post_thumbnail('large', array('class' => 'viewp-featured-image')); ?>
                        </div>
                    <?php endif; ?>

                    <div class="viewp-content">
                        <?php the_content(); ?>
                    </div>

                </article>

            <?php endwhile; ?>

        </main>
    </div>

<?php
get_sidebar();