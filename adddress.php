<?php
session_start();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "candy";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$errors = [];
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid CSRF token.";
    } else {
        // Sanitize inputs
        $name = $conn->real_escape_string(trim($_POST['name']));
        $category = $conn->real_escape_string(trim($_POST['category']));
        $price = floatval($_POST['price']);
        $description = $conn->real_escape_string(trim($_POST['description']));

        // Validate inputs
        if (empty($name) || strlen($name) > 255) {
            $errors[] = "Dress name is required and must be less than 255 characters.";
        }
        if (!in_array($category, ["Menswear", "Womenswear", "Kidswear"])) {
            $errors[] = "Invalid category selected.";
        }
        if ($price <= 0) {
            $errors[] = "Price must be greater than 0.";
        }
        if (empty($description)) {
            $errors[] = "Description is required.";
        }

        // Handle image upload
        $target_dir = "uploads/";
        $target_file = "";
        $uploadOk = 1;

        // Create uploads directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (isset($_FILES["image"]) && $_FILES["image"]["size"] > 0) {
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $unique_filename = uniqid('dress_') . "." . $imageFileType;
            $target_file = $target_dir . $unique_filename;

            // Validate image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $errors[] = "File is not an image.";
                $uploadOk = 0;
            }
            // Check file size (limit to 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $errors[] = "File is too large. Maximum size is 5MB.";
                $uploadOk = 0;
            }
            // Allow certain file formats
            if (!in_array($imageFileType, ["jpg", "png", "jpeg"])) {
                $errors[] = "Only JPG, JPEG, and PNG files are allowed.";
                $uploadOk = 0;
            }
            // Try to upload file
            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $errors[] = "Error uploading your file.";
                    $uploadOk = 0;
                }
            }
        }

        // Insert data into database if no errors
        if (empty($errors) && $uploadOk == 1) {
            $sql = "INSERT INTO dresses (name, category, price, description, image) 
                    VALUES ('$name', '$category', '$price', '$description', '$target_file')";
            
            if ($conn->query($sql) === TRUE) {
                $success = "Dress added successfully!";
                // Clear form data
                $_POST = [];
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
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
    <title>Admin - Add Dress</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
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
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-3xl font-bold text-purple-900 mb-6 text-center">Add New Dress</h2>
                
                <!-- Display success or error messages -->
                <?php if (!empty($success)): ?>
                    <div class="text-purple-600 text-center font-semibold mb-4"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="text-red-500 text-center mb-4">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div>
                        <label for="name" class="block text-sm font-medium text-purple-900">Dress Name</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500 transition">
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-purple-900">Category</label>
                        <select id="category" name="category" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500 transition">
                            <option value="" disabled <?php echo !isset($_POST['category']) ? 'selected' : ''; ?>>Select Category</option>
                            <option value="Menswear" <?php echo isset($_POST['category']) && $_POST['category'] === 'Menswear' ? 'selected' : ''; ?>>Menswear</option>
                            <option value="Womenswear" <?php echo isset($_POST['category']) && $_POST['category'] === 'Womenswear' ? 'selected' : ''; ?>>Womenswear</option>
                            <option value="Kidswear" <?php echo isset($_POST['category']) && $_POST['category'] === 'Kidswear' ? 'selected' : ''; ?>>Kidswear</option>
                        </select>
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-purple-900">Price (₹)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500 transition">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-purple-900">Description</label>
                        <textarea id="description" name="description" rows="4" required class="mt-1 block w-full p-3 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500 transition"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    <div>
                        <label for="image" class="block text-sm font-medium text-purple-900">Dress Image</label>
                        <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full p-3 border border-gray-300 rounded-md">
                        <img id="imagePreview" class="image-preview" src="#" alt="Image Preview">
                    </div>
                    <button type="submit" class="w-full bg-purple-600 text-white p-3 rounded-md hover:bg-purple-700 transition font-semibold">Add Dress</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>