<?php
ob_start(); // Start output buffering to prevent header issues
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "localhost";
$dbname = "candy";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Fetch user_type to determine where to redirect
    $stmt = $conn->prepare("SELECT usertype FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($usertype);
    $stmt->fetch();
    $stmt->close();
    if ($usertype == 1) {
        header("Location: welcome.php");
    } elseif ($usertype == 2) {
        header("Location: adminhome.php");
    }
    exit;
}

$message = "";
$messageClass = "error";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
        $messageClass = "error";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Server-side validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "A valid email is required.";
            $messageClass = "error";
        } elseif (empty($password)) {
            $message = "Password cannot be empty.";
            $messageClass = "error";
        } else {
            // Check user credentials
            $stmt = $conn->prepare("SELECT id, username, password, usertype FROM users WHERE email = ?");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $username, $stored_password, $usertype);
                $stmt->fetch();

                // Verify password (stored as plain text in the database)
                if ($password === $stored_password) {
                    session_regenerate_id(true); // Prevent session fixation
                    $_SESSION["user_id"] = $id;
                    $_SESSION["username"] = $username;
                    $_SESSION["usertype"] = $usertype; // Store user_type in session for future use

                    // Redirect based on user_type
                    if ($usertype == 1) {
                        header("Location: welcome.php");
                    } elseif ($usertype == 2) {
                        header("Location: adminhome.php");
                    }
                    exit;
                } else {
                    $message = "Invalid email or password.";
                    $messageClass = "error";
                }
            } else {
                $message = "Invalid email or password.";
                $messageClass = "error";
            }
            $stmt->close();
        }
    }
}

$conn->close();
ob_end_flush(); // End output buffering
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - Dress Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #6e48aa, #9d50bb);
            overflow: hidden;
            position: relative;
        }

        /* Animated background particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particles span {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) scale(0); }
            100% { transform: translateY(-10vh) scale(1); }
        }

        .signin-container {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .signin-container h2 {
            color: #fff;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-weight: 400;
            text-align: left;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 12px 12px 40px; /* Adjusted padding to accommodate the icon */
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 16px;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            background: rgba(255, 255, 255, 0.3);
            outline: none;
            transform: scale(1.02);
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #e55d87, #5fc3e4);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.3s ease;
        }

        input[type="submit"]:hover {
            background: linear-gradient(90deg, #d53f6b, #4ab0d1);
            transform: scale(1.05);
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .message.error {
            background: rgba(255, 0, 0, 0.2);
            color: #ff6666;
        }

        .signup-link {
            margin-top: 20px;
            font-size: 14px;
            color: #fff;
        }

        .signup-link a {
            color: #5fc3e4;
            text-decoration: none;
            font-weight: 700;
        }

        .signup-link a:hover {
            color: #e55d87;
        }

        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 16px;
            color: #fff;
        }

        @media (max-width: 480px) {
            .signin-container {
                padding: 20px;
                width: 95%;
            }

            .signin-container h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated particles in the background -->
    <div class="particles">
        <script>
            // Generate random particles
            for (let i = 0; i < 50; i++) {
                let particle = document.createElement("span");
                particle.style.width = Math.random() * 10 + "px";
                particle.style.height = particle.style.width;
                particle.style.left = Math.random() * 100 + "vw";
                particle.style.animationDuration = Math.random() * 10 + 5 + "s";
                particle.style.animationDelay = Math.random() * 5 + "s";
                document.querySelector(".particles").appendChild(particle);
            }
        </script>
    </div>

    <div class="signin-container">
        <h2>Sign In</h2>
        <form id="signinForm" method="POST" action="" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <!-- Added hidden fields and readonly to trick autofill -->
                    <input type="email" name="email_fake" style="display:none;" readonly>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required maxlength="100" aria-describedby="emailHelp" autocomplete="new-email">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <!-- Added hidden fields and readonly to trick autofill -->
                    <input type="password" name="password_fake" style="display:none;" readonly>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="6" aria-describedby="passwordHelp" autocomplete="new-password">
                </div>
            </div>
            <?php if ($message): ?>
                <div class="message <?php echo $messageClass; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <input type="submit" value="Sign In" id="submitBtn">
            <div class="loading" id="loading"></div>
        </form>
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>

    <script>
        // JavaScript to clear input fields on page load to prevent autofill
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            // Clear the fields on load
            emailInput.value = '';
            passwordInput.value = '';

            // Clear the fields on focus
            emailInput.addEventListener('focus', function() {
                emailInput.value = '';
            });

            passwordInput.addEventListener('focus', function() {
                passwordInput.value = '';
            });
        });
    </script>
</body>
</html>