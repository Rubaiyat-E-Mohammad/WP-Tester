<?php
/**
 * WP Tester WooCommerce Integration Class
 * 
 * Handles WooCommerce specific flow detection and testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Tester_WooCommerce {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $this->database = new WP_Tester_Database();
        
        // Hook into WooCommerce events
        add_action('woocommerce_product_set_stock_status', array($this, 'product_stock_changed'), 10, 3);
        add_action('woocommerce_new_product', array($this, 'new_product_added'));
        add_action('woocommerce_update_product', array($this, 'product_updated'));
        
        // Add WooCommerce specific flows
        add_filter('wp_tester_discovered_flows', array($this, 'add_woocommerce_flows'), 10, 3);
    }
    
    /**
     * Discover WooCommerce specific flows
     */
    public function discover_woocommerce_flows() {
        if (!class_exists('WooCommerce')) {
            return array();
        }
        
        $flows = array();
        
        // Shop page flow
        $shop_url = wc_get_page_permalink('shop');
        if ($shop_url) {
            $flows[] = array(
                'name' => 'Browse Shop Page',
                'type' => 'woocommerce_shop',
                'start_url' => $shop_url,
                'priority' => 7,
                'steps' => $this->generate_shop_flow_steps($shop_url)
            );
        }
        
        // Cart flow
        $cart_url = wc_get_cart_url();
        if ($cart_url) {
            $flows[] = array(
                'name' => 'Shopping Cart Flow',
                'type' => 'woocommerce_cart',
                'start_url' => $cart_url,
                'priority' => 9,
                'steps' => $this->generate_cart_flow_steps($cart_url)
            );
        }
        
        // Checkout flow
        $checkout_url = wc_get_checkout_url();
        if ($checkout_url) {
            $flows[] = array(
                'name' => 'Checkout Process',
                'type' => 'woocommerce_checkout',
                'start_url' => $checkout_url,
                'priority' => 10,
                'steps' => $this->generate_checkout_flow_steps($checkout_url)
            );
        }
        
        // My Account flows
        $account_url = wc_get_page_permalink('myaccount');
        if ($account_url) {
            $flows[] = array(
                'name' => 'My Account Dashboard',
                'type' => 'woocommerce_account',
                'start_url' => $account_url,
                'priority' => 6,
                'steps' => $this->generate_account_flow_steps($account_url)
            );
        }
        
        // Product page flows
        $product_flows = $this->discover_product_flows();
        $flows = array_merge($flows, $product_flows);
        
        // Category page flows
        $category_flows = $this->discover_category_flows();
        $flows = array_merge($flows, $category_flows);
        
        return $flows;
    }
    
    /**
     * Discover product page flows
     */
    private function discover_product_flows() {
        $flows = array();
        
        // Get sample products
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => 5,
            'orderby' => 'popularity'
        ));
        
        foreach ($products ?: [] as $product) {
            $product_url = get_permalink($product->get_id());
            
            $flows[] = array(
                'name' => 'Product View - ' . $product->get_name(),
                'type' => 'woocommerce_product',
                'start_url' => $product_url,
                'priority' => 8,
                'steps' => $this->generate_product_flow_steps($product_url, $product)
            );
        }
        
        return $flows;
    }
    
    /**
     * Discover category flows
     */
    private function discover_category_flows() {
        $flows = array();
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 5
        ));
        
        foreach ($categories ?: [] as $category) {
            $category_url = get_term_link($category);
            
            if (!is_wp_error($category_url)) {
                $flows[] = array(
                    'name' => 'Product Category - ' . $category->name,
                    'type' => 'woocommerce_category',
                    'start_url' => $category_url,
                    'priority' => 6,
                    'steps' => $this->generate_category_flow_steps($category_url)
                );
            }
        }
        
        return $flows;
    }
    
    /**
     * Generate shop flow steps
     */
    private function generate_shop_flow_steps($shop_url) {
        return array(
            array('action' => 'navigate', 'target' => $shop_url),
            array('action' => 'verify', 'target' => '.woocommerce-products-header', 'expected' => 'shop_header'),
            array('action' => 'verify', 'target' => '.products', 'expected' => 'product_grid'),
            array('action' => 'interact', 'target' => '.woocommerce-ordering'),
            array('action' => 'interact', 'target' => '.woocommerce-pagination'),
            array('action' => 'click', 'target' => '.product:first-child a'),
            array('action' => 'verify', 'target' => '.product', 'expected' => 'product_page')
        );
    }
    
    /**
     * Generate cart flow steps
     */
    private function generate_cart_flow_steps($cart_url) {
        return array(
            array('action' => 'navigate', 'target' => $cart_url),
            array('action' => 'verify', 'target' => '.woocommerce-cart-form', 'expected' => 'cart_form'),
            array('action' => 'interact', 'target' => '.qty'),
            array('action' => 'click', 'target' => '[name="update_cart"]'),
            array('action' => 'verify', 'target' => '.cart-subtotal', 'expected' => 'updated_total'),
            array('action' => 'click', 'target' => '.checkout-button'),
            array('action' => 'verify', 'target' => '.woocommerce-checkout', 'expected' => 'checkout_page')
        );
    }
    
    /**
     * Generate checkout flow steps
     */
    private function generate_checkout_flow_steps($checkout_url) {
        return array(
            array('action' => 'navigate', 'target' => $checkout_url),
            array('action' => 'verify', 'target' => '.woocommerce-checkout', 'expected' => 'checkout_form'),
            array('action' => 'fill_form', 'target' => 'checkout_form', 'data' => 'checkout_data'),
            array('action' => 'verify', 'target' => '.woocommerce-checkout-review-order', 'expected' => 'order_review'),
            array('action' => 'interact', 'target' => '.payment_methods'),
            array('action' => 'verify', 'target' => '#place_order', 'expected' => 'place_order_button')
            // Note: We don't actually place the order in testing
        );
    }
    
    /**
     * Generate account flow steps
     */
    private function generate_account_flow_steps($account_url) {
        return array(
            array('action' => 'navigate', 'target' => $account_url),
            array('action' => 'verify', 'target' => '.woocommerce-MyAccount-navigation', 'expected' => 'account_nav'),
            array('action' => 'click', 'target' => '.woocommerce-MyAccount-navigation-link--orders a'),
            array('action' => 'verify', 'target' => '.woocommerce-orders-table', 'expected' => 'orders_table'),
            array('action' => 'click', 'target' => '.woocommerce-MyAccount-navigation-link--edit-account a'),
            array('action' => 'verify', 'target' => '.woocommerce-EditAccountForm', 'expected' => 'edit_account_form')
        );
    }
    
    /**
     * Generate product flow steps
     */
    private function generate_product_flow_steps($product_url, $product) {
        $steps = array(
            array('action' => 'navigate', 'target' => $product_url),
            array('action' => 'verify', 'target' => '.product', 'expected' => 'product_page'),
            array('action' => 'verify', 'target' => '.product_title', 'expected' => 'product_title'),
            array('action' => 'verify', 'target' => '.price', 'expected' => 'product_price')
        );
        
        // Add variable product interactions
        if ($product->is_type('variable')) {
            $steps[] = array('action' => 'interact', 'target' => '.variations select');
            $steps[] = array('action' => 'verify', 'target' => '.single_variation', 'expected' => 'variation_selected');
        }
        
        // Add to cart if product is purchasable
        if ($product->is_purchasable() && $product->is_in_stock()) {
            $steps[] = array('action' => 'click', 'target' => '.single_add_to_cart_button');
            $steps[] = array('action' => 'verify', 'target' => '.woocommerce-message', 'expected' => 'added_to_cart');
        }
        
        return $steps;
    }
    
    /**
     * Generate category flow steps
     */
    private function generate_category_flow_steps($category_url) {
        return array(
            array('action' => 'navigate', 'target' => $category_url),
            array('action' => 'verify', 'target' => '.woocommerce-products-header', 'expected' => 'category_header'),
            array('action' => 'verify', 'target' => '.products', 'expected' => 'product_grid'),
            array('action' => 'interact', 'target' => '.woocommerce-ordering'),
            array('action' => 'click', 'target' => '.product:first-child a'),
            array('action' => 'verify', 'target' => '.product', 'expected' => 'product_page')
        );
    }
    
    /**
     * Add WooCommerce flows to discovered flows
     */
    public function add_woocommerce_flows($flows, $url, $page_type) {
        $wc_flows = $this->discover_woocommerce_flows();
        return array_merge($flows, $wc_flows);
    }
    
    /**
     * Handle product stock changes
     */
    public function product_stock_changed($product_id, $stock_status, $product) {
        // Check if auto-testing is enabled
        $settings = get_option('wp_tester_settings', array());
        $auto_test = $settings['auto_run_tests_on_flow_creation'] ?? false;
        
        if (!$auto_test) {
            return;
        }
        
        // Trigger re-testing of product flows when stock changes
        $this->schedule_product_flow_retest($product_id);
    }
    
    /**
     * Handle new product addition
     */
    public function new_product_added($product_id) {
        // Check if auto-generation is enabled
        $settings = get_option('wp_tester_settings', array());
        $auto_generate = $settings['auto_generate_flows_on_crawl'] ?? false;
        
        if (!$auto_generate) {
            return;
        }
        
        // Create new flow for the product
        $product = wc_get_product($product_id);
        if ($product && $product->is_published()) {
            $this->create_product_flow($product);
        }
    }
    
    /**
     * Handle product updates
     */
    public function product_updated($product_id) {
        // Check if auto-generation is enabled
        $settings = get_option('wp_tester_settings', array());
        $auto_generate = $settings['auto_generate_flows_on_crawl'] ?? false;
        
        if (!$auto_generate) {
            return;
        }
        
        // Update existing flow or create new one
        $product = wc_get_product($product_id);
        if ($product && $product->is_published()) {
            $this->update_product_flow($product);
        }
    }
    
    /**
     * Create flow for new product
     */
    private function create_product_flow($product) {
        $product_url = get_permalink($product->get_id());
        $steps = $this->generate_product_flow_steps($product_url, $product);
        
        $this->database->save_flow(
            'Product View - ' . $product->get_name(),
            'woocommerce_product',
            $product_url,
            $steps,
            'User can view product details and add to cart',
            8
        );
    }
    
    /**
     * Update existing product flow
     */
    private function update_product_flow($product) {
        // Find existing flow for this product
        global $wpdb;
        
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        $product_url = get_permalink($product->get_id());
        
        $existing_flow = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$flows_table} WHERE start_url = %s AND flow_type = 'woocommerce_product'",
            $product_url
        ));
        
        if ($existing_flow) {
            $steps = $this->generate_product_flow_steps($product_url, $product);
            
            $wpdb->update(
                $flows_table,
                array(
                    'flow_name' => 'Product View - ' . $product->get_name(),
                    'steps' => wp_json_encode($steps),
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing_flow->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        } else {
            $this->create_product_flow($product);
        }
    }
    
    /**
     * Schedule product flow retest
     */
    private function schedule_product_flow_retest($product_id) {
        $scheduler = new WP_Tester_Scheduler();
        
        // Get flow ID for this product
        global $wpdb;
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        $product_url = get_permalink($product_id);
        
        $flow = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$flows_table} WHERE start_url = %s AND flow_type = 'woocommerce_product'",
            $product_url
        ));
        
        if ($flow) {
            $scheduler->schedule_immediate_test_run(array($flow->id));
        }
    }
    
    /**
     * Get WooCommerce test data
     */
    public function get_woocommerce_test_data($data_type) {
        switch ($data_type) {
            case 'checkout_data':
                return array(
                    'billing_first_name' => 'Test',
                    'billing_last_name' => 'Customer',
                    'billing_email' => 'test@example.com',
                    'billing_phone' => '1234567890',
                    'billing_address_1' => '123 Test Street',
                    'billing_city' => 'Test City',
                    'billing_postcode' => '12345',
                    'billing_country' => 'US',
                    'billing_state' => 'CA'
                );
                
            case 'customer_data':
                return array(
                    'username' => 'testcustomer_' . time(),
                    'email' => 'testcustomer' . time() . '@example.com',
                    'password' => 'TestPassword123!',
                    'first_name' => 'Test',
                    'last_name' => 'Customer'
                );
                
            default:
                return array();
        }
    }
    
    /**
     * Validate WooCommerce setup
     */
    public function validate_woocommerce_setup() {
        $issues = array();
        
        if (!class_exists('WooCommerce')) {
            $issues[] = 'WooCommerce is not installed or activated';
            return $issues;
        }
        
        // Check required pages
        $required_pages = array(
            'shop' => wc_get_page_permalink('shop'),
            'cart' => wc_get_cart_url(),
            'checkout' => wc_get_checkout_url(),
            'myaccount' => wc_get_page_permalink('myaccount')
        );
        
        foreach ($required_pages as $page_name => $page_url) {
            if (!$page_url || $page_url === '') {
                $issues[] = "WooCommerce {$page_name} page is not configured";
            }
        }
        
        // Check if there are products
        $product_count = wp_count_posts('product');
        $product_count = $product_count ? $product_count->publish : 0;
        if ($product_count === 0) {
            $issues[] = 'No published products found';
        }
        
        // Check payment methods
        $payment_gateways = array();
        if (function_exists('WC') && ($wc = WC()) && method_exists($wc, 'payment_gateways') && ($gateways = $wc->payment_gateways())) {
            $payment_gateways = $gateways->get_available_payment_gateways();
        }
        if (empty($payment_gateways)) {
            $issues[] = 'No payment methods are enabled';
        }
        
        return $issues;
    }
    
    /**
     * Get WooCommerce specific metrics
     */
    public function get_woocommerce_metrics() {
        global $wpdb;
        
        $results_table = $wpdb->prefix . 'wp_tester_test_results';
        $flows_table = $wpdb->prefix . 'wp_tester_flows';
        
        $metrics = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_wc_tests,
                SUM(CASE WHEN tr.status = 'passed' THEN 1 ELSE 0 END) as passed_wc_tests,
                AVG(tr.execution_time) as avg_execution_time
            FROM {$results_table} tr
            JOIN {$flows_table} f ON tr.flow_id = f.id
            WHERE f.flow_type LIKE 'woocommerce_%'
            AND tr.started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        return array(
            'total_tests' => $metrics->total_wc_tests ?: 0,
            'success_rate' => $metrics->total_wc_tests > 0 
                ? round(($metrics->passed_wc_tests / $metrics->total_wc_tests) * 100, 1) 
                : 0,
            'avg_execution_time' => round($metrics->avg_execution_time ?: 0, 2)
        );
    }
}