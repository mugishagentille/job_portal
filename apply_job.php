<?php
require_once 'includes/header.php';

if (!isLoggedIn() || !hasRole('applicant')) {
    header("Location: login.php");
    exit();
}

$job_id = isset($_GET['id']) ? $_GET['id'] : 0;

$database = new Database();
$db = $database->getConnection();

// Check if job exists and is open
$query = "SELECT j.*, u.full_name as employer_name FROM jobs j 
          JOIN users u ON j.employer_id = u.id 
          WHERE j.id = :job_id AND j.status = 'open'";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: index.php");
    exit();
}

$job = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if already applied
$query = "SELECT * FROM applications WHERE job_id = :job_id AND applicant_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':job_id', $job_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $_SESSION['error'] = "You have already applied for this job.";
    header("Location: view_job.php?id=" . $job_id);
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cover_letter = trim($_POST['cover_letter']);

    // Handle file upload
    $target_dir = "uploads/resumes/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
    $file_name = time() . '_' . $_SESSION['user_id'] . '.' . $file_extension;
    $target_file = $target_dir . $file_name;

    $allowed_types = ['pdf', 'doc', 'docx'];

    if ($_FILES["resume"]["error"] == 4) {
        $error = "Please upload your resume.";
    } elseif (!in_array($file_extension, $allowed_types)) {
        $error = "Only PDF, DOC, and DOCX files are allowed.";
    } elseif ($_FILES["resume"]["size"] > 5000000) {
        $error = "File is too large. Maximum size is 5MB.";
    } elseif (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
        // Insert application
        $query = "INSERT INTO applications (job_id, applicant_id, cover_letter, resume_path) 
                  VALUES (:job_id, :applicant_id, :cover_letter, :resume_path)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':applicant_id', $_SESSION['user_id']);
        $stmt->bindParam(':cover_letter', $cover_letter);
        $stmt->bindParam(':resume_path', $target_file);

        if ($stmt->execute()) {
            $success = "Application submitted successfully!";
        } else {
            $error = "Failed to submit application. Please try again.";
        }
    } else {
        $error = "Failed to upload file. Please try again.";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Apply for: <?php echo htmlspecialchars($job['title']); ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <a href="applicant/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php else: ?>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="cover_letter" class="form-label">Cover Letter</label>
                                <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" required></textarea>
                                <small class="text-muted">Tell us why you're a great fit for this position.</small>
                            </div>

                            <div class="mb-3">
                                <label for="resume" class="form-label">Resume/CV *</label>
                                <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                                <small class="text-muted">Accepted formats: PDF, DOC, DOCX. Max size: 5MB</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Application</button>
                            <a href="view_job.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>