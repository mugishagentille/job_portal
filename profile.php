<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$user = getUserData($user_id);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Update basic info
    if (!empty($full_name) && !empty($email)) {
        $query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, address = :address 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':id', $user_id);

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully!";
            $user = getUserData($user_id);
        } else {
            $error = "Failed to update profile.";
        }
    }

    // Change password
    if (!empty($current_password) && !empty($new_password)) {
        if (password_verify($current_password, $user['password'])) {
            if ($new_password == $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user_id);

                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to change password.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $file_name = $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_types) && $_FILES["profile_image"]["size"] < 2000000) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $query = "UPDATE users SET profile_image = :profile_image WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':profile_image', $target_file);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $success = "Profile image updated!";
                $user = getUserData($user_id);
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid image format or size too large. Max 2MB, allowed: JPG, PNG, GIF";
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
    <div class="row">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php if ($user['profile_image'] && file_exists($user['profile_image'])): ?>
                        <img src="<?php echo $user['profile_image']; ?>" alt="Profile" class="rounded-circle mb-3" width="150" height="150">
                    <?php else: ?>
                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                            <i class="fas fa-user fa-5x text-white"></i>
                        </div>
                    <?php endif; ?>
                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if ($user['phone']): ?>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <h5>Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image"
                                    accept="image/*">
                                <small class="text-muted">Max size: 2MB (JPG, PNG, GIF)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5>Change Password</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
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


                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
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

<?php require_once 'includes/footer.php'; ?>