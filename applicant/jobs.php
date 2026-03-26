<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('applicant')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$min_salary = isset($_GET['min_salary']) ? $_GET['min_salary'] : '';

// Build query
$query = "SELECT j.*, c.name as category_name, u.full_name as employer_name,
          (SELECT COUNT(*) FROM saved_jobs WHERE user_id = :user_id AND job_id = j.id) as is_saved
          FROM jobs j 
          JOIN categories c ON j.category_id = c.id 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.status = 'open'";
$params = [':user_id' => $user_id];

if ($search) {
    $query .= " AND (j.title LIKE :search OR j.description LIKE :search OR j.requirements LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($category) {
    $query .= " AND j.category_id = :category";
    $params[':category'] = $category;
}
if ($location) {
    $query .= " AND j.location LIKE :location";
    $params[':location'] = "%$location%";
}
if ($job_type) {
    $query .= " AND j.job_type = :job_type";
    $params[':job_type'] = $job_type;
}
if ($min_salary) {
    $query .= " AND CAST(SUBSTRING_INDEX(j.salary, '-', 1) AS UNSIGNED) >= :min_salary";
    $params[':min_salary'] = $min_salary;
}

$query .= " ORDER BY j.created_at DESC";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$query = "SELECT * FROM categories ORDER BY name";
$stmt_cat = $db->prepare($query);
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Browse Jobs</h2>
    <p class="text-muted">Find your dream job from thousands of opportunities</p>

    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-md-3">
            <div class="sidebar">
                <h5><i class="fas fa-filter"></i> Filters</h5>
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Job title, keywords">
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location"
                            value="<?php echo htmlspecialchars($location); ?>"
                            placeholder="City, state, or remote">
                    </div>

                    <div class="mb-3">
                        <label for="job_type" class="form-label">Job Type</label>
                        <select class="form-control" id="job_type" name="job_type">
                            <option value="">All Types</option>
                            <option value="full-time" <?php echo $job_type == 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                            <option value="part-time" <?php echo $job_type == 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                            <option value="contract" <?php echo $job_type == 'contract' ? 'selected' : ''; ?>>Contract</option>
                            <option value="internship" <?php echo $job_type == 'internship' ? 'selected' : ''; ?>>Internship</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="min_salary" class="form-label">Minimum Salary</label>
                        <input type="number" class="form-control" id="min_salary" name="min_salary"
                            value="<?php echo htmlspecialchars($min_salary); ?>"
                            placeholder="Minimum salary">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>

                    <a href="jobs.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="fas fa-undo"></i> Reset Filters
                    </a>
                </form>
            </div>
        </div>

        <!-- Job Listings -->
        <div class="col-md-9">
            <div class="mb-3">
                <p class="text-muted">Found <?php echo count($jobs); ?> jobs</p>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No jobs found matching your criteria.
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
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
                                        <i class="fas fa-clock"></i> <?php echo ucfirst($job['job_type']); ?> •
                                        <?php echo ucfirst($job['experience_level']); ?> Level
                                    </p>
                                    <?php if ($job['salary']): ?>
                                        <p class="card-text">
                                            <i>Rwf</i> <?php echo htmlspecialchars($job['salary']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="card-text">
                                        <?php echo substr(htmlspecialchars($job['description']), 0, 150); ?>...
                                    </p>
                                    <small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <a href="../apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm w-100 mb-2">
                                        <i class="fas fa-paper-plane"></i> Apply Now
                                    </a>
                                    <?php if ($job['is_saved'] > 0): ?>
                                        <a href="unsave_job.php?id=<?php echo $job['id']; ?>" class="btn btn-danger btn-sm w-100">
                                            <i class="fas fa-heart"></i> Saved
                                        </a>
                                    <?php else: ?>
                                        <a href="save_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="far fa-heart"></i> Save Job
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>