<?php
/**
 * Admin Flow Test Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract result data if available
$status = $result['status'] ?? 'unknown';
$execution_time = $result['execution_time'] ?? 0;
$steps_completed = $result['steps_completed'] ?? 0;
$total_steps = $result['total_steps'] ?? 0;
$error_message = $result['error_message'] ?? '';
?>

<div class="wp-tester-modern">
    <!-- Modern Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" 
                     alt="WP Tester" class="logo">
                <div class="title-info">
                    <h1><?php echo esc_html($flow->flow_name); ?> - Test Results</h1>
                    <p class="subtitle">Live test execution results</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Back to Flow
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-primary modern-btn-small">
                    <span class="dashicons dashicons-controls-play"></span>
                    Run Test Again
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <?php if (isset($result) && !empty($result)): ?>
            
            <!-- Test Summary Stats -->
            <div class="modern-grid grid-4">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Status</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-<?php echo $status === 'passed' ? 'yes-alt' : ($status === 'failed' ? 'dismiss' : 'clock'); ?>"></span>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size: 1.5rem;">
                        <span class="status-badge <?php echo esc_attr($status); ?>">
                            <?php echo esc_html(strtoupper($status)); ?>
                        </span>
                    </div>
                    <div class="stat-change <?php echo $status === 'passed' ? 'positive' : ($status === 'failed' ? 'negative' : 'neutral'); ?>">
                        <span class="dashicons dashicons-<?php echo $status === 'passed' ? 'yes-alt' : ($status === 'failed' ? 'dismiss' : 'clock'); ?>"></span>
                        <?php echo $status === 'passed' ? 'Success' : ($status === 'failed' ? 'Failed' : 'In Progress'); ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Steps Executed</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-list-view"></span>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo esc_html($steps_completed); ?></div>
                    <div class="stat-change neutral">
                        <span class="dashicons dashicons-admin-generic"></span>
                        of <?php echo esc_html($total_steps); ?> total
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-label">Steps Passed</h3>
                        <div class="stat-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo esc_html($steps_completed); ?></div>
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
                    <div class="stat-value"><?php echo esc_html($total_steps - $steps_completed); ?></div>
                    <div class="stat-change <?php echo ($total_steps - $steps_completed) > 0 ? 'negative' : 'neutral'; ?>">
                        <span class="dashicons dashicons-<?php echo ($total_steps - $steps_completed) > 0 ? 'dismiss' : 'minus'; ?>"></span>
                        <?php echo ($total_steps - $steps_completed) > 0 ? 'Issues' : 'No Failures'; ?>
                    </div>
                </div>
            </div>

            <!-- Test Details -->
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
                                    <span class="dashicons dashicons-performance"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Execution Time</h4>
                                    <p><?php echo esc_html(number_format($execution_time, 6)); ?>s</p>
                                </div>
                            </div>
                        </div>

                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Flow Type</h4>
                                    <p><?php echo esc_html(ucfirst($flow->flow_type)); ?></p>
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
                                        <a href="<?php echo esc_url($flow->start_url); ?>" target="_blank" style="color: #00265e;">
                                            <?php echo esc_html($flow->start_url); ?>
                                            <span class="dashicons dashicons-external" style="font-size: 12px;"></span>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-clock"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Test Time</h4>
                                    <p><?php echo esc_html(current_time('M j, Y g:i A')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress & Results -->
                <div class="modern-card">
                    <div class="card-header">
                        <h2 class="card-title">Progress</h2>
                        <div class="status-badge <?php echo $status === 'passed' ? 'success' : ($status === 'failed' ? 'error' : 'warning'); ?>">
                            <?php echo esc_html(number_format(($steps_completed / max(1, $total_steps)) * 100, 1)); ?>% Complete
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div style="margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 600; color: #374151;">Test Progress</span>
                            <span style="font-weight: 700; color: #00265e; font-size: 1.125rem;">
                                <?php echo esc_html($steps_completed); ?>/<?php echo esc_html($total_steps); ?>
                            </span>
                        </div>
                        <div style="height: 12px; background: #f1f5f9; border-radius: 6px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(135deg, #00265e 0%, #4ECAB5 100%); width: <?php echo esc_attr(($steps_completed / max(1, $total_steps)) * 100); ?>%; transition: width 0.5s ease;"></div>
                        </div>
                    </div>

                    <!-- Success Rate -->
                    <div class="modern-list">
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-details">
                                    <h4>Success Rate</h4>
                                    <p><?php echo esc_html(number_format(($steps_completed / max(1, $total_steps)) * 100, 1)); ?>%</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-details">
                                    <h4>Average Step Time</h4>
                                    <p><?php echo esc_html(number_format($execution_time / max(1, $steps_completed), 3)); ?>s per step</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Details (if any) -->
            <?php if (!empty($error_message)): ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Error Details</h2>
                    <div class="status-badge error">Failed</div>
                </div>

                <div class="modern-list">
                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon" style="background: #fecaca; color: #991b1b;">
                                <span class="dashicons dashicons-warning"></span>
                            </div>
                            <div class="item-details">
                                <h4>Test Failed</h4>
                                <p style="color: #ef4444; font-weight: 500;">
                                    <?php echo esc_html($error_message); ?>
                                </p>
                                <p style="margin-top: 0.5rem; color: #64748b;">
                                    The test failed at step <?php echo esc_html($steps_completed + 1); ?> of <?php echo esc_html($total_steps); ?>.
                                </p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <span class="status-badge error">Error</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Next Steps</h2>
                </div>
                
                <div class="quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>" class="quick-action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <h4 class="action-title">View Flow Details</h4>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . $flow->id); ?>" class="quick-action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-edit"></span>
                        </div>
                        <h4 class="action-title">Edit Flow</h4>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="quick-action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                        <h4 class="action-title">All Results</h4>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="quick-action-card">
                        <div class="action-icon">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </div>
                        <h4 class="action-title">All Flows</h4>
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <span class="dashicons dashicons-controls-play"></span>
                </div>
                <h3>Test Not Found</h3>
                <p>The test result data is not available. Please try running the test again.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-secondary">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        Back to Flow
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-primary">
                        <span class="dashicons dashicons-controls-play"></span>
                        Run Test
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>