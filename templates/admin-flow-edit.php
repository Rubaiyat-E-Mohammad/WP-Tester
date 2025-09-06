<?php
/**
 * Admin Flow Edit Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Edit Flow', 'wp-tester'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wp_tester_edit_flow', 'wp_tester_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="flow_name"><?php _e('Flow Name', 'wp-tester'); ?></label>
                </th>
                <td>
                    <input type="text" id="flow_name" name="flow_name" value="<?php echo esc_attr($flow->flow_name); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="flow_type"><?php _e('Flow Type', 'wp-tester'); ?></label>
                </th>
                <td>
                    <select id="flow_type" name="flow_type">
                        <option value="registration" <?php selected($flow->flow_type, 'registration'); ?>><?php _e('Registration', 'wp-tester'); ?></option>
                        <option value="login" <?php selected($flow->flow_type, 'login'); ?>><?php _e('Login', 'wp-tester'); ?></option>
                        <option value="contact" <?php selected($flow->flow_type, 'contact'); ?>><?php _e('Contact', 'wp-tester'); ?></option>
                        <option value="search" <?php selected($flow->flow_type, 'search'); ?>><?php _e('Search', 'wp-tester'); ?></option>
                        <option value="navigation" <?php selected($flow->flow_type, 'navigation'); ?>><?php _e('Navigation', 'wp-tester'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="start_url"><?php _e('Start URL', 'wp-tester'); ?></label>
                </th>
                <td>
                    <input type="url" id="start_url" name="start_url" value="<?php echo esc_attr($flow->start_url); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="priority"><?php _e('Priority', 'wp-tester'); ?></label>
                </th>
                <td>
                    <input type="number" id="priority" name="priority" value="<?php echo esc_attr($flow->priority); ?>" min="1" max="10" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="is_active"><?php _e('Status', 'wp-tester'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php checked($flow->is_active, 1); ?> />
                        <?php _e('Active', 'wp-tester'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Update Flow', 'wp-tester')); ?>
    </form>
</div>
