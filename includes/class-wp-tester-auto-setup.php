<?php
/**
 * Auto Setup for Browser Automation
 * Automatically installs Playwright and Selenium when needed
 * 
 * @package WP_Tester
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// @phpstan-ignore-file - WordPress functions not available during static analysis


class WP_Tester_Auto_Setup {
    
    private $setup_complete_file;
    
    public function __construct() {
        $this->setup_complete_file = WP_TESTER_PLUGIN_DIR . '.testing-setup-complete';
        add_action('admin_init', array($this, 'check_and_setup'));
        add_action('wp_ajax_wp_tester_setup_browsers', array($this, 'ajax_setup_browsers'));
        add_action('wp_tester_auto_setup', array($this, 'execute_auto_setup'));
    }
    
    /**
     * Check if browser automation is set up and auto-setup if needed
     */
    public function check_and_setup() {
        // Only run for admin users
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if setup is already complete
        if ($this->is_setup_complete()) {
            return;
        }
        
        // Check if we're on a WP Tester admin page
        if (!$this->is_wp_tester_page()) {
            return;
        }
        
        // Auto-setup in background
        $this->run_auto_setup();
    }
    
    /**
     * Check if setup is complete
     */
    private function is_setup_complete() {
        if (!file_exists($this->setup_complete_file)) {
            return false;
        }
        
        // Check if setup is recent (within last 24 hours)
        $setup_time = filemtime($this->setup_complete_file);
        return (time() - $setup_time) < 86400; // 24 hours
    }
    
    /**
     * Check if we're on a WP Tester admin page
     * 
     * @return bool
     */
    private function is_wp_tester_page() {
        // Check if we're in admin area and have the function available
        if (!function_exists('is_admin') || 
            // @phpstan-ignore-next-line - WordPress function
            !is_admin() || !function_exists('get_current_screen')) {
            return false;
        }
        
        /** @var WP_Screen|null $screen */
        // @phpstan-ignore-next-line - WordPress function
        $screen = get_current_screen();
        return $screen && isset($screen->id) && strpos($screen->id, 'wp-tester') !== false;
    }
    
    /**
     * Run auto-setup
     */
    private function run_auto_setup() {
        // Run setup in background
        if (!wp_next_scheduled('wp_tester_auto_setup')) {
            wp_schedule_single_event(time(), 'wp_tester_auto_setup');
        }
    }
    
    /**
     * Execute auto-setup (called by WordPress cron)
     */
    public function execute_auto_setup() {
        $this->run_setup_script();
    }
    
    /**
     * AJAX handler for manual setup
     */
    public function ajax_setup_browsers() {
        check_ajax_referer('wp_tester_setup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->run_setup_script();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Run the setup script
     */
    private function run_setup_script() {
        $plugin_dir = WP_TESTER_PLUGIN_DIR;
        $script_path = $plugin_dir . 'scripts/setup-testing.js';
        
        // Validate paths and permissions
        if (!file_exists($script_path)) {
            error_log('WP Tester: Setup script not found at ' . $script_path);
            return array(
                'success' => false,
                'message' => 'Setup script not found'
            );
        }
        
        if (!is_readable($script_path)) {
            error_log('WP Tester: Setup script not readable');
            return array(
                'success' => false,
                'message' => 'Setup script not readable'
            );
        }
        
        // Check if Node.js is available (try multiple paths)
        $node_paths = array(
            'node',
            '/usr/local/bin/node',
            '/usr/bin/node',
            '/opt/homebrew/bin/node',
            getenv('HOME') . '/.nvm/versions/node/*/bin/node'
        );
        
        $node_found = false;
        $node_path = '';
        
        foreach ($node_paths as $path) {
            if (strpos($path, '*') !== false) {
                // Handle glob patterns
                $glob_paths = glob($path);
                if (!empty($glob_paths)) {
                    $node_path = $glob_paths[0];
                    $node_found = true;
                    break;
                }
            } else {
                $node_check = array();
                $node_return = 0;
                exec($path . ' --version 2>&1', $node_check, $node_return);
                
                if ($node_return === 0) {
                    $node_path = $path;
                    $node_found = true;
                    break;
                }
            }
        }
        
        if (!$node_found) {
            error_log('WP Tester: Node.js not found in any common paths');
            return array(
                'success' => false,
                'message' => 'Node.js is not installed or not available in PATH. Please install Node.js or add it to your system PATH.'
            );
        }
        
        // Change to plugin directory and run setup with timeout
        $old_cwd = getcwd();
        if ($old_cwd === false) {
            error_log('WP Tester: Cannot get current working directory');
            return array(
                'success' => false,
                'message' => 'Cannot access current directory'
            );
        }
        
        if (!is_string($plugin_dir) || !chdir($plugin_dir)) {
            error_log('WP Tester: Cannot change to plugin directory');
            return array(
                'success' => false,
                'message' => 'Cannot access plugin directory'
            );
        }
        
        $output = array();
        $return_code = 0;
        
        // Try to run the setup script, but if that fails, run npm commands directly
        $command = 'timeout 300 ' . escapeshellarg($node_path) . ' scripts/setup-testing.js 2>&1';
        if (PHP_OS_FAMILY === 'Windows') {
            $command = escapeshellarg($node_path) . ' scripts/setup-testing.js 2>&1';
        }
        
        exec($command, $output, $return_code);
        
        // If the script failed, try running npm commands directly
        if ($return_code !== 0) {
            error_log('WP Tester: Setup script failed, trying direct npm commands');
            
            // Try to find npm
            $npm_paths = array(
                'npm',
                '/usr/local/bin/npm',
                '/usr/bin/npm',
                '/opt/homebrew/bin/npm',
                getenv('HOME') . '/.nvm/versions/node/*/bin/npm'
            );
            
            $npm_found = false;
            $npm_path = '';
            
            foreach ($npm_paths as $path) {
                if (strpos($path, '*') !== false) {
                    $glob_paths = glob($path);
                    if (!empty($glob_paths)) {
                        $npm_path = $glob_paths[0];
                        $npm_found = true;
                        break;
                    }
                } else {
                    $npm_check = array();
                    $npm_return = 0;
                    exec($path . ' --version 2>&1', $npm_check, $npm_return);
                    
                    if ($npm_return === 0) {
                        $npm_path = $path;
                        $npm_found = true;
                        break;
                    }
                }
            }
            
            if ($npm_found) {
                // Run npm install and playwright install directly
                $commands = array(
                    escapeshellarg($npm_path) . ' install 2>&1',
                    escapeshellarg($node_path) . ' node_modules/.bin/playwright install 2>&1'
                );
                
                $all_output = array();
                $all_success = true;
                
                foreach ($commands as $cmd) {
                    $cmd_output = array();
                    $cmd_return = 0;
                    exec($cmd, $cmd_output, $cmd_return);
                    
                    $all_output = array_merge($all_output, $cmd_output);
                    
                    if ($cmd_return !== 0) {
                        $all_success = false;
                    }
                }
                
                $output = $all_output;
                $return_code = $all_success ? 0 : 1;
            }
        }
        
        // Restore original directory
        chdir($old_cwd);
        
        // Log the output for debugging
        error_log('WP Tester: Setup script output: ' . implode("\n", $output));
        
        if ($return_code === 0) {
            // Create completion marker
            $this->mark_setup_complete();
            return array(
                'success' => true,
                'message' => 'Browser automation setup completed successfully!'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Setup failed: ' . implode("\n", $output)
            );
        }
    }
    
    /**
     * Mark setup as complete
     */
    private function mark_setup_complete() {
        $content = date('Y-m-d H:i:s') . "\n";
        $content .= "Browser automation setup completed\n";
        $content .= "Playwright: " . ($this->check_playwright() ? 'Available' : 'Not Available') . "\n";
        $content .= "Selenium: " . ($this->check_selenium() ? 'Available' : 'Not Available') . "\n";
        
        file_put_contents($this->setup_complete_file, $content);
    }
    
    /**
     * Get setup status
     */
    public function get_setup_status() {
        $playwright_available = $this->check_playwright();
        $selenium_available = $this->check_selenium();
        
        return array(
            'playwright' => $playwright_available,
            'selenium' => $selenium_available,
            'setup_complete' => $this->is_setup_complete()
        );
    }
    
    /**
     * Check if Playwright is available
     */
    private function check_playwright() {
        try {
            $output = array();
            $return_code = 0;
            exec('npx playwright --version 2>&1', $output, $return_code);
            
            if ($return_code === 0) {
                // Additional check: verify browsers are installed
                $browser_check = array();
                $browser_return = 0;
                exec('npx playwright install --dry-run 2>&1', $browser_check, $browser_return);
                
                // If dry-run shows no browsers to install, they're already installed
                $output_text = implode(' ', $browser_check);
                return strpos($output_text, 'browsers') === false;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('WP Tester: Playwright check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if Selenium is available
     */
    private function check_selenium() {
        try {
            $output = array();
            $return_code = 0;
            exec('selenium-standalone --version 2>&1', $output, $return_code);
            
            if ($return_code === 0) {
                // Additional check: verify Selenium server can start
                $server_check = array();
                $server_return = 0;
                exec('selenium-standalone start --detach --version 2>&1', $server_check, $server_return);
                return $server_return === 0;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('WP Tester: Selenium check failed: ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize auto-setup
new WP_Tester_Auto_Setup();
