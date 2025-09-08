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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Enqueue modern UI styles for all admin pages
        // Note: Modern styles temporarily disabled to prevent sticky header issues
        // Will be re-enabled after fixing sticky positioning
        // wp_enqueue_style(
        //     'wp-tester-modern-admin',
        //     WP_TESTER_PLUGIN_URL . 'assets/dist/modern-styles.css',
        //     array(),
        //     WP_TESTER_VERSION
        // );
        
        // Enqueue modern UI styles
        wp_enqueue_style(
            'wp-tester-modern-ui',
            WP_TESTER_PLUGIN_URL . 'assets/css/modern-ui.css',
            array(),
            WP_TESTER_VERSION
        );
        
        // Enqueue legacy admin styles for menu icon compatibility
        wp_enqueue_style(
            'wp-tester-admin',
            WP_TESTER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_TESTER_VERSION
        );
        
        // Enqueue modern JavaScript on WP Tester pages (lightweight version)
        if (strpos($hook, 'wp-tester') !== false || strpos($hook, 'toplevel_page_wp-tester') !== false) {
            // Enqueue our basic admin JavaScript for interactions
            wp_enqueue_script(
                'wp-tester-admin-interactions',
                WP_TESTER_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                WP_TESTER_VERSION,
                true
            );
            
            // Localize script with basic data
            wp_localize_script('wp-tester-admin-interactions', 'wpTesterData', array(
                'nonce' => wp_create_nonce('wp_tester_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'version' => WP_TESTER_VERSION
            ));
        }
        
        // Only load plugin details popup on plugins page
        if ($hook === 'plugins.php') {
            wp_enqueue_script(
                'wp-tester-plugin-details',
                WP_TESTER_PLUGIN_URL . 'assets/js/plugin-details.js',
                array('jquery'),
                WP_TESTER_VERSION,
                true
            );
            
            wp_enqueue_style(
                'wp-tester-plugin-details',
                WP_TESTER_PLUGIN_URL . 'assets/css/plugin-details.css',
                array(),
                WP_TESTER_VERSION
            );
            
            // Localize script with plugin data
            wp_localize_script('wp-tester-plugin-details', 'wpTesterData', array(
                'nonce' => wp_create_nonce('wp_tester_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'pluginData' => $this->get_plugin_details()
            ));
        }
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
            WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png',
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
            __('Crawled Pages', 'wp-tester'),
            __('Crawled Pages', 'wp-tester'),
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
        
        add_submenu_page(
            'wp-tester',
            __('AI Flow Generator', 'wp-tester'),
            __('AI Flow Generator', 'wp-tester'),
            'manage_options',
            'wp-tester-ai-generator',
            array($this, 'ai_generator_page')
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
            'screenshot_on_success',
            __('Take Screenshots on Success (Debug)', 'wp-tester'),
            array($this, 'screenshot_on_success_callback'),
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
            case 'add':
                $this->add_flow_page();
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
        $crawl_results = $this->database->get_crawl_results(20, 0); // Load first 20 results
        $total_crawl_count = $this->database->get_crawl_results_count();
        $has_more_crawls = $total_crawl_count > 20;
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-crawl.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * AI Generator page
     */
    public function ai_generator_page() {
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-ai-generator.php';
    }
    
    /**
     * List flows page
     */
    private function list_flows_page() {
        // Clean up any duplicate flows first
        $removed_count = $this->database->remove_duplicate_flows();
        if ($removed_count > 0) {
            // Add admin notice about cleanup
            add_action('admin_notices', function() use ($removed_count) {
                echo '<div class="notice notice-info is-dismissible"><p>';
                printf(__('WP Tester: Removed %d duplicate flows from the database.', 'wp-tester'), $removed_count);
                echo '</p></div>';
            });
        }
        
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
     * Add flow page
     */
    private function add_flow_page() {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wp_tester_nonce']) && wp_verify_nonce($_POST['wp_tester_nonce'], 'wp_tester_add_flow')) {
            $flow_name = sanitize_text_field($_POST['flow_name'] ?? '');
            $flow_type = sanitize_text_field($_POST['flow_type'] ?? 'login');
            $start_url = esc_url_raw($_POST['start_url'] ?? '');
            $priority = intval($_POST['priority'] ?? 5);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($flow_name && $start_url) {
                $result = $this->database->save_flow($flow_name, $flow_type, $start_url, array(), '', $priority);
                if ($result) {
                    wp_redirect(admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . $this->database->get_flow_id_by_name($flow_name)));
                    exit;
                }
            }
        }
        
        // Create empty flow object for form
        $flow = (object) array(
            'id' => 0,
            'flow_name' => '',
            'flow_type' => 'login',
            'start_url' => '',
            'steps' => '[]',
            'expected_outcome' => '',
            'priority' => 5,
            'is_active' => 1,
            'ai_generated' => 0,
            'ai_provider' => '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-flow-edit.php';
    }

    /**
     * Edit flow page
     */
    private function edit_flow_page($flow_id) {
        $flow = $this->database->get_flow($flow_id);
        if (!$flow) {
            wp_die(__('Flow not found.', 'wp-tester'));
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wp_tester_nonce']) && wp_verify_nonce($_POST['wp_tester_nonce'], 'wp_tester_edit_flow')) {
            $flow_name = sanitize_text_field($_POST['flow_name'] ?? '');
            $flow_type = sanitize_text_field($_POST['flow_type'] ?? 'login');
            $start_url = esc_url_raw($_POST['start_url'] ?? '');
            $priority = intval($_POST['priority'] ?? 5);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $steps = array();
            if (isset($_POST['steps'])) {
                if (is_string($_POST['steps'])) {
                    $decoded_steps = json_decode($_POST['steps'], true);
                    $steps = is_array($decoded_steps) ? $decoded_steps : array();
                } elseif (is_array($_POST['steps'])) {
                    $steps = $_POST['steps'];
                }
            }
            $expected_outcome = sanitize_text_field($_POST['expected_outcome'] ?? '');
            
            if ($flow_name && $start_url) {
                // Update the flow
                global $wpdb;
                $result = $wpdb->update(
                    $wpdb->prefix . 'wp_tester_flows',
                    array(
                        'flow_name' => $flow_name,
                        'flow_type' => $flow_type,
                        'start_url' => $start_url,
                        'steps' => wp_json_encode($steps),
                        'expected_outcome' => $expected_outcome,
                        'priority' => $priority,
                        'is_active' => $is_active,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $flow_id),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s'),
                    array('%d')
                );
                
                if ($result !== false) {
                    // Refresh the flow data
                    $flow = $this->database->get_flow($flow_id);
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Flow updated successfully!', 'wp-tester') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to update flow. Please try again.', 'wp-tester') . '</p></div>';
                    });
                }
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Flow name and start URL are required.', 'wp-tester') . '</p></div>';
                });
            }
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
        
        // Debug: Log screenshot information
        $screenshots = $this->database->get_screenshots($result_id);
        // Found screenshots for result
        foreach ($screenshots as $screenshot) {
            // Processing screenshot
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
        $value = isset($settings['crawl_frequency']) ? $settings['crawl_frequency'] : 'never';
        
        echo '<select name="wp_tester_settings[crawl_frequency]">';
        echo '<option value="never"' . selected($value, 'never', false) . '>' . __('Never (Manual Only)', 'wp-tester') . '</option>';
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
    
    public function screenshot_on_success_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['screenshot_on_success']) ? $settings['screenshot_on_success'] : false;
        
        echo '<input type="checkbox" name="wp_tester_settings[screenshot_on_success]" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>' . __('Also take screenshots for successful steps (useful for debugging).', 'wp-tester') . '</label>';
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
            $allowed_frequencies = array('never', 'hourly', 'twicedaily', 'daily', 'weekly');
            $sanitized['crawl_frequency'] = in_array($input['crawl_frequency'], $allowed_frequencies) 
                ? $input['crawl_frequency'] 
                : 'never';
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
        
        if (isset($input['screenshot_on_success'])) {
            $sanitized['screenshot_on_success'] = (bool) $input['screenshot_on_success'];
        }
        
        if (isset($input['max_pages_per_crawl'])) {
            $sanitized['max_pages_per_crawl'] = max(10, min(1000, intval($input['max_pages_per_crawl'])));
        }
        
        if (isset($input['include_admin_in_crawl'])) {
            $sanitized['include_admin_in_crawl'] = (bool)$input['include_admin_in_crawl'];
        }
        
        if (isset($input['prevent_duplicate_flows'])) {
            $sanitized['prevent_duplicate_flows'] = (bool)$input['prevent_duplicate_flows'];
        }
        
        if (isset($input['crawl_schedule_time'])) {
            // Validate time format (HH:MM)
            if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input['crawl_schedule_time'])) {
                $sanitized['crawl_schedule_time'] = sanitize_text_field($input['crawl_schedule_time']);
            } else {
                $sanitized['crawl_schedule_time'] = '02:00'; // Default to 2 AM
            }
        }
        
        if (isset($input['crawl_schedule_days']) && is_array($input['crawl_schedule_days'])) {
            $allowed_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            $sanitized['crawl_schedule_days'] = array_intersect($input['crawl_schedule_days'], $allowed_days);
            
            // Ensure at least one day is selected
            if (empty($sanitized['crawl_schedule_days'])) {
                $sanitized['crawl_schedule_days'] = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
            }
        } else {
            // Default to weekdays if no days selected
            $sanitized['crawl_schedule_days'] = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
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
    
    /**
     * Get plugin details for the popup
     */
    public function get_plugin_details() {
        $stats = $this->database->get_dashboard_stats();
        
        return array(
            'name' => 'WP Tester',
            'version' => WP_TESTER_VERSION,
            'author' => 'Rubaiyat E Mohammad',
            'author_url' => 'https://github.com/Rubaiyat-E-Mohammad',
            'plugin_url' => 'https://github.com/Rubaiyat-E-Mohammad/WP-Tester',
            'logo_url' => WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png',
            'description' => 'Automatically tests all user flows on a WordPress site and produces detailed feedback without generating coded test scripts.',
            'features' => array(
                'Automatic flow detection and testing',
                'WooCommerce integration support',
                'Visual feedback with screenshots',
                'Detailed reporting and analytics',
                'Scheduled testing capabilities',
                'Email notifications for failures',
                'Multiple flow types support',
                'Easy setup and configuration'
            ),
            'stats' => array(
                'Total Pages Crawled' => $stats['total_pages'] ?: 0,
                'Active Flows' => $stats['total_flows'] ?: 0,
                'Recent Tests (24h)' => $stats['recent_tests'] ?: 0,
                'Success Rate (7 days)' => ($stats['success_rate'] ?: 0) . '%'
            ),
            'requirements' => array(
                'WordPress' => '6.0+',
                'PHP' => '7.4+',
                'MySQL' => '5.6+'
            ),
            'last_updated' => '2 months ago',
            'homepage' => 'https://github.com/Rubaiyat-E-Mohammad/WP-Tester'
        );
    }
}