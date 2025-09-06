<?php
/**
 * Admin Flows Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        <?php _e('User Flows', 'wp-tester'); ?>
        <button id="wp-tester-discover-flows" class="page-title-action">
            <?php _e('Discover New Flows', 'wp-tester'); ?>
        </button>
    </h1>
    
    <?php if (!empty($flows)): ?>
    <div class="wp-tester-flows-filters">
        <select id="wp-tester-flow-type-filter">
            <option value=""><?php _e('All Flow Types', 'wp-tester'); ?></option>
            <option value="registration"><?php _e('Registration', 'wp-tester'); ?></option>
            <option value="login"><?php _e('Login', 'wp-tester'); ?></option>
            <option value="contact"><?php _e('Contact', 'wp-tester'); ?></option>
            <option value="search"><?php _e('Search', 'wp-tester'); ?></option>
            <option value="woocommerce"><?php _e('WooCommerce', 'wp-tester'); ?></option>
            <option value="navigation"><?php _e('Navigation', 'wp-tester'); ?></option>
        </select>
        
        <select id="wp-tester-priority-filter">
            <option value=""><?php _e('All Priorities', 'wp-tester'); ?></option>
            <option value="10"><?php _e('Critical (10)', 'wp-tester'); ?></option>
            <option value="8-9"><?php _e('High (8-9)', 'wp-tester'); ?></option>
            <option value="5-7"><?php _e('Medium (5-7)', 'wp-tester'); ?></option>
            <option value="1-4"><?php _e('Low (1-4)', 'wp-tester'); ?></option>
        </select>
        
        <input type="text" id="wp-tester-search-flows" placeholder="<?php _e('Search flows...', 'wp-tester'); ?>">
    </div>
    
    <div class="wp-tester-table-container">
        <table class="wp-list-table widefat fixed striped" id="wp-tester-flows-table">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th><?php _e('Flow Name', 'wp-tester'); ?></th>
                    <th><?php _e('Type', 'wp-tester'); ?></th>
                    <th><?php _e('Priority', 'wp-tester'); ?></th>
                    <th><?php _e('Start URL', 'wp-tester'); ?></th>
                    <th><?php _e('Steps', 'wp-tester'); ?></th>
                    <th><?php _e('Status', 'wp-tester'); ?></th>
                    <th><?php _e('Last Test', 'wp-tester'); ?></th>
                    <th><?php _e('Actions', 'wp-tester'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flows as $flow): 
                    $steps = json_decode($flow->steps, true) ?: array();
                    $recent_result = $this->database->get_test_results($flow->id, 1, 0);
                    $last_result = !empty($recent_result) ? $recent_result[0] : null;
                ?>
                <tr data-flow-type="<?php echo esc_attr($flow->flow_type); ?>" data-priority="<?php echo esc_attr($flow->priority); ?>">
                    <th class="check-column">
                        <input type="checkbox" name="flow_ids[]" value="<?php echo $flow->id; ?>">
                    </th>
                    <td>
                        <strong>
                            <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>">
                                <?php echo esc_html($flow->flow_name); ?>
                            </a>
                        </strong>
                        <div class="row-actions">
                            <span class="view">
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=view&flow_id=' . $flow->id); ?>">
                                    <?php _e('View', 'wp-tester'); ?>
                                </a> |
                            </span>
                            <span class="edit">
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=edit&flow_id=' . $flow->id); ?>">
                                    <?php _e('Edit', 'wp-tester'); ?>
                                </a> |
                            </span>
                            <span class="test">
                                <a href="<?php echo admin_url('admin.php?page=wp-tester-flows&action=test&flow_id=' . $flow->id); ?>" class="wp-tester-test-flow">
                                    <?php _e('Test Now', 'wp-tester'); ?>
                                </a> |
                            </span>
                            <span class="delete">
                                <a href="#" class="wp-tester-delete-flow" data-flow-id="<?php echo $flow->id; ?>" style="color: #a00;">
                                    <?php _e('Delete', 'wp-tester'); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php echo wp_tester()->admin->get_flow_type_icon($flow->flow_type); ?>
                        <?php echo esc_html(ucfirst($flow->flow_type)); ?>
                    </td>
                    <td>
                        <span class="wp-tester-priority-badge wp-tester-priority-<?php echo $flow->priority >= 8 ? 'high' : ($flow->priority >= 5 ? 'medium' : 'low'); ?>">
                            <?php echo $flow->priority; ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($flow->start_url); ?>" target="_blank" title="<?php echo esc_attr($flow->start_url); ?>">
                            <?php echo esc_html(wp_parse_url($flow->start_url, PHP_URL_PATH) ?: '/'); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </td>
                    <td><?php echo count($steps); ?> <?php _e('steps', 'wp-tester'); ?></td>
                    <td>
                        <?php if ($flow->is_active): ?>
                            <span class="wp-tester-status-active"><?php _e('Active', 'wp-tester'); ?></span>
                        <?php else: ?>
                            <span class="wp-tester-status-inactive"><?php _e('Inactive', 'wp-tester'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($last_result): ?>
                            <?php echo wp_tester()->admin->get_status_badge($last_result->status); ?>
                            <br>
                            <small><?php echo human_time_diff(strtotime($last_result->started_at), current_time('timestamp')); ?> <?php _e('ago', 'wp-tester'); ?></small>
                        <?php else: ?>
                            <span class="wp-tester-status-never"><?php _e('Never tested', 'wp-tester'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small wp-tester-test-single-flow" data-flow-id="<?php echo $flow->id; ?>">
                            <?php _e('Test', 'wp-tester'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Bulk Actions -->
    <div class="wp-tester-bulk-actions">
        <select id="wp-tester-bulk-action">
            <option value=""><?php _e('Bulk Actions', 'wp-tester'); ?></option>
            <option value="test"><?php _e('Test Selected Flows', 'wp-tester'); ?></option>
            <option value="activate"><?php _e('Activate', 'wp-tester'); ?></option>
            <option value="deactivate"><?php _e('Deactivate', 'wp-tester'); ?></option>
            <option value="delete"><?php _e('Delete', 'wp-tester'); ?></option>
        </select>
        <button id="wp-tester-apply-bulk-action" class="button"><?php _e('Apply', 'wp-tester'); ?></button>
    </div>
    
    <?php else: ?>
    <div class="wp-tester-empty-state">
        <span class="dashicons dashicons-admin-generic"></span>
        <h2><?php _e('No Flows Found', 'wp-tester'); ?></h2>
        <p><?php _e('No user flows have been discovered yet. Run a site crawl to automatically detect flows.', 'wp-tester'); ?></p>
        <button id="wp-tester-discover-flows" class="button button-primary">
            <?php _e('Discover Flows Now', 'wp-tester'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Filter flows
    $('#wp-tester-flow-type-filter, #wp-tester-priority-filter').on('change', function() {
        filterFlows();
    });
    
    $('#wp-tester-search-flows').on('keyup', function() {
        filterFlows();
    });
    
    function filterFlows() {
        var typeFilter = $('#wp-tester-flow-type-filter').val();
        var priorityFilter = $('#wp-tester-priority-filter').val();
        var searchTerm = $('#wp-tester-search-flows').val().toLowerCase();
        
        $('#wp-tester-flows-table tbody tr').each(function() {
            var $row = $(this);
            var flowType = $row.data('flow-type');
            var priority = parseInt($row.data('priority'));
            var flowName = $row.find('td:nth-child(2) strong a').text().toLowerCase();
            
            var showRow = true;
            
            // Type filter
            if (typeFilter && flowType !== typeFilter) {
                showRow = false;
            }
            
            // Priority filter
            if (priorityFilter && showRow) {
                if (priorityFilter === '10' && priority !== 10) showRow = false;
                else if (priorityFilter === '8-9' && (priority < 8 || priority > 9)) showRow = false;
                else if (priorityFilter === '5-7' && (priority < 5 || priority > 7)) showRow = false;
                else if (priorityFilter === '1-4' && (priority < 1 || priority > 4)) showRow = false;
            }
            
            // Search filter
            if (searchTerm && showRow && flowName.indexOf(searchTerm) === -1) {
                showRow = false;
            }
            
            $row.toggle(showRow);
        });
    }
    
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="flow_ids[]"]').prop('checked', this.checked);
    });
    
    // Test single flow
    $('.wp-tester-test-single-flow').on('click', function() {
        var $button = $(this);
        var flowId = $button.data('flow-id');
        
        $button.prop('disabled', true).text('<?php _e('Testing...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_test_flow',
            flow_id: flowId,
            nonce: wpTesterAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Failed to run test. Please try again.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Test', 'wp-tester'); ?>');
        });
    });
    
    // Delete flow
    $('.wp-tester-delete-flow').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php _e('Are you sure you want to delete this flow?', 'wp-tester'); ?>')) {
            return;
        }
        
        var flowId = $(this).data('flow-id');
        
        $.post(ajaxurl, {
            action: 'wp_tester_delete_flow',
            flow_id: flowId,
            nonce: wpTesterAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Failed to delete flow. Please try again.', 'wp-tester'); ?>');
            }
        });
    });
    
    // Discover flows
    $('#wp-tester-discover-flows').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('<?php _e('Discovering...', 'wp-tester'); ?>');
        
        $.post(ajaxurl, {
            action: 'wp_tester_discover_flows',
            nonce: wpTesterAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Failed to discover flows. Please try again.', 'wp-tester'); ?>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Discover New Flows', 'wp-tester'); ?>');
        });
    });
    
    // Bulk actions
    $('#wp-tester-apply-bulk-action').on('click', function() {
        var action = $('#wp-tester-bulk-action').val();
        var selectedFlows = $('input[name="flow_ids[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (!action) {
            alert('<?php _e('Please select an action.', 'wp-tester'); ?>');
            return;
        }
        
        if (selectedFlows.length === 0) {
            alert('<?php _e('Please select at least one flow.', 'wp-tester'); ?>');
            return;
        }
        
        if (action === 'delete' && !confirm('<?php _e('Are you sure you want to delete the selected flows?', 'wp-tester'); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'wp_tester_bulk_action',
            bulk_action: action,
            flow_ids: selectedFlows,
            nonce: wpTesterAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php _e('Failed to perform bulk action. Please try again.', 'wp-tester'); ?>');
            }
        });
    });
});
</script>