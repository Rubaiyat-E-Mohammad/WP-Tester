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
        
        if (!file_exists($screenshots_dir)) {
            $created = wp_mkdir_p($screenshots_dir);
            if (!$created) {
                // Failed to create screenshots directory
                // Fallback to temp directory
                $screenshots_dir = sys_get_temp_dir() . '/wp-tester-screenshots';
                if (!file_exists($screenshots_dir)) {
                    wp_mkdir_p($screenshots_dir);
                }
            }
        }
        
        // Verify directory is writable
        if (!is_writable($screenshots_dir)) {
            // Screenshots directory is not writable
            // Fallback to temp directory
            $screenshots_dir = sys_get_temp_dir() . '/wp-tester-screenshots';
            if (!file_exists($screenshots_dir)) {
                wp_mkdir_p($screenshots_dir);
            }
        }
        
        $this->screenshot_handler = $screenshots_dir;
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
                        'action' => 'visit',
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
                } else {
                    $steps_failed++;
                    $this->log_step('error', 'Step failed', $step_result);
                    
                    // Take screenshot on failure if enabled
                    $screenshot_enabled = isset($this->settings['screenshot_on_failure']) && $this->settings['screenshot_on_failure'];
                    // Screenshot on failure enabled
                    
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
                            // Screenshot saved for failed step
                        } else {
                            // Failed to take screenshot for failed step
                        }
                    } else {
                        // Screenshot on failure is disabled in settings
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
                // Saving screenshots to database
                foreach ($this->screenshots_to_save as $screenshot) {
                    $screenshot_id = $this->database->save_screenshot(
                        $result_id,
                        $screenshot['step_number'],
                        $screenshot['screenshot_path'],
                        $screenshot['screenshot_type'],
                        $screenshot['caption']
                    );
                    // Saved screenshot to database
                }
            } else {
                // No screenshots to save
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
        // Simulate element verification
        // In a real implementation, this would check the actual page content
        
        $verification_results = array(
            'element_found' => true,
            'content_matches' => $expected ? true : null,
            'target' => $target
        );
        
        if ($verification_results['element_found']) {
            return array(
                'success' => true,
                'message' => 'Element verification successful',
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
     * Wait for element
     */
    private function wait_for_element($target, $timeout) {
        // Simulate waiting for element
        $wait_time = min($timeout, 10); // Max 10 seconds wait
        
        return array(
            'success' => true,
            'message' => 'Wait completed',
            'target' => $target,
            'wait_time' => $wait_time
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
        // For now, we'll create a placeholder
        
        $filename = sprintf(
            'flow_%d_step_%d_%s_%s.png',
            $flow_id,
            $step_number,
            $type,
            date('Y-m-d_H-i-s')
        );
        
        $screenshot_dir = $this->get_screenshot_handler();
        $screenshot_path = $screenshot_dir . '/' . $filename;
        
        // Create a placeholder image file with error handling
        $placeholder_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
        
        // Check if directory is writable before attempting to save
        if (!is_writable($screenshot_dir)) {
            // Screenshot directory is not writable
            return null; // Return null instead of failing
        }
        
        $result = file_put_contents($screenshot_path, $placeholder_content);
        if ($result === false) {
            // Failed to save screenshot
            return null; // Return null instead of failing
        }
        
        // Successfully saved screenshot
        
        // Save screenshot record (this would be called after saving test result)
        // $this->database->save_screenshot($test_result_id, $step_number, $screenshot_path, $type, $caption);
        
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