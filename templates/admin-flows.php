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
                <button id="test-connection" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-admin-tools"></span>
                    Test Connection
                </button>
                <button id="cleanup-flows" class="modern-btn modern-btn-danger modern-btn-small">
                    <span class="dashicons dashicons-trash"></span>
                    Cleanup Flows
                </button>
                <button id="cleanup-duplicates" class="modern-btn modern-btn-warning modern-btn-small">
                    <span class="dashicons dashicons-trash"></span>
                    Cleanup Duplicates
                </button>
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
                    // Get last test from database stats instead of individual flows
                    $database = new WP_Tester_Database();
                    $stats = $database->get_dashboard_stats();
                    $last_test = $stats['last_test'] ?? 'Never';
                    
                    if ($last_test !== 'Never') {
                        $last_test = date('M j, Y H:i', strtotime($last_test));
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
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <div style="position: relative;">
                        <input type="text" id="search-flows" placeholder="Search flows by name, type, or description..." 
                               style="padding: 0.5rem 0.5rem 0.5rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem; width: 300px; outline: none;"
                               onkeyup="filterFlows();">
                        <span class="dashicons dashicons-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 16px;"></span>
                    </div>
                    <select id="filter-flow-type" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem;">
                        <option value="">All Types</option>
                        <option value="manual">Manual</option>
                        <option value="ai_generated">AI Generated</option>
                        <option value="discovered">Discovered</option>
                    </select>
                    <select id="filter-flow-status" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
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
                <!-- Bulk Actions Header -->
                <div class="bulk-actions-header" style="display: none;">
                    <div class="bulk-actions-info">
                        <span class="selected-count">0 flows selected</span>
                    </div>
                    <div class="bulk-actions-buttons">
                        <select id="bulk-action-selector" class="modern-select">
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete Selected</option>
                            <option value="activate">Activate Selected</option>
                            <option value="deactivate">Deactivate Selected</option>
                        </select>
                        <button id="apply-bulk-action" class="modern-btn modern-btn-primary modern-btn-small" disabled>
                            Apply
                        </button>
                        <button id="clear-selection" class="modern-btn modern-btn-secondary modern-btn-small">
                            Clear Selection
                        </button>
                    </div>
                </div>
                
                <!-- Select All Header -->
                <div class="select-all-header">
                    <div class="select-all-checkbox">
                        <input type="checkbox" id="select-all-flows" class="select-all-checkbox-input">
                        <label for="select-all-flows">Select All Flows</label>
                    </div>
                </div>
                
                <div class="modern-list">
                    <?php foreach ($flows as $flow) : ?>
                        <div class="modern-list-item" data-flow-id="<?php echo esc_attr($flow->id ?? ''); ?>" data-flow-type="<?php echo esc_attr($flow->flow_type ?? 'generic'); ?>" data-flow-status="<?php echo esc_attr($flow->is_active ? 'active' : 'inactive'); ?>">
                            <div class="item-checkbox">
                                <input type="checkbox" class="flow-checkbox" value="<?php echo esc_attr($flow->id ?? ''); ?>" id="flow-<?php echo esc_attr($flow->id ?? ''); ?>">
                                <label for="flow-<?php echo esc_attr($flow->id ?? ''); ?>"></label>
                            </div>
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
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . ($flow->id ?? '')); ?>" 
                                       class="modern-btn modern-btn-secondary modern-btn-small">
                                        Edit
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . ($flow->id ?? '')); ?>" 
                                       class="modern-btn modern-btn-primary modern-btn-small">
                                        Test
                                    </a>
                                    <button type="button" class="modern-btn modern-btn-danger modern-btn-small wp-tester-delete-flow" 
                                            data-flow-id="<?php echo ($flow->id ?? ''); ?>"
                                            style="background: #ef4444; color: white; border: 1px solid #dc2626;">
                                        Delete
                                    </button>
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
    // Ensure ajaxurl is available
    if (typeof ajaxurl === 'undefined') {
        ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }

    // Search and filter functionality for flows
    window.filterFlows = function() {
        const searchTerm = $('#search-flows').val().toLowerCase();
        const selectedType = $('#filter-flow-type').val();
        const selectedStatus = $('#filter-flow-status').val();
        const $items = $('.modern-list-item');
        
        $items.each(function() {
            const $item = $(this);
            const flowName = $item.find('h4').text().toLowerCase();
            const flowType = $item.find('p').text().toLowerCase();
            const flowStatus = $item.find('.status-badge').text().toLowerCase();
            
            const matchesSearch = searchTerm === '' || 
                flowName.includes(searchTerm) || 
                flowType.includes(searchTerm) || 
                flowStatus.includes(searchTerm);
            
            const matchesType = selectedType === '' || $item.attr('data-flow-type') === selectedType;
            const matchesStatus = selectedStatus === '' || $item.attr('data-flow-status') === selectedStatus;
            
            const shouldShow = matchesSearch && matchesType && matchesStatus;
            
            if (shouldShow) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        // Update results count
        const visibleCount = $items.filter(':visible').length;
        const totalCount = $items.length;
        updateFlowsCount(visibleCount, totalCount);
    }
    
    // Update flows count display
    function updateFlowsCount(visible, total) {
        let countText = `${visible} of ${total} flows`;
        if (visible === total) {
            countText = `${total} flows`;
        }
        
        // Update or create results count display
        let $countDisplay = $('.flows-count');
        if ($countDisplay.length === 0) {
            $countDisplay = $('<div class="flows-count" style="padding: 0.5rem 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 0.8125rem; color: #64748b;"></div>');
            $('.modern-list').append($countDisplay);
        }
        $countDisplay.text(countText);
    }

    // Filter change handlers
    $('#filter-flow-type, #filter-flow-status').on('change', function() {
        filterFlows();
    });
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
    
    // Test connection functionality
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_test_connection'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessModal('Connection Test', 'AJAX connection is working! Response: ' + response.data.message);
                } else {
                    showErrorModal('Connection Test Failed', response.data.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Test connection AJAX error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                showErrorModal('Connection Test Failed', 'Could not connect to server. Error: ' + error);
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Cleanup all flows functionality
    $('#cleanup-flows').on('click', function(e) {
        e.preventDefault();
        
        
        if (!confirm('⚠️ WARNING: This will delete ALL flows permanently!\n\nAre you absolutely sure you want to delete all flows? This action cannot be undone.')) {
            return;
        }
        
        // Double confirmation for safety
        if (!confirm('This is your final warning!\n\nClicking OK will permanently delete ALL flows in your system. Are you 100% sure?')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Deleting All Flows...').prop('disabled', true);
        
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_cleanup_all_flows',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessModal('All Flows Deleted!', 
                        'Successfully deleted ' + (response.data.deleted_count || 0) + ' flows. Refreshing page...');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    console.error('Cleanup failed:', response.data);
                    showErrorModal('Cleanup Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.error('Cleanup All Flows AJAX Error:', {xhr, status, error});
                console.error('Response text:', xhr.responseText);
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Cleanup duplicates functionality
    $('#cleanup-duplicates').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to cleanup duplicate flows? This will remove all duplicate flows and keep only the oldest one of each type.')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Cleaning...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_cleanup_duplicates',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
            if (response.success) {
                    showSuccessModal('Cleanup Complete!', 
                        'Removed ' + (response.data.removed_count || 0) + ' duplicate flows. Refreshing page...');
                    setTimeout(() => location.reload(), 2000);
            } else {
                    showErrorModal('Cleanup Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.error('Cleanup AJAX Error:', {xhr, status, error});
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
    
    // Individual flow test
    // Setting up individual flow test handlers
    $(document).on('click', '.modern-btn', function(e) {
        // Modern button clicked
        
        if ($(this).text().trim() !== 'Test') {
            return; // Not a test button, let default behavior continue
        }
        
        e.preventDefault();
        // Test button clicked
        
        const button = $(this);
        const originalText = button.html();
        const href = button.attr('href');
        // Button href
        
        const flowId = href ? href.match(/flow_id=(\d+)/) : null;
        // Extracted flow ID
        
        if (!flowId) {
            console.error('Could not determine flow ID from href:', href);
            showErrorModal('Error', 'Could not determine flow ID');
            return;
        }
        
        // Show progress modal
        // Showing progress modal
        showProgressModal('Running Test', 'Executing flow test...');
        
        button.html('<span class="dashicons dashicons-controls-play"></span> Testing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
            action: 'wp_tester_test_flow',
                flow_id: flowId[1],
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideProgressModal();
            if (response.success) {
                    showSuccessModal('Test Complete!', 
                        'Flow test executed successfully. ' + (response.data.message || ''));
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
        // Show progress modal
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
        // Remove any existing success modals first
        $('#wp-tester-success-modal').remove();
        
        const modalId = 'wp-tester-success-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="wp-tester-modal-overlay">
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
                        <button class="modern-btn modern-btn-primary wp-tester-modal-close" data-modal-id="${modalId}">OK</button>
                    </div>
                </div>
            </div>
        `);
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Add click handler for the close button
        modal.find('.wp-tester-modal-close').on('click', function() {
            const targetModalId = $(this).data('modal-id');
            $('#' + targetModalId).fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Add click handler for overlay to close modal
        modal.on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    function showErrorModal(title, message) {
        // Remove any existing error modals first
        $('[id^="wp-tester-error-modal"]').remove();
        
        const modalId = 'wp-tester-error-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="wp-tester-modal-overlay">
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
                        <button class="modern-btn modern-btn-secondary wp-tester-modal-close" data-modal-id="${modalId}">Close</button>
                    </div>
                </div>
            </div>
        `);
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Add click handler for the close button
        modal.find('.wp-tester-modal-close').on('click', function() {
            const targetModalId = $(this).data('modal-id');
            $('#' + targetModalId).fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Add click handler for overlay to close modal
        modal.on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    // Delete flow functionality
    $('.wp-tester-delete-flow').on('click', function(e) {
        e.preventDefault();
        
        const flowId = parseInt($(this).data('flow-id'));
        const button = $(this);
        const flowName = button.closest('.modern-list-item').find('h4').text();
        
        if (!flowId || flowId === 0) {
            showErrorModal('Invalid Flow ID', 'Invalid flow ID provided');
            return;
        }
        
        if (!confirm(`Are you sure you want to delete the flow "${flowName}"? This action cannot be undone.`)) {
            return;
        }
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Deleting...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
            action: 'wp_tester_delete_flow',
            flow_id: flowId,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
            if (response.success) {
                    showSuccessModal('Flow Deleted', 'Flow has been deleted successfully.');
                    // Remove the flow item from the list
                    button.closest('.modern-list-item').fadeOut(300, function() {
                        $(this).remove();
                    });
            } else {
                    showErrorModal('Delete Failed', response.data.message || 'Failed to delete flow');
                    button.html('Delete').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete AJAX Error:', {xhr, status, error});
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
                button.html('Delete').prop('disabled', false);
            }
        });
    });
    
    // Bulk selection functionality
    let selectedFlows = [];
    
    // Select All functionality
    $('#select-all-flows').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.flow-checkbox').prop('checked', isChecked);
        updateBulkActions();
    });
    
    // Individual checkbox change
    $(document).on('change', '.flow-checkbox', function() {
        updateBulkActions();
        
        // Update select all checkbox state
        const totalCheckboxes = $('.flow-checkbox').length;
        const checkedCheckboxes = $('.flow-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all-flows').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all-flows').prop('indeterminate', false).prop('checked', true);
            } else {
            $('#select-all-flows').prop('indeterminate', true);
        }
    });
    
    // Update bulk actions visibility and state
    function updateBulkActions() {
        const checkedBoxes = $('.flow-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            $('.bulk-actions-header').show();
            $('.selected-count').text(count + ' flow' + (count === 1 ? '' : 's') + ' selected');
            $('#apply-bulk-action').prop('disabled', false);
        } else {
            $('.bulk-actions-header').hide();
            $('#apply-bulk-action').prop('disabled', true);
        }
    }
    
    // Clear selection
    $('#clear-selection').on('click', function() {
        $('.flow-checkbox').prop('checked', false);
        $('#select-all-flows').prop('checked', false).prop('indeterminate', false);
        updateBulkActions();
    });
    
    // Apply bulk action
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedIds = $('.flow-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!action || selectedIds.length === 0) {
            showErrorModal('Invalid Action', 'Please select an action and at least one flow.');
            return;
        }
        
        if (action === 'delete') {
            if (!confirm(`Are you sure you want to delete ${selectedIds.length} flow(s)? This action cannot be undone.`)) {
            return;
            }
        }
        
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-update-alt"></span> Processing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
            action: 'wp_tester_bulk_action',
            bulk_action: action,
                flow_ids: selectedIds,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
            if (response.success) {
                    showSuccessModal('Bulk Action Complete!', 
                        response.data.message || 'Action completed successfully. Refreshing page...');
                    setTimeout(() => location.reload(), 2000);
            } else {
                    showErrorModal('Bulk Action Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.error('Bulk Action AJAX Error:', {xhr, status, error});
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>

<style>
/* Bulk Selection Styles */
.bulk-actions-header {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bulk-actions-info {
    font-weight: 500;
    color: #374151;
}

.bulk-actions-buttons {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.select-all-header {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
}

.select-all-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.select-all-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

.select-all-checkbox label {
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    margin: 0;
}

.item-checkbox {
    display: flex;
    align-items: center;
    margin-right: 1rem;
}

.item-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

.item-checkbox label {
    display: none; /* We don't need visible labels for individual checkboxes */
}

.modern-list-item {
    display: flex;
    align-items: center;
}

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