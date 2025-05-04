<?php
require_once 'db.php';

$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    $full_name = sanitize($_POST['full_name']);
    $user_type = sanitize($_POST['user_type']);
    
    // Validate form data
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name) || empty($user_type)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if username or email already exists
        $checkQuery = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $checkResult = $conn->query($checkQuery);
        
        if ($checkResult->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = hashPassword($password);
            
            // Insert user into database
            $insertQuery = "INSERT INTO users (username, email, password, full_name, user_type) 
                            VALUES ('$username', '$email', '$hashed_password', '$full_name', '$user_type')";
            
            if ($conn->query($insertQuery) === TRUE) {
                $success = "Registration successful! You can now login.";
                
                // Redirect to login page after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Get user type from URL parameter
$default_user_type = isset($_GET['type']) ? $_GET['type'] : 'freelancer';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PeoplePerHour Clone</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        
        a {
            text-decoration: none;
            color: #1e88e5;
        }
        
        /* Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1e88e5;
        }
        
        .logo span {
            color: #333;
        }
        
        /* Registration Form */
        .register-section {
            padding: 60px 0;
        }
        
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            border-color: #1e88e5;
            outline: none;
        }
        
        .user-type-container {
            display: flex;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .user-type-option.active {
            background-color: #1e88e5;
            color: white;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #1e88e5;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #1565c0;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Error and Success Messages */
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #c62828;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #2e7d32;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer-text {
            font-size: 0.9rem;
            color: #bbb;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .register-container {
                padding: 20px;
            }
            
            .header-container {
                flex-direction: column;
                padding: 15px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">People<span>PerHour</span></a>
        </div>
    </header>

    <!-- Registration Section -->
    <section class="register-section">
        <div class="container">
            <div class="register-container">
                <h2 class="section-title">Create an Account</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="user_type" class="form-label">I want to:</label>
                        <div class="user-type-container">
                            <div class="user-type-option <?php echo ($default_user_type == 'freelancer') ? 'active' : ''; ?>" onclick="selectUserType('freelancer')">
                                Work as a Freelancer
                            </div>
                            <div class="user-type-option <?php echo ($default_user_type == 'client') ? 'active' : ''; ?>" onclick="selectUserType('client')">
                                Hire a Freelancer
                            </div>
                        </div>
                        <input type="hidden" name="user_type" id="user_type" value="<?php echo $default_user_type; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                    
                    <button type="submit" class="btn">Create Account</button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="footer-text">&copy; <?php echo date('Y'); ?> PeoplePerHour Clone. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function selectUserType(type) {
            document.getElementById('user_type').value = type;
            
            // Update active class
            const options = document.querySelectorAll('.user-type-option');
            options.forEach(option => {
                option.classList.remove('active');
            });
            
            if (type === 'freelancer') {
                options[0].classList.add('active');
            } else {
                options[1].classList.add('active');
            }
        }
    </script>
</body>
</html>
