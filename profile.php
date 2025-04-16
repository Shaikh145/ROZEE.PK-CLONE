<?php
session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'personal-info';

// Fetch user data
$user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Handle profile picture upload
$profile_pic_error = '';
$profile_pic_success = '';

if(isset($_POST['update_profile_pic'])) {
    // Check if file was uploaded without errors
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['profile_pic']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if(in_array(strtolower($filetype), $allowed)) {
            // Check file size - 5MB maximum
            $maxsize = 5 * 1024 * 1024;
            if($_FILES['profile_pic']['size'] < $maxsize) {
                // Create upload directory if it doesn't exist
                $upload_dir = 'uploads/profile_pics/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Create unique filename
                $new_filename = uniqid() . '.' . $filetype;
                $destination = $upload_dir . $new_filename;
                
                // Move the uploaded file
                if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                    // Update database with new profile picture
                    $update_query = "UPDATE users SET profile_pic = '$destination' WHERE user_id = '$user_id'";
                    
                    if(mysqli_query($conn, $update_query)) {
                        $profile_pic_success = "Profile picture updated successfully!";
                        
                        // Update user data
                        $user_result = mysqli_query($conn, $user_query);
                        $user = mysqli_fetch_assoc($user_result);
                    } else {
                        $profile_pic_error = "Failed to update profile picture in database: " . mysqli_error($conn);
                    }
                } else {
                    $profile_pic_error = "Failed to upload profile picture. Please try again.";
                }
            } else {
                $profile_pic_error = "File is too large. Maximum size is 5MB.";
            }
        } else {
            $profile_pic_error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        $profile_pic_error = "Please select a file to upload.";
    }
}

// Handle personal info update
$personal_info_error = '';
$personal_info_success = '';

if(isset($_POST['update_personal_info'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Validate input
    if(empty($full_name) || empty($email)) {
        $personal_info_error = "Name and email are required";
    } else {
        // Check if email already exists for another user
        $email_check_query = "SELECT * FROM users WHERE email = '$email' AND user_id != '$user_id'";
        $email_check_result = mysqli_query($conn, $email_check_query);
        
        if(mysqli_num_rows($email_check_result) > 0) {
            $personal_info_error = "Email already exists for another user";
        } else {
            // Update user info
            $update_query = "UPDATE users SET full_name = '$full_name', email = '$email', phone = '$phone', address = '$address' WHERE user_id = '$user_id'";
            
            if(mysqli_query($conn, $update_query)) {
                $personal_info_success = "Personal information updated successfully!";
                
                // Update user data
                $user_result = mysqli_query($conn, $user_query);
                $user = mysqli_fetch_assoc($user_result);
            } else {
                $personal_info_error = "Failed to update personal information: " . mysqli_error($conn);
            }
        }
    }
}

// Handle resume upload
$resume_error = '';
$resume_success = '';

if(isset($_POST['update_resume'])) {
    // Check if file was uploaded without errors
    if(isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowed = array('pdf', 'doc', 'docx');
        $filename = $_FILES['resume']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if(in_array(strtolower($filetype), $allowed)) {
            // Check file size - 10MB maximum
            $maxsize = 10 * 1024 * 1024;
            if($_FILES['resume']['size'] < $maxsize) {
                // Create upload directory if it doesn't exist
                $upload_dir = 'uploads/resumes/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Create unique filename
                $new_filename = uniqid() . '.' . $filetype;
                $destination = $upload_dir . $new_filename;
                
                // Move the uploaded file
                if(move_uploaded_file($_FILES['resume']['tmp_name'], $destination)) {
                    // Update database with new resume
                    $update_query = "UPDATE users SET resume = '$destination' WHERE user_id = '$user_id'";
                    
                    if(mysqli_query($conn, $update_query)) {
                        $resume_success = "Resume updated successfully!";
                        
                        // Update user data
                        $user_result = mysqli_query($conn, $user_query);
                        $user = mysqli_fetch_assoc($user_result);
                    } else {
                        $resume_error = "Failed to update resume in database: " . mysqli_error($conn);
                    }
                } else {
                    $resume_error = "Failed to upload resume. Please try again.";
                }
            } else {
                $resume_error = "File is too large. Maximum size is 10MB.";
            }
        } else {
            $resume_error = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
        }
    } else {
        $resume_error = "Please select a file to upload.";
    }
}

// Handle company profile update (for employers)
$company_error = '';
$company_success = '';

if(isset($_POST['update_company'])) {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $industry = mysqli_real_escape_string($conn, $_POST['industry']);
    $company_size = mysqli_real_escape_string($conn, $_POST['company_size']);
    $company_website = mysqli_real_escape_string($conn, $_POST['company_website']);
    $company_description = mysqli_real_escape_string($conn, $_POST['company_description']);
    
    // Validate input
    if(empty($company_name) || empty($industry) || empty($company_description)) {
        $company_error = "Company name, industry, and description are required";
    } else {
        // Check if company profile exists
        $company_check_query = "SELECT * FROM companies WHERE user_id = '$user_id'";
        $company_check_result = mysqli_query($conn, $company_check_query);
        
        if(mysqli_num_rows($company_check_result) > 0) {
            // Update existing company profile
            $company = mysqli_fetch_assoc($company_check_result);
            $company_id = $company['company_id'];
            
            $update_query = "UPDATE companies SET 
                            company_name = '$company_name', 
                            industry = '$industry', 
                            company_size = '$company_size', 
                            website = '$company_website', 
                            description = '$company_description' 
                            WHERE company_id = '$company_id'";
            
            if(mysqli_query($conn, $update_query)) {
                $company_success = "Company profile updated successfully!";
            } else {
                $company_error = "Failed to update company profile: " . mysqli_error($conn);
            }
        } else {
            // Create new company profile
            $insert_query = "INSERT INTO companies (user_id, company_name, industry, company_size, website, description) 
                            VALUES ('$user_id', '$company_name', '$industry', '$company_size', '$company_website', '$company_description')";
            
            if(mysqli_query($conn, $insert_query)) {
                $company_success = "Company profile created successfully!";
            } else {
                $company_error = "Failed to create company profile: " . mysqli_error($conn);
            }
        }
    }
    
    // Handle company logo upload
    if(isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['company_logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if(in_array(strtolower($filetype), $allowed)) {
            // Check file size - 5MB maximum
            $maxsize = 5 * 1024 * 1024;
            if($_FILES['company_logo']['size'] < $maxsize) {
                // Create upload directory if it doesn't exist
                $upload_dir = 'uploads/company_logos/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Create unique filename
                $new_filename = uniqid() . '.' . $filetype;
                $destination = $upload_dir . $new_filename;
                
                // Move the uploaded file
                if(move_uploaded_file($_FILES['company_logo']['tmp_name'], $destination)) {
                    // Update database with new logo
                    $company_check_query = "SELECT * FROM companies WHERE user_id = '$user_id'";
                    $company_check_result = mysqli_query($conn, $company_check_query);
                    $company = mysqli_fetch_assoc($company_check_result);
                    $company_id = $company['company_id'];
                    
                    $update_query = "UPDATE companies SET logo = '$destination' WHERE company_id = '$company_id'";
                    
                    if(!mysqli_query($conn, $update_query)) {
                        $company_error = "Failed to update company logo in database: " . mysqli_error($conn);
                    }
                } else {
                    $company_error = "Failed to upload company logo. Please try again.";
                }
            } else {
                $company_error = "File is too large. Maximum size is 5MB.";
            }
        } else {
            $company_error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }
}

// Fetch company data for employers
$company = null;
if($user_type == 'employer') {
    $company_query = "SELECT * FROM companies WHERE user_id = '$user_id'";
    $company_result = mysqli_query($conn, $company_query);
    if(mysqli_num_rows($company_result) > 0) {
        $company = mysqli_fetch_assoc($company_result);
    }
}

// Fetch saved jobs for jobseekers
$saved_jobs = array();
if($user_type == 'jobseeker') {
    $saved_jobs_query = "SELECT s.*, j.title, j.location, j.job_type, j.deadline, c.company_name 
                        FROM saved_jobs s 
                        JOIN jobs j ON s.job_id = j.job_id 
                        JOIN companies c ON j.company_id = c.company_id 
                        WHERE s.user_id = '$user_id' 
                        ORDER BY s.saved_at DESC";
    $saved_jobs_result = mysqli_query($conn, $saved_jobs_query);
    while($job = mysqli_fetch_assoc($saved_jobs_result)) {
        $saved_jobs[] = $job;
    }
}

// Fetch applications for jobseekers
$applications = array();
if($user_type == 'jobseeker') {
    $applications_query = "SELECT a.*, j.title, j.location, j.job_type, c.company_name 
                          FROM applications a 
                          JOIN jobs j ON a.job_id = j.job_id 
                          JOIN companies c ON j.company_id = c.company_id 
                          WHERE a.user_id = '$user_id' 
                          ORDER BY a.applied_at DESC";
    $applications_result = mysqli_query($conn, $applications_query);
    while($application = mysqli_fetch_assoc($applications_result)) {
        $applications[] = $application;
    }
}

// Fetch posted jobs for employers
$posted_jobs = array();
if($user_type == 'employer' && $company) {
    $posted_jobs_query = "SELECT j.*, COUNT(a.application_id) as application_count 
                         FROM jobs j 
                         LEFT JOIN applications a ON j.job_id = a.job_id 
                         WHERE j.company_id = '{$company['company_id']}' 
                         GROUP BY j.job_id 
                         ORDER BY j.created_at DESC";
    $posted_jobs_result = mysqli_query($conn, $posted_jobs_query);
    while($job = mysqli_fetch_assoc($posted_jobs_result)) {
        $posted_jobs[] = $job;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Rozee.pk Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #e81c4f, #c70039);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .logo span {
            color: #ffcc00;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 4px;
        }
        
        nav ul li a:hover, nav ul li a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .auth-buttons a {
            display: inline-block;
            padding: 8px 16px;
            margin-left: 10px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-btn {
            background-color: transparent;
            border: 1px solid white;
            color: white;
        }
        
        .login-btn:hover {
            background-color: white;
            color: #e81c4f;
        }
        
        .signup-btn {
            background-color: #ffcc00;
            color: #333;
            font-weight: 600;
        }
        
        .signup-btn:hover {
            background-color: #e6b800;
        }
        
        /* Profile Section */
        .profile-section {
            padding: 40px 0;
        }
        
        .profile-container {
            display: flex;
            gap: 30px;
        }
        
        .profile-sidebar {
            flex: 1;
            max-width: 300px;
        }
        
        .profile-main {
            flex: 3;
        }
        
        .profile-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 5px solid #f5f5f5;
            position: relative;
        }
        
        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        
        .profile-picture:hover .profile-picture-overlay {
            opacity: 1;
        }
        
        .profile-picture-overlay span {
            color: white;
            font-weight: 500;
            text-align: center;
            padding: 0 10px;
        }
        
        .profile-info {
            text-align: center;
        }
        
        .profile-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: #666;
            margin-bottom: 15px;
        }
        
        .profile-type {
            display: inline-block;
            padding: 5px 10px;
            background: #e81c4f;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .profile-action-btn {
            display: block;
            padding: 10px;
            background: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .profile-action-btn:hover {
            background: #e0e0e0;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .profile-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .profile-tab:hover {
            color: #e81c4f;
        }
        
        .profile-tab.active {
            color: #e81c4f;
            border-bottom-color: #e81c4f;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .submit-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #e81c4f;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #c70039;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .job-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .job-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .job-company {
            color: #e81c4f;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .job-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            color: #666;
        }
        
        .job-info span {
            display: flex;
            align-items: center;
        }
        
        .job-info i {
            margin-right: 5px;
        }
        
        .job-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .job-btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .view-btn {
            background: #e81c4f;
            color: white;
        }
        
        .view-btn:hover {
            background: #c70039;
        }
        
        .unsave-btn, .withdraw-btn {
            background: transparent;
            color: #e81c4f;
            border: 1px solid #e81c4f;
        }
        
        .unsave-btn:hover, .withdraw-btn:hover {
            background: #f9e9ec;
        }
        
        .job-date {
            color: #888;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-closed {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .status-reviewed {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-shortlisted {
            background-color: #e0f2f1;
            color: #00897b;
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-accepted {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .no-items {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            color: #666;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        .footer-content {
            text-align: center;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .profile-container {
                flex-direction: column;
            }
            
            .profile-sidebar {
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
            }
            
            .auth-buttons {
                margin-top: 15px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">Rozee<span>.pk</span></a>
            
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="jobs.php">Jobs</a></li>
                    <li><a href="companies.php">Companies</a></li>
                    <?php if($user_type == 'employer'): ?>
                        <li><a href="post-job.php">Post a Job</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <a href="profile.php" class="login-btn active">My Profile</a>
                <a href="logout.php" class="signup-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-container">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-picture">
                            <img src="<?php echo !empty($user['profile_pic']) ? $user['profile_pic'] : 'images/default_avatar.jpg'; ?>" alt="Profile Picture">
                            <label for="profile_pic_input" class="profile-picture-overlay">
                                <span>Change Profile Picture</span>
                            </label>
                        </div>
                        
                        <div class="profile-info">
                            <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                            <span class="profile-type"><?php echo ucfirst($user_type); ?></span>
                        </div>
                        
                        <form action="profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                            <input type="file" id="profile_pic_input" name="profile_pic" onchange="this.form.submit()">
                            <input type="hidden" name="update_profile_pic" value="1">
                        </form>
                        
                        <div class="profile-actions">
                            <a href="change-password.php" class="profile-action-btn">Change Password</a>
                            <a href="logout.php" class="profile-action-btn">Logout</a>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Main Content -->
                <div class="profile-main">
                    <div class="profile-card">
                        <!-- Profile Tabs -->
                        <div class="profile-tabs">
                            <div class="profile-tab <?php echo $active_tab == 'personal-info' ? 'active' : ''; ?>" onclick="location.href='profile.php?tab=personal-info'">Personal Info</div>
                            
                            <?php if($user_type == 'jobseeker'): ?>
                                <div class="profile-tab <?php echo $active_tab == 'resume' ? 'active' : ''; ?>" onclick="location.href='profile.php?tab=resume'">Resume</div>
                                <div class="profile-tab <?php echo $active_tab == 'saved-jobs' ? 'active' : ''; ?>" onclick="location.href='profile.php?tab=saved-jobs'">Saved Jobs</div>
                                <div class="profile-tab <?php echo $active_tab == 'applications' ? 'active' : ''; ?>" onclick="location.href='profile.php?tab=applications'">Applications</div>
                            <?php endif; ?>
                            
                            <?php if($user_type == 'employer'): ?>
                                <div class="profile-tab <?php echo $active_tab == 'company-profile' ? 'active' : ''; ?>" onclick="location.href='profile.php?tab=company-profile'">Company Profile</div>
                                <div class="profile-tab <?php echo $active_tab == 'posted-jobs' ? 'active' : ''; ?>" onclick="location.href='profile.php?tab=posted-jobs'">Posted Jobs</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Personal Info Tab -->
                        <div class="tab-content <?php echo $active_tab == 'personal-info' ? 'active' : ''; ?>" id="personal-info">
                            <h3>Personal Information</h3>
                            <p>Update your personal information</p>
                            
                            <?php if(!empty($personal_info_error)): ?>
                                <div class="error-message"><?php echo $personal_info_error; ?></div>
                            <?php endif; ?>
                            
                            <?php if(!empty($personal_info_success)): ?>
                                <div class="success-message"><?php echo $personal_info_success; ?></div>
                            <?php endif; ?>
                            
                            <?php if(!empty($profile_pic_error)): ?>
                                <div class="error-message"><?php echo $profile_pic_error; ?></div>
                            <?php endif; ?>
                            
                            <?php if(!empty($profile_pic_success)): ?>
                                <div class="success-message"><?php echo $profile_pic_success; ?></div>
                            <?php endif; ?>
                            
                            <form action="profile.php?tab=personal-info" method="POST">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_personal_info" class="submit-btn">Update Information</button>
                            </form>
                        </div>
                        
                        <!-- Resume Tab (Jobseekers) -->
                        <?php if($user_type == 'jobseeker'): ?>
                            <div class="tab-content <?php echo $active_tab == 'resume' ? 'active' : ''; ?>" id="resume">
                                <h3>Resume</h3>
                                <p>Upload your resume to apply for jobs</p>
                                
                                <?php if(!empty($resume_error)): ?>
                                    <div class="error-message"><?php echo $resume_error; ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($resume_success)): ?>
                                    <div class="success-message"><?php echo $resume_success; ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($user['resume'])): ?>
                                    <div class="success-message">
                                        Your resume is uploaded. <a href="<?php echo $user['resume']; ?>" target="_blank">View Resume</a>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="profile.php?tab=resume" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="resume">Upload Resume (PDF, DOC, DOCX)</label>
                                        <input type="file" id="resume" name="resume" required>
                                    </div>
                                    
                                    <button type="submit" name="update_resume" class="submit-btn">Upload Resume</button>
                                </form>
                            </div>
                            
                            <!-- Saved Jobs Tab (Jobseekers) -->
                            <div class="tab-content <?php echo $active_tab == 'saved-jobs' ? 'active' : ''; ?>" id="saved-jobs">
                                <h3>Saved Jobs</h3>
                                <p>Jobs you have saved for later</p>
                                
                                <?php if(isset($_GET['success']) && $active_tab == 'saved-jobs'): ?>
                                    <div class="success-message"><?php echo $_GET['success']; ?></div>
                                <?php endif; ?>
                                
                                <?php if(isset($_GET['error']) && $active_tab == 'saved-jobs'): ?>
                                    <div class="error-message"><?php echo $_GET['error']; ?></div>
                                <?php endif; ?>
                                
                                <?php if(count($saved_jobs) > 0): ?>
                                    <?php foreach($saved_jobs as $job): ?>
                                        <div class="job-card">
                                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                            <p class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                            
                                            <div class="job-info">
                                                <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                                                <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                                                <span><i class="deadline-icon">⏱️</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                                            </div>
                                            
                                            <div class="job-actions">
                                                <div>
                                                    <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="job-btn view-btn">View Details</a>
                                                    <a href="unsave-job.php?id=<?php echo $job['saved_id']; ?>" class="job-btn unsave-btn">Unsave</a>
                                                </div>
                                                
                                                <div class="job-date">
                                                    Saved on <?php echo date('M d, Y', strtotime($job['saved_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-items">
                                        <h3>No saved jobs</h3>
                                        <p>You haven't saved any jobs yet. Browse jobs and save the ones you're interested in.</p>
                                        <p><a href="jobs.php" class="job-btn view-btn">Browse Jobs</a></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Applications Tab (Jobseekers) -->
                            <div class="tab-content <?php echo $active_tab == 'applications' ? 'active' : ''; ?>" id="applications">
                                <h3>My Applications</h3>
                                <p>Jobs you have applied for</p>
                                
                                <?php if(isset($_GET['success']) && $active_tab == 'applications'): ?>
                                    <div class="success-message"><?php echo $_GET['success']; ?></div>
                                <?php endif; ?>
                                
                                <?php if(isset($_GET['error']) && $active_tab == 'applications'): ?>
                                    <div class="error-message"><?php echo $_GET['error']; ?></div>
                                <?php endif; ?>
                                
                                <?php if(count($applications) > 0): ?>
                                    <?php foreach($applications as $application): ?>
                                        <div class="job-card">
                                            <?php 
                                            $status_class = '';
                                            switch($application['status']) {
                                                case 'pending':
                                                    $status_class = 'status-pending';
                                                    break;
                                                case 'reviewed':
                                                    $status_class = 'status-reviewed';
                                                    break;
                                                case 'shortlisted':
                                                    $status_class = 'status-shortlisted';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'status-rejected';
                                                    break;
                                                case 'accepted':
                                                    $status_class = 'status-accepted';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                            
                                            <h3 class="job-title"><?php echo htmlspecialchars($application['title']); ?></h3>
                                            <p class="job-company"><?php echo htmlspecialchars($application['company_name']); ?></p>
                                            
                                            <div class="job-info">
                                                <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($application['location']); ?></span>
                                                <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($application['job_type']); ?></span>
                                                <span><i class="date-icon">📅</i> Applied on: <?php echo date('M d, Y', strtotime($application['applied_at'])); ?></span>
                                            </div>
                                            
                                            <div class="job-actions">
                                                <div>
                                                    <a href="job-details.php?id=<?php echo $application['job_id']; ?>" class="job-btn view-btn">View Job</a>
                                                    
                                                    <?php if($application['status'] == 'pending'): ?>
                                                        <a href="withdraw-application.php?id=<?php echo $application['application_id']; ?>" class="  ?>
                                                        <a href="withdraw-application.php?id=<?php echo $application['application_id']; ?>" class="job-btn withdraw-btn">Withdraw Application</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-items">
                                        <h3>No applications</h3>
                                        <p>You haven't applied for any jobs yet. Browse jobs and start applying.</p>
                                        <p><a href="jobs.php" class="job-btn view-btn">Browse Jobs</a></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Company Profile Tab (Employers) -->
                        <?php if($user_type == 'employer'): ?>
                            <div class="tab-content <?php echo $active_tab == 'company-profile' ? 'active' : ''; ?>" id="company-profile">
                                <h3>Company Profile</h3>
                                <p>Update your company information</p>
                                
                                <?php if(!empty($company_error)): ?>
                                    <div class="error-message"><?php echo $company_error; ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($company_success)): ?>
                                    <div class="success-message"><?php echo $company_success; ?></div>
                                <?php endif; ?>
                                
                                <form action="profile.php?tab=company-profile" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="company_name">Company Name</label>
                                        <input type="text" id="company_name" name="company_name" value="<?php echo $company ? htmlspecialchars($company['company_name']) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="industry">Industry</label>
                                            <select id="industry" name="industry" required>
                                                <option value="">Select Industry</option>
                                                <option value="IT" <?php echo $company && $company['industry'] == 'IT' ? 'selected' : ''; ?>>Information Technology</option>
                                                <option value="Banking" <?php echo $company && $company['industry'] == 'Banking' ? 'selected' : ''; ?>>Banking/Financial Services</option>
                                                <option value="Telecommunications" <?php echo $company && $company['industry'] == 'Telecommunications' ? 'selected' : ''; ?>>Telecommunications</option>
                                                <option value="Healthcare" <?php echo $company && $company['industry'] == 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                                <option value="Education" <?php echo $company && $company['industry'] == 'Education' ? 'selected' : ''; ?>>Education</option>
                                                <option value="Manufacturing" <?php echo $company && $company['industry'] == 'Manufacturing' ? 'selected' : ''; ?>>Manufacturing</option>
                                                <option value="Retail" <?php echo $company && $company['industry'] == 'Retail' ? 'selected' : ''; ?>>Retail</option>
                                                <option value="Other" <?php echo $company && $company['industry'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="company_size">Company Size</label>
                                            <select id="company_size" name="company_size">
                                                <option value="">Select Company Size</option>
                                                <option value="1-10" <?php echo $company && $company['company_size'] == '1-10' ? 'selected' : ''; ?>>1-10 employees</option>
                                                <option value="11-50" <?php echo $company && $company['company_size'] == '11-50' ? 'selected' : ''; ?>>11-50 employees</option>
                                                <option value="51-200" <?php echo $company && $company['company_size'] == '51-200' ? 'selected' : ''; ?>>51-200 employees</option>
                                                <option value="201-500" <?php echo $company && $company['company_size'] == '201-500' ? 'selected' : ''; ?>>201-500 employees</option>
                                                <option value="501-1000" <?php echo $company && $company['company_size'] == '501-1000' ? 'selected' : ''; ?>>501-1000 employees</option>
                                                <option value="1000+" <?php echo $company && $company['company_size'] == '1000+' ? 'selected' : ''; ?>>1000+ employees</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="company_website">Company Website</label>
                                        <input type="url" id="company_website" name="company_website" value="<?php echo $company ? htmlspecialchars($company['website']) : ''; ?>" placeholder="https://example.com">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="company_description">Company Description</label>
                                        <textarea id="company_description" name="company_description" required><?php echo $company ? htmlspecialchars($company['description']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="company_logo">Company Logo (JPG, JPEG, PNG, GIF)</label>
                                        <?php if($company && !empty($company['logo'])): ?>
                                            <div style="margin-bottom: 10px;">
                                                <img src="<?php echo $company['logo']; ?>" alt="Company Logo" style="max-width: 200px; max-height: 100px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" id="company_logo" name="company_logo">
                                    </div>
                                    
                                    <button type="submit" name="update_company" class="submit-btn">Update Company Profile</button>
                                </form>
                            </div>
                            
                            <!-- Posted Jobs Tab (Employers) -->
                            <div class="tab-content <?php echo $active_tab == 'posted-jobs' ? 'active' : ''; ?>" id="posted-jobs">
                                <h3>Posted Jobs</h3>
                                <p>Jobs you have posted</p>
                                
                                <div style="margin-bottom: 20px;">
                                    <a href="post-job.php" class="submit-btn">Post a New Job</a>
                                </div>
                                
                                <?php if(count($posted_jobs) > 0): ?>
                                    <?php foreach($posted_jobs as $job): ?>
                                        <div class="job-card">
                                            <span class="status-badge <?php echo $job['status'] == 'active' ? 'status-active' : 'status-closed'; ?>">
                                                <?php echo ucfirst($job['status']); ?>
                                            </span>
                                            
                                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                            
                                            <div class="job-info">
                                                <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                                                <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                                                <span><i class="applications-icon">👥</i> <?php echo $job['application_count']; ?> Applications</span>
                                                <span><i class="deadline-icon">⏱️</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                                            </div>
                                            
                                            <div class="job-actions">
                                                <div>
                                                    <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="job-btn view-btn">View</a>
                                                    <a href="edit-job.php?id=<?php echo $job['job_id']; ?>" class="job-btn unsave-btn">Edit</a>
                                                    <a href="view-applications.php?job_id=<?php echo $job['job_id']; ?>" class="job-btn view-btn">View Applications</a>
                                                    
                                                    <?php if($job['status'] == 'active'): ?>
                                                        <a href="close-job.php?id=<?php echo $job['job_id']; ?>" class="job-btn unsave-btn">Close Job</a>
                                                    <?php else: ?>
                                                        <a href="activate-job.php?id=<?php echo $job['job_id']; ?>" class="job-btn view-btn">Activate Job</a>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="job-date">
                                                    Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-items">
                                        <h3>No jobs posted</h3>
                                        <p>You haven't posted any jobs yet.</p>
                                        <p><a href="post-job.php" class="job-btn view-btn">Post a Job</a></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <p>&copy; 2023 Rozee.pk Clone. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Handle profile picture upload
        document.getElementById('profile_pic_input').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>
