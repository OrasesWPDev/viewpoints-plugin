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
     * The option name for storing post type settings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $option_name    Option name for storing post type settings.
     */
    protected $option_name = 'viewpoints_post_type_settings';

    /**
     * The path to the JSON files directory.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $json_dir    Path to the JSON files directory.
     */
    protected $json_dir;

    /**
     * The path to the post type JSON definition file.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $post_type_json_file    Path to the post type JSON file.
     */
    protected $post_type_json_file;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        viewpoints_plugin_log('Initializing Viewpoints_Post_Type class');
        $this->json_dir = VIEWPOINTS_PLUGIN_DIR . 'acf-json/';
        $this->post_type_json_file = $this->json_dir . 'post-type-viewpoints.json';
    }

    /**
     * Register hooks related to the custom post type.
     *
     * @since    1.0.0
     */
    public function register() {
        viewpoints_plugin_log('Registering Viewpoints post type hooks');

        // Add ACF JSON loading point
        add_filter('acf/settings/load_json', array($this, 'add_acf_json_load_point'));

        // Listen for field group sync completion
        add_action('acf/include_fields', array($this, 'on_fields_sync'));

        // Ensure directories exist
        $this->ensure_json_directory();

        // Register the post type on init (priority 10)
        add_action('init', array($this, 'register_post_type'), 10);

        // Register with ACF Pro (must be after ACF Pro loads)
        add_action('acf/init', array($this, 'register_with_acf'), 15);

        // Add hooks for post type functioning
        add_filter('template_include', array($this, 'viewpoint_template'), 99);

        // Check if post type exists (for logging purposes)
        add_action('init', array($this, 'check_post_type_exists'), 20); // After registration (priority 10)

        // Hook for syncing with ACF changes
        add_action('acf/save_post_type', array($this, 'sync_from_acf'), 20, 1);
    }

    /**
     * Register the Viewpoints custom post type.
     *
     * This method registers the custom post type with WordPress using the settings
     * provided or retrieved from the stored options (for ACF Pro sync).
     *
     * @since    1.0.0
     */
    public function register_post_type() {
        viewpoints_plugin_log('Registering viewpoints post type');

        // Get settings from options (for ACF sync) or use defaults from JSON
        $settings = get_option($this->option_name, array());

        if (empty($settings)) {
            viewpoints_plugin_log('Using default post type settings from JSON file');
            $settings = $this->get_default_post_type_settings();
        } else {
            viewpoints_plugin_log('Using stored post type settings from database');
        }

        // Register the post type
        $result = register_post_type($this->post_type, $settings);

        // Check for errors
        if (is_wp_error($result)) {
            viewpoints_plugin_log('Error registering post type: ' . $result->get_error_message(), 'error');
        } else {
            viewpoints_plugin_log('Post type registered successfully');

            // Store settings for future comparison (for sync)
            update_option($this->option_name, $settings);
        }

        // Maybe flush rewrite rules if this is a new registration
        if (get_option('viewpoints_plugin_flush_needed', false)) {
            viewpoints_plugin_log('Flushing rewrite rules after registration');
            flush_rewrite_rules();
            delete_option('viewpoints_plugin_flush_needed');
        }
    }

    /**
     * Get default post type settings from JSON file.
     *
     * These default settings are loaded from a JSON file and can be
     * overridden by ACF Pro changes.
     *
     * @since    1.0.0
     * @return   array    Default post type settings.
     */
    protected function get_default_post_type_settings() {
        // Check if JSON file exists
        if (!file_exists($this->post_type_json_file)) {
            viewpoints_plugin_log('Post type JSON file not found: ' . $this->post_type_json_file, 'error');
            return $this->get_fallback_post_type_settings();
        }

        // Read and decode JSON file
        $json_content = file_get_contents($this->post_type_json_file);
        $settings = json_decode($json_content, true);

        // Validate JSON data
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings)) {
            viewpoints_plugin_log('Invalid JSON in post type file: ' . json_last_error_msg(), 'error');
            return $this->get_fallback_post_type_settings();
        }

        viewpoints_plugin_log('Successfully loaded post type settings from JSON');
        return $settings;
    }

    /**
     * Fallback settings in case JSON file can't be loaded.
     *
     * This is a minimal configuration that ensures the post type works
     * even if the JSON file is missing or invalid.
     *
     * @since    1.0.0
     * @return   array    Fallback post type settings.
     */
    protected function get_fallback_post_type_settings() {
        viewpoints_plugin_log('Using fallback post type settings');

        return array(
            'labels' => array(
                'name' => 'Viewpoints',
                'singular_name' => 'Viewpoints',
            ),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title'),
            'rewrite' => array(
                'slug' => 'viewpoints',
                'with_front' => false
            ),
            'has_archive' => true,
        );
    }

    /**
     * Sync post type settings from ACF Pro.
     *
     * This method is called when post type settings are saved in ACF Pro
     * and updates the stored settings for future registrations.
     *
     * @since    1.0.0
     * @param    array    $args    Post type arguments from ACF.
     */
    public function sync_from_acf($args) {
        // Only process our specific post type
        if (!isset($args['post_type']) || $args['post_type'] !== $this->post_type) {
            return;
        }

        viewpoints_plugin_log('Syncing post type settings from ACF Pro');

        // Update stored settings
        update_option($this->option_name, $args);

        // Optionally write back to JSON file for two-way sync
        $this->maybe_update_json_file($args);

        // Flag that rewrite rules should be flushed
        update_option('viewpoints_plugin_flush_needed', true);

        viewpoints_plugin_log('Post type settings synced from ACF Pro');
    }

    /**
     * Maybe update the JSON file with new settings.
     *
     * Only updates if the file exists and is writable.
     *
     * @since    1.0.0
     * @param    array    $settings    New post type settings.
     * @return   bool                  Whether update was successful.
     */
    protected function maybe_update_json_file($settings) {
        // Ensure acf-json directory exists
        $this->ensure_json_directory();

        // Create file if it doesn't exist
        if (!file_exists($this->post_type_json_file)) {
            $result = touch($this->post_type_json_file);
            if (!$result) {
                viewpoints_plugin_log('Could not create JSON file: ' . $this->post_type_json_file, 'error');
                return false;
            }
        }

        // Check if file is writable
        if (!is_writable($this->post_type_json_file)) {
            viewpoints_plugin_log('JSON file not writable: ' . $this->post_type_json_file, 'warning');
            return false;
        }

        // Write settings to JSON file
        $json_content = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = file_put_contents($this->post_type_json_file, $json_content);

        if ($result === false) {
            viewpoints_plugin_log('Failed to write settings to JSON file', 'error');
            return false;
        }

        viewpoints_plugin_log('Updated JSON file with new settings');
        return true;
    }

    /**
     * Add local JSON loading point for ACF.
     *
     * @since    1.0.0
     * @param    array    $paths    Existing load paths.
     * @return   array              Modified paths including our plugin's directory.
     */
    public function add_acf_json_load_point($paths) {
        viewpoints_plugin_log('Adding ACF JSON load point: ' . $this->json_dir);
        $paths[] = $this->json_dir;
        return $paths;
    }

    /**
     * Callback for when fields are included/synced
     *
     * @since    1.0.0
     */
    public function on_fields_sync() {
        viewpoints_plugin_log('ACF fields have been included/synced - checking post type status');

        // This is a good place to flush rewrite rules after post type registration
        if (post_type_exists($this->post_type)) {
            viewpoints_plugin_log('Post type exists after sync, flushing rewrite rules');
            flush_rewrite_rules();
        }
    }

    /**
     * Check if the local JSON directory exists and create it if needed
     *
     * @since    1.0.0
     * @return   bool     Whether the directory exists or was created successfully
     */
    public function ensure_json_directory() {
        if (!file_exists($this->json_dir)) {
            viewpoints_plugin_log('Creating ACF JSON directory at: ' . $this->json_dir);

            $created = wp_mkdir_p($this->json_dir);

            if (!$created) {
                viewpoints_plugin_log('Failed to create ACF JSON directory', 'error');
                return false;
            }

            // Add index.php for security
            $index_file = $this->json_dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
                viewpoints_plugin_log('Created index.php protection file in ACF JSON directory');
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
            viewpoints_plugin_log('Viewpoints post type does not exist yet - waiting for sync', 'warning');
        } else {
            viewpoints_plugin_log('Viewpoints post type exists and is registered');
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
            viewpoints_plugin_log('Loading single viewpoint template');

            // Check for theme template first
            $theme_template = locate_template(array('single-' . $this->post_type . '.php'));

            if ($theme_template) {
                viewpoints_plugin_log('Using theme template: ' . $theme_template);
                return $theme_template;
            }

            // Fall back to plugin template
            $plugin_template = VIEWPOINTS_PLUGIN_TEMPLATES_DIR . 'single-' . $this->post_type . '.php';

            if (file_exists($plugin_template)) {
                viewpoints_plugin_log('Using plugin template: ' . $plugin_template);
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Register this post type with ACF Pro.
     *
     * @since    1.0.0
     */
    public function register_with_acf() {
        // Check if ACF Pro function exists
        if (!function_exists('acf_register_post_type')) {
            viewpoints_plugin_log('ACF Pro function acf_register_post_type not available', 'warning');
            return;
        }

        viewpoints_plugin_log('Registering viewpoints post type with ACF Pro');

        // Get the post type settings
        $settings = get_option($this->option_name, array());
        if (empty($settings)) {
            $settings = $this->get_default_post_type_settings();
        }

        // Register with ACF
        acf_register_post_type($this->post_type, $settings);

        // Add a modified timestamp to force sync notification
        if (!isset($settings['modified'])) {
            $settings['modified'] = time();
            update_option($this->option_name, $settings);
        }

        // Trigger sync notification by storing a different version in ACF's internal storage
        if (function_exists('acf_get_internal_post_type') && function_exists('acf_update_internal_post_type')) {
            $internal = acf_get_internal_post_type($this->post_type);
            if (!$internal || $internal['modified'] != $settings['modified']) {
                viewpoints_plugin_log('Setting up sync notification for ACF Pro');
                acf_update_internal_post_type($this->post_type, $settings);
            }
        }

        viewpoints_plugin_log('Post type registered with ACF Pro');
    }
}