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
                                <?php if (isset($step['error']) && !empty($step['error'])): ?>
                                    <div class="step-error" style="margin-top: 0.5rem; padding: 0.5rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; color: #dc2626; font-size: 0.875rem;">
                                        <strong>Error:</strong> <?php echo esc_html($step['error']); ?>
                                        <?php if (isset($step['error_details']) && !empty($step['error_details'])): ?>
                                            <br><strong>Details:</strong> <?php echo esc_html($step['error_details']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Screenshots -->
            <?php if (!empty($visual_evidence)): ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Screenshots</h2>
                    <div class="status-badge info"><?php echo count($visual_evidence); ?> images</div>
                </div>
                
                <!-- Debug Information -->
                <div style="background: #f0f0f0; padding: 1rem; margin: 1rem; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    Visual Evidence Count: <?php echo count($visual_evidence); ?><br>
                    <?php foreach ($visual_evidence as $i => $screenshot): ?>
                        Screenshot <?php echo $i + 1; ?>: 
                        Path: <?php echo esc_html($screenshot['path']); ?><br>
                        URL: <?php echo esc_html($screenshot['url']); ?><br>
                        Exists: <?php echo $screenshot['file_exists'] ? 'Yes' : 'No'; ?><br>
                        Type: <?php echo esc_html($screenshot['type']); ?><br><br>
                    <?php endforeach; ?>
                </div>

                <div class="screenshots-grid">
                    <?php foreach ($visual_evidence as $screenshot): ?>
                        <div class="screenshot-item">
                            <div class="screenshot-header">
                                <h4>Step <?php echo esc_html($screenshot['step_number']); ?> - <?php echo esc_html(ucfirst($screenshot['type'])); ?></h4>
                                <span class="status-badge <?php echo esc_attr($screenshot['type']); ?>">
                                    <?php echo esc_html(ucfirst($screenshot['type'])); ?>
                                </span>
                            </div>
                            
                            <?php if ($screenshot['file_exists']): ?>
                                <div class="screenshot-image">
                                    <img src="<?php echo esc_url($screenshot['url']); ?>" 
                                         alt="Screenshot for step <?php echo esc_attr($screenshot['step_number']); ?>"
                                         onclick="openScreenshotModal('<?php echo esc_js($screenshot['url']); ?>', '<?php echo esc_js($screenshot['caption']); ?>')">
                                </div>
                            <?php else: ?>
                                <div class="screenshot-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p>Screenshot not available</p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($screenshot['caption'])): ?>
                                <div class="screenshot-caption">
                                    <p><?php echo esc_html($screenshot['caption']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- No Screenshots Message -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Screenshots</h2>
                    <div class="status-badge warning">No screenshots available</div>
                </div>
                <div class="card-body">
                    <p>No screenshots were captured for this test result. This could be because:</p>
                    <ul>
                        <li>Screenshot on failure is disabled in settings</li>
                        <li>No steps failed during this test</li>
                        <li>Screenshots were not saved properly</li>
                    </ul>
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

<script>
function openScreenshotModal(imageUrl, caption) {
    const modal = $(`
        <div id="screenshot-modal" class="screenshot-modal-overlay">
            <div class="screenshot-modal">
                <div class="screenshot-modal-header">
                    <h3>Screenshot</h3>
                    <button class="screenshot-modal-close">&times;</button>
                </div>
                <div class="screenshot-modal-body">
                    <img src="${imageUrl}" alt="Screenshot" class="screenshot-modal-image">
                    ${caption ? `<p class="screenshot-modal-caption">${caption}</p>` : ''}
                </div>
            </div>
        </div>
    `);
    
    $('body').append(modal);
    modal.fadeIn(300);
    
    // Close modal handlers
    modal.find('.screenshot-modal-close').on('click', function() {
        modal.fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    modal.on('click', function(e) {
        if (e.target === this) {
            modal.fadeOut(300, function() {
                $(this).remove();
            });
        }
    });
}
</script>

<style>
/* Screenshots Grid */
.screenshots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.screenshot-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.screenshot-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.screenshot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: white;
    border-bottom: 1px solid #e2e8f0;
}

.screenshot-header h4 {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.screenshot-image {
    padding: 1rem;
    text-align: center;
    background: white;
}

.screenshot-image img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.screenshot-image img:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.screenshot-placeholder {
    padding: 2rem;
    text-align: center;
    color: #64748b;
    background: #f1f5f9;
}

.screenshot-placeholder .dashicons {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

.screenshot-caption {
    padding: 1rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.screenshot-caption p {
    margin: 0;
    font-size: 0.875rem;
    color: #64748b;
    font-style: italic;
}

/* Screenshot Modal */
.screenshot-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
}

.screenshot-modal {
    background: white;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.screenshot-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.screenshot-modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
}

.screenshot-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.screenshot-modal-close:hover {
    background: #e5e7eb;
    color: #374151;
}

.screenshot-modal-body {
    padding: 1.5rem;
    text-align: center;
}

.screenshot-modal-image {
    max-width: 100%;
    max-height: 70vh;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.screenshot-modal-caption {
    margin: 1rem 0 0 0;
    font-size: 0.875rem;
    color: #6b7280;
    font-style: italic;
}
</style>