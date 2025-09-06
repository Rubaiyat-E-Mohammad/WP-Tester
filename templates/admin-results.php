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
                            if (($result->status ?? '') === 'failed') $failed_count++;
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
        </div>

        <!-- Test Results List -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Recent Test Results</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <select id="filter-status" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem;">
                        <option value="">All Status</option>
                        <option value="passed">Passed</option>
                        <option value="failed">Failed</option>
                        <option value="partial">Partial</option>
                        <option value="running">Running</option>
                    </select>
                    <button class="modern-btn modern-btn-secondary modern-btn-small" id="export-results">
                        <span class="dashicons dashicons-download"></span>
                        Export
                    </button>
                </div>
            </div>

            <?php if (!empty($results)) : ?>
                <div class="modern-list" id="results-list">
                    <?php foreach ($results as $result) : ?>
                        <div class="modern-list-item" data-status="<?php echo esc_attr($result->status ?? ''); ?>">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-<?php 
                                        $status = $result->status ?? 'unknown';
                                        echo $status === 'passed' ? 'yes-alt' : 
                                            ($status === 'failed' ? 'dismiss' : 
                                            ($status === 'running' ? 'update' : 'clock'));
                                    ?>"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($result->flow_name ?? 'Unknown Flow'); ?></h4>
                                    <p>
                                        <?php echo esc_html(($result->steps_completed ?? 0) . ' of ' . ($result->total_steps ?? 0) . ' steps'); ?> • 
                                        Executed <?php echo esc_html($result->executed_at ?? 'Unknown time'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div class="status-badge <?php echo esc_attr($result->status ?? 'pending'); ?>">
                                    <?php echo esc_html(ucfirst($result->status ?? 'Unknown')); ?>
                                </div>
                                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
                                    <?php echo esc_html(($result->execution_time ?? '0') . 's execution time'); ?>
                                </div>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                    <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . ($result->id ?? '')); ?>" 
                                       class="modern-btn modern-btn-secondary modern-btn-small">
                                        View Details
                                    </a>
                                    <?php if (($result->status ?? '') === 'failed') : ?>
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

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#filter-status').on('change', function() {
        const selectedStatus = $(this).val();
        const $items = $('.modern-list-item');
        
        if (selectedStatus === '') {
            $items.show();
        } else {
            $items.hide();
            $items.filter('[data-status="' + selectedStatus + '"]').show();
        }
    });

    // Refresh results
    $('#refresh-results').on('click', function(e) {
        e.preventDefault();
        location.reload();
    });

    // Retry test functionality
    $('.retry-test').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const flowId = button.data('flow-id');
        const originalText = button.html();
        
        button.html('<div class="spinner"></div>').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_run_single_test',
                flow_id: flowId,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Test restarted successfully!');
                    location.reload();
                } else {
                    alert('Error restarting test: ' + (response.data || 'Unknown error'));
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

    // Export results
    $('#export-results').on('click', function(e) {
        e.preventDefault();
        alert('Export feature coming soon!');
    });

    // Load more results
    $('#load-more').on('click', function(e) {
        e.preventDefault();
        alert('Pagination feature coming soon!');
    });

    // Run test
    $('#run-test').on('click', function(e) {
        e.preventDefault();
        window.location.href = '<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>';
    });
});
</script>