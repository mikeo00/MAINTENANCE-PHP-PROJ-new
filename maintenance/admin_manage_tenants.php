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

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $linkId = (int)($_POST['link_id'] ?? 0);
    
    // Verify ownership indirectly by checking if this link exists for this admin
    // For simplicity/performance in this MVP, we proceed if ID is valid and assume non-malicious admin for now
    // But ideally we should verify the link ID belongs to an org owned by admin.
    // The previous helper `getLandlordTenantRequests` only returns owned links, so if we trust the UI...
    // But for security, let's verify.
    // However, for speed in this interaction, I'll rely on the query restriction in the helper check or just direct ID operation if acceptable.
    // Let's do a quick verification query.
    
    $verifyQuery = "
        SELECT uu.id 
        FROM user_units uu
        JOIN organizations o ON uu.organization_id = o.id
        WHERE uu.id = $linkId AND o.admin_id = {$user['id']}
    ";
    $verifyResult = mysqli_query($conn, $verifyQuery);
    
    if (mysqli_num_rows($verifyResult) > 0) {
        if (isset($_POST['approve_tenant'])) {
            $updateQuery = "UPDATE user_units SET status = 1 WHERE id = $linkId";
            if (mysqli_query($conn, $updateQuery)) {
                $successMessage = "Tenant approved successfully.";
            } else {
                $errorMessage = "Error approving tenant.";
            }
        } elseif (isset($_POST['remove_tenant'])) {
            $deleteQuery = "DELETE FROM user_units WHERE id = $linkId";
            if (mysqli_query($conn, $deleteQuery)) {
                $successMessage = "Tenant removed successfully.";
            } else {
                $errorMessage = "Error removing tenant.";
            }
        }
    } else {
        $errorMessage = "Invalid request or permission denied.";
    }
}

// Fetch requests
$requests = getLandlordTenantRequests($user['id']);

// Filters? Not strictly requested but helpful. User said "make a page where the join requests are sent".
// I'll stick to a simple list for now as requested.

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
    <title>Manage Tenants - Admin Dashboard</title>
    <link rel="stylesheet" href="all_requests.css"> <!-- Reusing table styles -->
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .dashboard-content { padding-top: 0; }
        
        /* Table Styles Override/Specifics */
        .tenant-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .tenant-info h3 {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .tenant-details {
            display: flex;
            gap: 16px;
            font-size: 14px;
            color: #64748b;
            flex-wrap: wrap;
        }
        
        .tenant-detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Actions */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-approve {
            background: #22c55e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-approve:hover { background: #16a34a; }
        
        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-remove:hover { background: #dc2626; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
        .status-active { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        
        .sidebar-nav .nav-item.active { background: #f5f3ff; color: #7c3aed; }
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
            <a href="admin_manage_tenants.php" class="nav-item active">
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
        <div class="top-bar">
            <div class="page-title">
                <h1 style="margin-bottom: 3px;">Manage Tenants</h1>
                <p style="margin-bottom: 10px;">Approve join requests and manage unit access</p>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if (isset($successMessage)): ?>
                <div class="success-alert"><img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            <?php if (isset($errorMessage)): ?>
                <div class="error-alert"><img src="images/times-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <h2>No Tenants or Requests</h2>
                    <p>No one has requested to join your units yet.</p>
                </div>
            <?php else: ?>
                <div class="tenants-list">
                    <?php foreach ($requests as $req): ?>
                        <div class="tenant-card">
                            <div class="tenant-info">
                                <h3><?php echo htmlspecialchars($req['tenant_name']); ?></h3>
                                <div class="tenant-details">
                                    <span class="tenant-detail-item"><img src="images/mail-box-mailbox-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($req['tenant_email']); ?></span>
                                    <span class="tenant-detail-item"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($req['org_name']); ?></span>
                                    <span class="tenant-detail-item"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"> Unit <?php echo htmlspecialchars($req['unit_number']); ?></span>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <?php if ($req['status'] == 0): ?>
                                    <span class="status-badge status-pending">Pending Approval</span>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="link_id" value="<?php echo $req['link_id']; ?>">
                                        <button type="submit" name="approve_tenant" class="btn-approve" title="Approve">
                                            <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="20" height="20"> Accept
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="status-badge status-active">Active Tenant</span>
                                <?php endif; ?>
                                
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to remove this tenant?');">
                                    <input type="hidden" name="link_id" value="<?php echo $req['link_id']; ?>">
                                    <button type="submit" name="remove_tenant" class="btn-remove" title="Remove/Delete">
                                        <img src="images/times-circle-svgrepo-com.svg" alt="Logo" width="20" height="20">
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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