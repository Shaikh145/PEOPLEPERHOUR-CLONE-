<?php
require_once 'db.php';

// Fetch featured freelancers
$featuredFreelancersQuery = "SELECT u.user_id, u.full_name, u.profile_image, u.skills, u.hourly_rate, u.rating, u.total_reviews 
                            FROM users u 
                            WHERE u.user_type = 'freelancer' 
                            ORDER BY u.rating DESC, u.total_reviews DESC 
                            LIMIT 8";
$featuredFreelancersResult = $conn->query($featuredFreelancersQuery);

// Fetch categories
$categoriesQuery = "SELECT * FROM categories ORDER BY category_name";
$categoriesResult = $conn->query($categoriesQuery);

// Fetch recent projects
$recentProjectsQuery = "SELECT p.project_id, p.title, p.description, p.budget_min, p.budget_max, p.deadline, 
                        c.category_name, u.full_name as client_name 
                        FROM projects p 
                        JOIN categories c ON p.category_id = c.category_id 
                        JOIN users u ON p.client_id = u.user_id 
                        WHERE p.status = 'open' 
                        ORDER BY p.created_at DESC 
                        LIMIT 6";
$recentProjectsResult = $conn->query($recentProjectsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeoplePerHour Clone - Find Freelancers & Projects</title>
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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1e88e5, #1565c0);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-container {
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .search-input {
            flex: 1;
            padding: 15px;
            border: none;
            font-size: 1rem;
        }
        
        .search-btn {
            padding: 15px 25px;
            background-color: #1e88e5;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .search-btn:hover {
            background-color: #1565c0;
        }
        
        /* Categories Section */
        .categories {
            padding: 60px 0;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2rem;
            color: #333;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .category-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .category-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #1e88e5;
        }
        
        .category-name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Featured Freelancers */
        .featured-freelancers {
            padding: 60px 0;
            background-color: #f9f9f9;
        }
        
        .freelancers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .freelancer-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
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
        
        /* Recent Projects */
        .recent-projects {
            padding: 60px 0;
            background-color: white;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .project-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .project-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .project-category {
            color: #1e88e5;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .project-description {
            margin-bottom: 15px;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .project-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .project-budget, .project-deadline {
            font-size: 0.9rem;
            color: #555;
        }
        
        .project-client {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 15px;
        }
        
        /* How It Works */
        .how-it-works {
            padding: 60px 0;
            background-color: #f9f9f9;
            text-align: center;
        }
        
        .steps-container {
            display: flex;
            justify-content: space-between;
            max-width: 900px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        
        .step {
            flex: 1;
            min-width: 250px;
            padding: 20px;
            margin: 15px;
        }
        
        .step-icon {
            font-size: 50px;
            color: #1e88e5;
            margin-bottom: 20px;
        }
        
        .step-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .step-description {
            color: #666;
        }
        
        /* Testimonials */
        .testimonials {
            padding: 60px 0;
            background-color: white;
        }
        
        .testimonial-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .testimonial {
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .testimonial-text {
            font-style: italic;
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: #555;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: #333;
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 0;
            background: linear-gradient(135deg, #1e88e5, #1565c0);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background-color: white;
            color: #1e88e5;
        }
        
        .btn-white:hover {
            background-color: #f5f5f5;
        }
        
        .btn-transparent {
            background-color: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-transparent:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #fff;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #bbb;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #1e88e5;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: #bbb;
            font-size: 1.2rem;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: #1e88e5;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
            color: #bbb;
            font-size: 0.9rem;
        }
        
        /* Responsive Styles */
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
            
            .hero {
                padding: 60px 0;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .search-btn {
                width: 100%;
            }
            
            .steps-container {
                flex-direction: column;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                margin-bottom: 10px;
            }
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
                <li><a href="how-it-works.php">How It Works</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] == 'client'): ?>
                        <li><a href="post-project.php" class="btn">Post a Project</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="create-service.php" class="btn">Create a Service</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Find the perfect freelance services for your business</h1>
            <p>Connect with talented professionals and get your projects done quickly and efficiently.</p>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search for services or skills...">
                <button class="search-btn">Search</button>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Popular Categories</h2>
            <div class="categories-grid">
                <?php if($categoriesResult->num_rows > 0): ?>
                    <?php while($category = $categoriesResult->fetch_assoc()): ?>
                        <div class="category-card" onclick="window.location.href='browse-freelancers.php?category=<?php echo $category['category_id']; ?>'">
                            <div class="category-icon">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <h3 class="category-name"><?php echo $category['category_name']; ?></h3>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No categories found.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Freelancers -->
    <section class="featured-freelancers">
        <div class="container">
            <h2 class="section-title">Featured Freelancers</h2>
            <div class="freelancers-grid">
                <?php if($featuredFreelancersResult->num_rows > 0): ?>
                    <?php while($freelancer = $featuredFreelancersResult->fetch_assoc()): ?>
                        <div class="freelancer-card">
                            <div class="freelancer-header">
                                <img src="<?php echo $freelancer['profile_image']; ?>" alt="<?php echo $freelancer['full_name']; ?>" class="freelancer-img">
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
                                <div class="freelancer-skills">
                                    <?php 
                                    $skills = explode(',', $freelancer['skills']);
                                    foreach($skills as $skill) {
                                        echo '<span class="skill-tag">' . trim($skill) . '</span>';
                                    }
                                    ?>
                                </div>
                                <div class="freelancer-rate">
                                    $<?php echo $freelancer['hourly_rate']; ?> / hour
                                </div>
                                <a href="freelancer-profile.php?id=<?php echo $freelancer['user_id']; ?>" class="btn btn-outline">View Profile</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No freelancers found.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Recent Projects -->
    <section class="recent-projects">
        <div class="container">
            <h2 class="section-title">Recent Projects</h2>
            <div class="projects-grid">
                <?php if($recentProjectsResult->num_rows > 0): ?>
                    <?php while($project = $recentProjectsResult->fetch_assoc()): ?>
                        <div class="project-card">
                            <h3 class="project-title"><?php echo $project['title']; ?></h3>
                            <div class="project-category"><?php echo $project['category_name']; ?></div>
                            <p class="project-description"><?php echo substr($project['description'], 0, 150) . '...'; ?></p>
                            <div class="project-details">
                                <div class="project-budget">
                                    Budget: $<?php echo $project['budget_min']; ?> - $<?php echo $project['budget_max']; ?>
                                </div>
                                <div class="project-deadline">
                                    Deadline: <?php echo date('M d, Y', strtotime($project['deadline'])); ?>
                                </div>
                            </div>
                            <div class="project-client">
                                Posted by: <?php echo $project['client_name']; ?>
                            </div>
                            <a href="project-details.php?id=<?php echo $project['project_id']; ?>" class="btn">View Project</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No projects found.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="step-title">Post a Project</h3>
                    <p class="step-description">Tell us what you need. Set your budget and timeline.</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="step-title">Get Proposals</h3>
                    <p class="step-description">Receive proposals from talented freelancers.</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="step-title">Choose & Collaborate</h3>
                    <p class="step-description">Select the best freelancer and get your project done.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">What Our Users Say</h2>
            <div class="testimonial-container">
                <div class="testimonial">
                    <p class="testimonial-text">"I found an amazing web developer on this platform who delivered my project on time and exceeded my expectations. Highly recommended!"</p>
                    <p class="testimonial-author">- John Smith, Business Owner</p>
                </div>
                <div class="testimonial">
                    <p class="testimonial-text">"As a freelancer, this platform has helped me connect with clients from around the world and grow my business."</p>
                    <p class="testimonial-author">- Sarah Johnson, Graphic Designer</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to get started?</h2>
            <p>Join thousands of clients and freelancers who are already using our platform to collaborate on amazing projects.</p>
            <div class="cta-buttons">
                <a href="register.php?type=client" class="btn btn-white">Hire a Freelancer</a>
                <a href="register.php?type=freelancer" class="btn btn-transparent">Become a Freelancer</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-section">
                    <h3>PeoplePerHour</h3>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="how-it-works.php">How It Works</a></li>
                        <li><a href="security.php">Security</a></li>
                        <li><a href="investor-relations.php">Investor Relations</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>For Clients</h3>
                    <ul class="footer-links">
                        <li><a href="find-freelancers.php">Find Freelancers</a></li>
                        <li><a href="post-project.php">Post a Project</a></li>
                        <li><a href="client-resources.php">Resources</a></li>
                        <li><a href="client-success-stories.php">Success Stories</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>For Freelancers</h3>
                    <ul class="footer-links">
                        <li><a href="find-projects.php">Find Projects</a></li>
                        <li><a href="create-profile.php">Create Profile</a></li>
                        <li><a href="freelancer-resources.php">Resources</a></li>
                        <li><a href="freelancer-success-stories.php">Success Stories</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Connect With Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                    <ul class="footer-links">
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="help-center.php">Help Center</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> PeoplePerHour Clone. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        // JavaScript for redirection
        function redirectTo(url) {
            window.location.href = url;
        }
    </script>
</body>
</html>
