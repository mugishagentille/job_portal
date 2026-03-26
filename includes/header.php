<?php
// Start output buffering to prevent header issues
ob_start();

// Include configuration
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Get user data
function getUserData($user_id)
{
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get current page URL
function currentPage()
{
    return basename($_SERVER['PHP_SELF']);
}

// Check if on specific page
function isActive($page)
{
    return currentPage() == $page ? 'active' : '';
}

// Redirect function with base URL
function redirect($path)
{
    // Clear output buffer before redirect
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Find Your Dream Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ecc19c;
            --secondary: #3b4d61;
            --accent: #000000;
            --font-main: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Change font here */
            --font-heading: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Heading font */
            --text-color: #333333;
            /* Main text color */
            --text-light: #666666;
            /* Light text color */
            --text-dark: #000000;
            /* Dark text color */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f8f9fa;
            font-family: var(--font-main);
            color: var(--text-color);
            /* Main text color */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Headings Styling */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--font-heading);
            color: var(--text-dark);
            font-weight: 600;
        }

        /* Paragraph text */
        p {
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Links */
        a {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #146b66;
        }

        /* Navigation Links */
        .nav-link {
            color: white !important;
            font-family: var(--font-heading);

            font-weight: 600;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary) !important;
            transform: translateY(-2px);
        }

        /* Card Titles */
        .card-title {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Form Labels */
        .form-label {
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Table Headers */
        .table thead th {
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid var(--secondary);
        }

        /* Buttons */
        .btn {
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        .btn-primary:hover {
            background-color: #146b66;
            border-color: #146b66;
        }

        /* Rest of your existing CSS remains the same */
        .navbar {
            background: rgb(13, 13, 33);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
            border-radius: 20px;
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary) !important;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--primary);
            margin-right: 8px;
        }

        .btn-outline-primary {
            color: var(--secondary);
            border-color: var(--secondary);
        }

        .btn-outline-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, #f5e6d4 100%);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: var(--accent);
            margin-bottom: 20px;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .job-card {
            border-left: 4px solid var(--secondary);
        }

        .sidebar {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        /* Main content wrapper - pushes footer down */
        .main-content {
            flex: 1 0 auto;
            padding-bottom: 30px;
        }

        /* Footer styling */
        .footer {
            background-color: var(--accent);
            color: white;
            padding: 40px 0 20px;
            margin-top: auto;
            flex-shrink: 0;
            width: 100%;
            position: relative;
            bottom: 0;
        }

        .footer a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary);
        }

        .footer hr {
            background-color: rgba(255, 255, 255, 0.1);
            margin: 20px 0;
        }

        /* Table styling */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        /* Container spacing */
        .container {
            padding-top: 20px;
            padding-bottom: 20px;
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }

            .card {
                margin-bottom: 20px;
            }

            .footer {
                padding: 30px 0 15px;
            }

            .container {
                padding-top: 15px;
                padding-bottom: 15px;
            }

            h1 {
                font-size: 1.8rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-briefcase"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('index.php'); ?>" href="<?php echo BASE_URL; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>admin/manage_users.php">
                                    <i class="fas fa-users"></i> Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>admin/manage_jobs.php">
                                    <i class="fas fa-briefcase"></i> Jobs
                                </a>
                            </li>
                        <?php elseif (hasRole('employer')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>employer/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>employer/post_job.php">
                                    <i class="fas fa-plus-circle"></i> Post Job
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>employer/my_jobs.php">
                                    <i class="fas fa-briefcase"></i> My Jobs
                                </a>
                            </li>
                        <?php elseif (hasRole('applicant')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>applicant/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>applicant/jobs.php">
                                    <i class="fas fa-search"></i> Browse Jobs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>applicant/my_applications.php">
                                    <i class="fas fa-file-alt"></i> Applications
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>applicant/saved_jobs.php">
                                    <i class="fas fa-heart"></i> Saved
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('login.php'); ?>" href="<?php echo BASE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('register.php'); ?>" href="<?php echo BASE_URL; ?>register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="main-content">