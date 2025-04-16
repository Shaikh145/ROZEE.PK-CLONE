<?php
session_start();
include 'db.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    
    // Validate input
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($user_type)) {
        $error = "All fields are required";
    } elseif($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if username or email already exists
        $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO users (username, email, password, full_name, user_type) 
                            VALUES ('$username', '$email', '$hashed_password', '$full_name', '$user_type')";
            
            if(mysqli_query($conn, $insert_query)) {
                $user_id = mysqli_insert_id($conn);
                
                // If user is employer, create company record
                if($user_type == 'employer') {
                    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
                    $industry = mysqli_real_escape_string($conn, $_POST['industry']);
                    
                    $company_query = "INSERT INTO companies (user_id, company_name, industry) 
                                    VALUES ('$user_id', '$company_name', '$industry')";
                    mysqli_query($conn, $company_query);
                }
                
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Rozee.pk Clone</title>
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
        
        /* Registration Form */
        .register-section {
            padding: 50px 0;
        }
        
        .register-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #666;
        }
        
        .user-type-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .user-type-btn {
            padding: 15px 30px;
            margin: 0 10px;
            border: 2px solid #e81c4f;
            border-radius: 8px;
            background: white;
            color: #e81c4f;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-btn.active {
            background: #e81c4f;
            color: white;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #e81c4f;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .employer-fields {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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
        
        .register-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #e81c4f;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 20px;
        }
        
        .register-btn:hover {
            background: #c70039;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #e81c4f;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .user-type-selector {
                flex-direction: column;
                gap: 10px;
            }
            
            .user-type-btn {
                margin: 5px 0;
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
        </div>
    </header>
    
    <!-- Registration Section -->
    <section class="register-section">
        <div class="container">
            <div class="register-container">
                <div class="register-header">
                    <h2>Create an Account</h2>
                    <p>Join Rozee.pk and unlock your career potential</p>
                </div>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="user-type-selector">
                    <button type="button" class="user-type-btn active" data-type="jobseeker">Job Seeker</button>
                    <button type="button" class="user-type-btn" data-type="employer">Employer</button>
                </div>
                
                <form action="register.php" method="POST">
                    <input type="hidden" name="user_type" id="user_type" value="jobseeker">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="employer-fields" id="employer-fields">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="industry">Industry</label>
                            <select id="industry" name="industry">
                                <option value="">Select Industry</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Banking/Financial Services">Banking/Financial Services</option>
                                <option value="Telecommunications">Telecommunications</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Education">Education</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Retail">Retail</option>
                                <option value="Media/Entertainment">Media/Entertainment</option>
                                <option value="Construction">Construction</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="register-btn">Create Account</button>
                    
                    <div class="login-link">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
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
    
    <script>
        // Toggle between job seeker and employer registration
        const userTypeBtns = document.querySelectorAll('.user-type-btn');
        const userTypeInput = document.getElementById('user_type');
        const employerFields = document.getElementById('employer-fields');
        
        userTypeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                userTypeBtns.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Set user type value
                const userType = this.getAttribute('data-type');
                userTypeInput.value = userType;
                
                // Show/hide employer fields
                if(userType === 'employer') {
                    employerFields.style.display = 'block';
                    document.getElementById('company_name').required = true;
                    document.getElementById('industry').required = true;
                } else {
                    employerFields.style.display = 'none';
                    document.getElementById('company_name').required = false;
                    document.getElementById('industry').required = false;
                }
            });
        });
    </script>
</body>
</html>
