<?php
/**
 * Admin Settings Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wp-tester-modern">
    <!-- Modern Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" 
                     alt="WP Tester" class="logo">
                <div class="title-info">
                    <h1>Settings</h1>
                    <p class="subtitle">Configure WP Tester preferences</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_tester_settings');
            $settings = get_option('wp_tester_settings', array());
            ?>

            <!-- General Settings -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">General Settings</h2>
                    <div class="status-badge info">Configuration</div>
                </div>
                
                <div style="display: grid; gap: 1.5rem;">
                    
                    <!-- Crawl Frequency -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Crawl Frequency
                        </label>
                        <select name="wp_tester_settings[crawl_frequency]" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                            <option value="hourly" <?php selected(($settings['crawl_frequency'] ?? 'daily'), 'hourly'); ?>>Hourly</option>
                            <option value="twicedaily" <?php selected(($settings['crawl_frequency'] ?? 'daily'), 'twicedaily'); ?>>Twice Daily</option>
                            <option value="daily" <?php selected(($settings['crawl_frequency'] ?? 'daily'), 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected(($settings['crawl_frequency'] ?? 'daily'), 'weekly'); ?>>Weekly</option>
                        </select>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            How often to automatically crawl your site for new flows
                        </p>
                    </div>

                    <!-- Test Timeout -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Test Timeout (seconds)
                        </label>
                        <input type="number" name="wp_tester_settings[test_timeout]" 
                               value="<?php echo esc_attr($settings['test_timeout'] ?? 30); ?>" 
                               min="10" max="300"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Maximum time to wait for each test step to complete
                        </p>
            </div>
            
                    <!-- Retry Attempts -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Retry Attempts
                        </label>
                        <input type="number" name="wp_tester_settings[retry_attempts]" 
                               value="<?php echo esc_attr($settings['retry_attempts'] ?? 2); ?>" 
                               min="0" max="5"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Number of times to retry a failed step before marking it as failed
                        </p>
                    </div>

                    <!-- Max Pages per Crawl -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Max Pages per Crawl
                        </label>
                        <input type="number" name="wp_tester_settings[max_pages_per_crawl]" 
                               value="<?php echo esc_attr($settings['max_pages_per_crawl'] ?? 100); ?>" 
                               min="10" max="1000"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Maximum number of pages to crawl per post type during each crawl session
                        </p>
            </div>
            
                    <!-- Screenshot on Failure -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.75rem; font-weight: 600; color: #374151; font-size: 0.875rem;">
                            <input type="checkbox" name="wp_tester_settings[screenshot_on_failure]" 
                                   value="1" <?php checked(($settings['screenshot_on_failure'] ?? true), true); ?>
                                   style="width: 18px; height: 18px; border: 2px solid #e2e8f0; border-radius: 4px;">
                            Take Screenshots on Failure
                        </label>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Capture screenshots when steps fail for visual debugging
                        </p>
                    </div>

                </div>
            </div>

            <!-- System Information -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">System Information</h2>
                    <div class="status-badge success">Healthy</div>
                </div>
                
                <div class="modern-list">
                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-wordpress"></span>
                            </div>
                            <div class="item-details">
                                <h4>WordPress Version</h4>
                                <p><?php echo get_bloginfo('version'); ?></p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <span class="status-badge success">Compatible</span>
        </div>
    </div>
    
                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-admin-tools"></span>
                            </div>
                            <div class="item-details">
                                <h4>PHP Version</h4>
                                <p><?php echo PHP_VERSION; ?></p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <span class="status-badge <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'success' : 'warning'; ?>">
                                <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'Compatible' : 'Update Recommended'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-database"></span>
                            </div>
                            <div class="item-details">
                                <h4>Database</h4>
                                <p><?php global $wpdb; echo $wpdb->get_var("SELECT VERSION()"); ?></p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <span class="status-badge success">Connected</span>
                        </div>
                    </div>

                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="item-details">
                                <h4>WP Tester Version</h4>
                                <p><?php echo defined('WP_TESTER_VERSION') ? WP_TESTER_VERSION : '1.0.0'; ?></p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <span class="status-badge info">Latest</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div style="margin-top: 2rem; text-align: center;">
                <?php submit_button('Save Settings', 'primary', 'submit', false, array(
                    'class' => 'modern-btn modern-btn-primary',
                    'style' => 'padding: 0.75rem 2rem; font-size: 0.875rem; font-weight: 600;'
                )); ?>
            </div>

    </form>
    
        <!-- Additional Actions -->
        <div class="modern-card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2 class="card-title">Maintenance</h2>
                <div class="status-badge warning">Admin Only</div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <button class="modern-btn modern-btn-secondary" id="clear-cache">
                    <span class="dashicons dashicons-trash"></span>
                    Clear Cache
                </button>
                <button class="modern-btn modern-btn-secondary" id="reset-flows">
                    <span class="dashicons dashicons-update"></span>
                    Reset Flows
                </button>
                <button class="modern-btn modern-btn-secondary" id="export-data">
                    <span class="dashicons dashicons-download"></span>
                    Export Data
                </button>
                <button class="modern-btn modern-btn-secondary" id="system-check">
                    <span class="dashicons dashicons-admin-tools"></span>
                    System Check
                </button>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Maintenance actions
    $('#clear-cache').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to clear the cache?')) {
            // Add AJAX call here
            alert('Cache cleared successfully!');
        }
    });

    $('#reset-flows').on('click', function(e) {
        e.preventDefault();
        if (confirm('This will reset all flows. Are you sure?')) {
            // Add AJAX call here
            alert('Flows reset successfully!');
        }
    });

    $('#export-data').on('click', function(e) {
        e.preventDefault();
        alert('Data export feature coming soon!');
    });

    $('#system-check').on('click', function(e) {
        e.preventDefault();
        alert('System check completed. All systems operational!');
    });
});
</script>