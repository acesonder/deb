<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $conn = getDBConnection();
    
    if ($_POST['action'] === 'mark_read') {
        $alert_id = $_POST['alert_id'] ?? 0;
        $stmt = $conn->prepare("UPDATE alerts SET is_read = TRUE WHERE alert_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $alert_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] === 'mark_all_read') {
        $stmt = $conn->prepare("UPDATE alerts SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
    header('Location: alerts.php');
    exit();
}

// Get alerts
$conn = getDBConnection();

// Unread alerts
$stmt = $conn->prepare("
    SELECT * FROM alerts 
    WHERE user_id = ? AND is_read = FALSE 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_alerts = [];
while ($row = $result->fetch_assoc()) {
    $unread_alerts[] = $row;
}
$stmt->close();

// Read alerts (last 20)
$stmt = $conn->prepare("
    SELECT * FROM alerts 
    WHERE user_id = ? AND is_read = TRUE 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$read_alerts = [];
while ($row = $result->fetch_assoc()) {
    $read_alerts[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .alerts-section {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .alerts-section h2 {
            margin-bottom: var(--spacing-md);
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-item {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            display: flex;
            gap: var(--spacing-md);
            align-items: start;
        }
        
        .alert-item.unread {
            background: var(--bg-primary);
            border-left: 4px solid var(--primary-color);
        }
        
        .alert-item.critical {
            background: #ffe6e6;
            border-left: 4px solid var(--danger);
        }
        
        .alert-item.warning {
            background: #fff4e6;
            border-left: 4px solid var(--warning);
        }
        
        .alert-icon {
            font-size: 2rem;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-message {
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .alert-time {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .alert-actions {
            display: flex;
            gap: var(--spacing-sm);
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
                <h1>🔔 Alerts & Notifications</h1>
                <p class="dashboard-subtitle">Stay informed about your health</p>
            </div>
            
            <!-- Unread Alerts -->
            <div class="alerts-section">
                <h2>
                    <span>Unread Alerts (<?php echo count($unread_alerts); ?>)</span>
                    <?php if (count($unread_alerts) > 0): ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-secondary" style="font-size: 0.875rem;">
                                Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                </h2>
                
                <?php if (count($unread_alerts) > 0): ?>
                    <?php foreach ($unread_alerts as $alert): ?>
                        <div class="alert-item unread <?php echo $alert['severity']; ?>">
                            <div class="alert-icon">
                                <?php
                                $icons = [
                                    'critical_bp' => '🩺',
                                    'warning_signs' => '⚠️',
                                    'medication_reminder' => '💊',
                                    'checkup_due' => '📅',
                                    'general' => 'ℹ️'
                                ];
                                echo $icons[$alert['alert_type']] ?? '🔔';
                                ?>
                            </div>
                            <div class="alert-content">
                                <div class="alert-message">
                                    <strong><?php echo htmlspecialchars($alert['alert_message']); ?></strong>
                                </div>
                                <div class="alert-time">
                                    <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                                </div>
                            </div>
                            <div class="alert-actions">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="alert_id" value="<?php echo $alert['alert_id']; ?>">
                                    <button type="submit" class="btn" style="background: var(--primary-color); color: white; padding: 0.5rem 1rem;">
                                        Mark as Read
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--spacing-lg);">
                        ✅ No unread alerts. You're all caught up!
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Read Alerts -->
            <?php if (count($read_alerts) > 0): ?>
                <div class="alerts-section">
                    <h2>Recent Read Alerts</h2>
                    
                    <?php foreach ($read_alerts as $alert): ?>
                        <div class="alert-item">
                            <div class="alert-icon" style="opacity: 0.5;">
                                <?php
                                $icons = [
                                    'critical_bp' => '🩺',
                                    'warning_signs' => '⚠️',
                                    'medication_reminder' => '💊',
                                    'checkup_due' => '📅',
                                    'general' => 'ℹ️'
                                ];
                                echo $icons[$alert['alert_type']] ?? '🔔';
                                ?>
                            </div>
                            <div class="alert-content">
                                <div class="alert-message" style="opacity: 0.7;">
                                    <?php echo htmlspecialchars($alert['alert_message']); ?>
                                </div>
                                <div class="alert-time">
                                    <?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Info Box -->
            <div class="alerts-section" style="background: var(--bg-primary);">
                <h3>ℹ️ About Alerts</h3>
                <ul style="color: var(--text-secondary); line-height: 1.8;">
                    <li><strong>Critical alerts</strong> require immediate attention</li>
                    <li>You'll receive alerts for high blood pressure readings</li>
                    <li>Stroke warning signs will trigger alerts</li>
                    <li>Medication reminders (coming soon) will help you stay on schedule</li>
                    <li>Check this page regularly to stay informed about your health</li>
                </ul>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
