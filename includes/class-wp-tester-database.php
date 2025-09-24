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
            forms_found int(11) DEFAULT 0,
            links_found int(11) DEFAULT 0,
            crawled_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_crawled datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY url (url(191)),
            KEY page_type (page_type),
            KEY last_crawled (last_crawled),
            KEY crawled_at (crawled_at),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Flows table
        $sql_flows = "CREATE TABLE {$this->flows_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            flow_name varchar(255) NOT NULL,
            flow_description text,
            flow_type varchar(100) NOT NULL,
            start_url varchar(2048) NOT NULL,
            steps longtext NOT NULL,
            expected_outcome text,
            priority int(11) DEFAULT 5,
            is_active tinyint(1) DEFAULT 1,
            ai_generated tinyint(1) DEFAULT 0,
            ai_provider varchar(100) DEFAULT NULL,
            created_by varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY flow_type (flow_type),
            KEY priority (priority),
            KEY is_active (is_active),
            KEY ai_generated (ai_generated)
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
        
        $result1 = dbDelta($sql_crawl);
        $result2 = dbDelta($sql_flows);
        $result3 = dbDelta($sql_results);
        $result4 = dbDelta($sql_screenshots);
        
        // Update database version
        update_option('wp_tester_db_version', self::DB_VERSION);
        
        // Log any database errors
        if (!empty($wpdb->last_error)) {
            error_log('WP Tester: Database table creation error: ' . $wpdb->last_error);
        } else {
            error_log('WP Tester: Database tables created successfully');
        }
    }
    
    /**
     * Save crawl result
     */
    public function save_crawl_result($url, $page_type, $title, $content_hash, $interactive_elements, $discovered_flows) {
        global $wpdb;
        
        // Ensure schema is up to date
        $this->update_crawl_results_table_schema();
        
        $result = $wpdb->replace(
            $this->crawl_results_table,
            array(
                'url' => $url,
                'page_type' => $page_type,
                'title' => $title,
                'content_hash' => $content_hash,
                'interactive_elements' => wp_json_encode($interactive_elements),
                'discovered_flows' => wp_json_encode($discovered_flows),
                'forms_found' => is_array($interactive_elements) ? count($interactive_elements) : 0,
                'links_found' => 0, // Will be set properly by crawler
                'crawled_at' => current_time('mysql'),
                'last_crawled' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Update the global last crawl timestamp
        update_option('wp_tester_last_crawl', current_time('mysql'));
        
        return $result;
    }
    
    /**
     * Update last crawl timestamp
     */
    public function update_last_crawl_timestamp() {
        update_option('wp_tester_last_crawl', current_time('mysql'));
    }
    
    /**
     * Get crawl results
     */
    public function get_crawl_results($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        // Ensure schema is up to date
        $this->update_crawl_results_table_schema();
        
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
     * Get crawl results count
     */
    public function get_crawl_results_count($filters = array()) {
        global $wpdb;
        
        // Ensure schema is up to date
        $this->update_crawl_results_table_schema();
        
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
        
        $sql = "SELECT COUNT(*) FROM {$this->crawl_results_table} WHERE {$where_clause}";
        
        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_var($sql);
        }
    }
    
    /**
     * Save flow
     */
    public function save_flow($flow_name, $flow_type, $start_url, $steps, $expected_outcome = '', $priority = 5, $ai_generated = false, $ai_provider = null) {
        global $wpdb;
        
        // Ensure schema is up to date
        $this->update_flows_table_schema();
        
        // Check if flow already exists to prevent duplicates
        // First try exact match
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
        
        // If no exact match, check for similar flows (same type and URL, similar name)
        $similar_flow = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->flows_table} WHERE flow_type = %s AND start_url = %s AND flow_name LIKE %s",
            $flow_type,
            $start_url,
            '%' . $wpdb->esc_like(substr($flow_name, 0, 50)) . '%'
        ));
        
        if ($similar_flow) {
            // Similar flow exists, return the existing ID instead of creating duplicate
            return $similar_flow;
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
                'is_active' => 1,
                'ai_generated' => $ai_generated ? 1 : 0,
                'ai_provider' => $ai_provider
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Remove duplicate flows
     */
    public function remove_duplicate_flows() {
        global $wpdb;
        
        $removed_count = 0;
        
        // First, remove exact duplicates
        $duplicates = $wpdb->get_results(
            "SELECT flow_name, flow_type, start_url, GROUP_CONCAT(id ORDER BY created_at ASC) as ids
             FROM {$this->flows_table} 
             GROUP BY flow_name, flow_type, start_url 
             HAVING COUNT(*) > 1"
        );
        
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
        
        // Second, remove similar flows (same type and URL, similar names)
        $similar_flows = $wpdb->get_results(
            "SELECT f1.id, f1.flow_name, f1.flow_type, f1.start_url
             FROM {$this->flows_table} f1
             INNER JOIN {$this->flows_table} f2 ON f1.flow_type = f2.flow_type 
                 AND f1.start_url = f2.start_url 
                 AND f1.id != f2.id
                 AND f1.flow_name LIKE CONCAT('%', SUBSTRING(f2.flow_name, 1, 50), '%')
             WHERE f1.created_at > f2.created_at
             ORDER BY f1.created_at ASC"
        );
        
        foreach ($similar_flows as $similar_flow) {
            $wpdb->delete($this->flows_table, array('id' => $similar_flow->id), array('%d'));
            $removed_count++;
        }
        
        return $removed_count;
    }
    
    /**
     * Force cleanup of all duplicate flows (more aggressive)
     */
    public function force_cleanup_duplicates() {
        global $wpdb;
        
        $removed_count = 0;
        
        // Get all flows grouped by type and URL
        $flow_groups = $wpdb->get_results(
            "SELECT flow_type, start_url, GROUP_CONCAT(id ORDER BY created_at ASC) as ids, COUNT(*) as count
             FROM {$this->flows_table} 
             GROUP BY flow_type, start_url 
             HAVING COUNT(*) > 1"
        );
        
        foreach ($flow_groups as $group) {
            $ids = explode(',', $group->ids);
            $keep_id = array_shift($ids); // Keep the first (oldest) one
            
            if (!empty($ids)) {
                // Delete all duplicates except the first one
                $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
                $delete_query = "DELETE FROM {$this->flows_table} WHERE id IN ($ids_placeholder)";
                
                $deleted_rows = $wpdb->query($wpdb->prepare($delete_query, $ids));
                $removed_count += count($ids);
            }
        }
        return $removed_count;
    }
    
    /**
     * Get duplicate flows information for debugging
     */
    public function get_duplicate_flows_info() {
        global $wpdb;
        
        // Get all flows with their details
        $all_flows = $wpdb->get_results("SELECT id, flow_name, flow_type, start_url, created_at FROM {$this->flows_table} ORDER BY flow_type, start_url, created_at");
        
        // Get duplicate groups
        $duplicate_groups = $wpdb->get_results(
            "SELECT flow_type, start_url, GROUP_CONCAT(id ORDER BY created_at ASC) as ids, COUNT(*) as count
             FROM {$this->flows_table} 
             GROUP BY flow_type, start_url 
             HAVING COUNT(*) > 1"
        );
        
        return array(
            'all_flows' => $all_flows,
            'duplicate_groups' => $duplicate_groups,
            'total_flows' => count($all_flows),
            'duplicate_groups_count' => count($duplicate_groups)
        );
    }
    
    /**
     * Create test duplicates for debugging (temporary method)
     */
    public function create_test_duplicates() {
        global $wpdb;
        
        // Create a few test duplicate flows
        $test_flows = array(
            array(
                'flow_name' => 'Test Login Flow',
                'flow_type' => 'login',
                'start_url' => 'https://example.com/login',
                'steps' => wp_json_encode(array(array('action' => 'click', 'selector' => '#login-btn'))),
                'expected_outcome' => 'User logged in successfully',
                'priority' => 5,
                'is_active' => 1
            ),
            array(
                'flow_name' => 'Test Login Flow Copy',
                'flow_type' => 'login',
                'start_url' => 'https://example.com/login',
                'steps' => wp_json_encode(array(array('action' => 'click', 'selector' => '#login-btn'))),
                'expected_outcome' => 'User logged in successfully',
                'priority' => 5,
                'is_active' => 1
            ),
            array(
                'flow_name' => 'Test Registration Flow',
                'flow_type' => 'registration',
                'start_url' => 'https://example.com/register',
                'steps' => wp_json_encode(array(array('action' => 'fill', 'selector' => '#email', 'value' => 'test@example.com'))),
                'expected_outcome' => 'User registered successfully',
                'priority' => 5,
                'is_active' => 1
            ),
            array(
                'flow_name' => 'Test Registration Flow Copy',
                'flow_type' => 'registration',
                'start_url' => 'https://example.com/register',
                'steps' => wp_json_encode(array(array('action' => 'fill', 'selector' => '#email', 'value' => 'test@example.com'))),
                'expected_outcome' => 'User registered successfully',
                'priority' => 5,
                'is_active' => 1
            )
        );
        
        $created_count = 0;
        foreach ($test_flows as $flow) {
            $result = $wpdb->insert($this->flows_table, $flow, array('%s', '%s', '%s', '%s', '%s', '%d', '%d'));
            if ($result) {
                $created_count++;
            }
        }
        
        return $created_count;
    }
    
    /**
     * Cleanup test results
     */
    public function cleanup_test_results($options = array()) {
        global $wpdb;
        
        $defaults = array(
            'older_than_days' => 30,
            'keep_successful' => true,
            'keep_failed' => false,
            'max_results_per_flow' => 10
        );
        
        $options = wp_parse_args($options, $defaults);
        $removed_count = 0;
        
        // Remove old test results
        if ($options['older_than_days'] > 0) {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$options['older_than_days']} days"));
            $where_conditions = array("started_at < %s");
            $where_values = array($cutoff_date);
            
            // Add status filters
            $status_conditions = array();
            if (!$options['keep_successful']) {
                $status_conditions[] = "status = 'passed'";
            }
            if (!$options['keep_failed']) {
                $status_conditions[] = "status = 'failed'";
            }
            
            if (!empty($status_conditions)) {
                $where_conditions[] = "(" . implode(' OR ', $status_conditions) . ")";
            }
            
            $sql = "DELETE FROM {$this->test_results_table} WHERE " . implode(' AND ', $where_conditions);
            $deleted_rows = $wpdb->query($wpdb->prepare($sql, $where_values));
            $removed_count += $deleted_rows;
        }
        
        // Keep only the most recent results per flow
        if ($options['max_results_per_flow'] > 0) {
            $flows = $wpdb->get_results("SELECT DISTINCT flow_id FROM {$this->test_results_table}");
            
            foreach ($flows as $flow) {
                $recent_results = $wpdb->get_results($wpdb->prepare(
                    "SELECT id FROM {$this->test_results_table} 
                     WHERE flow_id = %d 
                     ORDER BY started_at DESC 
                     LIMIT %d",
                    $flow->flow_id,
                    $options['max_results_per_flow']
                ));
                
                if (!empty($recent_results)) {
                    $keep_ids = array_map(function($result) { return $result->id; }, $recent_results);
                    $placeholders = implode(',', array_fill(0, count($keep_ids), '%d'));
                    
                    $deleted_from_flow = $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$this->test_results_table} 
                         WHERE flow_id = %d AND id NOT IN ($placeholders)",
                        array_merge(array($flow->flow_id), $keep_ids)
                    ));
                    $removed_count += $deleted_from_flow;
                }
            }
        }
        
        return $removed_count;
    }
    
    /**
     * Simple cleanup - remove all test results
     */
    public function cleanup_all_test_results() {
        global $wpdb;
        
        // Delete all test results
        $deleted_count = $wpdb->query("DELETE FROM {$this->test_results_table}");
        
        return $deleted_count;
    }
    
    /**
     * Get test results statistics for cleanup
     */
    public function get_test_results_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total test results
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table}");
        
        // By status
        $stats['passed'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'passed'");
        $stats['failed'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'failed'");
        
        // By age
        $stats['last_7_days'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['last_30_days'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['older_than_30_days'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE started_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        // Oldest and newest
        $stats['oldest'] = $wpdb->get_var("SELECT MIN(started_at) FROM {$this->test_results_table}");
        $stats['newest'] = $wpdb->get_var("SELECT MAX(started_at) FROM {$this->test_results_table}");
        
        return $stats;
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
     * Update flows table schema to add AI generation fields
     */
    public function update_flows_table_schema() {
        // Check if created_by column exists
        global $wpdb;
        $created_by_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'created_by'",
            $wpdb->dbname,
            $this->flows_table
        ));
        if (empty($created_by_exists)) {
            $result_created_by = $wpdb->query("ALTER TABLE {$this->flows_table} ADD COLUMN created_by varchar(100) DEFAULT NULL");
            if ($result_created_by !== false) {
                error_log('WP Tester: Successfully added created_by column to flows table');
            } else {
                error_log('WP Tester: Failed to add created_by column to flows table: ' . $wpdb->last_error);
            }
        }
        global $wpdb;
        
        // Check if flow_description column exists
        $description_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'flow_description'",
            $wpdb->dbname,
            $this->flows_table
        ));
        
        if (empty($description_exists)) {
            // Add flow_description column
            $result_desc = $wpdb->query("ALTER TABLE {$this->flows_table} ADD COLUMN flow_description text AFTER flow_name");
            
            if ($result_desc !== false) {
                error_log('WP Tester: Successfully added flow_description column to flows table');
            } else {
                error_log('WP Tester: Failed to add flow_description column to flows table: ' . $wpdb->last_error);
            }
        }
        
        // Check if ai_generated column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'ai_generated'",
            $wpdb->dbname,
            $this->flows_table
        ));
        
        if (empty($column_exists)) {
            // Add ai_generated column
            $result1 = $wpdb->query("ALTER TABLE {$this->flows_table} ADD COLUMN ai_generated tinyint(1) DEFAULT 0");
            
            // Add ai_provider column
            $result2 = $wpdb->query("ALTER TABLE {$this->flows_table} ADD COLUMN ai_provider varchar(100) DEFAULT NULL");
            
            // Add index for ai_generated
            $result3 = $wpdb->query("ALTER TABLE {$this->flows_table} ADD INDEX ai_generated (ai_generated)");
            
            if ($result1 !== false && $result2 !== false && $result3 !== false) {
                error_log('WP Tester: Successfully added AI generation columns to flows table');
            } else {
                error_log('WP Tester: Error adding AI generation columns to flows table: ' . $wpdb->last_error);
            }
        }
    }
    
    /**
     * Update crawl results table schema to include missing fields
     */
    public function update_crawl_results_table_schema() {
        global $wpdb;
        
        // Check if forms_found column exists
        $forms_column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'forms_found'",
            $wpdb->dbname,
            $this->crawl_results_table
        ));
        
        if (empty($forms_column_exists)) {
            // Add missing columns
            $wpdb->query("ALTER TABLE {$this->crawl_results_table} ADD COLUMN forms_found int(11) DEFAULT 0");
            $wpdb->query("ALTER TABLE {$this->crawl_results_table} ADD COLUMN links_found int(11) DEFAULT 0");
            $wpdb->query("ALTER TABLE {$this->crawl_results_table} ADD COLUMN crawled_at datetime DEFAULT CURRENT_TIMESTAMP");
            $wpdb->query("ALTER TABLE {$this->crawl_results_table} ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP");
            
            // Add indexes
            $wpdb->query("ALTER TABLE {$this->crawl_results_table} ADD INDEX crawled_at (crawled_at)");
            $wpdb->query("ALTER TABLE {$this->crawl_results_table} ADD INDEX created_at (created_at)");
            
            error_log('WP Tester: Updated crawl results table schema');
        }
    }

    /**
     * Get AI generated flows
     */
    public function get_ai_generated_flows($limit = 5) {
        global $wpdb;
        
        // Ensure schema is up to date
        $this->update_flows_table_schema();
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->flows_table} 
             WHERE ai_generated = 1 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        );
        
        return $wpdb->get_results($sql);
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
        
        $result = $wpdb->insert(
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
        
        if ($result === false) {
            error_log('WP Tester: Failed to save test result. Error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
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
     * Get test results for a specific period (for email reports)
     */
    public function get_test_results_for_period($date_from = '') {
        global $wpdb;
        
        $where_clause = '';
        $params = array();
        
        if (!empty($date_from)) {
            $where_clause = 'WHERE tr.completed_at >= %s';
            $params[] = $date_from;
        }
        
        $sql = "SELECT tr.*, f.flow_name, f.flow_type 
                FROM {$this->test_results_table} tr 
                LEFT JOIN {$this->flows_table} f ON tr.flow_id = f.id 
                {$where_clause}
                ORDER BY tr.completed_at DESC";
        
        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $results = $wpdb->get_results($sql);
        }
        
        // Format results for email report
        $formatted_results = array();
        foreach ($results as $result) {
            $formatted_results[] = array(
                'id' => $result->id,
                'flow_id' => $result->flow_id,
                'flow_name' => $result->flow_name ?: 'Unknown Flow',
                'flow_type' => $result->flow_type ?: 'unknown',
                'status' => $result->status,
                'steps_executed' => $result->steps_executed ?: 0,
                'steps_passed' => $result->steps_passed ?: 0,
                'steps_failed' => $result->steps_failed ?: 0,
                'execution_time' => $result->execution_time ?: 0,
                'completed_at' => $result->completed_at,
                'started_at' => $result->started_at,
                'error_message' => $result->error_message ?: ''
            );
        }
        
        return $formatted_results;
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
        
        error_log('WP Tester: Saving screenshot with result ID: ' . $test_result_id . ', Step: ' . $step_number);
        
        $result = $wpdb->insert(
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
        
        if ($result === false) {
            error_log("WP Tester: Failed to save screenshot to database. Error: " . $wpdb->last_error);
        } else {
            error_log('WP Tester: Screenshot saved successfully with ID: ' . $wpdb->insert_id);
        }
        
        return $result;
    }
    
    /**
     * Get screenshots for test result
     */
    public function get_screenshots($test_result_id) {
        global $wpdb;
        
        $screenshots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->screenshots_table} 
             WHERE test_result_id = %d 
             ORDER BY step_number ASC",
            $test_result_id
        ));
        
        error_log('WP Tester: Retrieved ' . count($screenshots) . ' screenshots for result ID ' . $test_result_id);
        if (!empty($screenshots)) {
            foreach ($screenshots as $screenshot) {
                error_log('WP Tester: Screenshot - Step: ' . $screenshot->step_number . ', Type: ' . $screenshot->screenshot_type . ', Path: ' . $screenshot->screenshot_path);
            }
        } else {
            // Debug: Check what screenshots exist in database
            $all_screenshots = $wpdb->get_results("SELECT * FROM {$this->screenshots_table} ORDER BY id DESC LIMIT 10");
            error_log('WP Tester: Last 10 screenshots in database:');
            foreach ($all_screenshots as $screenshot) {
                error_log('WP Tester: DB Screenshot - ID: ' . $screenshot->id . ', Result ID: ' . $screenshot->test_result_id . ', Step: ' . $screenshot->step_number);
            }
        }
        
        return $screenshots;
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
        
        // Last test - get the most recent test execution
        $last_test = $wpdb->get_var("SELECT MAX(started_at) FROM {$this->test_results_table}");
        $stats['last_test'] = $last_test ?: 'Never';
        
        // Last crawl - check both crawl results and a dedicated last crawl timestamp
        $last_crawl_from_results = $wpdb->get_var("SELECT MAX(last_crawled) FROM {$this->crawl_results_table}");
        $last_crawl_timestamp = get_option('wp_tester_last_crawl', null);
        
        // Use the most recent of the two
        if ($last_crawl_timestamp && $last_crawl_from_results) {
            $stats['last_crawl'] = max($last_crawl_timestamp, $last_crawl_from_results);
        } elseif ($last_crawl_timestamp) {
            $stats['last_crawl'] = $last_crawl_timestamp;
        } elseif ($last_crawl_from_results) {
            $stats['last_crawl'] = $last_crawl_from_results;
        } else {
            $stats['last_crawl'] = 'Never';
        }
        
        // Additional stats for comprehensive dashboard
        $stats['total_errors'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'failed'") ?: 0;
        $stats['failed_tests'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->test_results_table} WHERE status = 'failed'") ?: 0;
        
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