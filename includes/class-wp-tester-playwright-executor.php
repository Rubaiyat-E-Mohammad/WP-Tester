<?php
/**
 * WP Tester Playwright Executor Class
 * 
 * Handles test execution using Playwright for proper web automation
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Playwright_Executor {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Playwright configuration
     */
    private $config;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
        $this->config = $this->get_playwright_config();
    }
    
    /**
     * Get Playwright configuration
     */
    private function get_playwright_config() {
        return array(
            'headless' => true,
            'viewport' => array('width' => 1280, 'height' => 720),
            'timeout' => 30000,
            'wait_for_selector_timeout' => 10000,
            'navigation_timeout' => 30000,
            'screenshot_path' => wp_upload_dir()['basedir'] . '/wp-tester-screenshots',
            'video_path' => wp_upload_dir()['basedir'] . '/wp-tester-videos',
            'trace_path' => wp_upload_dir()['basedir'] . '/wp-tester-traces'
        );
    }
    
    /**
     * Execute a flow using Playwright
     */
    public function execute_flow($flow_id, $manual_trigger = false) {
        try {
            $flow = $this->database->get_flow($flow_id);
            if (!$flow) {
                throw new Exception('Flow not found');
            }
            
            $steps = json_decode($flow->steps, true) ?: array();
            if (empty($steps)) {
                throw new Exception('No steps found in flow');
            }
            
            // Generate test run ID
            $test_run_id = 'playwright_' . time() . '_' . wp_generate_password(8, false);
            
            // Create Playwright test script
            $test_script = $this->generate_playwright_script($flow, $steps);
            
            // Execute the test
            $result = $this->run_playwright_test($test_script, $test_run_id);
            
            // Save results to database
            $this->save_test_results($flow_id, $test_run_id, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('WP Tester Playwright Execution Error: ' . $e->getMessage());
            // Re-throw the exception to trigger fallback to regular executor
            throw $e;
        }
    }
    
    /**
     * Generate Playwright test script
     */
    private function generate_playwright_script($flow, $steps) {
        $script = "const { test, expect } = require('@playwright/test');\n\n";
        $script .= "test('{$flow->flow_name}', async ({ page }) => {\n";
        $script .= "  // Set viewport\n";
        $script .= "  await page.setViewportSize({ width: {$this->config['viewport']['width']}, height: {$this->config['viewport']['height']} });\n\n";
        
        $step_number = 1;
        foreach ($steps as $step) {
            $script .= $this->generate_step_code($step, $step_number);
            $step_number++;
        }
        
        $script .= "});\n";
        
        return $script;
    }
    
    /**
     * Generate code for a single step
     */
    private function generate_step_code($step, $step_number) {
        $action = $step['action'] ?? '';
        $target = $step['target'] ?? '';
        $value = $step['value'] ?? '';
        $expected_result = $step['expected_result'] ?? '';
        $wait_time = $step['wait_time'] ?? 0;
        
        $code = "  // Step {$step_number}: {$expected_result}\n";
        
        switch ($action) {
            case 'visit':
                $code .= "  await page.goto('{$target}');\n";
                $code .= "  await page.waitForLoadState('networkidle');\n";
                break;
                
            case 'click':
                $code .= "  await page.click('{$target}');\n";
                if ($wait_time > 0) {
                    $code .= "  await page.waitForTimeout({$wait_time} * 1000);\n";
                }
                break;
                
            case 'fill':
                $code .= "  await page.fill('{$target}', '{$value}');\n";
                break;
                
            case 'select':
                $code .= "  await page.selectOption('{$target}', '{$value}');\n";
                break;
                
            case 'wait':
                $code .= "  await page.waitForTimeout({$target} * 1000);\n";
                break;
                
            case 'hover':
                $code .= "  await page.hover('{$target}');\n";
                break;
                
            case 'scroll':
                $code .= "  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));\n";
                break;
                
            case 'keyboard':
                $code .= "  await page.keyboard.press('{$target}');\n";
                break;
                
            case 'upload':
                $code .= "  await page.setInputFiles('{$target}', '{$value}');\n";
                break;
                
            case 'drag':
                $code .= "  await page.dragAndDrop('{$target}', '{$value}');\n";
                break;
                
            default:
                $code .= "  // Unknown action: {$action}\n";
        }
        
        // Add screenshot on failure
        $code .= "  // Take screenshot for verification\n";
        $code .= "  await page.screenshot({ path: '{$this->config['screenshot_path']}/step_{$step_number}_{$action}.png' });\n\n";
        
        return $code;
    }
    
    /**
     * Run Playwright test
     */
    private function run_playwright_test($script, $test_run_id) {
        // Create temporary test file
        $test_file = sys_get_temp_dir() . '/wp-tester-test-' . $test_run_id . '.spec.js';
        file_put_contents($test_file, $script);
        
        error_log('WP Tester: Created Playwright test file: ' . $test_file);
        error_log('WP Tester: Test script content: ' . substr($script, 0, 500));
        
        // Check if Playwright is available
        $playwright_check = shell_exec('npx playwright --version 2>&1');
        error_log('WP Tester: Playwright version check: ' . $playwright_check);
        
        // Run Playwright test with better error handling
        $command = "npx playwright test {$test_file} --reporter=json 2>&1";
        error_log('WP Tester: Running command: ' . $command);
        
        $output = shell_exec($command);
        error_log('WP Tester: Command output: ' . $output);
        
        // Clean up test file
        if (file_exists($test_file)) {
            unlink($test_file);
        }
        
        // Parse results
        return $this->parse_playwright_results($output);
    }
    
    /**
     * Parse Playwright test results
     */
    private function parse_playwright_results($output) {
        // Log the raw output for debugging
        error_log('WP Tester: Playwright raw output: ' . $output);
        
        $results = json_decode($output, true);
        
        if (!$results) {
            error_log('WP Tester: Failed to decode JSON from Playwright output');
            return $this->parse_text_output($output);
        }
        
        if (!isset($results['suites'])) {
            error_log('WP Tester: No suites found in Playwright JSON results');
            return $this->parse_text_output($output);
        }
        
        $total_tests = 0;
        $passed_tests = 0;
        $failed_tests = 0;
        $execution_time = 0;
        
        foreach ($results['suites'] as $suite) {
            foreach ($suite['specs'] as $spec) {
                $total_tests++;
                if ($spec['ok']) {
                    $passed_tests++;
                } else {
                    $failed_tests++;
                }
                $execution_time += $spec['duration'] ?? 0;
            }
        }
        
        return array(
            'success' => $failed_tests === 0,
            'steps_executed' => $total_tests,
            'steps_passed' => $passed_tests,
            'steps_failed' => $failed_tests,
            'execution_time' => $execution_time / 1000, // Convert to seconds
            'raw_output' => $output
        );
    }
    
    /**
     * Parse text output when JSON fails
     */
    private function parse_text_output($output) {
        error_log('WP Tester: Parsing Playwright text output');
        
        // Check for common success/failure patterns in text output
        if (strpos($output, '✓') !== false || strpos($output, 'PASS') !== false || strpos($output, 'passed') !== false) {
            return array(
                'success' => true,
                'error' => '',
                'steps_executed' => 1,
                'steps_passed' => 1,
                'steps_failed' => 0,
                'execution_time' => 1.0,
                'raw_output' => $output
            );
        }
        
        if (strpos($output, '✗') !== false || strpos($output, 'FAIL') !== false || strpos($output, 'failed') !== false) {
            return array(
                'success' => false,
                'error' => 'Test failed - check Playwright output for details',
                'steps_executed' => 1,
                'steps_passed' => 0,
                'steps_failed' => 1,
                'execution_time' => 1.0,
                'raw_output' => $output
            );
        }
        
        // If we can't determine the result, assume failure
        return array(
            'success' => false,
            'error' => 'Unable to parse Playwright output: ' . substr($output, 0, 200),
            'steps_executed' => 0,
            'steps_passed' => 0,
            'steps_failed' => 0,
            'execution_time' => 0,
            'raw_output' => $output
        );
    }
    
    /**
     * Save test results to database
     */
    private function save_test_results($flow_id, $test_run_id, $result) {
        $status = $result['success'] ? 'passed' : 'failed';
        $error_message = $result['success'] ? '' : ($result['error'] ?? 'Test failed');
        
        $this->database->save_test_result(
            $flow_id,
            $test_run_id,
            $status,
            $result['steps_executed'],
            $result['steps_passed'],
            $result['steps_failed'],
            $result['execution_time'],
            $error_message,
            wp_json_encode($result),
            wp_json_encode(array())
        );
    }
    
    /**
     * Check if Playwright is installed
     */
    public function is_playwright_available() {
        // Check if npx is available
        $npx_check = shell_exec('npx --version 2>&1');
        if (strpos($npx_check, 'npm') === false) {
            error_log('WP Tester: npx not available');
            return false;
        }
        
        // Check if Playwright is installed
        $output = shell_exec('npx playwright --version 2>&1');
        error_log('WP Tester: Playwright version check output: ' . $output);
        
        if (strpos($output, 'playwright') === false || strpos($output, 'error') !== false) {
            error_log('WP Tester: Playwright not properly installed');
            return false;
        }
        
        // Check if browsers are installed
        $browser_check = shell_exec('npx playwright install --dry-run 2>&1');
        if (strpos($browser_check, 'browsers') !== false) {
            error_log('WP Tester: Playwright browsers not installed');
            return false;
        }
        
        return true;
    }
    
    /**
     * Install Playwright
     */
    public function install_playwright() {
        $commands = array(
            'npm install -g @playwright/test',
            'npx playwright install'
        );
        
        $results = array();
        foreach ($commands as $command) {
            $output = shell_exec($command . ' 2>&1');
            $results[] = array(
                'command' => $command,
                'output' => $output,
                'success' => strpos($output, 'error') === false
            );
        }
        
        return $results;
    }
    
    /**
     * Get Playwright status
     */
    public function get_status() {
        return array(
            'available' => $this->is_playwright_available(),
            'config' => $this->config,
            'screenshot_path' => $this->config['screenshot_path'],
            'video_path' => $this->config['video_path'],
            'trace_path' => $this->config['trace_path']
        );
    }
    
    /**
     * Execute multiple flows
     */
    public function execute_multiple_flows($flow_ids) {
        $results = array();
        
        foreach ($flow_ids as $flow_id) {
            $result = $this->execute_flow($flow_id);
            $results[$flow_id] = $result;
        }
        
        return $results;
    }
}
