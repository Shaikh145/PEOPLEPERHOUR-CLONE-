<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

// Check if project ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsRedirect('dashboard.php');
}

$project_id = sanitize($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get project details
$projectQuery = "SELECT p.*, c.category_name 
                FROM projects p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE p.project_id = $project_id";
$projectResult = $conn->query($projectQuery);

if ($projectResult->num_rows == 0) {
    jsRedirect('dashboard.php');
}

$project = $projectResult->fetch_assoc();

// Check if user is the owner of the project
if ($user_type == 'client' && $project['client_id'] != $user_id) {
    jsRedirect('dashboard.php');
}

// Get proposals for this project
$proposalsQuery = "SELECT pr.*, u.full_name, u.profile_image, u.rating, u.total_reviews 
                  FROM proposals pr 
                  JOIN users u ON pr.freelancer_id = u.user_id 
                  WHERE pr.project_id = $project_id 
                  ORDER BY pr.created_at DESC";
$proposalsResult = $conn->query($proposalsQuery);

// Handle proposal actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_type == 'client' && $project['client_id'] == $user_id) {
    if (isset($_POST['accept_proposal'])) {
        $proposal_id = sanitize($_POST['proposal_id']);
        $freelancer_id = sanitize($_POST['freelancer_id']);
        $bid_amount = sanitize($_POST['bid_amount']);
        $delivery_time = sanitize($_POST['delivery_time']);
        
        // Update proposal status
        $updateProposalQuery = "UPDATE proposals SET status = 'accepted' WHERE proposal_id = $proposal_id";
        $conn->query($updateProposalQuery);
        
        // Create contract
        $delivery_date = date('Y-m-d', strtotime("+$delivery_time days"));
        $createContractQuery = "INSERT INTO contracts (project_id, client_id, freelancer_id, proposal_id, amount, delivery_date) 
                               VALUES ($project_id, $user_id, $freelancer_id, $proposal_id, $bid_amount, '$delivery_date')";
        $conn->query($createContractQuery);
        
        // Update project status
        $updateProjectQuery = "UPDATE projects SET status = 'in_progress' WHERE project_id = $project_id";
        $conn->query($updateProjectQuery);
        
        // Redirect to refresh the page
        jsRedirect("view-project.php?id=$project_id");
    }
    
    if (isset($_POST['reject_proposal'])) {
        $proposal_id = sanitize($_POST['proposal_id']);
        
        // Update proposal status
        $updateProposalQuery = "UPDATE proposals SET status = 'rejected' WHERE proposal_id = $proposal_id";
        $conn->query($updateProposalQuery);
        
        // Redirect to refresh the page
        jsRedirect("view-project.php?id=$project_id");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Project - PeoplePerHour Clone</title>
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
        
        .btn-success {
            background-color: #4caf50;
        }
        
        .btn-success:hover {
            background-color: #388e3c;
        }
        
        .btn-danger {
            background-color: #f44336;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
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
        
        /* Project View Layout */
        .project-view {
            padding: 40px 0;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: #555;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .back-link:hover {
            color: #1e88e5;
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
        
        .project-budget, .project-deadline, .project-status {
            display: flex;
            align-items: center;
        }
        
        .project-budget i, .project-deadline i, .project-status i {
            margin-right: 5px;
        }
        
        .project-deadline i {
            color: #f44336;
        }
        
        .status-open {
            color: #1e88e5;
        }
        
        .status-in-progress {
            color: #ff9800;
        }
        
        .status-completed {
            color: #4caf50;
        }
        
        .status-cancelled {
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
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        /* Proposals Section */
        .proposals-section {
            margin-top: 30px;
        }
        
        .proposal-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .proposal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .proposal-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .freelancer-info {
            display: flex;
            align-items: center;
        }
        
        .freelancer-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .freelancer-details h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
        }
        
        .freelancer-rating {
            color: #ffc107;
        }
        
        .proposal-price {
            text-align: right;
        }
        
        .price-amount {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e88e5;
            margin-bottom: 5px;
        }
        
        .delivery-time {
            font-size: 0.9rem;
            color: #666;
        }
        
        .proposal-body {
            margin-bottom: 20px;
        }
        
        .proposal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .proposal-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status-accepted {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: #c62828;
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
        
        .sidebar-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .project-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .project-stats {
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #666;
        }
        
        .stat-value {
            font-weight: 600;
            color: #333;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #555;
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
            
            .proposal-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .proposal-price {
                text-align: left;
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Project View Section -->
    <section class="project-view">
        <div class="container">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            
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
                            <div class="project-status">
                                <i class="fas fa-info-circle"></i>
                                Status: <span class="status-<?php echo str_replace('_', '-', $project['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $project['status'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="project-description">
                        <h3 class="section-title">Project Description</h3>
                        <p><?php echo nl2br($project['description']); ?></p>
                    </div>
                    
                    <div class="proposals-section">
                        <h3 class="section-title">Proposals (<?php echo $proposalsResult->num_rows; ?>)</h3>
                        
                        <?php if($proposalsResult->num_rows > 0): ?>
                            <?php while($proposal = $proposalsResult->fetch_assoc()): ?>
                                <div class="proposal-card">
                                    <div class="proposal-header">
                                        <div class="freelancer-info">
                                            <img src="<?php echo $proposal['profile_image'] ? $proposal['profile_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $proposal['full_name']; ?>" class="freelancer-img">
                                            <div class="freelancer-details">
                                                <h4><?php echo $proposal['full_name']; ?></h4>
                                                <div class="freelancer-rating">
                                                    <?php 
                                                    $rating = $proposal['rating'];
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
                                                    <span>(<?php echo $proposal['total_reviews']; ?> reviews)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="proposal-price">
                                            <div class="price-amount">$<?php echo $proposal['bid_amount']; ?></div>
                                            <div class="delivery-time">Delivery in <?php echo $proposal['delivery_time']; ?> days</div>
                                        </div>
                                    </div>
                                    
                                    <div class="proposal-body">
                                        <p><?php echo nl2br($proposal['cover_letter']); ?></p>
                                    </div>
                                    
                                    <div class="proposal-actions">
                                        <?php if($proposal['status'] == 'pending' && $user_type == 'client' && $project['status'] == 'open'): ?>
                                            <form action="view-project.php?id=<?php echo $project_id; ?>" method="POST" style="display: inline;">
                                                <input type="hidden" name="proposal_id" value="<?php echo $proposal['proposal_id']; ?>">
                                                <input type="hidden" name="freelancer_id" value="<?php echo $proposal['freelancer_id']; ?>">
                                                <input type="hidden" name="bid_amount" value="<?php echo $proposal['bid_amount']; ?>">
                                                <input type="hidden" name="delivery_time" value="<?php echo $proposal['delivery_time']; ?>">
                                                <button type="submit" name="accept_proposal" class="btn btn-sm btn-success">Accept Proposal</button>
                                            </form>
                                            <form action="view-project.php?id=<?php echo $project_id; ?>" method="POST" style="display: inline;">
                                                <input type="hidden" name="proposal_id" value="<?php echo $proposal['proposal_id']; ?>">
                                                <button type="submit" name="reject_proposal" class="btn btn-sm btn-danger">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="proposal-status status-<?php echo $proposal['status']; ?>"><?php echo ucfirst($proposal['status']); ?></span>
                                        <?php endif; ?>
                                        
                                        <a href="messages.php?user=<?php echo $proposal['freelancer_id']; ?>" class="btn btn-sm btn-outline">Message</a>
                                        <a href="freelancer-profile.php?id=<?php echo $proposal['freelancer_id']; ?>" class="btn btn-sm">View Profile</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-paper-plane"></i>
                                <h3>No proposals yet</h3>
                                <p>When freelancers submit proposals to your project, they will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar">
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Project Actions</h3>
                        <?php if($user_type == 'client' && $project['client_id'] == $user_id): ?>
                            <div class="project-actions">
                                <?php if($project['status'] == 'open'): ?>
                                    <a href="edit-project.php?id=<?php echo $project_id; ?>" class="btn">Edit Project</a>
                                    <a href="close-project.php?id=<?php echo $project_id; ?>" class="btn btn-outline">Close Project</a>
                                <?php elseif($project['status'] == 'in_progress'): ?>
                                    <a href="complete-project.php?id=<?php echo $project_id; ?>" class="btn btn-success">Mark as Completed</a>
                                <?php endif; ?>
                            </div>
                        <?php elseif($user_type == 'freelancer'): ?>
                            <div class="project-actions">
                                <a href="project-details.php?id=<?php echo $project_id; ?>" class="btn">View Public Listing</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Project Stats</h3>
                        <div class="project-stats">
                            <?php
                            // Get project stats
                            $proposalsCountQuery = "SELECT COUNT(*) as count FROM proposals WHERE project_id = $project_id";
                            $proposalsCountResult = $conn->query($proposalsCountQuery);
                            $proposalsCount = $proposalsCountResult->fetch_assoc()['count'];
                            
                            $avgBidQuery = "SELECT AVG(bid_amount) as avg_bid FROM proposals WHERE project_id = $project_id";
                            $avgBidResult = $conn->query($avgBidQuery);
                            $avgBid = $avgBidResult->fetch_assoc()['avg_bid'];
                            
                            $minBidQuery = "SELECT MIN(bid_amount) as min_bid FROM proposals WHERE project_id = $project_id";
                            $minBidResult = $conn->query($minBidQuery);
                            $minBid = $minBidResult->fetch_assoc()['min_bid'];
                            
                            $maxBidQuery = "SELECT MAX(bid_amount) as max_bid FROM proposals WHERE project_id = $project_id";
                            $maxBidResult = $conn->query($maxBidQuery);
                            $maxBid = $maxBidResult->fetch_assoc()['max_bid'];
                            ?>
                            
                            <div class="stat-item">
                                <div class="stat-label">Total Proposals</div>
                                <div class="stat-value"><?php echo $proposalsCount; ?></div>
                            </div>
                            
                            <?php if($proposalsCount > 0): ?>
                                <div class="stat-item">
                                    <div class="stat-label">Average Bid</div>
                                    <div class="stat-value">$<?php echo number_format($avgBid, 2); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Lowest Bid</div>
                                    <div class="stat-value">$<?php echo number_format($minBid, 2); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Highest Bid</div>
                                    <div class="stat-value">$<?php echo number_format($maxBid, 2); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stat-item">
                                <div class="stat-label">Posted On</div>
                                <div class="stat-value"><?php echo date('M d, Y', strtotime($project['created_at'])); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Deadline</div>
                                <div class="stat-value"><?php echo date('M d, Y', strtotime($project['deadline'])); ?></div>
                            </div>
                        </div>
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
