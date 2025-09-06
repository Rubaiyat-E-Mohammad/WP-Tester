<?php
/**
 * Admin Result View Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Test Result Details', 'wp-tester'); ?></h1>
    
    <div class="wp-tester-result-details">
        <?php if (isset($report) && !empty($report)): ?>
            <div class="result-summary">
                <h2><?php _e('Test Summary', 'wp-tester'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Flow Name', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($report['flow']->flow_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'wp-tester'); ?></th>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($report['execution_summary']['overall_status']); ?>">
                                <?php echo esc_html($report['execution_summary']['status_label']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Success Rate', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($report['execution_summary']['success_rate']); ?>%</td>
                    </tr>
                    <tr>
                        <th><?php _e('Execution Time', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($report['execution_summary']['execution_time']); ?>s</td>
                    </tr>
                    <tr>
                        <th><?php _e('Test Date', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($report['execution_summary']['started_at']); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if (!empty($report['step_details'])): ?>
                <div class="step-details">
                    <h2><?php _e('Step Details', 'wp-tester'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Step', 'wp-tester'); ?></th>
                                <th><?php _e('Action', 'wp-tester'); ?></th>
                                <th><?php _e('Target', 'wp-tester'); ?></th>
                                <th><?php _e('Status', 'wp-tester'); ?></th>
                                <th><?php _e('Timestamp', 'wp-tester'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['step_details'] as $step): ?>
                                <tr>
                                    <td><?php echo esc_html($step['step_number']); ?></td>
                                    <td><?php echo esc_html($step['action']); ?></td>
                                    <td><?php echo esc_html($step['target']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($step['status']); ?>">
                                            <?php echo esc_html(ucfirst($step['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($step['timestamp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($report['suggestions'])): ?>
                <div class="suggestions">
                    <h2><?php _e('Suggestions', 'wp-tester'); ?></h2>
                    <?php foreach ($report['suggestions'] as $suggestion): ?>
                        <div class="suggestion-item priority-<?php echo esc_attr($suggestion['priority']); ?>">
                            <h3><?php echo esc_html($suggestion['title']); ?></h3>
                            <p><?php echo esc_html($suggestion['description']); ?></p>
                            <strong><?php _e('Recommended Action:', 'wp-tester'); ?></strong>
                            <p><?php echo esc_html($suggestion['action']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <p><?php _e('Test result not found or report could not be generated.', 'wp-tester'); ?></p>
        <?php endif; ?>
        
        <div class="result-actions">
            <a href="<?php echo admin_url('admin.php?page=wp-tester-results'); ?>" class="button">
                <?php _e('Back to Results', 'wp-tester'); ?>
            </a>
        </div>
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
.status-pending { background-color: #6c757d; }

.suggestion-item {
    margin-bottom: 20px;
    padding: 15px;
    border-left: 4px solid #ccc;
    background: #f9f9f9;
}
.suggestion-item.priority-critical { border-left-color: #dc3545; }
.suggestion-item.priority-high { border-left-color: #fd7e14; }
.suggestion-item.priority-medium { border-left-color: #ffc107; }
.suggestion-item.priority-low { border-left-color: #28a745; }
</style>
