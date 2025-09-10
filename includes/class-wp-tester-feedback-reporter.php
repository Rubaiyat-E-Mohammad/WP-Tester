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
        $step_start_time = null;
        
        foreach ($execution_log as $log_entry) {
            if ($log_entry['level'] === 'info' && strpos($log_entry['message'], 'Executing step') !== false) {
                $current_step++;
                $step_start_time = strtotime($log_entry['timestamp']);
                
                // Extract step information from log data
                $action = 'unknown';
                $target = 'unknown';
                $value = null;
                $selector = null;
                
                if (isset($log_entry['data'])) {
                    $action = isset($log_entry['data']['action']) ? $log_entry['data']['action'] : 'unknown';
                    $target = isset($log_entry['data']['target']) ? $log_entry['data']['target'] : 'unknown';
                    $value = isset($log_entry['data']['value']) ? $log_entry['data']['value'] : null;
                    $selector = isset($log_entry['data']['selector']) ? $log_entry['data']['selector'] : null;
                }
                
                $step_details[$current_step] = array(
                    'step_number' => $current_step,
                    'action' => $action,
                    'target' => $target,
                    'value' => $value,
                    'selector' => $selector,
                    'status' => 'pending',
                    'timestamp' => $log_entry['timestamp'],
                    'start_time' => $log_entry['timestamp'],
                    'end_time' => null,
                    'execution_time' => 0,
                    'details' => array(),
                    'logs' => array(),
                    'error' => null,
                    'error_details' => null,
                    'warnings' => array(),
                    'success_indicators' => array(),
                    'screenshot' => null,
                    'performance_metrics' => array(
                        'dom_ready_time' => null,
                        'network_requests' => 0,
                        'memory_usage' => null
                    )
                );
            } elseif (isset($step_details[$current_step])) {
                // Add log entry to step details
                $step_details[$current_step]['logs'][] = $log_entry;
                $step_details[$current_step]['details'][] = $log_entry;
                
                // Update step status and extract detailed information
                if ($log_entry['level'] === 'success') {
                    $step_details[$current_step]['status'] = 'passed';
                    $step_details[$current_step]['end_time'] = $log_entry['timestamp'];
                    
                    // Calculate execution time
                    if ($step_start_time) {
                        $step_details[$current_step]['execution_time'] = strtotime($log_entry['timestamp']) - $step_start_time;
                    }
                    
                    // Extract success indicators
                    if (isset($log_entry['data'])) {
                        if (isset($log_entry['data']['execution_time'])) {
                            $step_details[$current_step]['execution_time'] = $log_entry['data']['execution_time'];
                        }
                        if (isset($log_entry['data']['success_indicators'])) {
                            $step_details[$current_step]['success_indicators'] = $log_entry['data']['success_indicators'];
                        }
                        if (isset($log_entry['data']['performance'])) {
                            $step_details[$current_step]['performance_metrics'] = array_merge(
                                $step_details[$current_step]['performance_metrics'],
                                $log_entry['data']['performance']
                            );
                        }
                    }
                    
                } elseif ($log_entry['level'] === 'error') {
                    $step_details[$current_step]['status'] = 'failed';
                    $step_details[$current_step]['end_time'] = $log_entry['timestamp'];
                    $step_details[$current_step]['error'] = $log_entry['message'];
                    
                    // Calculate execution time
                    if ($step_start_time) {
                        $step_details[$current_step]['execution_time'] = strtotime($log_entry['timestamp']) - $step_start_time;
                    }
                    
                    // Extract detailed error information
                    if (isset($log_entry['data'])) {
                        if (isset($log_entry['data']['execution_time'])) {
                            $step_details[$current_step]['execution_time'] = $log_entry['data']['execution_time'];
                        }
                        if (isset($log_entry['data']['error'])) {
                            $step_details[$current_step]['error_details'] = $log_entry['data']['error'];
                        }
                        if (isset($log_entry['data']['error_type'])) {
                            $step_details[$current_step]['error_type'] = $log_entry['data']['error_type'];
                        }
                        if (isset($log_entry['data']['suggestions'])) {
                            $step_details[$current_step]['suggestions'] = $log_entry['data']['suggestions'];
                        }
                        if (isset($log_entry['data']['context'])) {
                            $step_details[$current_step]['context'] = $log_entry['data']['context'];
                        }
                    }
                    
                } elseif ($log_entry['level'] === 'warning') {
                    $step_details[$current_step]['warnings'][] = array(
                        'message' => $log_entry['message'],
                        'timestamp' => $log_entry['timestamp'],
                        'data' => isset($log_entry['data']) ? $log_entry['data'] : null
                    );
                }
            }
        }
        
        // Add screenshots to relevant steps
        foreach ($screenshots as $screenshot) {
            if (isset($step_details[$screenshot->step_number])) {
                $step_details[$screenshot->step_number]['screenshot'] = $screenshot;
            }
        }
        
        // Ensure all steps have execution_time (fallback to 0 if not set)
        foreach ($step_details as &$step) {
            if (!isset($step['execution_time']) || $step['execution_time'] === 0) {
                $step['execution_time'] = 0;
            }
            
            // Add step summary
            $step['summary'] = $this->generate_step_summary($step);
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
        $step_failures = array();
        $critical_failures = array();
        $warnings = array();
        
        foreach ($execution_log as $log_entry) {
            if ($log_entry['level'] === 'error') {
                $failure_data = array(
                    'timestamp' => $log_entry['timestamp'],
                    'message' => $log_entry['message'],
                    'data' => $log_entry['data'],
                    'step_number' => $this->extract_step_number($log_entry),
                    'error_type' => $this->categorize_error($log_entry['message']),
                    'severity' => $this->assess_error_severity($log_entry),
                    'context' => $this->extract_error_context($log_entry),
                    'suggestions' => $this->generate_error_suggestions($log_entry)
                );
                
                // Add detailed error information if available
                if (isset($log_entry['data']['error'])) {
                    $failure_data['error_details'] = $log_entry['data']['error'];
                }
                if (isset($log_entry['data']['error_type'])) {
                    $failure_data['error_type'] = $log_entry['data']['error_type'];
                }
                if (isset($log_entry['data']['context'])) {
                    $failure_data['context'] = array_merge($failure_data['context'], $log_entry['data']['context']);
                }
                if (isset($log_entry['data']['suggestions'])) {
                    $failure_data['suggestions'] = array_merge($failure_data['suggestions'], $log_entry['data']['suggestions']);
                }
                
                $failures[] = $failure_data;
                
                // Categorize by step
                if ($failure_data['step_number']) {
                    $step_failures[$failure_data['step_number']][] = $failure_data;
                }
                
                // Identify critical failures
                if ($failure_data['severity'] === 'critical') {
                    $critical_failures[] = $failure_data;
                }
                
                // Categorize error patterns
                $error_type = $failure_data['error_type'];
                if (!isset($error_patterns[$error_type])) {
                    $error_patterns[$error_type] = array(
                        'count' => 0,
                        'examples' => array(),
                        'common_causes' => array(),
                        'prevention_tips' => array()
                    );
                }
                $error_patterns[$error_type]['count']++;
                if (count($error_patterns[$error_type]['examples']) < 3) {
                    $error_patterns[$error_type]['examples'][] = $failure_data['message'];
                }
                
            } elseif ($log_entry['level'] === 'warning') {
                $warnings[] = array(
                    'timestamp' => $log_entry['timestamp'],
                    'message' => $log_entry['message'],
                    'data' => $log_entry['data'],
                    'step_number' => $this->extract_step_number($log_entry)
                );
            }
        }
        
        return array(
            'primary_error' => $test_result->error_message,
            'failure_count' => count($failures),
            'critical_failure_count' => count($critical_failures),
            'warning_count' => count($warnings),
            'failure_details' => $failures,
            'step_failures' => $step_failures,
            'critical_failures' => $critical_failures,
            'warnings' => $warnings,
            'error_patterns' => $error_patterns,
            'root_cause_analysis' => $this->analyze_root_cause($failures, $error_patterns),
            'impact_assessment' => $this->assess_impact($test_result, $failures),
            'failure_timeline' => $this->generate_failure_timeline($failures),
            'recovery_suggestions' => $this->generate_recovery_suggestions($failures, $error_patterns),
            'prevention_recommendations' => $this->generate_prevention_recommendations($error_patterns)
        );
    }
    
    /**
     * Prepare visual evidence
     */
    private function prepare_visual_evidence($screenshots) {
        // Screenshot functionality removed - return empty array
        return array();
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
            'average_step_time' => round($avg_step_time ?: 0, 2),
            'steps_per_second' => ($test_result->execution_time > 0 && $test_result->steps_executed > 0)
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
        
        $historical_success_rate = count($historical_results) > 0 
            ? round(($success_count / count($historical_results)) * 100, 1) 
            : 0;
        $average_execution_time = count($historical_results) > 0 
            ? round(($total_execution_time ?: 0) / count($historical_results), 2) 
            : 0;
        
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
            
            // Generate meaningful title and description
            $title = $issue->flow_name ?: 'Unknown Flow';
            $description = $this->generate_issue_description($issue, $critical_suggestions);
            
            $formatted_issues[] = array(
                'id' => $issue->id,
                'title' => $title,
                'description' => $description,
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
                'avg_execution_time' => round($flow->avg_execution_time ?: 0, 2),
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
        );
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }
    
    private function get_status_color($status) {
        $colors = array(
            'passed' => '#28a745',
            'failed' => '#dc3545',
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
        } elseif ($test_result->status === 'failed' && count($failures) > 1) {
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
        $time_penalty = min(($test_result->execution_time ?: 0) / 60, 1); // Penalty for tests over 1 minute
        
        return round((($success_ratio * 100) * (1 - $time_penalty * 0.2)) ?: 0, 1);
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
            // Calculate total steps and steps completed
            $steps_executed = $result->steps_executed ?: 0;
            $steps_passed = $result->steps_passed ?: 0;
            $steps_failed = $result->steps_failed ?: 0;
            $total_steps = $steps_executed ?: ($steps_passed + $steps_failed);
            
            $formatted[] = array(
                'id' => $result->id,
                'flow_name' => $result->flow_name ?: 'Unknown Flow',
                'flow_type' => $result->flow_type ?: 'unknown',
                'status' => $result->status ?: 'unknown',
                'status_label' => $this->get_status_label($result->status),
                'status_color' => $this->get_status_color($result->status),
                'execution_time' => $result->execution_time ?: 0,
                'started_at' => $result->started_at,
                'steps_completed' => $steps_executed,
                'total_steps' => $total_steps,
                'steps_executed' => $steps_executed,
                'steps_passed' => $steps_passed,
                'steps_failed' => $steps_failed,
                'success_rate' => $steps_executed > 0 
                    ? round(($steps_passed / $steps_executed) * 100, 1) 
                    : 0
            );
        }
        
        return $formatted;
    }
    
    /**
     * Generate meaningful issue description
     */
    private function generate_issue_description($issue, $critical_suggestions) {
        // If we have critical suggestions, use the first one for description
        if (!empty($critical_suggestions)) {
            $first_suggestion = reset($critical_suggestions);
            return $first_suggestion['description'] ?? $this->get_default_issue_description($issue);
        }
        
        // Use error message if available
        if (!empty($issue->error_message)) {
            return $issue->error_message;
        }
        
        // Generate description based on flow type and failure
        return $this->get_default_issue_description($issue);
    }
    
    /**
     * Get default issue description based on flow type
     */
    private function get_default_issue_description($issue) {
        $flow_type = $issue->flow_type ?? 'unknown';
        $flow_name = $issue->flow_name ?? 'Unknown Flow';
        
        $descriptions = array(
            'login' => "Login flow '{$flow_name}' failed. Users may not be able to access the site.",
            'registration' => "Registration flow '{$flow_name}' failed. New users cannot create accounts.",
            'woocommerce' => "E-commerce flow '{$flow_name}' failed. Customers may not be able to complete purchases.",
            'contact' => "Contact form '{$flow_name}' failed. Users cannot submit inquiries.",
            'search' => "Search functionality '{$flow_name}' failed. Users cannot find content.",
            'navigation' => "Navigation flow '{$flow_name}' failed. Users may have trouble browsing the site."
        );
        
        return $descriptions[$flow_type] ?? "Flow '{$flow_name}' failed during testing. This may affect user experience.";
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
        // Generate HTML content for PDF
        $html = $this->generate_pdf_html($data);
        
        // For now, return HTML content that can be saved as HTML file
        // In a full implementation, you would use a PDF library like TCPDF or DOMPDF
        return $html;
    }
    
    private function generate_pdf_html($data) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WP Tester Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #00265e; margin: 0; }
        .header p { color: #666; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .status-passed { color: #28a745; font-weight: bold; }
        .status-failed { color: #dc3545; font-weight: bold; }
        .status-running { color: #ffc107; font-weight: bold; }
        .status-pending { color: #6c757d; font-weight: bold; }
        .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>WP Tester Report</h1>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
        <p>Total Tests: ' . count($data) . '</p>
    </div>
    
    <div class="summary">
        <h3>Summary</h3>
        <p>Total Tests: ' . count($data) . '</p>
        <p>Passed: ' . count(array_filter($data, function($item) { return $item['status'] === 'passed'; })) . '</p>
        <p>Failed: ' . count(array_filter($data, function($item) { return $item['status'] === 'failed'; })) . '</p>
        <p>Running: ' . count(array_filter($data, function($item) { return $item['status'] === 'running'; })) . '</p>
        <p>Pending: ' . count(array_filter($data, function($item) { return $item['status'] === 'pending'; })) . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Test ID</th>
                <th>Flow Name</th>
                <th>Status</th>
                <th>Execution Time</th>
                <th>Steps</th>
                <th>Started At</th>
                <th>Completed At</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($data as $result) {
            $status_class = 'status-' . $result['status'];
            $html .= '<tr>
                <td>' . esc_html($result['test_id']) . '</td>
                <td>' . esc_html($result['flow_name']) . '</td>
                <td class="' . $status_class . '">' . esc_html(ucfirst($result['status'])) . '</td>
                <td>' . esc_html($result['execution_time']) . 's</td>
                <td>' . esc_html($result['steps_executed'] . '/' . ($result['steps_passed'] + $result['steps_failed'])) . '</td>
                <td>' . esc_html($result['started_at']) . '</td>
                <td>' . esc_html($result['completed_at']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate step summary
     */
    private function generate_step_summary($step) {
        $summary = array();
        
        if ($step['status'] === 'passed') {
            $summary['status_text'] = 'Step completed successfully';
            $summary['status_icon'] = '✓';
            $summary['status_color'] = '#28a745';
        } elseif ($step['status'] === 'failed') {
            $summary['status_text'] = 'Step failed';
            $summary['status_icon'] = '✗';
            $summary['status_color'] = '#dc3545';
        } else {
            $summary['status_text'] = 'Step pending';
            $summary['status_icon'] = '⏳';
            $summary['status_color'] = '#6c757d';
        }
        
        $summary['action_description'] = $this->format_action_description($step);
        $summary['execution_time_formatted'] = $this->format_execution_time($step['execution_time']);
        $summary['has_warnings'] = !empty($step['warnings']);
        $summary['warning_count'] = count($step['warnings']);
        
        return $summary;
    }
    
    /**
     * Format action description
     */
    private function format_action_description($step) {
        $action = $step['action'];
        $target = $step['target'];
        $value = $step['value'];
        
        switch ($action) {
            case 'click':
                return "Click on element: {$target}";
            case 'type':
                return "Type '{$value}' into: {$target}";
            case 'select':
                return "Select '{$value}' from: {$target}";
            case 'navigate':
                return "Navigate to: {$target}";
            case 'wait':
                return "Wait for: {$target}";
            case 'verify':
                return "Verify: {$target}";
            default:
                return "Execute {$action} on: {$target}";
        }
    }
    
    /**
     * Format execution time
     */
    private function format_execution_time($time) {
        if ($time < 1) {
            return round($time * 1000) . 'ms';
        } else {
            return round($time, 2) . 's';
        }
    }
    
    /**
     * Extract step number from log entry
     */
    private function extract_step_number($log_entry) {
        if (isset($log_entry['data']['step_number'])) {
            return $log_entry['data']['step_number'];
        }
        
        // Try to extract from message
        if (preg_match('/step (\d+)/i', $log_entry['message'], $matches)) {
            return (int)$matches[1];
        }
        
        return null;
    }
    
    /**
     * Assess error severity
     */
    private function assess_error_severity($log_entry) {
        $message = strtolower($log_entry['message']);
        
        // Critical errors
        if (strpos($message, 'critical') !== false || 
            strpos($message, 'fatal') !== false ||
            strpos($message, 'timeout') !== false) {
            return 'critical';
        }
        
        // High severity errors
        if (strpos($message, 'not found') !== false ||
            strpos($message, 'element not found') !== false ||
            strpos($message, 'page not found') !== false) {
            return 'high';
        }
        
        // Medium severity errors
        if (strpos($message, 'failed') !== false ||
            strpos($message, 'error') !== false) {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Extract error context
     */
    private function extract_error_context($log_entry) {
        $context = array();
        
        if (isset($log_entry['data']['url'])) {
            $context['url'] = $log_entry['data']['url'];
        }
        if (isset($log_entry['data']['selector'])) {
            $context['selector'] = $log_entry['data']['selector'];
        }
        if (isset($log_entry['data']['action'])) {
            $context['action'] = $log_entry['data']['action'];
        }
        if (isset($log_entry['data']['target'])) {
            $context['target'] = $log_entry['data']['target'];
        }
        
        return $context;
    }
    
    /**
     * Generate error suggestions
     */
    private function generate_error_suggestions($log_entry) {
        $suggestions = array();
        $message = strtolower($log_entry['message']);
        
        if (strpos($message, 'element not found') !== false) {
            $suggestions[] = 'Check if the element selector is correct';
            $suggestions[] = 'Verify the element exists on the page';
            $suggestions[] = 'Consider adding a wait condition before interacting with the element';
        }
        
        if (strpos($message, 'timeout') !== false) {
            $suggestions[] = 'Increase the timeout duration';
            $suggestions[] = 'Check if the page is loading slowly';
            $suggestions[] = 'Verify the network connection';
        }
        
        if (strpos($message, 'page not found') !== false) {
            $suggestions[] = 'Verify the URL is correct';
            $suggestions[] = 'Check if the page is accessible';
            $suggestions[] = 'Ensure the server is running';
        }
        
        return $suggestions;
    }
    
    /**
     * Generate failure timeline
     */
    private function generate_failure_timeline($failures) {
        $timeline = array();
        
        foreach ($failures as $failure) {
            $timeline[] = array(
                'timestamp' => $failure['timestamp'],
                'step' => $failure['step_number'],
                'error_type' => $failure['error_type'],
                'severity' => $failure['severity'],
                'message' => $failure['message']
            );
        }
        
        // Sort by timestamp
        usort($timeline, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        return $timeline;
    }
    
    /**
     * Generate recovery suggestions
     */
    private function generate_recovery_suggestions($failures, $error_patterns) {
        $suggestions = array();
        
        // Analyze most common error types
        $most_common_error = null;
        $max_count = 0;
        foreach ($error_patterns as $type => $pattern) {
            if ($pattern['count'] > $max_count) {
                $max_count = $pattern['count'];
                $most_common_error = $type;
            }
        }
        
        if ($most_common_error) {
            switch ($most_common_error) {
                case 'element_not_found':
                    $suggestions[] = 'Review and update element selectors';
                    $suggestions[] = 'Add explicit waits for dynamic content';
                    break;
                case 'timeout':
                    $suggestions[] = 'Increase timeout values for slow operations';
                    $suggestions[] = 'Optimize page loading performance';
                    break;
                case 'navigation':
                    $suggestions[] = 'Verify all URLs are accessible';
                    $suggestions[] = 'Check for redirect issues';
                    break;
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Generate prevention recommendations
     */
    private function generate_prevention_recommendations($error_patterns) {
        $recommendations = array();
        
        foreach ($error_patterns as $type => $pattern) {
            switch ($type) {
                case 'element_not_found':
                    $recommendations[] = 'Use more robust element selectors';
                    $recommendations[] = 'Implement proper wait strategies';
                    break;
                case 'timeout':
                    $recommendations[] = 'Set appropriate timeout values';
                    $recommendations[] = 'Monitor page performance';
                    break;
                case 'navigation':
                    $recommendations[] = 'Validate URLs before navigation';
                    $recommendations[] = 'Handle redirect scenarios';
                    break;
            }
        }
        
        return array_unique($recommendations);
    }
}