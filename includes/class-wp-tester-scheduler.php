<?php
/**
 * WP Tester Scheduler Class
 * 
 * Handles scheduling of periodic crawls and tests
 */

if (!defined('ABSPATH')) {
    exit;
}

// WordPress function declarations for linter
if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        return false;
    }
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
        
        // Add custom cron intervals
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
        
        // Hook into settings updates to reschedule events
        add_action('update_option_wp_tester_settings', array($this, 'reschedule_events'), 10, 2);
        
        // Schedule cleanup task
        if (!wp_next_scheduled('wp_tester_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'wp_tester_cleanup');
        }
        
        // Ensure crawl is scheduled based on current settings
        $this->ensure_crawl_scheduled();
        
        // Ensure tests are scheduled based on current settings
        $this->ensure_tests_scheduled();
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['monthly'] = array(
            'interval' => 30 * 24 * 60 * 60, // 30 days in seconds
            'display' => __('Monthly', 'wp-tester')
        );
        return $schedules;
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
            error_log('WP Tester: Starting scheduled test execution');
            
            $admin = new WP_Tester_Admin();
            $database = new WP_Tester_Database();
            
            // Get all active flows
            $flows = $this->get_flows_for_testing();
            
            if (empty($flows)) {
                error_log('WP Tester: No active flows found for scheduled testing');
                return;
            }
            
            $results = array();
            $total_flows = count($flows);
            $passed_flows = 0;
            $failed_flows = 0;
            
            error_log("WP Tester: Found {$total_flows} active flows for testing");
            
            // Run each flow
            foreach ($flows as $flow) {
                try {
                    error_log("WP Tester: Testing flow: {$flow->flow_name}");
                    
                    $result = $admin->execute_flow_with_fallback($flow->id, false);
                    
                    $results[] = array(
                        'flow_id' => $flow->id,
                        'flow_name' => $flow->flow_name,
                        'status' => $result['success'] && $result['status'] === 'passed' ? 'passed' : 'failed',
                        'execution_time' => $result['execution_time'] ?? 0,
                        'steps_passed' => $result['steps_passed'] ?? 0,
                        'steps_failed' => $result['steps_failed'] ?? 0,
                        'error_message' => $result['error_message'] ?? null
                    );
                    
                    if ($result['success'] && $result['status'] === 'passed') {
                        $passed_flows++;
                    } else {
                        $failed_flows++;
                    }
                    
                    // Add delay between tests to avoid overwhelming the server
                    sleep(2);
                    
                } catch (Exception $e) {
                    error_log("WP Tester: Error testing flow {$flow->flow_name}: " . $e->getMessage());
                    $results[] = array(
                        'flow_id' => $flow->id,
                        'flow_name' => $flow->flow_name,
                        'status' => 'failed',
                        'execution_time' => 0,
                        'steps_passed' => 0,
                        'steps_failed' => 0,
                        'error_message' => $e->getMessage()
                    );
                    $failed_flows++;
                }
            }
            
            // Send email notification if enabled
            $this->send_test_notification($results, $total_flows, $passed_flows, $failed_flows, 'scheduled');
            
            // Reschedule if this was a monthly test
            $settings = get_option('wp_tester_settings', array());
            if (($settings['test_frequency'] ?? 'never') === 'monthly') {
                $this->schedule_monthly_tests($settings['test_schedule_time'] ?? '02:00');
            }
            
            error_log(sprintf(
                'WP Tester: Scheduled testing completed. %d flows tested, %d passed, %d failed.',
                $total_flows, $passed_flows, $failed_flows
            ));
            
        } catch (Exception $e) {
            error_log('WP Tester: Error in scheduled testing: ' . $e->getMessage());
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
        // Clear existing scheduled crawl - use more aggressive clearing
        wp_clear_scheduled_hook('wp_tester_daily_crawl');
        
        // Also clear any specific scheduled events using the force clear method
        $this->force_clear_crawl_events();
        
        // Reschedule based on new frequency
        $frequency = isset($new_value['crawl_frequency']) ? $new_value['crawl_frequency'] : 'never';
        
        // Log the frequency change for debugging
        error_log("WP Tester: Crawl frequency changed to: " . $frequency);
        
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
            error_log("WP Tester: Crawl scheduled for frequency: " . $frequency);
        } else {
            error_log("WP Tester: Crawl frequency set to 'never' - no scheduling");
        }
        
        // Handle test scheduling changes
        $this->ensure_tests_scheduled();
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
     * Force clear crawl events (useful for debugging)
     */
    public function force_clear_crawl_events() {
        try {
            // Clear all instances of the crawl hook
            wp_clear_scheduled_hook('wp_tester_daily_crawl');
            
            // Also try to unschedule any specific events using WordPress cron functions
            $cron = get_option('cron');
            if ($cron && is_array($cron)) {
                foreach ($cron as $timestamp => $hooks) {
                    if (isset($hooks['wp_tester_daily_crawl']) && is_array($hooks['wp_tester_daily_crawl'])) {
                        foreach ($hooks['wp_tester_daily_crawl'] as $key => $event) {
                            if (isset($event['args'])) {
                                wp_unschedule_event($timestamp, 'wp_tester_daily_crawl', $event['args']);
                            } else {
                                // If no args, try to unschedule without args
                                wp_unschedule_event($timestamp, 'wp_tester_daily_crawl');
                            }
                        }
                    }
                }
            }
            
            error_log("WP Tester: Force cleared all crawl events");
            
        } catch (Exception $e) {
            error_log("WP Tester: Error clearing crawl events - " . $e->getMessage());
        }
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
     * Ensure crawl is scheduled based on current settings
     */
    public function ensure_crawl_scheduled() {
        $settings = get_option('wp_tester_settings', array());
        $frequency = $settings['crawl_frequency'] ?? 'never';
        
        // Only schedule if frequency is not 'never' and not already scheduled
        if ($frequency !== 'never' && !wp_next_scheduled('wp_tester_daily_crawl')) {
            if ($frequency === 'daily' && isset($settings['crawl_schedule_time']) && isset($settings['crawl_schedule_days'])) {
                // Use custom daily scheduling with specific time and days
                $this->schedule_custom_daily_crawl($settings['crawl_schedule_time'], $settings['crawl_schedule_days']);
            } else {
                // Use WordPress default frequency scheduling
                wp_schedule_event(time(), $frequency, 'wp_tester_daily_crawl');
            }
        }
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
    
    /**
     * Ensure tests are scheduled based on current settings
     */
    public function ensure_tests_scheduled() {
        $settings = get_option('wp_tester_settings', array());
        $frequency = $settings['test_frequency'] ?? 'never';
        
        // Clear existing scheduled tests
        wp_clear_scheduled_hook('wp_tester_test_flows');
        
        // Only schedule if frequency is not 'never'
        if ($frequency !== 'never') {
            if ($frequency === 'daily') {
                $this->schedule_daily_tests($settings['test_schedule_time'] ?? '02:00');
            } elseif ($frequency === 'weekly') {
                $this->schedule_weekly_tests($settings['test_schedule_time'] ?? '02:00', $settings['test_schedule_days'] ?? ['monday']);
            } elseif ($frequency === 'monthly') {
                $this->schedule_monthly_tests($settings['test_schedule_time'] ?? '02:00');
            }
        }
    }
    
    /**
     * Schedule daily tests
     */
    private function schedule_daily_tests($time) {
        // Use WordPress daily frequency
        if (!wp_next_scheduled('wp_tester_test_flows')) {
            wp_schedule_event(time(), 'daily', 'wp_tester_test_flows');
            error_log("WP Tester: Daily tests scheduled");
        }
    }
    
    /**
     * Schedule weekly tests
     */
    private function schedule_weekly_tests($time, $days) {
        // Use WordPress weekly frequency
        if (!wp_next_scheduled('wp_tester_test_flows')) {
            wp_schedule_event(time(), 'weekly', 'wp_tester_test_flows');
            error_log("WP Tester: Weekly tests scheduled");
        }
    }
    
    /**
     * Schedule monthly tests
     */
    private function schedule_monthly_tests($time) {
        // For monthly, schedule a single event and reschedule after execution
        $timestamp = $this->get_next_monthly_timestamp($time);
        if ($timestamp) {
            wp_schedule_single_event($timestamp, 'wp_tester_test_flows');
            error_log("WP Tester: Monthly tests scheduled for " . date('Y-m-d H:i:s', $timestamp));
        }
    }
    
    /**
     * Get next daily timestamp
     */
    private function get_next_daily_timestamp($time) {
        $today = date('Y-m-d');
        $datetime = $today . ' ' . $time . ':00';
        $timestamp = strtotime($datetime);
        
        // If time has passed today, schedule for tomorrow
        if ($timestamp <= time()) {
            $timestamp = strtotime('+1 day', $timestamp);
        }
        
        return $timestamp;
    }
    
    /**
     * Get next weekly timestamp
     */
    private function get_next_weekly_timestamp($time, $days) {
        $day_names = array(
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4,
            'friday' => 5, 'saturday' => 6, 'sunday' => 0
        );
        
        $next_timestamp = null;
        $today = date('N'); // 1 = Monday, 7 = Sunday
        
        foreach ($days as $day) {
            $day_num = $day_names[$day] ?? 1;
            $days_until = ($day_num - $today + 7) % 7;
            if ($days_until == 0) $days_until = 7; // Next week
            
            $target_date = date('Y-m-d', strtotime("+{$days_until} days"));
            $datetime = $target_date . ' ' . $time . ':00';
            $timestamp = strtotime($datetime);
            
            if ($next_timestamp === null || $timestamp < $next_timestamp) {
                $next_timestamp = $timestamp;
            }
        }
        
        return $next_timestamp;
    }
    
    /**
     * Get next monthly timestamp
     */
    private function get_next_monthly_timestamp($time) {
        $today = date('Y-m-d');
        $this_month = date('Y-m') . '-01 ' . $time . ':00';
        $timestamp = strtotime($this_month);
        
        // If time has passed this month, schedule for next month
        if ($timestamp <= time()) {
            $timestamp = strtotime('+1 month', $timestamp);
        }
        
        return $timestamp;
    }
    
    /**
     * Send test notification email
     */
    public function send_test_notification($results, $total_flows, $passed_flows, $failed_flows, $type = 'manual') {
        $settings = get_option('wp_tester_settings', array());
        
        // Debug: Check what settings we actually have
        error_log('WP Tester: Email settings: ' . print_r($settings, true));
        
        // Check if email notifications are enabled
        if (empty($settings['email_notifications'])) {
            error_log('WP Tester: Email notifications disabled');
            return;
        }
        
        $recipients = $settings['email_recipients'] ?? '';
        if (empty($recipients)) {
            error_log('WP Tester: No email recipients configured');
            return;
        }
        
        $recipient_emails = array_filter(array_map('trim', explode("\n", $recipients)));
        if (empty($recipient_emails)) {
            error_log('WP Tester: No valid email recipients found');
            return;
        }
        
        error_log('WP Tester: Sending email to: ' . implode(', ', $recipient_emails));
        
        // Generate email content
        $subject = sprintf(
            'WP Tester %s - %d/%d Tests Passed',
            ucfirst($type) . ' Test Results',
            $passed_flows,
            $total_flows
        );
        
        $html_content = $this->generate_test_email_html($results, $total_flows, $passed_flows, $failed_flows, $type);
        
        // Send email
        $email_success = $this->send_email($recipient_emails, $subject, $html_content);
        
        if ($email_success) {
            error_log('WP Tester: Test notification email sent successfully');
        } else {
            error_log('WP Tester: Failed to send test notification email');
        }
    }
    
    /**
     * Generate HTML email content for test results
     */
    private function generate_test_email_html($results, $total_flows, $passed_flows, $failed_flows, $type) {
        $status_color = $failed_flows > 0 ? '#dc3545' : '#28a745';
        $status_text = $failed_flows > 0 ? 'Some Tests Failed' : 'All Tests Passed';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>WP Tester Test Results</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f8fafc;">
            <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #00265e 0%, #0F9D7A 100%); color: white; padding: 2rem; text-align: center;">
                    <h1 style="margin: 0; font-size: 1.5rem; font-weight: 700;">WP Tester Test Results</h1>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 1rem;">' . ucfirst($type) . ' Test Execution Report</p>
                </div>
                
                <!-- Summary -->
                <div style="padding: 2rem;">
                    <div style="background: ' . $status_color . '; color: white; padding: 1.5rem; border-radius: 8px; text-align: center; margin-bottom: 2rem;">
                        <h2 style="margin: 0; font-size: 1.25rem; font-weight: 600;">' . $status_text . '</h2>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; opacity: 0.9;">
                            ' . $passed_flows . ' of ' . $total_flows . ' tests passed
                        </p>
                    </div>
                    
                    <!-- Test Results Table -->
                    <div style="margin-bottom: 2rem;">
                        <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1rem; font-weight: 600;">Test Results</h3>
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f9fafb;">
                                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Flow Name</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Status</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Time</th>
                                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Steps</th>
                                    </tr>
                                </thead>
                                <tbody>';
        
        foreach ($results as $result) {
            $status_badge = $result['status'] === 'passed' ? 
                '<span style="background-color: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">PASSED</span>' :
                '<span style="background-color: #ef4444; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">FAILED</span>';
            
            $html .= '
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 0.75rem; color: #374151;">' . esc_html($result['flow_name']) . '</td>
                                        <td style="padding: 0.75rem; text-align: center;">' . $status_badge . '</td>
                                        <td style="padding: 0.75rem; text-align: center; color: #6b7280;">' . number_format($result['execution_time'], 2) . 's</td>
                                        <td style="padding: 0.75rem; text-align: center; color: #6b7280;">' . $result['steps_passed'] . '/' . ($result['steps_passed'] + $result['steps_failed']) . '</td>
                                    </tr>';
        }
        
        $html .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                            Generated by WP Tester on ' . date('F j, Y \a\t g:i A') . '
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Send email using SMTP or WordPress mail
     */
    private function send_email($recipients, $subject, $html_content) {
        $settings = get_option('wp_tester_settings', array());
        
        error_log('WP Tester: send_email called with subject: ' . $subject);
        error_log('WP Tester: SMTP host: ' . ($settings['smtp_host'] ?? 'not set'));
        error_log('WP Tester: SMTP username: ' . ($settings['smtp_username'] ?? 'not set'));
        
        $success = false;
        
        // Use SMTP if configured
        if (!empty($settings['smtp_host']) && !empty($settings['smtp_username'])) {
            error_log('WP Tester: Using SMTP to send email');
            $success = $this->send_smtp_email($recipients, $subject, $html_content, $settings);
        } else {
            error_log('WP Tester: Using WordPress default mail');
            // Use WordPress default mail
            $success = $this->send_wp_email($recipients, $subject, $html_content, $settings);
        }
        
        if ($success) {
            error_log('WP Tester: Email sending completed successfully');
        } else {
            error_log('WP Tester: Email sending failed');
        }
        
        return $success;
    }
    
    /**
     * Send email using SMTP
     */
    private function send_smtp_email($recipients, $subject, $html_content, $settings) {
        $success_count = 0;
        $total_recipients = count($recipients);
        
        try {
            // Set up SMTP headers with additional delivery headers
            $from_email = $settings['from_email'] ?? get_option('admin_email');
            $from_name = $settings['from_name'] ?? 'WP Tester';
            
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Reply-To: ' . $from_email,
                'Return-Path: ' . $from_email,
                'X-Mailer: WP Tester Plugin',
                'X-Priority: 3',
                'MIME-Version: 1.0'
            );
            
            // Create a unique callback function name to avoid conflicts
            $callback_name = 'wp_tester_smtp_init_' . uniqid();
            
            // Use wp_mail with SMTP settings
            add_action('phpmailer_init', $callback_name);
            
            // Define the callback function
            $$callback_name = function($phpmailer) use ($settings) {
                try {
                    $phpmailer->isSMTP();
                    $phpmailer->Host = $settings['smtp_host'];
                    $phpmailer->SMTPAuth = true;
                    $phpmailer->Username = $settings['smtp_username'];
                    $phpmailer->Password = $settings['smtp_password'];
                    $phpmailer->Port = $settings['smtp_port'] ?? 587;
                    
                    // Set encryption
                    if ($settings['smtp_encryption'] === 'ssl') {
                        $phpmailer->SMTPSecure = 'ssl';
                    } elseif ($settings['smtp_encryption'] === 'tls') {
                        $phpmailer->SMTPSecure = 'tls';
                    }
                    
                    // Enable debug output for troubleshooting
                    $phpmailer->SMTPDebug = 2; // Enable verbose debug output
                    $phpmailer->Debugoutput = function($str, $level) {
                        error_log("WP Tester SMTP Debug: $str");
                    };
                    
                    // Additional SMTP settings for better delivery
                    $phpmailer->SMTPKeepAlive = true;
                    $phpmailer->Timeout = 30;
                    $phpmailer->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
                    
                } catch (Exception $e) {
                    error_log('WP Tester: PHPMailer configuration error - ' . $e->getMessage());
                }
            };
            
            // Send emails to each recipient
            foreach ($recipients as $recipient) {
                error_log('WP Tester: Attempting to send email to: ' . $recipient);
                error_log('WP Tester: Subject: ' . $subject);
                error_log('WP Tester: From: ' . ($settings['from_name'] ?? 'WP Tester') . ' <' . ($settings['from_email'] ?? get_option('admin_email')) . '>');
                
                if (function_exists('wp_mail')) {
                    // Clear any previous PHPMailer errors
                    global $phpmailer;
                    if (isset($phpmailer)) {
                        $phpmailer->clearErrorMessages();
                    }
                    
                    $result = wp_mail($recipient, $subject, $html_content, $headers);
                    
                    if ($result) {
                        $success_count++;
                        error_log('WP Tester: wp_mail SUCCESS for ' . $recipient);
                    } else {
                        error_log('WP Tester: wp_mail FAILED for ' . $recipient);
                        
                        // Get detailed error information
                        if (isset($phpmailer)) {
                            if (!empty($phpmailer->ErrorInfo)) {
                                error_log('WP Tester: PHPMailer error: ' . $phpmailer->ErrorInfo);
                            }
                            if (!empty($phpmailer->getSMTPInstance()->getError())) {
                                error_log('WP Tester: SMTP error: ' . $phpmailer->getSMTPInstance()->getError());
                            }
                        }
                        
                        // Check WordPress mail errors
                        $wp_mail_errors = apply_filters('wp_mail_failed', null);
                        if ($wp_mail_errors) {
                            error_log('WP Tester: WordPress mail error: ' . $wp_mail_errors->get_error_message());
                        }
                    }
                } else {
                    error_log('WP Tester: wp_mail not available, using PHP mail');
                    // Fallback to PHP mail if wp_mail is not available
                    $result = mail($recipient, $subject, $html_content, implode("\r\n", $headers));
                    if ($result) {
                        $success_count++;
                        error_log('WP Tester: PHP mail SUCCESS for ' . $recipient);
                    } else {
                        error_log('WP Tester: PHP mail FAILED for ' . $recipient);
                        $last_error = error_get_last();
                        if ($last_error) {
                            error_log('WP Tester: PHP error: ' . $last_error['message']);
                        }
                    }
                }
            }
            
            // Remove the callback to prevent interference with other emails
            remove_action('phpmailer_init', $callback_name);
            
            error_log("WP Tester: SMTP email sending completed. Success: $success_count/$total_recipients");
            
            return $success_count === $total_recipients;
            
        } catch (Exception $e) {
            error_log('WP Tester: SMTP email error - ' . $e->getMessage());
            
            // Remove the callback in case of error
            if (isset($callback_name)) {
                remove_action('phpmailer_init', $callback_name);
            }
            
            return false;
        }
    }
    
    /**
     * Test email functionality
     */
    public function test_email() {
        $test_results = array(array(
            'flow_id' => 999,
            'flow_name' => 'Test Flow',
            'status' => 'passed',
            'execution_time' => 1.5,
            'steps_passed' => 3,
            'steps_failed' => 0,
            'error_message' => null
        ));
        
        $this->send_test_notification($test_results, 1, 1, 0, 'test');
        
        // Return success status for AJAX response
        return true;
    }
    
    /**
     * Send email using WordPress default mail
     */
    private function send_wp_email($recipients, $subject, $html_content, $settings) {
        $success_count = 0;
        $total_recipients = count($recipients);
        
        $from_email = $settings['from_email'] ?? get_option('admin_email');
        $from_name = $settings['from_name'] ?? 'WP Tester';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'Return-Path: ' . $from_email,
            'X-Mailer: WP Tester Plugin',
            'X-Priority: 3',
            'MIME-Version: 1.0'
        );
        
        foreach ($recipients as $recipient) {
            error_log('WP Tester: Attempting to send WordPress mail to: ' . $recipient);
            
            if (function_exists('wp_mail')) {
                $result = wp_mail($recipient, $subject, $html_content, $headers);
                if ($result) {
                    $success_count++;
                    error_log('WP Tester: WordPress mail SUCCESS for ' . $recipient);
                } else {
                    error_log('WP Tester: WordPress mail FAILED for ' . $recipient);
                }
            } else {
                error_log('WP Tester: wp_mail not available, using PHP mail');
                // Fallback to PHP mail if wp_mail is not available
                $result = mail($recipient, $subject, $html_content, implode("\r\n", $headers));
                if ($result) {
                    $success_count++;
                    error_log('WP Tester: PHP mail SUCCESS for ' . $recipient);
                } else {
                    error_log('WP Tester: PHP mail FAILED for ' . $recipient);
                }
            }
        }
        
        error_log("WP Tester: WordPress mail sending completed. Success: $success_count/$total_recipients");
        
        return $success_count === $total_recipients;
    }
}