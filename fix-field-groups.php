<?php
/**
 * Utility script to check and load field groups
 *
 * This is a one-time utility to help debug and fix field group loading issues.
 * Access via: /wp-admin/admin.php?page=viewpoints-fix-field-groups
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Create admin page
add_action('admin_menu', function() {
    add_submenu_page(
        null, // Hidden from menu
        'Fix Viewpoints Field Groups',
        'Fix Field Groups',
        'manage_options',
        'viewpoints-fix-field-groups',
        'viewpoints_fix_field_groups_page'
    );
});

// The admin page callback
function viewpoints_fix_field_groups_page() {
    echo '<div class="wrap">';
    echo '<h1>Viewpoints Field Groups Utility</h1>';

    // Check if ACF is active
    if (!function_exists('acf_add_local_field_group')) {
        echo '<div class="notice notice-error"><p>Advanced Custom Fields must be active.</p></div>';
        echo '</div>';
        return;
    }

    // Define paths
    $acf_json_dir = VIEWPOINTS_PLUGIN_DIR . 'acf-json/';
    $field_group_file = $acf_json_dir . 'group_viewpoints_fields.json';

    // Display path info
    echo '<h2>File Information</h2>';
    echo '<p>ACF JSON Directory: ' . esc_html($acf_json_dir) . '</p>';
    echo '<p>Field Group File: ' . esc_html($field_group_file) . '</p>';
    echo '<p>Directory exists: ' . (is_dir($acf_json_dir) ? 'Yes' : 'No') . '</p>';

    // Check and create directory if needed
    if (!is_dir($acf_json_dir)) {
        wp_mkdir_p($acf_json_dir);
        echo '<div class="notice notice-success"><p>Created acf-json directory</p></div>';
    }

    // Display directory contents
    echo '<h2>Directory Contents</h2>';
    if (is_dir($acf_json_dir)) {
        $files = scandir($acf_json_dir);
        echo '<ul>';
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo '<li>' . esc_html($file) . ' - ' . size_format(filesize($acf_json_dir . $file)) . '</li>';
            }
        }
        echo '</ul>';
    }

    // Check if field group file exists
    echo '<h2>Field Group JSON File</h2>';
    echo '<p>File exists: ' . (file_exists($field_group_file) ? 'Yes' : 'No') . '</p>';

    // Create or update button
    if (isset($_POST['create_field_group']) && check_admin_referer('viewpoints_create_field_group')) {
        $field_group_json = stripslashes($_POST['field_group_json']);

        // Validate JSON
        $json_data = json_decode($field_group_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo '<div class="notice notice-error"><p>Invalid JSON: ' . json_last_error_msg() . '</p></div>';
        } else {
            // Valid JSON, save to file
            file_put_contents($field_group_file, $field_group_json);
            echo '<div class="notice notice-success"><p>Field group JSON saved successfully</p></div>';

            // Try to import it directly
            if (function_exists('acf_import_field_group')) {
                acf_import_field_group($json_data);
                echo '<div class="notice notice-success"><p>Field group imported directly via ACF</p></div>';
            }
        }
    }

    // Form to create/update field group
    echo '<form method="post">';
    wp_nonce_field('viewpoints_create_field_group');
    echo '<h3>Create/Update Field Group JSON</h3>';
    echo '<p>Paste the field group JSON below:</p>';

    // Default content for the field group
    $default_json = file_exists($field_group_file) ? file_get_contents($field_group_file) : <<<JSON
{
    "key": "group_67d1e260e486c",
    "title": "Viewpoint Field Groups",
    "fields": [
        {
            "key": "field_67d1e26112a1e",
            "label": "Viewpoint Bio",
            "name": "viewpoint_bio",
            "aria-label": "",
            "type": "wysiwyg",
            "instructions": "use P tags here (<p>)",
            "required": 0,
            "conditional_logic": 0,
            "wrapper": {
                "width": "",
                "class": "",
                "id": ""
            },
            "relevanssi_exclude": 0,
            "default_value": "",
            "allow_in_bindings": 1,
            "tabs": "all",
            "toolbar": "full",
            "media_upload": 1,
            "delay": 0
        },
        {
            "key": "field_67d1e2ac5607d",
            "label": "Content",
            "name": "content",
            "aria-label": "",
            "type": "repeater",
            "instructions": "Put all Headings and paragraphs here - add a new row for each heading / paragraph combo",
            "required": 1,
            "conditional_logic": 0,
            "wrapper": {
                "width": "",
                "class": "",
                "id": ""
            },
            "relevanssi_exclude": 0,
            "layout": "table",
            "pagination": 0,
            "min": 0,
            "max": 0,
            "collapsed": "",
            "button_label": "Add Row",
            "rows_per_page": 20,
            "sub_fields": [
                {
                    "key": "field_67d1e2e75607e",
                    "label": "Heading",
                    "name": "heading",
                    "aria-label": "",
                    "type": "text",
                    "instructions": "Put the Heading Here",
                    "required": 1,
                    "conditional_logic": 0,
                    "wrapper": {
                        "width": "",
                        "class": "",
                        "id": ""
                    },
                    "relevanssi_exclude": 0,
                    "default_value": "",
                    "maxlength": "",
                    "allow_in_bindings": 0,
                    "placeholder": "",
                    "prepend": "",
                    "append": "",
                    "parent_repeater": "field_67d1e2ac5607d"
                },
                {
                    "key": "field_67d1e3005607f",
                    "label": "Content",
                    "name": "content",
                    "aria-label": "",
                    "type": "wysiwyg",
                    "instructions": "Use P tags (<p>)",
                    "required": 1,
                    "conditional_logic": 0,
                    "wrapper": {
                        "width": "",
                        "class": "",
                        "id": ""
                    },
                    "relevanssi_exclude": 0,
                    "default_value": "",
                    "allow_in_bindings": 0,
                    "tabs": "all",
                    "toolbar": "full",
                    "media_upload": 1,
                    "delay": 0,
                    "parent_repeater": "field_67d1e2ac5607d"
                }
            ]
        }
    ],
    "location": [
        [
            {
                "param": "post_type",
                "operator": "==",
                "value": "viewpoints"
            }
        ]
    ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "",
    "show_in_rest": 0,
    "modified": 1710454020
}
JSON;

    echo '<textarea name="field_group_json" style="width:100%; height:500px;">' . esc_textarea($default_json) . '</textarea>';
    echo '<p><button type="submit" name="create_field_group" class="button button-primary">Save Field Group</button></p>';
    echo '</form>';

    // Check if ACF sync is available
    echo '<h2>ACF Field Group Sync Status</h2>';
    if (function_exists('acf_get_field_group')) {
        $field_group = acf_get_field_group('group_67d1e260e486c');
        echo '<p>Field Group in Database: ' . ($field_group ? 'Yes' : 'No') . '</p>';

        if ($field_group) {
            echo '<p>Field Group Title: ' . esc_html($field_group['title']) . '</p>';
            echo '<p>Field Count: ' . count($field_group['fields']) . '</p>';
        }
    }

    echo '</div>';
}