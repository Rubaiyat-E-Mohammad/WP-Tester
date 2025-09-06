<?php
/**
 * Admin Flow View Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get flow steps
$flow_steps = json_decode($flow->flow_steps, true) ?: array();
?>

<div class="wp-tester-modern">
    <!-- Modern Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" 
                     alt="WP Tester" class="logo">
                <div class="title-info">
                    <h1><?php echo esc_html($flow->flow_name); ?></h1>
                    <p class="subtitle"><?php echo esc_html(ucfirst($flow->flow_type)); ?> Flow Details</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Back to Flows
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-edit"></span>
                    Edit Flow
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-primary modern-btn-small">
                    <span class="dashicons dashicons-controls-play"></span>
                    Test Flow
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <!-- Flow Information Cards -->
        <div class="modern-grid grid-2">
            <!-- Basic Information -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Flow Information</h2>
                    <div class="status-badge <?php echo $flow->is_active ? 'success' : 'pending'; ?>">
                        <?php echo $flow->is_active ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                
                <div class="modern-list">
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
                                    <a href="<?php echo esc_url($flow->start_url); ?>" target="_blank" style="color: #1FC09A;">
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
                                <span class="dashicons dashicons-sort"></span>
                            </div>
                            <div class="item-details">
                                <h4>Priority</h4>
                                <p><?php echo esc_html($flow->priority); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="item-details">
                                <h4>Created</h4>
                                <p><?php echo esc_html(date('M j, Y g:i A', strtotime($flow->created_at))); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Statistics -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Test Statistics</h2>
                    <div class="status-badge info">Performance</div>
                </div>
                
                <div class="modern-list">
                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-list-view"></span>
                            </div>
                            <div class="item-details">
                                <h4>Total Steps</h4>
                                <p><?php echo count($flow_steps); ?> steps configured</p>
                            </div>
                        </div>
                    </div>

                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-chart-line"></span>
                            </div>
                            <div class="item-details">
                                <h4>Success Rate</h4>
                                <p><?php echo esc_html($flow->success_rate ?? 'N/A'); ?>% success rate</p>
                            </div>
                        </div>
                    </div>

                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="item-details">
                                <h4>Last Tested</h4>
                                <p><?php echo esc_html($flow->last_tested ?? 'Never'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="item-details">
                                <h4>Avg. Execution Time</h4>
                                <p><?php echo esc_html($flow->avg_execution_time ?? 'N/A'); ?>s average</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flow Steps -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Flow Steps</h2>
                <div class="status-badge info"><?php echo count($flow_steps); ?> steps</div>
            </div>

            <?php if (!empty($flow_steps)): ?>
                <div class="modern-list">
                    <?php foreach ($flow_steps as $index => $step): ?>
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-<?php 
                                        $action = $step['action'] ?? '';
                                        echo $action === 'navigate' ? 'admin-links' : 
                                            ($action === 'fill_form' ? 'edit' : 
                                            ($action === 'click' ? 'admin-generic' : 
                                            ($action === 'submit' ? 'yes-alt' : 'admin-tools'))); 
                                    ?>"></span>
                                </div>
                                <div class="item-details">
                                    <h4>Step <?php echo esc_html($index + 1); ?>: <?php echo esc_html(ucwords(str_replace('_', ' ', $step['action'] ?? 'Unknown'))); ?></h4>
                                    <p>
                                        <?php if (!empty($step['target'])): ?>
                                            Target: <?php echo esc_html($step['target']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($step['value'])): ?>
                                            • Value: <?php echo esc_html($step['value']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($step['url'])): ?>
                                            • URL: <?php echo esc_html($step['url']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div class="status-badge info">
                                    <?php echo esc_html(ucfirst($step['action'] ?? 'Unknown')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-list-view"></span>
                    </div>
                    <h3>No Steps Configured</h3>
                    <p>This flow doesn't have any steps configured yet.</p>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . $flow->id); ?>" class="modern-btn modern-btn-primary">
                        <span class="dashicons dashicons-edit"></span>
                        Configure Steps
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Test Results -->
        <?php if (!empty($recent_results)): ?>
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Recent Test Results</h2>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    View All Results
                </a>
            </div>

            <div class="modern-list">
                <?php foreach (array_slice($recent_results, 0, 5) as $result): ?>
                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-<?php echo $result->status === 'passed' ? 'yes-alt' : 'dismiss'; ?>"></span>
                            </div>
                            <div class="item-details">
                                <h4>Test Run - <?php echo esc_html(date('M j, Y g:i A', strtotime($result->executed_at))); ?></h4>
                                <p>
                                    <?php echo esc_html($result->steps_completed); ?> of <?php echo esc_html($result->total_steps); ?> steps • 
                                    <?php echo esc_html($result->execution_time); ?>s execution time
                                </p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <div class="status-badge <?php echo esc_attr($result->status); ?>">
                                <?php echo esc_html(ucfirst($result->status)); ?>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . $result->id); ?>" 
                                   class="modern-btn modern-btn-secondary modern-btn-small">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>