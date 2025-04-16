<?php
session_start();
include 'db.php';

// Check if user is logged in and is a jobseeker
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: login.php");
    exit();
}

// Check if job ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: jobs.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if job exists and is active
$job_query = "SELECT * FROM jobs WHERE job_id = '$job_id' AND status = 'active'";
$job_result = mysqli_query($conn, $job_query);

if(mysqli_num_rows($job_result) == 0) {
    header("Location: jobs.php?error=Job not found or is no longer active");
    exit();
}

$job = mysqli_fetch_assoc($job_result);

// Check if user has already applied for this job
$check_query = "SELECT * FROM applications WHERE user_id = '$user_id' AND job_id = '$job_id'";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    header("Location: job-details.php?id=$job_id&error=You have already applied for this job");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cover_letter = mysqli_real_escape_string($conn, $_POST['cover_letter']);

    // Insert application
    $insert_query = "INSERT INTO applications (user_id, job_id, cover_letter, status, applied_at) 
                    VALUES ('$user_id', '$job_id', '$cover_letter', 'pending', NOW())";

    if(mysqli_query($conn, $insert_query)) {
        header("Location: job-details.php?id=$job_id&success=Application submitted successfully");
        exit();
    } else {
        $error = "Failed to submit application: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - Rozee.pk Clone</title>
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
        
        /* Apply Job Section */
        .apply-job-section {
            padding: 40px 0;
        }
        
        .apply-job-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .apply-job-header {
            margin-bottom: 30px;
        }
        
        .apply-job-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .job-details {
            background: #f9f9f9;
            border-radius: 8px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-height: 200px;
            resize: vertical;
            transition: border 0.3s ease;
        }
        
        .form-group textarea:focus {
            border-color: #e81c4f;
            outline: none;
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
        
        .cancel-btn {
            display: inline-block;
            padding: 12px 30px;
            background: transparent;
            color: #e81c4f;
            border: 1px solid #e81c4f;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: #f9e9ec;
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
            
            .job-meta {
                flex-direction: column;
                gap: 5px;
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
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <a href="profile.php" class="login-btn">My Profile</a>
                <a href="logout.php" class="signup-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Apply Job Section -->
    <section class="apply-job-section">
        <div class="container">
            <div class="apply-job-container">
                <div class="apply-job-header">
                    <h2>Apply for Job</h2>
                    <p>Complete the form below to apply for this job</p>
                </div>
                
                <div class="job-details">
                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                    
                    <div class="job-meta">
                        <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                        <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                        <span><i class="deadline-icon">⏱️</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                    </div>
                </div>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="apply-job.php?id=<?php echo $job_id; ?>" method="POST">
                    <div class="form-group">
                        <label for="cover_letter">Cover Letter / Additional Information</label>
                        <textarea id="cover_letter" name="cover_letter" placeholder="Introduce yourself and explain why you're a good fit for this position..." required></textarea>
                    </div>
                    
                    <div>
                        <button type="submit" class="submit-btn">Submit Application</button>
                        <a href="job-details.php?id=<?php echo $job_id; ?>" class="cancel-btn">Cancel</a>
                    </div>
                </form>
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
