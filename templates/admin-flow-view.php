<?php
/**
 * Admin Flow View Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure flow object exists
if (!isset($flow) || !is_object($flow)) {
    wp_die(__('Flow not found.', 'wp-tester'));
}

// Get flow steps safely
$flow_steps = array();
if (isset($flow->steps) && !empty($flow->steps)) {
    $decoded_steps = json_decode($flow->steps, true);
    if (is_array($decoded_steps)) {
        $flow_steps = $decoded_steps;
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
                    <h1><?php echo esc_html($flow->flow_name ?? 'Unnamed Flow'); ?></h1>
                    <p class="subtitle"><?php echo esc_html(ucfirst($flow->flow_type ?? 'unknown')); ?> Flow Details</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Back to Flows
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . ($flow->id ?? 0)); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-edit"></span>
                    Edit Flow
                </a>
                <button type="button" class="modern-btn modern-btn-primary modern-btn-small test-flow-btn" 
                        data-flow-id="<?php echo ($flow->id ?? 0); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                    Test Flow
                </button>
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
                    <div class="status-badge <?php echo ($flow->is_active ?? 0) ? 'success' : 'pending'; ?>">
                        <?php echo ($flow->is_active ?? 0) ? 'Active' : 'Inactive'; ?>
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
                                <p><?php echo esc_html(ucfirst($flow->flow_type ?? 'unknown')); ?></p>
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
                                <span class="dashicons dashicons-sort"></span>
                            </div>
                            <div class="item-details">
                                <h4>Priority</h4>
                                <p><?php echo esc_html($flow->priority ?? 5); ?></p>
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
                                <p><?php echo esc_html(date('M j, Y g:i A', strtotime($flow->created_at ?? 'now'))); ?></p>
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
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . ($flow->id ?? 0)); ?>" class="modern-btn modern-btn-primary">
                        <span class="dashicons dashicons-edit"></span>
                        Configure Steps
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Test Results -->
        <?php if (isset($recent_results) && !empty($recent_results) && is_array($recent_results)): ?>
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Recent Test Results</h2>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    View All Results
                </a>
            </div>

            <div class="modern-list">
                <?php foreach (array_slice($recent_results ?: [], 0, 5) as $result): ?>
                    <?php if (!is_object($result)) continue; ?>
                    <div class="modern-list-item">
                        <div class="item-info">
                            <div class="item-icon">
                                <span class="dashicons dashicons-<?php echo ($result->status ?? 'failed') === 'passed' ? 'yes-alt' : 'dismiss'; ?>"></span>
                            </div>
                            <div class="item-details">
                                <h4>Test Run - <?php echo esc_html(date('M j, Y g:i A', strtotime($result->started_at ?? 'now'))); ?></h4>
                                <p>
                                    <?php echo esc_html($result->steps_executed ?? 0); ?> of <?php echo esc_html(($result->steps_executed ?? 0) + ($result->steps_failed ?? 0)); ?> steps • 
                                    <?php echo esc_html(number_format($result->execution_time ?? 0, 3)); ?>s execution time
                                </p>
                            </div>
                        </div>
                        <div class="item-meta">
                            <div class="status-badge <?php echo esc_attr($result->status ?? 'pending'); ?>">
                                <?php echo esc_html(ucfirst($result->status ?? 'unknown')); ?>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . ($result->id ?? 0)); ?>" 
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

<script>
jQuery(document).ready(function($) {
    // Ensure ajaxurl is available
    if (typeof ajaxurl === 'undefined') {
        ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }
    
    // Test flow functionality with progress modal
    $('.test-flow-btn').on('click', function() {
        const flowId = parseInt($(this).data('flow-id'));
        const button = $(this);
        const originalText = button.html();
        
        if (!flowId || flowId === 0) {
            showErrorModal('Invalid Flow ID', 'Invalid flow ID provided');
            return;
        }
        
        // Show progress modal
        showProgressModal('Running Test', 'Executing flow test...');
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_test_flow',
                flow_id: flowId,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideProgressModal();
                if (response.success) {
                    showSuccessModal('Test Complete!', 
                        'Flow test executed successfully. ' + (response.data.message || ''));
                    // Optionally refresh the page to show updated results
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showErrorModal('Test Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                hideProgressModal();
                console.error('Test AJAX Error:', {xhr, status, error});
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Modal functions (same as flows page)
    function showProgressModal(title, message) {
        const modal = $(`
            <div id="wp-tester-progress-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <div class="wp-tester-progress-container">
                            <div class="wp-tester-progress-bar">
                                <div class="wp-tester-progress-fill"></div>
                            </div>
                            <p class="wp-tester-progress-message">${message}</p>
                        </div>
                    </div>
                </div>
            </div>
        `);
        $('body').append(modal);
        modal.fadeIn(300);
    }
    
    function hideProgressModal() {
        $('#wp-tester-progress-modal').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    function showSuccessModal(title, message) {
        const modal = $(`
            <div id="wp-tester-success-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal wp-tester-modal-success">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-primary" onclick="$('#wp-tester-success-modal').fadeOut(300, function(){$(this).remove();})">OK</button>
                    </div>
                </div>
            </div>
        `);
        $('body').append(modal);
        modal.fadeIn(300);
    }
    
    function showErrorModal(title, message) {
        const modal = $(`
            <div id="wp-tester-error-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal wp-tester-modal-error">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon">
                            <span class="dashicons dashicons-dismiss"></span>
                        </div>
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-secondary" onclick="$('#wp-tester-error-modal').fadeOut(300, function(){$(this).remove();})">Close</button>
                    </div>
                </div>
            </div>
        `);
        $('body').append(modal);
        modal.fadeIn(300);
    }
});
</script>

<style>
/* Progress Modal Styles */
.wp-tester-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
}

.wp-tester-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    min-width: 400px;
    max-width: 500px;
    overflow: hidden;
}

.wp-tester-modal-header {
    padding: 2rem 2rem 1rem 2rem;
    text-align: center;
}

.wp-tester-modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.wp-tester-modal-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem auto;
    font-size: 24px;
}

.wp-tester-modal-success .wp-tester-modal-icon {
    background: linear-gradient(135deg, #00265e 0%, #4ECAB5 100%);
    color: white;
}

.wp-tester-modal-error .wp-tester-modal-icon {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.wp-tester-modal-body {
    padding: 0 2rem 1rem 2rem;
}

.wp-tester-modal-body p {
    margin: 0;
    color: #6b7280;
    text-align: center;
    line-height: 1.6;
}

.wp-tester-modal-footer {
    padding: 1rem 2rem 2rem 2rem;
    text-align: center;
}

.wp-tester-progress-container {
    text-align: center;
}

.wp-tester-progress-bar {
    width: 100%;
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.wp-tester-progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #00265e 0%, #4ECAB5 100%);
    width: 0%;
    animation: progressAnimation 2s ease-in-out infinite;
}

@keyframes progressAnimation {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

.wp-tester-progress-message {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}
</style>