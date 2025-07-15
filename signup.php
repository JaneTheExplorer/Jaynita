<?php
require_once '../includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_POST) {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if ($first_name && $last_name && $email && $password && $confirm_password) {
        if ($password === $confirm_password) {
            $result = $auth->registerUser($first_name, $last_name, $email, $password, $phone);
            if ($result['success']) {
                $success = $result['message'] . ' You can now login.';
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Passwords do not match';
        }
    } else {
        $error = 'Please fill in all required fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - FlyFlex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.html" class="logo">FlyFlex</a>
                <nav>
                    <ul class="nav-links">
                        <li><a href="../index.html">Home</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="../admin/login.php">Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #333;">Join FlyFlex</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Create Account</button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem;">
                Already have an account? <a href="login.php" style="color: #667eea;">Login here</a>
            </p>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
