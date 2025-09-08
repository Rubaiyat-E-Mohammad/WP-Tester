<?php
/**
 * Simple Flow Edit Template - Clean Version
 */

if (!defined('ABSPATH')) {
    exit;
}

$flow_id = isset($_GET['flow_id']) ? intval($_GET['flow_id']) : 0;
$flow = null;

if ($flow_id > 0) {
    global $wpdb;
    $flows_table = $wpdb->prefix . 'wp_tester_flows';
    $flow = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$flows_table} WHERE id = %d", $flow_id));
    
    if (!$flow) {
        wp_die('Flow not found');
    }
} else {
    // For new flows, create an empty flow object
    $flow = (object) array(
        'id' => 0,
        'flow_name' => '',
        'flow_type' => 'login',
        'start_url' => '',
        'steps' => '[]',
        'expected_outcome' => '',
        'priority' => 5,
        'is_active' => 1,
        'ai_generated' => 0,
        'ai_provider' => '',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
}

$steps = json_decode($flow->steps ?? '[]', true) ?: [];

// Only log if there are issues loading steps
if (empty($steps) && !empty($flow->steps)) {
    error_log('WP Tester: Warning - Flow has steps data but decoded to empty array');
}
?>

<div class="wrap">
    <h1><?php echo ($flow_id > 0) ? 'Edit Flow: ' . esc_html($flow->flow_name) : 'Add New Flow'; ?></h1>
    
    <div class="modern-card">
        <div class="modern-card-header">
            <h2>Flow Steps</h2>
            <button type="button" class="modern-btn modern-btn-primary" id="add-step-btn">Add Step</button>
        </div>
        
        <div class="modern-card-body">
            <div id="steps-list">
                <?php foreach ($steps as $index => $step): ?>
                <div class="step-item" data-index="<?php echo $index; ?>">
                    <div class="step-content">
                        <strong><?php echo esc_html($step['action']); ?></strong>
                        <span><?php echo esc_html($step['target']); ?></span>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="edit-step-btn" data-index="<?php echo $index; ?>">Edit</button>
                        <button type="button" class="delete-step-btn" data-index="<?php echo $index; ?>">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <form method="post" action="">
        <?php 
        if ($flow_id > 0) {
            wp_nonce_field('wp_tester_edit_flow', 'wp_tester_nonce');
            echo '<input type="hidden" name="flow_id" value="' . $flow_id . '">';
        } else {
            wp_nonce_field('wp_tester_add_flow', 'wp_tester_nonce');
        }
        ?>
        <input type="hidden" name="steps" id="flow_steps" value="<?php echo htmlspecialchars(json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
        
        <div class="modern-card">
            <div class="modern-card-header">
                <h2>Flow Details</h2>
            </div>
            <div class="modern-card-body">
                <div class="form-group">
                    <label for="flow_name">Flow Name:</label>
                    <input type="text" id="flow_name" name="flow_name" value="<?php echo esc_attr($flow->flow_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="flow_type">Flow Type:</label>
                    <select id="flow_type" name="flow_type">
                        <option value="login" <?php selected($flow->flow_type ?? '', 'login'); ?>>User Login</option>
                        <option value="registration" <?php selected($flow->flow_type ?? '', 'registration'); ?>>User Registration</option>
                        <option value="contact" <?php selected($flow->flow_type ?? '', 'contact'); ?>>Contact Form</option>
                        <option value="search" <?php selected($flow->flow_type ?? '', 'search'); ?>>Search</option>
                        <option value="woocommerce" <?php selected($flow->flow_type ?? '', 'woocommerce'); ?>>WooCommerce</option>
                        <option value="navigation" <?php selected($flow->flow_type ?? '', 'navigation'); ?>>Navigation</option>
                        <option value="modal" <?php selected($flow->flow_type ?? '', 'modal'); ?>>Modal Interaction</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="start_url">Start URL:</label>
                    <input type="url" id="start_url" name="start_url" value="<?php echo esc_attr($flow->start_url ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority">
                        <option value="1" <?php selected($flow->priority ?? 5, 1); ?>>Low (1)</option>
                        <option value="5" <?php selected($flow->priority ?? 5, 5); ?>>Medium (5)</option>
                        <option value="9" <?php selected($flow->priority ?? 5, 9); ?>>High (9)</option>
                        <option value="10" <?php selected($flow->priority ?? 5, 10); ?>>Critical (10)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" class="small-checkbox" <?php checked($flow->is_active ?? 1, 1); ?>>
                        Active Flow
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="expected_outcome">Expected Outcome:</label>
                    <textarea id="expected_outcome" name="expected_outcome"><?php echo esc_html($flow->expected_outcome ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="modern-btn modern-btn-primary"><?php echo ($flow_id > 0) ? 'Update Flow' : 'Create Flow'; ?></button>
                    <a href="<?php echo admin_url('admin.php?page=wp-tester-flows'); ?>" class="modern-btn modern-btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Step Editor Modal -->
<div id="step-editor-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Add New Step</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="step-form">
                <div class="form-group">
                    <label for="step-action">Action Type:</label>
                    <select id="step-action" name="action" required>
                        <option value="">Select Action</option>
                        <option value="navigate">Navigate to URL</option>
                        <option value="click">Click Element</option>
                        <option value="fill_input">Fill Input Field</option>
                        <option value="fill_form">Fill Form</option>
                        <option value="submit">Submit Form</option>
                        <option value="verify">Verify Element</option>
                        <option value="wait">Wait for Element</option>
                        <option value="scroll">Scroll to Element</option>
                        <option value="hover">Hover Element</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="step-target">Target:</label>
                    <input type="text" id="step-target" name="target" placeholder="CSS selector or URL" required>
                </div>
                
                <div class="form-group" id="step-data-group" style="display: none;">
                    <label for="step-data">Data/Value:</label>
                    <textarea id="step-data" name="data" placeholder="Enter data"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="step-description">Description:</label>
                    <input type="text" id="step-description" name="description" placeholder="Optional description">
                </div>
                
                <div class="form-group">
                    <label for="step-timeout">Timeout (seconds):</label>
                    <input type="number" id="step-timeout" name="timeout" value="30" min="1" max="300">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="modern-btn modern-btn-secondary" id="cancel-step">Cancel</button>
            <button type="button" class="modern-btn modern-btn-primary" id="save-step">Add Step</button>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.step-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 5px;
    margin-bottom: 10px;
}

.step-content {
    flex: 1;
}

.step-actions {
    display: flex;
    gap: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.modern-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.modern-btn-primary {
    background: #00265e;
    color: white;
}

.modern-btn-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.modern-btn:hover {
    opacity: 0.9;
}

.modern-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.modern-card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modern-card-body {
    padding: 20px;
}

/* Small checkbox styling */
.checkbox-label {
    display: flex !important;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    margin-bottom: 0 !important;
    font-weight: 600 !important;
}

.small-checkbox {
    width: 16px !important;
    height: 16px !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 1px solid #ddd !important;
    border-radius: 3px !important;
    background: white !important;
    cursor: pointer;
    flex-shrink: 0;
}

.small-checkbox:checked {
    background: #00265e !important;
    border-color: #00265e !important;
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m13.854 3.646-7.5 7.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6 10.293l7.146-7.147a.5.5 0 0 1 .708.708z'/%3e%3c/svg%3e") !important;
    background-size: 12px !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}

.small-checkbox:focus {
    outline: none !important;
    box-shadow: 0 0 0 2px rgba(0, 38, 94, 0.2) !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentStepIndex = null;
    
    // Add step button
    $('#add-step-btn').on('click', function() {
        currentStepIndex = null;
        $('#modal-title').text('Add New Step');
        $('#save-step').text('Add Step');
        resetStepForm();
        $('#step-editor-modal').show();
    });
    
    // Edit step button
    $(document).on('click', '.edit-step-btn', function() {
        currentStepIndex = $(this).data('index');
        const steps = JSON.parse($('#flow_steps').val() || '[]');
        const step = steps[currentStepIndex];
        
        // Only log if there's an issue
        if (!step) {
            console.warn(`Edit clicked but step not found at index ${currentStepIndex}`);
        }
        
        if (!step) {
            showErrorModal('Step not found');
            return;
        }
        
        $('#modal-title').text('Edit Step');
        $('#save-step').text('Update Step');
        populateStepForm(step);
        $('#step-editor-modal').show();
    });
    
    // Delete step button
    $(document).on('click', '.delete-step-btn', function() {
        if (confirm('Are you sure you want to delete this step?')) {
            const index = $(this).data('index');
            const steps = JSON.parse($('#flow_steps').val() || '[]');
            steps.splice(index, 1);
            $('#flow_steps').val(JSON.stringify(steps));
            updateStepsList();
        }
    });
    
    // Prevent form submission
    $('#step-form').on('submit', function(e) {
        e.preventDefault();
        $('#save-step').click();
    });
    
    // Save step
    $('#save-step').on('click', function() {
        const formData = {
            action: $('#step-action').val(),
            target: $('#step-target').val(),
            data: $('#step-data').val(),
            description: $('#step-description').val(),
            timeout: parseInt($('#step-timeout').val()) || 30
        };
        
        // Validate
        if (!formData.action) {
            showErrorModal('Please select an action type');
            return;
        }
        if (!formData.target) {
            showErrorModal('Please enter a target');
            return;
        }
        
        // Save step
        const steps = JSON.parse($('#flow_steps').val() || '[]');
        const stepsBefore = steps.length;
        
        if (currentStepIndex !== null && currentStepIndex >= 0) {
            // Editing existing step
            steps[currentStepIndex] = formData;
        } else {
            // Adding new step
            steps.push(formData);
        }
        
        // Ensure proper JSON encoding
        const encodedSteps = JSON.stringify(steps, null, 0);
        $('#flow_steps').val(encodedSteps);
        updateStepsList();
        $('#step-editor-modal').hide();
        
        // Reset currentStepIndex
        currentStepIndex = null;
    });
    
    // Cancel step
    $('#cancel-step, .modal-close').on('click', function() {
        $('#step-editor-modal').hide();
        currentStepIndex = null; // Reset currentStepIndex
    });
    
    // Close modal on outside click
    $('#step-editor-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Show/hide data field based on action
    $('#step-action').on('change', function() {
        const action = $(this).val();
        if (['fill_input', 'fill_form', 'verify'].includes(action)) {
            $('#step-data-group').show();
        } else {
            $('#step-data-group').hide();
        }
    });
    
    function resetStepForm() {
        $('#step-form')[0].reset();
        $('#step-timeout').val(30);
        $('#step-data-group').hide();
    }
    
    function populateStepForm(step) {
        // Clear form first
        $('#step-form')[0].reset();
        // Set values
        $('#step-action').val(step.action || '');
        $('#step-target').val(step.target || '');
        $('#step-data').val(step.data || '');
        $('#step-description').val(step.description || '');
        $('#step-timeout').val(step.timeout || 30);
        
        // Show/hide data field based on action
        const action = step.action || '';
        if (['fill_input', 'fill_form', 'verify'].includes(action)) {
            $('#step-data-group').show();
        } else {
            $('#step-data-group').hide();
        }
    }
    
    function updateStepsList() {
        const steps = JSON.parse($('#flow_steps').val() || '[]');
        let html = '';
        
        steps.forEach((step, index) => {
            html += `
                <div class="step-item" data-index="${index}">
                    <div class="step-content">
                        <strong>${step.action}</strong>
                        <span>${step.target}</span>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="edit-step-btn" data-index="${index}">Edit</button>
                        <button type="button" class="delete-step-btn" data-index="${index}">Delete</button>
                    </div>
                </div>
            `;
        });
        
        $('#steps-list').html(html);
    }
    
    // Initialize steps list
    updateStepsList();
    
    // Add form submission validation
    $('form').on('submit', function(e) {
        const stepsValue = $('#flow_steps').val();
        
        // Ensure steps are properly formatted
        try {
            // Clean and validate the JSON
            const cleanStepsValue = cleanAndValidateJSON(stepsValue || '[]');
            const steps = JSON.parse(cleanStepsValue);
            
            // Validate that we have steps
            if (steps.length === 0) {
                console.warn('No steps found in form submission!');
            }
            
            // Set the cleaned JSON
            $('#flow_steps').val(cleanStepsValue);
            
            // Store steps in sessionStorage as backup
            sessionStorage.setItem('wp_tester_steps_backup', cleanStepsValue);
            
        } catch (error) {
            console.error('Error parsing steps:', error);
            e.preventDefault();
            alert('Error with steps data. Please try again.');
            return false;
        }
    });
    
    // Check for steps backup on page load
    $(document).ready(function() {
        const backupSteps = sessionStorage.getItem('wp_tester_steps_backup');
        if (backupSteps) {
            try {
                const steps = JSON.parse(backupSteps);
                const currentSteps = JSON.parse($('#flow_steps').val() || '[]');
                
                // If current steps are empty but we have backup, restore them
                if (currentSteps.length === 0 && steps.length > 0) {
                    $('#flow_steps').val(JSON.stringify(steps));
                    updateStepsList();
                }
                
                // Clear backup after use
                sessionStorage.removeItem('wp_tester_steps_backup');
            } catch (error) {
                console.error('Error restoring steps from backup:', error);
            }
        }
    });
    
    // JSON validation and cleaning function
    function cleanAndValidateJSON(jsonString) {
        try {
            // First, try to parse as-is
            const parsed = JSON.parse(jsonString);
            return JSON.stringify(parsed, null, 0);
        } catch (error) {
            console.warn('JSON parse error, attempting to fix:', error);
            
            // Try to fix common issues
            let fixed = jsonString;
            
            // Remove extra escaping
            fixed = fixed.replace(/\\"/g, '"');
            fixed = fixed.replace(/\\\\/g, '\\');
            
            // Fix unescaped quotes in URLs
            fixed = fixed.replace(/"([^"]*https?:\/\/[^"]*)"([^"]*)"([^"]*)"/g, '"$1$2$3"');
            
            try {
                const parsed = JSON.parse(fixed);
                return JSON.stringify(parsed, null, 0);
            } catch (secondError) {
                console.error('Could not fix JSON:', secondError);
                return '[]';
            }
        }
    }
    
    // Error modal function
    function showErrorModal(title, message) {
        const modalHtml = `
            <div id="error-modal" class="modal" style="display: block;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>${title}</h3>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary modal-close">OK</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        $('.modal-close').on('click', function() {
            $('#error-modal').remove();
        });
    }
});
</script>