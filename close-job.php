<?php
session_start();
include 'db.php';

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

// Check if job ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php?tab=posted-jobs");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if job exists and belongs to the employer
$job_query = "SELECT j.* 
             FROM jobs j 
             JOIN companies c ON j.company_id = c.company_id 
             WHERE j.job_id = '$job_id' AND c.user_id = '$user_id'";
$job_result = mysqli_query($conn, $job_query);

if(mysqli_num_rows($job_result) == 0) {
    header("Location: profile.php?tab=posted-jobs");
    exit();
}

// Update job status to closed
$update_query = "UPDATE jobs SET status = 'closed' WHERE job_id = '$job_id'";

if(mysqli_query($conn, $update_query)) {
    header("Location: profile.php?tab=posted-jobs&success=Job closed successfully");
} else {
    header("Location: profile.php?tab=posted-jobs&error=Failed to close job");
}
?>
