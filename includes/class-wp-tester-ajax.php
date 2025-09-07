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
            
            $executor = new WP_Tester_Flow_Executor();
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
                
                $result = $executor->execute_flow($flow->id, true);
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
            $executor = new WP_Tester_Flow_Executor();
            $result = $executor->execute_flow($flow_id, true);
            
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
                    $executor = new WP_Tester_Flow_Executor();
                    $results = $executor->execute_multiple_flows($flow_ids);
                    
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
            $removed_count = $database->force_cleanup_duplicates();
            
            wp_send_json_success(array(
                'message' => sprintf(__('Cleaned up %d duplicate flows.', 'wp-tester'), $removed_count),
                'removed_count' => $removed_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to cleanup duplicates: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
}