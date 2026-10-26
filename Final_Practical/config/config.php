<?php
/**
 * StudentHub Portal - Application Configuration
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

// Prevent multiple definitions
if (!defined('STUDENTHUB_CONFIG_LOADED')) {
    define('STUDENTHUB_CONFIG_LOADED', true);

    // Timezone
    date_default_timezone_set('Asia/Kolkata');

    // Database Credentials (Supports environment variables or local defaults)
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'studenthub');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_CHARSET', 'utf8mb4');

    // Session Configuration
    define('SESSION_LIFETIME', 900); // 15 minutes inactivity timeout
    define('REMEMBER_ME_DAYS', 30);  // 30 days remember me

    // Application Paths
    define('APP_NAME', 'StudentHub Portal');
    define('BASE_PATH', dirname(__DIR__));
    define('DATA_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'data');
    define('UPLOADS_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'event_posters');

    // File Upload Limits
    define('MAX_POSTER_SIZE', 2 * 1024 * 1024); // 2 MB
    define('ALLOWED_POSTER_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
}
