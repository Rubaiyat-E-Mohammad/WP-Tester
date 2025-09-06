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

<div class="wrap wp-tester-modern">
    <!-- Modern Header with Logo -->
    <div class="wp-tester-modern-header glass-nav">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" alt="WP Tester Logo" class="logo" />
                <div class="title-section">
                    <h1><?php _e('WP Tester Dashboard', 'wp-tester'); ?></h1>
                    <div class="subtitle"><?php _e('Ultra-Modern Testing Dashboard', 'wp-tester'); ?></div>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline btn-sm" onclick="location.reload();">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh', 'wp-tester'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="btn btn-primary btn-sm">
                    <span class="dashicons dashicons-analytics"></span>
                    <?php _e('Run Test', 'wp-tester'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="wp-tester-content">
        
        <!-- Statistics Cards -->
        <div class="wp-tester-stats-grid">
            <div class="wp-tester-stat-card">
                <div class="wp-tester-stat-icon">
                    <span class="dashicons dashicons-admin-page"></span>
                </div>
                <div class="wp-tester-stat-content">
                    <h3><?php echo number_format($stats['total_pages'] ?: 0); ?></h3>
                    <p><?php _e('Pages Crawled', 'wp-tester'); ?></p>
                </div>
            </div>
            
            <div class="wp-tester-stat-card">
                <div class="wp-tester-stat-icon">
                    <span class="dashicons dashicons-analytics"></span>
                </div>
                <div class="wp-tester-stat-content">
                    <h3><?php echo number_format($stats['total_flows'] ?: 0); ?></h3>
                    <p><?php _e('Active Flows', 'wp-tester'); ?></p>
                </div>
            </div>
            
            <div class="wp-tester-stat-card">
                <div class="wp-tester-stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="wp-tester-stat-content">
                    <h3><?php echo number_format($stats['recent_tests'] ?: 0); ?></h3>
                    <p><?php _e('Tests (24h)', 'wp-tester'); ?></p>
                </div>
            </div>
            
            <div class="wp-tester-stat-card">
                <div class="wp-tester-stat-icon">
                    <span class="dashicons dashicons-yes-alt" style="color: <?php echo ($stats['success_rate'] ?: 0) > 80 ? '#28a745' : (($stats['success_rate'] ?: 0) > 60 ? '#ffc107' : '#dc3545'); ?>"></span>
                </div>
                <div class="wp-tester-stat-content">
                    <h3><?php echo ($stats['success_rate'] ?: 0); ?>%</h3>
                    <p><?php _e('Success Rate (7d)', 'wp-tester'); ?></p>
                </div>
            </div>
        </div>
        
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
                            <th><?php _e('Time', 'wp-tester'); ?></th>
                            <th><?php _e('Actions', 'wp-tester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_results as $result): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($result['flow_name'] ?: 'Unknown Flow'); ?></strong>
                                <br><small><?php echo esc_html($result['flow_type'] ?: 'unknown'); ?></small>
                            </td>
                            <td>
                                <span class="wp-tester-badge wp-tester-badge-<?php echo ($result['status'] === 'passed') ? 'success' : (($result['status'] === 'failed') ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($result['status'] ?: 'unknown'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($result['execution_time'] ?: '0'); ?>s</td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . ($result['id'] ?: 0)); ?>" class="button button-small">
                                    <?php _e('View Report', 'wp-tester'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
        
        <!-- Quick Actions -->
        <div class="wp-tester-quick-actions">
            <h2><?php _e('Quick Actions', 'wp-tester'); ?></h2>
            <div class="wp-tester-actions-grid">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="wp-tester-action-button">
                    <span class="dashicons dashicons-analytics"></span>
                    <span><?php _e('Manage Flows', 'wp-tester'); ?></span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="wp-tester-action-button">
                    <span class="dashicons dashicons-chart-line"></span>
                    <span><?php _e('View Reports', 'wp-tester'); ?></span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-crawl'); ?>" class="wp-tester-action-button">
                    <span class="dashicons dashicons-search"></span>
                    <span><?php _e('Crawl Results', 'wp-tester'); ?></span>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-settings'); ?>" class="wp-tester-action-button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span><?php _e('Settings', 'wp-tester'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
