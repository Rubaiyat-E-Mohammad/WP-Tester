<?php
/**
 * WP Tester Database Class
 * 
 * Handles all database operations for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Table names
     */
    private $crawl_results_table;
    private $flows_table;
    private $test_results_table;
    private $screenshots_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->crawl_results_table = $wpdb->prefix . 'wp_tester_crawl_results';
        $this->flows_table = $wpdb->prefix . 'wp_tester_flows';
        $this->test_results_table = $wpdb->prefix . 'wp_tester_test_results';
        $this->screenshots_table = $wpdb->prefix . 'wp_tester_screenshots';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Crawl results table
        $sql_crawl = "CREATE TABLE {$this->crawl_results_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            page_type varchar(100) NOT NULL,
            title text,
            content_hash varchar(64),
            interactive_elements longtext,
            discovered_flows longtext,
            last_crawled datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY url (url(191)),
            KEY page_type (page_type),
            KEY last_crawled (last_crawled)
        ) $charset_collate;";
        
        // Flows table
        $sql_flows = "CREATE TABLE {$this->flows_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            flow_name varchar(255) NOT NULL,
            flow_type varchar(100) NOT NULL,
            start_url varchar(2048) NOT NULL,
            steps longtext NOT NULL,
            expected_outcome text,
            priority int(11) DEFAULT 5,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY flow_type (flow_type),
            KEY priority (priority),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Test results table
        $sql_results = "CREATE TABLE {$this->test_results_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            flow_id bigint(20) unsigned NOT NULL,
            test_run_id varchar(64) NOT NULL,
            status varchar(20) NOT NULL,
            steps_executed int(11) DEFAULT 0,
            steps_passed int(11) DEFAULT 0,
            steps_failed int(11) DEFAULT 0,
            execution_time float DEFAULT 0,
            error_message text,
            detailed_log longtext,
            suggestions longtext,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY flow_id (flow_id),
            KEY test_run_id (test_run_id),
            KEY status (status),
            KEY started_at (started_at)
        ) $charset_collate;";
        
        // Screenshots table
        $sql_screenshots = "CREATE TABLE {$this->screenshots_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            test_result_id bigint(20) unsigned NOT NULL,
            step_number int(11) NOT NULL,
            screenshot_path varchar(500) NOT NULL,
            screenshot_type varchar(50) DEFAULT 'failure',
            caption text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_result_id (test_result_id),
            KEY step_number (step_number)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_crawl);
        dbDelta($sql_flows);
        dbDelta($sql_results);
        dbDelta($sql_screenshots);
        
        // Update database version
        update_option('wp_tester_db_version', self::DB_VERSION);
    }
    
    /**
     * Save crawl result
     */
    public function save_crawl_result($url, $page_type, $title, $content_hash, $interactive_elements, $discovered_flows) {
        global $wpdb;
        
        return $wpdb->replace(
            $this->crawl_results_table,
            array(
                'url' => $url,
                'page_type' => $page_type,
                'title' => $title,
                'content_hash' => $content_hash,
                'interactive_elements' => wp_json_encode($interactive_elements),
                'discovered_flows' => wp_json_encode($discovered_flows),
                'last_crawled' => current_time('mysql'),
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get crawl results
     */
    public function get_crawl_results($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        $where_conditions = array('status = "active"');
        $where_values = array();
        
        if (!empty($filters['page_type'])) {
            $where_conditions[] = 'page_type = %s';
            $where_values[] = $filters['page_type'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = '(url LIKE %s OR title LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT * FROM {$this->crawl_results_table} 
                WHERE {$where_clause} 
                ORDER BY last_crawled DESC 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Save flow
     */
    public function save_flow($flow_name, $flow_type, $start_url, $steps, $expected_outcome = '', $priority = 5) {
        global $wpdb;
        
        // Check if flow already exists to prevent duplicates
        $existing_flow = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->flows_table} WHERE flow_name = %s AND flow_type = %s AND start_url = %s",
            $flow_name,
            $flow_type,
            $start_url
        ));
        
        if ($existing_flow) {
            // Flow already exists, return the existing ID instead of creating duplicate
            return $existing_flow;
        }
        
        return $wpdb->insert(
            $this->flows_table,
            array(
                'flow_name' => $flow_name,
                'flow_type' => $flow_type,
                'start_url' => $start_url,
                'steps' => wp_json_encode($steps),
                'expected_outcome' => $expected_outcome,
                'priority' => $priority,
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
    }
    
    /**
     * Remove duplicate flows
     */
    public function remove_duplicate_flows() {
        global $wpdb;
        
        // Find and remove duplicate flows, keeping the oldest one
        $duplicates = $wpdb->get_results(
            "SELECT flow_name, flow_type, start_url, GROUP_CONCAT(id ORDER BY created_at ASC) as ids
             FROM {$this->flows_table} 
             GROUP BY flow_name, flow_type, start_url 
             HAVING COUNT(*) > 1"
        );
        
        $removed_count = 0;
        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            $keep_id = array_shift($ids); // Keep the first (oldest) one
            
            if (!empty($ids)) {
                $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$this->flows_table} WHERE id IN ($ids_placeholder)",
                    $ids
                ));
                $removed_count += count($ids);
            }
        }
        
        return $removed_count;
    }
    
    /**
     * Get flows
     */
    public function get_flows($active_only = true, $flow_type = '') {
        global $wpdb;
        
        $where_conditions = array();
        $where_values = array();
        
        if ($active_only) {
            $where_conditions[] = 'is_active = 1';
        }
        
        if (!empty($flow_type)) {
            $where_conditions[] = 'flow_type = %s';
            $where_values[] = $flow_type;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT * FROM {$this->flows_table} {$where_clause} ORDER BY priority DESC, created_at ASC";
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    /**
     * Get flow by ID
     */
    public function get_flow($flow_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->flows_table} WHERE id = %d",
            $flow_id
        ));
    }
    
    /**
     * Get flow ID by name
     */
    public function get_flow_id_by_name($flow_name) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->flows_table} WHERE flow_name = %s ORDER BY id DESC LIMIT 1",
            $flow_name
        ));
    }
    
    /**
     * Save test result
     */
    public function save_test_result($flow_id, $test_run_id, $status, $steps_executed, $steps_passed, $steps_failed, $execution_time, $error_message = '', $detailed_log = '', $suggestions = '') {
        global $wpdb;
        
        return $wpdb->insert(
            $this->test_results_table,
            array(
                'flow_id' => $flow_id,
                'test_run_id' => $test_run_id,
                'status' => $status,
                'steps_executed' => $steps_executed,
                'steps_passed' => $steps_passed,
                'steps_failed' => $steps_failed,
                'execution_time' => $execution_time,
                'error_message' => $error_message,
                'detailed_log' => $detailed_log,
                'suggestions' => $suggestions,
                'completed_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get test results
     */
    public function get_test_results($flow_id = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $where_clause = $flow_id ? 'WHERE tr.flow_id = %d' : '';
        
        $sql = "SELECT tr.*, f.flow_name, f.flow_type 
                FROM {$this->test_results_table} tr 
                LEFT JOIN {$this->flows_table} f ON tr.flow_id = f.id 
                {$where_clause}
                ORDER BY tr.started_at DESC 
                LIMIT %d OFFSET %d";
        
        if ($flow_id) {
            return $wpdb->get_results($wpdb->prepare($sql, $flow_id, $limit, $offset));
        } else {
            return $wpdb->get_results($wpdb->prepare($sql, $limit, $offset));
        }
    }
    
    /**
     * Get test result by ID
     */
    public function get_test_result($result_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT tr.*, f.flow_name, f.flow_type 
             FROM {$this->test_results_table} tr 
             LEFT JOIN {$this->flows_table} f ON tr.flow_id = f.id 
             WHERE tr.id = %d",
            $result_id
        ));
    }
    
    /**
     * Save screenshot
     */
    public function save_screenshot($test_result_id, $step_number, $screenshot_path, $screenshot_type = 'failure', $caption = '') {
        global $wpdb;
        
        return $wpdb->insert(
            $this->screenshots_table,
            array(
                'test_result_id' => $test_result_id,
                'step_number' => $step_number,
                'screenshot_path' => $screenshot_path,
                'screenshot_type' => $screenshot_type,
                'caption' => $caption
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get screenshots for test result
     */
    public function get_screenshots($test_result_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->screenshots_table} 
             WHERE test_result_id = %d 
             ORDER BY step_number ASC",
            $test_result_id
        ));
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total pages crawled
        $stats['total_pages'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->crawl_results_table} WHERE status = 'active'") ?: 0;
        
        // Total flows
        $stats['total_flows'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->flows_table}") ?: 0;
        
        // Active flows
        $stats['active_flows'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->flows_table} WHERE is_active = 1") ?: 0;
        
        // Tests executed in last 30 days
        $stats['tests_executed_30d'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?: 0;
        
        // Recent test results (24 hours)
        $stats['recent_tests'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?: 0;
        
        // Success rate (last 30 days, fallback to all time if no recent tests)
        $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?: 0;
        $successful_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'passed' AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") ?: 0;
        
        // If no tests in last 30 days, check all time
        if ($total_tests === 0) {
            $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table}") ?: 0;
            $successful_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'passed'") ?: 0;
        }
        
        $stats['success_rate'] = $total_tests > 0 ? round(($successful_tests / $total_tests) * 100, 1) : 0;
        
        // Average response time (execution time)
        $avg_execution_time = $wpdb->get_var("SELECT AVG(execution_time) FROM {$this->test_results_table} WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND execution_time > 0");
        $stats['avg_response_time'] = $avg_execution_time ? round($avg_execution_time, 2) : 0;
        
        // Critical issues (failed tests from last 24 hours)
        $stats['critical_issues'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'failed' AND started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?: 0;
        
        // Last crawl
        $last_crawl = $wpdb->get_var("SELECT MAX(last_crawled) FROM {$this->crawl_results_table}");
        $stats['last_crawl'] = $last_crawl ?: 'Never';
        
        // Additional stats for comprehensive dashboard
        $stats['total_errors'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'failed'") ?: 0;
        
        // Performance metrics
        $stats['avg_load_time'] = $avg_execution_time ?: 0;
        $stats['slowest_page'] = $wpdb->get_var("SELECT f.flow_name FROM {$this->test_results_table} tr LEFT JOIN {$this->flows_table} f ON tr.flow_id = f.id ORDER BY tr.execution_time DESC LIMIT 1") ?: '';
        $stats['fastest_page'] = $wpdb->get_var("SELECT f.flow_name FROM {$this->test_results_table} tr LEFT JOIN {$this->flows_table} f ON tr.flow_id = f.id WHERE tr.execution_time > 0 ORDER BY tr.execution_time ASC LIMIT 1") ?: '';
        
        return $stats;
    }
    
    /**
     * Clean old data
     */
    public function cleanup_old_data($days_to_keep = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        // Delete old test results and their screenshots
        $wpdb->query($wpdb->prepare(
            "DELETE tr, s FROM {$this->test_results_table} tr 
             LEFT JOIN {$this->screenshots_table} s ON tr.id = s.test_result_id 
             WHERE tr.started_at < %s",
            $cutoff_date
        ));
        
        // Delete old crawl results
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->crawl_results_table} 
             WHERE last_crawled < %s AND status = 'inactive'",
            $cutoff_date
        ));
    }
}