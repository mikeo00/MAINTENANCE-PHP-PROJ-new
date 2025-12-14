<?php
session_start();
require_once 'helpers.php';

// Check if user is logged in and is a landlord
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

$user = getUserByEmail($_SESSION['user_email']);
if (!$user || $user['role'] !== 'landlord') {
    header('Location: tenant_main.php');
    exit;
}

$orgId = $_GET['org_id'] ?? null;
if (!$orgId) {
    header('Location: admin_main.php');
    exit;
}

$organization = getOrganizationById($orgId);

// Verify landlord owns this organization
if ($organization['admin_id'] != $user['id']) {
    header('Location: admin_main.php');
    exit;
}

// Handle Unit Creation
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_unit'])) {
    $unitName = mysqli_real_escape_string($conn, $_POST['unit_name']);
    $unitDesc = mysqli_real_escape_string($conn, $_POST['unit_description']);
    $orgIdInt = (int)$orgId;
    
    if (empty($unitName)) {
        $errorMessage = 'Unit name is required';
    } else {
        // Check if unit name already exists in this org
        $query = "SELECT id FROM units WHERE name = '$unitName' AND organization_id = $orgIdInt";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $errorMessage = 'Unit name already exists in this organization';
        } else {
            $query = "INSERT INTO units (name, description, organization_id) VALUES ('$unitName', '$unitDesc', $orgIdInt)";
            if (mysqli_query($conn, $query)) {
                $successMessage = 'Unit created successfully!';
                // Refresh to show new unit
                // header("Refresh:0"); // Optional or just fall through
            } else {
                $errorMessage = "Error creating unit: " . mysqli_error($conn);
            }
        }
    }
}

// Get Units
$units = getUnitsByOrganization($orgId);

// Logout handling

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Units - <?php echo htmlspecialchars($organization['name']); ?></title>
    <link rel="stylesheet" href="units_main.css">
    <style>
        /* Add some specific styles for admin view if needed */
        .add-unit-section {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .add-unit-form {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .form-input-group {
            flex: 1;
            min-width: 200px;
        }
        .form-input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
        }
        .btn-create {
            padding: 12px 24px;
            background: #7c3aed;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-create:hover {
            background: #6d28d9;
        }
    </style>
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
            <div class="user-avatar"><img src="images/user-svgrepo-com.svg" alt="logo" width="30" height="30"> </div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_main.php" class="nav-item">
                <span class="nav-icon"><img src="images/building-svgrepo-com.svg" alt="logo" width="30" height="30"></span>
                <span>My Organizations</span>
            </a>
            <a href="admin_manage_tenants.php" class="nav-item">
                <span class="nav-icon"><img src="images/chart-waterfall-svgrepo-com.svg" alt="logo" width="30" height="30"></span>
                <span>Manage Tenants</span>
            </a>
            <a href="admin_all_requests.php" class="nav-item">
                <span class="nav-icon"><img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>All Requests</span>
            </a>
            <a href="profile.php" class="nav-item">
                <span class="nav-icon"><img src="images/user-svgrepo-com.svg" alt="logo" width="30" height="30"></span>
                <span>Profile</span>
            </a>
            <a href="?logout=1" class="nav-item">
                <span class="nav-icon"><img src="images/door-svgrepo-com.svg" alt="logo" width="30" height="30"></span>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <a href="admin_main.php" class="back-link">← Back to Organizations</a>
                <h1><?php echo htmlspecialchars($organization['name']); ?></h1>
                <p>Manage units for this organization</p>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="success-alert">
                <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-alert">
                <img src="images/times-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Create Unit Section -->
        <div class="add-unit-section">
            <h3 style="margin-bottom: 16px;">Add New Unit</h3>
            <form method="POST" class="add-unit-form">
                <div class="form-input-group">
                    <input type="text" name="unit_name" placeholder="Unit Name/Number (e.g. 101, Apt 4B)" required>
                </div>
                <div class="form-input-group">
                    <input type="text" name="unit_description" placeholder="Description (Optional)">
                </div>
                <button type="submit" name="create_unit" class="btn-create">Add Unit</button>
            </form>
        </div>

        <?php if (empty($units)): ?>
            <div class="empty-state">
                <div class="empty-icon"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <h2>No Units Yet</h2>
                <p>This organization has no units. Add one above to get started.</p>
            </div>
        <?php else: ?>
            <!-- Units Grid -->
            <div class="units-grid">
                <?php foreach ($units as $unit): ?>
                    <a href="admin_unit_requests.php?unit_id=<?php echo $unit['id']; ?>" class="unit-card">
                        <div class="unit-icon">
                            <div class="icon-badge"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                        </div>
                        
                        <div class="unit-content">
                            <div class="unit-header">
                                <h2><?php echo htmlspecialchars($unit['name']); ?></h2>
                            </div>
                            
                            <?php if (!empty($unit['description'])): ?>
                                <p class="unit-description"><?php echo htmlspecialchars($unit['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="card-footer">
                                <span class="view-units-btn">View Requests →</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
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