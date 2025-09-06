<?php
/**
 * Admin Crawl Results Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Crawl Results', 'wp-tester'); ?></h1>
    
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
