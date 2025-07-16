<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "candy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$message = '';

// Handle order cancellation
if (isset($_GET['cancel']) && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $conn->begin_transaction();
    try {
        // Check if the order belongs to the user and is not already cancelled
        $stmt_check = $conn->prepare("SELECT total_amount, status FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $order_id, $user_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            if ($order['status'] !== 'cancelled') {
                $total_amount = $order['total_amount'];

                // Update order status to cancelled
                $stmt_update_order = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ?");
                $stmt_update_order->bind_param("ii", $order_id, $user_id);
                $stmt_update_order->execute();
                $stmt_update_order->close();

                // Refund amount to user's wallet
                $stmt_wallet = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $stmt_wallet->bind_param("di", $total_amount, $user_id);
                $stmt_wallet->execute();
                $stmt_wallet->close();

                // Deduct from admin wallet (user_id = 2)
                $stmt_admin = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = 2");
                $stmt_admin->bind_param("d", $total_amount);
                $stmt_admin->execute();
                $stmt_admin->close();

                $message = "Order #$order_id cancelled successfully. Amount ₹" . number_format($total_amount, 2) . " refunded to your wallet.";
                $conn->commit();
            } else {
                $message = "Order #$order_id is already cancelled.";
            }
        } else {
            $message = "Invalid order or access denied.";
        }
        $stmt_check->close();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error cancelling order: " . $e->getMessage();
    }
}

// Fetch user's orders
$sql = "SELECT order_id, product_id, size, total_amount, status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($order_id, $product_id, $size, $total_amount, $status, $order_date);
    $orders = [];
    while ($stmt->fetch()) {
        $orders[] = ['order_id' => $order_id, 'product_id' => $product_id, 'size' => $size, 'total_amount' => $total_amount, 'status' => $status, 'order_date' => $order_date];
    }
} else {
    $error = "Failed to prepare statement: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ABC Shopping</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
            color: #333;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .container {
            max-width: 900px;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2.5rem;
            color: #1a2a6c;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1a2a6c;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        .cancel-btn {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border: 2px solid #dc3545;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            display: inline-block;
        }
        .cancel-btn:hover {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
        }
        .back-link {
            color: #b21f1f;
            text-decoration: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border: 2px solid #b21f1f;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            display: inline-block;
        }
        .back-link:hover {
            background-color: #b21f1f;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>My Orders</h1>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif (!empty($orders)): ?>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Product ID</th>
                    <th>Size</th>
                    <th>Total Amount (₹)</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($orders as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?></td>
                        <td><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                        <td>
                            <?php if ($row['status'] === 'confirmed'): ?>
                                <a href="?cancel=1&order_id=<?php echo htmlspecialchars($row['order_id']); ?>" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel order #<?php echo htmlspecialchars($row['order_id']); ?>?');">Cancel</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
        <a href="profile.php" class="back-link">Back to Profile</a>
    </div>
    <?php
    if ($stmt) $stmt->close();
    $conn->close();
    ?>
</body>
</html>