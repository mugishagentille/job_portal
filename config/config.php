<?php

// Base URL configuration - Update this based on your setup
define('BASE_URL', 'http://localhost/honey-ecommerce/');
define('BASE_PATH', dirname(__DIR__) . '/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'job_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Upload directories
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('RESUME_PATH', UPLOAD_PATH . 'resumes/');
define('PROFILE_PATH', UPLOAD_PATH . 'profiles/');

// Site configuration
define('SITE_NAME', 'JobPortal');
define('ADMIN_EMAIL', 'admin@jobportal.com');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============ AUTHENTICATION FUNCTIONS ============

// Database connection function
function getDB()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Check if user is logged in
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        session_destroy();
        header('Location: ' . BASE_URL . 'login.php?error=account_disabled');
        exit();
    }
}

// Check if user is admin
function requireAdmin()
{
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}

// Check if user is employer (admin or employer)
function requireEmployer()
{
    requireLogin();
    if (!in_array($_SESSION['user_role'], ['admin', 'employer'])) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}

// Helper function for time ago
function timeAgo($datetime)
{
    if (empty($datetime)) return 'Never';

    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}
