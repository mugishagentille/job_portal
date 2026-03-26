<?php
// Start output buffering
ob_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total employers
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'employer'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_employers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total applicants
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'applicant'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_applicants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total jobs
$query = "SELECT COUNT(*) as total FROM jobs";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending jobs
$query = "SELECT COUNT(*) as total FROM jobs WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Open jobs
$query = "SELECT COUNT(*) as total FROM jobs WHERE status = 'open'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['open_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total applications
$query = "SELECT COUNT(*) as total FROM applications";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent users
$query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent jobs
$query = "SELECT j.*, u.full_name as employer_name FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          ORDER BY j.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent applications
$query = "SELECT a.*, j.title as job_title, u.full_name as applicant_name 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users u ON a.applicant_id = u.id 
          ORDER BY a.applied_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Admin Dashboard</h2>
    <p class="text-muted">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-users fa-3x mb-2"></i>
                <h3><?php echo $stats['total_users']; ?></h3>
                <p class="mb-0">Total Users</p>
                <small><?php echo $stats['total_employers']; ?> Employers | <?php echo $stats['total_applicants']; ?> Applicants</small>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-briefcase fa-3x mb-2"></i>
                <h3><?php echo $stats['total_jobs']; ?></h3>
                <p class="mb-0">Total Jobs</p>
                <small><?php echo $stats['open_jobs']; ?> Open | <?php echo $stats['pending_jobs']; ?> Pending</small>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-file-alt fa-3x mb-2"></i>
                <h3><?php echo $stats['total_applications']; ?></h3>
                <p class="mb-0">Total Applications</p>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-chart-line fa-3x mb-2"></i>
                <h3><?php echo round(($stats['total_applications'] / max($stats['total_jobs'], 1)), 1); ?></h3>
                <p class="mb-0">Avg. Applications/Job</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_users as $user): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'employer' ? 'info' : 'success'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                        <br>
                                        <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?> mt-1">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="manage_users.php" class="btn btn-primary btn-sm w-100">View All Users</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Jobs</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_jobs as $job): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">by <?php echo htmlspecialchars($job['employer_name']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $job['status'] == 'open' ? 'success' : ($job['status'] == 'closed' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="manage_jobs.php" class="btn btn-primary btn-sm w-100">Manage Jobs</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Applications</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($app['applicant_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">applied for <?php echo htmlspecialchars($app['job_title']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'accepted' ? 'success' : 'danger'); ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="applications.php" class="btn btn-primary btn-sm w-100">View All Applications</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="add_user.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user-plus"></i> Add New User
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="add_job.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-briefcase"></i> Add New Job
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../admin/manage_categories.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-tags"></i> Manage Categories
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../profile.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>