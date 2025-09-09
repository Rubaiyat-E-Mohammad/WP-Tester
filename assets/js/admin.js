/**
 * WP Tester Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    var wpTester = {
        isRunning: false,
        testRunId: null
    };
    
    /**
     * Initialize admin functionality
     */
    function init() {
        bindEvents();
        initTooltips();
        initProgressBars();
        checkForRunningTests();
    }
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Quick action buttons
        $(document).on('click', '.wp-tester-action-button[data-action]', handleQuickAction);
        
        // Test flow buttons
        $(document).on('click', '.wp-tester-test-flow', handleTestFlow);
        
        // Delete flow buttons
        $(document).on('click', '.wp-tester-delete-flow', handleDeleteFlow);
        
        // Bulk actions
        $(document).on('click', '#wp-tester-apply-bulk-action', handleBulkAction);
        
        // Select all checkbox
        $(document).on('change', '#cb-select-all', handleSelectAll);
        
        // Filter changes
        $(document).on('change keyup', '.wp-tester-filter', handleFilterChange);
        
        // Export buttons
        $(document).on('click', '.wp-tester-export', handleExport);
        
        // Refresh buttons
        $(document).on('click', '.wp-tester-refresh', handleRefresh);
        
        // Universal modal close handlers
        initModalHandlers();
        
        // Auto-refresh for running tests
        setInterval(checkTestStatus, 5000);
    }
    
    /**
     * Handle quick actions
     */
    function handleQuickAction(e) {
        e.preventDefault();
        
        var $button = $(this);
        var action = $button.data('action');
        
        if (wpTester.isRunning) {
            showNotice('A test is already running. Please wait for it to complete.', 'warning');
            return;
        }
        
        switch (action) {
            case 'run_all_tests':
                runAllTests($button);
                break;
            case 'run_crawl':
                runCrawl($button);
                break;
            case 'discover_flows':
                discoverFlows($button);
                break;
            default:
                console.log('Unknown action:', action);
        }
    }
    
    /**
     * Run all tests
     */
    function runAllTests($button) {
        var originalText = $button.find('span:last').text();
        
        setButtonLoading($button, wpTesterAdmin.strings.test_running);
        wpTester.isRunning = true;
        
        $.post(ajaxurl, {
            action: 'wp_tester_run_all_tests',
            nonce: wpTesterAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showNotice(response.data.message || 'Failed to run tests', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to run tests. Please try again.', 'error');
        })
        .always(function() {
            setButtonLoading($button, originalText, false);
            wpTester.isRunning = false;
        });
    }
    
    /**
     * Run site crawl
     */
    function runCrawl($button) {
        var originalText = $button.find('span:last').text();
        
        setButtonLoading($button, 'Crawling...');
        
        $.post(ajaxurl, {
            action: 'wp_tester_run_crawl',
            nonce: wpTesterAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showNotice(response.data.message || 'Failed to run crawl', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to run crawl. Please try again.', 'error');
        })
        .always(function() {
            setButtonLoading($button, originalText, false);
        });
    }
    
    /**
     * Discover flows
     */
    function discoverFlows($button) {
        var originalText = $button.text();
        
        setButtonLoading($button, 'Discovering...');
        
        $.post(ajaxurl, {
            action: 'wp_tester_discover_flows',
            nonce: wpTesterAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showNotice(response.data.message || 'Failed to discover flows', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to discover flows. Please try again.', 'error');
        })
        .always(function() {
            setButtonLoading($button, originalText, false);
        });
    }
    
    /**
     * Handle test flow
     */
    function handleTestFlow(e) {
        e.preventDefault();
        
        var $button = $(this);
        var flowId = $button.data('flow-id');
        var originalText = $button.text();
        
        if (wpTester.isRunning) {
            showNotice('A test is already running. Please wait for it to complete.', 'warning');
            return;
        }
        
        setButtonLoading($button, 'Testing...');
        wpTester.isRunning = true;
        
        $.post(ajaxurl, {
            action: 'wp_tester_test_flow',
            flow_id: flowId,
            nonce: wpTesterAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                wpTester.testRunId = response.data.result.test_run_id;
                
                // Start monitoring test status
                monitorTestProgress();
            } else {
                showNotice(response.data.message || 'Failed to run test', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to run test. Please try again.', 'error');
        })
        .always(function() {
            setButtonLoading($button, originalText, false);
            wpTester.isRunning = false;
        });
    }
    
    /**
     * Handle delete flow
     */
    function handleDeleteFlow(e) {
        e.preventDefault();
        
        if (!confirm(wpTesterAdmin.strings.confirm_delete)) {
            return;
        }
        
        var $button = $(this);
        var flowId = $button.data('flow-id');
        var $row = $button.closest('tr');
        
        $.post(ajaxurl, {
            action: 'wp_tester_delete_flow',
            flow_id: flowId,
            nonce: wpTesterAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                $row.fadeOut(400, function() {
                    $(this).remove();
                });
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data.message || 'Failed to delete flow', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to delete flow. Please try again.', 'error');
        });
    }
    
    /**
     * Handle bulk actions
     */
    function handleBulkAction(e) {
        e.preventDefault();
        
        var action = $('#wp-tester-bulk-action').val();
        var selectedFlows = $('input[name="flow_ids[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (!action) {
            showNotice('Please select an action.', 'warning');
            return;
        }
        
        if (selectedFlows.length === 0) {
            showNotice('Please select at least one flow.', 'warning');
            return;
        }
        
        if (action === 'delete' && !confirm('Are you sure you want to delete the selected flows?')) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        
        setButtonLoading($button, 'Processing...');
        
        $.post(ajaxurl, {
            action: 'wp_tester_bulk_action',
            bulk_action: action,
            flow_ids: selectedFlows,
            nonce: wpTesterAdmin.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotice(response.data.message || 'Failed to perform bulk action', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to perform bulk action. Please try again.', 'error');
        })
        .always(function() {
            setButtonLoading($button, originalText, false);
        });
    }
    
    /**
     * Handle select all
     */
    function handleSelectAll() {
        var isChecked = $(this).prop('checked');
        $('input[name="flow_ids[]"]').prop('checked', isChecked);
    }
    
    /**
     * Handle filter changes
     */
    function handleFilterChange() {
        var filters = {};
        
        $('.wp-tester-filter').each(function() {
            var $filter = $(this);
            var name = $filter.data('filter');
            var value = $filter.val();
            
            if (value) {
                filters[name] = value;
            }
        });
        
        applyFilters(filters);
    }
    
    /**
     * Apply filters to table rows
     */
    function applyFilters(filters) {
        var $rows = $('.wp-tester-filterable-row');
        
        $rows.each(function() {
            var $row = $(this);
            var showRow = true;
            
            $.each(filters, function(filterName, filterValue) {
                var rowValue = $row.data(filterName);
                
                if (filterName === 'search') {
                    var searchText = $row.find('.wp-tester-searchable').text().toLowerCase();
                    if (searchText.indexOf(filterValue.toLowerCase()) === -1) {
                        showRow = false;
                    }
                } else if (rowValue && rowValue.toString() !== filterValue) {
                    showRow = false;
                }
            });
            
            $row.toggle(showRow);
        });
    }
    
    /**
     * Handle export
     */
    function handleExport(e) {
        e.preventDefault();
        
        var $button = $(this);
        var format = $button.data('format') || 'json';
        var flowId = $button.data('flow-id') || '';
        
        var url = ajaxurl + '?action=wp_tester_export_report&format=' + format + 
                  '&flow_id=' + flowId + '&nonce=' + wpTesterAdmin.nonce;
        
        window.location.href = url;
    }
    
    /**
     * Handle refresh
     */
    function handleRefresh(e) {
        e.preventDefault();
        location.reload();
    }
    
    /**
     * Monitor test progress
     */
    function monitorTestProgress() {
        if (!wpTester.testRunId) {
            return;
        }
        
        var checkInterval = setInterval(function() {
            $.post(ajaxurl, {
                action: 'wp_tester_get_test_status',
                test_run_id: wpTester.testRunId,
                nonce: wpTesterAdmin.nonce
            })
            .done(function(response) {
                if (response.success && response.data.completed) {
                    clearInterval(checkInterval);
                    
                    if (response.data.status === 'passed') {
                        showNotice(wpTesterAdmin.strings.test_completed, 'success');
                    } else {
                        showNotice(wpTesterAdmin.strings.test_failed, 'warning');
                    }
                    
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            });
        }, 3000);
        
        // Stop checking after 5 minutes
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 300000);
    }
    
    /**
     * Check for running tests
     */
    function checkForRunningTests() {
        // This would check if there are any tests currently running
        // and update the UI accordingly
    }
    
    /**
     * Check test status periodically
     */
    function checkTestStatus() {
        if (!wpTester.isRunning && !wpTester.testRunId) {
            return;
        }
        
        // Update any progress indicators or status displays
        $('.wp-tester-status-running').each(function() {
            var $status = $(this);
            // Update status if needed
        });
    }
    
    /**
     * Set button loading state
     */
    function setButtonLoading($button, loadingText, isLoading) {
        if (isLoading !== false) {
            $button.prop('disabled', true);
            
            if ($button.find('span:last').length) {
                $button.find('span:last').text(loadingText);
            } else {
                $button.text(loadingText);
            }
            
            $button.addClass('wp-tester-loading');
        } else {
            $button.prop('disabled', false);
            
            if ($button.find('span:last').length) {
                $button.find('span:last').text(loadingText);
            } else {
                $button.text(loadingText);
            }
            
            $button.removeClass('wp-tester-loading');
        }
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var noticeClass = 'notice notice-' + type;
        if (type === 'error') {
            noticeClass = 'notice notice-error';
        } else if (type === 'success') {
            noticeClass = 'notice notice-success';
        } else if (type === 'warning') {
            noticeClass = 'notice notice-warning';
        }
        
        var $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(400, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(400, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            var $element = $(this);
            
            $element.hover(
                function() {
                    var tooltip = $element.data('tooltip');
                    var $tooltip = $('<div class="wp-tester-tooltip-popup">' + tooltip + '</div>');
                    
                    $('body').append($tooltip);
                    
                    var offset = $element.offset();
                    $tooltip.css({
                        position: 'absolute',
                        top: offset.top - $tooltip.outerHeight() - 10,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                        zIndex: 9999
                    });
                },
                function() {
                    $('.wp-tester-tooltip-popup').remove();
                }
            );
        });
    }
    
    /**
     * Initialize progress bars
     */
    function initProgressBars() {
        $('.wp-tester-progress-bar').each(function() {
            var $bar = $(this);
            var $fill = $bar.find('.wp-tester-progress-fill');
            var percentage = $fill.data('percentage') || 0;
            
            // Animate progress bar
            setTimeout(function() {
                $fill.css('width', percentage + '%');
            }, 100);
        });
    }
    
    /**
     * Utility: Debounce function
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    /**
     * Utility: Format time duration
     */
    function formatDuration(seconds) {
        if (seconds < 60) {
            return Math.round(seconds * 100) / 100 + 's';
        } else if (seconds < 3600) {
            return Math.round(seconds / 60 * 10) / 10 + 'm';
        } else {
            return Math.round(seconds / 3600 * 10) / 10 + 'h';
        }
    }
    
    /**
     * Utility: Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Initialize universal modal close handlers
     */
    function initModalHandlers() {
        // Handle all modal close buttons
        $(document).on('click', '.modal-close, .modal-close-btn, .wp-tester-modal-close, .wp-tester-popup-close', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(this);
            const $modal = $button.closest('.modal-overlay, .wp-tester-modal, .wp-tester-popup-overlay, .modern-modal');
            
            if ($modal.length) {
                $modal.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        // Handle modal overlay clicks
        $(document).on('click', '.modal-overlay, .wp-tester-modal, .wp-tester-popup-overlay, .modern-modal', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        // Handle escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                $('.modal-overlay:visible, .wp-tester-modal:visible, .wp-tester-popup-overlay:visible, .modern-modal:visible').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    // Initialize when document is ready
    init();
    
    // Expose utilities globally
    window.wpTesterAdmin = window.wpTesterAdmin || {};
    window.wpTesterAdmin.utils = {
        showNotice: showNotice,
        setButtonLoading: setButtonLoading,
        formatDuration: formatDuration,
        formatFileSize: formatFileSize,
        debounce: debounce
    };
});