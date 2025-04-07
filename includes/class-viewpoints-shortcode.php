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
		// viewpoints_plugin_log('Initializing Viewpoints_Shortcode class');
	}

	/**
	 * Register the shortcode.
	 *
	 * @since    1.0.0
	 */
	public function register() {
		// viewpoints_plugin_log('Registering viewpoints shortcode');
		add_shortcode($this->shortcode_tag, array($this, 'shortcode_callback'));

		// Register breadcrumbs shortcode
		add_shortcode('viewpoint_breadcrumbs', array($this, 'breadcrumbs_shortcode_callback'));

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
		// viewpoints_plugin_log('Shortcode callback triggered with attributes: ' . print_r($atts, true));

		// Track that shortcode is being used
		$this->shortcode_used = true;

		// Parse attributes
		$atts = shortcode_atts(array(
			'count' => -1,                // Number of viewpoints to display (-1 for all)
			'orderby' => 'date',          // Order by parameter
			'order' => 'DESC',            // Order direction
			'category' => '',             // Category slug or ID
			'tag' => '',                  // Tag slug or ID
			'include' => '',              // Include specific viewpoints by ID
			'exclude' => '',              // Exclude specific viewpoints by ID
			'offset' => 0,                // Offset for query
			'columns' => 3,               // Number of columns (1, 2, 3, or 4)
			'class' => '',                // Additional CSS class
			'paged' => true,              // Whether to enable pagination
		), $atts, $this->shortcode_tag);

		// Validate columns (must be 1, 2, 3, or 4)
		$columns = intval($atts['columns']);
		if ($columns < 1 || $columns > 4) {
			$columns = 2; // Default to 2 columns if invalid
		}
		
		// Note: Column value is now respected from shortcode parameter
		
		// Get current page for pagination
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		
		// Set up query arguments
		$args = array(
			'post_type' => 'viewpoints',
			'posts_per_page' => $atts['count'],
			'orderby' => $atts['orderby'],
			'order' => $atts['order'],
			'post_status' => 'publish',
		);
		
		// Add pagination if enabled
		if ($atts['paged']) {
			$args['paged'] = $paged;
		}

		// Add category parameter if set
		if (!empty($atts['category'])) {
			if (is_numeric($atts['category'])) {
				$args['cat'] = $atts['category'];
			} else {
				$args['category_name'] = $atts['category'];
			}
		}

		// Add tag parameter if set
		if (!empty($atts['tag'])) {
			if (is_numeric($atts['tag'])) {
				$args['tag_id'] = $atts['tag'];
			} else {
				$args['tag'] = $atts['tag'];
			}
		}

		// Add include parameter if set
		if (!empty($atts['include'])) {
			$args['post__in'] = explode(',', $atts['include']);
		}

		// Add exclude parameter if set
		if (!empty($atts['exclude'])) {
			$args['post__not_in'] = explode(',', $atts['exclude']);
		}

		// Add offset parameter if set
		if (!empty($atts['offset'])) {
			$args['offset'] = $atts['offset'];
		}

		// Run the query
		$query = new WP_Query($args);

		// Start output buffering
		ob_start();

		// Include the template file
		include(VIEWPOINTS_PLUGIN_TEMPLATES_DIR . 'viewpoints-grid.php');

		// Return the buffered output
		return ob_get_clean();
	}

	/**
	 * Breadcrumbs shortcode callback
	 *
	 * Outputs a breadcrumb trail in the format: Home > Viewpoints > Current Post Title
	 *
	 * @since 1.0.0
	 * @return string Breadcrumbs HTML
	 */
	public function breadcrumbs_shortcode_callback() {
		ob_start();

		$home_url = home_url();
		$viewpoints_label = 'Viewpoints';

		// Since archive is disabled, create a fallback URL for the Viewpoints link
		// Use the site URL + /viewpoints/ as the fallback
		$viewpoints_archive_url = home_url('/viewpoints/');

		// Start breadcrumbs container
		echo '<div class="vp-breadcrumbs">';

		// Home link
		echo '<a href="' . esc_url($home_url) . '">Home</a>';
		echo '<span class="vp-breadcrumb-divider"> / </span>'; // Changed from &gt; to / with spaces

		if (is_singular('viewpoints')) {
			// Viewpoints archive link
			echo '<a href="' . esc_url($viewpoints_archive_url) . '">' . esc_html($viewpoints_label) . '</a>';
			echo '<span class="vp-breadcrumb-divider"> / </span>'; // Changed from &gt; to / with spaces

			// Current post
			echo '<span class="breadcrumb_last">' . esc_html(get_the_title()) . '</span>';
		}
		elseif (is_post_type_archive('viewpoints')) {
			// On archive page, just show Viewpoints as current
			echo '<span class="breadcrumb_last">' . esc_html($viewpoints_label) . '</span>';
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Detect if shortcode is used on the current page.
	 *
	 * @since    1.0.0
	 */
	public function detect_shortcode_usage() {
		global $post;
		if (is_singular() && is_a($post, 'WP_Post') && has_shortcode($post->post_content, $this->shortcode_tag)) {
			// viewpoints_plugin_log('Shortcode detected in post content');
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
