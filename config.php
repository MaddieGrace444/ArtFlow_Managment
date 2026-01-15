<?php
// config.php - Updated with gallery and admin functions
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'Artflow_db';
$username = 'root';
$password = 'root';
$port = 8889; // MAMP MySQL port

// Database connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


// HELPER FUNCTIONS

/**
 * Check if current user is an admin*/
function isAdmin() {
    return isset($_SESSION['user_id']);
}

/**
 Check if user is logged in*/
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.html");
        exit();
    }
}

/**
 * Redirect to dashboard if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get user information
 */
function getUserInfo($user_id = null) {
    global $pdo;
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

// Gallery configuration
define('GALLERY_UPLOAD_PATH', __DIR__ . '/gallery/uploads/');
define('GALLERY_THUMB_PATH', __DIR__ . '/gallery/thumbnails/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB for high-quality art images
define('ALLOWED_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
]);

// Website configuration
define('SITE_NAME', 'ArtFlow®');
define('SITE_URL', 'http://localhost:8888/Artflow_signup_project/');

// Ensure session is started (if not already started elsewhere)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


