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
$user_data = [];
$message = '';
$error = '';

// --- Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $age = trim($_POST['age'] ?? '');

    if (empty($username) || empty($phone) || empty($gender) || empty($address) || empty($city) || empty($state) || empty($pincode) || empty($age)) {
        $error = "All fields are required.";
    } else {
        $stmt_update = $conn->prepare("UPDATE users SET username = ?, phone = ?, gender = ?, address = ?, city = ?, state = ?, pincode = ?, age = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("ssssssssi", $username, $phone, $gender, $address, $city, $state, $pincode, $age, $user_id);
            if ($stmt_update->execute()) {
                // No message display, just proceed
            } else {
                $error = "Error updating profile: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $error = "Failed to prepare update statement: " . $conn->error;
        }
    }
}

// --- Fetch User Data ---
$stmt_fetch = $conn->prepare("SELECT username, phone, gender, address, email, city, state, pincode, age FROM users WHERE id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    } else {
        $error = "User data not found. Please log in again.";
        session_unset();
        session_destroy();
        header("Location: signin.php");
        exit();
    }
    $stmt_fetch->close();
} else {
    $error = "Failed to prepare fetch statement: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ABC Shopping</title>
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

        .edit-button {
            background-color: #fff;
            color: #6a0dad;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .edit-button:hover {
            background-color: #e9ecef;
        }

        .profile-main-card {
            background: linear-gradient(to right, #6a0dad, #8a2be2);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            margin: 30px 50px 0 50px; /* Align with the padding of profile-content-area */
            border-radius: 10px;
            flex-wrap: wrap;
            width: calc(100% - 100px); /* Account for left and right padding of 50px each */
            max-width: 100%; /* Span full width of the content area */
            box-sizing: border-box; /* Ensure padding is included in width */
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            font-size: 28px;
            font-weight: bold;
            margin-right: 20px;
        }

        .profile-card-info h2 {
            margin: 0 ;
            font-size: 20px;
            font-weight: 600;
        }

        .profile-card-info p {
          padding: 6px 12px;
    border-radius: 15px;
    display: inline-flex;
    align-items: center;
    margin: 2px 0;
    font-size: 14px;
    font-weight: 500;
    max-width: fit-content;  
        }

        .profile-card-info p i {
            margin-right: 8px;
            font-size: 14px;
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
            .profile-main-card {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }

        @media (max-width: 480px) {
            .profile-header {
                padding: 15px;
            }
            .edit-button {
                padding: 8px 15px;
                font-size: 14px;
            }
            .profile-avatar {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }
            .profile-card-info h2 {
                font-size: 20px;
            }
            .profile-card-info p {
                font-size: 14px;
            }
        }

        /* Additional styling for form and content area from second code */
        .profile-content-area {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            padding: 0 50px;
            margin-top: 20px;
        }
        .profile-info-section h3 i.fas {
    color: #8a2be2; /* Slightly different purple for distinction */
}
.quick-links-section h3 i.fas {
    color: #6a0dad; /* Matching the header color */
}
        .quick-links-section a[href="logout.php"] {
    background-color: #ff4d4d; /* Red background */
    color: #fff; /* White text for contrast */
}
        .quick-links-section {
            flex: 1;
            min-width: 220px;
            max-width: 250px;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .quick-links-section h3 {
            margin-top: 0;
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .quick-links-section ul {
            list-style: none;
            padding: 0;
        }

        .quick-links-section li {
            margin-bottom: 12px;
        }

        .quick-links-section a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .quick-links-section a:hover {
            background-color: #dee2e6;
            color: #222;
        }

        .quick-links-section a i {
            margin-right: 12px;
            font-size: 18px;
            color: #6a0dad;
        }

        .profile-info-section {
            flex: 2;
            min-width: 400px;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .profile-info-section h3 {
            margin-top: 0;
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 25px; /* Increased from 15px to 25px for more spacing */
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 15px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            background-color: #fff;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus {
            border-color: #8a2be2;
            box-shadow: 0 0 0 3px rgba(138, 43, 226, 0.2);
            outline: none;
        }

        .form-group input[type="email"][readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
            border: 1px solid #ddd;
            color: #777;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .form-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* New wrapper for scrollable content */
        .scrollable-wrapper {
            margin-top: 80px; /* Offset for fixed header */
            overflow-y: auto;
            height: calc(100vh - 80px); /* Full viewport height minus header */
        }
    </style>
</head>
<body>
    <div class="profile-header">
    <h1>My Profile</h1>
    <button class="edit-button" id="editProfileBtn"><i class="fas fa-edit"></i> Edit Profile</button>
</div>
    <div>
        <div class="profile-main-card">
            <?php
            $initials = '';
            if (isset($user_data['username'])) {
                $name_parts = explode(' ', $user_data['username']);
                foreach ($name_parts as $part) {
                    $initials .= strtoupper(substr($part, 0, 1));
                }
            } else {
                $initials = 'NA';
            }
            ?>
            <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="profile-card-info">
                <h2><?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></h2>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user_data['phone'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($user_data['gender'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-home"></i> <?php echo htmlspecialchars($user_data['address'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user_data['city'] ?? 'N/A'); ?>, <?php echo htmlspecialchars($user_data['state'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($user_data['pincode'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_data['age'] ?? 'N/A'); ?> years</p>
            </div>
            </div>
        <div class="profile-content-area">
            <div class="quick-links-section">
             <h3><i class="fas fa-bolt"></i> Quick Links</h3>

                <ul>
                    <li><a href="myorders.php"><i class="fas fa-shopping-cart"></i> My Orders</a></li>
                    <li><a href="wallet.php"><i class="fas fa-wallet"></i> Wallet</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
            <div class="profile-info-section">
                <h3><i class="fas fa-user-circle"></i> Profile Information</h3>
                <form id="profileForm" class="profile-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">User Name</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required disabled>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <select id="state" name="state" required disabled>
                                <option value="">Select State</option>
                                <option value="Andhra Pradesh" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Andhra Pradesh') ? 'selected' : ''; ?>>Andhra Pradesh</option>
                                <option value="Arunachal Pradesh" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Arunachal Pradesh') ? 'selected' : ''; ?>>Arunachal Pradesh</option>
                                <option value="Assam" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Assam') ? 'selected' : ''; ?>>Assam</option>
                                <option value="Bihar" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Bihar') ? 'selected' : ''; ?>>Bihar</option>
                                <option value="Chhattisgarh" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Chhattisgarh') ? 'selected' : ''; ?>>Chhattisgarh</option>
                                <option value="Goa" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Goa') ? 'selected' : ''; ?>>Goa</option>
                                <option value="Gujarat" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Gujarat') ? 'selected' : ''; ?>>Gujarat</option>
                                <option value="Haryana" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Haryana') ? 'selected' : ''; ?>>Haryana</option>
                                <option value="Himachal Pradesh" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Himachal Pradesh') ? 'selected' : ''; ?>>Himachal Pradesh</option>
                                <option value="Jharkhand" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Jharkhand') ? 'selected' : ''; ?>>Jharkhand</option>
                                <option value="Karnataka" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Karnataka') ? 'selected' : ''; ?>>Karnataka</option>
                                <option value="Kerala" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Kerala') ? 'selected' : ''; ?>>Kerala</option>
                                <option value="Madhya Pradesh" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Madhya Pradesh') ? 'selected' : ''; ?>>Madhya Pradesh</option>
                                <option value="Maharashtra" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Maharashtra') ? 'selected' : ''; ?>>Maharashtra</option>
                                <option value="Manipur" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Manipur') ? 'selected' : ''; ?>>Manipur</option>
                                <option value="Meghalaya" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Meghalaya') ? 'selected' : ''; ?>>Meghalaya</option>
                                <option value="Mizoram" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Mizoram') ? 'selected' : ''; ?>>Mizoram</option>
                                <option value="Nagaland" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Nagaland') ? 'selected' : ''; ?>>Nagaland</option>
                                <option value="Odisha" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Odisha') ? 'selected' : ''; ?>>Odisha</option>
                                <option value="Punjab" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Punjab') ? 'selected' : ''; ?>>Punjab</option>
                                <option value="Rajasthan" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Rajasthan') ? 'selected' : ''; ?>>Rajasthan</option>
                                <option value="Sikkim" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Sikkim') ? 'selected' : ''; ?>>Sikkim</option>
                                <option value="Tamil Nadu" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Tamil Nadu') ? 'selected' : ''; ?>>Tamil Nadu</option>
                                <option value="Telangana" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Telangana') ? 'selected' : ''; ?>>Telangana</option>
                                <option value="Tripura" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Tripura') ? 'selected' : ''; ?>>Tripura</option>
                                <option value="Uttar Pradesh" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Uttar Pradesh') ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                <option value="Uttarakhand" <?php echo (isset($user_data['state']) && $user_data['state'] == 'Uttarakhand') ? 'selected' : ''; ?>>Uttarakhand</option>
                                <option value="West Bengal" <?php echo (isset($user_data['state']) && $user_data['state'] == 'West Bengal') ? 'selected' : ''; ?>>West Bengal</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pincode">Pincode</label>
                            <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($user_data['pincode'] ?? ''); ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user_data['age'] ?? ''); ?>" required readonly min="1" max="120">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Address (Street)</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required readonly>
                    </div>
                    <button type="submit" name="update_profile" id="saveProfileBtn" style="display: none;">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('editProfileBtn').addEventListener('click', function() {
            const inputs = document.querySelectorAll('#profileForm input[readonly], #profileForm select[disabled]');
            const saveBtn = document.getElementById('saveProfileBtn');
            const isEditable = inputs[0].hasAttribute('readonly');
            inputs.forEach(input => {
                if (isEditable) {
                    input.removeAttribute('readonly');
                    if (input.tagName === 'SELECT') {
                        input.removeAttribute('disabled');
                    }
                } else {
                    input.setAttribute('readonly', true);
                    if (input.tagName === 'SELECT') {
                        input.setAttribute('disabled', true);
                    }
                }
            });

            saveBtn.style.display = isEditable ? 'block' : 'none';
            this.textContent = isEditable ? 'Cancel Edit' : 'Edit Profile';
        });
    </script>
</body>
</html>