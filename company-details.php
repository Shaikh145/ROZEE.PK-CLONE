<?php
session_start();
include 'db.php';

// Check if company ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: companies.php");
    exit();
}

$company_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch company details
$company_query = "SELECT * FROM companies WHERE company_id = '$company_id'";
$company_result = mysqli_query($conn, $company_query);

if(mysqli_num_rows($company_result) == 0) {
    header("Location: companies.php");
    exit();
}

$company = mysqli_fetch_assoc($company_result);

// Fetch active jobs from this company
$jobs_query = "SELECT * FROM jobs WHERE company_id = '$company_id' AND status = 'active' ORDER BY created_at DESC";
$jobs_result = mysqli_query($conn, $jobs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company['company_name']); ?> - Rozee.pk Clone</title>
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
        
        /* Company Details Section */
        .company-section {
            padding: 40px 0;
        }
        
        .company-header {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .company-logo {
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .company-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .company-industry {
            color: #e81c4f;
            font-weight: 500;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .company-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
            color: #666;
        }
        
        .company-meta span {
            display: flex;
            align-items: center;
        }
        
        .company-meta i {
            margin-right: 5px;
        }
        
        .company-website {
            display: inline-block;
            color: #e81c4f;
            text-decoration: none;
            font-weight: 500;
        }
        
        .company-website:hover {
            text-decoration: underline;
        }
        
        .company-description {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .company-description h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .company-description p {
            color: #666;
            white-space: pre-line;
        }
        
        .company-jobs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .company-jobs h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .job-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .job-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
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
        
        .view-job-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #e81c4f;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .view-job-btn:hover {
            background: #c70039;
        }
        
        .job-date {
            color: #888;
            font-size: 14px;
        }
        
        .no-jobs {
            text-align: center;
            color: #666;
            padding: 20px;
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
            
            .company-header {
                flex-direction: column;
                text-align: center;
            }
            
            .company-meta {
                justify-content: center;
            }
            
            .job-info {
                flex-direction: column;
                gap: 5px;
            }
            
            .job-actions {
                flex-direction: column;
                gap: 10px;
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
    
    <!-- Company Details Section -->
    <section class="company-section">
        <div class="container">
            <div class="company-header">
                <div class="company-logo">
                    <img src="<?php echo !empty($company['logo']) ? $company['logo'] : 'images/default_company.png'; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>">
                </div>
                
                <div class="company-info">
                    <h1 class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></h1>
                    <p class="company-industry"><?php echo htmlspecialchars($company['industry']); ?></p>
                    
                    <div class="company-meta">
                        <?php if(!empty($company['company_size'])): ?>
                            <span><i class="size-icon">👥</i> <?php echo htmlspecialchars($company['company_size']); ?> employees</span>
                        <?php endif; ?>
                        
                        <span><i class="jobs-icon">💼</i> <?php echo mysqli_num_rows($jobs_result); ?> active jobs</span>
                    </div>
                    
                    <?php if(!empty($company['website'])): ?>
                        <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="company-website">Visit Website</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="company-description">
                <h3>About <?php echo htmlspecialchars($company['company_name']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
            </div>
            
            <div class="company-jobs">
                <h3>Open Positions at <?php echo htmlspecialchars($company['company_name']); ?></h3>
                
                <?php if(mysqli_num_rows($jobs_result) > 0): ?>
                    <?php while($job = mysqli_fetch_assoc($jobs_result)): ?>
                        <div class="job-card">
                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                            
                            <div class="job-info">
                                <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                                <span><i class="experience-icon">⏱️</i> Experience: <?php echo htmlspecialchars($job['experience']); ?></span>
                                <span><i class="deadline-icon">📅</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                            </div>
                            
                            <div class="job-actions">
                                <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="view-job-btn">View Details</a>
                                <span class="job-date">Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-jobs">
                        <p>No open positions available at this time.</p>
                    </div>
                <?php endif; ?>
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
