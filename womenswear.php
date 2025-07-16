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
$isLoggedIn = isset($_SESSION['user_id']);
$userInfo = null;
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $userQuery = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userInfo = $userResult->fetch_assoc();
    $stmt->close();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Handle subcategory filter for womenswear
$subcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';

$result = null;
if (!empty($subcategory)) {
    $sql = "SELECT id, name, category, price, description, image FROM dresses WHERE category = 'Womenswear'";
    
    switch($subcategory) {
        case 'sarees':
            $sql .= " AND (name LIKE '%saree%' OR name LIKE '%sari%')";
            break;
        case 'lehengas':
            $sql .= " AND (name LIKE '%lehenga%' OR name LIKE '%lehanga%')";
            break;
        case 'dresses':
            $sql .= " AND (name LIKE '%dress%' OR name LIKE '%gown%')";
            break;
    }
    $sql .= " ORDER BY name ASC";
    
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Womenswear Collection - ABC Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
            min-height: 100vh;
            color: #333;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1a2a6c;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: #1a2a6c;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #f0f0f0;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .cart-icon {
            font-size: 1.5rem;
            color: #1a2a6c;
            cursor: pointer;
            position: relative;
        }

        .cart-icon .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #b21f1f;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8rem;
        }

        .sign-in-btn {
            background: #1a2a6c;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .sign-in-btn:hover {
            background: #b21f1f;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .category-filter {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.8rem 2rem;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: white;
            transform: translateY(-3px);
        }

        .filter-btn.active {
            background: white;
            color: #1a2a6c;
            border-color: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            width: 100%;
            height: 250px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-placeholder {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ccc;
            cursor: pointer;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-placeholder {
            transform: scale(1.05);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-category {
            font-size: 0.9rem;
            color: #b21f1f;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a2a6c;
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a2a6c;
            margin-bottom: 1rem;
        }

        .no-products, .welcome-message {
            text-align: center;
            color: white;
            padding: 4rem 1rem;
        }

        .no-products-icon, .welcome-message-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
        }

        .modal-header {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f);
            color: white;
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
            display: flex;
            gap: 2rem;
        }

        .modal-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
        }

        .modal-image .placeholder {
            width: 100%;
            height: 300px;
            background: #f0f0f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ccc;
        }

        .modal-details {
            flex: 1;
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a2a6c;
            margin-bottom: 0.5rem;
        }

        .modal-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a2a6c;
            margin-bottom: 1rem;
        }

        .modal-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .modal-order-btn {
            width: 100%;
            padding: 1rem;
            background: #1a2a6c;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-order-btn:hover {
            background: #b21f1f;
            transform: translateY(-2px);
        }

        .auth-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #b21f1f;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 3000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .auth-notification.show {
            transform: translateX(0);
        }

        .auth-notification .close-notification {
            cursor: pointer;
            margin-left: 1rem;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .category-filter {
                gap: 1rem;
            }

            .filter-btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .modal-body {
                flex-direction: column;
                gap: 1.5rem;
            }

            .modal-image img, .modal-image .placeholder {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="logo">ABC SHOPPING</a>
                <?php if ($isLoggedIn && $userInfo): ?>
                    <div class="user-menu">
                        <div class="user-avatar" onclick="toggleProfileModal()" aria-label="User Profile">
                            <?php echo strtoupper(substr($userInfo['name'] ?? $userInfo['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <a href="?logout=true" style="color: #b21f1f;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                <?php else: ?>
                    <a href="signin.php" class="sign-in-btn">Sign In</a>
                <?php endif; ?>
        </div>
    </nav>
    <div class="container">
        <div class="header">
            <h1>Women's Collection</h1>
            <p>Explore our curated selection of elegant womenswear</p>
        </div>
        
        <div class="category-filter">
            <a href="?subcategory=sarees" class="filter-btn <?php echo $subcategory === 'sarees' ? 'active' : ''; ?>">
                <i class="fas fa-sari"></i> Sarees
            </a>
            <a href="?subcategory=lehengas" class="filter-btn <?php echo $subcategory === 'lehengas' ? 'active' : ''; ?>">
                <i class="fas fa-sari"></i> Lehengas
            </a>
            <a href="?subcategory=dresses" class="filter-btn <?php echo $subcategory === 'dresses' ? 'active' : ''; ?>">
                <i class="fas fa-dress"></i> Dresses
            </a>
        </div>
        
        <div class="products-grid">
            <?php
            if (empty($subcategory)) {
                echo '<div class="welcome-message">';
                echo '<div class="welcome-message-icon"><i class="fas fa-sari"></i></div>';
                echo '<h3>Welcome to Our Womenswear Collection</h3>';
                echo '<p>Select a category above to explore our premium products</p>';
                echo '</div>';
            } elseif ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="product-card">';
                    echo '<div class="product-image" onclick="redirectToItems(' . $row['id'] . ')">';
                    if (!empty($row['image'])) {
                        echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                    } else {
                        echo '<div class="product-placeholder"><i class="fas fa-sari"></i></div>';
                    }
                    echo '</div>';
                    echo '<div class="product-info">';
                    echo '<div class="product-category">Womenswear</div>';
                    echo '<h3 class="product-name">' . htmlspecialchars($row['name']) . '</h3>';
                    echo '<p class="product-description">' . htmlspecialchars($row['description']) . '</p>';
                    echo '<div class="product-price">₹' . number_format($row['price'], 2) . '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-products">';
                echo '<div class="no-products-icon"><i class="fas fa-shopping-bag"></i></div>';
                echo '<h3>No products found</h3>';
                echo '<p>Check back soon for new arrivals!</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-btn" onclick="closeProfileModal()" aria-label="Close">×</span>
                <h2>My Profile</h2>
            </div>
            <div class="modal-body">
                <?php if ($isLoggedIn && $userInfo): ?>
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #1a2a6c, #b21f1f); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem;">
                            <?php echo strtoupper(substr($userInfo['name'] ?? $userInfo['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($userInfo['name'] ?? $userInfo['username'] ?? 'User'); ?></h3>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="font-weight: 600; color: #666;">Email:</label>
                            <p><?php echo htmlspecialchars($userInfo['email'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #666;">Phone:</label>
                            <p><?php echo htmlspecialchars($userInfo['phone'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #666;">Member Since:</label>
                            <p><?php echo isset($userInfo['created_at']) ? date('F j, Y', strtotime($userInfo['created_at'])) : 'Unknown'; ?></p>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: #666;">Total Orders:</label>
                            <p>0</p>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <button class="modal-order-btn" onclick="editProfile()">Edit Profile</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function redirectToItems(productId) {
            // Redirect to items page with product ID
            window.location.href = `items.php?id=${productId}`;
        }

        function toggleProfileModal() {
            document.getElementById('profileModal').classList.toggle('show');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('show');
        }

        function editProfile() {
            alert('Edit profile functionality to be implemented');
            // window.location.href = 'edit_profile.php';
        }

        document.getElementById('profileModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('profileModal')) {
                closeProfileModal();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>