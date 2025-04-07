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
		// Nothing to initialize
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
			ptcb_staff()->log('ACF get_field function not available', 'error');
			return $default;
		}

		if (!$post_id) {
			$post_id = get_the_ID();
		}

		$value = get_field($field_name, $post_id);
		return !empty($value) ? $value : $default;
	}

	/**
	 * Get company title field
	 *
	 * @param int $post_id Optional post ID
	 * @return string Company title or empty string
	 */
	public static function get_company_title($post_id = null) {
		return self::get_acf_field('company_title', $post_id, '');
	}

	/**
	 * Display the company title with proper markup
	 *
	 * @param int $post_id Optional post ID
	 * @param bool $echo Whether to echo or return the HTML
	 * @return string|void The HTML if $echo is false
	 */
	public static function the_company_title($post_id = null, $echo = true) {
		$company_title = self::get_company_title($post_id);

		if (empty($company_title)) {
			return '';
		}

		$html = '<div class="ptcb-staff-company-title">' . esc_html($company_title) . '</div>';

		if ($echo) {
			echo $html;
		} else {
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
		}

		// Set default class for the image
		if (!isset($attr['class'])) {
			$attr['class'] = 'ptcb-staff-image';
		} else {
			$attr['class'] .= ' ptcb-staff-image';
		}

		// Get the featured image
		if (has_post_thumbnail($post_id)) {
			return get_the_post_thumbnail($post_id, $size, $attr);
		}

		// No image found
		ptcb_staff()->log('No featured image for staff ID: ' . $post_id, 'info');
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

		$staff_query = new WP_Query($args);

		if ($staff_query->have_posts()) {
			return $staff_query->posts;
		}

		return array();
	}

	/**
	 * Check if we're on a staff single post
	 *
	 * @return bool True if viewing a staff single post
	 */
	public static function is_staff_single() {
		return is_singular('staff');
	}
}