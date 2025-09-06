<?php
/**
 * Admin Crawl Results Template - Modern UI
 */

if (!defined('ABSPATH')) {
    exit;
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
                    <h1>Crawl Results</h1>
                    <p class="subtitle">Site crawling and discovery results</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="modern-btn modern-btn-secondary modern-btn-small">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Dashboard
                </a>
                <button class="modern-btn modern-btn-primary modern-btn-small" id="start-crawl">
                    <span class="dashicons dashicons-search"></span>
                    Start Crawl
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="wp-tester-content">
        
        <!-- Crawl Stats Overview -->
        <div class="modern-grid grid-4">
            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Pages Crawled</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-page"></span>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($crawl_results ?? []); ?></div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-search"></span>
                    Discovered
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Forms Found</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-feedback"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $forms_count = 0;
                    if (!empty($crawl_results)) {
                        foreach ($crawl_results as $result) {
                            $forms_count += ($result['forms_found'] ?? 0);
                        }
                    }
                    echo $forms_count;
                    ?>
                </div>
                <div class="stat-change positive">
                    <span class="dashicons dashicons-feedback"></span>
                    Interactive
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Links Found</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-links"></span>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $links_count = 0;
                    if (!empty($crawl_results)) {
                        foreach ($crawl_results as $result) {
                            $links_count += ($result['links_found'] ?? 0);
                        }
                    }
                    echo $links_count;
                    ?>
                </div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-admin-links"></span>
                    Navigation
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <h3 class="stat-label">Last Crawl</h3>
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                </div>
                <div class="stat-value" style="font-size: 1.25rem;">
                    <?php
                    $last_crawl = 'Never';
                    if (!empty($crawl_results)) {
                        $latest = array_slice($crawl_results, 0, 1);
                        if (!empty($latest[0]['crawled_at'])) {
                            $last_crawl = date('M j', strtotime($latest[0]['crawled_at']));
                        }
                    }
                    echo esc_html($last_crawl);
                    ?>
                </div>
                <div class="stat-change neutral">
                    <span class="dashicons dashicons-clock"></span>
                    Timestamp
                </div>
            </div>
        </div>

        <!-- Crawl Results List -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Crawled Pages</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <select id="filter-page-type" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; background: white; font-size: 0.8125rem;">
                        <option value="">All Types</option>
                        <option value="page">Pages</option>
                        <option value="post">Posts</option>
                        <option value="product">Products</option>
                        <option value="category">Categories</option>
                    </select>
                    <button class="modern-btn modern-btn-secondary modern-btn-small" id="export-crawl">
                        <span class="dashicons dashicons-download"></span>
                        Export
                    </button>
                </div>
            </div>

            <?php if (!empty($crawl_results)) : ?>
                <div class="modern-list" id="crawl-list">
                    <?php foreach ($crawl_results as $result) : ?>
                        <div class="modern-list-item" data-page-type="<?php echo esc_attr($result['page_type'] ?? ''); ?>">
                            <div class="item-info">
                                <div class="item-icon">
                                    <span class="dashicons dashicons-<?php 
                                        $page_type = $result['page_type'] ?? 'page';
                                        $icons = [
                                            'page' => 'admin-page',
                                            'post' => 'admin-post',
                                            'product' => 'cart',
                                            'category' => 'category',
                                            'archive' => 'archive'
                                        ];
                                        echo $icons[$page_type] ?? 'admin-page';
                                    ?>"></span>
                                </div>
                                <div class="item-details">
                                    <h4>
                                        <a href="<?php echo esc_url($result['url'] ?? '#'); ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                            <?php echo esc_html($result['title'] ?? $result['url'] ?? 'Unknown Page'); ?>
                                            <span class="dashicons dashicons-external" style="font-size: 12px; margin-left: 0.25rem;"></span>
                                        </a>
                                    </h4>
                                    <p>
                                        <?php echo esc_html(ucfirst($result['page_type'] ?? 'Page')); ?> • 
                                        <?php echo esc_html(($result['forms_found'] ?? 0) . ' forms'); ?> • 
                                        <?php echo esc_html(($result['links_found'] ?? 0) . ' links'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div style="text-align: right; font-size: 0.8125rem; color: #64748b;">
                                    <div>Status: 
                                        <span class="status-badge <?php echo esc_attr($result['status'] ?? 'success'); ?>">
                                            <?php echo esc_html(ucfirst($result['status'] ?? 'Success')); ?>
                                        </span>
                                    </div>
                                    <div style="margin-top: 0.25rem;">
                                        Crawled: <?php echo esc_html($result['crawled_at'] ?? 'Unknown'); ?>
                                    </div>
                                </div>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                    <button class="modern-btn modern-btn-secondary modern-btn-small view-details" 
                                            data-url="<?php echo esc_attr($result['url'] ?? ''); ?>">
                                        View
                                    </button>
                                    <?php if (($result['forms_found'] ?? 0) > 0) : ?>
                                        <button class="modern-btn modern-btn-primary modern-btn-small create-flow" 
                                                data-url="<?php echo esc_attr($result['url'] ?? ''); ?>">
                                            Create Flow
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination placeholder -->
                <div style="margin-top: 1.5rem; text-align: center; padding: 1rem; border-top: 1px solid #f1f5f9;">
                    <button class="modern-btn modern-btn-secondary" id="load-more-crawl">
                        Load More Pages
                    </button>
                </div>

            <?php else : ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <h3>No Crawl Results</h3>
                    <p>Start your first crawl to discover pages, forms, and potential user flows on your site.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                        <button class="modern-btn modern-btn-primary" id="start-first-crawl">
                            <span class="dashicons dashicons-search"></span>
                            Start First Crawl
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Crawl Settings -->
        <div class="modern-card">
            <div class="card-header">
                <h2 class="card-title">Crawl Configuration</h2>
                <div class="status-badge info">Settings</div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: #374151;">
                        Auto Crawl Frequency
                    </h4>
                    <p style="margin: 0; font-size: 0.8125rem; color: #64748b;">
                        <?php 
                        $settings = get_option('wp_tester_settings', array());
                        echo esc_html(ucfirst($settings['crawl_frequency'] ?? 'Daily'));
                        ?>
                    </p>
                </div>
                
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: #374151;">
                        Max Pages per Crawl
                    </h4>
                    <p style="margin: 0; font-size: 0.8125rem; color: #64748b;">
                        <?php echo esc_html($settings['max_pages_per_crawl'] ?? 100); ?> pages
                    </p>
                </div>
                
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: #374151;">
                        Next Scheduled Crawl
                    </h4>
                    <p style="margin: 0; font-size: 0.8125rem; color: #64748b;">
                        <?php 
                        $next_crawl = wp_next_scheduled('wp_tester_auto_crawl');
                        echo $next_crawl ? date('M j, Y H:i', $next_crawl) : 'Not scheduled';
                        ?>
                    </p>
                </div>
                
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: #374151;">
                        Crawl Status
                    </h4>
                    <p style="margin: 0; font-size: 0.8125rem;">
                        <span class="status-badge success">Active</span>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#filter-page-type').on('change', function() {
        const selectedType = $(this).val();
        const $items = $('.modern-list-item');
        
        if (selectedType === '') {
            $items.show();
        } else {
            $items.hide();
            $items.filter('[data-page-type="' + selectedType + '"]').show();
        }
    });

    // Start crawl functionality
    $('#start-crawl, #start-first-crawl').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<div class="spinner"></div> Crawling...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_tester_start_crawl',
                nonce: '<?php echo wp_create_nonce('wp_tester_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Crawl started successfully! Results will appear here when complete.');
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    alert('Error starting crawl: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error connecting to server');
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    // View details functionality
    $('.view-details').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        if (url) {
            window.open(url, '_blank');
        }
    });

    // Create flow functionality
    $('.create-flow').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        alert('Flow creation for ' + url + ' coming soon!');
    });

    // Export crawl results
    $('#export-crawl').on('click', function(e) {
        e.preventDefault();
        alert('Crawl export feature coming soon!');
    });

    // Load more functionality
    $('#load-more-crawl').on('click', function(e) {
        e.preventDefault();
        alert('Pagination feature coming soon!');
    });
});
</script>