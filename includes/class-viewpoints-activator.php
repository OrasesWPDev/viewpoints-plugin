<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class Viewpoints_Activator {

    /**
     * Option name for storing the plugin version in the database.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version_option_name    The option name for the plugin version.
     */
    private static $version_option_name = 'viewpoints_plugin_version';

    /**
     * Option name for storing plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $settings_option_name    The option name for plugin settings.
     */
    private static $settings_option_name = 'viewpoints_plugin_settings';

    /**
     * Activate the plugin.
     *
     * Performs necessary setup and initialization when the plugin is activated.
     * Checks for version changes to handle plugin upgrades.
     *
     * @since    1.0.0
     */
    public static function activate() {
        viewpoints_plugin_log('Plugin activation started');

        // Create necessary directories
        self::create_directories();

        // Handle plugin version checks and upgrades
        self::handle_version_update();

        // Register initial settings
        self::register_settings();

        viewpoints_plugin_log('Plugin activation completed');
    }

    /**
     * Create necessary directories for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function create_directories() {
        viewpoints_plugin_log('Creating plugin directories');

        // Create logs directory if it doesn't exist
        $logs_dir = VIEWPOINTS_PLUGIN_DIR . 'logs';
        if (!file_exists($logs_dir)) {
            viewpoints_plugin_log('Creating logs directory at: ' . $logs_dir);
            wp_mkdir_p($logs_dir);

            // Create .htaccess to protect logs directory
            $htaccess_file = $logs_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                viewpoints_plugin_log('Creating .htaccess protection for logs directory');
                $htaccess_content = "# Deny direct access to files\n";
                $htaccess_content .= "<FilesMatch \"\\.log$\">\n";
                $htaccess_content .= "  Order Allow,Deny\n";
                $htaccess_content .= "  Deny from all\n";
                $htaccess_content .= "</FilesMatch>\n";
                file_put_contents($htaccess_file, $htaccess_content);
            }
        }

        // Create cache directory if it doesn't exist
        $cache_dir = VIEWPOINTS_PLUGIN_DIR . 'cache';
        if (!file_exists($cache_dir)) {
            viewpoints_plugin_log('Creating cache directory at: ' . $cache_dir);
            wp_mkdir_p($cache_dir);

            // Create .htaccess to protect cache directory
            $htaccess_file = $cache_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                viewpoints_plugin_log('Creating .htaccess protection for cache directory');
                $htaccess_content = "# Deny direct access\n";
                $htaccess_content .= "Order Allow,Deny\n";
                $htaccess_content .= "Deny from all\n";
                file_put_contents($htaccess_file, $htaccess_content);
            }
        }
    }

    /**
     * Handle version updates and cleanup old data if necessary.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function handle_version_update() {
        // Get currently stored version (if any)
        $stored_version = get_option(self::$version_option_name, '0.0.0');
        $current_version = VIEWPOINTS_PLUGIN_VERSION;

        viewpoints_plugin_log('Version check - Stored version: ' . $stored_version . ', Current version: ' . $current_version);

        // If the versions are different, we need to handle an update
        if (version_compare($stored_version, $current_version, '!=')) {
            viewpoints_plugin_log('Version change detected, cleaning up old plugin data');

            // Clean up old plugin settings but preserve content
            self::cleanup_old_data($stored_version, $current_version);

            // Update stored version
            update_option(self::$version_option_name, $current_version);
            viewpoints_plugin_log('Updated stored plugin version to: ' . $current_version);
        } else {
            viewpoints_plugin_log('No version change detected');
        }
    }

    /**
     * Clean up old plugin data during an update.
     *
     * This preserves actual content (posts) while cleaning up plugin settings/options.
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $old_version    The old plugin version.
     * @param    string    $new_version    The new plugin version.
     */
    private static function cleanup_old_data($old_version, $new_version) {
        viewpoints_plugin_log('Cleaning up old plugin data from v' . $old_version . ' during upgrade to v' . $new_version);

        // Reset any plugin settings/options to defaults
        // but DO NOT delete actual content (posts)

        // For example, we might reset plugin settings:
        $settings = get_option(self::$settings_option_name, array());

        // Log what settings are being reset
        viewpoints_plugin_log('Current plugin settings before reset: ' . print_r($settings, true));

        // Reset to defaults while preserving any custom settings
        $default_settings = self::get_default_settings();
        $updated_settings = array_merge($default_settings, $settings);

        update_option(self::$settings_option_name, $updated_settings);
        viewpoints_plugin_log('Plugin settings updated to defaults');

        // Additional version-specific upgrades could be added here
        // using version_compare() to target specific upgrades

        // Example of version-specific upgrade tasks:
        if (version_compare($old_version, '1.1.0', '<') && version_compare($new_version, '1.1.0', '>=')) {
            // Perform upgrade tasks specific to version 1.1.0
            viewpoints_plugin_log('Running 1.1.0-specific upgrades');
        }
    }

    /**
     * Register initial plugin settings.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function register_settings() {
        viewpoints_plugin_log('Registering initial plugin settings');

        // Get existing settings or create new ones if they don't exist
        $existing_settings = get_option(self::$settings_option_name, array());

        // If settings don't exist, initialize with defaults
        if (empty($existing_settings)) {
            viewpoints_plugin_log('No existing settings found, creating defaults');
            $default_settings = self::get_default_settings();
            update_option(self::$settings_option_name, $default_settings);
        } else {
            viewpoints_plugin_log('Existing settings found: ' . print_r($existing_settings, true));
        }
    }

    /**
     * Get default plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @return   array    The default plugin settings.
     */
    private static function get_default_settings() {
        viewpoints_plugin_log('Getting default plugin settings');

        // Define minimal default settings
        return array(
            'initialized' => true,
            'install_date' => current_time('mysql'),
            'version' => VIEWPOINTS_PLUGIN_VERSION,
        );
    }
}