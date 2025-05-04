<?php
require_once 'db.php';

// Check if user is logged in and is a client
if (!isLoggedIn() || $_SESSION['user_type'] != 'client') {
    jsRedirect('login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];

// Fetch categories
$categoriesQuery = "SELECT * FROM categories ORDER BY category_name";
$categoriesResult = $conn->query($categoriesQuery);

$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category_id = sanitize($_POST['category_id']);
    $budget_min = sanitize($_POST['budget_min']);
    $budget_max = sanitize($_POST['budget_max']);
    $deadline = sanitize($_POST['deadline']);
    
    // Validate form data
    if (empty($title) || empty($description) || empty($category_id) || empty($budget_min) || empty($budget_max) || empty($deadline)) {
        $error = "All fields are required";
    } elseif ($budget_min >= $budget_max) {
        $error = "Minimum budget must be less than maximum budget";
    } elseif (strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $error = "Deadline must be in the future";
    } else {
        // Insert project into database
        $insertQuery = "INSERT INTO projects (client_id, title, description, category_id, budget_min, budget_max, deadline) 
                        VALUES ($user_id, '$title', '$description', $category_id, $budget_min, $budget_max, '$deadline')";
        
        if ($conn->query($insertQuery) === TRUE) {
            $project_id = $conn->insert_id;
            $success = "Project posted successfully!";
            
            // Redirect to project page after 2 seconds
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'view-project.php?id=$project_id';
                }, 2000);
            </script>";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Project - PeoplePerHour Clone</title>
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
        
        .nav-menu {
            display: flex;
            align-items: center;
        }
        
        .nav-menu li {
            margin-left: 25px;
            list-style: none;
        }
        
        .nav-menu a {
            color: #555;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-menu a:hover {
            color: #1e88e5;
        }
        
        /* Post Project Form */
        .post-project-section {
            padding: 60px 0;
        }
        
        .post-project-container {
            max-width: 800px;
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
        
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-height: 150px;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        .form-textarea:focus {
            border-color: #1e88e5;
            outline: none;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            background-color: white;
            transition: border-color 0.3s;
        }
        
        .form-select:focus {
            border-color: #1e88e5;
            outline: none;
        }
        
        .budget-inputs {
            display: flex;
            gap: 15px;
        }
        
        .budget-inputs .form-group {
            flex: 1;
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
            .post-project-container {
                padding: 20px;
            }
            
            .header-container {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .nav-menu {
                margin-top: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-menu li {
                margin: 5px 10px;
            }
            
            .budget-inputs {
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
            <a href="index.php" class="logo">People<span>PerHour</span></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="browse-freelancers.php">Find Freelancers</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Post Project Section -->
    <section class="post-project-section">
        <div class="container">
            <div class="post-project-container">
                <h2 class="section-title">Post a New Project</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="post-project.php" method="POST">
                    <div class="form-group">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" id="title" name="title" class="form-input" placeholder="e.g. 'Website Design for E-commerce Store'" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php while($category = $categoriesResult->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Project Description</label>
                        <textarea id="description" name="description" class="form-textarea" placeholder="Describe your project in detail. Include requirements, expectations, and any specific skills needed." required></textarea>
                    </div>
                    
                    <div class="budget-inputs">
                        <div class="form-group">
                            <label for="budget_min" class="form-label">Minimum Budget ($)</label>
                            <input type="number" id="budget_min" name="budget_min" class="form-input" min="5" step="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="budget_max" class="form-label">Maximum Budget ($)</label>
                            <input type="number" id="budget_max" name="budget_max" class="form-input" min="5" step="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-input" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn">Post Project</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="footer-text">&copy; <?php echo date('Y'); ?> PeoplePerHour Clone. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
