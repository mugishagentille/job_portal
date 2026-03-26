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

// Handle job actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($action == 'approve') {
        $query = "UPDATE jobs SET status = 'open' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
        redirect('admin/manage_jobs.php?msg=Job approved successfully');
    } elseif ($action == 'reject') {
        $query = "UPDATE jobs SET status = 'closed' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
        redirect('admin/manage_jobs.php?msg=Job rejected');
    } elseif ($action == 'delete') {
        $query = "DELETE FROM jobs WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
        redirect('admin/manage_jobs.php?msg=Job deleted successfully');
    } elseif ($action == 'toggle_status') {
        $query = "UPDATE jobs SET status = IF(status = 'open', 'closed', 'open') WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
        redirect('admin/manage_jobs.php?msg=Job status updated');
    }
}

// Get all jobs with counts
$query = "SELECT j.*, u.full_name as employer_name, c.name as category_name,
          (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count
          FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          LEFT JOIN categories c ON j.category_id = c.id 
          ORDER BY j.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Jobs</h2>
                <div>
                    <a href="add_job.php" class="btn btn-success me-2">
                        <i class="fas fa-plus"></i> Add New Job
                    </a>
                    <a href="job_reports.php" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">All Jobs (<?php echo count($jobs); ?> total)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Employer</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Salary</th>
                                    <th>Apps</th>
                                    <th>Status</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jobs)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                            No jobs found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo $job['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($job['experience_level']); ?> •
                                                    <?php echo ucfirst($job['job_type']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($job['employer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($job['category_name']); ?></td>
                                            <td>
                                                <?php
                                                $type_colors = [
                                                    'full-time' => 'primary',
                                                    'part-time' => 'info',
                                                    'contract' => 'warning',
                                                    'internship' => 'secondary'
                                                ];
                                                $type_color = $type_colors[$job['job_type']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $type_color; ?>">
                                                    <?php echo str_replace('-', ' ', ucfirst($job['job_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($job['salary']): ?>
                                                    <?php echo htmlspecialchars($job['salary']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="job_applications.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                    <span class="badge bg-info">
                                                        <?php echo $job['application_count']; ?> apps
                                                    </span>
                                                </a>
                                            </td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'open' => 'success',
                                                    'closed' => 'danger',
                                                    'pending' => 'warning'
                                                ];
                                                $status_color = $status_colors[$job['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_color; ?>">
                                                    <?php echo ucfirst($job['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-info mb-1" title="View Details">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-warning mb-1" title="Edit Job">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <?php if ($job['status'] == 'pending'): ?>
                                                        <a href="?action=approve&id=<?php echo $job['id']; ?>"
                                                            class="btn btn-success mb-1" title="Approve"
                                                            onclick="return confirm('Approve this job?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                        <a href="?action=reject&id=<?php echo $job['id']; ?>"
                                                            class="btn btn-danger mb-1" title="Reject"
                                                            onclick="return confirm('Reject this job?')">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=toggle_status&id=<?php echo $job['id']; ?>"
                                                            class="btn btn-<?php echo $job['status'] == 'open' ? 'secondary' : 'success'; ?> mb-1"
                                                            title="<?php echo $job['status'] == 'open' ? 'Close Job' : 'Open Job'; ?>"
                                                            onclick="return confirm('Change job status?')">
                                                            <i class="fas fa-<?php echo $job['status'] == 'open' ? 'pause' : 'play'; ?>"></i>
                                                            <?php echo $job['status'] == 'open' ? 'Close' : 'Open'; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?php echo $job['id']; ?>"
                                                        class="btn btn-danger" title="Delete"
                                                        onclick="return confirm('Delete this job? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i> Delete
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
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>