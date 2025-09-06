<?php
/**
 * Admin Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$scheduler = new WP_Tester_Scheduler();
$scheduling_status = $scheduler->get_scheduling_status();
$system_requirements = wp_tester_check_system_requirements();
?>

<div class="wrap wp-tester-modern">
    <!-- Modern Header with Logo -->
    <div class="wp-tester-modern-header glass-nav">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" alt="WP Tester Logo" class="logo" />
                <div class="title-section">
                    <h1><?php _e('WP Tester Settings', 'wp-tester'); ?></h1>
                    <div class="subtitle"><?php _e('Configure general settings for WP Tester', 'wp-tester'); ?></div>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="btn btn-outline btn-sm">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Dashboard', 'wp-tester'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="wp-tester-content">
    
    <?php settings_errors(); ?>
    
    <!-- System Status -->
    <div class="wp-tester-system-status">
        <h2><?php _e('System Status', 'wp-tester'); ?></h2>
        
        <div class="wp-tester-status-grid">
            <?php foreach ($system_requirements as $requirement => $data): ?>
            <div class="wp-tester-status-item">
                <div class="wp-tester-status-icon">
                    <?php if ($data['met']): ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #28a745;"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-dismiss" style="color: #dc3545;"></span>
                    <?php endif; ?>
                </div>
                <div class="wp-tester-status-content">
                    <h4><?php echo esc_html(ucwords(str_replace('_', ' ', $requirement))); ?></h4>
                    <p>
                        <?php _e('Current:', 'wp-tester'); ?> 
                        <strong>
                            <?php 
                            if (is_bool($data['current'])) {
                                echo $data['current'] ? __('Yes', 'wp-tester') : __('No', 'wp-tester');
                            } elseif ($requirement === 'memory_limit' && is_numeric($data['current'])) {
                                echo wp_tester_format_file_size($data['current']);
                            } else {
                                echo esc_html($data['current']);
                            }
                            ?>
                        </strong>
                    </p>
                    <p>
                        <?php _e('Required:', 'wp-tester'); ?> 
                        <strong>
                            <?php 
                            if (is_bool($data['required'])) {
                                echo $data['required'] ? __('Yes', 'wp-tester') : __('No', 'wp-tester');
                            } elseif ($requirement === 'memory_limit' && is_numeric($data['required'])) {
                                echo wp_tester_format_file_size($data['required']);
                            } else {
                                echo esc_html($data['required']);
                            }
                            ?>
                        </strong>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Scheduling Status -->
    <div class="wp-tester-scheduling-status">
        <h2><?php _e('Scheduled Tasks', 'wp-tester'); ?></h2>
        
        <div class="wp-tester-schedule-grid">
            <div class="wp-tester-schedule-item">
                <h4><?php _e('Site Crawling', 'wp-tester'); ?></h4>
                <?php if ($scheduling_status['crawl']['scheduled']): ?>
                    <p class="wp-tester-status-good">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php printf(__('Next run: %s', 'wp-tester'), $scheduling_status['crawl']['human_time']); ?>
                    </p>
                <?php else: ?>
                    <p class="wp-tester-status-error">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Not scheduled', 'wp-tester'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="wp-tester-schedule-item">
                <h4><?php _e('Flow Testing', 'wp-tester'); ?></h4>
                <?php if ($scheduling_status['tests']['scheduled']): ?>
                    <p class="wp-tester-status-good">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php printf(__('Next run: %s', 'wp-tester'), $scheduling_status['tests']['human_time']); ?>
                    </p>
                <?php else: ?>
                    <p class="wp-tester-status-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('No tests scheduled', 'wp-tester'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="wp-tester-schedule-item">
                <h4><?php _e('Data Cleanup', 'wp-tester'); ?></h4>
                <?php if ($scheduling_status['cleanup']['scheduled']): ?>
                    <p class="wp-tester-status-good">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php printf(__('Next run: %s', 'wp-tester'), $scheduling_status['cleanup']['human_time']); ?>
                    </p>
                <?php else: ?>
                    <p class="wp-tester-status-error">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Not scheduled', 'wp-tester'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Settings Form -->
    <form method="post" action="options.php">
        <?php
        settings_fields('wp_tester_settings');
        do_settings_sections('wp_tester_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Crawl Frequency', 'wp-tester'); ?></th>
                <td>
                    <?php wp_tester()->admin->crawl_frequency_callback(); ?>
                    <p class="description"><?php _e('How often should WP Tester crawl your site for new content and flows?', 'wp-tester'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Test Timeout', 'wp-tester'); ?></th>
                <td>
                    <?php wp_tester()->admin->test_timeout_callback(); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Retry Attempts', 'wp-tester'); ?></th>
                <td>
                    <?php wp_tester()->admin->retry_attempts_callback(); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Screenshots on Failure', 'wp-tester'); ?></th>
                <td>
                    <?php wp_tester()->admin->screenshot_on_failure_callback(); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Max Pages per Crawl', 'wp-tester'); ?></th>
                <td>
                    <?php wp_tester()->admin->max_pages_per_crawl_callback(); ?>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <!-- Advanced Settings -->
    <div class="wp-tester-advanced-settings">
        <h2><?php _e('Advanced Settings', 'wp-tester'); ?></h2>
        
        <div class="wp-tester-setting-group">
            <h3><?php _e('Data Management', 'wp-tester'); ?></h3>
            
            <p>
                <button type="button" class="button" id="wp-tester-clean-test-data">
                    <?php _e('Clean Test Data', 'wp-tester'); ?>
                </button>
                <span class="description"><?php _e('Remove test users, comments, and other test data created during testing.', 'wp-tester'); ?></span>
            </p>
            
            <p>
                <button type="button" class="button" id="wp-tester-export-system-info">
                    <?php _e('Export System Info', 'wp-tester'); ?>
                </button>
                <span class="description"><?php _e('Export system information for debugging purposes.', 'wp-tester'); ?></span>
            </p>
        </div>
        
        <div class="wp-tester-setting-group">
            <h3><?php _e('Reset & Maintenance', 'wp-tester'); ?></h3>
            
            <p>
                <button type="button" class="button" id="wp-tester-reset-flows">
                    <?php _e('Reset All Flows', 'wp-tester'); ?>
                </button>
                <span class="description"><?php _e('Delete all flows and re-discover them from scratch.', 'wp-tester'); ?></span>
            </p>
            
            <p>
                <button type="button" class="button button-secondary" id="wp-tester-clear-cache">
                    <?php _e('Clear Cache', 'wp-tester'); ?>
                </button>
                <span class="description"><?php _e('Clear all cached data and force fresh crawl.', 'wp-tester'); ?></span>
            </p>
        </div>
    </div>
    
    <!-- Plugin Information -->
    <div class="wp-tester-plugin-info">
        <h2><?php _e('Plugin Information', 'wp-tester'); ?></h2>
        
        <?php $plugin_info = wp_tester_get_plugin_info(); ?>
        
        <table class="wp-list-table widefat">
            <tbody>
                <tr>
                    <th><?php _e('Plugin Name', 'wp-tester'); ?></th>
                    <td><?php echo esc_html($plugin_info['name']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Version', 'wp-tester'); ?></th>
                    <td><?php echo esc_html($plugin_info['version']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Author', 'wp-tester'); ?></th>
                    <td><?php echo esc_html($plugin_info['author']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Plugin URI', 'wp-tester'); ?></th>
                    <td><a href="<?php echo esc_url($plugin_info['plugin_uri']); ?>" target="_blank"><?php echo esc_html($plugin_info['plugin_uri']); ?></a></td>
                </tr>
                <tr>
                    <th><?php _e('Text Domain', 'wp-tester'); ?></th>
                    <td><?php echo esc_html($plugin_info['text_domain']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.wp-tester-system-status,
.wp-tester-scheduling-status,
.wp-tester-advanced-settings,
.wp-tester-plugin-info {
    margin: 30px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wp-tester-status-grid,
.wp-tester-schedule-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.wp-tester-status-item,
.wp-tester-schedule-item {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background: #f9f9f9;
}

.wp-tester-status-icon {
    margin-right: 15px;
    font-size: 20px;
}

.wp-tester-status-content h4,
.wp-tester-schedule-item h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #23282d;
}

.wp-tester-status-content p,
.wp-tester-schedule-item p {
    margin: 5px 0;
    font-size: 13px;
    color: #666;
}

.wp-tester-status-good {
    color: #28a745;
}

.wp-tester-status-warning {
    color: #ffc107;
}

.wp-tester-status-error {
    color: #dc3545;
}

.wp-tester-setting-group {
    margin: 20px 0;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.wp-tester-setting-group:last-child {
    border-bottom: none;
}

.wp-tester-setting-group h3 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.wp-tester-setting-group p {
    margin: 15px 0;
}

.wp-tester-setting-group .description {
    margin-left: 10px;
    color: #666;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Clean test data
    $('#wp-tester-clean-test-data').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to clean all test data? This action cannot be undone.', 'wp-tester'); ?>')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Cleaning...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_clean_test_data',
            nonce: '<?php echo wp_create_nonce('wp_tester_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Test data cleaned successfully.', 'wp-tester'); ?>');
            } else {
                alert('<?php _e('Failed to clean test data.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Clean Test Data', 'wp-tester'); ?>');
        });
    });
    
    // Export system info
    $('#wp-tester-export-system-info').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=wp-tester-settings&action=export_system_info&nonce=' . wp_create_nonce('wp_tester_admin_nonce')); ?>';
    });
    
    // Reset flows
    $('#wp-tester-reset-flows').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to reset all flows? This will delete all existing flows and test results.', 'wp-tester'); ?>')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Resetting...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_reset_flows',
            nonce: '<?php echo wp_create_nonce('wp_tester_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Flows reset successfully.', 'wp-tester'); ?>');
                location.reload();
            } else {
                alert('<?php _e('Failed to reset flows.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Reset All Flows', 'wp-tester'); ?>');
        });
    });
    
    // Clear cache
    $('#wp-tester-clear-cache').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Clearing...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_clear_cache',
            nonce: '<?php echo wp_create_nonce('wp_tester_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Cache cleared successfully.', 'wp-tester'); ?>');
            } else {
                alert('<?php _e('Failed to clear cache.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Clear Cache', 'wp-tester'); ?>');
        });
    });
});
</script>
    </div> <!-- wp-tester-content -->
</div> <!-- wp-tester-modern -->