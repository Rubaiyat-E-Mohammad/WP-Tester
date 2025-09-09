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
        add_action('wp_ajax_wp_tester_test_automation_suite', array($this, 'test_automation_suite'));
    }
    
    /**
     * Get supported frameworks
     */
    public function get_supported_frameworks() {
        return $this->supported_frameworks;
    }
    
    /**
     * Test automation suite endpoint
     */
    public function test_automation_suite() {
        error_log('WP Tester: test_automation_suite called');
        
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        // Test creating a simple fallback file
        $test_flow = array(
            'name' => 'Test Flow',
            'description' => 'Test flow for automation suite',
            'steps' => array(
                array('action' => 'navigate', 'target' => 'https://example.com', 'description' => 'Navigate to example'),
                array('action' => 'click', 'target' => '#button', 'description' => 'Click button')
            )
        );
        
        $files = $this->create_fallback_files('playwright', array($test_flow));
        
        wp_send_json_success(array(
            'message' => 'Test successful',
            'files_created' => count($files),
            'file_names' => array_keys($files)
        ));
    }
    
    /**
     * Generate automation suite
     */
    public function generate_automation_suite() {
        // Debug logging
        error_log('WP Tester: generate_automation_suite method called');
        error_log('WP Tester: POST data: ' . print_r($_POST, true));
        
        check_ajax_referer('wp_tester_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            error_log('WP Tester: User does not have manage_options permission');
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wp-tester')));
            return;
        }
        
        try {
            $framework = sanitize_text_field($_POST['framework'] ?? '');
            $flow_ids = array_filter(array_map('intval', $_POST['flow_ids'] ?? []));
            $include_setup = isset($_POST['include_setup']) ? (bool)$_POST['include_setup'] : true;
            $include_config = isset($_POST['include_config']) ? (bool)$_POST['include_config'] : true;
            
            error_log('WP Tester: Framework: ' . $framework);
            error_log('WP Tester: Flow IDs: ' . print_r($flow_ids, true));
            error_log('WP Tester: Include setup: ' . ($include_setup ? 'YES' : 'NO'));
            error_log('WP Tester: Include config: ' . ($include_config ? 'YES' : 'NO'));
            
            if (empty($framework) || !array_key_exists($framework, $this->supported_frameworks)) {
                error_log('WP Tester: Invalid framework: ' . $framework);
                wp_send_json_error(array('message' => __('Invalid framework selected', 'wp-tester')));
                return;
            }
            
            if (empty($flow_ids)) {
                error_log('WP Tester: No flow IDs provided');
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
                    error_log('WP Tester: Found flow: ' . $flow->flow_name . ' (ID: ' . $flow_id . ')');
                } else {
                    error_log('WP Tester: Flow not found for ID: ' . $flow_id);
                }
            }
            
            error_log('WP Tester: Total flows found: ' . count($flows));
            
            if (empty($flows)) {
                error_log('WP Tester: No valid flows found');
                wp_send_json_error(array('message' => __('No valid flows found', 'wp-tester')));
                return;
            }
            
            // Generate test suite using AI
            error_log('WP Tester: Starting AI generation...');
            $suite_data = $this->generate_test_suite_with_ai($framework, $flows, $include_setup, $include_config);
            error_log('WP Tester: AI generation result: ' . print_r($suite_data, true));
            
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
            error_log('WP Tester: generate_test_suite_with_ai called');
            error_log('WP Tester: Framework: ' . $framework);
            error_log('WP Tester: Flows count: ' . count($flows));
            
            // Get AI model configuration
            $model = get_option('wp_tester_ai_model', 'fallback-generator');
            $api_key = get_option('wp_tester_ai_api_key', '');
            
            error_log('WP Tester: AI Model: ' . $model);
            error_log('WP Tester: API Key present: ' . (empty($api_key) ? 'NO' : 'YES'));
            
            if (empty($model)) {
                error_log('WP Tester: No AI model configured');
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
            
            // For now, let's use fallback files directly to test the system
            error_log('WP Tester: Using fallback files for testing');
            $files = $this->create_fallback_files($framework, $flows_data);
            error_log('WP Tester: Fallback files created: ' . count($files) . ' files');
            
            return array('success' => true, 'files' => $files);
            
            // TODO: Re-enable AI generation once we confirm the system works
            /*
            // Call AI API
            $ai_response = $this->call_ai_for_code_generation($model, $api_key, $prompt);
            
            if ($ai_response['success']) {
                // Parse AI response to extract files
                $files = $this->parse_ai_generated_code($ai_response['content'], $framework);
                
                // If no files were parsed, create a basic fallback
                if (empty($files)) {
                    error_log('WP Tester: No files parsed from AI response, creating fallback files');
                    $files = $this->create_fallback_files($framework, $flows_data);
                }
                
                return array('success' => true, 'files' => $files);
            } else {
                return array('success' => false, 'error' => $ai_response['error']);
            }
            */
            
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
     * Create fallback files when AI parsing fails
     */
    private function create_fallback_files($framework, $flows_data) {
        $files = array();
        $framework_info = $this->supported_frameworks[$framework];
        
        // Create a basic test file for each flow
        foreach ($flows_data as $index => $flow) {
            $test_filename = 'test_' . ($index + 1) . '.' . $framework_info['extension'];
            
            switch ($framework) {
                case 'playwright':
                    $files[$test_filename] = $this->create_playwright_fallback($flow);
                    break;
                case 'selenium':
                    $files[$test_filename] = $this->create_selenium_fallback($flow);
                    break;
                case 'cypress':
                    $files[$test_filename] = $this->create_cypress_fallback($flow);
                    break;
                case 'puppeteer':
                    $files[$test_filename] = $this->create_puppeteer_fallback($flow);
                    break;
                case 'vitest':
                    $files[$test_filename] = $this->create_vitest_fallback($flow);
                    break;
            }
        }
        
        // Add configuration files
        switch ($framework) {
            case 'playwright':
                $files['playwright.config.ts'] = $this->get_playwright_config();
                $files['package.json'] = $this->get_playwright_package_json();
                break;
            case 'selenium':
                $files['pom.xml'] = $this->get_selenium_pom_xml();
                break;
            case 'cypress':
                $files['cypress.config.js'] = $this->get_cypress_config();
                $files['package.json'] = $this->get_cypress_package_json();
                break;
            case 'puppeteer':
                $files['package.json'] = $this->get_puppeteer_package_json();
                break;
            case 'vitest':
                $files['vitest.config.ts'] = $this->get_vitest_config();
                $files['package.json'] = $this->get_vitest_package_json();
                break;
        }
        
        // Add README
        $files['README.md'] = $this->get_readme_content($framework);
        
        return $files;
    }
    
    /**
     * Create Playwright fallback test
     */
    private function create_playwright_fallback($flow) {
        $steps = $flow['steps'] ?? array();
        $test_steps = '';
        
        foreach ($steps as $step) {
            $action = $step['action'] ?? 'navigate';
            $target = $step['target'] ?? '';
            $value = $step['value'] ?? '';
            
            switch ($action) {
                case 'navigate':
                    $test_steps .= "  await page.goto('{$target}');\n";
                    break;
                case 'click':
                    $test_steps .= "  await page.click('{$target}');\n";
                    break;
                case 'fill':
                    $test_steps .= "  await page.fill('{$target}', '{$value}');\n";
                    break;
                case 'wait':
                    $test_steps .= "  await page.waitForSelector('{$target}');\n";
                    break;
                case 'assert':
                    $test_steps .= "  await expect(page.locator('{$target}')).toContainText('{$value}');\n";
                    break;
            }
        }
        
        return "import { test, expect } from '@playwright/test';

test('{$flow['name']}', async ({ page }) => {
{$test_steps}
});";
    }
    
    /**
     * Create Selenium fallback test
     */
    private function create_selenium_fallback($flow) {
        $steps = $flow['steps'] ?? array();
        $test_steps = '';
        
        foreach ($steps as $step) {
            $action = $step['action'] ?? 'navigate';
            $target = $step['target'] ?? '';
            $value = $step['value'] ?? '';
            
            switch ($action) {
                case 'navigate':
                    $test_steps .= "        driver.get(\"{$target}\");\n";
                    break;
                case 'click':
                    $test_steps .= "        driver.findElement(By.css(\"{$target}\")).click();\n";
                    break;
                case 'fill':
                    $test_steps .= "        driver.findElement(By.css(\"{$target}\")).sendKeys(\"{$value}\");\n";
                    break;
                case 'wait':
                    $test_steps .= "        WebDriverWait wait = new WebDriverWait(driver, 10);\n";
                    $test_steps .= "        wait.until(ExpectedConditions.presenceOfElementLocated(By.css(\"{$target}\")));\n";
                    break;
                case 'assert':
                    $test_steps .= "        assert driver.findElement(By.css(\"{$target}\")).getText().contains(\"{$value}\");\n";
                    break;
            }
        }
        
        return "import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.By;
import org.openqa.selenium.chrome.ChromeDriver;
import org.openqa.selenium.support.ui.WebDriverWait;
import org.openqa.selenium.support.ui.ExpectedConditions;
import org.testng.annotations.Test;
import org.testng.annotations.BeforeMethod;
import org.testng.annotations.AfterMethod;
import static org.testng.Assert.*;

public class {$this->sanitize_class_name($flow['name'])} {
    private WebDriver driver;
    
    @BeforeMethod
    public void setUp() {
        driver = new ChromeDriver();
    }
    
    @Test
    public void test{$this->sanitize_class_name($flow['name'])}() {
{$test_steps}
    }
    
    @AfterMethod
    public void tearDown() {
        if (driver != null) {
            driver.quit();
        }
    }
}";
    }
    
    /**
     * Create Cypress fallback test
     */
    private function create_cypress_fallback($flow) {
        $steps = $flow['steps'] ?? array();
        $test_steps = '';
        
        foreach ($steps as $step) {
            $action = $step['action'] ?? 'navigate';
            $target = $step['target'] ?? '';
            $value = $step['value'] ?? '';
            
            switch ($action) {
                case 'navigate':
                    $test_steps .= "    cy.visit('{$target}');\n";
                    break;
                case 'click':
                    $test_steps .= "    cy.get('{$target}').click();\n";
                    break;
                case 'fill':
                    $test_steps .= "    cy.get('{$target}').type('{$value}');\n";
                    break;
                case 'wait':
                    $test_steps .= "    cy.get('{$target}').should('be.visible');\n";
                    break;
                case 'assert':
                    $test_steps .= "    cy.get('{$target}').should('contain', '{$value}');\n";
                    break;
            }
        }
        
        return "describe('{$flow['name']}', () => {
  it('should complete the test flow', () => {
{$test_steps}
  });
});";
    }
    
    /**
     * Create Puppeteer fallback test
     */
    private function create_puppeteer_fallback($flow) {
        $steps = $flow['steps'] ?? array();
        $test_steps = '';
        
        foreach ($steps as $step) {
            $action = $step['action'] ?? 'navigate';
            $target = $step['target'] ?? '';
            $value = $step['value'] ?? '';
            
            switch ($action) {
                case 'navigate':
                    $test_steps .= "  await page.goto('{$target}');\n";
                    break;
                case 'click':
                    $test_steps .= "  await page.click('{$target}');\n";
                    break;
                case 'fill':
                    $test_steps .= "  await page.type('{$target}', '{$value}');\n";
                    break;
                case 'wait':
                    $test_steps .= "  await page.waitForSelector('{$target}');\n";
                    break;
                case 'assert':
                    $test_steps .= "  const text = await page.\$eval('{$target}', el => el.textContent);\n";
                    $test_steps .= "  expect(text).toContain('{$value}');\n";
                    break;
            }
        }
        
        return "const puppeteer = require('puppeteer');

describe('{$flow['name']}', () => {
  let browser;
  let page;
  
  beforeAll(async () => {
    browser = await puppeteer.launch();
    page = await browser.newPage();
  });
  
  afterAll(async () => {
    await browser.close();
  });
  
  test('should complete the test flow', async () => {
{$test_steps}
  });
});";
    }
    
    /**
     * Create Vitest fallback test
     */
    private function create_vitest_fallback($flow) {
        $steps = $flow['steps'] ?? array();
        $test_steps = '';
        
        foreach ($steps as $step) {
            $action = $step['action'] ?? 'navigate';
            $target = $step['target'] ?? '';
            $value = $step['value'] ?? '';
            
            switch ($action) {
                case 'navigate':
                    $test_steps .= "    await page.goto('{$target}');\n";
                    break;
                case 'click':
                    $test_steps .= "    await page.click('{$target}');\n";
                    break;
                case 'fill':
                    $test_steps .= "    await page.fill('{$target}', '{$value}');\n";
                    break;
                case 'wait':
                    $test_steps .= "    await page.waitForSelector('{$target}');\n";
                    break;
                case 'assert':
                    $test_steps .= "    await expect(page.locator('{$target}')).toContainText('{$value}');\n";
                    break;
            }
        }
        
        return "import { test, expect } from 'vitest';
import { chromium } from 'playwright';

test('{$flow['name']}', async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
{$test_steps}
  } finally {
    await browser.close();
  }
});";
    }
    
    /**
     * Sanitize class name for Java
     */
    private function sanitize_class_name($name) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }
    
    /**
     * Get configuration files
     */
    private function get_playwright_config() {
        return "import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});";
    }
    
    private function get_playwright_package_json() {
        return '{
  "name": "wp-tester-playwright-suite",
  "version": "1.0.0",
  "description": "Generated test automation suite",
  "scripts": {
    "test": "playwright test",
    "test:ui": "playwright test --ui"
  },
  "devDependencies": {
    "@playwright/test": "^1.40.0"
  }
}';
    }
    
    private function get_selenium_pom_xml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">
    <modelVersion>4.0.0</modelVersion>
    
    <groupId>com.wptester</groupId>
    <artifactId>automation-suite</artifactId>
    <version>1.0.0</version>
    
    <properties>
        <maven.compiler.source>11</maven.compiler.source>
        <maven.compiler.target>11</maven.compiler.target>
    </properties>
    
    <dependencies>
        <dependency>
            <groupId>org.seleniumhq.selenium</groupId>
            <artifactId>selenium-java</artifactId>
            <version>4.15.0</version>
        </dependency>
        <dependency>
            <groupId>org.testng</groupId>
            <artifactId>testng</artifactId>
            <version>7.8.0</version>
        </dependency>
    </dependencies>
</project>';
    }
    
    private function get_cypress_config() {
        return "const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    supportFile: false,
    specPattern: 'cypress/e2e/**/*.cy.js',
  },
});";
    }
    
    private function get_cypress_package_json() {
        return '{
  "name": "wp-tester-cypress-suite",
  "version": "1.0.0",
  "description": "Generated test automation suite",
  "scripts": {
    "test": "cypress run",
    "test:open": "cypress open"
  },
  "devDependencies": {
    "cypress": "^13.6.0"
  }
}';
    }
    
    private function get_puppeteer_package_json() {
        return '{
  "name": "wp-tester-puppeteer-suite",
  "version": "1.0.0",
  "description": "Generated test automation suite",
  "scripts": {
    "test": "jest"
  },
  "devDependencies": {
    "puppeteer": "^21.5.0",
    "jest": "^29.7.0"
  }
}';
    }
    
    private function get_vitest_config() {
        return "import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'node',
  },
});";
    }
    
    private function get_vitest_package_json() {
        return '{
  "name": "wp-tester-vitest-suite",
  "version": "1.0.0",
  "description": "Generated test automation suite",
  "scripts": {
    "test": "vitest",
    "test:run": "vitest run"
  },
  "devDependencies": {
    "vitest": "^1.0.0",
    "playwright": "^1.40.0"
  }
}';
    }
    
    private function get_readme_content($framework) {
        $framework_info = $this->supported_frameworks[$framework];
        
        return "# WP Tester Automation Suite - {$framework_info['name']}

This is a generated test automation suite for {$framework_info['name']}.

## Setup

1. Install dependencies:
   ```bash
   npm install
   ```

2. Run tests:
   ```bash
   npm test
   ```

## Framework: {$framework_info['name']}
- Language: {$framework_info['language']}
- Description: {$framework_info['description']}

## Generated Files

This suite contains test files generated from your WordPress flows.

## Notes

- Update the base URLs in configuration files to match your environment
- Modify selectors and assertions as needed for your specific site
- Add additional test data and scenarios as required

Generated by WP Tester Plugin
";
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
            
            // Check if suite data has files
            if (empty($suite_data['files'])) {
                error_log('WP Tester: No files in suite data');
                wp_send_json_error(array('message' => __('No files generated for the automation suite', 'wp-tester')));
                return;
            }
            
            error_log('WP Tester: Suite has ' . count($suite_data['files']) . ' files');
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
                    error_log('WP Tester: ZIP file opened successfully, adding files...');
                    $this->add_directory_to_zip($zip, $project_dir, '');
                    $zip->close();
                    error_log('WP Tester: ZIP file created successfully');
                    
                    // Verify ZIP file was created
                    if (!file_exists($zip_path)) {
                        error_log('WP Tester: ZIP file does not exist after creation');
                        throw new Exception('ZIP file was not created');
                    }
                    
                    $zip_size = filesize($zip_path);
                    error_log('WP Tester: ZIP file size: ' . $zip_size . ' bytes');
                    
                    if ($zip_size === 0) {
                        error_log('WP Tester: ZIP file is empty');
                        throw new Exception('ZIP file is empty');
                    }
                } else {
                    error_log('WP Tester: Failed to open ZIP file for writing. Error code: ' . $result);
                    throw new Exception('Failed to create ZIP file. Error code: ' . $result);
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
