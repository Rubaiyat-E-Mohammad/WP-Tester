<?php
/**
 * Admin Flows Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
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
                    <h1>User Flows</h1>
                    <p class="subtitle">Manage and test your user flows</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Dashboard
                </a>
                <button id="wp-tester-discover-flows" class="modern-btn modern-btn-primary modern-btn-small">
                    <span class="dashicons dashicons-search"></span>
                    Discover Flows
        </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <!-- Flow Stats Overview -->
        <div class="modern-grid grid-4">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Total Flows</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($flows ?? []); ?></div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-admin-generic"></span>
                    Configured
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Active Flows</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                </div>
                <div class="stat-value"><?php 
                    $active_count = 0;
                    if (!empty($flows)) {
                        foreach ($flows as $flow) {
                            if (($flow->is_active ?? false)) $active_count++;
                        }
                    }
                    echo $active_count;
                ?></div>
                <div class="stat-change positive">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Running
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Success Rate</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $success_rate = 0;
                    if (!empty($flows)) {
                        $total_success = 0;
                        $total_flows = 0;
                        foreach ($flows as $flow) {
                            if (isset($flow->success_rate)) {
                                $total_success += $flow->success_rate;
                                $total_flows++;
                            }
                        }
                        if ($total_flows > 0) {
                            $success_rate = round($total_success / $total_flows, 1);
                        }
                    }
                    echo $success_rate;
                    ?>%
                </div>
                <div class="stat-change <?php echo $success_rate >= 90 ? 'positive' : ($success_rate >= 70 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $success_rate >= 90 ? 'arrow-up-alt' : ($success_rate >= 70 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    Overall
                </div>
    </div>
    
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Last Test</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 1.25rem;">
                    <?php
                    $last_test = 'Never';
                    if (!empty($flows)) {
                        foreach ($flows as $flow) {
                            if (!empty($flow->last_tested) && $flow->last_tested !== 'Never') {
                                $last_test = $flow->last_tested;
                                break;
                            }
                        }
                    }
                    echo esc_html($last_test);
                    ?>
                </div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    Timestamp
                </div>
            </div>
        </div>

        <!-- Flows List -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">All Flows</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="modern-btn modern-btn-secondary modern-btn-small" id="bulk-test-flows">
                        <span class="dashicons dashicons-controls-play"></span>
                        Test All
                    </button>
                    <button class="modern-btn modern-btn-primary modern-btn-small" id="add-new-flow">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Add New
                    </button>
                </div>
            </div>

            <?php if (!empty($flows)) : ?>
                <div class="modern-list">
                    <?php foreach ($flows as $flow) : ?>
                        <div class="modern-list-item" data-flow-id="<?php echo esc_attr($flow->id ?? ''); ?>">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-<?php 
                                        $flow_type = $flow->flow_type ?? 'generic';
                                        $icons = [
                                            'registration' => 'admin-users',
                                            'login' => 'admin-network', 
                                            'contact' => 'email-alt',
                                            'search' => 'search',
                                            'woocommerce' => 'cart',
                                            'navigation' => 'menu'
                                        ];
                                        echo $icons[$flow_type] ?? 'admin-generic';
                                    ?>"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($flow->flow_name ?? 'Unnamed Flow'); ?></h4>
                                    <p><?php 
                                        echo esc_html($flow->flow_type ?? 'Generic') . ' Flow • ';
                                        $steps = 0;
                                        if (isset($flow->steps) && !empty($flow->steps)) {
                                            $decoded_steps = json_decode($flow->steps, true);
                                            if (is_array($decoded_steps)) {
                                                $steps = count($decoded_steps);
                                            }
                                        }
                                        echo esc_html($steps . ' steps');
                                    ?></p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div class="status-badge <?php echo ($flow->is_active ?? false) ? 'success' : 'pending'; ?>">
                                    <?php echo ($flow->is_active ?? false) ? 'Active' : 'Inactive'; ?>
                                </div>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . ($flow->id ?? '')); ?>" 
                                       class="modern-btn modern-btn-secondary modern-btn-small">
                                        View
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . ($flow->id ?? '')); ?>" 
                                       class="modern-btn modern-btn-primary modern-btn-small">
                                        Test
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <h3>No Flows Found</h3>
                    <p>Get started by discovering flows on your site or creating a new one manually.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                        <button class="modern-btn modern-btn-secondary" id="discover-flows-btn">
                            <span class="dashicons dashicons-search"></span>
                            Discover Flows
                        </button>
                        <button class="modern-btn modern-btn-primary" id="create-flow-btn">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Create Flow
                        </button>
                    </div>
    </div>
            <?php endif; ?>
    </div>
    
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Discover flows functionality
    $('#wp-tester-discover-flows, #discover-flows-btn').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const originalText = button.html();
        
        // Show progress modal
        showProgressModal('Discovering Flows', 'Analyzing your site for testing opportunities...');
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Discovering...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_discover_flows',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideProgressModal();
                if (response.success) {
                    showSuccessModal('Flow Discovery Complete!', 
                        'Found ' + (response.data.discovered_flows || 0) + ' new testing flows. Refreshing page...');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showErrorModal('Discovery Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function() {
                hideProgressModal();
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Add New Flow
    $('#add-new-flow, #create-flow-btn').on('click', function(e) {
        e.preventDefault();
        window.location.href = '<?php echo admin_url('admin.php?page=wp-tester-flows&action=add'); ?>';
    });
    
    // Test all flows
    $('#bulk-test-flows').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const originalText = button.html();
        
        // Show progress modal
        showProgressModal('Running Tests', 'Executing all active flows...');
        
        button.html('<span class="dashicons dashicons-controls-play"></span> Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_run_all_tests',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideProgressModal();
                if (response.success) {
                    showSuccessModal('Tests Started!', 
                        'All tests have been queued for execution. Check results in a few minutes.');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showErrorModal('Test Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function() {
                hideProgressModal();
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Modal functions
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
    background: linear-gradient(135deg, #1FC09A 0%, #4ECAB5 100%);
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
    background: linear-gradient(135deg, #1FC09A 0%, #4ECAB5 100%);
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