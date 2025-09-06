<?php
/**
 * Admin Flow Test Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html($flow->flow_name); ?> - <?php _e('Test Results', 'wp-tester'); ?></h1>
    
    <div class="wp-tester-test-results">
        <?php if (isset($result) && !empty($result)): ?>
            <div class="test-summary">
                <h2><?php _e('Test Summary', 'wp-tester'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Status', 'wp-tester'); ?></th>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($result['status']); ?>">
                                <?php echo esc_html(ucfirst($result['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Steps Executed', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($result['steps_executed']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Steps Passed', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($result['steps_passed']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Steps Failed', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($result['steps_failed']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Execution Time', 'wp-tester'); ?></th>
                        <td><?php echo esc_html($result['execution_time']); ?>s</td>
                    </tr>
                </table>
            </div>
            
            <?php if (!empty($result['suggestions'])): ?>
                <div class="test-suggestions">
                    <h2><?php _e('Suggestions', 'wp-tester'); ?></h2>
                    <ul>
                        <?php foreach ($result['suggestions'] as $suggestion): ?>
                            <li>
                                <strong><?php echo esc_html($suggestion['title']); ?></strong>
                                <p><?php echo esc_html($suggestion['description']); ?></p>
                                <em><?php _e('Action:', 'wp-tester'); ?> <?php echo esc_html($suggestion['action']); ?></em>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <p><?php _e('Test execution failed or no results available.', 'wp-tester'); ?></p>
        <?php endif; ?>
        
        <div class="test-actions">
            <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>" class="button">
                <?php _e('Back to Flow', 'wp-tester'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="button button-primary">
                <?php _e('Run Test Again', 'wp-tester'); ?>
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
}
.status-passed { background-color: #28a745; }
.status-failed { background-color: #dc3545; }
.status-partial { background-color: #ffc107; color: #212529; }
</style>
