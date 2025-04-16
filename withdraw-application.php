<?php
session_start();
include 'db.php';

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: login.php");
    exit();
}

// Check if application ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php?tab=applications");
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if application exists and belongs to the user
$check_query = "SELECT * FROM applications WHERE application_id = '$application_id' AND user_id = '$user_id'";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) == 0) {
    header("Location: profile.php?tab=applications");
    exit();
}

$application = mysqli_fetch_assoc($check_result);

// Check if application status is pending
if($application['status'] != 'pending') {
    header("Location: profile.php?tab=applications&error=You can only withdraw pending applications");
    exit();
}

// Delete application
$delete_query = "DELETE FROM applications WHERE application_id = '$application_id' AND user_id = '$user_id'";

if(mysqli_query($conn, $delete_query)) {
    header("Location: profile.php?tab=applications&success=Application withdrawn successfully");
} else {
    header("Location: profile.php?tab=applications&error=Failed to withdraw application");
}
?>
