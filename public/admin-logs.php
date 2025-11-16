<?php
/**
 * Admin Logs Console
 * View system logs and events
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

// Require admin access
requireLogin();
$current_user = getCurrentUser();

if ($current_user['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Get filter parameters
$filter_level = $_GET['level'] ?? null;
$filter_type = $_GET['type'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// Get logs
$logs = getRecentLogs($limit, $filter_level, $filter_type);
$stats = getLogStatistics(7);

// Count by level
$level_counts = [];
foreach ($stats as $stat) {
    if (!isset($level_counts[$stat['log_level']])) {
        $level_counts[$stat['log_level']] = 0;
    }
    $level_counts[$stat['log_level']] += $stat['count'];
}

include __DIR__ . '/includes/header.php';
?>

<style>
    .logs-container {
        padding: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .stat-card.info { border-left: 5px solid #17a2b8; }
    .stat-card.warning { border-left: 5px solid #ffc107; }
    .stat-card.error { border-left: 5px solid #dc3545; }
    .stat-card.critical { border-left: 5px solid #8b0000; }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #333;
    }
    
    .stat-label {
        font-size: 1rem;
        color: #666;
        text-transform: uppercase;
    }
    
    .filter-bar {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .filter-bar select,
    .filter-bar input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-right: 10px;
    }
    
    .filter-bar button {
        padding: 10px 20px;
        background: #0077be;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .logs-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .logs-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .logs-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
    }
    
    .logs-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        color: #555;
    }
    
    .logs-table tr:hover {
        background: #f8f9fa;
    }
    
    .log-level {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .log-level.info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .log-level.warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .log-level.error {
        background: #f8d7da;
        color: #721c24;
    }
    
    .log-level.critical {
        background: #8b0000;
        color: white;
    }
    
    .log-message {
        max-width: 500px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .log-details {
        font-size: 0.85rem;
        color: #999;
    }
    
    .expand-btn {
        cursor: pointer;
        color: #0077be;
        text-decoration: underline;
    }
    
    .log-expanded {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 0.9rem;
    }
</style>

<div class="main-content">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="logs-container">
        <h1>🔍 Admin Logs Console</h1>
        <p class="subtitle">System events and error logs from the past 7 days</p>
        
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-number"><?php echo $level_counts['info'] ?? 0; ?></div>
                <div class="stat-label">Info</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $level_counts['warning'] ?? 0; ?></div>
                <div class="stat-label">Warnings</div>
            </div>
            
            <div class="stat-card error">
                <div class="stat-number"><?php echo $level_counts['error'] ?? 0; ?></div>
                <div class="stat-label">Errors</div>
            </div>
            
            <div class="stat-card critical">
                <div class="stat-number"><?php echo $level_counts['critical'] ?? 0; ?></div>
                <div class="stat-label">Critical</div>
            </div>
        </div>
        
        <div class="filter-bar">
            <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                <label>Level:</label>
                <select name="level">
                    <option value="">All Levels</option>
                    <option value="info" <?php echo $filter_level === 'info' ? 'selected' : ''; ?>>Info</option>
                    <option value="warning" <?php echo $filter_level === 'warning' ? 'selected' : ''; ?>>Warning</option>
                    <option value="error" <?php echo $filter_level === 'error' ? 'selected' : ''; ?>>Error</option>
                    <option value="critical" <?php echo $filter_level === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
                
                <label>Type:</label>
                <select name="type">
                    <option value="">All Types</option>
                    <option value="form_submission" <?php echo $filter_type === 'form_submission' ? 'selected' : ''; ?>>Form Submission</option>
                    <option value="form_validation" <?php echo $filter_type === 'form_validation' ? 'selected' : ''; ?>>Form Validation</option>
                    <option value="database_error" <?php echo $filter_type === 'database_error' ? 'selected' : ''; ?>>Database Error</option>
                    <option value="login" <?php echo $filter_type === 'login' ? 'selected' : ''; ?>>Login</option>
                    <option value="logout" <?php echo $filter_type === 'logout' ? 'selected' : ''; ?>>Logout</option>
                </select>
                
                <label>Limit:</label>
                <input type="number" name="limit" value="<?php echo $limit; ?>" min="10" max="1000" style="width: 100px;">
                
                <button type="submit">Apply Filters</button>
                <a href="admin-logs.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;">Clear Filters</a>
            </form>
        </div>
        
        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Level</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>User</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                No logs found matching the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($log['log_date'])); ?></td>
                                <td>
                                    <span class="log-level <?php echo $log['log_level']; ?>">
                                        <?php echo $log['log_level']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['log_type']); ?></td>
                                <td>
                                    <div class="log-message" title="<?php echo htmlspecialchars($log['message']); ?>">
                                        <?php echo htmlspecialchars($log['message']); ?>
                                    </div>
                                    <?php if ($log['additional_data']): ?>
                                        <div class="log-details">
                                            <span class="expand-btn" onclick="toggleDetails(<?php echo $log['log_id']; ?>)">
                                                View Details ▼
                                            </span>
                                        </div>
                                        <div id="details-<?php echo $log['log_id']; ?>" class="log-expanded" style="display: none;">
                                            <strong>Additional Data:</strong>
                                            <pre><?php echo htmlspecialchars(json_encode(json_decode($log['additional_data']), JSON_PRETTY_PRINT)); ?></pre>
                                            <strong>Request URI:</strong> <?php echo htmlspecialchars($log['request_uri']); ?><br>
                                            <strong>User Agent:</strong> <?php echo htmlspecialchars(substr($log['user_agent'], 0, 100)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $log['username'] ? htmlspecialchars($log['username']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleDetails(logId) {
    const details = document.getElementById('details-' + logId);
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>

</body>
</html>
