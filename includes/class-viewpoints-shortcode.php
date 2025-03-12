<?php
/**
 * Shortcode functionality for Viewpoints.
 *
 * This class registers and handles the shortcode for displaying viewpoints.
 *
 * @since      1.0.0
 */
class Viewpoints_Shortcode {

    /**
     * The shortcode tag.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $shortcode_tag    The shortcode tag.
     */
    protected $shortcode_tag = 'viewpoints';

    /**
     * Flag to track if shortcode is used on current page.
     *
     * @since    1.0.0
     * @access   protected
     * @var      bool    $shortcode_used    Whether the shortcode is used on the current page.
     */
    protected $shortcode_used = false;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        viewpoints_plugin_log('Initializing Viewpoints_Shortcode class');
    }

    /**
     * Register the shortcode.
     *
     * @since    1.0.0
     */
    public function register() {
        viewpoints_plugin_log('Registering viewpoints shortcode');
        add_shortcode($this->shortcode_tag, array($this, 'shortcode_callback'));

        // This action helps detect if shortcode is used
        add_action('wp', array($this, 'detect_shortcode_usage'));
    }

    /**
     * Callback function for the shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts      Shortcode attributes.
     * @param    string   $content   Content between shortcode tags.
     * @return   string              Shortcode output.
     */
    public function shortcode_callback($atts, $content = null) {
        viewpoints_plugin_log('Shortcode callback triggered with attributes: ' . print_r($atts, true));

        // Track that shortcode is being used
        $this->shortcode_used = true;

        // Parse attributes
        $atts = shortcode_atts(array(
            'count' => 5,                 // Number of viewpoints to display
            'orderby' => 'date',          // Order by parameter
            'order' => 'DESC',            // Order direction
            'category' => '',             // Category slug
        ), $atts, $this->shortcode_tag);

        // Validate and sanitize attributes
        $count = absint($atts['count']);
        $orderby = in_array($atts['orderby'], array('date', 'title', 'modified', 'menu_order')) ? $atts['orderby'] : 'date';
        $order = in_array(strtoupper($atts['order']), array('ASC', 'DESC')) ? strtoupper($atts['order']) : 'DESC';

        // Set up query arguments
        $args = array(
            'post_type' => 'viewpoint',
            'posts_per_page' => $count,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        );

        // Add category if specified
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['category']),
                ),
            );
        }

        viewpoints_plugin_log('Query arguments: ' . print_r($args, true));

        // Get viewpoints
        $viewpoints_query = new WP_Query($args);

        // Start output buffering
        ob_start();

        // Check if we have viewpoints
        if ($viewpoints_query->have_posts()) {
            echo '<div class="viewp-container">';

            while ($viewpoints_query->have_posts()) {
                $viewpoints_query->the_post();

                // Get featured image URL
                $featured_image = '';
                if (has_post_thumbnail()) {
                    $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                }

                echo '<div class="viewp-item">';

                // Display featured image if available
                if ($featured_image) {
                    echo '<div class="viewp-thumbnail">';
                    echo '<img src="' . esc_url($featured_image) . '" alt="' . esc_attr(get_the_title()) . '" />';
                    echo '</div>';
                }

                // Display title
                echo '<h2 class="viewp-title">';
                echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
                echo '</h2>';

                // Display content
                echo '<div class="viewp-content">';
                the_content();
                echo '</div>';

                echo '</div>'; // End .viewp-item
            }

            echo '</div>'; // End .viewp-container

            // Restore original post data
            wp_reset_postdata();
        } else {
            echo '<p class="viewp-no-results">No viewpoints found.</p>';
        }

        $output = ob_get_clean();
        viewpoints_plugin_log('Shortcode output generated');

        return $output;
    }

    /**
     * Detect if shortcode is used on the current page.
     *
     * @since    1.0.0
     */
    public function detect_shortcode_usage() {
        global $post;

        if (is_singular() && is_a($post, 'WP_Post') && has_shortcode($post->post_content, $this->shortcode_tag)) {
            viewpoints_plugin_log('Shortcode detected in post content');
            $this->shortcode_used = true;
        }
    }

    /**
     * Check if the shortcode is used on the current page.
     *
     * @since    1.0.0
     * @return   bool     Whether the shortcode is used.
     */
    public function is_shortcode_used() {
        return $this->shortcode_used;
    }
}