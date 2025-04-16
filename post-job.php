<?php
session_start();
include 'db.php';

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch company data
$company_query = "SELECT * FROM companies WHERE user_id = '$user_id'";
$company_result = mysqli_query($conn, $company_query);

// Check if employer has a company profile
if(mysqli_num_rows($company_result) == 0) {
    header("Location: profile.php?tab=company-profile");
    exit();
}

$company = mysqli_fetch_assoc($company_result);
$company_id = $company['company_id'];

// Handle job posting
$post_error = '';
$post_success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $requirements = mysqli_real_escape_string($conn, $_POST['requirements']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $job_type = mysqli_real_escape_string($conn, $_POST['job_type']);
    $salary_range = mysqli_real_escape_string($conn, $_POST['salary_range']);
    $experience = mysqli_real_escape_string($conn, $_POST['experience']);
    $education = mysqli_real_escape_string($conn, $_POST['education']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    
    // Validate input
    if(empty($title) || empty($description) || empty($requirements) || empty($location) || empty($job_type) || empty($experience) || empty($education) || empty($category) || empty($deadline)) {
        $post_error = "All fields are required except salary range";
    } else {
        // Insert job
        $insert_query = "INSERT INTO jobs (company_id, title, description, requirements, location, job_type, salary_range, experience, education, category, deadline) 
                        VALUES ('$company_id', '$title', '$description', '$requirements', '$location', '$job_type', '$salary_range', '$experience', '$education', '$category', '$deadline')";
        
        if(mysqli_query($conn, $insert_query)) {
            $job_id = mysqli_insert_id($conn);
            $post_success = "Job posted successfully! <a href='job-details.php?id=$job_id'>View Job</a>";
        } else {
            $post_error = "Failed to post job: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - Rozee.pk Clone</title>
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
        
        /* Post Job Section */
        .post-job-section {
            padding: 40px 0;
        }
        
        .post-job-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .post-job-header {
            margin-bottom: 30px;
        }
        
        .post-job-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .post-job-header p {
            color: #666;
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
        
        .post-job-btn {
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
        
        .post-job-btn:hover {
            background: #c70039;
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
                    <li><a href="post-job.php" class="active">Post a Job</a></li>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <a href="profile.php" class="login-btn">My Profile</a>
                <a href="logout.php" class="signup-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <!-- Post Job Section -->
    <section class="post-job-section">
        <div class="container">
            <div class="post-job-container">
                <div class="post-job-header">
                    <h2>Post a New Job</h2>
                    <p>Fill in the details below to post a new job opening at your company.</p>
                </div>
                
                <?php if(!empty($post_error)): ?>
                    <div class="error-message"><?php echo $post_error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($post_success)): ?>
                    <div class="success-message"><?php echo $post_success; ?></div>
                <?php endif; ?>
                
                <form action="post-job.php" method="POST">
                    <div class="form-group">
                        <label for="title">Job Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g. Senior Web Developer">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Job Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="IT">Information Technology</option>
                                <option value="Banking">Banking/Financial Services</option>
                                <option value="Telecommunications">Telecommunications</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Education">Education</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Retail">Retail</option>
                                <option value="Media">Media/Entertainment</option>
                                <option value="Construction">Construction</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_type">Job Type</label>
                            <select id="job_type" name="job_type" required>
                                <option value="">Select Job Type</option>
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Contract">Contract</option>
                                <option value="Internship">Internship</option>
                                <option value="Remote">Remote</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" required placeholder="e.g. Karachi, Pakistan">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description</label>
                        <textarea id="description" name="description" required placeholder="Provide a detailed description of the job role, responsibilities, and expectations..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements" required placeholder="List the skills, qualifications, and experience required for this position..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="experience">Experience Required</label>
                            <select id="experience" name="experience" required>
                                <option value="">Select Experience</option>
                                <option value="Fresh">Fresh/Entry Level</option>
                                <option value="1-2 years">1-2 years</option>
                                <option value="3-5 years">3-5 years</option>
                                <option value="5-10 years">5-10 years</option>
                                <option value="10+ years">10+ years</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="education">Education Required</label>
                            <select id="education" name="education" required>
                                <option value="">Select Education</option>
                                <option value="High School">High School</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Bachelors">Bachelors</option>
                                <option value="Masters">Masters</option>
                                <option value="PhD">PhD</option>
                                <option value="Any">Any</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_range">Salary Range (Optional)</label>
                            <input type="text" id="salary_range" name="salary_range" placeholder="e.g. 50,000-70,000 PKR">
                        </div>
                        
                        <div class="form-group">
                            <label for="deadline">Application Deadline</label>
                            <input type="date" id="deadline" name="deadline" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="post-job-btn">Post Job</button>
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
    
    <script>
        // Set minimum date for deadline to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').min = today;
    </script>
</body>
</html>
