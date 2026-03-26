<?php
require_once 'includes/header.php';

$app_id = isset($_GET['id']) ? $_GET['id'] : 0;

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get application details with proper permissions
if (hasRole('admin')) {
    $query = "SELECT a.*, j.title as job_title, j.description as job_description,
              u.full_name as applicant_name, u.email as applicant_email, u.phone,
              e.full_name as employer_name
              FROM applications a 
              JOIN jobs j ON a.job_id = j.id 
              JOIN users u ON a.applicant_id = u.id 
              JOIN users e ON j.employer_id = e.id 
              WHERE a.id = :app_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':app_id', $app_id);
} elseif (hasRole('employer')) {
    $query = "SELECT a.*, j.title as job_title, j.description as job_description,
              u.full_name as applicant_name, u.email as applicant_email, u.phone, u.address,
              e.full_name as employer_name
              FROM applications a 
              JOIN jobs j ON a.job_id = j.id 
              JOIN users u ON a.applicant_id = u.id 
              JOIN users e ON j.employer_id = e.id 
              WHERE a.id = :app_id AND j.employer_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':app_id', $app_id);
    $stmt->bindParam(':user_id', $user_id);
} else {
    $query = "SELECT a.*, j.title as job_title, j.description as job_description,
              u.full_name as employer_name
              FROM applications a 
              JOIN jobs j ON a.job_id = j.id 
              JOIN users u ON j.employer_id = u.id 
              WHERE a.id = :app_id AND a.applicant_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':app_id', $app_id);
    $stmt->bindParam(':user_id', $user_id);
}

$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: index.php");
    exit();
}

$application = $stmt->fetch(PDO::FETCH_ASSOC);

// Update status if employer/admin
if (($_SERVER['REQUEST_METHOD'] == 'POST') && (hasRole('employer') || hasRole('admin'))) {
    $status = $_POST['status'];
    $query = "UPDATE applications SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $app_id);
    $stmt->execute();

    header("Location: view_application.php?id=" . $app_id . "&msg=Status updated");
    exit();
}

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Application Details</h4>
                </div>
                <div class="card-body">
                    <h5>Job Information</h5>
                    <p><strong>Position:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
                    <p><strong>Employer:</strong> <?php echo htmlspecialchars($application['employer_name']); ?></p>

                    <h5 class="mt-4">Applicant Information</h5>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($application['applicant_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($application['applicant_email']); ?></p>
                    <?php if (isset($application['phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($application['phone'] ?: 'N/A'); ?></p>
                    <?php endif; ?>
                    <?php if (isset($application['address'])): ?>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($application['address'] ?: 'N/A'); ?></p>
                    <?php endif; ?>

                    <h5 class="mt-4">Cover Letter</h5>
                    <div class="border p-3 rounded bg-light">
                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                    </div>

                    <h5 class="mt-4">Resume/CV</h5>
                    <a href="<?php echo $application['resume_path']; ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-download"></i> Download Resume
                    </a>

                    <h5 class="mt-4">Application Status</h5>
                    <?php
                    $status_colors = [
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'shortlisted' => 'primary',
                        'accepted' => 'success',
                        'rejected' => 'danger'
                    ];
                    $color = $status_colors[$application['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?> fs-6">
                        <?php echo ucfirst($application['status']); ?>
                    </span>
                    <small class="text-muted ms-3">Applied on: <?php echo date('F d, Y', strtotime($application['applied_at'])); ?></small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?php if (hasRole('employer') || hasRole('admin')): ?>
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Update Application Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="status" class="form-label">Change Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="pending" <?php echo $application['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $application['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="shortlisted" <?php echo $application['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                    <option value="accepted" <?php echo $application['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <?php if (hasRole('applicant')): ?>
                        <a href="applicant/my_applications.php" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left"></i> Back to My Applications
                        </a>
                    <?php elseif (hasRole('employer')): ?>
                        <a href="employer/job_applications.php?id=<?php echo $application['job_id']; ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left"></i> Back to Applications
                        </a>
                    <?php elseif (hasRole('admin')): ?>
                        <a href="admin/manage_jobs.php" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left"></i> Back to Jobs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>