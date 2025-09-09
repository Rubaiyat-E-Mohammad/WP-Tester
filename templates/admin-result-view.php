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
$visual_evidence = $report['visual_evidence'] ?? [];

// Debug: Log what we received
error_log('WP Tester: Template received ' . count($visual_evidence) . ' visual evidence items');
if (!empty($visual_evidence)) {
    foreach ($visual_evidence as $evidence) {
        error_log('WP Tester: Template evidence - Step: ' . $evidence['step_number'] . ', File exists: ' . ($evidence['file_exists'] ? 'yes' : 'no') . ', URL: ' . ($evidence['url'] ?? 'none'));
    }
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
                                        <a href="<?php echo esc_url($flow->start_url ?? '#'); ?>" target="_blank" style="color: #00265e;">
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
                            <span style="font-weight: 700; color: #00265e; font-size: 1.125rem;">
                                <?php echo esc_html(number_format($execution_summary['success_rate'] ?? 0, 1)); ?>%
                            </span>
                        </div>
                        <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(135deg, #00265e 0%, #4ECAB5 100%); width: <?php echo esc_attr($execution_summary['success_rate'] ?? 0); ?>%; transition: width 0.5s ease;"></div>
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
                        <div class="modern-list-item" style="border-left: 4px solid <?php echo esc_attr($step['summary']['status_color'] ?? '#6c757d'); ?>; margin-bottom: 1rem;">
                            <div class="item-info" style="width: 100%;">
                                <div class="item-icon">
                                    <span style="color: <?php echo esc_attr($step['summary']['status_color'] ?? '#6c757d'); ?>; font-size: 1.2rem;">
                                        <?php echo esc_html($step['summary']['status_icon'] ?? '⏳'); ?>
                                    </span>
                                </div>
                                <div class="item-details" style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                        <h4 style="margin: 0; flex: 1;">Step <?php echo esc_html($index + 1); ?>: <?php echo esc_html($step['summary']['action_description'] ?? 'Unknown Action'); ?></h4>
                                        <span class="status-badge <?php echo esc_attr($step['status'] ?? 'pending'); ?>" style="margin-left: 1rem;">
                                            <?php echo esc_html(ucfirst($step['status'] ?? 'pending')); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                        <div>
                                            <strong>Execution Time:</strong> <?php echo esc_html($step['summary']['execution_time_formatted'] ?? '0ms'); ?>
                                        </div>
                                        <?php if (!empty($step['start_time'])): ?>
                                        <div>
                                            <strong>Started:</strong> <?php echo esc_html(date('H:i:s', strtotime($step['start_time']))); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($step['end_time'])): ?>
                                        <div>
                                            <strong>Completed:</strong> <?php echo esc_html(date('H:i:s', strtotime($step['end_time']))); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($step['error'])): ?>
                                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem;">
                                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="color: #dc2626; margin-right: 0.5rem;">⚠️</span>
                                                <strong style="color: #dc2626;">Error Details:</strong>
                                            </div>
                                            <p style="margin: 0; color: #dc2626; font-size: 0.875rem;">
                                                <?php echo esc_html($step['error']); ?>
                                            </p>
                                            <?php if (!empty($step['error_details'])): ?>
                                                <details style="margin-top: 0.5rem;">
                                                    <summary style="cursor: pointer; color: #dc2626; font-size: 0.8125rem;">Show technical details</summary>
                                                    <pre style="background: #f9fafb; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem; font-size: 0.75rem; overflow-x: auto;"><?php echo esc_html(is_array($step['error_details']) ? json_encode($step['error_details'], JSON_PRETTY_PRINT) : $step['error_details']); ?></pre>
                                                </details>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($step['warnings'])): ?>
                                        <div style="background: #fffbeb; border: 1px solid #fed7aa; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem;">
                                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="color: #d97706; margin-right: 0.5rem;">⚠️</span>
                                                <strong style="color: #d97706;">Warnings (<?php echo count($step['warnings']); ?>):</strong>
                                            </div>
                                            <?php foreach ($step['warnings'] as $warning): ?>
                                                <p style="margin: 0.25rem 0; color: #d97706; font-size: 0.875rem;">
                                                    • <?php echo esc_html($warning['message']); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($step['success_indicators'])): ?>
                                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem;">
                                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="color: #16a34a; margin-right: 0.5rem;">✓</span>
                                                <strong style="color: #16a34a;">Success Indicators:</strong>
                                            </div>
                                            <?php foreach ($step['success_indicators'] as $indicator): ?>
                                                <p style="margin: 0.25rem 0; color: #16a34a; font-size: 0.875rem;">
                                                    • <?php echo esc_html($indicator); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($step['suggestions'])): ?>
                                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.75rem; margin-top: 0.5rem;">
                                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="color: #2563eb; margin-right: 0.5rem;">💡</span>
                                                <strong style="color: #2563eb;">Suggestions:</strong>
                                            </div>
                                            <?php foreach ($step['suggestions'] as $suggestion): ?>
                                                <p style="margin: 0.25rem 0; color: #2563eb; font-size: 0.875rem;">
                                                    • <?php echo esc_html($suggestion); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
                                    <h4><?php echo esc_html($failure['message'] ?? 'Unknown Step'); ?></h4>
                                    <p><?php echo esc_html($failure['message'] ?? 'No error message available'); ?></p>
                                    <?php if (isset($failure['error_details']) && !empty($failure['error_details'])): ?>
                                        <p style="color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem;">
                                            <strong>Details:</strong> <?php echo esc_html($failure['error_details']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($failure['suggestion'])): ?>
                                        <p style="color: #00265e; font-weight: 500; margin-top: 0.5rem;">
                                            💡 Suggestion: <?php echo esc_html($failure['suggestion']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
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

