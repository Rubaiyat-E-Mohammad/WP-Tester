<?php
/**
 * Admin Test Results Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wp-tester-modern">
    <!-- Modern Header with Logo -->
    <div class="wp-tester-modern-header glass-nav">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" alt="WP Tester Logo" class="logo" />
                <div class="title-section">
                    <h1><?php _e('Test Results', 'wp-tester'); ?></h1>
                    <div class="subtitle"><?php _e('View and analyze your test results', 'wp-tester'); ?></div>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="btn btn-outline btn-sm">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Dashboard', 'wp-tester'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="wp-tester-content">
    
    <div class="wp-tester-results">
        <?php if (!empty($results)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Flow Name', 'wp-tester'); ?></th>
                        <th><?php _e('Flow Type', 'wp-tester'); ?></th>
                        <th><?php _e('Status', 'wp-tester'); ?></th>
                        <th><?php _e('Steps', 'wp-tester'); ?></th>
                        <th><?php _e('Execution Time', 'wp-tester'); ?></th>
                        <th><?php _e('Date', 'wp-tester'); ?></th>
                        <th><?php _e('Actions', 'wp-tester'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result->flow_name); ?></td>
                            <td><?php echo esc_html($result->flow_type); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($result->status); ?>">
                                    <?php echo esc_html(ucfirst($result->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($result->steps_passed . '/' . $result->steps_executed); ?></td>
                            <td><?php echo esc_html($result->execution_time); ?>s</td>
                            <td><?php echo esc_html($result->started_at); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-results&action=view&result_id=' . $result->id); ?>" class="button button-small">
                                    <?php _e('View', 'wp-tester'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No test results found. Run some tests to see results here.', 'wp-tester'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    color: white;
    font-weight: bold;
    font-size: 11px;
}
.status-passed { background-color: #28a745; }
.status-failed { background-color: #dc3545; }
.status-partial { background-color: #ffc107; color: #212529; }
</style>
    </div> <!-- wp-tester-content -->
</div> <!-- wp-tester-modern -->
