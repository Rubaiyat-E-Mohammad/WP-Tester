<?php
/**
 * RAW EMAIL TEST - No WordPress, No BS
 * This will test email sending at the most basic level
 */

// Basic security check
if (!isset($_GET['test']) || $_GET['test'] !== 'email') {
    die('Add ?test=email to the URL to run this test');
}

echo '<h1>RAW EMAIL TEST - NO WORDPRESS</h1>';
echo '<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>';

// Test 1: Check if mail function exists
echo '<h2>1. Basic PHP Mail Check</h2>';
if (function_exists('mail')) {
    echo '<p class="success">✓ PHP mail() function exists</p>';
} else {
    echo '<p class="error">✗ PHP mail() function does NOT exist</p>';
    die('Your server does not support email sending');
}

// Test 2: Check server configuration
echo '<h2>2. Server Configuration</h2>';
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
echo '<p><strong>Server:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</p>';
echo '<p><strong>Sendmail Path:</strong> ' . ini_get('sendmail_path') . '</p>';
echo '<p><strong>SMTP:</strong> ' . (ini_get('SMTP') ?: 'Not set') . '</p>';
echo '<p><strong>smtp_port:</strong> ' . (ini_get('smtp_port') ?: 'Not set') . '</p>';

// Test 3: Try to send a basic email
echo '<h2>3. Basic Email Test</h2>';

$to = 'tons2468@gmail.com';
$subject = 'RAW PHP MAIL TEST - ' . date('H:i:s');
$message = 'This is a RAW PHP mail test. If you get this, basic email works.';
$headers = "From: test@example.com\r\n";
$headers .= "Reply-To: test@example.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

echo '<p><strong>To:</strong> ' . $to . '</p>';
echo '<p><strong>Subject:</strong> ' . $subject . '</p>';
echo '<p><strong>Headers:</strong> ' . htmlspecialchars($headers) . '</p>';

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo '<p class="success">✓ PHP mail() returned TRUE</p>';
    echo '<p class="info">Check your email now! If you don\'t receive it, the issue is with your server\'s mail configuration.</p>';
} else {
    echo '<p class="error">✗ PHP mail() returned FALSE</p>';
    echo '<p class="error">Your server cannot send emails at all!</p>';
}

// Test 4: Check error logs
echo '<h2>4. Error Information</h2>';
$last_error = error_get_last();
if ($last_error) {
    echo '<p class="error"><strong>Last PHP Error:</strong> ' . $last_error['message'] . '</p>';
} else {
    echo '<p class="info">No PHP errors detected</p>';
}

// Test 5: Check if we can connect to Gmail SMTP directly
echo '<h2>5. Direct SMTP Connection Test</h2>';
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;

echo '<p>Testing connection to ' . $smtp_host . ':' . $smtp_port . '</p>';

$connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);

if ($connection) {
    echo '<p class="success">✓ Can connect to Gmail SMTP</p>';
    fclose($connection);
} else {
    echo '<p class="error">✗ Cannot connect to Gmail SMTP: ' . $errstr . ' (' . $errno . ')</p>';
    echo '<p class="error">Your hosting provider might be blocking SMTP connections!</p>';
}

// Test 6: Try different SMTP ports
echo '<h2>6. SMTP Port Test</h2>';
$ports = [25, 465, 587, 2525];
foreach ($ports as $port) {
    $test_conn = @fsockopen('smtp.gmail.com', $port, $errno, $errstr, 5);
    if ($test_conn) {
        echo '<p class="success">✓ Port ' . $port . ' is open</p>';
        fclose($test_conn);
    } else {
        echo '<p class="error">✗ Port ' . $port . ' is blocked: ' . $errstr . '</p>';
    }
}

echo '<h2>7. DIAGNOSIS</h2>';
if (!$result) {
    echo '<p class="error"><strong>PROBLEM:</strong> Your server cannot send emails at all. Contact your hosting provider.</p>';
} else {
    echo '<p class="info"><strong>STATUS:</strong> PHP mail() works, but emails might not be delivered due to:</p>';
    echo '<ul>';
    echo '<li>Server mail configuration issues</li>';
    echo '<li>Spam filtering</li>';
    echo '<li>Missing SPF/DKIM records</li>';
    echo '<li>Hosting provider restrictions</li>';
    echo '</ul>';
}

echo '<h2>8. NEXT STEPS</h2>';
echo '<p>1. Check your email inbox and spam folder</p>';
echo '<p>2. If no email received, contact your hosting provider</p>';
echo '<p>3. Ask them about mail() function and SMTP restrictions</p>';
echo '<p>4. Consider using a dedicated email service like SendGrid or Mailgun</p>';

echo '<p><strong>Test completed at:</strong> ' . date('Y-m-d H:i:s') . '</p>';
?>
