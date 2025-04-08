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
 * Modify the staff post type registration to use our custom permalink structure
 */
function ptcb_staff_modify_post_type() {
	global $wp_post_types;

	// Make sure the post type exists
	if (isset($wp_post_types['staff'])) {
		// Modify the rewrite rules
		$wp_post_types['staff']->rewrite = array(
			'slug' => 'ptcb-team/ptcb-staff',
			'with_front' => false,
			'feeds' => false,
			'pages' => true
		);

		// Ensure the post type has an archive
		$wp_post_types['staff']->has_archive = true;

		// Log the modification
		// Note: ptcb_staff() might not be available if called too early, adjust logging if needed
		if (function_exists('ptcb_staff') && PTCB_STAFF_DEBUG_MODE) {
			ptcb_staff()->log('Modified staff post type rewrite rules to use ptcb-team/ptcb-staff slug', 'info');
		}


		// Flush rewrite rules once - Check if option exists before potentially calling ptcb_staff()
		$option_exists = get_option('ptcb_staff_post_type_modified', 'not_found');
		if ($option_exists !== 'yes') {
			flush_rewrite_rules();
			update_option('ptcb_staff_post_type_modified', 'yes');
			// Note: ptcb_staff() might not be available if called too early, adjust logging if needed
			if (function_exists('ptcb_staff') && PTCB_STAFF_DEBUG_MODE) {
				ptcb_staff()->log('Flushed rewrite rules after modifying staff post type (via init hook)', 'info');
			}
		}
	}
}

// Run before init (priority 1) to modify the post type early
// This needs to run early to define the CPT rewrite base slug
add_action('init', 'ptcb_staff_modify_post_type', 1);


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
				// Cannot create log directory, disable logging for this request
				// define('PTCB_STAFF_DEBUG_MODE', false); // Avoid using define again
				return false; // Indicate failure
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
		return true; // Indicate success or already exists
	}
	/**
	 * Log a message when debug mode is enabled
	 */
	public function log($message, $level = 'info') {
		// Double check debug mode in case setup failed
		if (!PTCB_STAFF_DEBUG_MODE || !file_exists(PTCB_STAFF_LOG_DIR) || !is_writable(PTCB_STAFF_LOG_DIR)) {
			return false;
		}
		try {
			// Set timezone to EST
			$date = new DateTime('now', new DateTimeZone('America/New_York'));
			$timestamp = $date->format('Y-m-d H:i:s');
			// Format log message
			$log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
			// Write to log file with error suppression
			$log_file = PTCB_STAFF_LOG_DIR . 'ptcb-staff-' . $date->format('Y-m-d') . '.log';
			return (bool)@file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX); // Added LOCK_EX
		} catch (Exception $e) {
			// Log potential exception during logging itself? Maybe not.
			return false;
		}
	}
	/**
	 * Initialize hooks - minimal version
	 */
	private function init_hooks() {
		// CRITICAL: Register activation/deactivation hooks
		register_activation_hook(__FILE__, array($this, 'activation'));
		register_deactivation_hook(__FILE__, array($this, 'deactivation'));

		// *** CHANGE HERE: Use plugins_loaded instead of init ***
		// We'll initialize the main plugin parts after all plugins are loaded, but before init
		add_action('plugins_loaded', array($this, 'init_plugin'));
	}
	/**
	 * Plugin activation - SIMPLIFIED
	 */
	public function activation() {
		// Ensure post type modifications are registered *before* flushing
		ptcb_staff_modify_post_type();
		flush_rewrite_rules();
		// Log activation if possible (logging might not be fully set up yet)
		if (method_exists($this, 'log')) {
			$this->log('Plugin activated and rewrite rules flushed.', 'info');
		} else if (defined('PTCB_STAFF_DEBUG_MODE') && PTCB_STAFF_DEBUG_MODE) {
			error_log('PTCB Staff Plugin activated and rewrite rules flushed.');
		}
		// Ensure the option flag is removed so the init flush runs if needed
		delete_option('ptcb_staff_post_type_modified');
	}
	/**
	 * Plugin deactivation - SIMPLIFIED
	 */
	public function deactivation() {
		flush_rewrite_rules();
		// Remove the flag so rules get flushed again on next activation if needed
		delete_option('ptcb_staff_post_type_modified');
		// Log deactivation if possible
		if (method_exists($this, 'log')) {
			$this->log('Plugin deactivated and rewrite rules flushed.', 'info');
		} else if (defined('PTCB_STAFF_DEBUG_MODE') && PTCB_STAFF_DEBUG_MODE) {
			error_log('PTCB Staff Plugin deactivated and rewrite rules flushed.');
		}
	}
	/**
	 * Initialize the plugin after plugins are loaded
	 */
	public function init_plugin() {
		// Set up debug logging first
		$logging_ok = $this->setup_debug_logging();
		if ($logging_ok) {
			$this->log('init_plugin started on plugins_loaded hook.', 'info');
		}

		// Now load include files
		$this->load_files();

		// Add hooks that should run later than plugins_loaded
		add_action('admin_init', array($this, 'check_dependencies'));
		add_action('wp_enqueue_scripts', array($this, 'register_assets'));
		add_action('wp_footer', array($this, 'register_override_styles'), 999);

		if ($logging_ok) {
			$this->log('init_plugin completed.', 'info');
		}
	}
	/**
	 * Load core files
	 */
	private function load_files() {
		$load_error = false;
		// Only load files if they exist
		$template_file = PTCB_STAFF_PLUGIN_DIR . 'includes/class-ptcb-staff-templates.php';
		$helper_file = PTCB_STAFF_PLUGIN_DIR . 'includes/class-ptcb-staff-helpers.php';
		$shortcode_file = PTCB_STAFF_PLUGIN_DIR . 'includes/class-ptcb-staff-shortcodes.php';

		if (file_exists($template_file)) {
			include_once $template_file;
			if (class_exists('PTCB_Staff_Templates')) {
				$this->loaded_classes['PTCB_Staff_Templates'] = new PTCB_Staff_Templates();
				$this->log('Loaded and instantiated PTCB_Staff_Templates.', 'debug');
			} else {
				$this->log('Included class-ptcb-staff-templates.php but class PTCB_Staff_Templates not found!', 'error');
				$load_error = true;
			}
		} else {
			$this->log('Template file not found: ' . $template_file, 'error');
			$load_error = true;
		}

		if (file_exists($helper_file)) {
			include_once $helper_file;
			if (class_exists('PTCB_Staff_Helpers')) {
				$this->loaded_classes['PTCB_Staff_Helpers'] = new PTCB_Staff_Helpers();
				$this->log('Loaded and instantiated PTCB_Staff_Helpers.', 'debug');
			} else {
				$this->log('Included class-ptcb-staff-helpers.php but class PTCB_Staff_Helpers not found!', 'error');
				$load_error = true;
			}
		} else {
			$this->log('Helper file not found: ' . $helper_file, 'error');
			$load_error = true;
		}

		if (file_exists($shortcode_file)) {
			include_once $shortcode_file;
			if (class_exists('PTCB_Staff_Shortcodes')) {
				$this->loaded_classes['PTCB_Staff_Shortcodes'] = new PTCB_Staff_Shortcodes();
				$this->log('Loaded and instantiated PTCB_Staff_Shortcodes.', 'debug');
			} else {
				$this->log('Included class-ptcb-staff-shortcodes.php but class PTCB_Staff_Shortcodes not found!', 'error');
				$load_error = true;
			}
		} else {
			$this->log('Shortcode file not found: ' . $shortcode_file, 'error');
			$load_error = true;
		}

		if ($load_error) {
			// Optional: Add an admin notice if files are missing?
		}
	}
	/**
	 * Check for required plugins
	 */
	public function check_dependencies() {
		if (!class_exists('acf')) {
			add_action('admin_notices', array($this, 'acf_missing_notice'));
			$this->log('Dependency check failed: ACF class not found.', 'warning');
		}
		if (!class_exists('acf_pro')) {
			add_action('admin_notices', array($this, 'acf_pro_missing_notice'));
			$this->log('Dependency check failed: ACF Pro class not found.', 'warning');
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
		$this->log('Registering assets via wp_enqueue_scripts.', 'debug');
		// Register and enqueue CSS with dynamic versioning for all pages
		$css_file = PTCB_STAFF_PLUGIN_DIR . 'assets/css/ptcb-staff.css';
		if (file_exists($css_file)) {
			$css_version = filemtime($css_file);
			wp_enqueue_style(
				'ptcb-staff',
				PTCB_STAFF_PLUGIN_URL . 'assets/css/ptcb-staff.css',
				array(), // Dependencies
				$css_version
			);
			$this->log('Enqueued ptcb-staff CSS version ' . $css_version, 'debug');
		} else {
			$this->log('ptcb-staff CSS file not found: ' . $css_file, 'warning');
		}

		// Only load JS on single staff pages - Note: JS file doesn't seem to exist in context
		// Check if is_singular('staff') works correctly now
		if (is_singular('staff')) {
			$this->log('Attempting to enqueue JS for single staff page.', 'debug');
			// Register and enqueue JS with dynamic versioning
			$js_file = PTCB_STAFF_PLUGIN_DIR . 'assets/js/ptcb-staff.js'; // Assumes this path
			if (file_exists($js_file)) {
				$js_version = filemtime($js_file);
				wp_enqueue_script(
					'ptcb-staff',
					PTCB_STAFF_PLUGIN_URL . 'assets/js/ptcb-staff.js', // Assumes this path
					array('jquery'), // Dependencies
					$js_version,
					true // In footer
				);
				$this->log('Enqueued ptcb-staff JS version ' . $js_version, 'debug');
			} else {
				$this->log('ptcb-staff JS file not found: ' . $js_file, 'warning');
			}
		}
	}

	/**
	 * Register high-priority CSS to override theme styles
	 */
	public function register_override_styles() {
		$this->log('Registering override styles via wp_footer.', 'debug');
		$css_file = PTCB_STAFF_PLUGIN_DIR . 'assets/css/ptcb-staff.css';
		if (file_exists($css_file)) {
			$css_version = filemtime($css_file);
			// Use a different handle to ensure it's treated as a separate enqueue
			wp_enqueue_style(
				'ptcb-staff-override',
				PTCB_STAFF_PLUGIN_URL . 'assets/css/ptcb-staff.css',
				array(), // Dependencies - should ideally depend on 'ptcb-staff' if needed
				$css_version
			);
			$this->log('Enqueued ptcb-staff-override CSS version ' . $css_version, 'debug');
		}
		// Note: Enqueuing the same CSS file twice (once in wp_enqueue_scripts, once in wp_footer)
		// might be redundant if the goal is just late loading. wp_enqueue_scripts with a late priority
		// or changing the dependency array might be alternatives. Keep as is for now.
	}
}
/**
 * Main function to initialize the plugin instance
 * Provides a global access point to the plugin instance.
 */
function ptcb_staff() {
	return PTCB_Staff::get_instance();
}
// Initialize the plugin immediately
ptcb_staff();
