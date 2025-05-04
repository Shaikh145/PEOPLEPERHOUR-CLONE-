<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

// Check if proposal ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsRedirect('dashboard.php');
}

$proposal_id = sanitize($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get proposal details
$proposalQuery = "SELECT pr.*, p.title as project_title, p.description as project_description, 
                 p.budget_min, p.budget_max, p.deadline, p.client_id, p.status as project_status,
                 c.category_name, 
                 u_client.full_name as client_name, u_client.profile_image as client_image,
                 u_freelancer.full_name as freelancer_name, u_freelancer.profile_image as freelancer_image,
                 u_freelancer.rating as freelancer_rating, u_freelancer.total_reviews as freelancer_reviews
                 FROM proposals pr 
                 JOIN projects p ON pr.project_id = p.project_id
                 JOIN categories c ON p.category_id = c.category_id
                 JOIN users u_client ON p.client_id = u_client.user_id
                 JOIN users u_freelancer ON pr.freelancer_id = u_freelancer.user_id
                 WHERE pr.proposal_id = $proposal_id";
$proposalResult = $conn->query($proposalQuery);

if ($proposalResult->num_rows == 0) {
    jsRedirect('dashboard.php');
}

$proposal = $proposalResult->fetch_assoc();

// Check if user is authorized to view this proposal
if (($user_type == 'client' && $proposal['client_id'] != $user_id) && 
    ($user_type == 'freelancer' && $proposal['freelancer_id'] != $user_id)) {
    jsRedirect('dashboard.php');
}

// Handle proposal actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_type == 'client' && $proposal['client_id'] == $user_id) {
    if (isset($_POST['accept_proposal'])) {
        $freelancer_id = $proposal['freelancer_id'];
        $project_id = $proposal['project_id'];
        $bid_amount = $proposal['bid_amount'];
        $delivery_time = $proposal['delivery_time'];
        
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
        jsRedirect("view-proposal.php?id=$proposal_id");
    }
    
    if (isset($_POST['reject_proposal'])) {
        // Update proposal status
        $updateProposalQuery = "UPDATE proposals SET status = 'rejected' WHERE proposal_id = $proposal_id";
        $conn->query($updateProposalQuery);
        
        // Redirect to refresh the page
        jsRedirect("view-proposal.php?id=$proposal_id");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposal - PeoplePerHour Clone</title>
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
        
        /* Proposal View Layout */
        .proposal-view {
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
        
        .proposal-grid {
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
        
        .proposal-header {
            margin-bottom: 30px;
        }
        
        .proposal-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .proposal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .proposal-category {
            background-color: #e3f2fd;
            color: #1e88e5;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .proposal-budget, .proposal-deadline, .proposal-status {
            display: flex;
            align-items: center;
        }
        
        .proposal-budget i, .proposal-deadline i, .proposal-status i {
            margin-right: 5px;
        }
        
        .proposal-deadline i {
            color: #f44336;
        }
        
        .status-pending {
            color: #ff9800;
        }
        
        .status-accepted {
            color: #4caf50;
        }
        
        .status-rejected {
            color: #f44336;
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .project-details, .proposal-details {
            margin-bottom: 30px;
        }
        
        .project-description, .proposal-letter {
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .proposal-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .info-item {
            text-align: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            flex: 1;
            margin: 0 10px;
        }
        
        .info-item:first-child {
            margin-left: 0;
        }
        
        .info-item:last-child {
            margin-right: 0;
        }
        
        .info-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e88e5;
            margin-bottom: 5px;
        }
        
        .info-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .proposal-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
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
        
        .profile-card {
            text-align: center;
        }
        
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        
        .profile-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .profile-rating {
            color: #ffc107;
            margin-bottom: 10px;
        }
        
        .profile-stats {
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
            .proposal-grid {
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
            
            .proposal-meta, .proposal-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .info-item {
                margin: 5px 0;
            }
            
            .proposal-actions {
                flex-direction: column;
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

    <!-- Proposal View Section -->
    <section class="proposal-view">
        <div class="container">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            
            <div class="proposal-grid">
                <!-- Main Content -->
                <div class="main-content">
                    <div class="proposal-header">
                        <h1 class="proposal-title">Proposal for: <?php echo $proposal['project_title']; ?></h1>
                        <div class="proposal-meta">
                            <div class="proposal-category"><?php echo $proposal['category_name']; ?></div>
                            <div class="proposal-status">
                                <i class="fas fa-info-circle"></i>
                                Status: <span class="status-<?php echo $proposal['status']; ?>"><?php echo ucfirst($proposal['status']); ?></span>
                            </div>
                            <div class="proposal-date">
                                <i class="fas fa-calendar-alt"></i>
                                Submitted: <?php echo date('M d, Y', strtotime($proposal['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="project-details">
                        <h3 class="section-title">Project Details</h3>
                        <div class="project-description">
                            <p><?php echo nl2br($proposal['project_description']); ?></p>
                        </div>
                        <div class="proposal-meta">
                            <div class="proposal-budget">
                                <i class="fas fa-dollar-sign"></i>
                                Budget: $<?php echo $proposal['budget_min']; ?> - $<?php echo $proposal['budget_max']; ?>
                            </div>
                            <div class="proposal-deadline">
                                <i class="fas fa-clock"></i>
                                Deadline: <?php echo date('M d, Y', strtotime($proposal['deadline'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="proposal-details">
                        <h3 class="section-title">Proposal Details</h3>
                        <div class="proposal-info">
                            <div class="info-item">
                                <div class="info-value">$<?php echo $proposal['bid_amount']; ?></div>
                                <div class="info-label">Bid Amount</div>
                            </div>
                            <div class="info-item">
                                <div class="info-value"><?php echo $proposal['delivery_time']; ?> days</div>
                                <div class="info-label">Delivery Time</div>
                            </div>
                        </div>
                        <div class="proposal-letter">
                            <p><?php echo nl2br($proposal['cover_letter']); ?></p>
                        </div>
                    </div>
                    
                    <?php if($user_type == 'client' && $proposal['client_id'] == $user_id && $proposal['status'] == 'pending' && $proposal['project_status'] == 'open'): ?>
                        <div class="proposal-actions">
                            <form action="view-proposal.php?id=<?php echo $proposal_id; ?>" method="POST" style="display: inline;">
                                <button type="submit" name="accept_proposal" class="btn btn-success">Accept Proposal</button>
                            </form>
                            <form action="view-proposal.php?id=<?php echo $proposal_id; ?>" method="POST" style="display: inline;">
                                <button type="submit" name="reject_proposal" class="btn btn-danger">Reject Proposal</button>
                            </form>
                            <a href="messages.php?user=<?php echo $proposal['freelancer_id']; ?>" class="btn btn-outline">Message Freelancer</a>
                        </div>
                    <?php elseif($user_type == 'freelancer' && $proposal['freelancer_id'] == $user_id): ?>
                        <div class="proposal-actions">
                            <a href="messages.php?user=<?php echo $proposal['client_id']; ?>" class="btn">Message Client</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar">
                    <?php if($user_type == 'client'): ?>
                        <div class="sidebar-card">
                            <h3 class="sidebar-title">Freelancer</h3>
                            <div class="profile-card">
                                <img src="<?php echo $proposal['freelancer_image'] ? $proposal['freelancer_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $proposal['freelancer_name']; ?>" class="profile-img">
                                <h3 class="profile-name"><?php echo $proposal['freelancer_name']; ?></h3>
                                <div class="profile-rating">
                                    <?php 
                                    $rating = $proposal['freelancer_rating'];
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
                                    <span>(<?php echo $proposal['freelancer_reviews']; ?> reviews)</span>
                                </div>
                                <a href="freelancer-profile.php?id=<?php echo $proposal['freelancer_id']; ?>" class="btn btn-outline">View Profile</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="sidebar-card">
                            <h3 class="sidebar-title">Client</h3>
                            <div class="profile-card">
                                <img src="<?php echo $proposal['client_image'] ? $proposal['client_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $proposal['client_name']; ?>" class="profile-img">
                                <h3 class="profile-name"><?php echo $proposal['client_name']; ?></h3>
                                <a href="client-profile.php?id=<?php echo $proposal['client_id']; ?>" class="btn btn-outline">View Profile</a>
                            </div>
                        </div>
                    <?php endif; ?>
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
