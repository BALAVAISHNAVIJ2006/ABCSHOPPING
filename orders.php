<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "candy";
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle order status updates
$success_message = '';
$error_message = '';
$valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['id'], $_POST['status'])) {
    $order_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    if ($order_id === false || !in_array($status, $valid_statuses)) {
        $error_message = "Invalid order ID or status.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_amount = $order['total_amount'] ?? 0.00;

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $pdo->commit();

            if ($status === 'cancelled') {
                // Recalculate stats to reflect cancellation
                $stats_query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as active_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue
                    FROM orders";
                $stats_stmt = $pdo->query($stats_query);
                $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_orders' => 0, 'pending_orders' => 0, 'processing_orders' => 0, 'completed_orders' => 0, 'cancelled_orders' => 0, 'total_revenue' => 0.00];
            }
            $success_message = "Order status updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Failed to update order status: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT o.*, c.name as customer_name, c.email as customer_email 
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.customer_id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $query .= " AND o.order_date >= ?";
    $params[] = $date_from . ' 00:00:00'; // Ensure full datetime
}

if ($date_to) {
    $query .= " AND o.order_date <= ?";
    $params[] = $date_to . ' 23:59:59'; // Ensure full datetime range
}

if ($search) {
    $query .= " AND (o.id LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $search_param = "%" . trim($search) . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY o.order_date DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Failed to fetch orders: " . $e->getMessage();
    $orders = [];
}

// Get order statistics
$stats = ['total_orders' => 0, 'pending_orders' => 0, 'processing_orders' => 0, 'completed_orders' => 0, 'cancelled_orders' => 0, 'total_revenue' => 0.00];
try {
    $stats_query = "SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as active_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue
        FROM orders";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
} catch (PDOException $e) {
    $error_message = "Failed to fetch statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - ABC Shopping Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4c51bf;
            --secondary-color: #a855f7;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --sidebar-bg: #1e293b;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #d1d5db 100%);
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
        }
        .app-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
            border-right: 1px solid var(--glass-border);
        }
        .sidebar-header { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
        .sidebar-logo { width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; }
        .sidebar-title { font-size: 1.5rem; font-weight: 700; color: #e0e7ff; }
        .sidebar-nav { list-style: none; }
        .nav-item { margin-bottom: 10px; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; color: rgba(255, 255, 255, 0.8);
            text-decoration: none; border-radius: var(--border-radius);
            transition: var(--transition); font-weight: 500;
        }
        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.1); transform: translateX(5px); }
        .nav-link.active { background: var(--primary-color); color: white; font-weight: 600; }
        .nav-icon { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; }
        .sidebar-toggle {
            display: none; position: fixed; top: 15px; left: 15px;
            z-index: 1001; background: var(--primary-color);
            color: white; border: none; width: 45px; height: 45px;
            border-radius: 50%; font-size: 16px; cursor: pointer;
            box-shadow: var(--shadow-md); transition: var(--transition);
        }
        .sidebar-toggle:hover { transform: scale(1.1); }
        .main-content { flex: 1; margin-left: 260px; padding: 20px; min-height: 100vh; transition: var(--transition); }
        .header {
            background: var(--glass-bg); backdrop-filter: blur(10px);
            padding: 20px; border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg); margin-bottom: 20px;
            border: 1px solid var(--glass-border);
        }
        .header-content { display: flex; align-items: center; justify-content: space-between; gap: 15px; }
        .header-left h1 { font-size: 2rem; font-weight: 800; color: var(--primary-color); margin-bottom: 5px; }
        .header-left p { color: var(--text-secondary); font-size: 1rem; font-weight: 500; }
        .header-right .live-indicator {
            display: flex; align-items: center; gap: 8px;
            background: var(--success-color); color: white;
            padding: 10px 15px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 600;
            box-shadow: var(--shadow-md);
        }
        .live-dot { width: 6px; height: 6px; background: white; border-radius: 50%; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.3; } }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card {
            background: var(--glass-bg); backdrop-filter: blur(10px);
            padding: 20px; border-radius: var(--border-radius);
            box-shadow: var(--shadow-md); text-align: center;
            border: 1px solid var(--glass-border); transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .stat-icon { font-size: 2rem; margin-bottom: 10px; color: var(--primary-color); }
        .stat-number { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; color: var(--text-primary); }
        .stat-label { color: var(--text-secondary); font-weight: 500; font-size: 0.85rem; text-transform: uppercase; }
        .filters { background: var(--glass-bg); backdrop-filter: blur(10px); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow-md); margin-bottom: 20px; border: 1px solid var(--glass-border); }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-weight: 600; margin-bottom: 5px; color: var(--text-primary); font-size: 0.9rem; }
        input, select { padding: 10px; border: 1px solid var(--glass-border); border-radius: var(--border-radius); font-size: 14px; background: rgba(255, 255, 255, 0.9); transition: var(--transition); }
        input:focus, select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(76, 81, 191, 0.2); }
        .btn { padding: 10px 20px; border: none; border-radius: var(--border-radius); cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition); }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: #3b41b4; transform: translateY(-2px); }
        .btn-secondary { background: #64748b; color: white; }
        .btn-secondary:hover { background: #475569; transform: translateY(-2px); }
        .orders-table { background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--shadow-lg); overflow-x: auto; border: 1px solid var(--glass-border); }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--glass-border); }
        th { background: rgba(255, 255, 255, 0.2); font-weight: 700; color: var(--text-primary); font-size: 0.85rem; text-transform: uppercase; }
        tr:hover { background: rgba(76, 81, 191, 0.05); }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
        .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
        .status-processing { background: #dbeafe; color: #1e40af; border: 1px solid #3b82f6; }
        .status-completed { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .status-cancelled { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .status-select { padding: 5px 8px; border: 1px solid var(--glass-border); border-radius: 6px; font-size: 0.75rem; background: rgba(255, 255, 255, 0.9); cursor: pointer; }
        .status-select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(76, 81, 191, 0.2); }
        .success-message { background: var(--success-color); color: white; padding: 12px 16px; border-radius: var(--border-radius); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-weight: 600; box-shadow: var(--shadow-md); }
        .no-orders { text-align: center; padding: 40px 20px; color: var(--text-secondary); }
        .no-orders i { font-size: 3rem; margin-bottom: 15px; color: var(--primary-color); }
        .no-orders h3 { font-size: 1.2rem; margin-bottom: 10px; color: var(--text-primary); }
        .no-orders p { font-size: 0.9rem; max-width: 300px; margin: 0 auto; }
        .customer-info { display: flex; flex-direction: column; gap: 2px; }
        .customer-name { font-weight: 600; color: var(--text-primary); }
        .customer-email { font-size: 0.75rem; color: var(--text-muted); }
        .order-id { font-family: monospace; font-weight: 600; color: var(--primary-color); background: rgba(76, 81, 191, 0.1); padding: 2px 6px; border-radius: 4px; }
        .amount { font-weight: 700; color: #10b981; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .sidebar-toggle { display: block; }
            .main-content { margin-left: 0; padding: 60px 15px 15px; }
        }
        @media (max-width: 768px) {
            .header-content { flex-direction: column; text-align: center; }
            .filter-row { grid-template-columns: 1fr; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            table { font-size: 12px; }
            th, td { padding: 10px; }
        }
        @media (max-width: 480px) {
            .stats { grid-template-columns: 1fr; }
            .header-left h1 { font-size: 1.5rem; }
            .stat-number { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">A</div>
            <h1 class="sidebar-title">Admin Dashboard</h1>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-home"></i></span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="adddress.php" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-plus"></i></span>
                        Add Dress
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
                        Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-box"></i></span>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-users"></i></span>
                        Customers
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <main class="main-content">
        <div class="container">
            <div class="header">
                <div class="header-content">
                    <div class="header-left">
                        <h1><i class="fas fa-tshirt"></i> ABC Shopping</h1>
                        <p>Premium Dress Orders Management System</p>
                    </div>
                    <div class="header-right">
                        <div class="live-indicator">
                            <div class="live-dot"></div>
                            <span>Live Dashboard</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php elseif (isset($error_message)): ?>
                <div class="success-message" style="background: #ef4444; color: white;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['pending_orders']); ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['completed_orders']); ?></div>
                    <div class="stat-label">Completed Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                    <div class="stat-number">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-number"><?php echo number_format($stats['cancelled_orders']); ?></div>
                    <div class="stat-label">Cancelled Orders</div>
                </div>
            </div>
            <div class="filters">
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search"><i class="fas fa-search"></i> Search Orders</label>
                            <input type="text" id="search" name="search" placeholder="Order ID, customer name, email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="status"><i class="fas fa-filter"></i> Filter by Status</label>
                            <select id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date_from"><i class="fas fa-calendar"></i> From Date</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="date_to"><i class="fas fa-calendar"></i> To Date</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="?" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="orders-table">
                <?php if (empty($orders)): ?>
                    <div class="no-orders">
                        <i class="fas fa-inbox"></i>
                        <h3>No orders found</h3>
                        <p>Try adjusting your filters or check back later for new orders.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Order ID</th>
                                <th><i class="fas fa-user"></i> Customer</th>
                                <th><i class="fas fa-calendar-alt"></i> Ordered Date</th>
                                <th><i class="fas fa-rupee-sign"></i> Amount</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="order-id">#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-name"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest Customer'); ?></div>
                                            <div class="customer-email"><?php echo htmlspecialchars($order['customer_email'] ?? 'No email provided'); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y, h:i A', strtotime($order['order_date'])); ?></td>
                                    <td class="amount">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
<?php $pdo = null; ?>

