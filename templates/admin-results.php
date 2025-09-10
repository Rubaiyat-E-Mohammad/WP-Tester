<?php
/**
 * Admin Results Template - Modern UI
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
                    <h1>Test Results</h1>
                    <p class="subtitle">View and analyze test execution results</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Dashboard
                </a>
                <button class="modern-btn modern-btn-warning modern-btn-small" id="cleanup-test-results">
                    <span class="dashicons dashicons-trash"></span>
                    Cleanup Results
                </button>
                <button class="modern-btn modern-btn-primary modern-btn-small" id="refresh-results">
                    <span class="dashicons dashicons-update"></span>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <!-- Results Overview -->
        <div class="modern-grid grid-4">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Total Tests</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($results ?? []); ?></div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    All time
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Passed</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $passed_count = 0;
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            if (($result->status ?? '') === 'passed') $passed_count++;
                        }
                    }
                    echo $passed_count;
                    ?>
                </div>
                <div class="stat-change positive">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Success
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Failed</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $failed_count = 0;
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            $status = $result->status ?? '';
                            if ($status === 'failed') $failed_count++;
                        }
                    }
                    echo $failed_count;
                    ?>
                </div>
                <div class="stat-change negative">
                    <span class="dashicons dashicons-dismiss"></span>
                    Errors
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
                    $total_results = count($results ?? []);
                    if ($total_results > 0) {
                        $success_rate = round(($passed_count / $total_results) * 100, 1);
                    }
                    echo $success_rate;
                    ?>%
                </div>
                <div class="stat-change <?php echo $success_rate >= 90 ? 'positive' : ($success_rate >= 70 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $success_rate >= 90 ? 'arrow-up-alt' : ($success_rate >= 70 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    Overall
                </div>
            </div>

            <!-- Skipped Tests Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Skipped</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $skipped_count = 0;
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            if (($result->status ?? '') === 'skipped') $skipped_count++;
                        }
                    }
                    echo $skipped_count;
                    ?>
                </div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-controls-skipforward"></span>
                    Skipped
                </div>
            </div>

            <!-- Not Executed Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Not Executed</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $not_executed_count = 0;
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            if (($result->status ?? '') === 'not_executed' || ($result->status ?? '') === 'pending') $not_executed_count++;
                        }
                    }
                    echo $not_executed_count;
                    ?>
                </div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    Pending
                </div>
            </div>

            <!-- Total Executed Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Total Executed</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $executed_count = 0;
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            $status = $result->status ?? '';
                            if (in_array($status, ['passed', 'failed'])) $executed_count++;
                        }
                    }
                    echo $executed_count;
                    ?>
                </div>
                <div class="stat-change positive">
                    <span class="dashicons dashicons-yes-alt"></span>
                    Completed
                </div>
            </div>

            <!-- Total Time Card -->
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Total Time</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $total_time = 0;
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            $execution_time = floatval($result->execution_time ?? 0);
                            $total_time += $execution_time;
                        }
                    }
                    echo $total_time > 0 ? number_format($total_time, 1) . 's' : '0s';
                    ?>
                </div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    All Tests
                </div>
            </div>
        </div>

        <!-- Test Results List -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Recent Test Results</h2>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <div style="position: relative;">
                        <input type="text" id="search-test-results" placeholder="Search by flow name, status, or date..." 
                               style="padding: 0.5rem 0.5rem 0.5rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem; width: 300px; outline: none;"
                               onkeyup="filterTestResults()">
                        <span class="dashicons dashicons-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 16px;"></span>
                    </div>
                    <select id="filter-status" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem;">
                        <option value="">All Status</option>
                        <option value="passed">Passed</option>
                        <option value="failed">Failed</option>
                        <option value="skipped">Skipped</option>
                        <option value="not_executed">Not Executed</option>
                        <option value="running">Running</option>
                    </select>
                    <button class="modern-btn modern-btn-secondary modern-btn-small" id="export-results">
                        <span class="dashicons dashicons-download"></span>
                        Export
                    </button>
                </div>
            </div>

            <?php if (!empty($results)) : ?>
                <!-- Bulk Actions Header -->
                <div class="bulk-actions-header" style="display: none;">
                    <div class="bulk-actions-info">
                        <span class="selected-count">0 results selected</span>
                    </div>
                    <div class="bulk-actions-buttons">
                        <select id="bulk-action-selector" class="modern-select">
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete Selected</option>
                            <option value="export">Export Selected</option>
                        </select>
                        <button id="apply-bulk-action" class="modern-btn modern-btn-primary modern-btn-small" disabled>
                            Apply
                        </button>
                        <button id="clear-selection" class="modern-btn modern-btn-secondary modern-btn-small">
                            Clear Selection
                        </button>
                        <button id="debug-bulk-action" class="modern-btn modern-btn-secondary modern-btn-small">
                            Debug Test
                        </button>
                    </div>
                </div>
                
                <!-- Select All Header -->
                <div class="select-all-header">
                    <div class="select-all-checkbox">
                        <input type="checkbox" id="select-all-results" class="select-all-checkbox-input">
                        <label for="select-all-results">Select All Results</label>
                    </div>
                </div>
                
                <div class="modern-list" id="results-list">
                    <?php foreach ($results as $result) : ?>
                        <div class="modern-list-item" data-status="<?php 
                            $status = $result->status ?? '';
                            $display_status = $status;
                            echo esc_attr($display_status);
                        ?>">
                            <div class="item-checkbox">
                                <input type="checkbox" class="result-checkbox" value="<?php echo esc_attr($result->id ?? ''); ?>" id="result-<?php echo esc_attr($result->id ?? ''); ?>">
                                <label for="result-<?php echo esc_attr($result->id ?? ''); ?>"></label>
                            </div>
                            <div class="item-info">
                                <div class="item-icon">
                                    <?php 
                                    $status = $result->status ?? 'unknown';
                                    $display_status = $status;
                                    if ($display_status === 'passed'): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #28a745; font-size: 1.2rem;"></span>
                                    <?php elseif ($display_status === 'failed'): ?>
                                        <span class="dashicons dashicons-dismiss" style="color: #dc3545; font-size: 1.2rem;"></span>
                                    <?php elseif ($display_status === 'running'): ?>
                                        <span class="dashicons dashicons-update" style="color: #ffc107; font-size: 1.2rem; animation: spin 1s linear infinite;"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-clock" style="color: #6c757d; font-size: 1.2rem;"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($result->flow_name ?? 'Unknown Flow'); ?></h4>
                                    <p>
                                        <?php 
                                        $steps_executed = $result->steps_executed ?? 0;
                                        $steps_passed = $result->steps_passed ?? 0; 
                                        $steps_failed = $result->steps_failed ?? 0;
                                        $total_steps = $steps_executed ?: ($steps_passed + $steps_failed);
                                        $executed_time = $result->completed_at ?? $result->started_at ?? 'Unknown time';
                                        if ($executed_time !== 'Unknown time') {
                                            $executed_time = human_time_diff(strtotime($executed_time), current_time('timestamp')) . ' ago';
                                        }
                                        echo esc_html($steps_executed . ' of ' . $total_steps . ' steps'); 
                                        ?> • 
                                        Executed <?php echo esc_html($executed_time); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div class="status-badge <?php 
                                    $status = $result->status ?? 'pending';
                                    $display_status = $status;
                                    echo esc_attr($display_status);
                                ?>">
                                    <?php 
                                    $status = $result->status ?? 'Unknown';
                                    $display_text = ucfirst($status);
                                    echo esc_html($display_text);
                                    ?>
                                </div>
                                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
                                    <?php echo esc_html(number_format($result->execution_time ?? 0, 3) . 's execution time'); ?>
                                </div>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . ($result->id ?? '')); ?>" 
                                       class="modern-btn modern-btn-secondary modern-btn-small">
                                        View Details
                                    </a>
                                    <?php 
                                    $status = $result->status ?? '';
                                    if ($status === 'failed') : ?>
                                        <button class="modern-btn modern-btn-primary modern-btn-small retry-test" 
                                                data-flow-id="<?php echo esc_attr($result->flow_id ?? ''); ?>">
                                            Retry
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination placeholder -->
                <div style="margin-top: 1.5rem; text-align: center; padding: 1rem; border-top: 1px solid #f1f5f9;">
                    <button class="modern-btn modern-btn-secondary" id="load-more">
                        Load More Results
                    </button>
                </div>

            <?php else : ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <h3>No Test Results</h3>
                    <p>Run some tests to see results here. Get started by testing your flows.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                        <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary">
                            <span class="dashicons dashicons-admin-generic"></span>
                            View Flows
                        </a>
                        <button class="modern-btn modern-btn-primary" id="run-test">
                            <span class="dashicons dashicons-controls-play"></span>
                            Run Test
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#filter-status').on('change', function() {
        filterTestResults(); // Use the combined search and filter function
    });

    // Search functionality for test results
    function filterTestResults() {
        const searchTerm = $('#search-test-results').val().toLowerCase();
        const selectedStatus = $('#filter-status').val();
        const $items = $('.modern-list-item');
        
        $items.each(function() {
            const $item = $(this);
            const flowName = $item.find('h4').text().toLowerCase();
            const status = $item.find('.status-badge').text().toLowerCase();
            const description = $item.find('p').text().toLowerCase();
            
            const matchesSearch = searchTerm === '' || 
                flowName.includes(searchTerm) || 
                status.includes(searchTerm) || 
                description.includes(searchTerm);
            
            const matchesStatus = selectedStatus === '' || $item.attr('data-status') === selectedStatus;
            
            if (matchesSearch && matchesStatus) {
                $item.show();
            } else {
                $item.hide();
            }
        });
        
        // Update results count
        const visibleCount = $items.filter(':visible').length;
        const totalCount = $items.length;
        updateTestResultsCount(visibleCount, totalCount);
    }
    
    // Update test results count display
    function updateTestResultsCount(visible, total) {
        let countText = `${visible} of ${total} results`;
        if (visible === total) {
            countText = `${total} results`;
        }
        
        // Update or create results count display
        let $countDisplay = $('.test-results-count');
        if ($countDisplay.length === 0) {
            $countDisplay = $('<div class="test-results-count" style="padding: 0.5rem 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 0.8125rem; color: #64748b;"></div>');
            $('.modern-list').append($countDisplay);
        }
        $countDisplay.text(countText);
    }

    // Refresh results
    $('#refresh-results').on('click', function(e) {
        e.preventDefault();
        location.reload();
    });

    // Cleanup test results functionality
    $('#cleanup-test-results').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to remove ALL test results? This action cannot be undone.')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<span class="dashicons dashicons-update-alt"></span> Removing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_cleanup_all_test_results',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showSuccessModal('Cleanup Complete!', 
                        'Successfully removed ' + response.data.removed_count + ' test results.');
                    // Reload the page to show updated results
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showErrorModal('Cleanup Failed', response.data.message || 'Unknown error occurred.');
                }
            },
            error: function() {
                showErrorModal('Connection Error', 'Error connecting to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Retry test functionality
    $('.retry-test').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const flowId = button.data('flow-id');
        const originalText = button.html();
        
        if (!flowId || flowId === 0) {
            showErrorModal('Invalid Flow', 'Invalid flow ID for retry operation');
            return;
        }
        
        // Show progress modal
        showProgressModal('Retrying Test', 'Re-executing the failed test...');
        button.html('<span class="dashicons dashicons-update-alt"></span> Retrying...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_run_single_test',
                flow_id: flowId,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                hideProgressModal();
                if (response.success) {
                    showSuccessModal('Test Retry Complete!', 
                        'Test executed successfully. ' + (response.data.message || ''));
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showErrorModal('Retry Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                hideProgressModal();
                console.error('Retry Test AJAX Error:', {xhr, status, error});
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Export results
    $('#export-results').on('click', function(e) {
        e.preventDefault();
        showExportModal();
    });

    // Load more results
    $('#load-more').on('click', function(e) {
        e.preventDefault();
        loadMoreResults();
    });
    
    function loadMoreResults() {
        const $button = $('#load-more');
        const $resultsList = $('#results-list');
        const currentCount = $resultsList.find('.modern-list-item').length;
        
        // Show loading state
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> Loading...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_load_more_results',
                offset: currentCount,
                limit: 10
            },
            success: function(response) {
                if (response.success && response.data.results.length > 0) {
                    // Append new results
                    response.data.results.forEach(function(result) {
                        const resultHtml = createResultHtml(result);
                        $resultsList.append(resultHtml);
                    });
                    
                    // Update button state
                    if (response.data.has_more) {
                        $button.prop('disabled', false).html('Load More Results');
                    } else {
                        $button.hide();
                    }
                } else {
                    $button.hide();
                }
            },
            error: function() {
                $button.prop('disabled', false).html('Load More Results');
                showErrorModal('Error', 'Failed to load more results. Please try again.');
            }
        });
    }
    
    function createResultHtml(result) {
        const status = result.status || 'unknown';
        const statusIcon = status === 'passed' ? 'yes-alt' : (status === 'failed' ? 'dismiss' : 'clock');
        const statusColor = status === 'passed' ? '#28a745' : (status === 'failed' ? '#dc3545' : '#6c757d');
        
        return `
            <div class="modern-list-item" data-status="${status}">
                <div class="item-checkbox">
                    <input type="checkbox" class="result-checkbox" value="${result.id}" id="result-${result.id}">
                    <label for="result-${result.id}"></label>
                </div>
                <div class="item-info">
                    <div class="item-icon">
                        <span class="dashicons dashicons-${statusIcon}" style="color: ${statusColor}; font-size: 1.2rem;"></span>
                    </div>
                    <div class="item-details">
                        <h4>${result.flow_name || 'Unknown Flow'}</h4>
                        <p>
                            ${result.steps_executed || 0} of ${result.steps_total || 0} steps • 
                            Executed ${result.time_ago || 'unknown time ago'}
                        </p>
                    </div>
                </div>
                <div class="item-meta">
                    <div class="status-badge ${status}">
                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
                        ${(result.execution_time || 0).toFixed(3)}s execution time
                    </div>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                        <a href="${result.view_url}" class="modern-btn modern-btn-secondary modern-btn-small">
                            View Details
                        </a>
                        ${status === 'failed' ? `<button class="modern-btn modern-btn-primary modern-btn-small retry-test" data-flow-id="${result.flow_id}">Retry</button>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    // Run test
    $('#run-test').on('click', function(e) {
        e.preventDefault();
        
        
        const button = $(this);
        const originalText = button.html();
        
        // Show progress modal
        showProgressModal('Running Tests', 'Executing all configured flows...');
        button.html('<span class="dashicons dashicons-update-alt"></span> Running...').prop('disabled', true);
        
        // AJAX call to run tests
        const ajaxUrl = (typeof wpTesterData !== 'undefined' && wpTesterData.ajaxurl) ? wpTesterData.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
        const nonce = (typeof wpTesterData !== 'undefined' && wpTesterData.nonce) ? wpTesterData.nonce : '<?php echo wp_create_nonce('wp_tester_nonce'); ?>';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_tester_run_all_tests',
                nonce: nonce
            },
            success: function(response) {
                hideProgressModal();
                if (response.success) {
                    showSuccessModal('Tests Complete!', 
                        'All tests have been executed successfully. ' + (response.data.message || ''));
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showErrorModal('Test Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                hideProgressModal();
                console.error('Run All Tests AJAX Error:', {xhr, status, error});
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
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <p style="text-align: center; margin-top: 1rem; color: #6b7280;">${message}</p>
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
        $('[id^="wp-tester-success-modal"]').remove(); // Remove existing
        const modalId = 'wp-tester-success-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon" style="background: #d1fae5; color: #065f46;">
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
        
        // Handle close button
        modal.find('.wp-tester-modal-close').on('click', function() {
            modal.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Handle clicking outside modal
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }

    function showErrorModal(title, message) {
        $('[id^="wp-tester-error-modal"]').remove(); // Remove existing
        const modalId = 'wp-tester-error-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon" style="background: #fecaca; color: #991b1b;">
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
        
        // Handle close button
        modal.find('.wp-tester-modal-close').on('click', function() {
            modal.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Handle clicking outside modal
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }

    // Cleanup modal functions
    function showCleanupModal() {
        // Show cleanup modal
        
        // First get current stats
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_get_test_results_stats',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                // Get test results stats response
                if (response.success) {
                    const stats = response.data.stats;
                    // Stats received
                    showCleanupModalWithStats(stats);
                } else {
                    // Failed to get stats
                    showErrorModal('Error', 'Failed to load test results statistics');
                }
            },
            error: function(xhr, status, error) {
                console.error('WP Tester: get_test_results_stats AJAX error:', {xhr, status, error});
                showErrorModal('Error', 'Failed to connect to server');
            }
        });
    }

    function showCleanupModalWithStats(stats) {
        // Show cleanup modal with stats
        const modal = $(`
            <div id="cleanup-test-results-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal wp-tester-cleanup-modal">
                    <div class="wp-tester-modal-header">
                        <h3>Cleanup Test Results</h3>
                        <button class="wp-tester-modal-close">&times;</button>
                    </div>
                    <div class="wp-tester-modal-body">
                        <div class="cleanup-stats">
                            <h4>Current Statistics:</h4>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-label">Total Results:</span>
                                    <span class="stat-value">${stats.total || 0}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Passed:</span>
                                    <span class="stat-value">${stats.passed || 0}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Failed:</span>
                                    <span class="stat-value">${stats.failed || 0}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Older than 30 days:</span>
                                    <span class="stat-value">${stats.older_than_30_days || 0}</span>
                                </div>
                            </div>
                        </div>
                        
                        <form id="cleanup-form">
                            <div class="form-group">
                                <label for="older_than_days">Remove results older than (days):</label>
                                <input type="number" id="older_than_days" name="older_than_days" value="30" min="1" max="365">
                            </div>
                            
                            <div class="form-group">
                                <label>Keep results by status:</label>
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="keep_successful" checked> Keep successful tests
                                    </label>
                                    <label>
                                        <input type="checkbox" name="keep_failed"> Keep failed tests
                                    </label>
                                    <label>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_results_per_flow">Keep maximum results per flow:</label>
                                <input type="number" id="max_results_per_flow" name="max_results_per_flow" value="10" min="1" max="100">
                            </div>
                        </form>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button type="button" class="modern-btn modern-btn-secondary" id="cancel-cleanup">Cancel</button>
                        <button type="button" class="modern-btn modern-btn-danger" id="confirm-cleanup">Cleanup Results</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Handle modal close
        modal.find('#cancel-cleanup, .wp-tester-modal-close').on('click', function() {
            modal.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Handle cleanup confirmation
        modal.find('#confirm-cleanup').on('click', function() {
            const formData = {
                older_than_days: $('#older_than_days').val(),
                keep_successful: $('#cleanup-form input[name="keep_successful"]').is(':checked'),
                keep_failed: $('#cleanup-form input[name="keep_failed"]').is(':checked'),
                max_results_per_flow: $('#max_results_per_flow').val()
            };
            
            if (!confirm('Are you sure you want to cleanup test results? This action cannot be undone.')) {
                return;
            }
            
            const button = $(this);
            const originalText = button.html();
            
            button.html('<span class="dashicons dashicons-update-alt"></span> Cleaning...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_tester_cleanup_test_results',
                    nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>',
                    ...formData
                },
                success: function(response) {
                    modal.fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    if (response.success) {
                        showSuccessModal('Cleanup Complete!', 
                            'Removed ' + (response.data.removed_count || 0) + ' test results. Refreshing page...');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showErrorModal('Cleanup Failed', response.data.message || 'Unknown error occurred');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cleanup AJAX Error:', {xhr, status, error});
                    showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
                    button.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // Handle clicking outside modal to close
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    // Bulk selection functionality for results
    // Select All functionality
    $('#select-all-results').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.result-checkbox').prop('checked', isChecked);
        updateBulkActions();
    });
    
    // Individual checkbox change
    $(document).on('change', '.result-checkbox', function() {
        updateBulkActions();
        
        // Update select all checkbox state
        const totalCheckboxes = $('.result-checkbox').length;
        const checkedCheckboxes = $('.result-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all-results').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all-results').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all-results').prop('indeterminate', true);
        }
    });
    
    // Update bulk actions visibility and state
    function updateBulkActions() {
        const checkedBoxes = $('.result-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            $('.bulk-actions-header').show();
            $('.selected-count').text(count + ' result' + (count === 1 ? '' : 's') + ' selected');
            $('#apply-bulk-action').prop('disabled', false);
        } else {
            $('.bulk-actions-header').hide();
            $('#apply-bulk-action').prop('disabled', true);
        }
    }
    
    // Clear selection
    $('#clear-selection').on('click', function() {
        $('.result-checkbox').prop('checked', false);
        $('#select-all-results').prop('checked', false).prop('indeterminate', false);
        updateBulkActions();
    });
    
    // Debug bulk action
    $('#debug-bulk-action').on('click', function() {
        // Debug button clicked
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_debug_bulk_action',
                test_data: 'debug test',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                // Debug response received
                showSuccessModal('Debug Test Successful', 'Debug test completed successfully! Check console for details.');
            },
            error: function(xhr, status, error) {
                console.error('WP Tester: Debug error:', {xhr, status, error});
                showErrorModal('Debug Test Failed', 'Debug test failed! Check console for details.');
            }
        });
    });
    
    // Apply bulk action for results
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selectedIds = $('.result-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        // Debug: Log the action and selected IDs
        // Bulk action clicked
        
        if (!action || selectedIds.length === 0) {
            // No action or IDs selected
            showErrorModal('Invalid Action', 'Please select an action and at least one result.');
            return;
        }
        
        if (action === 'delete') {
            if (!confirm(`Are you sure you want to delete ${selectedIds.length} result(s)? This action cannot be undone.`)) {
                return;
            }
        }
        
        const button = $(this);
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-update-alt"></span> Processing...').prop('disabled', true);
        
        const ajaxData = {
            action: 'wp_tester_bulk_test_results_action',
            bulk_action: action,
            result_ids: selectedIds,
            nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
        };
        
        // Sending AJAX data
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                // AJAX success response
                if (response.success) {
                    // Bulk action successful
                    showSuccessModal('Bulk Action Complete!', 
                        response.data.message || 'Action completed successfully. Refreshing page...');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    // Bulk action failed
                    showErrorModal('Bulk Action Failed', response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.error('WP Tester: Bulk Action AJAX Error:', {xhr, status, error});
                console.error('WP Tester: Response Text:', xhr.responseText);
                showErrorModal('Connection Error', 'Could not connect to server. Please try again.');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Export modal function
    function showExportModal() {
        const modalId = 'export-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="modern-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: #00265e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span class="dashicons dashicons-download" style="color: white; font-size: 30px;"></span>
                        </div>
                        <h3 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">Export Test Results</h3>
                    </div>
                    <div style="color: #64748b; line-height: 1.6; margin-bottom: 2rem; text-align: center;">
                        Choose export format and options for your test results.
                    </div>
                    <div style="margin-bottom: 2rem;">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Export Format:</label>
                            <select id="export-format" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white;">
                                <option value="json">JSON (Complete Data)</option>
                                <option value="csv">CSV (Spreadsheet)</option>
                                <option value="pdf">PDF Report</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Date Range:</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <input type="date" id="export-date-from" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <input type="date" id="export-date-to" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button class="modal-close-btn" style="background: #6b7280; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Cancel
                        </button>
                        <button id="export-confirm" style="background: #00265e; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Export
                        </button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        const lastMonth = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        modal.find('#export-date-to').val(today);
        modal.find('#export-date-from').val(lastMonth);
        
        modal.find('.modal-close-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            modal.remove();
        });
        
        // Close modal when clicking on overlay
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
        
        modal.find('#export-confirm').on('click', function() {
            const format = modal.find('#export-format').val();
            const dateFrom = modal.find('#export-date-from').val();
            const dateTo = modal.find('#export-date-to').val();
            
            // Call export AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_tester_export_report',
                    format: format,
                    date_from: dateFrom,
                    date_to: dateTo,
                    nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
                },
                success: function(response) {
                    modal.remove();
                    if (response.success) {
                        // Create download link
                        const a = document.createElement('a');
                        a.href = response.data.download_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        
                        showSuccessModal('Export Complete!', 
                            'Test results exported successfully.<br>File: ' + response.data.filename);
                    } else {
                        showErrorModal('Export Failed', response.data.message || 'Unknown error occurred');
                    }
                },
                error: function() {
                    modal.remove();
                    showErrorModal('Export Error', 'Error connecting to server. Please try again.');
                }
            });
        });
    }
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

/* Cleanup Modal Styles */
.wp-tester-cleanup-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    min-width: 500px;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.cleanup-stats {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.cleanup-stats h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: white;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-value {
    font-weight: 600;
    color: #1f2937;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-group input[type="number"] {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.875rem;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: normal;
    margin-bottom: 0;
    cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin: 0;
}

.wp-tester-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    background: #f9fafb;
}

/* Progress Bar Styles */
.progress-container {
    padding: 1rem 0;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #00265e, #10b981);
    border-radius: 4px;
    animation: progress-animation 2s ease-in-out infinite;
}

@keyframes progress-animation {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

/* Modal Icon Styles */
.wp-tester-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.wp-tester-modal-icon .dashicons {
    font-size: 20px;
}

/* Modal Header with Icon */
.wp-tester-modal-header {
    display: flex;
    align-items: center;
    padding: 1.5rem 2rem 1rem 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.wp-tester-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}
</style>