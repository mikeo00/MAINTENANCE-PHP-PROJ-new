<?php
session_start();
require_once 'helpers.php';

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

$user = getUserByEmail($_SESSION['user_email']);
if (!$user || $user['role'] !== 'tenant') {
    header('Location: login.php');
    exit;
}

$orgId = $_GET['org_id'] ?? null;
if (!$orgId) {
    header('Location: tenant_main.php');
    exit;
}

// Get organization details
$organization = getOrganizationById($orgId);
if (!$organization) {
    header('Location: tenant_main.php');
    exit;
}

// Get user's units within this organization
$userUnits = getUserUnits($user['id'], $orgId);

// Check if user has any pending requests for this organization
$orgIdInt = (int)$orgId;
$userId = (int)$user['id'];
$pendingQuery = "SELECT id FROM user_units WHERE user_id = $userId AND organization_id = $orgIdInt AND status = 0";
$pendingResult = mysqli_query($conn, $pendingQuery);
$hasPending = mysqli_num_rows($pendingResult) > 0;


// Get all units in this organization
global $conn;
$orgIdInt = (int)$orgId;
$query = "SELECT * FROM units WHERE organization_id = $orgIdInt ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$allUnits = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get units user has already joined
$userUnitIds = array_column($userUnits, 'unit_id');

// Filter to show only units user hasn't joined
$availableUnits = array_filter($allUnits, function($unit) use ($userUnitIds) {
    return !in_array($unit['id'], $userUnitIds);
});

// Handle joining a new unit
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_unit'])) {
    $unitId = $_POST['unit_id'] ?? '';
    
    if (empty($unitId)) {
        $errorMessage = 'Please select a unit';
    } else {
        $unitId = (int)$unitId;
        $userId = (int)$user['id'];
        $orgIdInt = (int)$orgId;
        
        // Verify unit belongs to this organization
        $query = "SELECT id FROM units WHERE id = $unitId AND organization_id = $orgIdInt";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 0) {
            $errorMessage = 'Invalid unit selection';
        } else {
            // Check if user already has this unit
            $checkQuery = "SELECT id FROM user_units WHERE user_id = $userId AND unit_id = $unitId";
            $checkResult = mysqli_query($conn, $checkQuery);
            
            if (mysqli_num_rows($checkResult) > 0) {
                $errorMessage = 'You have already joined this unit';
            } else {
                // Add user to unit with pending status (0)
                $query = "INSERT INTO user_units (user_id, organization_id, unit_id, status) VALUES ($userId, $orgIdInt, $unitId, 0)";
                
                if (mysqli_query($conn, $query)) {
                    header("Location: organization_units.php?org_id=$orgId&success=pending");
                    exit;
                } else {
                    $errorMessage = "Error joining unit: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($organization['name']); ?> - Units</title>
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
            <div class="user-avatar"><img src="images/user-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="tenant_main.php" class="nav-item">
                <span class="nav-icon"><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>
                <span>My Organizations</span>
            </a>
            <a href="all_requests.php" class="nav-item">
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
                <a href="tenant_main.php" class="back-link">← Back to Organizations</a>
                <h1><?php echo htmlspecialchars($organization['name']); ?></h1>
                <p><img src="images/map-marker-2-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($organization['address']); ?></p>
            </div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'joined'): ?>
            <div class="success-alert">
                <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> Successfully joined unit! You can now submit maintenance requests.
            </div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] === 'pending'): ?>
            <div class="success-alert" style="background: #fff7ed; color: #9a3412; border-color: #ffedd5;">
                <img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> Request submitted! Your membership is pending approval from the landlord.
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-alert">
                <img src="images/times-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Join Unit Section -->
        <?php if (!empty($availableUnits)): ?>
            <div class="quick-add-section">
                <h3 style="margin-bottom: 16px; color: #1e293b; font-size: 18px;">
                    <span style="margin-right: 8px;"><img src="images/plus-large-svgrepo-com.svg" alt="Logo" width="30" height="30"></span>Join Additional Unit
                </h3>
                <form method="POST" class="quick-add-form">
                    <div class="form-inline-group">
                        <select name="unit_id" id="unit_id" required style="flex: 1; padding: 14px 18px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 15px; font-family: 'Outfit', sans-serif; background: #f8fafc; transition: all 0.2s ease;">
                            <option value="">Select a unit to join...</option>
                            <?php foreach ($availableUnits as $unit): ?>
                                <option value="<?php echo $unit['id']; ?>">
                                    Unit <?php echo htmlspecialchars($unit['name']); ?>
                                    <?php if (!empty($unit['description'])): ?>
                                        - <?php echo htmlspecialchars($unit['description']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="join_unit" class="btn-add">
                            Join Unit
                        </button>
                    </div>
                    <small style="color: #64748b; font-size: 13px; margin-top: 8px; display: block;">
                        Select from available units in this organization
                    </small>
                </form>
            </div>
        <?php elseif (!empty($userUnits)): ?>
            <div class="info-message" style="max-width: 1600px; margin: 0 auto 30px; background: #eff6ff; color: #1e40af; padding: 16px 24px; border-radius: 12px; border: 1px solid #dbeafe;">
                <img src="images/info-circle-svgrepo-com.svg" alt="Logo" width="30" height="30"> You have joined all available units in this organization.
            </div>
        <?php endif; ?>

        <?php if (empty($userUnits) && empty($availableUnits)): ?>
            <!-- No Units State -->
            <div class="empty-state">
                <div class="empty-icon"><img src="images/home-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <h2>No Units Available</h2>
                <p>This organization doesn't have any units created yet. Please contact the organization administrator to add units.</p>
                <a href="tenant_main.php" class="btn-primary">Back to Organizations</a>
            </div>
        <?php elseif ($hasPending && empty($userUnits)): ?>
            <!-- Pending State -->
            <div class="empty-state">
                <div class="empty-icon"><img src="images/hour-glass-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <h2>Waiting for Approval</h2>
                <p>Your request to join this organization is currently pending approval from the landlord. You will be able to access the units once approved.</p>
                <a href="tenant_main.php" class="btn-primary">Back to Organizations</a>
            </div>
        <?php elseif (empty($userUnits)): ?>
            <!-- No User Units but has available -->
            <div class="empty-state">
                <div class="empty-icon"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                <h2>No Units Joined Yet</h2>
                <p>You haven't joined any units in this organization yet. Select a unit above to get started.</p>
            </div>
        <?php else: ?>
            <!-- Units Grid -->
            <div class="units-grid">
                <?php foreach ($userUnits as $unit): ?>
                    <a href="unit_detail.php?unit_id=<?php echo $unit['unit_id']; ?>&org_id=<?php echo $orgId; ?>" class="unit-card">
                        <div class="unit-icon">
                            <div class="icon-badge"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"></div>
                        </div>
                        
                        <div class="unit-content">
                            <div class="unit-header">
                                <h2>Unit <?php echo htmlspecialchars($unit['unit_name']); ?></h2>
                            </div>
                            
                            <?php if (!empty($unit['unit_description'])): ?>
                                <p class="unit-description"><?php echo htmlspecialchars($unit['unit_description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="card-footer">
                                <span class="submit-request-btn">Submit Request →</span>
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

        // Add focus styling to select
        const selectElement = document.getElementById('unit_id');
        if (selectElement) {
            selectElement.addEventListener('focus', function() {
                this.style.borderColor = '#8b5cf6';
                this.style.background = 'white';
                this.style.boxShadow = '0 0 0 3px rgba(139, 92, 246, 0.1)';
            });
            
            selectElement.addEventListener('blur', function() {
                this.style.borderColor = '#e2e8f0';
                this.style.background = '#f8fafc';
                this.style.boxShadow = 'none';
            });
        }
    </script>
</body>
</html>
