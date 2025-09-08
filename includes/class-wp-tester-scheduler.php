<?php
/**
 * WP Tester Scheduler Class
 * 
 * Handles scheduling of periodic crawls and tests
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Scheduler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress cron system
        add_action('wp_tester_daily_crawl', array($this, 'run_scheduled_crawl'));
        add_action('wp_tester_test_flows', array($this, 'run_scheduled_tests'));
        add_action('wp_tester_cleanup', array($this, 'run_cleanup'));
        
        // Hook into settings updates to reschedule events
        add_action('update_option_wp_tester_settings', array($this, 'reschedule_events'), 10, 2);
        
        // Schedule cleanup task
        if (!wp_next_scheduled('wp_tester_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'wp_tester_cleanup');
        }
    }
    
    /**
     * Run scheduled crawl
     */
    public function run_scheduled_crawl() {
        try {
            $crawler = new WP_Tester_Crawler();
            $result = $crawler->run_full_crawl();
            
            // Log the result
            if ($result['success']) {
                error_log(sprintf(
                    'WP Tester: Scheduled crawl completed. Crawled %d URLs, discovered %d flows in %.2f seconds.',
                    $result['crawled_count'],
                    $result['discovered_flows'],
                    $result['execution_time']
                ));
                
                // Schedule tests for newly discovered flows
                $this->schedule_flow_tests();
            } else {
                error_log('WP Tester: Scheduled crawl failed - ' . $result['error']);
            }
            
        } catch (Exception $e) {
            error_log('WP Tester: Scheduled crawl exception - ' . $e->getMessage());
        }
    }
    
    /**
     * Run scheduled tests
     */
    public function run_scheduled_tests() {
        try {
            $admin = new WP_Tester_Admin();
            $database = new WP_Tester_Database();
            
            // Get flows that need testing (prioritize by priority and last test date)
            $flows = $this->get_flows_for_testing();
            
            $test_count = 0;
            $success_count = 0;
            
            foreach ($flows as $flow) {
                $result = $admin->execute_flow_with_fallback($flow->id, false);
                $test_count++;
                
                if ($result['success'] && $result['status'] === 'passed') {
                    $success_count++;
                }
                
                // Add delay between tests to avoid overwhelming the server
                sleep(5);
                
                // Limit number of tests per scheduled run
                if ($test_count >= 10) {
                    break;
                }
            }
            
            error_log(sprintf(
                'WP Tester: Scheduled tests completed. Ran %d tests, %d passed.',
                $test_count,
                $success_count
            ));
            
        } catch (Exception $e) {
            error_log('WP Tester: Scheduled tests exception - ' . $e->getMessage());
        }
    }
    
    /**
     * Run cleanup
     */
    public function run_cleanup() {
        try {
            $database = new WP_Tester_Database();
            $settings = get_option('wp_tester_settings', array());
            
            $retention_days = isset($settings['data_retention_days']) ? $settings['data_retention_days'] : 30;
            
            $database->cleanup_old_data($retention_days);
            
            // Clean up screenshot files
            $this->cleanup_screenshot_files($retention_days);
            
            error_log('WP Tester: Cleanup completed - removed data older than ' . $retention_days . ' days.');
            
        } catch (Exception $e) {
            error_log('WP Tester: Cleanup exception - ' . $e->getMessage());
        }
    }
    
    /**
     * Schedule flow tests after crawl
     */
    private function schedule_flow_tests() {
        // Schedule test run for 5 minutes from now
        if (!wp_next_scheduled('wp_tester_test_flows')) {
            wp_schedule_single_event(time() + 300, 'wp_tester_test_flows');
        }
    }
    
    /**
     * Get flows that need testing
     */
    private function get_flows_for_testing() {
        global $wpdb;
        
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        $results_table = $wpdb->prefix . 'wp_tester_test_results';
        
        // Get flows that haven't been tested recently, prioritized by priority
        $sql = "
            SELECT f.*, 
                   MAX(tr.started_at) as last_test_date,
                   COUNT(tr.id) as test_count
            FROM {$flows_table} f
            LEFT JOIN {$results_table} tr ON f.id = tr.flow_id 
                AND tr.started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            WHERE f.is_active = 1
            GROUP BY f.id
            HAVING (last_test_date IS NULL OR last_test_date < DATE_SUB(NOW(), INTERVAL 6 HOUR))
            ORDER BY f.priority DESC, last_test_date ASC
            LIMIT 20
        ";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Reschedule events when settings change
     */
    public function reschedule_events($old_value, $new_value) {
        // Clear existing scheduled crawl
        wp_clear_scheduled_hook('wp_tester_daily_crawl');
        
        // Reschedule based on new frequency
        $frequency = isset($new_value['crawl_frequency']) ? $new_value['crawl_frequency'] : 'never';
        
        // Only schedule if frequency is not 'never'
        if ($frequency !== 'never') {
            if ($frequency === 'daily' && isset($new_value['crawl_schedule_time']) && isset($new_value['crawl_schedule_days'])) {
                // Use custom daily scheduling with specific time and days
                $this->schedule_custom_daily_crawl($new_value['crawl_schedule_time'], $new_value['crawl_schedule_days']);
            } else {
                // Use WordPress default frequency scheduling
                if (!wp_next_scheduled('wp_tester_daily_crawl')) {
                    wp_schedule_event(time(), $frequency, 'wp_tester_daily_crawl');
                }
            }
        }
    }
    
    /**
     * Schedule custom daily crawl with specific time and days
     */
    private function schedule_custom_daily_crawl($time, $days) {
        if (empty($time) || empty($days)) {
            return;
        }
        
        // Parse time (HH:MM format)
        list($hour, $minute) = explode(':', $time);
        $hour = intval($hour);
        $minute = intval($minute);
        
        // Convert day names to WordPress day numbers (1 = Monday, 7 = Sunday)
        $day_numbers = array();
        $day_map = array(
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7
        );
        
        foreach ($days as $day) {
            if (isset($day_map[$day])) {
                $day_numbers[] = $day_map[$day];
            }
        }
        
        if (empty($day_numbers)) {
            return;
        }
        
        // Schedule for each selected day
        foreach ($day_numbers as $day_number) {
            $next_run = $this->get_next_weekday_time($day_number, $hour, $minute);
            if ($next_run) {
                wp_schedule_single_event($next_run, 'wp_tester_daily_crawl');
            }
        }
    }
    
    /**
     * Get next occurrence of a specific weekday and time
     */
    private function get_next_weekday_time($day_number, $hour, $minute) {
        $current_time = current_time('timestamp');
        $current_day = date('N', $current_time); // 1 = Monday, 7 = Sunday
        $current_hour = date('H', $current_time);
        $current_minute = date('i', $current_time);
        
        // Calculate days until next occurrence
        $days_until = ($day_number - $current_day + 7) % 7;
        
        // If it's the same day, check if time has passed
        if ($days_until === 0) {
            $current_time_minutes = $current_hour * 60 + $current_minute;
            $target_time_minutes = $hour * 60 + $minute;
            
            if ($current_time_minutes >= $target_time_minutes) {
                // Time has passed today, schedule for next week
                $days_until = 7;
            }
        }
        
        // Calculate the exact timestamp
        $target_date = date('Y-m-d', $current_time + ($days_until * 24 * 60 * 60));
        $target_timestamp = strtotime($target_date . ' ' . sprintf('%02d:%02d:00', $hour, $minute));
        
        return $target_timestamp;
    }
    
    /**
     * Clean up old screenshot files
     */
    private function cleanup_screenshot_files($retention_days) {
        $upload_dir = wp_upload_dir();
        $screenshots_dir = $upload_dir['basedir'] . '/wp-tester-screenshots';
        
        if (!is_dir($screenshots_dir)) {
            return;
        }
        
        $cutoff_time = time() - ($retention_days * 24 * 60 * 60);
        $files = glob($screenshots_dir . '/*.png');
        $deleted_count = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $deleted_count++;
                }
            }
        }
        
        if ($deleted_count > 0) {
            error_log("WP Tester: Cleaned up {$deleted_count} old screenshot files.");
        }
    }
    
    /**
     * Schedule immediate crawl
     */
    public function schedule_immediate_crawl() {
        wp_schedule_single_event(time() + 60, 'wp_tester_daily_crawl');
    }
    
    /**
     * Schedule immediate test run
     */
    public function schedule_immediate_test_run($flow_ids = null) {
        if ($flow_ids) {
            // Store flow IDs for targeted testing
            update_option('wp_tester_scheduled_flows', $flow_ids);
        }
        
        wp_schedule_single_event(time() + 30, 'wp_tester_test_flows');
    }
    
    /**
     * Get next scheduled events
     */
    public function get_scheduled_events() {
        return array(
            'crawl' => wp_next_scheduled('wp_tester_daily_crawl'),
            'tests' => wp_next_scheduled('wp_tester_test_flows'),
            'cleanup' => wp_next_scheduled('wp_tester_cleanup')
        );
    }
    
    /**
     * Clear all scheduled events
     */
    public function clear_all_scheduled_events() {
        wp_clear_scheduled_hook('wp_tester_daily_crawl');
        wp_clear_scheduled_hook('wp_tester_test_flows');
        wp_clear_scheduled_hook('wp_tester_cleanup');
    }
    
    /**
     * Check if events are properly scheduled
     */
    public function verify_scheduled_events() {
        $events = $this->get_scheduled_events();
        $issues = array();
        
        if (!$events['crawl']) {
            $issues[] = 'Daily crawl is not scheduled';
        }
        
        if (!$events['cleanup']) {
            $issues[] = 'Weekly cleanup is not scheduled';
        }
        
        return empty($issues) ? true : $issues;
    }
    
    /**
     * Get scheduling status for admin display
     */
    public function get_scheduling_status() {
        $events = $this->get_scheduled_events();
        
        return array(
            'crawl' => array(
                'scheduled' => (bool) $events['crawl'],
                'next_run' => $events['crawl'] ? date('Y-m-d H:i:s', $events['crawl']) : null,
                'human_time' => $events['crawl'] ? human_time_diff($events['crawl'], current_time('timestamp')) : null
            ),
            'tests' => array(
                'scheduled' => (bool) $events['tests'],
                'next_run' => $events['tests'] ? date('Y-m-d H:i:s', $events['tests']) : null,
                'human_time' => $events['tests'] ? human_time_diff($events['tests'], current_time('timestamp')) : null
            ),
            'cleanup' => array(
                'scheduled' => (bool) $events['cleanup'],
                'next_run' => $events['cleanup'] ? date('Y-m-d H:i:s', $events['cleanup']) : null,
                'human_time' => $events['cleanup'] ? human_time_diff($events['cleanup'], current_time('timestamp')) : null
            )
        );
    }
}