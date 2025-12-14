<?php
session_start();
require_once 'helpers.php';

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user = getUserByEmail($_SESSION['user_email']);
if (!$user || $user['role'] !== 'tenant') {
    header('Location: login.php');
    exit;
}

// Get all complaints by this user
$allComplaints = getComplaintsByUser($user['id']);

// Get unique categories for filter
$categories = [];
foreach ($allComplaints as $c) {
    if (!empty($c['category']) && !in_array($c['category'], $categories)) {
        $categories[] = $c['category'];
    }
}
sort($categories);

// Get statistics
$totalRequests = count($allComplaints);
$pendingCount = count(array_filter($allComplaints, fn($c) => $c['status'] === 'pending'));
$inProgressCount = count(array_filter($allComplaints, fn($c) => $c['status'] === 'in_progress'));
$resolvedCount = count(array_filter($allComplaints, fn($c) => $c['status'] === 'resolved'));

// Filter
$filterStatus = $_GET['status'] ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';

$filteredComplaints = $allComplaints;

if ($filterStatus !== 'all') {
    $filteredComplaints = array_filter($filteredComplaints, fn($c) => $c['status'] === $filterStatus);
}

if ($filterCategory !== 'all') {
    $filteredComplaints = array_filter($filteredComplaints, fn($c) => $c['category'] === $filterCategory);
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
    <title>All Requests - MaintenanceHub</title>
    <link rel="stylesheet" href="all_requests.css">
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu">
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
            <a href="tenant_main.php" class="nav-item">
                <span class="nav-icon"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>My Organizations</span>
            </a>
            <a href="all_requests.php" class="nav-item active">
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
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div>
                    <img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30">    
                    <h1> All Maintenance Requests</h1>
                    <p>View and track all your submitted maintenance requests</p>
                </div>
                <a href="tenant_main.php" class="btn-back">Back to Organizations</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-icon"><img src="images/chart-waterfall-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <div>
                    <h3><?php echo $totalRequests; ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon"><img src="images/hour-glass-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <div>
                    <h3><?php echo $pendingCount; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card progress">
                <div class="stat-icon"><img src="images/tools-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <div>
                    <h3><?php echo $inProgressCount; ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
            <div class="stat-card resolved">
                <div class="stat-icon"><img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <div>
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
            
            <div class="results-count">
                Showing <?php echo count($filteredComplaints); ?> of <?php echo $totalRequests; ?> requests
            </div>
        </div>

        <!-- Requests List -->
        <div class="requests-container">
            <?php if (empty($filteredComplaints)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><img src="images/mail-box-mailbox-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                    <h2>No Requests Found</h2>
                    <p>You haven't submitted any maintenance requests yet.</p>
                    <a href="tenant_main.php" class="btn-primary">Submit First Request</a>
                </div>
            <?php else: ?>
                <div class="requests-list">
                    <?php foreach ($filteredComplaints as $complaint): ?>
                        <?php 
                        $org = getOrganizationById($complaint['organization_id'] ?? 0);
                        // Get unit name if possible
                        $unitName = 'Unknown';
                        if (isset($complaint['unit_id'])) {
                            global $conn;
                            $unitId = (int)$complaint['unit_id'];
                            $query = "SELECT name FROM units WHERE id = $unitId";
                            $result = mysqli_query($conn, $query);
                            if ($u = mysqli_fetch_assoc($result)) {
                                $unitName = $u['name'];
                            }
                        }
                        ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="unit-badge">
                                    <img src="images/folder-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($complaint['category']); ?>
                                </div>
                                <div class="badges">
                                    <span class="badge <?php echo getStatusBadgeClass($complaint['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <h3><?php echo htmlspecialchars($complaint['title']); ?></h3>
                            
                            <p class="request-description"><?php echo htmlspecialchars($complaint['description']); ?></p>
                            
                            <div class="request-meta">
                                <span><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo $org ? htmlspecialchars($org['name']) : 'Unknown'; ?></span>
                                <span><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"> Unit <?php echo htmlspecialchars($unitName); ?></span>
                                <span><img src="images/time-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo formatDateTime($complaint['submitted_at']); ?></span>
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
            const hamburger = document.querySelector('.hamburger-menu');
            sidebar.classList.toggle('active');
            hamburger.classList.toggle('shifted');
        }

        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            const category = document.getElementById('categoryFilter').value;
            
            let url = 'all_requests.php?';
            if (status !== 'all') url += 'status=' + status + '&';
            if (category !== 'all') url += 'category=' + category;
            
            window.location.href = url;
        }
    </script>
</body>
</html>