<?php
/**
 * ACF Field Group Registration
 *
 * @package Viewpoints
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Viewpoints_ACF
 *
 * Handles the registration of ACF field groups for Viewpoints
 */
class Viewpoints_ACF {

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Field group key.
     *
     * @var string
     */
    protected $field_group_key = 'group_viewpoints_fields';

    /**
     * Post type key.
     *
     * @var string
     */
    protected $post_type_key = 'viewpoints';

    /**
     * Field group JSON filename.
     *
     * @var string
     */
    protected $field_group_filename = 'group_viewpoints_fields.json';

    /**
     * Post type JSON filename.
     *
     * @var string
     */
    protected $post_type_filename = 'post_type_viewpoints.json';

    /**
     * Initialize the class.
     */
    public function __construct() {
        viewpoints_plugin_log('Initializing Viewpoints_ACF class');

        // Register local JSON save point
        add_filter('acf/settings/save_json', array($this, 'acf_json_save_point'));

        // Register local JSON load point
        add_filter('acf/settings/load_json', array($this, 'acf_json_load_point'));

        // Hook into ACF initialization - important for correct loading order
        add_action('acf/init', array($this, 'initialize_acf_sync'), 5);

        // Add a notice if there are field groups that need syncing
        add_action('admin_notices', array($this, 'sync_admin_notice'));

        // Add an action to handle syncing
        add_action('admin_post_viewpoints_sync_acf', array($this, 'handle_sync_action'));

        viewpoints_plugin_log('Viewpoints_ACF initialization completed');
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
            viewpoints_plugin_log('Viewpoints ACF: Instance created');
        }
        return self::$instance;
    }

    /**
     * Set ACF JSON save point.
     *
     * @param string $path Path to save ACF JSON files.
     * @return string Modified path.
     */
    public function acf_json_save_point($path) {
        viewpoints_plugin_log('Viewpoints ACF: Setting save point to ' . VIEWPOINTS_PLUGIN_DIR . 'acf-json');
        return VIEWPOINTS_PLUGIN_DIR . 'acf-json';
    }

    /**
     * Add custom ACF JSON load point.
     *
     * @param array $paths Existing ACF JSON load paths.
     * @return array Modified paths.
     */
    public function acf_json_load_point($paths) {
        // Add our path to the existing load paths
        $paths[] = VIEWPOINTS_PLUGIN_DIR . 'acf-json';
        viewpoints_plugin_log('Viewpoints ACF: Adding load point ' . VIEWPOINTS_PLUGIN_DIR . 'acf-json');
        return $paths;
    }

    /**
     * Initialize ACF sync during acf/init hook.
     */
    public function initialize_acf_sync() {
        viewpoints_plugin_log('Viewpoints ACF: initialize_acf_sync called');

        // Check if we're in the admin and have ACF functions
        if (!is_admin() || !function_exists('acf_get_field_group')) {
            viewpoints_plugin_log('Viewpoints ACF: Skipping sync - not in admin or ACF functions not available');
            return;
        }

        // Import post type definitions
        $this->import_post_types();

        // Import field groups
        $this->import_field_groups();
    }

    /**
     * Import post type definitions from JSON
     */
    private function import_post_types() {
        viewpoints_plugin_log('Viewpoints ACF: Starting import_post_types');

        if (!function_exists('acf_get_post_type_post') || !function_exists('acf_update_post_type')) {
            viewpoints_plugin_log('Viewpoints ACF: ACF functions for post types not available');
            return;
        }

        $json_file = VIEWPOINTS_PLUGIN_DIR . 'acf-json/' . $this->post_type_filename;
        viewpoints_plugin_log('Viewpoints ACF: Looking for post type JSON file: ' . $json_file);

        // Check if the file exists
        if (!file_exists($json_file)) {
            viewpoints_plugin_log('Viewpoints ACF: Post type JSON file not found: ' . $json_file);
            return;
        }

        $json_content = file_get_contents($json_file);
        if (empty($json_content)) {
            viewpoints_plugin_log('Viewpoints ACF: Empty JSON file: ' . $json_file);
            return;
        }

        $post_type_data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            viewpoints_plugin_log('Viewpoints ACF: JSON decode error: ' . json_last_error_msg() . ' in file: ' . $json_file);
            return;
        }

        // Skip if not an array or missing required keys
        if (!is_array($post_type_data) || !isset($post_type_data['key'])) {
            viewpoints_plugin_log('Viewpoints ACF: Invalid post type data structure, missing key');
            return;
        }

        try {
            // Get post type key
            $post_type_key = $post_type_data['key'];
            viewpoints_plugin_log('Viewpoints ACF: Processing post type: ' . $post_type_key);

            // Check if this post type already exists in ACF
            $existing = false;
            if (function_exists('acf_get_post_type_post')) {
                $existing = acf_get_post_type_post($post_type_key);
            }

            if (!$existing) {
                // Set import info
                $post_type_data['import_source'] = 'viewpoints-plugin';
                $post_type_data['import_date'] = date('Y-m-d H:i:s');

                viewpoints_plugin_log('Viewpoints ACF: Importing post type: ' . $post_type_data['title']);

                // Different versions of ACF might require different approaches
                if (function_exists('acf_update_post_type')) {
                    acf_update_post_type($post_type_data);
                    viewpoints_plugin_log('Viewpoints ACF: Successfully imported post type via acf_update_post_type()');
                } else {
                    // Fallback to native WordPress registration if ACF function not available
                    $this->register_post_type_fallback($post_type_data);
                }
            } else {
                viewpoints_plugin_log('Viewpoints ACF: Post type already exists: ' . $post_type_key);
            }
        } catch (Exception $e) {
            viewpoints_plugin_log('Viewpoints ACF: Error importing post type: ' . $e->getMessage());
        }
    }

    /**
     * Fallback post type registration using WordPress native functions
     * Used if ACF post type registration fails
     *
     * @param array $post_type_data The post type definition from JSON
     */
    private function register_post_type_fallback($post_type_data) {
        // Only run if this isn't already registered
        if (post_type_exists($post_type_data['post_type'])) {
            viewpoints_plugin_log('Viewpoints ACF: Post type already exists via WordPress, skipping fallback registration');
            return;
        }

        viewpoints_plugin_log('Viewpoints ACF: Using fallback post type registration for: ' . $post_type_data['post_type']);

        // Get labels from the data or use defaults
        $labels = isset($post_type_data['labels']) ? $post_type_data['labels'] : array();

        // Basic arguments
        $args = array(
            'labels'             => $labels,
            'description'        => isset($post_type_data['description']) ? $post_type_data['description'] : '',
            'public'             => isset($post_type_data['public']) ? $post_type_data['public'] : true,
            'hierarchical'       => isset($post_type_data['hierarchical']) ? $post_type_data['hierarchical'] : false,
            'exclude_from_search' => isset($post_type_data['exclude_from_search']) ? $post_type_data['exclude_from_search'] : false,
            'publicly_queryable' => isset($post_type_data['publicly_queryable']) ? $post_type_data['publicly_queryable'] : true,
            'show_ui'            => isset($post_type_data['show_ui']) ? $post_type_data['show_ui'] : true,
            'show_in_menu'       => isset($post_type_data['show_in_menu']) ? $post_type_data['show_in_menu'] : true,
            'show_in_admin_bar'  => isset($post_type_data['show_in_admin_bar']) ? $post_type_data['show_in_admin_bar'] : false,
            'show_in_nav_menus'  => isset($post_type_data['show_in_nav_menus']) ? $post_type_data['show_in_nav_menus'] : true,
            'show_in_rest'       => isset($post_type_data['show_in_rest']) ? $post_type_data['show_in_rest'] : true,
            'menu_position'      => isset($post_type_data['menu_position']) ? $post_type_data['menu_position'] : null,
            'menu_icon'          => isset($post_type_data['menu_icon']) ? $post_type_data['menu_icon'] : 'dashicons-admin-post',
            'capability_type'    => 'post',
            'supports'           => isset($post_type_data['supports']) ? $post_type_data['supports'] : array('title', 'editor'),
            'taxonomies'         => isset($post_type_data['taxonomies']) ? $post_type_data['taxonomies'] : array(),
            'has_archive'        => isset($post_type_data['has_archive']) ? $post_type_data['has_archive'] : true,
        );

        // Handle rewrite rules
        if (isset($post_type_data['rewrite'])) {
            $rewrite = $post_type_data['rewrite'];
            $args['rewrite'] = array();

            // Handle simple or complex rewrite array formats
            if (is_array($rewrite)) {
                // If slug is specified
                if (isset($rewrite['slug'])) {
                    $args['rewrite']['slug'] = $rewrite['slug'];
                } else {
                    $args['rewrite']['slug'] = $post_type_data['post_type'];
                }

                // Handle feeds
                if (isset($rewrite['feeds'])) {
                    $args['rewrite']['feeds'] = ($rewrite['feeds'] === '1' || $rewrite['feeds'] === true);
                } else {
                    $args['rewrite']['feeds'] = false;
                }

                // Handle with_front if set
                if (isset($rewrite['with_front'])) {
                    $args['rewrite']['with_front'] = ($rewrite['with_front'] === '1' || $rewrite['with_front'] === true);
                }

                // Handle pages if set
                if (isset($rewrite['pages'])) {
                    $args['rewrite']['pages'] = ($rewrite['pages'] === '1' || $rewrite['pages'] === true);
                }
            } else if ($rewrite === false) {
                // If rewrite is explicitly set to false
                $args['rewrite'] = false;
            }
        }

        // Register the post type
        register_post_type($post_type_data['post_type'], $args);
        viewpoints_plugin_log('Viewpoints ACF: Fallback post type registration complete');
    }

    /**
     * Import field groups from JSON
     */
    private function import_field_groups() {
        viewpoints_plugin_log('Viewpoints ACF: Starting import_field_groups');

        if (!function_exists('acf_get_field_group') || !function_exists('acf_import_field_group')) {
            viewpoints_plugin_log('Viewpoints ACF: ACF functions for field groups not available');
            return;
        }

        $json_file = VIEWPOINTS_PLUGIN_DIR . 'acf-json/' . $this->field_group_filename;
        viewpoints_plugin_log('Viewpoints ACF: Looking for field group JSON file: ' . $json_file);

        if (!file_exists($json_file)) {
            viewpoints_plugin_log('Viewpoints ACF: Field group JSON file not found: ' . $json_file);
            return;
        }

        $json_content = file_get_contents($json_file);
        if (empty($json_content)) {
            viewpoints_plugin_log('Viewpoints ACF: Empty field group JSON file: ' . $json_file);
            return;
        }

        $field_group = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            viewpoints_plugin_log('Viewpoints ACF: Field group JSON decode error: ' . json_last_error_msg());
            return;
        }

        if (!is_array($field_group) || !isset($field_group['key'])) {
            viewpoints_plugin_log('Viewpoints ACF: Invalid field group JSON structure');
            return;
        }

        // Import the field group
        $this->import_single_field_group($field_group);
    }

    /**
     * Import a single field group
     *
     * @param array $field_group Field group definition
     */
    private function import_single_field_group($field_group) {
        viewpoints_plugin_log('Viewpoints ACF: Importing field group: ' . $field_group['key']);

        // Check if this field group already exists
        $existing = acf_get_field_group($field_group['key']);

        if (!$existing) {
            try {
                // Import the field group
                acf_import_field_group($field_group);
                viewpoints_plugin_log('Viewpoints ACF: Successfully imported field group: ' . $field_group['title']);
            } catch (Exception $e) {
                viewpoints_plugin_log('Viewpoints ACF: Error importing field group: ' . $e->getMessage());
            }
        } else {
            viewpoints_plugin_log('Viewpoints ACF: Field group already exists: ' . $field_group['key']);
        }
    }

    /**
     * Display admin notice if there are field groups that need syncing
     */
    public function sync_admin_notice() {
        // Only show on ACF admin pages
        $screen = get_current_screen();
        if (!$screen || !is_object($screen) || !isset($screen->id) || strpos($screen->id, 'acf-field-group') === false) {
            return;
        }

        viewpoints_plugin_log('Viewpoints ACF: Checking for field groups requiring sync');
        $sync_required = $this->get_field_groups_requiring_sync();

        if (!empty($sync_required) && is_array($sync_required)) {
            viewpoints_plugin_log('Viewpoints ACF: Found ' . count($sync_required) . ' field groups requiring sync');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    printf(
                        _n(
                            'There is %d Viewpoints field group that requires synchronization.',
                            'There are %d Viewpoints field groups that require synchronization.',
                            count($sync_required),
                            'viewpoints-plugin'
                        ),
                        count($sync_required)
                    );
                    ?>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=viewpoints_sync_acf'), 'viewpoints_sync_acf')); ?>" class="button button-primary">
                        <?php _e('Sync Field Groups', 'viewpoints-plugin'); ?>
                    </a>
                </p>
            </div>
            <?php
        } else {
            viewpoints_plugin_log('Viewpoints ACF: No field groups require sync');
        }
    }

    /**
     * Get field groups that require synchronization
     *
     * @return array Array of field groups that require synchronization
     */
    private function get_field_groups_requiring_sync() {
        if (!function_exists('acf_get_field_group')) {
            viewpoints_plugin_log('Viewpoints ACF: acf_get_field_group function not available');
            return array();
        }

        $sync_required = array();
        $json_file = VIEWPOINTS_PLUGIN_DIR . 'acf-json/' . $this->field_group_filename;

        if (file_exists($json_file)) {
            $json_content = file_get_contents($json_file);
            $json_group = json_decode($json_content, true);

            if (is_array($json_group) && isset($json_group['key'])) {
                // Get database version
                $db_group = acf_get_field_group($json_group['key']);
                viewpoints_plugin_log('Viewpoints ACF: Checking sync status for: ' . $json_group['key']);

                // If DB version doesn't exist or has a different modified time, it needs sync
                if (!$db_group) {
                    viewpoints_plugin_log('Viewpoints ACF: Field group not found in database, sync required');
                    $sync_required[] = $json_group;
                } else if (isset($json_group['modified']) && isset($db_group['modified']) && $db_group['modified'] != $json_group['modified']) {
                    viewpoints_plugin_log('Viewpoints ACF: Field group modified time mismatch, sync required');
                    viewpoints_plugin_log('Viewpoints ACF: JSON modified: ' . $json_group['modified'] . ', DB modified: ' . $db_group['modified']);
                    $sync_required[] = $json_group;
                }
            } else {
                viewpoints_plugin_log('Viewpoints ACF: Invalid field group JSON structure or missing key');
            }
        } else {
            viewpoints_plugin_log('Viewpoints ACF: Field group JSON file not found: ' . $json_file);
        }

        return $sync_required;
    }

    /**
     * Handle the synchronization action
     */
    public function handle_sync_action() {
        viewpoints_plugin_log('Viewpoints ACF: Handling sync action');

        // Security check
        if (!current_user_can('manage_options')) {
            viewpoints_plugin_log('Viewpoints ACF: Security check failed - insufficient permissions');
            wp_die(__('You do not have sufficient permissions to access this page.', 'viewpoints-plugin'));
        }

        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'viewpoints_sync_acf')) {
            viewpoints_plugin_log('Viewpoints ACF: Security check failed - invalid nonce');
            wp_die(__('Security check failed.', 'viewpoints-plugin'));
        }

        viewpoints_plugin_log('Viewpoints ACF: Security checks passed, proceeding with sync');

        // Import post types
        $this->import_post_types();

        // Import field groups
        $this->import_field_groups();

        viewpoints_plugin_log('Viewpoints ACF: Sync completed, redirecting');

        // Redirect to the main ACF field groups list
        wp_redirect(add_query_arg(array(
            'post_type' => 'acf-field-group',
            'sync' => 'complete',
            'count' => 1
        ), admin_url('edit.php')));
        exit;
    }
}