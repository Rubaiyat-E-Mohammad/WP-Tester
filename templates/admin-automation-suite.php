<?php
/**
 * Automation Suite Admin Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get automation suite instance
$automation_suite = new WP_Tester_Automation_Suite();
$frameworks = $automation_suite->get_supported_frameworks();

// Get all flows
$database = new WP_Tester_Database();
$flows = $database->get_flows();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-generic" style="color: #00265e; margin-right: 0.5rem;"></span>
        <?php _e('Automation Suite Generator', 'wp-tester'); ?>
    </h1>
    
    <div class="modern-admin-container" style="max-width: 1200px; margin: 2rem 0;">
        
        <!-- Framework Selection -->
        <div class="modern-card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 style="margin: 0; color: #00265e; display: flex; align-items: center;">
                    <span class="dashicons dashicons-admin-tools" style="margin-right: 0.5rem;"></span>
                    Select Test Framework
                </h3>
            </div>
            <div class="card-content">
                <div class="framework-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <?php foreach ($frameworks as $key => $framework): ?>
                    <div class="framework-option" data-framework="<?php echo esc_attr($key); ?>" 
                         style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all 0.3s ease; background: #fff;">
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <input type="radio" name="framework" value="<?php echo esc_attr($key); ?>" id="framework-<?php echo esc_attr($key); ?>" 
                                   style="margin-right: 0.5rem;">
                            <label for="framework-<?php echo esc_attr($key); ?>" style="font-weight: 600; color: #00265e; margin: 0;">
                                <?php echo esc_html($framework['name']); ?>
                            </label>
                        </div>
                        <p style="margin: 0; font-size: 0.875rem; color: #64748b;">
                            <?php echo esc_html($framework['description']); ?>
                        </p>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8;">
                            Language: <?php echo esc_html(ucfirst($framework['language'])); ?> | 
                            Extension: .<?php echo esc_html($framework['extension']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="framework-options" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="include-setup" checked style="margin-right: 0.5rem;">
                        <span style="font-size: 0.875rem;">Include setup files</span>
                    </label>
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" id="include-config" checked style="margin-right: 0.5rem;">
                        <span style="font-size: 0.875rem;">Include configuration files</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Flow Selection -->
        <div class="modern-card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 style="margin: 0; color: #00265e; display: flex; align-items: center;">
                    <span class="dashicons dashicons-list-view" style="margin-right: 0.5rem;"></span>
                    Select Flows to Include
                </h3>
            </div>
            <div class="card-content">
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <button type="button" id="select-all-flows" class="modern-btn modern-btn-secondary modern-btn-small">
                        Select All
                    </button>
                    <button type="button" id="deselect-all-flows" class="modern-btn modern-btn-secondary modern-btn-small">
                        Deselect All
                    </button>
                    <button type="button" id="select-active-only" class="modern-btn modern-btn-secondary modern-btn-small">
                        Active Only
                    </button>
                </div>
                
                <div class="flows-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <?php if (!empty($flows)): ?>
                        <?php foreach ($flows as $flow): ?>
                        <div class="flow-item" style="padding: 1rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center;">
                            <input type="checkbox" class="flow-checkbox" value="<?php echo esc_attr($flow->id); ?>" 
                                   id="flow-<?php echo esc_attr($flow->id); ?>" style="margin-right: 1rem;">
                            <div style="flex: 1;">
                                <label for="flow-<?php echo esc_attr($flow->id); ?>" style="font-weight: 600; color: #00265e; cursor: pointer; margin: 0;">
                                    <?php echo esc_html($flow->flow_name); ?>
                                </label>
                                <?php if (!empty($flow->flow_description)): ?>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b;">
                                    <?php echo esc_html($flow->flow_description); ?>
                                </p>
                                <?php endif; ?>
                                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8;">
                                    Type: <?php echo esc_html(ucfirst($flow->flow_type)); ?> | 
                                    Steps: <?php echo count(json_decode($flow->steps, true) ?: []); ?> | 
                                    Status: <span class="status-badge <?php echo $flow->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $flow->is_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #64748b;">
                            <span class="dashicons dashicons-info" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></span>
                            <p>No flows found. Create some flows first to generate automation suites.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Generate Button -->
        <div class="modern-card">
            <div class="card-content" style="text-align: center;">
                <button type="button" id="generate-suite" class="modern-btn modern-btn-primary modern-btn-large" 
                        style="padding: 1rem 2rem; font-size: 1.1rem;" disabled>
                    <span class="dashicons dashicons-download" style="margin-right: 0.5rem;"></span>
                    Generate Automation Suite
                </button>
                <p style="margin: 1rem 0 0 0; font-size: 0.875rem; color: #64748b;">
                    This will use AI to generate complete, executable test automation code for your selected flows.
                </p>
            </div>
        </div>
        
        <!-- Progress and Results -->
        <div id="generation-progress" class="modern-card" style="display: none; margin-top: 2rem;">
            <div class="card-content">
                <div style="text-align: center;">
                    <div class="spinner" style="display: inline-block; width: 2rem; height: 2rem; border: 3px solid #f3f3f3; border-top: 3px solid #00265e; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem;"></div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #00265e;">Generating Automation Suite...</h4>
                    <p style="margin: 0; color: #64748b;">This may take a few moments. Please don't close this page.</p>
                </div>
            </div>
        </div>
        
        <div id="generation-results" class="modern-card" style="display: none; margin-top: 2rem;">
            <div class="card-header">
                <h3 style="margin: 0; color: #00265e; display: flex; align-items: center;">
                    <span class="dashicons dashicons-yes-alt" style="margin-right: 0.5rem; color: #10b981;"></span>
                    Suite Generated Successfully
                </h3>
            </div>
            <div class="card-content">
                <div id="suite-info" style="margin-bottom: 1.5rem;">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div style="text-align: center;">
                    <button type="button" id="download-suite" class="modern-btn modern-btn-primary modern-btn-large">
                        <span class="dashicons dashicons-download" style="margin-right: 0.5rem;"></span>
                        Download ZIP File
                    </button>
                </div>
            </div>
        </div>
        
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.framework-option:hover {
    border-color: #00265e !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.framework-option.selected {
    border-color: #00265e !important;
    background: #f8fafc !important;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.active {
    background: #dcfce7;
    color: #166534;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.flow-item:hover {
    background: #f8fafc;
}

.flow-item:last-child {
    border-bottom: none !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Define ajaxurl for AJAX requests
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    let selectedFramework = '';
    let selectedFlows = [];
    let currentSuiteId = '';
    
    // Framework selection
    $('.framework-option').on('click', function() {
        $('.framework-option').removeClass('selected');
        $(this).addClass('selected');
        selectedFramework = $(this).data('framework');
        $(this).find('input[type="radio"]').prop('checked', true);
        updateGenerateButton();
    });
    
    // Flow selection
    $('.flow-checkbox').on('change', function() {
        updateSelectedFlows();
        updateGenerateButton();
    });
    
    // Select all flows
    $('#select-all-flows').on('click', function() {
        $('.flow-checkbox').prop('checked', true);
        updateSelectedFlows();
        updateGenerateButton();
    });
    
    // Deselect all flows
    $('#deselect-all-flows').on('click', function() {
        $('.flow-checkbox').prop('checked', false);
        updateSelectedFlows();
        updateGenerateButton();
    });
    
    // Select active only
    $('#select-active-only').on('click', function() {
        $('.flow-checkbox').prop('checked', false);
        $('.flow-item').each(function() {
            if ($(this).find('.status-badge.active').length > 0) {
                $(this).find('.flow-checkbox').prop('checked', true);
            }
        });
        updateSelectedFlows();
        updateGenerateButton();
    });
    
    // Generate suite
    $('#generate-suite').on('click', function() {
        if (!selectedFramework || selectedFlows.length === 0) {
            alert('Please select a framework and at least one flow.');
            return;
        }
        
        generateAutomationSuite();
    });
    
    // Download suite
    $(document).on('click', '#download-suite', function() {
        if (currentSuiteId) {
            downloadAutomationSuite(currentSuiteId);
        }
    });
    
    function updateSelectedFlows() {
        selectedFlows = [];
        $('.flow-checkbox:checked').each(function() {
            selectedFlows.push($(this).val());
        });
    }
    
    function updateGenerateButton() {
        const hasFramework = selectedFramework !== '';
        const hasFlows = selectedFlows.length > 0;
        $('#generate-suite').prop('disabled', !(hasFramework && hasFlows));
    }
    
    function generateAutomationSuite() {
        $('#generation-progress').show();
        $('#generation-results').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wp_tester_generate_automation_suite',
                framework: selectedFramework,
                flow_ids: selectedFlows,
                include_setup: $('#include-setup').is(':checked'),
                include_config: $('#include-config').is(':checked'),
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                $('#generation-progress').hide();
                
                if (response.success) {
                    currentSuiteId = response.data.suite_id;
                    showGenerationResults(response.data);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#generation-progress').hide();
                console.error('Generation error:', xhr, status, error);
                alert('Network error: ' + error);
            }
        });
    }
    
    function showGenerationResults(data) {
        const frameworkInfo = <?php echo json_encode($frameworks); ?>;
        const framework = frameworkInfo[data.framework];
        
        $('#suite-info').html(`
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                    <div style="font-size: 1.5rem; font-weight: 600; color: #00265e;">${data.flow_count}</div>
                    <div style="font-size: 0.875rem; color: #64748b;">Flows Included</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                    <div style="font-size: 1.5rem; font-weight: 600; color: #00265e;">${data.files ? data.files.length : 0}</div>
                    <div style="font-size: 0.875rem; color: #64748b;">Files Generated</div>
                </div>
                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                    <div style="font-size: 1.5rem; font-weight: 600; color: #00265e;">${framework.name}</div>
                    <div style="font-size: 0.875rem; color: #64748b;">Framework</div>
                </div>
            </div>
            <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #00265e;">Generated Files:</h4>
                <ul style="margin: 0; padding-left: 1.5rem;">
                    ${data.files && Array.isArray(data.files) ? data.files.map(file => `<li style="font-family: monospace; font-size: 0.875rem;">${file}</li>`).join('') : '<li style="font-family: monospace; font-size: 0.875rem;">No files generated</li>'}
                </ul>
            </div>
        `);
        
        $('#generation-results').show();
    }
    
    function downloadAutomationSuite(suiteId) {
        const downloadBtn = $('#download-suite');
        const originalText = downloadBtn.html();
        
        downloadBtn.html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite; margin-right: 0.5rem;"></span>Preparing Download...');
        downloadBtn.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wp_tester_download_automation_suite',
                suite_id: suiteId,
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    const errorMessage = typeof response.data === 'object' ? 
                        (response.data.message || JSON.stringify(response.data)) : 
                        response.data;
                    alert('Error: ' + errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Download error:', xhr, status, error);
                let errorMessage = 'Network error: ' + error;
                
                // Try to parse error response
                if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.data && errorData.data.message) {
                            errorMessage = errorData.data.message;
                        }
                    } catch (e) {
                        // If not JSON, use the response text
                        errorMessage = xhr.responseText;
                    }
                }
                
                alert('Error: ' + errorMessage);
            },
            complete: function() {
                downloadBtn.html(originalText);
                downloadBtn.prop('disabled', false);
            }
        });
    }
});
</script>
