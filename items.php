<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "candy";

$conn = new mysqli($servername, $username, $password, $dbname);
$product = null;
$errorMessage = "";

try {
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get product ID from URL
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($productId > 0) {
        $stmt = $conn->prepare("SELECT id, name, category, price, description, image FROM dresses WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $productId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
    }
    
} catch (Exception $e) {
    $errorMessage = "Error: " . $e->getMessage();
    error_log("Database error in item.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Determine back link and category title
$backLink = 'index.php'; // Default fallback
$categoryTitle = 'Product';
$placeholderIcon = 'shirt';

if ($product && isset($product['category'])) {
    switch (strtolower($product['category'])) {
        case 'menswear':
            $backLink = 'menswear.php';
            $categoryTitle = 'Menswear';
            $placeholderIcon = 'shirt';
            break;
        case 'kidswear':
            $backLink = 'kidswear.php';
            $categoryTitle = 'Kidswear';
            $placeholderIcon = 'child';
            break;
        case 'womenswear':
            $backLink = 'womenswear.php';
            $categoryTitle = 'Womenswear';
            $placeholderIcon = 'female';
            break;
        default:
            $backLink = 'index.php';
            $categoryTitle = 'Products';
            $placeholderIcon = 'shirt';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Product'; ?> - <?php echo $categoryTitle; ?> - ABC Shopping</title>
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
            color: #333;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .product-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a2a6c;
            margin: 0;
        }

        .category-badge {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .product-image-container {
            position: relative;
        }

        .product-image {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-image:hover img {
            transform: scale(1.05);
        }

        .product-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #aaa;
            border-radius: 12px;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .product-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a2a6c;
            margin: 0;
        }

        .product-description {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #1a2a6c;
        }

        .size-selection {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #e9ecef;
        }

        .size-selection label {
            font-weight: 700;
            color: #1a2a6c;
            display: block;
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
        }

        .size-selection select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #1a2a6c;
            border-radius: 8px;
            font-size: 1rem;
            color: #333;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .size-selection select:focus {
            outline: none;
            border-color: #b21f1f;
            box-shadow: 0 0 0 3px rgba(178, 31, 31, 0.1);
        }

        .size-chart-link {
            color: #1a2a6c;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .size-chart-link:hover {
            color: #b21f1f;
            text-decoration: underline;
        }

        .order-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #e9ecef;
        }

        .order-btn {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 42, 108, 0.3);
        }

        .order-btn:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #b21f1f;
            text-decoration: none;
            font-weight: 600;
            margin-top: 2rem;
            padding: 0.8rem 1.5rem;
            border: 2px solid #b21f1f;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #b21f1f;
            color: white;
            transform: translateY(-2px);
        }

        .error-container {
            text-align: center;
            padding: 3rem;
        }

        .error-icon {
            font-size: 4rem;
            color: #b21f1f;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a2a6c;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* Modal Styles */
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
            max-width: 700px;
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
            width: 35px;
            height: 35px;
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
        }

        .size-chart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .size-chart-table th,
        .size-chart-table td {
            border: 1px solid #ddd;
            padding: 1rem;
            text-align: center;
        }

        .size-chart-table th {
            background: #1a2a6c;
            color: white;
            font-weight: 700;
        }

        .size-chart-table td {
            background: #f9f9f9;
        }

        .size-chart-table tr:nth-child(even) td {
            background: #f1f3f4;
        }

        .size-note {
            color: #666;
            font-size: 0.9rem;
            font-style: italic;
            text-align: center;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 1.5rem;
            }
            
            .product-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .product-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .product-image {
                height: 300px;
            }
            
            .product-title {
                font-size: 2rem;
            }
            
            .product-price {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($product): ?>
            <div class="product-header">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="category-badge"><?php echo htmlspecialchars($product['category']); ?></div>
            </div>
            
            <div class="product-content">
                <div class="product-image-container">
                    <div class="product-image">
                        <?php
                        $imagePath = !empty($product['image']) ? htmlspecialchars($product['image']) : '';
                        if ($imagePath) {
                            echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($product['name']) . '">';
                        } else {
                            echo '<div class="product-placeholder"><i class="fas fa-' . $placeholderIcon . '"></i></div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="product-info">
                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="product-description">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </div>
                    
                    <div class="size-selection">
                        <label for="size">Select Size:</label>
                        <select id="size" name="size">
                            <?php if (strtolower($product['category']) === 'kidswear'): ?>
                                <option value="2T">2T (2-3 Years)</option>
                                <option value="4T" selected>4T (4-5 Years)</option>
                                <option value="6">6 (6-7 Years)</option>
                                <option value="8">8 (8-9 Years)</option>
                            <?php elseif (strtolower($product['category']) === 'womenswear'): ?>
                                <option value="XS">Extra Small (XS)</option>
                                <option value="S" selected>Small (S)</option>
                                <option value="M">Medium (M)</option>
                                <option value="L">Large (L)</option>
                            <?php else: ?>
                                <option value="S">Small (S)</option>
                                <option value="M" selected>Medium (M)</option>
                                <option value="L">Large (L)</option>
                                <option value="XL">Extra Large (XL)</option>
                            <?php endif; ?>
                        </select>
                        <a href="#" class="size-chart-link" onclick="toggleSizeChartModal()">
                            <i class="fas fa-ruler"></i> View Size Chart
                        </a>
                    </div>
                    
                    <div class="order-form">
                        <form action="placeorder.php" method="POST" onsubmit="return validateOrder();">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <input type="hidden" name="size" id="hidden_size">
                            <button type="submit" class="order-btn">
                                <i class="fas fa-shopping-cart"></i> Order Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>  
            <a href="<?php echo $backLink; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to <?php echo $categoryTitle; ?>
            </a>
        <?php elseif ($errorMessage): ?>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="error-title">Error</h1>
                <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
                <a href="<?php echo $backLink; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to <?php echo $categoryTitle; ?>
                </a>
            </div>
        <?php else: ?>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="error-title">Product Not Found</h1>
                <p class="error-message">The requested product could not be found or there was an error loading the product details.</p>
                <a href="<?php echo $backLink; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to <?php echo $categoryTitle; ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Size Chart Modal -->
    <div id="sizeChartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-btn" onclick="closeSizeChartModal()" aria-label="Close">×</span>
                <h2><i class="fas fa-ruler"></i> <?php echo $categoryTitle; ?> Size Chart</h2>
            </div>
            <div class="modal-body">
                <?php if ($product && strtolower($product['category']) === 'kidswear'): ?>
                    <table class="size-chart-table">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Age (Years)</th>
                                <th>Height (in)</th>
                                <th>Chest (in)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>2T</strong></td>
                                <td>2-3</td>
                                <td>33-35</td>
                                <td>20-21</td>
                            </tr>
                            <tr>
                                <td><strong>4T</strong></td>
                                <td>4-5</td>
                                <td>39-41</td>
                                <td>22-23</td>
                            </tr>
                            <tr>
                                <td><strong>6</strong></td>
                                <td>6-7</td>
                                <td>45-47</td>
                                <td>24-25</td>
                            </tr>
                            <tr>
                                <td><strong>8</strong></td>
                                <td>8-9</td>
                                <td>50-52</td>
                                <td>26-27</td>
                            </tr>
                        </tbody>
                    </table>
                <?php elseif ($product && strtolower($product['category']) === 'womenswear'): ?>
                    <table class="size-chart-table">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Bust (in)</th>
                                <th>Waist (in)</th>
                                <th>Hips (in)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>XS</strong></td>
                                <td>32-34</td>
                                <td>24-26</td>
                                <td>34-36</td>
                            </tr>
                            <tr>
                                <td><strong>S</strong></td>
                                <td>34-36</td>
                                <td>26-28</td>
                                <td>36-38</td>
                            </tr>
                            <tr>
                                <td><strong>M</strong></td>
                                <td>36-38</td>
                                <td>28-30</td>
                                <td>38-40</td>
                            </tr>
                            <tr>
                                <td><strong>L</strong></td>
                                <td>38-40</td>
                                <td>30-32</td>
                                <td>40-42</td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <table class="size-chart-table">
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Chest (in)</th>
                                <th>Waist (in)</th>
                                <th>Hips (in)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>S</strong></td>
                                <td>34-36</td>
                                <td>28-30</td>
                                <td>34-36</td>
                            </tr>
                            <tr>
                                <td><strong>M</strong></td>
                                <td>38-40</td>
                                <td>32-34</td>
                                <td>38-40</td>
                            </tr>
                            <tr>
                                <td><strong>L</strong></td>
                                <td>42-44</td>
                                <td>36-38</td>
                                <td>42-44</td>
                            </tr>
                            <tr>
                                <td><strong>XL</strong></td>
                                <td>46-48</td>
                                <td>40-42</td>
                                <td>46-48</td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
                <p class="size-note">
                    <i class="fas fa-info-circle"></i> 
                    Note: Measurements are approximate and may vary slightly. Please use this chart as a general guide.
                </p>
            </div>
        </div>
    </div>

    <script>
        function setSize() {
            const size = document.getElementById('size').value;
            document.getElementById('hidden_size').value = size;
        }

        function validateOrder() {
            <?php if (!$isLoggedIn): ?>
                alert('Please sign in to place an order.');
                window.location.href = 'signin.php';
                return false;
            <?php endif; ?>
            
            const size = document.getElementById('size').value;
            if (!size) {
                alert('Please select a size before placing your order.');
                return false;
            }
            
            setSize();
            return true;
        }

        function toggleSizeChartModal() {
            const modal = document.getElementById('sizeChartModal');
            modal.classList.toggle('show');
        }

        function closeSizeChartModal() {
            const modal = document.getElementById('sizeChartModal');
            modal.classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('sizeChartModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSizeChartModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSizeChartModal();
            }
        });
    </script>
</body>
</html>