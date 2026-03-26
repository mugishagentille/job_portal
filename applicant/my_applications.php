<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('applicant')) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get applications
$query = "SELECT a.*, j.title as job_title, j.location, j.salary, 
          u.full_name as employer_name, c.name as category_name
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN users u ON j.employer_id = u.id 
          LEFT JOIN categories c ON j.category_id = c.id 
          WHERE a.applicant_id = :user_id 
          ORDER BY a.applied_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>My Applications</h2>
    <p class="text-muted">Track all your job applications in one place</p>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Applications (<?php echo count($applications); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You haven't applied for any jobs yet.
                    <a href="jobs.php">Browse jobs</a> to get started!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Employer</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['employer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['location'] ?: 'Remote'); ?></td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'reviewed' => 'info',
                                            'shortlisted' => 'primary',
                                            'accepted' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $status_colors[$app['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                    <td>
                                        <a href="../view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>