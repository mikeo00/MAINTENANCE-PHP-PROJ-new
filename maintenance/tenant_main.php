<?php
session_start();
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user = getUserByEmail($_SESSION['user_email']);
if (!$user) {
    header('Location: login.php');
    exit;
}

// Get user's organizations
$userOrganizations = getUserOrganizations($user['id']);

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
    <title>My Organizations - MaintenanceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="units_main.css">
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
            <div class="user-avatar"><i class="fas fa-user"></i></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="tenant_main.php" class="nav-item active">
                <img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30">
                <span>My Organizations</span>
            </a>
            <a href="all_requests.php" class="nav-item">
                <img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30">
                <span>All Requests</span>
            </a>
            <a href="profile.php" class="nav-item">
                <img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30">
                <span>Profile</span>
            </a>
            <a href="?logout=1" class="nav-item">
                <img src="images/logout-2-svgrepo-com.svg" alt="Logo" width="30" height="30">
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>My Organizations</h1>
                <p>View and manage your organizations</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-alert">
                <?php if ($_GET['success'] === 'joined'): ?>
                    <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30">
                    Successfully joined organization! You can now submit maintenance requests.
                <?php elseif ($_GET['success'] === 'joined_unit'): ?>
                    <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30">
                    Successfully joined organization!
                <?php elseif ($_GET['success'] === 'pending'): ?>
                    <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30">
                    Request submitted! Your membership is pending approval from the landlord.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($userOrganizations)): ?>
            <!-- No Organizations State -->
            <div class="empty-state">
                <div class="empty-icon"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <h2>No Organizations Found</h2>
                <p>You haven't joined any organizations yet. Add your first organization to get started with maintenance requests.</p>
                <a href="join_organization.php" class="btn-primary">Join Organization</a>
            </div>
        <?php else: ?>
            <!-- Organizations Grid -->
            <div class="organizations-grid">
                <?php foreach ($userOrganizations as $org): ?>
                    <a href="organization_units.php?org_id=<?php echo $org['id']; ?>" class="organization-card">
                        <div class="organization-icon">
                            <div class="icon-circle"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                        </div>
                        
                        <div class="organization-content">
                            <div class="org-header">
                                <h2><?php echo htmlspecialchars($org['name']); ?></h2>
                            </div>
                            
                            <p class="org-address"><img src="images/map-marker-2-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($org['address']); ?></p>
                            
                            <?php if (!empty($org['description'])): ?>
                                <p class="org-description"><?php echo htmlspecialchars($org['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="card-footer">
                                <span class="view-units-btn">View My Units →</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <!-- Add New Organization Card -->
                <a href="join_organization.php" class="organization-card add-card">
                    <div class="add-card-content">
                        <div class="add-icon"><img src="images/plus-large-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                        <h3>Join New Organization</h3>
                        <p>Add a new organization to manage</p>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleMenu() {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburgerMenu');
            sidebar.classList.toggle('active');
            hamburger.classList.toggle('shifted');
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburgerMenu');
            
            if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
                sidebar.classList.remove('active');
                hamburger.classList.remove('shifted');
            }
        });
    </script>
</body>
</html>
