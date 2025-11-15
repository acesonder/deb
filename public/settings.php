<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            // Verify current password
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password_hash'])) {
                if (changePassword($user_id, $new_password)) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}

// Get user preferences
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .settings-section {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .settings-section h2 {
            margin-bottom: var(--spacing-md);
            color: var(--primary-color);
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <aside class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Settings</h1>
                <p class="dashboard-subtitle">Manage your account and preferences</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Account Information -->
            <div class="settings-section">
                <h2>👤 Account Information</h2>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($full_name); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?php echo htmlspecialchars($role); ?>" disabled>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="settings-section">
                <h2>🔐 Change Password</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required 
                               minlength="6" placeholder="At least 6 characters">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
            
            <!-- Alert Preferences -->
            <div class="settings-section">
                <h2>🔔 Alert Preferences</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-md);">
                    Configure when you receive health alerts and notifications.
                </p>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" <?php echo $preferences && $preferences['notifications_enabled'] ? 'checked' : ''; ?>>
                        Enable notifications
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="bp_threshold_systolic">Blood Pressure Alert Threshold</label>
                    <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: var(--spacing-sm); align-items: center;">
                        <input type="number" id="bp_threshold_systolic" 
                               value="<?php echo $preferences ? $preferences['bp_alert_threshold_systolic'] : 140; ?>" 
                               min="100" max="200">
                        <span>/</span>
                        <input type="number" id="bp_threshold_diastolic" 
                               value="<?php echo $preferences ? $preferences['bp_alert_threshold_diastolic'] : 90; ?>" 
                               min="60" max="120">
                    </div>
                    <small style="color: var(--text-secondary);">Get alerted when BP exceeds these values</small>
                </div>
            </div>
            
            <!-- Appearance -->
            <div class="settings-section">
                <h2>🎨 Appearance</h2>
                <div class="form-group">
                    <label for="theme">Theme</label>
                    <select id="theme">
                        <option value="light" <?php echo $preferences && $preferences['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo $preferences && $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                    </select>
                    <small style="color: var(--text-secondary);">Dark theme coming soon!</small>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="settings-section" style="border: 2px solid var(--danger);">
                <h2 style="color: var(--danger);">⚠️ Danger Zone</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-md);">
                    These actions are irreversible. Please be careful.
                </p>
                <button class="btn btn-danger" onclick="alert('This feature is not yet implemented. Contact administrator to deactivate your account.')">
                    Deactivate Account
                </button>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
