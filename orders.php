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
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --sidebar-bg: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #1a202c;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 8px 30px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 60px rgba(0, 0, 0, 0.2);
            --border-radius: 20px;
            --border-radius-sm: 12px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
        }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .app-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: var(--sidebar-bg); backdrop-filter: blur(20px); color: white; padding: 30px 25px; box-shadow: var(--shadow-xl); position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 1000; border-right: 1px solid rgba(255, 255, 255, 0.1); transition: var(--transition); }
        .sidebar-header { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; padding-bottom: 25px; border-bottom: 2px solid rgba(255, 255, 255, 0.1); }
        .sidebar-logo { width: 45px; height: 45px; background: var(--primary-gradient); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; box-shadow: var(--shadow-md); }
        .sidebar-title { font-size: 1.4rem; font-weight: 700; background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .sidebar-nav { list-style: none; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: var(--border-radius-sm); transition: var(--transition); font-weight: 500; position: relative; overflow: hidden; }
        .nav-link::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent); transition: var(--transition); }
        .nav-link:hover::before { left: 100%; }
        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.1); transform: translateX(5px); }
        .nav-link.active { background: var(--primary-gradient); color: white; box-shadow: var(--shadow-md); font-weight: 600; }
        .nav-icon { width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; }
        .sidebar-toggle { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: var(--primary-gradient); color: white; border: none; width: 50px; height: 50px; border-radius: 12px; font-size: 18px; cursor: pointer; box-shadow: var(--shadow-lg); transition: var(--transition); }
        .sidebar-toggle:hover { transform: scale(1.1); }
        .main-content { flex: 1; margin-left: 280px; padding: 30px; min-height: 100vh; transition: var(--transition); }
        .floating-particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; }
        .particle { position: absolute; width: 4px; height: 4px; background: rgba(255, 255, 255, 0.3); border-radius: 50%; animation: float 20s infinite linear; }
        @keyframes float { 0% { transform: translateY(100vh) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; } }
        .header { background: var(--glass-bg); backdrop-filter: blur(20px); padding: 35px; border-radius: var(--border-radius); box-shadow: var(--shadow-xl); margin-bottom: 30px; border: 1px solid var(--glass-border); position: relative; overflow: hidden; animation: slideInDown 0.8s ease-out; }
        @keyframes slideInDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary-gradient); animation: shimmer 2s infinite; }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        .header-content { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
        .header-left h1 { font-size: 2.5rem; font-weight: 800; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px; display: flex; align-items: center; gap: 15px; }
        .header-left h1 i { background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        .header-left p { color: var(--text-secondary); font-size: 1.1rem; font-weight: 500; }
        .header-right { display: flex; gap: 15px; align-items: center; }
        .live-indicator { display: flex; align-items: center; gap: 8px; background: var(--success-gradient); color: white; padding: 12px 20px; border-radius: 25px; font-size: 0.9rem; font-weight: 600; box-shadow: var(--shadow-md); }
        .live-dot { width: 8px; height: 8px; background: white; border-radius: 50%; animation: blink 1s infinite; }
        @keyframes blink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0.3; } }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: var(--glass-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow-lg); text-align: center; border: 1px solid var(--glass-border); transition: var(--transition); position: relative; overflow: hidden; animation: slideInUp 0.8s ease-out forwards; }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        @keyframes slideInUp { to { transform: translateY(0); opacity: 1; } }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary-gradient); transform: scaleX(0); transition: var(--transition); }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: var(--shadow-xl); }
        .stat-icon { font-size: 2.5rem; margin-bottom: 15px; opacity: 0.9; transition: var(--transition); }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(5deg); }
        .stat-number { font-size: 2.2rem; font-weight: 800; margin-bottom: 8px; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .stat-label { color: var(--text-secondary); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .filters { background: var(--glass-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow-lg); margin-bottom: 30px; border: 1px solid var(--glass-border); }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-weight: 600; margin-bottom: 8px; color: var(--text-primary); font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        input, select { padding: 12px 16px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: var(--border-radius-sm); font-size: 14px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); transition: var(--transition); font-weight: 500; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15); background: rgba(255, 255, 255, 0.95); transform: translateY(-2px); }
        .btn { padding: 12px 24px; border: none; border-radius: var(--border-radius-sm); cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: var(--transition); position: relative; overflow: hidden; }
        .btn::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent); transition: var(--transition); }
        .btn:hover::before { left: 100%; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: var(--shadow-md); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .btn-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; box-shadow: var(--shadow-md); }
        .btn-secondary:hover { background: linear-gradient(135deg, #4b5563, #374151); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .orders-table { background: var(--glass-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius); box-shadow: var(--shadow-xl); overflow: hidden; border: 1px solid var(--glass-border); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 16px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        th { background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%); font-weight: 700; color: var(--text-primary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 10; }
        tr { transition: var(--transition); }
        tr:hover { background: rgba(102, 126, 234, 0.08); transform: scale(1.01); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; transition: var(--transition); position: relative; }
        .status-badge::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent); transition: var(--transition); }
        .status-badge:hover::before { left: 100%; }
        .status-pending { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border: 2px solid #f59e0b; box-shadow: 0 3px 10px rgba(245, 158, 11, 0.3); }
        .status-processing { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; border: 2px solid #3b82f6; box-shadow: 0 3px 10px rgba(59, 130, 246, 0.3); }
        .status-completed { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; border: 2px solid #10b981; box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3); }
        .status-cancelled { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; border: 2px solid #ef4444; box-shadow: 0 3px 10px rgba(239, 68, 68, 0.3); }
        .status-select { padding: 6px 10px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 8px; font-size: 11px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); cursor: pointer; transition: var(--transition); font-weight: 600; }
        .status-select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2); transform: scale(1.05); }
        .success-message { background: var(--success-gradient); color: white; padding: 16px 20px; border-radius: var(--border-radius-sm); margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-weight: 600; box-shadow: var(--shadow-lg); animation: slideInDown 0.5s ease-out; }
        .no-orders { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .no-orders i { font-size: 4rem; margin-bottom: 20px; opacity: 0.6; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .no-orders h3 { font-size: 1.5rem; margin-bottom: 12px; color: var(--text-primary); font-weight: 700; }
        .no-orders p { font-size: 1rem; max-width: 350px; margin: 0 auto; }
        .customer-info { display: flex; flex-direction: column; gap: 4px; }
        .customer-name { font-weight: 700; color: var(--text-primary); font-size: 0.95rem; }
        .customer-email { font-size: 12px; color: var(--text-muted); font-style: italic; font-weight: 500; }
        .order-id { font-family: 'SF Mono', 'Monaco', 'Menlo', monospace; font-weight: 700; color: #667eea; font-size: 1rem; background: rgba(102, 126, 234, 0.1); padding: 4px 8px; border-radius: 6px; display: inline-block; }
        .amount { font-weight: 800; color: #059669; font-size: 1.1rem; display: flex; align-items: center; gap: 4px; }
        .amount::before { content: '₹'; font-size: 0.8rem; opacity: 0.7; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; } .sidebar.active { transform: translateX(0); } .sidebar-toggle { display: block; } .main-content { margin-left: 0; padding: 80px 20px 20px; } }
        @media (max-width: 768px) { .container { padding: 20px; } .header { padding: 25px; text-align: center; } .header-content { flex-direction: column; text-align: center; } .header-left h1 { font-size: 2.2rem; justify-content: center; } .filter-row { grid-template-columns: 1fr; } .stats { grid-template-columns: repeat(2, 1fr); gap: 20px; } table { font-size: 14px; } th, td { padding: 15px 10px; } .orders-table { overflow-x: auto; } }
        @media (max-width: 480px) { .stats { grid-template-columns: 1fr; } .header-left h1 { font-size: 1.8rem; } .stat-number { font-size: 2.2rem; } }
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); }
        ::-webkit-scrollbar-thumb { background: var(--primary-gradient); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--secondary-gradient); }
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
        <div class="floating-particles" id="particles"></div>
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
                <div class="success-message" style="background: var(--danger-gradient);">
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
                                            <i class="fas fa-circle"></i>
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
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + 'vw';
            particle.style.animationDuration = Math.random() * 10 + 10 + 's';
            particle.style.animationDelay = Math.random() * 5 + 's';
            particlesContainer.appendChild(particle);
        }
    </script>
</body>
</html> 