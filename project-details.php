<?php
require_once 'db.php';

// Check if project ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsRedirect('browse-projects.php');
}

$project_id = sanitize($_GET['id']);

// Get project details
$projectQuery = "SELECT p.*, c.category_name, u.user_id as client_id, u.full_name as client_name, 
                u.profile_image as client_image, u.location as client_location, u.rating as client_rating 
                FROM projects p 
                JOIN categories c ON p.category_id = c.category_id 
                JOIN users u ON p.client_id = u.user_id 
                WHERE p.project_id = $project_id";
$projectResult = $conn->query($projectQuery);

if ($projectResult->num_rows == 0) {
    jsRedirect('browse-projects.php');
}

$project = $projectResult->fetch_assoc();

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$isFreelancer = $isLoggedIn && $_SESSION['user_type'] == 'freelancer';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// Check if freelancer has already submitted a proposal
$hasProposal = false;
if ($isFreelancer) {
    $checkProposalQuery = "SELECT * FROM proposals WHERE project_id = $project_id AND freelancer_id = $user_id";
    $checkProposalResult = $conn->query($checkProposalQuery);
    $hasProposal = $checkProposalResult->num_rows > 0;
}

// Handle proposal submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isFreelancer && !$hasProposal) {
    $cover_letter = sanitize($_POST['cover_letter']);
    $bid_amount = sanitize($_POST['bid_amount']);
    $delivery_time = sanitize($_POST['delivery_time']);
    
    // Validate form data
    if (empty($cover_letter) || empty($bid_amount) || empty($delivery_time)) {
        $error = "All fields are required";
    } elseif ($bid_amount < 5) {
        $error = "Bid amount must be at least $5";
    } elseif ($delivery_time < 1) {
        $error = "Delivery time must be at least 1 day";
    } else {
        // Insert proposal into database
        $insertQuery = "INSERT INTO proposals (project_id, freelancer_id, cover_letter, bid_amount, delivery_time) 
                        VALUES ($project_id, $user_id, '$cover_letter', $bid_amount, $delivery_time)";
        
        if ($conn->query($insertQuery) === TRUE) {
            $success = "Your proposal has been submitted successfully!";
            $hasProposal = true;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Get similar projects
$similarProjectsQuery = "SELECT p.project_id, p.title, p.budget_min, p.budget_max, p.created_at 
                        FROM projects p 
                        WHERE p.category_id = {$project['category_id']} 
                        AND p.project_id != $project_id 
                        AND p.status = 'open' 
                        ORDER BY p.created_at DESC 
                        LIMIT 3";
$similarProjectsResult = $conn->query($similarProjectsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project['title']; ?> - PeoplePerHour Clone</title>
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
              sans-serif;
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
        
        ul {
            list-style: none;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #1e88e5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #1565c0;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid #1e88e5;
            color: #1e88e5;
        }
        
        .btn-outline:hover {
            background-color: #1e88e5;
            color: white;
        }
        
        /* Header Styles */
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
        }
        
        .nav-menu a {
            color: #555;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-menu a:hover {
            color: #1e88e5;
        }
        
        /* Project Details Layout */
        .project-details {
            padding: 40px 0;
        }
        
        .project-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        /* Main Content */
        .main-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .project-header {
            margin-bottom: 20px;
        }
        
        .project-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .project-category {
            background-color: #e3f2fd;
            color: #1e88e5;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .project-budget, .project-deadline {
            display: flex;
            align-items: center;
        }
        
        .project-budget i, .project-deadline i {
            margin-right: 5px;
        }
        
        .project-deadline i {
            color: #f44336;
        }
        
        .project-description {
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        /* Proposal Form */
        .proposal-form {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
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
        
        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .sidebar-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .client-profile {
            text-align: center;
        }
        
        .client-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        
        .client-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .client-location {
            color: #666;
            margin-bottom: 10px;
        }
        
        .client-rating {
            color: #ffc107;
            margin-bottom: 15px;
        }
        
        .client-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-weight: 600;
            color: #333;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Similar Projects */
        .similar-projects-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .similar-project {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .similar-project:last-child {
            border-bottom: none;
        }
        
        .similar-project-title {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .similar-project-budget {
            font-size: 0.9rem;
            color: #1e88e5;
            margin-bottom: 5px;
        }
        
        .similar-project-date {
            font-size: 0.8rem;
            color: #888;
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
        @media (max-width: 992px) {
            .project-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: -1;
            }
        }
        
        @media (max-width: 768px) {
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
            
            .project-meta {
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
            <a href="index.php" class="logo">People<span>PerHour</span></a>
            <ul class="nav-menu">
                <li><a href="browse-freelancers.php">Find Freelancers</a></li>
                <li><a href="browse-projects.php">Find Projects</a></li>
                <?php if($isLoggedIn): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="messages.php">Messages</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <!-- Project Details Section -->
    <section class="project-details">
        <div class="container">
            <div class="project-grid">
                <!-- Main Content -->
                <div class="main-content">
                    <div class="project-header">
                        <h1 class="project-title"><?php echo $project['title']; ?></h1>
                        <div class="project-meta">
                            <div class="project-category"><?php echo $project['category_name']; ?></div>
                            <div class="project-budget">
                                <i class="fas fa-dollar-sign"></i>
                                Budget: $<?php echo $project['budget_min']; ?> - $<?php echo $project['budget_max']; ?>
                            </div>
                            <div class="project-deadline">
                                <i class="fas fa-clock"></i>
                                Deadline: <?php echo date('M d, Y', strtotime($project['deadline'])); ?>
                            </div>
                            <div class="project-posted">
                                <i class="fas fa-calendar-alt"></i>
                                Posted: <?php echo date('M d, Y', strtotime($project['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="project-description">
                        <h3 class="section-title">Project Description</h3>
                        <p><?php echo nl2br($project['description']); ?></p>
                    </div>
                    
                    <?php if($isFreelancer && $project['status'] == 'open'): ?>
                        <?php if($hasProposal): ?>
                            <div class="success-message">You have already submitted a proposal for this project.</div>
                        <?php else: ?>
                            <div class="proposal-form">
                                <h3 class="section-title">Submit a Proposal</h3>
                                
                                <?php if(!empty($error)): ?>
                                    <div class="error-message"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($success)): ?>
                                    <div class="success-message"><?php echo $success; ?></div>
                                <?php endif; ?>
                                
                                <form action="project-details.php?id=<?php echo $project_id; ?>" method="POST">
                                    <div class="form-group">
                                        <label for="bid_amount" class="form-label">Your Bid Amount ($)</label>
                                        <input type="number" id="bid_amount" name="bid_amount" class="form-input" min="5" step="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="delivery_time" class="form-label">Delivery Time (days)</label>
                                        <input type="number" id="delivery_time" name="delivery_time" class="form-input" min="1" step="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="cover_letter" class="form-label">Cover Letter</label>
                                        <textarea id="cover_letter" name="cover_letter" class="form-textarea" placeholder="Explain why you're the best fit for this project..." required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn">Submit Proposal</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php elseif(!$isLoggedIn): ?>
                        <div class="proposal-form">
                            <h3 class="section-title">Want to work on this project?</h3>
                            <p>You need to sign up as a freelancer to submit a proposal.</p>
                            <a href="register.php?type=freelancer" class="btn" style="margin-top: 15px;">Sign Up as a Freelancer</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar">
                    <div class="sidebar-card">
                        <div class="client-profile">
                            <img src="<?php echo $project['client_image'] ? $project['client_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $project['client_name']; ?>" class="client-img">
                            <h3 class="client-name"><?php echo $project['client_name']; ?></h3>
                            <?php if(!empty($project['client_location'])): ?>
                                <div class="client-location"><?php echo $project['client_location']; ?></div>
                            <?php endif; ?>
                            <div class="client-rating">
                                <?php 
                                $rating = $project['client_rating'];
                                for($i = 1; $i <= 5; $i++) {
                                    if($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif($i - 0.5 <= $rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <?php
                            // Get client stats
                            $projectsCountQuery = "SELECT COUNT(*) as count FROM projects WHERE client_id = {$project['client_id']}";
                            $projectsCountResult = $conn->query($projectsCountQuery);
                            $projectsCount = $projectsCountResult->fetch_assoc()['count'];
                            
                            $contractsCountQuery = "SELECT COUNT(*) as count FROM contracts WHERE client_id = {$project['client_id']}";
                            $contractsCountResult = $conn->query($contractsCountQuery);
                            $contractsCount = $contractsCountResult->fetch_assoc()['count'];
                            ?>
                            
                            <div class="client-stats">
                                <div class="stat">
                                    <div class="stat-value"><?php echo $projectsCount; ?></div>
                                    <div class="stat-label">Projects</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo $contractsCount; ?></div>
                                    <div class="stat-label">Hires</div>
                                </div>
                            </div>
                            
                            <?php if($isLoggedIn && $isFreelancer): ?>
                                <a href="messages.php?user=<?php echo $project['client_id']; ?>" class="btn btn-outline">Contact Client</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3 class="similar-projects-title">Similar Projects</h3>
                        <?php if($similarProjectsResult->num_rows > 0): ?>
                            <?php while($similarProject = $similarProjectsResult->fetch_assoc()): ?>
                                <div class="similar-project">
                                    <h4 class="similar-project-title">
                                        <a href="project-details.php?id=<?php echo $similarProject['project_id']; ?>"><?php echo $similarProject['title']; ?></a>
                                    </h4>
                                    <div class="similar-project-budget">$<?php echo $similarProject['budget_min']; ?> - $<?php echo $similarProject['budget_max']; ?></div>
                                    <div class="similar-project-date">Posted <?php echo date('M d, Y', strtotime($similarProject['created_at'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No similar projects found.</p>
                        <?php endif; ?>
                    </div>
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

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
