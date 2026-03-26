<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('employer')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Total jobs posted
$query = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['total_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Open jobs
$query = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :user_id AND status = 'open'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['open_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total applications received
$query = "SELECT COUNT(*) as total FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          WHERE j.employer_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent jobs
$query = "SELECT * FROM jobs WHERE employer_id = :user_id ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent applications
$query = "SELECT a.*, j.title as job_title, u.full_name as applicant_name 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users u ON a.applicant_id = u.id 
          WHERE j.employer_id = :user_id 
          ORDER BY a.applied_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Employer Dashboard</h2>
    <p class="text-muted">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-briefcase fa-3x mb-2"></i>
                <h3><?php echo $stats['total_jobs']; ?></h3>
                <p class="mb-0">Total Jobs Posted</p>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-check-circle fa-3x mb-2"></i>
                <h3><?php echo $stats['open_jobs']; ?></h3>
                <p class="mb-0">Active Jobs</p>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-users fa-3x mb-2"></i>
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
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Jobs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Applications</th>
                                    <th>Posted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_jobs as $job): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $job['status'] == 'open' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($job['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $query = "SELECT COUNT(*) as count FROM applications WHERE job_id = :job_id";
                                            $stmt_count = $db->prepare($query);
                                            $stmt_count->bindParam(':job_id', $job['id']);
                                            $stmt_count->execute();
                                            $count = $stmt_count->fetch(PDO::FETCH_ASSOC)['count'];
                                            echo $count;
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="my_jobs.php" class="btn btn-primary btn-sm">View All Jobs</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Applications</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Applicant</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_applications as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'accepted' ? 'success' : 'danger'); ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="applications.php" class="btn btn-primary btn-sm">View All Applications</a>
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
                            <a href="post_job.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-plus-circle"></i> Post New Job
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="my_jobs.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-list"></i> Manage Jobs
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="applications.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-alt"></i> View Applications
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../profile.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-user"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>