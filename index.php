<?php
require_once 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jobs per page
$offset = ($page - 1) * $limit;

// Get search/filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';

// Build query for jobs with filters
$query = "SELECT j.*, c.name as category_name, u.full_name as employer_name 
          FROM jobs j 
          JOIN categories c ON j.category_id = c.id 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.status = 'open'";

$count_query = "SELECT COUNT(*) as total 
                FROM jobs j 
                JOIN categories c ON j.category_id = c.id 
                JOIN users u ON j.employer_id = u.id 
                WHERE j.status = 'open'";

$params = [];

// Add search filter
if (!empty($search)) {
    $query .= " AND (j.title LIKE :search OR j.description LIKE :search OR j.requirements LIKE :search)";
    $count_query .= " AND (j.title LIKE :search OR j.description LIKE :search OR j.requirements LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add category filter
if ($category > 0) {
    $query .= " AND j.category_id = :category";
    $count_query .= " AND j.category_id = :category";
    $params[':category'] = $category;
}

// Add location filter
if (!empty($location)) {
    $query .= " AND j.location LIKE :location";
    $count_query .= " AND j.location LIKE :location";
    $params[':location'] = "%$location%";
}

// Add job type filter
if (!empty($job_type)) {
    $query .= " AND j.job_type = :job_type";
    $count_query .= " AND j.job_type = :job_type";
    $params[':job_type'] = $job_type;
}

// Add order by and pagination
$query .= " ORDER BY j.created_at DESC LIMIT :limit OFFSET :offset";

// Get total jobs count
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    if ($key == ':category' || $key == ':job_type') {
        $count_stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$count_stmt->execute();
$total_jobs = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_jobs / $limit);

// Get jobs for current page
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    if ($key == ':category' || $key == ':job_type') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories with job count for sidebar
$query = "SELECT c.*, COUNT(j.id) as job_count 
          FROM categories c 
          LEFT JOIN jobs j ON c.id = j.category_id AND j.status = 'open' 
          GROUP BY c.id 
          ORDER BY job_count DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get job types for filter
$job_types = ['full-time' => 'Full Time', 'part-time' => 'Part Time', 'contract' => 'Contract', 'internship' => 'Internship'];
?>

<div class="container mt-4">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-md-10 mx-auto text-center">
            <h1 class="display-4 mb-4">Find Your Dream Job Today</h1>
            <p class="lead mb-4">Thousands of jobs from top companies waiting for you. Start your career journey with us.</p>

            <!-- Search Bar -->
            <form method="GET" action="" class="mt-4">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-lg" name="search"
                            placeholder="Job title, keywords, or company"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-lg" name="location"
                            placeholder="City, state, or remote"
                            value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-lg" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>

            <?php if (!isLoggedIn()): ?>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-success btn-lg me-2">Get Started</a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg">Login</a>
                </div>
            <?php else: ?>
                <?php if (hasRole('applicant')): ?>
                    <a href="applicant/jobs.php" class="btn btn-primary btn-lg mt-3">Browse All Jobs</a>
                <?php elseif (hasRole('employer')): ?>
                    <a href="employer/post_job.php" class="btn btn-primary btn-lg mt-3">Post a Job</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-md-3">
            <div class="sidebar">
                <h5><i class="fas fa-filter"></i> Filters</h5>

                <!-- Category Filter -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Categories</label>
                    <div class="list-group list-group-flush">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => 0, 'page' => 1])); ?>"
                            class="list-group-item list-group-item-action <?php echo $category == 0 ? 'active' : ''; ?>">
                            All Categories
                            <span class="badge bg-secondary float-end"><?php echo array_sum(array_column($categories, 'job_count')); ?></span>
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat['id'], 'page' => 1])); ?>"
                                class="list-group-item list-group-item-action <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                                <span class="badge bg-secondary float-end"><?php echo $cat['job_count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Job Type Filter -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Job Type</label>
                    <?php foreach ($job_types as $type_key => $type_name): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="job_type_filter"
                                id="job_type_<?php echo $type_key; ?>"
                                value="<?php echo $type_key; ?>"
                                <?php echo $job_type == $type_key ? 'checked' : ''; ?>
                                onchange="window.location.href='?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), job_type: this.checked ? this.value : '', page: 1}).toString()">
                            <label class="form-check-label" for="job_type_<?php echo $type_key; ?>">
                                <?php echo $type_name; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reset Filters Button -->
                <a href="index.php" class="btn btn-secondary w-100 mt-3">
                    <i class="fas fa-undo"></i> Reset Filters
                </a>
            </div>
        </div>

        <!-- Jobs Listings -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Available Jobs</h3>
                <p class="text-muted mb-0">Found <?php echo $total_jobs; ?> job<?php echo $total_jobs != 1 ? 's' : ''; ?></p>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <h5>No jobs found</h5>
                    <p>Try adjusting your search filters or check back later for new opportunities.</p>
                    <a href="index.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="card mb-3 job-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="card-title">
                                        <a href="view_job.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
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
                                        <i class="fas fa-clock"></i> <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?> •
                                        <?php echo ucfirst($job['experience_level']); ?> Level
                                    </p>
                                    <?php if ($job['salary']): ?>
                                        <p class="card-text">
                                            <i> Rwf </i> <?php echo htmlspecialchars($job['salary']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="card-text">
                                        <?php echo substr(htmlspecialchars($job['description']), 0, 150); ?>...
                                    </p>
                                    <small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small>
                                    <?php if ($job['end_date']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-hourglass-end"></i>
                                            Deadline: <?php echo date('M d, Y', strtotime($job['end_date'])); ?>
                                            <?php if (strtotime($job['end_date']) < time()): ?>
                                                <span class="badge bg-danger">Expired</span>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <!-- Add after the posted date in job cards -->

                                <div class="col-md-4 text-end">
                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm w-100 mb-2">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if (isLoggedIn() && hasRole('applicant')): ?>
                                        <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                        <?php echo $total_pages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row bg-light p-5 rounded mt-5">
        <div class="col-md-3 text-center mb-3">
            <h2 class="text-secondary"><?php echo number_format($total_jobs); ?>+</h2>
            <p>Active Jobs</p>
        </div>
        <div class="col-md-3 text-center mb-3">
            <?php
            $company_count = $db->query("SELECT COUNT(DISTINCT employer_id) as count FROM jobs WHERE status = 'open'")->fetch(PDO::FETCH_ASSOC)['count'];
            ?>
            <h2 class="text-secondary"><?php echo number_format($company_count); ?>+</h2>
            <p>Companies Hiring</p>
        </div>
        <div class="col-md-3 text-center mb-3">
            <?php
            $applicant_count = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'applicant'")->fetch(PDO::FETCH_ASSOC)['count'];
            ?>
            <h2 class="text-secondary"><?php echo number_format($applicant_count); ?>+</h2>
            <p>Job Seekers</p>
        </div>
        <div class="col-md-3 text-center mb-3">
            <h2 class="text-secondary">95%</h2>
            <p>Satisfaction Rate</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>