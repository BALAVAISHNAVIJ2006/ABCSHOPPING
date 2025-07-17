<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "candy";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$balance = 0.00;
$message = '';
$error = '';

// Fetch user role (assuming a 'users' table with 'role' column)
$role = 'user'; // Default role
$stmt_role = $conn->prepare("SELECT role FROM users WHERE id = ?");
if ($stmt_role) {
    $stmt_role->bind_param("i", $user_id);
    $stmt_role->execute();
    $result_role = $stmt_role->get_result();
    if ($result_role->num_rows > 0) {
        $row_role = $result_role->fetch_assoc();
        $role = $row_role['role'];
    }
    $stmt_role->close();
}

// --- Fetch Wallet Balance ---
$stmt_fetch = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $balance = $row['balance'];
    } else {
        // Initialize wallet with 0 if not exists
        $stmt_insert = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
        if ($stmt_insert) {
            $stmt_insert->bind_param("i", $user_id);
            $stmt_insert->execute();
            $balance = 0.00;
            $stmt_insert->close();
        } else {
            $error = "Failed to initialize wallet: " . $conn->error;
        }
    }
    $stmt_fetch->close();
} else {
    $error = "Failed to prepare fetch statement: " . $conn->error;
}

// --- Handle Add Funds ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds'])) {
    $amount = floatval(trim($_POST['amount'] ?? 0));
    if ($amount <= 0) {
        $error = "Please enter a valid amount greater than 0.";
    } else {
        $conn->begin_transaction();
        try {
            // Fetch current balance to ensure consistency
            $stmt_fetch = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
            $stmt_fetch->bind_param("i", $user_id);
            $stmt_fetch->execute();
            $result = $stmt_fetch->get_result();
            $current_balance = $result->num_rows > 0 ? $result->fetch_assoc()['balance'] : 0.00;
            $stmt_fetch->close();

            $new_balance = $current_balance + $amount;

            // Update wallet balance
            $stmt_update = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $stmt_update->bind_param("di", $new_balance, $user_id);
            $stmt_update->execute();
            $stmt_update->close();

            $balance = $new_balance;
            $message = "Funds added successfully. New balance: ₹" . number_format($balance, 2);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error processing transaction: " . $e->getMessage();
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
    <title>Wallet - ABC Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            min-height: 100vh;
            padding-top: 80px;
        }

        .profile-header {
            background-color: #6a0dad;
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            max-width: 1550px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .profile-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .wallet-main-card {
            background: linear-gradient(to right, #6a0dad, #8a2be2);
            color: white;
            padding: 30px;
            margin: 30px 50px 0 50px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            width: calc(100% - 100px);
            max-width: 100%;
            box-sizing: border-box;
        }

        .wallet-balance {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .wallet-content-area {
            padding: 0 50px;
            margin-top: 20px;
        }

        .wallet-form-section {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 500px;
            margin: 0 auto;
        }

        .wallet-form-section h3 {
            margin-top: 0;
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .wallet-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="number"] {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            background-color: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input[type="number"]:focus {
            border-color: #8a2be2;
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.2);
            outline: none;
        }

        .form-group button {
            padding: 12px;
            background-color: #6a0dad;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-group button:hover {
            background-color: #8a2be2;
        }

        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .admin-section {
            margin-top: 20px;
            display: <?php echo $role === 'admin' ? 'block' : 'none'; ?>;
        }

        .admin-section h3 {
            color: #1a2a6c;
            margin-bottom: 15px;
        }

        .admin-stats {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .scrollable-wrapper {
            margin-top: 80px;
            overflow-y: auto;
            height: calc(100vh - 80px);
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 10px 20px;
                flex-direction: column;
                align-items: flex-start;
            }
            .profile-header h1 {
                font-size: 24px;
            }
            .wallet-main-card {
                padding: 20px;
                margin: 20px;
                width: calc(100% - 40px);
            }
        }

        @media (max-width: 480px) {
            .wallet-balance {
                font-size: 28px;
            }
            .wallet-form-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-header">
        <h1>Wallet</h1>
    </div>
    <div class="scrollable-wrapper">
        <div class="wallet-main-card">
            <div class="wallet-balance">Balance: ₹<?php echo number_format($balance, 2); ?></div>
        </div>
        <div class="wallet-content-area">
            <div class="wallet-form-section">
                <h3>Add Funds</h3>
                <?php if ($message): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="" class="wallet-form">
                    <div class="form-group">
                        <label for="amount">Amount (₹)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="1" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_funds">Add Funds</button>
                    </div>
                </form>
            </div>

            <!-- Admin Section -->
            <div class="admin-section">
                <h3>Admin Controls</h3>
                <p>View and manage all wallets (admin only).</p>
                <a href="view_all_wallets.php">View All Wallets</a>
                <?php
                if ($role === 'admin') {
                    // Optional: Display total funds received from users (sum of order amounts)
                    $stmt_total = $conn->prepare("SELECT SUM(total_amount) as total_received FROM orders WHERE user_id != ?");
                    if ($stmt_total) {
                        $stmt_total->bind_param("i", $user_id);
                        $stmt_total->execute();
                        $result_total = $stmt_total->get_result();
                        $total_received = $result_total->fetch_assoc()['total_received'] ?? 0.00;
                        $stmt_total->close();
                        echo '<div class="admin-stats">Total Funds Received: ₹' . number_format($total_received, 2) . '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>