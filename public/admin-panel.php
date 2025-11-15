<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'];

// Get system statistics
$conn = getDBConnection();

// Total users by role
$stmt = $conn->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    WHERE is_active = TRUE 
    GROUP BY role
");
$users_by_role = [];
while ($row = $stmt->fetch_assoc()) {
    $users_by_role[$row['role']] = $row['count'];
}

// Total health logs
$total_logs = $conn->query("SELECT COUNT(*) as count FROM health_logs")->fetch_assoc()['count'];

// Total stroke warnings
$total_warnings = $conn->query("SELECT COUNT(*) as count FROM stroke_warnings")->fetch_assoc()['count'];

// Recent users
$recent_users = [];
$stmt = $conn->query("
    SELECT user_id, username, full_name, role, created_at, last_login 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");
while ($row = $stmt->fetch_assoc()) {
    $recent_users[] = $row;
}

// Recent health logs across all users
$recent_logs = [];
$stmt = $conn->query("
    SELECT hl.*, u.full_name 
    FROM health_logs hl
    JOIN users u ON hl.user_id = u.user_id
    ORDER BY hl.log_date DESC 
    LIMIT 10
");
while ($row = $stmt->fetch_assoc()) {
    $recent_logs[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <aside class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1>🔧 Admin Panel</h1>
                <p class="dashboard-subtitle">System overview and management</p>
            </div>
            
            <!-- System Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <p class="stat-value">
                            <?php 
                            $total_users = array_sum($users_by_role);
                            echo $total_users;
                            ?>
                        </p>
                        <p class="stat-label">
                            <?php foreach ($users_by_role as $role => $count): ?>
                                <?php echo ucfirst($role) . ': ' . $count; ?><br>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3>Total Health Logs</h3>
                        <p class="stat-value"><?php echo $total_logs; ?></p>
                        <p class="stat-label">entries</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-content">
                        <h3>Stroke Warnings</h3>
                        <p class="stat-value"><?php echo $total_warnings; ?></p>
                        <p class="stat-label">recorded</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="recent-activity">
                <h2>Recent Users</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-primary);">
                            <th style="padding: var(--spacing-sm); text-align: left;">Username</th>
                            <th style="padding: var(--spacing-sm); text-align: left;">Full Name</th>
                            <th style="padding: var(--spacing-sm); text-align: left;">Role</th>
                            <th style="padding: var(--spacing-sm); text-align: left;">Created</th>
                            <th style="padding: var(--spacing-sm); text-align: left;">Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                <td style="padding: var(--spacing-sm);"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td style="padding: var(--spacing-sm);"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td style="padding: var(--spacing-sm);">
                                    <span class="badge" style="background: var(--primary-color);">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td style="padding: var(--spacing-sm); color: var(--text-secondary);">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td style="padding: var(--spacing-sm); color: var(--text-secondary);">
                                    <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Health Logs -->
            <div class="recent-activity" style="margin-top: var(--spacing-lg);">
                <h2>Recent Health Logs (All Users)</h2>
                <?php if (count($recent_logs) > 0): ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="activity-item">
                            <div style="display: flex; justify-content: space-between;">
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                <span class="activity-date"><?php echo date('M j, Y g:i A', strtotime($log['log_date'])); ?></span>
                            </div>
                            <div style="color: var(--text-secondary); margin-top: var(--spacing-xs);">
                                <?php if ($log['systolic_bp'] && $log['diastolic_bp']): ?>
                                    BP: <?php echo $log['systolic_bp']; ?>/<?php echo $log['diastolic_bp']; ?> mmHg
                                <?php endif; ?>
                                <?php if ($log['heart_rate']): ?>
                                    • HR: <?php echo $log['heart_rate']; ?> bpm
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--spacing-lg);">
                        No health logs yet.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Admin Actions -->
            <div style="background: var(--bg-card); padding: var(--spacing-lg); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-top: var(--spacing-lg);">
                <h2 style="margin-bottom: var(--spacing-md);">Admin Actions</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <a href="manage-access.php" class="action-btn">
                        <span class="action-icon">👥</span>
                        <span>Manage Users</span>
                    </a>
                    <a href="health-history.php" class="action-btn">
                        <span class="action-icon">📋</span>
                        <span>View All Logs</span>
                    </a>
                    <a href="analytics.php" class="action-btn">
                        <span class="action-icon">📈</span>
                        <span>System Analytics</span>
                    </a>
                    <a href="settings.php" class="action-btn">
                        <span class="action-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
