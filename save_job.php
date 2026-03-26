<?php
require_once '../includes/header.php';

if (!isLoggedIn() || !hasRole('applicant')) {
    header("Location: ../login.php");
    exit();
}

$job_id = isset($_GET['id']) ? $_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

// Check if already saved
$query = "SELECT id FROM saved_jobs WHERE user_id = :user_id AND job_id = :job_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    $query = "INSERT INTO saved_jobs (user_id, job_id) VALUES (:user_id, :job_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':job_id', $job_id);
    $stmt->execute();
}

header("Location: ../view_job.php?id=" . $job_id . "&msg=Job saved successfully");
exit();
