<?php
/**
 * Template handling for Staff post type
 *
 * @package PTCB_Staff
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PTCB Staff Templates Class
 *
 * Handles loading of custom templates for the staff post type
 */
class PTCB_Staff_Templates {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Filter to override single template for staff post type
		add_filter('single_template', array($this, 'load_staff_template'));

		// Add body classes for staff posts
		add_filter('body_class', array($this, 'add_staff_body_classes'));
	}

	/**
	 * Load custom template for staff single posts
	 *
	 * @param string $template The current template path
	 * @return string The modified template path
	 */
	public function load_staff_template($template) {
		global $post;

		// Only modify template for staff post type
		if (is_object($post) && $post->post_type === 'staff') {
			$custom_template = PTCB_STAFF_PLUGIN_DIR . 'templates/single-staff.php';

			// Use our custom template if it exists
			if (file_exists($custom_template)) {
				ptcb_staff()->log('Loading custom staff template: ' . $custom_template, 'info');
				return $custom_template;
			} else {
				ptcb_staff()->log('Custom staff template not found, using default: ' . $template, 'warning');
			}
		}

		return $template;
	}

	/**
	 * Add staff-specific body classes
	 *
	 * @param array $classes Current body classes
	 * @return array Modified body classes
	 */
	public function add_staff_body_classes($classes) {
		if (is_singular('staff')) {
			$classes[] = 'ptcb-staff-single';
			$classes[] = 'ptcb-staff-template';
		}

		return $classes;
	}

	/**
	 * Get the company title field
	 *
	 * @param int $post_id Optional post ID
	 * @return string The company title or empty string if not found
	 */
	public static function get_company_title($post_id = null) {
		if (!function_exists('get_field')) {
			ptcb_staff()->log('ACF get_field function not available', 'error');
			return '';
		}

		if (!$post_id) {
			$post_id = get_the_ID();
		}

		$company_title = get_field('company_title', $post_id);
		return $company_title ? $company_title : '';
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
}