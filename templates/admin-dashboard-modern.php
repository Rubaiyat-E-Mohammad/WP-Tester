<?php
/**
 * Modern Admin Dashboard Template
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
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" alt="WP Tester Logo" class="logo animate-float" />
                <div class="title-section">
                    <h1 class="animate-fade-in"><?php _e('WP Tester', 'wp-tester'); ?></h1>
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
        <!-- Modern Statistics Grid -->
        <div class="stats-grid stagger-children">
            <div class="stat-card glass-card hover-lift">
                <div class="stat-header">
                    <div class="stat-icon stat-primary">
                        <span class="dashicons dashicons-admin-page"></span>
                    </div>
                    <div class="stat-trend trend-up">
                        <span>↗</span>12%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_pages']); ?></div>
                <div class="stat-label"><?php _e('Pages Crawled', 'wp-tester'); ?></div>
                <div class="stat-progress">
                    <div class="progress-bar progress-shimmer" style="width: 75%;"></div>
                </div>
                <div class="stat-footer">
                    <div class="last-updated">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php _e('Just now', 'wp-tester'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card glass-card hover-lift">
                <div class="stat-header">
                    <div class="stat-icon stat-success">
                        <span class="dashicons dashicons-analytics"></span>
                    </div>
                    <div class="stat-trend trend-up">
                        <span>↗</span>8%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_flows']); ?></div>
                <div class="stat-label"><?php _e('Active Flows', 'wp-tester'); ?></div>
                <div class="stat-progress">
                    <div class="progress-bar progress-shimmer" style="width: 60%;"></div>
                </div>
                <div class="stat-footer">
                    <div class="last-updated">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php _e('Just now', 'wp-tester'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card glass-card hover-lift">
                <div class="stat-header">
                    <div class="stat-icon stat-warning">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="stat-trend trend-down">
                        <span>↘</span>3%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['recent_tests']); ?></div>
                <div class="stat-label"><?php _e('Tests (24h)', 'wp-tester'); ?></div>
                <div class="stat-progress">
                    <div class="progress-bar progress-shimmer" style="width: 85%;"></div>
                </div>
                <div class="stat-footer">
                    <div class="last-updated">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php _e('Just now', 'wp-tester'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card glass-card hover-lift">
                <div class="stat-header">
                    <div class="stat-icon stat-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-trend trend-up">
                        <span>↗</span>5%
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['success_rate']; ?>%</div>
                <div class="stat-label"><?php _e('Success Rate', 'wp-tester'); ?></div>
                <div class="stat-progress">
                    <div class="progress-bar progress-shimmer" style="width: <?php echo $stats['success_rate']; ?>%;"></div>
                </div>
                <div class="stat-footer">
                    <div class="last-updated">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php _e('Just now', 'wp-tester'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-2 mt-8">
            <!-- Recent Test Results -->
            <div class="glass-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Recent Test Results', 'wp-tester'); ?>
                    </h3>
                    <div class="card-actions">
                        <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="btn btn-ghost btn-sm">
                            <?php _e('View All', 'wp-tester'); ?>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_results)) : ?>
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
                                    <?php foreach (array_slice($recent_results, 0, 5) as $result) : ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($result->flow_name); ?></strong>
                                                <br>
                                                <small class="text-gray-500"><?php echo esc_html($result->flow_type); ?></small>
                                            </td>
                                            <td>
                                                <span class="wp-tester-badge wp-tester-badge-<?php echo $result->status === 'passed' ? 'success' : ($result->status === 'failed' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($result->status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo esc_html($result->execution_time); ?>s
                                                <br>
                                                <small class="text-gray-500"><?php echo human_time_diff(strtotime($result->started_at), current_time('timestamp')); ?> ago</small>
                                            </td>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . $result->id); ?>" class="btn btn-outline btn-xs">
                                                    <?php _e('View Report', 'wp-tester'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="wp-tester-empty-state">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <h3><?php _e('No Recent Tests', 'wp-tester'); ?></h3>
                            <p><?php _e('Run your first test to see results here.', 'wp-tester'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="btn btn-primary">
                                <?php _e('Start Testing', 'wp-tester'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Flow Health Overview -->
            <div class="glass-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('Flow Health Overview', 'wp-tester'); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($flow_health)) : ?>
                        <div class="wp-tester-flow-health-list">
                            <?php foreach (array_slice($flow_health, 0, 5) as $flow) : ?>
                                <div class="wp-tester-flow-health-item">
                                    <div class="wp-tester-flow-info">
                                        <span class="dashicons dashicons-<?php echo $flow['icon']; ?>"></span>
                                        <div class="wp-tester-flow-details">
                                            <strong><?php echo esc_html($flow['name']); ?></strong>
                                            <small><?php echo esc_html($flow['type']); ?></small>
                                        </div>
                                    </div>
                                    <div class="wp-tester-flow-health-status">
                                        <span class="wp-tester-health-badge" style="background-color: <?php echo $flow['health_score'] > 80 ? '#28a745' : ($flow['health_score'] > 60 ? '#ffc107' : '#dc3545'); ?>">
                                            <?php echo $flow['health_score'] > 80 ? __('Excellent', 'wp-tester') : ($flow['health_score'] > 60 ? __('Good', 'wp-tester') : __('Needs Attention', 'wp-tester')); ?>
                                        </span>
                                        <div class="wp-tester-flow-metrics">
                                            <div class="wp-tester-progress-bar">
                                                <div class="wp-tester-progress-fill" style="width: <?php echo $flow['health_score']; ?>%;"></div>
                                            </div>
                                            <small><?php echo $flow['health_score']; ?>% success • <?php echo $flow['test_count']; ?> tests</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="wp-tester-empty-state">
                            <span class="dashicons dashicons-heart"></span>
                            <h3><?php _e('No Flow Data', 'wp-tester'); ?></h3>
                            <p><?php _e('Create and test some flows to see health metrics.', 'wp-tester'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions (without Learn More buttons) -->
        <div class="quick-actions">
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
