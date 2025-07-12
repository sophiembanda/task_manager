<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'task_manager');
define('DB_USER', 'root');
define('DB_PASS', '');

// Email configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // Add your email
define('SMTP_PASSWORD', ''); // Add your email password or app password

// Base URL
define('BASE_URL', 'http://localhost/task_manager/');

// Start session
session_start();
?>
