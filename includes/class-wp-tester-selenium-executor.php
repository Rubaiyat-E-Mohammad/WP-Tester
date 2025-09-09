<?php
/**
 * WP Tester Selenium Executor Class
 * 
 * Handles test execution using Selenium WebDriver for proper web automation
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Selenium_Executor {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Selenium configuration
     */
    private $config;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
        $this->config = $this->get_selenium_config();
    }
    
    /**
     * Get Selenium configuration
     */
    private function get_selenium_config() {
        return array(
            'browser' => 'chrome', // chrome, firefox, safari, edge
            'headless' => true,
            'window_size' => array('width' => 1280, 'height' => 720),
            'timeout' => 30,
            'implicit_wait' => 10,
            'page_load_timeout' => 30,
            'screenshot_path' => wp_upload_dir()['basedir'] . '/wp-tester-screenshots',
            'selenium_server_url' => 'http://localhost:4444/wd/hub',
            'chrome_driver_path' => '/usr/local/bin/chromedriver',
            'firefox_driver_path' => '/usr/local/bin/geckodriver'
        );
    }
    
    /**
     * Execute a flow using Selenium
     */
    public function execute_flow($flow_id, $manual_trigger = false) {
        // First check if Selenium is actually available
        if (!$this->is_selenium_available()) {
            throw new Exception('Selenium not available - please install and start Selenium server');
        }
        
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
            $test_run_id = 'selenium_' . time() . '_' . wp_generate_password(8, false);
            
            // Create Selenium test script
            $test_script = $this->generate_selenium_script($flow, $steps);
            
            // Execute the test
            $result = $this->run_selenium_test($test_script, $test_run_id);
            
            // Save results to database
            $this->save_test_results($flow_id, $test_run_id, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('WP Tester Selenium Execution Error: ' . $e->getMessage());
            // Re-throw the exception to trigger fallback
            throw $e;
        }
    }
    
    /**
     * Generate Selenium test script
     */
    private function generate_selenium_script($flow, $steps) {
        $script = "<?php\n";
        $script .= "require_once 'vendor/autoload.php';\n\n";
        $script .= "use Facebook\\WebDriver\\WebDriverBy;\n";
        $script .= "use Facebook\\WebDriver\\WebDriverExpectedCondition;\n";
        $script .= "use Facebook\\WebDriver\\WebDriverKeys;\n";
        $script .= "use Facebook\\WebDriver\\Remote\\RemoteWebDriver;\n";
        $script .= "use Facebook\\WebDriver\\Remote\\DesiredCapabilities;\n\n";
        
        $script .= "// Setup WebDriver\n";
        $script .= "\$host = '{$this->config['selenium_server_url']}';\n";
        $script .= "\$capabilities = DesiredCapabilities::chrome();\n";
        $script .= "\$capabilities->setCapability('chromeOptions', array('args' => array('--headless', '--no-sandbox', '--disable-dev-shm-usage')));\n";
        $script .= "\$driver = RemoteWebDriver::create(\$host, \$capabilities);\n\n";
        
        $script .= "try {\n";
        $script .= "  // Set window size\n";
        $script .= "  \$driver->manage()->window()->setSize(new Facebook\\WebDriver\\WebDriverDimension({$this->config['window_size']['width']}, {$this->config['window_size']['height']}));\n";
        $script .= "  \$driver->manage()->timeouts()->implicitlyWait({$this->config['implicit_wait']});\n";
        $script .= "  \$driver->manage()->timeouts()->pageLoadTimeout({$this->config['page_load_timeout']});\n\n";
        
        $step_number = 1;
        foreach ($steps as $step) {
            $script .= $this->generate_step_code($step, $step_number);
            $step_number++;
        }
        
        $script .= "  echo 'Test completed successfully';\n";
        $script .= "} catch (Exception \$e) {\n";
        $script .= "  echo 'Test failed: ' . \$e->getMessage();\n";
        $script .= "} finally {\n";
        $script .= "  \$driver->quit();\n";
        $script .= "}\n";
        
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
                $code .= "  \$driver->get('{$target}');\n";
                $code .= "  \$driver->wait()->until(WebDriverExpectedCondition::titleContains(''));\n";
                break;
                
            case 'click':
                $code .= "  \$element = \$driver->findElement(WebDriverBy::cssSelector('{$target}'));\n";
                $code .= "  \$element->click();\n";
                if ($wait_time > 0) {
                    $code .= "  sleep({$wait_time});\n";
                }
                break;
                
            case 'fill':
                $code .= "  \$element = \$driver->findElement(WebDriverBy::cssSelector('{$target}'));\n";
                $code .= "  \$element->clear();\n";
                $code .= "  \$element->sendKeys('{$value}');\n";
                break;
                
            case 'select':
                $code .= "  \$element = \$driver->findElement(WebDriverBy::cssSelector('{$target}'));\n";
                $code .= "  \$select = new Facebook\\WebDriver\\WebDriverSelect(\$element);\n";
                $code .= "  \$select->selectByValue('{$value}');\n";
                break;
                
            case 'wait':
                $code .= "  sleep({$target});\n";
                break;
                
            case 'hover':
                $code .= "  \$element = \$driver->findElement(WebDriverBy::cssSelector('{$target}'));\n";
                $code .= "  \$driver->getMouse()->mouseMove(\$element->getCoordinates());\n";
                break;
                
            case 'scroll':
                $code .= "  \$driver->executeScript('window.scrollTo(0, document.body.scrollHeight);');\n";
                break;
                
            case 'keyboard':
                $code .= "  \$driver->getKeyboard()->sendKeys(WebDriverKeys::{$target});\n";
                break;
                
            case 'upload':
                $code .= "  \$element = \$driver->findElement(WebDriverBy::cssSelector('{$target}'));\n";
                $code .= "  \$element->sendKeys('{$value}');\n";
                break;
                
            default:
                $code .= "  // Unknown action: {$action}\n";
        }
        
        // Add screenshot
        $code .= "  // Take screenshot\n";
        $code .= "  \$driver->takeScreenshot('{$this->config['screenshot_path']}/step_{$step_number}_{$action}.png');\n\n";
        
        return $code;
    }
    
    /**
     * Run Selenium test
     */
    private function run_selenium_test($script, $test_run_id) {
        // Create temporary test file
        $test_file = sys_get_temp_dir() . '/wp-tester-test-' . $test_run_id . '.php';
        file_put_contents($test_file, $script);
        
        // Run Selenium test
        $command = "php {$test_file}";
        $output = shell_exec($command . ' 2>&1');
        
        // Clean up test file
        unlink($test_file);
        
        // Parse results
        return $this->parse_selenium_results($output);
    }
    
    /**
     * Parse Selenium test results
     */
    private function parse_selenium_results($output) {
        $success = strpos($output, 'Test completed successfully') !== false;
        $error = $success ? '' : 'Test execution failed';
        
        return array(
            'success' => $success,
            'error' => $error,
            'steps_executed' => 1, // Simplified for now
            'steps_passed' => $success ? 1 : 0,
            'steps_failed' => $success ? 0 : 1,
            'execution_time' => 0, // Would need timing implementation
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
     * Check if Selenium is available
     */
    public function is_selenium_available() {
        // Check if selenium-standalone is installed
        $selenium_check = shell_exec('selenium-standalone --version 2>&1');
        if (strpos($selenium_check, 'selenium-standalone') === false) {
            error_log('WP Tester: selenium-standalone not installed');
            return false;
        }
        
        // Check if Selenium server is running
        $response = wp_remote_get($this->config['selenium_server_url'] . '/status', array('timeout' => 5));
        $is_running = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        if (!$is_running) {
            error_log('WP Tester: Selenium server not running at ' . $this->config['selenium_server_url']);
        }
        
        return $is_running;
    }
    
    /**
     * Get Selenium status
     */
    public function get_status() {
        return array(
            'available' => $this->is_selenium_available(),
            'config' => $this->config,
            'screenshot_path' => $this->config['screenshot_path'],
            'selenium_server_url' => $this->config['selenium_server_url']
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
