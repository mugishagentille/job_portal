<?php
// Start output buffering
ob_start();

require_once '../includes/header.php';

$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($job_id == 0) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get job details with all information
$query = "SELECT j.*, u.full_name as employer_name, u.email as employer_email, u.phone as employer_phone,
          c.name as category_name, c.description as category_description 
          FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          LEFT JOIN categories c ON j.category_id = c.id 
          WHERE j.id = :id AND j.status = 'open'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // Job not found or not open
    $_SESSION['error'] = "Job not found or no longer available.";
    header("Location: index.php");
    exit();
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user has already applied (if logged in)
$has_applied = false;
$is_saved = false;

if (isLoggedIn() && hasRole('applicant')) {
    $user_id = $_SESSION['user_id'];

    // Check if already applied
    $query = "SELECT id FROM applications WHERE job_id = :job_id AND applicant_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $has_applied = $stmt->rowCount() > 0;

    // Check if saved
    $query = "SELECT id FROM saved_jobs WHERE job_id = :job_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $is_saved = $stmt->rowCount() > 0;
}

// Get related jobs (same category)
$query = "SELECT j.*, u.full_name as employer_name 
          FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.category_id = :category_id 
          AND j.id != :job_id 
          AND j.status = 'open' 
          ORDER BY j.created_at DESC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':category_id', $job['category_id'], PDO::PARAM_INT);
$stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
$stmt->execute();
$related_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get application count
$query = "SELECT COUNT(*) as count FROM applications WHERE job_id = :job_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
$stmt->execute();
$application_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<div class="container mt-4">
    <div class="row">
        <!-- Main Job Details -->
        <div class="col-lg-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="applicant/jobs.php">Jobs</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($job['title']); ?></li>
                </ol>
            </nav>

            <!-- Job Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h2>
                            <h5 class="text-muted mb-3">
                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['employer_name']); ?>
                            </h5>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success fs-6 mb-2"><?php echo ucfirst($job['status']); ?></span>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt"></i> Posted: <?php echo date('F d, Y', strtotime($job['created_at'])); ?>
                            </small>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt fa-lg me-2" style="color: var(--secondary);"></i>
                                <div>
                                    <strong>Location</strong><br>
                                    <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock fa-lg me-2" style="color: var(--secondary);"></i>
                                <div>
                                    <strong>Job Type</strong><br>
                                    <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-line fa-lg me-2" style="color: var(--secondary);"></i>
                                <div>
                                    <strong>Experience Level</strong><br>
                                    <?php echo ucfirst($job['experience_level']); ?> Level
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-tag fa-lg me-2" style="color: var(--secondary);"></i>
                                <div>
                                    <strong>Category</strong><br>
                                    <?php echo htmlspecialchars($job['category_name']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-dollar-sign fa-lg me-2" style="color: var(--secondary);"></i>
                                <div>
                                    <strong>Salary Range</strong><br>
                                    <?php echo htmlspecialchars($job['salary'] ?: 'Negotiable'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users fa-lg me-2" style="color: var(--secondary);"></i>
                                <div>
                                    <strong>Applications</strong><br>
                                    <?php echo number_format($application_count); ?> applicant(s)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Description -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Job Description</h4>
                </div>
                <div class="card-body">
                    <div class="job-description">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </div>
                </div>
            </div>

            <!-- Job Requirements -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Requirements</h4>
                </div>
                <div class="card-body">
                    <div class="job-requirements">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </div>
                </div>
            </div>

            <!-- About the Employer -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">About the Employer</h4>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($job['employer_name']); ?></h5>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($job['employer_email']); ?></p>
                    <?php if ($job['employer_phone']): ?>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($job['employer_phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Jobs -->
            <?php if (!empty($related_jobs)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Similar Jobs You Might Like</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($related_jobs as $related): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <a href="view_job.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </a>
                                            </h6>
                                            <p class="card-text small text-muted">
                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($related['employer_name']); ?><br>
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($related['location'] ?: 'Remote'); ?>
                                            </p>
                                            <a href="view_job.php?id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-primary">View Job</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Apply Card -->
            <div class="card mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Interested in this job?</h5>
                </div>
                <div class="card-body">
                    <?php if (!isLoggedIn()): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Please <a href="login.php">login</a> or <a href="register.php">register</a> to apply for this job.
                        </div>
                        <a href="login.php" class="btn btn-primary w-100">Login to Apply</a>
                        <a href="register.php" class="btn btn-outline-primary w-100 mt-2">Create Account</a>

                    <?php elseif (hasRole('applicant')): ?>
                        <?php if ($has_applied): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> You have already applied for this position!
                                <hr>
                                <small>Your application is being reviewed by the employer.</small>
                            </div>
                            <a href="applicant/my_applications.php" class="btn btn-info w-100">
                                <i class="fas fa-file-alt"></i> View My Applications
                            </a>
                        <?php else: ?>
                            <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-paper-plane"></i> Apply Now
                            </a>

                            <?php if ($is_saved): ?>
                                <a href="applicant/unsave_job.php?id=<?php echo $job['id']; ?>" class="btn btn-danger w-100">
                                    <i class="fas fa-heart"></i> Remove from Saved
                                </a>
                            <?php else: ?>
                                <a href="applicant/save_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary w-100">
                                    <i class="far fa-heart"></i> Save for Later
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                    <?php elseif (hasRole('employer')): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You are logged in as an employer.
                            <a href="logout.php">Switch account</a> to apply for jobs.
                        </div>
                        <a href="employer/dashboard.php" class="btn btn-primary w-100">Go to Employer Dashboard</a>

                    <?php elseif (hasRole('admin')): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You are logged in as admin.
                            <a href="logout.php">Switch account</a> to apply for jobs.
                        </div>
                        <a href="admin/dashboard.php" class="btn btn-primary w-100">Go to Admin Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Job Overview Card -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Job Overview</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-calendar-alt text-secondary"></i>
                            <strong>Date Posted:</strong> <?php echo date('F d, Y', strtotime($job['created_at'])); ?>
                        </li>

                        <li class="mb-2">
                            <i class="fas fa-clock text-secondary"></i>
                            <strong>Job Type:</strong> <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-chart-line text-secondary"></i>
                            <strong>Experience:</strong> <?php echo ucfirst($job['experience_level']); ?> Level
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt text-secondary"></i>
                            <strong>Location:</strong> <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                        </li>
                        <!-- Add after location in job overview -->
                        <?php if ($job['start_date']): ?>
                            <li class="mb-2">
                                <i class="fas fa-calendar-plus text-secondary"></i>
                                <strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($job['start_date'])); ?>
                            </li>
                        <?php endif; ?>

                        <?php if ($job['end_date']): ?>
                            <li class="mb-2">
                                <i class="fas fa-calendar-times text-secondary"></i>
                                <strong>Application Deadline:</strong> <?php echo date('F d, Y', strtotime($job['end_date'])); ?>
                                <?php if (strtotime($job['end_date']) < time()): ?>
                                    <span class="badge bg-danger">Closed</span>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i>Rwf </i>
                            <strong>Salary:</strong> <?php echo htmlspecialchars($job['salary'] ?: 'Negotiable'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-tag text-secondary"></i>
                            <strong>Category:</strong> <?php echo htmlspecialchars($job['category_name']); ?>
                        </li>
                        <li>
                            <i class="fas fa-users text-secondary"></i>
                            <strong>Applications:</strong> <?php echo number_format($application_count); ?> received
                        </li>
                    </ul>
                </div>
            </div>


            <!-- Share Job Card -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Share This Job</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="copyJobLink()">
                            <i class="fas fa-copy"></i> Copy Link
                        </button>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                            target="_blank" class="btn btn-outline-primary">
                            <i class="fab fa-facebook"></i> Share on Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Check out this job: ' . $job['title']); ?>"
                            target="_blank" class="btn btn-outline-primary">
                            <i class="fab fa-twitter"></i> Share on Twitter
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($job['title']); ?>"
                            target="_blank" class="btn btn-outline-primary">
                            <i class="fab fa-linkedin"></i> Share on LinkedIn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyJobLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(function() {
            alert('Job link copied to clipboard!');
        }, function() {
            alert('Failed to copy link. Please copy manually.');
        });
    }
</script>

<style>
    .job-description,
    .job-requirements {
        line-height: 1.8;
        font-size: 1rem;
    }

    .job-description ul,
    .job-requirements ul {
        padding-left: 1.5rem;
        margin-top: 0.5rem;
    }

    .job-description li,
    .job-requirements li {
        margin-bottom: 0.5rem;
    }

    .sticky-top {
        z-index: 1;
    }

    .breadcrumb {
        background: transparent;
        padding: 0;
    }
</style>

<?php require_once '../includes/footer.php'; ?>