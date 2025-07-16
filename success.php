<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - ABC Shopping</title>
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
        .success-icon {
            font-size: 4rem;
            color: #1a2a6c;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 2.5rem;
            color: #1a2a6c;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .order-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .order-details p {
            margin: 0.5rem 0;
        }
        a {
            color: #b21f1f;
            text-decoration: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
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
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your purchase. Your order has been confirmed.</p>
        <?php if (isset($_GET['id'])): ?>
            <div class="order-details">
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($_GET['id']); ?></p>
                <p>Please keep this ID for your records.</p>
            </div>
        <?php endif; ?>
        <a href="welcome.php">Continue Shopping</a>
    </div>
</body>
</html>