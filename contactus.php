<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$dbname = "candy";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : null;
$email = null;
$message = '';
$error = '';

// Fetch email if user is logged in
if (isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row["email"];
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? '');
    $email_input = trim($_POST["email"] ?? '');
    $subject = trim($_POST["subject"] ?? '');
    $message_text = trim($_POST["message"] ?? '');

    // Basic validation
    if (empty($name) || empty($email_input) || empty($subject) || empty($message_text)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $conn->begin_transaction();
        try {
            // Insert message to database
            $stmt_insert = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt_insert === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt_insert->bind_param("ssss", $name, $email_input, $subject, $message_text);
            if (!$stmt_insert->execute()) {
                throw new Exception("Execute failed: " . $stmt_insert->error);
            }
            $stmt_insert->close();

            // For development/testing - just save to database without sending email
            // Uncomment the email section below when you have proper SMTP configuration
            
            /*
            // Email configuration - UPDATE THESE WITH YOUR ACTUAL EMAIL SETTINGS
            $to = "your-actual-email@domain.com"; // Replace with your actual email
            $headers = "From: noreply@yourdomain.com\r\n"; // Use your domain
            $headers .= "Reply-To: $email_input\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Email body
            $email_body = "New Contact Form Submission:\n\n";
            $email_body .= "Name: $name\n";
            $email_body .= "Email: $email_input\n";
            $email_body .= "Subject: $subject\n\n";
            $email_body .= "Message:\n$message_text\n\n";
            $email_body .= "Submitted: " . date('Y-m-d H:i:s');

            // Try to send email
            if (!mail($to, "Contact Form: " . $subject, $email_body, $headers)) {
                // Don't throw exception for email failure in development
                error_log("Email sending failed for contact form submission");
            }
            */

            $message = "Thank you! Your message has been received and saved successfully.";
            
            // Clear form variables
            $name = $email_input = $subject = $message_text = '';
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to process request: " . $e->getMessage();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ABC Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-top: 80px;
            background: 
                linear-gradient(135deg, rgba(0, 0, 0, 0.7), rgba(75, 0, 130, 0.3)),
                url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') no-repeat center center/cover;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated floating elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            animation: float-random 20s infinite ease-in-out;
        }

        .floating-icon:nth-child(1) {
            top: 20%;
            left: 10%;
            font-size: 2rem;
            animation-delay: 0s;
        }

        .floating-icon:nth-child(2) {
            top: 60%;
            right: 15%;
            font-size: 1.5rem;
            animation-delay: 5s;
        }

        .floating-icon:nth-child(3) {
            bottom: 30%;
            left: 20%;
            font-size: 2.5rem;
            animation-delay: 10s;
        }

        .floating-icon:nth-child(4) {
            top: 40%;
            right: 30%;
            font-size: 1.8rem;
            animation-delay: 15s;
        }

        @keyframes float-random {
            0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
            25% { transform: translateY(-20px) translateX(10px) rotate(5deg); }
            50% { transform: translateY(10px) translateX(-15px) rotate(-3deg); }
            75% { transform: translateY(-15px) translateX(5px) rotate(2deg); }
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 15px 50px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, rgba(106, 13, 173, 0.05), rgba(138, 43, 226, 0.05));
            z-index: -1;
        }

        .navbar .logo-container {
            display: flex;
            align-items: center;
            position: relative;
        }

        .navbar .logo {
            font-size: 26px;
            font-weight: 800;
            background: linear-gradient(135deg, #6a0dad, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-left: 15px;
            letter-spacing: -0.5px;
        }

        .navbar .logo-img {
            width: 45px;
            height: 45px;
            margin-right: 10px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid transparent;
            background: linear-gradient(135deg, #6a0dad, #ff6b6b);
            background-clip: padding-box;
            box-shadow: 0 4px 15px rgba(106, 13, 173, 0.3);
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 25px;
            position: relative;
            overflow: hidden;
        }

        .navbar .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(106, 13, 173, 0.1), transparent);
            transition: left 0.5s;
        }

        .navbar .nav-links a:hover::before {
            left: 100%;
        }

        .navbar .nav-links a:hover {
            color: #6a0dad;
            transform: translateY(-2px);
        }

        .navbar .nav-links a i {
            margin-right: 8px;
            font-size: 16px;
        }

        .user-info {
            padding: 10px 20px;
            background: rgba(106, 13, 173, 0.1);
            border-radius: 25px;
            font-size: 14px;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            max-width: 180px;
            font-weight: 500;
            border: 1px solid rgba(106, 13, 173, 0.2);
        }

        .user-info span {
            margin-bottom: 2px;
            font-weight: 600;
        }

        .user-info small {
            font-size: 12px;
            color: #666;
            opacity: 0.8;
        }

        .navbar .nav-links a[href="logout.php"] {
            background: linear-gradient(135deg, #ff4757, #ff3838);
            color: #fff;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
        }

        .navbar .nav-links a[href="logout.php"]:hover {
            background: linear-gradient(135deg, #ff3838, #ff2f2f);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 71, 87, 0.4);
        }

        .contact-container {
            max-width: 650px;
            margin: 100px auto 50px;
            padding: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 2;
            animation: slideUp 0.8s ease-out;
        }

        .contact-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, rgba(106, 13, 173, 0.3), rgba(255, 107, 107, 0.3));
            border-radius: 32px;
            z-index: -1;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contact-container h1 {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 40px;
            text-align: center;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: -1px;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group label {
            margin-bottom: 12px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            font-size: 16px;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group textarea {
            padding: 18px 24px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            font-family: 'Inter', sans-serif;
            backdrop-filter: blur(10px);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            background: rgba(255, 255, 255, 0.25);
            outline: none;
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 25px rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
            line-height: 1.6;
        }

        .form-group button {
            padding: 18px 30px;
            background: linear-gradient(135deg, #6a0dad, #8a2be2, #ff6b6b);
            color: #fff;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .form-group button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .form-group button:hover::before {
            left: 100%;
        }

        .form-group button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(106, 13, 173, 0.4);
            background: linear-gradient(135deg, #7b1fa2, #9c27b0, #ff5722);
        }

        .message {
            margin-top: 20px;
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
            font-weight: 600;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .success {
            background: rgba(76, 175, 80, 0.2);
            color: #fff;
            border: 2px solid rgba(76, 175, 80, 0.5);
            backdrop-filter: blur(10px);
        }

        .error {
            background: rgba(244, 67, 54, 0.2);
            color: #fff;
            border: 2px solid rgba(244, 67, 54, 0.5);
            backdrop-filter: blur(10px);
        }

        /* Contact info cards */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .info-card i {
            font-size: 24px;
            color: #ff6b6b;
            margin-bottom: 10px;
        }

        .info-card h3 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-card p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .contact-container {
                margin: 80px 20px 30px;
                padding: 30px 25px;
            }

            .contact-container h1 {
                font-size: 32px;
            }

            .form-group input,
            .form-group textarea {
                padding: 15px 20px;
                font-size: 15px;
            }

            .user-info {
                max-width: 150px;
            }
        }

        @media (max-width: 480px) {
            .contact-container {
                padding: 25px 20px;
            }

            .contact-container h1 {
                font-size: 28px;
            }

            .form-group button {
                font-size: 16px;
                padding: 15px 25px;
            }
        }
    </style>
</head>

<body>
    <div class="floating-elements">
        <i class="fas fa-shopping-cart floating-icon"></i>
        <i class="fas fa-shopping-bag floating-icon"></i>
        <i class="fas fa-gift floating-icon"></i>
        <i class="fas fa-tags floating-icon"></i>
    </div>

    <nav class="navbar">
        <div class="logo-container">
            <img src="logo.png" alt="ABC Shopping Logo" class="logo-img">
            <div class="logo">ABC Shopping</div>
        </div>
        <div class="nav-links">
            <div class="user-info">
                <span><?php echo htmlspecialchars($username ?? 'Guest'); ?></span>
                <small><?php echo htmlspecialchars($email ?? 'Not logged in'); ?></small>
            </div>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <div class="contact-container">
        <h1>Get In Touch</h1>
        
        <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="contact-form">
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($username ?: ''); ?>" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?: ''); ?>" placeholder="Enter your email address" required>
            </div>
            <div class="form-group">
                <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                <input type="text" id="subject" name="subject" placeholder="What is this regarding?" required>
            </div>
            <div class="form-group">
                <label for="message"><i class="fas fa-comment-dots"></i> Message</label>
                <textarea id="message" name="message" placeholder="Tell us how we can help you..." required></textarea>
            </div>
            <div class="form-group">
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </div>
        </form>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Enhanced form interactions
        const inputs = document.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.style.transform = 'scale(1)';
            });
        });

        // Add loading state to submit button
        const form = document.querySelector('.contact-form');
        const submitBtn = document.querySelector('button[type="submit"]');
        
        form.addEventListener('submit', function() {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>