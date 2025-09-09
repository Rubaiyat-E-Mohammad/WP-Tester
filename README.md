# WP Tester - WordPress Flow Testing Plugin

WP Tester is a comprehensive WordPress plugin that automatically tests all user flows on your WordPress site and provides detailed feedback without generating coded test scripts.

## Features

### 🔍 Comprehensive Site Crawling
- Automatically discovers all pages, posts, custom post types
- Detects WooCommerce flows (shop, cart, checkout, account)
- Identifies interactive elements (forms, buttons, links, modals)
- Handles dynamic content loaded via AJAX

### 🎯 Automated Flow Testing
- Executes all discovered user flows automatically
- Simulates user interactions (clicks, form submissions, navigation)
- Retry logic for intermittent failures
- Configurable timeouts and retry attempts

### 📊 Detailed Feedback Reports
- Comprehensive test result reports with visual evidence
- Screenshot capture on failures
- Step-by-step execution logs
- Performance metrics and timing data
- Historical trend analysis

### 🎛️ User-Friendly Dashboard
- Clean admin interface for managing flows and results
- Real-time test status monitoring
- Flow health overview with success rates
- Critical issue alerts and recommendations

### ⚙️ Advanced Configuration
- Configurable crawl frequency (hourly, daily, weekly)
- Adjustable test timeouts and retry settings
- Screenshot capture settings
- Data retention management

### 🛒 WooCommerce Integration
- Specialized flows for WooCommerce stores
- Product page testing
- Shopping cart flow validation
- Checkout process verification
- Customer account functionality testing

## Installation

1. **Upload the Plugin**
   ```bash
   # Upload the wp-tester folder to your WordPress plugins directory
   wp-content/plugins/wp-tester/
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "WP Tester" and click "Activate"

3. **Initial Setup**
   - Navigate to WP Tester → Settings
   - Configure your preferred settings
   - Run your first site crawl

## Getting Started

### 1. Run Initial Crawl
After activation, run your first site crawl to discover flows:

- Go to **WP Tester → Dashboard**
- Click **"Run Site Crawl"**
- Wait for the crawl to complete

### 2. Review Discovered Flows
- Navigate to **WP Tester → Flows**
- Review automatically discovered user flows
- Edit or disable flows as needed

### 3. Execute Tests
- From the dashboard, click **"Run All Tests"**
- Or test individual flows from the Flows page
- Monitor test progress in real-time

### 4. Analyze Results
- Go to **WP Tester → Test Results**
- Click on any result to view detailed reports
- Review screenshots and failure analysis

## Configuration

### Basic Settings
Navigate to **WP Tester → Settings** to configure:

- **Crawl Frequency**: How often to crawl your site (hourly, daily, weekly)
- **Test Timeout**: Maximum time to wait for each test step
- **Retry Attempts**: Number of retries for failed steps
- **Screenshots**: Enable/disable screenshot capture on failures
- **Max Pages**: Limit pages crawled per session

### Advanced Options
- **Data Retention**: How long to keep test results
- **Test Data Cleanup**: Remove test users and content
- **System Requirements**: Check if your system meets requirements

## Flow Types

### Standard WordPress Flows
- **User Registration**: Account creation process
- **User Login**: Authentication flow
- **Contact Forms**: Form submission testing
- **Site Search**: Search functionality
- **Navigation**: Menu and link testing
- **Comment Submission**: Comment form testing

### WooCommerce Flows (if WooCommerce is active)
- **Shop Browsing**: Product catalog navigation
- **Product Views**: Individual product pages
- **Shopping Cart**: Add to cart and cart management
- **Checkout Process**: Complete purchase flow
- **Customer Account**: Account dashboard and management

## Understanding Test Results

### Status Types
- **Passed**: All steps completed successfully
- **Failed**: One or more steps failed

### Report Components
- **Execution Summary**: Overall test statistics
- **Step Details**: Individual step results with timing
- **Failure Analysis**: Root cause analysis for failures
- **Visual Evidence**: Screenshots of failures
- **Suggestions**: Recommended corrective actions
- **Performance Metrics**: Execution time and efficiency scores

## Troubleshooting

### Common Issues

1. **Tests Timing Out**
   - Increase timeout settings in WP Tester → Settings
   - Check server performance and resources
   - Verify network connectivity

2. **Elements Not Found**
   - Page structure may have changed
   - Check if elements are loaded dynamically
   - Review element selectors in flow configuration

3. **High Memory Usage**
   - Reduce max pages per crawl
   - Increase PHP memory limit
   - Run tests during low-traffic periods

4. **Scheduled Tasks Not Running**
   - Verify WordPress cron is working
   - Check WP Tester → Settings → Scheduled Tasks
   - Consider using a server cron job

### System Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **Memory**: 128MB minimum (256MB recommended)
- **Extensions**: cURL, DOM, JSON

## API and Hooks

### Available Hooks

```php
// Customize discovered flows
add_filter('wp_tester_discovered_flows', 'my_custom_flows', 10, 3);

// Modify test data
add_filter('wp_tester_test_data', 'my_test_data', 10, 2);

// Custom flow steps
add_filter('wp_tester_flow_steps', 'my_flow_steps', 10, 2);

// After test completion
add_action('wp_tester_test_completed', 'my_test_handler', 10, 2);
```

### Programmatic Usage

```php
// Run a crawl programmatically
$crawler = new WP_Tester_Crawler();
$result = $crawler->run_full_crawl();

// Execute a specific flow
$executor = new WP_Tester_Flow_Executor();
$result = $executor->execute_flow($flow_id);

// Generate a report
$reporter = new WP_Tester_Feedback_Reporter();
$report = $reporter->generate_report($test_result_id);
```

## Data Privacy

WP Tester is designed with privacy in mind:

- **Test Data**: Uses dummy data for form submissions
- **No External Requests**: All testing happens on your server
- **Local Storage**: All data stored in your WordPress database
- **Cleanup Tools**: Built-in tools to remove test data

## Performance Considerations

- Tests run in background to minimize impact
- Configurable delays between test steps
- Memory usage monitoring and optimization
- Automatic cleanup of old data

## Support and Contributing

### Getting Help
- Check the troubleshooting section above
- Review system requirements
- Enable WordPress debug logging for detailed errors

### Feature Requests
- Submit feature requests via GitHub issues
- Include detailed use cases and requirements

### Contributing
- Follow WordPress coding standards
- Include tests for new features
- Update documentation as needed

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Complete site crawling functionality
- Automated flow testing
- Comprehensive reporting system
- WooCommerce integration
- Admin dashboard interface
- Scheduled testing capabilities

---

**WP Tester** - Ensuring your WordPress site works perfectly for every user, every time.