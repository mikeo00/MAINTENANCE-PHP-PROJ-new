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

// Get all complaints for landlord's organizations
$allComplaints = getAllLandlordComplaints($user['id']);

// Get Filter Options from data
$categories = array_values(array_unique(array_column($allComplaints, 'category')));
sort($categories);

// Get Organizations for filter
$orgs = getLandlordOrganizations($user['id']);

// Statistics
$totalRequests = count($allComplaints);
$pendingCount = count(array_filter($allComplaints, fn($c) => $c['status'] === 'pending'));
$inProgressCount = count(array_filter($allComplaints, fn($c) => $c['status'] === 'in_progress'));
$resolvedCount = count(array_filter($allComplaints, fn($c) => $c['status'] === 'resolved'));

// Filter Logic
$filterStatus = $_GET['status'] ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';
$filterOrg = $_GET['org_id'] ?? 'all';

$filteredComplaints = $allComplaints;

if ($filterStatus !== 'all') {
    $filteredComplaints = array_filter($filteredComplaints, fn($c) => $c['status'] === $filterStatus);
}

if ($filterCategory !== 'all') {
    $filteredComplaints = array_filter($filteredComplaints, fn($c) => $c['category'] === $filterCategory);
}

if ($filterOrg !== 'all') {
    $filteredComplaints = array_filter($filteredComplaints, fn($c) => $c['organization_id'] == $filterOrg);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $complaintId = (int)$_POST['complaint_id'];
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Security check: ensure complaint belongs to an org owned by this landlord
    // We can just check if the complaint ID is in our $allComplaints list to verify ownership
    $isOwned = false;
    foreach ($allComplaints as $c) {
        if ($c['id'] == $complaintId) {
            $isOwned = true;
            break;
        }
    }
    
    if ($isOwned) {
        $query = "UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId";
        if (mysqli_query($conn, $query)) {
            // retain filters
            $redirectUrl = "admin_all_requests.php?success=updated";
            if ($filterStatus !== 'all') $redirectUrl .= "&status=$filterStatus";
            if ($filterCategory !== 'all') $redirectUrl .= "&category=$filterCategory";
            if ($filterOrg !== 'all') $redirectUrl .= "&org_id=$filterOrg";
            
            header("Location: $redirectUrl");
            exit;
        }
    }
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
    <title>All Requests - Admin Dashboard</title>
    <link rel="stylesheet" href="all_requests.css">
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        /* Shared styles fix */
        .page-title h1 { color: #1e293b; }
        .dashboard-content { padding-top: 0; }
        .complaints-section { margin-top: 20px; }

        /* Sidebar active state */
        .sidebar-nav .nav-item.active {
            background: #f5f3ff;
            color: #7c3aed;
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
            <a href="admin_all_requests.php" class="nav-item active">
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
                <h1>All Maintenance Requests</h1>
                <p>Track requests across all your properties</p>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-alert">
                    <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> Status updated successfully!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><img src="images/chart-waterfall-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                    <div class="stat-info">
                        <h3><?php echo $totalRequests; ?></h3>
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

            <!-- Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <label>Status:</label>
                    <select onchange="applyFilter()" id="statusFilter">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $filterStatus === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Category:</label>
                    <select onchange="applyFilter()" id="categoryFilter">
                        <option value="all" <?php echo $filterCategory === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Organization:</label>
                    <select onchange="applyFilter()" id="orgFilter">
                        <option value="all" <?php echo $filterOrg === 'all' ? 'selected' : ''; ?>>All Organizations</option>
                        <?php foreach ($orgs as $org): ?>
                            <option value="<?php echo $org['id']; ?>" <?php echo $filterOrg == $org['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="results-count">
                    Showing <?php echo count($filteredComplaints); ?> of <?php echo $totalRequests; ?> requests
                </div>
            </div>

            <!-- Complaints Table -->
            <div class="complaints-section">
                
                <?php if (empty($filteredComplaints)): ?>
                    <div class="empty-state">
                        <p><img src="images/mail-box-mailbox-svgrepo-com.svg" alt="Logo" width="30" height="30"> No requests found.</p>
                    </div>
                <?php else: ?>
                    <div class="complaints-table">
                        <?php foreach ($filteredComplaints as $complaint): ?>
                            <div class="complaint-card">
                                <div class="complaint-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($complaint['title']); ?></h3>
                                        <div class="complaint-meta">
                                            <span class="meta-item"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($complaint['org_name']); ?></span>
                                            <span class="meta-item"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"> Unit <?php echo htmlspecialchars($complaint['unit_number']); ?></span>
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

        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            const category = document.getElementById('categoryFilter').value;
            const org = document.getElementById('orgFilter').value;
            
            let url = 'admin_all_requests.php?';
            if (status !== 'all') url += 'status=' + status + '&';
            if (category !== 'all') url += 'category=' + category + '&';
            if (org !== 'all') url += 'org_id=' + org;
            
            window.location.href = url;
        }
    </script>
</body>
</html>