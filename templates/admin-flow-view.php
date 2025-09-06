<?php
/**
 * Admin Flow View Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html($flow->flow_name); ?></h1>
    
    <div class="wp-tester-flow-details">
        <div class="flow-info">
            <h2><?php _e('Flow Information', 'wp-tester'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Flow Type', 'wp-tester'); ?></th>
                    <td><?php echo esc_html($flow->flow_type); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Start URL', 'wp-tester'); ?></th>
                    <td><a href="<?php echo esc_url($flow->start_url); ?>" target="_blank"><?php echo esc_html($flow->start_url); ?></a></td>
                </tr>
                <tr>
                    <th><?php _e('Priority', 'wp-tester'); ?></th>
                    <td><?php echo esc_html($flow->priority); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'wp-tester'); ?></th>
                    <td><?php echo $flow->is_active ? __('Active', 'wp-tester') : __('Inactive', 'wp-tester'); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="flow-steps">
            <h2><?php _e('Flow Steps', 'wp-tester'); ?></h2>
            <?php 
            $steps = json_decode($flow->steps, true);
            if (!empty($steps)): 
            ?>
                <ol>
                    <?php foreach ($steps as $step): ?>
                        <li>
                            <strong><?php echo esc_html($step['action']); ?></strong>
                            <?php if (!empty($step['target'])): ?>
                                - <?php echo esc_html($step['target']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p><?php _e('No steps defined for this flow.', 'wp-tester'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($recent_results)): ?>
            <div class="recent-results">
                <h2><?php _e('Recent Test Results', 'wp-tester'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'wp-tester'); ?></th>
                            <th><?php _e('Status', 'wp-tester'); ?></th>
                            <th><?php _e('Steps', 'wp-tester'); ?></th>
                            <th><?php _e('Execution Time', 'wp-tester'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_results as $result): ?>
                            <tr>
                                <td><?php echo esc_html($result->started_at); ?></td>
                                <td><?php echo esc_html($result->status); ?></td>
                                <td><?php echo esc_html($result->steps_passed . '/' . $result->steps_executed); ?></td>
                                <td><?php echo esc_html($result->execution_time . 's'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
