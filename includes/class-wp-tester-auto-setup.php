<?php
/**
 * Auto Setup for Browser Automation
 * Automatically installs Playwright and Selenium when needed
 */

class WP_Tester_Auto_Setup {
    
    private $setup_complete_file;
    
    public function __construct() {
        $this->setup_complete_file = WP_TESTER_PLUGIN_DIR . '.testing-setup-complete';
        add_action('admin_init', array($this, 'check_and_setup'));
        add_action('wp_ajax_wp_tester_setup_browsers', array($this, 'ajax_setup_browsers'));
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
     */
    private function is_wp_tester_page() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'wp-tester') !== false;
    }
    
    /**
     * Run auto-setup
     */
    private function run_auto_setup() {
        // Run setup in background
        wp_schedule_single_event(time(), 'wp_tester_auto_setup');
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
        
        if (!file_exists($script_path)) {
            return array(
                'success' => false,
                'message' => 'Setup script not found'
            );
        }
        
        // Change to plugin directory and run setup
        $old_cwd = getcwd();
        chdir($plugin_dir);
        
        $output = array();
        $return_code = 0;
        exec('node scripts/setup-testing.js 2>&1', $output, $return_code);
        
        chdir($old_cwd);
        
        if ($return_code === 0) {
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
        $output = array();
        $return_code = 0;
        exec('npx playwright --version 2>&1', $output, $return_code);
        return $return_code === 0;
    }
    
    /**
     * Check if Selenium is available
     */
    private function check_selenium() {
        $output = array();
        $return_code = 0;
        exec('selenium-standalone --version 2>&1', $output, $return_code);
        return $return_code === 0;
    }
}

// Initialize auto-setup
new WP_Tester_Auto_Setup();
