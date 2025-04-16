<?php
session_start();
include 'db.php';

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

// Check if job ID is provided
if(!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    header("Location: profile.php?tab=posted-jobs");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = mysqli_real_escape_string($conn, $_GET['job_id']);

// Check if job exists and belongs to the employer
$job_query = "SELECT j.*, c.company_name 
             FROM jobs j 
             JOIN companies c ON j.company_id = c.company_id 
             WHERE j.job_id = '$job_id' AND c.user_id = '$user_id'";
$job_result = mysqli_query($conn, $job_query);

if(mysqli_num_rows($job_result) == 0) {
    header("Location: profile.php?tab=posted-jobs");
    exit();
}

$job = mysqli_fetch_assoc($job_result);

// Get applications for the job
$applications_query = "SELECT a.*, u.full_name, u.email, u.phone 
                      FROM applications a 
                      JOIN users u ON a.user_id = u.user_id 
                      WHERE a.job_id = '$job_id' 
                      ORDER BY a.applied_at DESC";
$applications_result = mysqli_query($conn, $applications_query);

// Handle application status update
$status_updated = false;
$update_error = '';

if(isset($_POST['update_status'])) {
    $application_id = mysqli_real_escape_string($conn, $_POST['application_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE applications SET status = '$status' WHERE application_id = '$application_id' AND job_id = '$job_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $status_updated = true;
    } else {
        $update_error = "Failed to update application status: " . mysqli_error($conn);
    }
    
    // Refresh applications list
    $applications_result = mysqli_query($conn, $applications_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Rozee.pk Clone</title>
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
        
        /* Applications Section */
        .applications-section {
            padding: 40px 0;
        }
        
        .applications-header {
            margin-bottom: 30px;
        }
        
        .applications-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .job-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .job-title {
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
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #666;
        }
        
        .job-meta span {
            display: flex;
            align-items: center;
        }
        
        .job-meta i {
            margin-right: 5px;
        }
        
        .applications-count {
            margin-top: 15px;
            font-weight: 500;
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
        
        .applications-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .applications-table th, .applications-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .applications-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        
        .applications-table tr:last-child td {
            border-bottom: none;
        }
        
        .applications-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .applicant-name {
            font-weight: 500;
            color: #333;
        }
        
        .applicant-email, .applicant-phone {
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
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
        
        .application-actions {
            display: flex;
            gap: 10px;
        }
        
        .view-btn, .update-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
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
        
        .update-btn {
            background: #f0f0f0;
            color: #333;
            border: none;
            cursor: pointer;
        }
        
        .update-btn:hover {
            background: #e0e0e0;
        }
        
        .status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .status-select:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .no-applications {
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
            
            .applications-table {
                display: block;
                overflow-x: auto;
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
                    <li><a href="post-job.php">Post a Job</a></li>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <a href="profile.php" class="login-btn">My Profile</a>
                <a href="logout.php" class="signup-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <!-- Applications Section -->
    <section class="applications-section">
        <div class="container">
            <div class="applications-header">
                <h2>Job Applications</h2>
                <p>View and manage applications for your job posting</p>
            </div>
            
            <div class="job-details">
                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                <p class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></p>
                
                <div class="job-meta">
                    <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                    <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                    <span><i class="deadline-icon">⏱️</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                </div>
                
                <p class="applications-count">Total Applications: <?php echo mysqli_num_rows($applications_result); ?></p>
            </div>
            
            <?php if($status_updated): ?>
                <div class="status-updated">Application status updated successfully.</div>
            <?php endif; ?>
            
            <?php if(!empty($update_error)): ?>
                <div class="update-error"><?php echo $update_error; ?></div>
            <?php endif; ?>
            
            <?php if(mysqli_num_rows($applications_result) > 0): ?>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($application = mysqli_fetch_assoc($applications_result)): ?>
                            <tr>
                                <td>
                                    <div class="applicant-name"><?php echo htmlspecialchars($application['full_name']); ?></div>
                                </td>
                                <td>
                                    <div class="applicant-email"><?php echo htmlspecialchars($application['email']); ?></div>
                                    <?php if(!empty($application['phone'])): ?>
                                        <div class="applicant-phone"><?php echo htmlspecialchars($application['phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($application['applied_at'])); ?></td>
                                <td>
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
                                </td>
                                <td>
                                    <div class="application-actions">
                                        <a href="application-details.php?id=<?php echo $application['application_id']; ?>" class="view-btn">View Details</a>
                                        
                                        <form action="view-applications.php?job_id=<?php echo $job_id; ?>" method="POST" style="display: inline;">
                                            <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="">Update Status</option>
                                                <option value="pending" <?php echo $application['status'] == 'pending' ? 'disabled' : ''; ?>>Pending</option>
                                                <option value="reviewed" <?php echo $application['status'] == 'reviewed' ? 'disabled' : ''; ?>>Reviewed</option>
                                                <option value="shortlisted" <?php echo $application['status'] == 'shortlisted' ? 'disabled' : ''; ?>>Shortlisted</option>
                                                <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'disabled' : ''; ?>>Rejected</option>
                                                <option value="accepted" <?php echo $application['status'] == 'accepted' ? 'disabled' : ''; ?>>Accepted</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-applications">
                    <h3>No applications yet</h3>
                    <p>There are no applications for this job posting yet.</p>
                </div>
            <?php endif; ?>
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
