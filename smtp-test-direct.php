<?php
/**
 * DIRECT SMTP TEST - Shows exactly what happens
 */

if (!isset($_GET['test']) || $_GET['test'] !== 'smtp') {
    die('Add ?test=smtp to the URL to run this test');
}

echo '<h1>DIRECT SMTP TEST</h1>';
echo '<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f0f0f0;padding:10px;}</style>';

// SMTP settings - CHANGE THESE TO YOUR ACTUAL SETTINGS
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'rubaiyat.mohammad@wedevs.com';
$smtp_password = 'YOUR_APP_PASSWORD_HERE'; // PUT YOUR GMAIL APP PASSWORD HERE
$from_email = 'rubaiyat.mohammad@wedevs.com';
$from_name = 'WP Tester';
$to_email = 'tons2468@gmail.com';

echo '<h2>SMTP Settings</h2>';
echo '<p><strong>Host:</strong> ' . $smtp_host . '</p>';
echo '<p><strong>Port:</strong> ' . $smtp_port . '</p>';
echo '<p><strong>Username:</strong> ' . $smtp_username . '</p>';
echo '<p><strong>Password:</strong> ' . (empty($smtp_password) ? 'NOT SET - CHANGE THE PASSWORD IN THIS FILE!' : 'Set') . '</p>';

if (empty($smtp_password) || $smtp_password === 'YOUR_APP_PASSWORD_HERE') {
    echo '<p class="error">✗ Please edit this file and put your Gmail App Password in the $smtp_password variable!</p>';
    echo '<p>To get Gmail App Password:</p>';
    echo '<ol>';
    echo '<li>Go to your Google Account settings</li>';
    echo '<li>Enable 2-Factor Authentication</li>';
    echo '<li>Go to "App passwords"</li>';
    echo '<li>Generate a new app password for "Mail"</li>';
    echo '<li>Copy the 16-character password and paste it in this file</li>';
    echo '</ol>';
    die();
}

// Test SMTP connection
echo '<h2>Testing SMTP Connection</h2>';

$socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if (!$socket) {
    echo '<p class="error">✗ Cannot connect to SMTP server: ' . $errstr . ' (' . $errno . ')</p>';
    die();
}

echo '<p class="success">✓ Connected to SMTP server</p>';

// Read initial response
$response = fgets($socket, 512);
echo '<p><strong>Server Response:</strong> ' . trim($response) . '</p>';

// Send EHLO command
fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
$response = fgets($socket, 512);
echo '<p><strong>EHLO Response:</strong> ' . trim($response) . '</p>';

// Start TLS
fputs($socket, "STARTTLS\r\n");
$response = fgets($socket, 512);
echo '<p><strong>STARTTLS Response:</strong> ' . trim($response) . '</p>';

if (strpos($response, '220') === 0) {
    echo '<p class="success">✓ TLS started successfully</p>';
    
    // Enable crypto
    if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        echo '<p class="success">✓ TLS encryption enabled</p>';
        
        // Send EHLO again after TLS
        fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = fgets($socket, 512);
        echo '<p><strong>EHLO after TLS:</strong> ' . trim($response) . '</p>';
        
        // Authenticate
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        echo '<p><strong>AUTH LOGIN Response:</strong> ' . trim($response) . '</p>';
        
        if (strpos($response, '334') === 0) {
            // Send username
            fputs($socket, base64_encode($smtp_username) . "\r\n");
            $response = fgets($socket, 512);
            echo '<p><strong>Username Response:</strong> ' . trim($response) . '</p>';
            
            if (strpos($response, '334') === 0) {
                // Send password
                fputs($socket, base64_encode($smtp_password) . "\r\n");
                $response = fgets($socket, 512);
                echo '<p><strong>Password Response:</strong> ' . trim($response) . '</p>';
                
                if (strpos($response, '235') === 0) {
                    echo '<p class="success">✓ Authentication successful!</p>';
                    
                    // Send email
                    fputs($socket, "MAIL FROM:<" . $from_email . ">\r\n");
                    $response = fgets($socket, 512);
                    echo '<p><strong>MAIL FROM Response:</strong> ' . trim($response) . '</p>';
                    
                    fputs($socket, "RCPT TO:<" . $to_email . ">\r\n");
                    $response = fgets($socket, 512);
                    echo '<p><strong>RCPT TO Response:</strong> ' . trim($response) . '</p>';
                    
                    fputs($socket, "DATA\r\n");
                    $response = fgets($socket, 512);
                    echo '<p><strong>DATA Response:</strong> ' . trim($response) . '</p>';
                    
                    // Send email headers and body
                    $email_data = "From: " . $from_name . " <" . $from_email . ">\r\n";
                    $email_data .= "To: " . $to_email . "\r\n";
                    $email_data .= "Subject: DIRECT SMTP TEST - " . date('H:i:s') . "\r\n";
                    $email_data .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    $email_data .= "\r\n";
                    $email_data .= "This is a DIRECT SMTP test email.\r\n";
                    $email_data .= "If you receive this, SMTP is working!\r\n";
                    $email_data .= "\r\n";
                    $email_data .= "Test time: " . date('Y-m-d H:i:s') . "\r\n";
                    $email_data .= ".\r\n";
                    
                    fputs($socket, $email_data);
                    $response = fgets($socket, 512);
                    echo '<p><strong>Email Send Response:</strong> ' . trim($response) . '</p>';
                    
                    if (strpos($response, '250') === 0) {
                        echo '<p class="success">✓ EMAIL SENT SUCCESSFULLY!</p>';
                        echo '<p class="info">Check your inbox: ' . $to_email . '</p>';
                    } else {
                        echo '<p class="error">✗ Email sending failed</p>';
                    }
                    
                    // Quit
                    fputs($socket, "QUIT\r\n");
                    $response = fgets($socket, 512);
                    echo '<p><strong>QUIT Response:</strong> ' . trim($response) . '</p>';
                    
                } else {
                    echo '<p class="error">✗ Authentication failed - Check your username and password</p>';
                }
            } else {
                echo '<p class="error">✗ Username not accepted</p>';
            }
        } else {
            echo '<p class="error">✗ AUTH LOGIN not supported</p>';
        }
    } else {
        echo '<p class="error">✗ Failed to enable TLS encryption</p>';
    }
} else {
    echo '<p class="error">✗ STARTTLS failed</p>';
}

fclose($socket);

echo '<h2>SUMMARY</h2>';
echo '<p>This test shows you EXACTLY what happens when sending email via SMTP.</p>';
echo '<p>If you see "EMAIL SENT SUCCESSFULLY" but don\'t receive the email, the issue is:</p>';
echo '<ul>';
echo '<li>Gmail spam filtering</li>';
echo '<li>Missing SPF/DKIM records</li>';
echo '<li>Email delivery issues</li>';
echo '</ul>';
?>
