<?php
session_start();
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "localhost";
$dbname = "candy";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$messageClass = "";
$accountCreated = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
        $messageClass = "error";
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $pincode = filter_input(INPUT_POST, 'pincode', FILTER_SANITIZE_STRING);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Server-side validation
        if (empty($username) || strlen($username) > 50) {
            $message = "Username is required and must be 50 characters or less.";
            $messageClass = "error";
        } elseif (empty($email) || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "A valid email is required (max 100 characters).";
            $messageClass = "error";
        } elseif (strlen($password) < 6) {
            $message = "Password must be at least 6 characters.";
            $messageClass = "error";
        } elseif ($password !== $confirm_password) {
            $message = "Passwords do not match.";
            $messageClass = "error";
        } elseif (!empty($age) && (!is_numeric($age) || $age < 1 || $age > 120)) {
            $message = "Age must be between 1 and 120 if provided.";
            $messageClass = "error";
        } elseif (!empty($phone) && (strlen($phone) > 20 || !preg_match("/^[0-9]+$/", $phone))) {
            $message = "Phone number must be numeric and 20 characters or less if provided.";
            $messageClass = "error";
        } elseif (!empty($pincode) && (strlen($pincode) > 10 || !preg_match("/^[0-9]+$/", $pincode))) {
            $message = "Pincode must be numeric and 10 characters or less if provided.";
            $messageClass = "error";
        } elseif (!empty($gender) && strlen($gender) > 10) {
            $message = "Gender must be 10 characters or less if provided.";
            $messageClass = "error";
        } elseif (!empty($city) && strlen($city) > 100) {
            $message = "City must be 100 characters or less if provided.";
            $messageClass = "error";
        } elseif (!empty($state) && strlen($state) > 100) {
            $message = "State must be 100 characters or less if provided.";
            $messageClass = "error";
        } elseif (!empty($address) && strlen($address) > 255) {
            $message = "Address must be 255 characters or less if provided.";
            $messageClass = "error";
        } else {
            // Check for existing username or email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "Username or Email already exists.";
                $messageClass = "error";
            } else {
                $stmt->close();

                // Insert user (excluding created_at as it has a default value)
                $stmt = $conn->prepare("INSERT INTO users (username, email, age, gender, phone, city, state, address, pincode, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                // Bind parameters, using NULL for optional fields if empty
                $age = !empty($age) ? $age : null;
                $gender = !empty($gender) ? $gender : null;
                $phone = !empty($phone) ? $phone : null;
                $city = !empty($city) ? $city : null;
                $state = !empty($state) ? $state : null;
                $address = !empty($address) ? $address : null;
                $pincode = !empty($pincode) ? $pincode : null;

                $stmt->bind_param(
                    "ssisssssss",
                    $username,
                    $email,
                    $age,
                    $gender,
                    $phone,
                    $city,
                    $state,
                    $address,
                    $pincode,
                    $password // Store password as plain text
                );

                if ($stmt->execute()) {
                    $accountCreated = true;
                } else {
                    $message = "Error: " . $stmt->error;
                    $messageClass = "error";
                }
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up - Dress Shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: url('https://images.unsplash.com/photo-1520006400894-c1a7e33fb800?q=80&w=2070&auto=format&fit=crop') no-repeat center center/cover;
      font-family: 'Inter', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
      position: relative;
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.3);
      z-index: -1;
    }

    .signup-wrapper {
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 1000px;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .signup-wrapper h2 {
      margin-bottom: 30px;
      font-weight: 700;
      color: #222;
      text-align: center;
      font-size: 28px;
    }

    .form-container {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }

    .form-half {
      flex: 1;
      min-width: 300px;
    }

    .form-group {
      position: relative;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #444;
      font-size: 14px;
    }

    .input-icon {
      position: relative;
    }

    .input-icon i {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      color: #777;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    input[type="password"],
    select {
      width: 100%;
      padding: 12px 15px 12px 40px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      font-size: 16px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    input:focus,
    select:focus {
      border-color: #ff6b6b;
      box-shadow: 0 0 8px rgba(255, 107, 107, 0.3);
      outline: none;
    }

    .password-strength {
      font-size: 12px;
      color: #777;
      margin-top: 5px;
      display: none;
    }

    .password-strength.weak { color: #dc3545; }
    .password-strength.medium { color: #ffc107; }
    .password-strength.strong { color: #28a745; }

    input[type="submit"] {
      width: 100%;
      background: linear-gradient(90deg, #ff6b6b, #ff8e53);
      border: none;
      padding: 15px;
      font-size: 18px;
      font-weight: 700;
      color: white;
      border-radius: 12px;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s;
      position: relative;
      margin-top: 20px;
    }

    input[type="submit"]:hover {
      background: linear-gradient(90deg, #e55d5d, #e07b39);
      transform: translateY(-2px);
    }

    input[type="submit"]:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .message {
      text-align: center;
      padding: 12px;
      font-weight: 600;
      border-radius: 8px;
      margin: 15px 0;
      display: none;
      width: 100%;
    }

    .message.error {
      background: #f8d7da;
      color: #842029;
      display: block;
    }

    .message.success {
      background: #d1e7dd;
      color: #0f5132;
      display: block;
    }

    .signin-link {
      text-align: center;
      font-size: 14px;
      margin-top: 20px;
    }

    .signin-link a {
      color: #ff6b6b;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .signin-link a:hover {
      color: #e55d5d;
    }

    .loading {
      display: none;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 16px;
      color: #fff;
    }

    @media (max-width: 768px) {
      .form-container {
        flex-direction: column;
        gap: 10px;
      }
      .form-half {
        min-width: 100%;
      }
      .signup-wrapper {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
<?php if ($accountCreated): ?>
  <script>
    window.location.href = "signin.php";
  </script>
<?php else: ?>
  <div class="signup-wrapper">
    <h2>Create Your Account</h2>
    <form id="signupForm" method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <div class="form-container">
        <div class="form-half">
          <div class="form-group">
            <label for="username">Username</label>
            <div class="input-icon">
              <i class="fas fa-user"></i>
              <input type="text" id="username" name="username" required maxlength="50" aria-describedby="usernameHelp" />
            </div>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <div class="input-icon">
              <i class="fas fa-envelope"></i>
              <input type="email" id="email" name="email" required maxlength="100" aria-describedby="emailHelp" />
            </div>
          </div>
          <div class="form-group">
            <label for="age">Age</label>
            <div class="input-icon">
              <i class="fas fa-calendar-alt"></i>
              <input type="number" id="age" name="age" min="1" max="120" aria-describedby="ageHelp" />
            </div>
          </div>
          <div class="form-group">
            <label for="gender">Gender</label>
            <div class="input-icon">
              <i class="fas fa-venus-mars"></i>
              <select id="gender" name="gender">
                <option value="" selected>-- Select Gender --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Others">Others</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <div class="input-icon">
              <i class="fas fa-phone"></i>
              <input type="text" id="phone" name="phone" maxlength="20" placeholder="Enter phone number" />
            </div>
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <div class="input-icon">
              <i class="fas fa-city"></i>
              <input type="text" id="city" name="city" maxlength="100" />
            </div>
          </div>
        </div>
        <div class="form-half">
          <div class="form-group">
            <label for="state">State</label>
            <div class="input-icon">
              <i class="fas fa-globe"></i>
              <select id="state" name="state">
                <option value="" selected>-- Select State --</option>
                <option value="Andhra Pradesh">Andhra Pradesh</option>
                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                <option value="Assam">Assam</option>
                <option value="Bihar">Bihar</option>
                <option value="Chhattisgarh">Chhattisgarh</option>
                <option value="Goa">Goa</option>
                <option value="Gujarat">Gujarat</option>
                <option value="Haryana">Haryana</option>
                <option value="Himachal Pradesh">Himachal Pradesh</option>
                <option value="Jharkhand">Jharkhand</option>
                <option value="Karnataka">Karnataka</option>
                <option value="Kerala">Kerala</option>
                <option value="Madhya Pradesh">Madhya Pradesh</option>
                <option value="Maharashtra">Maharashtra</option>
                <option value="Manipur">Manipur</option>
                <option value="Meghalaya">Meghalaya</option>
                <option value="Mizoram">Mizoram</option>
                <option value="Nagaland">Nagaland</option>
                <option value="Odisha">Odisha</option>
                <option value="Punjab">Punjab</option>
                <option value="Rajasthan">Rajasthan</option>
                <option value="Sikkim">Sikkim</option>
                <option value="Tamil Nadu">Tamil Nadu</option>
                <option value="Telangana">Telangana</option>
                <option value="Tripura">Tripura</option>
                <option value="Uttar Pradesh">Uttar Pradesh</option>
                <option value="Uttarakhand">Uttarakhand</option>
                <option value="West Bengal">West Bengal</option>
                <option value="Delhi">Delhi</option>
                <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                <option value="Ladakh">Ladakh</option>
                <option value="Puducherry">Puducherry</option>
                <option value="Chandigarh">Chandigarh</option>
                <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
                <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
                <option value="Lakshadweep">Lakshadweep</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="address">Address</label>
            <div class="input-icon">
              <i class="fas fa-home"></i>
              <input type="text" id="address" name="address" maxlength="255" />
            </div>
          </div>
          <div class="form-group">
            <label for="pincode">Pincode</label>
            <div class="input-icon">
              <i class="fas fa-map-pin"></i>
              <input type="text" id="pincode" name="pincode" maxlength="10" placeholder="Enter pincode" />
            </div>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-icon">
              <i class="fas fa-lock"></i>
              <input type="password" id="password" name="password" required minlength="6" aria-describedby="passwordHelp" />
            </div>
            <div id="passwordStrength" class="password-strength"></div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-icon">
              <i class="fas fa-lock"></i>
              <input type="password" id="confirm_password" name="confirm_password" required minlength="6" />
            </div>
          </div>
        </div>
      </div>
      <?php if ($message): ?>
        <div class="message <?php echo $messageClass; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>
      <input type="submit" value="Sign Up" id="submitBtn" />
      <div class="loading" id="loading">Loading...</div>
    </form>
    <div class="signin-link">
      Already have an account? <a href="signin.php">Sign In</a>
    </div>
  </div>
<?php endif; ?>

<script>
  const passwordInput = document.getElementById('password');
  const passwordStrength = document.getElementById('passwordStrength');
  const ageInput = document.getElementById('age');
  const phoneInput = document.getElementById('phone');
  const pincodeInput = document.getElementById('pincode');

  // Password strength indicator
  passwordInput.addEventListener('input', () => {
    const password = passwordInput.value;
    passwordStrength.style.display = 'block';
    if (password.length < 6) {
      passwordStrength.textContent = 'Weak: Password must be at least 6 characters';
      passwordStrength.className = 'password-strength weak';
    } else if (password.length < 10 || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
      passwordStrength.textContent = 'Medium: Include uppercase and numbers for stronger password';
      passwordStrength.className = 'password-strength medium';
    } else {
      passwordStrength.textContent = 'Strong';
      passwordStrength.className = 'password-strength strong';
    }
  });

  // Age validation
  ageInput.addEventListener('input', () => {
    const age = parseInt(ageInput.value);
    if (age < 1 || age > 120) {
      ageInput.setCustomValidity('Age must be between 1 and 120');
    } else {
      ageInput.setCustomValidity('');
    }
  });

  // Phone validation
  phoneInput.addEventListener('input', () => {
    const phone = phoneInput.value;
    if (phone && !/^[0-9]+$/.test(phone)) {
      phoneInput.setCustomValidity('Phone number must be numeric');
    } else {
      phoneInput.setCustomValidity('');
    }
  });

  // Pincode validation
  pincodeInput.addEventListener('input', () => {
    const pincode = pincodeInput.value;
    if (pincode && !/^[0-9]+$/.test(pincode)) {
      pincodeInput.setCustomValidity('Pincode must be numeric');
    } else {
      pincodeInput.setCustomValidity('');
    }
  });
</script>
</body>
</html>
<?php ob_end_flush(); ?>