<?php
session_start();
require_once 'helpers.php';

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user = getUserByEmail($_SESSION['user_email']);
if (!$user || $user['role'] !== 'landlord') {
    header('Location: tenant_main.php');
    exit;
}

// Get landlord's organizations
$landlordOrganizations = getLandlordOrganizations($user['id']);

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
    <title>My Organizations - Admin Dashboard</title>
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
            <div class="user-avatar"><img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_main.php" class="nav-item active">
                <span class="nav-icon"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>My Organizations</span>
            </a>
            <a href="admin_manage_tenants.php" class="nav-item">
                <span class="nav-icon"><img src="images/people-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>Manage Tenants</span>
            </a>
            <a href="admin_all_requests.php" class="nav-item">
                <span class="nav-icon"><img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>All Requests</span>
            </a>
            <a href="profile.php" class="nav-item">
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
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>My Organizations</h1>
                <p>Manage your properties and units</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-alert">
                <?php if ($_GET['success'] === 'created'): ?>
                    <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> Successfully created new organization!
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($landlordOrganizations)): ?>
            <!-- No Organizations State -->
            <div class="empty-state">
                <div class="empty-icon"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <h2>No Organizations Found</h2>
                <p>You haven't created any organizations yet. Create your first organization to start managing units.</p>
                <a href="create_organization.php" class="btn-primary">Create Organization</a>
            </div>
        <?php else: ?>
            <!-- Organizations Grid -->
            <div class="organizations-grid">
                <?php foreach ($landlordOrganizations as $org): ?>
                    <a href="admin_organization_units.php?org_id=<?php echo $org['id']; ?>" class="organization-card">
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
                                <span class="view-units-btn">Manage Units →</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <!-- Create New Organization Card -->
                <a href="create_organization.php" class="organization-card add-card">
                    <div class="add-card-content">
                        <div class="add-icon"><img src="images/plus-large-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                        <h3>Create New Organization</h3>
                        <p>Add a new property to manage</p>
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