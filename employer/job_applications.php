<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('employer')) {
    header("Location: ../login.php");
    exit();
}

$job_id = isset($_GET['id']) ? $_GET['id'] : 0;
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get job details
$query = "SELECT * FROM jobs WHERE id = :id AND employer_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $job_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: my_jobs.php");
    exit();
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Update application status
if (isset($_GET['update_status'])) {
    $app_id = $_GET['app_id'];
    $status = $_GET['status'];

    $query = "UPDATE applications SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $app_id);
    $stmt->execute();

    header("Location: job_applications.php?id=" . $job_id . "&msg=Status updated");
    exit();
}

// Get applications for this job
$query = "SELECT a.*, u.full_name, u.email, u.phone, u.address 
          FROM applications a 
          JOIN users u ON a.applicant_id = u.id 
          WHERE a.job_id = :job_id 
          ORDER BY a.applied_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<div class="container mt-4">
    <h2>Applications for: <?php echo htmlspecialchars($job['title']); ?></h2>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-0">Total Applications: <?php echo count($applications); ?></h5>
                </div>
                <div class="col-md-6 text-end">
                    <a href="my_jobs.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Jobs
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo htmlspecialchars($app['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'accepted' ? 'success' : 'danger'); ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                <td>
                                    <a href="../view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Update Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="?id=<?php echo $job_id; ?>&update_status=1&app_id=<?php echo $app['id']; ?>&status=pending">Pending</a></li>
                                            <li><a class="dropdown-item" href="?id=<?php echo $job_id; ?>&update_status=1&app_id=<?php echo $app['id']; ?>&status=reviewed">Reviewed</a></li>
                                            <li><a class="dropdown-item" href="?id=<?php echo $job_id; ?>&update_status=1&app_id=<?php echo $app['id']; ?>&status=shortlisted">Shortlisted</a></li>
                                            <li><a class="dropdown-item" href="?id=<?php echo $job_id; ?>&update_status=1&app_id=<?php echo $app['id']; ?>&status=accepted">Accepted</a></li>
                                            <li><a class="dropdown-item" href="?id=<?php echo $job_id; ?>&update_status=1&app_id=<?php echo $app['id']; ?>&status=rejected">Rejected</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No applications received for this job yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>