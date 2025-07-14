<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/email.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (empty($test_email)) {
        $message = 'Please enter an email address';
        $messageType = 'error';
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
        $messageType = 'error';
    } else {
        $emailService = new EmailService();
        
        if ($emailService->testEmail($test_email)) {
            $message = 'Test email sent successfully to ' . $test_email;
            $messageType = 'success';
        } else {
            $message = 'Failed to send test email. Please check your server email configuration.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Task Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .message { padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .info-box { background: #d1ecf1; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .info-box h3 { margin-top: 0; }
        .requirements { background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .requirements h4 { margin-top: 0; }
        .requirements ul { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Email Configuration Test</h1>
            <div>
                <a href="tasks.php" class="btn">Back to Tasks</a>
                <a href="../index.php" class="btn">Dashboard</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Email Notifications</h3>
            <p>The task manager automatically sends email notifications when new tasks are assigned to users. Use this page to test if email functionality is working correctly on your server.</p>
        </div>
        
        <div class="requirements">
            <h4>Email Configuration</h4>
            <ul>
                <li><strong>Development:</strong> Use MailHog for testing emails locally</li>
                <li><strong>Production:</strong> Configure SMTP server (Gmail, SendGrid, etc.)</li>
                <li>See EMAIL_SETUP.md for detailed configuration instructions</li>
            </ul>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="test_email">Test Email Address:</label>
                <input type="email" id="test_email" name="test_email" 
                       value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>" 
                       placeholder="Enter email address to test" required>
            </div>
            
            <button type="submit" class="btn">Send Test Email</button>
        </form>
        
        <div style="margin-top: 40px;">
            <h3>Email Configuration Notes</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
                <h4>For Local Development (XAMPP/WAMP):</h4>
                <p>You need to configure an SMTP server or use a tool like MailHog for testing emails locally.</p>
                
                <h4>For Production:</h4>
                <p>Configure your server's SMTP settings or integrate with services like:</p>
                <ul>
                    <li>Gmail SMTP</li>
                    <li>SendGrid</li>
                    <li>Mailgun</li>
                    <li>Amazon SES</li>
                </ul>
                
                <h4>Current Configuration:</h4>
                <p><strong>From Email:</strong> noreply@taskmanager.local</p>
                <p><strong>Method:</strong> PHP mail() function</p>
                <p><strong>Status:</strong> <?= function_exists('mail') ? '✅ mail() function available' : '❌ mail() function not available' ?></p>
            </div>
        </div>
    </div>
</body>
</html>
