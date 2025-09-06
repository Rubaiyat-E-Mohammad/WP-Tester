<?php
/**
 * Admin Flow Edit Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure flow object exists
if (!isset($flow) || !is_object($flow)) {
    wp_die(__('Flow not found.', 'wp-tester'));
}

// Get flow steps safely
$flow_steps = array();
if (isset($flow->steps) && !empty($flow->steps)) {
    $decoded_steps = json_decode($flow->steps, true);
    if (is_array($decoded_steps)) {
        $flow_steps = $decoded_steps;
    }
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
                    <h1><?php echo ($flow->id ?? 0) > 0 ? 'Edit Flow: ' . esc_html($flow->flow_name ?? 'Unnamed Flow') : 'Add New Flow'; ?></h1>
                    <p class="subtitle"><?php echo ($flow->id ?? 0) > 0 ? 'Modify flow configuration and steps' : 'Create a new testing flow'; ?></p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . ($flow->id ?? 0)); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Back to Flow
                </a>
                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . ($flow->id ?? 0)); ?>" class="modern-btn modern-btn-primary modern-btn-small">
                    <span class="dashicons dashicons-controls-play"></span>
                    Test Flow
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <form method="post" action="">
            <?php 
            if (($flow->id ?? 0) > 0) {
                wp_nonce_field('wp_tester_edit_flow', 'wp_tester_nonce');
            } else {
                wp_nonce_field('wp_tester_add_flow', 'wp_tester_nonce');
            }
            ?>
            
            <!-- Basic Settings -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Flow Settings</h2>
                    <div class="status-badge info">Configuration</div>
                </div>
                
                <div style="display: grid; gap: 1.5rem;">
                    
                    <!-- Flow Name -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Flow Name
                        </label>
                        <input type="text" name="flow_name" id="flow_name" 
                               value="<?php echo esc_attr($flow->flow_name ?? ''); ?>"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;"
                               required>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            A descriptive name for this user flow
                        </p>
                    </div>

                    <!-- Flow Type -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Flow Type
                        </label>
                        <select name="flow_type" id="flow_type"
                                style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                            <option value="login" <?php selected($flow->flow_type ?? '', 'login'); ?>>User Login</option>
                            <option value="registration" <?php selected($flow->flow_type ?? '', 'registration'); ?>>User Registration</option>
                            <option value="contact" <?php selected($flow->flow_type ?? '', 'contact'); ?>>Contact Form</option>
                            <option value="search" <?php selected($flow->flow_type ?? '', 'search'); ?>>Search</option>
                            <option value="woocommerce" <?php selected($flow->flow_type ?? '', 'woocommerce'); ?>>WooCommerce</option>
                            <option value="navigation" <?php selected($flow->flow_type ?? '', 'navigation'); ?>>Navigation</option>
                            <option value="modal" <?php selected($flow->flow_type ?? '', 'modal'); ?>>Modal Interaction</option>
                        </select>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            The type of user interaction this flow tests
                        </p>
                    </div>

                    <!-- Start URL -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            Start URL
                        </label>
                        <input type="url" name="start_url" id="start_url" 
                               value="<?php echo esc_attr($flow->start_url ?? ''); ?>"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;"
                               required>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                            The URL where this flow begins
                        </p>
                    </div>

                    <!-- Priority and Status -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                Priority
                            </label>
                            <select name="priority" id="priority"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 0.875rem;">
                                <option value="1" <?php selected($flow->priority ?? 5, 1); ?>>Low (1)</option>
                                <option value="5" <?php selected($flow->priority ?? 5, 5); ?>>Medium (5)</option>
                                <option value="9" <?php selected($flow->priority ?? 5, 9); ?>>High (9)</option>
                                <option value="10" <?php selected($flow->priority ?? 5, 10); ?>>Critical (10)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: flex; align-items: center; gap: 0.75rem; font-weight: 600; color: #374151; font-size: 0.875rem; margin-top: 1.75rem;">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php checked($flow->is_active ?? 1, 1); ?>
                                       style="width: 18px; height: 18px; border: 2px solid #e2e8f0; border-radius: 4px;">
                                Active Flow
                            </label>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.8125rem; color: #64748b;">
                                Enable automatic testing for this flow
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Flow Steps -->
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Flow Steps</h2>
                    <div class="status-badge info"><?php echo count($flow_steps); ?> steps</div>
                </div>
                
                <div id="flow-steps-container">
                    <?php if (!empty($flow_steps)): ?>
                        <div class="modern-list">
                            <?php foreach ($flow_steps as $index => $step): ?>
                                <div class="modern-list-item step-item" data-step="<?php echo esc_attr($index); ?>">
                                    <div class="item-info">
                                        <div class="item-icon">
                                            <span class="dashicons dashicons-menu"></span>
                                        </div>
                                        <div class="item-details">
                                            <h4>Step <?php echo esc_html($index + 1); ?>: <?php echo esc_html(ucwords(str_replace('_', ' ', $step['action'] ?? 'Unknown'))); ?></h4>
                                            <p>
                                                <?php if (!empty($step['target'])): ?>
                                                    Target: <?php echo esc_html($step['target']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($step['value'])): ?>
                                                    • Value: <?php echo esc_html($step['value']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="item-meta">
                                        <button type="button" class="modern-btn modern-btn-secondary modern-btn-small edit-step" data-step="<?php echo esc_attr($index); ?>">
                                            Edit
                                        </button>
                                        <button type="button" class="modern-btn modern-btn-secondary modern-btn-small remove-step" data-step="<?php echo esc_attr($index); ?>" style="margin-left: 0.5rem;">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="dashicons dashicons-list-view"></span>
                            </div>
                            <h3>No Steps Configured</h3>
                            <p>Add steps to define the user flow testing sequence.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 1.5rem; text-align: center;">
                    <button type="button" class="modern-btn modern-btn-primary" id="add-step">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Add Step
                    </button>
                </div>
            </div>

            <!-- Hidden input for flow steps -->
            <input type="hidden" name="flow_steps" id="flow_steps" value="<?php echo esc_attr(json_encode($flow_steps)); ?>">

            <!-- Save Button -->
            <div style="margin-top: 2rem; text-align: center;">
                <input type="submit" name="save_flow" class="modern-btn modern-btn-primary" 
                       value="Save Flow" style="padding: 0.75rem 2rem; font-size: 0.875rem; font-weight: 600;">
            </div>

        </form>

        <!-- Step Editor Modal (placeholder for future implementation) -->
        <div id="step-editor-modal" style="display: none;">
            <div class="modern-card">
                <div class="card-header">
                    <h2 class="card-title">Edit Step</h2>
                </div>
                <p>Step editor interface will be implemented here.</p>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add step functionality (placeholder)
    $('#add-step').on('click', function() {
        alert('Step editor interface coming soon!');
    });

    // Edit step functionality (placeholder)
    $('.edit-step').on('click', function() {
        const step = $(this).data('step');
        alert('Edit step ' + (step + 1) + ' interface coming soon!');
    });

    // Remove step functionality (placeholder)
    $('.remove-step').on('click', function() {
        if (confirm('Are you sure you want to remove this step?')) {
            const step = $(this).data('step');
            // This would remove the step from the JSON and update the display
            alert('Remove step functionality coming soon!');
        }
    });
});
</script>