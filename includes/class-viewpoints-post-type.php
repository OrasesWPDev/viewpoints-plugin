<?php
/**
 * Custom Post Type functionality for Viewpoints.
 *
 * This class registers and manages the Viewpoints custom post type,
 * including synchronization with ACF Pro and proper template handling.
 *
 * @since      1.0.0
 */
class Viewpoints_Post_Type {

    /**
     * The name of the custom post type.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $post_type    The name of the custom post type.
     */
    protected $post_type = 'viewpoints';

    /**
     * The path to the JSON files directory.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $json_dir    Path to the JSON files directory.
     */
    protected $json_dir;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // viewpoints_plugin_log('Initializing Viewpoints_Post_Type class');
        $this->json_dir = VIEWPOINTS_PLUGIN_DIR . 'acf-json/';
    }

    /**
     * Register hooks related to the custom post type.
     *
     * @since    1.0.0
     */
    public function register() {
        // viewpoints_plugin_log('Registering Viewpoints post type hooks');

        // Add ACF JSON loading point
        add_filter('acf/settings/load_json', array($this, 'add_acf_json_load_point'));

        // Ensure directories exist
        $this->ensure_json_directory();

        // Register the post type with WordPress
        add_action('init', array($this, 'register_post_type'), 10);

        // Add hooks for post type functioning
        add_filter('template_include', array($this, 'viewpoint_template'), 99);

        // Check if post type exists (for logging purposes)
        add_action('init', array($this, 'check_post_type_exists'), 20);
    }

    /**
     * Register the Viewpoints custom post type.
     *
     * This method registers the custom post type with WordPress using the settings
     * saved in the ACF JSON file or fallback settings if needed.
     *
     * @since    1.0.0
     */
    public function register_post_type() {
        // viewpoints_plugin_log('Registering viewpoints post type');

        // Get settings from JSON or fallback
        $settings = $this->get_post_type_settings();

        // Register the post type
        $result = register_post_type($this->post_type, $settings);

        // Check for errors
        if (is_wp_error($result)) {
            // viewpoints_plugin_log('Error registering post type: ' . $result->get_error_message(), 'error');
        } else {
            // viewpoints_plugin_log('Post type registered successfully');
        }
    }

    /**
     * Get post type settings from JSON or fallback.
     *
     * @since    1.0.0
     * @return   array    Post type settings.
     */
    protected function get_post_type_settings() {
        // JSON file path (following ACF's naming convention)
        $json_file = $this->json_dir . 'post_type_viewpoints.json';

        // Check if JSON file exists
        if (file_exists($json_file)) {
            // Read and decode JSON file
            $json_content = file_get_contents($json_file);
            $settings = json_decode($json_content, true);

            // Validate JSON data
            if (json_last_error() === JSON_ERROR_NONE && is_array($settings)) {
                // viewpoints_plugin_log('Successfully loaded post type settings from JSON');
                return $settings;
            }

            // viewpoints_plugin_log('Invalid JSON in post type file: ' . json_last_error_msg(), 'error');
        } else {
            // viewpoints_plugin_log('Post type JSON file not found: ' . $json_file, 'warning');
        }

        // Fallback settings
        return array(
            'labels' => array(
                'name' => 'Viewpoints',
                'singular_name' => 'Viewpoints',
            ),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields'),
            'taxonomies' => array('category', 'block_categories', 'post_tag'),
            'rewrite' => array(
                'slug' => 'viewpoints',
                'with_front' => false
            ),
            'has_archive' => true,
        );
    }

    /**
     * Add local JSON loading point for ACF.
     *
     * @since    1.0.0
     * @param    array    $paths    Existing load paths.
     * @return   array              Modified paths including our plugin's directory.
     */
    public function add_acf_json_load_point($paths) {
        // viewpoints_plugin_log('Adding ACF JSON load point: ' . $this->json_dir);
        $paths[] = $this->json_dir;
        return $paths;
    }

    /**
     * Check if the local JSON directory exists and create it if needed
     *
     * @since    1.0.0
     * @return   bool     Whether the directory exists or was created successfully
     */
    public function ensure_json_directory() {
        if (!file_exists($this->json_dir)) {
            // viewpoints_plugin_log('Creating ACF JSON directory at: ' . $this->json_dir);

            $created = wp_mkdir_p($this->json_dir);

            if (!$created) {
                // viewpoints_plugin_log('Failed to create ACF JSON directory', 'error');
                return false;
            }

            // Add index.php for security
            $index_file = $this->json_dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
                // viewpoints_plugin_log('Created index.php protection file in ACF JSON directory');
            }

            return true;
        }

        return true;
    }

    /**
     * Check if the post type exists, if not log a warning.
     *
     * @since    1.0.0
     * @return   bool              Whether the post type exists.
     */
    public function check_post_type_exists() {
        $exists = post_type_exists($this->post_type);

        if (!$exists) {
            // viewpoints_plugin_log('Viewpoints post type does not exist yet - waiting for sync', 'warning');
        } else {
            // viewpoints_plugin_log('Viewpoints post type exists and is registered');
        }

        return $exists;
    }

    /**
     * Include custom template for single viewpoint.
     *
     * @since    1.0.0
     * @param    string   $template    Current template path.
     * @return   string                Modified template path.
     */
    public function viewpoint_template($template) {
        // Only modify for single viewpoint
        if (is_singular($this->post_type)) {
            // viewpoints_plugin_log('Loading single viewpoint template');

            // Check for theme template first
            $theme_template = locate_template(array('single-' . $this->post_type . '.php'));

            if ($theme_template) {
                // viewpoints_plugin_log('Using theme template: ' . $theme_template);
                return $theme_template;
            }

            // Fall back to plugin template
            $plugin_template = VIEWPOINTS_PLUGIN_TEMPLATES_DIR . 'single-viewpoint.php';

            if (file_exists($plugin_template)) {
                // viewpoints_plugin_log('Using plugin template: ' . $plugin_template);
                return $plugin_template;
            }
        }

        return $template;
    }
}
