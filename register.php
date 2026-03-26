<?php
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];

    // Validation
    if ($password != $confirm_password) {
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
            $query = "INSERT INTO users (username, email, password, full_name, role) 
                      VALUES (:username, :email, :password, :full_name, :role)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h3 class="text-center mb-0">Create Account</h3>
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
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
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

                        <div class="mb-3">
                            <label for="role" class="form-label">I want to</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="applicant">Find a Job (Job Seeker)</option>
                                <option value="employer">Hire Employees (Employer)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
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

<?php require_once 'includes/footer.php'; ?>