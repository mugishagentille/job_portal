<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('applicant')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Total applications
$query = "SELECT COUNT(*) as total FROM applications WHERE applicant_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending applications
$query = "SELECT COUNT(*) as total FROM applications 
          WHERE applicant_id = :user_id AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['pending_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Accepted applications
$query = "SELECT COUNT(*) as total FROM applications 
          WHERE applicant_id = :user_id AND status = 'accepted'";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['accepted_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Saved jobs
$query = "SELECT COUNT(*) as total FROM saved_jobs WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stats['saved_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent applications
$query = "SELECT a.*, j.title as job_title, j.location, e.full_name as employer_name 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users e ON j.employer_id = e.id 
          WHERE a.applicant_id = :user_id 
          ORDER BY a.applied_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recommended jobs
$query = "SELECT j.*, c.name as category_name, u.full_name as employer_name 
          FROM jobs j 
          JOIN categories c ON j.category_id = c.id 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.status = 'open' 
          ORDER BY j.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recommended_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>My Dashboard</h2>
    <p class="text-muted">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-file-alt fa-3x mb-2"></i>
                <h3><?php echo $stats['total_applications']; ?></h3>
                <p class="mb-0">Total Applications</p>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-clock fa-3x mb-2"></i>
                <h3><?php echo $stats['pending_applications']; ?></h3>
                <p class="mb-0">Pending Review</p>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-check-circle fa-3x mb-2"></i>
                <h3><?php echo $stats['accepted_applications']; ?></h3>
                <p class="mb-0">Accepted</p>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <i class="fas fa-heart fa-3x mb-2"></i>
                <h3><?php echo $stats['saved_jobs']; ?></h3>
                <p class="mb-0">Saved Jobs</p>
            </div>
        </div>
    </div>

    <div class="row">
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
                                    <th>Employer</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_applications as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                        <td><?php echo htmlspecialchars($app['employer_name']); ?></td>
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
                    <a href="my_applications.php" class="btn btn-primary btn-sm">View All Applications</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recommended Jobs</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($recommended_jobs as $job): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['employer_name']); ?>
                                            <br>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                                            <br>
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($job['category_name']); ?>
                                        </p>
                                        <small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small>
                                    </div>
                                    <a href="../view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary">View Job</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="jobs.php" class="btn btn-primary btn-sm mt-3">Browse All Jobs</a>
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
                            <a href="jobs.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search"></i> Find Jobs
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="my_applications.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-alt"></i> My Applications
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="saved_jobs.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-heart"></i> Saved Jobs
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