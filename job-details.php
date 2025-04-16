<?php
session_start();
include 'db.php';

// Check if job ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: jobs.php");
    exit();
}

$job_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch job details
$job_query = "SELECT j.*, c.company_name, c.logo, c.company_id 
             FROM jobs j 
             JOIN companies c ON j.company_id = c.company_id 
             WHERE j.job_id = '$job_id'";
$job_result = mysqli_query($conn, $job_query);

if(mysqli_num_rows($job_result) == 0) {
    header("Location: jobs.php");
    exit();
}

$job = mysqli_fetch_assoc($job_result);

// Check if user has already applied for this job
$has_applied = false;
if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker') {
    $user_id = $_SESSION['user_id'];
    $check_query = "SELECT * FROM applications WHERE user_id = '$user_id' AND job_id = '$job_id'";
    $check_result = mysqli_query($conn, $check_query);
    $has_applied = mysqli_num_rows($check_result) > 0;
}

// Check if user has saved this job
$has_saved = false;
if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker') {
    $user_id = $_SESSION['user_id'];
    $check_saved_query = "SELECT * FROM saved_jobs WHERE user_id = '$user_id' AND job_id = '$job_id'";
    $check_saved_result = mysqli_query($conn, $check_saved_query);
    $has_saved = mysqli_num_rows($check_saved_result) > 0;
}

// Get similar jobs
$similar_jobs_query = "SELECT j.*, c.company_name 
                      FROM jobs j 
                      JOIN companies c ON j.company_id = c.company_id 
                      WHERE j.category = '{$job['category']}' 
                      AND j.job_id != '$job_id' 
                      AND j.status = 'active' 
                      LIMIT 3";
$similar_jobs_result = mysqli_query($conn, $similar_jobs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - Rozee.pk Clone</title>
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
        
        /* Job Details Section */
        .job-details-section {
            padding: 40px 0;
        }
        
        .job-details-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .job-main {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .job-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .company-logo {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            overflow: hidden;
        }
        
        .company-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .job-title-company h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .company-name {
            color: #e81c4f;
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            color: #666;
        }
        
        .job-meta-item i {
            margin-right: 5px;
        }
        
        .job-description {
            margin-bottom: 30px;
        }
        
        .job-description h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .job-description p {
            color: #666;
            margin-bottom: 15px;
            white-space: pre-line;
        }
        
        .job-requirements {
            margin-bottom: 30px;
        }
        
        .job-requirements h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .job-requirements ul {
            padding-left: 20px;
            color: #666;
        }
        
        .job-requirements ul li {
            margin-bottom: 10px;
        }
        
        .job-sidebar {
            align-self: start;
        }
        
        .job-actions {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 12px 0;
            text-align: center;
            border-radius: 4px;
            font-weight: 600;
            margin-bottom: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .apply-btn {
            background: #e81c4f;
            color: white;
            border: none;
        }
        
        .apply-btn:hover {
            background: #c70039;
        }
        
        .applied-btn {
            background: #4caf50;
            color: white;
            cursor: default;
        }
        
        .save-btn {
            background: transparent;
            color: #e81c4f;
            border: 1px solid #e81c4f;
        }
        
        .save-btn:hover {
            background: #f9e9ec;
        }
        
        .saved-btn {
            background: #f9e9ec;
            color: #e81c4f;
            border: 1px solid #e81c4f;
        }
        
        .job-info-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .job-info-card h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .job-info-list {
            list-style: none;
        }
        
        .job-info-item {
            display: flex;
            margin-bottom: 15px;
        }
        
        .job-info-label {
            width: 120px;
            font-weight: 500;
            color: #333;
        }
        
        .job-info-value {
            flex: 1;
            color: #666;
        }
        
        .similar-jobs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .similar-jobs h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .similar-job-card {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .similar-job-card:last-child {
            border-bottom: none;
        }
        
        .similar-job-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .similar-job-company {
            color: #e81c4f;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .similar-job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .similar-job-link {
            color: #e81c4f;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .similar-job-link:hover {
            text-decoration: underline;
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
            
            .job-details-container {
                grid-template-columns: 1fr;
            }
            
            .job-header {
                flex-direction: column;
                text-align: center;
            }
            
            .company-logo {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .job-meta {
                justify-content: center;
            }
            
            .job-info-item {
                flex-direction: column;
            }
            
            .job-info-label {
                width: 100%;
                margin-bottom: 5px;
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
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'employer'): ?>
                        <li><a href="post-job.php">Post a Job</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="login-btn">My Profile</a>
                    <a href="logout.php" class="signup-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="register.php" class="signup-btn">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Job Details Section -->
    <section class="job-details-section">
        <div class="container">
            <?php if(isset($_GET['error'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <div class="job-details-container">
                <div class="job-main">
                    <div class="job-header">
                        <div class="company-logo">
                            <img src="<?php echo !empty($job['logo']) ? $job['logo'] : 'images/default_company.png'; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>">
                        </div>
                        
                        <div class="job-title-company">
                            <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                            <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                        </div>
                    </div>
                    
                    <div class="job-meta">
                        <div class="job-meta-item">
                            <i class="location-icon">📍</i>
                            <?php echo htmlspecialchars($job['location']); ?>
                        </div>
                        
                        <div class="job-meta-item">
                            <i class="job-type-icon">💼</i>
                            <?php echo htmlspecialchars($job['job_type']); ?>
                        </div>
                        
                        <div class="job-meta-item">
                            <i class="salary-icon">💰</i>
                            <?php echo htmlspecialchars($job['salary']); ?>
                        </div>
                        
                        <div class="job-meta-item">
                            <i class="experience-icon">⏱️</i>
                            Experience: <?php echo htmlspecialchars($job['experience']); ?>
                        </div>
                        
                        <div class="job-meta-item">
                            <i class="deadline-icon">📅</i>
                            Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?>
                        </div>
                    </div>
                    
                    <div class="job-description">
                        <h2>Job Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                    </div>
                    
                    <div class="job-requirements">
                        <h2>Requirements</h2>
                        <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                    </div>
                </div>
                
                <div class="job-sidebar">
                    <div class="job-actions">
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker'): ?>
                            <?php if($has_applied): ?>
                                <span class="action-btn applied-btn">Already Applied</span>
                            <?php else: ?>
                                <a href="apply-job.php?id=<?php echo $job_id; ?>" class="action-btn apply-btn">Apply Now</a>
                            <?php endif; ?>
                            
                            <?php if($has_saved): ?>
                                <a href="unsave-job.php?id=<?php echo $job_id; ?>&redirect=job-details" class="action-btn saved-btn">Saved ✓</a>
                            <?php else: ?>
                                <a href="save-job.php?id=<?php echo $job_id; ?>&redirect=job-details" class="action-btn save-btn">Save Job</a>
                            <?php endif; ?>
                        <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'employer'): ?>
                            <?php if($job['company_id'] == $company_id): ?>
                                <a href="edit-job.php?id=<?php echo $job_id; ?>" class="action-btn save-btn">Edit Job</a>
                                <a href="view-applications.php?job_id=<?php echo $job_id; ?>" class="action-btn apply-btn">View Applications</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="action-btn apply-btn">Login to Apply</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="job-info-card">
                        <h3>Job Overview</h3>
                        <ul class="job-info-list">
                            <li class="job-info-item">
                                <span class="job-info-label">Posted Date:</span>
                                <span class="job-info-value"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                            </li>
                            
                            <li class="job-info-item">
                                <span class="job-info-label">Job Type:</span>
                                <span class="job-info-value"><?php echo htmlspecialchars($job['job_type']); ?></span>
                            </li>
                            
                            <li class="job-info-item">
                                <span class="job-info-label">Salary:</span>
                                <span class="job-info-value"><?php echo htmlspecialchars($job['salary']); ?></span>
                            </li>
                            
                            <li class="job-info-item">
                                <span class="job-info-label">Location:</span>
                                <span class="job-info-value"><?php echo htmlspecialchars($job['location']); ?></span>
                            </li>
                            
                            <li class="job-info-item">
                                <span class="job-info-label">Category:</span>
                                <span class="job-info-value"><?php echo htmlspecialchars($job['category']); ?></span>
                            </li>
                            
                            <li class="job-info-item">
                                <span class="job-info-label">Experience:</span>
                                <span class="job-info-value"><?php echo htmlspecialchars($job['experience']); ?></span>
                            </li>
                            
                            <li class="job-info-item">
                                <span class="job-info-label">Deadline:</span>
                                <span class="job-info-value"><?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="similar-jobs">
                        <h3>Similar Jobs</h3>
                        
                        <?php if(mysqli_num_rows($similar_jobs_result) > 0): ?>
                            <?php while($similar_job = mysqli_fetch_assoc($similar_jobs_result)): ?>
                                <div class="similar-job-card">
                                    <h4 class="similar-job-title"><?php echo htmlspecialchars($similar_job['title']); ?></h4>
                                    <p class="similar-job-company"><?php echo htmlspecialchars($similar_job['company_name']); ?></p>
                                    
                                    <div class="similar-job-meta">
                                        <span><?php echo htmlspecialchars($similar_job['location']); ?></span>
                                        <span><?php echo htmlspecialchars($similar_job['job_type']); ?></span>
                                    </div>
                                    
                                    <a href="job-details.php?id=<?php echo $similar_job['job_id']; ?>" class="similar-job-link">View Job</a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No similar jobs found.</p>
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
</body>
</html>
