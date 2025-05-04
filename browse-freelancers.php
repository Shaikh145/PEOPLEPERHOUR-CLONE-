<?php
require_once 'db.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();

// Get categories for filter
$categoriesQuery = "SELECT * FROM categories ORDER BY category_name";
$categoriesResult = $conn->query($categoriesQuery);

// Build query based on filters
$query = "SELECT u.*, GROUP_CONCAT(DISTINCT s.category_id) as category_ids 
          FROM users u 
          LEFT JOIN services s ON u.user_id = s.freelancer_id 
          WHERE u.user_type = 'freelancer'";

// Apply category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = sanitize($_GET['category']);
    $query .= " AND EXISTS (SELECT 1 FROM services s2 WHERE s2.freelancer_id = u.user_id AND s2.category_id = $category_id)";
}

// Apply rate filter
if (isset($_GET['rate_min']) && !empty($_GET['rate_min'])) {
    $rate_min = sanitize($_GET['rate_min']);
    $query .= " AND u.hourly_rate >= $rate_min";
}

if (isset($_GET['rate_max']) && !empty($_GET['rate_max'])) {
    $rate_max = sanitize($_GET['rate_max']);
    $query .= " AND u.hourly_rate <= $rate_max";
}

// Apply search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $query .= " AND (u.full_name LIKE '%$search%' OR u.skills LIKE '%$search%' OR u.bio LIKE '%$search%')";
}

// Group by user_id to avoid duplicates
$query .= " GROUP BY u.user_id";

// Order by
$query .= " ORDER BY u.rating DESC, u.total_reviews DESC";

// Execute query
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Freelancers - PeoplePerHour Clone</title>
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
        
        /* Browse Freelancers Layout */
        .browse-freelancers {
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
        
        .rate-inputs {
            display: flex;
            gap: 10px;
        }
        
        .rate-inputs .filter-input {
            flex: 1;
        }
        
        .filter-btn {
            width: 100%;
            padding: 10px;
        }
        
        /* Freelancers List */
        .freelancers-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .freelancer-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .freelancer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .freelancer-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .freelancer-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .freelancer-info h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .freelancer-rating {
            color: #ffc107;
            margin-bottom: 5px;
        }
        
        .freelancer-body {
            padding: 20px;
        }
        
        .freelancer-skills {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .skill-tag {
            background-color: #e3f2fd;
            color: #1e88e5;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .freelancer-rate {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .freelancer-bio {
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            grid-column: 1 / -1;
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
            
            .freelancers-list {
                grid-template-columns: 1fr;
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
                <li><a href="browse-freelancers.php" class="active">Find Freelancers</a></li>
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

    <!-- Browse Freelancers Section -->
    <section class="browse-freelancers">
        <div class="container">
            <div class="browse-header">
                <h1 class="browse-title">Browse Freelancers</h1>
                <p class="browse-description">Find talented professionals for your projects. Filter by skills, hourly rate, and more to find the perfect match.</p>
            </div>
            
            <div class="browse-grid">
                <!-- Filter Sidebar -->
                <div class="filter-sidebar">
                    <h3 class="filter-title">Filter Freelancers</h3>
                    <form action="browse-freelancers.php" method="GET">
                        <div class="filter-group">
                            <label for="search" class="filter-label">Search</label>
                            <input type="text" id="search" name="search" class="filter-input" placeholder="Skills or keywords..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
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
                            <label class="filter-label">Hourly Rate</label>
                            <div class="rate-inputs">
                                <input type="number" name="rate_min" class="filter-input" placeholder="Min $" value="<?php echo isset($_GET['rate_min']) ? $_GET['rate_min'] : ''; ?>">
                                <input type="number" name="rate_max" class="filter-input" placeholder="Max $" value="<?php echo isset($_GET['rate_max']) ? $_GET['rate_max'] : ''; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn filter-btn">Apply Filters</button>
                    </form>
                </div>
                
                <!-- Freelancers List -->
                <div class="freelancers-list">
                    <?php if($result->num_rows > 0): ?>
                        <?php while($freelancer = $result->fetch_assoc()): ?>
                            <div class="freelancer-card">
                                <div class="freelancer-header">
                                    <img src="<?php echo $freelancer['profile_image'] ? $freelancer['profile_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $freelancer['full_name']; ?>" class="freelancer-img">
                                    <div class="freelancer-info">
                                        <h3><?php echo $freelancer['full_name']; ?></h3>
                                        <div class="freelancer-rating">
                                            <?php 
                                            $rating = $freelancer['rating'];
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
                                            <span>(<?php echo $freelancer['total_reviews']; ?> reviews)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="freelancer-body">
                                    <?php if(!empty($freelancer['skills'])): ?>
                                        <div class="freelancer-skills">
                                            <?php 
                                            $skills = explode(',', $freelancer['skills']);
                                            foreach($skills as $skill) {
                                                echo '<span class="skill-tag">' . trim($skill) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="freelancer-rate">
                                        $<?php echo $freelancer['hourly_rate']; ?> / hour
                                    </div>
                                    
                                    <?php if(!empty($freelancer['bio'])): ?>
                                        <div class="freelancer-bio">
                                            <?php echo substr($freelancer['bio'], 0, 150) . (strlen($freelancer['bio']) > 150 ? '...' : ''); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="freelancer-profile.php?id=<?php echo $freelancer['user_id']; ?>" class="btn">View Profile</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No freelancers found</h3>
                            <p>Try adjusting your filters or check back later for new freelancers.</p>
                            <a href="browse-freelancers.php" class="btn">Clear Filters</a>
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
