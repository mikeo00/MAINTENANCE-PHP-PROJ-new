<?php
session_start();
require_once 'helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $new_org_name = $_POST['new_org_name'] ?? '';
    $new_org_name = $_POST['new_org_name'] ?? '';
    
    // Validation
    if (empty($role) || empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields';
    } elseif ($role === 'landlord' && empty($new_org_name)) {
        $error = 'Landlords must provide an organization name';
    } elseif (!preg_match("#^[a-zA-Z\s'-]+$#", $name)) {
        $error = 'Name can only contain letters, spaces, hyphens, and apostrophes';
    } elseif ($role === 'landlord' && !preg_match("#^[a-zA-Z0-9\s.,'&-]+$#", $new_org_name)) {
        $error = 'Organization name can only contain letters, numbers, spaces, and basic punctuation';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (getUserByEmail($email)) {
        $error = 'Email address is already registered';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!preg_match("#^(?=.*[a-zA-Z])(?=.*[0-9])#", $password)) {
        $error = 'Password must contain at least one letter and one number';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Database connection is available via helpers.php -> db_connect.php
        global $conn;
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert User
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $name = mysqli_real_escape_string($conn, $name);
            $email = mysqli_real_escape_string($conn, $email);
            $role = mysqli_real_escape_string($conn, $role);
            
            $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";
            
            if (!mysqli_query($conn, $query)) {
                throw new Exception("Error creating user account: " . mysqli_error($conn));
            }
            
            $user_id = mysqli_insert_id($conn);
            
            // Handle Landlord Organization Creation
            if ($role === 'landlord') {
                $new_org_name = mysqli_real_escape_string($conn, $new_org_name);
                $query = "INSERT INTO organizations (name, admin_id) VALUES ('$new_org_name', $user_id)";
                if (!mysqli_query($conn, $query)) {
                    throw new Exception("Error creating organization: " . mysqli_error($conn));
                }
            }
            // Handle Tenant - No extra setup at signup
            
            // Commit transaction
            mysqli_commit($conn);
            
            $success = 'Account created successfully! You can now <a href="login.php" style="color: #059669; font-weight: 600;">login</a>.';
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Apartment Maintenance System</title>
    <meta name="description" content="Create an account to submit or manage apartment maintenance requests">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="images/maintenancehub-removebg-preview.png" alt="Logo" width="50" height="50">
            <h1> Join Our System</h1>
            <p>Register as a landlord or tenant to get started</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="role">I am a</label>
                <select 
                    id="role" 
                    name="role" 
                    required
                    style="width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif; background: white; cursor: pointer;"
                    onchange="toggleUnitField()"
                >
                    <option value="">Select your role...</option>
                    <option value="landlord" <?php echo (($_POST['role'] ?? '') === 'landlord') ? 'selected' : ''; ?>>Landlord / Property Manager</option>
                    <option value="tenant" <?php echo (($_POST['role'] ?? '') === 'tenant') ? 'selected' : ''; ?>>Tenant / Resident</option>
                </select>
            </div>

            <div class="form-group" id="new-org-group" style="display: none;">
                <label for="new_org_name">Organization Name</label>
                <input 
                    type="text" 
                    id="new_org_name" 
                    name="new_org_name" 
                    placeholder="Enter your organization name"
                    value="<?php echo htmlspecialchars($_POST['new_org_name'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    placeholder="John Doe"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="your.email@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Create a secure password (min. 6 characters)"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Re-enter your password"
                    required
                >
            </div>
            
            <button type="submit" class="submit-btn">Create Account</button>
        </form>
        
        <div class="form-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
    
    <script>
        function toggleUnitField() {
            const role = document.getElementById('role').value;
            const newOrgGroup = document.getElementById('new-org-group');
            const newOrgInput = document.getElementById('new_org_name');
            
            if (role === 'tenant') {
                newOrgGroup.style.display = 'none';
                newOrgInput.required = false;
            } else if (role === 'landlord') {
                newOrgGroup.style.display = 'block';
                newOrgInput.required = true;
            } else {
                newOrgGroup.style.display = 'none';
                newOrgInput.required = false;
            }
        }
        
        // Run on load
        toggleUnitField();
    </script>
</body>
</html>
