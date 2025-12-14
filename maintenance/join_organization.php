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

$successMessage = '';
$errorMessage = '';

// Get all organizations
global $conn;
$result = mysqli_query($conn, "SELECT * FROM organizations ORDER BY name ASC");
$allOrganizations = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get organizations user is already in
$userOrgs = getUserOrganizations($user['id']);
$userOrgIds = array_column($userOrgs, 'id');

// Filter to show only organizations user hasn't joined
$availableOrganizations = array_filter($allOrganizations, function($org) use ($userOrgIds) {
    return !in_array($org['id'], $userOrgIds);
});

// Get available units for selected organization (via AJAX or initial load)
$selectedOrgId = $_POST['organization_id'] ?? $_GET['org_id'] ?? null;
$availableUnits = [];
if ($selectedOrgId) {
    $selectedOrgId = (int)$selectedOrgId;
    // Get all units for this organization
    $query = "SELECT * FROM units WHERE organization_id = $selectedOrgId ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    $allUnits = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Get units user has already joined
    $userId = (int)$user['id'];
    $query = "SELECT unit_id FROM user_units WHERE user_id = $userId AND organization_id = $selectedOrgId";
    $result = mysqli_query($conn, $query);
    $userUnitIds = array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'unit_id');
    
    // Filter to show only units user hasn't joined
    $availableUnits = array_filter($allUnits, function($unit) use ($userUnitIds) {
        return !in_array($unit['id'], $userUnitIds);
    });
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_organization'])) {
    $orgId = $_POST['organization_id'] ?? '';
    $unitId = $_POST['unit_id'] ?? '';
    
    if (empty($orgId)) {
        $errorMessage = 'Please select an organization';
    } elseif (empty($unitId)) {
        $errorMessage = 'Please select a unit';
    } else {
        // Check if user already in this organization with this unit
        if (in_array($orgId, $userOrgIds)) {
            $errorMessage = 'You are already a member of this organization';
        } else {
            $orgId = (int)$orgId;
            $unitId = (int)$unitId;
            $userId = (int)$user['id'];
            
            // Verify unit belongs to organization
            $query = "SELECT id FROM units WHERE id = $unitId AND organization_id = $orgId";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) === 0) {
                $errorMessage = 'Invalid unit selection';
            } else {
                // Link user to unit with pending status (0)
                $query = "INSERT INTO user_units (user_id, organization_id, unit_id, status) VALUES ($userId, $orgId, $unitId, 0)";
                
                if (mysqli_query($conn, $query)) {
                    $_SESSION['selected_org_id'] = $orgId;
                    header('Location: tenant_main.php?success=pending');
                    exit;
                } else {
                    $errorMessage = "Error joining organization: " . mysqli_error($conn);
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
    <title>Join Organization - MaintenanceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="join_org.css">
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <a href="tenant_main.php" class="back-btn">
                <span>←</span> Back
            </a>
            <h1>Join New Organization</h1>
            <p>Select an organization and a unit to join</p>
        </div>

        <div class="form-container">
            <?php if ($errorMessage): ?>
                <div class="error-alert">
                    <img src="images/times-circle-svgrepo-com.svg" alt="Logo" width="50" height="50"> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($availableOrganizations)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><img src="images/check-circle-svgrepo-com.svg" alt="Logo" width="50" height="50"></div>
                    <h2>All Set!</h2>
                    <p>You're already a member of all available organizations.</p>
                    <a href="tenant_main.php" class="btn-primary">Back to My Organizations</a>
                </div>
            <?php else: ?>
                <form method="POST" id="joinForm">
                    <div class="form-group">
                        <label for="organization_search">Select Organization *</label>
                        <div class="searchable-dropdown">
                            <input type="text" id="organization_search" class="search-input" placeholder="Search organization..." autocomplete="off">
                            <input type="hidden" name="organization_id" id="organization_id" required value="<?php echo htmlspecialchars($selectedOrgId); ?>">
                            <div id="org_dropdown_list" class="dropdown-list">
                                <?php foreach ($availableOrganizations as $org): ?>
                                    <div class="dropdown-item" 
                                         data-id="<?php echo $org['id']; ?>" 
                                         data-name="<?php echo htmlspecialchars($org['name']); ?>"
                                         data-address="<?php echo htmlspecialchars($org['address'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($org['name']); ?>
                                        <small><?php echo htmlspecialchars($org['address'] ?? ''); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="org-info" id="orgInfo"></div>
                    </div>

                    <div class="form-group" id="unitGroup" style="<?php echo empty($availableUnits) ? 'display:none;' : ''; ?>">
                        <label for="unit_id">Select Unit *</label>
                        <select name="unit_id" id="unit_id" required>
                            <option value="">Choose a unit...</option>
                            <?php foreach ($availableUnits as $unit): ?>
                                <option value="<?php echo $unit['id']; ?>">
                                    Unit <?php echo htmlspecialchars($unit['name']); ?>
                                    <?php if (!empty($unit['description'])): ?>
                                        - <?php echo htmlspecialchars($unit['description']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Select from available units in this organization</small>
                    </div>

                    <div id="noUnitsMessage" style="display:none;" class="info-message">
                        <p><img src="images/exclamation-triangle-svgrepo-com.svg" alt="Logo" width="50" height="50"> No available units in this organization. All units are already assigned to you or no units have been created yet.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="join_organization" class="btn-submit" id="submitBtn" 
                                <?php echo empty($availableUnits) && $selectedOrgId ? 'disabled' : ''; ?>>
                            Join Organization
                        </button>
                        <a href="tenant_main.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('organization_search');
            const hiddenInput = document.getElementById('organization_id');
            const dropdownList = document.getElementById('org_dropdown_list');
            const items = document.querySelectorAll('.dropdown-item');
            const orgInfo = document.getElementById('orgInfo');

            // Initialize if value exists
            const initialId = hiddenInput.value;
            if (initialId) {
                const selectedItem = document.querySelector(`.dropdown-item[data-id="${initialId}"]`);
                if (selectedItem) {
                    searchInput.value = selectedItem.dataset.name;
                    showOrgInfo({
                        name: selectedItem.dataset.name,
                        address: selectedItem.dataset.address
                    });
                }
            }

            // Toggle dropdown
            searchInput.addEventListener('focus', () => {
                dropdownList.classList.add('show');
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.searchable-dropdown')) {
                    dropdownList.classList.remove('show');
                }
            });

            // Search filter
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                dropdownList.classList.add('show');
                let hasResults = false;

                items.forEach(item => {
                    const name = item.dataset.name.toLowerCase();
                    if (name.includes(term)) {
                        item.style.display = 'block';
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Select item
            items.forEach(item => {
                item.addEventListener('click', () => {
                    const id = item.dataset.id;
                    const name = item.dataset.name;
                    const address = item.dataset.address;

                    hiddenInput.value = id;
                    searchInput.value = name;
                    dropdownList.classList.remove('show');

                    showOrgInfo({ name, address });
                    
                    // Trigger unit load (reload page)
                    if (id) {
                        window.location.href = 'join_organization.php?org_id=' + id;
                    }
                });
            });

            function showOrgInfo(data) {
                orgInfo.style.display = 'block';
                orgInfo.innerHTML = `
                    <div class="info-card">
                        <div>
                            <strong>Organization:</strong><br>
                            ${data.name}
                        </div>
                        <div>
                            <strong>Address:</strong><br>
                            ${data.address || 'N/A'}
                        </div>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>
