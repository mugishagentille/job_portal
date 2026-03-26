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

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id == 0) {
    redirect('admin/manage_users.php');
}

$database = new Database();
$db = $database->getConnection();

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    redirect('admin/manage_users.php');
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($full_name)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if username/email exists for other users
        $check_query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $check_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $error = "Username or email already exists!";
        } else {
            // Prepare update query based on whether password is being changed
            if (!empty($new_password)) {
                if ($new_password != $confirm_password) {
                    $error = "New passwords do not match!";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET 
                                    username = :username, 
                                    email = :email, 
                                    full_name = :full_name, 
                                    role = :role, 
                                    status = :status, 
                                    phone = :phone, 
                                    address = :address, 
                                    password = :password 
                                    WHERE id = :id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                }
            } else {
                $update_query = "UPDATE users SET 
                                username = :username, 
                                email = :email, 
                                full_name = :full_name, 
                                role = :role, 
                                status = :status, 
                                phone = :phone, 
                                address = :address 
                                WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
            }

            if (!isset($error) || empty($error)) {
                // Bind common parameters
                $update_stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $update_stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
                $update_stmt->bindParam(':role', $role, PDO::PARAM_STR);
                $update_stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $update_stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                $update_stmt->bindParam(':address', $address, PDO::PARAM_STR);
                $update_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

                if ($update_stmt->execute()) {
                    $success = "User updated successfully!";
                    // Refresh user data
                    $refresh_query = "SELECT * FROM users WHERE id = :id";
                    $refresh_stmt = $db->prepare($refresh_query);
                    $refresh_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                    $refresh_stmt->execute();
                    $user = $refresh_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to update user. Please try again.";
                }
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <a href="manage_users.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="applicant" <?php echo $user['role'] == 'applicant' ? 'selected' : ''; ?>>
                                        Job Seeker (Applicant)
                                    </option>
                                    <option value="employer" <?php echo $user['role'] == 'employer' ? 'selected' : ''; ?>>
                                        Employer
                                    </option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>
                                        Administrator
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>
                                        Active
                                    </option>
                                    <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5>Change Password (Optional)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> Be careful when changing user roles or status. This affects what the user can access.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>

                        <?php if ($user['role'] != 'admin'): ?>
                            <a href="?action=delete&id=<?php echo $user['id']; ?>"
                                class="btn btn-danger float-end"
                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete User
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>