<?php
/**
 * Email Testing Script for Box2PK
 * Upload this to: /wp-content/plugins/shipbox/test-email.php
 * Access: https://box2pk.com/wp-content/plugins/shipbox/test-email.php
 */

require_once('../../../wp-load.php');

// CHANGE THIS TO YOUR EMAIL
$test_email = 'kamrannazir010@gmail.com';

echo '<html><head><title>Box2PK Email Test</title></head><body>';
echo '<h1>Box2PK Email Test</h1>';
echo '<hr>';

// Test 1: Basic wp_mail()
echo '<h2>Test 1: Basic wp_mail()</h2>';
$subject = 'Test Email from Box2PK - ' . date('Y-m-d H:i:s');
$body = '<h1>Test Email</h1><p>If you receive this, wp_mail() is working!</p>';
$headers = array('Content-Type: text/html; charset=UTF-8');

$sent = wp_mail($test_email, $subject, $body, $headers);

if ($sent) {
    echo '<p style="color: green;">✅ Email sent successfully to: ' . esc_html($test_email) . '</p>';
} else {
    echo '<p style="color: red;">❌ Email failed to send</p>';
}

// Check for errors
global $phpmailer;
if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
    echo '<p style="color: red;"><strong>Error:</strong> ' . esc_html($phpmailer->ErrorInfo) . '</p>';
}

echo '<hr>';

// Test 2: Check SMTP Plugin
echo '<h2>Test 2: SMTP Plugin Check</h2>';

if (function_exists('wp_mail_smtp')) {
    echo '<p style="color: green;">✅ WP Mail SMTP plugin is active</p>';
    
    // Get WP Mail SMTP settings
    $options = get_option('wp_mail_smtp', []);
    if (!empty($options)) {
        echo '<p><strong>SMTP Settings:</strong></p>';
        echo '<ul>';
        if (isset($options['mail']['mailer'])) {
            echo '<li>Mailer: ' . esc_html($options['mail']['mailer']) . '</li>';
        }
        if (isset($options['mail']['from_email'])) {
            echo '<li>From Email: ' . esc_html($options['mail']['from_email']) . '</li>';
        }
        if (isset($options['mail']['from_name'])) {
            echo '<li>From Name: ' . esc_html($options['mail']['from_name']) . '</li>';
        }
        echo '</ul>';
    }
} else {
    echo '<p style="color: orange;">⚠️ WP Mail SMTP plugin not detected</p>';
    echo '<p>Install it from: <a href="' . admin_url('plugin-install.php?s=wp+mail+smtp&tab=search') . '">WordPress Plugins</a></p>';
}

echo '<hr>';

// Test 3: ShipBox Email Service
echo '<h2>Test 3: ShipBox Email Service</h2>';

if (class_exists('ShipBox_Email_Service')) {
    echo '<p style="color: green;">✅ ShipBox_Email_Service class found</p>';
    
    // Create a mock order object
    global $wpdb;
    $mock_order = (object) [
        'id' => 999,
        'user_email' => $test_email,
        'display_customer_id' => 'TEST123',
        'customer_id' => 999,
        'display_name' => 'Test User',
        'order_number' => 'ORD-TEST-999',
        'is_consolidated' => 0,
        'final_price' => 5000
    ];
    
    $warehouse = "Test Warehouse\n123 Main St\nCity, Country";
    
    echo '<p>Attempting to send test email via ShipBox Email Service...</p>';
    $result = ShipBox_Email_Service::send_status_change_email($mock_order, 'address_confirmed', $warehouse);
    
    if ($result) {
        echo '<p style="color: green;">✅ ShipBox email sent successfully!</p>';
    } else {
        echo '<p style="color: red;">❌ ShipBox email failed</p>';
    }
} else {
    echo '<p style="color: red;">❌ ShipBox_Email_Service class not found</p>';
    echo '<p>Make sure the email service file is loaded in your plugin.</p>';
}

echo '<hr>';

// Test 4: Check Email Logs
echo '<h2>Test 4: Email Logs</h2>';

$table_name = $wpdb->prefix . 'shipbox_email_logs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo '<p style="color: green;">✅ Email logs table exists</p>';
    
    $recent_logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 5");
    
    if ($recent_logs) {
        echo '<p><strong>Recent Email Logs:</strong></p>';
        echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        echo '<tr><th>ID</th><th>Email To</th><th>Subject</th><th>Status</th><th>Sent At</th></tr>';
        foreach ($recent_logs as $log) {
            $status_color = ($log->status === 'sent') ? 'green' : 'red';
            echo '<tr>';
            echo '<td>' . esc_html($log->id) . '</td>';
            echo '<td>' . esc_html($log->email_to) . '</td>';
            echo '<td>' . esc_html($log->email_subject) . '</td>';
            echo '<td style="color: ' . $status_color . ';">' . esc_html($log->status) . '</td>';
            echo '<td>' . esc_html($log->sent_at) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p style="color: orange;">⚠️ No email logs found yet</p>';
    }
} else {
    echo '<p style="color: red;">❌ Email logs table does not exist</p>';
}

echo '<hr>';

// Test 5: PHP Configuration
echo '<h2>Test 5: PHP Configuration</h2>';
echo '<ul>';
echo '<li>PHP Version: ' . phpversion() . '</li>';
echo '<li>mail() function: ' . (function_exists('mail') ? '✅ Available' : '❌ Not available') . '</li>';
echo '<li>WordPress Version: ' . get_bloginfo('version') . '</li>';
echo '</ul>';

echo '<hr>';
echo '<p><strong>Next Steps:</strong></p>';
echo '<ol>';
echo '<li>Check your email inbox (and spam folder) for test emails</li>';
echo '<li>If emails not received, check WP Mail SMTP plugin settings</li>';
echo '<li>Enable WP_DEBUG to see error logs in /wp-content/debug.log</li>';
echo '<li>Check the Email Logs table above to see if emails are being logged</li>';
echo '</ol>';

echo '</body></html>';