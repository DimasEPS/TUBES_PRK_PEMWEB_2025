<?php
/**
 * Application Configuration
 * Konfigurasi umum aplikasi SiPEMAU
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Base URL
define('BASE_URL', env('APP_URL', 'http://localhost/TUBES_PRK_PEMWEB_2025/kelompok/kelompok_19/backend/public'));

// Upload Settings
define('UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/');
define('MAX_FILE_SIZE', (int)env('UPLOAD_MAX_SIZE', 5242880)); // 5MB default
$allowedExt = explode(',', env('UPLOAD_ALLOWED_EXT', 'jpg,jpeg,png,pdf'));
define('ALLOWED_EXTENSIONS', $allowedExt);

// Session Settings
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 3600)); // 1 hour

// Password Settings
define('PASSWORD_MIN_LENGTH', (int)env('PASSWORD_MIN_LENGTH', 8));

// Pagination
define('ITEMS_PER_PAGE', (int)env('ITEMS_PER_PAGE', 10));

// App Info
define('APP_NAME', env('APP_NAME', 'SiPEMAU'));
define('APP_VERSION', '1.0.0');
define('APP_ENV', env('APP_ENV', 'production'));
