<?php
/**
 * Security Helper Functions
 * Provides XSS protection, input validation, and CSRF protection
 */

/**
 * Escape output for safe HTML display (XSS protection)
 */
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Alias for escape function for shorter syntax
 */
function e($string) {
    return escape($string);
}

/**
 * Sanitize input string
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return trim(htmlspecialchars($input ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate integer input
 */
function validate_int($input, $min = null, $max = null) {
    $int = filter_var($input, FILTER_VALIDATE_INT);
    if ($int === false) {
        return false;
    }
    if ($min !== null && $int < $min) {
        return false;
    }
    if ($max !== null && $int > $max) {
        return false;
    }
    return $int;
}

/**
 * Validate float input
 */
function validate_float($input, $min = null, $max = null) {
    $float = filter_var($input, FILTER_VALIDATE_FLOAT);
    if ($float === false) {
        return false;
    }
    if ($min !== null && $float < $min) {
        return false;
    }
    if ($max !== null && $float > $max) {
        return false;
    }
    return $float;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field
 */
function csrf_token_field() {
    return '<input type="hidden" name="csrf_token" value="' . escape(generate_csrf_token()) . '">';
}

/**
 * Validate file upload
 */
function validate_file_upload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Nenhum arquivo foi enviado.'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Erro no upload do arquivo.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'Arquivo muito grande. Máximo: ' . formatBytes($max_size)];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        return ['valid' => false, 'error' => 'Tipo de arquivo não permitido. Permitidos: ' . implode(', ', $allowed_types)];
    }
    
    // Check if file is actually an image (for image uploads)
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Arquivo não é uma imagem válida.'];
        }
    }
    
    return ['valid' => true, 'extension' => $ext];
}

/**
 * Generate secure filename
 */
function generate_secure_filename($original_filename = null) {
    $extension = '';
    if ($original_filename) {
        $extension = '.' . pathinfo($original_filename, PATHINFO_EXTENSION);
    }
    return uniqid('file_' . date('Ymd_His') . '_') . $extension;
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Rate limiting (simple implementation)
 */
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $current_time = time();
    $rate_limit_key = 'rate_limit_' . $key;
    
    if (!isset($_SESSION[$rate_limit_key])) {
        $_SESSION[$rate_limit_key] = [];
    }
    
    // Clean old attempts
    $_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    if (count($_SESSION[$rate_limit_key]) >= $max_attempts) {
        return false;
    }
    
    $_SESSION[$rate_limit_key][] = $current_time;
    return true;
}

/**
 * Log security events
 */
function log_security_event($event, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0750, true);
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Secure redirect function
 */
function secure_redirect($url) {
    // Validate URL to prevent open redirects
    $parsed_url = parse_url($url);
    
    // Only allow relative URLs or URLs to the same domain
    if (isset($parsed_url['host'])) {
        $current_host = $_SERVER['HTTP_HOST'] ?? '';
        if ($parsed_url['host'] !== $current_host) {
            // Log potential redirect attack
            log_security_event('suspicious_redirect', ['attempted_url' => $url]);
            $url = 'index.php'; // Fallback to safe URL
        }
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Clean input recursively
 */
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return trim(strip_tags($data));
}

/**
 * Password strength validation
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password deve ter pelo menos 8 caracteres.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password deve conter pelo menos uma letra maiúscula.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password deve conter pelo menos uma letra minúscula.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password deve conter pelo menos um número.';
    }
    
    return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
}
