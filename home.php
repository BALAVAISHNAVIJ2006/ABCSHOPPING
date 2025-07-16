<?php
session_start();

// Check if user is logged in, if so redirect to welcome.php (from previous sign-in flow)
if (isset($_SESSION["user_id"]) && isset($_SESSION["username"])) {
    header("Location: welcome.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOME PAGE</title>
    <!-- Add Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Add Favicon -->
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
        }

        /* Navigation Bar */
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
            margin-left: 10px; /* Space between logo image and text */
        }

        .navbar .logo-img {
            width: 40px; /* Added width to match height */
    height: 40px;
    margin-right: 10px;
    border-radius: 50%; /* Makes the image circular */
    object-fit: cover; /* Ensures the image fills the circle */
    border: 2px ; /* Optional border for emphasis */
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

        /* Hero Section */
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

        /* Category Cards within Hero Section */
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

        /* Date and Time Section */
        .datetime-section {
            padding: 20px;
            background: #fff;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .datetime-section p {
            font-size: 18px;
            font-weight: 500;
            color: #333;
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .footer a {
            color: #ff4d4d;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
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
            }

            .navbar .nav-links a {
                margin: 0 10px;
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

            .datetime-section p {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .category-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo-container">
            <img src="logo.png" alt="ABC Shopping Logo" class="logo-img">
            <div class="logo">ABC Shopping</div>
        </div>
        <div class="nav-links">
            <a href="signin.php">sigin</a>
            <a href="signup.php">signup</a>
            
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Welcome to ABC Shopping</h1>
            <p>Explore the latest trends in fashion for all ages.</p>
            <!-- Category Cards -->
            <div class="category-cards">
                <!-- Men's Wear (Left) -->
                <div class="category-card" onclick="window.location.href='signup.php'">
                    <img src="menswear.png" alt="Men's Wear">
                    <div class="category-title">Men's Wear</div>
                </div>
                <!-- Women's Wear (Middle) -->
                <div class="category-card" onclick="window.location.href='signup.php'">
                    <img src="womenswear.png" alt="Women's Wear">
                    <div class="category-title">Women's Wear</div>
                </div>
                <!-- Kids Wear (Right) -->
                <div class="category-card" onclick="window.location.href='signup.php'">
                    <img src="kidswear.png" alt="Kids Wear">
                    <div class="category-title">Kids Wear</div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <p>© 2025 ABC Shopping. All rights reserved.</p>
        <p>
            <a href="#privacy">Privacy Policy</a> |
            <a href="#terms">Terms of Service</a> |
            <a href="#contact">Contact Us</a>
        </p>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>