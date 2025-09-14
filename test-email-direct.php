<?php
/**
 * Direct Email Test - Bypass WordPress completely
 * This will test email sending without any WordPress interference
 * 
 * @phpstan-ignore-file
 */

// Load WordPress
require_once('wp-config.php');
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

echo '<h1>Direct Email Test</h1>';
echo '<style>body{font-family:Arial,sans-serif;margin:20px;} .result{background:#f0f0f0;padding:15px;margin:10px 0;border-left:4px solid #0073aa;} .success{border-left-color:#00a32a;} .error{border-left-color:#d63638;}</style>';

if (isset($_POST['test_direct'])) {
    $to = sanitize_email($_POST['email']);
    $from_email = sanitize_email($_POST['from_email']);
    $from_name = sanitize_text_field($_POST['from_name']);
    
    echo '<div class="result">';
    echo '<h2>Testing Direct Email Sending</h2>';
    echo '<p><strong>To:</strong> ' . $to . '</p>';
    echo '<p><strong>From:</strong> ' . $from_name . ' &lt;' . $from_email . '&gt;</p>';
    
    // Test 1: PHP mail() function
    echo '<h3>Test 1: PHP mail() function</h3>';
    $subject1 = 'Direct PHP mail() test - ' . date('H:i:s');
    $message1 = 'This is a direct test using PHP mail() function.';
    $headers1 = "From: $from_name <$from_email>\r\n";
    $headers1 .= "Reply-To: $from_email\r\n";
    $headers1 .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $result1 = mail($to, $subject1, $message1, $headers1);
    echo '<p>Result: ' . ($result1 ? '<span style="color:green;">SUCCESS</span>' : '<span style="color:red;">FAILED</span>') . '</p>';
    
    // Test 2: WordPress wp_mail()
    echo '<h3>Test 2: WordPress wp_mail() function</h3>';
    $subject2 = 'WordPress wp_mail() test - ' . date('H:i:s');
    $message2 = 'This is a test using WordPress wp_mail() function.';
    $headers2 = array(
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'Content-Type: text/plain; charset=UTF-8'
    );
    
    $result2 = wp_mail($to, $subject2, $message2, $headers2);
    echo '<p>Result: ' . ($result2 ? '<span style="color:green;">SUCCESS</span>' : '<span style="color:red;">FAILED</span>') . '</p>';
    
    // Test 3: PHPMailer directly with plugin settings
    echo '<h3>Test 3: PHPMailer directly with SMTP settings</h3>';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            // Get plugin settings
            $settings = get_option('wp_tester_settings', array());
            
            if (!empty($settings['smtp_host']) && !empty($settings['smtp_username'])) {
                // @phpstan-ignore-next-line - PHPMailer class loaded dynamically
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $settings['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $settings['smtp_username'];
                $mail->Password = $settings['smtp_password'];
                $mail->Port = $settings['smtp_port'] ?? 587;
                
                // Set encryption based on settings
                if ($settings['smtp_encryption'] === 'ssl') {
                    $mail->SMTPSecure = 'ssl';
                } elseif ($settings['smtp_encryption'] === 'tls') {
                    $mail->SMTPSecure = 'tls';
                }
                
                $mail->SMTPDebug = 2;
                
                $mail->setFrom($from_email, $from_name);
                $mail->addAddress($to);
                $mail->isHTML(false);
                $mail->Subject = 'PHPMailer direct test - ' . date('H:i:s');
                $mail->Body = 'This is a direct test using PHPMailer with your SMTP settings.';
                
                $mail->send();
                echo '<p>Result: <span style="color:green;">SUCCESS</span></p>';
                echo '<p><strong>SMTP Settings Used:</strong></p>';
                echo '<ul>';
                echo '<li>Host: ' . $settings['smtp_host'] . '</li>';
                echo '<li>Port: ' . ($settings['smtp_port'] ?? 587) . '</li>';
                echo '<li>Encryption: ' . ($settings['smtp_encryption'] ?? 'none') . '</li>';
                echo '<li>Username: ' . $settings['smtp_username'] . '</li>';
                echo '</ul>';
            } else {
                echo '<p class="error">SMTP not configured in plugin settings</p>';
            }
        } catch (Exception $e) {
            echo '<p>Result: <span style="color:red;">FAILED - ' . $e->getMessage() . '</span></p>';
        }
    } else {
        echo '<p>PHPMailer not available</p>';
    }
    
    echo '</div>';
}

echo '<form method="post">';
echo '<h2>Email Test Configuration</h2>';
echo '<p><label>To Email: <input type="email" name="email" value="tons2468@gmail.com" required></label></p>';
echo '<p><label>From Email: <input type="email" name="from_email" value="rubaiyat.mohammad@wedevs.com" required></label></p>';
echo '<p><label>From Name: <input type="text" name="from_name" value="WP Tester" required></label></p>';
echo '<p><input type="submit" name="test_direct" value="Run Direct Email Tests" style="background:#00265e;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;"></p>';
echo '</form>';

// Show current SMTP settings
$settings = get_option('wp_tester_settings', array());
if (!empty($settings['smtp_host'])) {
    echo '<div class="result">';
    echo '<h2>Current SMTP Settings</h2>';
    echo '<p><strong>Host:</strong> ' . $settings['smtp_host'] . '</p>';
    echo '<p><strong>Port:</strong> ' . ($settings['smtp_port'] ?? 587) . '</p>';
    echo '<p><strong>Encryption:</strong> ' . ($settings['smtp_encryption'] ?? 'none') . '</p>';
    echo '<p><strong>Username:</strong> ' . $settings['smtp_username'] . '</p>';
    echo '<p><strong>Password:</strong> ' . (empty($settings['smtp_password']) ? 'Not set' : 'Set') . '</p>';
    echo '</div>';
} else {
    echo '<div class="result error">';
    echo '<h2>SMTP Not Configured</h2>';
    echo '<p>Please configure SMTP settings in WP Tester Settings first.</p>';
    echo '</div>';
}

echo '<div class="result">';
echo '<h2>Server Information</h2>';
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
echo '<p><strong>Server:</strong> ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
echo '<p><strong>Sendmail Path:</strong> ' . ini_get('sendmail_path') . '</p>';
echo '<p><strong>SMTP:</strong> ' . (ini_get('SMTP') ?: 'Not set') . '</p>';
echo '<p><strong>smtp_port:</strong> ' . (ini_get('smtp_port') ?: 'Not set') . '</p>';
echo '</div>';

echo '<div class="result">';
echo '<h2>Common SMTP Provider Settings</h2>';
echo '<h3>Gmail</h3>';
echo '<ul>';
echo '<li><strong>Host:</strong> smtp.gmail.com</li>';
echo '<li><strong>Port:</strong> 587</li>';
echo '<li><strong>Encryption:</strong> TLS</li>';
echo '<li><strong>Username:</strong> your-email@gmail.com</li>';
echo '<li><strong>Password:</strong> App Password (not regular password)</li>';
echo '</ul>';

echo '<h3>Outlook/Hotmail</h3>';
echo '<ul>';
echo '<li><strong>Host:</strong> smtp-mail.outlook.com</li>';
echo '<li><strong>Port:</strong> 587</li>';
echo '<li><strong>Encryption:</strong> TLS</li>';
echo '<li><strong>Username:</strong> your-email@outlook.com</li>';
echo '<li><strong>Password:</strong> Your regular password</li>';
echo '</ul>';

echo '<h3>Yahoo Mail</h3>';
echo '<ul>';
echo '<li><strong>Host:</strong> smtp.mail.yahoo.com</li>';
echo '<li><strong>Port:</strong> 587</li>';
echo '<li><strong>Encryption:</strong> TLS</li>';
echo '<li><strong>Username:</strong> your-email@yahoo.com</li>';
echo '<li><strong>Password:</strong> App Password</li>';
echo '</ul>';

echo '<h3>Custom SMTP (cPanel, etc.)</h3>';
echo '<ul>';
echo '<li><strong>Host:</strong> mail.yourdomain.com</li>';
echo '<li><strong>Port:</strong> 587 (TLS) or 465 (SSL)</li>';
echo '<li><strong>Encryption:</strong> TLS or SSL</li>';
echo '<li><strong>Username:</strong> your-email@yourdomain.com</li>';
echo '<li><strong>Password:</strong> Your email password</li>';
echo '</ul>';
echo '</div>';
?>
