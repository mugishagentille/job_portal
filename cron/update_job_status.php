<?php
// This file should be run daily via cron job
// To set up: Add to crontab: 0 0 * * * php /path/to/cron/update_job_status.php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');

// Update jobs that should become active today
$query = "UPDATE jobs 
          SET status = 'open' 
          WHERE status = 'pending' 
          AND start_date IS NOT NULL 
          AND start_date <= :today";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$activated = $stmt->rowCount();

// Close jobs that have passed their end date
$query = "UPDATE jobs 
          SET status = 'closed' 
          WHERE status = 'open' 
          AND end_date IS NOT NULL 
          AND end_date < :today";
$stmt = $db->prepare($query);
$stmt->bindParam(':today', $today);
$stmt->execute();
$closed = $stmt->rowCount();

// Log the results
$log = date('Y-m-d H:i:s') . " - Activated: $activated, Closed: $closed\n";
file_put_contents(dirname(__DIR__) . '/logs/cron.log', $log, FILE_APPEND);

echo "Cron job completed. Activated: $activated, Closed: $closed\n";
