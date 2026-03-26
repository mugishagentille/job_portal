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

// Get categories
$query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $category_id = $_POST['category_id'];
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $salary = trim($_POST['salary']);
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $experience_level = $_POST['experience_level'];

    if (empty($title) || empty($description) || empty($requirements)) {
        $error = "Please fill in all required fields.";
    } else {
        $query = "UPDATE jobs SET title = :title, category_id = :category_id, 
                  description = :description, requirements = :requirements, 
                  salary = :salary, location = :location, job_type = :job_type, 
                  experience_level = :experience_level 
                  WHERE id = :id AND employer_id = :user_id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':requirements', $requirements);
        $stmt->bindParam(':salary', $salary);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':job_type', $job_type);
        $stmt->bindParam(':experience_level', $experience_level);
        $stmt->bindParam(':id', $job_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            $success = "Job updated successfully!";
        } else {
            $error = "Failed to update job. Please try again.";
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Edit Job: <?php echo htmlspecialchars($job['title']); ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                value="<?php echo htmlspecialchars($job['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-control" id="category_id" name="category_id" required>
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
                                    <option value="entry" <?php echo $job['experience_level'] == 'entry' ? 'selected' : ''; ?>>Entry Level</option>
                                    <option value="mid" <?php echo $job['experience_level'] == 'mid' ? 'selected' : ''; ?>>Mid Level</option>
                                    <option value="senior" <?php echo $job['experience_level'] == 'senior' ? 'selected' : ''; ?>>Senior Level</option>
                                    <option value="lead" <?php echo $job['experience_level'] == 'lead' ? 'selected' : ''; ?>>Lead/Manager</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="salary" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary" name="salary"
                                    value="<?php echo htmlspecialchars($job['salary']); ?>"
                                    placeholder="e.g., $50,000 - $70,000">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location"
                                    value="<?php echo htmlspecialchars($job['location']); ?>"
                                    placeholder="e.g., New York, NY or Remote">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="6" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements *</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="6" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Job</button>
                        <a href="my_jobs.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>