<?php
session_start();
include 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a job seeker to save jobs']);
    exit();
}

// Check if job ID is provided
if(!isset($_POST['job_id']) || empty($_POST['job_id'])) {
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = mysqli_real_escape_string($conn, $_POST['job_id']);

// Check if job exists
$job_query = "SELECT * FROM jobs WHERE job_id = '$job_id'";
$job_result = mysqli_query($conn, $job_query);

if(mysqli_num_rows($job_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Job not found']);
    exit();
}

// Check if job is already saved
$check_query = "SELECT * FROM saved_jobs WHERE user_id = '$user_id' AND job_id = '$job_id'";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already saved this job']);
    exit();
}

// Save job
$save_query = "INSERT INTO saved_jobs (user_id, job_id) VALUES ('$user_id', '$job_id')";

if(mysqli_query($conn, $save_query)) {
    echo json_encode(['success' => true, 'message' => 'Job saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save job: ' . mysqli_error($conn)]);
}
?>
