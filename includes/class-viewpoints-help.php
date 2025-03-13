<?php
/**
 * Help Documentation Page
 *
 * @package Viewpoints
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle Viewpoints help documentation.
 */
class Viewpoints_Help {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Initialize the class.
     */
    private function __construct() {
        // Add submenu page
        add_action('admin_menu', array($this, 'add_help_page'), 30);
        // Add admin-specific styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        // Add plugin action links
        add_filter('plugin_action_links_' . VIEWPOINTS_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Add Help/Documentation page for the plugin
     */
    public function add_help_page() {
        add_submenu_page(
            'edit.php?post_type=viewpoints',  // Parent menu slug
            'Viewpoints Help',                // Page title
            'How to Use',                     // Menu title
            'edit_posts',                     // Capability
            'viewpoints-help',                // Menu slug
            array($this, 'help_page_content') // Callback function
        );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified links
     */
    public function add_plugin_action_links($links) {
        $help_link = '<a href="' . admin_url('edit.php?post_type=viewpoints&page=viewpoints-help') . '">' . __('Help', 'viewpoints-plugin') . '</a>';
        array_unshift($links, $help_link);
        return $links;
    }

    /**
     * Enqueue styles for admin help page
     *
     * @param string $hook Current admin page
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our help page
        if ('viewpoints_page_viewpoints-help' !== $hook) {
            return;
        }

        // Add inline styles for help page
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
    }

    /**
     * Get admin styles for help page
     *
     * @return string CSS styles
     */
    private function get_admin_styles() {
        return '
            .vp-help-wrap {
                max-width: 1300px;
                margin: 20px 20px 0 0;
            }
            .vp-help-header {
                background: #fff;
                padding: 20px;
                border-radius: 3px;
                margin-bottom: 20px;
                border-left: 4px solid #68348d;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .vp-help-section {
                background: #fff;
                padding: 20px;
                border-radius: 3px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                overflow-x: auto;
            }
            .vp-help-section h2 {
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
                margin-top: 0;
            }
            .vp-help-section h3 {
                margin-top: 1.5em;
                margin-bottom: 0.5em;
            }
            .vp-help-section table {
                border-collapse: collapse;
                width: 100%;
                margin: 1em 0;
                table-layout: fixed;
            }
            .vp-help-section table th,
            .vp-help-section table td {
                text-align: left;
                padding: 8px;
                border: 1px solid #ddd;
                vertical-align: top;
                word-wrap: break-word;
                word-break: break-word;
                hyphens: auto;
            }
            .vp-help-section table th:nth-child(1),
            .vp-help-section table td:nth-child(1) {
                width: 15%;
            }
            .vp-help-section table th:nth-child(2),
            .vp-help-section table td:nth-child(2) {
                width: 25%;
            }
            .vp-help-section table th:nth-child(3),
            .vp-help-section table td:nth-child(3) {
                width: 10%;
            }
            .vp-help-section table th:nth-child(4),
            .vp-help-section table td:nth-child(4) {
                width: 20%;
            }
            .vp-help-section table th:nth-child(5),
            .vp-help-section table td:nth-child(5) {
                width: 30%;
            }
            .vp-help-section table th {
                background-color: #f8f8f8;
                font-weight: 600;
            }
            .vp-help-section table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .vp-help-section code {
                background: #f8f8f8;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 13px;
                color: #68348d;
                display: inline-block;
                max-width: 100%;
                overflow-wrap: break-word;
                white-space: normal;
            }
            .vp-shortcode-example {
                background: #f8f8f8;
                padding: 15px;
                border-left: 4px solid #68348d;
                font-family: monospace;
                margin: 10px 0;
                overflow-x: auto;
                white-space: pre-wrap;
                word-break: break-word;
            }
        ';
    }

    /**
     * Content for help page
     */
    public function help_page_content() {
        ?>
        <div class="wrap vp-help-wrap">
            <div class="vp-help-header">
                <h1><?php esc_html_e('Viewpoints - Documentation', 'viewpoints-plugin'); ?></h1>
                <p><?php esc_html_e('This page provides documentation on how to use Viewpoints shortcodes and features.', 'viewpoints-plugin'); ?></p>
            </div>

            <!-- Overview Section -->
            <div class="vp-help-section">
                <h2><?php esc_html_e('Overview', 'viewpoints-plugin'); ?></h2>
                <p><?php esc_html_e('Viewpoints allows you to create and display viewpoint profiles on your site. The plugin provides shortcodes to display viewpoints in a grid layout and add breadcrumb navigation.', 'viewpoints-plugin'); ?></p>
                <ul>
                    <li><code>[viewpoints]</code> - <?php esc_html_e('Display multiple viewpoints in a grid layout', 'viewpoints-plugin'); ?></li>
                    <li><code>[viewpoint_breadcrumbs]</code> - <?php esc_html_e('Display breadcrumb navigation for viewpoints', 'viewpoints-plugin'); ?></li>
                </ul>
            </div>

            <!-- Shortcode Section -->
            <div class="vp-help-section">
                <h2><?php esc_html_e('Shortcode: [viewpoints]', 'viewpoints-plugin'); ?></h2>
                <p><?php esc_html_e('This shortcode displays a grid of Viewpoints with various customization options.', 'viewpoints-plugin'); ?></p>

                <h3><?php esc_html_e('Basic Usage', 'viewpoints-plugin'); ?></h3>
                <div class="vp-shortcode-example">
                    [viewpoints]
                </div>

                <h3><?php esc_html_e('Display Options', 'viewpoints-plugin'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Description', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Default', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Options', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Examples', 'viewpoints-plugin'); ?></th>
                    </tr>
                    <tr>
                        <td><code>columns</code></td>
                        <td><?php esc_html_e('Number of columns in grid view', 'viewpoints-plugin'); ?></td>
                        <td><code>2</code></td>
                        <td><?php esc_html_e('1-4', 'viewpoints-plugin'); ?></td>
                        <td><code>columns="2"</code><br><code>columns="4"</code></td>
                    </tr>
                    <tr>
                        <td><code>count</code></td>
                        <td><?php esc_html_e('Number of viewpoints to display', 'viewpoints-plugin'); ?></td>
                        <td><code>-1</code></td>
                        <td><?php esc_html_e('any number, -1 for all', 'viewpoints-plugin'); ?></td>
                        <td><code>count="6"</code><br><code>count="-1"</code></td>
                    </tr>
                    <tr>
                        <td><code>paged</code></td>
                        <td><?php esc_html_e('Whether to show pagination', 'viewpoints-plugin'); ?></td>
                        <td><code>true</code></td>
                        <td><code>true</code>, <code>false</code></td>
                        <td><code>paged="false"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Ordering Parameters', 'viewpoints-plugin'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Description', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Default', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Options', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Examples', 'viewpoints-plugin'); ?></th>
                    </tr>
                    <tr>
                        <td><code>order</code></td>
                        <td><?php esc_html_e('Sort order', 'viewpoints-plugin'); ?></td>
                        <td><code>DESC</code></td>
                        <td><code>ASC</code>, <code>DESC</code></td>
                        <td><code>order="ASC"</code></td>
                    </tr>
                    <tr>
                        <td><code>orderby</code></td>
                        <td><?php esc_html_e('Field to order by', 'viewpoints-plugin'); ?></td>
                        <td><code>date</code></td>
                        <td><code>date</code>, <code>title</code>, <code>menu_order</code>, <code>rand</code></td>
                        <td><code>orderby="title"</code><br><code>orderby="rand"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Filtering Parameters', 'viewpoints-plugin'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Description', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Default', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Options', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Examples', 'viewpoints-plugin'); ?></th>
                    </tr>
                    <tr>
                        <td><code>category</code></td>
                        <td><?php esc_html_e('Filter by category', 'viewpoints-plugin'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('category slug or ID', 'viewpoints-plugin'); ?></td>
                        <td><code>category="featured"</code><br><code>category="5"</code></td>
                    </tr>
                    <tr>
                        <td><code>tag</code></td>
                        <td><?php esc_html_e('Filter by tag', 'viewpoints-plugin'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('tag slug or ID', 'viewpoints-plugin'); ?></td>
                        <td><code>tag="healthcare"</code><br><code>tag="8"</code></td>
                    </tr>
                    <tr>
                        <td><code>include</code></td>
                        <td><?php esc_html_e('Include only specific viewpoints', 'viewpoints-plugin'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('IDs separated by commas', 'viewpoints-plugin'); ?></td>
                        <td><code>include="42,51,90"</code></td>
                    </tr>
                    <tr>
                        <td><code>exclude</code></td>
                        <td><?php esc_html_e('Exclude specific viewpoints', 'viewpoints-plugin'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('IDs separated by commas', 'viewpoints-plugin'); ?></td>
                        <td><code>exclude="42,51,90"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Advanced Parameters', 'viewpoints-plugin'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Description', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Default', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Options', 'viewpoints-plugin'); ?></th>
                        <th><?php esc_html_e('Examples', 'viewpoints-plugin'); ?></th>
                    </tr>
                    <tr>
                        <td><code>offset</code></td>
                        <td><?php esc_html_e('Number of posts to skip', 'viewpoints-plugin'); ?></td>
                        <td><code>0</code></td>
                        <td><?php esc_html_e('any number', 'viewpoints-plugin'); ?></td>
                        <td><code>offset="3"</code></td>
                    </tr>
                    <tr>
                        <td><code>class</code></td>
                        <td><?php esc_html_e('Additional CSS classes', 'viewpoints-plugin'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('any class names', 'viewpoints-plugin'); ?></td>
                        <td><code>class="featured-viewpoints"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Example Shortcodes', 'viewpoints-plugin'); ?></h3>
                <p><?php esc_html_e('Basic grid with 2 columns:', 'viewpoints-plugin'); ?></p>
                <div class="vp-shortcode-example">
                    [viewpoints columns="2" count="6"]
                </div>

                <p><?php esc_html_e('Display viewpoints from a specific category with pagination:', 'viewpoints-plugin'); ?></p>
                <div class="vp-shortcode-example">
                    [viewpoints category="healthcare" paged="true" count="12"]
                </div>

                <p><?php esc_html_e('Display viewpoints in a 2-column layout, randomly ordered:', 'viewpoints-plugin'); ?></p>
                <div class="vp-shortcode-example">
                    [viewpoints columns="2" orderby="rand"]
                </div>

                <p><?php esc_html_e('Display specific viewpoints by ID:', 'viewpoints-plugin'); ?></p>
                <div class="vp-shortcode-example">
                    [viewpoints include="42,51,90" orderby="post__in"]
                </div>
            </div>

            <!-- Breadcrumbs Shortcode Section -->
            <div class="vp-help-section">
                <h2><?php esc_html_e('Shortcode: [viewpoint_breadcrumbs]', 'viewpoints-plugin'); ?></h2>
                <p><?php esc_html_e('This shortcode displays breadcrumb navigation for viewpoints.', 'viewpoints-plugin'); ?></p>

                <h3><?php esc_html_e('Basic Usage', 'viewpoints-plugin'); ?></h3>
                <div class="vp-shortcode-example">
                    [viewpoint_breadcrumbs]
                </div>

                <p><?php esc_html_e('The breadcrumbs will display:', 'viewpoints-plugin'); ?></p>
                <ul>
                    <li><?php esc_html_e('Home > Viewpoints (when on the archive page)', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Home > Viewpoints > Viewpoint Title (when on a single viewpoint page)', 'viewpoints-plugin'); ?></li>
                </ul>
            </div>

            <!-- Finding IDs Section -->
            <div class="vp-help-section">
                <h2><?php esc_html_e('Finding Viewpoint IDs', 'viewpoints-plugin'); ?></h2>
                <p><?php esc_html_e('To find the ID of a Viewpoint:', 'viewpoints-plugin'); ?></p>
                <ol>
                    <li><?php esc_html_e('Go to Viewpoints in the admin menu', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Hover over a viewpoint\'s title', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Look at the URL that appears in your browser\'s status bar', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('The ID is the number after "post=", e.g., post=42', 'viewpoints-plugin'); ?></li>
                </ol>
                <p><?php esc_html_e('Alternatively, open a viewpoint for editing and the ID will be visible in the URL.', 'viewpoints-plugin'); ?></p>
            </div>

            <!-- Create New Viewpoints Section -->
            <div class="vp-help-section">
                <h2><?php esc_html_e('Creating Viewpoints', 'viewpoints-plugin'); ?></h2>
                <p><?php esc_html_e('To create a new Viewpoint:', 'viewpoints-plugin'); ?></p>
                <ol>
                    <li><?php esc_html_e('Go to Viewpoints > Add New in the admin menu', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Add a title for your viewpoint', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Set a featured image - this will be displayed in the grid view', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Fill in the custom fields in the Viewpoint Field Groups section', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Publish your viewpoint when ready', 'viewpoints-plugin'); ?></li>
                </ol>
                <p><?php esc_html_e('The featured image is particularly important as it is what displays in the grid view on archive pages and in the shortcode output.', 'viewpoints-plugin'); ?></p>
            </div>

            <!-- Need Help Section -->
            <div class="vp-help-section">
                <h2><?php esc_html_e('Need More Help?', 'viewpoints-plugin'); ?></h2>
                <p><?php esc_html_e('If you need further assistance:', 'viewpoints-plugin'); ?></p>
                <ul>
                    <li><?php esc_html_e('Contact your website administrator', 'viewpoints-plugin'); ?></li>
                    <li><?php esc_html_e('Refer to the WordPress documentation for general shortcode usage', 'viewpoints-plugin'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
