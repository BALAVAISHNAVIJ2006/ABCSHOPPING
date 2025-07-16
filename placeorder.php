<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "candy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$returnPage = 'menswear.php'; // Default return page
if (isset($_POST['category'])) {
    $category = $_POST['category'];
    if (in_array($category, ['menswear', 'womenswear', 'kidswear'])) {
        $returnPage = $category . '.php';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $size = isset($_POST['size']) ? $_POST['size'] : '';
    $total_amount = 0;

    // Fetch the price from the dresses table based on product_id
    $priceStmt = $conn->prepare("SELECT price FROM dresses WHERE id = ?");
    if ($priceStmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $priceStmt->bind_param("i", $productId);
    $priceStmt->execute();
    $priceResult = $priceStmt->get_result();
    if ($priceResult->num_rows > 0) {
        $product = $priceResult->fetch_assoc();
        $total_amount = $product['price'];
    }
    $priceStmt->close();

    // Validate input
    if ($productId <= 0 || !in_array($size, ['S', 'M', 'L', 'XL', '2T', '4T', '6', '8', 'XS'])) {
        $error = "Invalid product ID or size.";
    } else {
        $status = "confirmed";
        $conn->begin_transaction();
        try {
            // Check user wallet balance
            $stmt_check = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
            $stmt_check->bind_param("i", $userId);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $user_balance = $result->num_rows > 0 ? $result->fetch_assoc()['balance'] : 0.00;
            $stmt_check->close();

            if ($user_balance < $total_amount) {
                $conn->rollback();
                header("Location: index.php?error=insufficient_balance");
                exit();
            }

            // Deduct from user wallet
            $new_user_balance = $user_balance - $total_amount;
            $stmt_update_user = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $stmt_update_user->bind_param("di", $new_user_balance, $userId);
            $stmt_update_user->execute();
            $stmt_update_user->close();

            // Add to admin wallet (updated to adminId = 2)
            $adminId = 2; // Updated to use adminId = 2
            $stmt_admin_check = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
            $stmt_admin_check->bind_param("i", $adminId);
            $stmt_admin_check->execute();
            $admin_result = $stmt_admin_check->get_result();
            $admin_balance = $admin_result->num_rows > 0 ? $admin_result->fetch_assoc()['balance'] : 0.00;
            $stmt_admin_check->close();

            $new_admin_balance = $admin_balance + $total_amount;
            $stmt_update_admin = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $stmt_update_admin->bind_param("di", $new_admin_balance, $adminId);
            $stmt_update_admin->execute();
            $stmt_update_admin->close();

            // Insert order with order_date
            $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, size, total_amount, status, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error . " (Check if 'orders' table exists)");
            }
            $stmt->bind_param("iisis", $userId, $productId, $size, $total_amount, $status);
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();

            $conn->commit();
            header("Location: success.php?id=" . urlencode($orderId));
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
} else {
    $error = "Invalid request method.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - ABC Shopping</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
            color: #333;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            max-width: 600px;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 {
            font-size: 2rem;
            color: #1a2a6c;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        a {
            color: #b21f1f;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border: 2px solid #b21f1f;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
        }
        a:hover {
            background-color: #b21f1f;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Error</h1>
        <p><?php echo isset($error) ? htmlspecialchars($error) : 'An unexpected error occurred.'; ?></p>
        <a href="<?php echo htmlspecialchars($returnPage); ?>">Back to <?php echo ucfirst(str_replace('.php', '', $returnPage)); ?></a>
    </div>
</body>
</html>