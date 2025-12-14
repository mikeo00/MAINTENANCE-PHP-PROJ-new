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

$unitId = $_GET['unit_id'] ?? null;
if (!$unitId) {
    header('Location: admin_main.php');
    exit;
}

// Verify landlord owns the organization this unit belongs to
$unit = getUnitById($unitId);
if (!$unit) {
    header('Location: admin_main.php');
    exit;
}

$organization = getOrganizationById($unit['organization_id']);
if ($organization['admin_id'] != $user['id']) {
    header('Location: admin_main.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $complaintId = (int)$_POST['complaint_id'];
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);
    
    global $conn;
    $query = "UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId AND unit_id = $unitId"; // Ensure complaint belongs to unit
    
    if (mysqli_query($conn, $query)) {
        header("Location: admin_unit_requests.php?unit_id=$unitId&success=updated");
        exit;
    }
}

// Get complaints for this unit
$complaints = getComplaintsByUnit($unitId);

// Statistics
$totalComplaints = count($complaints);
$pendingCount = count(array_filter($complaints, fn($c) => $c['status'] === 'pending'));
$inProgressCount = count(array_filter($complaints, fn($c) => $c['status'] === 'in_progress'));
$resolvedCount = count(array_filter($complaints, fn($c) => $c['status'] === 'resolved'));

// Logout handling

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - Unit <?php echo htmlspecialchars($unit['name']); ?></title>
    <link rel="stylesheet" href="units_main.css">
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        /* Overrides/Fixes for mixed css */
        .page-title h1 { color: #1e293b; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; text-decoration: none; color: #64748b; font-weight: 500; }
        .back-link:hover { color: #7c3aed; }
        .dashboard-content { padding-top: 0; }
        .complaints-section { margin-top: 20px; }
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
            <div class="user-avatar"><img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_main.php" class="nav-item">
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
                <span class="nav-icon"><img src="images/door-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <a href="admin_organization_units.php?org_id=<?php echo $organization['id']; ?>" class="back-link">
                    ← Back to Units
                </a>
                <h1>Unit <?php echo htmlspecialchars($unit['name']); ?> Requests</h1>
                <p><?php echo htmlspecialchars($organization['name']); ?></p>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-alert">
                    ✓ Status updated successfully!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                    <div class="stat-info">
                        <h3><?php echo $totalComplaints; ?></h3>
                        <p>Total</p>
                    </div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon"><img src="images/hour-glass-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                    <div class="stat-info">
                        <h3><?php echo $pendingCount; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card progress">
                    <div class="stat-icon"><img src="images/tools-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                    <div class="stat-info">
                        <h3><?php echo $inProgressCount; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                <div class="stat-card resolved">
                    <div class="stat-icon"><img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                    <div class="stat-info">
                        <h3><?php echo $resolvedCount; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <!-- Complaints Table -->
            <div class="complaints-section">
                <h2>Maintenance Requests</h2>
                
                <?php if (empty($complaints)): ?>
                    <div class="empty-state">
                        <p><img src="images/mail-box-mailbox-svgrepo-com.svg" alt="Logo" width="30" height="30"> No requests found for this unit.</p>
                    </div>
                <?php else: ?>
                    <div class="complaints-table">
                        <?php foreach ($complaints as $complaint): ?>
                            <div class="complaint-card">
                                <div class="complaint-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($complaint['title']); ?></h3>
                                        <div class="complaint-meta">
                                            <span class="meta-item"><img src="images/folder-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($complaint['category']); ?></span>
                                            <span class="meta-item"><img src="images/time-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo formatDateTime($complaint['submitted_at']); ?></span>
                                        </div>
                                    </div>
                                    <div class="badges">
                                        <span class="badge <?php echo getStatusBadgeClass($complaint['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="complaint-body">
                                    <p><?php echo htmlspecialchars($complaint['description']); ?></p>
                                </div>
                                
                                <div class="complaint-footer">
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                        <label for="status_<?php echo $complaint['id']; ?>">Update Status:</label>
                                        <select name="status" id="status_<?php echo $complaint['id']; ?>">
                                            <option value="pending" <?php echo $complaint['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="in_progress" <?php echo $complaint['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">Update</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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