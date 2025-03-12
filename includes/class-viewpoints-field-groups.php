<?php
/**
 * Field Groups functionality for Viewpoints.
 *
 * This class ensures the ACF field groups are properly set up
 * by directing ACF Pro to the appropriate JSON files for syncing.
 *
 * @since      1.0.0
 */
class Viewpoints_Field_Groups {

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
        viewpoints_plugin_log('Initializing Viewpoints_Field_Groups class');
        $this->json_dir = VIEWPOINTS_PLUGIN_DIR . 'acf-json/';
    }

    /**
     * Register hooks related to the field groups.
     *
     * @since    1.0.0
     */
    public function register() {
        viewpoints_plugin_log('Registering Viewpoints field group hooks');

        // Add ACF JSON loading point
        add_filter('acf/settings/load_json', array($this, 'add_acf_json_load_point'));

        // Listen for field group sync completion
        add_action('acf/include_fields', array($this, 'on_fields_sync'));

        // Ensure directories exist
        $this->ensure_json_directory();
    }

    /**
     * Add local JSON loading point for ACF.
     *
     * @since    1.0.0
     * @param    array    $paths    Existing load paths.
     * @return   array              Modified paths including our plugin's directory.
     */
    public function add_acf_json_load_point($paths) {
        viewpoints_plugin_log('Adding ACF JSON load point for field groups: ' . $this->json_dir);
        $paths[] = $this->json_dir;
        return $paths;
    }

    /**
     * Callback for when fields are included/synced
     *
     * @since    1.0.0
     */
    public function on_fields_sync() {
        viewpoints_plugin_log('ACF fields have been included/synced - checking field group status');

        // Additional operations after field groups are synced can be added here
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            $viewpoint_groups = 0;

            foreach ($field_groups as $field_group) {
                if (isset($field_group['title']) && stripos($field_group['title'], 'viewpoint') !== false) {
                    $viewpoint_groups++;
                    viewpoints_plugin_log('Found Viewpoint field group: ' . $field_group['title']);
                }
            }

            viewpoints_plugin_log('Total Viewpoint field groups found: ' . $viewpoint_groups);
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
     * Check if there are field groups available for sync.
     * This is a helper method for debugging purposes.
     *
     * @since    1.0.0
     * @return   bool     Whether field groups are available for sync.
     */
    public function check_sync_available() {
        // Only check if ACF Pro is active and needed functions exist
        if (!function_exists('acf_get_local_json_files') || !function_exists('acf_get_field_group')) {
            viewpoints_plugin_log('ACF functions not available to check sync status', 'warning');
            return false;
        }

        viewpoints_plugin_log('Checking for available field group sync');

        // Get local JSON files
        $json_files = acf_get_local_json_files();
        if (empty($json_files)) {
            viewpoints_plugin_log('No local JSON files found');
            return false;
        }

        viewpoints_plugin_log('Found ' . count($json_files) . ' local JSON files');

        // Check each file to see if it's synced
        $sync_available = false;
        foreach ($json_files as $key => $file) {
            // Get the field group from the file
            $local_field_group = json_decode(file_get_contents($file), true);
            if (!$local_field_group) {
                continue;
            }

            // Only care about our plugin's field groups
            if (isset($local_field_group['title']) && stripos($local_field_group['title'], 'viewpoint') === false) {
                continue;
            }

            // Check if this field group exists in the database
            $db_field_group = acf_get_field_group($key);

            if (!$db_field_group) {
                viewpoints_plugin_log('Field group needs sync: ' . $local_field_group['title']);
                $sync_available = true;
            } else {
                // Check if the database version matches the file version
                $db_modified = $db_field_group['modified'] ?? 0;
                $file_modified = $local_field_group['modified'] ?? 0;

                if ($file_modified > $db_modified) {
                    viewpoints_plugin_log('Field group needs update: ' . $local_field_group['title']);
                    $sync_available = true;
                }
            }
        }

        return $sync_available;
    }
}