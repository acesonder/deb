<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

// Check if this is first login (show welcome guide)
$show_welcome = !isset($_SESSION['welcome_shown']);
$_SESSION['welcome_shown'] = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Deb's Health Tracker</title>
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
                <h1>Welcome, <?php echo htmlspecialchars($full_name); ?>!</h1>
                <p class="dashboard-subtitle">Your Health Dashboard</p>
            </div>
            
            <?php if ($show_welcome): ?>
            <div class="welcome-guide" id="welcomeGuide">
                <div class="welcome-content">
                    <button class="close-welcome" onclick="closeWelcome()" aria-label="Close welcome guide">&times;</button>
                    <h2>👋 Welcome to Your Health Tracker!</h2>
                    
                    <div class="welcome-sections">
                        <div class="welcome-section">
                            <h3>📊 Dashboard Features</h3>
                            <ul>
                                <li><strong>Health Logging:</strong> Record blood pressure, heart rate, symptoms, and more</li>
                                <li><strong>Analytics:</strong> View trends and patterns in your health data</li>
                                <li><strong>Stroke Warnings:</strong> Track and monitor warning signs</li>
                                <li><strong>Medications:</strong> Manage your medication schedule</li>
                                <li><strong>Alerts:</strong> Get notified of important health changes</li>
                            </ul>
                        </div>
                        
                        <div class="welcome-section">
                            <h3>🔐 Account Management</h3>
                            <ul>
                                <li>Change your password anytime from Settings</li>
                                <li>Create viewer accounts for family members or doctors</li>
                                <li>Control who can access your health information</li>
                            </ul>
                        </div>
                        
                        <div class="welcome-section">
                            <h3>🚨 Important Reminders</h3>
                            <ul>
                                <li>Log your vitals daily for accurate tracking</li>
                                <li>Record any unusual symptoms immediately</li>
                                <li>Watch for stroke warning signs (FAST: Face, Arms, Speech, Time)</li>
                                <li>This app is a supplement to professional medical care</li>
                            </ul>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary" onclick="closeWelcome()">Get Started</button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quick Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card" id="latestBP">
                    <div class="stat-icon">💗</div>
                    <div class="stat-content">
                        <h3>Latest Blood Pressure</h3>
                        <p class="stat-value" id="bpValue">--/--</p>
                        <p class="stat-label">mmHg</p>
                    </div>
                </div>
                
                <div class="stat-card" id="latestHR">
                    <div class="stat-icon">❤️</div>
                    <div class="stat-content">
                        <h3>Latest Heart Rate</h3>
                        <p class="stat-value" id="hrValue">--</p>
                        <p class="stat-label">bpm</p>
                    </div>
                </div>
                
                <div class="stat-card" id="logsThisWeek">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3>Logs This Week</h3>
                        <p class="stat-value" id="logsCount">--</p>
                        <p class="stat-label">entries</p>
                    </div>
                </div>
                
                <div class="stat-card" id="alertsCount">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-content">
                        <h3>Unread Alerts</h3>
                        <p class="stat-value" id="alertsValue">--</p>
                        <p class="stat-label">notifications</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="log-health.php" class="action-btn">
                        <span class="action-icon">➕</span>
                        <span>Log Health Data</span>
                    </a>
                    <a href="analytics.php" class="action-btn">
                        <span class="action-icon">📈</span>
                        <span>View Analytics</span>
                    </a>
                    <a href="medications.php" class="action-btn">
                        <span class="action-icon">💊</span>
                        <span>Manage Medications</span>
                    </a>
                    <a href="stroke-warnings.php" class="action-btn">
                        <span class="action-icon">⚠️</span>
                        <span>Stroke Warnings</span>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h2>Recent Activity</h2>
                <div id="recentActivityList">
                    <p class="loading">Loading recent activity...</p>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
