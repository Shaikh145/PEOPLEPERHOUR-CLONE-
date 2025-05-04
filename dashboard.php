<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$userQuery = "SELECT * FROM users WHERE user_id = $user_id";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

// Get user-specific data
if ($user_type == 'freelancer') {
    // Get freelancer's active services
    $servicesQuery = "SELECT * FROM services WHERE freelancer_id = $user_id AND status = 'active'";
    $servicesResult = $conn->query($servicesQuery);
    
    // Get freelancer's active proposals
    $proposalsQuery = "SELECT p.*, pr.title as project_title, pr.budget_min, pr.budget_max, u.full_name as client_name 
                      FROM proposals p 
                      JOIN projects pr ON p.project_id = pr.project_id 
                      JOIN users u ON pr.client_id = u.user_id 
                      WHERE p.freelancer_id = $user_id 
                      ORDER BY p.created_at DESC";
    $proposalsResult = $conn->query($proposalsQuery);
    
    // Get freelancer's active contracts
    $contractsQuery = "SELECT c.*, p.title as project_title, u.full_name as client_name 
                      FROM contracts c 
                      JOIN projects p ON c.project_id = p.project_id 
                      JOIN users u ON c.client_id = u.user_id 
                      WHERE c.freelancer_id = $user_id 
                      ORDER BY c.created_at DESC";
    $contractsResult = $conn->query($contractsQuery);
} else {
    // Get client's active projects
    $projectsQuery = "SELECT * FROM projects WHERE client_id = $user_id ORDER BY created_at DESC";
    $projectsResult = $conn->query($projectsQuery);
    
    // Get client's active contracts
    $contractsQuery = "SELECT c.*, p.title as project_title, u.full_name as freelancer_name 
                      FROM contracts c 
                      JOIN projects p ON c.project_id = p.project_id 
                      JOIN users u ON c.freelancer_id = u.user_id 
                      WHERE c.client_id = $user_id 
                      ORDER BY c.created_at DESC";
    $contractsResult = $conn->query($contractsQuery);
    
    // Get proposals for client's projects
    $proposalsQuery = "SELECT pr.*, p.title as project_title, u.full_name as freelancer_name 
                      FROM proposals pr 
                      JOIN projects p ON pr.project_id = p.project_id 
                      JOIN users u ON pr.freelancer_id = u.user_id 
                      WHERE p.client_id = $user_id 
                      ORDER BY pr.created_at DESC";
    $proposalsResult = $conn->query($proposalsQuery);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PeoplePerHour Clone</title>
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
        
        /* Dashboard Layout */
        .dashboard {
            padding: 40px 0;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .dashboard-title {
            font-size: 1.8rem;
            color: #333;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        
        /* Sidebar */
        .sidebar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .user-profile {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-type {
            color: #1e88e5;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px;
            border-radius: 4px;
            color: #555;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #e3f2fd;
            color: #1e88e5;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e88e5;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status {
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
        
        .status-active {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
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
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                margin-bottom: 30px;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 20px;
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
                <li><a href="messages.php">Messages</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Dashboard -->
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
                <?php if($user_type == 'client'): ?>
                    <a href="post-project.php" class="btn">Post a Project</a>
                <?php else: ?>
                    <a href="create-service.php" class="btn">Create a Service</a>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-grid">
                <!-- Sidebar -->
                <div class="sidebar">
                    <div class="user-profile">
                        <img src="<?php echo $user['profile_image'] ? $user['profile_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $user['full_name']; ?>" class="profile-img">
                        <h3 class="user-name"><?php echo $user['full_name']; ?></h3>
                        <p class="user-type"><?php echo ucfirst($user['user_type']); ?></p>
                        <a href="edit-profile.php" class="btn btn-outline btn-sm">Edit Profile</a>
                    </div>
                    
                    <ul class="sidebar-menu">
                        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <?php if($user_type == 'freelancer'): ?>
                            <li><a href="my-services.php"><i class="fas fa-briefcase"></i> My Services</a></li>
                            <li><a href="my-proposals.php"><i class="fas fa-paper-plane"></i> My Proposals</a></li>
                            <li><a href="browse-projects.php"><i class="fas fa-search"></i> Find Projects</a></li>
                        <?php else: ?>
                            <li><a href="my-projects.php"><i class="fas fa-project-diagram"></i> My Projects</a></li>
                            <li><a href="browse-freelancers.php"><i class="fas fa-search"></i> Find Freelancers</a></li>
                        <?php endif; ?>
                        <li><a href="my-contracts.php"><i class="fas fa-file-contract"></i> Contracts</a></li>
                        <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                        <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    </ul>
                </div>
                
                <!-- Main Content -->
                <div class="main-content">
                    <!-- Stats -->
                    <div class="stats-grid">
                        <?php if($user_type == 'freelancer'): ?>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $servicesResult->num_rows; ?></div>
                                <div class="stat-label">Active Services</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $proposalsResult->num_rows; ?></div>
                                <div class="stat-label">Proposals Sent</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $contractsResult->num_rows; ?></div>
                                <div class="stat-label">Active Contracts</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">$<?php echo number_format($user['account_balance'], 2); ?></div>
                                <div class="stat-label">Account Balance</div>
                            </div>
                        <?php else: ?>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $projectsResult->num_rows; ?></div>
                                <div class="stat-label">Active Projects</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $proposalsResult->num_rows; ?></div>
                                <div class="stat-label">Proposals Received</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $contractsResult->num_rows; ?></div>
                                <div class="stat-label">Active Contracts</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">$<?php echo number_format($user['account_balance'], 2); ?></div>
                                <div class="stat-label">Account Balance</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($user_type == 'freelancer'): ?>
                        <!-- Recent Proposals -->
                        <h3 class="section-title">Recent Proposals</h3>
                        <div class="table-responsive">
                            <?php if($proposalsResult->num_rows > 0): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Client</th>
                                            <th>Bid Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($proposal = $proposalsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $proposal['project_title']; ?></td>
                                                <td><?php echo $proposal['client_name']; ?></td>
                                                <td>$<?php echo $proposal['bid_amount']; ?></td>
                                                <td>
                                                    <span class="status status-<?php echo strtolower($proposal['status']); ?>">
                                                        <?php echo ucfirst($proposal['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($proposal['created_at'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="view-proposal.php?id=<?php echo $proposal['proposal_id']; ?>" class="btn btn-sm">View</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-paper-plane"></i>
                                    <h3>No proposals yet</h3>
                                    <p>Start bidding on projects to grow your business.</p>
                                    <a href="browse-projects.php" class="btn btn-outline">Find Projects</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Active Contracts -->
                        <h3 class="section-title">Active Contracts</h3>
                        <div class="table-responsive">
                            <?php if($contractsResult->num_rows > 0): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Client</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Deadline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($contract = $contractsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $contract['project_title']; ?></td>
                                                <td><?php echo $contract['client_name']; ?></td>
                                                <td>$<?php echo $contract['amount']; ?></td>
                                                <td>
                                                    <span class="status status-<?php echo strtolower($contract['status']); ?>">
                                                        <?php echo ucfirst($contract['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($contract['delivery_date'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="view-contract.php?id=<?php echo $contract['contract_id']; ?>" class="btn btn-sm">View</a>
                                                        <a href="messages.php?contract=<?php echo $contract['contract_id']; ?>" class="btn btn-sm btn-outline">Message</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-contract"></i>
                                    <h3>No active contracts</h3>
                                    <p>When clients accept your proposals, contracts will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Active Projects -->
                        <h3 class="section-title">My Projects</h3>
                        <div class="table-responsive">
                            <?php if($projectsResult->num_rows > 0): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Budget</th>
                                            <th>Proposals</th>
                                            <th>Status</th>
                                            <th>Posted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($project = $projectsResult->fetch_assoc()): ?>
                                            <?php 
                                            // Count proposals for this project
                                            $proposalCountQuery = "SELECT COUNT(*) as count FROM proposals WHERE project_id = " . $project['project_id'];
                                            $proposalCountResult = $conn->query($proposalCountQuery);
                                            $proposalCount = $proposalCountResult->fetch_assoc()['count'];
                                            ?>
                                            <tr>
                                                <td><?php echo $project['title']; ?></td>
                                                <td>$<?php echo $project['budget_min']; ?> - $<?php echo $project['budget_max']; ?></td>
                                                <td><?php echo $proposalCount; ?></td>
                                                <td>
                                                    <span class="status status-<?php echo strtolower($project['status']); ?>">
                                                        <?php echo ucfirst($project['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="view-project.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm">View</a>
                                                        <a href="edit-project.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-project-diagram"></i>
                                    <h3>No projects yet</h3>
                                    <p>Post your first project to start receiving proposals from freelancers.</p>
                                    <a href="post-project.php" class="btn">Post a Project</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Recent Proposals -->
                        <h3 class="section-title">Recent Proposals</h3>
                        <div class="table-responsive">
                            <?php if($proposalsResult->num_rows > 0): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Freelancer</th>
                                            <th>Bid Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($proposal = $proposalsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $proposal['project_title']; ?></td>
                                                <td><?php echo $proposal['freelancer_name']; ?></td>
                                                <td>$<?php echo $proposal['bid_amount']; ?></td>
                                                <td>
                                                    <span class="status status-<?php echo strtolower($proposal['status']); ?>">
                                                        <?php echo ucfirst($proposal['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($proposal['created_at'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="view-proposal.php?id=<?php echo $proposal['proposal_id']; ?>" class="btn btn-sm">View</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-paper-plane"></i>
                                    <h3>No proposals yet</h3>
                                    <p>When freelancers submit proposals to your projects, they will appear here.</p>
                                </div>
                            <?php endif; ?>
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
