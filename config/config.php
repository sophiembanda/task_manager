<?php
require_once __DIR__ . '/env.php';

// Database configuration (from environment variables)
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_NAME', EnvLoader::get('DB_NAME', 'task_manager'));
define('DB_USER', EnvLoader::get('DB_USERNAME', 'root'));
define('DB_PASS', EnvLoader::get('DB_PASSWORD', ''));

// Email configuration (from environment variables)
define('SMTP_HOST', EnvLoader::get('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', EnvLoader::getInt('SMTP_PORT', 587));
define('SMTP_USERNAME', EnvLoader::get('GMAIL_USERNAME', ''));
define('SMTP_PASSWORD', EnvLoader::get('GMAIL_PASSWORD', ''));

// Base URL (from environment variables)
define('BASE_URL', EnvLoader::get('BASE_URL', 'http://localhost/task_manager/'));

// Session configuration
define('SESSION_LIFETIME', EnvLoader::getInt('SESSION_LIFETIME', 3600));

// Set session timeout BEFORE starting session
if (EnvLoader::isDevelopment()) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
}

// Start session
session_start();
?>
