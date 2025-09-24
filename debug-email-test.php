<?php
/**
 * WP Tester Email Debug Script
 * Run this script to test email functionality
 */

// Include WordPress if not already loaded
if (!defined('ABSPATH')) {
    // Adjust path as needed for your WordPress installation
    $wp_load_path = '../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. Please adjust the path to wp-load.php');
    }
}

// Check if WP Tester plugin is active
if (!class_exists('WP_Tester_Scheduler')) {
    die('WP Tester plugin not found or not active');
}

echo '<h1>WP Tester Email Debug Tool</h1>';

// Get current settings
$settings = get_option('wp_tester_settings', array());

echo '<h2>Current Email Settings</h2>';
echo '<table border="1" cellpadding="5">';
echo '<tr><td><strong>Email Notifications</strong></td><td>' . (($settings['email_notifications'] ?? false) ? 'Enabled' : 'Disabled') . '</td></tr>';
echo '<tr><td><strong>Email Recipients</strong></td><td>' . esc_html($settings['email_recipients'] ?? 'Not set') . '</td></tr>';
echo '<tr><td><strong>SMTP Host</strong></td><td>' . esc_html($settings['smtp_host'] ?? 'Not set') . '</td></tr>';
echo '<tr><td><strong>SMTP Port</strong></td><td>' . esc_html($settings['smtp_port'] ?? 'Not set') . '</td></tr>';
echo '<tr><td><strong>SMTP Username</strong></td><td>' . esc_html($settings['smtp_username'] ?? 'Not set') . '</td></tr>';
echo '<tr><td><strong>SMTP Password</strong></td><td>' . ((!empty($settings['smtp_password'])) ? '***SET***' : 'Not set') . '</td></tr>';
echo '<tr><td><strong>SMTP Encryption</strong></td><td>' . esc_html($settings['smtp_encryption'] ?? 'Not set') . '</td></tr>';
echo '<tr><td><strong>From Email</strong></td><td>' . esc_html($settings['from_email'] ?? get_option('admin_email')) . '</td></tr>';
echo '<tr><td><strong>From Name</strong></td><td>' . esc_html($settings['from_name'] ?? 'WP Tester') . '</td></tr>';
echo '</table>';

// Test email functionality
if (isset($_POST['test_email'])) {
    echo '<h2>Test Email Results</h2>';
    
    $test_email = sanitize_email($_POST['test_email']);
    if ($test_email && is_email($test_email)) {
        
        // Create scheduler instance
        $scheduler = new WP_Tester_Scheduler();
        
        // Temporarily override recipients for test
        $original_recipients = $settings['email_recipients'] ?? '';
        $settings['email_recipients'] = $test_email;
        update_option('wp_tester_settings', $settings);
        
        // Try to send test email
        echo '<p><strong>Sending test email to:</strong> ' . esc_html($test_email) . '</p>';
        
        $result = $scheduler->test_email();
        
        if ($result) {
            echo '<p style="color: green;"><strong>✓ Test email sent successfully!</strong></p>';
            echo '<p>Check your inbox and spam folder.</p>';
        } else {
            echo '<p style="color: red;"><strong>✗ Test email failed!</strong></p>';
            echo '<p>Check the WordPress error logs for detailed error messages.</p>';
        }
        
        // Restore original recipients
        $settings['email_recipients'] = $original_recipients;
        update_option('wp_tester_settings', $settings);
        
    } else {
        echo '<p style="color: red;"><strong>Invalid email address provided!</strong></p>';
    }
}

// Simple PHP mail test
if (isset($_POST['php_mail_test'])) {
    echo '<h2>PHP Mail Test Results</h2>';
    
    $test_email = sanitize_email($_POST['test_email_php']);
    if ($test_email && is_email($test_email)) {
        
        $subject = 'PHP Mail Test from WP Tester';
        $message = 'This is a test email sent using PHP\'s mail() function.';
        $headers = 'From: ' . get_option('admin_email') . "\r\n";
        $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        
        $result = mail($test_email, $subject, $message, $headers);
        
        if ($result) {
            echo '<p style="color: green;"><strong>✓ PHP mail test successful!</strong></p>';
        } else {
            echo '<p style="color: red;"><strong>✗ PHP mail test failed!</strong></p>';
            echo '<p>Your server may not be configured for sending emails.</p>';
        }
        
    } else {
        echo '<p style="color: red;"><strong>Invalid email address provided!</strong></p>';
    }
}

// WordPress wp_mail test
if (isset($_POST['wp_mail_test'])) {
    echo '<h2>WordPress wp_mail Test Results</h2>';
    
    $test_email = sanitize_email($_POST['test_email_wp']);
    if ($test_email && is_email($test_email)) {
        
        $subject = 'WordPress wp_mail Test from WP Tester';
        $message = 'This is a test email sent using WordPress wp_mail() function.';
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: WP Tester <' . get_option('admin_email') . '>'
        );
        
        $result = wp_mail($test_email, $subject, $message, $headers);
        
        if ($result) {
            echo '<p style="color: green;"><strong>✓ WordPress wp_mail test successful!</strong></p>';
        } else {
            echo '<p style="color: red;"><strong>✗ WordPress wp_mail test failed!</strong></p>';
            
            // Get any wp_mail errors
            global $phpmailer;
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                echo '<p><strong>PHPMailer Error:</strong> ' . esc_html($phpmailer->ErrorInfo) . '</p>';
            }
        }
        
    } else {
        echo '<p style="color: red;"><strong>Invalid email address provided!</strong></p>';
    }
}
?>

<h2>Email Tests</h2>

<h3>WP Tester Email Test</h3>
<form method="post">
    <p>
        <label>Test Email Address: 
            <input type="email" name="test_email" required style="width: 300px;">
        </label>
    </p>
    <p>
        <input type="submit" value="Send WP Tester Test Email" style="background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer;">
    </p>
</form>

<h3>PHP Mail Test</h3>
<form method="post">
    <p>
        <label>Test Email Address: 
            <input type="email" name="test_email_php" required style="width: 300px;">
        </label>
    </p>
    <p>
        <input type="submit" name="php_mail_test" value="Send PHP Mail Test" style="background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;">
    </p>
</form>

<h3>WordPress wp_mail Test</h3>
<form method="post">
    <p>
        <label>Test Email Address: 
            <input type="email" name="test_email_wp" required style="width: 300px;">
        </label>
    </p>
    <p>
        <input type="submit" name="wp_mail_test" value="Send WordPress wp_mail Test" style="background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer;">
    </p>
</form>

<h2>SMTP Provider Specific Instructions</h2>

<h3>Gmail Setup</h3>
<ul>
    <li><strong>Host:</strong> smtp.gmail.com</li>
    <li><strong>Port:</strong> 587 (TLS) or 465 (SSL)</li>
    <li><strong>Encryption:</strong> TLS or SSL</li>
    <li><strong>Authentication:</strong> Use App Password (not regular password)</li>
    <li><strong>Generate App Password:</strong> Google Account > Security > 2-Step Verification > App passwords</li>
</ul>

<h3>Outlook/Hotmail Setup</h3>
<ul>
    <li><strong>Host:</strong> smtp-mail.outlook.com</li>
    <li><strong>Port:</strong> 587</li>
    <li><strong>Encryption:</strong> TLS</li>
    <li><strong>Authentication:</strong> Your regular email and password</li>
</ul>

<h3>Yahoo Mail Setup</h3>
<ul>
    <li><strong>Host:</strong> smtp.mail.yahoo.com</li>
    <li><strong>Port:</strong> 587 or 465</li>
    <li><strong>Encryption:</strong> TLS or SSL</li>
    <li><strong>Authentication:</strong> Use App Password</li>
</ul>

<h2>Troubleshooting Tips</h2>
<ul>
    <li><strong>Check WordPress Error Logs:</strong> Look in wp-content/debug.log for detailed error messages</li>
    <li><strong>SMTP Settings:</strong> Make sure SMTP host, username, password, and port are correctly configured</li>
    <li><strong>Gmail Users:</strong> Use App Passwords instead of regular passwords for SMTP authentication</li>
    <li><strong>Firewall/Hosting:</strong> Some hosting providers block outgoing email. Contact your host if needed</li>
    <li><strong>Email Recipients:</strong> Make sure email notifications are enabled and recipients are configured in WP Tester settings</li>
    <li><strong>SSL Certificate Issues:</strong> If getting SSL errors, check your hosting provider's PHP/OpenSSL configuration</li>
    <li><strong>Port Blocking:</strong> Some hosts block ports 25, 465, 587. Check with your hosting provider</li>
</ul>
