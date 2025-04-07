<?php
/**
 * Plugin Name: PTCB Staff
 * Plugin URI: https://github.com/OrasesWPDev/ptcb-staff
 * Description: Custom WordPress plugin for managing staff profiles with ACF Pro integration
 * Version: 1.0.0
 * Author: Orases
 * Author URI: https://orases.com
 *
 * @package PTCB_Staff
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
// CRITICAL CHANGE: Disable debug mode completely during development
define('PTCB_STAFF_DEBUG_MODE', true);
// Define plugin constants
define('PTCB_STAFF_VERSION', '1.0.0');
define('PTCB_STAFF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PTCB_STAFF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PTCB_STAFF_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PTCB_STAFF_LOG_DIR', PTCB_STAFF_PLUGIN_DIR . 'logs/');
/**
 * Main PTCB Staff Plugin Class
 */
final class PTCB_Staff {
	/**
	 * Singleton instance
	 */
	private static $instance = null;
	/**
	 * Store loaded classes
	 */
	private $loaded_classes = [];
	/**
	 * Get the singleton instance
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Constructor - SIMPLIFIED TO BARE MINIMUM
	 */
	private function __construct() {
		// IMPORTANT: Only initialize hooks during constructor
		// All other initialization is deferred until after activation
		$this->init_hooks();
	}
	/**
	 * Setup debug logging - only if explicitly needed
	 */
	private function setup_debug_logging() {
		if (!PTCB_STAFF_DEBUG_MODE) {
			return false;
		}
		// Only try to create directory if it doesn't exist
		if (!file_exists(PTCB_STAFF_LOG_DIR)) {
			if (!@mkdir(PTCB_STAFF_LOG_DIR, 0755, true)) {
				return false;
			}
			@file_put_contents(PTCB_STAFF_LOG_DIR . '.htaccess',
				"# Prevent direct access to files\n" .
				"<FilesMatch \"\.log$\">\n" .
				"Order allow,deny\n" .
				"Deny from all\n" .
				"</FilesMatch>\n"
			);
			@file_put_contents(PTCB_STAFF_LOG_DIR . 'index.html', '<!-- Silence is golden -->');
		}
		return true;
	}
	/**
	 * Log a message when debug mode is enabled
	 */
	public function log($message, $level = 'info') {
		if (!PTCB_STAFF_DEBUG_MODE) {
			return false;
		}
		try {
			// Set timezone to EST
			$date = new DateTime('now', new DateTimeZone('America/New_York'));
			$timestamp = $date->format('Y-m-d H:i:s');
			// Format log message
			$log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
			// Write to log file with error suppression
			$log_file = PTCB_STAFF_LOG_DIR . 'ptcb-staff-' . date('Y-m-d') . '.log';
			return (bool)@file_put_contents($log_file, $log_message, FILE_APPEND);
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * Initialize hooks - minimal version
	 */
	private function init_hooks() {
		// CRITICAL: Register activation but don't do any processing in the hook yet
		register_activation_hook(__FILE__, array($this, 'activation'));
		register_deactivation_hook(__FILE__, array($this, 'deactivation'));
		// We'll initialize the plugin on init after activation is complete
		add_action('init', array($this, 'init_plugin'), 20); // Late priority
	}
	/**
	 * Plugin activation - SIMPLIFIED
	 */
	public function activation() {
		// Just flush rewrite rules and exit - no processing during activation
		flush_rewrite_rules();
	}
	/**
	 * Plugin deactivation - SIMPLIFIED
	 */
	public function deactivation() {
		flush_rewrite_rules();
	}
	/**
	 * Initialize the plugin after WordPress is fully loaded
	 * This runs during normal page load, not during activation
	 */
	public function init_plugin() {
		// Now it's safe to set up debug logging
		$this->setup_debug_logging();
		// Now it's safe to load includes
		$this->load_files();
		// Check for ACF Pro dependency
		add_action('admin_init', array($this, 'check_dependencies'));
		// Load assets
		add_action('wp_enqueue_scripts', array($this, 'register_assets'));

		// Add high-priority enqueue for CSS to override theme styles
		add_action('wp_footer', array($this, 'register_override_styles'), 999);
	}
	/**
	 * Load core files - SIMPLIFIED
	 */
	private function load_files() {
		// Only load files if they exist
		$template_file = PTCB_STAFF_PLUGIN_DIR . 'includes/class-ptcb-staff-templates.php';
		$helper_file = PTCB_STAFF_PLUGIN_DIR . 'includes/class-ptcb-staff-helpers.php';
		$shortcode_file = PTCB_STAFF_PLUGIN_DIR . 'includes/class-ptcb-staff-shortcodes.php';
		if (file_exists($template_file)) {
			include_once $template_file;
			if (class_exists('PTCB_Staff_Templates')) {
				$this->loaded_classes['PTCB_Staff_Templates'] = new PTCB_Staff_Templates();
			}
		}
		if (file_exists($helper_file)) {
			include_once $helper_file;
			if (class_exists('PTCB_Staff_Helpers')) {
				$this->loaded_classes['PTCB_Staff_Helpers'] = new PTCB_Staff_Helpers();
			}
		}
		if (file_exists($shortcode_file)) {
			include_once $shortcode_file;
			if (class_exists('PTCB_Staff_Shortcodes')) {
				$this->loaded_classes['PTCB_Staff_Shortcodes'] = new PTCB_Staff_Shortcodes();
			}
		}
	}
	/**
	 * Check for required plugins
	 */
	public function check_dependencies() {
		if (!class_exists('acf')) {
			add_action('admin_notices', array($this, 'acf_missing_notice'));
		}
		if (!class_exists('acf_pro')) {
			add_action('admin_notices', array($this, 'acf_pro_missing_notice'));
		}
	}
	/**
	 * Display ACF missing notice
	 */
	public function acf_missing_notice() {
		?>
        <div class="notice notice-error">
            <p><?php _e('PTCB Staff Plugin requires Advanced Custom Fields to be installed and activated.', 'ptcb-staff'); ?></p>
        </div>
		<?php
	}
	/**
	 * Display ACF Pro missing notice
	 */
	public function acf_pro_missing_notice() {
		?>
        <div class="notice notice-error">
            <p><?php _e('PTCB Staff Plugin requires the PRO version of Advanced Custom Fields to be installed and activated.', 'ptcb-staff'); ?></p>
        </div>
		<?php
	}
	/**
	 * Register CSS and JS assets with dynamic versioning
	 * UPDATED: Load CSS on all pages where shortcode might be used
	 */
	public function register_assets() {
		// Register and enqueue CSS with dynamic versioning for all pages
		$css_file = PTCB_STAFF_PLUGIN_DIR . 'assets/css/ptcb-staff.css';
		if (file_exists($css_file)) {
			$css_version = filemtime($css_file);
			wp_enqueue_style(
				'ptcb-staff',
				PTCB_STAFF_PLUGIN_URL . 'assets/css/ptcb-staff.css',
				array(),
				$css_version
			);
		}

		// Only load JS on single staff pages
		if (is_singular('staff')) {
			// Register and enqueue JS with dynamic versioning
			$js_file = PTCB_STAFF_PLUGIN_DIR . 'assets/js/ptcb-staff.js';
			if (file_exists($js_file)) {
				$js_version = filemtime($js_file);
				wp_enqueue_script(
					'ptcb-staff',
					PTCB_STAFF_PLUGIN_URL . 'assets/js/ptcb-staff.js',
					array('jquery'),
					$js_version,
					true
				);
			}
		}
	}

	/**
	 * Register high-priority CSS to override theme styles
	 * NEW METHOD: Ensures our CSS is applied last
	 */
	public function register_override_styles() {
		$css_file = PTCB_STAFF_PLUGIN_DIR . 'assets/css/ptcb-staff.css';
		if (file_exists($css_file)) {
			$css_version = filemtime($css_file);
			wp_enqueue_style(
				'ptcb-staff-override',
				PTCB_STAFF_PLUGIN_URL . 'assets/css/ptcb-staff.css',
				array(),
				$css_version
			);
		}
	}
}
/**
 * Main function to initialize the plugin
 */
function ptcb_staff() {
	return PTCB_Staff::get_instance();
}
// Initialize the plugin
ptcb_staff();