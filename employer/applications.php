<?php
// Start output buffering
ob_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn()) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Handle application status update
if (isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['status'])) {
    $app_id = $_POST['application_id'];
    $new_status = $_POST['status'];

    // Check permission based on role
    if ($user_role == 'admin') {
        $stmt = $db->prepare("UPDATE applications SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $app_id])) {
            $message = "Application status updated successfully!";
        } else {
            $error = "Failed to update application status.";
        }
    } elseif ($user_role == 'employer') {
        // Verify employer owns the job for this application
        $check = $db->prepare("
            SELECT a.id FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            WHERE a.id = ? AND j.employer_id = ?
        ");
        $check->execute([$app_id, $user_id]);
        if ($check->fetch()) {
            $stmt = $db->prepare("UPDATE applications SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $app_id])) {
                $message = "Application status updated successfully!";
            } else {
                $error = "Failed to update application status.";
            }
        } else {
            $error = "You don't have permission to update this application.";
        }
    }
}

// Handle delete application
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $app_id = $_GET['delete'];

    if ($user_role == 'admin') {
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        if ($stmt->execute([$app_id])) {
            $message = "Application deleted successfully!";
        } else {
            $error = "Failed to delete application.";
        }
    } elseif ($user_role == 'employer') {
        $check = $db->prepare("
            SELECT a.id FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            WHERE a.id = ? AND j.employer_id = ?
        ");
        $check->execute([$app_id, $user_id]);
        if ($check->fetch()) {
            $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
            if ($stmt->execute([$app_id])) {
                $message = "Application deleted successfully!";
            } else {
                $error = "Failed to delete application.";
            }
        } else {
            $error = "You don't have permission to delete this application.";
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on user role
if ($user_role == 'admin') {
    // Admin sees all applications
    $query = "
        SELECT a.*, j.title as job_title, j.location, u.full_name as applicant_name, u.email as applicant_email, 
               e.full_name as employer_name, j.employer_id
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN users u ON a.applicant_id = u.id 
        JOIN users e ON j.employer_id = e.id 
        WHERE 1=1
    ";
} else {
    // Employer sees only applications for their jobs
    $query = "
        SELECT a.*, j.title as job_title, j.location, u.full_name as applicant_name, u.email as applicant_email,
               e.full_name as employer_name, j.employer_id
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN users u ON a.applicant_id = u.id 
        JOIN users e ON j.employer_id = e.id 
        WHERE j.employer_id = :employer_id
    ";
}

$params = [];

if ($user_role != 'admin') {
    $params[':employer_id'] = $user_id;
}

if ($status_filter != 'all') {
    $query .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

if ($job_filter > 0) {
    $query .= " AND a.job_id = :job_id";
    $params[':job_id'] = $job_filter;
}

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR j.title LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY a.applied_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get jobs list for filter (employer only sees their jobs, admin sees all)
if ($user_role == 'admin') {
    $jobs_stmt = $db->query("SELECT id, title FROM jobs ORDER BY created_at DESC");
} else {
    $jobs_stmt = $db->prepare("SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY created_at DESC");
    $jobs_stmt->execute([$user_id]);
}
$jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
if ($user_role == 'admin') {
    $total_stmt = $db->query("SELECT COUNT(*) FROM applications");
    $total = $total_stmt->fetchColumn();

    $pending_stmt = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
    $pending = $pending_stmt->fetchColumn();

    $reviewed_stmt = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'reviewed'");
    $reviewed = $reviewed_stmt->fetchColumn();

    $shortlisted_stmt = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'shortlisted'");
    $shortlisted = $shortlisted_stmt->fetchColumn();

    $hired_stmt = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'hired'");
    $hired = $hired_stmt->fetchColumn();

    $rejected_stmt = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'");
    $rejected = $rejected_stmt->fetchColumn();
} else {
    $stats_query = "SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ?";
    $stmt = $db->prepare($stats_query);
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'pending'");
    $stmt->execute([$user_id]);
    $pending = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'reviewed'");
    $stmt->execute([$user_id]);
    $reviewed = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'shortlisted'");
    $stmt->execute([$user_id]);
    $shortlisted = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'hired'");
    $stmt->execute([$user_id]);
    $hired = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? AND a.status = 'rejected'");
    $stmt->execute([$user_id]);
    $rejected = $stmt->fetchColumn();
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-file-alt me-2"></i>Applications</h2>
            <p class="text-muted">
                <?php if ($user_role == 'admin'): ?>
                    Manage all job applications
                <?php else: ?>
                    Manage applications for your jobs
                <?php endif; ?>
            </p>
        </div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print Report
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-2x mb-2" style="color: #1e847f;"></i>
                    <h3><?php echo $total; ?></h3>
                    <p class="mb-0">Total</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x mb-2" style="color: #ffc107;"></i>
                    <h3><?php echo $pending; ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-eye fa-2x mb-2" style="color: #17a2b8;"></i>
                    <h3><?php echo $reviewed; ?></h3>
                    <p class="mb-0">Reviewed</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-star fa-2x mb-2" style="color: #28a745;"></i>
                    <h3><?php echo $shortlisted; ?></h3>
                    <p class="mb-0">Shortlisted</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-user-check fa-2x mb-2" style="color: #1e847f;"></i>
                    <h3><?php echo $hired; ?></h3>
                    <p class="mb-0">Hired</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle fa-2x mb-2" style="color: #dc3545;"></i>
                    <h3><?php echo $rejected; ?></h3>
                    <p class="mb-0">Rejected</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewed" <?php echo $status_filter == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="shortlisted" <?php echo $status_filter == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="hired" <?php echo $status_filter == 'hired' ? 'selected' : ''; ?>>Hired</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Job</label>
                    <select name="job_id" class="form-select">
                        <option value="0">All Jobs</option>
                        <?php foreach ($jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" <?php echo $job_filter == $job['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or job..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Job</th>
                            <th>Employer</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="mt-2 text-muted">No applications found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $app['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($app['applicant_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($app['applicant_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['location']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['employer_name']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                        <br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($app['applied_at'])); ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $app['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="reviewed" <?php echo $app['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                <option value="shortlisted" <?php echo $app['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                                <option value="hired" <?php echo $app['status'] == 'hired' ? 'selected' : ''; ?>>Hired</option>
                                                <option value="rejected" <?php echo $app['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!empty($app['resume_path'])): ?>
                                                <a href="../uploads/resumes/<?php echo htmlspecialchars($app['resume_path']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo $app['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this application?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .btn,
        .btn-primary,
        form,
        .no-print,
        .card-header form,
        .btn-group {
            display: none !important;
        }

        .card {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        body {
            background: white;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
    }
</style>

<?php
require_once '../includes/footer.php';
?>