<?php
/**
 * WP Tester Dashboard Template - Modern UI
 * Professional CRM-style dashboard with clean design
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$stats = $dashboard_data['statistics'] ?? [];
$recent_results = $dashboard_data['recent_results'] ?? [];
$critical_issues = $dashboard_data['critical_issues'] ?? [];
$flow_health = $dashboard_data['flow_health'] ?? [];
$recommendations = $dashboard_data['recommendations'] ?? [];

// Calculate summary stats
$total_flows = $stats['total_flows'] ?? 0;
$total_tests = $stats['tests_executed_30d'] ?? 0;
$success_rate = $stats['success_rate'] ?? 0; // Changed from 100 to 0 - should show actual calculated rate
$avg_response_time = $stats['avg_response_time'] ?? 0;
$failed_tests = $stats['failed_tests'] ?? 0;
$last_crawl = $stats['last_crawl'] ?? 'Never';
$active_flows = $stats['active_flows'] ?? 0;
?>

<div class="wp-tester-modern">
    <!-- Modern Header -->
    <div class="wp-tester-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" 
                     alt="WP Tester" class="logo">
                <div class="title-info">
                    <h1>WP Tester Dashboard</h1>
                    <p class="subtitle">Automated testing overview and insights</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-admin-generic"></span>
                    Manage Flows
                </a>
                <a href="#" class="modern-btn modern-btn-primary modern-btn-small" id="run-all-tests">
                    <span class="dashicons dashicons-controls-play"></span>
                    Run Tests
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <!-- Key Metrics -->
        <div class="modern-grid grid-8">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Total Flows</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html($total_flows); ?></div>
                <div class="stat-change positive">
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                    Active monitoring
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Last Test</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                </div>
                <div class="stat-value"><?php 
                    $last_test = $stats['last_test'] ?? 'Never';
                    if ($last_test !== 'Never') {
                        echo esc_html(human_time_diff(strtotime($last_test), current_time('timestamp')) . ' ago');
                    } else {
                        echo esc_html('Never');
                    }
                ?></div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-<?php echo ($last_test !== 'Never') ? 'yes-alt' : 'dismiss'; ?>"></span>
                    <?php echo ($last_test !== 'Never') ? 'Recent activity' : 'No tests run'; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Tests Executed</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html($total_tests); ?></div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    Last 30 days
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Success Rate</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html(number_format($success_rate, 1)); ?>%</div>
                <div class="stat-change <?php echo $success_rate >= 90 ? 'positive' : ($success_rate >= 70 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $success_rate >= 90 ? 'arrow-up-alt' : ($success_rate >= 70 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    <?php echo $success_rate >= 90 ? 'Excellent' : ($success_rate >= 70 ? 'Good' : 'Needs attention'); ?>
        </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Avg Response</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-performance"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html(number_format($avg_response_time, 1)); ?>s</div>
                <div class="stat-change <?php echo $avg_response_time <= 2 ? 'positive' : ($avg_response_time <= 5 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $avg_response_time <= 2 ? 'arrow-up-alt' : ($avg_response_time <= 5 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    <?php echo $avg_response_time <= 2 ? 'Fast' : ($avg_response_time <= 5 ? 'Normal' : 'Slow'); ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Failed Tests</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html($failed_tests); ?></div>
                <div class="stat-change <?php echo $failed_tests == 0 ? 'positive' : ($failed_tests <= 5 ? 'neutral' : 'negative'); ?>">
                    <span class="dashicons dashicons-<?php echo $failed_tests == 0 ? 'arrow-up-alt' : ($failed_tests <= 5 ? 'minus' : 'arrow-down-alt'); ?>"></span>
                    <?php echo $failed_tests == 0 ? 'Perfect' : ($failed_tests <= 5 ? 'Acceptable' : 'Critical'); ?>
            </div>
        </div>
        
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Last Crawl</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                </div>
                <div class="stat-value"><?php 
                    if ($last_crawl && $last_crawl !== 'Never') {
                        echo esc_html(human_time_diff(strtotime($last_crawl), current_time('timestamp'))) . ' ago';
                    } else {
                        echo 'Never';
                    }
                ?></div>
                <div class="stat-change <?php echo $last_crawl && $last_crawl !== 'Never' ? 'positive' : 'neutral'; ?>">
                    <span class="dashicons dashicons-<?php echo $last_crawl && $last_crawl !== 'Never' ? 'arrow-up-alt' : 'minus'; ?>"></span>
                    <?php echo $last_crawl && $last_crawl !== 'Never' ? 'Recent' : 'Not started'; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Active Flows</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-controls-play"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo esc_html($active_flows); ?></div>
                <div class="stat-change <?php echo $active_flows > 0 ? 'positive' : 'neutral'; ?>">
                    <span class="dashicons dashicons-<?php echo $active_flows > 0 ? 'arrow-up-alt' : 'minus'; ?>"></span>
                    <?php echo $active_flows > 0 ? 'Monitoring' : 'Inactive'; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <h4 class="action-title">Manage Flows</h4>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <h4 class="action-title">View Results</h4>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-crawl'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <h4 class="action-title">Crawl Results</h4>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-settings'); ?>" class="quick-action-card">
                    <div class="action-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
            </div>
                    <h4 class="action-title">Settings</h4>
                </a>
        </div>
    </div>
    
        <div class="modern-grid grid-2">
            <!-- Recent Test Results -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Recent Test Results</h2>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                        View All
        </a>
    </div>
                <div class="modern-list">
                    <?php if (!empty($recent_results)) : ?>
                        <?php foreach (array_slice($recent_results, 0, 5) as $result) : ?>
                            <div class="modern-list-item">
                                <div class="item-info">
                                    <div class="item-icon">
                                        <span class="dashicons dashicons-<?php echo ($result['status'] ?? 'unknown') === 'passed' ? 'yes-alt' : (($result['status'] ?? 'unknown') === 'failed' ? 'dismiss' : 'clock'); ?>"></span>
                                    </div>
                                    <div class="item-details">
                                        <h4><?php echo esc_html($result['flow_name'] ?? 'Unknown Flow'); ?></h4>
                                        <p><?php echo esc_html(($result['steps_completed'] ?? 0) . ' of ' . ($result['total_steps'] ?? 0) . ' steps completed'); ?></p>
                                    </div>
                                </div>
                                <div class="item-meta">
                                    <div class="status-badge <?php echo esc_attr($result['status'] ?? 'pending'); ?>">
                                        <?php echo esc_html(ucfirst($result['status'] ?? 'Unknown')); ?>
                                    </div>
                                    <div style="margin-top: 0.25rem; font-size: 0.75rem;">
                                        <?php echo esc_html($result['execution_time'] ?? '0'); ?>s
            </div>
            </div>
        </div>
        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="dashicons dashicons-chart-line"></span>
                            </div>
                            <h3>No Recent Tests</h3>
                            <p>Run your first test to see results here</p>
                            <a href="#" class="modern-btn modern-btn-primary" id="run-first-test">
                                <span class="dashicons dashicons-controls-play"></span>
                                Run First Test
                            </a>
    </div>
    <?php endif; ?>
                </div>
            </div>

            <!-- Flow Health Status -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Flow Health Status</h2>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                        Manage
                    </a>
                </div>
                <div class="modern-list">
                    <?php if (!empty($flow_health)) : ?>
                        <?php foreach (array_slice($flow_health, 0, 5) as $flow) : ?>
                            <div class="modern-list-item">
                                <div class="item-info">
                                    <div class="item-icon">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    </div>
                                    <div class="item-details">
                                        <h4><?php echo esc_html($flow['flow_name'] ?? 'Unknown Flow'); ?></h4>
                                        <p><?php echo esc_html('Last tested: ' . ($flow['last_test_date'] ?? 'Never')); ?></p>
                                    </div>
                                </div>
                                <div class="item-meta">
                                    <div class="status-badge <?php echo esc_attr($flow['health_status'] ?? 'pending'); ?>">
                                        <?php echo esc_html(ucfirst($flow['health_status'] ?? 'Unknown')); ?>
                                    </div>
                                    <div style="margin-top: 0.25rem; font-size: 0.75rem;">
                                        <?php echo esc_html(($flow['success_rate'] ?? 0) . '% success'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="dashicons dashicons-admin-generic"></span>
            </div>
                            <h3>No Flows Found</h3>
                            <p>Create your first flow to start testing</p>
                            <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-primary">
                                <span class="dashicons dashicons-plus-alt"></span>
                                Create Flow
                </a>
            </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Critical Issues & Recommendations -->
        <?php if (!empty($critical_issues) || !empty($recommendations)) : ?>
        <div class="modern-grid grid-2">
            <!-- Critical Issues -->
            <?php if (!empty($critical_issues)) : ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Critical Issues</h2>
                    <span class="status-badge error"><?php echo count($critical_issues); ?> issues</span>
                </div>
                <div class="modern-list">
                    <?php foreach (array_slice($critical_issues, 0, 3) as $issue) : ?>
                        <div class="modern-list-item">
                            <div class="item-info">
                                <div class="item-icon" style="background: #fecaca; color: #991b1b;">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($issue['title'] ?? 'Critical Issue'); ?></h4>
                                    <p><?php echo esc_html($issue['description'] ?? 'Issue description not available'); ?></p>
                        </div>
                    </div>
                            <div class="item-meta">
                                <span class="status-badge error">Critical</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recommendations -->
            <?php if (!empty($recommendations)) : ?>
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Recommendations</h2>
                    <button class="status-badge info recommendation-tips-btn" 
                            data-recommendations='<?php echo esc_attr(json_encode($recommendations)); ?>'>
                        <?php echo count($recommendations); ?> tips
                    </button>
                </div>
                <div class="modern-list">
                    <?php foreach (array_slice($recommendations, 0, 3) as $index => $recommendation) : ?>
                        <div class="modern-list-item recommendation-item" 
                             data-recommendation='<?php echo esc_attr(json_encode($recommendation)); ?>'>
                            <div class="item-info">
                                <div class="item-icon" style="background: #dbeafe; color: #1e40af;">
                                    <span class="dashicons dashicons-lightbulb"></span>
                                </div>
                                <div class="item-details">
                                    <h4><?php echo esc_html($recommendation['title'] ?? 'Recommendation'); ?></h4>
                                    <p><?php echo esc_html($recommendation['description'] ?? 'Recommendation description not available'); ?></p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <button class="status-badge info recommendation-tip-btn" 
                                        data-recommendation='<?php echo esc_attr(json_encode($recommendation)); ?>'>
                                    Tip
                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Debug: Check if buttons exist
    console.log('Run All Tests button found:', $('#run-all-tests').length);
    console.log('Run First Test button found:', $('#run-first-test').length);
    
    // Run all tests functionality
    $('#run-all-tests, #run-first-test').on('click', function(e) {
        e.preventDefault();
        
        console.log('Run Test button clicked!');
        console.log('wpTesterData available:', typeof wpTesterData !== 'undefined');
        
        const button = $(this);
        const originalText = button.html();
        
        // Show progress modal
        showProgressModal('Running All Tests', 'Executing all configured flows...');
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
    
    // Recommendation tips functionality
    $('.recommendation-tips-btn').on('click', function(e) {
        e.preventDefault();
        const recommendations = JSON.parse($(this).data('recommendations'));
        showRecommendationsModal('All Recommendations', recommendations);
    });
    
    $('.recommendation-tip-btn').on('click', function(e) {
        e.preventDefault();
        const recommendation = JSON.parse($(this).data('recommendation'));
        showRecommendationsModal('Recommendation Details', [recommendation]);
    });
    
    function showRecommendationsModal(title, recommendations) {
        const modal = $(`
            <div id="wp-tester-recommendations-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon">
                            <span class="dashicons dashicons-lightbulb"></span>
                        </div>
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <div class="recommendations-list">
                            ${recommendations.map(rec => `
                                <div class="recommendation-detail">
                                    <div class="recommendation-header">
                                        <h4>${rec.title}</h4>
                                        <span class="priority-badge ${rec.priority}">${rec.priority.toUpperCase()}</span>
                                    </div>
                                    <p class="recommendation-description">${rec.description}</p>
                                    <div class="recommendation-action">
                                        <strong>Action:</strong> ${rec.action}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-primary" onclick="$('#wp-tester-recommendations-modal').fadeOut(300, function(){$(this).remove();})">Close</button>
                    </div>
                </div>
            </div>
        `);
        $('body').append(modal);
        modal.fadeIn(300);
    }
    
    // Progress and result modal functions
    function showProgressModal(title, message) {
        const modal = $(`
            <div id="wp-tester-progress-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon">
                            <span class="dashicons dashicons-update-alt spinning"></span>
                        </div>
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
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
        $('[id^="wp-tester-success-modal"]').remove();
        
        const modalId = 'wp-tester-success-modal-' + Date.now();
        const modal = $(`
            <div id="${modalId}" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-primary wp-tester-modal-close" data-modal-id="${modalId}">Close</button>
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
                <div class="wp-tester-modal">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <h3>${title}</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-primary wp-tester-modal-close" data-modal-id="${modalId}">Close</button>
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
});
</script>

<style>
/* Recommendation Modal Styles */
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
    min-width: 500px;
    max-width: 700px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.wp-tester-modal-header {
    padding: 2rem 2rem 1rem 2rem;
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
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
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem auto;
    font-size: 24px;
}

.wp-tester-modal-body {
    padding: 1.5rem 2rem;
    overflow-y: auto;
    flex: 1;
}

.wp-tester-modal-footer {
    padding: 1rem 2rem 2rem 2rem;
    text-align: center;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.recommendation-detail {
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
}

.recommendation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.recommendation-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.priority-badge.critical {
    background: #fecaca;
    color: #991b1b;
}

.priority-badge.high {
    background: #fed7aa;
    color: #9a3412;
}

.priority-badge.medium {
    background: #fef3c7;
    color: #92400e;
}

.priority-badge.low {
    background: #d1fae5;
    color: #065f46;
}

.recommendation-description {
    margin: 0 0 1rem 0;
    color: #6b7280;
    line-height: 1.6;
}

.recommendation-action {
    padding: 0.75rem;
    background: #e0f2fe;
    border-radius: 6px;
    color: #0c4a6e;
    font-size: 0.875rem;
}

.recommendation-tips-btn,
.recommendation-tip-btn {
    cursor: pointer;
    transition: all 0.2s ease;
}

.recommendation-tips-btn:hover,
.recommendation-tip-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Progress Bar Styles */
.progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 1rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 4px;
    animation: progress-animation 2s ease-in-out infinite;
}

@keyframes progress-animation {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

/* Spinning Animation */
.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>