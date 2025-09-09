<?php
/**
 * WP Tester Automation Suite Class
 * 
 * Generates test automation code for existing flows in various frameworks
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Automation_Suite {
    
    /**
     * Supported frameworks
     */
    private $supported_frameworks = array(
        'playwright' => array(
            'name' => 'Playwright',
            'language' => 'typescript',
            'extension' => 'ts',
            'description' => 'Modern end-to-end testing framework'
        ),
        'selenium' => array(
            'name' => 'Selenium WebDriver',
            'language' => 'java',
            'extension' => 'java',
            'description' => 'Cross-browser automation framework'
        ),
        'cypress' => array(
            'name' => 'Cypress',
            'language' => 'javascript',
            'extension' => 'js',
            'description' => 'Fast, easy and reliable testing framework'
        ),
        'puppeteer' => array(
            'name' => 'Puppeteer',
            'language' => 'javascript',
            'extension' => 'js',
            'description' => 'Headless Chrome automation'
        ),
        'vitest' => array(
            'name' => 'Vitest',
            'language' => 'typescript',
            'extension' => 'ts',
            'description' => 'Fast unit testing framework with ESM support'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX actions
        add_action('wp_ajax_wp_tester_generate_automation_suite', array($this, 'generate_automation_suite'));
        add_action('wp_ajax_wp_tester_download_automation_suite', array($this, 'download_automation_suite'));
    }
    
    /**
     * Get supported frameworks
     */
    public function get_supported_frameworks() {
        return $this->supported_frameworks;
    }
    
    /**
     * Generate automation suite
     */
    public function generate_automation_suite() {
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $framework = sanitize_text_field($_POST['framework'] ?? '');
            $flow_ids = array_filter(array_map('intval', $_POST['flow_ids'] ?? []));
            $include_setup = isset($_POST['include_setup']) ? (bool)$_POST['include_setup'] : true;
            $include_config = isset($_POST['include_config']) ? (bool)$_POST['include_config'] : true;
            
            if (empty($framework) || !array_key_exists($framework, $this->supported_frameworks)) {
                wp_send_json_error(array('message' => __('Invalid framework selected', 'wp-tester')));
                return;
            }
            
            if (empty($flow_ids)) {
                wp_send_json_error(array('message' => __('No flows selected', 'wp-tester')));
                return;
            }
            
            // Get flows from database
            $database = new WP_Tester_Database();
            $flows = array();
            foreach ($flow_ids as $flow_id) {
                $flow = $database->get_flow($flow_id);
                if ($flow) {
                    $flows[] = $flow;
                }
            }
            
            if (empty($flows)) {
                wp_send_json_error(array('message' => __('No valid flows found', 'wp-tester')));
                return;
            }
            
            // Generate test suite using AI
            $suite_data = $this->generate_test_suite_with_ai($framework, $flows, $include_setup, $include_config);
            
            if ($suite_data['success']) {
                // Store the generated suite temporarily
                $suite_id = $this->store_generated_suite($framework, $flows, $suite_data['files']);
                
                wp_send_json_success(array(
                    'message' => sprintf(__('Automation suite generated successfully for %d flows', 'wp-tester'), count($flows)),
                    'suite_id' => $suite_id,
                    'framework' => $framework,
                    'flow_count' => count($flows),
                    'files' => $suite_data['files']
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to generate automation suite: ', 'wp-tester') . $suite_data['error']));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error generating automation suite: ', 'wp-tester') . $e->getMessage()));
        }
    }
    
    /**
     * Generate test suite using AI
     */
    private function generate_test_suite_with_ai($framework, $flows, $include_setup, $include_config) {
        try {
            // Get AI model configuration
            $model = get_option('wp_tester_ai_model', 'fallback-generator');
            $api_key = get_option('wp_tester_ai_api_key', '');
            
            if (empty($model)) {
                return array('success' => false, 'error' => 'No AI model configured');
            }
            
            // Prepare flows data for AI
            $flows_data = array();
            foreach ($flows as $flow) {
                $steps = json_decode($flow->steps, true);
                $flows_data[] = array(
                    'name' => $flow->flow_name,
                    'description' => $flow->flow_description ?? '',
                    'steps' => $steps ?: array()
                );
            }
            
            // Create AI prompt for code generation
            $framework_info = $this->supported_frameworks[$framework];
            $prompt = $this->create_code_generation_prompt($framework, $framework_info, $flows_data, $include_setup, $include_config);
            
            // Call AI API
            $ai_response = $this->call_ai_for_code_generation($model, $api_key, $prompt);
            
            if ($ai_response['success']) {
                // Parse AI response to extract files
                $files = $this->parse_ai_generated_code($ai_response['content'], $framework);
                return array('success' => true, 'files' => $files);
            } else {
                return array('success' => false, 'error' => $ai_response['error']);
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Create AI prompt for code generation
     */
    private function create_code_generation_prompt($framework, $framework_info, $flows_data, $include_setup, $include_config) {
        $prompt = "You are an expert test automation engineer. Generate a complete test automation suite for the {$framework_info['name']} framework using {$framework_info['language']}.
        
REQUIREMENTS:
1. Generate complete, executable test files for each flow
2. Use best practices for {$framework_info['name']}
3. Include proper error handling and assertions
4. Make tests maintainable and readable
5. Use modern syntax and patterns
6. For Java (Selenium): Use Maven project structure with proper package declarations
7. For TypeScript (Playwright): Use proper TypeScript types and modern ES6+ syntax
8. For Vitest: Use modern ESM imports, proper TypeScript types, and Vitest-specific APIs

FRAMEWORK: {$framework_info['name']} ({$framework_info['description']})
LANGUAGE: {$framework_info['language']}
FILE EXTENSION: .{$framework_info['extension']}

FLOWS TO CONVERT:
" . json_encode($flows_data, JSON_PRETTY_PRINT) . "

ADDITIONAL REQUIREMENTS:
- Include setup files: " . ($include_setup ? 'YES' : 'NO') . "
- Include configuration files: " . ($include_config ? 'YES' : 'NO') . "

OUTPUT FORMAT:
Generate each file wrapped in ```filename.extension``` code blocks. Include:

FOR PLAYWRIGHT (TypeScript):
1. Main test files (one per flow) with .ts extension
2. playwright.config.ts configuration file
3. tsconfig.json for TypeScript configuration
4. package.json with Playwright dependencies
5. README.md with setup instructions

FOR SELENIUM (Java):
1. Main test files (one per flow) with .java extension and proper package structure
2. pom.xml for Maven dependencies
3. TestNG or JUnit configuration files
4. README.md with setup instructions

FOR CYPRESS (JavaScript):
1. Main test files (one per flow) with .js extension
2. cypress.config.js configuration file
3. package.json with Cypress dependencies
4. README.md with setup instructions

FOR PUPPETEER (JavaScript):
1. Main test files (one per flow) with .js extension
2. package.json with Puppeteer dependencies
3. README.md with setup instructions

FOR VITEST (TypeScript):
1. Main test files (one per flow) with .test.ts extension
2. vitest.config.ts configuration file
3. tsconfig.json for TypeScript configuration
4. package.json with Vitest and testing dependencies
5. README.md with setup instructions

IMPORTANT: Each file must be wrapped in ```filename.extension``` code blocks. Be thorough and include all necessary files for a complete test suite.";

        return $prompt;
    }
    
    /**
     * Call AI for code generation
     */
    private function call_ai_for_code_generation($model, $api_key, $prompt) {
        // Get model configuration
        $ai_generator = new WP_Tester_AI_Flow_Generator();
        $model_config = $ai_generator->get_model_config($model);
        
        if (!$model_config) {
            return array('success' => false, 'error' => 'Invalid AI model configuration');
        }
        
        // Prepare messages for AI
        $messages = array(
            array('role' => 'system', 'content' => 'You are an expert test automation engineer. Generate complete, executable test automation code.'),
            array('role' => 'user', 'content' => $prompt)
        );
        
        // Use the existing AJAX class to call AI
        $ajax_class = new WP_Tester_Ajax();
        $response = $ajax_class->call_ai_api($model, $api_key, $messages, 0.3, null);
        
        if ($response['success']) {
            return array('success' => true, 'content' => $response['message']);
        } else {
            return array('success' => false, 'error' => $response['error']);
        }
    }
    
    /**
     * Parse AI generated code
     */
    private function parse_ai_generated_code($content, $framework) {
        $files = array();
        
        // Extract files from code blocks
        $pattern = '/```([^`\n]+\.' . $this->supported_frameworks[$framework]['extension'] . ')\s*\n(.*?)\n```/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $filename = trim($match[1]);
            $file_content = trim($match[2]);
            
            if (!empty($filename) && !empty($file_content)) {
                $files[$filename] = $file_content;
            }
        }
        
        // Also check for other common files
        $other_patterns = array(
            '/```(package\.json)\s*\n(.*?)\n```/s',
            '/```(requirements\.txt)\s*\n(.*?)\n```/s',
            '/```(pom\.xml)\s*\n(.*?)\n```/s',
            '/```(tsconfig\.json)\s*\n(.*?)\n```/s',
            '/```(playwright\.config\.ts)\s*\n(.*?)\n```/s',
            '/```(cypress\.config\.js)\s*\n(.*?)\n```/s',
            '/```(vitest\.config\.ts)\s*\n(.*?)\n```/s',
            '/```(README\.md)\s*\n(.*?)\n```/s',
            '/```(config\.[^`\n]+)\s*\n(.*?)\n```/s'
        );
        
        foreach ($other_patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $filename = trim($match[1]);
                $file_content = trim($match[2]);
                
                if (!empty($filename) && !empty($file_content)) {
                    $files[$filename] = $file_content;
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Store generated suite temporarily
     */
    private function store_generated_suite($framework, $flows, $files) {
        $suite_id = 'suite_' . time() . '_' . wp_generate_password(8, false);
        
        $suite_data = array(
            'framework' => $framework,
            'flows' => $flows,
            'files' => $files,
            'created_at' => current_time('mysql'),
            'created_by' => 0
        );
        
        // Store in WordPress options (temporary)
        update_option('wp_tester_automation_suite_' . $suite_id, $suite_data);
        
        // Clean up old suites (keep only last 10)
        $this->cleanup_old_suites();
        
        return $suite_id;
    }
    
    /**
     * Clean up old generated suites
     */
    private function cleanup_old_suites() {
        global $wpdb;
        
        $options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'wp_tester_automation_suite_%' 
             ORDER BY option_id DESC"
        );
        
        if (count($options) > 10) {
            $to_delete = array_slice($options, 10);
            foreach ($to_delete as $option) {
                delete_option($option->option_name);
            }
        }
    }
    
    /**
     * Download automation suite
     */
    public function download_automation_suite() {
        // Debug logging
        error_log('WP Tester: download_automation_suite method called');
        error_log('WP Tester: POST data: ' . print_r($_POST, true));
        
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            error_log('WP Tester: User does not have manage_options permission');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $suite_id = sanitize_text_field($_POST['suite_id'] ?? '');
            error_log('WP Tester: Suite ID: ' . $suite_id);
            
            if (empty($suite_id)) {
                error_log('WP Tester: Empty suite ID');
                wp_send_json_error(array('message' => __('Invalid suite ID', 'wp-tester')));
                return;
            }
            
            // Get suite data
            $suite_data = get_option('wp_tester_automation_suite_' . $suite_id);
            error_log('WP Tester: Suite data found: ' . (empty($suite_data) ? 'NO' : 'YES'));
            
            if (!$suite_data) {
                error_log('WP Tester: Suite not found for ID: ' . $suite_id);
                wp_send_json_error(array('message' => __('Suite not found or expired', 'wp-tester')));
                return;
            }
            
            // Create ZIP file
            error_log('WP Tester: Creating ZIP file...');
            $zip_file = $this->create_zip_file($suite_data);
            error_log('WP Tester: ZIP file result: ' . ($zip_file ? $zip_file : 'FAILED'));
            
            if ($zip_file) {
                wp_send_json_success(array(
                    'download_url' => $zip_file,
                    'filename' => basename($zip_file)
                ));
            } else {
                error_log('WP Tester: Failed to create ZIP file');
                wp_send_json_error(array('message' => __('Failed to create ZIP file', 'wp-tester')));
            }
            
        } catch (Exception $e) {
            error_log('WP Tester: Download exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error downloading suite: ', 'wp-tester') . $e->getMessage()));
        }
    }
    
    /**
     * Create ZIP file from suite data
     */
    private function create_zip_file($suite_data) {
        try {
            error_log('WP Tester: Starting ZIP file creation');
            error_log('WP Tester: Suite data: ' . print_r($suite_data, true));
            
            // Create temporary directory
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/wp-tester-temp/' . uniqid();
            error_log('WP Tester: Temp directory: ' . $temp_dir);
            
            if (!wp_mkdir_p($temp_dir)) {
                error_log('WP Tester: Failed to create temp directory: ' . $temp_dir);
                throw new Exception('Failed to create temporary directory');
            }
            
            // Create framework-specific directory structure
            $framework = $suite_data['framework'];
            $project_dir = $temp_dir . '/wp-tester-automation-suite-' . $framework;
            error_log('WP Tester: Project directory: ' . $project_dir);
            wp_mkdir_p($project_dir);
            
            // Write files
            error_log('WP Tester: Writing ' . count($suite_data['files']) . ' files');
            foreach ($suite_data['files'] as $filename => $content) {
                $file_path = $project_dir . '/' . $filename;
                $file_dir = dirname($file_path);
                
                if (!wp_mkdir_p($file_dir)) {
                    error_log('WP Tester: Failed to create directory for: ' . $filename);
                    throw new Exception('Failed to create directory for ' . $filename);
                }
                
                if (file_put_contents($file_path, $content) === false) {
                    error_log('WP Tester: Failed to write file: ' . $filename);
                    throw new Exception('Failed to write file ' . $filename);
                }
                error_log('WP Tester: Successfully wrote file: ' . $filename);
            }
            
            // Create ZIP file
            $zip_filename = 'wp-tester-automation-suite-' . $framework . '-' . date('Y-m-d-H-i-s') . '.zip';
            $zip_path = $temp_dir . '/' . $zip_filename;
            error_log('WP Tester: ZIP filename: ' . $zip_filename);
            error_log('WP Tester: ZIP path: ' . $zip_path);
            
            if (class_exists('ZipArchive')) {
                error_log('WP Tester: ZipArchive class exists');
                $zip = new ZipArchive();
                $result = $zip->open($zip_path, ZipArchive::CREATE);
                error_log('WP Tester: ZipArchive open result: ' . $result);
                
                if ($result === TRUE) {
                    $this->add_directory_to_zip($zip, $project_dir, '');
                    $zip->close();
                    error_log('WP Tester: ZIP file created successfully');
                } else {
                    error_log('WP Tester: Failed to open ZIP file for writing');
                    throw new Exception('Failed to create ZIP file');
                }
            } else {
                error_log('WP Tester: ZipArchive class not available');
                throw new Exception('ZIP extension not available');
            }
            
            // Move to uploads directory
            $final_path = $upload_dir['path'] . '/' . $zip_filename;
            error_log('WP Tester: Final path: ' . $final_path);
            
            if (rename($zip_path, $final_path)) {
                error_log('WP Tester: ZIP file moved successfully');
                // Clean up temp directory
                $this->delete_directory($temp_dir);
                
                $download_url = $upload_dir['url'] . '/' . $zip_filename;
                error_log('WP Tester: Download URL: ' . $download_url);
                return $download_url;
            } else {
                error_log('WP Tester: Failed to move ZIP file from ' . $zip_path . ' to ' . $final_path);
                throw new Exception('Failed to move ZIP file');
            }
            
        } catch (Exception $e) {
            error_log('WP Tester: Failed to create ZIP file: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add directory to ZIP recursively
     */
    private function add_directory_to_zip($zip, $dir, $zip_path) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            
            $file_path = $dir . '/' . $file;
            $zip_file_path = $zip_path . ($zip_path ? '/' : '') . $file;
            
            if (is_dir($file_path)) {
                $zip->addEmptyDir($zip_file_path);
                $this->add_directory_to_zip($zip, $file_path, $zip_file_path);
            } else {
                $zip->addFile($file_path, $zip_file_path);
            }
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
