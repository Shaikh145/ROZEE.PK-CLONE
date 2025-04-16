<?php
session_start();
include 'db.php';

// Check if user is logged in and is an employer
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: login.php");
    exit();
}

// Check if job ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php?tab=posted-jobs");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if job exists and belongs to the employer
$job_query = "SELECT j.* 
             FROM jobs j 
             JOIN companies c ON j.company_id = c.company_id 
             WHERE j.job_id = '$job_id' AND c.user_id = '$user_id'";
$job_result = mysqli_query($conn, $job_query);

if(mysqli_num_rows($job_result) == 0) {
    header("Location: profile.php?tab=posted-jobs");
    exit();
}

$job = mysqli_fetch_assoc($job_result);

// Handle job update
$update_error = '';
$update_success = '';

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
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate input
    if(empty($title) || empty($description) || empty($requirements) || empty($location) || empty($job_type) || empty($experience) || empty($education) || empty($category) || empty($deadline)) {
        $update_error = "All fields are required except salary range";
    } else {
        // Update job
        $update_query = "UPDATE jobs SET 
                        title = '$title', 
                        description = '$description', 
                        requirements = '$requirements', 
                        location = '$location', 
                        job_type = '$job_type', 
                        salary_range = '$salary_range', 
                        experience = '$experience', 
                        education = '$education', 
                        category = '$category', 
                        deadline = '$deadline', 
                        status = '$status' 
                        WHERE job_id = '$job_id'";
        
        if(mysqli_query($conn, $update_query)) {
            $update_success = "Job updated successfully! <a href='job-details.php?id=$job_id'>View Job</a>";
            
            // Refresh job data
            $job_result = mysqli_query($conn, $job_query);
            $job = mysqli_fetch_assoc($job_result);
        } else {
            $update_error = "Failed to update job: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Rozee.pk Clone</title>
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
        
        /* Edit Job Section */
        .edit-job-section {
            padding: 40px 0;
        }
        
        .edit-job-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .edit-job-header {
            margin-bottom: 30px;
        }
        
        .edit-job-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .edit-job-header p {
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
        
        .update-job-btn {
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
        
        .update-job-btn:hover {
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
                    <li><a href="post-job.php">Post a Job</a></li>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <a href="profile.php" class="login-btn">My Profile</a>
                <a href="logout.php" class="signup-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <!-- Edit Job Section -->
    <section class="edit-job-section">
        <div class="container">
            <div class="edit-job-container">
                <div class="edit-job-header">
                    <h2>Edit Job Posting</h2>
                    <p>Update the details of your job posting</p>
                </div>
                
                <?php if(!empty($update_error)): ?>
                    <div class="error-message"><?php echo $update_error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($update_success)): ?>
                    <div class="success-message"><?php echo $update_success; ?></div>
                <?php endif; ?>
                
                <form action="edit-job.php?id=<?php echo $job_id; ?>" method="POST">
                    <div class="form-group">
                        <label for="title">Job Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Job Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="IT" <?php echo $job['category'] == 'IT' ? 'selected' : ''; ?>>Information Technology</option>
                                <option value="Banking" <?php echo $job['category'] == 'Banking' ? 'selected' : ''; ?>>Banking/Financial Services</option>
                                <option value="Telecommunications" <?php echo $job['category'] == 'Telecommunications' ? 'selected' : ''; ?>>Telecommunications</option>
                                <option value="Healthcare" <?php echo $job['category'] == 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="Education" <?php echo $job['category'] == 'Education' ? 'selected' : ''; ?>>Education</option>
                                <option value="Engineering" <?php echo $job['category'] == 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                <option value="Marketing" <?php echo $job['category'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Sales" <?php echo $job['category'] == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                <option value="Other" <?php echo $job['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_type">Job Type</label>
                            <select id="job_type" name="job_type" required>
                                <option value="">Select Job Type</option>
                                <option value="Full-time" <?php echo $job['job_type'] == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo $job['job_type'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contract" <?php echo $job['job_type'] == 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="Internship" <?php echo $job['job_type'] == 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                <option value="Remote" <?php echo $job['job_type'] == 'Remote' ? 'selected' : ''; ?>>Remote</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements</label>
                        <textarea id="requirements" name="requirements" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="experience">Experience</label>
                            <select id="experience" name="experience" required>
                                <option value="">Select Experience</option>
                                <option value="Fresh" <?php echo $job['experience'] == 'Fresh' ? 'selected' : ''; ?>>Fresh/Entry Level</option>
                                <option value="1-2 years" <?php echo $job['experience'] == '1-2 years' ? 'selected' : ''; ?>>1-2 years</option>
                                <option value="3-5 years" <?php echo $job['experience'] == '3-5 years' ? 'selected' : ''; ?>>3-5 years</option>
                                <option value="5-10 years" <?php echo $job['experience'] == '5-10 years' ? 'selected' : ''; ?>>5-10 years</option>
                                <option value="10+ years" <?php echo $job['experience'] == '10+ years' ? 'selected' : ''; ?>>10+ years</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="education">Education</label>
                            <select id="education" name="education" required>
                                <option value="">Select Education</option>
                                <option value="High School" <?php echo $job['education'] == 'High School' ? 'selected' : ''; ?>>High School</option>
                                <option value="Diploma" <?php echo $job['education'] == 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                                <option value="Bachelor's" <?php echo $job['education'] == 'Bachelor\'s' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                <option value="Master's" <?php echo $job['education'] == 'Master\'s' ? 'selected' : ''; ?>>Master's Degree</option>
                                <option value="PhD" <?php echo $job['education'] == 'PhD' ? 'selected' : ''; ?>>PhD</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_range">Salary Range (Optional)</label>
                            <input type="text" id="salary_range" name="salary_range" value="<?php echo htmlspecialchars($job['salary_range']); ?>" placeholder="e.g. $50,000 - $70,000">
                        </div>
                        
                        <div class="form-group">
                            <label for="deadline">Application Deadline</label>
                            <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($job['deadline']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Job Status</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo $job['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="closed" <?php echo $job['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="update-job-btn">Update Job</button>
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
