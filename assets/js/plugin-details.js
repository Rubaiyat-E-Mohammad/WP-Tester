/**
 * WP Tester Plugin Details Popup
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // DO NOT manipulate plugin links - handled by PHP filter now
        // replacePluginLink(); // DISABLED
        
        // NUCLEAR cleanup - remove ALL duplicate View Details links
        cleanupDuplicateViewDetailsLinks();
        
        // Re-run cleanup after DOM changes (in case WordPress adds links dynamically)
        setTimeout(cleanupDuplicateViewDetailsLinks, 1000);
        setTimeout(cleanupDuplicateViewDetailsLinks, 2000);
        
        // Handle View Details click
        $(document).on('click', '.wp-tester-view-details', function(e) {
            e.preventDefault();
            showPluginDetailsPopup();
        });
        
        // Handle popup close
        $(document).on('click', '.wp-tester-popup-close', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closePluginDetailsPopup();
        });
        
        // Handle overlay click
        $(document).on('click', '.wp-tester-popup-overlay', function(e) {
            if (e.target === this) {
                closePluginDetailsPopup();
            }
        });
        
        // Handle escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                closePluginDetailsPopup();
            }
        });
    });
    
    function cleanupDuplicateViewDetailsLinks() {
        // Find the WP Tester plugin row
        var pluginRow = $('tr[data-plugin*="wp-tester/wp-tester.php"]');
        if (pluginRow.length === 0) {
            // Try alternative selector
            pluginRow = $('tr').filter(function() {
                return $(this).find('.plugin-title strong').text().trim() === 'WP Tester';
            });
        }
        
        if (pluginRow.length > 0) {
            var viewDetailsLinks = pluginRow.find('a').filter(function() {
                var text = $(this).text().trim().toLowerCase();
                return text.includes('view') && text.includes('detail');
            });
            
            // If multiple View Details links found, keep only the first one with our class
            if (viewDetailsLinks.length > 1) {
                var customLink = viewDetailsLinks.filter('.wp-tester-view-details').first();
                if (customLink.length > 0) {
                    // Remove all other view details links
                    viewDetailsLinks.not(customLink).remove();
                } else {
                    // Keep only the first one and add our class
                    viewDetailsLinks.first().addClass('wp-tester-view-details').attr('href', '#');
                    viewDetailsLinks.not(':first').remove();
                }
            }
        }
    }
    
    function replacePluginLink() {
        // Find the WP Tester plugin row
        var pluginRow = $('tr[data-plugin*="wp-tester/wp-tester.php"]');
        if (pluginRow.length === 0) {
            // Try alternative selector
            pluginRow = $('tr').filter(function() {
                return $(this).find('.plugin-title strong').text().trim() === 'WP Tester';
            });
        }
        
        if (pluginRow.length > 0) {
            // Find and replace the "Visit plugin site" link
            var visitLink = pluginRow.find('a').filter(function() {
                return $(this).text().includes('Visit plugin site');
            });
            
            if (visitLink.length > 0) {
                visitLink.text('View Details');
                visitLink.attr('href', '#');
                visitLink.addClass('wp-tester-view-details');
            } else {
                // If no existing link, add one
                var actionsDiv = pluginRow.find('.row-actions');
                if (actionsDiv.length > 0) {
                    actionsDiv.append(' | <a href="#" class="wp-tester-view-details">View Details</a>');
                }
            }
        }
    }
    
    function showPluginDetailsPopup() {
        var data = wpTesterData.pluginData;
        
        var popupHtml = `
            <div class="wp-tester-popup-overlay">
                <div class="wp-tester-popup-container">
                    <div class="wp-tester-popup-header">
                        <div class="wp-tester-plugin-icon">
                            <img src="${wpTesterData.pluginData.logo_url}" alt="WP Tester Logo" />
                        </div>
                        <div class="wp-tester-plugin-info">
                            <h2>${data.name}</h2>
                            <p class="wp-tester-description">${data.description}</p>
                            <div class="wp-tester-meta">
                                <span class="version">Version ${data.version}</span>
                                <span class="author">By <a href="${data.author_url}" target="_blank">${data.author}</a></span>
                                <span class="updated">Last Updated: ${data.last_updated}</span>
                            </div>
                        </div>
                        <button class="wp-tester-popup-close">&times;</button>
                    </div>
                    
                    <div class="wp-tester-popup-body">
                        <div class="wp-tester-popup-tabs">
                            <button class="wp-tester-tab-button active" data-tab="description">Description</button>
                            <button class="wp-tester-tab-button" data-tab="features">Features</button>
                            <button class="wp-tester-tab-button" data-tab="stats">Statistics</button>
                            <button class="wp-tester-tab-button" data-tab="requirements">Requirements</button>
                        </div>
                        
                        <div class="wp-tester-popup-content">
                            <div class="wp-tester-tab-content active" id="wp-tester-tab-description">
                                <h3>About WP Tester</h3>
                                <p>${data.description}</p>
                                <p>WP Tester is the ultimate solution for automating user flow testing on WordPress websites. It intelligently crawls your site, discovers interactive elements, and automatically tests critical user journeys without requiring any coding knowledge.</p>
                                <p><strong>Perfect for:</strong></p>
                                <ul>
                                    <li>E-commerce websites (WooCommerce support)</li>
                                    <li>Membership sites</li>
                                    <li>Contact form validation</li>
                                    <li>User registration flows</li>
                                    <li>Search functionality testing</li>
                                </ul>
                            </div>
                            
                            <div class="wp-tester-tab-content" id="wp-tester-tab-features">
                                <h3>Key Features</h3>
                                <ul class="wp-tester-features-list">
                                    ${data.features.map(feature => `<li><span class="dashicons dashicons-yes"></span> ${feature}</li>`).join('')}
                                </ul>
                            </div>
                            
                            <div class="wp-tester-tab-content" id="wp-tester-tab-stats">
                                <h3>Current Statistics</h3>
                                <div class="wp-tester-stats-grid">
                                    ${Object.entries(data.stats).map(([key, value]) => `
                                        <div class="wp-tester-stat-item">
                                            <span class="wp-tester-stat-value">${value}</span>
                                            <span class="wp-tester-stat-label">${key}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div class="wp-tester-tab-content" id="wp-tester-tab-requirements">
                                <h3>System Requirements</h3>
                                <table class="wp-tester-requirements-table">
                                    ${Object.entries(data.requirements).map(([key, value]) => `
                                        <tr>
                                            <td><strong>${key}:</strong></td>
                                            <td>${value}</td>
                                        </tr>
                                    `).join('')}
                                </table>
                                <p><strong>Recommended:</strong> Latest versions for optimal performance and security.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="wp-tester-popup-footer">
                        <a href="${data.homepage}" target="_blank" class="button button-primary">
                            <span class="dashicons dashicons-external"></span> Visit Plugin Homepage
                        </a>
                        <a href="${data.author_url}" target="_blank" class="button">
                            <span class="dashicons dashicons-admin-users"></span> View Author Profile
                        </a>
                        <button class="button wp-tester-popup-close">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(popupHtml);
        
        // Handle tab switching
        $('.wp-tester-tab-button').on('click', function() {
            var tabId = $(this).data('tab');
            
            $('.wp-tester-tab-button').removeClass('active');
            $('.wp-tester-tab-content').removeClass('active');
            
            $(this).addClass('active');
            $('#wp-tester-tab-' + tabId).addClass('active');
        });
    }
    
    function closePluginDetailsPopup() {
        $('.wp-tester-popup-overlay').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
})(jQuery);
