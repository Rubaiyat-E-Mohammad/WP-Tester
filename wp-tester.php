<?php
/**
 * Plugin Name: WP Tester
 * Plugin URI: https://github.com/Rubaiyat-E-Mohammad/WP-Tester
 * Description: Automatically tests all user flows on a WordPress site and produces detailed feedback without generating coded test scripts.
 * Version: 1.0.5
 * Author: REMTech
 * Author URI: https://github.com/Rubaiyat-E-Mohammad
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-tester
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WordPress function declarations for linter
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) { return $value; }
}
if (!function_exists('get_locale')) {
    function get_locale() { return 'en_US'; }
}
if (!function_exists('load_textdomain')) {
    function load_textdomain($domain, $mofile) { return true; }
}
if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) { return true; }
}
if (!function_exists('did_action')) {
    function did_action($tag) { return false; }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}

// Define plugin constants
define('WP_TESTER_VERSION', '1.0.5');
define('WP_TESTER_PLUGIN_FILE', __FILE__);
define('WP_TESTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_TESTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_TESTER_ASSETS_URL', WP_TESTER_PLUGIN_URL . 'assets/');

/**
 * Main WP Tester Plugin Class
 */
class WP_Tester {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    public $crawler;
    public $flow_executor;
    public $feedback_reporter;
    public $admin;
    public $scheduler;
    public $database;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load text domain early but after plugins_loaded
        add_action('plugins_loaded', array($this, 'load_textdomain'), 1);
        
        // Initialize AJAX handler after text domain is loaded
        add_action('plugins_loaded', array($this, 'init_ajax'), 10);
        
        // Initialize admin after text domain is loaded
        add_action('plugins_loaded', array($this, 'init_admin'), 10);
        
        // Initialize automation suite after text domain is loaded
        add_action('plugins_loaded', array($this, 'init_automation_suite'), 10);
        
        // Add a simple test AJAX action directly to verify AJAX works
        add_action('wp_ajax_wp_tester_simple_test', array($this, 'simple_ajax_test'));
        
        add_action('plugins_loaded', array($this, 'init'), 5);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('WP_Tester', 'uninstall'));
    }
    
    /**
     * Simple AJAX test
     */
    public function simple_ajax_test() {
        wp_send_json_success(array(
            'message' => 'Simple AJAX test working',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Initialize AJAX handler
     */
    public function init_ajax() {
        // Include AJAX class if not already included
        if (!class_exists('WP_Tester_Ajax')) {
            require_once plugin_dir_path(WP_TESTER_PLUGIN_FILE) . 'includes/class-wp-tester-ajax.php';
        }
        
        // Initialize AJAX handler
        new WP_Tester_Ajax();
    }
    
    /**
     * Initialize admin interface
     */
    public function init_admin() {
        // Include admin class if not already included
        if (!class_exists('WP_Tester_Admin')) {
            require_once plugin_dir_path(WP_TESTER_PLUGIN_FILE) . 'includes/class-wp-tester-admin.php';
        }
        
        // Initialize admin interface
        $this->admin = new WP_Tester_Admin();
    }
    
    /**
     * Initialize automation suite
     */
    public function init_automation_suite() {
        // Include automation suite class if not already included
        if (!class_exists('WP_Tester_Automation_Suite')) {
            require_once plugin_dir_path(WP_TESTER_PLUGIN_FILE) . 'includes/class-wp-tester-automation-suite.php';
        }
        
        // Initialize automation suite
        new WP_Tester_Automation_Suite();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        // Load plugin text domain
        $domain = 'wp-tester';
        
        // Check if WordPress functions are available
        if (function_exists('apply_filters') && function_exists('get_locale')) {
            $locale = apply_filters('plugin_locale', get_locale(), $domain);
        } else {
            $locale = 'en_US'; // Fallback locale
        }
        
        // Load from languages directory in plugin
        $mo_file = WP_TESTER_PLUGIN_DIR . 'languages/' . $domain . '-' . $locale . '.mo';
        
        if (file_exists($mo_file) && function_exists('load_textdomain')) {
            load_textdomain($domain, $mo_file);
        }
        
        // Fallback to WordPress.org language pack
        if (function_exists('load_plugin_textdomain')) {
            load_plugin_textdomain($domain, false, basename(dirname(__FILE__)) . '/languages');
        }
    }
    
    /**
     * Get translated string safely
     */
    private function get_translated_string($string) {
        // Only translate if text domain is loaded and WordPress functions are available
        if (function_exists('did_action') && did_action('plugins_loaded') && function_exists('__')) {
            return __($string, 'wp-tester');
        }
        return $string;
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-database.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-crawler.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-flow-executor.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-feedback-reporter.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-scheduler.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-admin.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-ajax.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-woocommerce.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-ai-flow-generator.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/class-wp-tester-automation-suite.php';
        require_once WP_TESTER_PLUGIN_DIR . 'includes/functions.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        $this->database = new WP_Tester_Database();
        
        // Update database schema on admin pages
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) {
            $this->database->update_flows_table_schema();
            $this->database->update_crawl_results_table_schema();
        }
        
        $this->crawler = new WP_Tester_Crawler();
        $this->flow_executor = new WP_Tester_Flow_Executor();
        $this->feedback_reporter = new WP_Tester_Feedback_Reporter();
        $this->scheduler = new WP_Tester_Scheduler();
        
        // Admin, AJAX handlers, and Automation Suite are initialized separately after text domain is loaded
        
        // Initialize WooCommerce integration if active
        if (class_exists('WooCommerce')) {
            new WP_Tester_WooCommerce();
        }
        
        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(WP_TESTER_PLUGIN_FILE), array($this, 'add_plugin_action_links'));
        // Add plugin row meta with high priority to override WordPress defaults
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 999, 2);
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'wp-tester-frontend',
            WP_TESTER_ASSETS_URL . 'js/frontend.js',
            array('jquery'),
            WP_TESTER_VERSION,
            true
        );
        
        wp_localize_script('wp-tester-frontend', 'wpTesterAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_tester_nonce')
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-tester') === false) {
            return;
        }
        
        wp_enqueue_script(
            'wp-tester-admin',
            WP_TESTER_ASSETS_URL . 'js/admin.js',
            array('jquery', 'wp-util'),
            WP_TESTER_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wp-tester-admin',
            WP_TESTER_ASSETS_URL . 'css/admin.css',
            array(),
            WP_TESTER_VERSION
        );
        
        wp_localize_script('wp-tester-admin', 'wpTesterAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_tester_admin_nonce'),
            'strings' => array(
                'confirm_delete' => $this->get_translated_string('Are you sure you want to delete this test result?'),
                'test_running' => $this->get_translated_string('Test is running...'),
                'test_completed' => $this->get_translated_string('Test completed successfully!'),
                'test_failed' => $this->get_translated_string('Test failed. Please check the logs.')
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Include required files for activation
        $this->includes();
        
        // Create database tables
        $this->database = new WP_Tester_Database();
        $this->database->create_tables();
        
        // Update table schemas to ensure all columns exist
        $this->database->update_flows_table_schema();
        
        // Note: No automatic crawling scheduled - user must manually trigger crawls
        
        // Set default options
        add_option('wp_tester_settings', array(
            'crawl_frequency' => 'never',
            'test_timeout' => 30,
            'retry_attempts' => 2,
            'screenshot_on_failure' => true,
            'enable_woocommerce' => class_exists('WooCommerce'),
            'max_pages_per_crawl' => 100
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wp_tester_daily_crawl');
        wp_clear_scheduled_hook('wp_tester_test_flows');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wp-tester-settings') . '">' . $this->get_translated_string('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    
    /**
     * Add plugin row meta links (properly preserve version/author info)
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(WP_TESTER_PLUGIN_FILE) === $file) {
            $cleaned_links = array();
            
            // Keep all links except "View details" variations
            foreach ($links as $key => $link) {
                if (is_string($link)) {
                    // Only remove actual "View details" links, keep version/author info
                    if (preg_match('/view\s*details/i', $link) && strpos($link, '<a') !== false) {
                        continue; // Skip only "View details" links
                    }
                }
                $cleaned_links[$key] = $link;
            }
            
            // Add our custom "View Details" link
            $cleaned_links['wp_tester_view_details'] = '<a href="#" class="wp-tester-view-details">' . $this->get_translated_string('View Details') . '</a>';
            
            return $cleaned_links;
        }
        return $links;
    }
    
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'wp_tester_crawl_results',
            $wpdb->prefix . 'wp_tester_flows',
            $wpdb->prefix . 'wp_tester_test_results',
            $wpdb->prefix . 'wp_tester_screenshots'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Remove options
        delete_option('wp_tester_settings');
        delete_option('wp_tester_version');
        
        // Clear scheduled events
        wp_clear_scheduled_hook('wp_tester_daily_crawl');
        wp_clear_scheduled_hook('wp_tester_test_flows');
        
        // Remove uploaded screenshots
        $upload_dir = wp_upload_dir();
        $screenshots_dir = $upload_dir['basedir'] . '/wp-tester-screenshots';
        if (is_dir($screenshots_dir)) {
            // Include functions file to access wp_tester_remove_directory
            require_once WP_TESTER_PLUGIN_DIR . 'includes/functions.php';
            wp_tester_remove_directory($screenshots_dir);
        }
    }
}

// Initialize the plugin
function wp_tester() {
    return WP_Tester::get_instance();
}

// Start the plugin
wp_tester();