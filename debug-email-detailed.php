<?php
/**
 * WP Tester Detailed Email Debug Script
 * 
 * This script provides comprehensive debugging for email delivery issues.
 * Place this file in your WordPress root directory and access it via browser.
 * 
 * IMPORTANT: Remove this file after debugging for security reasons.
 * 
 * @phpstan-ignore-file
 * @psalm-suppress UndefinedClass
 */

// Load WordPress
require_once('wp-config.php');

// Ensure WordPress is fully loaded
if (!defined('ABSPATH')) {
    die('WordPress not found. Please place this file in your WordPress root directory.');
}

// Load WordPress bootstrap
require_once(ABSPATH . 'wp-settings.php');

// Check if user is logged in and has admin privileges
if (!function_exists('is_user_logged_in') || !function_exists('current_user_can')) {
    die('WordPress functions not available. Please check your WordPress installation.');
}

$is_logged_in = false;
$can_manage = false;

if (function_exists('is_user_logged_in')) {
    $is_logged_in = call_user_func('is_user_logged_in');
}

if (function_exists('current_user_can')) {
    $can_manage = call_user_func('current_user_can', 'manage_options');
}

if (!$is_logged_in || !$can_manage) {
    die('Access denied. You must be logged in as an administrator.');
}

echo '<h1>WP Tester Detailed Email Debug</h1>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .debug-section { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa; }
    .success { border-left-color: #00a32a; }
    .error { border-left-color: #d63638; }
    .warning { border-left-color: #dba617; }
    pre { background: #f1f1f1; padding: 10px; overflow-x: auto; max-height: 300px; }
    .log-output { background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 12px; }
</style>';

// Get plugin settings
$settings = get_option('wp_tester_settings', array());

echo '<div class="debug-section">';
echo '<h2>1. Current Plugin Settings</h2>';
echo '<pre>' . print_r($settings, true) . '</pre>';
echo '</div>';

// Test email with detailed logging
if (isset($_POST['test_detailed_email'])) {
    $test_email = sanitize_email($_POST['test_email']);
    if ($test_email) {
        echo '<div class="debug-section">';
        echo '<h2>2. Detailed Email Test Results</h2>';
        
        // Clear any previous errors
        error_clear_last();
        
        // Enable error logging
        ini_set('log_errors', 1);
        ini_set('error_log', ABSPATH . 'wp-content/debug.log');
        
        echo '<h3>Test Email Configuration:</h3>';
        echo '<p><strong>To:</strong> ' . $test_email . '</p>';
        echo '<p><strong>From:</strong> ' . ($settings['from_name'] ?? 'WP Tester') . ' &lt;' . ($settings['from_email'] ?? get_option('admin_email')) . '&gt;</p>';
        echo '<p><strong>SMTP Host:</strong> ' . ($settings['smtp_host'] ?? 'Not configured') . '</p>';
        echo '<p><strong>SMTP Port:</strong> ' . ($settings['smtp_port'] ?? 'Not configured') . '</p>';
        echo '<p><strong>SMTP Encryption:</strong> ' . ($settings['smtp_encryption'] ?? 'Not configured') . '</p>';
        
        // Create test email content
        $subject = 'WP Tester Detailed Debug Test - ' . date('Y-m-d H:i:s');
        $message = '
        <html>
        <head>
            <title>WP Tester Debug Email</title>
        </head>
        <body>
            <h2>WP Tester Debug Email</h2>
            <p>This is a detailed debug test email from WP Tester.</p>
            <p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Server:</strong> ' . $_SERVER['SERVER_NAME'] . '</p>
            <p><strong>WordPress URL:</strong> ' . get_site_url() . '</p>
            <p>If you receive this email, the basic email functionality is working.</p>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($settings['from_name'] ?? 'WP Tester') . ' <' . ($settings['from_email'] ?? get_option('admin_email')) . '>',
            'Reply-To: ' . ($settings['from_email'] ?? get_option('admin_email')),
            'X-Mailer: WP Tester Debug Script'
        );
        
        echo '<h3>Attempting to send email...</h3>';
        
        // Test with SMTP if configured
        if (!empty($settings['smtp_host']) && !empty($settings['smtp_username'])) {
            echo '<p class="warning">Using SMTP configuration...</p>';
            
            // Create a test scheduler instance
            $scheduler = new WP_Tester_Scheduler();
            
            // Create test results
            $test_results = array(array(
                'flow_id' => 999,
                'flow_name' => 'Detailed Debug Test Flow',
                'status' => 'passed',
                'execution_time' => 1.0,
                'steps_passed' => 1,
                'steps_failed' => 0,
                'error_message' => null
            ));
            
            // Temporarily set the recipient
            $original_recipients = $settings['email_recipients'];
            $settings['email_recipients'] = $test_email;
            update_option('wp_tester_settings', $settings);
            
            // Send test email using the plugin's method
            $scheduler->send_test_notification($test_results, 1, 1, 0, 'detailed_debug');
            
            // Restore original recipients
            $settings['email_recipients'] = $original_recipients;
            update_option('wp_tester_settings', $settings);
            
            echo '<p class="success">✓ SMTP test email sent via plugin method</p>';
        } else {
            echo '<p class="warning">Using WordPress default mail...</p>';
            
            // Test with WordPress default mail
            $result = wp_mail($test_email, $subject, $message, $headers);
            
            if ($result) {
                echo '<p class="success">✓ WordPress mail test sent successfully</p>';
            } else {
                echo '<p class="error">✗ WordPress mail test failed</p>';
                
                // Get PHPMailer error if available
                global $phpmailer;
                if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                    echo '<p class="error">PHPMailer Error: ' . esc_html($phpmailer->ErrorInfo) . '</p>';
                }
            }
        }
        
        echo '<h3>Recent Error Logs:</h3>';
        $log_file = ABSPATH . 'wp-content/debug.log';
        if (file_exists($log_file)) {
            $logs = file_get_contents($log_file);
            $wp_tester_logs = array_filter(explode("\n", $logs), function($line) {
                return strpos($line, 'WP Tester') !== false;
            });
            
            if (!empty($wp_tester_logs)) {
                echo '<div class="log-output">' . esc_html(implode("\n", array_slice($wp_tester_logs, -20))) . '</div>';
            } else {
                echo '<p>No WP Tester logs found in debug.log</p>';
            }
        } else {
            echo '<p class="warning">Debug log file not found at: ' . $log_file . '</p>';
        }
        
        echo '</div>';
    } else {
        echo '<div class="debug-section error">';
        echo '<h2>Error</h2>';
        echo '<p>Invalid email address provided.</p>';
        echo '</div>';
    }
}

// Test SMTP connection directly
if (isset($_POST['test_smtp_connection'])) {
    echo '<div class="debug-section">';
    echo '<h2>3. Direct SMTP Connection Test</h2>';
    
    if (!empty($settings['smtp_host']) && !empty($settings['smtp_username'])) {
        echo '<p>Testing SMTP connection to: ' . $settings['smtp_host'] . ':' . ($settings['smtp_port'] ?? 587) . '</p>';
        
        // Test SMTP connection
        $smtp_host = $settings['smtp_host'];
        $smtp_port = $settings['smtp_port'] ?? 587;
        $smtp_username = $settings['smtp_username'];
        $smtp_password = $settings['smtp_password'];
        $smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
        
        $connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
        
        if ($connection) {
            echo '<p class="success">✓ SMTP connection established</p>';
            fclose($connection);
        } else {
            echo '<p class="error">✗ SMTP connection failed: ' . $errstr . ' (' . $errno . ')</p>';
        }
        
        // Test with PHPMailer directly using plugin settings
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo '<p>Testing with PHPMailer using plugin SMTP settings...</p>';
            
            if (!empty($settings['smtp_host']) && !empty($settings['smtp_username'])) {
                // @phpstan-ignore-next-line - PHPMailer class loaded dynamically
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_username'];
                    $mail->Password = $settings['smtp_password'];
                    $mail->Port = $settings['smtp_port'] ?? 587;
                    $mail->SMTPDebug = 2;
                    
                    // Set encryption based on plugin settings
                    if ($settings['smtp_encryption'] === 'ssl') {
                        $mail->SMTPSecure = 'ssl';
                    } elseif ($settings['smtp_encryption'] === 'tls') {
                        $mail->SMTPSecure = 'tls';
                    }
                    
                    $mail->setFrom($settings['from_email'] ?? get_option('admin_email'), $settings['from_name'] ?? 'WP Tester');
                    $mail->addAddress($test_email ?? 'test@example.com');
                    $mail->isHTML(true);
                    $mail->Subject = 'PHPMailer Direct Test - ' . date('Y-m-d H:i:s');
                    $mail->Body = '<h1>PHPMailer Direct Test</h1><p>This email was sent directly via PHPMailer using your plugin SMTP settings.</p>';
                    
                    $mail->send();
                    echo '<p class="success">✓ PHPMailer direct test successful</p>';
                    echo '<p><strong>SMTP Settings Used:</strong></p>';
                    echo '<ul>';
                    echo '<li>Host: ' . $settings['smtp_host'] . '</li>';
                    echo '<li>Port: ' . ($settings['smtp_port'] ?? 587) . '</li>';
                    echo '<li>Encryption: ' . ($settings['smtp_encryption'] ?? 'none') . '</li>';
                    echo '<li>Username: ' . $settings['smtp_username'] . '</li>';
                    echo '</ul>';
                    
                } catch (Exception $e) {
                    echo '<p class="error">✗ PHPMailer direct test failed: ' . $e->getMessage() . '</p>';
                }
            } else {
                echo '<p class="warning">SMTP not configured in plugin settings</p>';
            }
        }
        
    } else {
        echo '<p class="warning">SMTP not configured</p>';
    }
    
    echo '</div>';
}

echo '<div class="debug-section">';
echo '<h2>4. Email Test Form</h2>';
echo '<form method="post">';
echo '<p><label>Test Email Address: <input type="email" name="test_email" value="' . ($_POST['test_email'] ?? '') . '" required></label></p>';
echo '<p><input type="submit" name="test_detailed_email" value="Send Detailed Test Email" style="background: #00265e; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;"></p>';
echo '</form>';

if (!empty($settings['smtp_host'])) {
    echo '<form method="post">';
    echo '<p><input type="submit" name="test_smtp_connection" value="Test SMTP Connection" style="background: #d63638; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;"></p>';
    echo '</form>';
}
echo '</div>';

echo '<div class="debug-section warning">';
echo '<h2>⚠ Security Notice</h2>';
echo '<p><strong>Important:</strong> This debug script should be removed after debugging for security reasons.</p>';
echo '<p>To remove this file, delete <code>debug-email-detailed.php</code> from your WordPress root directory.</p>';
echo '</div>';
?>
