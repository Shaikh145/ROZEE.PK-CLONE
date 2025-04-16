<?php
session_start();
include 'db.php';

// Initialize search parameters
$search_query = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$job_type = isset($_GET['job_type']) ? mysqli_real_escape_string($conn, $_GET['job_type']) : '';
$experience = isset($_GET['experience']) ? mysqli_real_escape_string($conn, $_GET['experience']) : '';

// Build SQL query
$sql = "SELECT j.*, c.company_name, c.logo 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.company_id 
        WHERE j.status = 'active'";

if(!empty($search_query)) {
    $sql .= " AND (j.title LIKE '%$search_query%' OR j.description LIKE '%$search_query%' OR c.company_name LIKE '%$search_query%')";
}

if(!empty($location)) {
    $sql .= " AND j.location LIKE '%$location%'";
}

if(!empty($category)) {
    $sql .= " AND j.category = '$category'";
}

if(!empty($job_type)) {
    $sql .= " AND j.job_type = '$job_type'";
}

if(!empty($experience)) {
    $sql .= " AND j.experience = '$experience'";
}

$sql .= " ORDER BY j.created_at DESC";

// Execute query
$result = mysqli_query($conn, $sql);

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM jobs WHERE status = 'active' ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);

// Get locations for filter
$locations_query = "SELECT DISTINCT location FROM jobs WHERE status = 'active' ORDER BY location";
$locations_result = mysqli_query($conn, $locations_query);

// Get job types for filter
$job_types_query = "SELECT DISTINCT job_type FROM jobs WHERE status = 'active' ORDER BY job_type";
$job_types_result = mysqli_query($conn, $job_types_query);

// Get experience levels for filter
$experience_query = "SELECT DISTINCT experience FROM jobs WHERE status = 'active' ORDER BY experience";
$experience_result = mysqli_query($conn, $experience_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - Rozee.pk Clone</title>
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
        
        /* Search Section */
        .search-section {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-input {
            flex: 2;
            min-width: 200px;
        }
        
        .search-location {
            flex: 1;
            min-width: 150px;
        }
        
        .search-btn {
            flex: 0 0 auto;
        }
        
        .search-input input, .search-location input, .search-btn button {
            width: 100%;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-input input, .search-location input {
            border: 1px solid #ddd;
            transition: border 0.3s ease;
        }
        
        .search-input input:focus, .search-location input:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .search-btn button {
            background: #e81c4f;
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .search-btn button:hover {
            background: #c70039;
        }
        
        /* Jobs Section */
        .jobs-section {
            padding: 40px 0;
        }
        
        .jobs-container {
            display: flex;
            gap: 30px;
        }
        
        .filters-sidebar {
            flex: 1;
            max-width: 300px;
        }
        
        .jobs-main {
            flex: 3;
        }
        
        .filter-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-card h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-card .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-card .filter-group:last-child {
            margin-bottom: 0;
        }
        
        .filter-card label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .filter-card select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-card select:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .filter-btn {
            width: 100%;
            padding: 10px;
            background: #e81c4f;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .filter-btn:hover {
            background: #c70039;
        }
        
        .jobs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .jobs-header h2 {
            font-size: 24px;
            color: #333;
        }
        
        .jobs-count {
            color: #666;
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
        
        .job-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .job-logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
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
        
        .job-title-company h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .job-company {
            color: #e81c4f;
            font-weight: 500;
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
        
        .job-description {
            color: #555;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
        
        .save-btn {
            background: transparent;
            color: #e81c4f;
            border: 1px solid #e81c4f;
        }
        
        .save-btn:hover {
            background: #f9e9ec;
        }
        
        .job-date {
            color: #888;
            font-size: 14px;
        }
        
        .no-jobs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            color: #666;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 4px;
            background: white;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover, .pagination a.active {
            background: #e81c4f;
            color: white;
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
            .jobs-container {
                flex-direction: column;
            }
            
            .filters-sidebar {
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
            
            .search-form {
                flex-direction: column;
                gap: 10px;
            }
            
            .search-input, .search-location {
                min-width: 100%;
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
                    <li><a href="jobs.php" class="active">Jobs</a></li>
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
    
    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <form action="jobs.php" method="GET" class="search-form">
                <div class="search-input">
                    <input type="text" name="q" placeholder="Job title, keywords, or company" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="search-location">
                    <input type="text" name="location" placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                
                <div class="search-btn">
                    <button type="submit">Search Jobs</button>
                </div>
            </form>
        </div>
    </section>
    
    <!-- Jobs Section -->
    <section class="jobs-section">
        <div class="container">
            <div class="jobs-container">
                <!-- Filters Sidebar -->
                <div class="filters-sidebar">
                    <div class="filter-card">
                        <h3>Filter Jobs</h3>
                        
                        <form action="jobs.php" method="GET">
                            <?php if(!empty($search_query)): ?>
                                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                            <?php endif; ?>
                            
                            <?php if(!empty($location)): ?>
                                <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                            <?php endif; ?>
                            
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="job_type">Job Type</label>
                                <select id="job_type" name="job_type">
                                    <option value="">All Types</option>
                                    <?php while($type = mysqli_fetch_assoc($job_types_result)): ?>
                                        <option value="<?php echo htmlspecialchars($type['job_type']); ?>" <?php echo $job_type == $type['job_type'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['job_type']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="experience">Experience</label>
                                <select id="experience" name="experience">
                                    <option value="">All Experience Levels</option>
                                    <?php while($exp = mysqli_fetch_assoc($experience_result)): ?>
                                        <option value="<?php echo htmlspecialchars($exp['experience']); ?>" <?php echo $experience == $exp['experience'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($exp['experience']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="filter-btn">Apply Filters</button>
                        </form>
                    </div>
                </div>
                
                <!-- Jobs Main Content -->
                <div class="jobs-main">
                    <div class="jobs-header">
                        <h2>Available Jobs</h2>
                        <span class="jobs-count"><?php echo mysqli_num_rows($result); ?> jobs found</span>
                    </div>
                    
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($job = mysqli_fetch_assoc($result)): ?>
                            <div class="job-card">
                                <div class="job-card-header">
                                    <div class="job-logo">
                                        <img src="<?php echo !empty($job['logo']) ? htmlspecialchars($job['logo']) : 'images/default_company.jpg'; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?> logo">
                                    </div>
                                    
                                    <div class="job-title-company">
                                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                        <p class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="job-info">
                                    <span><i class="location-icon">📍</i> <?php echo htmlspecialchars($job['location']); ?></span>
                                    <span><i class="job-type-icon">💼</i> <?php echo htmlspecialchars($job['job_type']); ?></span>
                                    <span><i class="experience-icon">⏳</i> <?php echo htmlspecialchars($job['experience']); ?></span>
                                    <span><i class="deadline-icon">⏱️</i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                                </div>
                                
                                <div class="job-description">
                                    <?php echo substr(strip_tags(htmlspecialchars($job['description'])), 0, 200) . '...'; ?>
                                </div>
                                
                                <div class="job-actions">
                                    <div>
                                        <a href="job-details.php?id=<?php echo $job['job_id']; ?>" class="job-btn view-btn">View Details</a>
                                        
                                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'jobseeker'): ?>
                                            <button class="job-btn save-btn" onclick="saveJob(<?php echo $job['job_id']; ?>)">Save Job</button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="job-date">
                                        Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <a href="#" class="active">1</a>
                            <a href="#">2</a>
                            <a href="#">3</a>
                            <a href="#">Next</a>
                        </div>
                    <?php else: ?>
                        <div class="no-jobs">
                            <h3>No jobs found</h3>
                            <p>Try adjusting your search criteria or browse all available jobs.</p>
                        </div>
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
    
    <script>
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
