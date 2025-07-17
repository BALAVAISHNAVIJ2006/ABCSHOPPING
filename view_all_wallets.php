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

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
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

if ($role !== 'admin') {
    header("Location: wallet.php");
    exit();
}

// Fetch all wallet balances
$stmt_wallets = $conn->prepare("SELECT user_id, balance FROM wallets");
$stmt_wallets->execute();
$result_wallets = $stmt_wallets->get_result();
$wallets = $result_wallets->fetch_all(MYSQLI_ASSOC);
$stmt_wallets->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Wallets - ABC Shopping</title>
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
        .wallet-content-area {
            padding: 0 50px;
            margin-top: 20px;
        }
        .wallets-table {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #6a0dad;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
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
            .wallets-table {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-header">
        <h1>All Wallets</h1>
    </div>
    <div class="scrollable-wrapper">
        <div class="wallet-content-area">
            <div class="wallets-table">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wallets as $wallet): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($wallet['user_id']); ?></td>
                                <td><?php echo number_format($wallet['balance'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>