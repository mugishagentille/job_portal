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

// Handle user actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'];

    if ($action == 'activate') {
        $query = "UPDATE users SET status = 'active' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        redirect('admin/manage_users.php?msg=User activated successfully');
    } elseif ($action == 'deactivate') {
        $query = "UPDATE users SET status = 'inactive' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        redirect('admin/manage_users.php?msg=User deactivated successfully');
    } elseif ($action == 'delete') {
        // Don't allow deleting admin users
        $query = "SELECT role FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role'] != 'admin') {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            redirect('admin/manage_users.php?msg=User deleted successfully');
        } else {
            redirect('admin/manage_users.php?msg=Cannot delete admin user');
        }
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Users</h2>
                <a href="add_user.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">All Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'employer' ? 'info' : 'success'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['role'] != 'admin'): ?>
                                                    <?php if ($user['status'] == 'active'): ?>
                                                        <a href="?action=deactivate&id=<?php echo $user['id']; ?>"
                                                            class="btn btn-sm btn-secondary" title="Deactivate"
                                                            onclick="return confirm('Deactivate this user?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activate&id=<?php echo $user['id']; ?>"
                                                            class="btn btn-sm btn-success" title="Activate"
                                                            onclick="return confirm('Activate this user?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?php echo $user['id']; ?>"
                                                        class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Delete this user? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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