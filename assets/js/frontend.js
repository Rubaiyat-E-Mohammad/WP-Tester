/**
 * WP Tester Frontend JavaScript
 * 
 * Handles frontend interaction tracking and dynamic content detection
 */

jQuery(document).ready(function($) {
    'use strict';
    
    var wpTester = {
        interactions: [],
        dynamicContent: [],
        isTracking: false
    };
    
    /**
     * Initialize frontend tracking
     */
    function init() {
        // Only initialize if tracking is enabled
        if (typeof wpTesterAjax === 'undefined') {
            return;
        }
        
        initInteractionTracking();
        initDynamicContentDetection();
        initAjaxDetection();
    }
    
    /**
     * Initialize interaction tracking
     */
    function initInteractionTracking() {
        // Track clicks on interactive elements
        $(document).on('click', 'a, button, input[type="button"], input[type="submit"]', function(e) {
            trackInteraction('click', this, e);
        });
        
        // Track form submissions
        $(document).on('submit', 'form', function(e) {
            trackInteraction('submit', this, e);
        });
        
        // Track input changes
        $(document).on('change', 'input, select, textarea', function(e) {
            trackInteraction('change', this, e);
        });
        
        // Track hover events on dropdowns and menus
        $(document).on('mouseenter', '.menu-item, .dropdown, [data-toggle="dropdown"]', function(e) {
            trackInteraction('hover', this, e);
        });
        
        // Track modal triggers
        $(document).on('click', '[data-toggle="modal"], [data-target^="#"]', function(e) {
            trackInteraction('modal_trigger', this, e);
        });
    }
    
    /**
     * Initialize dynamic content detection
     */
    function initDynamicContentDetection() {
        // Observe DOM changes
        if (window.MutationObserver) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        detectDynamicContent(mutation.addedNodes);
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        // Detect initially loaded dynamic content
        detectDynamicContent(document.querySelectorAll('[data-dynamic], .ajax-content, .dynamic-content'));
    }
    
    /**
     * Initialize AJAX detection
     */
    function initAjaxDetection() {
        // Override jQuery AJAX to detect AJAX requests
        if (typeof $.ajaxSetup === 'function') {
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    trackAjaxRequest('start', settings);
                },
                complete: function(xhr, textStatus) {
                    trackAjaxRequest('complete', {
                        status: textStatus,
                        responseURL: xhr.responseURL
                    });
                }
            });
        }
        
        // Detect fetch API calls
        if (window.fetch) {
            var originalFetch = window.fetch;
            window.fetch = function() {
                var args = arguments;
                trackAjaxRequest('fetch_start', {
                    url: args[0],
                    options: args[1]
                });
                
                return originalFetch.apply(this, arguments).then(function(response) {
                    trackAjaxRequest('fetch_complete', {
                        url: response.url,
                        status: response.status
                    });
                    return response;
                });
            };
        }
    }
    
    /**
     * Track user interaction
     */
    function trackInteraction(type, element, event) {
        var $element = $(element);
        
        var interaction = {
            type: type,
            timestamp: Date.now(),
            element: {
                tagName: element.tagName.toLowerCase(),
                id: element.id || '',
                className: element.className || '',
                text: $element.text().trim().substring(0, 100),
                href: element.href || '',
                action: element.action || '',
                name: element.name || '',
                value: element.value || ''
            },
            location: {
                url: window.location.href,
                pathname: window.location.pathname,
                hash: window.location.hash
            },
            viewport: {
                width: $(window).width(),
                height: $(window).height(),
                scrollTop: $(window).scrollTop()
            }
        };
        
        // Add specific data based on interaction type
        switch (type) {
            case 'click':
                interaction.coordinates = {
                    x: event.pageX,
                    y: event.pageY
                };
                break;
                
            case 'submit':
                interaction.formData = getFormData($element);
                break;
                
            case 'change':
                interaction.newValue = element.value;
                break;
        }
        
        wpTester.interactions.push(interaction);
        
        // Send interaction data periodically
        if (wpTester.interactions.length >= 10) {
            sendInteractionData();
        }
    }
    
    /**
     * Detect dynamic content
     */
    function detectDynamicContent(nodes) {
        var dynamicElements = [];
        
        $(nodes).each(function() {
            var $node = $(this);
            
            // Skip text nodes
            if (this.nodeType !== 1) {
                return;
            }
            
            // Check for dynamic content indicators
            if ($node.hasClass('ajax-content') || 
                $node.hasClass('dynamic-content') ||
                $node.attr('data-dynamic') ||
                $node.find('[data-dynamic]').length > 0) {
                
                dynamicElements.push({
                    element: this,
                    timestamp: Date.now(),
                    type: 'dynamic_content',
                    attributes: {
                        id: this.id || '',
                        className: this.className || '',
                        dataDynamic: $node.attr('data-dynamic') || ''
                    }
                });
            }
            
            // Check for lazy-loaded images
            if ($node.is('img[data-src], img[loading="lazy"]') ||
                $node.find('img[data-src], img[loading="lazy"]').length > 0) {
                
                dynamicElements.push({
                    element: this,
                    timestamp: Date.now(),
                    type: 'lazy_content',
                    attributes: {
                        src: $node.attr('src') || '',
                        dataSrc: $node.attr('data-src') || ''
                    }
                });
            }
        });
        
        if (dynamicElements.length > 0) {
            wpTester.dynamicContent = wpTester.dynamicContent.concat(dynamicElements);
        }
    }
    
    /**
     * Track AJAX requests
     */
    function trackAjaxRequest(type, data) {
        var ajaxData = {
            type: type,
            timestamp: Date.now(),
            url: window.location.href,
            data: data
        };
        
        // Store AJAX request data
        if (!wpTester.ajaxRequests) {
            wpTester.ajaxRequests = [];
        }
        
        wpTester.ajaxRequests.push(ajaxData);
    }
    
    /**
     * Get form data
     */
    function getFormData($form) {
        var formData = {};
        
        $form.find('input, select, textarea').each(function() {
            var $field = $(this);
            var name = this.name;
            var type = this.type;
            var value = $field.val();
            
            if (name && type !== 'password' && type !== 'hidden') {
                // Only store non-sensitive data
                if (type === 'checkbox' || type === 'radio') {
                    formData[name] = this.checked;
                } else {
                    formData[name] = value ? 'has_value' : 'empty';
                }
            }
        });
        
        return formData;
    }
    
    /**
     * Send interaction data to server
     */
    function sendInteractionData() {
        if (wpTester.interactions.length === 0) {
            return;
        }
        
        var dataToSend = {
            interactions: wpTester.interactions.slice(),
            dynamicContent: wpTester.dynamicContent.slice(),
            ajaxRequests: wpTester.ajaxRequests ? wpTester.ajaxRequests.slice() : [],
            pageInfo: {
                url: window.location.href,
                title: document.title,
                referrer: document.referrer,
                userAgent: navigator.userAgent,
                timestamp: Date.now()
            }
        };
        
        // Send data via AJAX
        $.post(wpTesterAjax.ajax_url, {
            action: 'wp_tester_track_interaction',
            data: JSON.stringify(dataToSend),
            nonce: wpTesterAjax.nonce
        }).done(function(response) {
            // Clear sent data
            wpTester.interactions = [];
            wpTester.dynamicContent = [];
            if (wpTester.ajaxRequests) {
                wpTester.ajaxRequests = [];
            }
        }).fail(function() {
            // Keep data for retry
            console.log('WP Tester: Failed to send interaction data');
        });
    }
    
    /**
     * Detect page load type
     */
    function detectPageLoadType() {
        var loadType = 'normal';
        
        // Check if it's a single page application navigation
        if (window.history && window.history.pushState) {
            var originalPushState = window.history.pushState;
            var originalReplaceState = window.history.replaceState;
            
            window.history.pushState = function() {
                loadType = 'spa_navigation';
                originalPushState.apply(window.history, arguments);
                trackPageLoad(loadType);
            };
            
            window.history.replaceState = function() {
                loadType = 'spa_replace';
                originalReplaceState.apply(window.history, arguments);
                trackPageLoad(loadType);
            };
        }
        
        // Check for hash changes
        $(window).on('hashchange', function() {
            trackPageLoad('hash_change');
        });
        
        // Initial page load
        trackPageLoad(loadType);
    }
    
    /**
     * Track page load
     */
    function trackPageLoad(type) {
        var pageLoadData = {
            type: 'page_load',
            loadType: type,
            timestamp: Date.now(),
            url: window.location.href,
            title: document.title,
            performance: getPerformanceData()
        };
        
        // Send page load data
        $.post(wpTesterAjax.ajax_url, {
            action: 'wp_tester_track_interaction',
            data: JSON.stringify(pageLoadData),
            nonce: wpTesterAjax.nonce
        });
    }
    
    /**
     * Get performance data
     */
    function getPerformanceData() {
        if (!window.performance || !window.performance.timing) {
            return null;
        }
        
        var timing = window.performance.timing;
        
        return {
            loadTime: timing.loadEventEnd - timing.navigationStart,
            domReadyTime: timing.domContentLoadedEventEnd - timing.navigationStart,
            responseTime: timing.responseEnd - timing.requestStart,
            renderTime: timing.loadEventEnd - timing.domContentLoadedEventStart
        };
    }
    
    /**
     * Detect accessibility features
     */
    function detectAccessibilityFeatures() {
        var features = [];
        
        // Check for ARIA labels
        if ($('[aria-label], [aria-labelledby], [aria-describedby]').length > 0) {
            features.push('aria_labels');
        }
        
        // Check for skip links
        if ($('a[href^="#"]:contains("skip"), a[href^="#"]:contains("Skip")').length > 0) {
            features.push('skip_links');
        }
        
        // Check for alt text on images
        var imagesWithoutAlt = $('img:not([alt])').length;
        var totalImages = $('img').length;
        
        if (totalImages > 0) {
            features.push({
                type: 'image_alt_text',
                coverage: Math.round(((totalImages - imagesWithoutAlt) / totalImages) * 100)
            });
        }
        
        return features;
    }
    
    /**
     * Send data before page unload
     */
    function sendDataBeforeUnload() {
        $(window).on('beforeunload', function() {
            if (wpTester.interactions.length > 0) {
                // Use sendBeacon if available for reliable delivery
                if (navigator.sendBeacon) {
                    var data = new FormData();
                    data.append('action', 'wp_tester_track_interaction');
                    data.append('data', JSON.stringify({
                        interactions: wpTester.interactions,
                        dynamicContent: wpTester.dynamicContent,
                        final: true
                    }));
                    data.append('nonce', wpTesterAjax.nonce);
                    
                    navigator.sendBeacon(wpTesterAjax.ajax_url, data);
                } else {
                    // Fallback to synchronous AJAX
                    sendInteractionData();
                }
            }
        });
    }
    
    /**
     * Initialize everything
     */
    init();
    detectPageLoadType();
    sendDataBeforeUnload();
    
    // Send data periodically
    setInterval(function() {
        if (wpTester.interactions.length > 0) {
            sendInteractionData();
        }
    }, 30000); // Every 30 seconds
    
    // Expose API for external use
    window.wpTesterFrontend = {
        trackInteraction: trackInteraction,
        detectDynamicContent: detectDynamicContent,
        sendInteractionData: sendInteractionData,
        getInteractions: function() {
            return wpTester.interactions;
        },
        getDynamicContent: function() {
            return wpTester.dynamicContent;
        }
    };
});