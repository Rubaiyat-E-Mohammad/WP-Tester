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
$ai_model = get_option('wp_tester_ai_model', '');
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

// Get AI generated flows
$database = new WP_Tester_Database();
// Ensure database schema is up to date
$database->update_flows_table_schema();
$ai_generated_flows = $database->get_ai_generated_flows(5);
?>

<div class="wrap">
    <!-- Modern Header -->
    <div class="modern-header" style="background: linear-gradient(135deg, #00265e 0%, #0F9D7A 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: white;">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 6.5V7.5C15 8.3 14.3 9 13.5 9H10.5C9.7 9 9 8.3 9 7.5V6.5L3 7V9L9 8.5V9.5C9 10.3 9.7 11 10.5 11H13.5C14.3 11 15 10.3 15 9.5V8.5L21 9ZM6.5 12C5.7 12 5 12.7 5 13.5V16.5C5 17.3 5.7 18 6.5 18H7.5V22H9V18H15V22H16.5V18H17.5C18.3 18 19 17.3 19 16.5V13.5C19 12.7 18.3 12 17.5 12H6.5Z" fill="currentColor"/>
                    </svg>
                    <div style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 1.2rem; font-weight: bold; color: white;">🤖</div>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 2rem; font-weight: 700; color: white;">AI Flow Generator</h1>
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
            <h2 style="margin: 0; color: #00265e; font-size: 1.5rem; font-weight: 600;">AI Configuration</h2>
            <div class="status-badge <?php echo $has_api_key ? 'success' : 'warning'; ?>" style="margin-left: auto;">
                <?php echo $has_api_key ? 'AI Enabled' : 'Fallback Mode'; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- AI Model Configuration -->
            <div>
                <!-- AI Model Selection -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #00265e; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        AI Model
                    </label>
                    <select id="ai-model-select" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <option value="">Select AI Model...</option>
                        <!-- Models will be loaded here -->
                    </select>
                    <p id="ai-model-description" style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Choose your AI model. Free models work without API keys, paid models require API keys.
                    </p>
                </div>
                
                <!-- API Key Input (Hidden by default) -->
                <div id="api-key-section" style="margin-bottom: 1.5rem; display: none;">
                    <label style="display: block; font-weight: 600; color: #00265e; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        API Key
                    </label>
                    <input type="password" id="ai-api-key" 
                           value="<?php echo esc_attr($ai_api_key); ?>"
                           placeholder="Enter your API key"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                    <p id="api-key-help" style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        <!-- API key help text will be loaded here -->
                    </p>
                </div>
                
                <!-- Save Button -->
                <button id="save-ai-config" class="modern-btn modern-btn-primary">
                    <span class="dashicons dashicons-saved"></span>
                    Save Configuration
                </button>
            </div>
            
            <!-- Site Analysis -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #00265e; font-size: 1.125rem; font-weight: 600;">Site Analysis</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #00265e; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['type']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Site Type</div>
                    </div>
                    
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #00265e; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['pages_count']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Pages</div>
                    </div>
                    
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #00265e; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['posts_count']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Posts</div>
                    </div>
                    
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #00265e; margin-bottom: 0.25rem;">
                            <?php echo esc_html($site_analysis['plugins_count']); ?>
                        </div>
                        <div style="font-size: 0.8125rem; color: #64748b;">Plugins</div>
                    </div>
                </div>
                
                <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #00265e;">
                    <div style="font-weight: 600; color: #00265e; margin-bottom: 0.5rem;">Theme: <?php echo esc_html($site_analysis['theme']); ?></div>
                    <div style="font-size: 0.875rem; color: #00265e;">
                        AI will analyze your site structure and generate intelligent test flows based on your content and functionality.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flow Generation Options -->
    <div class="modern-card" style="background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <h2 style="margin: 0 0 1.5rem 0; color: #00265e; font-size: 1.5rem; font-weight: 600;">Flow Generation Options</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Target Areas -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #00265e; font-size: 1.125rem; font-weight: 600;">Target Areas</h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="include-frontend" checked style="width: 1.25rem; height: 1.25rem; accent-color: #00265e;">
                        <div>
                            <div style="font-weight: 600; color: #00265e;">Frontend Pages</div>
                            <div style="font-size: 0.8125rem; color: #64748b;">Public pages, posts, and user-facing content</div>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="include-admin" checked style="width: 1.25rem; height: 1.25rem; accent-color: #00265e;">
                        <div>
                            <div style="font-weight: 600; color: #00265e;">Admin Panel</div>
                            <div style="font-size: 0.8125rem; color: #64748b;">WordPress admin, settings, and management areas</div>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="include-plugins" style="width: 1.25rem; height: 1.25rem; accent-color: #00265e;">
                        <div>
                            <div style="font-weight: 600; color: #00265e;">Plugin-Specific Flows</div>
                            <div style="font-size: 0.8125rem; color: #64748b;">AI-generated flows for selected plugins</div>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Generation Settings -->
            <div>
                <h3 style="margin: 0 0 1rem 0; color: #00265e; font-size: 1.125rem; font-weight: 600;">Generation Settings</h3>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #00265e; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        Max Flows per Area
                    </label>
                    <input type="number" id="max-flows" value="10" min="1" max="50" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Maximum number of flows to generate for each area
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #00265e; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        Max Flows per Plugin
                    </label>
                    <input type="number" id="max-flows-per-plugin" value="5" min="1" max="20" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Maximum number of flows to generate for each selected plugin
                    </p>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #00265e; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        Focus Areas
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="ecommerce" checked style="accent-color: #00265e;">
                            <span style="font-size: 0.875rem;">E-commerce</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="content" checked style="accent-color: #00265e;">
                            <span style="font-size: 0.875rem;">Content</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="user_management" checked style="accent-color: #00265e;">
                            <span style="font-size: 0.875rem;">User Management</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" value="settings" checked style="accent-color: #00265e;">
                            <span style="font-size: 0.875rem;">Settings</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Plugin Selection -->
        <div id="plugin-selection-section" style="display: none; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h3 style="margin: 0 0 1rem 0; color: #00265e; font-size: 1.125rem; font-weight: 600;">Select Plugins for Flow Generation</h3>
            <p style="margin: 0 0 1.5rem 0; color: #64748b; font-size: 0.875rem;">
                Choose which plugins should have AI-generated test flows created. AI will analyze each plugin's functionality and create relevant test scenarios.
            </p>
            
            <div class="plugin-cards-grid" style="max-height: 500px; overflow-y: auto; width: 100%; max-width: 100%;">
                <?php foreach ($available_plugins as $plugin): ?>
                <div class="plugin-card" 
                     data-plugin-slug="<?php echo esc_attr($plugin['slug']); ?>"
                     style="cursor: pointer; position: relative; background: white; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                    
                    <!-- Header with icon and selection -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 0;">
                            <div style="width: 32px; height: 32px; border-radius: 6px; background: linear-gradient(135deg, #1FC09A, #0ea5e9); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="dashicons dashicons-admin-plugins" style="color: white; font-size: 14px;"></span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h3 style="margin: 0; font-size: 0.875rem; font-weight: 700; color: #00265e; line-height: 1.2; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo esc_html($plugin['name']); ?>
                                </h3>
                                <div style="display: inline-block; padding: 0.125rem 0.5rem; background: #f0f9ff; color: #0369a1; border-radius: 12px; font-size: 0.625rem; font-weight: 600; text-transform: uppercase;">
                                    <?php echo esc_html($plugin['type']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selection Indicator -->
                        <div class="plugin-selection-indicator" style="width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; cursor: pointer; z-index: 10; flex-shrink: 0;">
                            <div class="checkmark" style="width: 8px; height: 8px; background: #1FC09A; border-radius: 50%; opacity: 0; transition: opacity 0.3s ease;"></div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <p style="margin: 0 0 0.75rem 0; font-size: 0.75rem; color: #64748b; line-height: 1.4; min-height: 2rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo esc_html(wp_trim_words($plugin['description'] ?? '', 8)); ?>
                    </p>
                    
                    <!-- Footer with version and author -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid #f1f5f9;">
                        <div style="font-size: 0.625rem; color: #9ca3af;">
                            <div style="font-weight: 600; color: #64748b;">v<?php echo esc_html($plugin['version']); ?></div>
                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($plugin['author']); ?></div>
                        </div>
                        <div style="font-size: 0.625rem; color: #1FC09A; font-weight: 600;">
                            Ready
                        </div>
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
        <h2 style="margin: 0 0 1rem 0; color: #00265e; font-size: 1.5rem; font-weight: 600;">Ready to Generate AI Flows?</h2>
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
                    <span style="font-weight: 600; color: #00265e;">Generating Flows...</span>
                    <span id="progress-percentage" style="font-weight: 600; color: #00265e;">0%</span>
                </div>
                <div style="background: #e5e7eb; border-radius: 4px; height: 8px; overflow: hidden;">
                    <div id="progress-bar" style="background: linear-gradient(90deg, #00265e, #0F9D7A); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
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
            <div style="width: 40px; height: 40px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #00265e;">
                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 6.5V7.5C15 8.3 14.3 9 13.5 9H10.5C9.7 9 9 8.3 9 7.5V6.5L3 7V9L9 8.5V9.5C9 10.3 9.7 11 10.5 11H13.5C14.3 11 15 10.3 15 9.5V8.5L21 9ZM6.5 12C5.7 12 5 12.7 5 13.5V16.5C5 17.3 5.7 18 6.5 18H7.5V22H9V18H15V22H16.5V18H17.5C18.3 18 19 17.3 19 16.5V13.5C19 12.7 18.3 12 17.5 12H6.5Z" fill="currentColor"/>
                </svg>
                <div style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 0.9rem; font-weight: bold; color: #00265e;">🤖</div>
            </div>
            <h2 style="margin: 0; color: #00265e; font-size: 1.5rem; font-weight: 600; line-height: 1.2;">Recent AI Generated Flows</h2>
        </div>
        
        <div id="ai-flows-list">
            <?php if (!empty($ai_generated_flows)): ?>
                <div class="modern-list">
                    <?php foreach ($ai_generated_flows as $flow): ?>
                        <div class="modern-list-item" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.75rem; background: white; transition: all 0.2s ease;">
                            <div class="item-info" style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; border-radius: 6px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span class="dashicons dashicons-admin-generic" style="color: #00265e; font-size: 16px;"></span>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: #00265e;"><?php echo esc_html($flow->flow_name); ?></h4>
                                        <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b;">
                                            <?php echo esc_html($flow->flow_type); ?> • 
                                            <?php echo esc_html(human_time_diff(strtotime($flow->created_at), current_time('timestamp'))); ?> ago
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="item-actions" style="display: flex; align-items: center; gap: 0.5rem;">
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                                    <span class="dashicons dashicons-visibility"></span>
                                    View
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-primary modern-btn-small">
                                    <span class="dashicons dashicons-controls-play"></span>
                                    Test
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #d1d5db;">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 6.5V7.5C15 8.3 14.3 9 13.5 9H10.5C9.7 9 9 8.3 9 7.5V6.5L3 7V9L9 8.5V9.5C9 10.3 9.7 11 10.5 11H13.5C14.3 11 15 10.3 15 9.5V8.5L21 9ZM6.5 12C5.7 12 5 12.7 5 13.5V16.5C5 17.3 5.7 18 6.5 18H7.5V22H9V18H15V22H16.5V18H17.5C18.3 18 19 17.3 19 16.5V13.5C19 12.7 18.3 12 17.5 12H6.5Z" fill="currentColor"/>
                        </svg>
                        <div>
                            <p style="margin: 0; font-size: 1.125rem;">No AI flows generated yet</p>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Click "Generate AI Flows" to create intelligent test flows</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
    background: linear-gradient(135deg, #00265e 0%, #0F9D7A 100%);
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
    border-color: #00265e;
    background: #f0fdfa;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 192, 154, 0.1);
}

.plugin-card input[type="checkbox"]:checked + div {
    color: #00265e;
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
    
    // Initialize plugin selection counter
    console.log('Document ready - initializing plugin selection counter');
    updateSelectedPluginsCount();
    
    // Add click handlers for plugin cards
    $('.plugin-card').on('click', function(e) {
        const pluginSlug = $(this).data('plugin-slug');
        console.log('Card clicked via jQuery handler:', pluginSlug);
        togglePluginSelection(pluginSlug);
    });
    
    // Save AI Configuration
    $('#save-ai-config').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = button.html();
        
        // Add loading animation
        button.addClass('saving');
        button.html(`
            <div class="save-spinner"></div>
            <span>Saving Configuration...</span>
        `);
        button.prop('disabled', true);
        
        const selectedModel = $('#ai-model-select').val();
        const apiKey = $('#ai-api-key').val();
        
        console.log('Save AI Config - Selected Model:', selectedModel);
        console.log('Save AI Config - API Key:', apiKey ? '***' + apiKey.slice(-4) : 'empty');
        
        if (!selectedModel) {
            showErrorModal('Model Selection Required', 'Please select an AI model first.');
            button.html(originalText).prop('disabled', false);
            return;
        }
        
        const selectedOption = $('#ai-model-select option:selected');
        const isFree = selectedOption.attr('data-free') === 'true';
        const provider = selectedOption.attr('data-provider');
        
        // For paid models, check if API key is provided
        if (!isFree && !apiKey.trim()) {
            showErrorModal('API Key Required', 'API key is required for paid models. Please enter your API key.');
            button.html(originalText).prop('disabled', false);
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_set_ai_api_key',
                api_key: apiKey,
                api_provider: provider,
                model: selectedModel,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                console.log('Save AI Config - AJAX Response:', response);
                if (response.success) {
                    // Show success animation
                    button.removeClass('saving').addClass('success');
                    button.html(`
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span>Configuration Saved!</span>
                    `);
                    
                    // Show success modal
                    showSuccessModal('Configuration Saved', 'AI configuration saved successfully!');
                    
                    // Reload after a short delay to allow modal to show
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error animation
                    button.removeClass('saving').addClass('error');
                    button.html(`
                        <span class="dashicons dashicons-warning"></span>
                        <span>Save Failed</span>
                    `);
                    
                    showErrorModal('Save Failed', 'Failed to save configuration: ' + (response.data.message || 'Unknown error'));
                    
                    // Reset button after delay
                    setTimeout(function() {
                        button.removeClass('error');
                        button.html(originalText).prop('disabled', false);
                    }, 2000);
                }
            },
            error: function() {
                // Show error animation
                button.removeClass('saving').addClass('error');
                button.html(`
                    <span class="dashicons dashicons-warning"></span>
                    <span>Connection Error</span>
                `);
                
                showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
                
                // Reset button after delay
                setTimeout(function() {
                    button.removeClass('error');
                    button.html(originalText).prop('disabled', false);
                }, 2000);
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
        const countElement = $('#selected-plugins-count');
        const newText = selectedCount + ' plugin' + (selectedCount !== 1 ? 's' : '') + ' selected';
        
        console.log('Updating selected count:', selectedCount);
        console.log('Count element found:', countElement.length > 0);
        console.log('New text:', newText);
        
        countElement.text(newText);
    }
    
    // Toggle plugin selection
    function togglePluginSelection(pluginSlug) {
        console.log('=== TOGGLE PLUGIN SELECTION ===');
        console.log('Plugin slug:', pluginSlug);
        
        const card = $(`.plugin-card[data-plugin-slug="${pluginSlug}"]`);
        const checkbox = card.find('.plugin-checkbox');
        const indicator = card.find('.plugin-selection-indicator');
        const checkmark = indicator.find('.checkmark');
        
        console.log('Card found:', card.length > 0);
        console.log('Card element:', card[0]);
        console.log('Checkbox found:', checkbox.length > 0);
        console.log('Checkbox element:', checkbox[0]);
        
        if (card.length === 0) {
            console.error('Card not found for slug:', pluginSlug);
            return;
        }
        
        if (checkbox.length === 0) {
            console.error('Checkbox not found in card');
            return;
        }
        
        // Toggle checkbox
        const isChecked = checkbox.prop('checked');
        console.log('Current checkbox state:', isChecked);
        
        checkbox.prop('checked', !isChecked);
        console.log('New checkbox state:', !isChecked);
        
        // Update visual state using CSS classes
        if (!isChecked) {
            card.addClass('selected');
            console.log('Added selected class to card');
        } else {
            card.removeClass('selected');
            console.log('Removed selected class from card');
        }
        
        // Force update the counter
        setTimeout(function() {
            updateSelectedPluginsCount();
        }, 100);
        
        console.log('=== END TOGGLE ===');
    }
    
    // AI Model Selection
    let availableModels = {
        free_models: {},
        paid_models: {},
        models_by_provider: {}
    };
    
    // Load available models on page load
    function loadAvailableModels() {
        console.log('Loading available AI models...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_get_available_ai_models',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                console.log('AI Models Response:', response);
                if (response.success) {
                    availableModels = response.data;
                    populateModelDropdown();
                } else {
                    console.error('Failed to load AI models:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading AI models:', {xhr, status, error});
            }
        });
    }
    
    function populateModelDropdown() {
        console.log('Populating model dropdown with:', availableModels);
        const modelSelect = $('#ai-model-select');
        const description = $('#ai-model-description');
        
        // Clear existing options except the first one
        modelSelect.find('option:not(:first)').remove();
        
        // Add free models first
        Object.keys(availableModels.free_models).forEach(modelId => {
            const model = availableModels.free_models[modelId];
            const option = $('<option></option>')
                .attr('value', modelId)
                .attr('data-provider', model.provider)
                .attr('data-free', 'true')
                .text(`${model.name} (${model.provider}) - Free`);
            modelSelect.append(option);
        });
        
        // Add paid models
        Object.keys(availableModels.paid_models).forEach(modelId => {
            const model = availableModels.paid_models[modelId];
            const option = $('<option></option>')
                .attr('value', modelId)
                .attr('data-provider', model.provider)
                .attr('data-free', 'false')
                .text(`${model.name} (${model.provider}) - Paid`);
            modelSelect.append(option);
        });
        
        // Set default to first free model
        const firstFreeModel = modelSelect.find('option[data-free="true"]:first');
        if (firstFreeModel.length > 0) {
            firstFreeModel.prop('selected', true);
            updateApiKeySection();
        }
    }
    
    function updateApiKeySection() {
        const selectedOption = $('#ai-model-select option:selected');
        const isFree = selectedOption.attr('data-free') === 'true';
        const provider = selectedOption.attr('data-provider');
        const apiKeySection = $('#api-key-section');
        const apiKeyHelp = $('#api-key-help');
        
        if (isFree) {
            apiKeySection.hide();
            $('#ai-model-description').text('Free models work without API keys and are recommended for testing.');
        } else {
            apiKeySection.show();
            $('#ai-model-description').text('Paid models require API keys and offer enhanced capabilities.');
            
            // Update API key help text based on provider
            let helpText = '';
            let apiUrl = '';
            
            switch(provider) {
                case 'OpenAI':
                    helpText = 'Get your API key from OpenAI Platform';
                    apiUrl = 'https://platform.openai.com/api-keys';
                    break;
                case 'Anthropic':
                    helpText = 'Get your API key from Anthropic Console';
                    apiUrl = 'https://console.anthropic.com/';
                    break;
                case 'Google':
                    helpText = 'Get your API key from Google AI Studio';
                    apiUrl = 'https://aistudio.google.com/';
                    break;
                case 'X.AI':
                    helpText = 'Get your API key from X.AI Console';
                    apiUrl = 'https://console.x.ai/';
                    break;
                case 'DeepSeek':
                    helpText = 'Get your API key from DeepSeek Platform';
                    apiUrl = 'https://platform.deepseek.com/';
                    break;
                case 'Mistral AI':
                    helpText = 'Get your API key from Mistral AI Console';
                    apiUrl = 'https://console.mistral.ai/';
                    break;
                case 'Cohere':
                    helpText = 'Get your API key from Cohere Dashboard';
                    apiUrl = 'https://dashboard.cohere.ai/';
                    break;
                case 'Perplexity':
                    helpText = 'Get your API key from Perplexity API';
                    apiUrl = 'https://www.perplexity.ai/settings/api';
                    break;
                case 'Hugging Face':
                    helpText = 'Get your API key from Hugging Face';
                    apiUrl = 'https://huggingface.co/settings/tokens';
                    break;
                case 'Meta':
                    helpText = 'Get your API key from Hugging Face (for Meta models)';
                    apiUrl = 'https://huggingface.co/settings/tokens';
                    break;
                default:
                    helpText = 'Get your API key from the provider\'s website';
                    apiUrl = '#';
            }
            
            apiKeyHelp.html(`<a href="${apiUrl}" target="_blank" style="color: #00265e; text-decoration: none;">${helpText}</a>`);
        }
    }
    
    // Model selection change handler
    $('#ai-model-select').on('change', function() {
        updateApiKeySection();
    });
    
    // Load models on page load
    loadAvailableModels();
    
    // Set current model after models are loaded
    setTimeout(function() {
        const currentModel = '<?php echo esc_js($ai_model); ?>';
        if (currentModel) {
            $('#ai-model-select').val(currentModel);
            updateApiKeySection();
        }
    }, 1000);
    
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
/* Plugin card styling - responsive design */
.plugin-card {
    transition: all 0.3s ease;
    min-height: 140px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    margin: 0;
    padding: 1rem;
    overflow: hidden;
    word-wrap: break-word;
}

.plugin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(31, 192, 154, 0.15);
    border-color: #1FC09A;
}

.plugin-card:hover .plugin-selection-indicator {
    border-color: #1FC09A;
    transform: scale(1.1);
}

.plugin-card.selected {
    border-color: #1FC09A !important;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 24px rgba(31, 192, 154, 0.2) !important;
}

.plugin-card.selected h3 {
    color: #1FC09A !important;
}

.plugin-card.selected .plugin-selection-indicator {
    border-color: #1FC09A !important;
    background: #1FC09A !important;
    box-shadow: 0 4px 12px rgba(31, 192, 154, 0.3) !important;
}

.plugin-card.selected .checkmark {
    opacity: 1 !important;
    background: white !important;
    width: 8px !important;
    height: 8px !important;
}

/* Fixed grid layout for consistent spacing */
.plugin-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

/* Responsive breakpoints - much more aggressive */
@media (max-width: 1024px) {
    .plugin-cards-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
}

@media (max-width: 768px) {
    .plugin-cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
}

@media (max-width: 480px) {
    .plugin-cards-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
}

@media (max-width: 360px) {
    .plugin-cards-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .plugin-card {
        padding: 0.75rem;
        min-height: 120px;
    }
}

/* Ensure cards are clickable */
.plugin-card {
    pointer-events: auto;
    cursor: pointer;
}

.plugin-card * {
    pointer-events: none;
}

.plugin-selection-indicator {
    pointer-events: auto;
    cursor: pointer;
}

/* Save Configuration Button Animations */
#save-ai-config {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

#save-ai-config.saving {
    background: linear-gradient(135deg, #1FC09A, #0ea5e9) !important;
    transform: scale(0.98);
    box-shadow: 0 4px 20px rgba(31, 192, 154, 0.3);
}

#save-ai-config.success {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    transform: scale(1.02);
    box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
}

#save-ai-config.error {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    transform: scale(1.02);
    box-shadow: 0 6px 25px rgba(239, 68, 68, 0.4);
}

/* Spinner Animation */
.save-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: inline-block;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Button Content Animation */
#save-ai-config span {
    transition: all 0.3s ease;
}

#save-ai-config .dashicons {
    transition: all 0.3s ease;
}

/* Pulse Animation for Success */
#save-ai-config.success {
    animation: pulse-success 0.6s ease-in-out;
}

@keyframes pulse-success {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1.02); }
}

/* Shake Animation for Error */
#save-ai-config.error {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
</style>
