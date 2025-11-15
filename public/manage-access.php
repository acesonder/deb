<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

// Only admin and patient can manage access
if ($role !== 'admin' && $role !== 'patient') {
    header('Location: dashboard.php');
    exit();
}

$success = '';
$error = '';

// Handle create viewer account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_viewer') {
        $viewer_username = $_POST['viewer_username'] ?? '';
        $viewer_name = $_POST['viewer_name'] ?? '';
        $viewer_email = $_POST['viewer_email'] ?? '';
        $viewer_password = $_POST['viewer_password'] ?? '';
        
        if (empty($viewer_username) || empty($viewer_name) || empty($viewer_password)) {
            $error = 'Username, name, and password are required.';
        } elseif (strlen($viewer_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $new_user_id = createUser($viewer_username, $viewer_password, $viewer_name, 'viewer', $viewer_email);
            
            if ($new_user_id) {
                // For patients, automatically grant access to themselves
                if ($role === 'patient') {
                    $conn = getDBConnection();
                    $stmt = $conn->prepare("INSERT INTO access_permissions (patient_id, viewer_id, granted_by) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $user_id, $new_user_id, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    $conn->close();
                }
                
                $success = "Viewer account created successfully! Username: $viewer_username";
            } else {
                $error = 'Failed to create viewer account. Username may already exist.';
            }
        }
    } elseif ($_POST['action'] === 'revoke_access') {
        $permission_id = $_POST['permission_id'] ?? 0;
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE access_permissions SET is_active = FALSE WHERE permission_id = ? AND patient_id = ?");
        $stmt->bind_param("ii", $permission_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Access revoked successfully.';
        }
        $stmt->close();
        $conn->close();
    } elseif ($_POST['action'] === 'grant_access') {
        $viewer_id = $_POST['viewer_id'] ?? 0;
        $patient_id = $role === 'admin' ? ($_POST['patient_id'] ?? $user_id) : $user_id;
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO access_permissions (patient_id, viewer_id, granted_by, is_active) VALUES (?, ?, ?, TRUE) ON DUPLICATE KEY UPDATE is_active = TRUE");
        $stmt->bind_param("iii", $patient_id, $viewer_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Access granted successfully.';
        }
        $stmt->close();
        $conn->close();
    }
}

// Get existing viewer accounts
$conn = getDBConnection();

// Get viewers with access
if ($role === 'admin') {
    $stmt = $conn->prepare("
        SELECT ap.permission_id, ap.patient_id, ap.viewer_id, ap.is_active, ap.granted_at,
               u1.full_name as viewer_name, u1.email as viewer_email,
               u2.full_name as patient_name
        FROM access_permissions ap
        JOIN users u1 ON ap.viewer_id = u1.user_id
        JOIN users u2 ON ap.patient_id = u2.user_id
        ORDER BY ap.granted_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT ap.permission_id, ap.viewer_id, ap.is_active, ap.granted_at,
               u.full_name as viewer_name, u.email as viewer_email
        FROM access_permissions ap
        JOIN users u ON ap.viewer_id = u.user_id
        WHERE ap.patient_id = ?
        ORDER BY ap.granted_at DESC
    ");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$permissions = [];
while ($row = $result->fetch_assoc()) {
    $permissions[] = $row;
}
$stmt->close();

// Get all viewers (for admin)
$all_viewers = [];
if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT user_id, username, full_name, email FROM users WHERE role = 'viewer'");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_viewers[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Access - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .access-section {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .access-section h2 {
            margin-bottom: var(--spacing-md);
            color: var(--primary-color);
        }
        
        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-md);
        }
        
        .permissions-table th,
        .permissions-table td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .permissions-table th {
            background: var(--bg-primary);
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #e6f9f5;
            color: #00765a;
        }
        
        .status-inactive {
            background: #ffe6e6;
            color: #c41e3a;
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
                <h1>👥 Manage Access</h1>
                <p class="dashboard-subtitle">Control who can view health data</p>
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
            
            <!-- Create Viewer Account -->
            <div class="access-section">
                <h2>Create New Viewer Account</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-md);">
                    Create accounts for family members or doctors to view health data.
                </p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_viewer">
                    
                    <div class="form-group">
                        <label for="viewer_username">Username *</label>
                        <input type="text" id="viewer_username" name="viewer_username" required 
                               placeholder="e.g., dr_smith">
                    </div>
                    
                    <div class="form-group">
                        <label for="viewer_name">Full Name *</label>
                        <input type="text" id="viewer_name" name="viewer_name" required 
                               placeholder="e.g., Dr. John Smith">
                    </div>
                    
                    <div class="form-group">
                        <label for="viewer_email">Email</label>
                        <input type="email" id="viewer_email" name="viewer_email" 
                               placeholder="e.g., doctor@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="viewer_password">Password *</label>
                        <input type="password" id="viewer_password" name="viewer_password" required 
                               minlength="6" placeholder="At least 6 characters">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Viewer Account</button>
                </form>
            </div>
            
            <!-- Active Permissions -->
            <div class="access-section">
                <h2>Current Access Permissions</h2>
                
                <?php if (count($permissions) > 0): ?>
                    <table class="permissions-table">
                        <thead>
                            <tr>
                                <th>Viewer Name</th>
                                <?php if ($role === 'admin'): ?>
                                    <th>Patient</th>
                                <?php endif; ?>
                                <th>Email</th>
                                <th>Granted On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($permissions as $perm): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($perm['viewer_name']); ?></td>
                                    <?php if ($role === 'admin'): ?>
                                        <td><?php echo htmlspecialchars($perm['patient_name']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($perm['viewer_email'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($perm['granted_at'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $perm['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $perm['is_active'] ? 'Active' : 'Revoked'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($perm['is_active']): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="revoke_access">
                                                <input type="hidden" name="permission_id" value="<?php echo $perm['permission_id']; ?>">
                                                <button type="submit" class="btn btn-danger" 
                                                        onclick="return confirm('Are you sure you want to revoke access?')">
                                                    Revoke
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="grant_access">
                                                <input type="hidden" name="viewer_id" value="<?php echo $perm['viewer_id']; ?>">
                                                <button type="submit" class="btn btn-secondary">Restore</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--spacing-lg);">
                        No viewer accounts have been granted access yet.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Info Box -->
            <div class="access-section" style="background: var(--bg-primary);">
                <h3>ℹ️ About Viewer Access</h3>
                <ul style="color: var(--text-secondary); line-height: 1.8;">
                    <li><strong>Viewer accounts</strong> can only view health data - they cannot make changes</li>
                    <li>Viewers can see health logs, analytics, and alerts</li>
                    <li>You can revoke access at any time</li>
                    <li>Each viewer gets their own username and password</li>
                    <li>Share login credentials securely with authorized viewers only</li>
                </ul>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
