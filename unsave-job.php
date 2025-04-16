<?php
session_start();
include 'db.php';

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: login.php");
    exit();
}

// Check if saved job ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php?tab=saved-jobs");
    exit();
}

$user_id = $_SESSION['user_id'];
$saved_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if saved job exists and belongs to the user
$check_query = "SELECT * FROM saved_jobs WHERE saved_id = '$saved_id' AND user_id = '$user_id'";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) == 0) {
    header("Location: profile.php?tab=saved-jobs");
    exit();
}

// Delete saved job
$delete_query = "DELETE FROM saved_jobs WHERE saved_id = '$saved_id' AND user_id = '$user_id'";

if(mysqli_query($conn, $delete_query)) {
    header("Location: profile.php?tab=saved-jobs&success=Job removed from saved list");
} else {
    header("Location: profile.php?tab=saved-jobs&error=Failed to remove job from saved list");
}
?>
