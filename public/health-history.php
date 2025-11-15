<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

// For viewers, get the patient they have access to
if ($role === 'viewer') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT patient_id FROM access_permissions WHERE viewer_id = ? AND is_active = TRUE LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['patient_id'];
    }
    $stmt->close();
    $conn->close();
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get health logs
$conn = getDBConnection();

// Count total logs
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM health_logs WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_logs = $result->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total_logs / $per_page);

// Get logs for current page
$stmt = $conn->prepare("
    SELECT * FROM health_logs 
    WHERE user_id = ? 
    ORDER BY log_date DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health History - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .history-table {
            width: 100%;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: var(--bg-primary);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        tr:hover {
            background: var(--bg-primary);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
        }
        
        .pagination a,
        .pagination span {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-primary);
            background: var(--bg-card);
            box-shadow: var(--shadow-sm);
        }
        
        .pagination a:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: white;
        }
        
        .log-details {
            font-size: 0.875rem;
            color: var(--text-secondary);
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
                <h1>📋 Health History</h1>
                <p class="dashboard-subtitle">View all your health log entries (<?php echo $total_logs; ?> total)</p>
            </div>
            
            <?php if (count($logs) > 0): ?>
                <div class="history-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Blood Pressure</th>
                                <th>Heart Rate</th>
                                <th>Mood</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($log['log_date'])); ?></strong><br>
                                        <span class="log-details"><?php echo date('g:i A', strtotime($log['log_date'])); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log['systolic_bp'] && $log['diastolic_bp']): ?>
                                            <strong><?php echo $log['systolic_bp']; ?>/<?php echo $log['diastolic_bp']; ?></strong> mmHg
                                        <?php else: ?>
                                            <span class="log-details">Not recorded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['heart_rate']): ?>
                                            <strong><?php echo $log['heart_rate']; ?></strong> bpm
                                        <?php else: ?>
                                            <span class="log-details">Not recorded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['mood']): ?>
                                            <?php
                                            $mood_emojis = [
                                                'excellent' => '😄',
                                                'good' => '🙂',
                                                'fair' => '😐',
                                                'poor' => '😟',
                                                'critical' => '😰'
                                            ];
                                            echo $mood_emojis[$log['mood']] ?? '';
                                            echo ' ' . ucfirst($log['mood']);
                                            ?>
                                        <?php else: ?>
                                            <span class="log-details">Not recorded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="log-details">
                                        <?php
                                        $details = [];
                                        if ($log['weight']) $details[] = "Weight: {$log['weight']} lbs";
                                        if ($log['temperature']) $details[] = "Temp: {$log['temperature']}°F";
                                        if ($log['sleep_hours']) $details[] = "Sleep: {$log['sleep_hours']}h";
                                        if ($log['stress_level']) $details[] = "Stress: {$log['stress_level']}/10";
                                        
                                        if (count($details) > 0) {
                                            echo implode(' • ', $details);
                                        }
                                        
                                        if ($log['symptoms']) {
                                            echo "<br><strong>Symptoms:</strong> " . htmlspecialchars(substr($log['symptoms'], 0, 50));
                                            if (strlen($log['symptoms']) > 50) echo '...';
                                        }
                                        
                                        if ($log['notes']) {
                                            echo "<br><strong>Notes:</strong> " . htmlspecialchars(substr($log['notes'], 0, 50));
                                            if (strlen($log['notes']) > 50) echo '...';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">← Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php elseif ($i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php elseif (abs($i - $page) == 3): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No health logs yet. Start by <a href="log-health.php">logging your health data</a>.
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
