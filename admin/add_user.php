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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "Please fill in all required fields.";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if username exists
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $query = "INSERT INTO users (username, email, password, full_name, role, status, phone, address) 
                      VALUES (:username, :email, :password, :full_name, :role, :status, :phone, :address)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);

            if ($stmt->execute()) {
                $success = "User created successfully!";
                // Clear form after successful submission
                $_POST = array();
            } else {
                $error = "Failed to create user. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Add New User</h4>
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
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                                <span class="input-group-text" onclick="togglePassword('new_password', this)" style="cursor:pointer;">
                                    <i class="bi bi-eye-slash"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                <span class="input-group-text" onclick="togglePassword('confirm_password', this)" style="cursor:pointer;">
                                    <i class="bi bi-eye-slash"></i>
                                </span>
                            </div>
                            <div id="password-match-message" style="font-size: 0.95em; margin-top: 5px;"></div>
                        </div>



                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="applicant" <?php echo (isset($_POST['role']) && $_POST['role'] == 'applicant') ? 'selected' : ''; ?>>
                                        Job Seeker (Applicant)
                                    </option>
                                    <option value="employer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'employer') ? 'selected' : ''; ?>>
                                        Employer
                                    </option>
                                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>
                                        Administrator
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>
                                        Active
                                    </option>
                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> The user will receive an email notification with their login credentials (email functionality to be implemented).
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create User
                        </button>
                        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function togglePassword(fieldId, el) {
        const input = document.getElementById(fieldId);
        const icon = el.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            input.type = "password";
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const message = document.getElementById('password-match-message');

        function checkPasswordMatch() {
            if (!confirmPassword.value) {
                message.textContent = '';
                return;
            }
            if (newPassword.value === confirmPassword.value) {
                message.textContent = 'Passwords match';
                message.style.color = 'green';
            } else {
                message.textContent = 'Passwords do not match';
                message.style.color = 'red';
            }
        }

        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    });
</script>
<?php
require_once '../includes/footer.php';
?>