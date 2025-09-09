<?php
/**
 * WP Tester Utility Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove directory recursively
 */
function wp_tester_remove_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? wp_tester_remove_directory($path) : unlink($path);
    }
    
    return rmdir($dir);
}

/**
 * Format file size
 */
function wp_tester_format_file_size($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Sanitize flow step data
 */
function wp_tester_sanitize_flow_steps($steps) {
    if (!is_array($steps)) {
        return array();
    }
    
    $sanitized = array();
    
    foreach ($steps as $step) {
        if (!is_array($step)) {
            continue;
        }
        
        $sanitized_step = array();
        
        if (isset($step['action'])) {
            $sanitized_step['action'] = sanitize_text_field($step['action']);
        }
        
        if (isset($step['target'])) {
            $sanitized_step['target'] = sanitize_text_field($step['target']);
        }
        
        if (isset($step['data'])) {
            $sanitized_step['data'] = sanitize_text_field($step['data']);
        }
        
        if (isset($step['expected'])) {
            $sanitized_step['expected'] = sanitize_text_field($step['expected']);
        }
        
        if (!empty($sanitized_step)) {
            $sanitized[] = $sanitized_step;
        }
    }
    
    return $sanitized;
}

/**
 * Generate test run ID
 */
function wp_tester_generate_test_run_id() {
    return 'test_' . date('Ymd_His') . '_' . wp_generate_password(8, false);
}

/**
 * Get flow type display name
 */
function wp_tester_get_flow_type_name($flow_type) {
    $flow_types = array(
        'registration' => __('User Registration', 'wp-tester'),
        'login' => __('User Login', 'wp-tester'),
        'contact' => __('Contact Form', 'wp-tester'),
        'search' => __('Site Search', 'wp-tester'),
        'newsletter' => __('Newsletter Subscription', 'wp-tester'),
        'comment' => __('Comment Submission', 'wp-tester'),
        'navigation' => __('Navigation', 'wp-tester'),
        'modal' => __('Modal Interaction', 'wp-tester'),
        'woocommerce_shop' => __('WooCommerce Shop', 'wp-tester'),
        'woocommerce_product' => __('WooCommerce Product', 'wp-tester'),
        'woocommerce_cart' => __('WooCommerce Cart', 'wp-tester'),
        'woocommerce_checkout' => __('WooCommerce Checkout', 'wp-tester'),
        'woocommerce_account' => __('WooCommerce Account', 'wp-tester'),
        'woocommerce_category' => __('WooCommerce Category', 'wp-tester')
    );
    
    return isset($flow_types[$flow_type]) ? $flow_types[$flow_type] : ucfirst(str_replace('_', ' ', $flow_type));
}

/**
 * Get status icon
 */
function wp_tester_get_status_icon($status) {
    $icons = array(
        'passed' => 'yes-alt',
        'failed' => 'dismiss',
        'running' => 'update',
        'pending' => 'clock'
    );
    
    $icon = isset($icons[$status]) ? $icons[$status] : 'marker';
    return '<span class="dashicons dashicons-' . $icon . '"></span>';
}

/**
 * Validate URL
 */
function wp_tester_validate_url($url) {
    if (empty($url)) {
        return false;
    }
    
    // Check if it's a valid URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Check if it's from the same site (security)
    $site_url = site_url();
    $url_host = wp_parse_url($url, PHP_URL_HOST);
    $site_host = wp_parse_url($site_url, PHP_URL_HOST);
    
    return $url_host === $site_host;
}

/**
 * Get memory usage
 */
function wp_tester_get_memory_usage() {
    return array(
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => wp_tester_get_memory_limit()
    );
}

/**
 * Get memory limit in bytes
 */
function wp_tester_get_memory_limit() {
    $memory_limit = ini_get('memory_limit');
    
    if ($memory_limit == -1) {
        return -1;
    }
    
    $unit = strtolower(substr($memory_limit, -1));
    $value = (int) $memory_limit;
    
    switch ($unit) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

/**
 * Check if system meets requirements
 */
function wp_tester_check_system_requirements() {
    $requirements = array();
    
    // PHP version
    $php_version = phpversion();
    $requirements['php_version'] = array(
        'current' => $php_version,
        'required' => '7.4',
        'met' => version_compare($php_version, '7.4', '>=')
    );
    
    // WordPress version
    $wp_version = get_bloginfo('version') ?: '0.0.0';
    $requirements['wp_version'] = array(
        'current' => $wp_version,
        'required' => '6.0',
        'met' => version_compare($wp_version, '6.0', '>=')
    );
    
    // Memory limit
    $memory_limit = wp_tester_get_memory_limit();
    $requirements['memory_limit'] = array(
        'current' => $memory_limit,
        'required' => 128 * 1024 * 1024, // 128MB
        'met' => $memory_limit === -1 || $memory_limit >= 128 * 1024 * 1024
    );
    
    // cURL extension
    $requirements['curl'] = array(
        'current' => extension_loaded('curl'),
        'required' => true,
        'met' => extension_loaded('curl')
    );
    
    // DOM extension
    $requirements['dom'] = array(
        'current' => extension_loaded('dom'),
        'required' => true,
        'met' => extension_loaded('dom')
    );
    
    // JSON extension
    $requirements['json'] = array(
        'current' => extension_loaded('json'),
        'required' => true,
        'met' => extension_loaded('json')
    );
    
    return $requirements;
}

/**
 * Log debug message
 */
function wp_tester_log($message, $level = 'info') {
    if (!WP_DEBUG || !WP_DEBUG_LOG) {
        return;
    }
    
    $log_message = sprintf(
        '[WP Tester] [%s] [%s] %s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );
    
    error_log($log_message);
}

/**
 * Get plugin info
 */
function wp_tester_get_plugin_info() {
    $plugin_data = get_plugin_data(WP_TESTER_PLUGIN_FILE);
    
    return array(
        'name' => $plugin_data['Name'],
        'version' => $plugin_data['Version'],
        'author' => $plugin_data['Author'],
        'description' => $plugin_data['Description'],
        'plugin_uri' => $plugin_data['PluginURI'],
        'text_domain' => $plugin_data['TextDomain'],
        'requires_wp' => $plugin_data['RequiresWP'],
        'requires_php' => $plugin_data['RequiresPHP']
    );
}

/**
 * Generate random test data
 */
function wp_tester_generate_random_data($type = 'string', $length = 10) {
    switch ($type) {
        case 'email':
            return 'test' . time() . rand(100, 999) . '@example.com';
            
        case 'username':
            return 'testuser' . time() . rand(100, 999);
            
        case 'password':
            return wp_generate_password($length, true, true);
            
        case 'phone':
            return sprintf('(%03d) %03d-%04d', rand(100, 999), rand(100, 999), rand(1000, 9999));
            
        case 'name':
            $names = array('John', 'Jane', 'Mike', 'Sarah', 'David', 'Emily', 'Chris', 'Lisa');
            return $names[array_rand($names)];
            
        case 'address':
            return rand(100, 9999) . ' Test Street';
            
        case 'city':
            $cities = array('New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia');
            return $cities[array_rand($cities)];
            
        case 'zipcode':
            return sprintf('%05d', rand(10000, 99999));
            
        default:
            return wp_generate_password($length, false);
    }
}

/**
 * Clean test data
 */
function wp_tester_clean_test_data() {
    global $wpdb;
    
    // Remove test users
    $test_users = get_users(array(
        'search' => '*testuser*',
        'search_columns' => array('user_login', 'user_email')
    ));
    
    if ($test_users) {
        foreach ($test_users as $user) {
            if (strpos($user->user_login, 'testuser') !== false) {
                wp_delete_user($user->ID);
            }
        }
    }
    
    // Remove test comments
    $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_content LIKE '%WP Tester%' OR comment_author_email LIKE '%@example.com'");
    
    // Clean comment meta
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_ID FROM {$wpdb->comments})");
}

/**
 * Get system info for debugging
 */
function wp_tester_get_system_info() {
    global $wpdb;
    
    $info = array(
        'wp_version' => get_bloginfo('version'),
        'php_version' => phpversion(),
        'mysql_version' => $wpdb->db_version(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'active_plugins' => get_option('active_plugins'),
        'active_theme' => function_exists('wp_get_theme') && ($theme = wp_get_theme()) ? $theme->get('Name') : 'Unknown',
        'site_url' => site_url(),
        'home_url' => home_url(),
        'wp_debug' => WP_DEBUG,
        'wp_debug_log' => WP_DEBUG_LOG
    );
    
    return $info;
}

/**
 * Export system info as text
 */
function wp_tester_export_system_info() {
    $info = wp_tester_get_system_info();
    $output = "WP Tester System Information\n";
    $output .= "===========================\n\n";
    
    foreach ($info as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        
        if (is_array($value)) {
            $output .= "{$label}:\n";
            foreach ($value as $item) {
                $output .= "  - {$item}\n";
            }
        } else {
            $output .= "{$label}: {$value}\n";
        }
    }
    
    return $output;
}

/**
 * Check if running in CLI mode
 */
function wp_tester_is_cli() {
    return defined('WP_CLI') && WP_CLI;
}

/**
 * Get current user IP
 */
function wp_tester_get_user_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}