<?php
session_start();
require_once 'helpers.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user = getUserByEmail($_SESSION['user_email']);
if (!$user) {
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
    <title>Profile - MaintenanceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="profile.css"> <!-- use existing styles -->
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu" id="hamburgerMenu">
        <button class="hamburger-btn" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="user-avatar"><img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>  
        </div>
        <nav class="sidebar-nav">
            <a href="<?php echo $user['role'] === 'tenant' ? 'tenant_main.php' : 'admin_main.php'; ?>" class="nav-item">
                <span class="nav-icon"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>My organizations</span>
            </a>
            <?php if ($user['role'] === 'landlord'): ?>
            <a href="admin_manage_tenants.php" class="nav-item">
                <span class="nav-icon"><img src="images/people-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>Manage Tenants</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $user['role'] === 'tenant' ? 'all_requests.php' : 'admin_all_requests.php'; ?>" class="nav-item">
                <span class="nav-icon"><img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>All Requests</span>
            </a>
            <a href="profile.php" class="nav-item active">
                <span class="nav-icon"><img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>Profile</span>
            </a>
            <a href="?logout=1" class="nav-item">
                <span class="nav-icon"><img src="images/logout-2-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="header-content">
                <div>
                    <h1><img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30"> My Profile</h1>
                    <p>View and update your personal information</p>
                </div>
                <a href="<?php echo $user['role'] === 'tenant' ? 'tenant_main.php' : 'admin_main.php'; ?>" class="btn-back">Back to Organizations</a>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="requests-container">
            <div class="request-card">
                <h3>Name</h3>
                <p><?php echo htmlspecialchars($user['name']); ?></p>
            </div>
            <div class="request-card">
                <h3>Email</h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div class="request-card">
                <h3>Role</h3>
                <p><?php echo htmlspecialchars($user['role']); ?></p>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu(){
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburgerMenu');
            sidebar.classList.toggle('active');
            hamburger.classList.toggle('shifted');
        }
    </script>
</body>
</html>