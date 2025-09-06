<?php
/**
 * Admin Result View Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get flow and test result data
$test_result = $report['test_result'] ?? null;
$flow = $report['flow'] ?? null;
$execution_summary = $report['execution_summary'] ?? [];
$step_details = $report['step_details'] ?? [];
$failure_analysis = $report['failure_analysis'] ?? [];
?>

<div class="wp-tester-modern">
    <!-- Modern Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" 
                     alt="WP Tester" class="logo">
                <div class="title-info">
                    <h1><?php echo esc_html($flow->flow_name ?? 'Test Result'); ?> - Test Results</h1>
                    <p class="subtitle">Detailed test execution analysis</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Back to Results
                </a>
                <?php if ($flow): ?>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-primary modern-btn-small">
                        <span class="dashicons dashicons-controls-play"></span>
                        Run Test Again
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <?php if (isset($report) && !empty($report)): ?>
            
            <!-- Test Summary Stats -->
            <div class="modern-grid grid-4">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Status</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-<?php echo ($execution_summary['overall_status'] ?? '') === 'passed' ? 'yes-alt' : 'dismiss'; ?>"></span>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <span class="status-badge <?php echo esc_attr($execution_summary['overall_status'] ?? 'pending'); ?>">
                            <?php echo esc_html(ucfirst($execution_summary['overall_status'] ?? 'Unknown')); ?>
                        </span>
                    </div>
                    <div class="stat-change neutral">
                        <span class="dashicons dashicons-info"></span>
                        Final Result
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Steps Executed</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-list-view"></span>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo esc_html($execution_summary['total_steps'] ?? 0); ?></div>
                    <div class="stat-change neutral">
                        <span class="dashicons dashicons-admin-generic"></span>
                        Total Steps
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Steps Passed</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo esc_html($execution_summary['passed_steps'] ?? 0); ?></div>
                    <div class="stat-change positive">
                        <span class="dashicons dashicons-yes-alt"></span>
                        Successful
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Steps Failed</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-dismiss"></span>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo esc_html($execution_summary['failed_steps'] ?? 0); ?></div>
                    <div class="stat-change <?php echo ($execution_summary['failed_steps'] ?? 0) > 0 ? 'negative' : 'neutral'; ?>">
                        <span class="dashicons dashicons-<?php echo ($execution_summary['failed_steps'] ?? 0) > 0 ? 'dismiss' : 'minus'; ?>"></span>
                        <?php echo ($execution_summary['failed_steps'] ?? 0) > 0 ? 'Issues Found' : 'No Failures'; ?>
                    </div>
                </div>
            </div>

            <!-- Execution Details -->
            <div class="modern-grid grid-2">
                <!-- Test Information -->
                <div class="modern-card">
                    <div class="card-header">
                        <h2 class="card-title">Test Information</h2>
                        <div class="status-badge info">Details</div>
                    </div>
                    
                    <div class="modern-list">
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Flow Type</h4>
                                    <p><?php echo esc_html(ucfirst($flow->flow_type ?? 'Unknown')); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-admin-links"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Start URL</h4>
                                    <p>
                                        <a href="<?php echo esc_url($flow->start_url ?? '#'); ?>" target="_blank" style="color: #1FC09A;">
                                            <?php echo esc_html($flow->start_url ?? 'Not specified'); ?>
                                            <span class="dashicons dashicons-external" style="font-size: 12px;"></span>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-performance"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Execution Time</h4>
                                    <p><?php echo esc_html(number_format($execution_summary['execution_time'] ?? 0, 3)); ?>s</p>
                                </div>
                            </div>
                        </div>

                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-clock"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Test Date</h4>
                                    <p><?php echo esc_html($execution_summary['started_at'] ?? 'Unknown'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success Rate -->
                <div class="modern-card">
                    <div class="card-header">
                        <h2 class="card-title">Performance Metrics</h2>
                        <div class="status-badge <?php echo ($execution_summary['success_rate'] ?? 0) >= 90 ? 'success' : (($execution_summary['success_rate'] ?? 0) >= 70 ? 'warning' : 'error'); ?>">
                            <?php echo esc_html(number_format($execution_summary['success_rate'] ?? 0, 1)); ?>% Success
                        </div>
                    </div>
                    
                    <div style="margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 600; color: #374151;">Success Rate</span>
                            <span style="font-weight: 700; color: #1FC09A; font-size: 1.125rem;">
                                <?php echo esc_html(number_format($execution_summary['success_rate'] ?? 0, 1)); ?>%
                            </span>
                        </div>
                        <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(135deg, #1FC09A 0%, #4ECAB5 100%); width: <?php echo esc_attr($execution_summary['success_rate'] ?? 0); ?>%; transition: width 0.5s ease;"></div>
                        </div>
                    </div>

                    <div class="modern-list">
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-details">
                                    <h4>Average Step Time</h4>
                                    <p><?php echo esc_html(number_format(($execution_summary['execution_time'] ?? 0) / max(1, $execution_summary['total_steps'] ?? 1), 3)); ?>s per step</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step Details -->
            <?php if (!empty($step_details)): ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Step-by-Step Execution</h2>
                    <div class="status-badge info"><?php echo count($step_details); ?> steps</div>
                </div>

                <div class="modern-list">
                    <?php foreach ($step_details as $index => $step): ?>
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-<?php 
                                        echo ($step['status'] ?? '') === 'passed' ? 'yes-alt' : 
                                            (($step['status'] ?? '') === 'failed' ? 'dismiss' : 'clock'); 
                                    ?>"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Step <?php echo esc_html($index + 1); ?>: <?php echo esc_html($step['action'] ?? 'Unknown Action'); ?></h4>
                                    <p>
                                        <?php if (!empty($step['target'])): ?>
                                            Target: <?php echo esc_html($step['target']); ?> • 
                                        <?php endif; ?>
                                        Duration: <?php echo esc_html(number_format($step['execution_time'] ?? 0, 3)); ?>s
                                    </p>
                                    <?php if (!empty($step['error_message'])): ?>
                                        <p style="color: #ef4444; font-weight: 500; margin-top: 0.5rem;">
                                            Error: <?php echo esc_html($step['error_message']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div class="status-badge <?php echo esc_attr($step['status'] ?? 'pending'); ?>">
                                    <?php echo esc_html(ucfirst($step['status'] ?? 'Unknown')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Failure Analysis -->
            <?php if (!empty($failure_analysis) && !empty($failure_analysis['failure_details'])): ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Failure Analysis</h2>
                    <div class="status-badge error"><?php echo count($failure_analysis['failure_details']); ?> issues</div>
                </div>

                <div class="modern-list">
                    <?php foreach ($failure_analysis['failure_details'] as $failure): ?>
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon" style="background: #fecaca; color: #991b1b;">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($failure['step'] ?? 'Unknown Step'); ?></h4>
                                    <p><?php echo esc_html($failure['error'] ?? 'No error message available'); ?></p>
                                    <?php if (!empty($failure['suggestion'])): ?>
                                        <p style="color: #1FC09A; font-weight: 500; margin-top: 0.5rem;">
                                            💡 Suggestion: <?php echo esc_html($failure['suggestion']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-meta">
                                <span class="status-badge error">Error</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h3>No Test Data Available</h3>
                <p>The test result data could not be found or is incomplete.</p>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="modern-btn modern-btn-primary">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Back to Results
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>