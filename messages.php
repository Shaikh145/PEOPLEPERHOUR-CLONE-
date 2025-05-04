<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get all conversations
$conversationsQuery = "SELECT 
                        CASE 
                            WHEN m.sender_id = $user_id THEN m.receiver_id
                            ELSE m.sender_id
                        END as contact_id,
                        u.full_name as contact_name,
                        u.profile_image as contact_image,
                        MAX(m.created_at) as last_message_time,
                        (SELECT message_text FROM messages WHERE 
                            ((sender_id = $user_id AND receiver_id = contact_id) OR 
                            (sender_id = contact_id AND receiver_id = $user_id))
                            ORDER BY created_at DESC LIMIT 1) as last_message,
                        COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = $user_id THEN 1 END) as unread_count
                    FROM messages m
                    JOIN users u ON (
                        CASE 
                            WHEN m.sender_id = $user_id THEN m.receiver_id
                            ELSE m.sender_id
                        END = u.user_id
                    )
                    WHERE m.sender_id = $user_id OR m.receiver_id = $user_id
                    GROUP BY contact_id
                    ORDER BY last_message_time DESC";
$conversationsResult = $conn->query($conversationsQuery);

// Get active conversation
$active_contact_id = 0;
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $active_contact_id = sanitize($_GET['user']);
    
    // Get contact details
    $contactQuery = "SELECT * FROM users WHERE user_id = $active_contact_id";
    $contactResult = $conn->query($contactQuery);
    $contact = $contactResult->fetch_assoc();
    
    // Get messages for this conversation
    $messagesQuery = "SELECT m.*, 
                        CASE WHEN m.sender_id = $user_id THEN 1 ELSE 0 END as is_sender
                    FROM messages m
                    WHERE (m.sender_id = $user_id AND m.receiver_id = $active_contact_id)
                    OR (m.sender_id = $active_contact_id AND m.receiver_id = $user_id)
                    ORDER BY m.created_at ASC";
    $messagesResult = $conn->query($messagesQuery);
    
    // Mark messages as read
    $updateQuery = "UPDATE messages SET is_read = 1 
                    WHERE sender_id = $active_contact_id AND receiver_id = $user_id AND is_read = 0";
    $conn->query($updateQuery);
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message']) && $active_contact_id > 0) {
    $message_text = sanitize($_POST['message_text']);
    $contract_id = isset($_POST['contract_id']) ? sanitize($_POST['contract_id']) : 'NULL';
    
    if (!empty($message_text)) {
        $insertQuery = "INSERT INTO messages (sender_id, receiver_id, contract_id, message_text) 
                        VALUES ($user_id, $active_contact_id, $contract_id, '$message_text')";
        
        if ($conn->query($insertQuery) === TRUE) {
            // Redirect to refresh the page and avoid form resubmission
            jsRedirect("messages.php?user=$active_contact_id");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PeoplePerHour Clone</title>
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
        
        /* Messages Layout */
        .messages-section {
            padding: 40px 0;
        }
        
        .messages-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: grid;
            grid-template-columns: 300px 1fr;
            height: calc(100vh - 200px);
            min-height: 500px;
        }
        
        /* Conversations List */
        .conversations-list {
            border-right: 1px solid #eee;
            overflow-y: auto;
        }
        
        .conversations-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: #333;
        }
        
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        
        .conversation-item:hover, .conversation-item.active {
            background-color: #f5f5f5;
        }
        
        .contact-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .contact-info {
            flex: 1;
            min-width: 0;
        }
        
        .contact-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .last-message {
            font-size: 0.9rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-meta {
            text-align: right;
            min-width: 50px;
        }
        
        .message-time {
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 5px;
        }
        
        .unread-badge {
            display: inline-block;
            background-color: #1e88e5;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            font-size: 0.8rem;
        }
        
        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .chat-contact-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .chat-contact-name {
            font-weight: 600;
            color: #333;
        }
        
        .messages-container-inner {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .message-sender {
            background-color: #e3f2fd;
            color: #333;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        
        .message-receiver {
            background-color: #f5f5f5;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        
        .message-time-small {
            font-size: 0.7rem;
            color: #888;
            margin-top: 5px;
            text-align: right;
        }
        
        .message-form {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .message-input:focus {
            border-color: #1e88e5;
            outline: none;
        }
        
        .send-btn {
            padding: 12px 20px;
            background-color: #1e88e5;
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .send-btn:hover {
            background-color: #1565c0;
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: #666;
            padding: 20px;
            text-align: center;
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
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .conversations-list {
                display: none;
            }
            
            .conversations-list.active {
                display: block;
            }
            
            .chat-area {
                display: none;
            }
            
            .chat-area.active {
                display: flex;
            }
            
            .back-to-conversations {
                display: block;
                margin-right: 10px;
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
                <li><a href="messages.php" class="active">Messages</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Messages Section -->
    <section class="messages-section">
        <div class="container">
            <div class="messages-container">
                <!-- Conversations List -->
                <div class="conversations-list <?php echo $active_contact_id ? '' : 'active'; ?>">
                    <div class="conversations-header">
                        Conversations
                    </div>
                    
                    <?php if($conversationsResult->num_rows > 0): ?>
                        <?php while($conversation = $conversationsResult->fetch_assoc()): ?>
                            <div class="conversation-item <?php echo ($active_contact_id == $conversation['contact_id']) ? 'active' : ''; ?>" onclick="window.location.href='messages.php?user=<?php echo $conversation['contact_id']; ?>'">
                                <img src="<?php echo $conversation['contact_image'] ? $conversation['contact_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $conversation['contact_name']; ?>" class="contact-img">
                                <div class="contact-info">
                                    <div class="contact-name"><?php echo $conversation['contact_name']; ?></div>
                                    <div class="last-message"><?php echo $conversation['last_message']; ?></div>
                                </div>
                                <div class="conversation-meta">
                                    <div class="message-time"><?php echo date('H:i', strtotime($conversation['last_message_time'])); ?></div>
                                    <?php if($conversation['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?php echo $conversation['unread_count']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>No conversations yet</h3>
                            <p>Start a conversation by messaging a freelancer or client.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area <?php echo $active_contact_id ? 'active' : ''; ?>">
                    <?php if($active_contact_id): ?>
                        <div class="chat-header">
                            <a href="messages.php" class="back-to-conversations" style="display: none;"><i class="fas fa-arrow-left"></i></a>
                            <img src="<?php echo $contact['profile_image'] ? $contact['profile_image'] : 'uploads/default_profile.jpg'; ?>" alt="<?php echo $contact['full_name']; ?>" class="chat-contact-img">
                            <div class="chat-contact-name"><?php echo $contact['full_name']; ?></div>
                        </div>
                        
                        <div class="messages-container-inner">
                            <?php if($messagesResult->num_rows > 0): ?>
                                <?php while($message = $messagesResult->fetch_assoc()): ?>
                                    <div class="message <?php echo $message['is_sender'] ? 'message-sender' : 'message-receiver'; ?>">
                                        <?php echo nl2br($message['message_text']); ?>
                                        <div class="message-time-small"><?php echo date('H:i', strtotime($message['created_at'])); ?></div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment"></i>
                                    <h3>No messages yet</h3>
                                    <p>Send a message to start the conversation.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form class="message-form" action="messages.php?user=<?php echo $active_contact_id; ?>" method="POST">
                            <input type="text" name="message_text" class="message-input" placeholder="Type your message..." required>
                            <?php if(isset($_GET['contract']) && !empty($_GET['contract'])): ?>
                                <input type="hidden" name="contract_id" value="<?php echo sanitize($_GET['contract']); ?>">
                            <?php endif; ?>
                            <button type="submit" name="send_message" class="send-btn">Send</button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the list or start a new one.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        // Auto-scroll to bottom of messages
        const messagesContainer = document.querySelector('.messages-container-inner');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Mobile view toggle
        const backButton = document.querySelector('.back-to-conversations');
        const conversationsList = document.querySelector('.conversations-list');
        const chatArea = document.querySelector('.chat-area');
        
        function handleResize() {
            if (window.innerWidth <= 768) {
                if (backButton) backButton.style.display = 'block';
                
                if (chatArea.classList.contains('active')) {
                    conversationsList.classList.remove('active');
                }
            } else {
                if (backButton) backButton.style.display = 'none';
                conversationsList.classList.add('active');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        if (backButton) {
            backButton.addEventListener('click', function(e) {
                e.preventDefault();
                conversationsList.classList.add('active');
                chatArea.classList.remove('active');
            });
        }
    </script>
</body>
</html>
