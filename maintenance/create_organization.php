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

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_org'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $adminId = (int)$user['id'];
    
    if (empty($name) || empty($address)) {
        $errorMessage = 'Name and Address are required';
    } else {
        $query = "INSERT INTO organizations (name, address, description, admin_id) VALUES ('$name', '$address', '$description', $adminId)";
        
        if (mysqli_query($conn, $query)) {
            header('Location: admin_main.php?success=created');
            exit;
        } else {
            $errorMessage = "Error creating organization: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization - MaintenanceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="join_org.css"> <!-- Reusing existing form styles -->
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <a href="admin_main.php" class="back-btn">
                <span>←</span> Back
            </a>
            <h1>Create New Organization</h1>
            <p>Add a new property to manage</p>
        </div>

        <div class="form-container">
            <?php if ($errorMessage): ?>
                <div class="error-alert">
                    <img src="images/times-circle-svgrepo-com.svg" alt="Logo" width="50" height="50"> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Organization Name *</label>
                    <input type="text" name="name" id="name" required placeholder="e.g. Sunset Apartments">
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" name="address" id="address" required placeholder="e.g. 123 Main St, City">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3" placeholder="Optional description of the property"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="create_org" class="btn-submit">
                        Create Organization
                    </button>
                    <a href="admin_main.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
