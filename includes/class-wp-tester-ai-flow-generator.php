<?php
/**
 * WP Tester AI Flow Generator
 * 
 * Uses AI to intelligently discover and create flows for both frontend and backend
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_AI_Flow_Generator {
    
    private $database;
    private $ai_model;
    private $admin_pages;
    private $frontend_pages;
    private $available_models;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new WP_Tester_Database();
        $this->ai_model = 'gpt-3.5-turbo'; // Default model
        $this->admin_pages = array();
        $this->frontend_pages = array();
        $this->available_models = $this->get_available_models();
    }
    
    /**
     * Get all available AI models with their configurations
     */
    private function get_available_models() {
        return array(
            // FREE MODELS (No API Key Required)
            'gpt-3.5-turbo' => array(
                'name' => 'GPT-3.5 Turbo',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'free_tier' => true,
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'max_tokens' => 3000,
                'temperature' => 0.2,
                'description' => 'Fast and efficient for most tasks with enhanced flow generation quality'
            ),
            'gemini-pro' => array(
                'name' => 'Gemini Pro',
                'provider' => 'Google',
                'type' => 'chat',
                'free_tier' => true,
                'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
                'max_tokens' => 3000,
                'temperature' => 0.2,
                'description' => 'Google\'s advanced AI model with free tier - excellent for detailed flow generation'
            ),
            'gemini-pro-vision' => array(
                'name' => 'Gemini Pro Vision',
                'provider' => 'Google',
                'type' => 'multimodal',
                'free_tier' => true,
                'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent',
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'description' => 'Multimodal model for text and image understanding'
            ),
            'grok-beta' => array(
                'name' => 'Grok Beta',
                'provider' => 'X.AI',
                'type' => 'chat',
                'free_tier' => true,
                'api_url' => 'https://api.x.ai/v1/chat/completions',
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'description' => 'X.AI\'s conversational AI with real-time knowledge'
            ),
            'deepseek-chat' => array(
                'name' => 'DeepSeek Chat',
                'provider' => 'DeepSeek',
                'type' => 'chat',
                'free_tier' => true,
                'api_url' => 'https://api.deepseek.com/v1/chat/completions',
                'max_tokens' => 2500,
                'temperature' => 0.2,
                'description' => 'Advanced reasoning and coding capabilities with enhanced flow generation'
            ),
            'deepseek-coder' => array(
                'name' => 'DeepSeek Coder',
                'provider' => 'DeepSeek',
                'type' => 'code',
                'free_tier' => true,
                'api_url' => 'https://api.deepseek.com/v1/chat/completions',
                'max_tokens' => 2500,
                'temperature' => 0.1,
                'description' => 'Specialized for code generation and analysis with precise flow creation'
            ),
            'starcoder' => array(
                'name' => 'StarCoder',
                'provider' => 'Hugging Face',
                'type' => 'code',
                'free_tier' => true,
                'api_url' => 'https://api-inference.huggingface.co/models/bigcode/starcoder',
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'description' => 'Open-source code generation model'
            ),
            'starcoder2' => array(
                'name' => 'StarCoder2',
                'provider' => 'Hugging Face',
                'type' => 'code',
                'free_tier' => true,
                'api_url' => 'https://api-inference.huggingface.co/models/bigcode/starcoder2-15b',
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'description' => 'Improved version of StarCoder with better performance'
            ),
            'santacoder' => array(
                'name' => 'SantaCoder',
                'provider' => 'Hugging Face',
                'type' => 'code',
                'free_tier' => true,
                'api_url' => 'https://api-inference.huggingface.co/models/bigcode/santacoder',
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'description' => 'Lightweight code generation model'
            ),
            'codellama' => array(
                'name' => 'Code LLaMA',
                'provider' => 'Meta',
                'type' => 'code',
                'free_tier' => true,
                'api_url' => 'https://api-inference.huggingface.co/models/codellama/CodeLlama-7b-hf',
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'description' => 'Meta\'s code generation model based on LLaMA'
            ),
            'claude-3-haiku' => array(
                'name' => 'Claude 3 Haiku',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => true,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 2500,
                'temperature' => 0.2,
                'description' => 'Fast and efficient Claude model with enhanced flow generation'
            ),
            'mistral-7b' => array(
                'name' => 'Mistral 7B',
                'provider' => 'Mistral AI',
                'type' => 'chat',
                'free_tier' => true,
                'api_url' => 'https://api.mistral.ai/v1/chat/completions',
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'description' => 'Efficient open-source model'
            ),
            'codemistral' => array(
                'name' => 'CodeMistral',
                'provider' => 'Mistral AI',
                'type' => 'code',
                'free_tier' => true,
                'api_url' => 'https://api.mistral.ai/v1/chat/completions',
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'description' => 'Specialized for code generation'
            ),
            
            // PAID MODELS (API Key Required)
            // OpenAI Paid Models
            'gpt-4' => array(
                'name' => 'GPT-4',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Most capable model for complex reasoning'
            ),
            'gpt-4-turbo' => array(
                'name' => 'GPT-4 Turbo',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Faster and more efficient GPT-4 variant'
            ),
            'gpt-4o' => array(
                'name' => 'GPT-4o',
                'provider' => 'OpenAI',
                'type' => 'multimodal',
                'free_tier' => false,
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Multimodal GPT-4 with vision capabilities'
            ),
            'gpt-4o-mini' => array(
                'name' => 'GPT-4o Mini',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Cost-effective GPT-4 variant'
            ),
            'gpt-5' => array(
                'name' => 'GPT-5',
                'provider' => 'OpenAI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'max_tokens' => 8000,
                'temperature' => 0.7,
                'description' => 'Next-generation OpenAI model (when available)'
            ),
            
            // Anthropic Paid Models
            'claude-3-sonnet' => array(
                'name' => 'Claude 3 Sonnet',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Balanced performance and capability'
            ),
            'claude-3-opus' => array(
                'name' => 'Claude 3 Opus',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Most powerful Claude model for complex tasks'
            ),
            'claude-3.5-sonnet' => array(
                'name' => 'Claude 3.5 Sonnet',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Enhanced Claude 3.5 with improved reasoning'
            ),
            'claude-3.5-opus' => array(
                'name' => 'Claude 3.5 Opus',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 8000,
                'temperature' => 0.7,
                'description' => 'Most advanced Claude model available'
            ),
            'claude-4-sonnet' => array(
                'name' => 'Claude 4 Sonnet',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 8000,
                'temperature' => 0.7,
                'description' => 'Next-generation Claude Sonnet (when available)'
            ),
            'claude-4-opus' => array(
                'name' => 'Claude 4 Opus',
                'provider' => 'Anthropic',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.anthropic.com/v1/messages',
                'max_tokens' => 16000,
                'temperature' => 0.7,
                'description' => 'Most powerful Claude model (when available)'
            ),
            
            // Google Paid Models
            'gemini-ultra' => array(
                'name' => 'Gemini Ultra',
                'provider' => 'Google',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-ultra:generateContent',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Google\'s most capable model'
            ),
            'gemini-pro-max' => array(
                'name' => 'Gemini Pro Max',
                'provider' => 'Google',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-max:generateContent',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Enhanced Gemini Pro with extended capabilities'
            ),
            
            // X.AI Paid Models
            'grok-pro' => array(
                'name' => 'Grok Pro',
                'provider' => 'X.AI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.x.ai/v1/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Advanced Grok model with enhanced capabilities'
            ),
            'grok-ultra' => array(
                'name' => 'Grok Ultra',
                'provider' => 'X.AI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.x.ai/v1/chat/completions',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Most powerful Grok model available'
            ),
            
            // DeepSeek Paid Models
            'deepseek-pro' => array(
                'name' => 'DeepSeek Pro',
                'provider' => 'DeepSeek',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.deepseek.com/v1/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Enhanced DeepSeek model for complex reasoning'
            ),
            'deepseek-ultra' => array(
                'name' => 'DeepSeek Ultra',
                'provider' => 'DeepSeek',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.deepseek.com/v1/chat/completions',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Most capable DeepSeek model'
            ),
            
            // Mistral AI Paid Models
            'mistral-large' => array(
                'name' => 'Mistral Large',
                'provider' => 'Mistral AI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.mistral.ai/v1/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Mistral\'s most capable model'
            ),
            'mistral-nemo' => array(
                'name' => 'Mistral Nemo',
                'provider' => 'Mistral AI',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.mistral.ai/v1/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Specialized Mistral model for specific tasks'
            ),
            
            // Cohere Models
            'command-plus' => array(
                'name' => 'Command Plus',
                'provider' => 'Cohere',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.cohere.ai/v1/chat',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Cohere\'s advanced conversational model'
            ),
            'command-nightly' => array(
                'name' => 'Command Nightly',
                'provider' => 'Cohere',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.cohere.ai/v1/chat',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Latest experimental Cohere model'
            ),
            
            // Perplexity Models
            'llama-3.1-sonar' => array(
                'name' => 'Llama 3.1 Sonar',
                'provider' => 'Perplexity',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.perplexity.ai/chat/completions',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'description' => 'Perplexity\'s search-enhanced model'
            ),
            'llama-3.1-sonar-large' => array(
                'name' => 'Llama 3.1 Sonar Large',
                'provider' => 'Perplexity',
                'type' => 'chat',
                'free_tier' => false,
                'api_url' => 'https://api.perplexity.ai/chat/completions',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'description' => 'Enhanced Perplexity model with web search'
            )
        );
    }
    
    /**
     * Get available models for UI display
     */
    public function get_available_models_for_ui() {
        return $this->available_models;
    }
    
    /**
     * Get only free models (no API key required)
     */
    public function get_free_models() {
        $free_models = array();
        foreach ($this->available_models as $model_id => $model_config) {
            if ($model_config['free_tier'] === true) {
                $free_models[$model_id] = $model_config;
            }
        }
        return $free_models;
    }
    
    /**
     * Get only paid models (API key required)
     */
    public function get_paid_models() {
        $paid_models = array();
        foreach ($this->available_models as $model_id => $model_config) {
            if ($model_config['free_tier'] === false) {
                $paid_models[$model_id] = $model_config;
            }
        }
        return $paid_models;
    }
    
    /**
     * Get models grouped by provider
     */
    public function get_models_by_provider() {
        $grouped_models = array();
        foreach ($this->available_models as $model_id => $model_config) {
            $provider = $model_config['provider'];
            if (!isset($grouped_models[$provider])) {
                $grouped_models[$provider] = array();
            }
            $grouped_models[$provider][$model_id] = $model_config;
        }
        return $grouped_models;
    }
    
    /**
     * Set the AI model to use
     */
    public function set_ai_model($model_id) {
        if (isset($this->available_models[$model_id])) {
            $this->ai_model = $model_id;
            return true;
        }
        return false;
    }
    
    /**
     * Get current AI model configuration
     */
    public function get_current_model_config() {
        return $this->available_models[$this->ai_model] ?? $this->available_models['gpt-3.5-turbo'];
    }
    
    /**
     * Generate AI-powered flows for the entire site
     */
    public function generate_ai_flows($options = array()) {
        $default_options = array(
            'include_admin' => true,
            'include_frontend' => true,
            'include_plugins' => false,
            'selected_plugins' => array(),
            'max_flows_per_area' => 10,
            'max_flows_per_plugin' => 5,
            'ai_model' => 'gpt-3.5-turbo',
            'focus_areas' => array('ecommerce', 'content', 'user_management', 'settings')
        );
        
        $options = wp_parse_args($options, $default_options);
        
        try {
            $results = array(
                'frontend_flows' => 0,
                'admin_flows' => 0,
                'plugin_flows' => 0,
                'total_flows' => 0,
                'errors' => array()
            );
            
            // Discover pages and content
            $this->discover_site_structure($options);
            
            // Generate frontend flows
            if ($options['include_frontend']) {
                $frontend_flows = $this->generate_frontend_flows($options);
                $results['frontend_flows'] = count($frontend_flows);
                $results['total_flows'] += $results['frontend_flows'];
            }
            
            // Generate admin flows
            if ($options['include_admin']) {
                $admin_flows = $this->generate_admin_flows($options);
                $results['admin_flows'] = count($admin_flows);
                $results['total_flows'] += $results['admin_flows'];
            }
            
            // Generate plugin flows
            if ($options['include_plugins'] && !empty($options['selected_plugins'])) {
                $plugin_flows = $this->generate_plugin_flows($options);
                $results['plugin_flows'] = count($plugin_flows);
                $results['total_flows'] += $results['plugin_flows'];
            }
            
            return array(
                'success' => true,
                'message' => sprintf(
                    __('AI generated %d flows (%d frontend, %d admin, %d plugin)', 'wp-tester'),
                    $results['total_flows'],
                    $results['frontend_flows'],
                    $results['admin_flows'],
                    $results['plugin_flows']
                ),
                'results' => $results
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Discover site structure (frontend and admin)
     */
    private function discover_site_structure($options) {
        // Discover frontend pages
        if ($options['include_frontend']) {
            $this->discover_frontend_pages();
        }
        
        // Discover admin pages
        if ($options['include_admin']) {
            $this->discover_admin_pages();
        }
    }
    
    /**
     * Discover frontend pages
     */
    private function discover_frontend_pages() {
        $this->frontend_pages = array();
        
        // Get public pages
        $pages = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => 50,
            'meta_query' => array(
                array(
                    'key' => '_wp_page_template',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        foreach (($pages ?? []) as $page) {
            $this->frontend_pages[] = array(
                'url' => get_permalink($page->ID),
                'title' => $page->post_title,
                'type' => $page->post_type,
                'id' => $page->ID,
                'content' => wp_strip_all_tags($page->post_content ?? '')
            );
        }
        
        // Add special pages
        $special_pages = array(
            'home' => home_url('/'),
            'blog' => get_permalink(get_option('page_for_posts')),
            'contact' => $this->find_contact_page(),
            'about' => $this->find_about_page(),
            'shop' => $this->find_shop_page()
        );
        
        foreach ($special_pages as $type => $url) {
            if ($url && !$this->page_exists_in_list($url)) {
                $this->frontend_pages[] = array(
                    'url' => $url,
                    'title' => ucfirst($type) . ' Page',
                    'type' => 'special',
                    'id' => 0,
                    'content' => ''
                );
            }
        }
    }
    
    /**
     * Discover admin pages
     */
    private function discover_admin_pages() {
        $this->admin_pages = array();
        
        // Get WordPress admin pages
        $admin_pages = array(
            'dashboard' => admin_url('index.php'),
            'posts' => admin_url('edit.php'),
            'pages' => admin_url('edit.php?post_type=page'),
            'media' => admin_url('upload.php'),
            'comments' => admin_url('edit-comments.php'),
            'appearance' => admin_url('themes.php'),
            'plugins' => admin_url('plugins.php'),
            'users' => admin_url('users.php'),
            'tools' => admin_url('tools.php'),
            'settings' => admin_url('options-general.php'),
            'wp_tester' => admin_url('admin.php?page=wp-tester'),
            'wp_tester_flows' => admin_url('admin.php?page=wp-tester-flows'),
            'wp_tester_results' => admin_url('admin.php?page=wp-tester-results'),
            'wp_tester_crawl' => admin_url('admin.php?page=wp-tester-crawl'),
            'wp_tester_settings' => admin_url('admin.php?page=wp-tester-settings')
        );
        
        // Add WooCommerce admin pages if available
        if (class_exists('WooCommerce')) {
            $woo_pages = array(
                'woo_orders' => admin_url('edit.php?post_type=shop_order'),
                'woo_products' => admin_url('edit.php?post_type=product'),
                'woo_customers' => admin_url('admin.php?page=wc-admin'),
                'woo_reports' => admin_url('admin.php?page=wc-reports'),
                'woo_settings' => admin_url('admin.php?page=wc-settings')
            );
            $admin_pages = array_merge($admin_pages, $woo_pages);
        }
        
        foreach ($admin_pages as $name => $url) {
            $this->admin_pages[] = array(
                'url' => $url,
                'title' => ucwords(str_replace('_', ' ', $name)),
                'type' => 'admin',
                'area' => $this->categorize_admin_page($name),
                'requires_auth' => true
            );
        }
    }
    
    /**
     * Generate frontend flows using AI
     */
    private function generate_frontend_flows($options) {
        $flows = array();
        $site_info = $this->get_site_analysis();
        
        foreach (($this->frontend_pages ?? []) as $page) {
            if (count($flows) >= $options['max_flows_per_area']) {
                break;
            }
            
            $flow_data = $this->generate_flow_with_ai($page, 'frontend', $site_info);
            if ($flow_data && $this->is_unique_flow($flow_data)) {
                $flow_id = $this->save_ai_generated_flow($flow_data);
                if ($flow_id) {
                    $flows[] = $flow_id;
                }
            }
        }
        
        return $flows;
    }
    
    /**
     * Generate admin flows using AI
     */
    private function generate_admin_flows($options) {
        $flows = array();
        $site_info = $this->get_site_analysis();
        
        foreach (($this->admin_pages ?? []) as $page) {
            if (count($flows) >= $options['max_flows_per_area']) {
                break;
            }
            
            $flow_data = $this->generate_flow_with_ai($page, 'admin', $site_info);
            if ($flow_data && $this->is_unique_flow($flow_data)) {
                $flow_id = $this->save_ai_generated_flow($flow_data);
                if ($flow_id) {
                    $flows[] = $flow_id;
                }
            }
        }
        
        return $flows;
    }
    
    /**
     * Generate plugin flows using AI
     */
    private function generate_plugin_flows($options) {
        $flows = array();
        $site_info = $this->get_site_analysis();
        
        foreach ($options['selected_plugins'] as $plugin_slug) {
            if (count($flows) >= ($options['max_flows_per_plugin'] * count($options['selected_plugins']))) {
                break;
            }
            
            $plugin_info = $this->get_plugin_info($plugin_slug);
            if (!$plugin_info) {
                continue;
            }
            
            $plugin_flows = $this->generate_flows_for_plugin($plugin_info, $options, $site_info);
            foreach ($plugin_flows as $flow_data) {
                if ($this->is_unique_flow($flow_data)) {
                    $flow_id = $this->save_ai_generated_flow($flow_data);
                    if ($flow_id) {
                        $flows[] = $flow_id;
                    }
                }
            }
        }
        
        return $flows;
    }
    
    /**
     * Generate flows for a specific plugin
     */
    private function generate_flows_for_plugin($plugin_info, $options, $site_info) {
        $flows = array();
        
        // Get plugin pages and functionality
        $plugin_pages = $this->discover_plugin_pages($plugin_info);
        $plugin_functionality = $this->analyze_plugin_functionality($plugin_info);
        
        foreach ($plugin_pages as $page) {
            if (count($flows) >= $options['max_flows_per_plugin']) {
                break;
            }
            
            $flow_data = $this->generate_plugin_flow_with_ai($page, $plugin_info, $plugin_functionality, $site_info);
            if ($flow_data) {
                $flows[] = $flow_data;
            }
        }
        
        return $flows;
    }
    
    /**
     * Get plugin information
     */
    private function get_plugin_info($plugin_slug) {
        $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
        
        if (!file_exists($plugin_dir) || !is_dir($plugin_dir)) {
            return null;
        }
        
        // Find the main plugin file
        $plugin_file = $this->find_main_plugin_file($plugin_dir, $plugin_slug);
        if (!$plugin_file) {
            return null;
        }
        
        $plugin_data = get_plugin_data($plugin_file);
        
        return array(
            'slug' => $plugin_slug,
            'name' => $plugin_data['Name'],
            'description' => $plugin_data['Description'],
            'version' => $plugin_data['Version'],
            'author' => $plugin_data['Author'],
            'plugin_uri' => $plugin_data['PluginURI'],
            'file' => $plugin_file
        );
    }
    
    /**
     * Find the main plugin file in a plugin directory
     */
    private function find_main_plugin_file($plugin_dir, $plugin_slug) {
        // Common main plugin file names
        $possible_files = array(
            $plugin_slug . '.php',
            $plugin_slug . '/' . $plugin_slug . '.php',
            'index.php',
            'main.php',
            'plugin.php'
        );
        
        foreach ($possible_files as $file) {
            $file_path = $plugin_dir . '/' . $file;
            if (file_exists($file_path) && is_file($file_path)) {
                // Check if it's a valid WordPress plugin file
                $content = file_get_contents($file_path);
                if (strpos($content, 'Plugin Name:') !== false || strpos($content, '<?php') !== false) {
                    return $file_path;
                }
            }
        }
        
        // If no standard file found, look for any PHP file with plugin header
        $files = glob($plugin_dir . '/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'Plugin Name:') !== false) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * Discover plugin pages and functionality
     */
    private function discover_plugin_pages($plugin_info) {
        $pages = array();
        
        // Common plugin admin pages
        $common_pages = array(
            'settings' => admin_url('admin.php?page=' . $plugin_info['slug']),
            'main' => admin_url('admin.php?page=' . $plugin_info['slug'] . '-main'),
            'options' => admin_url('admin.php?page=' . $plugin_info['slug'] . '-options'),
            'tools' => admin_url('admin.php?page=' . $plugin_info['slug'] . '-tools'),
            'help' => admin_url('admin.php?page=' . $plugin_info['slug'] . '-help')
        );
        
        // Check for custom post types
        $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
        foreach (($custom_post_types ?? []) as $post_type) {
            if (strpos($post_type->name, $plugin_info['slug']) !== false) {
                $pages[] = array(
                    'url' => admin_url('edit.php?post_type=' . $post_type->name),
                    'title' => $post_type->label,
                    'type' => 'admin',
                    'area' => 'custom_post_type',
                    'post_type' => $post_type->name
                );
            }
        }
        
        // Check for custom admin pages
        global $admin_page_hooks;
        foreach (($admin_page_hooks ?? []) as $hook => $page_title) {
            if (strpos($hook, $plugin_info['slug']) !== false) {
                $pages[] = array(
                    'url' => admin_url('admin.php?page=' . $hook),
                    'title' => $page_title,
                    'type' => 'admin',
                    'area' => 'custom_admin_page',
                    'hook' => $hook
                );
            }
        }
        
        // Add common pages if they exist
        foreach ($common_pages as $type => $url) {
            $pages[] = array(
                'url' => $url,
                'title' => ucfirst($type) . ' - ' . $plugin_info['name'],
                'type' => 'admin',
                'area' => $type,
                'plugin' => $plugin_info['slug']
            );
        }
        
        return $pages;
    }
    
    /**
     * Analyze plugin functionality
     */
    private function analyze_plugin_functionality($plugin_info) {
        $functionality = array(
            'type' => 'unknown',
            'features' => array(),
            'has_frontend' => false,
            'has_admin' => true,
            'has_shortcodes' => false,
            'has_widgets' => false,
            'has_custom_post_types' => false,
            'has_custom_taxonomies' => false
        );
        
        // Analyze plugin file for common patterns
        if (!isset($plugin_info['file']) || !file_exists($plugin_info['file']) || !is_file($plugin_info['file'])) {
            error_log('WP Tester: Plugin file not found or invalid: ' . ($plugin_info['file'] ?? 'undefined'));
            return $functionality;
        }
        
        $plugin_content = file_get_contents($plugin_info['file']);
        if ($plugin_content === false) {
            error_log('WP Tester: Failed to read plugin file: ' . $plugin_info['file']);
            return $functionality;
        }
        
        // Detect plugin type based on common patterns
        if (strpos($plugin_content, 'woocommerce') !== false || strpos($plugin_info['name'], 'WooCommerce') !== false) {
            $functionality['type'] = 'ecommerce';
        } elseif (strpos($plugin_content, 'contact') !== false || strpos($plugin_info['name'], 'Contact') !== false) {
            $functionality['type'] = 'contact_form';
        } elseif (strpos($plugin_content, 'seo') !== false || strpos($plugin_info['name'], 'SEO') !== false) {
            $functionality['type'] = 'seo';
        } elseif (strpos($plugin_content, 'security') !== false || strpos($plugin_info['name'], 'Security') !== false) {
            $functionality['type'] = 'security';
        } elseif (strpos($plugin_content, 'backup') !== false || strpos($plugin_info['name'], 'Backup') !== false) {
            $functionality['type'] = 'backup';
        } elseif (strpos($plugin_content, 'cache') !== false || strpos($plugin_info['name'], 'Cache') !== false) {
            $functionality['type'] = 'performance';
        } elseif (strpos($plugin_content, 'form') !== false || strpos($plugin_info['name'], 'Form') !== false) {
            $functionality['type'] = 'form_builder';
        } elseif (strpos($plugin_content, 'gallery') !== false || strpos($plugin_info['name'], 'Gallery') !== false) {
            $functionality['type'] = 'media';
        } elseif (strpos($plugin_content, 'social') !== false || strpos($plugin_info['name'], 'Social') !== false) {
            $functionality['type'] = 'social';
        } elseif (strpos($plugin_content, 'analytics') !== false || strpos($plugin_info['name'], 'Analytics') !== false) {
            $functionality['type'] = 'analytics';
        }
        
        // Detect features
        if (strpos($plugin_content, 'add_shortcode') !== false) {
            $functionality['has_shortcodes'] = true;
        }
        if (strpos($plugin_content, 'wp_widget') !== false) {
            $functionality['has_widgets'] = true;
        }
        if (strpos($plugin_content, 'register_post_type') !== false) {
            $functionality['has_custom_post_types'] = true;
        }
        if (strpos($plugin_content, 'register_taxonomy') !== false) {
            $functionality['has_custom_taxonomies'] = true;
        }
        if (strpos($plugin_content, 'wp_enqueue_script') !== false && strpos($plugin_content, 'wp_head') !== false) {
            $functionality['has_frontend'] = true;
        }
        
        return $functionality;
    }
    
    /**
     * Generate plugin flow using AI
     */
    private function generate_plugin_flow_with_ai($page, $plugin_info, $plugin_functionality, $site_info) {
        try {
            $prompt = $this->build_plugin_ai_prompt($page, $plugin_info, $plugin_functionality, $site_info);
            $ai_response = $this->call_ai_api($prompt);
            
            if ($ai_response && isset($ai_response['flow'])) {
                return $this->parse_plugin_ai_response($ai_response, $page, $plugin_info);
            }
            
            return $this->generate_plugin_fallback_flow($page, $plugin_info, $plugin_functionality);
            
        } catch (Exception $e) {
            error_log('WP Tester Plugin AI Flow Generation Error: ' . $e->getMessage());
            return $this->generate_plugin_fallback_flow($page, $plugin_info, $plugin_functionality);
        }
    }
    
    /**
     * Build AI prompt for plugin flow generation
     */
    private function build_plugin_ai_prompt($page, $plugin_info, $plugin_functionality, $site_info) {
        $prompt = "You are a senior WordPress plugin QA automation engineer with 15+ years of experience in plugin testing, WordPress core integration, and automated test design. You specialize in creating comprehensive test scenarios that validate plugin functionality, catch integration bugs, and ensure plugins work seamlessly with WordPress.\n\n";
        
        $prompt .= "## PLUGIN ANALYSIS & CONTEXT\n";
        $prompt .= "**Plugin Deep Dive:**\n";
        $prompt .= "- Plugin Name: {$plugin_info['name']}\n";
        $prompt .= "- Plugin Slug: {$plugin_info['slug']}\n";
        $prompt .= "- Plugin Description: {$plugin_info['description']}\n";
        $prompt .= "- Plugin Type: {$plugin_functionality['type']}\n";
        $prompt .= "- Plugin Version: {$plugin_info['version']}\n";
        $prompt .= "- Plugin Author: {$plugin_info['author']}\n";
        $prompt .= "- Plugin URI: {$plugin_info['plugin_uri']}\n";
        
        $prompt .= "\n**Target Page Context:**\n";
        $prompt .= "- Page URL: {$page['url']}\n";
        $prompt .= "- Page Title: {$page['title']}\n";
        $prompt .= "- Page Area: {$page['area']}\n";
        $prompt .= "- Site Type: {$site_info['type']}\n";
        $prompt .= "- WordPress Version: " . get_bloginfo('version') . "\n";
        $theme = wp_get_theme();
        $prompt .= "- Active Theme: " . ($theme ? $theme->get_template() : 'Unknown') . "\n";
        
        $prompt .= "\n**Plugin Capabilities Analysis:**\n";
        if ($plugin_functionality['has_shortcodes']) {
            $prompt .= "- ✅ Shortcode Support: Plugin provides shortcode functionality for content integration\n";
        }
        if ($plugin_functionality['has_widgets']) {
            $prompt .= "- ✅ Widget Support: Plugin provides widget functionality for sidebars\n";
        }
        if ($plugin_functionality['has_custom_post_types']) {
            $prompt .= "- ✅ Custom Post Types: Plugin creates custom content types\n";
        }
        if ($plugin_functionality['has_custom_taxonomies']) {
            $prompt .= "- ✅ Custom Taxonomies: Plugin creates custom categorization systems\n";
        }
        if ($plugin_functionality['has_frontend']) {
            $prompt .= "- ✅ Frontend Features: Plugin affects frontend display and functionality\n";
        }
        if ($plugin_functionality['has_admin']) {
            $prompt .= "- ✅ Admin Interface: Plugin adds admin interface and management tools\n";
        }
        
        $prompt .= "\n## EXPERT PLUGIN TESTING STRATEGY\n";
        $prompt .= "Your mission is to create a test flow that:\n";
        $prompt .= "1. **Validates Core Plugin Functionality** - Test the plugin's primary purpose and features\n";
        $prompt .= "2. **Tests WordPress Integration** - Ensure proper hooks, filters, actions, and core compatibility\n";
        $prompt .= "3. **Validates User Workflows** - Test real user scenarios and common interaction patterns\n";
        $prompt .= "4. **Checks Data Integrity & Persistence** - Ensure data is saved, retrieved, and displayed correctly\n";
        $prompt .= "5. **Tests Error Handling & Edge Cases** - Verify graceful handling of invalid inputs and failure scenarios\n";
        $prompt .= "6. **Validates Security & Permissions** - Test access controls, data sanitization, and security measures\n";
        $prompt .= "7. **Checks Performance Impact** - Ensure plugin doesn't cause slowdowns or resource issues\n";
        $prompt .= "8. **Tests Cross-Theme Compatibility** - Verify plugin works with different themes\n";
        $prompt .= "9. **Validates Plugin Updates & Migration** - Test upgrade paths and data migration\n";
        $prompt .= "10. **Tests Plugin Deactivation/Cleanup** - Ensure clean removal without leaving traces\n";
        
        $prompt .= "\n## ADVANCED PLUGIN ACTION FRAMEWORK\n";
        $prompt .= "Use these actions strategically for plugin testing:\n";
        $prompt .= "- **visit**: Navigate to plugin admin pages, frontend pages, or specific URLs\n";
        $prompt .= "- **click**: Interact with plugin buttons, links, tabs, toggles, and controls\n";
        $prompt .= "- **fill**: Enter data in plugin forms, settings, and input fields\n";
        $prompt .= "- **select**: Choose options from plugin dropdowns, checkboxes, and radio buttons\n";
        $prompt .= "- **wait**: Pause for AJAX calls, data processing, plugin operations (2-5 seconds)\n";
        $prompt .= "- **hover**: Trigger tooltips, previews, hover effects, and contextual menus\n";
        $prompt .= "- **scroll**: Navigate through long plugin pages, content, and settings\n";
        $prompt .= "- **keyboard**: Use keyboard shortcuts, accessibility features, and navigation\n";
        $prompt .= "- **upload**: Test file uploads, media handling, and import functionality\n";
        $prompt .= "- **export/import**: Test data export/import, backup/restore functionality\n";
        $prompt .= "- **toggle**: Test on/off switches, enable/disable features, and settings toggles\n";
        
        $prompt .= "\n## PRECISION PLUGIN CSS SELECTOR STRATEGY\n";
        $prompt .= "Use plugin-specific selectors in this priority order:\n";
        $prompt .= "1. **Plugin-specific data attributes**: [data-{$plugin_info['slug']}='value'], [data-plugin='{$plugin_info['slug']}']\n";
        $prompt .= "2. **Plugin form elements**: input[name='{$plugin_info['slug']}_field'], select[name='{$plugin_info['slug']}_option']\n";
        $prompt .= "3. **Plugin-specific IDs**: #{$plugin_info['slug']}-element, #{$plugin_info['slug']}-settings\n";
        $prompt .= "4. **Plugin classes**: .{$plugin_info['slug']}-class, .plugin-{$plugin_info['slug']}, .{$plugin_info['slug']}-admin\n";
        $prompt .= "5. **WordPress admin selectors**: .wrap, .form-table, .button-primary, .notice\n";
        $prompt .= "6. **Plugin-specific buttons**: .{$plugin_info['slug']}-button, .{$plugin_info['slug']}-submit\n";
        $prompt .= "7. **Plugin tabs and navigation**: .{$plugin_info['slug']}-nav, .{$plugin_info['slug']}-tabs\n";
        $prompt .= "8. **Plugin-specific containers**: .{$plugin_info['slug']}-container, .{$plugin_info['slug']}-wrapper\n";
        
        $prompt .= "\n## COMPREHENSIVE PLUGIN TEST DATA LIBRARY\n";
        $prompt .= "Use realistic, plugin-appropriate test data:\n";
        $prompt .= "- **Plugin Settings**: Realistic configuration values that match plugin purpose\n";
        $prompt .= "- **Content Data**: Meaningful test content that demonstrates plugin functionality\n";
        $prompt .= "- **User Data**: Diverse, realistic user information for user management plugins\n";
        $prompt .= "- **File Uploads**: Appropriate file types, sizes, and formats for plugin functionality\n";
        $prompt .= "- **API Keys**: Test/development keys for plugins that integrate with external services\n";
        $prompt .= "- **Database Values**: Realistic database entries that test plugin data handling\n";
        $prompt .= "- **Plugin-specific Data**: Custom post types, taxonomies, and plugin-specific content\n";
        $prompt .= "- **Configuration Values**: Settings that test plugin's configuration options\n";
        
        $prompt .= "\n## PLUGIN-SPECIFIC EXPECTED RESULTS\n";
        $prompt .= "Define plugin-specific, measurable outcomes:\n";
        $prompt .= "- **Functionality Success**: Plugin features work as documented and expected\n";
        $prompt .= "- **Data Persistence**: Settings, content, and data are saved and retrieved correctly\n";
        $prompt .= "- **User Feedback**: Appropriate success/error messages, notifications, and alerts\n";
        $prompt .= "- **WordPress Integration**: Proper hooks, filters, actions, and core compatibility\n";
        $prompt .= "- **Security Validation**: Proper permission checks, data sanitization, and access controls\n";
        $prompt .= "- **Performance Impact**: No significant slowdown, memory leaks, or resource issues\n";
        $prompt .= "- **UI/UX Validation**: Proper interface rendering, responsive design, and user experience\n";
        $prompt .= "- **Plugin State Management**: Proper activation, deactivation, and configuration states\n";
        
        $prompt .= "\n## STRICT JSON RESPONSE FORMAT\n";
        $prompt .= "Generate ONLY a valid JSON response with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"flow_name\": \"[Plugin-specific descriptive name that clearly indicates what is being tested]\",\n";
        $prompt .= "  \"flow_type\": \"[plugin_admin|plugin_frontend|plugin_setup|plugin_configuration|plugin_integration|plugin_security|plugin_performance|plugin_migration]\",\n";
        $prompt .= "  \"description\": \"[Detailed explanation of what this plugin flow tests, why it's important, and what bugs it might catch]\",\n";
        $prompt .= "  \"steps\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"action\": \"[visit|click|fill|select|wait|hover|scroll|keyboard|upload|export|import|toggle]\",\n";
        $prompt .= "      \"target\": \"[Plugin-specific CSS selector or URL]\",\n";
        $prompt .= "      \"value\": \"[Realistic plugin test data]\",\n";
        $prompt .= "      \"expected_result\": \"[Specific, measurable plugin outcome with success criteria]\",\n";
        $prompt .= "      \"wait_time\": [Optional: seconds to wait after this step]\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"priority\": \"[high|medium|low based on plugin functionality importance and user impact]\",\n";
        $prompt .= "  \"tags\": [\"plugin\", \"{$plugin_info['slug']}\", \"[relevant functionality tags]\"]\n";
        $prompt .= "}\n\n";
        
        $prompt .= "## EXPERT PLUGIN QUALITY STANDARDS\n";
        $prompt .= "1. **Flow name must be plugin-specific and descriptive** - Include plugin name and exact functionality being tested\n";
        $prompt .= "2. **Test actual plugin features** - Focus on plugin's unique functionality, not generic WordPress features\n";
        $prompt .= "3. **Include comprehensive error scenarios** - Test plugin error handling, edge cases, and failure modes\n";
        $prompt .= "4. **Use plugin-appropriate test data** - Test data should match plugin's purpose and functionality\n";
        $prompt .= "5. **Test WordPress integration thoroughly** - Verify proper hooks, filters, actions, and core compatibility\n";
        $prompt .= "6. **Consider different plugin user personas** - Admin users, editors, subscribers, and guests\n";
        $prompt .= "7. **Test plugin security comprehensively** - Verify permission checks, data validation, and security measures\n";
        $prompt .= "8. **Validate plugin performance impact** - Ensure plugin doesn't cause performance issues\n";
        $prompt .= "9. **Test plugin state management** - Verify proper activation, deactivation, and configuration states\n";
        $prompt .= "10. **Focus on business-critical plugin functionality** - Prioritize features that impact user experience and business goals\n";
        
        $prompt .= "\n## EXCELLENT PLUGIN FLOW NAME EXAMPLES\n";
        $prompt .= "- \"{$plugin_info['name']} - Complete Settings Configuration and Validation Workflow\"\n";
        $prompt .= "- \"{$plugin_info['name']} - User Registration and Profile Management with Plugin Features\"\n";
        $prompt .= "- \"{$plugin_info['name']} - Content Creation, Management, and Publishing Workflow\"\n";
        $prompt .= "- \"{$plugin_info['name']} - Data Import/Export and Backup/Restore Functionality\"\n";
        $prompt .= "- \"{$plugin_info['name']} - Frontend Display, User Interaction, and Public Features\"\n";
        $prompt .= "- \"{$plugin_info['name']} - Admin Dashboard, User Management, and Administrative Controls\"\n";
        $prompt .= "- \"{$plugin_info['name']} - Plugin Activation, Configuration, and Integration Testing\"\n";
        $prompt .= "- \"{$plugin_info['name']} - Security Validation, Permission Checks, and Access Control Testing\"\n";
        
        $prompt .= "\n## CONCRETE PLUGIN TESTING EXAMPLES\n";
        $prompt .= "**For a Contact Form Plugin:**\n";
        $prompt .= "- Test form creation and configuration\n";
        $prompt .= "- Test form submission with valid and invalid data\n";
        $prompt .= "- Test email notifications and delivery\n";
        $prompt .= "- Test form validation and error handling\n";
        $prompt .= "- Test form styling and customization\n";
        $prompt .= "- Test form analytics and reporting\n";
        
        $prompt .= "\n**For an E-commerce Plugin:**\n";
        $prompt .= "- Test product creation and management\n";
        $prompt .= "- Test shopping cart and checkout process\n";
        $prompt .= "- Test payment gateway integration\n";
        $prompt .= "- Test order management and fulfillment\n";
        $prompt .= "- Test customer management and communication\n";
        $prompt .= "- Test reporting and analytics features\n";
        
        $prompt .= "\n**For a SEO Plugin:**\n";
        $prompt .= "- Test meta tag configuration and validation\n";
        $prompt .= "- Test sitemap generation and submission\n";
        $prompt .= "- Test keyword analysis and optimization\n";
        $prompt .= "- Test social media integration\n";
        $prompt .= "- Test performance monitoring and recommendations\n";
        $prompt .= "- Test plugin integration with other SEO tools\n";
        
        $prompt .= "\n## FINAL PLUGIN TESTING INSTRUCTIONS\n";
        $prompt .= "Create a comprehensive, realistic test flow that thoroughly validates the {$plugin_info['name']} plugin functionality. Think like a real user who wants to accomplish specific tasks with this plugin. Focus on scenarios that would actually break in production and cause user frustration. Make every step actionable, specific, and valuable for catching real plugin bugs.\n\n";
        $prompt .= "Remember: Plugin testing is about validating the plugin's unique value proposition. Test what makes this plugin special, not just generic WordPress functionality. Quality over quantity - better to have 5 well-thought-out steps that test core plugin features than 15 generic steps.";
        
        return $prompt;
    }
    
    /**
     * Parse plugin AI response
     */
    private function parse_plugin_ai_response($ai_response, $page, $plugin_info) {
        $flow = $ai_response['flow'];
        
        return array(
            'flow_name' => $flow['flow_name'] ?? $this->generate_plugin_flow_name($page['title'], $plugin_info['name']),
            'flow_type' => $flow['flow_type'] ?? 'plugin_admin',
            'start_url' => $page['url'],
            'steps' => $flow['steps'] ?? array(),
            'description' => $flow['description'] ?? "Plugin test flow for {$plugin_info['name']} - {$page['title']}",
            'priority' => $flow['priority'] ?? 'medium',
            'tags' => $flow['tags'] ?? array('plugin', $plugin_info['slug'], 'ai-generated'),
            'is_active' => true,
            'created_by' => 'ai_plugin',
            'plugin_slug' => $plugin_info['slug']
        );
    }
    
    /**
     * Generate plugin fallback flow
     */
    private function generate_plugin_fallback_flow($page, $plugin_info, $plugin_functionality) {
        $flow_name = $this->generate_plugin_flow_name($page['title'], $plugin_info['name']);
        $flow_type = $this->determine_plugin_flow_type($page, $plugin_functionality);
        $steps = $this->generate_plugin_basic_steps($page, $plugin_functionality);
        
        return array(
            'flow_name' => $flow_name,
            'flow_type' => $flow_type,
            'start_url' => $page['url'],
            'steps' => $steps,
            'description' => "Basic plugin test flow for {$plugin_info['name']} - {$page['title']}",
            'priority' => 'medium',
            'tags' => array('plugin', $plugin_info['slug'], 'auto-generated'),
            'is_active' => true,
            'created_by' => 'plugin_crawler',
            'plugin_slug' => $plugin_info['slug']
        );
    }
    
    /**
     * Generate unique plugin flow name
     */
    private function generate_plugin_flow_name($page_title, $plugin_name) {
        $base_name = "Plugin: {$plugin_name} - {$page_title}";
        $counter = 1;
        $flow_name = $base_name;
        
        while ($this->flow_name_exists($flow_name)) {
            $flow_name = "{$base_name} ({$counter})";
            $counter++;
        }
        
        return $flow_name;
    }
    
    /**
     * Determine plugin flow type
     */
    private function determine_plugin_flow_type($page, $plugin_functionality) {
        if ($page['area'] === 'settings' || $page['area'] === 'options') {
            return 'plugin_configuration';
        } elseif ($page['area'] === 'custom_post_type') {
            return 'plugin_admin';
        } elseif ($plugin_functionality['has_frontend']) {
            return 'plugin_frontend';
        } else {
            return 'plugin_admin';
        }
    }
    
    /**
     * Generate basic plugin steps
     */
    private function generate_plugin_basic_steps($page, $plugin_functionality) {
        $steps = array();
        
        $steps[] = array(
            'action' => 'visit',
            'target' => $page['url'],
            'value' => '',
            'expected_result' => 'Plugin page loads successfully'
        );
        
        $steps[] = array(
            'action' => 'wait',
            'target' => '2',
            'value' => '',
            'expected_result' => 'Page fully loads'
        );
        
        // Add plugin-specific steps based on functionality
        if ($page['area'] === 'settings' || $page['area'] === 'options') {
            $steps[] = array(
                'action' => 'click',
                'target' => 'input[type="submit"], button[type="submit"], .button-primary',
                'value' => '',
                'expected_result' => 'Settings save successfully'
            );
        } elseif ($page['area'] === 'custom_post_type') {
            $steps[] = array(
                'action' => 'click',
                'target' => '.page-title-action, .add-new-h2',
                'value' => '',
                'expected_result' => 'Add new item page loads'
            );
        }
        
        return $steps;
    }
    
    /**
     * Generate flow using AI
     */
    private function generate_flow_with_ai($page, $area, $site_info) {
        try {
            $prompt = $this->build_ai_prompt($page, $area, $site_info);
            $ai_response = $this->call_ai_api($prompt);
            
            if ($ai_response && isset($ai_response['flow'])) {
                return $this->parse_ai_response($ai_response, $page, $area);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('WP Tester AI Flow Generation Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Build AI prompt for flow generation
     */
    private function build_ai_prompt($page, $area, $site_info) {
        $prompt = "You are a senior WordPress QA automation engineer with 15+ years of experience in web application testing, user experience analysis, and automated test design. You specialize in creating comprehensive, realistic test scenarios that catch real-world bugs and validate critical user journeys.\n\n";
        
        $prompt .= "## MISSION CRITICAL CONTEXT\n";
        $prompt .= "**Target Page Analysis:**\n";
        $prompt .= "- URL: {$page['url']}\n";
        $prompt .= "- Page Title: {$page['title']}\n";
        $prompt .= "- Testing Area: {$area}\n";
        $prompt .= "- Site Type: {$site_info['type']}\n";
        $prompt .= "- WordPress Version: " . get_bloginfo('version') . "\n";
        $theme = wp_get_theme();
        $prompt .= "- Active Theme: " . ($theme ? $theme->get_template() : 'Unknown') . "\n";
        
        if (!empty($page['content'])) {
            $content_preview = substr(strip_tags($page['content']), 0, 1000);
            $prompt .= "- Page Content Analysis: " . $content_preview . "...\n";
        }
        
        $prompt .= "\n## EXPERT TESTING STRATEGY\n";
        $prompt .= "Your task is to create a test flow that:\n";
        $prompt .= "1. **Simulates Real User Behavior** - Think like actual users, not robots\n";
        $prompt .= "2. **Tests Critical Business Logic** - Focus on functionality that matters to business success\n";
        $prompt .= "3. **Covers Edge Cases & Error Scenarios** - Test boundary conditions, invalid inputs, and failure modes\n";
        $prompt .= "4. **Validates Data Integrity** - Ensure data is saved, retrieved, and displayed correctly\n";
        $prompt .= "5. **Tests User Experience Flow** - Verify smooth navigation, feedback, and user satisfaction\n";
        $prompt .= "6. **Checks Cross-Browser Compatibility** - Consider different browser behaviors\n";
        $prompt .= "7. **Tests Performance & Responsiveness** - Ensure fast loading and mobile compatibility\n";
        $prompt .= "8. **Validates Security & Permissions** - Test access controls and data protection\n";
        
        $prompt .= "\n## ADVANCED ACTION FRAMEWORK\n";
        $prompt .= "Use these actions strategically:\n";
        $prompt .= "- **visit**: Navigate to URLs (always start flows with this)\n";
        $prompt .= "- **click**: Interact with buttons, links, checkboxes, radio buttons\n";
        $prompt .= "- **fill**: Enter data in text fields, textareas, search boxes\n";
        $prompt .= "- **select**: Choose from dropdowns, multi-selects, date pickers\n";
        $prompt .= "- **wait**: Pause for AJAX calls, page transitions, animations (2-5 seconds)\n";
        $prompt .= "- **hover**: Trigger tooltips, dropdown menus, preview effects\n";
        $prompt .= "- **scroll**: Navigate long pages, reveal lazy-loaded content\n";
        $prompt .= "- **keyboard**: Use Enter, Tab, Escape, arrow keys for accessibility\n";
        $prompt .= "- **upload**: Test file uploads (images, documents, media)\n";
        $prompt .= "- **drag**: Drag and drop interactions if applicable\n";
        
        $prompt .= "\n## PRECISION CSS SELECTOR STRATEGY\n";
        $prompt .= "Use the most reliable selectors in this priority order:\n";
        $prompt .= "1. **Data attributes**: [data-testid='submit-btn'], [data-cy='login-form']\n";
        $prompt .= "2. **Form elements**: input[name='email'], select[name='country'], textarea[name='message']\n";
        $prompt .= "3. **IDs**: #login-button, #search-form, #user-menu\n";
        $prompt .= "4. **Semantic classes**: .btn-primary, .form-control, .nav-link, .card-title\n";
        $prompt .= "5. **Text-based**: button:contains('Submit'), a:contains('Login'), input[placeholder*='Email']\n";
        $prompt .= "6. **Role-based**: [role='button'], [role='link'], [role='textbox']\n";
        $prompt .= "7. **Avoid**: Generic selectors like div, span, p without context\n";
        
        $prompt .= "\n## REALISTIC TEST DATA LIBRARY\n";
        $prompt .= "Use diverse, realistic test data:\n";
        $prompt .= "- **Names**: John Smith, Maria García, Ahmed Hassan, Li Wei, Priya Patel\n";
        $prompt .= "- **Emails**: john.smith@example.com, maria.garcia@company.org, test.user+qa@domain.net\n";
        $prompt .= "- **Phone Numbers**: +1-555-123-4567, +44-20-7946-0958, +91-98765-43210\n";
        $prompt .= "- **Addresses**: 123 Main St, New York, NY 10001, 456 Oxford St, London W1C 1JN\n";
        $prompt .= "- **Companies**: Acme Corp, Tech Solutions Inc, Global Enterprises Ltd\n";
        $prompt .= "- **Content**: Meaningful, realistic text that makes sense in context\n";
        $prompt .= "- **Passwords**: TestPass123!, SecureP@ssw0rd, MyStr0ng#Pass\n";
        $prompt .= "- **URLs**: https://example.com, https://test-site.org, https://demo.net\n";
        
        $prompt .= "\n## COMPREHENSIVE EXPECTED RESULTS\n";
        $prompt .= "Define specific, measurable outcomes:\n";
        $prompt .= "- **Success Indicators**: Page loads, forms submit, data saves, redirects occur\n";
        $prompt .= "- **Visual Feedback**: Success messages, error alerts, loading states, UI changes\n";
        $prompt .= "- **Data Validation**: Correct data storage, retrieval, and display\n";
        $prompt .= "- **Error Handling**: Proper validation messages, graceful error recovery\n";
        $prompt .= "- **Performance Metrics**: Page load times, response times, resource usage\n";
        $prompt .= "- **Accessibility**: Keyboard navigation, screen reader compatibility\n";
        
        $prompt .= "\n## STRICT JSON RESPONSE FORMAT\n";
        $prompt .= "Generate ONLY a valid JSON response with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"flow_name\": \"[Specific, descriptive name that clearly indicates the test scenario]\",\n";
        $prompt .= "  \"flow_type\": \"[navigation|form|ecommerce|content|admin|user_management|settings|security|api|integration]\",\n";
        $prompt .= "  \"description\": \"[Detailed explanation of what this flow tests, why it's important, and what bugs it might catch]\",\n";
        $prompt .= "  \"steps\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"action\": \"[visit|click|fill|select|wait|hover|scroll|keyboard|upload|drag]\",\n";
        $prompt .= "      \"target\": \"[Precise CSS selector or URL]\",\n";
        $prompt .= "      \"value\": \"[Realistic test data for fill/select actions]\",\n";
        $prompt .= "      \"expected_result\": \"[Specific, measurable outcome with success criteria]\",\n";
        $prompt .= "      \"wait_time\": [Optional: seconds to wait after this step]\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"priority\": \"[high|medium|low based on business impact and user frequency]\",\n";
        $prompt .= "  \"tags\": [\"[relevant tags for categorization and filtering]\"]\n";
        $prompt .= "}\n\n";
        
        $prompt .= "## EXPERT QUALITY STANDARDS\n";
        $prompt .= "1. **Flow name must be specific and actionable** - Include the exact functionality being tested\n";
        $prompt .= "2. **Steps must be logical and sequential** - Each step should build naturally on the previous\n";
        $prompt .= "3. **Include both positive and negative test cases** - Test success paths AND failure scenarios\n";
        $prompt .= "4. **Use realistic, varied test data** - No placeholder text, use actual user-like data\n";
        $prompt .= "5. **Specify measurable outcomes** - Each step should have clear success/failure criteria\n";
        $prompt .= "6. **Consider different user personas** - Admin users, regular users, guests, etc.\n";
        $prompt .= "7. **Test accessibility and usability** - Include keyboard navigation and screen reader considerations\n";
        $prompt .= "8. **Focus on business-critical functionality** - Prioritize features that impact user experience and business goals\n";
        
        $prompt .= "\n## EXCELLENT FLOW NAME EXAMPLES\n";
        $prompt .= "- \"Complete User Registration with Email Verification and Profile Setup\"\n";
        $prompt .= "- \"E-commerce Product Purchase with Payment Gateway and Order Confirmation\"\n";
        $prompt .= "- \"Content Creation with Media Upload, SEO Settings, and Publishing Workflow\"\n";
        $prompt .= "- \"Admin User Management with Role Assignment and Permission Validation\"\n";
        $prompt .= "- \"Contact Form Submission with Validation, Email Notification, and Success Feedback\"\n";
        $prompt .= "- \"Search Functionality with Filters, Sorting, and Result Pagination\"\n";
        $prompt .= "- \"User Login with Remember Me, Password Reset, and Account Lockout Protection\"\n";
        
        $prompt .= "\n## CONCRETE TESTING EXAMPLES\n";
        $prompt .= "**For a Contact Form Page:**\n";
        $prompt .= "- Test valid form submission with all fields\n";
        $prompt .= "- Test form validation with missing required fields\n";
        $prompt .= "- Test email format validation\n";
        $prompt .= "- Test form submission with special characters\n";
        $prompt .= "- Test form reset/clear functionality\n";
        $prompt .= "- Test form submission with very long text\n";
        
        $prompt .= "\n**For an E-commerce Product Page:**\n";
        $prompt .= "- Test product image gallery and zoom functionality\n";
        $prompt .= "- Test add to cart with different quantities\n";
        $prompt .= "- Test product reviews and rating system\n";
        $prompt .= "- Test related products and recommendations\n";
        $prompt .= "- Test product sharing and wishlist functionality\n";
        $prompt .= "- Test mobile responsiveness and touch interactions\n";
        
        $prompt .= "\n## FINAL INSTRUCTIONS\n";
        $prompt .= "Create a comprehensive, realistic test flow that thoroughly validates the functionality of this page. Think like a real user who wants to accomplish specific tasks. Focus on scenarios that would actually break in production and cause user frustration. Make every step actionable, specific, and valuable for catching real bugs.\n\n";
        $prompt .= "Remember: Quality over quantity. Better to have 5 well-thought-out steps than 15 generic ones.";
        
        return $prompt;
    }
    
    /**
     * Call AI API (using OpenAI or similar)
     */
    private function call_ai_api($prompt) {
        $model_config = $this->get_current_model_config();
        $api_key = get_option('wp_tester_ai_api_key', '');
        
        if (empty($api_key)) {
            // Fallback to rule-based generation
            return $this->generate_fallback_flow($prompt);
        }
        
        // Call the appropriate API based on the model
        switch ($model_config['provider']) {
            case 'OpenAI':
                return $this->call_openai_api($prompt, $model_config, $api_key);
            case 'Google':
                return $this->call_gemini_api($prompt, $model_config, $api_key);
            case 'X.AI':
                return $this->call_grok_api($prompt, $model_config, $api_key);
            case 'DeepSeek':
                return $this->call_deepseek_api($prompt, $model_config, $api_key);
            case 'Hugging Face':
                return $this->call_huggingface_api($prompt, $model_config, $api_key);
            case 'Anthropic':
                return $this->call_claude_api($prompt, $model_config, $api_key);
            case 'Mistral AI':
                return $this->call_mistral_api($prompt, $model_config, $api_key);
            case 'Meta':
                return $this->call_meta_api($prompt, $model_config, $api_key);
            default:
                return $this->generate_fallback_flow($prompt);
        }
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai_api($prompt, $model_config, $api_key) {
        $system_message = "You are a senior WordPress QA automation engineer with 15+ years of experience. You excel at creating comprehensive, realistic test flows that catch real-world bugs and validate critical user journeys. Your responses are always in valid JSON format and focus on actionable, specific test scenarios that provide real value for automated testing.";
        
        $response = wp_remote_post($model_config['api_url'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $this->ai_model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_message
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => $model_config['max_tokens'],
                'temperature' => $model_config['temperature']
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call Google Gemini API
     */
    private function call_gemini_api($prompt, $model_config, $api_key) {
        $system_instruction = "You are a senior WordPress QA automation engineer with 15+ years of experience. You excel at creating comprehensive, realistic test flows that catch real-world bugs and validate critical user journeys. Your responses are always in valid JSON format and focus on actionable, specific test scenarios that provide real value for automated testing.";
        
        $response = wp_remote_post($model_config['api_url'] . '?key=' . $api_key, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $system_instruction . "\n\n" . $prompt)
                        )
                    )
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => $model_config['max_tokens'],
                    'temperature' => $model_config['temperature']
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $data['candidates'][0]['content']['parts'][0]['text'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call Grok API (X.AI)
     */
    private function call_grok_api($prompt, $model_config, $api_key) {
        $system_message = "You are a senior WordPress QA automation engineer with 15+ years of experience. You excel at creating comprehensive, realistic test flows that catch real-world bugs and validate critical user journeys. Your responses are always in valid JSON format and focus on actionable, specific test scenarios that provide real value for automated testing.";
        
        $response = wp_remote_post($model_config['api_url'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => 'grok-beta',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_message
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => $model_config['max_tokens'],
                'temperature' => $model_config['temperature']
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call DeepSeek API
     */
    private function call_deepseek_api($prompt, $model_config, $api_key) {
        $response = wp_remote_post($model_config['api_url'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $this->ai_model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => $model_config['max_tokens'],
                'temperature' => $model_config['temperature']
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call Hugging Face API
     */
    private function call_huggingface_api($prompt, $model_config, $api_key) {
        $response = wp_remote_post($model_config['api_url'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'inputs' => $prompt,
                'parameters' => array(
                    'max_new_tokens' => $model_config['max_tokens'],
                    'temperature' => $model_config['temperature'],
                    'return_full_text' => false
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data[0]['generated_text'])) {
            $content = $data[0]['generated_text'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call Claude API (Anthropic)
     */
    private function call_claude_api($prompt, $model_config, $api_key) {
        $response = wp_remote_post($model_config['api_url'], array(
            'headers' => array(
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ),
            'body' => wp_json_encode(array(
                'model' => $this->ai_model,
                'max_tokens' => $model_config['max_tokens'],
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data['content'][0]['text'])) {
            $content = $data['content'][0]['text'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call Mistral API
     */
    private function call_mistral_api($prompt, $model_config, $api_key) {
        $response = wp_remote_post($model_config['api_url'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $this->ai_model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => $model_config['max_tokens'],
                'temperature' => $model_config['temperature']
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_fallback_flow($prompt);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body ?: '{}', true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            return json_decode($content, true);
        }
        
        return $this->generate_fallback_flow($prompt);
    }
    
    /**
     * Call Meta API (Code LLaMA via Hugging Face)
     */
    private function call_meta_api($prompt, $model_config, $api_key) {
        // Meta models are typically accessed via Hugging Face
        return $this->call_huggingface_api($prompt, $model_config, $api_key);
    }
    
    /**
     * Generate fallback flow when AI is not available
     */
    private function generate_fallback_flow($prompt) {
        // Extract page info from prompt
        preg_match('/Page URL: ([^\n]+)/', $prompt, $url_matches);
        preg_match('/Page Title: ([^\n]+)/', $prompt, $title_matches);
        preg_match('/Area: ([^\n]+)/', $prompt, $area_matches);
        
        $url = $url_matches[1] ?? '';
        $title = $title_matches[1] ?? 'Unknown Page';
        $area = $area_matches[1] ?? 'frontend';
        
        // Generate basic flow based on page type
        $flow_name = $this->generate_unique_flow_name($title, $area);
        $flow_type = $this->determine_flow_type($url, $title, $area);
        $steps = $this->generate_basic_steps($url, $area);
        
        return array(
            'flow' => array(
                'flow_name' => $flow_name,
                'flow_type' => $flow_type,
                'description' => "Basic test flow for {$title}",
                'steps' => $steps,
                'priority' => 'medium',
                'tags' => array($area, 'auto-generated')
            )
        );
    }
    
    /**
     * Parse AI response and format for database
     */
    private function parse_ai_response($ai_response, $page, $area) {
        $flow = $ai_response['flow'];
        
        return array(
            'flow_name' => $flow['flow_name'] ?? $this->generate_unique_flow_name($page['title'], $area),
            'flow_type' => $flow['flow_type'] ?? 'navigation',
            'start_url' => $page['url'],
            'steps' => $flow['steps'] ?? array(),
            'description' => $flow['description'] ?? "AI-generated flow for {$page['title']}",
            'priority' => $flow['priority'] ?? 'medium',
            'tags' => $flow['tags'] ?? array($area, 'ai-generated'),
            'is_active' => true,
            'created_by' => 'ai'
        );
    }
    
    /**
     * Generate unique flow name
     */
    private function generate_unique_flow_name($title, $area) {
        $base_name = "Test {$title} - {$area}";
        $counter = 1;
        $flow_name = $base_name;
        
        while ($this->flow_name_exists($flow_name)) {
            $flow_name = "{$base_name} ({$counter})";
            $counter++;
        }
        
        return $flow_name;
    }
    
    /**
     * Check if flow name already exists
     */
    private function flow_name_exists($flow_name) {
        global $wpdb;
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$flows_table} WHERE flow_name = %s",
            $flow_name
        ));
        
        return $exists > 0;
    }
    
    /**
     * Check if flow is unique
     */
    private function is_unique_flow($flow_data) {
        global $wpdb;
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        
        // Check for similar flows
        $similar = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$flows_table} 
             WHERE flow_name = %s OR (start_url = %s AND flow_type = %s)",
            $flow_data['flow_name'],
            $flow_data['start_url'],
            $flow_data['flow_type']
        ));
        
        return $similar == 0;
    }
    
    /**
     * Save AI-generated flow to database
     */
    private function save_ai_generated_flow($flow_data) {
        try {
            return $this->database->save_flow(
                $flow_data['flow_name'] ?? 'AI Generated Flow',
                $flow_data['flow_type'] ?? 'ai_generated',
                $flow_data['start_url'] ?? '',
                $flow_data['steps'] ?? [],
                $flow_data['expected_outcome'] ?? '',
                $flow_data['priority'] ?? 5,
                true, // ai_generated = true
                $this->ai_model // ai_provider
            );
        } catch (Exception $e) {
            error_log('WP Tester: Failed to save AI flow: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get site analysis for AI context
     */
    private function get_site_analysis() {
        $theme = wp_get_theme();
        $page_counts = wp_count_posts('page');
        $post_counts = wp_count_posts('post');
        
        return array(
            'type' => class_exists('WooCommerce') ? 'ecommerce' : 'content',
            'theme' => $theme ? $theme->get('Name') : 'Unknown',
            'plugins' => $this->get_active_plugins(),
            'pages_count' => $page_counts ? $page_counts->publish : 0,
            'posts_count' => $post_counts ? $post_counts->publish : 0
        );
    }
    
    /**
     * Get active plugins list
     */
    private function get_active_plugins() {
        $active_plugins = get_option('active_plugins', array());
        $plugins = array();
        
        foreach (($active_plugins ?? []) as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugins[] = $plugin_data['Name'];
        }
        
        return $plugins;
    }
    
    /**
     * Helper methods for page discovery
     */
    private function find_contact_page() {
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 1,
            'meta_query' => array(
                array(
                    'key' => '_wp_page_template',
                    'value' => 'contact',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        return !empty($pages) ? get_permalink($pages[0]->ID) : null;
    }
    
    private function find_about_page() {
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 1,
            's' => 'about'
        ));
        
        return !empty($pages) ? get_permalink($pages[0]->ID) : null;
    }
    
    private function find_shop_page() {
        if (class_exists('WooCommerce')) {
            $shop_page_id = wc_get_page_id('shop');
            return $shop_page_id ? get_permalink($shop_page_id) : null;
        }
        return null;
    }
    
    private function page_exists_in_list($url) {
        foreach ($this->frontend_pages as $page) {
            if ($page['url'] === $url) {
                return true;
            }
        }
        return false;
    }
    
    private function categorize_admin_page($name) {
        $categories = array(
            'content' => array('posts', 'pages', 'media', 'comments'),
            'appearance' => array('appearance', 'themes'),
            'plugins' => array('plugins'),
            'users' => array('users'),
            'tools' => array('tools'),
            'settings' => array('settings', 'options'),
            'ecommerce' => array('woo_', 'shop_'),
            'wp_tester' => array('wp_tester')
        );
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($name, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }
    
    private function determine_flow_type($url, $title, $area) {
        if ($area === 'admin') {
            return 'admin';
        }
        
        if (strpos($url, 'shop') !== false || strpos($url, 'cart') !== false || strpos($url, 'checkout') !== false) {
            return 'ecommerce';
        }
        
        if (strpos($url, 'contact') !== false || strpos($title, 'contact') !== false) {
            return 'form';
        }
        
        return 'navigation';
    }
    
    private function generate_basic_steps($url, $area) {
        $steps = array();
        
        if ($area === 'admin') {
            $steps[] = array(
                'action' => 'visit',
                'target' => $url,
                'value' => '',
                'expected_result' => 'Admin page loads successfully'
            );
            
            $steps[] = array(
                'action' => 'wait',
                'target' => '2',
                'value' => '',
                'expected_result' => 'Page fully loads'
            );
        } else {
            $steps[] = array(
                'action' => 'visit',
                'target' => $url,
                'value' => '',
                'expected_result' => 'Page loads successfully'
            );
            
            $steps[] = array(
                'action' => 'wait',
                'target' => '1',
                'value' => '',
                'expected_result' => 'Page content is visible'
            );
        }
        
        return $steps;
    }
    
    /**
     * Get available plugins for selection
     */
    public function get_available_plugins() {
        $plugins = array();
        $active_plugins = get_option('active_plugins', array());
        
        foreach (($active_plugins ?? []) as $plugin_file) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            $plugin_slug = dirname($plugin_file);
            
            // Skip WP Tester itself
            if ($plugin_slug === 'WP-Tester') {
                continue;
            }
            
            if ($plugin_data && is_array($plugin_data)) {
                $plugins[] = array(
                    'slug' => $plugin_slug,
                    'name' => $plugin_data['Name'] ?? 'Unknown Plugin',
                    'description' => wp_strip_all_tags($plugin_data['Description'] ?? ''),
                    'version' => $plugin_data['Version'] ?? '1.0',
                    'author' => wp_strip_all_tags($plugin_data['Author'] ?? 'Unknown'),
                    'author_uri' => $plugin_data['AuthorURI'] ?? '',
                    'file' => $plugin_file,
                    'type' => $this->detect_plugin_type($plugin_data['Name'] ?? '', wp_strip_all_tags($plugin_data['Description'] ?? ''))
                );
            }
        }
        
        // Sort by name
        usort($plugins, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $plugins;
    }
    
    /**
     * Detect plugin type based on name and description
     */
    private function detect_plugin_type($name, $description) {
        $text = strtolower($name . ' ' . $description);
        
        if (strpos($text, 'woocommerce') !== false || strpos($text, 'ecommerce') !== false || strpos($text, 'shop') !== false) {
            return 'ecommerce';
        } elseif (strpos($text, 'contact') !== false || strpos($text, 'form') !== false) {
            return 'contact_form';
        } elseif (strpos($text, 'seo') !== false) {
            return 'seo';
        } elseif (strpos($text, 'security') !== false || strpos($text, 'firewall') !== false) {
            return 'security';
        } elseif (strpos($text, 'backup') !== false) {
            return 'backup';
        } elseif (strpos($text, 'cache') !== false || strpos($text, 'performance') !== false) {
            return 'performance';
        } elseif (strpos($text, 'gallery') !== false || strpos($text, 'media') !== false) {
            return 'media';
        } elseif (strpos($text, 'social') !== false) {
            return 'social';
        } elseif (strpos($text, 'analytics') !== false || strpos($text, 'tracking') !== false) {
            return 'analytics';
        } elseif (strpos($text, 'membership') !== false || strpos($text, 'user') !== false) {
            return 'membership';
        } elseif (strpos($text, 'page builder') !== false || strpos($text, 'builder') !== false) {
            return 'page_builder';
        } else {
            return 'general';
        }
    }
}
