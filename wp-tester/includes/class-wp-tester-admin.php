<?php
/**
 * WP Tester Admin Class
 * 
 * Handles the admin dashboard UI for viewing flows and test results
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Admin {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Feedback reporter instance
     */
    private $feedback_reporter;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
        $this->feedback_reporter = new WP_Tester_Feedback_Reporter();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WP Tester', 'wp-tester'),
            __('WP Tester', 'wp-tester'),
            'manage_options',
            'wp-tester',
            array($this, 'dashboard_page'),
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'wp-tester',
            __('Dashboard', 'wp-tester'),
            __('Dashboard', 'wp-tester'),
            'manage_options',
            'wp-tester',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'wp-tester',
            __('Flows', 'wp-tester'),
            __('Flows', 'wp-tester'),
            'manage_options',
            'wp-tester-flows',
            array($this, 'flows_page')
        );
        
        add_submenu_page(
            'wp-tester',
            __('Test Results', 'wp-tester'),
            __('Test Results', 'wp-tester'),
            'manage_options',
            'wp-tester-results',
            array($this, 'results_page')
        );
        
        add_submenu_page(
            'wp-tester',
            __('Crawl Results', 'wp-tester'),
            __('Crawl Results', 'wp-tester'),
            'manage_options',
            'wp-tester-crawl',
            array($this, 'crawl_page')
        );
        
        add_submenu_page(
            'wp-tester',
            __('Settings', 'wp-tester'),
            __('Settings', 'wp-tester'),
            'manage_options',
            'wp-tester-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        register_setting('wp_tester_settings', 'wp_tester_settings', array($this, 'sanitize_settings'));
        
        // Add settings sections and fields
        add_settings_section(
            'wp_tester_general',
            __('General Settings', 'wp-tester'),
            array($this, 'general_section_callback'),
            'wp_tester_settings'
        );
        
        add_settings_field(
            'crawl_frequency',
            __('Crawl Frequency', 'wp-tester'),
            array($this, 'crawl_frequency_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'test_timeout',
            __('Test Timeout (seconds)', 'wp-tester'),
            array($this, 'test_timeout_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'retry_attempts',
            __('Retry Attempts', 'wp-tester'),
            array($this, 'retry_attempts_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'screenshot_on_failure',
            __('Take Screenshots on Failure', 'wp-tester'),
            array($this, 'screenshot_on_failure_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'max_pages_per_crawl',
            __('Max Pages per Crawl', 'wp-tester'),
            array($this, 'max_pages_per_crawl_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $dashboard_data = $this->feedback_reporter->generate_dashboard_summary();
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Flows page
     */
    public function flows_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $flow_id = isset($_GET['flow_id']) ? intval($_GET['flow_id']) : 0;
        
        switch ($action) {
            case 'view':
                $this->view_flow_page($flow_id);
                break;
            case 'edit':
                $this->edit_flow_page($flow_id);
                break;
            case 'test':
                $this->test_flow_page($flow_id);
                break;
            default:
                $this->list_flows_page();
        }
    }
    
    /**
     * Test results page
     */
    public function results_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;
        
        switch ($action) {
            case 'view':
                $this->view_result_page($result_id);
                break;
            default:
                $this->list_results_page();
        }
    }
    
    /**
     * Crawl results page
     */
    public function crawl_page() {
        $crawl_results = $this->database->get_crawl_results(50, 0);
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-crawl.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * List flows page
     */
    private function list_flows_page() {
        $flows = $this->database->get_flows(true);
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-flows.php';
    }
    
    /**
     * View flow page
     */
    private function view_flow_page($flow_id) {
        $flow = $this->database->get_flow($flow_id);
        if (!$flow) {
            wp_die(__('Flow not found.', 'wp-tester'));
        }
        
        $recent_results = $this->database->get_test_results($flow_id, 10, 0);
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-flow-view.php';
    }
    
    /**
     * Edit flow page
     */
    private function edit_flow_page($flow_id) {
        $flow = $this->database->get_flow($flow_id);
        if (!$flow) {
            wp_die(__('Flow not found.', 'wp-tester'));
        }
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-flow-edit.php';
    }
    
    /**
     * Test flow page
     */
    private function test_flow_page($flow_id) {
        $flow = $this->database->get_flow($flow_id);
        if (!$flow) {
            wp_die(__('Flow not found.', 'wp-tester'));
        }
        
        // Execute the flow
        $executor = new WP_Tester_Flow_Executor();
        $result = $executor->execute_flow($flow_id, true);
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-flow-test.php';
    }
    
    /**
     * List results page
     */
    private function list_results_page() {
        $results = $this->database->get_test_results(null, 50, 0);
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-results.php';
    }
    
    /**
     * View result page
     */
    private function view_result_page($result_id) {
        $report = $this->feedback_reporter->generate_report($result_id);
        if (isset($report['error'])) {
            wp_die(__('Test result not found.', 'wp-tester'));
        }
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-result-view.php';
    }
    
    /**
     * Settings callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for WP Tester.', 'wp-tester') . '</p>';
    }
    
    public function crawl_frequency_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['crawl_frequency']) ? $settings['crawl_frequency'] : 'daily';
        
        echo '<select name="wp_tester_settings[crawl_frequency]">';
        echo '<option value="hourly"' . selected($value, 'hourly', false) . '>' . __('Hourly', 'wp-tester') . '</option>';
        echo '<option value="twicedaily"' . selected($value, 'twicedaily', false) . '>' . __('Twice Daily', 'wp-tester') . '</option>';
        echo '<option value="daily"' . selected($value, 'daily', false) . '>' . __('Daily', 'wp-tester') . '</option>';
        echo '<option value="weekly"' . selected($value, 'weekly', false) . '>' . __('Weekly', 'wp-tester') . '</option>';
        echo '</select>';
    }
    
    public function test_timeout_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['test_timeout']) ? $settings['test_timeout'] : 30;
        
        echo '<input type="number" name="wp_tester_settings[test_timeout]" value="' . esc_attr($value) . '" min="10" max="300" />';
        echo '<p class="description">' . __('Maximum time to wait for each test step to complete.', 'wp-tester') . '</p>';
    }
    
    public function retry_attempts_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['retry_attempts']) ? $settings['retry_attempts'] : 2;
        
        echo '<input type="number" name="wp_tester_settings[retry_attempts]" value="' . esc_attr($value) . '" min="0" max="5" />';
        echo '<p class="description">' . __('Number of times to retry a failed step before marking it as failed.', 'wp-tester') . '</p>';
    }
    
    public function screenshot_on_failure_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['screenshot_on_failure']) ? $settings['screenshot_on_failure'] : true;
        
        echo '<input type="checkbox" name="wp_tester_settings[screenshot_on_failure]" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>' . __('Take screenshots when steps fail for visual debugging.', 'wp-tester') . '</label>';
    }
    
    public function max_pages_per_crawl_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['max_pages_per_crawl']) ? $settings['max_pages_per_crawl'] : 100;
        
        echo '<input type="number" name="wp_tester_settings[max_pages_per_crawl]" value="' . esc_attr($value) . '" min="10" max="1000" />';
        echo '<p class="description">' . __('Maximum number of pages to crawl per post type during each crawl session.', 'wp-tester') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['crawl_frequency'])) {
            $allowed_frequencies = array('hourly', 'twicedaily', 'daily', 'weekly');
            $sanitized['crawl_frequency'] = in_array($input['crawl_frequency'], $allowed_frequencies) 
                ? $input['crawl_frequency'] 
                : 'daily';
        }
        
        if (isset($input['test_timeout'])) {
            $sanitized['test_timeout'] = max(10, min(300, intval($input['test_timeout'])));
        }
        
        if (isset($input['retry_attempts'])) {
            $sanitized['retry_attempts'] = max(0, min(5, intval($input['retry_attempts'])));
        }
        
        if (isset($input['screenshot_on_failure'])) {
            $sanitized['screenshot_on_failure'] = (bool) $input['screenshot_on_failure'];
        }
        
        if (isset($input['max_pages_per_crawl'])) {
            $sanitized['max_pages_per_crawl'] = max(10, min(1000, intval($input['max_pages_per_crawl'])));
        }
        
        return $sanitized;
    }
    
    /**
     * Get status badge HTML
     */
    public function get_status_badge($status) {
        $badges = array(
            'passed' => '<span class="wp-tester-badge wp-tester-badge-success">Passed</span>',
            'failed' => '<span class="wp-tester-badge wp-tester-badge-danger">Failed</span>',
            'partial' => '<span class="wp-tester-badge wp-tester-badge-warning">Partial</span>',
            'running' => '<span class="wp-tester-badge wp-tester-badge-info">Running</span>'
        );
        
        return isset($badges[$status]) ? $badges[$status] : '<span class="wp-tester-badge wp-tester-badge-secondary">' . ucfirst($status) . '</span>';
    }
    
    /**
     * Get priority badge HTML
     */
    public function get_priority_badge($priority) {
        $badges = array(
            'critical' => '<span class="wp-tester-badge wp-tester-badge-danger">Critical</span>',
            'high' => '<span class="wp-tester-badge wp-tester-badge-warning">High</span>',
            'medium' => '<span class="wp-tester-badge wp-tester-badge-info">Medium</span>',
            'low' => '<span class="wp-tester-badge wp-tester-badge-secondary">Low</span>'
        );
        
        return isset($badges[$priority]) ? $badges[$priority] : '<span class="wp-tester-badge wp-tester-badge-secondary">' . ucfirst($priority) . '</span>';
    }
    
    /**
     * Format execution time
     */
    public function format_execution_time($seconds) {
        if ($seconds < 60) {
            return round($seconds, 2) . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . 'm';
        } else {
            return round($seconds / 3600, 1) . 'h';
        }
    }
    
    /**
     * Get flow type icon
     */
    public function get_flow_type_icon($flow_type) {
        $icons = array(
            'registration' => 'admin-users',
            'login' => 'admin-network',
            'contact' => 'email-alt',
            'search' => 'search',
            'woocommerce' => 'cart',
            'navigation' => 'menu',
            'modal' => 'visibility'
        );
        
        $icon = isset($icons[$flow_type]) ? $icons[$flow_type] : 'admin-generic';
        return '<span class="dashicons dashicons-' . $icon . '"></span>';
    }
}