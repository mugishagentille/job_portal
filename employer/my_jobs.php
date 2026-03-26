<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('employer')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle job actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $job_id = $_GET['id'];

    if ($action == 'edit') {
        header("Location: edit_job.php?id=" . $job_id);
        exit();
    } elseif ($action == 'delete') {
        $query = "DELETE FROM jobs WHERE id = :id AND employer_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $job_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        header("Location: my_jobs.php?msg=Job deleted successfully");
        exit();
    } elseif ($action == 'toggle_status') {
        $query = "UPDATE jobs SET status = IF(status = 'open', 'closed', 'open') 
                  WHERE id = :id AND employer_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $job_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        header("Location: my_jobs.php?msg=Job status updated");
        exit();
    }
}

// Get employer's jobs
$query = "SELECT j.*, c.name as category_name, 
          (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as applications_count
          FROM jobs j 
          LEFT JOIN categories c ON j.category_id = c.id 
          WHERE j.employer_id = :user_id 
          ORDER BY j.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<div class="container mt-4">
    <h2>My Jobs</h2>

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
                    <h5 class="mb-0">Jobs Posted</h5>
                </div>
                <div class="col-md-6 text-end">
                    <a href="post_job.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Post New Job
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Applications</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><?php echo htmlspecialchars($job['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $job['status'] == 'open' ? 'success' : ($job['status'] == 'closed' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="job_applications.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                        <?php echo $job['applications_count']; ?> applicants
                                    </a>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                <td>
                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?action=edit&id=<?php echo $job['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=toggle_status&id=<?php echo $job['id']; ?>"
                                        class="btn btn-sm btn-<?php echo $job['status'] == 'open' ? 'secondary' : 'success'; ?>"
                                        onclick="return confirm('Change job status?')">
                                        <i class="fas fa-<?php echo $job['status'] == 'open' ? 'pause' : 'play'; ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $job['id']; ?>"
                                        class="btn btn-sm btn-danger" onclick="return confirm('Delete this job?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No jobs posted yet. <a href="post_job.php">Post your first job</a>
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