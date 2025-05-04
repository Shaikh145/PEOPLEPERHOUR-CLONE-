<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

// Check if contract ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsRedirect('dashboard.php');
}

$contract_id = sanitize($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get contract details
$contractQuery = "SELECT c.*, p.title as project_title, p.description as project_description, 
                 p.category_id, pr.cover_letter, pr.bid_amount, pr.delivery_time,
                 u_client.user_id as client_id, u_client.full_name as client_name, u_client.profile_image as client_image,
                 u_freelancer.user_id as freelancer_id, u_freelancer.full_name as freelancer_name, u_freelancer.profile_image as freelancer_image,
                 cat.category_name
                 FROM contracts c 
                 JOIN projects p ON c.project_id = p.project_id
                 JOIN proposals pr ON c.proposal_id = pr.proposal_id
                 JOIN users u_client ON c.client_id = u_client.user_id
                 JOIN users u_freelancer ON c.freelancer_id = u_freelancer.user_id
                 JOIN categories cat ON p.category_id = cat.category_id
                 WHERE c.contract_id = $contract_id";
$contractResult = $conn->query($contractQuery);

if ($contractResult->num_rows == 0) {
    jsRedirect('dashboard.php');
}

$contract = $contractResult->fetch_assoc();

// Check if user is authorized to view this contract
if ($contract['client_id'] != $user_id && $contract['freelancer_id'] != $user_id) {
    jsRedirect('dashboard.php');
}

// Handle contract actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Complete contract (client)
    if (isset($_POST['complete_contract']) && $user_type == 'client' && $contract['client_id'] == $user_id) {
        $updateQuery = "UPDATE contracts SET status = 'completed' WHERE contract_id = $contract_id";
        
        if ($conn->query($updateQuery) === TRUE) {
            // Update project status
            $updateProjectQuery = "UPDATE projects SET status = 'completed' WHERE project_id = {$contract['project_id']}";
            $conn->query($updateProjectQuery);
            
            // Process payment
            $amount = $contract['amount'];
            $freelancer_id = $contract['freelancer_id'];
            
            $paymentQuery = "INSERT INTO payments (contract_id, payer_id, receiver_id, amount, payment_method, status) 
                            VALUES ($contract_id, $user_id, $freelancer_id, $amount, 'online_payment', 'completed')";
            $conn->query($paymentQuery);
            
            // Update freelancer balance
            $updateBalanceQuery = "UPDATE users SET account_balance = account_balance + $amount WHERE user_id = $freelancer_id";
            $conn->query($updateBalanceQuery);
            
            $success = "Contract has been marked as completed and payment has been processed.";
            
            // Refresh the page
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'view-contract.php?id=$contract_id';
                }, 2000);
            </script>";
        } else {
            $error = "Error updating contract: " . $conn->error;
        }
    }
    
    // Cancel contract
    if (isset($_POST['cancel_contract']) && ($contract['status'] == 'active' || $contract['status'] == 'disputed')) {
        $updateQuery = "UPDATE contracts SET status = 'cancelled' WHERE contract_id = $contract_id";
        
        if ($conn->query($updateQuery) === TRUE) {
            // Update project status
            $updateProjectQuery = "UPDATE projects SET status = 'cancelled' WHERE project_id = {$contract['project_id']}";
            $conn->query($updateProjectQuery);
            
            $success = "Contract has been cancelled.";
            
            // Refresh the page
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'view-contract.php?id=$contract_id';
                }, 2000);
            </script>";
        } else {
            $error = "Error cancelling contract: " . $conn->error;
        }
    }
    
    // Dispute contract
    if (isset($_POST['dispute_contract']) && $contract['status'] == 'active') {
        $dispute_reason = sanitize($_POST['dispute_reason']);
        
        if (empty($dispute_reason)) {
            $error = "Please provide a reason for the dispute.";
        } else {
            $updateQuery = "UPDATE contracts SET status = 'disputed' WHERE contract_id = $contract_id";
            
            if ($conn->query($updateQuery) === TRUE) {
                // Create a system message about the dispute
                $system_message = "Contract #$contract_id has been marked as disputed. Reason: $dispute_reason";
                
                // Send message to both parties
                $insertMessageQuery1 = "INSERT INTO messages (sender_id, receiver_id, contract_id, message_text) 
                                      VALUES ($user_id, {$contract['client_id']}, $contract_id, '$system_message')";
                $conn->query($insertMessageQuery1);
                
                $insertMessageQuery2 = "INSERT INTO messages (sender_id, receiver_id, contract_id, message_text) 
                                      VALUES ($user_id, {$contract['freelancer_id']}, $contract_id, '$system_message')";
                $conn->query($insertMessageQuery2);
                
                $success = "Contract has been marked as disputed. Our team will review the case.";
                
                // Refresh the page
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'view-contract.php?id=$contract_id';
                    }, 2000);
                </script>";
            } else {
                $error = "Error disputing contract: " . $conn->error;
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
    <title>Contract #<?php echo $contract_id; ?> - PeoplePerHour Clone</title>
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
        
        .btn-warning {
            background-color: #ff9800;
        }
        
        .btn-warning:hover {
            background-color: #f57c00;
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
        
        /* Contract View Layout */
        .contract-view {
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
        
        .contract-grid {
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
        
        .contract-header {
            margin-bottom: 30px;
        }
        
        .contract-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .contract-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .contract-category {
            background-color: #e3f2fd;
            color: #1e88e5;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .contract-amount, .contract-deadline, .contract-status {
            display: flex;
            align-items: center;
        }
        
        .contract-amount i, .contract-deadline i, .contract-status i {
            margin-right: 5px;
        }
        
        .contract-deadline i {
            color: #f44336;
        }
        
        .status-active {
            color: #1e88e5;
        }
        
        .status-completed {
            color: #4caf50;
        }
        
        .status-cancelled {
            color: #f44336;
        }
        
        .status-disputed {
            color: #ff9800;
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .project-details, .contract-details, .proposal-details {
            margin-bottom: 30px;
        }
        
        .project-description, .proposal-letter {
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .contract-info {
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
        
        .contract-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        /* Dispute Form */
        .dispute-form {
            margin-top: 20px;
            display: none;
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
        
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-height: 100px;
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
        
        .profile-role {
            color: #666;
            margin-bottom: 15px;
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
            .contract-grid {
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
            
            .contract-meta, .contract-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .info-item {
                margin: 5px 0;
            }
            
            .contract-actions {
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

    <!-- Contract View Section -->
    <section class="contract-view">
        <div class="container">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            
            <?php if(!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="contract-grid">
                <!-- Main Content -->
                <div class="main-content">
                    <div class="contract-header">
                        <h1 class="contract-title">Contract #<?php echo $contract_id; ?>: <?php echo $contract['project_title']; ?></h1>
                        <div class="contract-meta">
                            <div class="contract-category"><?php echo $contract['category_name']; ?></div>
                            <div class="contract-amount">
                                <i class="fas fa-dollar-sign"></i>
                                Amount: $<?php echo $contract['amount']; ?>
                            </div>
                            <div class="contract-deadline">
                                <i class="fas fa-clock"></i>
                                Deadline: <?php echo date('M d, Y', strtotime($contract['delivery_date'])); ?>
                            </div>
                            <div class="contract-status">
                                <i class="fas fa-info-circle"></i>
                                Status: <span class="status-<?php echo $contract['status']; ?>"><?php echo ucfirst($contract['status']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="project-details">
                        <h3 class="section-title">Project Details</h3>
                        <div class="project-description">
                            <p><?php echo nl2br($contract['project_description']); ?></p>
                        </div>
                    </div>
                    
                    <div class="contract-details">
                        <h3 class="section-title">Contract Details</h3>
                        <div class="contract-info">
                            <div class="info-item">
                                <div class="info-value">$<?php echo $contract['amount']; ?></div>
                                <div class="info-label">Total Amount</div>
                            </div>
                            <div class="info-item">
                                <div class="info-value"><?php echo date('M d, Y', strtotime($contract['created_at'])); ?></div>
                                <div class="info-label">Start Date</div>
                            </div>
                            <div class="info-item">
                                <div class="info-value"><?php echo date('M d, Y', strtotime($contract['delivery_date'])); ?></div>
                                <div class="info-label">Deadline</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="proposal-details">
                        <h3 class="section-title">Proposal Details</h3>
                        <div class="proposal-letter">
                            <p><?php echo nl2br($contract['cover_letter']); ?></p>
                        </div>
                    </div>
                    
                    <?php if($contract['status'] == 'active'): ?>
                        <div class="contract-actions">
                            <?php if($user_type == 'client' && $contract['client_id'] == $user_id): ?>
                                <form action="view-contract.php?id=<?php echo $contract_id; ?>" method="POST" style="display: inline;">
                                    <button type="submit" name="complete_contract" class="btn btn-success">Mark as Completed</button>
                                </form>
                            <?php endif; ?>
                            
                            <button type="button" id="dispute-btn" class="btn btn-warning">Dispute Contract</button>
                            
                            <form action="view-contract.php?id=<?php echo $contract_id; ?>" method="POST" style="display: inline;">
                                <button type="submit" name="cancel_contract" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this contract?');">Cancel Contract</button>
                            </form>
                            
                            <a href="messages.php?user=<?php echo ($user_id == $contract['client_id']) ? $contract['freelancer_id'] : $contract['client_id']; ?>&contract=<?php echo $contract_id; ?>" class="btn btn-outline">Message</a>
                        </div>
                        
                        <div id="dispute-form" class="dispute-form">
                            <form action="view-contract.php?id=<?php echo $contract_id; ?>" method="POST">
                                <div class="form-group">
                                    <label for="dispute_reason" class="form-label">Reason for Dispute</label>
                                    <textarea id="dispute_reason" name="dispute_reason" class="form-textarea" placeholder="Please explain why you are disputing this contract..." required></textarea>
                                </div>
                                <button type="submit" name="dispute_contract" class="btn btn-warning">Submit Dispute</button>
                            </form>
                        </div>
                    <?php elseif($contract['status'] == 'disputed'): ?>
                        <div class="contract-actions">
                            <a href="messages.php?user=<?php echo ($user_id == $contract['client_id']) ? $contract['freelancer_id'] : $contract['client_id']; ?>&contract=<?php echo $contract_id; ?>" class="btn">Message</a>
                            <form action="view-contract.php?id=<?php echo $contract_id; ?>" method="POST" style="display: inline;">
                                <button type="submit" name="cancel_contract" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this contract?');">Cancel Contract</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="contract-actions">
                            <a href="messages.php?user=<?php echo ($user_id == $contract['client_id']) ? $contract['freelancer_id'] : $contract['client_id']; ?>&contract=<?php echo $contract_id; ?>" class="btn">Message</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar">
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Client</h3>
                        <div class="profile-card">
                            <img src="<?php echo $contract['client_image'] ? $contract['client_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $contract['client_name']; ?>" class="profile-img">
                            <h3 class="profile-name"><?php echo $contract['client_name']; ?></h3>
                            <div class="profile-role">Client</div>
                            <?php if($user_id != $contract['client_id']): ?>
                                <a href="messages.php?user=<?php echo $contract['client_id']; ?>&contract=<?php echo $contract_id; ?>" class="btn btn-outline">Message Client</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Freelancer</h3>
                        <div class="profile-card">
                            <img src="<?php echo $contract['freelancer_image'] ? $contract['freelancer_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $contract['freelancer_name']; ?>" class="profile-img">
                            <h3 class="profile-name"><?php echo $contract['freelancer_name']; ?></h3>
                            <div class="profile-role">Freelancer</div>
                            <?php if($user_id != $contract['freelancer_id']): ?>
                                <a href="messages.php?user=<?php echo $contract['freelancer_id']; ?>&contract=<?php echo $contract_id; ?>" class="btn btn-outline">Message Freelancer</a>
                            <?php endif; ?>
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
    
    <script>
        // Toggle dispute form
        const disputeBtn = document.getElementById('dispute-btn');
        const disputeForm = document.getElementById('dispute-form');
        
        if (disputeBtn && disputeForm) {
            disputeBtn.addEventListener('click', function() {
                if (disputeForm.style.display === 'block') {
                    disputeForm.style.display = 'none';
                } else {
                    disputeForm.style.display = 'block';
                }
            });
        }
    </script>
</body>
</html>
