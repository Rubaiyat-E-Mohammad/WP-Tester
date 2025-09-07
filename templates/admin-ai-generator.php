<?php
/**
 * AI Flow Generator Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current AI settings
$ai_api_key = get_option('wp_tester_ai_api_key', '');
$ai_api_provider = get_option('wp_tester_ai_api_provider', 'openai');
$has_api_key = !empty($ai_api_key);

// Get site analysis
$theme = wp_get_theme();
$page_counts = wp_count_posts('page');
$post_counts = wp_count_posts('post');
$active_plugins = get_option('active_plugins', array());

$site_analysis = array(
    'type' => class_exists('WooCommerce') ? 'E-commerce' : 'Content/Blog',
    'theme' => $theme ? $theme->get('Name') : 'Unknown',
    'pages_count' => $page_counts ? $page_counts->publish : 0,
    'posts_count' => $post_counts ? $post_counts->publish : 0,
    'plugins_count' => is_array($active_plugins) ? count($active_plugins) : 0
);

// Get available plugins
$ai_generator = new WP_Tester_AI_Flow_Generator();
$available_plugins = $ai_generator->get_available_plugins();
?>

<div class="wrap">
    <!-- Modern Header -->
    <div class="modern-header" style="background: linear-gradient(135deg, #1FC09A 0%, #0F9D7A 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; color: white; flex-shrink: 0;">
                    <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/WP Tester Logo.png'); ?>" 
                         alt="WP Tester" style="width: 100%; height: 100%; border-radius: 8px; object-fit: contain;" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 1.2rem; font-weight: bold;">WP</div>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 2rem; font-weight: 700;">AI Flow Generator</h1>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 1.1rem;">Intelligent flow detection for frontend and backend</p>
                </div>
            </div>
        </div>
        <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
        <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%; z-index: 1;"></div>
    </div>

    <!-- AI Configuration Card -->
    <div class="modern-card" style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; justify-content: between; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; color: #1f2937; font-size: 1.5rem; font-weight: 600;">AI Configuration</h2>
            <div class="status-badge <?php echo $has_api_key ? 'success' : 'warning'; ?>" style="margin-left: auto;">
                <?php echo $has_api_key ? 'AI Enabled' : 'Fallback Mode'; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- API Key Configuration -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.125rem; font-weight: 600;">API Configuration</h3>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        AI Provider
                    </label>
                    <select id="ai-provider" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <option value="openai" <?php selected($ai_api_provider, 'openai'); ?>>OpenAI (GPT-3.5-turbo)</option>
                        <option value="anthropic" <?php selected($ai_api_provider, 'anthropic'); ?>>Anthropic (Claude) - Coming Soon</option>
                        <option value="local" <?php selected($ai_api_provider, 'local'); ?>>Local Model - Coming Soon</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        API Key
                    </label>
                    <input type="password" id="ai-api-key" 
                           value="<?php echo esc_attr($ai_api_key); ?>"
                           placeholder="Enter your API key (optional - uses fallback if empty)"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Get your free API key from <a href="https://platform.openai.com/api-keys" target="_blank" style="color: #1FC09A;">OpenAI Platform</a>
                    </p>
                </div>
                
                <button id="save-api-key" class="modern-btn modern-btn-primary">
                    <span class="dashicons dashicons-saved"></span>
                    Save API Key
                </button>
                
                <!-- AI Provider Selection -->
                <div style="margin-top: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        AI Provider
                    </label>
                    <select id="ai-provider" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <option value="free">Free Models (No API Key Required)</option>
                        <option value="openai">OpenAI (Paid - API Key Required)</option>
                        <option value="anthropic">Anthropic (Paid - API Key Required)</option>
                        <option value="google">Google (Paid - API Key Required)</option>
                        <option value="xai">X.AI (Paid - API Key Required)</option>
                        <option value="deepseek">DeepSeek (Paid - API Key Required)</option>
                        <option value="mistral">Mistral AI (Paid - API Key Required)</option>
                        <option value="cohere">Cohere (Paid - API Key Required)</option>
                        <option value="perplexity">Perplexity (Paid - API Key Required)</option>
                    </select>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Choose your AI provider. Free models work without API keys, paid models require API keys.
                    </p>
                </div>
                
                <!-- AI Model Selection -->
                <div id="ai-model-section" style="margin-top: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        AI Model
                    </label>
                    <select id="ai-model" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <!-- Free models will be loaded here -->
                    </select>
                    <p id="ai-model-description" style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Free models are recommended for testing and don't require API keys.
                    </p>
                </div>
            </div>
            
            <!-- Site Analysis -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.125rem; font-weight: 600;">Site Analysis</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1FC09A; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['type']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Site Type</div>
                    </div>
                    
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1FC09A; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['pages_count']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Pages</div>
                    </div>
                    
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1FC09A; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['posts_count']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Posts</div>
                    </div>
                    
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #1FC09A; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['plugins_count']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Plugins</div>
                    </div>
                </div>
                
                <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #1FC09A;">
                    <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">Theme: <?php echo esc_html($site_analysis['theme']); ?></div>
                    <div style="font-size: 0.875rem; color: #1e40af;">
                        AI will analyze your site structure and generate intelligent test flows based on your content and functionality.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flow Generation Options -->
    <div class="modern-card" style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <h2 style="margin: 0 0 1.5rem 0; color: #1f2937; font-size: 1.5rem; font-weight: 600;">Flow Generation Options</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Target Areas -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.125rem; font-weight: 600;">Target Areas</h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="include-frontend" checked style="width: 1.25rem; height: 1.25rem; accent-color: #1FC09A;">
                        <div>
                            <div style="font-weight: 600; color: #374151;">Frontend Pages</div>
                            <div style="font-size: 0.8125rem; color: #64748b;">Public pages, posts, and user-facing content</div>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="include-admin" checked style="width: 1.25rem; height: 1.25rem; accent-color: #1FC09A;">
                        <div>
                            <div style="font-weight: 600; color: #374151;">Admin Panel</div>
                            <div style="font-size: 0.8125rem; color: #64748b;">WordPress admin, settings, and management areas</div>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="include-plugins" style="width: 1.25rem; height: 1.25rem; accent-color: #1FC09A;">
                        <div>
                            <div style="font-weight: 600; color: #374151;">Plugin-Specific Flows</div>
                            <div style="font-size: 0.8125rem; color: #64748b;">AI-generated flows for selected plugins</div>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Generation Settings -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.125rem; font-weight: 600;">Generation Settings</h3>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        Max Flows per Area
                    </label>
                    <input type="number" id="max-flows" value="10" min="1" max="50" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Maximum number of flows to generate for each area
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        Max Flows per Plugin
                    </label>
                    <input type="number" id="max-flows-per-plugin" value="5" min="1" max="20" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Maximum number of flows to generate for each selected plugin
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        Focus Areas
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="ecommerce" checked style="accent-color: #1FC09A;">
                            <span style="font-size: 0.875rem;">E-commerce</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="content" checked style="accent-color: #1FC09A;">
                            <span style="font-size: 0.875rem;">Content</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="user_management" checked style="accent-color: #1FC09A;">
                            <span style="font-size: 0.875rem;">User Management</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="settings" checked style="accent-color: #1FC09A;">
                            <span style="font-size: 0.875rem;">Settings</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Plugin Selection -->
        <div id="plugin-selection-section" style="display: none; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h3 style="margin: 0 0 1rem 0; color: #374151; font-size: 1.125rem; font-weight: 600;">Select Plugins for Flow Generation</h3>
            <p style="margin: 0 0 1.5rem 0; color: #64748b; font-size: 0.875rem;">
                Choose which plugins should have AI-generated test flows created. AI will analyze each plugin's functionality and create relevant test scenarios.
            </p>
            
            <div class="plugin-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; max-height: 500px; overflow-y: auto; padding: 0.5rem;">
                <?php foreach ($available_plugins as $plugin): ?>
                <div class="plugin-card" style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem; background: white; transition: all 0.3s ease; cursor: pointer; position: relative; min-height: 200px; display: flex; flex-direction: column;" 
                     data-plugin-slug="<?php echo esc_attr($plugin['slug']); ?>"
                     onclick="togglePluginSelection('<?php echo esc_attr($plugin['slug']); ?>')">
                    
                    <!-- Plugin Logo -->
                    <div style="display: flex; justify-content: center; margin-bottom: 0.75rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #1FC09A, #0ea5e9); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.25rem;">
                            <?php 
                            $plugin_name = $plugin['name'];
                            $initials = '';
                            $words = explode(' ', $plugin_name);
                            foreach ($words as $word) {
                                if (strpos($word, 'WP') === 0) {
                                    $initials .= 'W';
                                } else {
                                    $initials .= strtoupper(substr($word, 0, 1));
                                }
                                if (strlen($initials) >= 2) break;
                            }
                            echo esc_html($initials);
                            ?>
                        </div>
                    </div>
                    
                    <!-- Plugin Name -->
                    <div style="text-align: center; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; font-weight: 600; color: #374151; font-size: 0.875rem; line-height: 1.2;"><?php echo esc_html($plugin['name']); ?></h4>
                    </div>
                    
                    <!-- Plugin Type Badge -->
                    <div style="text-align: center; margin-bottom: 0.75rem;">
                        <span class="plugin-type-badge" style="padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.6875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; background: #e0f2fe; color: #0369a1;">
                            <?php echo esc_html($plugin['type']); ?>
                        </span>
                    </div>
                    
                    <!-- Plugin Description -->
                    <div style="flex: 1; text-align: center; margin-bottom: 0.75rem;">
                        <p style="margin: 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">
                            <?php echo esc_html(wp_trim_words($plugin['description'] ?? '', 8)); ?>
                        </p>
                    </div>
                    
                    <!-- Plugin Version & Author -->
                    <div style="text-align: center; margin-bottom: 0.5rem;">
                        <div style="font-size: 0.6875rem; color: #9ca3af;">
                            v<?php echo esc_html($plugin['version']); ?> • 
                            <?php if (!empty($plugin['author_uri'])): ?>
                                <a href="<?php echo esc_url($plugin['author_uri']); ?>" target="_blank" style="color: #1FC09A; text-decoration: none;">
                                    <?php echo esc_html($plugin['author']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($plugin['author']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Selection Indicator -->
                    <div class="plugin-selection-indicator" style="position: absolute; top: 0.75rem; right: 0.75rem; width: 20px; height: 20px; border: 2px solid #e5e7eb; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                        <div class="checkmark" style="width: 10px; height: 10px; background: #1FC09A; border-radius: 50%; opacity: 0; transition: opacity 0.2s ease;"></div>
                    </div>
                    
                    <!-- Hidden checkbox for form submission -->
                    <input type="checkbox" class="plugin-checkbox" value="<?php echo esc_attr($plugin['slug']); ?>" style="display: none;">
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($available_plugins)): ?>
            <div style="text-align: center; padding: 2rem; color: #64748b;">
                <div class="dashicons dashicons-admin-plugins" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></div>
                <p style="margin: 0; font-size: 1.125rem;">No plugins available for flow generation</p>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Install and activate some plugins to generate plugin-specific test flows</p>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <button id="select-all-plugins" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Select All
                </button>
                <button id="deselect-all-plugins" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-dismiss"></span>
                    Deselect All
                </button>
                <span id="selected-plugins-count" style="font-size: 0.875rem; color: #64748b; margin-left: auto;">
                    0 plugins selected
                </span>
            </div>
        </div>
    </div>

    <!-- Generate Flows Button -->
    <div class="modern-card" style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;">
        <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 1.5rem; font-weight: 600;">Ready to Generate AI Flows?</h2>
        <p style="margin: 0 0 2rem 0; color: #64748b; font-size: 1.125rem;">
            AI will analyze your site and create intelligent test flows for both frontend and backend areas.
        </p>
        
        <button id="generate-ai-flows" class="modern-btn modern-btn-primary" style="padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600;">
            <span class="dashicons dashicons-admin-generic"></span>
            Generate AI Flows
        </button>
        
        <div id="generation-progress" style="display: none; margin-top: 2rem;">
            <div style="background: #f3f4f6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; justify-content: between; margin-bottom: 0.5rem;">
                    <span style="font-weight: 600; color: #374151;">Generating Flows...</span>
                    <span id="progress-percentage" style="font-weight: 600; color: #1FC09A;">0%</span>
                </div>
                <div style="background: #e5e7eb; border-radius: 4px; height: 8px; overflow: hidden;">
                    <div id="progress-bar" style="background: linear-gradient(90deg, #1FC09A, #0F9D7A); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                </div>
            </div>
            <div id="generation-status" style="font-size: 0.875rem; color: #64748b; text-align: center;">
                Initializing AI flow generation...
            </div>
        </div>
    </div>

    <!-- Recent AI Generated Flows -->
    <div class="modern-card" style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold; color: #1FC09A; flex-shrink: 0; margin-right: 0.5rem;">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/WP Tester Logo.png'); ?>" 
                     alt="WP Tester" style="width: 100%; height: 100%; border-radius: 8px; object-fit: contain;" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 0.9rem; font-weight: bold;">WP</div>
            </div>
            <div style="flex: 1;">
                <h2 style="margin: 0; color: #1f2937; font-size: 1.5rem; font-weight: 600; line-height: 1.2;">Recent AI Generated Flows</h2>
            </div>
        </div>
        
        <div id="ai-flows-list">
            <div style="text-align: center; padding: 2rem; color: #64748b;">
                <div class="dashicons dashicons-admin-generic" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></div>
                <p style="margin: 0; font-size: 1.125rem;">No AI flows generated yet</p>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Click "Generate AI Flows" to create intelligent test flows</p>
            </div>
        </div>
    </div>
</div>

<style>
.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #1FC09A 0%, #0F9D7A 100%);
    color: white;
}

.modern-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 192, 154, 0.3);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-badge.success {
    background: #dcfce7;
    color: #166534;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.dashicons {
    font-family: dashicons;
    font-size: 1.25rem;
    line-height: 1;
}

.plugin-card:hover {
    border-color: #1FC09A;
    background: #f0fdfa;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 192, 154, 0.1);
}

.plugin-card input[type="checkbox"]:checked + div {
    color: #1FC09A;
}

.plugin-type-badge {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .plugin-card {
        min-height: 100px !important;
    }
    
    .plugin-card label {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .plugin-card input[type="checkbox"] {
        margin-top: 0 !important;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ensure ajaxurl is available
    if (typeof ajaxurl === 'undefined') {
        ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    
    // Save API Key
    $('#save-api-key').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-update-alt"></span> Saving...').prop('disabled', true);
        
        const apiKey = $('#ai-api-key').val();
        const provider = $('#ai-provider').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_set_ai_api_key',
                api_key: apiKey,
                api_provider: provider,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessModal('API Key Saved', 'API key saved successfully!');
                    location.reload(); // Reload to update status
                } else {
                    showErrorModal('API Key Save Failed', 'Failed to save API key: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
            },
            complete: function() {
                // Always reset button state, even on success
                setTimeout(function() {
                    button.html(originalText).prop('disabled', false);
                }, 1000);
            }
        });
    });
    
    // Plugin selection toggle
    $('#include-plugins').on('change', function() {
        if ($(this).is(':checked')) {
            $('#plugin-selection-section').slideDown(300);
        } else {
            $('#plugin-selection-section').slideUp(300);
        }
    });
    
    // Plugin selection handlers
    $('.plugin-checkbox').on('change', function() {
        updateSelectedPluginsCount();
    });
    
    $('#select-all-plugins').on('click', function() {
        $('.plugin-card').each(function() {
            const pluginSlug = $(this).data('plugin-slug');
            const checkbox = $(this).find('.plugin-checkbox');
            if (!checkbox.prop('checked')) {
                togglePluginSelection(pluginSlug);
            }
        });
    });
    
    $('#deselect-all-plugins').on('click', function() {
        $('.plugin-card').each(function() {
            const pluginSlug = $(this).data('plugin-slug');
            const checkbox = $(this).find('.plugin-checkbox');
            if (checkbox.prop('checked')) {
                togglePluginSelection(pluginSlug);
            }
        });
    });
    
    function updateSelectedPluginsCount() {
        const selectedCount = $('.plugin-checkbox:checked').length;
        $('#selected-plugins-count').text(selectedCount + ' plugin' + (selectedCount !== 1 ? 's' : '') + ' selected');
    }
    
    // Toggle plugin selection
    function togglePluginSelection(pluginSlug) {
        const card = $(`.plugin-card[data-plugin-slug="${pluginSlug}"]`);
        const checkbox = card.find('.plugin-checkbox');
        const indicator = card.find('.plugin-selection-indicator');
        const checkmark = indicator.find('.checkmark');
        
        // Toggle checkbox
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        // Update visual state
        if (checkbox.prop('checked')) {
            card.css({
                'border-color': '#1FC09A',
                'background-color': '#f0fdf4',
                'transform': 'translateY(-2px)',
                'box-shadow': '0 4px 12px rgba(31, 192, 154, 0.15)'
            });
            indicator.css('border-color', '#1FC09A');
            checkmark.css('opacity', '1');
        } else {
            card.css({
                'border-color': '#e5e7eb',
                'background-color': 'white',
                'transform': 'translateY(0)',
                'box-shadow': 'none'
            });
            indicator.css('border-color', '#e5e7eb');
            checkmark.css('opacity', '0');
        }
        
        updateSelectedPluginsCount();
    }
    
    // AI Provider and Model Selection
    let availableModels = {
        free_models: {},
        paid_models: {},
        models_by_provider: {}
    };
    
    // Load available models on page load
    function loadAvailableModels() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_get_available_ai_models',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    availableModels = response.data;
                    updateModelDropdown();
                }
            },
            error: function() {
                console.error('Failed to load AI models');
            }
        });
    }
    
    // Update model dropdown based on selected provider
    function updateModelDropdown() {
        const provider = $('#ai-provider').val();
        const modelSelect = $('#ai-model');
        const description = $('#ai-model-description');
        
        // Clear existing options
        modelSelect.empty();
        
        if (provider === 'free') {
            // Show free models
            Object.keys(availableModels.free_models).forEach(modelId => {
                const model = availableModels.free_models[modelId];
                const option = $('<option></option>')
                    .attr('value', modelId)
                    .text(`${model.name} (${model.provider}) - Free`);
                modelSelect.append(option);
            });
            description.text('Free models work without API keys and are recommended for testing.');
        } else {
            // Show paid models for selected provider
            const providerModels = availableModels.models_by_provider[provider] || {};
            Object.keys(providerModels).forEach(modelId => {
                const model = providerModels[modelId];
                if (!model.free_tier) { // Only show paid models
                    const option = $('<option></option>')
                        .attr('value', modelId)
                        .text(`${model.name} - Paid`);
                    modelSelect.append(option);
                }
            });
            description.text('Paid models require API keys and offer enhanced capabilities.');
        }
        
        // Set default selection
        if (modelSelect.find('option').length > 0) {
            modelSelect.val(modelSelect.find('option:first').val());
        }
    }
    
    // Provider change handler
    $('#ai-provider').on('change', function() {
        updateModelDropdown();
        
        // Show/hide API key section based on provider
        const provider = $(this).val();
        if (provider === 'free') {
            $('#api-key-section').hide();
        } else {
            $('#api-key-section').show();
        }
    });
    
    // Load models on page load
    loadAvailableModels();
    
    // Generate AI Flows
    $('#generate-ai-flows').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = button.html();
        
        // Get options
        const includeFrontend = $('#include-frontend').is(':checked');
        const includeAdmin = $('#include-admin').is(':checked');
        const includePlugins = $('#include-plugins').is(':checked');
        const maxFlows = $('#max-flows').val();
        const maxFlowsPerPlugin = $('#max-flows-per-plugin').val() || 5;
        const aiProvider = $('#ai-provider').val() || 'free';
        const aiModel = $('#ai-model').val() || 'gpt-3.5-turbo';
        const focusAreas = [];
        const selectedPlugins = [];
        
        $('input[type="checkbox"][value]').each(function() {
            if ($(this).is(':checked')) {
                focusAreas.push($(this).val());
            }
        });
        
        $('.plugin-checkbox:checked').each(function() {
            selectedPlugins.push($(this).val());
        });
        
        if (!includeFrontend && !includeAdmin && !includePlugins) {
            showErrorModal('Selection Required', 'Please select at least one target area (Frontend, Admin, or Plugins).');
            return;
        }
        
        if (includePlugins && selectedPlugins.length === 0) {
            showErrorModal('Plugin Selection Required', 'Please select at least one plugin for flow generation.');
            return;
        }
        
        // Show progress
        button.html('<span class="dashicons dashicons-update-alt"></span> Generating...').prop('disabled', true);
        $('#generation-progress').show();
        
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            $('#progress-bar').css('width', progress + '%');
            $('#progress-percentage').text(Math.round(progress) + '%');
            
            if (progress < 30) {
                $('#generation-status').text('Analyzing site structure...');
            } else if (progress < 60) {
                $('#generation-status').text('Generating frontend flows...');
            } else if (progress < 90) {
                $('#generation-status').text('Generating admin flows...');
            }
        }, 500);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_generate_ai_flows',
                include_frontend: includeFrontend,
                include_admin: includeAdmin,
                include_plugins: includePlugins,
                selected_plugins: selectedPlugins,
                max_flows_per_area: maxFlows,
                max_flows_per_plugin: maxFlowsPerPlugin,
                focus_areas: focusAreas.join(','),
                ai_provider: aiProvider,
                ai_model: aiModel,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            timeout: 300000, // 5 minutes
            success: function(response) {
                clearInterval(progressInterval);
                $('#progress-bar').css('width', '100%');
                $('#progress-percentage').text('100%');
                $('#generation-status').text('Generation completed!');
                
                if (response.success) {
                    setTimeout(function() {
                        showSuccessModal('AI Flows Generated', 'AI flows generated successfully!\n\n' + response.data.message);
                        location.reload(); // Reload to show new flows
                    }, 1000);
                } else {
                    showErrorModal('AI Generation Failed', 'AI flow generation failed: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                if (status === 'timeout') {
                    showErrorModal('Generation Timeout', 'Generation timed out. Please try again with fewer flows.');
                } else {
                    showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
                }
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
                setTimeout(function() {
                    $('#generation-progress').hide();
                }, 2000);
            }
        });
    });
});
</script>

<style>
/* Plugin card styling */
.plugin-card:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
    border-color: #1FC09A !important;
}

.plugin-card.selected {
    border-color: #1FC09A !important;
    background-color: #f0fdf4 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(31, 192, 154, 0.15) !important;
}

/* Responsive plugin grid */
.plugin-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

@media (max-width: 1200px) {
    .plugin-grid {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

@media (max-width: 900px) {
    .plugin-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 600px) {
    .plugin-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
