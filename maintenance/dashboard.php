<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Apartment Maintenance System</title>
    <meta name="description" content="Manage your apartment maintenance requests">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #14b8a6 100%);
            color: white;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content h1 {
            font-size: 28px;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: white;
            color: #0ea5e9;
        }
        
        .dashboard-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 30px;
        }
        
        .welcome-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .welcome-card h2 {
            color: #1a1a2e;
            margin-bottom: 12px;
            font-size: 24px;
        }
        
        .welcome-card p {
            color: #6b7280;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="header-content">
            <img src="images/building-svgrepo-com.svg" > <h1> Maintenance Dashboard</h1>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?>!</h2>
            <p>Manage apartment maintenance requests and organizations from your dashboard.</p>
        </div>
    </div>
</body>
</html>