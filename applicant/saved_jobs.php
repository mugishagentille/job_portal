<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('applicant')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle unsave
if (isset($_GET['unsave'])) {
    $job_id = $_GET['id'];
    $query = "DELETE FROM saved_jobs WHERE user_id = :user_id AND job_id = :job_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':job_id', $job_id);
    $stmt->execute();
    header("Location: saved_jobs.php?msg=Job removed from saved list");
    exit();
}

// Get saved jobs
$query = "SELECT j.*, c.name as category_name, u.full_name as employer_name,
          (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND applicant_id = :user_id) as has_applied
          FROM saved_jobs sj 
          JOIN jobs j ON sj.job_id = j.id 
          JOIN categories c ON j.category_id = c.id 
          JOIN users u ON j.employer_id = u.id 
          WHERE sj.user_id = :user_id AND j.status = 'open'
          ORDER BY sj.saved_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$saved_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<div class="container mt-4">
    <h2>Saved Jobs</h2>
    <p class="text-muted">Jobs you've saved for later</p>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($saved_jobs)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                <h5>No saved jobs yet</h5>
                <p class="text-muted">Save jobs you're interested in to review them later.</p>
                <a href="jobs.php" class="btn btn-primary">Browse Jobs</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($saved_jobs as $job): ?>
            <div class="card mb-3 job-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <h5 class="card-title">
                                <a href="../view_job.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['employer_name']); ?>
                                <br>
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                                <br>
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($job['category_name']); ?>
                                <br>
                                <i class="fas fa-clock"></i> <?php echo ucfirst($job['job_type']); ?>
                            </p>
                            <?php if ($job['salary']): ?>
                                <p class="card-text">
                                    <i>Rwf</i> <?php echo htmlspecialchars($job['salary']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($job['has_applied'] > 0): ?>
                                <button class="btn btn-success btn-sm w-100 mb-2" disabled>
                                    <i class="fas fa-check"></i> Already Applied
                                </button>
                            <?php else: ?>
                                <a href="../apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-paper-plane"></i> Apply Now
                                </a>
                            <?php endif; ?>
                            <a href="?unsave=1&id=<?php echo $job['id']; ?>" class="btn btn-danger btn-sm w-100"
                                onclick="return confirm('Remove this job from saved list?')">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>