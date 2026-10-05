<?php
/**
 * Application Configuration
 * Achar Authentic E-Commerce Store
 */

// Prevent multiple definitions
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Session configuration (Only start if not started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Site Identification
define('APP_NAME', 'Achar Heritage');
define('APP_TAGLINE', 'Authentic Handcrafted Indian Pickles');

// Dynamic Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : str_replace('\\', '/', $scriptDir);

// Detect if inside /admin, /shop, etc. to get root base
$basePath = preg_replace('/(\/(admin|shop|account|checkout|ajax|auth))(\/.*)?$/', '', $scriptDir);
define('BASE_URL', rtrim($protocol . $host . $basePath, '/'));

// Directory Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Currency & Commerce Defaults
define('CURRENCY', 'INR');
define('CURRENCY_SYMBOL', '₹');
define('DEFAULT_SHIPPING_FEE', 49.00);
define('FREE_SHIPPING_THRESHOLD', 499.00);

// Payment Gateway Configuration (Structure ready for Razorpay/Cashfree/PayU)
define('PAYMENT_MODE', 'sandbox'); // sandbox or production
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_placeholderKey123');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'rzp_test_placeholderSecret456');

// File Upload Constraints
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Contact Defaults
define('DEFAULT_PHONE', '+91 98765 43210');
define('DEFAULT_WHATSAPP', '919876543210');
define('DEFAULT_EMAIL', 'care@acharheritage.com');
define('DEFAULT_ADDRESS', '42, Heritage Spice Street, Old Mandi, Jaipur, Rajasthan 302001');

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
