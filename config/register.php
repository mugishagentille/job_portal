<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'cashier')");
            if ($stmt->execute([$name, $email, $hashed])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SuperMarket Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, rgb(255, 254, 254) 0%, rgb(194, 197, 194) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h2 {
            color: #2E7D32;
            font-weight: 700;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }

        .btn-register {
            background: #2E7D32;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
        }

        .btn-register:hover {
            background: #1B5E20;
        }
    </style>
</head>

<body>
    <div class="register-card">
        <div class="register-header">
            <h2></i>Create Account</h2>
            <p class="text-muted">Join To Explore a lot</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
            <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
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
            <!--<div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password (min 6 characters)" required></div>
            <div class="mb-3"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required></div>-->
            <button type="submit" class="btn-register">Register</button>
        </form>
        <div class="text-center mt-3">Already have an account? <a href="login.php" style="color:#FFA000;">Login</a></div>
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
</body>

</html>