<?php
/**
 * Helper functions for Staff plugin
 *
 * @package PTCB_Staff
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PTCB Staff Helpers Class
 *
 * Utility functions for the plugin
 */
class PTCB_Staff_Helpers {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Log initialization
		ptcb_staff()->log('PTCB_Staff_Helpers initialized', 'info');
	}

	/**
	 * Safely get ACF field with error handling
	 *
	 * @param string $field_name The ACF field name
	 * @param int $post_id Optional post ID
	 * @param mixed $default Default value if field doesn't exist
	 * @return mixed Field value or default
	 */
	public static function get_acf_field($field_name, $post_id = null, $default = '') {
		if (!function_exists('get_field')) {
			ptcb_staff()->log('ACF get_field function not available when trying to get ' . $field_name, 'error');
			return $default;
		}

		if (!$post_id) {
			$post_id = get_the_ID();
			ptcb_staff()->log('No post ID provided, using current post ID: ' . $post_id, 'debug');
		}

		ptcb_staff()->log('Getting ACF field: ' . $field_name . ' for post ID: ' . $post_id, 'debug');
		$value = get_field($field_name, $post_id);

		if (empty($value)) {
			ptcb_staff()->log('ACF field ' . $field_name . ' is empty for post ID: ' . $post_id . ', using default value', 'debug');
			return $default;
		}

		ptcb_staff()->log('Successfully retrieved ACF field: ' . $field_name, 'debug');
		return $value;
	}

	/**
	 * Get company title field
	 *
	 * @param int $post_id Optional post ID
	 * @return string Company title or empty string
	 */
	public static function get_company_title($post_id = null) {
		ptcb_staff()->log('Getting company title for post ID: ' . ($post_id ?: 'current post'), 'debug');
		$title = self::get_acf_field('company_title', $post_id, '');
		ptcb_staff()->log('Company title value: ' . ($title ?: 'empty'), 'debug');
		return $title;
	}

	/**
	 * Display the company title with proper markup
	 *
	 * @param int $post_id Optional post ID
	 * @param bool $echo Whether to echo or return the HTML
	 * @return string|void The HTML if $echo is false
	 */
	public static function the_company_title($post_id = null, $echo = true) {
		ptcb_staff()->log('Generating company title HTML for post ID: ' . ($post_id ?: 'current post'), 'debug');

		$company_title = self::get_company_title($post_id);

		if (empty($company_title)) {
			ptcb_staff()->log('Company title is empty, returning empty string', 'debug');
			return '';
		}

		$html = '<div class="ptcb-staff-company-title">' . esc_html($company_title) . '</div>';
		ptcb_staff()->log('Generated company title HTML', 'debug');

		if ($echo) {
			ptcb_staff()->log('Echoing company title HTML', 'debug');
			echo $html;
		} else {
			ptcb_staff()->log('Returning company title HTML', 'debug');
			return $html;
		}
	}

	/**
	 * Get staff featured image with fallback
	 *
	 * @param int $post_id Optional post ID
	 * @param string $size Image size
	 * @param array $attr Image attributes
	 * @return string HTML image tag or empty string
	 */
	public static function get_staff_image($post_id = null, $size = 'medium', $attr = array()) {
		if (!$post_id) {
			$post_id = get_the_ID();
			ptcb_staff()->log('No post ID provided for image, using current post ID: ' . $post_id, 'debug');
		}

		ptcb_staff()->log('Getting staff image for post ID: ' . $post_id . ' with size: ' . $size, 'debug');

		// Set default class for the image
		if (!isset($attr['class'])) {
			$attr['class'] = 'ptcb-staff-image';
		} else {
			$attr['class'] .= ' ptcb-staff-image';
		}

		ptcb_staff()->log('Image attributes: ' . print_r($attr, true), 'debug');

		// Get the featured image
		if (has_post_thumbnail($post_id)) {
			ptcb_staff()->log('Featured image found for post ID: ' . $post_id, 'debug');
			return get_the_post_thumbnail($post_id, $size, $attr);
		}

		// No image found
		ptcb_staff()->log('No featured image found for post ID: ' . $post_id, 'warning');
		return '';
	}

	/**
	 * Get all staff members with custom query
	 *
	 * @param array $args WP_Query arguments
	 * @return array Array of WP_Post objects
	 */
	public static function get_staff_members($args = array()) {
		$default_args = array(
			'post_type' => 'staff',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_status' => 'publish',
		);

		$args = wp_parse_args($args, $default_args);

		ptcb_staff()->log('Querying staff members with args: ' . print_r($args, true), 'info');

		$staff_query = new WP_Query($args);

		if ($staff_query->have_posts()) {
			ptcb_staff()->log('Query found ' . count($staff_query->posts) . ' staff members', 'info');
			return $staff_query->posts;
		}

		ptcb_staff()->log('No staff members found in query', 'warning');
		return array();
	}

	/**
	 * Check if we're on a staff single post
	 *
	 * @return bool True if viewing a staff single post
	 */
	public static function is_staff_single() {
		$is_staff = is_singular('staff');
		ptcb_staff()->log('Checking if current page is a staff single: ' . ($is_staff ? 'true' : 'false'), 'debug');
		return $is_staff;
	}
}