<?php
/**
 * Plugin Name: WP Tester
 * Plugin URI: https://example.com/wp-tester
 * Description: Automatically tests all user flows on a WordPress site and produces detailed feedback without generating coded test scripts.
 * Version: 1.0.0
 * Author: WP Tester Team
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

// Define plugin constants
define('WP_TESTER_VERSION', '1.0.0');
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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('WP_Tester', 'uninstall'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wp-tester', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Setup hooks
        $this->setup_hooks();
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
        require_once WP_TESTER_PLUGIN_DIR . 'includes/functions.php';
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        $this->database = new WP_Tester_Database();
        $this->crawler = new WP_Tester_Crawler();
        $this->flow_executor = new WP_Tester_Flow_Executor();
        $this->feedback_reporter = new WP_Tester_Feedback_Reporter();
        $this->scheduler = new WP_Tester_Scheduler();
        $this->admin = new WP_Tester_Admin();
        
        // Initialize AJAX handler
        new WP_Tester_Ajax();
        
        // Initialize WooCommerce integration if active
        if (class_exists('WooCommerce')) {
            new WP_Tester_WooCommerce();
        }
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
                'confirm_delete' => __('Are you sure you want to delete this test result?', 'wp-tester'),
                'test_running' => __('Test is running...', 'wp-tester'),
                'test_completed' => __('Test completed successfully!', 'wp-tester'),
                'test_failed' => __('Test failed. Please check the logs.', 'wp-tester')
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->database = new WP_Tester_Database();
        $this->database->create_tables();
        
        // Schedule initial crawl
        if (!wp_next_scheduled('wp_tester_daily_crawl')) {
            wp_schedule_event(time(), 'daily', 'wp_tester_daily_crawl');
        }
        
        // Set default options
        add_option('wp_tester_settings', array(
            'crawl_frequency' => 'daily',
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