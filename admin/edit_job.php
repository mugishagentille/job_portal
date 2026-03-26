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

$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($job_id == 0) {
    redirect('admin/manage_jobs.php');
}

$database = new Database();
$db = $database->getConnection();

// Get job data - separate query for initial load
$query = "SELECT j.*, u.full_name as employer_name, u.email as employer_email 
          FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    redirect('admin/manage_jobs.php');
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt_cat = $db->prepare($query);
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// Get employers for dropdown
$query = "SELECT id, full_name, email FROM users WHERE role = 'employer' ORDER BY full_name";
$stmt_emp = $db->prepare($query);
$stmt_emp->execute();
$employers = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get all form data
    $title = trim($_POST['title']);
    $employer_id = intval($_POST['employer_id']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $salary = trim($_POST['salary']);
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $experience_level = $_POST['experience_level'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // Validation
    $errors = [];
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    if (empty($employer_id)) {
        $errors[] = "Employer is required";
    }
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    if (empty($requirements)) {
        $errors[] = "Job requirements are required";
    }

    // Validate dates
    if (!empty($end_date) && !empty($start_date) && strtotime($end_date) < strtotime($start_date)) {
        $errors[] = "End date cannot be earlier than start date.";
    }

    if (empty($errors)) {
        try {
            // Update query with dates
            $query = "UPDATE jobs SET 
                      title = :title, 
                      employer_id = :employer_id,
                      category_id = :category_id, 
                      description = :description, 
                      requirements = :requirements, 
                      salary = :salary, 
                      location = :location, 
                      job_type = :job_type, 
                      experience_level = :experience_level,
                      status = :status,
                      start_date = :start_date,
                      end_date = :end_date
                      WHERE id = :id";

            $stmt = $db->prepare($query);

            // Execute with array
            $params = [
                ':title' => $title,
                ':employer_id' => $employer_id,
                ':category_id' => $category_id,
                ':description' => $description,
                ':requirements' => $requirements,
                ':salary' => $salary,
                ':location' => $location,
                ':job_type' => $job_type,
                ':experience_level' => $experience_level,
                ':status' => $status,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':id' => $job_id
            ];

            if ($stmt->execute($params)) {
                $success = "Job updated successfully!";
                // Refresh job data after update
                $refresh_query = "SELECT j.*, u.full_name as employer_name, u.email as employer_email 
                                 FROM jobs j 
                                 JOIN users u ON j.employer_id = u.id 
                                 WHERE j.id = :id";
                $refresh_stmt = $db->prepare($refresh_query);
                $refresh_stmt->bindParam(':id', $job_id, PDO::PARAM_INT);
                $refresh_stmt->execute();
                $job = $refresh_stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Failed to update job. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Job: <?php echo htmlspecialchars($job['title']); ?></h4>
                        <a href="manage_jobs.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Jobs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                value="<?php echo htmlspecialchars($job['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="employer_id" class="form-label">Employer *</label>
                            <select class="form-control" id="employer_id" name="employer_id" required>
                                <option value="">Select Employer</option>
                                <?php foreach ($employers as $employer): ?>
                                    <option value="<?php echo $employer['id']; ?>"
                                        <?php echo $job['employer_id'] == $employer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employer['full_name']); ?> (<?php echo $employer['email']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo $job['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="job_type" class="form-label">Job Type *</label>
                                <select class="form-control" id="job_type" name="job_type" required>
                                    <option value="full-time" <?php echo $job['job_type'] == 'full-time' ? 'selected' : ''; ?>>Full Time</option>
                                    <option value="part-time" <?php echo $job['job_type'] == 'part-time' ? 'selected' : ''; ?>>Part Time</option>
                                    <option value="contract" <?php echo $job['job_type'] == 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="internship" <?php echo $job['job_type'] == 'internship' ? 'selected' : ''; ?>>Internship</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="experience_level" class="form-label">Experience Level *</label>
                                <select class="form-control" id="experience_level" name="experience_level" required>
                                    <option value="entry" <?php echo $job['experience_level'] == 'entry' ? 'selected' : ''; ?>>Entry Level (0-2 years)</option>
                                    <option value="mid" <?php echo $job['experience_level'] == 'mid' ? 'selected' : ''; ?>>Mid Level (3-5 years)</option>
                                    <option value="senior" <?php echo $job['experience_level'] == 'senior' ? 'selected' : ''; ?>>Senior Level (6-9 years)</option>
                                    <option value="lead" <?php echo $job['experience_level'] == 'lead' ? 'selected' : ''; ?>>Lead/Manager (10+ years)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="salary" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary" name="salary"
                                    value="<?php echo htmlspecialchars($job['salary']); ?>"
                                    placeholder="e.g., Rwf 50,000 - Rwf 70,000">
                                <small class="text-muted">Enter salary range or negotiable</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location"
                                    value="<?php echo htmlspecialchars($job['location']); ?>"
                                    placeholder="e.g., New York, NY or Remote">
                                <small class="text-muted">Can be city, state, or "Remote"</small>
                            </div>
                        </div>

                        <!-- Job Posting Dates Section -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-calendar-alt"></i> Job Posting Dates</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date (When job becomes active)</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                            value="<?php echo isset($job['start_date']) && $job['start_date'] ? $job['start_date'] : ''; ?>"
                                            min="<?php echo date('Y-m-d'); ?>">
                                        <small class="text-muted">Leave blank to start immediately</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date (Application deadline)</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                            value="<?php echo isset($job['end_date']) && $job['end_date'] ? $job['end_date'] : ''; ?>"
                                            min="<?php echo date('Y-m-d'); ?>">
                                        <small class="text-muted">Leave blank for no expiration</small>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>Jobs will automatically appear on the start date</li>
                                        <li>Jobs will automatically close after the end date</li>
                                        <li>Current date: <?php echo date('Y-m-d'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Status Section -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Job Status *</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="open" <?php echo isset($job['status']) && $job['status'] == 'open' ? 'selected' : ''; ?>>
                                    Open - Accepting Applications
                                </option>
                                <option value="closed" <?php echo isset($job['status']) && $job['status'] == 'closed' ? 'selected' : ''; ?>>
                                    Closed - Not Accepting Applications
                                </option>
                                <option value="pending" <?php echo isset($job['status']) && $job['status'] == 'pending' ? 'selected' : ''; ?>>
                                    Pending - Waiting for Approval
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="6" required><?php
                                                                                                                    echo htmlspecialchars($job['description']);
                                                                                                                    ?></textarea>
                            <small class="text-muted">Describe the role, responsibilities, and what makes this position great.</small>
                        </div>

                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements *</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="6" required><?php
                                                                                                                    echo htmlspecialchars($job['requirements']);
                                                                                                                    ?></textarea>
                            <small class="text-muted">List required skills, qualifications, and experience.</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Changes will be visible immediately to all users.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Job
                            </button>
                            <a href="manage_jobs.php" class="btn btn-secondary">
                                Cancel
                            </a>
                            <a href="view_job.php?id=<?php echo $job_id; ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Job
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Automatically set end date to be after start date
    document.getElementById('start_date').addEventListener('change', function() {
        const endDate = document.getElementById('end_date');
        if (endDate.value && this.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
        endDate.min = this.value;
    });

    // Validate dates before form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (startDate && endDate && endDate < startDate) {
            e.preventDefault();
            alert('End date cannot be earlier than start date!');
            return false;
        }
    });
</script>

<?php
require_once '../includes/footer.php';
?>