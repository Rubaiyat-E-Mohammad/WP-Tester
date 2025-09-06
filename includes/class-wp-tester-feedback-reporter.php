<?php
/**
 * WP Tester Feedback Reporter Class
 * 
 * Generates comprehensive feedback reports with visual evidence and suggestions
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Feedback_Reporter {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
    }
    
    /**
     * Generate comprehensive report for a test result
     */
    public function generate_report($test_result_id) {
        $test_result = $this->database->get_test_result($test_result_id);
        if (!$test_result) {
            return array('error' => 'Test result not found');
        }
        
        $flow = $this->database->get_flow($test_result->flow_id);
        $screenshots = $this->database->get_screenshots($test_result_id);
        $execution_log = json_decode($test_result->detailed_log, true) ?: array();
        $suggestions = json_decode($test_result->suggestions, true) ?: array();
        
        return array(
            'test_result' => $test_result,
            'flow' => $flow,
            'execution_summary' => $this->generate_execution_summary($test_result),
            'step_details' => $this->generate_step_details($execution_log, $screenshots),
            'failure_analysis' => $this->generate_failure_analysis($test_result, $execution_log),
            'visual_evidence' => $this->prepare_visual_evidence($screenshots),
            'suggestions' => $this->format_suggestions($suggestions),
            'performance_metrics' => $this->calculate_performance_metrics($test_result),
            'historical_comparison' => $this->get_historical_comparison($test_result->flow_id),
            'report_generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Generate execution summary
     */
    private function generate_execution_summary($test_result) {
        $success_rate = $test_result->steps_executed > 0 
            ? round(($test_result->steps_passed / $test_result->steps_executed) * 100, 1) 
            : 0;
        
        return array(
            'overall_status' => $test_result->status,
            'success_rate' => $success_rate,
            'total_steps' => $test_result->steps_executed,
            'passed_steps' => $test_result->steps_passed,
            'failed_steps' => $test_result->steps_failed,
            'execution_time' => $test_result->execution_time,
            'test_run_id' => $test_result->test_run_id,
            'started_at' => $test_result->started_at,
            'completed_at' => $test_result->completed_at,
            'status_label' => $this->get_status_label($test_result->status),
            'status_color' => $this->get_status_color($test_result->status)
        );
    }
    
    /**
     * Generate detailed step information
     */
    private function generate_step_details($execution_log, $screenshots) {
        $step_details = array();
        $current_step = 0;
        
        foreach ($execution_log as $log_entry) {
            if ($log_entry['level'] === 'info' && strpos($log_entry['message'], 'Executing step') !== false) {
                $current_step++;
                $step_details[$current_step] = array(
                    'step_number' => $current_step,
                    'action' => isset($log_entry['data']['action']) ? $log_entry['data']['action'] : 'unknown',
                    'target' => isset($log_entry['data']['target']) ? $log_entry['data']['target'] : 'unknown',
                    'status' => 'pending',
                    'timestamp' => $log_entry['timestamp'],
                    'details' => array(),
                    'screenshot' => null
                );
            } elseif (isset($step_details[$current_step])) {
                // Add details to current step
                $step_details[$current_step]['details'][] = $log_entry;
                
                if ($log_entry['level'] === 'success') {
                    $step_details[$current_step]['status'] = 'passed';
                } elseif ($log_entry['level'] === 'error') {
                    $step_details[$current_step]['status'] = 'failed';
                    $step_details[$current_step]['error'] = $log_entry['message'];
                }
            }
        }
        
        // Add screenshots to relevant steps
        foreach ($screenshots as $screenshot) {
            if (isset($step_details[$screenshot->step_number])) {
                $step_details[$screenshot->step_number]['screenshot'] = $screenshot;
            }
        }
        
        return array_values($step_details);
    }
    
    /**
     * Generate failure analysis
     */
    private function generate_failure_analysis($test_result, $execution_log) {
        if ($test_result->status === 'passed') {
            return null;
        }
        
        $failures = array();
        $error_patterns = array();
        
        foreach ($execution_log as $log_entry) {
            if ($log_entry['level'] === 'error') {
                $failures[] = array(
                    'timestamp' => $log_entry['timestamp'],
                    'message' => $log_entry['message'],
                    'data' => $log_entry['data']
                );
                
                // Categorize error patterns
                $error_type = $this->categorize_error($log_entry['message']);
                if (!isset($error_patterns[$error_type])) {
                    $error_patterns[$error_type] = 0;
                }
                $error_patterns[$error_type]++;
            }
        }
        
        return array(
            'primary_error' => $test_result->error_message,
            'failure_count' => count($failures),
            'failure_details' => $failures,
            'error_patterns' => $error_patterns,
            'root_cause_analysis' => $this->analyze_root_cause($failures, $error_patterns),
            'impact_assessment' => $this->assess_impact($test_result, $failures)
        );
    }
    
    /**
     * Prepare visual evidence
     */
    private function prepare_visual_evidence($screenshots) {
        $visual_evidence = array();
        
        foreach ($screenshots as $screenshot) {
            $upload_dir = wp_upload_dir();
            $screenshot_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $screenshot->screenshot_path);
            
            $visual_evidence[] = array(
                'step_number' => $screenshot->step_number,
                'type' => $screenshot->screenshot_type,
                'caption' => $screenshot->caption,
                'url' => $screenshot_url,
                'path' => $screenshot->screenshot_path,
                'created_at' => $screenshot->created_at,
                'file_exists' => file_exists($screenshot->screenshot_path)
            );
        }
        
        return $visual_evidence;
    }
    
    /**
     * Format suggestions for display
     */
    private function format_suggestions($suggestions) {
        $formatted = array();
        
        foreach ($suggestions as $suggestion) {
            $formatted[] = array(
                'type' => $suggestion['type'],
                'title' => $suggestion['title'],
                'description' => $suggestion['description'],
                'priority' => $suggestion['priority'],
                'action' => $suggestion['action'],
                'priority_label' => $this->get_priority_label($suggestion['priority']),
                'priority_color' => $this->get_priority_color($suggestion['priority']),
                'icon' => $this->get_suggestion_icon($suggestion['type'])
            );
        }
        
        // Sort by priority
        usort($formatted, function($a, $b) {
            $priority_order = array('critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1);
            return $priority_order[$b['priority']] - $priority_order[$a['priority']];
        });
        
        return $formatted;
    }
    
    /**
     * Calculate performance metrics
     */
    private function calculate_performance_metrics($test_result) {
        $avg_step_time = $test_result->steps_executed > 0 
            ? $test_result->execution_time / $test_result->steps_executed 
            : 0;
        
        return array(
            'total_execution_time' => $test_result->execution_time,
            'average_step_time' => round($avg_step_time, 2),
            'steps_per_second' => $test_result->execution_time > 0 
                ? round($test_result->steps_executed / $test_result->execution_time, 2) 
                : 0,
            'performance_rating' => $this->calculate_performance_rating($test_result->execution_time, $test_result->steps_executed),
            'efficiency_score' => $this->calculate_efficiency_score($test_result)
        );
    }
    
    /**
     * Get historical comparison
     */
    private function get_historical_comparison($flow_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_tester_test_results';
        
        // Get last 10 test results for this flow
        $historical_results = $wpdb->get_results($wpdb->prepare(
            "SELECT status, steps_passed, steps_failed, execution_time, started_at 
             FROM {$table_name} 
             WHERE flow_id = %d 
             ORDER BY started_at DESC 
             LIMIT 10",
            $flow_id
        ));
        
        if (empty($historical_results)) {
            return null;
        }
        
        $success_count = 0;
        $total_execution_time = 0;
        $trend_data = array();
        
        foreach ($historical_results as $result) {
            if ($result->status === 'passed') {
                $success_count++;
            }
            $total_execution_time += $result->execution_time;
            
            $trend_data[] = array(
                'date' => $result->started_at,
                'status' => $result->status,
                'success_rate' => ($result->steps_passed + $result->steps_failed) > 0 
                    ? round(($result->steps_passed / ($result->steps_passed + $result->steps_failed)) * 100, 1) 
                    : 0,
                'execution_time' => $result->execution_time
            );
        }
        
        $historical_success_rate = round(($success_count / count($historical_results)) * 100, 1);
        $average_execution_time = round($total_execution_time / count($historical_results), 2);
        
        return array(
            'total_tests' => count($historical_results),
            'historical_success_rate' => $historical_success_rate,
            'average_execution_time' => $average_execution_time,
            'trend_data' => $trend_data,
            'trend_analysis' => $this->analyze_trend($trend_data)
        );
    }
    
    /**
     * Generate dashboard summary report
     */
    public function generate_dashboard_summary() {
        $stats = $this->database->get_dashboard_stats();
        
        // Get recent test results
        $recent_results = $this->database->get_test_results(null, 10, 0);
        
        // Get critical issues
        $critical_issues = $this->get_critical_issues();
        
        // Get flow health summary
        $flow_health = $this->get_flow_health_summary();
        
        return array(
            'statistics' => $stats,
            'recent_results' => $this->format_recent_results($recent_results),
            'critical_issues' => $critical_issues,
            'flow_health' => $flow_health,
            'recommendations' => $this->generate_dashboard_recommendations($stats, $critical_issues)
        );
    }
    
    /**
     * Get critical issues
     */
    private function get_critical_issues() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wp_tester_test_results';
        
        $critical_issues = $wpdb->get_results(
            "SELECT tr.*, f.flow_name, f.flow_type 
             FROM {$table_name} tr 
             LEFT JOIN {$wpdb->prefix}wp_tester_flows f ON tr.flow_id = f.id 
             WHERE tr.status = 'failed' 
             AND tr.started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             ORDER BY tr.started_at DESC 
             LIMIT 5"
        );
        
        $formatted_issues = array();
        foreach ($critical_issues as $issue) {
            $suggestions = json_decode($issue->suggestions, true) ?: array();
            $critical_suggestions = array_filter($suggestions, function($s) {
                return $s['priority'] === 'critical';
            });
            
            $formatted_issues[] = array(
                'id' => $issue->id,
                'flow_name' => $issue->flow_name,
                'flow_type' => $issue->flow_type,
                'error_message' => $issue->error_message,
                'failed_at' => $issue->started_at,
                'critical_suggestions' => array_values($critical_suggestions),
                'severity' => $this->calculate_issue_severity($issue)
            );
        }
        
        return $formatted_issues;
    }
    
    /**
     * Get flow health summary
     */
    private function get_flow_health_summary() {
        global $wpdb;
        
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        $results_table = $wpdb->prefix . 'wp_tester_test_results';
        
        $flow_health = $wpdb->get_results(
            "SELECT f.id, f.flow_name, f.flow_type, f.priority,
                    COUNT(tr.id) as total_tests,
                    SUM(CASE WHEN tr.status = 'passed' THEN 1 ELSE 0 END) as passed_tests,
                    AVG(tr.execution_time) as avg_execution_time,
                    MAX(tr.started_at) as last_test_date
             FROM {$flows_table} f
             LEFT JOIN {$results_table} tr ON f.id = tr.flow_id 
                 AND tr.started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             WHERE f.is_active = 1
             GROUP BY f.id
             ORDER BY f.priority DESC"
        );
        
        $health_summary = array();
        foreach ($flow_health as $flow) {
            $success_rate = $flow->total_tests > 0 
                ? round(($flow->passed_tests / $flow->total_tests) * 100, 1) 
                : 0;
            
            $health_status = $this->determine_flow_health($success_rate, $flow->total_tests);
            
            $health_summary[] = array(
                'flow_id' => $flow->id,
                'flow_name' => $flow->flow_name,
                'flow_type' => $flow->flow_type,
                'priority' => $flow->priority,
                'success_rate' => $success_rate,
                'total_tests' => $flow->total_tests,
                'avg_execution_time' => round($flow->avg_execution_time, 2),
                'last_test_date' => $flow->last_test_date,
                'health_status' => $health_status,
                'health_color' => $this->get_health_color($health_status)
            );
        }
        
        return $health_summary;
    }
    
    /**
     * Generate export report
     */
    public function generate_export_report($flow_id = null, $date_from = null, $date_to = null, $format = 'json') {
        $filters = array();
        if ($date_from) $filters['date_from'] = $date_from;
        if ($date_to) $filters['date_to'] = $date_to;
        
        if ($flow_id) {
            $test_results = $this->database->get_test_results($flow_id, 1000, 0);
        } else {
            $test_results = $this->database->get_test_results(null, 1000, 0);
        }
        
        $export_data = array();
        foreach ($test_results as $result) {
            $detailed_report = $this->generate_report($result->id);
            $export_data[] = array(
                'test_id' => $result->id,
                'flow_name' => $result->flow_name,
                'flow_type' => $result->flow_type,
                'status' => $result->status,
                'execution_time' => $result->execution_time,
                'steps_executed' => $result->steps_executed,
                'steps_passed' => $result->steps_passed,
                'steps_failed' => $result->steps_failed,
                'started_at' => $result->started_at,
                'completed_at' => $result->completed_at,
                'error_message' => $result->error_message,
                'suggestions_count' => count($detailed_report['suggestions']),
                'has_screenshots' => !empty($detailed_report['visual_evidence'])
            );
        }
        
        switch ($format) {
            case 'csv':
                return $this->export_to_csv($export_data);
            case 'pdf':
                return $this->export_to_pdf($export_data);
            default:
                return $export_data;
        }
    }
    
    /**
     * Helper methods for formatting and analysis
     */
    
    private function get_status_label($status) {
        $labels = array(
            'passed' => 'Passed',
            'failed' => 'Failed',
            'partial' => 'Partially Passed'
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }
    
    private function get_status_color($status) {
        $colors = array(
            'passed' => '#28a745',
            'failed' => '#dc3545',
            'partial' => '#ffc107'
        );
        return isset($colors[$status]) ? $colors[$status] : '#6c757d';
    }
    
    private function get_priority_label($priority) {
        return ucfirst($priority);
    }
    
    private function get_priority_color($priority) {
        $colors = array(
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#28a745'
        );
        return isset($colors[$priority]) ? $colors[$priority] : '#6c757d';
    }
    
    private function get_suggestion_icon($type) {
        $icons = array(
            'timeout' => 'clock',
            'element_missing' => 'search',
            'http_error' => 'wifi-off',
            'flow_health' => 'activity',
            'registration_specific' => 'user-plus',
            'woocommerce_specific' => 'shopping-cart'
        );
        return isset($icons[$type]) ? $icons[$type] : 'alert-circle';
    }
    
    private function categorize_error($error_message) {
        $error = strtolower($error_message);
        
        if (strpos($error, 'timeout') !== false) return 'timeout';
        if (strpos($error, 'element not found') !== false) return 'element_missing';
        if (strpos($error, 'http error') !== false) return 'http_error';
        if (strpos($error, 'network') !== false) return 'network_error';
        if (strpos($error, 'validation') !== false) return 'validation_error';
        
        return 'general_error';
    }
    
    private function analyze_root_cause($failures, $error_patterns) {
        if (empty($failures)) return null;
        
        $most_common_error = array_search(max($error_patterns), $error_patterns);
        
        $analysis = array(
            'primary_error_type' => $most_common_error,
            'error_frequency' => $error_patterns,
            'likely_causes' => $this->get_likely_causes($most_common_error),
            'recommended_actions' => $this->get_recommended_actions($most_common_error)
        );
        
        return $analysis;
    }
    
    private function get_likely_causes($error_type) {
        $causes = array(
            'timeout' => array('Slow page loading', 'Heavy resource usage', 'Network issues'),
            'element_missing' => array('Page structure changed', 'Element selector outdated', 'Dynamic content loading'),
            'http_error' => array('Server issues', 'URL changed', 'Permission problems'),
            'network_error' => array('Connectivity issues', 'DNS problems', 'Firewall blocking')
        );
        
        return isset($causes[$error_type]) ? $causes[$error_type] : array('Unknown cause');
    }
    
    private function get_recommended_actions($error_type) {
        $actions = array(
            'timeout' => array('Increase timeout settings', 'Optimize page performance', 'Check server resources'),
            'element_missing' => array('Update element selectors', 'Check page structure', 'Wait for dynamic content'),
            'http_error' => array('Verify URL accessibility', 'Check server status', 'Review permissions'),
            'network_error' => array('Check network connectivity', 'Verify DNS settings', 'Review firewall rules')
        );
        
        return isset($actions[$error_type]) ? $actions[$error_type] : array('Review configuration');
    }
    
    private function assess_impact($test_result, $failures) {
        $impact_level = 'low';
        
        if ($test_result->status === 'failed') {
            $impact_level = 'high';
        } elseif ($test_result->status === 'partial' && count($failures) > 1) {
            $impact_level = 'medium';
        }
        
        return array(
            'level' => $impact_level,
            'description' => $this->get_impact_description($impact_level, $test_result),
            'affected_functionality' => $this->get_affected_functionality($test_result->flow_type)
        );
    }
    
    private function get_impact_description($level, $test_result) {
        switch ($level) {
            case 'high':
                return "Critical functionality is broken. Users cannot complete the {$test_result->flow_type} flow.";
            case 'medium':
                return "Some functionality is impaired. Users may experience issues with the {$test_result->flow_type} flow.";
            default:
                return "Minor issues detected. Overall functionality is working correctly.";
        }
    }
    
    private function get_affected_functionality($flow_type) {
        $functionality = array(
            'registration' => array('User registration', 'Account creation', 'User onboarding'),
            'login' => array('User authentication', 'Access to protected content', 'User sessions'),
            'woocommerce' => array('Product purchases', 'Checkout process', 'Payment processing'),
            'contact' => array('Contact form submissions', 'Customer inquiries', 'Lead generation'),
            'search' => array('Site search', 'Content discovery', 'User navigation')
        );
        
        return isset($functionality[$flow_type]) ? $functionality[$flow_type] : array('General site functionality');
    }
    
    private function calculate_performance_rating($execution_time, $steps_executed) {
        if ($steps_executed === 0) return 'N/A';
        
        $avg_step_time = $execution_time / $steps_executed;
        
        if ($avg_step_time < 1) return 'Excellent';
        if ($avg_step_time < 2) return 'Good';
        if ($avg_step_time < 5) return 'Average';
        return 'Poor';
    }
    
    private function calculate_efficiency_score($test_result) {
        if ($test_result->steps_executed === 0) return 0;
        
        $success_ratio = $test_result->steps_passed / $test_result->steps_executed;
        $time_penalty = min($test_result->execution_time / 60, 1); // Penalty for tests over 1 minute
        
        return round((($success_ratio * 100) * (1 - $time_penalty * 0.2)), 1);
    }
    
    private function analyze_trend($trend_data) {
        if (count($trend_data) < 2) return 'Insufficient data';
        
        $recent_success_rates = array_slice(array_column($trend_data, 'success_rate'), 0, 5);
        $avg_recent = array_sum($recent_success_rates) / count($recent_success_rates);
        
        if ($avg_recent > 80) return 'Stable';
        if ($avg_recent > 60) return 'Declining';
        return 'Critical';
    }
    
    private function format_recent_results($results) {
        $formatted = array();
        
        foreach ($results as $result) {
            $formatted[] = array(
                'id' => $result->id,
                'flow_name' => $result->flow_name,
                'flow_type' => $result->flow_type,
                'status' => $result->status,
                'status_label' => $this->get_status_label($result->status),
                'status_color' => $this->get_status_color($result->status),
                'execution_time' => $result->execution_time,
                'started_at' => $result->started_at,
                'success_rate' => $result->steps_executed > 0 
                    ? round(($result->steps_passed / $result->steps_executed) * 100, 1) 
                    : 0
            );
        }
        
        return $formatted;
    }
    
    private function calculate_issue_severity($issue) {
        $severity_score = 0;
        
        // Base severity on failure frequency
        if ($issue->steps_failed > $issue->steps_passed) $severity_score += 3;
        elseif ($issue->steps_failed > 0) $severity_score += 2;
        
        // Consider execution time
        if ($issue->execution_time > 30) $severity_score += 1;
        
        // Consider error type
        if (strpos(strtolower($issue->error_message), 'critical') !== false) $severity_score += 2;
        if (strpos(strtolower($issue->error_message), 'timeout') !== false) $severity_score += 1;
        
        if ($severity_score >= 4) return 'Critical';
        if ($severity_score >= 2) return 'High';
        return 'Medium';
    }
    
    private function determine_flow_health($success_rate, $total_tests) {
        if ($total_tests === 0) return 'Unknown';
        if ($success_rate >= 90) return 'Excellent';
        if ($success_rate >= 75) return 'Good';
        if ($success_rate >= 50) return 'Fair';
        return 'Poor';
    }
    
    private function get_health_color($health_status) {
        $colors = array(
            'Excellent' => '#28a745',
            'Good' => '#17a2b8',
            'Fair' => '#ffc107',
            'Poor' => '#dc3545',
            'Unknown' => '#6c757d'
        );
        return isset($colors[$health_status]) ? $colors[$health_status] : '#6c757d';
    }
    
    private function generate_dashboard_recommendations($stats, $critical_issues) {
        $recommendations = array();
        
        if ($stats['success_rate'] < 80) {
            $recommendations[] = array(
                'title' => 'Improve Overall Success Rate',
                'description' => 'Your current success rate is ' . $stats['success_rate'] . '%. Focus on fixing the most common failure patterns.',
                'priority' => 'high',
                'action' => 'Review and fix failing flows'
            );
        }
        
        if ($stats['critical_issues'] > 0) {
            $recommendations[] = array(
                'title' => 'Address Critical Issues',
                'description' => 'You have ' . $stats['critical_issues'] . ' critical issues that need immediate attention.',
                'priority' => 'critical',
                'action' => 'Fix critical failures immediately'
            );
        }
        
        if ($stats['recent_tests'] < 5) {
            $recommendations[] = array(
                'title' => 'Increase Testing Frequency',
                'description' => 'Only ' . $stats['recent_tests'] . ' tests were run in the last 24 hours. Consider increasing testing frequency.',
                'priority' => 'medium',
                'action' => 'Adjust testing schedule'
            );
        }
        
        return $recommendations;
    }
    
    private function export_to_csv($data) {
        if (empty($data)) return '';
        
        $output = fopen('php://temp', 'w');
        
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    private function export_to_pdf($data) {
        // This would require a PDF library like TCPDF or DOMPDF
        // For now, return a placeholder
        return 'PDF export functionality would be implemented here';
    }
}