<?php
/**
 * Setup Script for Deb's Health Tracker
 * Run this once to initialize the database and create default users
 */

// Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'deb_health_tracker';

// Default credentials
$admin_username = 'admin';
$admin_password = 'admin123'; // Change this after first login!
$deb_username = 'deb';
$deb_access_code = '80087355';

echo "<h1>Deb's Health Tracker - Setup</h1>";

// Connect to MySQL (without selecting database first)
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<p>✅ Connected to MySQL server</p>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Database '$db_name' created or already exists</p>";
} else {
    die("<p>❌ Error creating database: " . $conn->error . "</p>");
}

// Select the database
$conn->select_db($db_name);

// Read and execute the SQL file
$sql_file = __DIR__ . '/config/init_db.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Remove the CREATE DATABASE and USE statements as we've already done that
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            if ($conn->query($statement) === FALSE) {
                echo "<p>⚠️ Warning: " . $conn->error . "</p>";
            }
        }
    }
    
    echo "<p>✅ Database tables created</p>";
} else {
    die("<p>❌ SQL file not found: $sql_file</p>");
}

// Check if admin user already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create admin user
    $admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, is_active) VALUES (?, ?, 'System Administrator', 'admin', TRUE)");
    $stmt->bind_param("ss", $admin_username, $admin_hash);
    
    if ($stmt->execute()) {
        echo "<p>✅ Admin user created - Username: <strong>$admin_username</strong>, Password: <strong>$admin_password</strong></p>";
        echo "<p style='color: red;'>⚠️ <strong>IMPORTANT:</strong> Change the admin password after first login!</p>";
    }
} else {
    echo "<p>ℹ️ Admin user already exists</p>";
}

// Check if Deb's user already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $deb_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create Deb's user with the access code
    $deb_hash = password_hash($deb_access_code, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, is_active, created_by) VALUES (?, ?, 'Deb', 'patient', TRUE, 1)");
    $stmt->bind_param("ss", $deb_username, $deb_hash);
    
    if ($stmt->execute()) {
        echo "<p>✅ Deb's user created - Username: <strong>$deb_username</strong>, Access Code: <strong>$deb_access_code</strong></p>";
        
        // Create default preferences for Deb
        $deb_id = $conn->insert_id;
        $stmt = $conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
        $stmt->bind_param("i", $deb_id);
        $stmt->execute();
    }
} else {
    echo "<p>ℹ️ Deb's user already exists</p>";
}

$conn->close();

echo "<hr>";
echo "<h2>Setup Complete! 🎉</h2>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> Username: <code>admin</code>, Password: <code>admin123</code></li>";
echo "<li><strong>Deb:</strong> Username: <code>deb</code>, Access Code: <code>80087355</code></li>";
echo "</ul>";
echo "<p><a href='public/index.php' style='display: inline-block; padding: 10px 20px; background: #0077be; color: white; text-decoration: none; border-radius: 8px;'>Go to Application</a></p>";
echo "<p style='color: #ff6b6b;'><strong>Security Note:</strong> Delete this setup.php file after setup is complete!</p>";
?>
