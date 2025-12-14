<?php
session_start();
require_once 'helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $user = getUserByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_email'] = $email;

            // Redirect based on role
            if ($user['role'] === 'landlord') {
                header('Location: admin_main.php');
            } else {
                header('Location: tenant_main.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Apartment Maintenance System</title>
    <meta name="description" content="Login to manage maintenance requests for your apartment">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth.css">
</head>

<body style="overflow: hidden;">
    <div class="auth-container">
        <div class="auth-header">
            <img src="images/maintenancehub-removebg-preview.png" alt="Logo" width="50" height="50">
            <h1> Maintenance Portal</h1>
            <p>Login as landlord or tenant to manage requests</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required>
            </div>

            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <div class="form-footer">
            Don't have an account? <a href="signup.php">Sign up</a>
        </div>
    </div>
</body>

</html>