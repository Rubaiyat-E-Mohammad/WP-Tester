<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = $dashboard_data['statistics'];
$recent_results = $dashboard_data['recent_results'];
$critical_issues = $dashboard_data['critical_issues'];
$flow_health = $dashboard_data['flow_health'];
$recommendations = $dashboard_data['recommendations'];
?>

<div class="wrap">
    <h1><?php _e('WP Tester Dashboard', 'wp-tester'); ?></h1>
    
    <!-- Statistics Cards -->
    <div class="wp-tester-stats-grid">
        <div class="wp-tester-stat-card">
            <div class="wp-tester-stat-icon">
                <span class="dashicons dashicons-admin-page"></span>
            </div>
            <div class="wp-tester-stat-content">
                <h3><?php echo number_format($stats['total_pages']); ?></h3>
                <p><?php _e('Pages Crawled', 'wp-tester'); ?></p>
            </div>
        </div>
        
        <div class="wp-tester-stat-card">
            <div class="wp-tester-stat-icon">
                <span class="dashicons dashicons-analytics"></span>
            </div>
            <div class="wp-tester-stat-content">
                <h3><?php echo number_format($stats['total_flows']); ?></h3>
                <p><?php _e('Active Flows', 'wp-tester'); ?></p>
            </div>
        </div>
        
        <div class="wp-tester-stat-card">
            <div class="wp-tester-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="wp-tester-stat-content">
                <h3><?php echo number_format($stats['recent_tests']); ?></h3>
                <p><?php _e('Tests (24h)', 'wp-tester'); ?></p>
            </div>
        </div>
        
        <div class="wp-tester-stat-card">
            <div class="wp-tester-stat-icon">
                <span class="dashicons dashicons-yes-alt" style="color: <?php echo $stats['success_rate'] > 80 ? '#28a745' : ($stats['success_rate'] > 60 ? '#ffc107' : '#dc3545'); ?>"></span>
            </div>
            <div class="wp-tester-stat-content">
                <h3><?php echo $stats['success_rate']; ?>%</h3>
                <p><?php _e('Success Rate (7d)', 'wp-tester'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Critical Issues Alert -->
    <?php if (!empty($critical_issues)): ?>
    <div class="wp-tester-alert wp-tester-alert-danger">
        <h3><?php _e('Critical Issues Detected', 'wp-tester'); ?></h3>
        <p><?php printf(__('You have %d critical issues that require immediate attention.', 'wp-tester'), count($critical_issues)); ?></p>
        <a href="<?php echo admin_url('admin.php?page=wp-tester-results&status=failed'); ?>" class="button button-primary">
            <?php _e('View Critical Issues', 'wp-tester'); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Recommendations -->
    <?php if (!empty($recommendations)): ?>
    <div class="wp-tester-recommendations">
        <h2><?php _e('Recommendations', 'wp-tester'); ?></h2>
        <?php foreach ($recommendations as $recommendation): ?>
        <div class="wp-tester-recommendation wp-tester-priority-<?php echo esc_attr($recommendation['priority']); ?>">
            <div class="wp-tester-recommendation-icon">
                <span class="dashicons dashicons-lightbulb"></span>
            </div>
            <div class="wp-tester-recommendation-content">
                <h4><?php echo esc_html($recommendation['title']); ?></h4>
                <p><?php echo esc_html($recommendation['description']); ?></p>
                <strong><?php _e('Recommended Action:', 'wp-tester'); ?></strong> <?php echo esc_html($recommendation['action']); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="wp-tester-dashboard-grid">
        <!-- Recent Test Results -->
        <div class="wp-tester-dashboard-section">
            <h2><?php _e('Recent Test Results', 'wp-tester'); ?></h2>
            
            <?php if (!empty($recent_results)): ?>
            <div class="wp-tester-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Flow', 'wp-tester'); ?></th>
                            <th><?php _e('Status', 'wp-tester'); ?></th>
                            <th><?php _e('Success Rate', 'wp-tester'); ?></th>
                            <th><?php _e('Time', 'wp-tester'); ?></th>
                            <th><?php _e('Date', 'wp-tester'); ?></th>
                            <th><?php _e('Actions', 'wp-tester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_results as $result): ?>
                        <tr>
                            <td>
                                <?php echo wp_tester()->admin->get_flow_type_icon($result['flow_type']); ?>
                                <strong><?php echo esc_html($result['flow_name']); ?></strong>
                                <br><small><?php echo esc_html($result['flow_type']); ?></small>
                            </td>
                            <td><?php echo wp_tester()->admin->get_status_badge($result['status']); ?></td>
                            <td>
                                <div class="wp-tester-progress-bar">
                                    <div class="wp-tester-progress-fill" style="width: <?php echo $result['success_rate']; ?>%"></div>
                                </div>
                                <?php echo $result['success_rate']; ?>%
                            </td>
                            <td><?php echo wp_tester()->admin->format_execution_time($result['execution_time']); ?></td>
                            <td><?php echo human_time_diff(strtotime($result['started_at']), current_time('timestamp')); ?> <?php _e('ago', 'wp-tester'); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . $result['id']); ?>" class="button button-small">
                                    <?php _e('View Report', 'wp-tester'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <p class="wp-tester-section-footer">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="button">
                    <?php _e('View All Results', 'wp-tester'); ?>
                </a>
            </p>
            <?php else: ?>
            <div class="wp-tester-empty-state">
                <span class="dashicons dashicons-analytics"></span>
                <h3><?php _e('No Test Results Yet', 'wp-tester'); ?></h3>
                <p><?php _e('Run your first test to see results here.', 'wp-tester'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="button button-primary">
                    <?php _e('View Flows', 'wp-tester'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Flow Health -->
        <div class="wp-tester-dashboard-section">
            <h2><?php _e('Flow Health Overview', 'wp-tester'); ?></h2>
            
            <?php if (!empty($flow_health)): ?>
            <div class="wp-tester-flow-health-list">
                <?php foreach (array_slice($flow_health, 0, 10) as $flow): ?>
                <div class="wp-tester-flow-health-item">
                    <div class="wp-tester-flow-info">
                        <?php echo wp_tester()->admin->get_flow_type_icon($flow['flow_type']); ?>
                        <div class="wp-tester-flow-details">
                            <strong><?php echo esc_html($flow['flow_name']); ?></strong>
                            <small><?php echo esc_html($flow['flow_type']); ?></small>
                        </div>
                    </div>
                    <div class="wp-tester-flow-health-status">
                        <span class="wp-tester-health-badge" style="background-color: <?php echo $flow['health_color']; ?>">
                            <?php echo $flow['health_status']; ?>
                        </span>
                        <div class="wp-tester-flow-metrics">
                            <small><?php echo $flow['success_rate']; ?>% success</small>
                            <small><?php echo $flow['total_tests']; ?> tests</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <p class="wp-tester-section-footer">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="button">
                    <?php _e('Manage Flows', 'wp-tester'); ?>
                </a>
            </p>
            <?php else: ?>
            <div class="wp-tester-empty-state">
                <span class="dashicons dashicons-admin-generic"></span>
                <h3><?php _e('No Flows Detected', 'wp-tester'); ?></h3>
                <p><?php _e('Run a crawl to discover flows on your site.', 'wp-tester'); ?></p>
                <button id="wp-tester-run-crawl" class="button button-primary">
                    <?php _e('Run Crawl Now', 'wp-tester'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="wp-tester-quick-actions">
        <h2><?php _e('Quick Actions', 'wp-tester'); ?></h2>
        <div class="wp-tester-actions-grid">
            <button id="wp-tester-run-all-tests" class="wp-tester-action-button">
                <span class="dashicons dashicons-controls-play"></span>
                <span><?php _e('Run All Tests', 'wp-tester'); ?></span>
            </button>
            <button id="wp-tester-run-crawl" class="wp-tester-action-button">
                <span class="dashicons dashicons-search"></span>
                <span><?php _e('Run Site Crawl', 'wp-tester'); ?></span>
            </button>
            <a href="<?php echo admin_url('admin.php?page=wp-tester-settings'); ?>" class="wp-tester-action-button">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php _e('Settings', 'wp-tester'); ?></span>
            </a>
            <button id="wp-tester-export-report" class="wp-tester-action-button">
                <span class="dashicons dashicons-download"></span>
                <span><?php _e('Export Report', 'wp-tester'); ?></span>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Run all tests
    $('#wp-tester-run-all-tests').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).find('span:last').text('<?php _e('Running...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_run_all_tests',
            nonce: wpTesterAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Failed to run tests. Please try again.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).find('span:last').text('<?php _e('Run All Tests', 'wp-tester'); ?>');
        });
    });
    
    // Run crawl
    $('#wp-tester-run-crawl').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).find('span:last').text('<?php _e('Crawling...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_run_crawl',
            nonce: wpTesterAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Failed to run crawl. Please try again.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).find('span:last').text('<?php _e('Run Site Crawl', 'wp-tester'); ?>');
        });
    });
    
    // Export report
    $('#wp-tester-export-report').on('click', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=wp-tester&action=export'); ?>';
    });
});
</script>