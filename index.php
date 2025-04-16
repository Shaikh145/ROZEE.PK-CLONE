<?php
session_start();
include 'db.php';

// Fetch job categories for filter
$category_query = "SELECT DISTINCT category FROM jobs WHERE status = 'active'";
$category_result = mysqli_query($conn, $category_query);

// Fetch job types for filter
$type_query = "SELECT DISTINCT job_type FROM jobs WHERE status = 'active'";
$type_result = mysqli_query($conn, $type_query);

// Fetch locations for filter
$location_query = "SELECT DISTINCT location FROM jobs WHERE status = 'active'";
$location_result = mysqli_query($conn, $location_query);

// Handle search and filters
$where_clause = "WHERE j.status = 'active'";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clause .= " AND (j.title LIKE '%$search%' OR j.description LIKE '%$search%' OR c.company_name LIKE '%$search%')";
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = mysqli_real_escape_string($conn, $_GET['category']);
    $where_clause .= " AND j.category = '$category'";
}

if (isset($_GET['job_type']) && !empty($_GET['job_type'])) {
    $job_type = mysqli_real_escape_string($conn, $_GET['job_type']);
    $where_clause .= " AND j.job_type = '$job_type'";
}

if (isset($_GET['location']) && !empty($_GET['location'])) {
    $location = mysqli_real_escape_string($conn, $_GET['location']);
    $where_clause .= " AND j.location = '$location'";
}

// Fetch jobs with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$jobs_per_page = 10;
$offset = ($page - 1) * $jobs_per_page;

$jobs_query = "SELECT j.*, c.company_name, c.logo FROM jobs j 
               JOIN companies c ON j.company_id = c.company_id 
               $where_clause 
               ORDER BY j.created_at DESC 
               LIMIT $offset, $jobs_per_page";
$jobs_result = mysqli_query($conn, $jobs_query);

// Count total jobs for pagination
$count_query = "SELECT COUNT(*) as total FROM jobs j 
                JOIN companies c ON j.company_id = c.company_id 
                $where_clause";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_jobs = $count_row['total'];
$total_pages = ceil($total_jobs / $jobs_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rozee.pk Clone - Find Your Dream Job</title>
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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
        }
        
        .hero-content {
            width: 100%;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-box {
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            background: white;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .search-box input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            outline: none;
            font-size: 16px;
        }
        
        .search-box button {
            padding: 15px 30px;
            background: #e81c4f;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .search-box button:hover {
            background: #c70039;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .filter-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        
        .filter-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .filter-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .apply-filter {
            background-color: #e81c4f;
            color: white;
            margin-left: 10px;
        }
        
        .apply-filter:hover {
            background-color: #c70039;
        }
        
        .reset-filter {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .reset-filter:hover {
            background-color: #e0e0e0;
        }
        
        /* Jobs Section */
        .jobs-section {
            margin: 30px 0;
        }
        
        .jobs-section h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }
        
        .jobs-count {
            margin-bottom: 15px;
            color: #666;
        }
        
        .job-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .job-logo {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 20px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .job-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .job-details {
            flex: 1;
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
            margin-bottom: 10px;
            color: #666;
        }
        
        .job-info span {
            display: flex;
            align-items: center;
        }
        
        .job-info i {
            margin-right: 5px;
        }
        
        .job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .job-tag {
            background-color: #f0f0f0;
            color: #666;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .job-actions {
            display: flex;
            align-items: center;
        }
        
        .view-job-btn {
            padding: 8px 16px;
            background-color: #e81c4f;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .view-job-btn:hover {
            background-color: #c70039;
        }
        
        .save-job-btn {
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            margin-left: 15px;
            transition: color 0.3s ease;
        }
        
        .save-job-btn:hover {
            color: #e81c4f;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            border-radius: 4px;
            background-color: white;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        
        .pagination .active {
            background-color: #e81c4f;
            color: white;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #ffcc00;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section ul li a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
        }
        
        .footer-bottom p {
            color: #ccc;
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
            
            .hero {
                height: 500px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .search-box {
                flex-direction: column;
                border-radius: 8px;
            }
            
            .search-box input {
                width: 100%;
                border-radius: 8px 8px 0 0;
            }
            
            .search-box button {
                width: 100%;
                border-radius: 0 0 8px 8px;
            }
            
            .job-card {
                flex-direction: column;
                text-align: center;
            }
            
            .job-logo {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .job-info {
                justify-content: center;
            }
            
            .job-actions {
                margin-top: 15px;
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .filters {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-buttons button {
                width: 100%;
                margin-left: 0;
            }
            
            .pagination a, .pagination span {
                padding: 6px 12px;
                margin: 0 2px;
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
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Find Your Dream Job Today</h1>
            <p>Search through thousands of job listings to find the perfect match for your skills and career goals.</p>
            
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Job title, keywords, or company" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">Search Jobs</button>
            </form>
        </div>
    </section>
    
    <!-- Filter Section -->
    <section class="container filter-section">
        <h3>Filter Jobs</h3>
        <form action="index.php" method="GET" id="filter-form">
            <input type="hidden" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            
            <div class="filters">
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php while($category = mysqli_fetch_assoc($category_result)): ?>
                            <option value="<?php echo htmlspecialchars($category['category']); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="job_type">Job Type</label>
                    <select name="job_type" id="job_type">
                        <option value="">All Types</option>
                        <?php while($type = mysqli_fetch_assoc($type_result)): ?>
                            <option value="<?php echo htmlspecialchars($type['job_type']); ?>" <?php echo (isset($_GET['job_type']) && $_GET['job_type'] == $type['job_type']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['job_type']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="location">Location</label>
                    <select name="location" id="location">
                        <option value="">All Locations</option>
                        <?php while($location = mysqli_fetch_assoc($location_result)): ?>
                            <option value="<?php echo htmlspecialchars($location['location']); ?>" <?php echo (isset($_GET['location']) && $_GET['location'] == $location['location']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['location']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-buttons">
                <button type="button" class="reset-filter" id="reset-filter">Reset</button>
                <button type="submit" class="apply-filter">Apply Filters</button>
            </div>
        </form>
    </section>
    
    <!-- Jobs Section -->
    <section class="container jobs-section">
        <h2>Latest Job Opportunities</h2>
        <div class="jobs-count">
            <p>Found <?php echo $total_jobs; ?> jobs matching your criteria</p>
        </div>
        
        <?php if(mysqli_num_rows($jobs_result) > 0): ?>
            <?php while($job = mysqli_fetch_assoc($jobs_result)): ?>
                <div class="job-card">
                    <div class="job-logo">
                        <img src="<?php echo !empty($job['logo']) ? htmlspecialchars($job['logo']) : 'images/default_company.jpg'; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?> logo">
                    </div>
                    
                    <div class="job-details">
                        <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></p>
                        
                        <div class="job-info">
                            <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                            <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                            <span><i class="salary-icon">💰</i> <?php echo !empty($job['salary_range']) ? htmlspecialchars($job['salary_range']) : 'Not disclosed'; ?></span>
                            <span><i class="deadline-icon">⏱️</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                        </div>
                        
                        <div class="job-tags">
                            <span class="job-tag"><?php echo htmlspecialchars($job['category']); ?></span>
                            <span class="job-tag"><?php echo htmlspecialchars($job['experience']); ?> Experience</span>
                        </div>
                    </div>
                    
                    <div class="job-actions">
                        <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="view-job-btn">View Details</a>
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker'): ?>
                            <button class="save-job-btn" onclick="saveJob(<?php echo $job['job_id']; ?>)">
                                <i class="bookmark-icon">🔖</i> Save
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <!-- Pagination -->
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['job_type']) ? '&job_type='.urlencode($_GET['job_type']) : ''; ?><?php echo isset($_GET['location']) ? '&location='.urlencode($_GET['location']) : ''; ?>">Previous</a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['job_type']) ? '&job_type='.urlencode($_GET['job_type']) : ''; ?><?php echo isset($_GET['location']) ? '&location='.urlencode($_GET['location']) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['job_type']) ? '&job_type='.urlencode($_GET['job_type']) : ''; ?><?php echo isset($_GET['location']) ? '&location='.urlencode($_GET['location']) : ''; ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-jobs">
                <p>No jobs found matching your criteria. Try adjusting your filters or search terms.</p>
            </div>
        <?php endif; ?>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <ul>
                        <li><a href="#">About Rozee.pk</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>For Job Seekers</h3>
                    <ul>
                        <li><a href="#">Browse Jobs</a></li>
                        <li><a href="#">Career Advice</a></li>
                        <li><a href="#">Resume Tips</a></li>
                        <li><a href="#">Interview Preparation</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>For Employers</h3>
                    <ul>
                        <li><a href="#">Post a Job</a></li>
                        <li><a href="#">Recruitment Solutions</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Employer Resources</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Connect With Us</h3>
                    <ul>
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">Twitter</a></li>
                        <li><a href="#">LinkedIn</a></li>
                        <li><a href="#">Instagram</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2023 Rozee.pk Clone. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Reset filter form
        document.getElementById('reset-filter').addEventListener('click', function() {
            document.getElementById('category').value = '';
            document.getElementById('job_type').value = '';
            document.getElementById('location').value = '';
            document.getElementById('filter-form').submit();
        });
        
        // Save job function
        function saveJob(jobId) {
            // Check if user is logged in
            <?php if(!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            // AJAX request to save job
            fetch('save-job.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'job_id=' + jobId
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Job saved successfully!');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the job.');
            });
        }
    </script>
</body>
</html>
