<?php
session_start();
include 'db.php';

// Fetch all companies
$companies_query = "SELECT c.*, COUNT(j.job_id) as job_count 
                   FROM companies c 
                   LEFT JOIN jobs j ON c.company_id = j.company_id AND j.status = 'active' 
                   GROUP BY c.company_id 
                   ORDER BY c.company_name ASC";
$companies_result = mysqli_query($conn, $companies_query);

// Handle search
$search_query = '';
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = "WHERE c.company_name LIKE '%$search%' OR c.industry LIKE '%$search%'";
    
    $companies_query = "SELECT c.*, COUNT(j.job_id) as job_count 
                       FROM companies c 
                       LEFT JOIN jobs j ON c.company_id = j.company_id AND j.status = 'active' 
                       $search_query 
                       GROUP BY c.company_id 
                       ORDER BY c.company_name ASC";
    $companies_result = mysqli_query($conn, $companies_query);
}

// Handle industry filter
if(isset($_GET['industry']) && !empty($_GET['industry'])) {
    $industry = mysqli_real_escape_string($conn, $_GET['industry']);
    $industry_query = $search_query ? "AND c.industry = '$industry'" : "WHERE c.industry = '$industry'";
    
    $companies_query = "SELECT c.*, COUNT(j.job_id) as job_count 
                       FROM companies c 
                       LEFT JOIN jobs j ON c.company_id = j.company_id AND j.status = 'active' 
                       $search_query $industry_query 
                       GROUP BY c.company_id 
                       ORDER BY c.company_name ASC";
    $companies_result = mysqli_query($conn, $companies_query);
}

// Get all industries for filter
$industries_query = "SELECT DISTINCT industry FROM companies ORDER BY industry ASC";
$industries_result = mysqli_query($conn, $industries_query);
$industries = array();
while($row = mysqli_fetch_assoc($industries_result)) {
    $industries[] = $row['industry'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - Rozee.pk Clone</title>
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
        
        /* Companies Section */
        .companies-section {
            padding: 40px 0;
        }
        
        .companies-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .companies-header h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .companies-header p {
            color: #666;
            font-size: 18px;
        }
        
        .search-filter-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-input:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .search-btn {
            padding: 12px 25px;
            background: #e81c4f;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .search-btn:hover {
            background: #c70039;
        }
        
        .filter-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filter-label {
            font-weight: 500;
            color: #333;
        }
        
        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-width: 200px;
        }
        
        .filter-select:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .reset-filters {
            margin-left: auto;
            color: #e81c4f;
            text-decoration: none;
            font-weight: 500;
        }
        
        .reset-filters:hover {
            text-decoration: underline;
        }
        
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .company-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .company-logo {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .company-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .company-industry {
            color: #e81c4f;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .company-info {
            color: #666;
            margin-bottom: 15px;
        }
        
        .company-jobs {
            font-weight: 500;
            color: #333;
            margin-bottom: 15px;
        }
        
        .view-company-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #e81c4f;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .view-company-btn:hover {
            background: #c70039;
        }
        
        .no-companies {
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
            
            .search-form {
                flex-direction: column;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .reset-filters {
                margin-left: 0;
                margin-top: 10px;
            }
            
            .companies-grid {
                grid-template-columns: 1fr;
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
                    <li><a href="companies.php" class="active">Companies</a></li>
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
    
    <!-- Companies Section -->
    <section class="companies-section">
        <div class="container">
            <div class="companies-header">
                <h2>Browse Companies</h2>
                <p>Discover top companies hiring now</p>
            </div>
            
            <div class="search-filter-container">
                <form action="companies.php" method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search companies by name or industry..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-btn">Search</button>
                </form>
                
                <div class="filter-container">
                    <span class="filter-label">Filter by Industry:</span>
                    <select name="industry" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Industries</option>
                        <?php foreach($industries as $industry): ?>
                            <option value="<?php echo $industry; ?>" <?php echo (isset($_GET['industry']) && $_GET['industry'] == $industry) ? 'selected' : ''; ?>><?php echo $industry; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if(isset($_GET['search']) || isset($_GET['industry'])): ?>
                        <a href="companies.php" class="reset-filters">Reset Filters</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(mysqli_num_rows($companies_result) > 0): ?>
                <div class="companies-grid">
                    <?php while($company = mysqli_fetch_assoc($companies_result)): ?>
                        <div class="company-card">
                            <div class="company-logo">
                                <img src="<?php echo !empty($company['logo']) ? $company['logo'] : 'images/default_company.png'; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>">
                            </div>
                            
                            <h3 class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></h3>
                            <p class="company-industry"><?php echo htmlspecialchars($company['industry']); ?></p>
                            
                            <?php if(!empty($company['company_size'])): ?>
                                <p class="company-info"><?php echo htmlspecialchars($company['company_size']); ?> employees</p>
                            <?php endif; ?>
                            
                            <p class="company-jobs"><?php echo $company['job_count']; ?> active jobs</p>
                            
                            <a href="company-details.php?id=<?php echo $company['company_id']; ?>" class="view-company-btn">View Company</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-companies">
                    <h3>No companies found</h3>
                    <p>No companies match your search criteria. Please try different keywords or filters.</p>
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
