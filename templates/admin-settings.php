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
                            <option value="never" <?php selected(($settings['crawl_frequency'] ?? 'never'), 'never'); ?>>Never (Manual Only)</option>
                            <option value="hourly" <?php selected(($settings['crawl_frequency'] ?? 'never'), 'hourly'); ?>>Hourly</option>
                            <option value="twicedaily" <?php selected(($settings['crawl_frequency'] ?? 'never'), 'twicedaily'); ?>>Twice Daily</option>
                            <option value="daily" <?php selected(($settings['crawl_frequency'] ?? 'never'), 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected(($settings['crawl_frequency'] ?? 'never'), 'weekly'); ?>>Weekly</option>
                        </select>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            How often to automatically crawl your site for new flows
                        </p>
                    </div>


                    <!-- Max Pages per Crawl -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Max Pages per Crawl
                        </label>
                        <input type="number" name="wp_tester_settings[max_pages_per_crawl]" value="<?php echo esc_attr($settings['max_pages_per_crawl'] ?? 100); ?>" min="10" max="1000" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Maximum number of pages to crawl in a single crawl session
                        </p>
                    </div>

                    <!-- Crawl Schedule Time -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Crawl Schedule Time
                        </label>
                        <input type="time" name="wp_tester_settings[crawl_schedule_time]" value="<?php echo esc_attr($settings['crawl_schedule_time'] ?? '02:00'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Time of day to run scheduled crawls (24-hour format)
                        </p>
                    </div>

                    <!-- Crawl Schedule Days -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Crawl Schedule Days
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; margin-bottom: 0.5rem;">
                            <?php 
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            $selected_days = $settings['crawl_schedule_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                            foreach ($days as $day) : 
                            ?>
                                <label style="display: flex; flex-direction: column; align-items: center; cursor: pointer; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: <?php echo in_array($day, $selected_days) ? '#f0fdf4' : 'white'; ?>;">
                                    <input type="checkbox" name="wp_tester_settings[crawl_schedule_days][]" value="<?php echo $day; ?>" <?php checked(in_array($day, $selected_days)); ?> style="margin-bottom: 0.25rem; accent-color: #00265e;">
                                    <span style="font-size: 0.75rem; font-weight: 500; color: #374151; text-transform: capitalize;"><?php echo substr($day, 0, 3); ?></span>
                                </label>
            <?php endforeach; ?>
        </div>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Select which days of the week to run scheduled crawls
                        </p>
    </div>
    
                    <!-- Include Admin Panel in Crawl -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="wp_tester_settings[include_admin_in_crawl]" value="1" <?php checked(($settings['include_admin_in_crawl'] ?? true), true); ?> style="accent-color: #00265e;">
                            <span style="font-weight: 600; color: #374151; font-size: 0.875rem;">Include Admin Panel in Crawl</span>
                        </label>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Automatically discover and create flows for WordPress admin pages
                        </p>
                    </div>

                    <!-- Prevent Duplicate Flows -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="wp_tester_settings[prevent_duplicate_flows]" value="1" <?php checked(($settings['prevent_duplicate_flows'] ?? true), true); ?> style="accent-color: #00265e;">
                            <span style="font-weight: 600; color: #374151; font-size: 0.875rem;">Prevent Duplicate Flows</span>
                        </label>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Automatically prevent creation of duplicate flows during crawling
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

                    <!-- Screenshot on Success (Debug) -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.75rem; font-weight: 600; color: #374151; font-size: 0.875rem;">
                            <input type="checkbox" name="wp_tester_settings[screenshot_on_success]" 
                                   value="1" <?php checked(($settings['screenshot_on_success'] ?? false), true); ?>
                                   style="width: 18px; height: 18px; border: 2px solid #e2e8f0; border-radius: 4px;">
                            Take Screenshots on Success (Debug)
                        </label>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            Also capture screenshots for successful steps (useful for debugging)
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
                    'style' => 'padding: 12px 24px; font-size: 0.875rem; font-weight: 600; background: #00265e !important; border: 2px solid #00265e !important; border-radius: 50px !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; box-shadow: 0 4px 15px rgba(0, 38, 94, 0.2) !important;'
                )); ?>
            </div>

    </form>
    
    <style>
    /* Save Settings Button Hover Effects */
    .modern-btn.modern-btn-primary:hover {
        background: #001a3d !important;
        border-color: #001a3d !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(0, 38, 94, 0.3) !important;
        transition: all 0.3s ease !important;
    }
    </style>
    
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
    // Ensure ajaxurl is available
    if (typeof ajaxurl === 'undefined') {
        ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    
    // Maintenance actions
    $('#clear-cache').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to clear the cache?')) {
            const button = $(this);
            const originalText = button.html();
            button.html('<span class="dashicons dashicons-update-alt"></span> Clearing...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_tester_clear_cache',
                    nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessModal('Cache Cleared', 'Cache cleared successfully!');
                    } else {
                        showErrorModal('Cache Clear Failed', response.data.message || 'Unknown error occurred');
                    }
                },
                error: function() {
                    showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
                },
                complete: function() {
                    button.html(originalText).prop('disabled', false);
                }
            });
        }
    });

    $('#reset-flows').on('click', function(e) {
        e.preventDefault();
        if (confirm('This will reset all flows. Are you sure?')) {
            const button = $(this);
            const originalText = button.html();
            button.html('<span class="dashicons dashicons-update-alt"></span> Resetting...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_tester_reset_flows',
                    nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessModal('Flows Reset', 'All flows have been reset successfully!');
                    } else {
                        showErrorModal('Reset Failed', response.data.message || 'Unknown error occurred');
                    }
                },
                error: function() {
                    showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
                },
                complete: function() {
                    button.html(originalText).prop('disabled', false);
                }
            });
        }
    });

    $('#export-data').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-update-alt"></span> Exporting...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_export_data',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
            if (response.success) {
                    // Create download link
                    const a = document.createElement('a');
                    a.href = response.data.download_url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    
                    showSuccessModal('Data exported successfully!', 
                        'Export file: ' + response.data.filename + '<br>File size: ' + response.data.file_size);
            } else {
                    showErrorModal('Export Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function() {
                showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    $('#system-check').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-update-alt"></span> Checking...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_system_check',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSystemCheckModal(response.data.checks);
                } else {
                    showErrorModal('System Check Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function() {
                showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Modal functions
    function showSuccessModal(title, message) {
        const modalId = 'success-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="modern-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span class="dashicons dashicons-yes" style="color: white; font-size: 30px;"></span>
                        </div>
                        <h3 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">${title}</h3>
                    </div>
                    <div style="color: #64748b; line-height: 1.6; margin-bottom: 2rem; text-align: center;">
                        ${message}
                    </div>
                    <div style="text-align: center;">
                        <button class="modal-close-btn" style="background: #00265e; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.find('.modal-close-btn, .modern-modal').on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
    }
    
    function showErrorModal(title, message) {
        const modalId = 'error-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="modern-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span class="dashicons dashicons-warning" style="color: white; font-size: 30px;"></span>
                        </div>
                        <h3 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">${title}</h3>
                    </div>
                    <div style="color: #64748b; line-height: 1.6; margin-bottom: 2rem; text-align: center;">
                        ${message}
                    </div>
                    <div style="text-align: center;">
                        <button class="modal-close-btn" style="background: #ef4444; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.find('.modal-close-btn, .modern-modal').on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
    }
    
    function showSystemCheckModal(checks) {
        const modalId = 'system-check-modal-' + Date.now();
        let content = '<div style="max-height: 400px; overflow-y: auto;">';
        
        Object.keys(checks).forEach(category => {
            content += `<div style="margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.75rem 0; color: #374151; font-size: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">${category}</h4>`;
            
            Object.keys(checks[category]).forEach(check => {
                const status = checks[category][check].status;
                const statusIcon = status === 'ok' ? '✓' : (status === 'warning' ? '⚠' : '✗');
                const statusColor = status === 'ok' ? '#10b981' : (status === 'warning' ? '#f59e0b' : '#ef4444');
                
                content += `<div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6;">
                    <span style="color: ${statusColor}; font-weight: bold; font-size: 1.125rem;">${statusIcon}</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #374151; font-size: 0.875rem;">${check.replace(/_/g, ' ')}</div>
                        <div style="color: #64748b; font-size: 0.8125rem;">${checks[category][check].message}</div>
                    </div>
                </div>`;
            });
            
            content += '</div>';
        });
        
        content += '</div>';
        
        const modal = $(`
            <div id="${modalId}" class="modern-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: #00265e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span class="dashicons dashicons-admin-tools" style="color: white; font-size: 30px;"></span>
                        </div>
                        <h3 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">System Check Results</h3>
                    </div>
                    ${content}
                    <div style="text-align: center; margin-top: 2rem;">
                        <button class="modal-close-btn" style="background: #00265e; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.find('.modal-close-btn, .modern-modal').on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
    }
});
</script>