<?php
session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if application ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    if($_SESSION['user_type'] == 'employer') {
        header("Location: profile.php?tab=posted-jobs");
    } else {
        header("Location: profile.php?tab=applications");
    }
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$application_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch application details
if($user_type == 'employer') {
    // For employers, check if the application is for their job
    $application_query = "SELECT a.*, j.title as job_title, j.location, j.job_type, j.company_id, 
                         u.full_name, u.email, u.phone, u.resume, u.profile_pic 
                         FROM applications a 
                         JOIN jobs j ON a.job_id = j.job_id 
                         JOIN users u ON a.user_id = u.user_id 
                         JOIN companies c ON j.company_id = c.company_id 
                         WHERE a.application_id = '$application_id' AND c.user_id = '$user_id'";
} else {
    // For jobseekers, check if the application is theirs
    $application_query = "SELECT a.*, j.title as job_title, j.location, j.job_type, 
                         c.company_name, c.logo 
                         FROM applications a 
                         JOIN jobs j ON a.job_id = j.job_id 
                         JOIN companies c ON j.company_id = c.company_id 
                         WHERE a.application_id = '$application_id' AND a.user_id = '$user_id'";
}

$application_result = mysqli_query($conn, $application_query);

if(mysqli_num_rows($application_result) == 0) {
    if($user_type == 'employer') {
        header("Location: profile.php?tab=posted-jobs");
    } else {
        header("Location: profile.php?tab=applications");
    }
    exit();
}

$application = mysqli_fetch_assoc($application_result);

// Handle status update (for employers)
$status_updated = false;
$update_error = '';

if($user_type == 'employer' && isset($_POST['update_status'])) {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE applications SET status = '$status' WHERE application_id = '$application_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $status_updated = true;
        
        // Refresh application data
        $application_result = mysqli_query($conn, $application_query);
        $application = mysqli_fetch_assoc($application_result);
    } else {
        $update_error = "Failed to update application status: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - Rozee.pk Clone</title>
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
        
        nav ul li a:hover {
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
        
        /* Application Details Section */
        .application-section {
            padding: 40px 0;
        }
        
        .application-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .application-header {
            margin-bottom: 30px;
        }
        
        .application-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .status-updated {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .update-error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
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
        
        .job-details, .applicant-details {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .job-title, .applicant-name {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .job-company {
            color: #e81c4f;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .job-meta, .applicant-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .job-meta span, .applicant-meta span {
            display: flex;
            align-items: center;
        }
        
        .job-meta i, .applicant-meta i {
            margin-right: 5px;
        }
        
        .applicant-profile {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .applicant-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }
        
        .applicant-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .applicant-info {
            flex: 1;
        }
        
        .cover-letter {
            margin-bottom: 30px;
        }
        
        .cover-letter h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .cover-letter-content {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            white-space: pre-line;
        }
        
        .application-actions {
            margin-top: 30px;
        }
        
        .action-btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .primary-btn {
            background: #e81c4f;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .primary-btn:hover {
            background: #c70039;
        }
        
        .secondary-btn {
            background: transparent;
            color: #e81c4f;
            border: 1px solid #e81c4f;
        }
        
        .secondary-btn:hover {
            background: #f9e9ec;
        }
        
        .status-form {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .status-form h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .status-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .status-select:focus {
            border-color: #e81c4f;
            outline: none;
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
            
            .job-meta, .applicant-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .applicant-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .applicant-avatar {
                margin-right: 0;
                margin-bottom: 15px;
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
                <a href="profile.php" class="login-btn">My Profile</a>
                <a href="logout.php" class="signup-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <!-- Application Details Section -->
    <section class="application-section">
        <div class="container">
            <div class="application-container">
                <div class="application-header">
                    <h2>Application Details</h2>
                    <p>View details of the job application</p>
                </div>
                
                <?php if($status_updated): ?>
                    <div class="status-updated">Application status updated successfully.</div>
                <?php endif; ?>
                
                <?php if(!empty($update_error)): ?>
                    <div class="update-error"><?php echo $update_error; ?></div>
                <?php endif; ?>
                
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
                    Status: <?php echo ucfirst($application['status']); ?>
                </span>
                
                <!-- Job Details -->
                <div class="job-details">
                    <h3 class="job-title"><?php echo htmlspecialchars($application['job_title']); ?></h3>
                    
                    <?php if($user_type == 'jobseeker'): ?>
                        <p class="job-company"><?php echo htmlspecialchars($application['company_name']); ?></p>
                    <?php endif; ?>
                    
                    <div class="job-meta">
                        <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($application['location']); ?></span>
                        <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($application['job_type']); ?></span>
                        <span><i class="date-icon">📅</i> Applied on: <?php echo date('M d, Y', strtotime($application['applied_at'])); ?></span>
                    </div>
                </div>
                
                <?php if($user_type == 'employer'): ?>
                    <!-- Applicant Details (for employers) -->
                    <div class="applicant-details">
                        <div class="applicant-profile">
                            <div class="applicant-avatar">
                                <img src="<?php echo !empty($application['profile_pic']) ? $application['profile_pic'] : 'images/default_avatar.jpg'; ?>" alt="Applicant Profile">
                            </div>
                            
                            <div class="applicant-info">
                                <h3 class="applicant-name"><?php echo htmlspecialchars($application['full_name']); ?></h3>
                                
                                <div class="applicant-meta">
                                    <span><i class="email-icon">✉️</i> <?php echo htmlspecialchars($application['email']); ?></span>
                                    <?php if(!empty($application['phone'])): ?>
                                        <span><i class="phone-icon">📞</i> <?php echo htmlspecialchars($application['phone']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(!empty($application['resume'])): ?>
                            <div class="applicant-resume">
                                <a href="<?php echo $application['resume']; ?>" target="_blank" class="action-btn secondary-btn">View Resume</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Cover Letter -->
                <div class="cover-letter">
                    <h3>Cover Letter / Additional Information</h3>
                    <div class="cover-letter-content">
                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                    </div>
                </div>
                
                <!-- Application Actions -->
                <div class="application-actions">
                    <?php if($user_type == 'employer'): ?>
                        <a href="view-applications.php?job_id=<?php echo $application['job_id']; ?>" class="action-btn secondary-btn">Back to Applications</a>
                        
                        <!-- Status Update Form -->
                        <div class="status-form">
                            <h3>Update Application Status</h3>
                            <form action="application-details.php?id=<?php echo $application_id; ?>" method="POST">
                                <select name="status" class="status-select">
                                    <option value="pending" <?php echo $application['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $application['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="shortlisted" <?php echo $application['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                    <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="accepted" <?php echo $application['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                </select>
                                <button type="submit" name="update_status" class="action-btn primary-btn">Update Status</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <a href="profile.php?tab=applications" class="action-btn secondary-btn">Back to My Applications</a>
                        <a href="job-details.php?id=<?php echo $application['job_id']; ?>" class="action-btn primary-btn">View Job</a>
                        
                        <?php if($application['status'] == 'pending'): ?>
                            <a href="withdraw-application.php?id=<?php echo $application_id; ?>" class="action-btn secondary-btn">Withdraw Application</a>
                        <?php endif; ?>
                    <?php endif; ?>
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
</body>
</html>
