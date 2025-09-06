<?php
/**
 * Admin Crawl Results Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wp-tester-modern">
    <!-- Modern Header with Logo -->
    <div class="wp-tester-modern-header glass-nav">
        <div class="header-content">
            <div class="logo-section">
                <img src="<?php echo esc_url(WP_TESTER_PLUGIN_URL . 'assets/images/wp-tester-logo.png'); ?>" alt="WP Tester Logo" class="logo" />
                <div class="title-section">
                    <h1><?php _e('Crawl Results', 'wp-tester'); ?></h1>
                    <div class="subtitle"><?php _e('View your website crawl results', 'wp-tester'); ?></div>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-tester'); ?>" class="btn btn-outline btn-sm">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php _e('Back to Dashboard', 'wp-tester'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="wp-tester-content">
    
    <div class="wp-tester-crawl-results">
        <?php if (!empty($crawl_results)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('URL', 'wp-tester'); ?></th>
                        <th><?php _e('Page Type', 'wp-tester'); ?></th>
                        <th><?php _e('Title', 'wp-tester'); ?></th>
                        <th><?php _e('Last Crawled', 'wp-tester'); ?></th>
                        <th><?php _e('Status', 'wp-tester'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($crawl_results as $result): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($result->url); ?>" target="_blank">
                                    <?php echo esc_html($result->url); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($result->page_type); ?></td>
                            <td><?php echo esc_html($result->title); ?></td>
                            <td><?php echo esc_html($result->last_crawled); ?></td>
                            <td><?php echo esc_html($result->status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No crawl results found. Run a crawl to see results here.', 'wp-tester'); ?></p>
        <?php endif; ?>
    </div>
</div>
    </div> <!-- wp-tester-content -->
</div> <!-- wp-tester-modern -->
