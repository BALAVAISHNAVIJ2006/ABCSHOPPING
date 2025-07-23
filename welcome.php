<?php
session_start();

// Redirect to adminhome.php if user_id is 2
if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] == 2) {
    header("Location: adminhome.php");
    exit();
}
// Database connection
$host = "localhost";
$dbname = "candy";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Initialize variables for username and email
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : null;
$email = null;

// If user is logged in, fetch their email
if (isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row["email"];
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ABC Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            color: #333;
            overflow-x: hidden;
            min-height: 100vh; /* Ensure body takes full viewport height */
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 50px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .logo-container {
            display: flex;
            align-items: center;
        }

        .navbar .logo {
            font-size: 24px;
            font-weight: 700;
            color: purple;
            margin-left: 10px;
        }

        .navbar .logo-img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid purple;
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
        }

        .navbar .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .navbar .nav-links a:hover {
            color: #ff4d4d;
        }

        .navbar .nav-links a i {
            margin-right: 5px;
        }

        .user-info {
            margin-left: 20px;
            font-size: 14px;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        .user-info span {
            margin-right: 0;
            margin-bottom: 5px;
        }

        .user-info small {
            font-size: 12px;
            color: #666;
        }

        .navbar .nav-links a[href="logout.php"] {
            background-color: #ff4d4d;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .navbar .nav-links a[href="logout.php"]:hover {
            background-color: #e60000;
        }

        .hero-section {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: url('https://images.unsplash.com/photo-1445205170230-053b83016050?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80') no-repeat center center/cover;
            position: relative;
            margin-top: 70px;
            flex: 1 0 auto; /* Allow hero to grow but not shrink */
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .hero-content {
            position: relative;
            color: white;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-content p {
            font-size: 20px;
            margin-bottom: 30px;
            font-weight: 400;
        }

        .shop-now-btn {
            background: linear-gradient(90deg, #ff4d4d, #ff8787);
            color: white;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 40px;
        }

        .shop-now-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .category-cards {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: row;
            justify-content: center;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .category-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 300px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .category-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .category-title {
            padding: 15px;
            font-size: 20px;
            font-weight: 600;
            color: #333;
            background: #f5f7fa;
        }

        .footer {
            background: linear-gradient(135deg, #333, #4a4a4a);
            color: #ecf0f1;
            padding: 30px 20px;
            text-align: center;
            width: 100%;
            flex-shrink: 0; /* Prevent footer from shrinking */
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer p {
            margin-bottom: 15px;
            font-size: 14px;
        }

        .footer .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer a {
            color: #ff4d4d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #e60000;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
            }

            .navbar .logo-container {
                margin-bottom: 10px;
            }

            .navbar .nav-links {
                margin-top: 10px;
                flex-direction: column;
                align-items: center;
            }

            .navbar .nav-links a {
                margin: 5px 0;
            }

            .user-info {
                margin: 5px 0;
                max-width: 120px;
            }

            .hero-content h1 {
                font-size: 32px;
            }

            .hero-content p {
                font-size: 16px;
            }

            .shop-now-btn {
                padding: 12px 30px;
                font-size: 16px;
            }

            .category-cards {
                flex-direction: column;
                align-items: center;
            }

            .category-card {
                max-width: 250px;
            }

            .footer {
                padding: 20px 15px;
            }

            .footer .footer-links {
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .category-card {
                max-width: 100%;
            }

            .user-info {
                max-width: 100px;
                font-size: 12px;
            }

            .user-info small {
                font-size: 10px;
            }

            .footer {
                padding: 15px 10px;
            }

            .footer .footer-links {
                gap: 10px;
            }

            .footer p {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo-container">
            <img src="logo.png" alt="ABC Shopping Logo" class="logo-img">
            <div class="logo">ABC Shopping</div>
        </div>
        <div class="nav-links">
            <a href="profile.php">
                <i class="fas fa-user-circle"></i>
            </a>
            <div class="user-info">
                <span><?php echo htmlspecialchars($username); ?></span>
                <small><?php echo htmlspecialchars($email); ?></small>
            </div>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    <section class="hero-section">
        <div class="hero-content">
            <h1>Welcome to ABC Shopping</h1>
            <p>Explore the latest trends in fashion for all ages.</p>
            <div class="category-cards">
                <div class="category-card" onclick="window.location.href='menswear.php'">
                    <img src="menswear.png" alt="Men's Wear">
                    <div class="category-title">Men's Wear</div>
                </div>
                <div class="category-card" onclick="window.location.href='womenswear.php'">
                    <img src="womenswear.png" alt="Women's Wear">
                    <div class="category-title">Women's Wear</div>
                </div>
                <div class="category-card" onclick="window.location.href='kidswear.php'">
                    <img src="kidswear.png" alt="Kids Wear">
                    <div class="category-title">Kids Wear</div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>© 2025 ABC Shopping. All rights reserved.</p>
        <div class="footer-links">
            <a href="#privacy">Privacy Policy</a>
            <a href="#terms">Terms of Service</a>
            <a href="contactus.php">Contact Us</a>
        </div>
    </footer>

    <script>
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
    </script>
</body>
</html>
