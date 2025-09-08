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
                <button type="button" class="modern-btn modern-btn-primary modern-btn-small" id="test-flow-btn" 
                        data-flow-id="<?php echo ($flow->id ?? 0); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                    Test Flow
                </button>
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
                    <button type="button" class="modern-btn modern-btn-primary" id="add-step" onclick="console.log('Button clicked via onclick'); showStepEditor(); return false;">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Add Step
                    </button>
                </div>
            </div>

            <!-- Hidden input for flow steps -->
            <input type="hidden" name="steps" id="flow_steps" value="<?php echo esc_attr(json_encode($flow_steps)); ?>">

            <!-- Save Button -->
            <div style="margin-top: 2rem; text-align: center;">
                <input type="submit" name="save_flow" class="modern-btn modern-btn-primary" 
                       value="Save Flow" style="padding: 0.75rem 2rem; font-size: 0.875rem; font-weight: 600;">
            </div>

        </form>


    </div>
</div>

<style>
/* Modal Styles */
.wp-tester-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wp-tester-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.wp-tester-modal-header {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.wp-tester-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.wp-tester-modal-header h3 {
    margin: 0;
    flex: 1;
    color: #1f2937;
    font-size: 1.25rem;
    font-weight: 600;
}

.wp-tester-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.wp-tester-modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.wp-tester-modal-body {
    padding: 1.5rem;
}

.wp-tester-modal-footer {
    padding: 1rem 1.5rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #00265e;
    box-shadow: 0 0 0 3px rgba(0, 38, 94, 0.1);
}

.form-group textarea {
    min-height: 80px;
    resize: vertical;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test flow functionality
    $('#test-flow-btn').on('click', function() {
        const flowId = parseInt($(this).data('flow-id'));
        
        if (!flowId || flowId === 0) {
            // Show popup for unsaved flows
            showTestFlowDialog();
        } else {
            // Redirect to test page for saved flows
            window.location.href = '<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id='); ?>' + flowId;
        }
    });
    
    // Add step functionality
    $(document).on('click', '#add-step', function(e) {
        e.preventDefault();
        console.log('Add Step button clicked');
        showStepEditor();
    });
    
    // Also try direct binding as fallback
    $('#add-step').on('click', function(e) {
        e.preventDefault();
        console.log('Add Step button clicked (direct binding)');
        showStepEditor();
    });

    // Edit step functionality
    $('.edit-step').on('click', function() {
        const stepIndex = parseInt($(this).data('step'));
        showStepEditor(stepIndex);
    });

    // Remove step functionality
    $('.remove-step').on('click', function() {
        const stepIndex = parseInt($(this).data('step'));
        removeStep(stepIndex);
    });
    
    // Step editor functions
    window.showStepEditor = function(stepIndex = null) {
        console.log('showStepEditor called with stepIndex:', stepIndex);
        const isEdit = stepIndex !== null;
        const existingStep = isEdit ? getStepData(stepIndex) : {};
        
        const modal = $(`
            <div id="wp-tester-step-editor-modal" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal wp-tester-step-editor">
                    <div class="wp-tester-modal-header">
                        <h3>${isEdit ? 'Edit Step' : 'Add New Step'}</h3>
                        <button class="wp-tester-modal-close">&times;</button>
                    </div>
                    <div class="wp-tester-modal-body">
                        <form id="step-editor-form">
                            <div class="form-group">
                                <label for="step-action">Action Type:</label>
                                <select id="step-action" name="action" required>
                                    <option value="">Select Action</option>
                                    <option value="navigate" ${existingStep.action === 'navigate' ? 'selected' : ''}>Navigate to URL</option>
                                    <option value="click" ${existingStep.action === 'click' ? 'selected' : ''}>Click Element</option>
                                    <option value="fill_input" ${existingStep.action === 'fill_input' ? 'selected' : ''}>Fill Input Field</option>
                                    <option value="fill_form" ${existingStep.action === 'fill_form' ? 'selected' : ''}>Fill Form</option>
                                    <option value="submit" ${existingStep.action === 'submit' ? 'selected' : ''}>Submit Form</option>
                                    <option value="verify" ${existingStep.action === 'verify' ? 'selected' : ''}>Verify Element</option>
                                    <option value="wait" ${existingStep.action === 'wait' ? 'selected' : ''}>Wait for Element</option>
                                    <option value="scroll" ${existingStep.action === 'scroll' ? 'selected' : ''}>Scroll to Element</option>
                                    <option value="hover" ${existingStep.action === 'hover' ? 'selected' : ''}>Hover Element</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="step-target">Target (CSS Selector or URL):</label>
                                <input type="text" id="step-target" name="target" value="${existingStep.target || ''}" 
                                       placeholder="e.g., #submit-button, .login-form, https://example.com" required>
                            </div>
                            
                            <div class="form-group" id="step-data-group" style="display: none;">
                                <label for="step-data">Data/Value:</label>
                                <textarea id="step-data" name="data" placeholder="Enter data (JSON format for forms, text for inputs)">${existingStep.data || ''}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="step-description">Description (Optional):</label>
                                <input type="text" id="step-description" name="description" value="${existingStep.description || ''}" 
                                       placeholder="Brief description of this step">
                            </div>
                            
                            <div class="form-group">
                                <label for="step-timeout">Timeout (seconds):</label>
                                <input type="number" id="step-timeout" name="timeout" value="${existingStep.timeout || 30}" 
                                       min="1" max="300">
                            </div>
                        </form>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button type="button" class="modern-btn modern-btn-secondary" id="cancel-step">Cancel</button>
                        <button type="button" class="modern-btn modern-btn-primary" id="save-step">${isEdit ? 'Update Step' : 'Add Step'}</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(300);
        console.log('Modal appended and faded in');
        
        // Show/hide data field based on action type
        $('#step-action').on('change', function() {
            const action = $(this).val();
            const dataGroup = $('#step-data-group');
            
            if (['fill_input', 'fill_form', 'verify'].includes(action)) {
                dataGroup.show();
            } else {
                dataGroup.hide();
            }
        }).trigger('change');
        
        // Handle modal close
        modal.find('#cancel-step').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Cancel button clicked');
            closeStepEditor();
        });
        
        modal.find('.wp-tester-modal-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeStepEditor();
        });
        
        // Handle save step
        modal.find('#save-step').on('click', function(e) {
            e.preventDefault();
            saveStep(stepIndex);
        });
        
        // Handle clicking outside modal to close
        modal.on('click', function(e) {
            if (e.target === this) {
                closeStepEditor();
            }
        });
        
        // Handle ESC key to close modal
        $(document).on('keydown.modal', function(e) {
            if (e.keyCode === 27) { // ESC key
                closeStepEditor();
                $(document).off('keydown.modal');
            }
        });
    }
    
    window.closeStepEditor = function() {
        console.log('closeStepEditor called');
        // Clean up event handlers
        $(document).off('keydown.modal');
        
        $('#wp-tester-step-editor-modal').fadeOut(300, function() {
            console.log('Modal removed');
            $(this).remove();
        });
    }
    
    window.saveStep = function(stepIndex = null) {
        console.log('saveStep called with stepIndex:', stepIndex);
        
        const formData = {
            action: $('#step-action').val(),
            target: $('#step-target').val(),
            data: $('#step-data').val(),
            description: $('#step-description').val(),
            timeout: parseInt($('#step-timeout').val()) || 30
        };
        
        console.log('Form data:', formData);
        
        // Validate required fields
        if (!formData.action || formData.action.trim() === '') {
            showErrorModal('Validation Error', 'Please select an action type.');
            return;
        }
        
        if (!formData.target || formData.target.trim() === '') {
            showErrorModal('Validation Error', 'Please enter a target (CSS selector or URL).');
            return;
        }
        
        // Get current steps
        let steps = [];
        try {
            steps = JSON.parse($('#flow_steps').val() || '[]');
        } catch (e) {
            steps = [];
        }
        
        if (stepIndex !== null) {
            // Edit existing step
            steps[stepIndex] = formData;
        } else {
            // Add new step
            steps.push(formData);
        }
        
        // Update hidden input
        $('#flow_steps').val(JSON.stringify(steps));
        
        // Update UI
        updateStepsDisplay(steps);
        
        // Close modal
        closeStepEditor();
        
        // Show success message
        showSuccessMessage(stepIndex !== null ? 'Step updated successfully!' : 'Step added successfully!');
    }
    
    function getStepData(stepIndex) {
        try {
            const steps = JSON.parse($('#flow_steps').val() || '[]');
            return steps[stepIndex] || {};
        } catch (e) {
            return {};
        }
    }
    
    function updateStepsDisplay(steps) {
        const container = $('.modern-list');
        container.empty();
        
        if (steps.length === 0) {
            container.html('<p style="text-align: center; color: #64748b; margin: 2rem 0;">No steps defined yet. Click "Add Step" to get started.</p>');
            return;
        }
        
        steps.forEach((step, index) => {
            const stepHtml = $(`
                <div class="modern-list-item" data-step="${index}">
                    <div class="item-info">
                        <div class="item-icon">
                            <span class="dashicons dashicons-${getActionIcon(step.action)}"></span>
                        </div>
                        <div class="item-details">
                            <h4>Step ${index + 1}: ${step.action}</h4>
                            <p>${step.description || step.target}</p>
                        </div>
                    </div>
                    <div class="item-meta">
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="modern-btn modern-btn-secondary modern-btn-small edit-step" data-step="${index}">
                                <span class="dashicons dashicons-edit"></span>
                                Edit
                            </button>
                            <button class="modern-btn modern-btn-danger modern-btn-small remove-step" data-step="${index}">
                                <span class="dashicons dashicons-trash"></span>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            `);
            container.append(stepHtml);
        });
        
        // Re-bind event handlers
        $('.edit-step').on('click', function() {
            const stepIndex = parseInt($(this).data('step'));
            showStepEditor(stepIndex);
        });
        
        $('.remove-step').on('click', function() {
            const stepIndex = parseInt($(this).data('step'));
            removeStep(stepIndex);
        });
    }
    
    function removeStep(stepIndex) {
        if (confirm('Are you sure you want to remove this step?')) {
            let steps = [];
            try {
                steps = JSON.parse($('#flow_steps').val() || '[]');
            } catch (e) {
                steps = [];
            }
            
            steps.splice(stepIndex, 1);
            $('#flow_steps').val(JSON.stringify(steps));
            updateStepsDisplay(steps);
            showSuccessMessage('Step removed successfully!');
        }
    }
    
    function getActionIcon(action) {
        const icons = {
            'navigate': 'external',
            'click': 'button',
            'fill_input': 'edit',
            'fill_form': 'forms',
            'submit': 'upload',
            'verify': 'yes-alt',
            'wait': 'clock',
            'scroll': 'arrow-down-alt',
            'hover': 'move'
        };
        return icons[action] || 'admin-generic';
    }
    
    function showSuccessMessage(message) {
        const notice = $(`
            <div class="notice notice-success is-dismissible" style="margin: 1rem 0;">
                <p>${message}</p>
                <button type="button" class="notice-dismiss" onclick="$(this).parent().fadeOut()">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        $('.wp-tester-content').prepend(notice);
        setTimeout(() => notice.fadeOut(), 3000);
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
                        <button class="wp-tester-modal-close">&times;</button>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-primary" id="success-modal-ok">OK</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Add proper event handlers
        modal.find('#success-modal-ok, .wp-tester-modal-close').on('click', function(e) {
            e.preventDefault();
            modal.fadeOut(300, function() {
                $(this).remove();
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
        
        // Handle ESC key to close modal
        $(document).on('keydown.successModal', function(e) {
            if (e.keyCode === 27) { // ESC key
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
                $(document).off('keydown.successModal');
            }
        });
        
        // Auto close after 3 seconds
        setTimeout(() => {
            modal.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
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
                        <button class="wp-tester-modal-close">&times;</button>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button class="modern-btn modern-btn-primary" id="error-modal-ok">OK</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Add proper event handlers
        modal.find('#error-modal-ok, .wp-tester-modal-close').on('click', function(e) {
            e.preventDefault();
            modal.fadeOut(300, function() {
                $(this).remove();
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
        
        // Handle ESC key to close modal
        $(document).on('keydown.errorModal', function(e) {
            if (e.keyCode === 27) { // ESC key
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
                $(document).off('keydown.errorModal');
            }
        });
    }
    
    function showTestFlowDialog() {
        const modal = $(`
            <div id="wp-tester-test-flow-dialog" class="wp-tester-modal-overlay">
                <div class="wp-tester-modal wp-tester-test-flow-dialog">
                    <div class="wp-tester-modal-header">
                        <div class="wp-tester-modal-icon">
                            <span class="dashicons dashicons-info"></span>
                        </div>
                        <h3>Save Flow First</h3>
                    </div>
                    <div class="wp-tester-modal-body">
                        <p>You need to save this flow before you can test it.</p>
                        <p>Please fill in the required fields and click "Save Flow" first, then you'll be able to test it.</p>
                    </div>
                    <div class="wp-tester-modal-footer">
                        <button type="button" class="modern-btn modern-btn-secondary" onclick="closeTestFlowDialog()">Cancel</button>
                        <button type="button" class="modern-btn modern-btn-primary" onclick="focusSaveButton()">Save Flow First</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.fadeIn(300);
    }
    
    function closeTestFlowDialog() {
        $('#wp-tester-test-flow-dialog').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    function focusSaveButton() {
        closeTestFlowDialog();
        // Scroll to and highlight the save button
        $('html, body').animate({
            scrollTop: $('input[name="save_flow"]').offset().top - 100
        }, 500);
        $('input[name="save_flow"]').focus().addClass('highlight-save-btn');
        setTimeout(() => {
            $('input[name="save_flow"]').removeClass('highlight-save-btn');
        }, 2000);
    }
    
    // Debug: Check if add-step button exists
    console.log('Add step button exists:', $('#add-step').length > 0);
    console.log('Add step button element:', $('#add-step')[0]);
    
    // Initialize steps display
    updateStepsDisplay(JSON.parse($('#flow_steps').val() || '[]'));
});
</script>

<style>
/* Step Editor Modal Styles */
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

.wp-tester-step-editor {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    min-width: 500px;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.wp-tester-modal-header {
    padding: 1.5rem 2rem 1rem 2rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wp-tester-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.wp-tester-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.wp-tester-modal-close:hover {
    color: #374151;
}

.wp-tester-modal-body {
    padding: 1.5rem 2rem;
}

.wp-tester-modal-footer {
    padding: 1rem 2rem 1.5rem 2rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #00265e;
    box-shadow: 0 0 0 3px rgba(31, 192, 154, 0.1);
}

.form-group textarea {
    min-height: 80px;
    resize: vertical;
}

.modern-btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
}

.modern-btn-danger:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
}

/* Test Flow Dialog Styles */
.wp-tester-test-flow-dialog {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    min-width: 450px;
    max-width: 500px;
}

.wp-tester-test-flow-dialog .wp-tester-modal-header {
    padding: 2rem 2rem 1rem 2rem;
    text-align: center;
    border-bottom: none;
}

.wp-tester-test-flow-dialog .wp-tester-modal-icon {
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

.wp-tester-test-flow-dialog .wp-tester-modal-body {
    padding: 0 2rem 1rem 2rem;
    text-align: center;
}

.wp-tester-test-flow-dialog .wp-tester-modal-body p {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.wp-tester-test-flow-dialog .wp-tester-modal-footer {
    padding: 1rem 2rem 2rem 2rem;
    border-top: none;
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Save button highlight effect */
.highlight-save-btn {
    animation: saveButtonPulse 2s ease-in-out;
    box-shadow: 0 0 0 4px rgba(31, 192, 154, 0.3) !important;
}

@keyframes saveButtonPulse {
    0%, 100% { 
        transform: scale(1);
        box-shadow: 0 0 0 4px rgba(31, 192, 154, 0.3);
    }
    50% { 
        transform: scale(1.05);
        box-shadow: 0 0 0 8px rgba(31, 192, 154, 0.2);
    }
}
</style>