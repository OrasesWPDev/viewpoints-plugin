<?php
/**
 * Plugin Name: Viewpoints Plugin
 * Plugin URI: https://github.com/OrasesWPDev/viewpoints-plugin/blob/main/viewpoints-plugin.php
 * Description: Registers a custom post type for Viewpoints with ACF Pro field groups.
 * Version: 1.0.0
 * Author: Orases
 * Author URI: https://orases.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('VIEWPOINTS_PLUGIN_VERSION', '1.0.0');
define('VIEWPOINTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIEWPOINTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIEWPOINTS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('VIEWPOINTS_PLUGIN_FILE', __FILE__);
define('VIEWPOINTS_PLUGIN_INCLUDES_DIR', VIEWPOINTS_PLUGIN_DIR . 'includes/');
define('VIEWPOINTS_PLUGIN_TEMPLATES_DIR', VIEWPOINTS_PLUGIN_DIR . 'templates/');
define('VIEWPOINTS_PLUGIN_ASSETS_DIR', VIEWPOINTS_PLUGIN_DIR . 'assets/');
define('VIEWPOINTS_PLUGIN_ASSETS_URL', VIEWPOINTS_PLUGIN_URL . 'assets/');

// Define debug constant based on WP_DEBUG
define('VIEWPOINTS_PLUGIN_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Log debug message.
 *
 * @param mixed $message Message to log.
 * @param string $level Log level.
 */
function viewpoints_plugin_log($message, $level = 'debug') {
    // Define log constants if not already defined
    if (!defined('VIEWPOINTS_PLUGIN_DEBUG') || !VIEWPOINTS_PLUGIN_DEBUG) {
        return;
    }

    // Format message
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }

    // Prepend time and level
    $message = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message;

    // Log file path
    $log_file = VIEWPOINTS_PLUGIN_DIR . 'logs/debug.log';

    // Create logs directory if it doesn't exist
    $logs_dir = dirname($log_file);
    if (!file_exists($logs_dir)) {
        wp_mkdir_p($logs_dir);
    }

    // Append to log file
    error_log($message . PHP_EOL, 3, $log_file);
}

/**
 * Check if ACF Pro is active
 *
 * @return bool True if ACF Pro is active, false otherwise
 */
function viewpoints_plugin_is_acf_pro_active() {
    return class_exists('acf') && function_exists('acf_add_local_field_group');
}

/**
 * Admin notice for missing ACF Pro dependency
 */
function viewpoints_plugin_acf_pro_admin_notice() {
    ?>
    <div class="notice notice-error">
        <p>Viewpoints Plugin requires Advanced Custom Fields PRO to be installed and activated.</p>
    </div>
    <?php
}

/**
 * Check for plugin dependencies
 */
function viewpoints_plugin_check_dependencies() {
    if (!viewpoints_plugin_is_acf_pro_active()) {
        add_action('admin_notices', 'viewpoints_plugin_acf_pro_admin_notice');
        return false;
    }
    return true;
}

// Register activation and deactivation hooks
register_activation_hook(VIEWPOINTS_PLUGIN_FILE, 'viewpoints_plugin_activate');
register_deactivation_hook(VIEWPOINTS_PLUGIN_FILE, 'viewpoints_plugin_deactivate');

/**
 * Plugin activation function.
 */
function viewpoints_plugin_activate() {
    // Check ACF Pro dependency
    if (!viewpoints_plugin_is_acf_pro_active()) {
        // Deactivate the plugin
        deactivate_plugins(VIEWPOINTS_PLUGIN_BASENAME);

        // Bail and show admin notice
        wp_die('Viewpoints Plugin requires Advanced Custom Fields PRO to be installed and activated.', 'Plugin dependency check', array('back_link' => true));

        return;
    }

    require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-activator.php';
    Viewpoints_Activator::activate();
    flush_rewrite_rules();

    // Log activation
    viewpoints_plugin_log('Plugin activated');
}

/**
 * Plugin deactivation function.
 */
function viewpoints_plugin_deactivate() {
    require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-deactivator.php';
    Viewpoints_Deactivator::deactivate();
    flush_rewrite_rules();

    // Log deactivation
    viewpoints_plugin_log('Plugin deactivated');
}

// Check dependencies before initializing the plugin
if (!viewpoints_plugin_check_dependencies()) {
    return;
}

// Include necessary files
require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-utils.php';
require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-plugin.php';
require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-post-type.php';
require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-field-groups.php';
require_once VIEWPOINTS_PLUGIN_INCLUDES_DIR . 'class-viewpoints-shortcode.php';

// Initialize plugin
function viewpoints_plugin_init() {
    $plugin = Viewpoints_Plugin::get_instance();
    $plugin->run();
}
add_action('plugins_loaded', 'viewpoints_plugin_init');