<?php
session_start();
require_once 'helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user = getUserByEmail($_SESSION['user_email']);
if (!$user || $user['role'] !== 'tenant') {
    header('Location: login.php');
    exit;
}

$unitId = $_GET['unit_id'] ?? null;
$orgId = $_GET['org_id'] ?? null;

if (!$unitId || !$orgId) {
    header('Location: tenant_main.php');
    exit;
}

// Get organization details
$organization = getOrganizationById($orgId);
if (!$organization) {
    header('Location: tenant_main.php');
    exit;
}

// Get unit details
$unit = getUnitById($unitId);
if (!$unit) {
    header('Location: organization_units.php?org_id=' . $orgId);
    exit;
}

// Verify user has access to this unit
if (!isUserInUnit($user['id'], $unitId)) {
    header('Location: tenant_main.php?error=no_access');
    exit;
}

// Get all complaints for this unit by this user
$myComplaints = getComplaintsByUnit($unitId, $user['id']);

// Sort by submitted date (newest first)
usort($myComplaints, function($a, $b) {
    return strtotime($b['submitted_at']) - strtotime($a['submitted_at']);
});

$successMessage = '';
$errorMessage = '';

// Handle new complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $category = $_POST['category'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    
    if (empty($category) || empty($title) || empty($description)) {
        $errorMessage = 'Please fill in all required fields';
    } else {
        global $conn;
        
        $orgIdInt = (int)$orgId;
        $userId = (int)$user['id'];
        $unitIdInt = (int)$unitId;
        $category = mysqli_real_escape_string($conn, $category);
        $title = mysqli_real_escape_string($conn, $title);
        $description = mysqli_real_escape_string($conn, $description);
        
        $query = "INSERT INTO complaints (organization_id, user_id, unit_id, category, title, description, status) VALUES ($orgIdInt, $userId, $unitIdInt, '$category', '$title', '$description', 'pending')";
        
        if (mysqli_query($conn, $query)) {
            $successMessage = 'Maintenance request submitted successfully!';
            
            // Refresh complaints
            $myComplaints = getComplaintsByUnit($unitId, $user['id']);
        } else {
            $errorMessage = 'Error submitting request: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit <?php echo htmlspecialchars($unit['name']); ?> - Maintenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="unit_detail.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="header-container">
            <a href="organization_units.php?org_id=<?php echo $orgId; ?>" class="back-btn">
                <span>←</span> Back to Units
            </a>
            <div class="unit-header-info">
                <div class="header-details">
                    <h1><?php echo htmlspecialchars($organization['name']); ?></h1>
                    <p class="org-address"><img src="images/map-marker-2-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo htmlspecialchars($organization['address']); ?></p>
                    <div class="header-meta">
                        <span class="my-unit"><img src="images/home-4-svgrepo-com.svg" alt="Logo" width="30" height="30"> My Unit: <strong><?php echo htmlspecialchars($unit['name']); ?></strong></span>
                        <span><img src="images/building-svgrepo-com.svg" alt="Logo" width="30" height="30"> Property Unit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
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

        <div class="content-grid">
            <!-- Submit Request Form -->
            <div class="form-section">
                <h2><img src="images/wrench-svgrepo-com.svg" alt="Logo" width="30" height="30"> Submit Maintenance Request</h2>
                <form method="POST" class="request-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select name="category" id="category" required>
                                <option value="">Select category...</option>
                                <option value="Plumbing">Plumbing</option>
                                <option value="Electrical">Electrical</option>
                                <option value="HVAC">HVAC</option>
                                <option value="Appliances">Appliances</option>
                                <option value="Structural">Structural</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority *</label>
                            <select name="priority" id="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Issue Title *</label>
                        <input type="text" name="title" id="title" placeholder="Brief description" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Detailed Description *</label>
                        <textarea name="description" id="description" rows="4" placeholder="Provide detailed information..." required></textarea>
                    </div>
                    
                    <button type="submit" name="submit_complaint" class="btn-submit">Submit Request</button>
                </form>
            </div>

            <!-- My Requests -->
            <div class="requests-section">
                <h2><img src="images/clipboard-list-svgrepo-com.svg" alt="Logo" width="30" height="30"> My Requests (<?php echo count($myComplaints); ?>)</h2>
                
                <?php if (empty($myComplaints)): ?>
                    <div class="empty-state">
                        <p><img src="images/inbox-alt-svgrepo-com.svg" alt="Logo" width="30" height="30"> No requests submitted yet</p>
                    </div>
                <?php else: ?>
                    <div class="requests-list">
                        <?php foreach ($myComplaints as $complaint): ?>
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
                                <p><?php echo htmlspecialchars($complaint['description']); ?></p>
                                <div class="request-meta">
                                    <span><img src="images/time-svgrepo-com.svg" alt="Logo" width="30" height="30"> <?php echo formatDateTime($complaint['submitted_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
