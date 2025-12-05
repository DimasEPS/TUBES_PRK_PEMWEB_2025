<?php
/**
 * Helper Functions
 * Fungsi-fungsi bantuan umum
 */

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Format tanggal Indonesia
 */
function formatDateIndo($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $bulan[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    
    return "$day $month $year, $time WIB";
}

/**
 * Upload file
 */
function uploadFile($file, $allowedTypes = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File terlalu besar. Maksimal ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt = $allowedTypes ?? ALLOWED_EXTENSIONS;
    
    if (!in_array($extension, $allowedExt)) {
        throw new Exception('Tipe file tidak diizinkan. Hanya: ' . implode(', ', $allowedExt));
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = UPLOAD_DIR . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Gagal mengupload file');
    }
    
    return $filename;
}

/**
 * Delete file
 */
function deleteFile($filename) {
    $filepath = UPLOAD_DIR . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'MENUNGGU' => 'badge-warning',
        'DIPROSES' => 'badge-info',
        'SELESAI' => 'badge-success'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Get status label
 */
function getStatusLabel($status) {
    $labels = [
        'MENUNGGU' => 'Menunggu',
        'DIPROSES' => 'Diproses',
        'SELESAI' => 'Selesai'
    ];
    return $labels[$status] ?? $status;
}

/**
 * Escape output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}
