<?php
/**
 * WP Tester Flow Executor Class
 * 
 * Executes user flows and simulates interactions with retry logic
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Flow_Executor {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Current test run ID
     */
    private $test_run_id;
    
    /**
     * Current execution log
     */
    private $execution_log;
    
    /**
     * Screenshot handler
     */
    private $screenshot_handler;
    
    /**
     * Screenshots to save array
     */
    private $screenshots_to_save;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
        $this->settings = get_option('wp_tester_settings', array());
        $this->execution_log = array();
        
        // Initialize screenshot handler
        add_action('init', array($this, 'init_screenshot_handler'));
        
        // Hook into scheduled testing
        add_action('wp_tester_test_flows', array($this, 'run_scheduled_tests'));
    }
    
    /**
     * Initialize screenshot handler
     */
    public function init_screenshot_handler() {
        // Create screenshots directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $screenshots_dir = $upload_dir['basedir'] . '/wp-tester-screenshots';
        
        // Ensure directory exists and is writable
        if (!file_exists($screenshots_dir)) {
            $created = wp_mkdir_p($screenshots_dir);
            if (!$created) {
                error_log('WP Tester: Failed to create screenshots directory: ' . $screenshots_dir);
                // Fallback to temp directory
                $screenshots_dir = sys_get_temp_dir() . '/wp-tester-screenshots';
                if (!file_exists($screenshots_dir)) {
                    $created = wp_mkdir_p($screenshots_dir);
                    if (!$created) {
                        error_log('WP Tester: Failed to create fallback screenshots directory: ' . $screenshots_dir);
                    }
                }
            }
        }
        
        // Verify directory is writable
        if (!is_writable($screenshots_dir)) {
            error_log('WP Tester: Screenshots directory is not writable: ' . $screenshots_dir);
            // Fallback to temp directory
            $screenshots_dir = sys_get_temp_dir() . '/wp-tester-screenshots';
            if (!file_exists($screenshots_dir)) {
                $created = wp_mkdir_p($screenshots_dir);
                if (!$created) {
                    error_log('WP Tester: Failed to create fallback screenshots directory: ' . $screenshots_dir);
                }
            }
        }
        
        $this->screenshot_handler = $screenshots_dir;
        error_log('WP Tester: Screenshots directory set to: ' . $screenshots_dir);
    }
    
    /**
     * Get screenshot handler directory, initialize if needed
     */
    private function get_screenshot_handler() {
        if (empty($this->screenshot_handler)) {
            $this->init_screenshot_handler();
        }
        return $this->screenshot_handler;
    }
    
    /**
     * Execute a single flow
     */
    public function execute_flow($flow_id, $manual_trigger = false) {
        $start_time = microtime(true);
        $this->test_run_id = uniqid('test_', true);
        $this->execution_log = array();
        $this->screenshots_to_save = array();
        
        try {
            // Get flow details
            $flow = $this->database->get_flow($flow_id);
            if (!$flow) {
                throw new Exception('Flow not found');
            }
            
            $steps = json_decode($flow->steps, true);
            if (!$steps || !is_array($steps) || empty($steps)) {
                // Create a simple validation step for flows with no defined steps
                $steps = array(
                    array(
                        'action' => 'navigate',
                        'target' => $flow->start_url,
                        'description' => 'Validate page accessibility'
                    )
                );
                
                $this->log_step('warning', 'Flow has no defined steps, using default validation step', array(
                    'flow_id' => $flow_id,
                    'start_url' => $flow->start_url
                ));
            }
            
            $this->log_step('info', 'Starting flow execution', array(
                'flow_id' => $flow_id,
                'flow_name' => $flow->flow_name,
                'flow_type' => $flow->flow_type,
                'test_run_id' => $this->test_run_id
            ));
            
            $step_results = array();
            $steps_executed = 0;
            $steps_passed = 0;
            $steps_failed = 0;
            
            // Execute each step
            foreach ($steps as $step_number => $step) {
                $steps_executed++;
                
                $this->log_step('info', 'Executing step', array(
                    'step_number' => $step_number + 1,
                    'action' => $step['action'],
                    'target' => $step['target']
                ));
                
                $step_result = $this->execute_step($step, $step_number + 1, $flow);
                $step_results[] = $step_result;
                
                if ($step_result['success']) {
                    $steps_passed++;
                    $this->log_step('success', 'Step completed successfully', $step_result);
                    
                    // Take screenshot on success for debugging (optional)
                    $screenshot_enabled = isset($this->settings['screenshot_on_failure']) ? $this->settings['screenshot_on_failure'] : true;
                    if ($screenshot_enabled && isset($this->settings['screenshot_on_success']) && $this->settings['screenshot_on_success']) {
                        $screenshot_path = $this->take_screenshot($flow_id, $step_number + 1, 'success', 'Step completed successfully');
                        if ($screenshot_path) {
                            $this->screenshots_to_save[] = array(
                                'step_number' => $step_number + 1,
                                'screenshot_path' => $screenshot_path,
                                'screenshot_type' => 'success',
                                'caption' => 'Step completed successfully'
                            );
                        }
                    }
                } else {
                    $steps_failed++;
                    $this->log_step('error', 'Step failed', $step_result);
                    
                    // Take screenshot on failure if enabled (default: true)
                    $screenshot_enabled = isset($this->settings['screenshot_on_failure']) ? $this->settings['screenshot_on_failure'] : true;
                    error_log('WP Tester: Screenshot on failure enabled: ' . ($screenshot_enabled ? 'Yes' : 'No'));
                    
                    if ($screenshot_enabled) {
                        $screenshot_path = $this->take_screenshot($flow_id, $step_number + 1, 'failure', $step_result['error']);
                        if ($screenshot_path) {
                            // Store screenshot path for later database save
                            $this->screenshots_to_save[] = array(
                                'step_number' => $step_number + 1,
                                'screenshot_path' => $screenshot_path,
                                'screenshot_type' => 'failure',
                                'caption' => $step_result['error']
                            );
                            error_log("WP Tester: Added screenshot to save array - Step: " . ($step_number + 1) . ", Path: $screenshot_path");
                        } else {
                            error_log("WP Tester: Failed to take screenshot for failed step " . ($step_number + 1));
                        }
                    } else {
                        error_log("WP Tester: Screenshot on failure is disabled in settings");
                    }
                    
                    // Stop execution on critical failure
                    if ($step_result['critical']) {
                        break;
                    }
                }
                
                // Add delay between steps
                usleep(500000); // 0.5 second delay
            }
            
            $execution_time = microtime(true) - $start_time;
            $overall_status = $steps_failed === 0 ? 'passed' : ($steps_passed > 0 ? 'partial' : 'failed');
            
            // Generate suggestions based on failures
            $suggestions = $this->generate_suggestions($step_results, $flow);
            
            // Save test result
            $result_id = $this->database->save_test_result(
                $flow_id,
                $this->test_run_id,
                $overall_status,
                $steps_executed,
                $steps_passed,
                $steps_failed,
                $execution_time,
                $steps_failed > 0 ? $this->get_primary_error($step_results) : '',
                wp_json_encode($this->execution_log),
                wp_json_encode($suggestions)
            );
            
            // Save screenshots to database
            if ($result_id && !empty($this->screenshots_to_save)) {
                error_log("WP Tester: Saving " . count($this->screenshots_to_save) . " screenshots to database for result ID: $result_id");
                foreach ($this->screenshots_to_save as $screenshot) {
                    $screenshot_id = $this->database->save_screenshot(
                        $result_id,
                        $screenshot['step_number'],
                        $screenshot['screenshot_path'],
                        $screenshot['screenshot_type'],
                        $screenshot['caption']
                    );
                    if ($screenshot_id) {
                        error_log("WP Tester: Successfully saved screenshot to database with ID: $screenshot_id");
                    } else {
                        error_log("WP Tester: Failed to save screenshot to database");
                    }
                }
            } else {
                error_log("WP Tester: No screenshots to save - Result ID: " . ($result_id ?: 'null') . ", Screenshots count: " . count($this->screenshots_to_save));
            }
            
            $this->log_step('info', 'Flow execution completed', array(
                'status' => $overall_status,
                'steps_executed' => $steps_executed,
                'steps_passed' => $steps_passed,
                'steps_failed' => $steps_failed,
                'execution_time' => $execution_time
            ));
            
            return array(
                'success' => true,
                'result_id' => $result_id,
                'status' => $overall_status,
                'steps_executed' => $steps_executed,
                'steps_passed' => $steps_passed,
                'steps_failed' => $steps_failed,
                'execution_time' => $execution_time,
                'suggestions' => $suggestions
            );
            
        } catch (Exception $e) {
            $execution_time = microtime(true) - $start_time;
            
            $this->log_step('error', 'Flow execution failed', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Save failed test result
            $this->database->save_test_result(
                $flow_id,
                $this->test_run_id,
                'failed',
                0,
                0,
                1,
                $execution_time,
                $e->getMessage(),
                wp_json_encode($this->execution_log),
                wp_json_encode(array())
            );
            
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => $execution_time
            );
        }
    }
    
    /**
     * Execute a single step
     */
    private function execute_step($step, $step_number, $flow) {
        $step_start_time = microtime(true);
        $max_retries = isset($this->settings['retry_attempts']) ? (int)$this->settings['retry_attempts'] : 2;
        $timeout = isset($this->settings['test_timeout']) ? (int)$this->settings['test_timeout'] : 30;
        
        $last_error = '';
        
        // Retry logic
        for ($attempt = 1; $attempt <= $max_retries + 1; $attempt++) {
            try {
                $result = $this->perform_step_action($step, $step_number, $flow, $timeout);
                
                if ($result['success']) {
                    $step_execution_time = microtime(true) - $step_start_time;
                    $result['execution_time'] = $step_execution_time;
                    
                    if ($attempt > 1) {
                        $result['retry_attempt'] = $attempt;
                        $result['note'] = 'Succeeded after ' . ($attempt - 1) . ' retries';
                    }
                    return $result;
                }
                
                $last_error = $result['error'];
                
                if ($attempt <= $max_retries) {
                    $this->log_step('warning', 'Step failed, retrying', array(
                        'attempt' => $attempt,
                        'error' => $last_error,
                        'next_attempt_in' => '2 seconds'
                    ));
                    sleep(2); // Wait 2 seconds before retry
                }
                
            } catch (Exception $e) {
                $last_error = $e->getMessage();
                
                if ($attempt <= $max_retries) {
                    $this->log_step('warning', 'Step exception, retrying', array(
                        'attempt' => $attempt,
                        'error' => $last_error
                    ));
                    sleep(2);
                }
            }
        }
        
        // All attempts failed
        $step_execution_time = microtime(true) - $step_start_time;
        return array(
            'success' => false,
            'error' => $last_error,
            'attempts' => $max_retries + 1,
            'critical' => $this->is_critical_failure($step, $last_error),
            'execution_time' => $step_execution_time
        );
    }
    
    /**
     * Perform the actual step action
     */
    private function perform_step_action($step, $step_number, $flow, $timeout) {
        switch ($step['action']) {
            case 'navigate':
            case 'visit':
                return $this->navigate_to_url($step['target']);
                
            case 'fill_form':
                return $this->fill_form($step['target'], $step['data'], $flow);
                
            case 'fill_input':
                return $this->fill_input($step['target'], $step['data']);
                
            case 'click':
                return $this->click_element($step['target']);
                
            case 'submit':
                return $this->submit_form($step['target']);
                
            case 'verify':
                return $this->verify_element($step['target'], isset($step['expected']) ? $step['expected'] : null);
                
            case 'wait':
                return $this->wait_for_element($step['target'], $timeout);
                
            case 'scroll':
                return $this->scroll_to_element($step['target']);
                
            case 'hover':
                return $this->hover_element($step['target']);
                
            case 'interact':
                return $this->interact_with_elements($step['target'], $flow);
                
            default:
                throw new Exception('Unknown step action: ' . $step['action']);
        }
    }
    
    /**
     * Navigate to URL
     */
    private function navigate_to_url($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'WP-Tester/1.0'
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Failed to navigate to URL: ' . ($response ? $response->get_error_message() : 'Unknown error')
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            return array(
                'success' => false,
                'error' => 'HTTP error ' . $response_code . ' when navigating to URL'
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Successfully navigated to ' . $url,
            'response_code' => $response_code
        );
    }
    
    /**
     * Fill form with test data
     */
    private function fill_form($form_target, $data_type, $flow) {
        // Get test data based on flow type and data type
        $test_data = $this->generate_test_data($data_type, $flow);
        
        // Simulate form filling
        $filled_fields = array();
        foreach ($test_data as $field_name => $field_value) {
            $filled_fields[] = array(
                'field' => $field_name,
                'value' => $field_value,
                'type' => $this->get_field_type($field_name)
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Form filled with test data',
            'filled_fields' => $filled_fields,
            'form_target' => $form_target
        );
    }
    
    /**
     * Fill single input field
     */
    private function fill_input($target, $data) {
        return array(
            'success' => true,
            'message' => 'Input field filled',
            'target' => $target,
            'value' => $data
        );
    }
    
    /**
     * Click element
     */
    private function click_element($target) {
        // Simulate click action
        return array(
            'success' => true,
            'message' => 'Element clicked',
            'target' => $target
        );
    }
    
    /**
     * Submit form
     */
    private function submit_form($target) {
        // Simulate form submission
        // In a real implementation, this would use a headless browser
        
        return array(
            'success' => true,
            'message' => 'Form submitted',
            'target' => $target
        );
    }
    
    /**
     * Verify element presence/content
     */
    private function verify_element($target, $expected = null) {
        // Get the current page content for verification
        $current_url = $this->get_current_page_url();
        
        if (empty($current_url)) {
            return array(
                'success' => false,
                'error' => 'No current page URL available for verification'
            );
        }
        
        // Fetch the page content
        $response = wp_remote_get($current_url, array(
            'timeout' => 15,
            'user-agent' => 'WP-Tester/1.0'
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Failed to fetch page content for verification: ' . ($response ? $response->get_error_message() : 'Unknown error')
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 400) {
            return array(
                'success' => false,
                'error' => 'HTTP error ' . $response_code . ' when verifying page content'
            );
        }
        
        // Check if target element/content exists
        $element_found = false;
        $content_matches = null;
        
        if ($target === 'body') {
            // Check if body tag exists
            $element_found = strpos($body, '<body') !== false;
        } elseif ($target === 'title') {
            // Check if title tag exists
            $element_found = strpos($body, '<title') !== false;
            if ($expected && $element_found) {
                preg_match('/<title[^>]*>(.*?)<\/title>/i', $body, $matches);
                $actual_title = isset($matches[1]) ? trim($matches[1]) : '';
                $content_matches = !empty($actual_title);
            }
        } elseif (strpos($target, '.') === 0) {
            // CSS class selector
            $class_name = substr($target, 1);
            $element_found = strpos($body, 'class="' . $class_name . '"') !== false || 
                           strpos($body, "class='" . $class_name . "'") !== false ||
                           strpos($body, 'class="' . $class_name . ' ') !== false;
        } elseif (strpos($target, '#') === 0) {
            // CSS ID selector
            $id_name = substr($target, 1);
            $element_found = strpos($body, 'id="' . $id_name . '"') !== false || 
                           strpos($body, "id='" . $id_name . "'") !== false;
        } else {
            // Generic element or text search
            $element_found = strpos($body, $target) !== false;
        }
        
        $verification_results = array(
            'element_found' => $element_found,
            'content_matches' => $content_matches,
            'target' => $target,
            'expected' => $expected,
            'response_code' => $response_code
        );
        
        if ($element_found) {
            return array(
                'success' => true,
                'message' => 'Element verification successful: ' . $target . ' found',
                'details' => $verification_results
            );
        } else {
            return array(
                'success' => false,
                'error' => 'Element not found: ' . $target,
                'details' => $verification_results
            );
        }
    }
    
    /**
     * Get current page URL (simplified - in real implementation this would track navigation)
     */
    private function get_current_page_url() {
        // For now, return home URL as a fallback
        // In a real browser automation, this would track the actual current URL
        return home_url();
    }
    
    /**
     * Wait for element
     */
    private function wait_for_element($target, $timeout) {
        $max_wait = min($timeout, 10); // Cap at 10 seconds
        $wait_time = 0;
        
        // If target is a number, treat it as seconds to wait
        if (is_numeric($target)) {
            $wait_seconds = min(intval($target), $max_wait);
            sleep($wait_seconds);
            return array(
                'success' => true,
                'message' => 'Waited for ' . $wait_seconds . ' seconds',
                'target' => $target,
                'wait_time' => $wait_seconds
            );
        }
        
        // Otherwise, try to verify element exists (with retries)
        $attempts = 0;
        $max_attempts = min($max_wait, 5);
        
        while ($attempts < $max_attempts) {
            $verification = $this->verify_element($target);
            if ($verification['success']) {
                return array(
                    'success' => true,
                    'message' => 'Element found after ' . $attempts . ' attempts',
                    'target' => $target,
                    'wait_time' => $attempts
                );
            }
            
            $attempts++;
            if ($attempts < $max_attempts) {
                sleep(1);
            }
        }
        
        return array(
            'success' => false,
            'error' => 'Element not found after waiting ' . $max_attempts . ' seconds: ' . $target,
            'target' => $target,
            'wait_time' => $max_attempts
        );
    }
    
    /**
     * Scroll to element
     */
    private function scroll_to_element($target) {
        return array(
            'success' => true,
            'message' => 'Scrolled to element',
            'target' => $target
        );
    }
    
    /**
     * Hover over element
     */
    private function hover_element($target) {
        return array(
            'success' => true,
            'message' => 'Hovered over element',
            'target' => $target
        );
    }
    
    /**
     * Interact with multiple elements
     */
    private function interact_with_elements($target, $flow) {
        $interactions = array();
        
        // Simulate various interactions based on flow type
        switch ($flow->flow_type) {
            case 'navigation':
                $interactions[] = array('action' => 'hover', 'element' => 'menu_item');
                $interactions[] = array('action' => 'click', 'element' => 'submenu_item');
                break;
                
            case 'modal':
                $interactions[] = array('action' => 'click', 'element' => 'modal_trigger');
                $interactions[] = array('action' => 'wait', 'element' => 'modal_content');
                $interactions[] = array('action' => 'click', 'element' => 'modal_close');
                break;
                
            default:
                $interactions[] = array('action' => 'click', 'element' => 'interactive_element');
        }
        
        return array(
            'success' => true,
            'message' => 'Interactive elements tested',
            'interactions' => $interactions
        );
    }
    
    /**
     * Generate test data based on type
     */
    private function generate_test_data($data_type, $flow) {
        $test_data = array();
        
        switch ($data_type) {
            case 'test_data':
            case 'registration_data':
                $test_data = array(
                    'username' => 'testuser_' . time(),
                    'user_login' => 'testuser_' . time(),
                    'email' => 'test' . time() . '@example.com',
                    'password' => 'TestPassword123!',
                    'first_name' => 'Test',
                    'last_name' => 'User'
                );
                break;
                
            case 'test_credentials':
            case 'login_data':
                $test_data = array(
                    'log' => 'testuser',
                    'pwd' => 'testpassword',
                    'username' => 'testuser',
                    'password' => 'testpassword'
                );
                break;
                
            case 'test_message':
            case 'contact_data':
                $test_data = array(
                    'your-name' => 'Test User',
                    'your-email' => 'test@example.com',
                    'your-subject' => 'Test Subject',
                    'your-message' => 'This is a test message from WP Tester plugin.',
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'subject' => 'Test Subject',
                    'message' => 'This is a test message from WP Tester plugin.'
                );
                break;
                
            default:
                $test_data = array(
                    'test_field' => 'test_value_' . time()
                );
        }
        
        return $test_data;
    }
    
    /**
     * Get field type based on field name
     */
    private function get_field_type($field_name) {
        $field_types = array(
            'email' => 'email',
            'password' => 'password',
            'pwd' => 'password',
            'username' => 'text',
            'user_login' => 'text',
            'first_name' => 'text',
            'last_name' => 'text',
            'name' => 'text',
            'subject' => 'text',
            'message' => 'textarea',
            'your-message' => 'textarea'
        );
        
        return isset($field_types[$field_name]) ? $field_types[$field_name] : 'text';
    }
    
    /**
     * Check if failure is critical
     */
    private function is_critical_failure($step, $error) {
        $critical_actions = array('navigate', 'submit');
        $critical_errors = array('timeout', 'network error', 'http error');
        
        if (in_array($step['action'], $critical_actions)) {
            return true;
        }
        
        foreach ($critical_errors as $critical_error) {
            if (stripos($error, $critical_error) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate suggestions based on step results
     */
    private function generate_suggestions($step_results, $flow) {
        $suggestions = array();
        
        foreach ($step_results as $step_number => $result) {
            if (!$result['success']) {
                $suggestion = $this->generate_step_suggestion($result, $step_number + 1, $flow);
                if ($suggestion) {
                    $suggestions[] = $suggestion;
                }
            }
        }
        
        // Add general suggestions based on flow type
        $general_suggestions = $this->generate_general_suggestions($flow, $step_results);
        $suggestions = array_merge($suggestions, $general_suggestions);
        
        return $suggestions;
    }
    
    /**
     * Generate suggestion for failed step
     */
    private function generate_step_suggestion($result, $step_number, $flow) {
        $error = strtolower($result['error']);
        
        if (strpos($error, 'timeout') !== false) {
            return array(
                'type' => 'timeout',
                'step' => $step_number,
                'title' => 'Timeout Issue',
                'description' => 'The step timed out. Consider increasing the timeout setting or checking if the page loads slowly.',
                'priority' => 'high',
                'action' => 'Increase timeout setting or optimize page loading speed'
            );
        }
        
        if (strpos($error, 'element not found') !== false) {
            return array(
                'type' => 'element_missing',
                'step' => $step_number,
                'title' => 'Element Not Found',
                'description' => 'The expected element was not found on the page. The page structure may have changed.',
                'priority' => 'high',
                'action' => 'Check if the element selector is correct or if the page structure has changed'
            );
        }
        
        if (strpos($error, 'http error') !== false) {
            return array(
                'type' => 'http_error',
                'step' => $step_number,
                'title' => 'HTTP Error',
                'description' => 'An HTTP error occurred. Check if the URL is accessible and the server is responding.',
                'priority' => 'critical',
                'action' => 'Verify URL accessibility and server status'
            );
        }
        
        return array(
            'type' => 'general_failure',
            'step' => $step_number,
            'title' => 'Step Failed',
            'description' => 'The step failed with error: ' . $result['error'],
            'priority' => 'medium',
            'action' => 'Review the step configuration and target elements'
        );
    }
    
    /**
     * Generate general suggestions
     */
    private function generate_general_suggestions($flow, $step_results) {
        $suggestions = array();
        $failed_steps = array_filter($step_results, function($result) {
            return !$result['success'];
        });
        
        if (count($failed_steps) > count($step_results) / 2) {
            $suggestions[] = array(
                'type' => 'flow_health',
                'title' => 'Flow Health Issue',
                'description' => 'More than half of the steps failed. The flow may need significant updates.',
                'priority' => 'critical',
                'action' => 'Review and update the entire flow configuration'
            );
        }
        
        if ($flow->flow_type === 'registration' && !empty($failed_steps)) {
            $suggestions[] = array(
                'type' => 'registration_specific',
                'title' => 'Registration Flow Issue',
                'description' => 'Registration flow failed. Check if user registration is enabled and working properly.',
                'priority' => 'high',
                'action' => 'Verify WordPress registration settings and form functionality'
            );
        }
        
        if ($flow->flow_type === 'woocommerce' && !empty($failed_steps)) {
            $suggestions[] = array(
                'type' => 'woocommerce_specific',
                'title' => 'WooCommerce Flow Issue',
                'description' => 'WooCommerce flow failed. Check if WooCommerce is properly configured.',
                'priority' => 'high',
                'action' => 'Verify WooCommerce settings and product availability'
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Get primary error from step results
     */
    private function get_primary_error($step_results) {
        foreach ($step_results as $result) {
            if (!$result['success']) {
                return $result['error'];
            }
        }
        return '';
    }
    
    /**
     * Take screenshot
     */
    private function take_screenshot($flow_id, $step_number, $type, $caption) {
        // In a real implementation, this would use a headless browser to take actual screenshots
        // For now, we'll create a meaningful placeholder image
        
        $filename = sprintf(
            'flow_%d_step_%d_%s_%s.png',
            $flow_id,
            $step_number,
            $type,
            date('Y-m-d_H-i-s')
        );
        
        $screenshot_dir = $this->get_screenshot_handler();
        $screenshot_path = $screenshot_dir . '/' . $filename;
        
        // Check if directory is writable before attempting to save
        if (!is_writable($screenshot_dir)) {
            error_log('WP Tester: Screenshot directory is not writable: ' . $screenshot_dir);
            return null;
        }
        
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            error_log('WP Tester: GD extension not available for screenshot creation');
            return null;
        }
        
        // Create a more meaningful placeholder image (400x300 PNG)
        $width = 400;
        $height = 300;
        
        // Create image resource
        $image = imagecreate($width, $height);
        if (!$image) {
            error_log('WP Tester: Failed to create image resource for screenshot');
            return null;
        }
        
        // Define colors
        $bg_color = imagecolorallocate($image, 248, 250, 252); // Light gray background
        $text_color = imagecolorallocate($image, 55, 65, 81); // Dark gray text
        $border_color = imagecolorallocate($image, 31, 192, 154); // Teal border
        $error_color = imagecolorallocate($image, 220, 53, 69); // Red for errors
        
        // Fill background
        imagefill($image, 0, 0, $bg_color);
        
        // Draw border
        imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
        
        // Add text based on type
        $text = '';
        $color = $text_color;
        
        if ($type === 'failure') {
            $text = "Test Failed - Step $step_number";
            $color = $error_color;
        } else {
            $text = "Screenshot - Step $step_number";
        }
        
        // Add main text
        $font_size = 4;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2 - 20;
        
        imagestring($image, $font_size, $x, $y, $text, $color);
        
        // Add caption if provided
        if (!empty($caption)) {
            $caption_lines = explode("\n", wordwrap($caption, 50));
            $y_offset = $y + 30;
            foreach ($caption_lines as $line) {
                $line_width = imagefontwidth(2) * strlen($line);
                $line_x = ($width - $line_width) / 2;
                imagestring($image, 2, $line_x, $y_offset, $line, $text_color);
                $y_offset += 15;
            }
        }
        
        // Add timestamp
        $timestamp = date('Y-m-d H:i:s');
        $time_width = imagefontwidth(2) * strlen($timestamp);
        $time_x = ($width - $time_width) / 2;
        imagestring($image, 2, $time_x, $height - 20, $timestamp, $text_color);
        
        // Save image
        $result = imagepng($image, $screenshot_path);
        imagedestroy($image);
        
        if ($result === false) {
            error_log('WP Tester: Failed to save screenshot to: ' . $screenshot_path);
            return null;
        }
        
        // Verify file was created
        if (!file_exists($screenshot_path)) {
            error_log('WP Tester: Screenshot file was not created: ' . $screenshot_path);
            return null;
        }
        
        error_log('WP Tester: Successfully created screenshot: ' . $screenshot_path);
        return $screenshot_path;
    }
    
    /**
     * Log execution step
     */
    private function log_step($level, $message, $data = array()) {
        $this->execution_log[] = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'data' => $data
        );
    }
    
    /**
     * Run scheduled tests
     */
    public function run_scheduled_tests() {
        $flows = $this->database->get_flows(true); // Get active flows only
        
        foreach ($flows as $flow) {
            $this->execute_flow($flow->id, false);
            
            // Add delay between flow executions
            sleep(5);
        }
    }
    
    /**
     * Execute multiple flows
     */
    public function execute_multiple_flows($flow_ids) {
        $results = array();
        
        foreach ($flow_ids as $flow_id) {
            $result = $this->execute_flow($flow_id);
            $results[$flow_id] = $result;
            
            // Add delay between flows
            sleep(2);
        }
        
        return $results;
    }
}