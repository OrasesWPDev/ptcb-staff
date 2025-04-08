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

		// Add custom permalink structure for staff post type
		add_filter('post_type_link', array($this, 'staff_permalink_structure'), 10, 2);

		// Add rewrite rules for staff custom permalinks
		add_action('init', array($this, 'add_staff_rewrite_rules'), 10);

		// Log initialization
		ptcb_staff()->log('PTCB_Staff_Templates initialized', 'info');
		add_filter('query_vars', array($this, 'register_query_vars'));
		add_action('pre_get_posts', array($this, 'pre_get_posts'));
	}

	/**
	 * Load custom template for staff single posts
	 *
	 * @param string $template The current template path
	 * @return string The modified template path
	 */
	public function load_staff_template($template) {
		global $post;

		// Debug logs
		ptcb_staff()->log('Template loading function called for post ID: ' . ($post ? $post->ID : 'none'), 'debug');
		ptcb_staff()->log('Post type: ' . ($post ? $post->post_type : 'none'), 'debug');
		ptcb_staff()->log('Current template path: ' . $template, 'debug');

		// Only modify template for staff post type
		if (is_object($post) && $post->post_type === 'staff') {
			ptcb_staff()->log('Processing template for staff post ID: ' . $post->ID, 'info');

			$custom_template = PTCB_STAFF_PLUGIN_DIR . 'templates/single-staff.php';

			// Use our custom template if it exists
			if (file_exists($custom_template)) {
				ptcb_staff()->log('Loading custom staff template: ' . $custom_template, 'info');
				return $custom_template;
			} else {
				ptcb_staff()->log('Custom staff template not found at: ' . $custom_template, 'warning');
				ptcb_staff()->log('Using default template: ' . $template, 'info');
			}
		} else {
			ptcb_staff()->log('Post is not staff type or not valid object', 'debug');
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
			ptcb_staff()->log('Adding staff body classes', 'info');
			$classes[] = 'ptcb-staff-single';
			$classes[] = 'ptcb-staff-template';
			ptcb_staff()->log('Added classes: ptcb-staff-single, ptcb-staff-template', 'debug');
		}

		return $classes;
	}

	/**
	 * Register custom query variables
	 *
	 * @param array $vars Query variables
	 * @return array Modified query variables
	 */
	public function register_query_vars($vars) {
		$vars[] = 'staff';
		ptcb_staff()->log('Registered staff query variable', 'debug');
		return $vars;
	}

	/**
	 * Set up post data for custom permalinks
	 *
	 * @param WP_Query $query The main query
	 */
	public function pre_get_posts($query) {
		// Only modify main query
		if (!$query->is_main_query()) {
			return;
		}

		// Check if our query var is present
		$staff_slug = $query->get('staff');
		if (!empty($staff_slug)) {
			// Tell WordPress this is a single staff post
			$query->set('post_type', 'staff');
			$query->set('name', $staff_slug);
			$query->is_single = true;
			$query->is_singular = true;

			ptcb_staff()->log('Modified main query for staff slug: ' . $staff_slug, 'debug');
		}
	}

	/**
	 * Modify permalink structure for staff posts
	 *
	 * @param string $post_link The post's permalink
	 * @param WP_Post $post The post object
	 * @return string Modified permalink
	 */
	public function staff_permalink_structure($post_link, $post) {
		// Only modify staff post type permalinks
		if ($post->post_type !== 'staff') {
			return $post_link;
		}

		ptcb_staff()->log('Modifying permalink for staff post: ' . $post->ID, 'debug');

		// Get the post slug
		$slug = $post->post_name;

		// Create the custom permalink structure
		$custom_link = home_url('/ptcb-team/ptcb-staff/' . $slug . '/');

		ptcb_staff()->log('Custom permalink: ' . $custom_link, 'debug');

		return $custom_link;
	}

	/**
	 * Add rewrite rules for staff custom permalinks
	 */
	public function add_staff_rewrite_rules() {
		// Create rewrite rule for /ptcb-team/ptcb-staff/staff-member/
		add_rewrite_rule(
			'ptcb-team/ptcb-staff/([^/]+)/?$',
			'index.php?staff=$matches[1]',
			'top'
		);

		ptcb_staff()->log('Added staff custom rewrite rules', 'debug');
	}
} // End of class PTCB_Staff_Templates