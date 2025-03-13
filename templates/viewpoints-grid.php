<?php
/**
 * Template for displaying viewpoints in a grid layout
 *
 * @package Viewpoints_Plugin
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add container class
$container_class = 'vp-grid-container';
if (!empty($atts['class'])) {
    $container_class .= ' ' . esc_attr($atts['class']);
}

// Add columns class
$container_class .= ' vp-columns-' . $columns;
?>

<div class="<?php echo esc_attr($container_class); ?>">
    <?php if ($query->have_posts()) : ?>
        <div class="vp-grid">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                // Get post data
                $post_id = get_the_ID();
                $title = get_the_title();
                $permalink = get_permalink();
                
                // Get the custom excerpt field
                $excerpt = '';
                if (function_exists('get_field')) {
                    $excerpt = get_field('viewpoint_excerpt', $post_id);
                }
                ?>
                
                <div class="vp-grid-item">
                    <a href="<?php echo esc_url($permalink); ?>" class="vp-grid-item-link">
                        <div class="vp-grid-item-inner">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="vp-grid-thumbnail">
                                    <?php 
                                    // Display featured image at 350x350
                                    echo get_the_post_thumbnail($post_id, array(350, 350), array(
                                        'class' => 'vp-thumbnail',
                                        'alt' => esc_attr($title)
                                    )); 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="vp-grid-content">
                                <div class="vp-grid-excerpt">
                                    <?php 
                                    // Get the viewpoint bio instead of using the title
                                    $bio = '';
                                    if (function_exists('get_field')) {
                                        $bio = get_field('viewpoint_bio', $post_id);
                                        if (empty($bio)) {
                                            // Fallback to excerpt if bio is empty
                                            $bio = $excerpt;
                                        }
                                    }
                                    
                                    // Output the bio content
                                    echo $bio;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($atts['paged'] && $query->max_num_pages > 1) : ?>
            <div class="vp-pagination">
                <?php
                $big = 999999999; // need an unlikely integer
                echo paginate_links(array(
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $query->max_num_pages,
                    'prev_text' => '&laquo; Previous',
                    'next_text' => 'Next &raquo;',
                ));
                ?>
            </div>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p class="vp-no-results"><?php _e('No viewpoints found.', 'viewpoints-plugin'); ?></p>
    <?php endif; ?>
</div>
