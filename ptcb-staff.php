<?php
/**
 * Plugin Name: PTCB Staff
 * Plugin URI:
 * Description: Custom WordPress plugin for managing staff profiles with ACF Pro integration
 * Version: 1.0.0
 * Author:
 * Author URI:
 *
 * @package PTCB_Staff
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Debug mode - enabled for development
 * Controls logging and visualization features throughout the plugin.
 * Particularly useful for template and hook debugging with Flatsome theme.
 */
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
	 *
	 * @var PTCB_Staff
	 */
	private static $instance = null;

	/**
	 * Store loaded classes
	 *
	 * @var array
	 */
	private $loaded_classes = [];

	/**
	 * Get the singleton instance
	 *
	 * @return PTCB_Staff
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Setup debug logging
		$this->setup_debug_logging();

		// Auto-load includes
		$this->autoload_includes();

		// Initialize hooks
		$this->init_hooks();

		// Log initialization
		$this->log('Plugin initialized', 'info');
	}

	/**
	 * Setup debug logging
	 */
	private function setup_debug_logging() {
		if (PTCB_STAFF_DEBUG_MODE && !file_exists(PTCB_STAFF_LOG_DIR)) {
			mkdir(PTCB_STAFF_LOG_DIR, 0755, true);

			// Create .htaccess file to prevent direct access
			$htaccess_content = "# Prevent direct access to files\n";
			$htaccess_content .= "<FilesMatch \"\.log$\">\n";
			$htaccess_content .= "Order allow,deny\n";
			$htaccess_content .= "Deny from all\n";
			$htaccess_content .= "</FilesMatch>\n";

			file_put_contents(PTCB_STAFF_LOG_DIR . '.htaccess', $htaccess_content);

			// Create index.html to prevent directory listing
			file_put_contents(PTCB_STAFF_LOG_DIR . 'index.html', '<!-- Silence is golden -->');
		}
	}

	/**
	 * Log a message when debug mode is enabled
	 *
	 * @param string $message The message to log
	 * @param string $level   The severity level (info, warning, error)
	 */
	public function log($message, $level = 'info') {
		if (!PTCB_STAFF_DEBUG_MODE) {
			return;
		}

		// Set timezone to EST
		$date = new DateTime('now', new DateTimeZone('America/New_York'));
		$timestamp = $date->format('Y-m-d H:i:s');

		// Format log message
		$log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

		// Write to log file
		$log_file = PTCB_STAFF_LOG_DIR . 'ptcb-staff-' . date('Y-m-d') . '.log';
		file_put_contents($log_file, $log_message, FILE_APPEND);
	}

	/**
	 * Auto-load included files
	 */
	private function autoload_includes() {
		// Core includes directory
		$this->load_files_from_directory(PTCB_STAFF_PLUGIN_DIR . 'includes');

		// Admin files if in admin
		if (is_admin()) {
			$admin_dir = PTCB_STAFF_PLUGIN_DIR . 'admin';
			if (file_exists($admin_dir)) {
				$this->load_files_from_directory($admin_dir);
			}
		}
	}

	/**
	 * Load all PHP files from a directory
	 *
	 * @param string $dir The directory to load files from
	 */
	private function load_files_from_directory($dir) {
		if (!file_exists($dir)) {
			$this->log("Directory does not exist: {$dir}", 'warning');
			return;
		}

		$files = glob($dir . '/*.php');
		foreach ($files as $file) {
			$this->load_file($file);
		}
	}

	/**
	 * Load a single PHP file
	 *
	 * @param string $file The file path to load
	 */
	private function load_file($file) {
		$filename = basename($file);

		// Skip index files
		if ($filename === 'index.php') {
			return;
		}

		// Load the file
		require_once $file;

		// Check if this is a class file
		if (strpos($filename, 'class-') === 0) {
			$this->maybe_instantiate_class($file);
		}

		$this->log("Loaded file: {$filename}", 'info');
	}

	/**
	 * Try to instantiate a class from the file
	 *
	 * @param string $file The file path that might contain a class
	 */
	private function maybe_instantiate_class($file) {
		$filename = basename($file, '.php');
		$class_name = $this->filename_to_classname($filename);

		if (class_exists($class_name)) {
			// Only instantiate if not already loaded
			if (!isset($this->loaded_classes[$class_name])) {
				$this->loaded_classes[$class_name] = new $class_name();
				$this->log("Instantiated class: {$class_name}", 'info');
			}
		}
	}

	/**
	 * Convert filename to class name
	 *
	 * @param string $filename The filename without extension
	 * @return string The expected class name
	 */
	private function filename_to_classname($filename) {
		// Remove 'class-' prefix
		$name = str_replace('class-', '', $filename);

		// Convert to CamelCase
		$name = str_replace('-', '_', $name);
		$name = str_replace('_', ' ', $name);
		$name = ucwords($name);
		$name = str_replace(' ', '_', $name);

		return $name;
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		register_activation_hook(__FILE__, array($this, 'activation'));
		register_deactivation_hook(__FILE__, array($this, 'deactivation'));

		// Check for ACF Pro dependency
		add_action('admin_init', array($this, 'check_dependencies'));

		// Load assets only on single post type pages
		add_action('wp_enqueue_scripts', array($this, 'register_assets'));
	}

	/**
	 * Plugin activation
	 */
	public function activation() {
		// Create required directories
		if (!file_exists(PTCB_STAFF_PLUGIN_DIR . 'includes')) {
			mkdir(PTCB_STAFF_PLUGIN_DIR . 'includes', 0755, true);
		}

		if (!file_exists(PTCB_STAFF_PLUGIN_DIR . 'assets/css')) {
			mkdir(PTCB_STAFF_PLUGIN_DIR . 'assets/css', 0755, true);
		}

		if (!file_exists(PTCB_STAFF_PLUGIN_DIR . 'assets/js')) {
			mkdir(PTCB_STAFF_PLUGIN_DIR . 'assets/js', 0755, true);
		}

		if (!file_exists(PTCB_STAFF_PLUGIN_DIR . 'templates')) {
			mkdir(PTCB_STAFF_PLUGIN_DIR . 'templates', 0755, true);
		}

		// Flush rewrite rules
		flush_rewrite_rules();

		$this->log('Plugin activated', 'info');
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivation() {
		// Flush rewrite rules
		flush_rewrite_rules();

		$this->log('Plugin deactivated', 'info');
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
	 */
	public function register_assets() {
		// Only load assets on relevant single post type pages
		// You'll need to update 'staff' with your actual custom post type name
		if (is_singular('staff')) {
			// Register and enqueue CSS with dynamic versioning
			$css_file = PTCB_STAFF_PLUGIN_DIR . 'assets/css/ptcb-staff.css';
			$css_version = file_exists($css_file) ? filemtime($css_file) : PTCB_STAFF_VERSION;
			wp_enqueue_style(
				'ptcb-staff',
				PTCB_STAFF_PLUGIN_URL . 'assets/css/ptcb-staff.css',
				array(),
				$css_version
			);
			$this->log('Enqueued CSS file with version: ' . $css_version, 'info');

			// Register and enqueue JS with dynamic versioning
			$js_file = PTCB_STAFF_PLUGIN_DIR . 'assets/js/ptcb-staff.js';
			$js_version = file_exists($js_file) ? filemtime($js_file) : PTCB_STAFF_VERSION;
			wp_enqueue_script(
				'ptcb-staff',
				PTCB_STAFF_PLUGIN_URL . 'assets/js/ptcb-staff.js',
				array('jquery'),
				$js_version,
				true
			);
			$this->log('Enqueued JS file with version: ' . $js_version, 'info');
		}
	}
}

/**
 * Main function to initialize the plugin
 *
 * @return PTCB_Staff The main plugin instance
 */
function ptcb_staff() {
	return PTCB_Staff::get_instance();
}

// Initialize the plugin
ptcb_staff();