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
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        try {
            $executor = new WP_Tester_Flow_Executor();
            $database = new WP_Tester_Database();
            
            $flows = $database->get_flows(true); // Get active flows only
            $results = array();
            
            foreach ($flows as $flow) {
                $result = $executor->execute_flow($flow->id, true);
                $results[] = array(
                    'flow_id' => $flow->id,
                    'flow_name' => $flow->flow_name,
                    'result' => $result
                );
                
                // Small delay between flows
                usleep(100000); // 0.1 second
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Executed %d flows successfully.', 'wp-tester'), count($flows)),
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
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        try {
            $crawler = new WP_Tester_Crawler();
            $result = $crawler->run_full_crawl();
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Crawl completed. Discovered %d URLs and %d flows in %.2f seconds.', 'wp-tester'),
                        $result['crawled_count'],
                        $result['discovered_flows'],
                        $result['execution_time']
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
            wp_send_json_error(array(
                'message' => __('Failed to run crawl: ', 'wp-tester') . $e->getMessage()
            ));
        }
    }
    
    /**
     * Test single flow
     */
    public function test_flow() {
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        $flow_id = intval($_POST['flow_id']);
        if (!$flow_id) {
            wp_send_json_error(array(
                'message' => __('Invalid flow ID.', 'wp-tester')
            ));
        }
        
        try {
            $executor = new WP_Tester_Flow_Executor();
            $result = $executor->execute_flow($flow_id, true);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Flow test completed with status: %s', 'wp-tester'),
                        $result['status']
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
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        $flow_id = intval($_POST['flow_id']);
        if (!$flow_id) {
            wp_send_json_error(array(
                'message' => __('Invalid flow ID.', 'wp-tester')
            ));
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
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        try {
            $crawler = new WP_Tester_Crawler();
            $result = $crawler->run_full_crawl();
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Flow discovery completed. Found %d new flows.', 'wp-tester'),
                        $result['discovered_flows']
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
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $flow_ids = array_map('intval', $_POST['flow_ids']);
        
        if (empty($action) || empty($flow_ids)) {
            wp_send_json_error(array(
                'message' => __('Invalid action or no flows selected.', 'wp-tester')
            ));
        }
        
        try {
            global $wpdb;
            $flows_table = $wpdb->prefix . 'wp_tester_flows';
            
            switch ($action) {
                case 'test':
                    $executor = new WP_Tester_Flow_Executor();
                    $results = $executor->execute_multiple_flows($flow_ids);
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Tested %d flows.', 'wp-tester'), count($flow_ids)),
                        'results' => $results
                    ));
                    break;
                    
                case 'activate':
                    $wpdb->update(
                        $flows_table,
                        array('is_active' => 1),
                        array('id' => $flow_ids),
                        array('%d'),
                        array('%d')
                    );
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Activated %d flows.', 'wp-tester'), count($flow_ids))
                    ));
                    break;
                    
                case 'deactivate':
                    $wpdb->update(
                        $flows_table,
                        array('is_active' => 0),
                        array('id' => $flow_ids),
                        array('%d'),
                        array('%d')
                    );
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Deactivated %d flows.', 'wp-tester'), count($flow_ids))
                    ));
                    break;
                    
                case 'delete':
                    foreach ($flow_ids as $flow_id) {
                        $wpdb->delete($flows_table, array('id' => $flow_id), array('%d'));
                    }
                    
                    wp_send_json_success(array(
                        'message' => sprintf(__('Deleted %d flows.', 'wp-tester'), count($flow_ids))
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
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        $test_run_id = sanitize_text_field($_POST['test_run_id']);
        if (!$test_run_id) {
            wp_send_json_error(array(
                'message' => __('Invalid test run ID.', 'wp-tester')
            ));
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
        check_ajax_referer('wp_tester_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-tester'));
        }
        
        try {
            $format = sanitize_text_field($_POST['format']) ?: 'json';
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
}