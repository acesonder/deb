<?php
/**
 * Interactive Verification and Setup Dashboard
 * Provides easy-to-use buttons for configuration, testing, and verification
 */

session_start();

// Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'deb_health_tracker';

// Store results
$results = [];
$action = $_GET['action'] ?? '';

// Handle actions
if ($action) {
    switch ($action) {
        case 'test_db':
            $results['db_test'] = testDatabaseConnection();
            break;
        case 'check_permissions':
            $results['permissions'] = checkDatabasePermissions();
            break;
        case 'import_sample':
            $results['import'] = importSampleData();
            break;
        case 'clear_data':
            $results['clear'] = clearDatabase();
            break;
        case 'verify_all':
            $results['verify'] = verifyAllFiles();
            break;
        case 'check_links':
            $results['links'] = checkAllLinks();
            break;
        case 'test_forms':
            $results['forms'] = testAllForms();
            break;
    }
}

function testDatabaseConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    $result = ['status' => 'success', 'messages' => []];
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass);
        
        if ($conn->connect_error) {
            $result['status'] = 'error';
            $result['messages'][] = "❌ Connection failed: " . $conn->connect_error;
            return $result;
        }
        
        $result['messages'][] = "✅ Connected to MySQL server";
        
        // Check if database exists
        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $db_name);
        $stmt->execute();
        $db_result = $stmt->get_result();
        
        if ($db_result->num_rows > 0) {
            $result['messages'][] = "✅ Database '$db_name' exists";
            
            // Select database and check tables
            $conn->select_db($db_name);
            $tables_result = $conn->query("SHOW TABLES");
            $table_count = $tables_result->num_rows;
            $result['messages'][] = "✅ Found $table_count tables in database";
            
            // List all tables
            $tables = [];
            while ($row = $tables_result->fetch_array()) {
                $tables[] = $row[0];
            }
            $result['messages'][] = "📋 Tables: " . implode(', ', $tables);
        } else {
            $result['status'] = 'warning';
            $result['messages'][] = "⚠️ Database '$db_name' does not exist. Run setup.php first.";
        }
        
        $conn->close();
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['messages'][] = "❌ Error: " . $e->getMessage();
    }
    
    return $result;
}

function checkDatabasePermissions() {
    global $db_host, $db_user, $db_pass, $db_name;
    $result = ['status' => 'success', 'messages' => []];
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            $result['status'] = 'error';
            $result['messages'][] = "❌ Connection failed";
            return $result;
        }
        
        // Test SELECT
        $test = $conn->query("SELECT 1");
        if ($test) {
            $result['messages'][] = "✅ SELECT permission: OK";
        }
        
        // Test INSERT
        $conn->query("CREATE TEMPORARY TABLE test_insert (id INT)");
        $insert = $conn->query("INSERT INTO test_insert VALUES (1)");
        if ($insert) {
            $result['messages'][] = "✅ INSERT permission: OK";
        }
        
        // Test UPDATE
        $update = $conn->query("UPDATE test_insert SET id = 2");
        if ($update) {
            $result['messages'][] = "✅ UPDATE permission: OK";
        }
        
        // Test DELETE
        $delete = $conn->query("DELETE FROM test_insert WHERE id = 2");
        if ($delete) {
            $result['messages'][] = "✅ DELETE permission: OK";
        }
        
        $conn->close();
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['messages'][] = "❌ Error: " . $e->getMessage();
    }
    
    return $result;
}

function importSampleData() {
    global $db_host, $db_user, $db_pass, $db_name;
    $result = ['status' => 'success', 'messages' => []];
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            $result['status'] = 'error';
            $result['messages'][] = "❌ Connection failed";
            return $result;
        }
        
        // Check if user 'deb' exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = 'deb'");
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            $result['status'] = 'error';
            $result['messages'][] = "❌ User 'deb' not found. Run setup.php first.";
            return $result;
        }
        
        $user = $user_result->fetch_assoc();
        $user_id = $user['user_id'];
        
        // Generate 30 days of sample health data
        $inserted = 0;
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d H:i:s', strtotime("-$i days"));
            $systolic = rand(120, 145);
            $diastolic = rand(75, 92);
            $heart_rate = rand(65, 85);
            $weight = rand(150, 160);
            $temp = rand(975, 985) / 10; // 97.5 - 98.5
            $sleep = rand(6, 9);
            $activity = ['sedentary', 'light', 'moderate', 'active'][rand(0, 3)];
            $stress = rand(1, 10);
            $mood = ['excellent', 'good', 'fair', 'poor'][rand(0, 3)];
            $notes = "Sample health log entry for day " . (30 - $i);
            
            $stmt = $conn->prepare("INSERT INTO health_logs (user_id, log_date, systolic_bp, diastolic_bp, heart_rate, weight, temperature, sleep_hours, activity_level, stress_level, mood, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiiiddsiss", $user_id, $date, $systolic, $diastolic, $heart_rate, $weight, $temp, $sleep, $activity, $stress, $mood, $notes);
            
            if ($stmt->execute()) {
                $inserted++;
            }
        }
        
        $result['messages'][] = "✅ Inserted $inserted sample health log entries";
        
        // Insert sample medications
        $medications = [
            ['Lisinopril', '10mg', 'Once daily'],
            ['Metformin', '500mg', 'Twice daily'],
            ['Aspirin', '81mg', 'Once daily']
        ];
        
        $med_inserted = 0;
        foreach ($medications as $med) {
            $stmt = $conn->prepare("INSERT IGNORE INTO medications (user_id, medication_name, dosage, frequency, start_date, is_active) VALUES (?, ?, ?, ?, CURDATE(), TRUE)");
            $stmt->bind_param("isss", $user_id, $med[0], $med[1], $med[2]);
            if ($stmt->execute()) {
                $med_inserted++;
            }
        }
        
        $result['messages'][] = "✅ Inserted $med_inserted sample medications";
        
        $conn->close();
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['messages'][] = "❌ Error: " . $e->getMessage();
    }
    
    return $result;
}

function clearDatabase() {
    global $db_host, $db_user, $db_pass, $db_name;
    $result = ['status' => 'success', 'messages' => []];
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            $result['status'] = 'error';
            $result['messages'][] = "❌ Connection failed";
            return $result;
        }
        
        // Clear data from tables (but keep structure and users)
        $tables_to_clear = ['health_logs', 'stroke_warnings', 'alerts', 'access_permissions', 'health_goals', 'achievements', 'logging_streaks'];
        
        foreach ($tables_to_clear as $table) {
            $conn->query("DELETE FROM $table WHERE user_id != 1"); // Keep admin data
            $result['messages'][] = "✅ Cleared table: $table";
        }
        
        // Clear non-admin medications
        $conn->query("DELETE FROM medications WHERE user_id != 1");
        $result['messages'][] = "✅ Cleared medications (non-admin)";
        
        $result['messages'][] = "✅ Database cleared (users preserved)";
        
        $conn->close();
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['messages'][] = "❌ Error: " . $e->getMessage();
    }
    
    return $result;
}

function verifyAllFiles() {
    $result = ['status' => 'success', 'messages' => []];
    
    $required_files = [
        // Core files
        'config/database.php',
        'config/init_db.sql',
        'includes/auth.php',
        'setup.php',
        
        // Public pages
        'public/index.php',
        'public/login.php',
        'public/logout.php',
        'public/dashboard.php',
        'public/log-health.php',
        'public/quick-log.php',
        'public/health-history.php',
        'public/analytics.php',
        'public/analytics-enhanced.php',
        'public/medications.php',
        'public/medication-effectiveness.php',
        'public/stroke-warnings.php',
        'public/alerts.php',
        'public/goals.php',
        'public/export.php',
        'public/settings.php',
        'public/manage-access.php',
        'public/admin-panel.php',
        'public/help.php',
        
        // Includes
        'public/includes/header.php',
        'public/includes/sidebar.php',
        
        // API endpoints
        'public/api/get-latest-bp.php',
        'public/api/get-latest-hr.php',
        'public/api/get-logs-count.php',
        'public/api/get-alerts-count.php',
        'public/api/get-recent-activity.php',
        
        // Assets
        'public/css/style.css',
        'public/js/main.js',
        'public/js/dashboard.js',
        'public/js/analytics.js'
    ];
    
    $base_dir = __DIR__;
    $missing = [];
    $found = 0;
    
    foreach ($required_files as $file) {
        $full_path = $base_dir . '/' . $file;
        if (file_exists($full_path)) {
            $found++;
        } else {
            $missing[] = $file;
        }
    }
    
    $result['messages'][] = "✅ Found $found out of " . count($required_files) . " required files";
    
    if (count($missing) > 0) {
        $result['status'] = 'warning';
        $result['messages'][] = "⚠️ Missing files:";
        foreach ($missing as $file) {
            $result['messages'][] = "   - $file";
        }
    } else {
        $result['messages'][] = "✅ All required files present";
    }
    
    return $result;
}

function checkAllLinks() {
    $result = ['status' => 'success', 'messages' => []];
    $base_dir = __DIR__;
    
    // Check sidebar links
    $sidebar_file = $base_dir . '/public/includes/sidebar.php';
    if (file_exists($sidebar_file)) {
        $content = file_get_contents($sidebar_file);
        preg_match_all('/href=["\']([^"\']+)["\']/', $content, $matches);
        
        $links = $matches[1];
        $checked = 0;
        $missing = [];
        
        foreach ($links as $link) {
            // Skip external links and special links
            if (strpos($link, 'http') === 0 || strpos($link, '#') === 0 || strpos($link, 'javascript:') === 0) {
                continue;
            }
            
            $file_path = $base_dir . '/public/' . ltrim($link, '/');
            if (file_exists($file_path)) {
                $checked++;
            } else {
                $missing[] = $link;
            }
        }
        
        $result['messages'][] = "✅ Checked $checked links in sidebar";
        
        if (count($missing) > 0) {
            $result['status'] = 'warning';
            $result['messages'][] = "⚠️ Missing link targets:";
            foreach ($missing as $link) {
                $result['messages'][] = "   - $link";
            }
        }
    } else {
        $result['status'] = 'error';
        $result['messages'][] = "❌ sidebar.php not found";
    }
    
    return $result;
}

function testAllForms() {
    $result = ['status' => 'success', 'messages' => []];
    $base_dir = __DIR__;
    
    $form_files = [
        'public/log-health.php',
        'public/quick-log.php',
        'public/medications.php',
        'public/stroke-warnings.php',
        'public/settings.php'
    ];
    
    $checked = 0;
    $issues = [];
    
    foreach ($form_files as $file) {
        $full_path = $base_dir . '/' . $file;
        if (file_exists($full_path)) {
            $content = file_get_contents($full_path);
            
            // Check for form validation
            if (strpos($content, 'required') === false && strpos($content, 'validation') === false) {
                $issues[] = "$file - No validation detected";
            }
            
            $checked++;
        }
    }
    
    $result['messages'][] = "✅ Checked $checked form files";
    
    if (count($issues) > 0) {
        $result['status'] = 'warning';
        $result['messages'][] = "⚠️ Forms needing validation:";
        foreach ($issues as $issue) {
            $result['messages'][] = "   - $issue";
        }
    }
    
    return $result;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Dashboard - Deb's Health Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .button-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .action-btn {
            display: block;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .action-btn.test { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .action-btn.check { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .action-btn.import { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .action-btn.clear { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .action-btn.verify { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .action-btn.links { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
        .action-btn.forms { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333; }
        
        .results-section {
            margin-top: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 5px solid #667eea;
        }
        
        .result-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .result-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .result-box.success {
            border-left: 5px solid #28a745;
        }
        
        .result-box.warning {
            border-left: 5px solid #ffc107;
        }
        
        .result-box.error {
            border-left: 5px solid #dc3545;
        }
        
        .message {
            padding: 8px 0;
            color: #555;
            line-height: 1.6;
        }
        
        .app-link {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .app-link:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .icon {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verification Dashboard</h1>
        <p class="subtitle">Interactive Setup and Testing for Deb's Health Tracker</p>
        
        <div class="button-grid">
            <a href="?action=test_db" class="action-btn test">
                <span class="icon">🔌</span>
                Test Database Connection
            </a>
            
            <a href="?action=check_permissions" class="action-btn check">
                <span class="icon">🔐</span>
                Check Database Permissions
            </a>
            
            <a href="?action=import_sample" class="action-btn import">
                <span class="icon">📥</span>
                Import Sample Data
            </a>
            
            <a href="?action=clear_data" class="action-btn clear" onclick="return confirm('Are you sure you want to clear all data?')">
                <span class="icon">🗑️</span>
                Clear Database
            </a>
            
            <a href="?action=verify_all" class="action-btn verify">
                <span class="icon">✅</span>
                Verify All Files
            </a>
            
            <a href="?action=check_links" class="action-btn links">
                <span class="icon">🔗</span>
                Check All Links
            </a>
            
            <a href="?action=test_forms" class="action-btn forms">
                <span class="icon">📝</span>
                Test Form Validation
            </a>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="results-section">
            <h2>📊 Results</h2>
            
            <?php foreach ($results as $title => $data): ?>
            <div class="result-box <?php echo $data['status']; ?>">
                <h3><?php echo ucfirst(str_replace('_', ' ', $title)); ?></h3>
                <?php foreach ($data['messages'] as $message): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding: 20px; background: #e8f4f8; border-radius: 8px;">
            <h3>🚀 Quick Links</h3>
            <a href="setup.php" class="app-link">Run Setup Script</a>
            <a href="public/index.php" class="app-link">Go to Application</a>
            <a href="verify.php" class="app-link" style="background: #6c757d;">Refresh Dashboard</a>
        </div>
    </div>
</body>
</html>
