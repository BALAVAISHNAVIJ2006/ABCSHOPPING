<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "candy";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle category filter
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch dresses based on selected category
$sql = "SELECT name, category, price, description, image FROM dresses";
if ($category !== 'all') {
    $sql .= " WHERE category = ?";
}
$stmt = $conn->prepare($sql);
if ($category !== 'all') {
    $stmt->bind_param("s", $category);
}
$stmt->execute();
$result = $stmt->get_result();

// Handle delete action with prepared statement
if (isset($_GET['delete']) && isset($_GET['name'])) {
    $name = $conn->real_escape_string($_GET['name']);
    $delete_sql = "DELETE FROM dresses WHERE name = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("s", $name);
    if ($delete_stmt->execute()) {
        header("Location: products.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $delete_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-card {
            max-width: 300px;
            margin: 10px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        .product-image {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            transition: opacity 0.3s ease;
        }
        .product-image:hover {
            opacity: 0.9;
        }
        .filter-dropdown {
            position: relative;
        }
        .filter-menu {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            z-index: 1;
        }
        .filter-dropdown:hover .filter-menu {
            display: block;
        }
        .filter-menu a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .filter-menu a:hover {
            background-color: #f1f1f1;
        }
        .btn-group {
            margin-top: 10px;
        }
        .btn-group button {
            margin-right: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }
        .btn-edit:hover {
            background-color: #45a049;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn-delete:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-purple-900 text-white flex flex-col p-4 fixed h-full">
            <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>
            <nav>
                <ul>
                    <li class="mb-4">
                        <a href="dashboard.php" class="flex items-center p-2 rounded-lg hover:bg-purple-700 transition">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="adddress.php" class="flex items-center p-2 rounded-lg bg-purple-700">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Dress
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="orders.php" class="flex items-center p-2 rounded-lg hover:bg-purple-700 transition">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Orders
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="products.php" class="flex items-center p-2 rounded-lg bg-purple-700">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Products
                        </a>
                    </li>
                    <li class="mb-4">
                        <a href="customers.php" class="flex items-center p-2 rounded-lg hover:bg-purple-700 transition">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Customers
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <!-- Main Content -->
        <div class="flex-1 p-6 ml-64">
            <div class="container mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-purple-900">Product Listings</h1>
                    <div class="filter-dropdown">
                        <button class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition duration-300">
                            Filter by Category
                        </button>
                        <div class="filter-menu">
                            <a href="?category=all" class="block">All</a>
                            <a href="?category=Menswear" class="block">Menswear</a>
                            <a href="?category=Womenswear" class="block">Womenswear</a>
                            <a href="?category=Kidswear" class="block">Kidswear</a>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="product-card">';
                            echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '" class="product-image">';
                            echo '<h2 class="text-xl font-semibold mt-2 text-gray-800">' . htmlspecialchars($row['name']) . '</h2>';
                            echo '<p class="text-gray-600">Category: ' . htmlspecialchars($row['category']) . '</p>';
                            echo '<p class="text-gray-800 font-medium">Price: ₹' . htmlspecialchars($row['price']) . '</p>';
                            echo '<p class="text-gray-700 line-clamp-2">' . htmlspecialchars($row['description']) . '</p>';
                            echo '<div class="btn-group">';
                            echo '<a href="editproduct.php?name=' . urlencode($row['name']) . '"><button class="btn-edit">Edit</button></a>';
                            echo '<a href="?delete=1&name=' . urlencode($row['name']) . '" onclick="return confirm(\'Are you sure you want to delete this product?\');"><button class="btn-delete">Delete</button></a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-center text-gray-500 text-lg">No products available in this category.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $conn->close();
    ?>
</body>
</html>  