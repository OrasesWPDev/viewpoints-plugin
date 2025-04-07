<?php
/**
 * Utility functions for the Viewpoints Plugin.
 *
 * This class provides helper methods that can be used throughout the plugin.
 *
 * @since      1.0.0
 */
class Viewpoints_Utils {

    /**
     * Get the URL for a single viewpoint post.
     *
     * @since    1.0.0
     * @param    int|WP_Post    $post    Post ID or post object.
     * @return   string                  The URL for the viewpoint.
     */
    public static function get_viewpoint_url($post) {
        viewpoints_plugin_log('Getting URL for viewpoint: ' . (is_numeric($post) ? $post : $post->ID));

        $post = get_post($post);
        if (!$post || 'viewpoint' !== $post->post_type) {
            viewpoints_plugin_log('Invalid post or not a viewpoint: ' . print_r($post, true), 'error');
            return '';
        }

        $url = get_permalink($post);
        viewpoints_plugin_log('Generated URL: ' . $url);

        return $url;
    }

    /**
     * Get the featured image URL for a viewpoint.
     *
     * @since    1.0.0
     * @param    int|WP_Post    $post     Post ID or post object.
     * @param    string         $size     Image size (thumbnail, medium, large, full).
     * @param    string         $default  Default image URL if no featured image exists.
     * @return   string                   The featured image URL.
     */
    public static function get_featured_image_url($post, $size = 'full', $default = '') {
        viewpoints_plugin_log('Getting featured image for viewpoint: ' . (is_numeric($post) ? $post : $post->ID) . ', size: ' . $size);

        $post = get_post($post);
        if (!$post) {
            viewpoints_plugin_log('Invalid post: ' . print_r($post, true), 'error');
            return $default;
        }

        if (has_post_thumbnail($post)) {
            $image_id = get_post_thumbnail_id($post);
            $image = wp_get_attachment_image_src($image_id, $size);

            if ($image) {
                viewpoints_plugin_log('Found featured image: ' . $image[0]);
                return $image[0];
            }
        }

        viewpoints_plugin_log('No featured image found, using default: ' . $default);
        return $default;
    }

    /**
     * Get an ACF image field URL.
     *
     * @since    1.0.0
     * @param    string         $field_name  The ACF field name.
     * @param    int|WP_Post    $post        Post ID or post object.
     * @param    string         $size        Image size (thumbnail, medium, large, full).
     * @param    string         $default     Default image URL if no image exists.
     * @return   string                      The image URL.
     */
    public static function get_acf_image_url($field_name, $post = null, $size = 'full', $default = '') {
        viewpoints_plugin_log('Getting ACF image field: ' . $field_name . ' for post: ' . (is_numeric($post) ? $post : (is_object($post) ? $post->ID : 'current')));

        if (!function_exists('get_field')) {
            viewpoints_plugin_log('ACF not active', 'error');
            return $default;
        }

        $image = get_field($field_name, $post);

        if (empty($image)) {
            viewpoints_plugin_log('No image found in field: ' . $field_name);
            return $default;
        }

        // Handle different ACF image return formats
        if (is_array($image) && isset($image['sizes'][$size])) {
            viewpoints_plugin_log('Found image in array format: ' . $image['sizes'][$size]);
            return $image['sizes'][$size];
        } elseif (is_array($image) && isset($image['url'])) {
            viewpoints_plugin_log('Found image in array format with URL: ' . $image['url']);
            return $image['url'];
        } elseif (is_numeric($image)) {
            // Image is returned as ID
            $src = wp_get_attachment_image_src($image, $size);
            if ($src) {
                viewpoints_plugin_log('Found image by ID: ' . $src[0]);
                return $src[0];
            }
        }

        viewpoints_plugin_log('Could not determine image format, using default: ' . $default);
        return $default;
    }

	/**
	 * Get content excerpt with custom length.
	 *
	 * @since    1.0.0
	 * @param    string    $content     The content to get excerpt from.
	 * @param    int       $length      The length of the excerpt in characters (default: 120).
	 * @param    string    $more        What to append if content is longer than excerpt.
	 * @param    bool      $with_quotes Whether to add quotes around the excerpt.
	 * @return   string                 The excerpt.
	 */
	public static function get_excerpt($content, $length = 120, $more = '[...]', $with_quotes = true) {
		viewpoints_plugin_log('Generating excerpt with character length: ' . $length);

		// Strip shortcodes and HTML
		$excerpt = strip_shortcodes($content);
		$excerpt = strip_tags($excerpt);

		// Trim to desired character count (not word count)
		if (mb_strlen($excerpt) > $length) {
			$excerpt = mb_substr($excerpt, 0, $length) . $more;
		}

		// Add quotation marks if required
		if ($with_quotes) {
			$excerpt = '"' . $excerpt . '"';
		}

		viewpoints_plugin_log('Generated excerpt with final length: ' . mb_strlen($excerpt));
		return $excerpt;
	}

    /**
     * Sanitize and validate viewpoint data.
     *
     * @since    1.0.0
     * @param    array     $data        The data to sanitize.
     * @param    array     $allowed     Array of allowed fields and their sanitization functions.
     * @return   array                  The sanitized data.
     */
    public static function sanitize_viewpoint_data($data, $allowed = array()) {
        viewpoints_plugin_log('Sanitizing viewpoint data: ' . print_r($data, true));

        if (empty($allowed)) {
            // Default sanitization rules if none provided
            $allowed = array(
                'title' => 'sanitize_text_field',
                'content' => 'wp_kses_post',
                'excerpt' => 'sanitize_textarea_field',
                'status' => array('Viewpoints_Utils', 'sanitize_post_status')
            );
        }

        $sanitized = array();
        foreach ($allowed as $field => $sanitizer) {
            if (!isset($data[$field])) {
                continue;
            }

            if (is_callable($sanitizer)) {
                $sanitized[$field] = call_user_func($sanitizer, $data[$field]);
                viewpoints_plugin_log('Sanitized field: ' . $field);
            } else {
                // If sanitizer is not callable, keep the original value
                $sanitized[$field] = $data[$field];
                viewpoints_plugin_log('No sanitization applied to field: ' . $field, 'warning');
            }
        }

        viewpoints_plugin_log('Sanitization complete: ' . print_r($sanitized, true));
        return $sanitized;
    }

    /**
     * Sanitize post status.
     *
     * @since    1.0.0
     * @param    string    $status      The post status to sanitize.
     * @return   string                 Sanitized post status.
     */
    public static function sanitize_post_status($status) {
        $allowed_statuses = array('publish', 'draft', 'pending', 'private', 'future');

        if (!in_array($status, $allowed_statuses)) {
            viewpoints_plugin_log('Invalid post status: ' . $status . ', defaulting to draft', 'warning');
            return 'draft';
        }

        return $status;
    }

    /**
     * Cache a value with the plugin's prefix.
     *
     * @since    1.0.0
     * @param    string    $key         Cache key.
     * @param    mixed     $value       Value to cache.
     * @param    int       $expiration  Expiration time in seconds.
     * @return   bool                   True on success, false on failure.
     */
    public static function set_cache($key, $value, $expiration = 3600) {
        $prefixed_key = 'viewpoints_' . $key;
        viewpoints_plugin_log('Setting cache for key: ' . $prefixed_key);

        return set_transient($prefixed_key, $value, $expiration);
    }

    /**
     * Get a cached value.
     *
     * @since    1.0.0
     * @param    string    $key          Cache key.
     * @param    mixed     $default      Default value if cache is not found.
     * @return   mixed                   Cached value or default.
     */
    public static function get_cache($key, $default = false) {
        $prefixed_key = 'viewpoints_' . $key;
        viewpoints_plugin_log('Getting cache for key: ' . $prefixed_key);

        $value = get_transient($prefixed_key);

        if (false === $value) {
            viewpoints_plugin_log('Cache miss for key: ' . $prefixed_key);
            return $default;
        }

        viewpoints_plugin_log('Cache hit for key: ' . $prefixed_key);
        return $value;
    }

    /**
     * Delete a cached value.
     *
     * @since    1.0.0
     * @param    string    $key          Cache key.
     * @return   bool                    True on success, false on failure.
     */
    public static function delete_cache($key) {
        $prefixed_key = 'viewpoints_' . $key;
        viewpoints_plugin_log('Deleting cache for key: ' . $prefixed_key);

        return delete_transient($prefixed_key);
    }

    /**
     * Format a date according to the site's date format.
     *
     * @since    1.0.0
     * @param    string    $date         Date string or timestamp.
     * @param    string    $format       PHP date format (optional).
     * @return   string                  Formatted date.
     */
    public static function format_date($date, $format = '') {
        viewpoints_plugin_log('Formatting date: ' . $date);

        if (empty($format)) {
            $format = get_option('date_format');
        }

        if (is_numeric($date)) {
            $timestamp = $date;
        } else {
            $timestamp = strtotime($date);
        }

        if (false === $timestamp) {
            viewpoints_plugin_log('Invalid date format: ' . $date, 'error');
            return '';
        }

        $formatted = date_i18n($format, $timestamp);
        viewpoints_plugin_log('Formatted date: ' . $formatted);

        return $formatted;
    }
}