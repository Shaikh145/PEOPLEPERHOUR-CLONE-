<?php
require_once 'db.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$isFreelancer = $isLoggedIn && $_SESSION['user_type'] == 'freelancer';

// Get categories for filter
$categoriesQuery = "SELECT * FROM categories ORDER BY category_name";
$categoriesResult = $conn->query($categoriesQuery);

// Build query based on filters
$query = "SELECT p.*, c.category_name, u.full_name as client_name, u.profile_image as client_image 
          FROM projects p 
          JOIN categories c ON p.category_id = c.category_id 
          JOIN users u ON p.client_id = u.user_id 
          WHERE p.status = 'open'";

// Apply category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = sanitize($_GET['category']);
    $query .= " AND p.category_id = $category_id";
}

// Apply budget filter
if (isset($_GET['budget_min']) && !empty($_GET['budget_min'])) {
    $budget_min = sanitize($_GET['budget_min']);
    $query .= " AND p.budget_max >= $budget_min";
}

if (isset($_GET['budget_max']) && !empty($_GET['budget_max'])) {
    $budget_max = sanitize($_GET['budget_max']);
    $query .= " AND p.budget_min <= $budget_max";
}

// Apply search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $query .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
}

// Order by
$query .= " ORDER BY p.created_at DESC";

// Execute query
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Projects - PeoplePerHour Clone</title>
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
        
        /* Browse Projects Layout */
        .browse-projects {
            padding: 40px 0;
        }
        
        .browse-header {
            margin-bottom: 30px;
        }
        
        .browse-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .browse-description {
            color: #666;
            max-width: 700px;
        }
        
        .browse-grid {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        
        /* Filter Sidebar */
        .filter-sidebar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            height: fit-content;
        }
        
        .filter-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #555;
        }
        
        .filter-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            background-color: white;
            margin-bottom: 10px;
        }
        
        .budget-inputs {
            display: flex;
            gap: 10px;
        }
        
        .budget-inputs .filter-input {
            flex: 1;
        }
        
        .filter-btn {
            width: 100%;
            padding: 10px;
        }
        
        /* Projects List */
        .projects-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .project-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .project-title {
            font-size: 1.3rem;
            color: #333;
        }
        
        .project-budget {
            font-weight: 600;
            color: #1e88e5;
        }
        
        .project-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
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
        
        .project-deadline {
            display: flex;
            align-items: center;
        }
        
        .project-deadline i {
            margin-right: 5px;
            color: #f44336;
        }
        
        .project-description {
            margin-bottom: 20px;
            color: #555;
        }
        
        .project-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .project-client {
            display: flex;
            align-items: center;
        }
        
        .client-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .client-info {
            font-size: 0.9rem;
        }
        
        .client-name {
            font-weight: 600;
            color: #333;
        }
        
        .project-date {
            color: #888;
            font-size: 0.8rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
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
        
        .empty-state p {
            color: #777;
            margin-bottom: 20px;
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
            .browse-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-sidebar {
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
            
            .project-header, .project-meta, .project-footer {
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
                <li><a href="browse-projects.php" class="active">Find Projects</a></li>
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

    <!-- Browse Projects Section -->
    <section class="browse-projects">
        <div class="container">
            <div class="browse-header">
                <h1 class="browse-title">Browse Projects</h1>
                <p class="browse-description">Find projects that match your skills and start earning. Apply with a strong proposal to increase your chances of getting hired.</p>
            </div>
            
            <div class="browse-grid">
                <!-- Filter Sidebar -->
                <div class="filter-sidebar">
                    <h3 class="filter-title">Filter Projects</h3>
                    <form action="browse-projects.php" method="GET">
                        <div class="filter-group">
                            <label for="search" class="filter-label">Search</label>
                            <input type="text" id="search" name="search" class="filter-input" placeholder="Keywords..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="category" class="filter-label">Category</label>
                            <select id="category" name="category" class="filter-select">
                                <option value="">All Categories</option>
                                <?php 
                                // Reset the pointer to the beginning
                                $categoriesResult->data_seek(0);
                                while($category = $categoriesResult->fetch_assoc()): 
                                    $selected = (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $selected; ?>><?php echo $category['category_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Budget Range</label>
                            <div class="budget-inputs">
                                <input type="number" name="budget_min" class="filter-input" placeholder="Min $" value="<?php echo isset($_GET['budget_min']) ? $_GET['budget_min'] : ''; ?>">
                                <input type="number" name="budget_max" class="filter-input" placeholder="Max $" value="<?php echo isset($_GET['budget_max']) ? $_GET['budget_max'] : ''; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn filter-btn">Apply Filters</button>
                    </form>
                </div>
                
                <!-- Projects List -->
                <div class="projects-list">
                    <?php if($result->num_rows > 0): ?>
                        <?php while($project = $result->fetch_assoc()): ?>
                            <div class="project-card">
                                <div class="project-header">
                                    <h3 class="project-title"><?php echo $project['title']; ?></h3>
                                    <div class="project-budget">$<?php echo $project['budget_min']; ?> - $<?php echo $project['budget_max']; ?></div>
                                </div>
                                
                                <div class="project-meta">
                                    <div class="project-category"><?php echo $project['category_name']; ?></div>
                                    <div class="project-deadline">
                                        <i class="fas fa-clock"></i>
                                        Deadline: <?php echo date('M d, Y', strtotime($project['deadline'])); ?>
                                    </div>
                                </div>
                                
                                <div class="project-description">
                                    <?php echo substr($project['description'], 0, 200) . (strlen($project['description']) > 200 ? '...' : ''); ?>
                                </div>
                                
                                <div class="project-footer">
                                    <div class="project-client">
                                        <img src="<?php echo $project['client_image'] ? $project['client_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $project['client_name']; ?>" class="client-img">
                                        <div class="client-info">
                                            <div class="client-name"><?php echo $project['client_name']; ?></div>
                                            <div class="project-date">Posted <?php echo date('M d, Y', strtotime($project['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <a href="project-details.php?id=<?php echo $project['project_id']; ?>" class="btn">View Project</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No projects found</h3>
                            <p>Try adjusting your filters or check back later for new projects.</p>
                            <a href="browse-projects.php" class="btn">Clear Filters</a>
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
