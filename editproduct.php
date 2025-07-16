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
// Fetch product details for editing
$name = isset($_GET['name']) ? $conn->real_escape_string($_GET['name']) : '';
$sql = "SELECT name, category, price, description, image FROM dresses WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = floatval($_POST['price']);
    $description = $conn->real_escape_string($_POST['description']);
    $image = $row['image']; // Keep existing image unless new one is uploaded

    // Handle image upload if new file is provided
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $target_dir = "uploads/";
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid('dress_') . "." . $imageFileType;
        $target_file = $target_dir . $unique_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $target_file;
        }
    }

    // Update database with prepared statement
    $update_sql = "UPDATE dresses SET name = ?, category = ?, price = ?, description = ?, image = ? WHERE name = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssdsss", $new_name, $category, $price, $description, $image, $name);

    if ($update_stmt->execute()) {
        header("Location: products.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <a href="adddress.php" class="flex items-center p-2 rounded-lg hover:bg-purple-700 transition">
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
                        <a href="products.php" class="flex items-center p-2 rounded-lg hover:bg-purple-700 transition">
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
                <h1 class="text-3xl font-bold text-purple-900 mb-6">Edit Product</h1>
                <form method="post" enctype="multipart/form-data" class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-lg">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-purple-900">Dress Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div class="mb-4">
                        <label for="category" class="block text-sm font-medium text-purple-900">Category</label>
                        <select id="category" name="category" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                            <option value="Menswear" <?php echo $row['category'] === 'Menswear' ? 'selected' : ''; ?>>Menswear</option>
                            <option value="Womenswear" <?php echo $row['category'] === 'Womenswear' ? 'selected' : ''; ?>>Womenswear</option>
                            <option value="Kidswear" <?php echo $row['category'] === 'Kidswear' ? 'selected' : ''; ?>>Kidswear</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="price" class="block text-sm font-medium text-purple-900">Price (₹)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($row['price']); ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-purple-900">Description</label>
                        <textarea id="description" name="description" rows="4" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500"><?php echo htmlspecialchars($row['description']); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="image" class="block text-sm font-medium text-purple-900">Dress Image</label>
                        <input type="file" id="image" name="image" class="mt-1 block w-full p-3 border border-gray-300 rounded-md">
                        <p class="text-sm text-gray-500 mt-1">Current image: <?php echo htmlspecialchars($row['image']); ?></p>
                    </div>
                    <button type="submit" class="w-full bg-purple-600 text-white p-3 rounded-md hover:bg-purple-700 transition font-semibold">Update Dress</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>   