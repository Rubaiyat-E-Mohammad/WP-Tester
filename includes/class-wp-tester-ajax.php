<?php
/**
 * WP Tester AJAX Class
 * 
 * Handles AJAX requests for dynamic content and interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin AJAX actions
        add_action('wp_ajax_wp_tester_run_all_tests', array($this, 'run_all_tests'));
        add_action('wp_ajax_wp_tester_run_crawl', array($this, 'run_crawl'));
        add_action('wp_ajax_wp_tester_test_flow', array($this, 'test_flow'));
        add_action('wp_ajax_wp_tester_run_single_test', array($this, 'run_single_test'));
        add_action('wp_ajax_wp_tester_delete_flow', array($this, 'delete_flow'));
        add_action('wp_ajax_wp_tester_discover_flows', array($this, 'discover_flows'));
        add_action('wp_ajax_wp_tester_bulk_action', array($this, 'bulk_action'));
        add_action('wp_ajax_wp_tester_get_test_status', array($this, 'get_test_status'));
        add_action('wp_ajax_wp_tester_export_report', array($this, 'export_report'));
        
        // Frontend AJAX actions (if needed)
        add_action('wp_ajax_nopriv_wp_tester_track_interaction', array($this, 'track_interaction'));
        
        // Modern UI AJAX actions
        add_action('wp_ajax_wp_tester_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_wp_tester_cleanup_duplicates', array($this, 'cleanup_duplicates'));
        add_action('wp_ajax_wp_tester_cleanup_all_flows', array($this, 'cleanup_all_flows'));
        add_action('wp_ajax_wp_tester_get_duplicate_flows_info', array($this, 'get_duplicate_flows_info'));
        add_action('wp_ajax_wp_tester_cleanup_test_results', array($this, 'cleanup_test_results'));
        add_action('wp_ajax_wp_tester_cleanup_all_test_results', array($this, 'cleanup_all_test_results'));
        add_action('wp_ajax_wp_tester_get_test_results_stats', array($this, 'get_test_results_stats'));
        add_action('wp_ajax_wp_tester_bulk_test_results_action', array($this, 'bulk_test_results_action'));
        add_action('wp_ajax_wp_tester_bulk_crawl_action', array($this, 'bulk_crawl_action'));
        add_action('wp_ajax_wp_tester_debug_bulk_action', array($this, 'debug_bulk_action'));
        add_action('wp_ajax_wp_tester_cleanup_crawl_duplicates', array($this, 'cleanup_crawl_duplicates'));
        add_action('wp_ajax_wp_tester_cleanup_all_crawls', array($this, 'cleanup_all_crawls'));
        add_action('wp_ajax_wp_tester_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_wp_tester_reset_flows', array($this, 'reset_flows'));
        add_action('wp_ajax_wp_tester_export_data', array($this, 'export_data'));
        add_action('wp_ajax_wp_tester_system_check', array($this, 'system_check'));
        add_action('wp_ajax_wp_tester_generate_ai_flows', array($this, 'generate_ai_flows'));
        add_action('wp_ajax_wp_tester_set_ai_api_key', array($this, 'set_ai_api_key'));
        add_action('wp_ajax_wp_tester_get_available_plugins', array($this, 'get_available_plugins'));
        
        // AI Chat AJAX actions
        add_action('wp_ajax_wp_tester_ai_chat', array($this, 'ai_chat'));
        add_action('wp_ajax_wp_tester_save_conversation', array($this, 'save_conversation'));
        add_action('wp_ajax_wp_tester_create_ai_flow', array($this, 'create_ai_flow'));
        add_action('wp_ajax_wp_tester_get_ai_flows', array($this, 'get_ai_flows'));
        add_action('wp_ajax_wp_tester_load_more_results', array($this, 'load_more_results'));
        add_action('wp_ajax_wp_tester_get_available_ai_models', array($this, 'get_available_ai_models'));
        add_action('wp_ajax_wp_tester_load_more_crawl_results', array($this, 'load_more_crawl_results'));
        add_action('wp_ajax_wp_tester_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_wp_tester_debug_actions', array($this, 'debug_actions'));
    }
    
    /**
     * Test AJAX connection
     */
    public function test_connection() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        global $current_user;
        $user_id = isset($current_user->ID) ? $current_user->ID : 0;
        
        wp_send_json_success(array(
            'message' => 'AJAX connection working',
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'debug_info' => array(
                'ajax_action' => 'wp_tester_test_connection',
                'nonce_verified' => true,
                'user_can_manage_options' => current_user_can('manage_options')
            )
        ));
    }
    
    /**
     * Debug AJAX actions
     */
    public function debug_actions() {
        global $wp_filter;
        
        $ajax_actions = array();
        foreach ($wp_filter as $hook => $filters) {
            if (strpos($hook, 'wp_ajax_wp_tester_') === 0) {
                $ajax_actions[] = $hook;
            }
        }
        
        wp_send_json_success(array(
            'message' => 'AJAX actions debug',
            'registered_actions' => $ajax_actions,
            'wp_tester_actions' => array(
                'wp_ajax_wp_tester_test_connection' => !empty($wp_filter['wp_ajax_wp_tester_test_connection']),
                'wp_ajax_wp_tester_create_ai_flow' => !empty($wp_filter['wp_ajax_wp_tester_create_ai_flow']),
                'wp_ajax_wp_tester_ai_chat' => !empty($wp_filter['wp_ajax_wp_tester_ai_chat'])
            ),
            'debug_info' => array(
                'current_user_can_manage_options' => current_user_can('manage_options')
            )
        ));
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            // Set longer execution time for bulk operations
            if (current_user_can('manage_options')) {
                set_time_limit(300); // 5 minutes max
            }
            
            $admin = new WP_Tester_Admin();
            $database = new WP_Tester_Database();
            
            $flows = $database->get_flows(true); // Get active flows only
            
            if (empty($flows)) {
                wp_send_json_error(array(
                    'message' => __('No active flows found to test.', 'wp-tester')
                ));
                return;
            }
            
            $results = array();
            $start_time = time();
            $max_execution_time = 240; // 4 minutes to allow for cleanup
            
            foreach ($flows as $flow) {
                // Check execution time limit
                if ((time() - $start_time) > $max_execution_time) {
                    $results[] = array(
                        'flow_id' => $flow->id,
                        'flow_name' => $flow->flow_name,
                        'result' => array(
                            'success' => false,
                            'error' => 'Execution time limit reached'
                        )
                    );
                    break;
                }
                
                $result = $admin->execute_flow_with_fallback($flow->id, true);
                $results[] = array(
                    'flow_id' => $flow->id,
                    'flow_name' => $flow->flow_name,
                    'result' => $result
                );
                
                // Small delay between flows to prevent server overload
                usleep(100000); // 0.1 second
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Executed %d flows successfully.', 'wp-tester'), count($flows ?: [])),
                'results' => $results
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to run tests: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Run site crawl
     */
    public function run_crawl() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        // Set longer execution time for crawl process
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');
        
        try {
            $crawler = new WP_Tester_Crawler();
            $result = $crawler->run_full_crawl();
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Crawl completed. Discovered %d URLs and %d flows in %.2f seconds.', 'wp-tester'),
                        $result['crawled_count'] ?: 0,
                        $result['discovered_flows'] ?: 0,
                        $result['execution_time'] ?: 0
                    ),
                    'crawled_count' => $result['crawled_count'],
                    'discovered_flows' => $result['discovered_flows'],
                    'execution_time' => $result['execution_time']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Crawl failed: ', 'wp-tester') . $result['error']
                ));
            }
            
        } catch (Exception $e) {
            error_log('WP Tester Crawl Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Failed to run crawl: ', 'wp-tester') . $e->getMessage()
            ));
        } catch (Error $e) {
            error_log('WP Tester Crawl Fatal Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Crawl encountered a fatal error. Please check your server logs.', 'wp-tester')
            ));
        }
    }
    
    /**
     * Test single flow
     */
    public function test_flow() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $flow_id = intval($_POST['flow_id'] ?? 0);
        if (!$flow_id || $flow_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid flow ID.', 'wp-tester')
            ));
            return;
        }
        
        try {
            $admin = new WP_Tester_Admin();
            $result = $admin->execute_flow_with_fallback($flow_id, true);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Flow test completed with status: %s', 'wp-tester'),
                        $result['status'] ?: 'unknown'
                    ),
                    'result' => $result
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Flow test failed: ', 'wp-tester') . $result['error']
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to test flow: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Run single test (retry functionality)
     */
    public function run_single_test() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $flow_id = intval($_POST['flow_id'] ?? 0);
        if (!$flow_id || $flow_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid flow ID.', 'wp-tester')
            ));
            return;
        }
        
        try {
            // Set execution time limit for single test
            set_time_limit(120); // 2 minutes
            
            $admin = new WP_Tester_Admin();
            $result = $admin->execute_flow_with_fallback($flow_id, true);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Test completed successfully. Status: %s, Steps: %d/%d, Time: %.2fs', 'wp-tester'),
                        $result['status'],
                        $result['steps_passed'],
                        $result['steps_executed'],
                        $result['execution_time']
                    ),
                    'status' => $result['status'],
                    'steps_executed' => $result['steps_executed'],
                    'steps_passed' => $result['steps_passed'],
                    'steps_failed' => $result['steps_failed'],
                    'execution_time' => $result['execution_time']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Test failed: ', 'wp-tester') . $result['error']
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to run test: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Delete flow
     */
    public function delete_flow() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $flow_id = intval($_POST['flow_id'] ?? 0);
        if (!$flow_id || $flow_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid flow ID.', 'wp-tester')
            ));
            return;
        }
        
        try {
            global $wpdb;
            
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            $result = $wpdb->delete($flows_table, array('id' => $flow_id), array('%d'));
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => __('Flow deleted successfully.', 'wp-tester')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to delete flow.', 'wp-tester')
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to delete flow: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Discover flows
     */
    public function discover_flows() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $crawler = new WP_Tester_Crawler();
            $result = $crawler->run_full_crawl();
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Flow discovery completed. Found %d new flows.', 'wp-tester'),
                        $result['discovered_flows'] ?: 0
                    ),
                    'discovered_flows' => $result['discovered_flows']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Flow discovery failed: ', 'wp-tester') . $result['error']
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to discover flows: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Bulk actions
     */
    public function bulk_action() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $flow_ids = array_filter(array_map('intval', $_POST['flow_ids'] ?? []));
        
        if (empty($action) || empty($flow_ids)) {
            wp_send_json_error(array(
                'message' => __('Invalid action or no flows selected.', 'wp-tester')
            ));
            return;
        }
        
        // Validate action
        $allowed_actions = array('test', 'activate', 'deactivate', 'delete');
        if (!in_array($action, $allowed_actions, true)) {
            wp_send_json_error(array(
                'message' => __('Invalid bulk action.', 'wp-tester')
            ));
            return;
        }
        
        try {
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            switch ($action) {
                case 'test':
                    $admin = new WP_Tester_Admin();
                    $results = array();
                    foreach ($flow_ids as $flow_id) {
                        $results[$flow_id] = $admin->execute_flow_with_fallback($flow_id);
                    }
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Tested %d flows.', 'wp-tester'), count($flow_ids ?: [])),
                        'results' => $results
                    ));
                    break;
                    
                case 'activate':
                    $placeholders = implode(',', array_fill(0, count($flow_ids), '%d'));
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$flows_table} SET is_active = 1 WHERE id IN ({$placeholders})",
                        ...$flow_ids
                    ));
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Activated %d flows.', 'wp-tester'), count($flow_ids ?: []))
                    ));
                    break;
                    
                case 'deactivate':
                    $placeholders = implode(',', array_fill(0, count($flow_ids), '%d'));
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$flows_table} SET is_active = 0 WHERE id IN ({$placeholders})",
                        ...$flow_ids
                    ));
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Deactivated %d flows.', 'wp-tester'), count($flow_ids ?: []))
                    ));
                    break;
                    
                case 'delete':
                    $placeholders = implode(',', array_fill(0, count($flow_ids), '%d'));
                    $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$flows_table} WHERE id IN ({$placeholders})",
                        ...$flow_ids
                    ));
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Deleted %d flows.', 'wp-tester'), count($flow_ids ?: []))
                    ));
                    break;
                    
                default:
                    wp_send_json_error(array(
                        'message' => __('Unknown bulk action.', 'wp-tester')
                    ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to perform bulk action: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Get test status (for real-time updates)
     */
    public function get_test_status() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $test_run_id = sanitize_text_field($_POST['test_run_id'] ?? '');
        if (empty($test_run_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid test run ID.', 'wp-tester')
            ));
            return;
        }
        
        try {
            global $wpdb;
            $results_table = $wpdb->prefix . 'wp_tester_test_results';
            
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$results_table} WHERE test_run_id = %s ORDER BY started_at DESC LIMIT 1",
                $test_run_id
            ));
            
            if ($result) {
                wp_send_json_success(array(
                    'status' => $result->status,
                    'steps_executed' => $result->steps_executed,
                    'steps_passed' => $result->steps_passed,
                    'steps_failed' => $result->steps_failed,
                    'execution_time' => $result->execution_time,
                    'completed' => !empty($result->completed_at)
                ));
            } else {
                wp_send_json_success(array(
                    'status' => 'running',
                    'completed' => false
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to get test status: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Export report
     */
    public function export_report() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $format = sanitize_text_field($_POST['format'] ?? 'json');
            $allowed_formats = array('json', 'csv', 'pdf');
            if (!in_array($format, $allowed_formats, true)) {
                $format = 'json';
            }
            
            $flow_id = !empty($_POST['flow_id']) ? intval($_POST['flow_id']) : null;
            $date_from = !empty($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : null;
            $date_to = !empty($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : null;
            
            $reporter = new WP_Tester_Feedback_Reporter();
            $export_data = $reporter->generate_export_report($flow_id, $date_from, $date_to, $format);
            
            switch ($format) {
                case 'csv':
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="wp-tester-report-' . date('Y-m-d') . '.csv"');
                    echo $export_data;
                    break;
                    
                case 'pdf':
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="wp-tester-report-' . date('Y-m-d') . '.pdf"');
                    echo $export_data;
                    break;
                    
                default:
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="wp-tester-report-' . date('Y-m-d') . '.json"');
                    echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
            }
            
            exit;
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to export report: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Track interaction (frontend)
     */
    public function track_interaction() {
        // This could be used to track real user interactions for comparison
        // with automated tests (future feature)
        
        // Validate required fields
        if (empty($_POST['element']) || empty($_POST['action']) || empty($_POST['url'])) {
            wp_send_json_error(array(
                'message' => 'Missing required interaction data'
            ));
            return;
        }
        
        $interaction_data = array(
            'element' => sanitize_text_field($_POST['element']),
            'action' => sanitize_text_field($_POST['action']),
            'url' => esc_url_raw($_POST['url']),
            'timestamp' => current_time('mysql')
        );
        
        // Store interaction data for analysis
        // This is optional and can be implemented based on privacy requirements
        
        wp_send_json_success(array(
            'message' => 'Interaction tracked'
        ));
    }
    
    /**
     * Get dashboard statistics for React components
     */
    public function get_dashboard_stats() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            $stats = $database->get_dashboard_stats();
            
            // Enhanced stats for modern UI
            $enhanced_stats = array(
                'totalPages' => $stats['total_pages'] ?: 0,
                'totalFlows' => $stats['total_flows'] ?: 0,
                'recentTests' => $stats['recent_tests'] ?: 0,
                'successRate' => $stats['success_rate'] ?: 0,
                'avgResponseTime' => $stats['avg_response_time'] ?: 0,
                'totalErrors' => $stats['total_errors'] ?: 0,
                'lastCrawl' => $stats['last_crawl'] ?: '',
                'systemStatus' => array(
                    'database' => 'ok',
                    'crawler' => 'ok',
                    'php_version' => PHP_VERSION,
                    'wp_version' => get_bloginfo('version'),
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'disk_space' => function_exists('disk_free_space') ? 
                        round(disk_free_space('.') / 1024 / 1024 / 1024, 2) . ' GB' : 'Unknown'
                ),
                'recentActivity' => $this->get_recent_activity(),
                'performance' => array(
                    'avg_load_time' => $stats['avg_load_time'] ?: 0,
                    'slowest_page' => $stats['slowest_page'] ?: '',
                    'fastest_page' => $stats['fastest_page'] ?: ''
                )
            );
            
            wp_send_json_success($enhanced_stats);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to fetch dashboard stats', 'wp-tester'),
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Get recent activity for dashboard
     */
    private function get_recent_activity() {
        $database = new WP_Tester_Database();
        $recent_results = $database->get_test_results(null, 5, 0); // Use existing method
        
        $activity = array();
        foreach ($recent_results ?: [] as $result) {
            // Handle different possible column names
            $created_time = $result->completed_at ?? $result->started_at ?? current_time('mysql');
            $status = $result->status ?: 'unknown';
            
            $activity[] = array(
                'id' => $result->id,
                'title' => sprintf(__('Test completed for flow: %s', 'wp-tester'), $result->flow_name ?: 'Unknown Flow'),
                'description' => sprintf(
                    __('%d steps executed, %d passed, %d failed', 'wp-tester'), 
                    $result->steps_executed ?: 0,
                    $result->steps_passed ?: 0,
                    $result->steps_failed ?: 0
                ),
                'time' => human_time_diff(strtotime($created_time), current_time('timestamp')) . ' ago',
                'type' => ($status === 'passed' || $status === 'completed') ? 'success' : 
                         ($status === 'failed' ? 'error' : 'warning'),
                'status' => $status
            );
        }
        
        return $activity;
    }
    
    /**
     * Cleanup duplicate flows
     */
    public function cleanup_duplicates() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            
            // First, let's check how many flows exist before cleanup
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            $total_flows_before = $wpdb->get_var("SELECT COUNT(*) FROM {$flows_table}");
            
            // Check for duplicates before cleanup
            $duplicates_before = $wpdb->get_results(
                "SELECT flow_type, start_url, COUNT(*) as count
                 FROM {$flows_table} 
                 GROUP BY flow_type, start_url 
                 HAVING COUNT(*) > 1"
            );
            
            $removed_count = $database->force_cleanup_duplicates();
            
            // Check how many flows exist after cleanup
            $total_flows_after = $wpdb->get_var("SELECT COUNT(*) FROM {$flows_table}");
            
            wp_send_json_success(array(
                'message' => sprintf(__('Cleaned up %d duplicate flows. Found %d duplicate groups before cleanup.', 'wp-tester'), $removed_count, count($duplicates_before)),
                'removed_count' => $removed_count,
                'debug_info' => array(
                    'flows_before' => $total_flows_before,
                    'flows_after' => $total_flows_after,
                    'duplicate_groups' => count($duplicates_before)
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to cleanup duplicates: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Cleanup all flows (delete all flows)
     */
    public function cleanup_all_flows() {
        // Check nonce
        if (!check_ajax_referer('wp_tester_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'wp-tester')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            $results_table = $wpdb->prefix . 'wp_tester_test_results';
            
            // Check if tables exist
            $flows_exists = $wpdb->get_var("SHOW TABLES LIKE '{$flows_table}'");
            $results_exists = $wpdb->get_var("SHOW TABLES LIKE '{$results_table}'");
            
            if (!$flows_exists) {
                wp_send_json_error(array('message' => __('Flows table does not exist. Please deactivate and reactivate the plugin.', 'wp-tester')));
                return;
            }
            
            // Count flows before deletion
            $total_flows = $wpdb->get_var("SELECT COUNT(*) FROM {$flows_table}");
            
            // Delete all flows
            $deleted_flows = $wpdb->query("DELETE FROM {$flows_table}");
            
            // Also clean up related test results if table exists
            $deleted_results = 0;
            if ($results_exists) {
                $deleted_results = $wpdb->query("DELETE FROM {$results_table}");
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully deleted all %d flows and %d related test results.', 'wp-tester'), $deleted_flows, $deleted_results),
                'deleted_count' => $deleted_flows,
                'deleted_results' => $deleted_results,
                'debug_info' => array(
                    'flows_before' => $total_flows,
                    'flows_after' => 0,
                    'flows_table' => $flows_table,
                    'results_table' => $results_table,
                    'tables_exist' => array(
                        'flows' => $flows_exists ? 'yes' : 'no',
                        'results' => $results_exists ? 'yes' : 'no'
                    )
                )
            ));
            
        } catch (Exception $e) {
            error_log('WP Tester: Cleanup all flows error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Failed to cleanup all flows: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Get duplicate flows information for debugging
     */
    public function get_duplicate_flows_info() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            $duplicate_info = $database->get_duplicate_flows_info();
            
            wp_send_json_success(array(
                'message' => 'Duplicate flows information retrieved',
                'data' => $duplicate_info
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to get duplicate flows info: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Get test results statistics
     */
    public function get_test_results_stats() {
        // Get test results stats
        
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            // Insufficient permissions
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            $stats = $database->get_test_results_stats();
            
            // Stats retrieved successfully
            
            wp_send_json_success(array(
                'stats' => $stats
            ));
            
        } catch (Exception $e) {
            // Error getting stats
            wp_send_json_error(array(
                'message' => __('Failed to get test results statistics: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Cleanup test results
     */
    public function cleanup_test_results() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            
            // Get cleanup options from POST data
            $options = array(
                'older_than_days' => intval($_POST['older_than_days'] ?? 30),
                'keep_successful' => isset($_POST['keep_successful']) ? (bool)$_POST['keep_successful'] : true,
                'keep_failed' => isset($_POST['keep_failed']) ? (bool)$_POST['keep_failed'] : false,
                'max_results_per_flow' => intval($_POST['max_results_per_flow'] ?? 10)
            );
            
            $removed_count = $database->cleanup_test_results($options);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Cleaned up %d test results.', 'wp-tester'), $removed_count),
                'removed_count' => $removed_count,
                'options' => $options
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to cleanup test results: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Simple cleanup - remove all test results
     */
    public function cleanup_all_test_results() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            $removed_count = $database->cleanup_all_test_results();
            
            wp_send_json_success(array(
                'message' => sprintf(__('Removed all %d test results.', 'wp-tester'), $removed_count),
                'removed_count' => $removed_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to cleanup test results: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Bulk actions for test results
     */
    public function bulk_test_results_action() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        // Bulk test results action
        
        if (!current_user_can('manage_options')) {
            // Insufficient permissions
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $result_ids = array_filter(array_map('intval', $_POST['result_ids'] ?? []));
        
        // Processing bulk action
        
        if (empty($action) || empty($result_ids)) {
            // Empty action or result IDs
            wp_send_json_error(array(
                'message' => __('Invalid action or no results selected.', 'wp-tester')
            ));
            return;
        }
        
        // Validate action
        $allowed_actions = array('delete', 'export');
        if (!in_array($action, $allowed_actions, true)) {
            wp_send_json_error(array(
                'message' => __('Invalid bulk action.', 'wp-tester')
            ));
            return;
        }
        
        try {
            global $wpdb;
            $test_results_table = $wpdb->prefix . 'wp_tester_test_results';
            
            switch ($action) {
                case 'delete':
                    $placeholders = implode(',', array_fill(0, count($result_ids), '%d'));
                    $delete_sql = "DELETE FROM {$test_results_table} WHERE id IN ({$placeholders})";
                    $deleted_count = $wpdb->query($wpdb->prepare($delete_sql, ...$result_ids));
                    
                    if ($deleted_count === false) {
                        error_log("WP Tester: Delete query failed: " . $wpdb->last_error);
                        wp_send_json_error(array(
                            'message' => __('Delete operation failed: ', 'wp-tester') . $wpdb->last_error
                        ));
                        return;
                    }
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Deleted %d test results.', 'wp-tester'), $deleted_count)
                    ));
                    break;
                    
                case 'export':
                    // For now, just return success - export functionality can be implemented later
                    wp_send_json_success(array(
                        'message' => sprintf(__('Export functionality for %d results will be implemented soon.', 'wp-tester'), count($result_ids))
                    ));
                    break;
                    
                default:
                    wp_send_json_error(array(
                        'message' => __('Invalid bulk action.', 'wp-tester')
                    ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to perform bulk action: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Bulk actions for crawl results
     */
    public function bulk_crawl_action() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $crawl_ids = array_filter(array_map('intval', $_POST['crawl_ids'] ?? []));
        
        if (empty($action) || empty($crawl_ids)) {
            wp_send_json_error(array(
                'message' => __('Invalid action or no crawl results selected.', 'wp-tester')
            ));
            return;
        }
        
        // Validate action
        $allowed_actions = array('delete', 'export');
        if (!in_array($action, $allowed_actions, true)) {
            wp_send_json_error(array(
                'message' => __('Invalid bulk action.', 'wp-tester')
            ));
            return;
        }
        
        try {
            global $wpdb;
            $crawl_results_table = $wpdb->prefix . 'wp_tester_crawl_results';
            
            switch ($action) {
                case 'delete':
                    $placeholders = implode(',', array_fill(0, count($crawl_ids), '%d'));
                    $deleted_count = $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$crawl_results_table} WHERE id IN ({$placeholders})",
                        ...$crawl_ids
                    ));
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Deleted %d crawl results.', 'wp-tester'), $deleted_count)
                    ));
                    break;
                    
                case 'export':
                    // For now, just return success - export functionality can be implemented later
                    wp_send_json_success(array(
                        'message' => sprintf(__('Export functionality for %d crawl results will be implemented soon.', 'wp-tester'), count($crawl_ids))
                    ));
                    break;
                    
                default:
                    wp_send_json_error(array(
                        'message' => __('Invalid bulk action.', 'wp-tester')
                    ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to perform bulk action: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Cleanup duplicate crawl results
     */
    public function cleanup_crawl_duplicates() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            global $wpdb;
            $crawl_results_table = $wpdb->prefix . 'wp_tester_crawl_results';
            
            // Ensure schema is up to date
            $database = new WP_Tester_Database();
            $database->update_crawl_results_table_schema();
            
            // Debug: Log the table name and query
            error_log("WP Tester: Cleanup crawl duplicates using table: {$crawl_results_table}");
            
            // Check if table exists first
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$crawl_results_table}'");
            if (!$table_exists) {
                wp_send_json_error(array('message' => __('Crawl results table does not exist', 'wp-tester')));
                return;
            }
            
            // Find duplicate crawl results based on URL and page_type
            // Use COALESCE to handle missing columns gracefully
            $duplicates = $wpdb->get_results(
                "SELECT url, page_type, 
                        GROUP_CONCAT(id ORDER BY COALESCE(last_crawled, created_at, '1970-01-01') ASC) as ids, 
                        COUNT(*) as count
                 FROM {$crawl_results_table} 
                 GROUP BY url, page_type 
                 HAVING COUNT(*) > 1"
            );
            
            error_log("WP Tester: Found " . count($duplicates) . " duplicate crawl result groups");
            
            $removed_count = 0;
            
            foreach ($duplicates as $duplicate) {
                error_log("WP Tester: Processing duplicate group - URL: {$duplicate->url}, Type: {$duplicate->page_type}, Count: {$duplicate->count}, IDs: {$duplicate->ids}");
                
                $ids = explode(',', $duplicate->ids);
                $keep_id = array_shift($ids); // Keep the first (oldest) one
                
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                    $delete_query = "DELETE FROM {$crawl_results_table} WHERE id IN ({$placeholders})";
                    error_log("WP Tester: Executing delete query: {$delete_query} with IDs: " . implode(',', $ids));
                    
                    $deleted_count = $wpdb->query($wpdb->prepare($delete_query, ...$ids));
                    error_log("WP Tester: Delete query affected {$deleted_count} rows");
                    $removed_count += $deleted_count;
                }
            }
            
            error_log("WP Tester: Total crawl duplicates removed: {$removed_count}");
            
            wp_send_json_success(array(
                'message' => sprintf(__('Removed %d duplicate crawl results.', 'wp-tester'), $removed_count)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to cleanup duplicates: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Cleanup all crawl results
     */
    public function cleanup_all_crawls() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            global $wpdb;
            $crawl_results_table = $wpdb->prefix . 'wp_tester_crawl_results';
            
            // Check if table exists first
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$crawl_results_table}'");
            if (!$table_exists) {
                wp_send_json_error(array('message' => __('Crawl results table does not exist', 'wp-tester')));
                return;
            }
            
            // Get count before deletion
            $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$crawl_results_table}");
            
            // Delete all crawl results
            $deleted_count = $wpdb->query("DELETE FROM {$crawl_results_table}");
            
            error_log("WP Tester: Cleanup all crawls - deleted {$deleted_count} out of {$total_count} total records");
            
            wp_send_json_success(array(
                'message' => sprintf(__('Deleted all %d crawl results successfully.', 'wp-tester'), $deleted_count),
                'deleted_count' => $deleted_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to cleanup all crawls: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Clear cache
     */
    public function clear_cache() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Clear any plugin-specific cache
            delete_transient('wp_tester_cache');
            delete_transient('wp_tester_dashboard_cache');
            
            wp_send_json_success(array(
                'message' => __('Cache cleared successfully', 'wp-tester')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to clear cache: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Reset flows
     */
    public function reset_flows() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            // Delete all flows
            $deleted_count = $wpdb->query("DELETE FROM {$flows_table}");
            
            wp_send_json_success(array(
                'message' => sprintf(__('Reset %d flows successfully', 'wp-tester'), $deleted_count)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to reset flows: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Export data
     */
    public function export_data() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $database = new WP_Tester_Database();
            
            // Get all data
            $data = array(
                'flows' => $database->get_flows(),
                'test_results' => $database->get_test_results(),
                'crawl_results' => $database->get_crawl_results(1000, 0),
                'export_date' => current_time('mysql'),
                'plugin_version' => WP_TESTER_VERSION,
                'site_url' => get_site_url(),
                'exported_by' => wp_get_current_user()->display_name ?? 'Unknown'
            );
            
            // Generate filename
            $filename = 'wp-tester-export-' . date('Y-m-d-H-i-s') . '.json';
            
            // Create temporary file
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['path'] . '/' . $filename;
            
            // Write data to file
            $json_data = wp_json_encode($data, JSON_PRETTY_PRINT);
            if (file_put_contents($temp_file, $json_data) === false) {
                throw new Exception('Failed to create export file');
            }
            
            // Return download URL
            $download_url = $upload_dir['url'] . '/' . $filename;
            
            wp_send_json_success(array(
                'message' => __('Data exported successfully!', 'wp-tester'),
                'download_url' => $download_url,
                'filename' => $filename,
                'file_size' => size_format(filesize($temp_file))
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to export data: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * System check
     */
    public function system_check() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $checks = array();
            
            // Check database tables
            global $wpdb;
            $tables = array(
                'flows' => $wpdb->prefix . 'wp_tester_flows',
                'test_results' => $wpdb->prefix . 'wp_tester_test_results',
                'crawl_results' => $wpdb->prefix . 'wp_tester_crawl_results',
                'screenshots' => $wpdb->prefix . 'wp_tester_screenshots'
            );
            
            foreach ($tables as $name => $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
                $checks['database'][$name] = array(
                    'status' => $exists ? 'ok' : 'error',
                    'message' => $exists ? 'Table exists' : 'Table missing'
                );
            }
            
            // Check file permissions
            $upload_dir = wp_upload_dir();
            $screenshots_dir = $upload_dir['basedir'] . '/wp-tester-screenshots';
            $checks['permissions']['screenshots_dir'] = array(
                'status' => is_writable($upload_dir['basedir']) ? 'ok' : 'warning',
                'message' => is_writable($upload_dir['basedir']) ? 'Writable' : 'Not writable'
            );
            
            // Check PHP version
            $checks['php']['version'] = array(
                'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'ok' : 'warning',
                'message' => 'PHP ' . PHP_VERSION
            );
            
            // Check WordPress version
            global $wp_version;
            $checks['wordpress']['version'] = array(
                'status' => version_compare($wp_version, '6.0', '>=') ? 'ok' : 'warning',
                'message' => 'WordPress ' . $wp_version
            );
            
            wp_send_json_success(array(
                'message' => __('System check completed', 'wp-tester'),
                'checks' => $checks
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to perform system check: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Generate AI flows
     */
    public function generate_ai_flows() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            // Set longer execution time for AI processing
            set_time_limit(300); // 5 minutes
            
            $ai_generator = new WP_Tester_AI_Flow_Generator();
            
            // Get options from POST data
            $options = array(
                'include_admin' => isset($_POST['include_admin']) ? (bool)$_POST['include_admin'] : true,
                'include_frontend' => isset($_POST['include_frontend']) ? (bool)$_POST['include_frontend'] : true,
                'include_plugins' => isset($_POST['include_plugins']) ? (bool)$_POST['include_plugins'] : false,
                'selected_plugins' => isset($_POST['selected_plugins']) ? array_filter($_POST['selected_plugins']) : array(),
                'max_flows_per_area' => intval($_POST['max_flows_per_area'] ?? 10),
                'max_flows_per_plugin' => intval($_POST['max_flows_per_plugin'] ?? 5),
                'focus_areas' => array_filter(explode(',', $_POST['focus_areas'] ?? 'ecommerce,content,user_management,settings')),
                'ai_provider' => isset($_POST['ai_provider']) ? sanitize_text_field($_POST['ai_provider']) : 'free',
                'ai_model' => isset($_POST['ai_model']) ? sanitize_text_field($_POST['ai_model']) : 'gpt-3.5-turbo',
            );
            
            // Set the selected AI model
            if (!$ai_generator->set_ai_model($options['ai_model'])) {
                wp_send_json_error(array('message' => __('Invalid AI model selected', 'wp-tester')));
                return;
            }
            
            // For paid models, check if API key is provided
            if ($options['ai_provider'] !== 'free') {
                $api_key = get_option('wp_tester_ai_api_key', '');
                if (empty($api_key)) {
                    wp_send_json_error(array('message' => __('API key is required for paid models. Please configure your API key first.', 'wp-tester')));
                    return;
                }
            }
            
            $result = $ai_generator->generate_ai_flows($options);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'results' => $result['results']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('AI flow generation failed: ', 'wp-tester') . $result['error']
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to generate AI flows: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Set AI API key
     */
    public function set_ai_api_key() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            $api_provider = sanitize_text_field($_POST['api_provider'] ?? 'openai');
            $model = sanitize_text_field($_POST['model'] ?? '');
            
            // Validate API key format (basic validation) - only for paid models
            if (!empty($api_key) && strlen($api_key) < 10) {
                wp_send_json_error(array(
                    'message' => __('API key appears to be too short. Please check and try again.', 'wp-tester')
                ));
                return;
            }
            
            // Save API key and model configuration
            update_option('wp_tester_ai_api_key', $api_key);
            update_option('wp_tester_ai_api_provider', $api_provider);
            update_option('wp_tester_ai_model', $model);
            
            // Test API key if provided
            if (!empty($api_key)) {
                $test_result = $this->test_ai_api_key($api_key, $api_provider);
                if (!$test_result['success']) {
                    wp_send_json_error(array(
                        'message' => __('API key saved but test failed: ', 'wp-tester') . $test_result['error']
                    ));
                    return;
                }
            }
            
            wp_send_json_success(array(
                'message' => empty($api_key) ? 
                    __('API key removed. AI flows will use fallback generation.', 'wp-tester') :
                    __('API key saved and tested successfully!', 'wp-tester')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to save API key: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Get available plugins for AI flow generation
     */
    public function get_available_plugins() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $ai_generator = new WP_Tester_AI_Flow_Generator();
            $plugins = $ai_generator->get_available_plugins();
            
            wp_send_json_success(array(
                'plugins' => $plugins,
                'count' => count($plugins)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to get available plugins: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Get available AI models
     */
    public function get_available_ai_models() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $ai_generator = new WP_Tester_AI_Flow_Generator();
            $free_models = $ai_generator->get_free_models();
            $paid_models = $ai_generator->get_paid_models();
            $models_by_provider = $ai_generator->get_models_by_provider();
            
            wp_send_json_success(array(
                'free_models' => $free_models,
                'paid_models' => $paid_models,
                'models_by_provider' => $models_by_provider,
                'free_count' => count($free_models),
                'paid_count' => count($paid_models),
                'total_count' => count($free_models) + count($paid_models)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to get available AI models: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Test AI API key
     */
    private function test_ai_api_key($api_key, $provider) {
        try {
            $test_prompt = "Test connection. Respond with 'OK' if you can read this.";
            
            switch ($provider) {
                case 'openai':
                    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $api_key,
                            'Content-Type' => 'application/json',
                        ),
                        'body' => wp_json_encode(array(
                            'model' => 'gpt-3.5-turbo',
                            'messages' => array(
                                array(
                                    'role' => 'user',
                                    'content' => $test_prompt
                                )
                            ),
                            'max_tokens' => 10
                        )),
                        'timeout' => 15
                    ));
                    break;
                    
                default:
                    return array('success' => false, 'error' => 'Unsupported API provider');
            }
            
            if (is_wp_error($response)) {
                return array('success' => false, 'error' => $response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body ?: '{}', true);
            
            if (isset($data['error'])) {
                return array('success' => false, 'error' => $data['error']['message'] ?? 'Unknown API error');
            }
            
            return array('success' => true);
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Debug bulk action - simple test endpoint
     */
    public function debug_bulk_action() {
        // Debug bulk action called
        
        wp_send_json_success(array(
            'message' => 'Debug endpoint working',
            'post_data' => $_POST,
            'get_data' => $_GET,
            'request_data' => $_REQUEST
        ));
    }
    
    /**
     * Load more crawl results for pagination
     */
    public function load_more_crawl_results() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 20);
            $offset = ($page - 1) * $per_page;
            
            // Get crawl results from database
            $database = new WP_Tester_Database();
            $crawl_results = $database->get_crawl_results($per_page, $offset);
            
            // Get total count for pagination info
            $total_count = $database->get_crawl_results_count();
            $has_more = ($offset + $per_page) < $total_count;
            
            // Ensure we don't return more results than actually exist
            $actual_loaded = count($crawl_results);
            
            // Safety check: ensure total_loaded_so_far never exceeds total_count
            $total_loaded_so_far = min($offset + $actual_loaded, $total_count);
            
            // Debug logging for pagination issues
            error_log("WP Tester Crawl Pagination Debug: page={$page}, per_page={$per_page}, offset={$offset}, actual_loaded={$actual_loaded}, total_count={$total_count}, total_loaded_so_far={$total_loaded_so_far}");
            
            // Generate HTML for the new results
            $html = '';
            if (!empty($crawl_results)) {
                foreach ($crawl_results as $result) {
                    $page_type = $result->page_type ?? 'page';
                    $icons = [
                        'page' => 'admin-page',
                        'post' => 'admin-post',
                        'product' => 'cart',
                        'category' => 'category',
                        'archive' => 'archive'
                    ];
                    $icon = $icons[$page_type] ?? 'admin-page';
                    
                    $html .= '<div class="modern-list-item" data-page-type="' . esc_attr($page_type) . '">';
                    $html .= '<div class="item-checkbox">';
                    $html .= '<input type="checkbox" class="crawl-checkbox" value="' . esc_attr($result->id ?? '') . '" id="crawl-' . esc_attr($result->id ?? '') . '">';
                    $html .= '<label for="crawl-' . esc_attr($result->id ?? '') . '"></label>';
                    $html .= '</div>';
                    $html .= '<div class="item-info">';
                    $html .= '<div class="item-icon">';
                    $html .= '<span class="dashicons dashicons-' . $icon . '"></span>';
                    $html .= '</div>';
                    $html .= '<div class="item-details">';
                    $html .= '<h4>';
                    $html .= '<a href="' . esc_url($result->url ?? '#') . '" target="_blank" style="color: inherit; text-decoration: none;">';
                    $html .= esc_html($result->title ?? $result->url ?? 'Unknown Page');
                    $html .= '<span class="dashicons dashicons-external" style="font-size: 12px; margin-left: 0.25rem;"></span>';
                    $html .= '</a>';
                    $html .= '</h4>';
                    $html .= '<p>';
                    $html .= esc_html(ucfirst($page_type)) . ' • ';
                    $html .= esc_html(($result->forms_found ?? 0) . ' forms') . ' • ';
                    $html .= esc_html(($result->links_found ?? 0) . ' links');
                    $html .= '</p>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="item-meta">';
                    $html .= '<div style="text-align: right; font-size: 0.8125rem; color: #64748b;">';
                    $html .= '<div>Status: ';
                    $html .= '<span class="status-badge ' . esc_attr($result->status ?? 'success') . '">';
                    $html .= esc_html(ucfirst($result->status ?? 'Success'));
                    $html .= '</span>';
                    $html .= '</div>';
                    $html .= '<div style="margin-top: 0.25rem;">';
                    $html .= 'Crawled: ' . esc_html($result->crawled_at ?? 'Unknown');
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">';
                    $html .= '<button class="modern-btn modern-btn-secondary modern-btn-small view-details" data-url="' . esc_attr($result->url ?? '') . '">View</button>';
                    if (($result->forms_found ?? 0) > 0) {
                        $html .= '<button class="modern-btn modern-btn-primary modern-btn-small create-flow" data-url="' . esc_attr($result->url ?? '') . '">Create Flow</button>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
            
            wp_send_json_success(array(
                'html' => $html,
                'has_more' => $has_more,
                'current_page' => $page,
                'total_count' => $total_count,
                'loaded_count' => $actual_loaded,
                'total_loaded_so_far' => $total_loaded_so_far
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error loading crawl results: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * AI Chat handler
     */
    public function ai_chat() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $message = sanitize_text_field($_POST['message'] ?? '');
            $temperature = floatval($_POST['temperature'] ?? 0);
            $max_tokens = !empty($_POST['max_tokens']) ? intval($_POST['max_tokens']) : null;
            $chat_history = $_POST['chat_history'] ?? array();
            
            if (empty($message)) {
                wp_send_json_error('Message is required');
            }
            
            // Get model and API key from AI flow generator settings
            $model = get_option('wp_tester_ai_model', 'fallback-generator');
            $api_key = get_option('wp_tester_ai_api_key', '');
            $api_provider = get_option('wp_tester_ai_api_provider', 'openai');
            
            if (empty($model)) {
                wp_send_json_error('No AI model configured. Please configure your AI model in the AI Flow Generator settings.');
            }
            
            // Check if model requires API key
            $ai_generator = new WP_Tester_AI_Flow_Generator();
            $model_config = $ai_generator->get_model_config($model);
            
            if (!$model_config) {
                wp_send_json_error('Invalid AI model configured. Please check your AI Flow Generator settings.');
            }
            
            // For models that require API key, check if API key is provided
            if ($model_config['requires_api_key'] && empty($api_key)) {
                wp_send_json_error('API key is required for the configured model. Please add your API key in the AI Flow Generator settings.');
            }
            
            // Prepare the AI prompt
            $system_prompt = "You are a WordPress testing assistant. Your primary goal is to help users create test flows for their WordPress sites.

BEHAVIOR RULES:
1. For greetings (hi, hello, etc.) - respond with a friendly greeting and ask what they'd like to test
2. For general questions - answer normally without generating flows
3. ONLY generate JSON flows when the user explicitly asks for a test flow or describes specific functionality they want to test

When a user explicitly requests a test flow, analyze their request and provide a structured test flow in JSON format.

IMPORTANT: When you generate a test flow, ALWAYS wrap it in ```json``` code blocks. The JSON should have this structure:
{
  \"name\": \"Flow Name\",
  \"description\": \"Brief description of what this flow tests\",
  \"steps\": [
    {
      \"action\": \"navigate\",
      \"target\": \"URL or selector\",
      \"value\": \"optional value\",
      \"description\": \"What this step does\"
    }
  ]
}

Available actions: navigate, click, fill, select, wait, assert, scroll, hover, double_click, right_click, drag_drop, upload_file, take_screenshot, custom_script

DO NOT generate flows for simple greetings or general conversation. Only generate flows when explicitly requested.";
            
            $messages = array(
                array('role' => 'system', 'content' => $system_prompt)
            );
            
            // Add chat history
            foreach ($chat_history as $msg) {
                $messages[] = array(
                    'role' => $msg['type'] === 'user' ? 'user' : 'assistant',
                    'content' => $msg['content']
                );
            }
            
            // Add current message
            $messages[] = array('role' => 'user', 'content' => $message);
            
            // Call AI API
            $response = $this->call_ai_api($model, $api_key, $messages, $temperature, $max_tokens);
            
            if ($response['success']) {
                $ai_response = $response['message'];
                
                // Check if AI wants to create a flow
                $create_flow = false;
                $flow_data = null;
                
                // Only create flows if the user's message contains explicit flow request keywords
                $flow_request_keywords = [
                    'create', 'generate', 'make', 'build', 'test flow', 'flow for', 'testing', 'test the', 'flow to test'
                ];
                
                $user_message_lower = strtolower($message ?: '');
                $has_flow_request = false;
                
                foreach ($flow_request_keywords as $keyword) {
                    if (strpos($user_message_lower, $keyword) !== false) {
                        $has_flow_request = true;
                        break;
                    }
                }
                
                // Only proceed with flow creation if user explicitly requested it
                if ($has_flow_request) {
                    // Try multiple JSON extraction patterns
                    $json_patterns = [
                        '/```json\s*(\{.*?\})\s*```/s',  // ```json { ... } ```
                        '/```\s*(\{.*?\})\s*```/s',      // ``` { ... } ```
                    ];
                    
                    foreach ($json_patterns as $pattern) {
                        if (preg_match($pattern, $ai_response, $matches)) {
                            $json_string = $matches[1];
                            $flow_json = json_decode($json_string, true);
                            
                            if ($flow_json && isset($flow_json['name']) && isset($flow_json['steps'])) {
                                $create_flow = true;
                                $flow_data = $flow_json;
                                break;
                            }
                        }
                    }
                }
                
                wp_send_json_success(array(
                    'message' => $ai_response,
                    'create_flow' => $create_flow,
                    'flow_data' => $flow_data
                ));
            } else {
                wp_send_json_error($response['error']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Call AI API
     */
    public function call_ai_api($model, $api_key, $messages, $temperature, $max_tokens) {
        // Get model configuration
        $ai_generator = new WP_Tester_AI_Flow_Generator();
        $model_config = $ai_generator->get_model_config($model);
        
        if (!$model_config) {
            return array('success' => false, 'error' => 'Invalid model configuration');
        }
        
        // Use the model's configured API URL
        $url = $model_config['api_url'];
        
        if (empty($url)) {
            return array('success' => false, 'error' => 'No API URL configured for this model');
        }
        
        // Prepare data based on provider
        $data = array();
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        // For Mistral AI, try to use a more standard model name if mistral-7b fails
        $actual_model = $model;
        if ($model_config['provider'] === 'Mistral AI' && $model === 'mistral-7b') {
            $actual_model = 'mistral-small'; // Try with a more standard name
        }
        
        switch ($model_config['provider']) {
            case 'OpenAI':
                $data = array(
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            case 'Google':
                $data = array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array('text' => $messages[count($messages) - 1]['content'])
                            )
                        )
                    ),
                    'generationConfig' => array(
                        'temperature' => $temperature
                    )
                );
                if ($max_tokens !== null) {
                    $data['generationConfig']['maxOutputTokens'] = $max_tokens;
                }
                $headers['x-goog-api-key'] = $api_key;
                break;
                
            case 'Anthropic':
                $data = array(
                    'model' => $model,
                    'temperature' => $temperature,
                    'messages' => $messages
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['x-api-key'] = $api_key;
                $headers['anthropic-version'] = '2023-06-01';
                break;
                
            case 'X.AI':
                $data = array(
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            case 'DeepSeek':
                $data = array(
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            case 'Hugging Face':
                $data = array(
                    'inputs' => $messages[count($messages) - 1]['content'],
                    'parameters' => array(
                        'temperature' => $temperature
                    )
                );
                if ($max_tokens !== null) {
                    $data['parameters']['max_new_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            case 'Mistral AI':
                $data = array(
                    'model' => $actual_model,
                    'messages' => $messages,
                    'temperature' => $temperature
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            case 'Cohere':
                $data = array(
                    'model' => $model,
                    'message' => $messages[count($messages) - 1]['content'],
                    'temperature' => $temperature
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            case 'Perplexity':
                $data = array(
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature
                );
                if ($max_tokens !== null) {
                    $data['max_tokens'] = $max_tokens;
                }
                $headers['Authorization'] = 'Bearer ' . $api_key;
                break;
                
            default:
                return array('success' => false, 'error' => 'Unsupported API provider: ' . $model_config['provider']);
        }
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);
        
        // Debug logging for API responses
        error_log('WP Tester AI API Debug - Original Model: ' . $model . ', Actual Model: ' . $actual_model . ', Provider: ' . $model_config['provider']);
        error_log('WP Tester AI API Debug - Request Data: ' . json_encode($data));
        error_log('WP Tester AI API Debug - Response Body: ' . $body);
        error_log('WP Tester AI API Debug - Response Data: ' . print_r($response_data, true));
        
        if (isset($response_data['error'])) {
            return array('success' => false, 'error' => $response_data['error']['message']);
        }
        
        // Parse response based on provider
        $content = '';
        switch ($model_config['provider']) {
            case 'OpenAI':
            case 'X.AI':
            case 'DeepSeek':
            case 'Mistral AI':
            case 'Perplexity':
                if (isset($response_data['choices'][0]['message']['content'])) {
                    $content = $response_data['choices'][0]['message']['content'];
                }
                break;
                
            case 'Google':
                if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                    $content = $response_data['candidates'][0]['content']['parts'][0]['text'];
                }
                break;
                
            case 'Anthropic':
                if (isset($response_data['content'][0]['text'])) {
                    $content = $response_data['content'][0]['text'];
                }
                break;
                
            case 'Hugging Face':
                if (isset($response_data[0]['generated_text'])) {
                    $content = $response_data[0]['generated_text'];
                }
                break;
                
            case 'Cohere':
                if (isset($response_data['text'])) {
                    $content = $response_data['text'];
                }
                break;
        }
        
        if (!empty($content)) {
            return array('success' => true, 'message' => $content);
        }
        
        // If no content found, log the response structure for debugging
        error_log('WP Tester AI API Debug - No content found in response. Response structure: ' . print_r($response_data, true));
        
        return array('success' => false, 'error' => 'Invalid response from AI API. Response structure: ' . json_encode($response_data));
    }
    
    /**
     * Save conversation
     */
    public function save_conversation() {
        try {
            $chat_history = $_POST['chat_history'] ?? array();
            
            // Save to WordPress options
            $conversations = get_option('wp_tester_ai_conversations', array());
            $conversations[] = array(
                'timestamp' => current_time('mysql'),
                'chat_history' => $chat_history
            );
            
            // Keep only last 10 conversations
            if (is_array($conversations) && count($conversations) > 10) {
                $conversations = array_slice($conversations, -10);
            }
            
            update_option('wp_tester_ai_conversations', $conversations);
            
            wp_send_json_success('Conversation saved successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Error saving conversation: ' . $e->getMessage());
        }
    }
    
    /**
     * Create AI flow
     */
    public function create_ai_flow() {
        // Debug logging
        error_log('WP Tester: create_ai_flow method called');
        error_log('WP Tester: POST data: ' . print_r($_POST, true));
        
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            error_log('WP Tester: User does not have manage_options permission');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        error_log('WP Tester: Starting flow creation process');
        
        try {
            $flow_name = sanitize_text_field($_POST['flow_name'] ?? '');
            $flow_description = sanitize_text_field($_POST['flow_description'] ?? '');
            $flow_data = $_POST['flow_data'] ?? array();
            
            if (empty($flow_name)) {
                wp_send_json_error('Flow name is required');
            }
            
            // Create flow in database
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            $result = $wpdb->insert(
                $flows_table,
                array(
                    'flow_name' => $flow_name,
                    'flow_description' => $flow_description,
                    'flow_type' => 'ai_generated',
                    'steps' => json_encode($flow_data['steps'] ?? array()),
                    'is_active' => 1,
                    'priority' => 5,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
            );
            
            if ($result) {
                $flow_id = $wpdb->insert_id;
                
                // Mark as AI generated (using options instead of post meta)
                update_option('wp_tester_ai_flow_' . $flow_id . '_generated', true);
                update_option('wp_tester_ai_flow_' . $flow_id . '_model', sanitize_text_field($_POST['model'] ?? ''));
                
                wp_send_json_success(array(
                    'flow_id' => $flow_id,
                    'message' => 'Flow created successfully'
                ));
            } else {
                error_log('WP Tester: Failed to create flow. Database error: ' . $wpdb->last_error);
                wp_send_json_error('Failed to create flow. Database error: ' . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error creating flow: ' . $e->getMessage());
        }
    }
    
    /**
     * Get AI flows
     */
    public function get_ai_flows() {
        try {
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            $flows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$flows_table} WHERE flow_type = 'ai_generated' ORDER BY created_at DESC LIMIT 5"
            ));
            
            $formatted_flows = array();
            foreach ($flows as $flow) {
                $formatted_flows[] = array(
                    'id' => $flow->id,
                    'name' => $flow->flow_name,
                    'description' => $flow->flow_description,
                    'created_at' => $flow->created_at,
                    'edit_url' => admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . $flow->id),
                    'test_url' => admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id)
                );
            }
            
            wp_send_json_success($formatted_flows);
            
        } catch (Exception $e) {
            wp_send_json_error('Error loading AI flows: ' . $e->getMessage());
        }
    }
    
    /**
     * Load more test results
     */
    public function load_more_results() {
        try {
            $offset = intval($_POST['offset'] ?? 0);
            $limit = intval($_POST['limit'] ?? 10);
            
            global $wpdb;
            $results_table = $wpdb->prefix . 'wp_tester_test_results';
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            // Get more results with flow information
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT tr.*, f.flow_name 
                 FROM {$results_table} tr 
                 LEFT JOIN {$flows_table} f ON tr.flow_id = f.id 
                 ORDER BY tr.started_at DESC 
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            ));
            
            // Check if there are more results
            $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$results_table}");
            $has_more = ($offset + $limit) < $total_count;
            
            // Format results for frontend
            $formatted_results = array();
            foreach ($results as $result) {
                $formatted_results[] = array(
                    'id' => $result->id,
                    'flow_id' => $result->flow_id,
                    'flow_name' => $result->flow_name ?: 'Unknown Flow',
                    'status' => $result->status,
                    'steps_executed' => $result->steps_executed,
                    'steps_total' => $result->steps_executed, // Assuming all steps were attempted
                    'execution_time' => $result->execution_time,
                    'time_ago' => human_time_diff(strtotime($result->started_at), current_time('timestamp')) . ' ago',
                    'view_url' => admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . $result->id)
                );
            }
            
            wp_send_json_success(array(
                'results' => $formatted_results,
                'has_more' => $has_more,
                'total_count' => $total_count,
                'loaded_count' => count($formatted_results)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error loading more results: ' . $e->getMessage());
        }
    }
}