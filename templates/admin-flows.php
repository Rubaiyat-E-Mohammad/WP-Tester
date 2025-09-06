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
                            if (($flow['is_active'] ?? false)) $active_count++;
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
                            if (isset($flow['success_rate'])) {
                                $total_success += $flow['success_rate'];
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
                            if (!empty($flow['last_tested']) && $flow['last_tested'] !== 'Never') {
                                $last_test = $flow['last_tested'];
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
                        <div class="modern-list-item" data-flow-id="<?php echo esc_attr($flow['id'] ?? ''); ?>">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-<?php 
                                        $flow_type = $flow['flow_type'] ?? 'generic';
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
                                    <h4><?php echo esc_html($flow['flow_name'] ?? 'Unnamed Flow'); ?></h4>
                                    <p><?php echo esc_html($flow['flow_type'] ?? 'Generic'); ?> Flow • <?php echo esc_html(($flow['step_count'] ?? 0) . ' steps'); ?></p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div class="status-badge <?php echo ($flow['is_active'] ?? false) ? 'success' : 'pending'; ?>">
                                    <?php echo ($flow['is_active'] ?? false) ? 'Active' : 'Inactive'; ?>
                                </div>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . ($flow['id'] ?? '')); ?>" 
                                       class="modern-btn modern-btn-secondary modern-btn-small">
                                        View
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . ($flow['id'] ?? '')); ?>" 
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
        
        button.html('<div class="spinner"></div> Discovering...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_discover_flows',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                    alert('Error discovering flows: ' + (response.data || 'Unknown error'));
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
    
    // Test all flows
    $('#bulk-test-flows').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<div class="spinner"></div> Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_run_all_tests',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
            if (response.success) {
                    alert('All tests started successfully!');
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
    
    // Add new flow placeholder
    $('#add-new-flow, #create-flow-btn').on('click', function(e) {
        e.preventDefault();
        alert('Flow creation interface coming soon!');
    });
});
</script>