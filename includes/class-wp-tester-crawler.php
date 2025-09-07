<?php
/**
 * WP Tester Crawler Class
 * 
 * Crawls the WordPress site to detect pages, posts, custom post types, and interactive elements
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_Crawler {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Crawl settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
        $this->settings = get_option('wp_tester_settings', array());
        
        // Hook into WordPress actions (manual only - no automatic crawling)
        add_action('wp_tester_daily_crawl', array($this, 'run_full_crawl'));
        // Note: Removed automatic post crawling hooks - user must manually trigger crawls
    }
    
    /**
     * Run full site crawl
     */
    public function run_full_crawl() {
        $start_time = microtime(true);
        
        try {
            // Get all public post types
            $post_types = get_post_types(array('public' => true), 'names');
            
            $crawled_urls = array();
            $discovered_flows = array();
            
            // Crawl homepage
            $home_url = home_url('/');
            $this->crawl_url($home_url, 'homepage', $crawled_urls, $discovered_flows);
            
            // Crawl posts and pages
            foreach ($post_types ?: [] as $post_type) {
                $this->crawl_post_type($post_type, $crawled_urls, $discovered_flows);
            }
            
            // Crawl archives
            $this->crawl_archives($crawled_urls, $discovered_flows);
            
            // Crawl special WordPress pages
            $this->crawl_special_pages($crawled_urls, $discovered_flows);
            
            // Discover and save flows
            $saved_flows_count = $this->process_discovered_flows($discovered_flows);
            
            $execution_time = microtime(true) - $start_time;
            
            // Log crawl completion
            error_log(sprintf(
                'WP Tester: Full crawl completed. Crawled %d URLs, saved %d flows in %.2f seconds.',
                count($crawled_urls),
                $saved_flows_count,
                $execution_time
            ));
            
            return array(
                'success' => true,
                'crawled_count' => count($crawled_urls),
                'execution_time' => $execution_time,
                'discovered_flows' => $saved_flows_count
            );
            
        } catch (Exception $e) {
            error_log('WP Tester Crawl Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Crawl specific post type
     */
    private function crawl_post_type($post_type, &$crawled_urls, &$discovered_flows) {
        $max_pages = isset($this->settings['max_pages_per_crawl']) ? $this->settings['max_pages_per_crawl'] : 100;
        
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'numberposts' => $max_pages,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($posts ?: [] as $post) {
            $url = get_permalink($post->ID);
            if ($url && !in_array($url, $crawled_urls)) {
                $this->crawl_url($url, $post_type, $crawled_urls, $discovered_flows, $post);
            }
        }
    }
    
    /**
     * Crawl archives
     */
    private function crawl_archives(&$crawled_urls, &$discovered_flows) {
        // Category archives
        $categories = get_categories(array('hide_empty' => true));
        foreach ($categories ?: [] as $category) {
            $url = get_category_link($category->term_id);
            if ($url && !in_array($url, $crawled_urls)) {
                $this->crawl_url($url, 'category_archive', $crawled_urls, $discovered_flows);
            }
        }
        
        // Tag archives
        $tags = get_tags(array('hide_empty' => true));
        foreach ($tags ?: [] as $tag) {
            $url = get_tag_link($tag->term_id);
            if ($url && !in_array($url, $crawled_urls)) {
                $this->crawl_url($url, 'tag_archive', $crawled_urls, $discovered_flows);
            }
        }
        
        // Author archives
        $authors = get_users(array('who' => 'authors'));
        foreach ($authors ?: [] as $author) {
            $url = get_author_posts_url($author->ID);
            if ($url && !in_array($url, $crawled_urls)) {
                $this->crawl_url($url, 'author_archive', $crawled_urls, $discovered_flows);
            }
        }
    }
    
    /**
     * Crawl special WordPress pages
     */
    private function crawl_special_pages(&$crawled_urls, &$discovered_flows) {
        $special_pages = array(
            'search' => home_url('/?s=test'),
            'login' => wp_login_url(),
            'register' => wp_registration_url(),
            'lost_password' => wp_lostpassword_url()
        );
        
        foreach ($special_pages as $page_type => $url) {
            if ($url && !in_array($url, $crawled_urls)) {
                $this->crawl_url($url, $page_type, $crawled_urls, $discovered_flows);
            }
        }
    }
    
    /**
     * Crawl single URL
     */
    private function crawl_url($url, $page_type, &$crawled_urls, &$discovered_flows, $post = null) {
        if (in_array($url, $crawled_urls)) {
            return;
        }
        
        $crawled_urls[] = $url;
        
        try {
            // Get page content
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'WP-Tester/1.0'
            ));
            
            if (is_wp_error($response)) {
                return;
            }
            
            $html = wp_remote_retrieve_body($response) ?: '';
            $content_hash = md5($html);
            
            // Parse HTML for interactive elements
            $interactive_elements = $this->parse_interactive_elements($html);
            
            // Discover flows based on page content
            $page_flows = $this->discover_flows($html, $url, $page_type, $interactive_elements);
            $discovered_flows = array_merge($discovered_flows, $page_flows);
            
            // Get page title
            $title = $this->extract_title($html, $post);
            
            // Save crawl result
            $this->database->save_crawl_result(
                $url,
                $page_type,
                $title,
                $content_hash,
                $interactive_elements,
                $page_flows
            );
            
        } catch (Exception $e) {
            error_log('WP Tester: Error crawling URL ' . $url . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Parse interactive elements from HTML
     */
    private function parse_interactive_elements($html) {
        $elements = array(
            'forms' => array(),
            'buttons' => array(),
            'links' => array(),
            'inputs' => array(),
            'selects' => array(),
            'textareas' => array(),
            'modals' => array(),
            'dropdowns' => array()
        );
        
        // Create DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Find forms
        $forms = $xpath->query('//form');
        foreach ($forms as $form) {
            /** @var DOMElement $form */
            $form_data = array(
                'id' => $form->getAttribute('id'),
                'class' => $form->getAttribute('class'),
                'action' => $form->getAttribute('action'),
                'method' => $form->getAttribute('method') ?: 'get',
                'fields' => array()
            );
            
            // Find form fields
            $fields = $xpath->query('.//input | .//select | .//textarea', $form);
            foreach ($fields as $field) {
                /** @var DOMElement $field */
                $field_data = array(
                    'type' => $field->getAttribute('type') ?: $field->nodeName,
                    'name' => $field->getAttribute('name'),
                    'id' => $field->getAttribute('id'),
                    'required' => $field->hasAttribute('required'),
                    'placeholder' => $field->getAttribute('placeholder')
                );
                $form_data['fields'][] = $field_data;
            }
            
            $elements['forms'][] = $form_data;
        }
        
        // Find buttons
        $buttons = $xpath->query('//button | //input[@type="button"] | //input[@type="submit"]');
        foreach ($buttons as $button) {
            /** @var DOMElement $button */
            $elements['buttons'][] = array(
                'id' => $button->getAttribute('id'),
                'class' => $button->getAttribute('class'),
                'type' => $button->getAttribute('type') ?: 'button',
                'text' => trim($button->textContent ?: $button->getAttribute('value')),
                'onclick' => $button->getAttribute('onclick')
            );
        }
        
        // Find links
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            /** @var DOMElement $link */
            $href = $link->getAttribute('href');
            if (!empty($href) && $href !== '#') {
                $elements['links'][] = array(
                    'href' => $href,
                    'text' => trim($link->textContent),
                    'class' => $link->getAttribute('class'),
                    'target' => $link->getAttribute('target')
                );
            }
        }
        
        // Find input fields (not in forms)
        $inputs = $xpath->query('//input[not(ancestor::form)]');
        foreach ($inputs as $input) {
            /** @var DOMElement $input */
            $elements['inputs'][] = array(
                'type' => $input->getAttribute('type') ?: 'text',
                'name' => $input->getAttribute('name'),
                'id' => $input->getAttribute('id'),
                'class' => $input->getAttribute('class'),
                'placeholder' => $input->getAttribute('placeholder')
            );
        }
        
        // Find select elements
        $selects = $xpath->query('//select');
        foreach ($selects as $select) {
            /** @var DOMElement $select */
            $options = array();
            $option_nodes = $xpath->query('.//option', $select);
            foreach ($option_nodes as $option) {
                /** @var DOMElement $option */
                $options[] = array(
                    'value' => $option->getAttribute('value'),
                    'text' => trim($option->textContent)
                );
            }
            
            $elements['selects'][] = array(
                'name' => $select->getAttribute('name'),
                'id' => $select->getAttribute('id'),
                'class' => $select->getAttribute('class'),
                'options' => $options
            );
        }
        
        // Find textareas
        $textareas = $xpath->query('//textarea');
        foreach ($textareas as $textarea) {
            /** @var DOMElement $textarea */
            $elements['textareas'][] = array(
                'name' => $textarea->getAttribute('name'),
                'id' => $textarea->getAttribute('id'),
                'class' => $textarea->getAttribute('class'),
                'placeholder' => $textarea->getAttribute('placeholder')
            );
        }
        
        // Find modals and dropdowns (common patterns)
        $modals = $xpath->query('//*[contains(@class, "modal") or contains(@class, "popup") or contains(@class, "dialog")]');
        foreach ($modals as $modal) {
            /** @var DOMElement $modal */
            $elements['modals'][] = array(
                'id' => $modal->getAttribute('id'),
                'class' => $modal->getAttribute('class'),
                'trigger' => $this->find_modal_trigger($modal, $xpath)
            );
        }
        
        $dropdowns = $xpath->query('//*[contains(@class, "dropdown") or contains(@class, "menu")]');
        foreach ($dropdowns as $dropdown) {
            /** @var DOMElement $dropdown */
            $elements['dropdowns'][] = array(
                'id' => $dropdown->getAttribute('id'),
                'class' => $dropdown->getAttribute('class'),
                'items' => $this->find_dropdown_items($dropdown, $xpath)
            );
        }
        
        return $elements;
    }
    
    /**
     * Discover flows based on page content
     */
    private function discover_flows($html, $url, $page_type, $interactive_elements) {
        $flows = array();
        
        // Registration flow
        if ($this->has_registration_form($interactive_elements)) {
            $flows[] = array(
                'name' => 'User Registration',
                'type' => 'registration',
                'start_url' => $url,
                'priority' => 8
            );
        }
        
        // Login flow
        if ($this->has_login_form($interactive_elements)) {
            $flows[] = array(
                'name' => 'User Login',
                'type' => 'login',
                'start_url' => $url,
                'priority' => 9
            );
        }
        
        // Contact form flow
        if ($this->has_contact_form($interactive_elements)) {
            $flows[] = array(
                'name' => 'Contact Form Submission',
                'type' => 'contact',
                'start_url' => $url,
                'priority' => 7
            );
        }
        
        // Search flow
        if ($this->has_search_form($interactive_elements)) {
            $flows[] = array(
                'name' => 'Site Search',
                'type' => 'search',
                'start_url' => $url,
                'priority' => 6
            );
        }
        
        // Newsletter subscription
        if ($this->has_newsletter_form($interactive_elements)) {
            $flows[] = array(
                'name' => 'Newsletter Subscription',
                'type' => 'newsletter',
                'start_url' => $url,
                'priority' => 5
            );
        }
        
        // Comment flow (for posts)
        if ($page_type === 'post' && $this->has_comment_form($interactive_elements)) {
            $flows[] = array(
                'name' => 'Comment Submission',
                'type' => 'comment',
                'start_url' => $url,
                'priority' => 4
            );
        }
        
        // Navigation flows
        $navigation_flows = $this->discover_navigation_flows($interactive_elements, $url);
        $flows = array_merge($flows, $navigation_flows);
        
        return $flows;
    }
    
    /**
     * Check if page has registration form
     */
    private function has_registration_form($elements) {
        foreach ($elements['forms'] as $form) {
            $field_names = array_column($form['fields'], 'name');
            if (in_array('user_login', $field_names) || in_array('username', $field_names) || 
                (in_array('email', $field_names) && in_array('password', $field_names))) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if page has login form
     */
    private function has_login_form($elements) {
        foreach ($elements['forms'] as $form) {
            $field_names = array_column($form['fields'], 'name');
            if (in_array('log', $field_names) || in_array('pwd', $field_names) ||
                (in_array('username', $field_names) && in_array('password', $field_names))) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if page has contact form
     */
    private function has_contact_form($elements) {
        foreach ($elements['forms'] as $form) {
            $field_names = array_column($form['fields'], 'name');
            $form_classes = strtolower($form['class']);
            
            if (strpos($form_classes, 'contact') !== false ||
                strpos($form_classes, 'wpcf7') !== false ||
                in_array('your-message', $field_names) ||
                in_array('message', $field_names)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if page has search form
     */
    private function has_search_form($elements) {
        foreach ($elements['forms'] as $form) {
            $field_names = array_column($form['fields'], 'name');
            if (in_array('s', $field_names) || in_array('search', $field_names)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if page has newsletter form
     */
    private function has_newsletter_form($elements) {
        foreach ($elements['forms'] as $form) {
            $form_classes = strtolower($form['class']);
            if (strpos($form_classes, 'newsletter') !== false ||
                strpos($form_classes, 'subscribe') !== false ||
                strpos($form_classes, 'mailchimp') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if page has comment form
     */
    private function has_comment_form($elements) {
        foreach ($elements['forms'] as $form) {
            $field_names = array_column($form['fields'], 'name');
            if (in_array('comment', $field_names) || in_array('author', $field_names)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Discover navigation flows
     */
    private function discover_navigation_flows($elements, $start_url) {
        $flows = array();
        
        // Menu navigation
        foreach ($elements['dropdowns'] as $dropdown) {
            if (!empty($dropdown['items'])) {
                $flows[] = array(
                    'name' => 'Menu Navigation - ' . $dropdown['class'],
                    'type' => 'navigation',
                    'start_url' => $start_url,
                    'priority' => 3
                );
            }
        }
        
        // Modal interactions
        foreach ($elements['modals'] as $modal) {
            $flows[] = array(
                'name' => 'Modal Interaction - ' . $modal['class'],
                'type' => 'modal',
                'start_url' => $start_url,
                'priority' => 2
            );
        }
        
        return $flows;
    }
    
    /**
     * Process discovered flows and save them
     */
    private function process_discovered_flows($discovered_flows) {
        // Group flows by type and name to avoid duplicates
        $unique_flows = array();
        
        foreach ($discovered_flows as $flow) {
            $key = $flow['type'] . '_' . $flow['name'];
            if (!isset($unique_flows[$key])) {
                $unique_flows[$key] = $flow;
            }
        }
        
        // Save unique flows
        $saved_count = 0;
        foreach ($unique_flows as $flow) {
            $steps = $this->generate_flow_steps($flow);
            $expected_outcome = $this->generate_expected_outcome($flow);
            
            $result = $this->database->save_flow(
                $flow['name'],
                $flow['type'],
                $flow['start_url'],
                $steps,
                $expected_outcome,
                $flow['priority']
            );
            
            if ($result) {
                $saved_count++;
            }
        }
        
        return $saved_count;
    }
    
    /**
     * Generate flow steps based on flow type
     */
    private function generate_flow_steps($flow) {
        $steps = array();
        
        switch ($flow['type']) {
            case 'registration':
                $steps = array(
                    array('action' => 'navigate', 'target' => $flow['start_url']),
                    array('action' => 'fill_form', 'target' => 'registration_form', 'data' => 'test_data'),
                    array('action' => 'submit', 'target' => 'registration_form'),
                    array('action' => 'verify', 'target' => 'success_message')
                );
                break;
                
            case 'login':
                $steps = array(
                    array('action' => 'navigate', 'target' => $flow['start_url']),
                    array('action' => 'fill_form', 'target' => 'login_form', 'data' => 'test_credentials'),
                    array('action' => 'submit', 'target' => 'login_form'),
                    array('action' => 'verify', 'target' => 'dashboard_or_redirect')
                );
                break;
                
            case 'contact':
                $steps = array(
                    array('action' => 'navigate', 'target' => $flow['start_url']),
                    array('action' => 'fill_form', 'target' => 'contact_form', 'data' => 'test_message'),
                    array('action' => 'submit', 'target' => 'contact_form'),
                    array('action' => 'verify', 'target' => 'confirmation_message')
                );
                break;
                
            case 'search':
                $steps = array(
                    array('action' => 'navigate', 'target' => $flow['start_url']),
                    array('action' => 'fill_input', 'target' => 'search_field', 'data' => 'test query'),
                    array('action' => 'submit', 'target' => 'search_form'),
                    array('action' => 'verify', 'target' => 'search_results')
                );
                break;
                
            default:
                $steps = array(
                    array('action' => 'navigate', 'target' => $flow['start_url']),
                    array('action' => 'interact', 'target' => 'interactive_elements'),
                    array('action' => 'verify', 'target' => 'expected_response')
                );
        }
        
        return $steps;
    }
    
    /**
     * Generate expected outcome for flow
     */
    private function generate_expected_outcome($flow) {
        switch ($flow['type']) {
            case 'registration':
                return 'User successfully registers and receives confirmation';
            case 'login':
                return 'User successfully logs in and is redirected to dashboard';
            case 'contact':
                return 'Contact form is submitted and confirmation message is displayed';
            case 'search':
                return 'Search results are displayed for the query';
            default:
                return 'Interactive elements respond correctly to user actions';
        }
    }
    
    /**
     * Crawl single post (triggered on post update)
     */
    public function crawl_single_post($post_id, $post_after, $post_before) {
        if ($post_after->post_status !== 'publish') {
            return;
        }
        
        $url = get_permalink($post_id);
        if (!$url) {
            return;
        }
        
        $crawled_urls = array();
        $discovered_flows = array();
        
        $this->crawl_url($url, $post_after->post_type, $crawled_urls, $discovered_flows, $post_after);
        
        if (!empty($discovered_flows)) {
            $this->process_discovered_flows($discovered_flows);
        }
    }
    
    /**
     * Extract page title
     */
    private function extract_title($html, $post = null) {
        if ($post) {
            return $post->post_title;
        }
        
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches);
        return isset($matches[1]) ? trim(strip_tags($matches[1])) : '';
    }
    
    /**
     * Find modal trigger
     */
    private function find_modal_trigger($modal, $xpath) {
        $modal_id = $modal->getAttribute('id');
        if (!$modal_id) {
            return null;
        }
        
        $triggers = $xpath->query("//*[@data-target='#{$modal_id}' or @href='#{$modal_id}']");
        if ($triggers->length > 0) {
            $trigger = $triggers->item(0);
            return array(
                'tag' => $trigger->nodeName,
                'id' => $trigger->getAttribute('id'),
                'class' => $trigger->getAttribute('class'),
                'text' => trim($trigger->textContent)
            );
        }
        
        return null;
    }
    
    /**
     * Find dropdown items
     */
    private function find_dropdown_items($dropdown, $xpath) {
        $items = array();
        $links = $xpath->query('.//a', $dropdown);
        
        foreach ($links as $link) {
            $items[] = array(
                'href' => $link->getAttribute('href'),
                'text' => trim($link->textContent),
                'class' => $link->getAttribute('class')
            );
        }
        
        return $items;
    }
}