<?php
/**
 * WP Tester Admin Class
 * 
 * Handles the admin dashboard UI for viewing flows and test results
 */

if (!defined('ABSPATH')) {
    exit;
}

// WordPress function declarations for linter
if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return sanitize_text_field($email);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return esc_html($text);
    }
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
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Settings saved successfully!</p></div>';
        }
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
        
        add_submenu_page(
            'wp-tester',
            __('AI Chat', 'wp-tester'),
            __('AI Chat', 'wp-tester'),
            'manage_options',
            'wp-tester-ai-chat',
            array($this, 'ai_chat_page')
        );
        
        add_submenu_page(
            'wp-tester',
            __('Automation Suite', 'wp-tester'),
            __('Automation Suite', 'wp-tester'),
            'manage_options',
            'wp-tester-automation-suite',
            array($this, 'automation_suite_page')
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
        
        add_settings_field(
            'include_admin_in_crawl',
            __('Include Admin Panel in Crawl', 'wp-tester'),
            array($this, 'include_admin_in_crawl_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'prevent_duplicate_flows',
            __('Prevent Duplicate Flows', 'wp-tester'),
            array($this, 'prevent_duplicate_flows_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'crawl_schedule_time',
            __('Crawl Schedule Time', 'wp-tester'),
            array($this, 'crawl_schedule_time_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'crawl_schedule_days',
            __('Crawl Schedule Days', 'wp-tester'),
            array($this, 'crawl_schedule_days_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        // Test frequency settings
        add_settings_field(
            'test_frequency',
            __('Test Frequency', 'wp-tester'),
            array($this, 'test_frequency_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'test_schedule_time',
            __('Test Schedule Time', 'wp-tester'),
            array($this, 'test_schedule_time_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'email_notifications',
            __('Email Notifications', 'wp-tester'),
            array($this, 'email_notifications_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        // Email configuration fields
        add_settings_field(
            'email_recipients',
            __('Email Recipients', 'wp-tester'),
            array($this, 'email_recipients_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'smtp_host',
            __('SMTP Host', 'wp-tester'),
            array($this, 'smtp_host_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'smtp_port',
            __('SMTP Port', 'wp-tester'),
            array($this, 'smtp_port_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'smtp_username',
            __('SMTP Username', 'wp-tester'),
            array($this, 'smtp_username_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'smtp_password',
            __('SMTP Password', 'wp-tester'),
            array($this, 'smtp_password_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'smtp_encryption',
            __('SMTP Encryption', 'wp-tester'),
            array($this, 'smtp_encryption_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'from_email',
            __('From Email', 'wp-tester'),
            array($this, 'from_email_callback'),
            'wp_tester_settings',
            'wp_tester_general'
        );
        
        add_settings_field(
            'from_name',
            __('From Name', 'wp-tester'),
            array($this, 'from_name_callback'),
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
            $expected_outcome = sanitize_text_field($_POST['expected_outcome'] ?? '');
            
            // Handle steps
            $steps = array();
            if (isset($_POST['steps'])) {
                if (is_string($_POST['steps'])) {
                    $decoded_steps = json_decode($_POST['steps'], true);
                    $steps = is_array($decoded_steps) ? $decoded_steps : array();
                } elseif (is_array($_POST['steps'])) {
                    $steps = $_POST['steps'];
                }
            }
            
            if ($flow_name && $start_url) {
                $result = $this->database->save_flow($flow_name, $flow_type, $start_url, $steps, $expected_outcome, $priority);
                if ($result) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Flow created successfully!', 'wp-tester') . '</p></div>';
                    });
                    wp_redirect(admin_url('admin.php?page=wp-tester-flows'));
                    exit;
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create flow. Please try again.', 'wp-tester') . '</p></div>';
                    });
                }
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Flow name and start URL are required.', 'wp-tester') . '</p></div>';
                });
            }
        }
        
        // Create empty flow object for form, pre-fill start_url if provided
        $start_url = isset($_GET['start_url']) ? esc_url_raw($_GET['start_url']) : '';
        $flow = (object) array(
            'id' => 0,
            'flow_name' => '',
            'flow_type' => 'login',
            'start_url' => $start_url,
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
                    // Clean the JSON string - remove any extra escaping
                    $clean_steps = stripslashes($_POST['steps']);
                    
                    $decoded_steps = json_decode($clean_steps, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_steps)) {
                        $steps = $decoded_steps;
                    } else {
                        error_log('WP Tester: JSON decode error: ' . json_last_error_msg());
                        
                        // Try to fix common JSON issues
                        $fixed_json = $this->fix_json_syntax($clean_steps);
                        if ($fixed_json) {
                            $decoded_steps = json_decode($fixed_json, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_steps)) {
                                $steps = $decoded_steps;
                            } else {
                                error_log('WP Tester: JSON fix failed: ' . json_last_error_msg());
                                $steps = array();
                            }
                        } else {
                            $steps = array();
                        }
                    }
                } elseif (is_array($_POST['steps'])) {
                    $steps = $_POST['steps'];
                }
            }
            $expected_outcome = sanitize_text_field($_POST['expected_outcome'] ?? '');
            
            if ($flow_name && $start_url) {
                // Ensure steps are properly encoded
                $encoded_steps = wp_json_encode($steps);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('WP Tester: JSON encoding error: ' . json_last_error_msg());
                    $encoded_steps = '[]';
                }
                
                // Update the flow
                global $wpdb;
                $result = $wpdb->update(
                    $wpdb->prefix . 'wp_tester_flows',
                    array(
                        'flow_name' => $flow_name,
                        'flow_type' => $flow_type,
                        'start_url' => $start_url,
                        'steps' => $encoded_steps,
                        'expected_outcome' => $expected_outcome,
                        'priority' => $priority,
                        'is_active' => $is_active,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $flow_id),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    error_log('WP Tester: Database update failed: ' . $wpdb->last_error);
                }
                
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
     * Fix common JSON syntax issues
     */
    private function fix_json_syntax($json_string) {
        if (empty($json_string)) {
            return false;
        }
        
        // Remove any extra escaping
        $json_string = stripslashes($json_string);
        
        // Fix common issues with malformed JSON
        // 1. Fix unescaped quotes in strings
        $json_string = preg_replace('/"([^"]*)"([^"]*)"([^"]*)"/', '"$1\\"$2\\"$3"', $json_string);
        
        // 2. Fix missing quotes around object keys
        $json_string = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $json_string);
        
        // 3. Fix trailing commas
        $json_string = preg_replace('/,(\s*[}\]])/', '$1', $json_string);
        
        // 4. Fix single quotes to double quotes
        $json_string = str_replace("'", '"', $json_string);
        
        // 5. Try to fix malformed URLs in JSON
        $json_string = preg_replace('/"([^"]*https?:\/\/[^"]*)"([^"]*)"([^"]*)"/', '"$1$2$3"', $json_string);
        
        // Only log if there was an actual fix applied
        if ($json_string !== stripslashes($json_string)) {
            error_log('WP Tester: JSON syntax fixed');
        }
        
        return $json_string;
    }
    
    /**
     * Get the test executor (basic flow executor)
     */
    public function get_test_executor() {
        return new WP_Tester_Flow_Executor();
    }
    
    /**
     * Execute flow with basic executor
     */
    public function execute_flow_with_fallback($flow_id, $manual_trigger = false) {
        $executor = $this->get_test_executor();
        return $executor->execute_flow($flow_id, $manual_trigger);
    }
    
    /**
     * Test flow page
     */
    private function test_flow_page($flow_id) {
        $flow = $this->database->get_flow($flow_id);
        if (!$flow) {
            wp_die(__('Flow not found.', 'wp-tester'));
        }
        
        // Execute the flow with automatic fallback
        $result = $this->execute_flow_with_fallback($flow_id, true);
        
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
        error_log('WP Tester: Viewing result page for result ID: ' . $result_id);
        
        $report = $this->feedback_reporter->generate_report($result_id);
        if (isset($report['error'])) {
            wp_die(__('Test result not found.', 'wp-tester'));
        }
        
        // Get screenshot information for the report
        $screenshots = $this->database->get_screenshots($result_id);
        
        // Debug the report data
        error_log('WP Tester: Report generated for result ID ' . $result_id);
        error_log('WP Tester: Visual evidence count: ' . count($report['visual_evidence'] ?? []));
        if (!empty($report['visual_evidence'])) {
            foreach ($report['visual_evidence'] as $evidence) {
                error_log('WP Tester: Visual evidence - Step: ' . $evidence['step_number'] . ', File exists: ' . ($evidence['file_exists'] ? 'yes' : 'no'));
            }
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
        $value = isset($settings['test_timeout']) ? $settings['test_timeout'] : 10;
        
        echo '<input type="number" name="wp_tester_settings[test_timeout]" value="' . esc_attr($value) . '" min="5" max="300" />';
        echo '<p class="description">' . __('Maximum time to wait for each test step to complete.', 'wp-tester') . '</p>';
    }
    
    public function retry_attempts_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['retry_attempts']) ? $settings['retry_attempts'] : 0;
        
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
    
    public function include_admin_in_crawl_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['include_admin_in_crawl']) ? $settings['include_admin_in_crawl'] : true;
        
        echo '<input type="checkbox" name="wp_tester_settings[include_admin_in_crawl]" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>' . __('Automatically discover and create flows for WordPress admin pages.', 'wp-tester') . '</label>';
    }
    
    public function prevent_duplicate_flows_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['prevent_duplicate_flows']) ? $settings['prevent_duplicate_flows'] : true;
        
        echo '<input type="checkbox" name="wp_tester_settings[prevent_duplicate_flows]" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>' . __('Automatically prevent creation of duplicate flows during crawling.', 'wp-tester') . '</label>';
    }
    
    public function crawl_schedule_time_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['crawl_schedule_time']) ? $settings['crawl_schedule_time'] : '02:00';
        
        echo '<input type="time" name="wp_tester_settings[crawl_schedule_time]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Time of day to run scheduled crawls (24-hour format).', 'wp-tester') . '</p>';
    }
    
    public function crawl_schedule_days_callback() {
        $settings = get_option('wp_tester_settings', array());
        $selected_days = isset($settings['crawl_schedule_days']) ? $settings['crawl_schedule_days'] : array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
        
        $days = array(
            'monday' => __('Monday', 'wp-tester'),
            'tuesday' => __('Tuesday', 'wp-tester'),
            'wednesday' => __('Wednesday', 'wp-tester'),
            'thursday' => __('Thursday', 'wp-tester'),
            'friday' => __('Friday', 'wp-tester'),
            'saturday' => __('Saturday', 'wp-tester'),
            'sunday' => __('Sunday', 'wp-tester')
        );
        
        foreach ($days as $day => $label) {
            echo '<label><input type="checkbox" name="wp_tester_settings[crawl_schedule_days][]" value="' . $day . '" ' . checked(in_array($day, $selected_days), true, false) . ' /> ' . $label . '</label><br>';
        }
        echo '<p class="description">' . __('Select which days of the week to run scheduled crawls.', 'wp-tester') . '</p>';
    }
    
    public function test_frequency_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['test_frequency']) ? $settings['test_frequency'] : 'never';
        
        $options = array(
            'never' => __('Never (Manual Only)', 'wp-tester'),
            'daily' => __('Daily', 'wp-tester'),
            'weekly' => __('Weekly', 'wp-tester'),
            'monthly' => __('Monthly', 'wp-tester')
        );
        
        echo '<select name="wp_tester_settings[test_frequency]">';
        foreach ($options as $key => $label) {
            echo '<option value="' . $key . '" ' . selected($value, $key, false) . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('How often to automatically run all active flows.', 'wp-tester') . '</p>';
    }
    
    public function test_schedule_time_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['test_schedule_time']) ? $settings['test_schedule_time'] : '02:00';
        
        echo '<input type="time" name="wp_tester_settings[test_schedule_time]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Time of day to run scheduled tests (24-hour format).', 'wp-tester') . '</p>';
    }
    
    public function email_notifications_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['email_notifications']) ? $settings['email_notifications'] : false;
        
        echo '<input type="checkbox" name="wp_tester_settings[email_notifications]" value="1" ' . checked($value, true, false) . ' />';
        echo '<label>' . __('Send email notifications for test results.', 'wp-tester') . '</label>';
        echo '<p class="description">' . __('Enable email notifications for both scheduled and manual tests.', 'wp-tester') . '</p>';
    }
    
    public function email_recipients_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['email_recipients']) ? $settings['email_recipients'] : '';
        
        echo '<textarea name="wp_tester_settings[email_recipients]" rows="3" cols="50" placeholder="' . esc_attr(__('Enter email addresses, one per line', 'wp-tester')) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('Email addresses to receive test result notifications (one per line).', 'wp-tester') . '</p>';
    }
    
    public function smtp_host_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['smtp_host']) ? $settings['smtp_host'] : '';
        
        echo '<input type="text" name="wp_tester_settings[smtp_host]" value="' . esc_attr($value) . '" placeholder="smtp.gmail.com" />';
        echo '<p class="description">' . __('SMTP server hostname.', 'wp-tester') . '</p>';
    }
    
    public function smtp_port_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['smtp_port']) ? $settings['smtp_port'] : 587;
        
        echo '<input type="number" name="wp_tester_settings[smtp_port]" value="' . esc_attr($value) . '" min="1" max="65535" />';
        echo '<p class="description">' . __('SMTP server port (587 for TLS, 465 for SSL).', 'wp-tester') . '</p>';
    }
    
    public function smtp_username_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['smtp_username']) ? $settings['smtp_username'] : '';
        
        echo '<input type="text" name="wp_tester_settings[smtp_username]" value="' . esc_attr($value) . '" placeholder="your-email@gmail.com" />';
        echo '<p class="description">' . __('SMTP authentication username.', 'wp-tester') . '</p>';
    }
    
    public function smtp_password_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['smtp_password']) ? $settings['smtp_password'] : '';
        
        echo '<input type="password" name="wp_tester_settings[smtp_password]" value="' . esc_attr($value) . '" placeholder="' . esc_attr(__('Your email password or app password', 'wp-tester')) . '" />';
        echo '<p class="description">' . __('SMTP authentication password.', 'wp-tester') . '</p>';
    }
    
    public function smtp_encryption_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['smtp_encryption']) ? $settings['smtp_encryption'] : 'tls';
        
        $options = array(
            'none' => __('None', 'wp-tester'),
            'tls' => __('TLS', 'wp-tester'),
            'ssl' => __('SSL', 'wp-tester')
        );
        
        echo '<select name="wp_tester_settings[smtp_encryption]">';
        foreach ($options as $key => $label) {
            echo '<option value="' . $key . '" ' . selected($value, $key, false) . '>' . $label . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Encryption method for SMTP connection.', 'wp-tester') . '</p>';
    }
    
    public function from_email_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['from_email']) ? $settings['from_email'] : get_option('admin_email');
        
        echo '<input type="email" name="wp_tester_settings[from_email]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Email address to send notifications from.', 'wp-tester') . '</p>';
    }
    
    public function from_name_callback() {
        $settings = get_option('wp_tester_settings', array());
        $value = isset($settings['from_name']) ? $settings['from_name'] : 'WP Tester';
        
        echo '<input type="text" name="wp_tester_settings[from_name]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Name to display in email sender field.', 'wp-tester') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        // Debug logging
        error_log('WP Tester: Sanitizing settings input: ' . print_r($input, true));
        
        $sanitized = array();
        
        if (isset($input['crawl_frequency'])) {
            $allowed_frequencies = array('never', 'hourly', 'twicedaily', 'daily', 'weekly');
            $sanitized['crawl_frequency'] = in_array($input['crawl_frequency'], $allowed_frequencies) 
                ? $input['crawl_frequency'] 
                : 'never';
        }
        
        if (isset($input['test_timeout'])) {
            $sanitized['test_timeout'] = max(5, min(300, intval($input['test_timeout'])));
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
        
        // Test frequency settings
        if (isset($input['test_frequency'])) {
            $allowed_frequencies = array('never', 'daily', 'weekly', 'monthly');
            $sanitized['test_frequency'] = in_array($input['test_frequency'], $allowed_frequencies) 
                ? $input['test_frequency'] 
                : 'never';
        }
        
        if (isset($input['test_schedule_time'])) {
            // Validate time format (HH:MM)
            if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $input['test_schedule_time'])) {
                $sanitized['test_schedule_time'] = sanitize_text_field($input['test_schedule_time']);
            } else {
                $sanitized['test_schedule_time'] = '02:00'; // Default to 2 AM
            }
        }
        
        // Email notification settings
        if (isset($input['email_notifications'])) {
            $sanitized['email_notifications'] = (bool) $input['email_notifications'];
        }
        
        // Email configuration settings
        if (isset($input['email_recipients'])) {
            $recipients = array_filter(array_map('trim', explode("\n", $input['email_recipients'])));
            $valid_recipients = array();
            foreach ($recipients as $recipient) {
                if (is_email($recipient)) {
                    $valid_recipients[] = sanitize_email($recipient);
                }
            }
            $sanitized['email_recipients'] = implode("\n", $valid_recipients);
        }
        
        if (isset($input['smtp_host'])) {
            $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host']);
        }
        
        if (isset($input['smtp_port'])) {
            $sanitized['smtp_port'] = max(1, min(65535, intval($input['smtp_port'])));
        }
        
        if (isset($input['smtp_username'])) {
            $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username']);
        }
        
        if (isset($input['smtp_password'])) {
            $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password']);
        }
        
        if (isset($input['smtp_encryption'])) {
            $allowed_encryption = array('none', 'tls', 'ssl');
            $sanitized['smtp_encryption'] = in_array($input['smtp_encryption'], $allowed_encryption) 
                ? $input['smtp_encryption'] 
                : 'tls';
        }
        
        if (isset($input['from_email'])) {
            $email = sanitize_email($input['from_email']);
            $sanitized['from_email'] = is_email($email) ? $email : get_option('admin_email');
        }
        
        if (isset($input['from_name'])) {
            $sanitized['from_name'] = sanitize_text_field($input['from_name']);
        }
        
        // Debug logging
        error_log('WP Tester: Sanitized settings output: ' . print_r($sanitized, true));
        
        return $sanitized;
    }
    
    /**
     * Get status badge HTML
     */
    public function get_status_badge($status) {
        $badges = array(
            'passed' => '<span class="wp-tester-badge wp-tester-badge-success">Passed</span>',
            'failed' => '<span class="wp-tester-badge wp-tester-badge-danger">Failed</span>',
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
    
    /**
     * AI Chat page
     */
    public function ai_chat_page() {
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-ai-chat.php';
    }
    
    /**
     * Automation Suite page
     */
    public function automation_suite_page() {
        include WP_TESTER_PLUGIN_DIR . 'templates/admin-automation-suite.php';
    }
}