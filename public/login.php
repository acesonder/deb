<?php
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deb's Health Tracker - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo-section">
                <h1 class="app-title">Deb's Health Tracker</h1>
                <p class="tagline">Your Personal Health Companion</p>
            </div>
            
            <form method="POST" action="" class="login-form">
                <h2>Welcome</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autocomplete="username"
                        aria-label="Username"
                        placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Access Code / Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        aria-label="Password"
                        placeholder="Enter your access code">
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    Sign In
                </button>
                
                <div class="login-info">
                    <p><strong>For Deb:</strong> Use username "deb" and your access code</p>
                    <p><strong>For Admin:</strong> Use admin credentials</p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
