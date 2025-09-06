<?php
/**
 * WP Tester Dashboard Template - Modern UI
 * Professional CRM-style dashboard with clean design
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$stats = $dashboard_data['statistics'] ?? [];
$recent_results = $dashboard_data['recent_results'] ?? [];
$critical_issues = $dashboard_data['critical_issues'] ?? [];
$flow_health = $dashboard_data['flow_health'] ?? [];
$recommendations = $dashboard_data['recommendations'] ?? [];

// Calculate summary stats
$total_flows = $stats['total_flows'] ?? 0;
$total_tests = $stats['total_tests'] ?? 0;
$success_rate = $stats['success_rate'] ?? 0;
$avg_response_time = $stats['avg_response_time'] ?? 0;
?>

<div class="wp-tester-modern">
    <!-- Modern Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" 
                     alt="WP Tester" class="logo">
                <div class="title-info">
                    <h1>WP Tester Dashboard</h1>
                    <p class="subtitle">Automated testing overview and insights</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-admin-generic"></span>
                    Manage Flows
                </a>
                <a href="#" class="modern-btn modern-btn-primary modern-btn-small" id="run-all-tests">
                    <span class="dashicons dashicons-controls-play"></span>
                    Run Tests
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <!-- Key Metrics -->
        <div class="modern-grid grid-4">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Total Flows</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html($total_flows); ?></div>
                <div class="stat-change positive">
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                    Active monitoring
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Tests Executed</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html($total_tests); ?></div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    Last 30 days
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Success Rate</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html(number_format($success_rate, 1)); ?>%</div>
                <div class="stat-change <?php echo $success_rate >= 90 ? 'positive' : ($success_rate >= 70 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $success_rate >= 90 ? 'arrow-up-alt' : ($success_rate >= 70 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    <?php echo $success_rate >= 90 ? 'Excellent' : ($success_rate >= 70 ? 'Good' : 'Needs attention'); ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Avg Response</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-performance"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html(number_format($avg_response_time, 1)); ?>s</div>
                <div class="stat-change <?php echo $avg_response_time <= 2 ? 'positive' : ($avg_response_time <= 5 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $avg_response_time <= 2 ? 'arrow-up-alt' : ($avg_response_time <= 5 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    <?php echo $avg_response_time <= 2 ? 'Fast' : ($avg_response_time <= 5 ? 'Normal' : 'Slow'); ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <h4 class="action-title">Manage Flows</h4>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <h4 class="action-title">View Results</h4>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-crawl'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <h4 class="action-title">Crawl Results</h4>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-settings'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <h4 class="action-title">Settings</h4>
                </a>
            </div>
        </div>

        <div class="modern-grid grid-2">
            <!-- Recent Test Results -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Recent Test Results</h2>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                        View All
                    </a>
                </div>
                <div class="modern-list">
                    <?php if (!empty($recent_results)) : ?>
                        <?php foreach (array_slice($recent_results, 0, 5) as $result) : ?>
                            <div class="modern-list-item">
                                <div class="item-info">
                                    <div class="item-icon">
                                        <span class="dashicons dashicons-<?php echo ($result['status'] ?? 'unknown') === 'passed' ? 'yes-alt' : (($result['status'] ?? 'unknown') === 'failed' ? 'dismiss' : 'clock'); ?>"></span>
                                    </div>
                                    <div class="item-details">
                                        <h4><?php echo esc_html($result['flow_name'] ?? 'Unknown Flow'); ?></h4>
                                        <p><?php echo esc_html(($result['steps_completed'] ?? 0) . ' of ' . ($result['total_steps'] ?? 0) . ' steps completed'); ?></p>
                                    </div>
                                </div>
                                <div class="item-meta">
                                    <div class="status-badge <?php echo esc_attr($result['status'] ?? 'pending'); ?>">
                                        <?php echo esc_html(ucfirst($result['status'] ?? 'Unknown')); ?>
                                    </div>
                                    <div style="margin-top: 0.25rem; font-size: 0.75rem;">
                                        <?php echo esc_html($result['execution_time'] ?? '0'); ?>s
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="dashicons dashicons-chart-line"></span>
                            </div>
                            <h3>No Recent Tests</h3>
                            <p>Run your first test to see results here</p>
                            <a href="#" class="modern-btn modern-btn-primary" id="run-first-test">
                                <span class="dashicons dashicons-controls-play"></span>
                                Run First Test
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Flow Health Status -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Flow Health Status</h2>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                        Manage
                    </a>
                </div>
                <div class="modern-list">
                    <?php if (!empty($flow_health)) : ?>
                        <?php foreach (array_slice($flow_health, 0, 5) as $flow) : ?>
                            <div class="modern-list-item">
                                <div class="item-info">
                                    <div class="item-icon">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    </div>
                                    <div class="item-details">
                                        <h4><?php echo esc_html($flow['name'] ?? 'Unknown Flow'); ?></h4>
                                        <p><?php echo esc_html('Last tested: ' . ($flow['last_tested'] ?? 'Never')); ?></p>
                                    </div>
                                </div>
                                <div class="item-meta">
                                    <div class="status-badge <?php echo esc_attr($flow['status'] ?? 'pending'); ?>">
                                        <?php echo esc_html(ucfirst($flow['status'] ?? 'Unknown')); ?>
                                    </div>
                                    <div style="margin-top: 0.25rem; font-size: 0.75rem;">
                                        <?php echo esc_html(($flow['success_rate'] ?? 0) . '% success'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <h3>No Flows Found</h3>
                            <p>Create your first flow to start testing</p>
                            <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-primary">
                                <span class="dashicons dashicons-plus-alt"></span>
                                Create Flow
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Critical Issues & Recommendations -->
        <?php if (!empty($critical_issues) || !empty($recommendations)) : ?>
        <div class="modern-grid grid-2">
            <!-- Critical Issues -->
            <?php if (!empty($critical_issues)) : ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Critical Issues</h2>
                    <span class="status-badge error"><?php echo count($critical_issues); ?> issues</span>
                </div>
                <div class="modern-list">
                    <?php foreach (array_slice($critical_issues, 0, 3) as $issue) : ?>
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon" style="background: #fecaca; color: #991b1b;">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($issue['title'] ?? 'Critical Issue'); ?></h4>
                                    <p><?php echo esc_html($issue['description'] ?? 'Issue description not available'); ?></p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <span class="status-badge error">Critical</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recommendations -->
            <?php if (!empty($recommendations)) : ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Recommendations</h2>
                    <span class="status-badge info"><?php echo count($recommendations); ?> tips</span>
                </div>
                <div class="modern-list">
                    <?php foreach (array_slice($recommendations, 0, 3) as $recommendation) : ?>
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon" style="background: #dbeafe; color: #1e40af;">
                                    <span class="dashicons dashicons-lightbulb"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($recommendation['title'] ?? 'Recommendation'); ?></h4>
                                    <p><?php echo esc_html($recommendation['description'] ?? 'Recommendation description not available'); ?></p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <span class="status-badge info">Tip</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Run all tests functionality
    $('#run-all-tests, #run-first-test').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const originalText = button.html();
        
        // Show loading state
        button.html('<div class="spinner"></div> Running Tests...').prop('disabled', true);
        
        // AJAX call to run tests
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_run_all_tests',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show updated results
                    location.reload();
                } else {
                    alert('Error running tests: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error connecting to server');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>